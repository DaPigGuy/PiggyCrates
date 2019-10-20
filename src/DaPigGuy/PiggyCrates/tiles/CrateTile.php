<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates\tiles;

use DaPigGuy\PiggyCrates\crates\Crate;
use DaPigGuy\PiggyCrates\PiggyCrates;
use DaPigGuy\PiggyCrates\tasks\RouletteTask;
use pocketmine\item\Item;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\Player;
use pocketmine\tile\Chest;
use pocketmine\utils\TextFormat;

/**
 * Class CrateTile
 * @package DaPigGuy\PiggyCrates\tiles
 */
class CrateTile extends Chest
{
    /** @var string */
    public $crateName;
    /** @var Crate */
    public $crateType;

    /** @var bool */
    public $isOpen = false;
    /** @var Player|null */
    public $currentPlayer;

    /** @var array[] */
    public $floatingTextParticles = [];

    /**
     * @return Crate|null
     */
    public function getCrateType(): ?Crate
    {
        return $this->crateType;
    }

    /**
     * @param Player $player
     * @param Item $key
     */
    public function openCrate(Player $player, Item $key): void
    {
        if ($this->crateType === null) return;
        if ($this->isOpen) {
            $player->sendTip(TextFormat::RED . "Crate is currently being opened.");
            return;
        }
        if (count($player->getInventory()->getContents()) > $player->getInventory()->getSize() - $this->crateType->getDropCount()) {
            $player->sendTip(TextFormat::RED . "You must have " . $this->crateType->getDropCount() . " empty slots.");
            return;
        }

        $player->getInventory()->removeItem($key->setCount(1));

        $pk = new BlockEventPacket();
        $pk->x = $this->x;
        $pk->y = $this->y;
        $pk->z = $this->z;
        $pk->eventType = 1;
        $pk->eventData = 1;
        $this->getLevel()->broadcastPacketToViewers($this, $pk);

        $this->getInventory()->clearAll();
        $this->getInventory()->setItem(4, Item::get(Item::END_ROD, 0, 1));
        $this->getInventory()->setItem(22, Item::get(Item::END_ROD, 0, 1));

        $this->isOpen = true;
        $this->currentPlayer = $player;

        switch (PiggyCrates::$instance->getConfig()->getNested("crates.mode")) {
            case "instant":
                $this->closeCrate();
                foreach ($this->crateType->getDrop($this->crateType->getDropCount()) as $drop) {
                    $player->getInventory()->addItem($drop);
                }
                break;
            case "roulette":
            default:
                PiggyCrates::$instance->getScheduler()->scheduleRepeatingTask(new RouletteTask($this), 1);
                break;
        }
    }

    public function closeCrate(): void
    {
        if (!$this->isOpen) return;

        $pk = new BlockEventPacket();
        $pk->x = $this->x;
        $pk->y = $this->y;
        $pk->z = $this->z;
        $pk->eventType = 1;
        $pk->eventData = 0;
        $this->getLevel()->broadcastPacketToViewers($this, $pk);

        $this->isOpen = false;
        $this->currentPlayer = null;
    }

    public function close(): void
    {
        foreach ($this->floatingTextParticles as $floatingTextParticle) {
            $floatingTextParticle[1]->setInvisible();
            if ($floatingTextParticle[0]->getLevel()) $floatingTextParticle[0]->getLevel()->addParticle($floatingTextParticle[1], [$floatingTextParticle[0]]);
        }
        parent::close();
    }

    /**
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->isOpen;
    }

    /**
     * @return Player|null
     */
    public function getCurrentPlayer(): ?Player
    {
        return $this->currentPlayer;
    }

    /**
     * @param CompoundTag $nbt
     */
    protected function readSaveData(CompoundTag $nbt): void
    {
        parent::readSaveData($nbt);
        $this->crateName = $nbt->getString("CrateType");
        $this->crateType = PiggyCrates::getCrate($this->crateName);

        $this->scheduleUpdate();
    }

    /**
     * @param CompoundTag $nbt
     */
    protected function writeSaveData(CompoundTag $nbt): void
    {
        parent::writeSaveData($nbt);
        $nbt->setString("CrateType", $this->crateName);
    }

    /**
     * @param CompoundTag $nbt
     */
    public function addAdditionalSpawnData(CompoundTag $nbt): void
    {
        parent::addAdditionalSpawnData($nbt);
        $nbt->setString(self::TAG_ID, "Chest");
        $nbt->setString(self::TAG_CUSTOM_NAME, $this->crateType->getName() . " Crate");
    }

    /**
     * @return bool
     */
    public function onUpdate(): bool
    {
        if (!$this->closed && $this->crateType->getFloatingText() !== "") {
            foreach ($this->floatingTextParticles as $key => $floatingTextParticle) {
                /** @var Player $player */
                $player = $floatingTextParticle[0];
                /** @var FloatingTextParticle $particle */
                $particle = $floatingTextParticle[1];
                if (!$player->isOnline() || $player->getLevel() !== $this->level) {
                    $particle->setInvisible();
                    $this->level->addParticle($particle, [$player]);
                    unset($this->floatingTextParticles[$key]);
                }
            }
            foreach ($this->level->getPlayers() as $player) {
                if (!isset($this->floatingTextParticles[$player->getName()])) {
                    $this->floatingTextParticles[$player->getName()] = [$player, new FloatingTextParticle($this->add(0.5, 1, 0.5), $this->crateType->getFloatingText())];
                    $this->level->addParticle($this->floatingTextParticles[$player->getName()][1], [$player]);
                }
            }
        }
        return !$this->closed;
    }
}