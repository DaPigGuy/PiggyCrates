<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates\crates;

use DaPigGuy\PiggyCrates\PiggyCrates;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;

/**
 * Class Crate
 * @package DaPigGuy\PiggyCrates\crates
 */
class Crate
{
    /** @var PiggyCrates */
    private $plugin;

    /** @var string */
    public $name;
    /** @var CrateItem[] */
    public $drops;
    /** @var string[] */
    public $commands;

    /**
     * Crate constructor.
     * @param PiggyCrates $plugin
     * @param string $name
     * @param CrateItem[] $drops
     * @param string[] $commands
     */
    public function __construct(PiggyCrates $plugin, string $name, array $drops, array $commands)
    {
        $this->plugin = $plugin;
        $this->name = $name;
        $this->drops = $drops;
        $this->commands = $commands;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return CrateItem[]
     */
    public function getDrops(): array
    {
        return $this->drops;
    }

    /**
     * @return string[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @param int $amount
     * @return Item
     */
    public function getDrop(int $amount): Item
    {
        $dropTable = [];
        foreach ($this->drops as $drop) {
            for ($i = 0; $i < $drop->getChance(); $i++) {
                $dropTable[] = $drop->getItem();
            }
        }

        $keys = array_rand($dropTable, $amount);
        if (!is_array($keys)) $keys = [$keys];
        return array_map(function ($key) use ($dropTable) {
            return $dropTable[$key];
        }, $keys);
    }

    /**
     * @param Player $player
     * @param int $amount
     */
    public function giveKey(Player $player, int $amount): void
    {
        $key = Item::get($this->plugin->getConfig()->getNested("key.id"), $this->plugin->getConfig()->getNested("key.meta"), $amount);
        $key->setCustomName(ucfirst(str_replace("{CRATE}", $this->getName(), $this->getName() . $this->plugin->getConfig()->getNested("key.name"))));
        $key->setLore([str_replace("{CRATE}", $this->getName(), $this->plugin->getConfig()->getNested("key.lore"))]);
        $key->setNamedTagEntry(new ListTag(Item::TAG_ENCH));
        $key->setNamedTagEntry(new StringTag("KeyType", $this->getName()));
        $player->getInventory()->addItem($key);
    }
}