<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates;

use DaPigGuy\PiggyCrates\tiles\CrateTile;
use pocketmine\block\Block;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\inventory\ChestInventory;

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