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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) .'/../../vendor/autoload.php';
//include dirname(__FILE__) .'/blink_const.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
//use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7;
 
class blink_camera extends eqLogic
{
    const BLINK_URL_LOGIN="/api/v5/account/login";
    const BLINK_DEFAULT_USER_AGENT="Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36";
    const BLINK_CLIENT_NAME="Jeedom";
    const BLINK_DEVICE_IDENTIFIER="Jeedom";
    /*     * *************************Attributs****************************** */
    const FORMAT_DATETIME="Y-m-d\TH:i:sT" ;//2019-07-15T18:40:44+00:00
    const FORMAT_DATETIME_OUT="Y-m-d_His" ;//2019-07-15T18:40:44+00:00
    const ERROR_IMG="/plugins/blink_camera/img/error.png";
    const NO_EVENT_IMG="/plugins/blink_camera/img/no_event.png";
    const GET_RESOURCE="/plugins/blink_camera/core/php/getResource.php?file=";
    const PREFIX_THUMBNAIL="thumbnail";    
    public static $_widgetPossibility = array('custom' => array(
        'visibility' => true,
        'displayName' => true,
        'displayObjectName' => true,
        'optionalParameters' => true,
        'background-color' => true,
        'text-color' => true,
        'border' => true,
        'border-radius' => true,
        'layout' => true
    ));

    public static function logdebugBlinkAPIRequest($message) {
        //if (!config::byKeys('log::level::blink_camera_api')) {
           // config::save('log::level::blink_camera_api', '{"100":"1","200":"0","300":"0","400":"0","1000":"0","default":"0"}');
        //}
        //log::add('blink_camera_api','debug',$message);
        return;
    }
    public static function logdebugBlinkAPIResponse($message) {
        //if (!config::byKeys('log::level::blink_camera_api')) {
            //config::save('log::level::blink_camera_api', '{"100":"1","200":"0","300":"0","400":"0","1000":"0","default":"0"}');
        //}
        //log::add('blink_camera_api','debug',$message);
        return;
    }

    public static function logdebug($message) {
        log::add('blink_camera','debug',$message);
        return;
    }

    public static function loginfo($message) {
        log::add('blink_camera','info',$message);
    }

    public static function logerror($message) {
        log::add('blink_camera','error',$message);
    }

    public static function logwarn($message) {
        log::add('blink_camera','warning',$message);
    }

    /* Every 10 minutes, check and download last event video (named last.mp4 in Jeedom) */
    public static function cron10($_eqLogic_id = null)
    {
        if ($_eqLogic_id == null) {
            $eqLogics = self::byType('blink_camera', true);
        } else {
            $eqLogics = array(self::byId($_eqLogic_id));
        }
        foreach ($eqLogics as $cam) {
            if ($cam->getIsEnable() == 1  && $cam->getToken()) {
                $last_event=$cam->getLastEvent(false);
                if (isset($last_event)) { 
                    $info = $cam->getCmd(null, 'source_last_event');
                    if (is_object($info)) {
                        $cam->checkAndUpdateCmd('source_last_event', $last_event['source']);
                    }
                   self::getMediaForce($last_event['media'], $cam->getId(), 'last','mp4',true);
                }
            }
        }
    }
     
    public static function cron($_eqLogic_id = null)
    {
        if ($_eqLogic_id == null) {
            $eqLogics = self::byType('blink_camera', true);
        } else {
            $eqLogics = array(self::byId($_eqLogic_id));
        }
        foreach ($eqLogics as $cam) {
            //blink_camera::logdebug('blink_camera->cron() '.$cam->getConfiguration("camera_id"));
               
            if ($cam->getIsEnable() == 1 && $cam->isConnected()) {
                //blink_camera::logdebug('blink_camera->cron() camera active');
                $cam->getLastEventDate();
                //$cam->refreshCameraInfos();
            }
        }
    }


    public static function cronHourly($_eqLogic_id = null)
    {
        // blink_camera::logdebug('blink_camera->cronHourly()');
           
        if ($_eqLogic_id == null) { // La fonction n’a pas d’argument donc on recherche tous les équipements du plugin
            $eqLogics = self::byType('blink_camera', true);
        } else {// La fonction a l’argument id(unique) d’un équipement(eqLogic
            $eqLogics = array(self::byId($_eqLogic_id));
        }
    
        foreach ($eqLogics as $cam) {//parcours tous les équipements du plugin blink_camera
            if ($cam->getIsEnable() == 1  && $cam->getToken(true)) {//vérifie que l'équipement est acitf
                $cam->forceCleanup(true);
                $cam->getLastEventDate(true);
                $cam->refreshCameraInfos("cronHourly");
            }
        }
    }

    public static function isOpenMediasAccess() {
        return !config::byKey('medias_security', 'blink_camera');
    }
    public static function queryGet(string $url) {
        $_tokenBlink=config::byKey('token', 'blink_camera');
        $_accountBlink=config::byKey('account', 'blink_camera');
        $_regionBlink=config::byKey('region', 'blink_camera');
        $jsonrep=null;
        if (!$_tokenBlink=="" && !$_accountBlink=="" && !$_regionBlink=="") {
            blink_camera::logdebugBlinkAPIRequest("CALL[queryGet]: ".$url);
            $client = new GuzzleHttp\Client(['verify' => false,'base_uri' => 'https://rest.'.$_regionBlink.'.immedia-semi.com/'.$url]);
            $r = $client->request('GET', $url, [
                //['http_errors' => false],
                'headers' => [
                    //'Host'=> 'rest-'.$_regionBlink.'.immedia-semi.com',
                    'TOKEN_AUTH'=> ''.$_tokenBlink,
                    'User-Agent' =>  ''.blink_camera::BLINK_DEFAULT_USER_AGENT,
                    'Accept' => '/'
                    ]
            ]);
            $jsonrep= json_decode($r->getBody(), true);
            blink_camera::logdebugBlinkAPIResponse(print_r($jsonrep,true));
        }    
        return $jsonrep;
    }

   
    
    public static function queryGetMedia(string $url, string $file_path) {
        $_tokenBlink=config::byKey('token', 'blink_camera');
        $_accountBlink=config::byKey('account', 'blink_camera');
        $_regionBlink=config::byKey('region', 'blink_camera');
        $jsonrep=null;
        if (!$_tokenBlink=="" && !$_accountBlink=="" && !$_regionBlink=="") {
            blink_camera::logdebugBlinkAPIRequest("CALL[queryGetMedia]: ".$url);
            $client = new GuzzleHttp\Client(['verify' => false,'base_uri' => 'https://rest.'.$_regionBlink.'.immedia-semi.com/'.$url]);
            $r = $client->request('GET', $url, [
                'sink' => $file_path,
                //['http_errors' => false],
                'headers' => [
                    'Host'=> 'rest-'.$_regionBlink.'.immedia-semi.com',
                    'TOKEN_AUTH'=> ''.$_tokenBlink,
                    'User-Agent' =>  ''.blink_camera::BLINK_DEFAULT_USER_AGENT,
                    'Content-Type' => 'application/json',
                    'Accept' => '/'
                    ]
            ]);
            $jsonrep= json_decode($r->getBody(), true);
          
            blink_camera::logdebugBlinkAPIResponse(print_r($jsonrep,true));
        }    
        return $jsonrep;
    }
    
    public static function queryPostLogin(string $url, string $datas) {
        //blink_camera::logdebug('queryPostLogin(url='.$url.',datas='.$datas.') START');
        blink_camera::logdebugBlinkAPIRequest("CALL[queryPostLogin]: ".$url);
        $jsonrep=null;
        $client = new GuzzleHttp\Client(['verify' => false,'base_uri' => 'https://rest.prod.immedia-semi.com/'. $url]);
        $r = $client->request('POST', 'login', [
            //['http_errors' => false],
            ['timeout' => 1],
            'headers' => [
                'Host'=> 'rest-prod.immedia-semi.com',
                'Content-Type'=> 'application/json',
                'User-Agent' =>  blink_camera::BLINK_DEFAULT_USER_AGENT,
                'Accept' => '/'
            ],
            'json' => json_decode($datas)
        ]);
        $jsonrep= json_decode($r->getBody(), true);
        blink_camera::logdebugBlinkAPIResponse(print_r($jsonrep,true));
        /*blink_camera::logdebug('#######################################');
        blink_camera::logdebug('            queryPostLogin');        
        blink_camera::logdebug(print_r($jsonrep,true));
        blink_camera::logdebug('#######################################');
		*/
        return $jsonrep;
    }
    // 
    public static function queryPostPinVerify(string $pin) {
        //blink_camera::logdebug('queryPostPinVerify(pin='.$pin.') START');
        $client_id=config::byKey('client', 'blink_camera');
        $account_id=config::byKey('account', 'blink_camera');
        $_regionBlink=config::byKey('region', 'blink_camera');
        $_tokenBlink=config::byKey('token', 'blink_camera');

        $url='https://rest-'.$_regionBlink.'.immedia-semi.com/api/v4/account/'.$account_id.'/client/'.$client_id.'/pin/verify';
        blink_camera::logdebugBlinkAPIRequest("CALL[queryPostPinVerify]: ".$url);
        $datas="{\"pin\":".$pin."}";
        try {
            $client = new GuzzleHttp\Client(['verify' => false,'base_uri' =>  $url]);
            $r = $client->request('POST',$url,  [
                //['http_errors' => false],
                ['timeout' => 1],
                'headers' => [
                    'TOKEN_AUTH'=> ''.$_tokenBlink,
                    'User-Agent' =>  blink_camera::BLINK_DEFAULT_USER_AGENT
                ],
                'json' => json_decode($datas)
            ]);
            $jsonrep= json_decode($r->getBody(), true);
            blink_camera::logdebugBlinkAPIResponse(print_r($jsonrep,true));

            if ($jsonrep['valid']==1) {
                blink_camera::logdebug('queryPostPinVerify(pin='.$pin.') Vérification OK');
                config::save('verif', 'true', 'blink_camera');
                return 0;
            } else {
                config::save('verif', 'false', 'blink_camera');
                //blink_camera::logdebug('queryPostPinVerify(pin='.$pin.') Vérification KO');
                return 1;
            }
        }  catch (Exception $e) {
            blink_camera::logdebug('ERROR:'.print_r($e->getTraceAsString(), true));
            blink_camera::logdebug('ERROR:'.print_r($e->getMessage(), true));
            return 1;
        }
        return 0;
    }
    public static function queryPost(string $url, string $datas="{}") {
        //blink_camera::logdebug('queryPost(url='.$url.') START');
        //blink_camera::logdebug('queryPost datas:'.$datas);
        $_regionBlink=config::byKey('region', 'blink_camera');
        $_tokenBlink=config::byKey('token', 'blink_camera');
        blink_camera::logdebugBlinkAPIRequest("CALL[queryPost]: ".$url);
        try {
            $baseuri='https://rest.'.$_regionBlink.'.immedia-semi.com';
            $client = new GuzzleHttp\Client(['verify' => false,'base_uri' =>  $baseuri]);
            $r = $client->request('POST',$baseuri.'/'.$url,  [
                //['http_errors' => false],
                ['timeout' => 1],
                'headers' => [
                    'TOKEN_AUTH'=> ''.$_tokenBlink,
                    'User-Agent' =>  blink_camera::BLINK_DEFAULT_USER_AGENT
                ],
                'json' => json_decode($datas)
            ]);
            $jsonrep= json_decode($r->getBody(), true);
            blink_camera::logdebugBlinkAPIResponse(print_r($jsonrep,true));
            return $jsonrep;

        }  catch (Exception $e) {
            blink_camera::logdebug('ERROR:'.print_r($e->getTraceAsString(), true));
            blink_camera::logdebug('ERROR:'.print_r($e->getMessage(), true));
        }
        return "{}";
    }
    public  function queryPostLiveview() {
        $network_id=$this->getConfiguration("network_id");
        $camera_id=$this->getConfiguration("camera_id");
        $datas='{"intent":"liveview","motion_event_start_time":""}';
        $_accountBlink=config::byKey('account', 'blink_camera');
        $_regionBlink=config::byKey('region', 'blink_camera');
        $_tokenBlink=config::byKey('token', 'blink_camera');
        $url="api/v5/accounts/".$_accountBlink."/networks/".$network_id."/cameras/".$camera_id."/liveview";
        blink_camera::logdebug('queryPostLiveview(url='.$url.') START');
        blink_camera::logdebug('queryPostLiveview datas:'.$datas);
        try {
            $baseuri='https://rest.'.$_regionBlink.'.immedia-semi.com';
            $client = new GuzzleHttp\Client(['verify' => false,'base_uri' =>  $baseuri]);
            $r = $client->request('POST',$baseuri.'/'.$url,  [
                //['http_errors' => false],
                ['timeout' => 1],
                'headers' => [
                    'TOKEN_AUTH'=> ''.$_tokenBlink,
                    'User-Agent' =>  blink_camera::BLINK_DEFAULT_USER_AGENT
                ],
                'json' => json_decode($datas)
            ]);
            return json_decode($r->getBody());
        }  catch (Exception $e) {
            blink_camera::logdebug('ERROR:'.print_r($e->getTraceAsString(), true));
            blink_camera::logdebug('ERROR:'.print_r($e->getMessage(), true));
        }
        return "";
    }
    public function getConfigHistory() {
        $cfgHisto=$this->getConfiguration('history_display_mode');
        if (!isset($cfgHisto) || $cfgHisto=='') {
            $this->setConfigHistory();
            $cfgHisto=$this->getConfiguration('history_display_mode');
        }
//        blink_camera::logdebug('getConfigHistory:'.print_r($cfgHisto, true));
        return $cfgHisto;
    }
    public function setConfigHistory(string $cfgHisto="mp4") {
        $this->setConfiguration('history_display_mode',$cfgHisto);
 //       blink_camera::logdebug('setConfigHistory:'.print_r($cfgHisto, true));
        $this->save();
    }
    public static function isConnected() {
        $_tokenBlink=config::byKey('token', 'blink_camera');
        //blink_camera::logdebug('isConnected Token : '.$_tokenBlink);
        $_accountBlink=config::byKey('account', 'blink_camera');
        //blink_camera::logdebug('isConnected Account : '.$_accountBlink);
        $_regionBlink=config::byKey('region', 'blink_camera');
        //blink_camera::logdebug('isConnected Région : '.$_regionBlink);
        $_verif=config::byKey('verif', 'blink_camera');
        //blink_camera::logdebug('isConnected Vérification : '.$_verif);
        if ($_tokenBlink!=="" && $_accountBlink!=="" && $_regionBlink!=="" && $_verif=="true") {
            return true;
        } else {
            //blink_camera::logwarn("isConnected() - FALSE");
        } ;
    }
    public static function getToken(bool $forceReinit=false )
    {
        //blink_camera::logdebug('getToken() START');
        $date = date_create();
        $tstamp1=date_timestamp_get($date);
        $email=config::byKey('param1', 'blink_camera');
        $pwd=config::byKey('param2', 'blink_camera');
        $email_prev=config::byKey('param1_prev', 'blink_camera');
        $pwd_prev=config::byKey('param2_prev', 'blink_camera');
        if (!$forceReinit) {
            $forceReinit=($email!==$email_prev || $pwd!==$pwd_prev);
        }

        /* Test de validité du token deja existant */
        $need_new_token=false;
        $_tokenBlink=config::byKey('token', 'blink_camera');
        $_accountBlink=config::byKey('account', 'blink_camera');
        $_regionBlink=config::byKey('region', 'blink_camera');
        if (!$forceReinit) {
            // Check if a new token is required
            //TODO : don't check if pin code verification is required
            if (!$_tokenBlink=="" && !$_accountBlink=="" && !$_regionBlink=="") {
                $url='/api/v3/accounts/'.$_accountBlink.'/homescreen';
                try {
                    blink_camera::logdebugBlinkAPIRequest("CALL[queryToken] -->");
                    $jsonrep=blink_camera::queryGet($url);
                }
                catch (TransferException $e) {
                    blink_camera::logdebug('ERROR:'.print_r($e->getTraceAsString(), true));
                    $need_new_token=true;
                }
            } else {
                $need_new_token=true;
            }
            if (!$need_new_token) {
                //blink_camera::logdebug('blink_camera->getToken() Reuse existing token');
                $date = date_create();
                $tstamp2=date_timestamp_get($date);
                //blink_camera::logdebug('getToken()-1 END : '.($tstamp2-$tstamp1).' ms');
                return true;
            }
        } else {
            config::save('token', '', 'blink_camera');
            config::save('account', '', 'blink_camera');
            config::save('region', '', 'blink_camera');
            $_tokenBlink='';
            $_accountBlink='';
            $_regionBlink='';
        }
        if ($_tokenBlink=="BAD_TOKEN") {
            $date = date_create();
            $tstamp2=date_timestamp_get($date);
            //blink_camera::logdebug('getToken()-1bis END : '.($tstamp2-$tstamp1).' ms');
            return false;
        }
        $_tokenBlink=config::byKey('token', 'blink_camera');
        $_accountBlink=config::byKey('account', 'blink_camera');
        $_regionBlink=config::byKey('region', 'blink_camera');
        if ($_tokenBlink=="" && $_accountBlink=="" && $_regionBlink=="") {
            
            //blink_camera::logdebug('getToken() - Nouveau TOKEN');
            config::save('param1_prev', $email, 'blink_camera');
            config::save('param2_prev', $pwd, 'blink_camera');
            $notification_key=config::byKey('notification_key', 'blink_camera');
            $unique_id=config::byKey('notification_key', 'blink_camera');
            $_verifBlink=config::byKey('verif', 'blink_camera');
            if ($_verifBlink=="true") {
                $reauthArg=",\"reauth\":\"true\"";
            }
            $data = "{\"email\" : \"".$email."\",\"password\": \"".$pwd."\",\"notification_key\" : \"".$notification_key."\",\"unique_id\":\"".$unique_id."\",\"device_identifier\":\"".blink_camera::BLINK_DEVICE_IDENTIFIER."\",\"client_name\":\"".blink_camera::BLINK_CLIENT_NAME."\"".$reauthArg."}";
            try {
                $jsonrep=blink_camera::queryPostLogin(blink_camera::BLINK_URL_LOGIN,$data);
            } catch (TransferException $e) {
                if ($e->hasResponse()===true) {
                    $response=$e->getResponse();
                    $code=$response->getStatusCode();
                    if ($code===401) {
                        config::save('token', 'BAD_TOKEN', 'blink_camera');
                        config::save('verif', 'false', 'blink_camera');
                        blink_camera::logdebug('Invalid credentials used for Blink Camera.');
                        //blink_camera::logdebug(print_r($response,true));

                        $date = date_create();
                        $tstamp2=date_timestamp_get($date);
                        //blink_camera::logdebug('getToken()-2 END : '.($tstamp2-$tstamp1).' ms');
                        return false;
                    }
                }
                blink_camera::logdebug('An error occured during Blink Cloud call: /login - ERROR:'.print_r($e->getMessage(), true));
                $date = date_create();
                $tstamp2=date_timestamp_get($date);
                //blink_camera::logdebug('getToken()-3 END : '.($tstamp2-$tstamp1).' ms');
                return false;
            }
            $_tokenBlink=$jsonrep['auth']['token'];
            $_accountBlink=$jsonrep['account']['account_id'];
            $_regionBlink=$jsonrep['account']['tier'];
            $_clientIdBlink=$jsonrep['account']['client_id'];
            if ($_verifBlink=="false") {
                blink_camera::loginfo("Verification required with email code");
            }
            config::save('token', $_tokenBlink, 'blink_camera');
            config::save('account', $_accountBlink, 'blink_camera');
            config::save('region', $_regionBlink,'blink_camera');
            config::save('client', $_clientIdBlink, 'blink_camera');
            $date = date_create();
            $tstamp2=date_timestamp_get($date);
            //blink_camera::logdebug('getToken()-4 END : '.($tstamp2-$tstamp1).' ms');
        }
        return true;
    }


    public static function reformatConfigDatas(array $jsonin, string $region, string $account)
    {
        $jsonstr= "{\"region\":\"".$region."\",\"account\":\"".$account."\",\"networks\":[";
        $nets= array();
        foreach ($jsonin['media'] as $media) {
            $network_id="".$media['network_id'];
            if (!in_array($network_id, $nets, true)) {
                $nets[]=$network_id;
            }
        }
        $nbNet=0;
        foreach ($nets as $currentnet) {
            if ($nbNet>0) {
                $jsonstr=$jsonstr.",";
            }
            $nbNet=$nbNet + 1;
            $nbCam=0;
            $cameras=array();
            $jsonstr=$jsonstr."{\"network_id\":\"".$currentnet."\" ,\"network_name\":\"".$jsonin['media'][0]['network_name']."\" ,\"camera\" :[";
            foreach ($jsonin['media'] as $media) {
                if ($media['network_id']==$currentnet) {
                    if (!in_array($media['device_id'], $cameras, true)) {
                        if ($nbCam>0) {
                            $jsonstr=$jsonstr.",";
                        }
                        $nbCam=$nbCam + 1;
                        $cameras[]=$media['device_id'];
                        $jsonstr=$jsonstr."{\"device_id\":\"".$media['device_id']."\",\"device_name\":\"".$media['device_name']."\"}";
                    }
                }
            }
            $jsonstr=$jsonstr."]}";
        }
        $jsonstr=$jsonstr."]}";
        return $jsonstr;
    }
    public static function getNoEventImg() {
        return blink_camera::NO_EVENT_IMG; 
    }
    public static function reformatConfigDatas2(array $jsonin)
    {
        $account=config::byKey('account', 'blink_camera');
        $region=config::byKey('region', 'blink_camera');
        $jsonstr= "{\"region\":\"".$region."\",\"account\":\"".$account."\",\"networks\":[";
        $nets= array();
        foreach ($jsonin['networks'] as $netw) {
            $network_id="".$netw['id'];
            if (!array_key_exists($network_id, $nets)) {
                $nets[$network_id]=$netw['name'];
            }
        }
        $nbNet=0;
        foreach ($nets as $currentnet => $currentnetname) {
            if ($nbNet>0) {
                $jsonstr=$jsonstr.",";
            }
            $nbNet=$nbNet + 1;
            $nbCam=0;
            $cameras=array();
            $jsonstr=$jsonstr."{\"network_id\":\"".$currentnet."\" ,\"network_name\":\"".$currentnetname."\" ,\"camera\" :[";
            foreach ($jsonin['cameras'] as $cams) {
                if ($cams['network_id']==$currentnet) {
                    if (!in_array($cams['id'], $cameras, true)) {
                        if ($nbCam>0) {
                            $jsonstr=$jsonstr.",";
                        }
                        $nbCam=$nbCam + 1;
                        $cameras[]=$cams['id'];
                        $jsonstr=$jsonstr."{\"device_id\":\"".$cams['id']."\",\"device_name\":\"".$cams['name']."\"}";
                    }
                }
            }
            foreach ($jsonin['owls'] as $cams) {
                if ($cams['network_id']==$currentnet) {
                    if (!in_array($cams['id'], $cameras, true)) {
                        if ($nbCam>0) {
                            $jsonstr=$jsonstr.",";
                        }
                        $nbCam=$nbCam + 1;
                        $cameras[]=$cams['id'];
                        $jsonstr=$jsonstr."{\"device_id\":\"".$cams['id']."\",\"device_name\":\"".$cams['name']."\",\"device_type\":\"".$cams['type']."\"}";
                    }
                }
            }
            foreach ($jsonin['doorbells'] as $cams) {
                if ($cams['network_id']==$currentnet) {
                    if (!in_array($cams['id'], $cameras, true)) {
                        if ($nbCam>0) {
                            $jsonstr=$jsonstr.",";
                        }
                        $nbCam=$nbCam + 1;
                        $cameras[]=$cams['id'];
                        $jsonstr=$jsonstr."{\"device_id\":\"".$cams['id']."\",\"device_name\":\"".$cams['name']."\"}";
                    }
                }
            }
            $jsonstr=$jsonstr."]}";
        }
        $jsonstr=$jsonstr."]}";
        return $jsonstr;
    }
    public function getAccountConfigDatas2($force_json_string=false,$forceReinitToken=false) {
        if (self::getToken($forceReinitToken)) {
            $datas=blink_camera::getHomescreenData("getAccountConfigDatas2");
            if ($datas==null) 
                $datas=[];
            //blink_camera::logdebug('getAccountConfigDatas2() '.print_r($datas,true));
            $reto=self::reformatConfigDatas2($datas);
            //blink_camera::logdebug('getAccountConfigDatas2() after reformat '.print_r($reto,true));
            return $force_json_string ? $reto : json_decode($reto,true);
        }
        $messag='{"message":"{{Impossible de se connecter au compte Blink. Vérifiez vos identifiants et mots de passe. Recharger la page ensuite.}}"}';
        return $force_json_string ? $messag : json_decode($messag,true);
	}


    public function reformatVideoDatas(array $jsonin)
    {
        $jsonstr= "[";
        $cpt=0;
        foreach ($jsonin['media'] as $media) {
            if ($this->getConfiguration('network_id')==$media['network_id']) {
                if ($this->getConfiguration('camera_id')==$media['device_id']) {
                    if ($cpt>0) {
                        $jsonstr=$jsonstr.",";
                    }
                    $cpt++;
                    $jsonstr=$jsonstr."{\"deleted\":";
                    if ($media['deleted']) {
                        $jsonstr=$jsonstr."true";
                    } else {
                        $jsonstr=$jsonstr."false";
                    }
                    $jsonstr=$jsonstr.",\"id\":\"".$media['id']."\"";
                    if (isset($media['source'])) {
                        $jsonstr=$jsonstr.",\"source\":\"".$media['source']."\"";
                    }
                    $jsonstr=$jsonstr.",\"device_id\":\"".$media['device_id']."\",\"device_name\":\"".$media['device_name']."\",\"media\":\"".$media['media']."\",\"thumbnail\":\"".$media['thumbnail']."\",\"created_at\":\"".$media['created_at']."\"}";
                }
            }
        }
        $jsonstr=$jsonstr."]";
        return $jsonstr;
    }
    
    public static function timestampToBlinkDate($timestamp) {
        $blinkDateFormat="c"; #format ISO : 2021-12-05T14:06:22+00:00 
        return blink_camera::getDateJeedomTimezone(gmdate($blinkDateFormat,$timestamp));
    }

    public static function getDateJeedomTimezone(string $date="")
    {
        $dtim = date_create_from_format(blink_camera::FORMAT_DATETIME, $date);
        // Manage negative timezone
        // https://github.com/d9-197/blink_camera/issues/13
        if (getTZoffsetMin()<0) {
            $dtim=date_sub($dtim, new DateInterval("PT".abs(getTZoffsetMin())."M"));  
        } else {
            $dtim=date_add($dtim, new DateInterval("PT".abs(getTZoffsetMin())."M"));  
        }
        return date_format($dtim, blink_camera::FORMAT_DATETIME_OUT);
    }
    public static function getMedia($urlMedia, $equipement_id, $filename="default",$format="mp4")
    {
        return blink_camera::getMediaForce($urlMedia, $equipement_id, $filename,$format,false);
    }
    public static function getMediaForce($urlMedia, $equipement_id, $filename="default",$format="mp4",$overwrite=false)
    {
        //blink_camera::logdebug('blink_camera->getMedia() url : '.$urlMedia);
        if (!empty($urlMedia)) {
                $_tokenBlink=config::byKey('token', 'blink_camera');
                $_accountBlink=config::byKey('account', 'blink_camera');
                $_regionBlink=config::byKey('region', 'blink_camera');
                $filenameTab = explode('/', $urlMedia);
               // blink_camera::logdebug("blink_camera->getMedia() split : ".print_r($filenameTab,true));
                if (count($filenameTab)>0) {
                    if ($filename==="default") {
                        $filename = 'thumb.png';
                        $filename = $filenameTab[count($filenameTab)-1];
                    }
                    if ($filenameTab[count($filenameTab)-2]==="thumb" || $format!=="mp4") {
                        if (!strpos($filename, '.jpg')) {
                            $filename =$filename .".jpg";
                        }
                    } else if ($format==="mp4"){
                        if (!strpos($filename, '.mp4')) {
                            $filename =$filename .".mp4";
                        }
                    }
                } else {
                    $filename='thumb.png';
                }
                $folderBase=__DIR__.'/../../medias/'.$equipement_id.'/';
            if ((!file_exists($folderBase.$filename) || $overwrite) && self::isConnected()) {
                //blink_camera::logdebug("blink_camera->getMedia() url : $urlMedia - path : $filename");
                if (!empty($_tokenBlink) && !empty($_accountBlink) && !empty($_regionBlink)) {
                    if (!file_exists($folderBase)) {
                        mkdir($folderBase, 0775);
                        chmod($folderBase, 0775);
                    }
                    if (!file_exists($folderBase.$filename) || $overwrite) {
                        $file_path = fopen($folderBase.$filename, 'w');
                        if (file_exists($folderBase.$filename)) {
                            chmod($folderBase.$filename, 0775);
                        }
                        try {
                            blink_camera::logdebugBlinkAPIRequest("CALL[getMediaForce] -->");
                            blink_camera::queryGetMedia($urlMedia,$folderBase.$filename);
                            if (file_exists($folderBase.$filename)) {
                                chmod($folderBase.$filename, 0775);
                            }
                        } 
                        catch (TransferException $e) {
                            blink_camera::logdebug('An error occured during Blink Cloud call: '.$urlMedia. ' - ERROR:'.print_r($e->getMessage(), true));
                            blink_camera::deleteMedia($folderBase.$filename);
                            return blink_camera::ERROR_IMG;
                        }
                    }
                } else {
                    //blink_camera::logdebug("blink_camera->getMedia() url : $urlMedia - path : error.png");
                    return blink_camera::ERROR_IMG;
                }
            }
                //blink_camera::logdebug("blink_camera->getMedia() url : $urlMedia - path : $filename");
                return '/plugins/blink_camera/medias/'.$equipement_id.'/'.$filename;
        }
        return blink_camera::ERROR_IMG;
    }

    public static function getHomescreenData($callOrig="")
    {
        $jsonrep=json_decode('{"message":"error"}',true);
        if (self::isConnected()) {
            $_accountBlink=config::byKey('account', 'blink_camera');
            $url='/api/v3/accounts/'.$_accountBlink.'/homescreen';
            try {
                blink_camera::logdebugBlinkAPIRequest("CALL[getHomescreenData from ".$callOrig."] -->");
                $jsonrep=blink_camera::queryGet($url);
                #$folderJson=__DIR__.'/../../medias/getHomescreenData.json';
                #file_put_contents($folderJson,json_encode($jsonrep));
            }
            catch (TransferException $e) {
                $errorTxt='ERROR: getHomescreenData - '.print_r($e->getMessage(), true);
                    blink_camera::logwarn($errorTxt);
                    $jsonrep=json_decode('{"message":"'.$errorTxt.'"}',true);

            }
        //blink_camera::logdebug('getHomescreenData :\n'.print_r($jsonrep,true));
            return $jsonrep;
        }
    }
        
    /*     * *********************Méthodes d'instance************************* */
    public function getBlinkDeviceType() {
        $valeur = $this->getConfiguration("camera_type");
        if ($valeur=="") {
            $datas=blink_camera::getHomescreenData("getBlinkDeviceType");
            $camera_id = $this->getConfiguration("camera_id");
            foreach ($datas['cameras'] as $device) {
                if ("".$device['id']==="".$camera_id) {
                    $valeur=$device['type'];
                }
            }
            foreach ($datas['owls'] as $device) {
                if ("".$device['id']==="".$camera_id) {
                    $valeur=$device['type'];
                }
            }
            foreach ($datas['doorbells'] as $device) {
                if ("".$device['id']==="".$camera_id) {
                    $valeur=$device['type'];
                }
            }
            $this->setConfiguration("camera_type",$valeur);
            $this->save();
        }
        
        //blink_camera::logdebug('TYPE DEVICE='.$valeur);
		return $valeur;
    }
    public function getLocalMedia() {
        // https://github.com/fronzbot/blinkpy#sync-module-local-storage

/*        if ($clip_id=="") {
            $last_event=$this->getLastEvent();
            if (isset($last_event)) {
                $clip_id= $last_event['media'];
            }
        }*/
        $_accountBlink=config::byKey('account', 'blink_camera');
        $netId=$this->getConfiguration('network_id');
        $syncId=$this->getConfiguration('sync_id');
        if (!$syncId =="") {
            $url_manifest='/api/v1/accounts/'.$_accountBlink.'/networks/'.$netId.'/sync_modules/'.$syncId.'/local_storage/manifest';
            $url_manifest_req=$url_manifest.'/request';
            /* POST : {base_url}/api/v1/accounts/{account_id}/networks/{network_id}/sync_modules/{sync_id}/local_storage/manifest/request */
            $jsonrep=blink_camera::queryPost($url_manifest_req);
            if (isset($jsonrep)) {
    $folderJson=__DIR__.'/../../medias/'.$this->getId().'/localStorage_ph1.json';
    file_put_contents($folderJson,json_encode($jsonrep));
                jeedomUtils.sleep(1);
//blink_camera::logdebug('getLocalMedia Phase 1 : '.print_r($jsonrep,true));
                $manifest_req_id=$jsonrep['id'];
                // blink_camera::logdebug('getLocalMedia : '.$manifest_req_id);
                /* GET : {base_url}/api/v1/accounts/{account_id}/networks/{network_id}/sync_modules/{sync_id}/local_storage/manifest/request/{manifest_request_id} */
                $url=$url_manifest_req.'/'.$manifest_req_id;
                $jsonrep=blink_camera::queryGet($url);
                if (isset($jsonrep)) {
    $folderJson=__DIR__.'/../../medias/'.$this->getId().'/localStorage_ph2.json';
    file_put_contents($folderJson,json_encode($jsonrep));
//blink_camera::logdebug('getLocalMedia Phase 2 : '.print_r($jsonrep,true));
                    $manifest_id=$jsonrep['manifest_id'];
                    if (isset($manifest_id)) {
                        foreach ($jsonrep['clips'] as $clips) {
                            $clip_id=$clips['id'];
                            $camera_name=$clips['camera_name'];
                            $clip_date=$clips['created_at'];
                            $filename=$clip_id.'-'.blink_camera::getDateJeedomTimezone($clip_date).'_LOCAL';
                            if ($camera_name===$this->getName()) {
                                $url_media=$url_manifest.'/'.$manifest_id.'/clip/request/'.$clip_id;
                                /* POST : {base_url}/api/v1/accounts/{account_id}/networks/{network_id}/sync_modules/{sync_id}/local_storage/manifest/{manifest_id}/clip/request/{clip_id} */
                                $jsonrep=blink_camera::queryPost($url_media);
    $folderJson=__DIR__.'/../../medias/'.$this->getId().'/localStorage_ph3.json';
    file_put_contents($folderJson,json_encode($jsonrep));
    //blink_camera::logdebug('getLocalMedia Phase 3 : '.print_r($jsonrep,true));
    //blink_camera::logdebug('getLocalMedia URL MEDIA : '.$url_media);
                                jeedomUtils.sleep(1);
                                self::getMediaForce($url_media, $this->getId(), $filename,'mp4',true);
                            }
                        }
                    }
                }
            }
        }
    }
    public function isConfigured()
    {
        $network_id = $this->getConfiguration("network_id");
        $camera_id = $this->getConfiguration("camera_id");
        if ($network_id!=="" && $camera_id!=="") {
            return true;
        }
        return false;
    }
 
	public function getCameraInfo() {
        $jsonrep=json_decode('{"message":erreur"}',true);
        if (self::isConnected() && $this->isConfigured()) {

            $url='/network/'.$this->getConfiguration('network_id').'/camera/'.$this->getConfiguration('camera_id');
            try {
               blink_camera::logdebugBlinkAPIRequest("CALL[getCameraInfo] -->");
               $jsonrep=blink_camera::queryGet($url);
               #$folderJson=__DIR__.'/../../medias/getCameraInfoOwl.json';
                #file_put_contents($folderJson,json_encode($jsonrep));
            } catch (TransferException $e) {
                blink_camera::logdebug('getCameraInfo (type device='.$this->getBlinkDeviceType().')- An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                return $jsonrep;
            }
            //blink_camera::logdebug('getCameraInfo  '.$url. ' - response:'.print_r($jsonrep, true));
            return $jsonrep;
        }
	}

	public function getCameraThumbnail($forceDownload=false) {
		if ($this->getBlinkDeviceType()!=="owlZZ") {
	      	$lastThumbnailTime = $this->getConfiguration("last_camera_thumb_time");
	      	$newtime=time();
	      	if ($forceDownload || ($newtime-$lastThumbnailTime)>5*6) {
		        $datas=blink_camera::getHomescreenData("getCameraThumbnail");
                
                //blink_camera::logdebug('getHomescreenData - responce: '.print_r($datas, true));
                
                $camera_id = $this->getConfiguration("camera_id");

                //blink_camera::logdebug('getCameraThumbnail (Camera id:'.$this->getId().')- refresh thumbnail URL- previous time: '.$lastThumbnailTime.' - new time:'.$newtime.' - path:'.$path);
	        	foreach ($datas['cameras'] as $device) {
                    if ("".$device['id']==="".$camera_id) {
                        $timestamp_thumb=$device['thumbnail'];
                        $pattern="/.*ts=([0-9]*).*/";
                        
                        if (preg_match($pattern, $timestamp_thumb, $matches)) {
                            $timestamp_thumb="-".blink_camera::timestampToBlinkDate($matches[1]);
                        } else {
                            $timestamp_thumb="";
                        }
                        $path=$this->getMediaForce($device['thumbnail'].'.jpg', $this->getId(),"thumbnail".$timestamp_thumb,"jpg",true);
                    }
                }	  
                foreach ($datas['owls'] as $device) {
                    if ("".$device['id']==="".$camera_id) {
                        $timestamp_thumb=$device['thumbnail'];
                        $pattern="/.*ts=([0-9]*).*/";
                        
                        if (preg_match($pattern, $timestamp_thumb, $matches)) {
                            $timestamp_thumb="-".blink_camera::timestampToBlinkDate($matches[1]);
                        } else {
                            $timestamp_thumb="";
                        }
                        $path=$this->getMediaForce($device['thumbnail'].'.jpg', $this->getId(),"thumbnail".$timestamp_thumb,"jpg",true);
                    }
                }
                foreach ($datas['doorbells'] as $device) {
                    if ("".$device['id']==="".$camera_id) {
                        $timestamp_thumb=$device['thumbnail'];
                        $pattern="/.*ts=([0-9]*).*/";
                        
                        if (preg_match($pattern, $timestamp_thumb, $matches)) {
                            $timestamp_thumb="-".blink_camera::timestampToBlinkDate($matches[1]);
                        } else {
                            $timestamp_thumb="";
                        }
                        $path=$this->getMediaForce($device['thumbnail'].'.jpg', $this->getId(),"thumbnail".$timestamp_thumb,"jpg",true);
                    }
                }

           		$pathRandom=trim(network::getNetworkAccess(config::byKey('blink_base_url', 'blink_camera'), '', '', false), '/').str_replace(" ","%20",blink_camera::GET_RESOURCE.$path."&".$this->generateRandomString());
          		if (isset($path) && $path <> "") {
          		}
          		$this->setConfiguration("last_camera_thumb_time",$newtime);
          		$this->setConfiguration("camera_thumb_url",$pathRandom);
                $this->checkAndUpdateCmd('camera_thumb_url',$pathRandom);
                $this->checkAndUpdateCmd('camera_thumb_path',$path);
                $path=$pathRandom;

			} else {
          		$path= $this->getConfiguration("camera_thumb_url");
	      		//blink_camera::logdebug('getCameraThumbnail (Camera id:'.$this->getId().')- not need to refresh thumbnail URL- previous time: '.$lastThumbnailTime.' - new time:'.$newtime.' - path:'.$path);
        	}
		}
		return $path;
	}


    public function getVideoList(int $page=1)
    {
        $network_id = $this->getConfiguration("network_id");
        $camera_id = $this->getConfiguration("camera_id");
        $jsonstr="erreur";
        if (self::isConnected() && $this->isConfigured()) {
            $_tokenBlink=config::byKey('token', 'blink_camera');
            $_accountBlink=config::byKey('account', 'blink_camera');
            $_regionBlink=config::byKey('region', 'blink_camera');
            $url='/api/v1/accounts/'.$_accountBlink.'/media/changed?since=2019-04-19T23:11:20+0000&page='.$page;
            
            try {
                blink_camera::logdebugBlinkAPIRequest("CALL[getVideoList] -->");
                $jsonrep=blink_camera::queryGet($url);
                //$folderJson=__DIR__.'/../../medias/getVideoList.json';
                //file_put_contents($folderJson,json_encode($jsonrep));
                
                if (isset($jsonrep)) {
                    $jsonstr =self::reformatVideoDatas($jsonrep);
                }
            } catch (TransferException $e) {
                blink_camera::logdebug('An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                return $jsonstr;
            }
            return $jsonstr;
        }
	}
    
    public function requestNewMediaCamera($type="clip")
    {
        return $this->requestNewMedia($type,"camera");
    }
    public function requestNewMediaDoorbell($type="clip")
    {
        return $this->requestNewMedia($type,"doorbells");
    }
    public function requestNewMediaMini($type="clip")
    {
        return $this->requestNewMedia($type,"owl");
    }
	public function requestNewMedia($type="clip",$typeDevice="camera")
    {
        $jsonrep=json_decode('["message":"erreur"]');
        if (($type==="clip" || $type ==="thumbnail" ) &&self::isConnected() && $this->isConfigured()) {
            $_accountBlink=config::byKey('account', 'blink_camera');
                    if ($typeDevice==='owl') {
                        // https://rest.prde.immedia-semi.com/api/v1/accounts/{{accountid}}/networks/194881/owls/3287/clip
                        $url='/api/v1/accounts/'.$_accountBlink.'/networks/'.$this->getConfiguration('network_id').'/owls/'.$this->getConfiguration('camera_id').'/'.$type;
                    } else if ($typeDevice==='doorbells')  {
                        // https://rest.prde.immedia-semi.com/api/v1/accounts/{{accountid}}/networks/194881/owls/3287/clip
                        $url='/api/v1/accounts/'.$_accountBlink.'/networks/'.$this->getConfiguration('network_id').'/doorbells/'.$this->getConfiguration('camera_id').'/'.$type;
                    } else  {
                        $url='/network/'.$this->getConfiguration('network_id').'/'.$typeDevice.'/'.$this->getConfiguration('camera_id').'/'.$type;
                    }
                    blink_camera::logdebugBlinkAPIRequest("CALL[requestNewMedia]: --> ");
                try {
                    $jsonrep=blink_camera::queryPost($url);
                } catch (TransferException $e) {
                    blink_camera::logdebug('An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                    return false;
                }
            return $jsonrep;
        }
	}


    public function forceCleanup($download=false)
    {
        $nbMax= (int) config::byKey('nb_max_video', 'blink_camera');
        if ($nbMax <= 0) {
            $nbMax=-1;
        }
        $cptVideo=0;
        $existingFilesOnJeedom = scandir($this->getMediaDir());
        $fileToDelete =array();
        $fileOnCloudAndOnJeedom =array();
        $fileToDownload =array();
        $fileCloud =array();
        $fileCloudThumb =array();
        $fileToKeep=array();
        $fileToKeep[]="last.mp4";
        $fileToKeep[]="thumbnail.jpg";
        if ($this->isConnected()) {
            $pageVide=0;
            for ($page=1;$page<=100;$page++) {
                $videos=$this->getVideoList($page);
                $videosJson=json_decode($videos, true);
                $existVideoInPage=false;
                // Si en cherchant des videos on a rencontré 50 pages vides, on arrete de rechercher (perfo)
                if ($pageVide>=10) {
                    break;
                }
                foreach ($videosJson as $video) {
                    $existVideoInPage=true;
                    //blink_camera::logdebug( 'blink_camera->forceCleanup() video dans page : '. $page);
                    break;
                }
                if ($existVideoInPage) {
                    //blink_camera::logdebug( 'blink_camera->forceCleanup() process videos of page : '. $page);            
                    $existVideoInPage=false;
                    foreach ($existingFilesOnJeedom as $file) {
                        if (($key = array_search($file, $fileOnCloudAndOnJeedom)) == false) {
                            if ($file!=="." && $file!=="..") {
                                $filename="";
                                foreach ($videosJson as $video) {
                                    if (!$video['deleted']) {
                                        $filename=$video['id'].'-'.blink_camera::getDateJeedomTimezone($video['created_at']).'.mp4';
                                        if (($key = array_search($filename, $fileCloud)) == false) {
                                            $fileCloud[$filename]=$video['media'];
                                            $cptVideo++;
                                            if ($file === $filename && ($key = array_search($filename, $fileOnCloudAndOnJeedom)) == false) {
                                                $fileOnCloudAndOnJeedom[]=$filename;
                                                //blink_camera::logdebug( 'blink_camera->forceCleanup() fichier existant trouve sur le cloud : '. $filename);
                                            }
                                            $filename=$video['id'].'-'.blink_camera::getDateJeedomTimezone($video['created_at']).'.jpg';
                                            $fileCloudThumb[$filename]=$video['thumbnail'];
                                            if ($file === $filename && ($key = array_search($filename, $fileOnCloudAndOnJeedom)) == false) {
                                                $fileOnCloudAndOnJeedom[]=$filename;
                                                //blink_camera::logdebug( 'blink_camera->forceCleanup() fichier existant trouve sur le cloud : '. $filename);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $pageVide++;
                }
            }  
            //blink_camera::logdebug( 'blink_camera->forceCleanup() Videos listed on cloud : '. count($fileCloud));     
                   
            $cptVideo=0;

            // TELECHARGEMENT DES FICHIERS MANQUANTS
            $fileCloud=array_unique($fileCloud);
            arsort($fileCloud);
            
            // Récupération des videos
            foreach ($fileCloud as $filename => $urlMedia) {
                if ($nbMax>0 && $cptVideo>=$nbMax) {
                    break;
                } 
                if (($key = array_search($filename, $fileOnCloudAndOnJeedom)) == false) {
                    if ($download) { // Si demandé, on télécharge les vidéos disponibles
                        $path=$this->getMedia($urlMedia, $this->getId(), $filename);
                        //blink_camera::logdebug( 'blink_camera->forceCleanup() download file: '. $filename);
                        $filenameThumb=str_replace(".mp4",".jpg",$filename);
                        // Fix du 29 03 2022 : 
                        //$path=$this->getMedia($fileCloudThumb[$filenameThumb], $this->getId(), $filenameThumb);                        
                        $path=$this->getMedia($fileCloudThumb[$filenameThumb], $this->getId(), $filenameThumb,"jpg");                        
                        //blink_camera::logdebug( 'blink_camera->forceCleanup() download file: '. $filenameThumb);
                    }
                }
                $fileToKeep[]=$filename;
                $fileToKeep[]=str_replace(".mp4",".jpg",$filename);
                $cptVideo++;
            }
            
            // SUPPRESSION DES FICHIERS QUI NE SONT PLUS SUR CLOUD
            foreach ($existingFilesOnJeedom as $file) {
                if (($key = array_search($file, $fileToKeep)) == false) {
                    if ($file!=="." && $file!=="..") {
                        // On ne supprime pas les thumbnail de camera
                        if(preg_match("#.*".blink_camera::PREFIX_THUMBNAIL."-.*\.jpg$#",strtolower($file))==false){
                            $fileToDelete[]=$file;
                        }
                    }
                }
            }
        }

        // Nettoyage des fichiers du dossier medias
        foreach ($fileToDelete as $file) {
            if ($file!=="." && $file!=="..") {
                //blink_camera::logdebug( 'blink_camera->forceCleanup() Delete file: '. $this->getMediaDir().'/'.$file);
                blink_camera::deleteMedia($this->getMediaDir().'/'.$file);
            }
        }

        $temp =$this->getLastEvent(false);
        self::getMedia($temp['media'], $this->getId(), 'last','mp4');
        $info = $this->getCmd(null, 'source_last_event');
        if (is_object($info)) {
            $this->checkAndUpdateCmd('source_last_event', $temp['source']);
        }
		// récup thumbnail de la caméra
		$this->getCameraThumbnail(true);
    }

    public function getLastEvent($include_deleted=false)
    {
        //blink_camera::logdebug('blink_camera->getLastEvent() start');
        // on boucle sur les pages au cas ou les premières pages ne contiendraient que des event supprimés
        for ($page=1;$page<=50;$page++) {
            $jsonvideo=$this->getVideoList($page);
            foreach (json_decode($jsonvideo, true) as $event) {
                if ($include_deleted || $event['deleted']===false) {
                    //blink_camera::logdebug('blink_camera->getLastEvent() '.$event['created_at']);
                    if (!isset($last_event) || $last_event['created_at']<$event['created_at']) {
                        $last_event=$event;
                    }
                }
            }
            if (isset($last_event)) {
                //blink_camera::logdebug('blink_camera->getLastEvent() return an event:'.$last_event['created_at']);
                self::getMedia($last_event['media'], $this->getId(), 'last','mp4');
                return $last_event;
            } else {
                return null;
            }
        }
        $jsonstr='[{"id":"error",deleted":false,"device_id":"xxxxx","device_name":"xxxx","media":"xxxxxxx","thumbnail":"/plugins/blink_camera/medias/x0.png","created_at":"2019-01-01T00:00:01+0000"}]';
        return json_decode($jsonstr, true);
    }

  function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
	}
  
    public function getLastEventDate($ignorePrevious=false)
    {
        if ($this->isConfigured()) {
            //blink_camera::logdebug('blink_camera->getLastEventDate() '.$this->getId().' START');
            $event = $this->getLastEvent(false);
            if (!isset($event)) {
                $this->checkAndUpdateCmd('last_event', "-");
                $this->checkAndUpdateCmd('thumb_path',"-");
                $this->checkAndUpdateCmd('thumb_url',"-");
                $this->checkAndUpdateCmd('clip_path',"-");
                $this->checkAndUpdateCmd('clip_url',"-");
            } else {
                $infoCmd=$this->getCmd(null, 'last_event');
                if (is_object($infoCmd) && isset($event)) {
                    $previous=$infoCmd->execCmd();
                    $dtim = date_create_from_format(blink_camera::FORMAT_DATETIME, $event['created_at']);
                    if (getTZoffsetMin()<0) {
                        $dtim=date_sub($dtim, new DateInterval("PT".abs(getTZoffsetMin())."M"));  
                    } else {
                        $dtim=date_add($dtim, new DateInterval("PT".abs(getTZoffsetMin())."M"));  
                    }
                    $new=date_format($dtim, blink_camera::FORMAT_DATETIME_OUT);
                    if (isset($new) && $new!="" && ($new>$previous || $ignorePrevious)) {
                        //blink_camera::logdebug('New event detected:'.$new. ' (previous:'.$previous.')');
                        $this->checkAndUpdateCmd('last_event', $new);
                        $pathThumb=blink_camera::getMedia($event['thumbnail'],$this->getId(),$event['id'].'-'.blink_camera::getDateJeedomTimezone($event['created_at']),'jpg');
                        $this->checkAndUpdateCmd('thumb_path',$pathThumb);
                        $urlThumb=trim(network::getNetworkAccess(config::byKey('blink_base_url', 'blink_camera'), '', '', false), '/').str_replace(" ","%20",blink_camera::GET_RESOURCE.$pathThumb);
                        $this->checkAndUpdateCmd('thumb_url',$urlThumb);
                        $pathLastVideo=blink_camera::getMedia($event['media'],$this->getId(),$event['id'].'-'.blink_camera::getDateJeedomTimezone($event['created_at']));
                        $this->checkAndUpdateCmd('clip_path',$pathLastVideo);
                        $urlLastVideo=trim(network::getNetworkAccess(config::byKey('blink_base_url', 'blink_camera'), '', '', false), '/').str_replace(" ","%20",blink_camera::GET_RESOURCE.$pathLastVideo);
                        $this->checkAndUpdateCmd('clip_url',$urlLastVideo);
                    }
                }
                $info = $this->getCmd(null, 'source_last_event');
                if (is_object($info) && isset($event)) {
                    //blink_camera::logdebug('blink_camera->getLastEvent() init last event source '.print_r($event,true) );
                    $this->checkAndUpdateCmd('source_last_event', $event['source']);
                }
            }
            //Recalcul de la vignette à afficher
            $facteur= (float) config::byKey('blink_size_thumbnail', 'blink_camera');
            if ($facteur<=0) {
                $hauteurVignette='width="100%"';
                $largeurVignette='width="100%"';
            } else {
                $hauteurVignette='height="'.(720*$facteur).'"';
                $largeurVignette='width="'.(1280*$facteur).'"';    
            }
            $urlLine ='<img src="#urlFile#" '.$largeurVignette.' '. $hauteurVignette.' class="vignette" style="display:block;padding:5px;" data-eqLogic_id="'.$this->getId().'"/>';
            $config_thumb=config::byKey('blink_dashboard_content_type', 'blink_camera');
            
            if ($config_thumb==="1" && $this->getBlinkDeviceType()!=="owlZZ") {
                // On affiche la vignette de la caméra
                $this->getCameraThumbnail();
              	$thumbUrlCmd=$this->getCmd(null, 'camera_thumb_path');
          	  	$urlFile=$thumbUrlCmd->execCmd();
            } else if ($config_thumb==="3") {
                $clipUrlCmd=$this->getCmd(null, 'clip_path');
                $urlFile=$clipUrlCmd->execCmd();
                //On affiche la video
                $urlLine ='<video class="displayVideo vignette" '. $hauteurVignette.' controls loop data-src="#urlFile#" style="display:block;padding:5px;cursor:pointer"><source src="#urlFile#">Your browser does not support the video tag.</video>';
            } else  {
                $thumbUrlCmd=$this->getCmd(null, 'thumb_path');
                $urlFile=$thumbUrlCmd->execCmd();
                //On affiche la vignette de la derniere video
            }                           
            if (($urlFile ==="-" || $urlFile ==="") && $config_thumb !== 1) {
                $urlLine ='<img src="#urlFile#" '.$largeurVignette.' '. $hauteurVignette.' class="vignette" style="display:block;padding:5px;" data-eqLogic_id="'.$this->getId().'"/>';
                if ((boolean) config::byKey('fallback_to_thumbnail', 'blink_camera')) {
                    $this->getCameraThumbnail();
                   	$thumbUrlCmd=$this->getCmd(null, 'camera_thumb_path');
              	  	$urlFile=$thumbUrlCmd->execCmd();
                } else {
                    $urlFile=blink_camera::getNoEventImg();
                }
            }
            if (!isset($urlFile) || $urlFile ==="") {
                $urlFile=blink_camera::getNoEventImg();
            }
            if ($urlFile!==blink_camera::getNoEventImg()) {
                $urlFile=blink_camera::GET_RESOURCE.$urlFile;
            }
            $replace['#urlFile#']=$urlFile;
            $this->checkAndUpdateCmd('thumbnail',template_replace($replace, $urlLine));
        }
	}
	public function refreshCameraInfos($callOrig="") {
		if ($this->isConfigured()&& $this->isConnected()) {
            $this->getCameraThumbnail();
            //$this->emptyCacheWidget();
            if ($this->getBlinkDeviceType()!=="owl" && $this->getBlinkDeviceType()!=="lotus") {
                $datas=$this->getCameraInfo();
                if (!$datas['message']) {
                   /* // MAJ Température 
                    $tempe=(float) $datas['camera_status']['temperature'];
                    blink_camera::logdebug('refreshCameraInfos() '.$this->getConfiguration('camera_id').' - temperature = '.print_r($tempe,true));
                    $blink_tempUnit=config::byKey('blink_tempUnit', 'blink_camera');
                    if ($blink_tempUnit==="C") {
                        $tempe =($tempe - 32) / 1.8;
                    }
                    //blink_camera::logdebug('refreshCameraInfos() '.$this->getConfiguration('camera_id').' - temperature = '.print_r($tempe,true));
                   
                    $this->checkAndUpdateCmd('temperature', $tempe);
                    $this->setConfiguration('camera_temperature',$tempe);
                    */
                    // MAJ Power 
                    $power=(float) $datas['camera_status']['battery_voltage'];
                    $this->checkAndUpdateCmd('power', ($power/100));
                    $this->setConfiguration('camera_voltage',($power/100));
                    $power_full=155;
                    $power_empty=145;
                    if ($power>=$power_full) {
                        $battery = 100;
                    } else if ($power<$power_full && $power>$power_empty) {
                        $battery = ceil(($power*100)/$power_full);
                    } else {
                        $battery = 1;
                    }
                    $this->checkAndUpdateCmd('battery', $battery);
                    $this->setConfiguration('battery',$battery);
                    $this->batteryStatus($battery);

                    // MAJ WIFI
                    $wifi=(float) $datas['camera_status']['wifi_strength'];
                    $this->checkAndUpdateCmd('wifi_strength', $wifi);
                    $this->setConfiguration('camera_wifi',$wifi);
                }
            } else {
                $this->checkAndUpdateCmd('battery', 100);
                $this->setConfiguration('battery',100);
                $this->batteryStatus(100);
            }
            $datas=blink_camera::getHomescreenData("refreshCameraInfos - ".$callOrig);
            if (!$datas['message']) {
                foreach($datas['cameras'] as $camera) {
                    //blink_camera::logdebug('refreshCameraInfos() '.$this->getConfiguration('camera_id').' - '.print_r($camera,true));
                    if ($camera['id']==$this->getConfiguration('camera_id')) {
                        if ($camera['enabled']===true) {
                            $this->checkAndUpdateCmd('arm_status_camera', 1);
                            $this->setConfiguration('camera_status',true);
                        } else 
                        {
                            $this->checkAndUpdateCmd('arm_status_camera', 0);
                            $this->setConfiguration('camera_status',false);
                        }
                        $this->setConfiguration('camera_type',$camera['type']);
                        $this->setConfiguration('camera_name',$camera['name']);
                        $this->setConfiguration('camera_battery_status',$camera['battery']);
                        $signal=$camera['signals'];
                        $tempe=(float) $signal['temp'];
                        //blink_camera::logdebug('refreshCameraInfos() '.$this->getConfiguration('camera_id').' - temperature = '.print_r($tempe,true));
                        $blink_tempUnit=config::byKey('blink_tempUnit', 'blink_camera');
                        if ($blink_tempUnit==="C") {
                            $tempe =($tempe - 32) / 1.8;
                        }
                        $this->checkAndUpdateCmd('temperature', $tempe);
                        $this->setConfiguration('camera_temperature',$tempe);
                        break;
                    }
                }
                foreach($datas['owls'] as $camera) {
                    //blink_camera::logdebug('refreshCameraInfos() '.$this->getConfiguration('camera_id').' - '.print_r($camera,true));
                    if ($camera['id']==$this->getConfiguration('camera_id')) {
                        /*if ($camera['enabled']===true) {
                            $this->checkAndUpdateCmd('arm_status_camera', 1);
                            $this->setConfiguration('camera_status',true);
                        } else 
                        {
                            $this->checkAndUpdateCmd('arm_status_camera', 0);
                            $this->setConfiguration('camera_status',false);
                        }*/
                        $this->setConfiguration('camera_type',$camera['type']);
                        $this->setConfiguration('camera_name',$camera['name']);
                        //$this->setConfiguration('camera_battery_status',$camera['battery']);
                        break;
                    }
                }
                foreach($datas['doorbells'] as $camera) {
                    //blink_camera::logdebug('refreshCameraInfos() '.$this->getConfiguration('camera_id').' - doorbells - '.print_r($camera,true));
                    if ($camera['id']==$this->getConfiguration('camera_id')) {
                        if ($camera['enabled']===true) {
                            $this->checkAndUpdateCmd('arm_status_camera', 1);
                            $this->setConfiguration('camera_status',true);
                        } else 
                        {
                            $this->checkAndUpdateCmd('arm_status_camera', 0);
                            $this->setConfiguration('camera_status',false);
                        }
                        $this->setConfiguration('camera_type',$camera['type']);
                        $this->setConfiguration('camera_name',$camera['name']);
                        $this->setConfiguration('camera_battery_status',$camera['battery']);
                        $signal=$camera['signals'];
                        $batteryLevel=(float) $signal['battery'];

                        $battery=100*$batteryLevel/5;
                        $this->checkAndUpdateCmd('battery', $battery);
                        $this->setConfiguration('battery',$battery);
                        $this->batteryStatus($battery);
    
                        // MAJ WIFI
                        $wifi=100*(float) $signal['wifi']/5;
                        $this->checkAndUpdateCmd('wifi_strength', $wifi);
                        $this->setConfiguration('camera_wifi',$wifi);
                        break;
                    }
                }
                foreach($datas['networks'] as $network) {
                    if ($network['id']==$this->getConfiguration('network_id')) {
                        if ($network['armed']===true) {
                            $this->checkAndUpdateCmd('arm_status', 1);
                        } else 
                        {
                            $this->checkAndUpdateCmd('arm_status', 0);
                        }
                        $this->setConfiguration('network_status',$network['armed']);
                        $this->setConfiguration('network_time_zone',$network['time_zone']);
                        $this->setConfiguration('network_name',$network['name']);
                        break;
                    }
                }
                foreach($datas['sync_modules'] as $syncMod) {
                    if ($syncMod['network_id']==$this->getConfiguration('network_id')) {
                        if ($syncMod['local_storage_enabled']===true) {
                            $this->setConfiguration('storage', 'local');
                        } else 
                        {
                            $this->setConfiguration('storage', 'cloud');
                        }
                        $this->setConfiguration('sync_id',$syncMod['id']);
//                        blink_camera::logdebug('refreshCameraInfo: network_id:f'.$syncMod['network_id'].' - syncId:'.$syncMod['id']);
                        break;
                    }
                }
                $this->setConfiguration('account_storage',$datas['video_stats']['storage']);
                $this->setConfiguration('account_auto_delete_days', $datas['video_stats']['auto_delete_days']);
                $this->save();
            }
		}
    }
    
    public function getMediaDir()
    {
        return __DIR__.'/../../medias/'.$this->getId();
    }

    public function networkArm()
    {
        if (self::isConnected() && $this->isConfigured()) {
                $url='/network/'.$this->getConfiguration('network_id').'/arm';
                try {
                    blink_camera::queryPost($url);
                    jeedomUtils.sleep(1);
                    $this->refreshCameraInfos("networkArm");
                    return true;
                } catch (TransferException $e) {
                    blink_camera::logdebug('An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                    return false;
                }
        }
        return false;
    }
    public function networkDisarm()
    {
        if (self::isConnected() && $this->isConfigured()) {
            $url='/network/'.$this->getConfiguration('network_id').'/disarm';
            try {
                blink_camera::queryPost($url);
                jeedomUtils.sleep(1);
                $this->refreshCameraInfos("networkDisarm");
                return true;
            } catch (TransferException $e) {
                blink_camera::logdebug('An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                return false;
            }
        }
        return false;
    }



    public function cameraArm()
    {
        if (self::isConnected() && $this->isConfigured()) {
            $url="/network/".$this->getConfiguration('network_id')."/camera/".$this->getConfiguration('camera_id')."/enable";
            try {
                blink_camera::queryPost($url);
                jeedomUtils.sleep(1);
                $this->refreshCameraInfos("cameraArm");
                return true;
            } catch (TransferException $e) {
                blink_camera::logdebug('An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                return false;
            }
        }
        return false;
    }

    public function cameraDisarm()
    {
        if (self::isConnected() && $this->isConfigured()) {
            $url="/network/".$this->getConfiguration('network_id')."/camera/".$this->getConfiguration('camera_id')."/disable";
            try {
                blink_camera::queryPost($url);
                jeedomUtils.sleep(1);
                $this->refreshCameraInfos("cameraDisarm");
                return true;
            } catch (TransferException $e) {
                blink_camera::logdebug('An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                return false;
            }
       }
        return false;
    }

    public static function deleteMedia($filepath,$folder=false) {
        $filepath=trim(realpath($filepath));
        if (!empty($filepath)) {
            // On controle que l'on soit bien dans le dossier de stockage des medias du plugin !
            if (strpos($filepath, '/plugins/blink_camera/') !== false && strpos($filepath, '/medias/') !== false) {
            $commande='rm ';
                if ($folder) {
                    $commande.='-rf ';
                }
                $commande.="'".$filepath."'";
                //blink_camera::logdebug('delete: '.$commande);
                shell_exec($commande);
            } else {
                blink_camera::logdebug('Plugin blink camera try to delete file not in "medias" folder : '.$filepath);
            }
        }
    }
    
    public static function deleteMediaCloud($filepath) {
        $filepath=realpath($filepath);
        //blink_camera::logdebug('deleteMediaCloud(filepath='.$filepath.') START');
        // On controle que l'on soit bien dans le dossier de stockage des medias du plugin !                
        if (strpos($filepath, '/plugins/blink_camera/') !== false && strpos($filepath, '/medias/') !== false) {
            $mediaId=explode('-',basename($filepath))[0];

           
            if (isset($mediaId) && $mediaId!="" && self::isConnected()) {
                $_accountBlink=config::byKey('account', 'blink_camera');
                $datas='{"media_list":['.$mediaId.']}';
                $url='/api/v1/accounts/'.$_accountBlink.'/media/delete';
                try {
                    $jsonrep=blink_camera::queryPost($url,$datas);
                    // Recup de la camera concernée pour vérifier si on est sur la suppression du dernier event
                    $cameraId=explode('/',explode('/medias/',$filepath)[1])[0];
                    $eqLogics = self::byType('blink_camera', true);
                    foreach ($eqLogics as $blink_camera) {
                        if ($blink_camera->getId() == $cameraId) {
                                $infoCmd=$blink_camera->getCmd(null, 'last_event');
                                if (is_object($infoCmd)) {
                                    $previous=$infoCmd->execCmd();
                                    $fichier=basename($filepath);
                                    if (strpos($fichier, $previous) !== false) {
                                        // Si on a supprimé le dernier event, on force le recalcul de la date de dernier event
                                        $blink_camera->getLastEventDate(true);
                                    }
                                }
                        }
                    }
                    if ($jsonrep['code']==='711') {
                        return true;
                    } 
                    return false;
                } catch (TransferException $e) {
                    blink_camera::logdebug('An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                    return false;
                }
            }
            return false;
        } else {
            blink_camera::logdebug('Plugin blink camera try to delete file in Blink cloud but not in "medias" folder');
        }
    }

    public static function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }



    private static function genererIdAleatoire($longueur = 16)
    {
        //return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($longueur/strlen($x)) )),1,$longueur);
        return substr(str_shuffle(str_repeat($x='0123456789', ceil($longueur/strlen($x)) )),1,$longueur);
    }

  	public static function backupExclude() {
        $pathToExclude= array();
        $pathToExclude[]="medias";
        $pathToExclude[]="test";
        $pathToExclude[]="../afficheur";
        return $pathToExclude;
    }
    public function preInsert()
    {
        //$this->setConfiguration("test","PREUPDATE2");
    }

    public function postInsert()
    {
    }

    public function preSave()
    {
        //$this->setDisplay("width", "400px");
        //$this->setDisplay("showNameOndashboard", 0);
    }

    public function postSave()
    {
        $typeDevice=$this->getBlinkDeviceType();
        $this->setConfiguration("type",$typeDevice);
        $info = $this->getCmd(null, 'arm_status');
        if (!is_object($info)) {
            blink_camera::loginfo( 'Create new information : arm_status');
            $info = new blink_cameraCmd();
            $info->setName(__('Système armé ?', __FILE__));
            $info->setDisplay("showNameOndashboard", 1);
            $info->setIsVisible(true);
            $info->setLogicalId('arm_status');
            $info->setEqLogic_id($this->getId());
            $info->setType('info');
            $info->setTemplate('dashboard', 'lock');
            $info->setTemplate('mobile', 'lock');
            $info->setSubType('binary');
            $info->setConfiguration('generic_type',"LOCK_STATE");
            $info->setOrder(1);
            $info->save();
        }
        if ($typeDevice!="owl" and $typeDevice!="lotus") {
            $arm_status_camera = $this->getCmd(null, 'arm_status_camera');
            if (!is_object($arm_status_camera)) {
                blink_camera::loginfo( 'Create new information : arm_status_camera');
                $arm_status_camera = new blink_cameraCmd();
                $arm_status_camera->setName(__('Caméra armée ?', __FILE__));
                $arm_status_camera->setDisplay("showNameOndashboard", 1);
                $arm_status_camera->setIsVisible(true);
                $arm_status_camera->setLogicalId('arm_status_camera');
                $arm_status_camera->setEqLogic_id($this->getId());
                $arm_status_camera->setType('info');
                $arm_status_camera->setTemplate('dashboard', 'lock');
                $arm_status_camera->setTemplate('mobile', 'lock');
                $arm_status_camera->setSubType('binary');
                $arm_status_camera->setConfiguration('generic_type',"LOCK_STATE");
                $arm_status_camera->setOrder(2);
                $arm_status_camera->save();
            }

            $temperature = $this->getCmd(null, 'temperature');
            if (!is_object($temperature)) {
                blink_camera::loginfo( 'Create new information : temperature');
                $temperature = new blink_cameraCmd();
                $temperature->setName(__('Température', __FILE__));
                $temperature->setTemplate('dashboard', 'badge');
                $temperature->setDisplay("showNameOndashboard", 1);
                $temperature->setConfiguration('generic_type',"TEMPERATURE");
                $temperature->setConfiguration('historizeRound',"1");
                $temperature->setUnite('°'.config::byKey('blink_tempUnit', 'blink_camera'));
                $temperature->setIsVisible(true);
                $temperature->setLogicalId('temperature');
                $temperature->setEqLogic_id($this->getId());
                $temperature->setType('info');
                $temperature->setSubType('numeric');
                $temperature->setOrder(3);
                $temperature->save();
            }
            $power = $this->getCmd(null, 'power');
                if (!is_object($power)) {
                    blink_camera::loginfo( 'Create new information : power');
                    $power = new blink_cameraCmd();
                    $power->setName(__('Pile', __FILE__));
                    $power->setTemplate('dashboard', 'badge');
                    $power->setDisplay("showNameOndashboard", 1);
                    $power->setConfiguration('historizeRound',"2");
                    $power->setConfiguration('historizeRound',"2");
                    $power->setUnite('V');
                    $power->setIsVisible(true);
                    $power->setLogicalId('power');
                    $power->setEqLogic_id($this->getId());
                    $power->setType('info');
                    $power->setSubType('numeric');
                    $power->setOrder(4);
                    $power->save();
                }
            $wifi_strength = $this->getCmd(null, 'wifi_strength');
            if (!is_object($wifi_strength)) {
                blink_camera::loginfo( 'Create new information : wifi_strength');
                $wifi_strength = new blink_cameraCmd();
                $wifi_strength->setName(__('Puissance Wifi', __FILE__));
                $wifi_strength->setTemplate('dashboard', 'badge');
                $wifi_strength->setDisplay("showNameOndashboard", 1);
                $wifi_strength->setConfiguration('historizeRound',"0");
                $wifi_strength->setConfiguration('historizeRound',"0");
                $wifi_strength->setUnite('dB');
                $wifi_strength->setIsVisible(true);
                $wifi_strength->setLogicalId('wifi_strength');
                $wifi_strength->setEqLogic_id($this->getId());
                $wifi_strength->setType('info');
                $wifi_strength->setSubType('numeric');
                $wifi_strength->setOrder(5);
                $wifi_strength->save();
            }
        }
        $info = $this->getCmd(null, 'last_event');
        if (!is_object($info)) {
            blink_camera::loginfo( 'Create new information : last_event');
            $info = new blink_cameraCmd();
            $info->setName(__('Dernier événement', __FILE__));
            $info->setLogicalId('last_event');
            $info->setEqLogic_id($this->getId());
            $info->setType('info');
            $info->setIsVisible(true);
            $info->setTemplate('dashboard', 'default');
            $info->setDisplay("showNameOndashboard", 1);
            $info->setSubType('string');
            $info->setOrder(6);
            $info->save();
        }

        $info = $this->getCmd(null, 'thumbnail');
        if (!is_object($info)) {
            blink_camera::loginfo( 'Create new information : thumbnail');
            $info = new blink_cameraCmd();
            $info->setName(__('Vignette', __FILE__));
            $info->setDisplay("showNameOndashboard", 0);
            $info->setIsVisible(true);
            $info->setTemplate('dashboard', 'default');
            $info->setLogicalId('thumbnail');
            $info->setEqLogic_id($this->getId());
            $info->setType('info');
            $info->setSubType('string');
            $info->setOrder(7);
            $info->save();
        }

        $info = $this->getCmd(null, 'clip_path');
        if (!is_object($info)) {
            blink_camera::loginfo( 'Create new information : clip_path');
            $info = new blink_cameraCmd();
            $info->setName(__('Chemin vidéo', __FILE__));
            $info->setDisplay("showNameOndashboard", 1);
            $info->setIsVisible(0);
            $info->setTemplate('dashboard', 'default');
            $info->setLogicalId('clip_path');
            $info->setEqLogic_id($this->getId());
            $info->setType('info');
            $info->setSubType('string');
            $info->setOrder(8);
            $info->save();
        }
        $info = $this->getCmd(null, 'thumb_path');
        if (!is_object($info)) {
            blink_camera::loginfo( 'Create new information : thumb_path');
            $info = new blink_cameraCmd();
            $info->setName(__('Chemin vignette', __FILE__));
            $info->setDisplay("showNameOndashboard", 1);
            $info->setIsVisible(0);
            $info->setTemplate('dashboard', 'default');
            $info->setLogicalId('thumb_path');
            $info->setEqLogic_id($this->getId());
            $info->setType('info');
            $info->setSubType('string');
            $info->setOrder(9);
            $info->save();
        }
        $info = $this->getCmd(null, 'camera_thumb_path');
        if (!is_object($info)) {
            blink_camera::loginfo( 'Create new information : camera_thumb_path');
            $info = new blink_cameraCmd();
            $info->setName(__('Chemin vignette caméra', __FILE__));
            $info->setDisplay("showNameOndashboard", 1);
            $info->setIsVisible(0);
            $info->setTemplate('dashboard', 'default');
            $info->setLogicalId('camera_thumb_path');
            $info->setEqLogic_id($this->getId());
            $info->setType('info');
            $info->setSubType('string');
            $info->setOrder(9);
            $info->save();
        }
        $info = $this->getCmd(null, 'thumb_url');
        if (!is_object($info)) {
            blink_camera::loginfo( 'Create new information : thumb_url');
            $info = new blink_cameraCmd();
            $info->setName(__('URL vignette', __FILE__));
            $info->setDisplay("showNameOndashboard", 1);
            $info->setIsVisible(0);
            $info->setTemplate('dashboard', 'default');
            $info->setLogicalId('thumb_url');
            $info->setEqLogic_id($this->getId());
            $info->setType('info');
            $info->setSubType('string');
            $info->setOrder(10);
            $info->save();
        }
        $info = $this->getCmd(null, 'camera_thumb_url');
        if (!is_object($info)) {
            blink_camera::loginfo( 'Create new information : camera_thumb_url');
            $info = new blink_cameraCmd();
            $info->setName(__('URL vignette caméra', __FILE__));
            $info->setDisplay("showNameOndashboard", 1);
            $info->setIsVisible(0);
            $info->setTemplate('dashboard', 'default');
            $info->setLogicalId('camera_thumb_url');
            $info->setEqLogic_id($this->getId());
            $info->setType('info');
            $info->setSubType('string');
            $info->setOrder(10);
            $info->save();
        }
        $info = $this->getCmd(null, 'clip_url');
        if (!is_object($info)) {
            blink_camera::loginfo( 'Create new information : clip_url');
            $info = new blink_cameraCmd();
            $info->setName(__('URL dernière vidéo', __FILE__));
            $info->setDisplay("showNameOndashboard", 1);
            $info->setIsVisible(0);
            $info->setTemplate('dashboard', 'default');
            $info->setLogicalId('clip_url');
            $info->setEqLogic_id($this->getId());
            $info->setType('info');
            $info->setSubType('string');
            $info->setOrder(11);
            $info->save();
        }
        if ($typeDevice!="owl") {
            $battery = $this->getCmd(null, 'battery');
            if (!is_object($battery)) {
                blink_camera::loginfo( 'Create new information : battery');
                $battery = new blink_cameraCmd();
                $battery->setName(__('Pile (pourcentage)', __FILE__));
                $battery->setTemplate('dashboard', 'badge');
                $battery->setDisplay("showNameOndashboard", 1);
                $battery->setConfiguration('historizeRound',"2");
                $battery->setUnite('%');
                $battery->setIsVisible(true);
                $battery->setLogicalId('battery');
                $battery->setEqLogic_id($this->getId());
                $battery->setType('info');
                $battery->setSubType('numeric');
                $battery->setOrder(12);
                $battery->save();
            }
        }
        $refresh = $this->getCmd(null, 'refresh');
        if (!is_object($refresh)) {
            blink_camera::loginfo( 'Create new action : refresh');
            $refresh = new blink_cameraCmd();
            $refresh->setName(__('Rafraichir', __FILE__));
            $refresh->setEqLogic_id($this->getId());
            $refresh->setLogicalId('refresh');
            $refresh->setType('action');
            $refresh->setSubType('other');
            //$refresh->setOrder(100);
            $refresh->save();
        }
        
        $arm_network = $this->getCmd(null, 'arm_network');
        if (!is_object($arm_network)) {
            blink_camera::loginfo( 'Create new action : arm_network');
            $arm_network = new blink_cameraCmd();
            $arm_network->setName(__('Armer le système', __FILE__));
            $arm_network->setEqLogic_id($this->getId());
            $arm_network->setLogicalId('arm_network');
            $arm_network->setType('action');
            $arm_network->setSubType('other');
            //$arm_network->setOrder(101);
            $arm_network->setDisplay('icon','<i class="jeedom jeedom-lock-ferme"></i>'); 
            $arm_network->useIconAndName();
            $arm_network->save();
        }
        
        $arm_network = $this->getCmd(null, 'disarm_network');
        if (!is_object($arm_network)) {
            blink_camera::loginfo( 'Create new action : disarm_network');
            $arm_network = new blink_cameraCmd();
            $arm_network->setName(__('Désarmer le système', __FILE__));
            $arm_network->setEqLogic_id($this->getId());
            $arm_network->setLogicalId('disarm_network');
            $arm_network->setType('action');
            $arm_network->setSubType('other');
            //$arm_network->setOrder(102);
            $arm_network->setDisplay('icon','<i class="jeedom jeedom-lock-ouvert"></i>'); 
            $arm_network->useIconAndName();
            $arm_network->save();
        }
        if ($typeDevice!="owl" and $typeDevice!="lotus") {
            $arm_camera = $this->getCmd(null, 'arm_camera');
            if (!is_object($arm_camera)) {
                blink_camera::loginfo( 'Create new action : arm_camera');
                $arm_camera = new blink_cameraCmd();
                $arm_camera->setName(__('Armer la caméra', __FILE__));
                $arm_camera->setEqLogic_id($this->getId());
                $arm_camera->setLogicalId('arm_camera');
                $arm_camera->setType('action');
                $arm_camera->setSubType('other');
                //$arm_camera->setOrder(103);
                $arm_camera->setDisplay('icon','<i class="jeedom jeedom-lock-ferme"></i>'); 
                $arm_camera->useIconAndName();
                $arm_camera->save();
            }
            
            $disarm_camera = $this->getCmd(null, 'disarm_camera');
            if (!is_object($disarm_camera)) {
                blink_camera::loginfo( 'Create new action : disarm_camera');
                $disarm_camera = new blink_cameraCmd();
                $disarm_camera->setName(__('Désarmer la caméra', __FILE__));
                $disarm_camera->setEqLogic_id($this->getId());
                $disarm_camera->setLogicalId('disarm_camera');
                $disarm_camera->setType('action');
                $disarm_camera->setSubType('other');
                //$arm_camera->setOrder(104);
                $disarm_camera->setDisplay('icon','<i class="jeedom jeedom-lock-ouvert"></i>'); 
                $disarm_camera->useIconAndName();
                $disarm_camera->save();
            }
        }
        $history = $this->getCmd(null, 'history');
        if (!is_object($history)) {
            blink_camera::loginfo( 'Create new action : history');
            $history = new blink_cameraCmd();
            $history->setName(__('Historique', __FILE__));
            $history->setDisplay('icon','<i class="fa fa-clock"></i>'); 
            $history->setEqLogic_id($this->getId());
            $history->setLogicalId('history');
            $history->setType('action');
            $history->setSubType('other');
            //$history->setOrder(105);
            $history->useIconAndName();
            $history->save();
        }
		$newClip = $this->getCmd(null, 'new_clip');
        if (!is_object($newClip)) {
            blink_camera::loginfo( 'Create new action : new_clip');
            $newClip = new blink_cameraCmd();
            $newClip->setName(__('Prendre une vidéo', __FILE__));
            $newClip->setEqLogic_id($this->getId());
            $newClip->setLogicalId('new_clip');
            $newClip->setType('action');
            $newClip->setSubType('other');
            $newClip->setDisplay('icon','<i class="fa fa-video"></i>'); 
            $newClip->useIconAndName();
            //$newClip->setOrder(106);
            $newClip->save();
        }

        $newThumbnail = $this->getCmd(null, 'new_thumbnail');
        if (!is_object($newThumbnail)) {
            blink_camera::loginfo( 'Create new action : new_thumbnail');
            $newThumbnail = new blink_cameraCmd();
            $newThumbnail->setName(__('Prendre une photo', __FILE__));
            $newThumbnail->setEqLogic_id($this->getId());
            $newThumbnail->setLogicalId('new_thumbnail');
            $newThumbnail->setType('action');
            $newThumbnail->setSubType('other');
            $newThumbnail->setDisplay('icon','<i class="fa fa-camera"></i>'); 
            $newThumbnail->useIconAndName();
            //$newClip->setOrder(106);
            $newThumbnail->save();
        }

		$force_download = $this->getCmd(null, 'force_download');
        if (!is_object($force_download)) {
            blink_camera::loginfo( 'Create new action : force_download');
            $force_download = new blink_cameraCmd();
            $force_download->setName(__('Forcer le téléchargement', __FILE__));
            $force_download->setEqLogic_id($this->getId());
            $force_download->setLogicalId('force_download');
            $force_download->setType('action');
            $force_download->setSubType('other');
            $force_download->setDisplay('icon','<i class="fa fa-download"></i>'); 
            $force_download->useIconAndName();
            //$force_download->setOrder(107);
            $force_download->save();
        }
        if ($typeDevice=="lotus") {
            $info = $this->getCmd(null, 'source_last_event');
            if (!is_object($info)) {
                blink_camera::loginfo( 'Create new information : source_last_event');
                $info = new blink_cameraCmd();
                $info->setName(__('Source du dernier événement', __FILE__));
                $info->setDisplay("showNameOndashboard", 1);
                $info->setIsVisible(0);
                $info->setTemplate('dashboard', 'default');
                $info->setLogicalId('source_last_event');
                $info->setEqLogic_id($this->getId());
                $info->setType('info');
                $info->setSubType('string');
                $info->save();
            }

        }

        $notification_key=config::byKey('notification_key', 'blink_camera');
        $unique_id=config::byKey('notification_key', 'blink_camera');

        //$notification_key="";
        //$unique_id="";    
        if (!isset($notification_key) || $notification_key==="" || strlen($notification_key) <> 152) {
            $notification_key=blink_camera::genererIdAleatoire(152);
            config::save('notification_key', $notification_key, 'blink_camera');
        }
        if (!isset($unique_id) || $unique_id==="" || strlen($unique_id) <> 16) {
            $unique_id=blink_camera::genererIdAleatoire(16);
            config::save('unique_id', $unique_id, 'blink_camera');
        }
/*
        if (blink_camera::isConnected()) {
            $this::refreshCameraInfos();
            $this::getLastEventDate();
            //$this::emptyCacheWidget();
        }
    */
    }

    public function preUpdate()
    {
    }

    public function postUpdate()
    {
		/*$cmd = $this->getCmd(null, 'refresh'); 
		if (($this->getIsEnable() == 1) && is_object($cmd)) { 
			 $cmd->execCmd();
        }*/
        //$this::refreshCacheWidget();
    }

    public function preRemove()
    {
        blink_camera::deleteMedia($this->getMediaDir(),true);
    }

    public function postRemove()
    {
    }
    
    public function toHtml($_version = 'dashboard', $_fluxOnly = false)
    {
        return parent::toHtml($_version);
    }

    /*
     * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */
    public static function postConfig_param1($value)
    {
        self::postConfigOverall($value);
    }
    public static function postConfig_param2($value)
    {
        self::postConfigOverall($value);
    }
    public static function postConfig_blink_dashboard_content_type($value)
    {
        self::postConfigOverall($value);
	}
    public static function postConfig_blink_size_videos($value)
    {
        self::postConfigOverall($value);
	}	

    public static function postConfigOverall($value)
    {
        /*
        $eqLogics = self::byType('blink_camera', true);
        foreach ($eqLogics as $blink_camera) {
            if ($blink_camera->getIsEnable() == 1) {
                $blink_camera->emptyCacheWidget();
            }
        }
        */
    }
	public static function templateWidget()
	{
        $return = array('info' => array('binary' => array()));
        $return['info']['binary']['Camera or System status'] = array(
            'template' => 'switch',
            'display' => array(
                '#icon#' => '<i class=\'jeedom jeedom-lock-ferme\'></i>',
            ),
            'replace' => array(
                '#_icon_on_#' => '<i class=\'jeedom jeedom-lock-ferme\'></i>',
                '#_icon_off_#' => '<i class=\'jeedom jeedom-lock-ouvert\'></i>',
                '#_time_widget_#' => '0'
            )
        );
        return $return;
    }
}

class blink_cameraCmd extends cmd
{
    /*     * *************************Attributs****************************** */
    public static $_widgetPossibility = array('custom' => true, 'custom::layout' => true);


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */
    public function toHtml($_version = 'dashboard', $_options = '', $_cmdColor = null) {
        if ($this->getLogicalId() == 'arm_status_camera' || $this->getLogicalId() == 'arm_status') {
            $bl_cam=$this->getEqLogic();
            if ($bl_cam->isConnected() && $bl_cam->isConfigured()) {
                if ($this->getLogicalId() == 'arm_status_camera') {
                    $on = $bl_cam->getCmd(null, 'arm_camera');
                    $off = $bl_cam->getCmd(null, 'disarm_camera');
                } else {
                    $on = $bl_cam->getCmd(null, 'arm_network');
                    $off = $bl_cam->getCmd(null, 'disarm_network');
                }
                if (is_object($on) && is_object($off)) {
                    $replace['cmd_on_id'] = $on->getId();
                    $replace['cmd_off_id'] = $off->getId();
                    $replace['_icon_on_'] ='<i class=\"icon jeedom-lock-ferme\"></i>';
                    $replace['_icon_off_'] ='<i class=\"icon jeedom-lock-ouvert\"></i>';
                } else {
                    $replace['cmd_on_id'] = '""';
                    $replace['cmd_off_id'] = '""';
                    $replace['_icon_on_'] ='';
                    $replace['_icon_off_'] ='';
                }
                return parent::toHtml($_version,$replace,$_cmdColor);
    //		    $template = $this->getTemplate($_version, 'default');
    //            blink_camera::logdebug('cmd->toHtml '. $replace['#id#'].' template : '.$template);
    //            if ($template =='blink_camera::switch') {
    //              blink_camera::logdebug('cmd->toHtml '. $replace['#id#'].' parent : '.print_r($result,true));
    //            }
            } else {
                return "";
            } 
        }else if ($this->getLogicalId() == 'arm_status') {
            $result=parent::toHtml($_version,$_options,$_cmdColor);
            return $result;
        } else if ($this->getLogicalId()==='history') {
            $bl_cam=$this->getEqLogic();
            //if ($bl_cam->isConnected() && $bl_cam->isConfigured()) {
                if ($bl_cam->isConfigured()) {
                //blink_camera::logdebug('toHtml history : '.print_r(parent::toHtml($_version,$_options,$_cmdColor),true));
                $result=parent::toHtml($_version,$_options,$_cmdColor);
                $bl_cam=$this->getEqLogic();
                
                $result.='<script> $(\'.cmd[data-cmd_id='.$this->getId().']\').off(\'click\').on(\'click\', function () {';
                $result.='$(\'#md_modal\').dialog({title: "Historique '.$bl_cam->getName().'"});';
#                $result.='$(\'#md_modal\').load(\'index.php?v=d&plugin=blink_camera&modal=blink_camera.history&id='.$bl_cam->getId().'&mode='.$bl_cam->getConfigHistory().'\').dialog(\'open\');});';
                $result.='$(\'#md_modal\').load(\'index.php?v=d&plugin=blink_camera&modal=blink_camera.history&id='.$bl_cam->getId().'\').dialog(\'open\');});';
                $result.="</script>";

                return $result;
            } else {
                return "";
            }
        }else if ($this->getType()!=='action') {
            $bl_cam=$this->getEqLogic();
            if ($this->getLogicalId()==='thumbnail' && !$bl_cam->isConnected() ) {
                return "<div class=\"badge text-warning text-wrap\">".__('Mode hors ligne', __FILE__)."</div>".parent::toHtml($_version, $_options, $_cmdColor);
            } else if ($bl_cam->isConfigured()) {
                return parent::toHtml($_version, $_options, $_cmdColor);
            } else {
                return "";
            }
        } else {
            $bl_cam=$this->getEqLogic();
            if ($bl_cam->isConnected() && $bl_cam->isConfigured()) {
                return parent::toHtml($_version, $_options, $_cmdColor);
            } else {
                return "";
            }
        }
        
    }
    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */
	public function useIconAndName($_version="dashboard") {
		$version2 = jeedom::versionAlias($_version, false);
		$this->setDisplay('showIconAndName' . $version2, "1");
	}

    public function execute($_options = array())
    {
        $eqlogic = $this->getEqLogic(); //récupère l'éqqlogic de la commande $this

        switch ($this->getLogicalId()) {	//vérifie le logicalid de la commande
            case 'refresh':
                if ($eqlogic->isConfigured() && blink_camera::isConnected()) {
                    //rafraichissement de la datetime du dernier event
                    $eqlogic->getLastEventDate();
                    //$eqlogic->refreshCameraInfos("execute refresh");
                }
           
				break;
			case 'force_download':
                // Nettoyage des fichiers du dossier medias
				$eqlogic->forceCleanup(true);
                 
                //rafraichissement de la datetime du dernier event
                $eqlogic->getLastEventDate();
                $eqlogic->refreshCameraInfos("execute force_download");
                break;
			
			case 'new_clip':
                if ($eqlogic->getBlinkDeviceType()==="owl") {
                    $eqlogic->requestNewMediaMini("clip");
                } else if ($eqlogic->getBlinkDeviceType()==="lotus") {
                    $eqlogic->requestNewMediaDoorbell("clip");
                } else {
                    $eqlogic->requestNewMediaCamera("clip");
                }
                $eqlogic->getLastEventDate();
                $eqlogic->refreshCameraInfos("execute new_clip");
				break;

            case 'new_thumbnail':
                if ($eqlogic->getBlinkDeviceType()==="owl") {
                    $eqlogic->requestNewMediaMini("thumbnail");
                } else if ($eqlogic->getBlinkDeviceType()==="lotus") {
                    $eqlogic->requestNewMediaDoorbell("thumbnail");
                } else {
                    $eqlogic->requestNewMediaCamera("thumbnail");
                }
                $eqlogic->getLastEventDate();
                $eqlogic->refreshCameraInfos("execute new_thumbnail");
                break;                
                
            case 'arm_network':
                $eqlogic->networkArm();
                break;

            case 'disarm_network':
                $eqlogic->networkDisarm();
                break;

            case 'arm_camera':
                $eqlogic->cameraArm();
                jeedomUtils.sleep(1);
                $eqlogic->getLocalMedia();
                break;

            case 'disarm_camera':
                $eqlogic->cameraDisarm();
                break;
        }
    }

}