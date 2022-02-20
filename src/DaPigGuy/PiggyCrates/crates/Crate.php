<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates\crates;

use DaPigGuy\PiggyCrates\PiggyCrates;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;

class Crate
{
    /** @var PiggyCrates */
    private PiggyCrates $plugin;

    /** @var string */
    public string $name;
    /** @var string */
    public string $floatingText;
    /** @var CrateItem[] */
    public array $drops;
    /** @var int */
    public int $dropCount;
    /** @var string[] */
    public array $commands;

    /**
     * @param CrateItem[] $drops
     * @param string[] $commands
     */
    public function __construct(PiggyCrates $plugin, string $name, string $floatingText, array $drops, int $dropCount, array $commands)
    {
        $this->plugin = $plugin;
        $this->name = $name;
        $this->floatingText = $floatingText;
        $this->drops = $drops;
        $this->dropCount = $dropCount;
        $this->commands = $commands;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFloatingText(): string
    {
        return $this->floatingText;
    }

    /**
     * @return CrateItem[]
     */
    public function getDrops(): array
    {
        return $this->drops;
    }

    /**
     * @return CrateItem[]
     */
    public function getDrop(int $amount): array
    {
        $dropTable = [];
        foreach ($this->drops as $drop) {
            for ($i = 0; $i < $drop->getChance(); $i++) {
                $dropTable[] = $drop;
            }
        }

        $keys = array_rand($dropTable, $amount);
        if (!is_array($keys)) $keys = [$keys];
        return array_map(function ($key) use ($dropTable) {
            return $dropTable[$key];
        }, $keys);
    }

    public function getDropCount(): int
    {
        return $this->dropCount;
    }

    /**
     * @return string[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function giveKey(Player $player, int $amount): void
    {
        $key = ItemFactory::getInstance()->get((int)$this->plugin->getConfig()->getNested("keys.id"), (int)$this->plugin->getConfig()->getNested("keys.meta"), $amount);
        $key->setCustomName(ucfirst(str_replace("{CRATE}", $this->getName(), $this->plugin->getConfig()->getNested("keys.name"))));
        $key->setLore([str_replace("{CRATE}", $this->getName(), $this->plugin->getConfig()->getNested("keys.lore"))]);
        $key->getNamedTag()->setString("KeyType", $this->getName());
        $player->getInventory()->addItem($key);
    }

    public function isValidKey(Item $item): bool
    {
        return $item->getId() === (int)$this->plugin->getConfig()->getNested("keys.id") &&
            $item->getMeta() === (int)$this->plugin->getConfig()->getNested("keys.meta") &&
            $item->getNamedTag()->getString("KeyType") === $this->getName();
    }
}