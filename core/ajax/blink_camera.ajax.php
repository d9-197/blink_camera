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
    require_once dirname(__FILE__) . '/../class/blink_camera.class.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }
    if (!function_exists('str_starts_with')) {
        function str_starts_with($str, $start) {
          return (@substr_compare($str, $start, 0, strlen($start))==0);
        }
      }
    ajax::init();

    blink_camera::logdebug('AJAX : action='.init('action'));

    if (init('action') == 'removeRecord') { 
        $file = init('file');
        $dir = init('dir');
        $idEquipment = init('ideq');
        $filepath = realpath($dir.'/'.$file);
        blink_camera::deleteMedia($filepath);
        if (!blink_camera::endsWith($file, "*") && !str_starts_with($file, blink_camera::PREFIX_THUMBNAIL)) {
            blink_camera::deleteMediaCloud($filepath,$idEquipment);
        }
        ajax::success();
    }

    if (init('action') == 'getConfig') {
        $config = blink_camera::getAccountConfigDatas(false,false);
		if ($config==null) {
			throw new Exception(__('Unable to load Blink Camera configuration.', __FILE__));
        }

        $return=json_encode($config);
		ajax::success($return);
    }

    if (init('action') == 'getEmails') {
        $config = blink_camera::getConfigBlinkAccountsList();
        blink_camera::logdebug('blink_camera.ajax - getEmails: '.print_r($config,true));
		if ($config==null) {
			throw new Exception(__('Unable to load Blink Camera configuration.', __FILE__));
        }
        $return=json_encode($config);
		ajax::success($return);
    }
    if (init('action') == 'getNetworks') {
        $config = blink_camera::getAccountConfigDatas(false,false);
        //$config=json_decode($config);
        blink_camera::logdebug('blink_camera.ajax - getNetworks: '.print_r($config,true));
        if ($config==null) {
            throw new Exception(__('Unable to load Blink Camera configuration.', __FILE__));
        }
        //blink_camera::logdebug('blink_camera.ajax - getNetworks: '.print_r($config['emails'],true));
        foreach ($config as $emails) {
            foreach ($emails as $email) {
                blink_camera::logdebug('blink_camera.ajax - getNetworks: '.print_r($email,true));
                blink_camera::logdebug('blink_camera.ajax - getNetworks - email= '.$email['email'].' versus ' .init('email'));
                if ($email['email']==init('email')) {
                    blink_camera::logdebug('blink_camera.ajax - getNetworks - $email[\'networks\']= '.print_r($email['networks'],true));
                    $return=json_encode($email['networks']);
                }
            }
        }
        ajax::success($return);
    }
    if (init('action') == 'getCameras') {
        $config = blink_camera::getAccountConfigDatas(false,false);
        //$config=json_decode($config);
        blink_camera::logdebug('blink_camera.ajax - getCameras: '.print_r($config,true));
        if ($config==null) {
            throw new Exception(__('Unable to load Blink Camera configuration.', __FILE__));
        }
        //blink_camera::logdebug('blink_camera.ajax - getNetworks: '.print_r($config['emails'],true));
        foreach ($config as $emails) {
            foreach ($emails as $email) {
                //blink_camera::logdebug('blink_camera.ajax - getCameras: '.print_r($email,true));
                foreach ($email['networks'] as $network) {
                    blink_camera::logdebug('blink_camera.ajax - getCameras - email= '.$network['network_id'].' versus ' .init('netid'));
                    if ($network['network_id']==init('netid')) {
                        $return=json_encode($network['cameras']);
                    }
                }
            }
        }
        ajax::success($return);
    }

    if (init('action') == 'getEmail') {
        blink_camera::logdebug('blink_camera.ajax - getEmail: '.init('ideq'));
        $cam=blink_camera::byId(init('ideq'));
        $config = $cam->getConfiguration('email');
        blink_camera::logdebug('blink_camera.ajax - getEmail: '.print_r($config,true));
		if ($config==null) {
			throw new Exception(__('Unable to load Blink Camera configuration.', __FILE__));
        }
        $return=json_encode($config);
		ajax::success($return);
    }
    if (init('action') == 'test_blink') {
        blink_camera::logdebug('blink_camera.ajax - test_blink: '.print_r(init('email'),true));
        $status = blink_camera::getToken(init('email'),false);
        $json='{"token":"false"}';
        if ($status===true) {
            $json='{"token":"true"}';
            if ($need_pin_verification=config::byKey('verif', 'blink_camera')==="false") {
                $json='{"token":"verif"}';
            }
        } else {
            if (config::byKey('limit_login', 'blink_camera')==="true") {
                $json='{"token":"limit"}';
            }

        }
        //blink_camera::logdebug("test connexion : ".$json);
        $return=json_encode($json);
		ajax::success($return);
    }
    if (init('action') == 'reinitConfig') {
        $status = blink_camera::getToken(init('email'),false);
        $json='{"reinit":"KO"}';
        config::save('token', '', 'blink_camera');
        config::save('account', '', 'blink_camera');
        config::save('region', '', 'blink_camera');
        config::save('client', '', 'blink_camera');
        config::save('verif', '', 'blink_camera');
        blink_camera::logdebug("reinit config");
        $json='{"reinit":"OK"}';
        $return=json_encode($json);
		ajax::success($return);
    }
    
    if (init('action') == 'verifyPinCode') {
            $pin = init('pin');
            $email=init('email');
            $status= blink_camera::queryPostPinVerify($pin,$email);
            ajax::success(json_encode(array('status' => $status)));
    }
    

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>