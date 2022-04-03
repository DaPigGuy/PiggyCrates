<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates;

use DaPigGuy\PiggyCrates\tiles\CrateTile;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\tile\Chest;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;

class EventListener implements Listener
{
    public function __construct(private PiggyCrates $plugin)
    {
    }

    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $world = $block->getPosition()->getWorld();
        $item = $player->getInventory()->getItemInHand();

        if ($block->getId() === BlockLegacyIds::CHEST) {
            $tile = $world->getTile($block->getPosition());
            if ($tile instanceof CrateTile) {
                if ($tile->getCrateType() === null) {
                    $player->sendTip($this->plugin->getMessage("crates.error.invalid-crate"));
                } elseif ($tile->getCrateType()->isValidKey($item)) {
                    $tile->openCrate($player, $item);
                } elseif ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
                    if (PiggyCrates::getInstance()->getConfig()->get("preview-crate", true) === true){
                        $tile->previewCrate($player);
                    }
                }
                $event->cancel();
                return;
            }
            if ($tile instanceof Chest) {
                if (($crate = $this->plugin->getCrateToCreate($player)) !== null) {
                    $newTile = new CrateTile($world, $block->getPosition());
                    $newTile->setCrateType($crate);
                    $tile->close();
                    $world->addTile($newTile);
                    $player->sendMessage($this->plugin->getMessage("crates.success.crate-created", ["{CRATE}" => $crate->getName()]));
                    $this->plugin->setInCrateCreationMode($player, null);
                    $event->cancel();
                    return;
                }
            }
        }
        if ($item->getNamedTag()->getTag("KeyType") !== null) $event->cancel();
    }
}
