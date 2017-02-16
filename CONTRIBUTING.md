# Contributing to the project

**Table of Contents**
-   [General](#general)
-   [Requirements](#requirements)
-   [Windows developer issues](#windows-issues)
-   [PHP Contributions](#php)
-   [JavaScript Contributions](#javascript)
-   [CSS Contributions](#css)
-   [Icons](#icons)
-   [Git Submodules used](#git-submodules)
-   [NPM Modules / Dev dependencies](#dev-dependencies)

-   -   -

## General
Write access to the GitHub repository is restricted, so make a fork and clone that.
All work should be done on its own branch, named according to the issue number
(*e.g. `42` or `bug/23`*). When you are finished with your work, push your feature
branch to your fork, preserving branch name (*not to master*), and create a pull
request.

All pull requests are tested using Travis-CI before being allowed to merge into
master. These tests consist of linting PHP and JavaScript code, as well as ensuring
that JavaScript coding style rules defined in [`.eslintrc`](./.eslintrc) are followed.
Additional tests may be added in the future.

**Always pull from `upstream master` prior to sending pull-requests.**

## Requirements
-   [Apache](https://httpd.apache.org/)
-   [PHP](https://secure.php.net/)
-   [MariaDB](https://mariadb.org/) or [MySQL](https://dev.mysql.com/)
-   [Node/NPM](https://nodejs.org/en/)
-   [Composer](https://getcomposer.org/)
-   [Git](https://www.git-scm.com/download/)

## Windows issues
> This project requires several command line tools which require installation and
> some configuration on Windows. The following will need to be added to your `PATH`
> in order to be functional. "Git Shell" & "Git Bash" that comes with GitHub Desktop
> or Git GUI are fairly usable so long as you select "Use Windows' default console window"
> during installation. See [Windows Environment Extension](https://technet.microsoft.com/en-us/library/cc770493.aspx)

-   PHP
-   Node
-   Git
-   MySQL
-   GPG (GPG4Win)

## PHP
This project uses PHP's native autoloader [`spl_autoload`](https://secure.php.net/manual/en/function.spl-autoload.php),
which is configured via `.travis.yml` and `.htaccess` environment variables.
Apache will automatically include the autoloader script using `php_value auto_prepend_file`,
but since this uses relative paths, it will only work correctly in the project's
root directory. To use in other directories, place a `.htaccess` and set the
relative path accordingly.

`spl_autoload` searches through include path (check `get_include_path`) for a
filepath matching the namespace and class attempting to be autoloading, converted
to lowercase (*`new \Vendor\Package\Class` searches for vendor/package/class.php*).

All pull requests **MUST** pass `php -l` linting, not raise any `E_STRICT` errors
when run, avoid usage or global variables, and not declare any constants or functions
in the global namespaces. All declared constants and functions must be in a file
whose namespace is set according to its path, relative to `DOCUMENT_ROOT`.

For development, [`shgysk8zer0\Core\Console`](https://github.com/shgysk8zer0/core/blob/master/console.php)
may be used for debugging/logging purposes.

```php
<?php
namespace KVSun\Sample;
use \shgysk8zer0\Core\Console;

Console::log($_SERVER);
Console::info($_REQUEST);
Console::table($my_array);

// Or as an error / exception handler
// This is already enabled for admins and when running from localhost
set_exception_handler('shgysk8zer0\Core\Console::error');
```

Since the minimum PHP version is 7.0, we can use code similar to the following:

```php
<?php
namespace KVSun\Sample;

/**
 * Sums up a list of numbers
 * @param Float $nums A list of numbers
 * @return Float      Their sum
 */
function sum(Float ...$nums): Float
{
	return array_sum($nums);
}

sum(1,2,3); // Returns 6
```

Where possible, functions and methods should use typehinting and declare
their return types, as seen in the above example.

## JavaScript
Due to Content-Security-Policy, use of `eval` and inline scripts are **prohibited**.
Further, this project uses ECMAScript 2015 [modules](http://exploringjs.com/es6/ch_modules.html),
so be sure to familiarize yourself with the syntax.

All JavaScript **MUST** pass Eslint according to the rules defined in `.eslintrc`
and have an extension of `.es6`.
Since this project minifies and packages all JavaScript using Babel & Webpack, with
the exception of `custom.es6`, all script **MUST NOT** execute any code, but only
import/export functions, classes, etc.

### Example module exporting
```js
// module.es6
// Export a function
export function $(sel) {
	// ...
}

// Export a class
export default class MyClass {
	//...
}

// Export a constant
export const MY_CONST = 'Can\'t touch this';

// Export a selection of things by name
export {something, somethingElse};

// Not exported / usable from external resources
function internalFunc() {
	// ...
}

const LOCAL_ONLY = 'For internal use only';
```

### Example module importing
```js
// Imports default module export as foo
import MyClass from './module.es6';

// Import and alias
import {foo as bar} from './path/to/module.es6';

// Import module exports by name
import {$, MyClass, MY_CONST} from './module.es6';

// Import all the things!
import * as stuff from './another/module.es6';
```

## CSS
Like in the above, one of the goals of this project is to keep things working
natively, which means standardized CSS and JavaScript. Although the features may
be new, `import` and `export` in JavaScript, and `@import` and `--var-name: value;`
are official standards. In the case of CSS, browser support does exist, and so
this project will use `@import` and CSS variables in favor of SASS or LESS.

```css
/* Import from other stylesheets */
@import "path/to/style.css";

/* Set a variable / custom property */
:root {
	--foo: #f00;
}

/* Use a variable / custom property */
.foo {
	color: var(--foo);
}
/* Easily set multiple bakgrounds */
.some-class {
	background-image: var(--bg-1) var(--bg-2);
}
/* Define sizes */
.size-me {
	width: var(--some-width);
}
/* Automatically resize to something else, making perfect sizing easy */
.auto-width {
	/* Units should match here, since we cannot compare "100vw - 50px" until runtime*/
	width: calc(100vw - var(--some-width));
}
```

## Icons
Wherever possible, all icons are to be created in SVG and minified. PNGs may then be created in whatever size is appropriate. Also, all commonly used icons are to be added to `images/icons.svg` so that they may be used using `<symbol>` and `<use xlink:href/>`. These are automatically generated
using `npm run build:icons`. To add more icons, simply add them to the
list located in `images/icons.csv` as `$id,path/to/icon.svg`.

## NPM
Several useful modules are included for Node users, which is strongly recommended for all development aside from PHP. Simply run `npm install` after download to install all Node modules and Git submodules. There are also several NPM scripts configured, which may be run using `npm run $script`.
-   `build:css` which transpiles and minifies CSS
-   `build:js` which transpiles and minifies JavaScript
-   `build:icons` which creates SVG sprites from `images/icons.json`
-   `build:all` which runs all of the above
-   `update` which updates Git submodules recursively, installing any new ones
-   `test` which runs any configured tests
NPM also has a `postinstall` script which will automatically install and update

## Git submodules
-   [shgysk8zer0/core_api](https://github.com/shgysk8zer0/core_api/)
-   [shgysk8zer0/core](https://github.com/shgysk8zer0/core/)
-   [shgysk8zer0/dom](https://github.com/shgysk8zer0/dom/)
-   [shgysk8zer0/phpcrypt](https://github.com/shgysk8zer0/phpcrypt/)
-   [shgysk8zer0/std-js](https://github.com/shgysk8zer0/std-js/)
-   [shgysk8zer0/core-css](https://github.com/shgysk8zer0/core-css/)
-   [shgysk8zer0/fonts](https://github.com/shgysk8zer0/fonts/)
-   [shgysk8zer0/svg-icons](https://github.com/shgysk8zer0/svg-icons/)
-   [shgysk8zer0/logos](https://github.com/shgysk8zer0/logos/)
-   [github/octicons](https://github.com/github/octicons/)
-   [necolas/normalize.css](https://github.com/necolas/normalize.css/)

## Dev dependencies
-   [Myth](http://www.myth.io/)
-   [Babel](https://babeljs.io/)
-   [Webpack](https://webpack.github.io/)
-   [ESLint](http://eslint.org/)
-   [svgo](https://github.com/svg/svgo)
-   [svg-sprite-generator](https://github.com/frexy/svg-sprite-generator)
