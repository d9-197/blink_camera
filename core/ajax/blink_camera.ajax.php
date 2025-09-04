<?php

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    require_once dirname(__FILE__) . '/../class/blink_camera.class.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
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
		if ($config===null) {
			throw new Exception(__('Unable to load Blink Camera configuration.', __FILE__));
        }

        $return=json_encode($config);
		ajax::success($return);
    }

    if (init('action') == 'getEmails') {
        $config = blink_camera::getConfigBlinkAccountsList();
        blink_camera::logdebug('blink_camera.ajax - getEmails: '.print_r($config,true));
		if ($config===null) {
			throw new Exception(__('Unable to load Blink Camera configuration.', __FILE__));
        }
        $return=json_encode($config);
		ajax::success($return);
    }
    if (init('action') == 'getNetworks') {
        $config = blink_camera::getAccountConfigDatas(false,false);
        blink_camera::logdebug('blink_camera.ajax - getNetworks: '.print_r($config,true));
        if ($config===null) {
            throw new Exception(__('Unable to load Blink Camera configuration.', __FILE__));
        }
        foreach ($config as $emails) {
            foreach ($emails as $email) {
                blink_camera::logdebug('blink_camera.ajax - getNetworks: '.print_r($email,true));
                blink_camera::logdebug('blink_camera.ajax - getNetworks - email= '.$email['email'].' versus ' .init('email'));
                if ($email['email']===init('email')) {
                    blink_camera::logdebug('blink_camera.ajax - getNetworks - RESULTAT= '.print_r($email['networks'],true));
                    $return=json_encode($email['networks']);
                }
            }
        }
        ajax::success($return);
    }
    if (init('action') == 'getCameras') {
        $config = blink_camera::getAccountConfigDatas(false,false);
        blink_camera::logdebug('blink_camera.ajax - getCameras: '.print_r($config,true));
        if ($config===null) {
            throw new Exception(__('Unable to load Blink Camera configuration.', __FILE__));
        }
        foreach ($config as $emails) {
            foreach ($emails as $email) {
                foreach ($email['networks'] as $network) {
                    blink_camera::logdebug('blink_camera.ajax - getCameras - networkid= '.$network['network_id'].' versus ' .init('netid'));
                    if ($network['network_id']===init('netid')) {
                        blink_camera::logdebug('blink_camera.ajax - getCameras - RESULTAT= '.print_r($network['camera'],true));
                        $return=json_encode($network['camera']);
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
        blink_camera::logdebug('blink_camera.ajax - getEmail: '.$config);
		/*if ($config==null) {
			throw new Exception(__('Unable to load Blink Camera configuration.', __FILE__));
        }*/
        $return=json_encode($config);
		ajax::success($return);
    }
    if (init('action') == 'setEmail') {
        blink_camera::logdebug('blink_camera.ajax - setEmail: '.init('ideq'));
        $cam=blink_camera::byId(init('ideq'));
        $newEmail=init('email');
        if ($newEmail!=="") {
            $config = $cam->setConfiguration('email',init('email'));
            $cam->save();
        }
        $config = $cam->getConfiguration('email');
        blink_camera::logdebug('blink_camera.ajax - setEmail: '.print_r($config,true));
        $json='{"status":"true"}';
        $return=json_encode($json);
		ajax::success($return);
    }
    if (init('action') == 'getNetwork') {
        blink_camera::logdebug('blink_camera.ajax - getNetwork: '.init('ideq'));
        $cam=blink_camera::byId(init('ideq'));
        $config='{"network_id":"'.$cam->getConfiguration('network_id').'","network_name":"'.$cam->getConfiguration('network_name').'"}';
        blink_camera::logdebug('blink_camera.ajax - getNetwork: RESULTAT '.print_r($config,true));
		/*if ($config==null) {
			throw new Exception(__('Unable to load Blink Camera configuration.', __FILE__));
        }*/
		ajax::success($config);
    }
    if (init('action') == 'setNetwork') {
        blink_camera::logdebug('blink_camera.ajax - setNetwork: '.init('ideq'));
        $cam=blink_camera::byId(init('ideq'));
        $newNetId=init('netid');
        $newNetName=init('netname');
        if ($newNetId!="" && $newNetName!="") {
            $cam->setConfiguration('network_id',$newNetId);
            $cam->setConfiguration('network_name',$newNetName);
            $cam->save();
        }
        blink_camera::logdebug('blink_camera.ajax - setNetwork: '.$cam->getConfiguration('network_id').' - '.$cam->getConfiguration('network_name'));
        $json='{"status":"true"}';
        $return=json_encode($json);
		ajax::success($return);
    }
    if (init('action') == 'getCamera') {
        blink_camera::logdebug('blink_camera.ajax - getCamera: '.init('ideq'));
        $cam=blink_camera::byId(init('ideq'));
        $config='{"device_id":"'.$cam->getConfiguration('camera_id').'","device_name":"'.$cam->getConfiguration('camera_name').'"}';
        blink_camera::logdebug('blink_camera.ajax - getCamera: '.print_r($config,true));
		/*if ($config==null) {
			throw new Exception(__('Unable to load Blink Camera configuration.', __FILE__));
        }*/
		ajax::success($config);
    }
    if (init('action') == 'setCamera') {
        blink_camera::logdebug('blink_camera.ajax - setCamera: '.init('ideq'));
        $cam=blink_camera::byId(init('ideq'));
        $newDevId=init('devid');
        $newDevName=init('devname');
        if ($newDevId!="" && $newDevName!="") {
            $cam->setConfiguration('camera_id',$newDevId);
            $cam->setConfiguration('camera_name',$newDevName);
            $cam->save();
        }
        blink_camera::logdebug('blink_camera.ajax - setCamera: '.$cam->getConfiguration('camera_id').' - '.$cam->getConfiguration('camera_name'));
        $json='{"status":"true"}';
        $return=json_encode($json);
		ajax::success($return);
    }
    if (init('action') == 'update_cfg_account') {
        blink_camera::logdebug('blink_camera.ajax - update_cfg_account: -'.init('email')."- -".init('key')."- -".init('value')."-");
        $value=init('value');
        if (init('key')=='pwd') {
            $value= utils::encrypt(init('value'));
        }
        blink_camera::logdebug('blink_camera.ajax - update_cfg_account: '.init('email')." ".init('key')." ".$value);
        blink_camera::setConfigBlinkAccount(init('email'),init('key'),$value);
        $json='{"status":"true"}';
        $return=json_encode($json);
		ajax::success($return);
    }
    if (init('action') == 'remove_cfg_account') {
        blink_camera::logdebug('blink_camera.ajax - remove_cfg_account: -'.init('email'));
        blink_camera::delConfigBlinkAccount(init('email'));
        $json='{"status":"true"}';
        $return=json_encode($json);
		ajax::success($return);
    }
    if (init('action') == 'reinit_cfg_account') {
        blink_camera::logdebug('blink_camera.ajax - reinit_cfg_account');
        blink_camera::delAllConfigBlinkAccounts();
        $json='{"status":"true"}';
        $return=json_encode($json);
		ajax::success($return);
    }
    if (init('action') == 'test_blink') {
        $email=init('email');
        blink_camera::logdebug('blink_camera.ajax - test_blink: '.print_r($email,true));
        $status = blink_camera::getToken($email,false);
        blink_camera::logdebug('blink_camera.ajax - test_blink: '.print_r($email,true).' status:'.$status);
        $json='{"token":"false"}';
        if ($status===true) {
            $json='{"token":"true"}';
            if ($need_pin_verification=blink_camera::getConfigBlinkAccount($email,"verif")==="false") {
                $json='{"token":"verif"}';
            }
        } else {
            if (blink_camera::getConfigBlinkAccount($email,'limitLogin')==="true") {
                $json='{"token":"limit"}';
            }

        }
        blink_camera::logdebug("blink_camera.ajax - test_blink : ".$json);
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
            blink_camera::logdebug("blink_camera.ajax - verifyPinCode : ".print_r(array('status' => $status),true));
            ajax::success(json_encode(array('status' => $status)));
    }
    

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
?>