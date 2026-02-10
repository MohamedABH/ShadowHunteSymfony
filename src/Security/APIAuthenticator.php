<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class APIAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization') 
            || $request->cookies->has('jwt_token');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        // Try to get token from Authorization header first, then from cookie
        $token = null;
        if ($request->headers->has('Authorization') && str_starts_with($request->headers->get('Authorization'), 'Bearer ')) {
            $token = substr($request->headers->get('Authorization'), 7);
        } elseif ($request->cookies->has('jwt_token')) {
            $token = $request->cookies->get('jwt_token');
        }
        
        if (!$token) {
            throw new AuthenticationException('No token provided');
        }
        
        try {
            $payload = $this->jwtManager->parse($token);
            $identifier = $payload['username'] ?? null;
            
            if (!$identifier) {
                throw new AuthenticationException('Invalid token');
            }
        } catch (\Exception $e) {
            throw new AuthenticationException('Invalid token: ' . $e->getMessage());
        }

        return new SelfValidatingPassport(new UserBadge($identifier));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response(
            'Authentication Failed: ' . $exception->getMessage(),
            Response::HTTP_UNAUTHORIZED
        );
    }

    //    public function start(Request $request, ?AuthenticationException $authException = null): Response
    //    {
    //        /*
    //         * If you would like this class to control what happens when an anonymous user accesses a
    //         * protected page (e.g. redirect to /login), uncomment this method and make this class
    //         * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
    //         *
    //         * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
    //         */
    //    }
}
