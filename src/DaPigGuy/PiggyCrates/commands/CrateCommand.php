<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates\commands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use DaPigGuy\PiggyCrates\PiggyCrates;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class CrateCommand extends BaseCommand
{
    /** @var PiggyCrates */
    protected $plugin;

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->plugin->getMessage("commands.use-in-game"));
            return;
        }
        if (!isset($args["type"])) {
            $sender->sendMessage("Usage: /crate <type>");
            return;
        }
        if ($args["type"] === "cancel") {
            if (!$this->plugin->inCrateCreationMode($sender)) {
                $sender->sendMessage($this->plugin->getMessage("commands.crate.creation-mode.not-in-mode"));
                return;
            }
            $this->plugin->setInCrateCreationMode($sender, null);
            $sender->sendMessage($this->plugin->getMessage("commands.crate.creation-mode.cancelled"));
            return;
        }
        $crate = $this->plugin->getCrate($args["type"]);
        if ($crate === null) {
            $sender->sendMessage($this->plugin->getMessage("commands.crate.error.invalid-crate"));
            return;
        }
        $this->plugin->setInCrateCreationMode($sender, $crate);
        $sender->sendMessage($this->plugin->getMessage("commands.crate.success"));
    }

    /**
     * @throws ArgumentOrderException
     */
    public function prepare(): void
    {
        $this->setPermission("piggycrates.command.crate");
        $this->registerArgument(0, new RawStringArgument("type"));
    }
}