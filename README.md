ShineUnited WordPress Tools
===========================


Installation
------------

```sh
$ composer require shineunited/wptools --dev
```

Commands
--------

### wp-init

Initializes the project with a basic bedrock install.

#### Usage

```sh
$ composer wp-init
```

### wp-require

Specialized alias of composer's require command for wordpress plugins and themes.

| Alias    | Description                                    |
| -------- | ---------------------------------------------- |
| plugin/* | installs plugin from wpackagist repository     |
| theme/*  | installs theme from wpackagist repository      |
| kinsta   | installs kinsta-mu-plugins                     |
| bedrock  | installs standard bedrock packages and plugins |

#### Usage

```sh
$ composer wp-require [packages]
```

### wp-remove

Specialized alias of composer's remove command for wordpress plugins and themes.

#### Usage

```sh
$ composer wp-remove [packages]
```
