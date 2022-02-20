<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates\commands;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use DaPigGuy\PiggyCrates\PiggyCrates;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class KeyCommand extends BaseCommand
{
    /** @var PiggyCrates */
    protected $plugin;
    
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!isset($args["type"])) {
            $sender->sendMessage("Usage: /key <type>");
            return;
        }
        if (!$sender instanceof Player && !isset($args["player"])) {
            $sender->sendMessage("Usage: /key <type> <amount> <player>");
            return;
        }
        $target = empty($args["player"]) ? $sender : $this->plugin->getServer()->getPlayerExact($args["player"]);
        if (!$target instanceof Player) {
            $sender->sendMessage($this->plugin->getMessage("commands.key.error.invalid-player"));
            return;
        }
        /** @var int $amount */
        $amount = $args["amount"] ?? 1;
        if (!is_numeric($amount)) {
            $sender->sendMessage($this->plugin->getMessage("commands.key.error.not-numeric"));
            return;
        }
        $crate = $this->plugin->getCrate($args["type"]);
        if ($crate === null) {
            $sender->sendMessage($this->plugin->getMessage("commands.key.error.invalid-crate"));
            return;
        }
        $crate->giveKey($target, $amount);
        $target->sendMessage($this->plugin->getMessage("commands.key.success.sender", ["{CRATE}" => $crate->getName()]));
        $sender->sendMessage($this->plugin->getMessage("commands.key.success.target", ["{CRATE}" => $crate->getName(), "{TARGET}" => $target->getName()]));

    }

    /**
     * @throws ArgumentOrderException
     */
    public function prepare(): void
    {
        $this->setPermission("piggycrates.command.key");
        $this->registerArgument(0, new RawStringArgument("type"));
        $this->registerArgument(1, new IntegerArgument("amount", true));
        $this->registerArgument(2, new RawStringArgument("player", true));
    }
}