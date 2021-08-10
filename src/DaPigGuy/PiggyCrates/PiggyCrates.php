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
use DaPigGuy\PiggyCrates\utils\Utils;
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
    private static $instance;

    /** @var Config */
    private $messages;

    /** @var Crate[] */
    public $crates = [];
    /** @var array */
    public $crateCreation;

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

        if ($this->getServer()->getPluginManager()->getPlugin("InvCrashFix") === null) {
            $this->getLogger()->info("Missing InvCrashFix plugin. Download here: https://poggit.pmmp.io/r/94956/InvCrashFix_dev-3.phar");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        self::$instance = $this;

        Tile::registerTile(CrateTile::class);

        $this->saveResource("crates.yml");
        $this->saveResource("messages.yml");
        $this->messages = new Config($this->getDataFolder() . "messages.yml");
        $this->saveDefaultConfig();

        $crateConfig = new Config($this->getDataFolder() . "crates.yml");
        $types = ["item", "command"];
        foreach ($crateConfig->get("crates") as $crateName => $crateData) {
            $this->crates[$crateName] = new Crate($this, $crateName, $crateData["floating-text"] ?? "", array_map(function (array $itemData) use ($crateName, $types): CrateItem {
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

    public static function getInstance(): PiggyCrates
    {
        return self::$instance;
    }

    public function getMessage(string $key, array $tags = []): string
    {
        return Utils::translateColorTags(str_replace(array_keys($tags), $tags, $this->messages->getNested($key, $key)));
    }

    public function getCrate(string $name): ?Crate
    {
        return $this->crates[$name] ?? null;
    }

    public function getCrates(): array
    {
        return $this->crates;
    }

    public function inCrateCreationMode(Player $player): bool
    {
        return isset($this->crateCreation[$player->getName()]);
    }

    public function setInCrateCreationMode(Player $player, ?Crate $crate): void
    {
        if ($crate === null) {
            unset($this->crateCreation[$player->getName()]);
        }
        $this->crateCreation[$player->getName()] = $crate;
    }

    public function getCrateToCreate(Player $player): ?Crate
    {
        return $this->crateCreation[$player->getName()] ?? null;
    }
}