<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates\tasks;

use DaPigGuy\PiggyCrates\crates\CrateItem;
use DaPigGuy\PiggyCrates\PiggyCrates;
use DaPigGuy\PiggyCrates\tiles\CrateTile;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\SharedInvMenu;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\item\Item;
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

    /** @var bool */
    private $preview = false;
    /**
     * @var SharedInvMenu
     */
    private $menu;
    /**
     * @var Player|null
     */
    private $player;

    public function __construct(CrateTile $tile, ?Player $player = null, bool $preview = false)
    {
        $this->tile = $tile;
        $this->itemsLeft = $tile->getCrateType() === null ? 0 : $tile->getCrateType()->getDropCount();

        if ($preview) {
            $this->preview = true;
            $this->player = $player;
            $this->setupPreviewMenu();
        }
    }

    public function onRun(int $currentTick): void
    {
        if ($this->preview) {
            $this->roulette(true);
            return;
        }

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
        $this->roulette();
    }

    public function roulette(bool $preview = false): void
    {
        $crateType = $this->tile->getCrateType();
        if ($crateType === null) {
            if (($handler = $this->getHandler()) !== null) {
                $handler->cancel();
            }
            return;
        }

        if ($preview) $this->currentTick++;

        if ($this->currentTick % PiggyCrates::$instance->getConfig()->getNested("crates.roulette.speed") === 0) {
            $lastRewards = [];
            /**
             * @var  int $slot
             * @var  CrateItem $lastReward
             */
            foreach ($this->lastRewards as $slot => $lastReward) {
                if ($slot !== 9) {
                    $lastRewards[$slot - 1] = $lastReward;
                    if ($preview) {
                        $this->menu->getInventory()->setItem($slot - 1, $lastReward->getItem());
                    } else {
                        $this->tile->getInventory()->setItem($slot - 1, $lastReward->getItem());
                    }
                }
            }

            $lastRewards[17] = $crateType->getDrop(1)[0];
            if ($preview) {
                $this->menu->getInventory()->setItem(17, $lastRewards[17]->getItem());
            } else {
                $this->tile->getInventory()->setItem(17, $lastRewards[17]->getItem());
            }
            $this->lastRewards = $lastRewards;
        }
    }

    private function setupPreviewMenu(): void
    {
        $this->menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $this->menu->getInventory()->setItem(4, Item::get(Item::END_ROD, 0, 1));
        $this->menu->getInventory()->setItem(22, Item::get(Item::END_ROD, 0, 1));
        $this->menu->readonly();
        $this->menu->setName($this->tile->getName());

        if ($this->player instanceof Player && $this->player->isOnline()) {
            $this->menu->send($this->player);
        }

        $this->menu->setInventoryCloseListener(function (Player $player, InvMenuInventory $inventory): bool {
            if (($handler = $this->getHandler()) !== null) {
                $handler->cancel();
            }
            return true;
        });
    }
}