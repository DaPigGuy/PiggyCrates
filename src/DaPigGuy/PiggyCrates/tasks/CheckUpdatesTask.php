<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates\tasks;

use DaPigGuy\PiggyCrates\PiggyCrates;
use Exception;
use pocketmine\plugin\ApiVersion;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\Internet;

class CheckUpdatesTask extends AsyncTask
{
    public function onRun(): void
    {
        $this->setResult([Internet::getURL("https://poggit.pmmp.io/releases.json?name=PiggyCrates", 10, [], $error), $error]);
    }

    public function onCompletion(): void
    {
        $plugin = PiggyCrates::getInstance();
        try {
            if ($plugin->isEnabled()) {
                $results = $this->getResult();

                $error = $results[1];
                if ($error !== null) throw new Exception($error);

                $data = json_decode($results[0]->getBody(), true);
                if (version_compare($plugin->getDescription()->getVersion(), $data[0]["version"]) === -1) {
                    if (ApiVersion::isCompatible($plugin->getServer()->getApiVersion(), $data[0]["api"][0])) {
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