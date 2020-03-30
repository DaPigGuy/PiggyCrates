<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates\tasks;

use DaPigGuy\PiggyCrates\crates\CrateItem;
use DaPigGuy\PiggyCrates\PiggyCrates;
use DaPigGuy\PiggyCrates\tiles\CrateTile;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class RouletteTask extends Task
{
    /** @var CrateTile */
    private $tile;
    /** @var int */
    private $currentTick = 0;
    /** @var bool */
    private $showReward = false;

    /** @var int */
    private $itemsLeft;

    /** @var CrateItem[] */
    private $lastRewards = [];

    public function __construct(CrateTile $tile)
    {
        $this->tile = $tile;
        $this->itemsLeft = $tile->getCrateType() === null ? 0 : $tile->getCrateType()->getDropCount();
    }

    public function onRun(int $currentTick): void
    {
        if (!$this->tile->getCurrentPlayer() instanceof Player || !$this->tile->getCurrentPlayer()->isOnline() || ($crateType = $this->tile->getCrateType()) === null) {
            $this->tile->closeCrate();
            if (($handler = $this->getHandler()) !== null) $handler->cancel();
            return;
        }
        $this->currentTick++;
        $this->tile->getCurrentPlayer()->addWindow($this->tile->getInventory());
        if ($this->currentTick >= PiggyCrates::$instance->getConfig()->getNested("crates.roulette.duration")) {
            if (!$this->showReward) {
                $this->showReward = true;
            } elseif ($this->currentTick - PiggyCrates::$instance->getConfig()->getNested("crates.roulette.duration") > 20) {
                $this->itemsLeft--;
                $this->tile->getCurrentPlayer()->getInventory()->addItem($this->tile->getInventory()->getItem(13));
                foreach ($this->lastRewards[13]->getCommands() as $command) {
                    $this->tile->getCurrentPlayer()->getServer()->dispatchCommand(new ConsoleCommandSender(), str_replace("{PLAYER}", $this->tile->getCurrentPlayer()->getName(), $command));
                }
                if ($this->itemsLeft === 0) {
                    foreach ($crateType->getCommands() as $command) {
                        $this->tile->getCurrentPlayer()->getServer()->dispatchCommand(new ConsoleCommandSender(), str_replace("{PLAYER}", $this->tile->getCurrentPlayer()->getName(), $command));
                    }
                    $this->tile->getCurrentPlayer()->removeWindow($this->tile->getInventory());
                    $this->tile->closeCrate();
                    if (($handler = $this->getHandler()) !== null) $handler->cancel();
                } else {
                    $this->currentTick = 0;
                    $this->showReward = false;
                }
            }
            return;
        }

        if ($this->currentTick % PiggyCrates::$instance->getConfig()->getNested("crates.roulette.speed") === 0) {
            $lastRewards = [];
            /**
             * @var  int $slot
             * @var  CrateItem $lastReward
             */
            foreach ($this->lastRewards as $slot => $lastReward) {
                if ($slot !== 9) {
                    $lastRewards[$slot - 1] = $lastReward;
                    $this->tile->getInventory()->setItem($slot - 1, $lastReward->getItem());
                }
            }
            $lastRewards[17] = $crateType->getDrop(1)[0];
            $this->tile->getInventory()->setItem(17, $lastRewards[17]->getItem());
            $this->lastRewards = $lastRewards;
        }
    }
}