<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates\crates;

use pocketmine\item\Item;

class CrateItem
{
    /** @var Item */
    public $item;
    /** @var string[] */
    public $commands;
    /** @var int */
    public $chance;

    /**
     * @param string[] $commands
     */
    public function __construct(Item $item, array $commands, int $chance)
    {
        $this->item = $item;
        $this->commands = $commands;
        $this->chance = $chance;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    /**
     * @return string[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function getChance(): int
    {
        return $this->chance;
    }
}