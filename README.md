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

...
