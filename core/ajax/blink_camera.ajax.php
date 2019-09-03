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
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }
    
    ajax::init();

    if (init('action') == 'removeRecord') { 
        $file = init('file');
        $dir = init('dir');
        $filepath = realpath($dir.'/'.$file);
        blink_camera::deleteMedia($filepath);
        if (!blink_camera::endsWith($file, "*")) {
            blink_camera::deleteMediaCloud($filepath);
        }
        ajax::success();
    }

    if (init('action') == 'getConfig') {
        $config = blink_camera::getAccountConfigDatas2(false);
		if ($config==null) {
			throw new Exception(__('Unable to load Blink Camera configuration.', __FILE__));
        }
        /*
        if ($config['message']) {
            ajax::error("".str_replace("{{","",str_replace("}}","","".$config['message'])));            
        }*/
        $return=json_encode($config);
		ajax::success($return);
    }
    
    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
