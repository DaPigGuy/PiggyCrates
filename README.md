# PiggyCrates [![Poggit-CI](https://poggit.pmmp.io/ci.badge/DaPigGuy/PiggyCrates/PiggyCrates/master)](https://poggit.pmmp.io/ci/DaPigGuy/PiggyCrates)

PiggyCrates is a simple and customizable crates plugin, supporting an unlimited amount of crate types. It also supports vanilla and custom enchants, such as [PiggyCustomEnchants](https://github.com/DaPigGuy/PiggyCustomEnchants/). 

## Commands
| Command | Description | Permissions | Aliases
| --- | --- | --- | --- |
| `/crate <crate>` | Changes a chest to a crate by tapping | `piggycrates.command.crate` | N/A |
| `/key` | Gives a player a specific crate key | `piggycrates.command.key` | N/A |
| `/keyall` | Gives all online players a specific crate key | `piggycrates.command.keyall` | N/A |

## Permissions
| Permissions | Description | Default |
| --- | --- | --- |
| `piggycrates` | Allows usage of all PiggyCrates features | `false` |
| `piggycrates.command` | Allow usage of all PiggyCrates commands | `op` |
| `piggycrates.command.crate` | Allow usage of all /crate commands | `op` |
| `piggycrates.command.key` | Allow usage of the /key command | `op` |
| `piggycrates.command.keyall` | Allow usage of the /keyall command | `op` |

## Issue Reporting
* If you experience an unexpected non-crash behavior with PiggyCrates, click [here](https://github.com/DaPigGuy/PiggyCrates/issues/new?assignees=DaPigGuy&labels=bug&template=bug_report.md&title=).
* If you experience a crash in PiggyCrates, click [here](https://github.com/DaPigGuy/PiggyCrates/issues/new?assignees=DaPigGuy&labels=bug&template=crash.md&title=).
* If you would like to suggest a feature to be added to PiggyCrates, click [here](https://github.com/DaPigGuy/PiggyCrates/issues/new?assignees=DaPigGuy&labels=suggestion&template=suggestion.md&title=).
* If you require support, please join our discord server [here](https://discord.gg/qmnDsSD).
* Do not file any issues related to outdated API version; we will resolve such issues as soon as possible.
* We do not support any spoons of PocketMine-MP. Anything to do with spoons (Issues or PRs) will be ignored.
  * This includes plugins that modify PocketMine-MP's behavior directly, such as TeaSpoon.

## Information
* We do not support any spoons. Anything to do with spoons (Issues or PRs) will be ignored.
* We are using the following virions: [Commando](https://github.com/CortexPE/Commando).
    * **You MUST use the pre-compiled phar from [Poggit-CI](https://poggit.pmmp.io/ci/DaPigGuy/PiggyCrates/~) instead of GitHub.**
    * If you wish to run it via source, check out [DEVirion](https://github.com/poggit/devirion).
* Check out our installation guide at [PiggyDocs](https://piggydocs.aericio.net/PiggyCrates.html).
  * Any custom enchantment plugin can be used, as long as the custom enchant has been registered to the server.
* Check out our [Discord Server](https://discord.gg/qmnDsSD) for additional plugin support.

## License
```
   Copyright 2018-2020 DaPigGuy

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

```