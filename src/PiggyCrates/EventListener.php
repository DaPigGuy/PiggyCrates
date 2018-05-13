<?php

namespace PiggyCrates;


use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

/**
 * Class EventListener
 * @package PiggyCrates
 */
class EventListener implements Listener
{
    private $plugin;

    /**
     * EventListener constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param BlockBreakEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if (!$this->plugin->canChangeCrates()) {
            if ($this->plugin->isCrateBlock($block->getId(), $block->getDamage())) {
                if ($block->getLevel()->getBlock($block->add(0, 1))->getId() == Block::CHEST) {
                    if (!$player->hasPermission("piggycrates.crates.destroy")) {
                        $player->sendMessage(TextFormat::RED . "You do not have permission to destroy a crate.");
                        $event->setCancelled();
                    }
                }
            } elseif ($block->getId() == Block::CHEST) {
                $b = $block->getLevel()->getBlock($block->subtract(0, 1));
                if ($this->plugin->isCrateBlock($b->getId(), $b->getDamage())) {
                    if (!$player->hasPermission("piggycrates.crates.destroy")) {
                        $player->sendMessage(TextFormat::RED . "You do not have permission to destroy a crate.");
                        $event->setCancelled();
                    }
                }
            }
        }
    }

    /**
     * @param BlockPlaceEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if (!$this->plugin->canChangeCrates()) {
            if ($this->plugin->isCrateBlock($block->getId(), $block->getDamage())) {
                if ($block->getLevel()->getBlock($block->add(0, 1))->getId() == Block::CHEST) {
                    if (!$player->hasPermission("piggycrates.crates.create")) {
                        $player->sendMessage(TextFormat::RED . "You do not have permission to create a crate.");
                        $event->setCancelled();
                    }
                }
            } elseif ($block->getId() == Block::CHEST) {
                $b = $block->getLevel()->getBlock($block->subtract(0, 1));
                if ($this->plugin->isCrateBlock($b->getId(), $b->getDamage())) {
                    if (!$player->hasPermission("piggycrates.crates.create")) {
                        $player->sendMessage(TextFormat::RED . "You do not have permission to create a crate.");
                        $event->setCancelled();
                    }
                }
            }
        }
    }

    /**
     * @param PlayerInteractEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled false
     */
    public function onInteract(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $b = $block->getLevel()->getBlock($block->subtract(0, 1));
        $item = $event->getItem();
        if ($block->getId() == Block::CHEST && ($type = $this->plugin->isCrateBlock($b->getId(), $b->getDamage())) !== false) {
            if (!$player->hasPermission("piggycrates.crates.use")) {
                $player->sendMessage(TextFormat::RED . "You do not have permission to use a crate.");
            } else {
                if (!($keytype = $this->plugin->isCrateKey($item)) || $keytype !== $type) {
                    $player->sendMessage(TextFormat::RED . "You require a " . ucfirst($type) . " key to open this crate.");
                } else {
                    $drops = array_rand($this->plugin->getCrateDrops($type), $this->plugin->getCrateDropAmount($type));
                    if (!is_array($drops)) {
                        $drops = [$drops];
                    }
                    $list = [];
                    $items = [];
                    $dropsReceivable = [];
                    foreach ($drops as $drop) {
                        $values = $this->plugin->getCrateDrops($type)[$drop];
                        $list[] = $values["amount"] . " " . $values["name"];
                        $i = Item::get($values["id"], $values["meta"], $values["amount"]);
                        $i->setCustomName($values["name"]);
                        if (isset($values["enchantments"])) {
                            foreach ($values["enchantments"] as $enchantment => $enchantmentinfo) {
                                $level = $enchantmentinfo["level"];
                                if (!is_null($ce = $this->plugin->getServer()->getPluginManager()->getPlugin("PiggyCustomEnchants")) && !is_null($enchant = \PiggyCustomEnchants\CustomEnchants\CustomEnchants::getEnchantmentByName($enchantment))) {
                                    $i = $ce->addEnchantment($i, $enchantment, $level);
                                } else {
                                    if (!is_null($enchant = Enchantment::getEnchantmentByName($enchantment))) {
                                        $i->addEnchantment(new EnchantmentInstance($enchant, $level));
                                    }
                                }
                            }
                        }
                        $dropsReceivable[$drop] = $player->getInventory()->canAddItem($i);
                        $items[] = $i;
                    }
                    if(array_search(false, $dropsReceivable) === false){
                        $player->getInventory()->removeItem($item->setCount(1));
                        $player->getInventory()->addItem(...$items);
                        $player->sendTip(TextFormat::GREEN . "You have received " . implode(", ", $list));
                    }else{
                        $player->sendTip(TextFormat::RED ."Please clear your inventory.");
                    }
                }
            }
            $event->setCancelled();
        }
        if ($this->plugin->isCrateKey($item) !== false) {
            $event->setCancelled();
        }
    }
}
