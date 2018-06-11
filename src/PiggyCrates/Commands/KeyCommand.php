<?php

namespace PiggyCrates\Commands;

use PiggyCrates\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

/**
 * Class KeyCommand
 * @package PiggyCrates\Commands
 */
class KeyCommand extends PluginCommand
{
    /**
     * KeyCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct(string $name, Main $plugin)
    {
        parent::__construct($name, $plugin);
        $this->setDescription("Give a crate key");
        $this->setUsage("/key <type> [player]");
        $this->setPermission("piggycrates.command.key");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return bool|mixed
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        $plugin = $this->getPlugin();
        if ($plugin instanceof Main) {
            if (!isset($args[0])) {
                $sender->sendMessage("Usage: /key <type> [amount] [player]");
                return false;
            }
            $target = $sender;
            $amount = 1;
            $args[0] = strtolower($args[0]);
            if (isset($args[2])) {
                $target = $plugin->getServer()->getPlayer($args[2]);
                if (!$target instanceof Player) {
                    $sender->sendMessage(TextFormat::RED . "Invalid player.");
                    return false;
                }
            } else {
                if (!$target instanceof Player) {
                    $sender->sendMessage(TextFormat::RED . "Please specify a player.");
                    return false;
                }
            }
            if (isset($args[1])) {
                if (is_numeric($args[1])) {
                    $amount = $args[1];
                } else {
                    $sender->sendMessage(TextFormat::RED . "Amount must be numeric.");
                    return false;
                }
            }
            if (!$plugin->getCrateType($args[0])) {
                $sender->sendMessage(TextFormat::RED . "Invalid crate type.");
                return false;
            }
            $plugin->giveKey($target, $amount, $args[0]);
            $sender->sendMessage(TextFormat::GREEN . ucfirst($args[0]) . " key has been given.");
            return true;
        }
        return false;
    }
}