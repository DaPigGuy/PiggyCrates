<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates;

use DaPigGuy\PiggyCrates\crates\Crate;
use DaPigGuy\PiggyCustomEnchants\CustomEnchantManager;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

/**
 * Class PiggyCrates
 * @package DaPigGuy\PiggyCrates
 */
class PiggyCrates extends PluginBase
{
    /** @var Crate[] */
    public static $crates = [];

    public function onEnable(): void
    {
        $this->saveDefaultConfig();
        $this->saveResource("crates.yml");

        $crateConfig = new Config($this->getDataFolder() . "crates.yml");
        foreach ($crateConfig->get("crates") as $crateName => $crateData) {
            self::$crates[$crateName] = new Crate($this, $crateName, array_map(function (array $itemData): Item {
                $item = Item::get($itemData["id"], $itemData["meta"], $itemData["count"], $itemData["nbt"] ?? "");
                if (isset($itemData["name"])) $item->setCustomName($itemData["name"]);
                if (isset($itemData["enchantments"])) foreach ($itemData["enchantments"] as $enchantmentData) {
                    $enchantment = Enchantment::getEnchantmentByName($enchantmentData["name"]) ?? ((($plugin = $this->getServer()->getPluginManager()->getPlugin("PiggyCustomEnchants")) !== null && $plugin->isEnabled()) ? CustomEnchantManager::getEnchantmentByName($enchantmentData["name"]) : null);
                    if ($enchantment !== null) $item->addEnchantment(new EnchantmentInstance($enchantment, $enchantmentData["level"]));
                }
                return $item;
            }, $crateData["drops"] ?? []), $crateData["commands"] ?? []);
        }

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
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
}