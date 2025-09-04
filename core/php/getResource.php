<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../../../../core/php/authentification.php';
if (!isConnect() && !blink_camera::isOpenMediasAccess()) {
	header("Statut: 404 Page non trouvée");
	header('HTTP/1.0 404 Not Found');
	$_SERVER['REDIRECT_STATUS'] = 404;
	echo "<h1>404 Not found</h1>";
	echo "Page not found.";
	die();
}

$file=init('file');
$file_root="/plugins/blink_camera/medias/";
$file_root_plugin="/plugins/blink_camera/";
if (substr($file,0,strlen($file_root_plugin))==$file_root_plugin) {
	$file = dirname(__FILE__) . '/../../../../' . $file;
} else if (substr($file,0,strlen($file_root))==$file_root && strpos($file, '..') === false) {
	$file = dirname(__FILE__) . '/../../../../' . $file;
} else if (substr($file,0,1)=='/' && strpos($file, $file_root_plugin) !== false)  {
	$file=$file;
} else  {
		#blink_camera::logerror('blink_camera getResource.php - Access attempt denied: '.$file);
		header("Statut: 404 Page non trouvée");
		header('HTTP/1.0 404 Not Found');
		$_SERVER['REDIRECT_STATUS'] = 404;
		echo "<h1>404 Not found</h1>";
		echo "Page not found.";
		die();
}

//blink_camera::logdebug('blink_camera getResource.php '.$file);
$pathinfo = pathinfo($file);
if ($pathinfo['extension'] != 'jpg' && $pathinfo['extension'] != 'png' && $pathinfo['extension'] != 'mp4') {
	die();
}
if (file_exists($file)) {
	switch ($pathinfo['extension']) {
		case 'png':
		$contentType = 'image/png';
		$md5 = init('md5');
		$etagFile = ($md5 == '') ? md5_file($file) : $md5;
		break;
		case 'jpg':
		$contentType = 'image/jpeg';
		$md5 = init('md5');
		$etagFile = ($md5 == '') ? md5_file($file) : $md5;
		break;
		case 'mp4':
		$contentType = 'video/mp4';
		$etagFile = md5_file($file);
		break;
		default:
		die();
	}
	header('Content-Type: ' . $contentType);
	$lastModified = filemtime($file);
	$ifModifiedSince = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false);
	$etagHeader = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
	header('Etag: ' . $etagFile);
	header('Cache-Control: public');
	if ((isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $lastModified) || $etagHeader === $etagFile) {
		header('HTTP/1.1 304 Not Modified');
		exit;
	}
	echo file_get_contents($file);
	exit;
} else {
	header("Statut: 404 Page non trouvée");
	header('HTTP/1.0 404 Not Found');
	$_SERVER['REDIRECT_STATUS'] = 404;
	echo "<h1>404 Not found - RESOURCE NOT FOUND $file</h1>";
	echo "Page not found.";
	die();

}
