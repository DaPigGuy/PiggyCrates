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
    /** @var int */
    public $chance;

    /**
     * CrateItem constructor.
     * @param Item $item
     * @param int $chance
     */
    public function __construct(Item $item, int $chance)
    {
        $this->item = $item;
        $this->chance = $chance;
    }

    /**
     * @return Item
     */
    public function getItem(): Item
    {
        return $this->getItem();
    }

    /**
     * @return int
     */
    public function getChance(): int
    {
        return $this->chance;
    }
}