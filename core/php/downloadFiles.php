<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */
try {
	require_once __DIR__ . '/blink_camera.inc.php';
	include_file('core', 'authentification', 'php');
	if (!isConnect() && !jeedom::apiAccess(init('apikey'))) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}
    unautorizedInDemo();
    $pahtFilter=init('filter');
	$pathfile = calculPath(urldecode(init('pathfile')));
	$targetName=urldecode(init('archive'));
	blink_camera::logdebug($targetName);
	$archivename='archive';
	if ($targetName!=='') {
		$archivename=$targetName;
	}
    $pathfileOrig=$pathfile;		
	if ($pathfile === false) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}
	if (strpos($pathfile, '.php') !== false) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}
	$rootPath = realpath(__DIR__ . '/../../../');
	if (strpos($pathfile, $rootPath) === false) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}
	if (!isConnect('admin')) {
		$adminFiles = array('log', 'backup', '.sql', 'scenario', '.tar', '.gz');
		foreach ($adminFiles as $adminFile) {
			if (strpos($pathfile, $adminFile) !== false) {
				throw new Exception(__('401 - Accès non autorisé', __FILE__));
			}
		}
	}
    if (is_dir(str_replace('*', '', $pathfile))) {
		if (!isConnect('admin')) {
			throw new Exception(__('401 - Accès non autorisé', __FILE__));
        }
        system('cd ' . $pathfile . ';tar cfz ' . jeedom::getTmpFolder('downloads') . '/'.$archivename.'.tar.gz '.$pahtFilter.' > /dev/null 2>&1');
        $pathfile = jeedom::getTmpFolder('downloads') . '/'.$archivename.'.tar.gz';
	} else {
		if (!isConnect('admin')) {
			throw new Exception(__('401 - Accès non autorisé', __FILE__));
		}
        system('cd ' . $pathfile . ';tar cfz ' . jeedom::getTmpFolder('downloads') . '/'.$archivename.'.tar.gz ' . $pahtFilter . '> /dev/null 2>&1');
		$pathfile = jeedom::getTmpFolder('downloads') . '/'.$archivename.'.tar.gz';
	}
	$path_parts = pathinfo($pathfile);
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename=' . $path_parts['basename']);
	readfile($pathfile);
	if (file_exists(jeedom::getTmpFolder('downloads') . '/'.$archivename.'.tar.gz')) {
		unlink(jeedom::getTmpFolder('downloads') . '/'.$archivename.'.tar.gz');
	}
	exit;
} catch (Exception $e) {
	echo $e->getMessage();
}
