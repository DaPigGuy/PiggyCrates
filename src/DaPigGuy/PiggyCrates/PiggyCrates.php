<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates;

use CortexPE\Commando\BaseCommand;
use DaPigGuy\PiggyCrates\commands\CrateCommand;
use DaPigGuy\PiggyCrates\commands\KeyAllCommand;
use DaPigGuy\PiggyCrates\commands\KeyCommand;
use DaPigGuy\PiggyCrates\crates\Crate;
use DaPigGuy\PiggyCrates\crates\CrateItem;
use DaPigGuy\PiggyCrates\tasks\CheckUpdatesTask;
use DaPigGuy\PiggyCrates\tiles\CrateTile;
use DaPigGuy\PiggyCustomEnchants\CustomEnchantManager;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Tile;
use pocketmine\utils\Config;
use ReflectionException;

/**
 * Class PiggyCrates
 * @package DaPigGuy\PiggyCrates
 */
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
     */
    public function onEnable(): void
    {
        if (!class_exists(BaseCommand::class)) {
            $this->getLogger()->error("Commando virion not found. Please download PiggyCrates from Poggit-CI or use DEVirion (not recommended).");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        self::$instance = $this;

        Tile::registerTile(CrateTile::class);

        $this->saveDefaultConfig();
        $this->saveResource("crates.yml");

        $crateConfig = new Config($this->getDataFolder() . "crates.yml");
        foreach ($crateConfig->get("crates") as $crateName => $crateData) {
            self::$crates[$crateName] = new Crate($this, $crateName, $crateData["floating-text"] ?? "", array_map(function (array $itemData): CrateItem {
                $item = Item::get($itemData["id"], $itemData["meta"], $itemData["amount"], $itemData["nbt"] ?? "");
                if (isset($itemData["name"])) $item->setCustomName($itemData["name"]);
                if (isset($itemData["lore"])) $item->setLore(explode("\n", $itemData["lore"]));
                if (isset($itemData["enchantments"])) foreach ($itemData["enchantments"] as $enchantmentData) {
                    $enchantment = Enchantment::getEnchantmentByName($enchantmentData["name"]) ?? ((($plugin = $this->getServer()->getPluginManager()->getPlugin("PiggyCustomEnchants")) !== null && $plugin->isEnabled()) ? CustomEnchantManager::getEnchantmentByName($enchantmentData["name"]) : null);
                    if ($enchantment !== null) $item->addEnchantment(new EnchantmentInstance($enchantment, $enchantmentData["level"]));
                }
                return new CrateItem($item, $itemData["commands"] ?? [], $itemData["chance"] ?? 100);
            }, $crateData["drops"] ?? []), $crateData["amount"], $crateData["commands"] ?? []);
        }

        $this->getServer()->getCommandMap()->register("piggycrates", new CrateCommand($this, "crate", "Create a crate"));
        $this->getServer()->getCommandMap()->register("piggycrates", new KeyCommand($this, "key", "Give a crate key"));
        $this->getServer()->getCommandMap()->register("piggycrates", new KeyAllCommand($this, "keyall", "Give all online players a crate key"));

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

        $this->getServer()->getAsyncPool()->submitTask(new CheckUpdatesTask($this->getDescription()->getVersion(), $this->getDescription()->getCompatibleApis()[0]));
    }

    /**
     * @param string $name
     * @return Crate|null
     */
    public static function getCrate(string $name): ?Crate
    {
        return self::$crates[$name] ?? null;
    }

    /**
     * @return array
     */
    public static function getCrates(): array
    {
        return self::$crates;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public static function inCrateCreationMode(Player $player): bool
    {
        return isset(self::$crateCreation[$player->getName()]);
    }

    /**
     * @param Player $player
     * @param Crate|null $crate
     */
    public static function setInCrateCreationMode(Player $player, ?Crate $crate): void
    {
        if ($crate === null) {
            unset(self::$crateCreation[$player->getName()]);
        }
        self::$crateCreation[$player->getName()] = $crate;
    }

    /**
     * @param Player $player
     * @return Crate|null
     */
    public static function getCrateToCreate(Player $player): ?Crate
    {
        return self::$crateCreation[$player->getName()] ?? null;
    }
}