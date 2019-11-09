<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates\crates;

use pocketmine\item\Item;

/**
 * Class CrateItem
 * @package DaPigGuy\PiggyCrates\crates
 */
class CrateItem
{
    /** @var Item */
    public $item;
    /** @var string[] */
    public $commands;
    /** @var int */
    public $chance;

    /**
     * CrateItem constructor.
     * @param Item $item
     * @param string[] $commands
     * @param int $chance
     */
    public function __construct(Item $item, array $commands, int $chance)
    {
        $this->item = $item;
        $this->commands = $commands;
        $this->chance = $chance;
    }

    /**
     * @return Item
     */
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

    /**
     * @return int
     */
    public function getChance(): int
    {
        return $this->chance;
    }
}