# Card Effect System Documentation

## Overview

The card effect system uses the **Strategy Pattern** with one shared execution service for all cards extending `AbstractCard`.

## Current Architecture

### Core Components

1. **AbstractCardEffectHandlerInterface** (`src/Service/AbstractCardEffect/AbstractCardEffectHandlerInterface.php`)
2. **AbstractCardEffectResult** (`src/Service/AbstractCardEffect/AbstractCardEffectResult.php`)
3. **AbstractCardEffectService** (`src/Service/AbstractCardEffect/AbstractCardEffectService.php`)
4. **Handler classes** under:
    - `src/Service/AbstractCardEffect/Handler/ActionCard/`
    - `src/Service/AbstractCardEffect/Handler/PlaceCard/`
    - `src/Service/AbstractCardEffect/Handler/CharacterCard/`

### Handler Discovery

All handlers are auto-registered with:

```php
#[AutoconfigureTag('app.abstract_card_effect_handler')]
```

## How It Works

### 1. Create a New Handler

```php
<?php
namespace App\Service\AbstractCardEffect\Handler\ActionCard;

use App\Entity\AbstractCard;
use App\Entity\ActionCard;
use App\Entity\Game;
use App\Entity\Player;
use App\Service\AbstractCardEffect\AbstractCardEffectHandlerInterface;
use App\Service\AbstractCardEffect\AbstractCardEffectResult;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.abstract_card_effect_handler')]
class MyActionCardHandler implements AbstractCardEffectHandlerInterface
{
    public function supports(AbstractCard $card): bool
    {
        return $card instanceof ActionCard && $card->getName() === 'My Card Name';
    }

    public function getRequiredContext(): array
    {
        return ['targetPlayerId'];
    }

    public function execute(AbstractCard $card, Player $player, Game $game, array $context = []): AbstractCardEffectResult
    {
        if (!$card instanceof ActionCard) {
            return AbstractCardEffectResult::failure('Unsupported card type');
        }

        return AbstractCardEffectResult::success('Card played successfully', ['changes' => 'data']);
    }
}
```

### 2. Result Types

**Success:**

```php
return AbstractCardEffectResult::success('Effect applied', ['player_id' => $player->getId()]);
```

**Failure:**

```php
return AbstractCardEffectResult::failure('Target player not found');
```

**Pending action (extra input required):**

```php
return AbstractCardEffectResult::requiresAction(
    'Additional information required',
    ['required' => ['targetChoice']]
);
```

### 3. Service Usage

```php
$result = $this->abstractCardEffectService->executeCardEffect($card, $player, $game, $context);
```

## Existing ActionCard Handlers

- `EauBeniteHandler`
- `ChauveSourisVampireHandler`
- `VisionFurtiveHandler`
- `PoupeeDemoniaqueHandler`
- `DefaultCardHandler`

These are now located in `src/Service/AbstractCardEffect/Handler/ActionCard/`.

## Adding New Cards

1. Create handler class in the correct folder:
    - Action card: `src/Service/AbstractCardEffect/Handler/ActionCard/`
    - Place card: `src/Service/AbstractCardEffect/Handler/PlaceCard/`
2. Add `#[AutoconfigureTag('app.abstract_card_effect_handler')]`
3. Implement `supports()`, `getRequiredContext()`, and `execute()`
4. No manual service registration required
