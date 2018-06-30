<?php

namespace PiggyCrates;

use PiggyCrates\Commands\KeyCommand;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

/**
 * Class Main
 * @package PiggyCrates
 */
class Main extends PluginBase
{
    /** @var string */
    private $key;
    /** @var string */
    private $keyLore;
    /** @var bool */
    private $allowCrateChanges;
    /** @var array */
    private $crates;
    /** @var array */
    private $crateDrops;
    /** @var array */
    private $crateBlocks;

    public function onEnable()
    {
        $this->initCrates();
        $this->saveDefaultConfig();
        $this->key = $this->getConfig()->getNested("key");
        $this->keyLore = $this->getConfig()->getNested("key-lore");
        $this->allowCrateChanges = $this->getConfig()->getNested("allow-crate-changes");
        $this->getServer()->getCommandMap()->register("key", new KeyCommand("key", $this), "key");
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getLogger()->info(TextFormat::GREEN . "Enabled.");
    }

    public function initCrates()
    {
        $this->saveResource("crates.yml");
        $file = new Config($this->getDataFolder() . "crates.yml");
        foreach ($file->getNested("crates") as $type => $values) {
            $this->crates[$type] = $values;
            $this->crateDrops[$type] = $values["drops"];
            $this->crateBlocks[$values["block"]] = $type;
        }
    }

    /**
     * Returns true if allow-crate-changes is enabled.
     *
     * @return bool
     */
    public function canChangeCrates()
    {
        return $this->allowCrateChanges;
    }

    /**
     * Get a crate type's data. Returns false if crate type does not exist.
     *
     * @param string $type
     * @return array|false
     */
    public function getCrateType(string $type)
    {
        return isset($this->crates[$type]) ? $this->crates[$type] : false;
    }

    /**
     * Returns array of all crate type names
     *
     * @return array
     */
    public function getCrateTypes()
    {
        return array_keys($this->crates);
    }

    /**
     * Returns array of drops. Returns null if crate type does not exist.
     *
     * @param string $type
     * @return null|array
     */
    public function getCrateDrops(string $type)
    {
        return !$this->getCrateType($type) ? null : $this->crateDrops[$type];
    }

    /**
     * Get the amount of items that a crate drops
     *
     * @param string $type
     * @return int
     */
    public function getCrateDropAmount(string $type)
    {
        return !$this->getCrateType($type) ? 0 : $this->crates[$type]["amount"];
    }

    /**
     * Get the delay between crate drops
     *
     * @param string $type
     * @return int
     */
    public function getCrateDropDelay(string $type)
    {
        return !$this->getCrateType($type) ? 0 : (isset($this->crates[$type]["delay"]) ? $this->crates[$type]["delay"] : 0);
    }

    /**
     * Get the particles on crate drops
     *
     * @param string $type
     * @return int
     */
    public function getCrateDropParticle(string $type)
    {
        return !$this->getCrateType($type) ? 0 : (isset($this->crates[$type]["particle"]) ? $this->crates[$type]["particle"] : "none");
    }

    /**
     * Returns string of block id & meta. Returns null if crate type does not exist.
     *
     * @param string $type
     * @return null|string
     */
    public function getCrateBlock(string $type)
    {
        return !$this->getCrateDrops($type) ? null : $this->crateBlocks[$type];
    }

    /**
     * Check if the id & meta of a block matches that of a crate block
     *
     * @param int $id
     * @param int $meta
     * @return bool
     */
    public function isCrateBlock(int $id, int $meta)
    {
        return isset($this->crateBlocks[$id . ":" . $meta]) ? $this->crateBlocks[$id . ":" . $meta] : false;
    }

    /**
     * Checks if item is a crate key
     *
     * @param Item $item
     * @return bool|NamedTag
     */
    public function isCrateKey(Item $item)
    {
        $values = explode(":", $this->key);
        return (isset($values[0]) && isset($values[1]) && $values[0] == $item->getId() && $values[1] == $item->getDamage() && !is_null($keytype = $item->getNamedTagEntry("KeyType"))) ? $keytype->getValue() : false;
    }

    /**
     * Gives player a key of a certain crate type. Returns false if crate type does not exist.
     *
     * @param Player $player
     * @param int $amount
     * @param string $type
     * @return bool
     */
    public function giveKey(Player $player, int $amount, string $type)
    {
        if (is_null($this->getCrateDrops($type))) {
            return false;
        }
        $values = explode(":", $this->key);
        if (!isset($values[0]) || !isset($values[1])) {
            return false;
        }
        $key = Item::get($values[0], $values[1], $amount);
        $key->addEnchantment(new EnchantmentInstance(new Enchantment(255, "", Enchantment::RARITY_COMMON, Enchantment::SLOT_ALL, Enchantment::SLOT_ALL, 1))); //Glowing key effect
        $key->setCustomName(ucfirst($type . " Key"));
        $key->setLore([$this->keyLore]);
        $key->setNamedTagEntry(new StringTag("KeyType", $type));
        $player->getInventory()->addItem($key);
        return true;
    }
}
