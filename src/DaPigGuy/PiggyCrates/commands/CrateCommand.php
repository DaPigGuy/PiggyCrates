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

	/**
	 * @param CommandSender $sender
	 * @param string $aliasUsed
	 * @param array $args
	 */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(PiggyCrates::getInstance()->getMessage("commands.use-in-game"));
            return;
        }
        if (!isset($args["type"])) {
            $sender->sendMessage("Usage: /crate <type>");
            return;
        }
        if ($args["type"] === "cancel") {
            if (!PiggyCrates::getInstance()->inCrateCreationMode($sender)) {
                $sender->sendMessage(PiggyCrates::getInstance()->getMessage("commands.crate.creation-mode.not-in-mode"));
                return;
            }
            PiggyCrates::getInstance()->setInCrateCreationMode($sender, null);
            $sender->sendMessage(PiggyCrates::getInstance()->getMessage("commands.crate.creation-mode.cancelled"));
            return;
        }
        $crate = PiggyCrates::getInstance()->getCrate($args["type"]);
        if ($crate === null) {
            $sender->sendMessage(PiggyCrates::getInstance()->getMessage("commands.crate.error.invalid-crate"));
            return;
        }
        PiggyCrates::getInstance()->setInCrateCreationMode($sender, $crate);
        $sender->sendMessage(PiggyCrates::getInstance()->getMessage("commands.crate.success"));
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