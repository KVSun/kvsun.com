<?php
namespace KVSun\Unit
{
	const ERROR_LOG = './errors.log';
	if (PHP_SAPI !== 'cli') {
		http_response_code(503);
		exit();
	} elseif (version_compare(\PHP_VERSION, '7.0.0', '<')) {
		echo 'PHP version 7 or greater is required.' . PHP_EOL;
		exit(1);
	}

	/**
	 * Function to register in `set_error_handler`, throws an `\ErrorException`
	 * @param  integeer $errno      Error level
	 * @param  String   $errstr     Error message
	 * @param  String   $errfile    File the error occured in
	 * @param  integer  $errline    Line in $errfile
	 * @param  array    $errcontext Variables defined in the scope of error
	 * @return Bool                 True prevent default error handler
	 */
	function error_handler(
		Int    $errno,
		String $errstr,
		String $errfile    = null,
		Int    $errline    = null,
		Array  $errcontext = array()
	) : Bool
	{
		exception_handler(new \ErrorException($errstr, 0, $errno, $errfile, $errline));
		return true;
	}

	/**
	 * Function to register in `set_exception_handler`. Logs and echoes exception, then exits
	 * @param  Throwable $exc  The error or exception
	 * @return void
	 */
	function exception_handler(\Throwable $exc)
	{
		error_log($exc . PHP_EOL, 3, ERROR_LOG);
		echo $exc;
		// exit(1);
	}

	/**
	 * Recursively lint a directory
	 * @param  String   $dir            Directory to lint
	 * @param  Array    $exts           Array of extensions to lint in directory
	 * @param  Array    $ignore_dirs    Ignore directories in this array
	 * @param  Callable $error_callback Callback to call when linting fails
	 * @return Bool                     Whether or not all files linted without errors
	 * @see https://secure.php.net/manual/en/class.recursiveiteratoriterator.php
	 */
	function lint_dir(
		String   $dir            = __DIR__,
		Array    $exts           = ['php', 'phtml'],
		Array    $ignore_dirs    = ['.git', 'node_modules', 'vendor'],
		Callable $error_callback = null
	): Bool
	{
		$path = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);

		ob_start();
		while ($path->valid()) {
			if ($path->isFile() and in_array($path->getExtension(), $exts)) {
				$output = [];
				$msg = @exec(
					sprintf("php -l %s", escapeshellarg($path->getPathName())),
					$output,
					$return_var
				);

				if ($return_var !== 0) {
					if (isset($error_callback)) {
						$error_callback($msg);
						return false;
					} else {
						throw new \ParseError($msg);
					}
				}
			} elseif ($path->isDir() and ! in_array($path, $ignore_dirs)) {
				// So long as $dir is the first argument of the function, this will
				// always work, even if the name of the function changes.
				$args = array_slice(func_get_args(), 1);
				if (! call_user_func(__FUNCTION__, $path->getPathName(), ...$args)) {
					return false;
				}
			}
			$path->next();
		}
		ob_get_clean();
		return true;
	}

	// Setup autoloading
	set_include_path(realpath('./classes') . PATH_SEPARATOR . get_include_path());
	spl_autoload_register('spl_autoload');

	// Set error and exception handlers
	set_error_handler(__NAMESPACE__ . '\error_handler', E_ALL);
	set_exception_handler(__NAMESPACE__ . '\exception_handler');

	// Set asser options
	assert_options(ASSERT_ACTIVE, 1);
	assert_options(ASSERT_WARNING, 1);
	assert_options(ASSERT_BAIL, 1);

	assert(lint_dir(
		__DIR__,
		['php'], [
			'.git',
			'node_modules',
			'vendor',
			'scripts',
			'stylesheets',
			'fonts',
			'custom-fonts',
			'config',
			'images',
			'keys',
			'rss',
		]),
		'Lint PHP scripts'
	);
	echo 'All test completed.';
}
