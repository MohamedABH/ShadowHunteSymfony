# Card Effect System Documentation

## Overview

The card effect system uses the **Strategy Pattern** to handle different action card effects. Each card has its own handler class that implements the card's specific logic.

## Architecture

### Core Components

1. **CardEffectHandlerInterface**: Contract for all card handlers
2. **CardEffectResult**: Encapsulates the result of a card effect
3. **CardEffectService**: Orchestrates handler selection and execution
4. **Handler Classes**: Individual implementations for each card

## How It Works

### 1. Create a New Card Handler

```php
<?php
namespace App\Service\CardEffect\Handler;

use App\Service\CardEffect\CardEffectHandlerInterface;
use App\Service\CardEffect\CardEffectResult;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.card_effect_handler')]
class MyCardHandler implements CardEffectHandlerInterface
{
    public function supports(ActionCard $card): bool
    {
        return $card->getName() === 'My Card Name';
    }
    
    public function getRequiredContext(): array
    {
        return ['targetPlayerId', 'diceRoll']; // What info is needed
    }
    
    public function execute(ActionCard $card, Player $player, Game $game, array $context = []): CardEffectResult
    {
        // Implement card logic here
        return CardEffectResult::success('Card played successfully', ['changes' => 'data']);
    }
}
```

### 2. Handler Results

**Simple Success:**
```php
return CardEffectResult::success('Healed 2 damage', [
    'player_id' => $player->getId(),
    'damage_after' => $newDamage
]);
```

**Failure:**
```php
return CardEffectResult::failure('Target player not found');
```

**Requires Additional Action:**
```php
return CardEffectResult::requiresAction(
    'Player must choose an option',
    ['required' => ['playerChoice'], 'choices' => ['option1', 'option2']]
);
```

### 3. Use in Controller/Service

```php
public function playCard(ActionCard $card, Player $player, array $context)
{
    $result = $this->cardEffectService->executeCardEffect($card, $player, $game, $context);
    
    if ($result->hasPendingActions()) {
        // Return to client, asking for more info
        return ['status' => 'pending', 'required' => $result->getPendingActions()];
    }
    
    if ($result->isSuccess()) {
        return ['status' => 'success', 'changes' => $result->getChanges()];
    }
}
```

## Example Handlers Implemented

1. **EauBeniteHandler**: Simple heal effect
2. **ChauveSourisVampireHandler**: Damage target, heal self (requires target)
3. **VisionFurtiveHandler**: Conditional effect with player choice
4. **PoupeeDemoniaqueHandler**: Dice-based random outcome
5. **DefaultCardHandler**: Fallback for unimplemented cards

## Flow for Complex Cards

For cards requiring player interaction (like "Vision furtive"):

1. **First call**: Client provides `targetPlayerId`
2. **Handler checks**: Is target Hunter/Shadow?
3. **Returns**: `requiresAction` with choices `['give_equipment', 'take_damage']`
4. **Client responds**: User selects choice
5. **Second call**: Client provides `targetChoice`
6. **Handler executes**: Based on choice
7. **Returns**: `success` with changes

## Benefits

- **Type-safe**: Full IDE support and type checking
- **Testable**: Each handler can be unit tested
- **Extensible**: Add new handlers without modifying existing code
- **Flexible**: Handles complex logic, conditionals, and multi-step interactions
- **Maintainable**: Each card's logic is isolated
- **Auto-discovery**: Uses Symfony's autoconfigure tags

## Adding New Cards

1. Create handler class in `src/Service/CardEffect/Handler/`
2. Add `#[AutoconfigureTag('app.card_effect_handler')]` attribute
3. Implement `supports()`, `getRequiredContext()`, and `execute()`
4. No service configuration needed (auto-discovered)
