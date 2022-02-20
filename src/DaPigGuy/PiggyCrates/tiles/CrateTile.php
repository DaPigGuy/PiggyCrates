<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates\tiles;

use DaPigGuy\PiggyCrates\crates\Crate;
use DaPigGuy\PiggyCrates\crates\CrateItem;
use DaPigGuy\PiggyCrates\PiggyCrates;
use DaPigGuy\PiggyCrates\tasks\RouletteTask;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\tile\Chest;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\World;

class CrateTile extends Chest
{
    public string $crateName;
    public ?Crate $crateType;

    /** @var bool */
    public bool $isOpen = false;
    public ?Player $currentPlayer;

    /** @var array[] */
    public array $floatingTextParticles = [];

    public function __construct(World $world, Vector3 $pos)
    {
        parent::__construct($world, $pos);
        PiggyCrates::getInstance()->crateTiles[] = $this;
    }

    public function getCrateType(): ?Crate
    {
        return $this->crateType;
    }

    public function setCrateType(Crate $crate): void
    {
        $this->crateName = $crate->getName();
        $this->crateType = $crate;
    }

    public function openCrate(Player $player, Item $key): void
    {
        if (($crateType = $this->crateType) === null) return;
        if ($this->isOpen) {
            $player->sendTip(PiggyCrates::getInstance()->getMessage("crates.error.currently-opened"));
            return;
        }
        if (count($player->getInventory()->getContents()) > $player->getInventory()->getSize() - $crateType->getDropCount()) {
            $player->sendTip(PiggyCrates::getInstance()->getMessage("crates.error.inventory-full", ["{COUNT}" => $crateType->getDropCount()]));
            return;
        }

        $player->getInventory()->removeItem($key->setCount(1));

        $this->getPosition()->getWorld()->broadcastPacketToViewers($this->getPosition(), BlockEventPacket::create(BlockPosition::fromVector3($this->getPosition()->asVector3()), 1, 1));

        $this->isOpen = true;
        $this->currentPlayer = $player;

        switch (PiggyCrates::getInstance()->getConfig()->getNested("crates.mode")) {
            case "instant":
                $this->closeCrate();
                $server = $player->getServer();
                foreach ($crateType->getDrop($crateType->getDropCount()) as $drop) {
                    if ($drop->getType() === "item") $player->getInventory()->addItem($drop->getItem());
                    foreach ($drop->getCommands() as $command) {
                        $server->dispatchCommand(new ConsoleCommandSender($server, $server->getLanguage()), str_replace("{PLAYER}", $player->getName(), $command));
                    }
                }
                foreach ($crateType->getCommands() as $command) {
                    $server->dispatchCommand(new ConsoleCommandSender($server, $server->getLanguage()), str_replace("{PLAYER}", $player->getName(), $command));
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
        if (!$this->isOpen) return;

        $this->getPosition()->getWorld()->broadcastPacketToViewers($this->getPosition(), BlockEventPacket::create(BlockPosition::fromVector3($this->getPosition()->asVector3()), 1, 0));

        $this->isOpen = false;
        $this->currentPlayer = null;
    }

    public function previewCrate(Player $player): void
    {
        if (($crateType = $this->crateType) === null) return;

        $drops = $crateType->getDrops();
        usort($drops, function (CrateItem $a, CrateItem $b) {
            if ($a->getChance() > $b->getChance()) return -1;
            if ($a->getChance() < $b->getChance()) return 1;
            return 0;
        });

        $chances = 0;
        foreach ($drops as $crateItem) $chances += $crateItem->chance;

        $menu = InvMenu::create(count($drops) > 27 ? InvMenuTypeIds::TYPE_DOUBLE_CHEST : InvMenuTypeIds::TYPE_CHEST);
        $menu->setListener(InvMenu::readonly());
        $menu->setName(PiggyCrates::getInstance()->getMessage("crates.menu-name", ["{CRATE}" => $crateType->getName()]));

        $slot = 0;
        foreach ($drops as $crateItem) {
            if ($slot > 53) break; // Maximum supported preview items is 54, meaning lowest chances are not shown.
            $item = clone $crateItem->item;
            $item->setCustomName(TextFormat::RESET . PiggyCrates::getInstance()->getMessage("crates.preview.item.name", ["{COUNT}" => $crateItem->getItem()->getCount(), "{ITEM}" => $item->getName()]));
            $item->setLore([TextFormat::RESET, TextFormat::RESET . PiggyCrates::getInstance()->getMessage("crates.preview.item.lore", ["{CHANCE}" => round(($crateItem->chance / $chances) * 100, 2, PHP_ROUND_HALF_UP)])]);
            $menu->getInventory()->setItem($slot, $item);
            $slot++;
        }
        $menu->send($player);
    }

    public function close(): void
    {
        foreach ($this->floatingTextParticles as $floatingTextParticle) {
            $floatingTextParticle[1]->setInvisible();
            if ($floatingTextParticle[0]->getWorld()) $floatingTextParticle[0]->getWorld()->addParticle($floatingTextParticle[1], [$floatingTextParticle[0]]);
        }
        unset(PiggyCrates::getInstance()->crateTiles[array_search($this, PiggyCrates::getInstance()->crateTiles)]);
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
        if (!$this->closed && $this->crateType !== null && $this->crateType->getFloatingText() !== "") {
            $world = $this->getPosition()->getWorld();
            foreach ($this->floatingTextParticles as $key => $floatingTextParticle) {
                $player = $floatingTextParticle[0];
                $particle = $floatingTextParticle[1];
                if (!$player->isOnline() || $player->getWorld() !== $world) {
                    $particle->setInvisible();
                    $world->addParticle($this->getPosition()->add(0.5, 1, 0.5), $particle, [$player]);
                    unset($this->floatingTextParticles[$key]);
                }
            }
            foreach ($world->getPlayers() as $player) {
                if (!isset($this->floatingTextParticles[$player->getName()])) {
                    $this->floatingTextParticles[$player->getName()] = [$player, new FloatingTextParticle($this->crateType->getFloatingText())];
                    $world->addParticle($this->getPosition()->add(0.5, 1, 0.5), $this->floatingTextParticles[$player->getName()][1], [$player]);
                }
            }
        }
        return !$this->closed;
    }

    public function readSaveData(CompoundTag $nbt): void
    {
        parent::readSaveData($nbt);
        $this->crateName = $nbt->getString("CrateType");
        $this->crateType = PiggyCrates::getInstance()->getCrate($this->crateName);
    }

    protected function writeSaveData(CompoundTag $nbt): void
    {
        parent::writeSaveData($nbt);
        $nbt->setString("CrateType", $this->crateName);
    }
}