<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates;

use DaPigGuy\PiggyCrates\tiles\CrateTile;
use pocketmine\block\Block;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\inventory\ChestInventory;
use pocketmine\level\Level;
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
        /** @var Level $level */
        $level = $block->getLevel();
        $item = $player->getInventory()->getItemInHand();
        if ($block->getId() === Block::CHEST) {
            $tile = $level->getTile($block);
            if ($tile instanceof CrateTile) {
                if ($tile->getCrateType() === null) {
                    $player->sendTip(TextFormat::RED . "Invalid or missing crate type.");
                } elseif ($tile->getCrateType()->isValidKey($item)) {
                    $tile->openCrate($player, $item);
                }
                $event->setCancelled();
                return;
            }
            if ($tile instanceof Chest) {
                if (($crate = PiggyCrates::getCrateToCreate($player)) !== null) {
                    $nbt = $tile->getSpawnCompound();
                    $nbt->setString("CrateType", $crate->getName());
                    /** @var CrateTile $newTile */
                    $newTile = Tile::createTile("CrateTile", $level, $nbt);
                    $newTile->spawnToAll();
                    $tile->close();
                    $player->sendMessage(TextFormat::GREEN . $crate->getName() . " Crate created.");
                    PiggyCrates::setInCrateCreationMode($player, null);
                    $event->setCancelled();
                    return;
                }
            }
        }
        if ($item->getNamedTagEntry("KeyType") !== null) $event->setCancelled();
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