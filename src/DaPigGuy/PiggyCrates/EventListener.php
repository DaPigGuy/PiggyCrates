<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates;

use pocketmine\event\Listener;

/**
 * Class EventListener
 * @package DaPigGuy\PiggyCrates
 */
class EventListener implements Listener
{
    /** @var PiggyCrates */
    private $plugin;

    /**
     * EventListener constructor.
     * @param PiggyCrates $plugin
     */
    public function __construct(PiggyCrates $plugin)
    {
        $this->plugin = $plugin;
    }
}