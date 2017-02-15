<?php

namespace KVSun;

use \shgysk8zer0\Core as Core;

if (in_array(PHP_SAPI, ['cli', 'cli-server'])) {
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';
}

set_error_handler('shgysk8zer0\Core\Console::error', E_ALL);
set_exception_handler('shgysk8zer0\Core\Console::error');

if (array_key_exists('upload', $_FILES)) {
	$imgs = Core\Image::responsiveImagesFromUpload(
		'upload',
		['images', 'uploads', date('Y'), date('m')]
	);
	Core\Console::info($imgs);
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Upload test</title>
		<meta charset="utf-8" />
	</head>
	<body>
		<form action="<?="{$_SERVER['PHP_SELF']}"?>" method="post" enctype="multipart/form-data">
			<input type="file" name="upload[]" accept="image/jpeg,image/png,image/gif" multiple required autofocus />
			<br />
			<button type="submit">Upload</button>
		</form>
	</body>
</html>
