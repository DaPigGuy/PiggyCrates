<?php

namespace DaPigGuy\PiggyCrates\Commands;

use DaPigGuy\PiggyCrates\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

/**
 * Class KeyAllCommand
 * @package DaPigGuy\PiggyCrates\Commands
 */
class KeyAllCommand extends PluginCommand
{
    /**
     * KeyAllCommand constructor.
     * @param string $name
     * @param Main   $plugin
     */
    public function __construct(string $name, Main $plugin)
    {
        parent::__construct($name, $plugin);
        $this->setDescription("Give everyone a crate key");
        $this->setUsage("/keyall <type> [amount]");
        $this->setPermission("piggycrates.command.keyall");
    }

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$this->testPermission($sender)) return;
        $plugin = $this->getPlugin();
        if ($plugin instanceof Main) {
            if (!isset($args[0])) {
                $sender->sendMessage("Usage: /keyall <type> [amount]");
                return;
            }
            $amount = 1;
            $args[0] = strtolower($args[0]);
            if (isset($args[1])) {
                if (is_numeric($args[1])) {
                    $amount = $args[1];
                } else {
                    $sender->sendMessage(TextFormat::RED . "Amount must be numeric.");
                    return;
                }
            }
            if (!$plugin->getCrateType($args[0])) {
                $sender->sendMessage(TextFormat::RED . "Invalid crate type.");
                return;
            }
            foreach ($plugin->getServer()->getOnlinePlayers() as $target) {
                $plugin->giveKey($target, $amount, $args[0]);
                $target->sendMessage(TextFormat::GREEN . "You've received the " . ucfirst($args[0]) . " key!");
            }
            $sender->sendMessage(TextFormat::GREEN . "You've given EVERYONE the " . ucfirst($args[0]) . " key.");
        }
    }
}