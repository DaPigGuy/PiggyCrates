<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates\commands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use DaPigGuy\PiggyCrates\PiggyCrates;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

/**
 * Class CrateCommand
 * @package DaPigGuy\PiggyCrates\commands
 */
class CrateCommand extends BaseCommand
{
    /** @var PiggyCrates */
    private $plugin;

    /**
     * @param PiggyCrates $plugin
     * @param string $name
     * @param string $description
     * @param string[] $aliases
     */
    public function __construct(PiggyCrates $plugin, string $name, string $description = "", array $aliases = [])
    {
        $this->plugin = $plugin;
        parent::__construct($name, $description, $aliases);
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array $args
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "Please use this in-game.");
            return;
        }
        if (!isset($args["type"])) {
            $sender->sendMessage("Usage: /crate <type>");
            return;
        }
        if ($args["type"] === "cancel") {
            if (!PiggyCrates::inCrateCreationMode($sender)) {
                $sender->sendMessage(TextFormat::RED . "You are not in crate creation mode.");
                return;
            }
            PiggyCrates::setInCrateCreationMode($sender, null);
            $sender->sendMessage(TextFormat::GREEN . "Crate creation cancelled.");
            return;
        }
        $crate = PiggyCrates::getCrate($args["type"]);
        if ($crate === null) {
            $sender->sendMessage(TextFormat::RED . "Invalid crate.");
            return;
        }
        PiggyCrates::setInCrateCreationMode($sender, $crate);
        $sender->sendMessage(TextFormat::GREEN . "Please tap a chest block to create a crate, or use /crate cancel to cancel.");
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