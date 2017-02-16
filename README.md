# kvsun.com
[Kern Valley Sun website](https://kernvalleysun.com)

[![screenshot](./screenshot.png)](https://kernvalleysun.com)

[![Build Status](https://travis-ci.org/KVSun/kvsun.com.svg?branch=master)](https://travis-ci.org/KVSun/kvsun.com)
[![Latest Stable Version](https://poser.pugx.org/kvsun/kvsun.com/v/stable)](https://packagist.org/packages/kvsun/kvsun.com)
[![Total Downloads](https://poser.pugx.org/kvsun/kvsun.com/downloads)](https://packagist.org/packages/kvsun/kvsun.com)
[![License](https://poser.pugx.org/kvsun/kvsun.com/license)](https://packagist.org/packages/kvsun/kvsun.com)
[![composer.lock](https://poser.pugx.org/kvsun/kvsun.com/composerlock)](https://packagist.org/packages/kvsun/kvsun.com)
[![Gitter](https://badges.gitter.im/KernValleySun/kvsun.com.svg)](https://gitter.im/KernValleySun/kvsun.com?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
- - -

## Quick Nav
-   [Requirements](#requirements)
-   [Installation](#installation)
-   [Contributing](./CONTRIBUTING.md)
-   [Security](#security)
-   [Contacting Us](#contacting-us)

## Requirements
-   [PHP](https://secure.php.net/) >= 7.0
-   [NPM](https://www.npmjs.com/) >= 3.10
-   [Composer](https://getcomposer.org/) >= 1.3.2
-   [MariaDB](https://mariadb.org/) ~15.1 or [MySQL](https://dev.mysql.com/)

## Installation
```sh
git clone git://github.com/KVSun/kvsun.com.git
cd kvsun.com
npm install
sudo mysql
#... Create database and user
```

## Contacting Us
-   [Report an issue](https://github.com/KVSun/kvsun.com/issues)
-   [Open a pull request](https://github.com/KVSun/kvsun.com/pull/new/master)
-   [Email](mailto:czuber@kvsun.com)
-   [Phone](tel:+17603793667,14)

## Security
-   All SQL queries taking user input use [`PDO::prepare`](https://secure.php.net/manual/en/pdo.prepare.php) to prevent SQL injection
-   Use [Content-Security-Policy](https://developer.mozilla.org/en-US/docs/Web/Security/CSP) to prevent loading of unautorized resources
-   Use [`shgysk8zer0\PHPCrypt`](https://github.com/shgysk8zer0/phpcrypt) for encryption and cryptographic signatures
