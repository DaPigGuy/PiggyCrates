<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates\tiles;

use DaPigGuy\PiggyCrates\crates\Crate;
use DaPigGuy\PiggyCrates\crates\CrateItem;
use DaPigGuy\PiggyCrates\PiggyCrates;
use DaPigGuy\PiggyCrates\tasks\RouletteTask;
use muqsit\invmenu\InvMenu;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\Player;
use pocketmine\tile\Chest;
use pocketmine\utils\TextFormat;

class CrateTile extends Chest
{
    /** @var string */
    public $crateName;
    /** @var Crate|null */
    public $crateType;
    /** @var bool */
    public $isOpen = false;
    /** @var Player|null */
    public $currentPlayer;
    /** @var array[] */
    public $floatingTextParticles = [];
    /** @var InvMenu */
    private $menu;

    public function __construct(Level $level, CompoundTag $nbt)
    {
        parent::__construct($level, $nbt);
        if (($crateType = $this->crateType) === null) return;
        $this->menu = InvMenu::create(count($crateType->getDrops()) > 27 ? InvMenu::TYPE_DOUBLE_CHEST : InvMenu::TYPE_CHEST);
        $this->menu->setListener(InvMenu::readonly());
        $this->menu->setName(PiggyCrates::getInstance()->getMessage("crates.menu-name", ["{CRATE}" => $crateType->getName()]));
    }

    public function getCrateType(): ?Crate
    {
        return $this->crateType;
    }

    public function openCrate(Player $player, Item $key): void
    {
        if (($crateType = $this->crateType) === null || ($level = $this->getLevel()) === null) return;
        if ($this->isOpen) {
            $player->sendTip(PiggyCrates::getInstance()->getMessage("crates.error.currently-opened"));
            return;
        }
        if (count($player->getInventory()->getContents()) > $player->getInventory()->getSize() - $crateType->getDropCount()) {
            $player->sendTip(PiggyCrates::getInstance()->getMessage("crates.error.inventory-full", ["{COUNT}" => $crateType->getDropCount()]));
            return;
        }

        $player->getInventory()->removeItem($key->setCount(1));

        $pk = new BlockEventPacket();
        $pk->x = $this->getFloorX();
        $pk->y = $this->getFloorY();
        $pk->z = $this->getFloorZ();
        $pk->eventType = 1;
        $pk->eventData = 1;
        $level->broadcastPacketToViewers($this, $pk);

        $this->isOpen = true;
        $this->currentPlayer = $player;

        switch (PiggyCrates::getInstance()->getConfig()->getNested("crates.mode")) {
            case "instant":
                $this->closeCrate();
                foreach ($crateType->getDrop($crateType->getDropCount()) as $drop) {
                    if ($drop->getType() === "item") $player->getInventory()->addItem($drop->getItem());
                    foreach ($drop->getCommands() as $command) {
                        $player->getServer()->dispatchCommand(new ConsoleCommandSender(), str_replace("{PLAYER}", $player->getName(), $command));
                    }
                }
                foreach ($crateType->getCommands() as $command) {
                    $player->getServer()->dispatchCommand(new ConsoleCommandSender(), str_replace("{PLAYER}", $player->getName(), $command));
                }
                break;
            case "roulette":
            default:
                PiggyCrates::getInstance()->getScheduler()->scheduleRepeatingTask(new RouletteTask($this), 1);
                break;
        }
    }

    public function closeCrate(): void
    {
        if (!$this->isOpen || ($level = $this->getLevel()) === null) return;

        $pk = new BlockEventPacket();
        $pk->x = $this->getFloorX();
        $pk->y = $this->getFloorY();
        $pk->z = $this->getFloorZ();
        $pk->eventType = 1;
        $pk->eventData = 0;
        $level->broadcastPacketToViewers($this, $pk);

        $this->isOpen = false;
        $this->currentPlayer = null;
    }

    public function previewCrate(Player $player): void
    {
        if (($crateType = $this->crateType) === null || ($level = $this->getLevel()) === null) return;

        $drops = $crateType->getDrops();
        usort($drops, function (CrateItem $a, CrateItem $b) {
            if ($a->getChance() > $b->getChance()) return -1;
            if ($a->getChance() < $b->getChance()) return 1;
            return 0;
        });

        $chances = 0;
        foreach ($drops as $crateItem) $chances += $crateItem->chance;

        $slot = 0;
        foreach ($drops as $crateItem) {
            if ($slot > 53) break; // Maximum supported preview items is 54, meaning lowest chances are not shown.
            $item = clone $crateItem->item;
            $item->setCustomName(TextFormat::RESET . PiggyCrates::getInstance()->getMessage("crates.preview.item.name", ["{COUNT}" => $crateItem->getItem()->getCount(), "{ITEM}" => $item->getName()]));
            $item->setLore([TextFormat::RESET, TextFormat::RESET . PiggyCrates::getInstance()->getMessage("crates.preview.item.lore", ["{CHANCE}" => round(($crateItem->chance / $chances) * 100, 2, PHP_ROUND_HALF_UP)])]);
            $this->menu->getInventory()->setItem($slot, $item);
            $slot++;
        }
        $this->menu->send($player);
    }

    public function close(): void
    {
        foreach ($this->floatingTextParticles as $floatingTextParticle) {
            $floatingTextParticle[1]->setInvisible();
            if ($floatingTextParticle[0]->getLevel()) $floatingTextParticle[0]->getLevel()->addParticle($floatingTextParticle[1], [$floatingTextParticle[0]]);
        }
        parent::close();
    }

    public function isOpen(): bool
    {
        return $this->isOpen;
    }

    public function getCurrentPlayer(): ?Player
    {
        return $this->currentPlayer;
    }

    public function addAdditionalSpawnData(CompoundTag $nbt): void
    {
        parent::addAdditionalSpawnData($nbt);
        $nbt->setString(self::TAG_ID, "Chest");
        $nbt->setString(self::TAG_CUSTOM_NAME, ($this->crateType === null ? "Unknown" : $this->crateType->getName()) . " Crate");
    }

    public function onUpdate(): bool
    {
        if (!$this->closed && ($level = $this->getLevel()) !== null && $this->crateType !== null && $this->crateType->getFloatingText() !== "") {
            foreach ($this->floatingTextParticles as $key => $floatingTextParticle) {
                /** @var Player $player */
                $player = $floatingTextParticle[0];
                /** @var FloatingTextParticle $particle */
                $particle = $floatingTextParticle[1];
                if (!$player->isOnline() || $player->getLevel() !== $level) {
                    $particle->setInvisible();
                    $level->addParticle($particle, [$player]);
                    unset($this->floatingTextParticles[$key]);
                }
            }
            foreach ($level->getPlayers() as $player) {
                if (!isset($this->floatingTextParticles[$player->getName()])) {
                    $this->floatingTextParticles[$player->getName()] = [$player, new FloatingTextParticle($this->add(0.5, 1, 0.5), $this->crateType->getFloatingText())];
                    $level->addParticle($this->floatingTextParticles[$player->getName()][1], [$player]);
                }
            }
        }
        return !$this->closed;
    }

    protected function readSaveData(CompoundTag $nbt): void
    {
        parent::readSaveData($nbt);
        $this->crateName = $nbt->getString("CrateType");
        $this->crateType = PiggyCrates::getInstance()->getCrate($this->crateName);

        $this->scheduleUpdate();
    }

    protected function writeSaveData(CompoundTag $nbt): void
    {
        parent::writeSaveData($nbt);
        $nbt->setString("CrateType", $this->crateName);
    }
}
