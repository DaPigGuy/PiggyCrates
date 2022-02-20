# PiggyCrates [![Poggit-CI](https://poggit.pmmp.io/shield.dl/PiggyCrates)](https://poggit.pmmp.io/p/PiggyCrates) [![Discord](https://img.shields.io/discord/330850307607363585?logo=discord)](https://discord.gg/qmnDsSD)

PiggyCrates is a simple and customizable crates plugin, supporting an unlimited amount of crate types. It also supports vanilla and custom enchants, such as [PiggyCustomEnchants](https://github.com/DaPigGuy/PiggyCustomEnchants/). 

## Prerequisites
* Basic knowledge on how to install plugins from Poggit Releases and/or Poggit CI
* PMMP 4.0.0 or greater

## Installation & Setup
1. Install the plugin from Poggit.
2. Start your server.
3. (Optional) The `config.yml` file has a few options that allow you to customize your key items & crate modes (default is roulette). 
4. Open the `crates.yml`.
5. PiggyCrates supports an unlimited number of crates. To define a crate, add a key `crates.yourcratename`.
6. Configure your crate types. Crate types have several different properties:
   * (Optional) `floating-text`: Floating text that will appear above the crate type. Leave empty for no floating text.
   * (Optional) `commands`: Commands to be run by CONSOLE when crate type is opened. Use `{PLAYER}` as a placeholder for player name.
   * `drops`: Possible drops of a crate type. Items are defined with the properties:
     * `id`: Item ID
     * `meta`: Item Meta
     * `amount`: Item Amount
     * (Optional) `type`: Item Type
       * (Default) `item`: Runs all crate item commands & gives the item
       * `command`: Runs all crate item commands
     * (Optional) `chance`: Item Weight
     * (Optional) `nbt`: Item NBT as stringified JSON
     * (Optional) `name`: Item Name
     * (Optional) `lore`: Item Lore as string with line breaks represented as `\n`
     * (Optional) `enchantments`: Item enchantments defined like so:
       ```yaml
       enchantments:
        - name: "Protection"
          level: 1
        - name: "Thorns"
          level: 1
       ```
     * (Optional) `commands`: Commands to be run by CONSOLE if item is dropped by crate.
   * `amount`: Amount of drops a single crate type will give.
   
   **Example**:
   ```yaml
   crates:
     # Crate type named "Example"
     Example:
       floating-text: "Example Crate"
       amount: 1
       # Will run the command "/say" when opened
       commands:
         - "say {PLAYER} has opened the example crate!"
       drops:
         # 50% chance for 1x Diamond Sword named Sharpened Diamond Sword w/ Sharpness 5 enchantment
         # Will run the command "/tell" on drop
         - id: 276
           meta: 0
           amount: 1
           chance: 50
           name: "Sharpened Diamond Sword"
           enchantments:
             - name: "Sharpness"
               level: 5
           commands:
             - "tell {PLAYER} You got a Sharpened Diamond Sword! ;o"
         # Identical to the above drop but with a 25% chance and an Iron Sword
         - id: 267
           meta: 0
           amount: 1
           chance: 25
           name: "Sharpened Iron Sword"
           enchantments:
             - name: "Sharpness"
               level: 5
           commands:
             - "tell {PLAYER} You got a Sharpened Iron Sword! ;o"
         # 25% chance for player to get money
         - id: 266
           meta: 0
           amount: 1
           chance: 25
           name: "$2500"
           type: command
           commands:
             - "givemoney {PLAYER} 2500"
    ```
7. Restart your server.
8. Connect to your server.
9. Place a chest block where you intend on having a crate.
9. Run the command `/crate <crate name>`
10. Tap the target chest.
11. Repeat with other crate types.
12. You're done!

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

## Additional Information
* We do not support any spoons. Anything to do with spoons (Issues or PRs) will be ignored.
* We are using the following virions: [Commando](https://github.com/CortexPE/Commando) and [InvMenu](https://github.com/Muqsit/InvMenu).
    * **Unless you know what you are doing, use the pre-compiled phar from [Poggit-CI](https://poggit.pmmp.io/ci/DaPigGuy/PiggyCrates/~) and not GitHub.**
    * If you wish to run it via source, check out [DEVirion](https://github.com/poggit/devirion).
* Any custom enchantment plugin can be used, as long as the custom enchant has been registered to the server.
* Detailed Installation Guide available at [PiggyDocs](https://piggydocs.aericio.net/PiggyCrates.html).
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