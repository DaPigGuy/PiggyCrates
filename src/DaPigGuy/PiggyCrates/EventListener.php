<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates;

use DaPigGuy\PiggyCrates\tiles\CrateTile;
use pocketmine\block\Block;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\inventory\ChestInventory;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;

/**
 * Class EventListener
 * @package DaPigGuy\PiggyCrates
 */
class EventListener implements Listener
{
    /** @var PiggyCrates */
    private $plugin;

    /**
     * EventListener constructor.
     * @param PiggyCrates $plugin
     */
    public function __construct(PiggyCrates $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $player->getInventory()->getItemInHand();
        if ($block->getId() === Block::CHEST) {
            $tile = $block->getLevel()->getTile($block);
            if ($tile instanceof CrateTile) {
                if ($tile->getCrateType()->isValidKey($item)) {
                    $tile->open($player, $item);
                }
                $event->setCancelled();
            } elseif ($tile instanceof Chest) {
                if (PiggyCrates::inCrateCreationMode($player)) {
                    $nbt = $tile->getSpawnCompound();
                    $nbt->setString("CrateType", PiggyCrates::getCrateToCreate($player)->getName());
                    /** @var CrateTile $newTile */
                    $newTile = Tile::createTile("CrateTile", $event->getBlock()->getLevel(), $nbt);
                    $newTile->spawnToAll();
                    $tile->close();
                    $player->sendMessage(TextFormat::GREEN . PiggyCrates::getCrateToCreate($player)->getName() . " Crate created.");
                    PiggyCrates::setInCrateCreationMode($player, null);
                }
            }
        }
    }

    /**
     * @param InventoryTransactionEvent $event
     */
    public function onTransaction(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        $inventories = $transaction->getInventories();
        foreach ($inventories as $inventory) {
            if ($inventory instanceof ChestInventory && $inventory->getHolder() instanceof CrateTile) {
                $event->setCancelled();
            }
        }
    }
}