<?php

namespace PiggyCrates;

use PiggyCrates\Tasks\DropsTask;
use PiggyCustomEnchants\CustomEnchants\CustomEnchants;
use PiggyCustomEnchants\Main as CE;
use pocketmine\block\Block;
use pocketmine\command\ConsoleCommandSender;
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
    /** @var Main */
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
                    $possibleDrops = $this->plugin->getCrateDrops($type);
                    $drops = [];
                    foreach ($possibleDrops as $possibleDrop => $values) {
                        $chance = 10;
                        if (isset($values["chance"])) $chance = $values["chance"];
                        $drops = array_merge($drops, array_fill(0, $chance, $values));
                    }
                    $pickedDrops = array_rand($drops, $this->plugin->getCrateDropAmount($type));
                    if (!is_array($pickedDrops)) {
                        $pickedDrops = [$pickedDrops];
                    }

                    $dropsReceivable = [];
                    foreach ($pickedDrops as $pickedDrop) {
                        $values = $drops[$pickedDrop];
                        $i = Item::get($values["id"], $values["meta"], $values["amount"]);
                        $i->setCustomName($values["name"]);
                        if (isset($values["enchantments"])) {
                            foreach ($values["enchantments"] as $enchantment => $enchantmentinfo) {
                                $level = $enchantmentinfo["level"];
                                /** @var CE $ce */
                                $ce = $this->plugin->getServer()->getPluginManager()->getPlugin("PiggyCustomEnchants");
                                if (!is_null($ce) && !is_null($enchant = CustomEnchants::getEnchantmentByName($enchantment))) {
                                    $i = $ce->addEnchantment($i, $enchantment, $level);
                                } else {
                                    if (!is_null($enchant = Enchantment::getEnchantmentByName($enchantment))) {
                                        $i->addEnchantment(new EnchantmentInstance($enchant, $level));
                                    }
                                }
                            }
                        }
                        if (isset($values["lore"])) {
                            $i->setLore([$values["lore"]]);
                        }
                        $dropsReceivable[$pickedDrop] = $player->getInventory()->canAddItem($i);
                    }
                    if (array_search(false, $dropsReceivable)) {
                        $player->sendTip(TextFormat::RED . "Please clear your inventory.");
                    } else {
                        if ($this->plugin->getCrateDropDelay($type) <= 0) {
                            $list = [];
                            $items = [];
                            foreach ($pickedDrops as $pickedDrop) {
                                $values = $drops[$pickedDrop];
                                $list[] = $values["amount"] . " " . $values["name"];
                                $i = Item::get($values["id"], $values["meta"], $values["amount"]);
                                $i->setCustomName($values["name"]);
                                if (isset($values["enchantments"])) {
                                    foreach ($values["enchantments"] as $enchantment => $enchantmentinfo) {
                                        $level = $enchantmentinfo["level"];
                                        /** @var CE $ce */
                                        $ce = $this->plugin->getServer()->getPluginManager()->getPlugin("PiggyCustomEnchants");
                                        if (!is_null($ce) && !is_null($enchant = CustomEnchants::getEnchantmentByName($enchantment))) {
                                            $i = $ce->addEnchantment($i, $enchantment, $level);
                                        } else {
                                            if (!is_null($enchant = Enchantment::getEnchantmentByName($enchantment))) {
                                                $i->addEnchantment(new EnchantmentInstance($enchant, $level));
                                            }
                                        }
                                    }
                                }
                                if (isset($values["lore"])) {
                                    $i->setLore([$values["lore"]]);
                                }
                                if (isset($values["command"])) {
                                    $cmd = $values["command"];
                                    $cmd = str_replace(["%PLAYER%"], [$player->getName()], $cmd);
                                    $this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), $cmd);
                                }
                                $items[] = $i;
                                $particles = "pocketmine\\level\\particle\\" . ucfirst($this->plugin->getCrateDropParticle($type)) . "Particle";
                                if (class_exists($particles)) {
                                    $block->getLevel()->addParticle(new $particles($block->add(0, 2)));
                                }
                            }
                            $player->getInventory()->removeItem($item->setCount(1));
                            $player->getInventory()->addItem(...$items);
                            $player->sendTip(TextFormat::GREEN . "You have received " . implode(", ", $list));
                        } else {
                            $task = new DropsTask($this->plugin, $player, $block, $item, $type, $drops, $pickedDrops);
                            $handler = $this->plugin->getScheduler()->scheduleRepeatingTask($task, 20);
                            $task->setHandler($handler);
                        }
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
