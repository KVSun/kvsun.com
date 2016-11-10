<?php
namespace Test_Funcs;
/**
 * Lint a script and assert that it uses valid syntax
 *
 * @param  string $script The PHP script to lint
 * @return void
 */
function lint_script($script)
{
	$clean_syntax = php_check_syntax($script, $syntax_errors);
	assert($clean_syntax, $syntax_errors . __FILE__);
}

/**
 * Get all of the sub-directories withing a directory
 *
 * @param  string $dir The directory to scan
 * @return void
 */
function get_dirs($dir = __DIR__)
{
	return glob("{$dir}/*", GLOB_ONLYDIR);
}

/**
 * Scan a directory for "*.php"
 *
 * @param  string $dir The directory to scan
 * @return void
 */
function get_scripts($dir = __DIR__)
{
	return glob("{$dir}/*.php");
}

/**
 * Recursively lint the PHP scripts in a directory
 *
 * @param  string $dir Directory to scan recursively
 * @return void
 */
function lint_scripts_recursive($dir = __DIR__)
{
	static $funcs;
	if (is_null($funcs)) {
		$funcs = \shgysk8zer0\Core\NamespacedFunction::load(__NAMESPACE__);
	}
	array_map($funcs->lint_script, get_scripts($dir));
	array_map(__FUNCTION__, get_dirs($dir));
}
