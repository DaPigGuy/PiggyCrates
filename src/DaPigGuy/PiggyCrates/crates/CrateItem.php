<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates\crates;

use pocketmine\item\Item;

class CrateItem
{
    /**
     * @param string[] $commands
     */
    public function __construct(public Item $item, public string $type, public array $commands, public int $chance)
    {
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function getType(): string
    {
        return $this->type;
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