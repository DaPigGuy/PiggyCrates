<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates\tiles;

use DaPigGuy\PiggyCrates\crates\Crate;
use DaPigGuy\PiggyCrates\PiggyCrates;
use pocketmine\item\Item;
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
    public function open(Player $player, Item $key): void
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

        $this->isOpen = true;
        $this->currentPlayer = $player;

        switch (PiggyCrates::getCrateMode()) {
            case "instant":
                $this->close();
                foreach ($this->crateType->getDrop($this->crateType->getDropCount()) as $drop){
                    $player->getInventory()->addItem($drop);
                }
                break;
            case "roulette":
            default:
                break;
        }
    }

    public function close(): void
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
    }
}