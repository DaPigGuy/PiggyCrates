<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\exception\HookAlreadyRegistered;
use CortexPE\Commando\PacketHooker;
use DaPigGuy\PiggyCrates\commands\CrateCommand;
use DaPigGuy\PiggyCrates\commands\KeyAllCommand;
use DaPigGuy\PiggyCrates\commands\KeyCommand;
use DaPigGuy\PiggyCrates\crates\Crate;
use DaPigGuy\PiggyCrates\crates\CrateItem;
use DaPigGuy\PiggyCrates\tasks\CheckUpdatesTask;
use DaPigGuy\PiggyCrates\tiles\CrateTile;
use DaPigGuy\PiggyCustomEnchants\CustomEnchantManager;
use DaPigGuy\PiggyCustomEnchants\PiggyCustomEnchants;
use Exception;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\nbt\JsonNbtParser;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Tile;
use pocketmine\utils\Config;
use ReflectionException;

class PiggyCrates extends PluginBase
{
    /** @var PiggyCrates */
    public static $instance;

    /** @var Crate[] */
    public static $crates = [];

    /** @var array */
    public static $crateCreation;

    /**
     * @throws ReflectionException
     * @throws HookAlreadyRegistered
     */
    public function onEnable(): void
    {
        foreach (
            [
                "Commando" => BaseCommand::class,
                "InvMenu" => InvMenuHandler::class
            ] as $virion => $class
        ) {
            if (!class_exists($class)) {
                $this->getLogger()->error($virion . " virion not found. Please download PiggyCrates from Poggit-CI or use DEVirion (not recommended).");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }
        }

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        self::$instance = $this;

        Tile::registerTile(CrateTile::class);

        $this->saveDefaultConfig();
        $this->saveResource("crates.yml");

        $crateConfig = new Config($this->getDataFolder() . "crates.yml");
        $types = ["item", "command"];
        foreach ($crateConfig->get("crates") as $crateName => $crateData) {
            self::$crates[$crateName] = new Crate($this, $crateName, $crateData["floating-text"] ?? "", array_map(function (array $itemData) use ($crateName, $types): CrateItem {
                $tags = "";
                if (isset($itemData["nbt"])) {
                    try {
                        $tags = JsonNbtParser::parseJson($itemData["nbt"]) ?? "";
                    } catch (Exception $e) {
                        $this->getLogger()->warning("Invalid crate item NBT supplied in crate type " . $crateName . ".");
                    }
                }
                $item = Item::get($itemData["id"], $itemData["meta"], $itemData["amount"], $tags);
                if (isset($itemData["name"])) $item->setCustomName($itemData["name"]);
                if (isset($itemData["lore"])) $item->setLore(explode("\n", $itemData["lore"]));
                if (isset($itemData["enchantments"])) foreach ($itemData["enchantments"] as $enchantmentData) {
                    if (!isset($enchantmentData["name"]) || !isset($enchantmentData["level"])) {
                        $this->getLogger()->error("Invalid enchantment configuration used in crate " . $crateName);
                        continue;
                    }
                    $enchantment = Enchantment::getEnchantmentByName($enchantmentData["name"]) ?? ((($plugin = $this->getServer()->getPluginManager()->getPlugin("PiggyCustomEnchants")) instanceof PiggyCustomEnchants && $plugin->isEnabled()) ? CustomEnchantManager::getEnchantmentByName($enchantmentData["name"]) : null);
                    if ($enchantment !== null) $item->addEnchantment(new EnchantmentInstance($enchantment, $enchantmentData["level"]));
                }
                $itemData["type"] = $itemData["type"] ?? "item";
                if (!in_array($itemData["type"], $types)) {
                    $itemData["type"] = "item";
                    $this->getLogger()->warning("Invalid crate item type supplied in crate type " . $crateName . ". Assuming type item.");
                }
                return new CrateItem($item, $itemData["type"], $itemData["commands"] ?? [], $itemData["chance"] ?? 100);
            }, $crateData["drops"] ?? []), $crateData["amount"], $crateData["commands"] ?? []);
        }

        if (!PacketHooker::isRegistered()) PacketHooker::register($this);
        $this->getServer()->getCommandMap()->register("piggycrates", new CrateCommand($this, "crate", "Create a crate"));
        $this->getServer()->getCommandMap()->register("piggycrates", new KeyCommand($this, "key", "Give a crate key"));
        $this->getServer()->getCommandMap()->register("piggycrates", new KeyAllCommand($this, "keyall", "Give all online players a crate key"));

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

        $this->getServer()->getAsyncPool()->submitTask(new CheckUpdatesTask());
    }

    public static function getCrate(string $name): ?Crate
    {
        return self::$crates[$name] ?? null;
    }

    public static function getCrates(): array
    {
        return self::$crates;
    }

    public static function inCrateCreationMode(Player $player): bool
    {
        return isset(self::$crateCreation[$player->getName()]);
    }

    public static function setInCrateCreationMode(Player $player, ?Crate $crate): void
    {
        if ($crate === null) {
            unset(self::$crateCreation[$player->getName()]);
        }
        self::$crateCreation[$player->getName()] = $crate;
    }

    public static function getCrateToCreate(Player $player): ?Crate
    {
        return self::$crateCreation[$player->getName()] ?? null;
    }
}