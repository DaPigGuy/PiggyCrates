<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates\tasks;

use DaPigGuy\PiggyCrates\PiggyCrates;
use Exception;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;

class CheckUpdatesTask extends AsyncTask
{
    public function onRun(): void
    {
        $this->setResult([Internet::getURL("https://poggit.pmmp.io/releases.json?name=PiggyCrates", 10, [], $error), $error]);
    }

    public function onCompletion(Server $server): void
    {
        /** @var PiggyCrates $plugin */
        $plugin = $server->getPluginManager()->getPlugin("PiggyCrates");
        try {
            if ($plugin->isEnabled()) {
                $results = $this->getResult();

                $error = $results[1];
                if ($error !== null) throw new Exception($error);

                $data = json_decode($results[0], true);
                if (version_compare($plugin->getDescription()->getVersion(), $data[0]["version"]) === -1) {
                    if ($server->getPluginManager()->isCompatibleApi($data[0]["api"][0]["from"])) {
                        $plugin->getLogger()->info("PiggyCrates v" . $data[0]["version"] . " is available for download at " . $data[0]["artifact_url"] . "/PiggyCrates.phar");
                    }
                }
            }
        } catch (Exception $exception) {
            $plugin->getLogger()->warning("Auto-update check failed.");
            $plugin->getLogger()->debug((string)$exception);
        }
    }
}