# Git Clone

Clone the Dixlase repository to get the latest source code with full version control. This is the recommended method for developers who want to stay up to date with the latest changes.

## Prerequisites

- [Git](https://git-scm.com/) installed on your system
- [Composer](https://getcomposer.org/) 2.x

## Steps

```bash
# Clone the repository
git clone https://github.com/Dixlase/dixlase-core.git dixlase
cd dixlase

# Install PHP dependencies
composer install
```

## Cloning a Specific Version

To clone a specific release, name its tag (see the [releases page](https://github.com/Dixlase/dixlase-core/releases) for the list):

```bash
git clone --branch v0.2.2 https://github.com/Dixlase/dixlase-core.git dixlase
cd dixlase
composer install
```

## Updating

`main` always holds the latest release. Pull it and update dependencies:

```bash
git pull origin main
composer install
```

## Next Steps

After cloning, proceed to set up your [development environment](../development/index.md) or [production environment](../production/index.md).
