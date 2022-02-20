<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates\crates;

use pocketmine\item\Item;

class CrateItem
{
    public Item $item;
    public string $type;
    /** @var string[] */
    public array $commands;
    public int $chance;

    /**
     * @param string[] $commands
     */
    public function __construct(Item $item, string $type, array $commands, int $chance)
    {
        $this->item = $item;
        $this->type = $type;
        $this->commands = $commands;
        $this->chance = $chance;
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