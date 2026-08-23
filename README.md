# Shudd3r/Toolshed
[![Latest stable release](https://poser.pugx.org/shudd3r/toolshed/version)](https://packagist.org/packages/shudd3r/toolshed)
[![Build status](https://github.com/shudd3r/toolshed/workflows/build/badge.svg)](https://github.com/shudd3r/toolshed/actions)
[![Coverage status](https://coveralls.io/repos/github/shudd3r/toolshed/badge.svg?branch=develop)](https://coveralls.io/github/shudd3r/toolshed?branch=develop)
[![PHP version](https://img.shields.io/packagist/php-v/shudd3r/toolshed.svg)](https://packagist.org/packages/shudd3r/toolshed)
[![LICENSE](https://img.shields.io/github/license/shudd3r/toolshed.svg?color=blue)](LICENSE)
### Composer plugin orchestrating global dev tool packages

Most dev dependencies listed in `composer.json` of your projects are not
an integral part of these projects. At best, they are common libraries,
like testing frameworks, that your dev environment depends on. These tools
could be installed in shared locations and used remotely.
This is the whole idea behind **Toolshed** plugin.

##### Core features:
- **Avoid multiple installations of the same tool**, which might reduce
  filesystem clutter by thousands of files. Tools are installed, updated
  & synchronized in Composer's global subdirectories, so multiple versions
  of the same tool would still be available for different projects.

- **Isolate the project's dev environment** - "dirty" tool dependencies
  such as autoloaded polyfill functions might make your IDE behave as if
  your target PHP version supported them (which would remain true until
  the project reaches no-dev stage on production).

- **Neutral to non-plugin environments** - use dev tools as if they were
  installed as the project's local `require-dev` packages. Environments
  with the plugin installed shouldn't notice any difference beside reduced
  number of libraries within the `vendor` directory. The plugin maintains
  only _redirect binaries_ to shared tool executables.

### Installation with [Composer](https://getcomposer.org/)
```bash
composer global config allow-plugins.shudd3r/toolshed true
composer global require shudd3r/toolshed
```

### Basic Usage
Select commonly used tools from `require-dev` section of `composer.json`
that are not specific to your project's dev environment and add their
package names to `extra.shared-tools` list. For example:
```json
{
    "require-dev": {
        "phpunit/phpunit": "^12.0",
        "friendsofphp/php-cs-fixer": "^3.90",
        "project/template-builder": "2.4.*"
    },
    "extra": {
        "shared-tools": ["phpunit/phpunit", "friendsofphp/php-cs-fixer"]
    }
}
```
You can add this section directly to project's `composer.json` file or use
the following command (quotes might need escaping on Windows):
```bash
composer config --json --merge extra.shared-tools '["friendsofphp/php-cs-fixer"]'
```

Now, running `composer update` or `install` will remove the listed tool
files from project's `vendor` directory and move them to `shared-tools` in
Composer's global `home` location, leaving only short _redirect binaries_
in your `vendor/bin` directory instead. The goal is to make the tools work
as if the plugin was not installed.

### TODO & Known issues
> [!WARNING]
> Links to shared tools are not removed after tool is removed from list
> or plugin is deactivated (either globally or `shared-tools` removed
> from `composer.json`. Composer doesn't overwrite existing binary files.
>
> **TODO:** Requires reorganizing link creation control flow

> [!WARNING]
> IDE integrations (PhpStorm):
> - Tool namespace references require adding external libraries, which
>   would import unwanted classes and polyfill functions, defeating the
>   isolation goal.
> - Configuration for PHPUnit tests requires either the `.phar` location
>   or the path to composer's `autoload.php`, which might change
>   dynamically due to version updates.
>
> **Possible solution**: Merged `autoload.php` for main namespaces or
> static redirect file

> [!WARNING]
> Relative paths for tool commands that directly or indirectly refer
> to resources from `vendor` directory will no longer be valid.
>
> **Possible solution**: Custom binaries (outside plugin scope)

> [!WARNING]
> Faulty path resolution in binary files. For example Composer
> autoload.php` lookup based on current working directory.
>
> **Possible solution**: Fixing PRs or custom binaries
