<?php
namespace KVSun;

use \shgysk8zer0\Core as Core;
use \shgysk8zer0\Core_API as API;
use \shgysk8zer0\DOM as DOM;

/**
 * Create `<dialog>` & `<form>` for updating user data
 * @param  shgysk8zer0\Login\User          $user User data to update from
 * @return shgysk8zer0\DOM\HTMLElement     `<dialog><form>...</dialog>`
 */
function user_update_form(\shgysk8zer0\Login\User $user)
{
	$dom = new DOM\HTML();
	$dialog = $dom->body->append('dialog', null, [
		'id' => 'update-user-dialog',
	]);
	$dialog->append('br');
	$logout = $dialog->append('button', null, [
		'type' => 'buton',
		'title' => 'Logout',
		'data-request' => 'action=logout',
		'data-confirm' => 'Are you sure you want to logout?',
	]);
	use_icon('sign-out', $logout, ['height' => 32, 'width' => 32]);
	$dialog->append('button', null, [
		'data-delete' => "#{$dialog->id}",
	]);

	$form = $dialog->append('form', null, [
		'name' => 'user-update',
		'action' => '/api.php',
		'method' => 'POST',
	]);

	$form->append('h4', 'Change avatar');
	$form->append('a', null, [
		'href' => 'https://gravatar.com/',
		'target' => '_blank',
	])->append('img', null, [
		'src' => new Core\Gravatar($user->email, 128),
		'height' => 128,
		'width' => 128,
		'alt' => 'Update user image on Gravatar',
		'title' => 'Update user image on Gravatar',
	]);

	$fieldset = $form->append('fieldset');
	$fieldset->append('legend', 'User info');

	$label = $fieldset->append('label', 'Email');
	$input = $fieldset->append('input', null, [
		'name' => "{$form->name}[email]",
		'id' => "{$form->name}-email",
		'type' => 'email',
		'value' => $user->email,
		'placeholder' => 'user@example.com',
		'required' => '',
	]);
	$label->for = $input->id;
	$fieldset->append('br');

	$label = $fieldset->append('label', 'Name');
	$input = $fieldset->append('input', null, [
		'name' => "{$form->name}[name]",
		'id' => "{$form->name}-name",
		'type' => 'text',
		'value' => isset($user->name) ? $user->name : null,
		'placeholder' => 'Firstname Lastname',
		'pattern' => '^[A-z]+ [A-z]+$',
	]);
	$label->for = $input->id;
	$fieldset->append('br');

	$label = $fieldset->append('label', 'Phone number');
	$input = $fieldset->append('input', null, [
		'name' => "{$form->name}[tel]",
		'id' => "{$form->name}-tel",
		'type' => 'tel',
		'value' => isset($user->tel) ? $user->tel : null,
		'placeholder' => '1-760-379-1234',
	]);
	$label->for = $input->id;
	$fieldset->append('br');

	$label = $fieldset->append('label', 'Twitter URL');
	$input = $fieldset->append('input', null, [
		'name' => "{$form->name}[twitter]",
		'id' => "{$form->name}-twitter",
		'type' => 'url',
		'value' => isset($user->twitter) ? $user->twitter : null,
		'placeholder' => 'https://twitter.com/{USER}',
		'pattern' => '^https://twitter\.com/[\w]{1,32}$',
	]);
	$label->for = $input->id;
	$fieldset->append('br');

	$label = $fieldset->append('label', 'G+ URL');
	$input = $fieldset->append('input', null, [
		'name' => "{$form->name}[g+]",
		'id' => "{$form->name}-gplus",
		'type' => 'url',
		'value' => isset($user->{'g+'}) ? $user->{'g+'} : null,
		'placeholder' => 'https://plus.google.com/u/0/+{USER}',
		'pattern' => '^https://plus.google.com/u/0/\+[\w]{1,35}$'
	]);
	$label->for = $input->id;
	$fieldset->append('br');

	$form->append('hr');

	$form->append('button', 'Submit', ['type' => 'submit']);
	$form->append('button', 'reset', ['type' => 'reset']);
	return $dialog;
}

function make_cc_form(DOM\HTMLElement $parent = null, $name = 'ccform')
{
	if (is_null($parent)) {
		$dom = new DOM\HTML();
		$parent = $dom->body;
	}
	$user = restore_login();
	$form = $parent->append('form', null, [
		'method' => 'POST',
		'action' => '/api.php',
		'name' => $name,
	]);

	$fieldset = $form->append('fieldset');
	$fieldset->append('legend', 'Authorize.net Credentials');
	$label = $fieldset->append('label', 'Name');
	$input = $fieldset->append('input', null, [
		'type' => 'text',
		'name' => "{$form->name}[auth][name]",
		'id' => "{$form->name}-auth-name",
		'required' => '',
	]);

	$label->for = $input->id;
	$fieldset->append('br');

	$label = $fieldset->append('label', 'Transaction Key');
	$input = $fieldset->append('input', null, [
		'name' => "{$form->name}[auth][key]",
		'id' => "{$form->name}-auth-key",
		'require' => ''
	]);
	$label->for = $input->id;

	$fieldset->append('br');
	$label = $fieldset->append('label', 'Sandbox?');
	$input = $fieldset->append('input', null, [
		'type' => 'checkbox',
		'name' => "{$form->name}[auth][sandbox]",
		'checked' => ''
	]);

	$label->for = $input->id;

	$fieldset->append('br');

	$label = $fieldset->append('label', '$');
	$input = $fieldset->append('input', null, [
		'type' => 'number',
		'name' => "{$form->name}[cost]",
		'id' => "{$form->name}-cost",
		'min' => 0.01,
		'step' => 0.01,
		'placeholder' => '2.78',
		'required' => '',
	]);
	$label->for = $input->id;

	$form->importHTML(file_get_contents('components/forms/ccform.html'));

	// $fieldset = $form->append('fieldset');
	//
	// use_icon('credit-card', $fieldset->append('legend'), ['title' => 'Credit Card info']);
	//
	// $label = $fieldset->append('label', 'Name on Card');
	// $input = $fieldset->append('input' ,null, [
	// 	'type' => 'text',
	// 	'name' => "{$form->name}[name]",
	// 	'id' => "{$form->name}-name",
	// 	'value' => isset($user->name) ? $user->name : null,
	// 	'placeholder' => 'Name on credit card',
	// 	'pattern' => '^[A-z]+ ([A-z]+ )?[A-z]+$',
	// 	'autocomplete' => 'cc-name',
	// 	'required' => '',
	// ]);
	//
	// $label->for = $input->id;
	// $fieldset->append('br');
	//
	// $label = $fieldset->append('label', 'Credit Card #');
	//
	// $input = $fieldset->append('input',null, [
	// 	'name' => "{$form->name}[ccnum]",
	// 	'id' => "{$form->name}-ccnum",
	// 	'type' => 'number',
	// 	'min' => pow(10,13),
	// 	'max' => pow(10, 17) - 1,
	// 	'autocomplete' => 'cc-number',
	// 	'size' => 16,
	// 	'placeholder' => '#############',
	// 	'required' => '',
	// ]);
	// $label->for = $input->id;
	//
	// $fieldset->append('br');
	//
	// $label = $fieldset->append('label', 'Credit Card expiration');
	// $fieldset->append('br');
	// $input = $fieldset->append('input', null, [
	// 	'name' => "{$form->name}[expires][month]",
	// 	'id' => "{$form->name}-expires-month",
	// 	'type' => 'number',
	// 	'min' => 1,
	// 	'max' => 12,
	// 	'placeholder' => 'mm',
	// 	'size' => 2,
	// 	'maxlength' => 2,
	// 	'minlength' => 2,
	// 	'autocomplete' => 'cc-exp-month',
	// 	'required' => '',
	// ]);
	// $fieldset->append('span', '/');
	// $fieldset->append('input', null, [
	// 	'name' => "{$form->name}[expires][year]",
	// 	'id' => "{$form->name}-expires-year",
	// 	'type' => 'number',
	// 	'min' => date('Y'),
	// 	'max' => date('Y') + 20,
	// 	'autocomplete' => 'cc-exp-year',
	// 	'placeholder' => 'YYYY',
	// 	'maxlength' => 4,
	// 	'minlength' => 4,
	// 	'size' => 4,
	// 	'required' => ''
	// ]);
	//
	// $label->for = $input->id;
	// $fieldset->append('br');
	//
	// $label = $fieldset->append('Label', 'CSC');
	// $input = $fieldset->append('input', null, [
	// 	'name' => "{$form->name}[csc]",
	// 	'id' => "{$form->name}-csc",
	// 	'type' => 'number',
	// 	'min' => 100,
	// 	'max' => 9999,
	// 	'placeholder' => '####',
	// 	'autocomplete' => 'cc-csc',
	// 	'size' => 4,
	// 	'required' => ''
	// ]);
	// $label->for = $input->id;
	// $fieldset->append('br');
	//
	// $fieldset = $form->append('fieldset');
	// $fieldset->append('legend', 'Payment Info');
	// $label = $fieldset->append('label', 'Cost');
	// $input = $fieldset->append('input', null, [
	// 	'name' => "{$form->name}[cost]",
	// 	'id' => "{$form->name}-cost",
	// 	'type' => 'number',
	// 	'min' => '0',
	// 	'step' => 0.01,
	// 	'placeholder' => '1.00',
	// 	'value' => 1,
	// 	'size' => 4,
	// 	'width' => 4,
	// 	'required' => '',
	// ]);
	// $label->for = $input->id;

	$form->append('button', 'Submit', ['type' => 'submit']);
	file_put_contents('/home/shgysk8zer0/html/kvsun.com/components/forms/Birth.html', $form);
	return $form;
}

/**
 * [exception_error_handler description]
 * @param  Int    $severity [description]
 * @param  String $message  [description]
 * @param  String $file     [description]
 * @param  Int    $line     [description]
 * @return Bool             [description]
 */
function exception_error_handler(
	$severity,
	$message,
	$file,
	$line
)
{
	$e = new \Throwable($message, 0, $severity, $file, $line);
	Core\Console::getInstance()->error(['error' => [
		'message' => $e->getMessage(),
		'file'    => $e->getFile(),
		'line'    => $e->getLine(),
		'code'    => $e->getCode(),
		'trace'   => $e->getTrace(),
	]]);
	return true;
}

/**
 * Gets login user from cookie or session
 * @param void
 * @return shgysk8zer0\Login\User [description]
 */
function restore_login()
{
	return \shgysk8zer0\Login\User::restore('user', DB_CREDS);
}

function check_role($role = 'admin')
{
	$user = restore_login();
	if (! in_array($role, USER_ROLES)) {
		throw new \InvalidArgumentException("$role is not a valid user role.");
	}
	return isset($user->status) and array_search($role, USER_ROLES) >= $user->status;
}

function setcookie(
	$name,
	$value,
	$httpOnly = true,
	$path = '/'
)
{
	return \setcookie(
		$name,
		$value,
		strtotime('+1 month'),
		$path,
		$_SERVER['HTTP_HOST'],
		array_key_exists('HTTPS', $_SERVER),
		$httpOnly
	);
}

function make_datalist($name, Array $items, $return_string = true)
{
	$tmp = new \DOMDocument();
	$datalist = $tmp->appendChild($tmp->createElement('datalist'));
	$datalist->setAttribute('id', $name);
	foreach ($items as $item) {
		$option = $datalist->appendChild($tmp->createElement('option'));
		$option->setAttribute('value', $item);
	}

	return $return_string ? $tmp->saveHTML($datalist) : $datalist;
}

/**
 * [use_icon description]
 * @param  String         $icon   [description]
 * @param  DOMHTMLElement $parent [description]
 * @param  array          $attrs  [description]
 * @return [type]                 [description]
 */
function use_icon(
	$icon,
	DOM\HTMLElement $parent,
	Array $attrs = array()
)
{
	$attrs = array_merge([
		'xmlns'       => 'http://www.w3.org/2000/svg',
		'xmlns:xlink' => 'http://www.w3.org/1999/xlink',
		'version'     => 1.1,
		'height'      => 64,
		'width'       => 64,
	], $attrs);
	$svg = $parent->append('svg', null, $attrs);
	$svg->append('use', null, [
		'xlink:href' => DOMAIN . SPRITES . "#{$icon}"
	]);

	return $svg;
}

/**
 * [load description]
 * @param  Array $files  file1, file2, ...
 * @return Array         [description]
 */
function load(...$files)
{
	return array_map(__NAMESPACE__ . '\load_file', $files);
}

/**
 * [load_file description]
 * @param  String $file [description]
 * @param  String $ext  [description]
 * @return mixed        [description]
 */
function load_file($file, $ext = EXT)
{
	static $args = null;

	if (is_null($args)) {
		$args = array(
			DOM\HTML::getInstance(),
			Core\PDO::load(DB_CREDS),
			new Page(Core\URL::getInstance()),
		);
	}
	$ret = require_once(COMPONENTS . $file . $ext);

	if (is_callable($ret)) {
		return call_user_func_array($ret, $args);
	} elseif (is_string($ret)) {
		return $ret;
	} else {
		trigger_error("$file did not return a function or string.");
	}
}

/**
 * [append_to_dom description]
 * @param  String          $fname   [description]
 * @param  DOM\HTML\Element  $el    [description]
 * @return DOM\HTML\Element         [description]
 */
function append_to_dom($fname, DOM\HTMLElement $el)
{
	$ext = pathinfo($fname, PATHINFO_EXTENSION);
	if (empty($ext)) {
		$fname .= '.html';
	}
	$html = file_get_contents(COMPONENTS . $fname);
	return $el->importHTML($html);
}

/**
 * [get_path description]
 * @return Array [description]
 */
function get_path()
{
	static $path = null;
	if (is_null($path)) {
		$path = array_filter(explode('/', trim($_SERVER['REQUEST_URI'], '/')));
	}
	return $path;
}
