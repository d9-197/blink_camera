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
    const FORMAT_DATETIME="Y-m-d\TH:i:sT" ;
    const FORMAT_DATETIME_OUT="Y-m-d_His" ;
    const FORMAT_DATETIME_OUT_FR="d/m/Y H:i:s" ;
    const FORMAT_DATE_OUT="Y-m-d" ;
    const FORMAT_DATE_OUT_FR="d/m/Y" ;
    const ERROR_IMG="/plugins/blink_camera/img/error.png";
    const NO_EVENT_IMG="/plugins/blink_camera/img/no_event.png";
    const GET_RESOURCE="/plugins/blink_camera/core/php/getResource.php?file=";
    const PREFIX_THUMBNAIL="thumbnail";    
    const ATTENTE_MAXI_DEFAUT=2;
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

    public static function logDebugBlinkAPIRequest($message) {
        config::save('log::level::blink_camera_api', config::byKey('log::level::blink_camera'));
        log::add('blink_camera_api','debug',$message);
        return;
    }
    public static function logDebugBlinkAPIResponse($message) {
        config::save('log::level::blink_camera_api', config::byKey('log::level::blink_camera'));
        log::add('blink_camera_api','debug',$message);
        return;
    }
    private static function logDebugBlinkResponse($message) {
        if (log::getLogLevel('blink_camera')==100) {
            //message::add('Blink Camera',__('APImessage', __FILE__). __($message, __FILE__));
        }
        self::logdebug(__('APImessage', __FILE__). __($message, __FILE__));
    }
    public static function cleanSpecialCharacters($string, $replacement='_') {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        return preg_replace('/[^A-Za-z0-9]/', $replacement, $string); // Removes special chars.
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
    public static function searchForFile($fileToSearchFor){
        foreach (glob($fileToSearchFor) as $filesFound) {
            return $filesFound;
        }
        return false;
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
            $email=$cam->getConfiguration("email");
            if (self::isConnected($email)) {
                if ($cam->getIsEnable() == 1) {
                    $cam->refreshCameraInfos("cron10");
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
            $email=$cam->getConfiguration("email");
            if (self::isConnected($email)) {
                if ($cam->getIsEnable() == 1) {
                    $cam->refreshCameraInfos();
                    $cam->getLastEventDate();
                }
            }
        }
    }


    public static function cronHourly($_eqLogic_id = null)
    {
        if ($_eqLogic_id == null) {
            $eqLogics = self::byType('blink_camera', true);
        } else {
            $eqLogics = array(self::byId($_eqLogic_id));
        }
        foreach ($eqLogics as $cam) {
            $email=$cam->getConfiguration("email");
            if (self::isConnected($email)) {
                if ($cam->getIsEnable() == 1) {
                    $cam->forceCleanup(true);
               }
            }
        }
    }
    public static function cronDaily($_eqLogic_id = null)
    {
       //self::getToken(true);
    }
    
    private static function startwith(string $text,string $criteria) {
        return substr( $text, 0, strlen($criteria)) === $criteria;
    }
    public static function isOpenMediasAccess() {
        return !config::byKey('medias_security', 'blink_camera');
    }
    public static function initNewConfigAccount() {
        $email=config::byKey('param1', 'blink_camera');
        self::logdebug('initNewConfigAccount '.$email);
        config::remove('configBlinkAccounts','blink_camera');
        //config::save('configBlinkAccounts','-','blink_camera');
        self::setConfigBlinkAccount($email,'pwd',utils::encrypt(config::byKey('param2', 'blink_camera')));
        //self::setConfigBlinkAccount($email,'pwd',config::byKey('param2', 'blink_camera'));
        self::setConfigBlinkAccount($email,'accountId',config::byKey('account', 'blink_camera'));
        self::setConfigBlinkAccount($email,'token',config::byKey('token', 'blink_camera'));
        self::setConfigBlinkAccount($email,'client',config::byKey('client', 'blink_camera'));
        self::setConfigBlinkAccount($email,'region',config::byKey('region', 'blink_camera'));
        self::setConfigBlinkAccount($email,'uniqId',config::byKey('unique_id', 'blink_camera'));
        self::setConfigBlinkAccount($email,'notificationKey',config::byKey('notification_key', 'blink_camera'));
        self::setConfigBlinkAccount($email,'limitLogin',config::byKey('limit_login', 'blink_camera'));
        self::setConfigBlinkAccount($email,'verif',config::byKey('verif', 'blink_camera'));
        
    }

    public static function getConfigBlinkAccount(string $email, string $key) {
        $accounts=config::byKey('configBlinkAccounts','blink_camera');
        //self::logdebug('getConfigBlinkAccount all values: '. print_r($accounts,true));
        foreach($accounts as $account) {
            if ($account['email'] == $email) {
                //self::logdebug('getConfigBlinkAccount account : '. print_r($account,true));
                //self::logdebug('getConfigBlinkAccount account '.$key.': '. $account[$key]);
                return $account[$key];
            }
        }
    }
    public static function getConfigBlinkAccountsList() {
        $accounts=config::byKey('configBlinkAccounts','blink_camera');
        //self::logdebug('getConfigBlinkAccountsList all values: '. print_r($accounts,true));
        $listAccounts=array();
        foreach($accounts as $account) {
            //self::logdebug('getConfigBlinkAccountsList account : '. $account['account']);
            $listAccounts[]=$account['email'];
        }
        return $listAccounts;
    }

    public static function setConfigBlinkAccount(string $email, string $key,string $value) {
        $accounts=config::byKey('configBlinkAccounts','blink_camera');
        $accountsObj=json_decode($accounts);
        //self::logdebug('setConfigBlinkAccount ('.$email.') previous values: '. print_r($accounts,true));
        $accountIndex=0;
        foreach($accounts as $account) {
          //  self::logdebug('setConfigBlinkAccount account found: '. print_r($account,true));
            if ($account['email'] == $email) {
                //self::logdebug('setConfigBlinkAccount account previous value: '. $account[$key]);
                $account[$key]=$value;
                $accountsObj[$accountIndex]=$account;
                //self::logdebug('setConfigBlinkAccount accounts after : '. print_r($accounts,true));
                config::save('configBlinkAccounts',json_encode($accountsObj),'blink_camera');
                return true;
            }
            $accountIndex++;
        }
        $newAccount=array();
        $newAccount['email']=$email;
        $newAccount[$key]=$value;
        $accountsObj[$accountIndex]=$newAccount;
        config::save('configBlinkAccounts',json_encode($accountsObj),'blink_camera');
        return true;
    }
    public static function queryGet(string $url, string $email) {
        $_tokenBlink=self::getConfigBlinkAccount($email,'token');
        $_accountBlink=self::getConfigBlinkAccount($email,'accountId');
        $_regionBlink=self::getConfigBlinkAccount($email,'region');
        $jsonrep=null;
        self::logdebug("token / accountId / region : ".$_tokenBlink." / " .$_accountBlink." / ".$_regionBlink);
        if (!$_tokenBlink=="" && !$_accountBlink=="" && !$_regionBlink=="") {
            $lock=self::checkAndGetLock('getQuery');
            self::logDebugBlinkAPIRequest("CALL[queryGet]: ".'https://rest.'.$_regionBlink.'.immedia-semi.com/'.$url);
            $client = new GuzzleHttp\Client(['verify' => false,'base_uri' => 'https://rest.'.$_regionBlink.'.immedia-semi.com/'.$url]);
            $r = $client->request('GET', $url, [
                //['http_errors' => false],
                'headers' => [
                    //'Host'=> 'rest-'.$_regionBlink.'.immedia-semi.com',
                    'TOKEN_AUTH'=> ''.$_tokenBlink,
                    'User-Agent' =>  ''.self::BLINK_DEFAULT_USER_AGENT,
                    'Accept' => '/'
                    ]
            ]);
            self::releaseLock($lock);
            $jsonrep= json_decode($r->getBody(), true);
            self::logDebugBlinkAPIResponse(print_r($jsonrep,true));
        }    
        return $jsonrep;
    }
 
    public static function queryGetMedia(string $url, string $file_path, string $email) {
        $_tokenBlink=self::getConfigBlinkAccount($email,'token');
        $_accountBlink=self::getConfigBlinkAccount($email,'accountId');
        $_regionBlink=self::getConfigBlinkAccount($email,'region');
        $jsonrep=null;
        if (!$_tokenBlink=="" && !$_accountBlink=="" && !$_regionBlink=="") {
            $lock=self::checkAndGetLock('queryGetMedia');
            self::logDebugBlinkAPIRequest("CALL[queryGetMedia]: ".$url);
            try {
                $client = new GuzzleHttp\Client(['verify' => false,'base_uri' => 'https://rest.'.$_regionBlink.'.immedia-semi.com/'.$url]);
                $r = $client->request('GET', $url, [
                    'sink' => $file_path,
                    //['http_errors' => false],
                    'headers' => [
                        'Host'=> 'rest-'.$_regionBlink.'.immedia-semi.com',
                        'TOKEN_AUTH'=> ''.$_tokenBlink,
                        'User-Agent' =>  ''.self::BLINK_DEFAULT_USER_AGENT,
                        'Content-Type' => 'application/json',
                        'Accept' => '/'
                        ]
                ]);
                self::releaseLock($lock);
                $jsonrep= json_decode($r->getBody(), true);
            
                self::logDebugBlinkAPIResponse(print_r($jsonrep,true));
            }  catch (Exception $e) {
                self::releaseLock($lock);
                self::logdebug('ERROR:'.print_r($e->getTraceAsString(), true));
                self::logdebug('ERROR:'.print_r($e->getMessage(), true));
                return 1;
            }
        }    
        return $jsonrep;
    }
    
    public static function queryPostLogin(string $url, string $datas, string $email) {
        //self::logdebug('queryPostLogin(url='.$url.',datas='.$datas.') START');
        
        self::logDebugBlinkAPIRequest("CALL[queryPostLogin]: ".$url);
        $lock=self::checkAndGetLock('queryPostLogin');  
        $jsonrep=null;
        try {
            $client = new GuzzleHttp\Client(['verify' => false,'base_uri' => 'https://rest.prod.immedia-semi.com/'. $url]);
            $r = $client->request('POST', 'login', [
                //['http_errors' => false],
                ['timeout' => 1],
                'headers' => [
                    'Host'=> 'rest-prod.immedia-semi.com',
                    'Content-Type'=> 'application/json',
                    'User-Agent' =>  self::BLINK_DEFAULT_USER_AGENT,
                    'Accept' => '/'
                ],
                'json' => json_decode($datas)
            ]);
            self::releaseLock($lock);
            $jsonrep= json_decode($r->getBody(), true);
            self::logDebugBlinkAPIResponse(print_r($jsonrep,true));
            self::setConfigBlinkAccount($email,'limit_login', 'false');
            return $jsonrep;
        }  catch (Exception $e) {
            self::releaseLock($lock);
            //{"message":"Login limit exceeded. Please disable any 3rd party automation and try again in 60 minutes."
            $response = $e->getResponse();
            $responseJson = json_decode($response->getBody()->getContents(),true);
            if (isset($responseJson['message'])) {
                self::logDebugBlinkResponse($responseJson['message']);
                if (self::startwith(strtolower($responseJson['message']),"Login limit exceeded")) {
                    self::setConfigBlinkAccount($email,'limit_login', 'false');
                }
            }
            self::logdebug('ERROR:'.print_r($e->getTraceAsString(), true));
            self::logdebug('ERROR:'.print_r($e->getMessage(), true));
            throw $e;
        }
    }
    // 
    public static function queryPostPinVerify(string $pin, string $email) {
        //self::logdebug('queryPostPinVerify(pin='.$pin.') START');
        $client_id=self::getConfigBlinkAccount($email,'client');
        $_tokenBlink=self::getConfigBlinkAccount($email,'token');
        $account_id=self::getConfigBlinkAccount($email,'accountId');
        $_regionBlink=self::getConfigBlinkAccount($email,'region');
        $url='https://rest-'.$_regionBlink.'.immedia-semi.com/api/v4/account/'.$account_id.'/client/'.$client_id.'/pin/verify';
        self::logDebugBlinkAPIRequest("CALL[queryPostPinVerify]: ".$url);
        $lock=self::checkAndGetLock('queryPostPinVerify');  
        $datas="{\"pin\":".$pin."}";
        try {
            $client = new GuzzleHttp\Client(['verify' => false,'base_uri' =>  $url]);
            $r = $client->request('POST',$url,  [
                //['http_errors' => false],
                ['timeout' => 1],
                'headers' => [
                    'TOKEN_AUTH'=> ''.$_tokenBlink,
                    'User-Agent' =>  self::BLINK_DEFAULT_USER_AGENT
                ],
                'json' => json_decode($datas)
            ]);
            self::releaseLock($lock);
            $jsonrep= json_decode($r->getBody(), true);
            self::logDebugBlinkAPIResponse(print_r($jsonrep,true));

            if ($jsonrep['valid']==1) {
                self::logdebug('queryPostPinVerify(pin='.$pin.') Vérification OK');
                self::setConfigBlinkAccount($email,'verif', 'true');
                return 0;
            } else {
                self::setConfigBlinkAccount($email,'verif', 'false');
                //self::logdebug('queryPostPinVerify(pin='.$pin.') Vérification KO');
                return 1;
            }
        }  catch (Exception $e) {
            self::releaseLock($lock);
            self::logdebug('ERROR:'.print_r($e->getTraceAsString(), true));
            self::logdebug('ERROR:'.print_r($e->getMessage(), true));
            return 1;
        }
        return 0;
    }
    public static function queryPost(string $url, string $datas="{}", string $email) {
        //self::logdebug('queryPost(url='.$url.') START');
        //self::logdebug('queryPost datas:'.$datas);
        $_tokenBlink=self::getConfigBlinkAccount($email,'token');
        $_regionBlink=self::getConfigBlinkAccount($email,'region');
        self::logDebugBlinkAPIRequest("CALL[queryPost]: ".$url);
        $lock=self::checkAndGetLock('queryPost'); 
        try {
            $baseuri='https://rest.'.$_regionBlink.'.immedia-semi.com';
            $client = new GuzzleHttp\Client(['verify' => false,'base_uri' =>  $baseuri]);
            $r = $client->request('POST',$baseuri.'/'.$url,  [
                //['http_errors' => false],
                ['timeout' => 1],
                'headers' => [
                    'TOKEN_AUTH'=> ''.$_tokenBlink,
                    'User-Agent' =>  self::BLINK_DEFAULT_USER_AGENT
                ],
                'json' => json_decode($datas)
            ]);
            //self::releaseLock($lock);
            $jsonrep= json_decode($r->getBody(), true);
            self::logDebugBlinkAPIResponse(print_r($jsonrep,true));
            return $jsonrep;

        }  catch (Exception $e) {
            self::releaseLock($lock);
            throw $e;
            /*$response = $e->getResponse();
            $responseJson = json_decode($response->getBody()->getContents(),true);
            if ($responseJson['code']=='307') {
                self::logDebugBlinkResponse($responseJson['message']);
            }*/
        }
        return "{}";
    }
   /* public  function queryPostLiveview() {
        $network_id=$this->getConfiguration("network_id");
        $camera_id=$this->getConfiguration("camera_id");
        $datas='{"intent":"liveview","motion_event_start_time":""}';
        $_accountBlink=config::byKey('account', 'blink_camera');
        $_regionBlink=config::byKey('region', 'blink_camera');
        $_tokenBlink=config::byKey('token', 'blink_camera');
        $url="api/v5/accounts/".$_accountBlink."/networks/".$network_id."/cameras/".$camera_id."/liveview";
        self::logdebug('queryPostLiveview(url='.$url.') START');
        self::logdebug('queryPostLiveview datas:'.$datas);
        try {
            $baseuri='https://rest.'.$_regionBlink.'.immedia-semi.com';
            $client = new GuzzleHttp\Client(['verify' => false,'base_uri' =>  $baseuri]);
            $r = $client->request('POST',$baseuri.'/'.$url,  [
                //['http_errors' => false],
                ['timeout' => 1],
                'headers' => [
                    'TOKEN_AUTH'=> ''.$_tokenBlink,
                    'User-Agent' =>  self::BLINK_DEFAULT_USER_AGENT
                ],
                'json' => json_decode($datas)
            ]);
            return json_decode($r->getBody());
        }  catch (Exception $e) {
            self::logdebug('ERROR:'.print_r($e->getTraceAsString(), true));
            self::logdebug('ERROR:'.print_r($e->getMessage(), true));
        }
        return "";
    }*/
    public function getConfigHistory() {
        $cfgHisto=$this->getConfiguration('history_display_mode');
        if (!isset($cfgHisto) || $cfgHisto=='') {
            $this->setConfigHistory();
            $cfgHisto=$this->getConfiguration('history_display_mode');
        }
//        self::logdebug('getConfigHistory:'.print_r($cfgHisto, true));
        return $cfgHisto;
    }
    public function setConfigHistory(string $cfgHisto="mp4") {
        $this->setConfiguration('history_display_mode',$cfgHisto);
 //       self::logdebug('setConfigHistory:'.print_r($cfgHisto, true));
        $this->save();
    }
    public static function isConnected(string $email="") {
        $_tokenBlink=self::getConfigBlinkAccount($email,'token');
        //self::logdebug("isConnected($email) - token:".$_tokenBlink);
        $_accountBlink=self::getConfigBlinkAccount($email,'accountId');
        //self::logdebug("isConnected($email) - accountId:".$_accountBlink);
        $_regionBlink=self::getConfigBlinkAccount($email,'region');
        //self::logdebug("isConnected($email) - region:".$_regionBlink);
        $_verif=self::getConfigBlinkAccount($email,'verif');
        //self::logdebug("isConnected($email) - verif:".$_verif);
        if ($_tokenBlink!=="" && $_accountBlink!=="" && $_regionBlink!=="" && $_verif=="true") {
            return true;
        } else {
            //self::logdebug("isConnected($email) - FALSE");
        } ;
    }
    public static function getToken(string $email,bool $forceReinit=false )
    {
        $argu='FALSE';
        if ($forceReinit) {
            $argu='TRUE';
        }
        $updFlag=$argu;
        self::logdebug('getToken('.$email.','.$argu.') START');

        $date = date_create();
        $tstamp1=date_timestamp_get($date);
        $pwd=utils::decrypt(self::getConfigBlinkAccount($email,'pwd'));
        self::logdebug('getToken('.$email.','.$argu.')  password='.self::getConfigBlinkAccount($email,'pwd'));

        $pwd_prev=utils::decrypt(self::getConfigBlinkAccount($email,'pwd_prev'));
        $email_prev=self::getConfigBlinkAccount($email,'account_prev');

          
        if (!$forceReinit) {
            $forceReinit=($email!==$email_prev || $pwd!==$pwd_prev);
            if (!$forceReinit) {
                $updFlag='FALSE';
            }
        }
        self::logdebug('getToken('.$email.','.$argu.') '.$updFlag);

        /* Test de validité du token deja existant */
        $need_new_token=false;
        $_tokenBlink=self::getConfigBlinkAccount($email,'token');
        $_regionBlink=self::getConfigBlinkAccount($email,'region');
        $_accountBlink=self::getConfigBlinkAccount($email,'accountId');

        if (!$forceReinit) {
            // Check if a new token is required
            //TODO : don't check if pin code verification is required
            if (!$_tokenBlink=="" && !$_accountBlink=="" && !$_regionBlink=="") {
               /* $url='/api/v3/accounts/'.$_accountBlink.'/homescreen';
                try {
                    self::logDebugBlinkAPIRequest("CALL[queryToken] -->");
                    $jsonrep=self::queryGet($url);
                }
                catch (TransferException $e) {
                    self::logdebug('ERROR:'.print_r($e->getTraceAsString(), true));
                    $need_new_token=true;
                }*/
                $reponseHomescreen=self::getHomescreenData("getToken",$email);
                if ($reponseHomescreen['message']) {
                    $need_new_token=true;
                }
            } else {
                $need_new_token=true;
            }
            if (!$need_new_token) {
                //self::logdebug('blink_camera->getToken() Reuse existing token');
                $date = date_create();
                $tstamp2=date_timestamp_get($date);
                //self::logdebug('getToken()-1 END : '.($tstamp2-$tstamp1).' ms');
                return true;
            }
        } else {
            self::setConfigBlinkAccount($email,'token','');
            self::setConfigBlinkAccount($email,'region','');
            self::setConfigBlinkAccount($email,'accountId','');

            $_tokenBlink='';
            $_accountBlink='';
            $_regionBlink='';
        }
        if ($_tokenBlink=="BAD_TOKEN") {
            $date = date_create();
            $tstamp2=date_timestamp_get($date);
            //self::logdebug('getToken()-1bis END : '.($tstamp2-$tstamp1).' ms');
            return false;
        }
        $_tokenBlink=self::getConfigBlinkAccount($email,'token');
        $_regionBlink=self::getConfigBlinkAccount($email,'region');
        $_accountBlink=self::getConfigBlinkAccount($email,'accountId');
        if ($_tokenBlink=="" && $_accountBlink=="" && $_regionBlink=="") {
            
            self::logdebug('getToken('.$argu.') '.$updFlag. ' : Nouveau TOKEN');
            $_regionBlink=self::getConfigBlinkAccount($email,'region');
            $_accountBlink=self::getConfigBlinkAccount($email,'accountId');
            self::setConfigBlinkAccount($email,'account_prev',$email);      
            self::setConfigBlinkAccount($email,'pwd_prev',utils::encrypt($pwd)); 
            $_verifBlink=self::getConfigBlinkAccount($email,'verif');

            $notification_key=self::getConfigBlinkAccount($email,'notification_key');
            $unique_id=self::getConfigBlinkAccount($email,'uniqId');
            $_verifBlink=self::getConfigBlinkAccount($email,'verif');
            if ($_verifBlink=="true") {
                $reauthArg=",\"reauth\":\"true\"";
            }
            $data = "{\"email\" : \"".$email."\",\"password\": \"".$pwd."\",\"notification_key\" : \"".$notification_key."\",\"unique_id\":\"".$unique_id."\",\"device_identifier\":\"".self::BLINK_DEVICE_IDENTIFIER."\",\"client_name\":\"".self::BLINK_CLIENT_NAME."\"".$reauthArg."}";
            try {
                $jsonrep=self::queryPostLogin(self::BLINK_URL_LOGIN,$data,$email);
            } catch (TransferException $e) {
                if ($e->hasResponse()===true) {
                    $response=$e->getResponse();
                    $code=$response->getStatusCode();
                    if ($code===401) {
                        self::setConfigBlinkAccount($email,'token','BAD_TOKEN');
                        self::setConfigBlinkAccount($email,'verif','false');
            
                        self::logdebug('Invalid credentials used for Blink Camera.');
                        //self::logdebug(print_r($response,true));

                        $date = date_create();
                        $tstamp2=date_timestamp_get($date);
                        //self::logdebug('getToken()-2 END : '.($tstamp2-$tstamp1).' ms');
                        return false;
                    }
                }
                self::logdebug('An error occured during Blink Cloud call: /login - ERROR:'.print_r($e->getMessage(), true));
                //$date = date_create();
                //$tstamp2=date_timestamp_get($date);
                //self::logdebug('getToken()-3 END : '.($tstamp2-$tstamp1).' ms');
                return false;
            }
            $_tokenBlink=$jsonrep['auth']['token'];
            $_accountBlink=$jsonrep['account']['account_id'];
            $_regionBlink=$jsonrep['account']['tier'];
            $_clientIdBlink=$jsonrep['account']['client_id'];
            if ($_verifBlink=="false") {
                self::loginfo("Verification required with email code");
            }
            self::setConfigBlinkAccount($email,'token',$_tokenBlink);
            self::setConfigBlinkAccount($email,'accountId',$_accountBlink);
            self::setConfigBlinkAccount($email,'region',$_regionBlink);
            self::setConfigBlinkAccount($email,'client',$_clientIdBlink);
            //$date = date_create();
            //$tstamp2=date_timestamp_get($date);
            //self::logdebug('getToken()-4 END : '.($tstamp2-$tstamp1).' ms');
        }
        return true;
    }


    /*public static function reformatConfigDatas(array $jsonin, string $region, string $account)
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
    }*/
    public static function getNoEventImg() {
        return self::NO_EVENT_IMG; 
    }
    public static function reformatConfigDatas(array $jsonin,string $email)
    {

        $account=self::getConfigBlinkAccount($email,'accountId');
        $region=self::getConfigBlinkAccount($email,'region');
        $jsonstr= "{\"email\":\"".$email."\",\"region\":\"".$region."\",\"account\":\"".$account."\",\"networks\":[";
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
    public function getAccountConfigDatas($force_json_string=false,$forceReinitToken=false) {
        $emails=self::getConfigBlinkAccountsList();
        self::logdebug('getAccountConfigDatas() emails: '.print_r($emails,true));
        $reto=array();
        $notFirst=false;
        foreach($emails as $email) {
            if ($email && $email!=='') {
                if ($notFirst) {
                    //$reto=$reto.",";
                }
                self::logdebug('getAccountConfigDatas() for email: '.$email);
                //if (self::isConnected($email)) {
                    $datas=self::getHomescreenData("getAccountConfigDatas",$email);
                    if ($datas==null) 
                        $datas=[];
                    self::logdebug('getAccountConfigDatas() avant reformat '.print_r($datas,true));
                    $datas=self::reformatConfigDatas($datas,$email);
                    self::logdebug('getAccountConfigDatas() after reformat '.print_r($datas,true));
                    $reto[]=json_decode($datas,true);
                //} else {
                    //$reto+='{"message":"{{Impossible de se connecter au compte Blink. Vérifiez vos identifiants et mots de passe. Recharger la page ensuite.}}"}';
                //}
                $notFirst=true;
            }
        }
        //$reto=$reto."]";
        self::logdebug('getAccountConfigDatas() reto '.print_r($reto,true));
        return $force_json_string ? json_encode($reto,true) : array("emails"=>$reto);
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
        return self::getDateJeedomTimezone(gmdate($blinkDateFormat,$timestamp));
    }

    public static function getDateJeedomTimezone(string $date="")
    {
        $dtim = date_create_from_format(self::FORMAT_DATETIME, $date);
        // Manage negative timezone
        // https://github.com/d9-197/blink_camera/issues/13
        if (getTZoffsetMin()<0) {
            $dtim=date_sub($dtim, new DateInterval("PT".abs(getTZoffsetMin())."M"));  
        } else {
            $dtim=date_add($dtim, new DateInterval("PT".abs(getTZoffsetMin())."M"));  
        }
        return date_format($dtim, self::FORMAT_DATETIME_OUT);
    }
    public static function getDatetimeLocaleJeedom($valeur, $_format = self::FORMAT_DATETIME_OUT)
    {
            if (config::byKey('language', 'core', 'fr_FR')==='fr_FR'){
                $dtim = date_create_from_format($_format, $valeur);
                return date_format($dtim, self::FORMAT_DATETIME_OUT_FR);
            }
            return $valeur;
    }
    public static function getDateLocaleJeedom($valeur, $_format = self::FORMAT_DATE_OUT)
    {
            if (config::byKey('language', 'core', 'fr_FR')==='fr_FR'){
                $dtim = date_create_from_format($_format, $valeur);
                return date_format($dtim, self::FORMAT_DATE_OUT_FR);
            }
            return $valeur;
    }


    public static function getMedia($urlMedia, $equipement_id, $filename="default",$format="mp4")
    {
        $cam = self::byId($equipement_id);
        if ($cam->getConfiguration('storage')=='local') {
            return self::getMediaLocal($urlMedia,$equipement_id);
        } else {
            return self::getMediaForce($urlMedia, $equipement_id, $filename,$format,false);
        }
    }
    private static function getMediaForce($urlMedia, $equipement_id, $filename="default",$format="mp4",$overwrite=false)
    {
        $cam = self::byId($equipement_id);
        $email=$cam->getConfiguration("email");

        //self::logdebug('blink_camera->getMediaForce() url : '.$urlMedia);
        if (!empty($urlMedia)) {
                $_tokenBlink=self::getConfigBlinkAccount($email,'token');
                $_accountBlink=self::getConfigBlinkAccount($email,'accountId');
                $_regionBlink=self::getConfigBlinkAccount($email,'region');
                $filenameTab = explode('/', $urlMedia);
               // self::logdebug("blink_camera->getMediaForce() split : ".print_r($filenameTab,true));
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
            if (($filename=="last" || !file_exists($folderBase.$filename) || $overwrite) && self::isConnected($email)) {
                //self::logdebug("blink_camera->getMediaForce() url : $urlMedia - path : $filename");
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
                            self::logDebugBlinkAPIRequest("CALL[getMediaForce] -->");
                            self::queryGetMedia($urlMedia,$folderBase.$filename,$email);
                            if (file_exists($folderBase.$filename)) {
                                chmod($folderBase.$filename, 0775);
                            }
                        } 
                        catch (TransferException $e) {
                            self::logdebug('An error occured during Blink Cloud call: '.$urlMedia. ' - ERROR:'.print_r($e->getMessage(), true));
                            self::deleteMedia($folderBase.$filename);
                            self::logdebug("blink_camera->getMediaForce() url : $urlMedia - path : error.png 1");
                            return self::ERROR_IMG;
                        }
                    }
                } else {
                    self::logdebug("blink_camera->getMediaForce() url : $urlMedia - path : error.png 2");
                    return self::ERROR_IMG;
                }
            }
                //self::logdebug("blink_camera->getMediaForce() url : $urlMedia - path : $filename");
                return '/plugins/blink_camera/medias/'.$equipement_id.'/'.$filename;
        }
        self::logdebug("blink_camera->getMediaForce() url : $urlMedia - path : error.png 3");
        return self::ERROR_IMG;
    }

    public static function getHomescreenData($callOrig="",$email)
    {
        $jsonrep=json_decode('{"message":"error"}',true);
        //if (self::isConnected($email)) {
            $_accountBlink=self::getConfigBlinkAccount($email,'accountId');
            $url='/api/v4/accounts/'.$_accountBlink.'/homescreen';
            try {
                self::logDebugBlinkAPIRequest("CALL[getHomescreenData from ".$callOrig." (email=".$email.")] -->");
                $jsonrep=self::queryGet($url,$email);
                #$folderJson=__DIR__.'/../../medias/getHomescreenData.json';
                #file_put_contents($folderJson,json_encode($jsonrep));
            }
            catch (TransferException $e) {
                $errorTxt='ERROR: getHomescreenData - '.print_r($e->getMessage(), true);
                    self::logwarn($errorTxt);
                    $jsonrep=json_decode('{"message":"'.$errorTxt.'"}',true);

            }
            //self::logdebug('getHomescreenData :\n'.print_r($jsonrep,true));
            return $jsonrep;
        //}
    }
        
    /*     * *********************Méthodes d'instance************************* */
    public function getBlinkDeviceType() {
        $valeur = $this->getConfiguration("camera_type");
        $email = $this->getConfiguration("email");
        self::logdebug('getBlinkDeviceType '.$this->getId().' TYPE DEVICE dans configuration='.$valeur);
        if ($valeur=="" && $this->isConfigured()&& self::isConnected($email)) {
            $datas=self::getHomescreenData("getBlinkDeviceType",$email);
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
            self::logdebug('getBlinkDeviceType '.$this->getId().' NEW TYPE DEVICE='.$valeur);
            if ($valeur!=="") {
                $this->setConfiguration("camera_type",$valeur);
                $this->save();
            }
        }
		return $valeur;
    }

    public function getBlinkHumanDeviceType() {
        $type=$this->getBlinkDeviceType();
        if (__($type, __FILE__)==$type) {
            return __('type_name_missing', __FILE__).' : '.$type;
        } else {
            return __($type, __FILE__);
        }
    }
    private static function getMediaLocal($clip_id_req="",$equipement_id=null) {
        $cam = self::byId($equipement_id);
        $email=$cam->getConfiguration("email");
        self::logdebug('getMediaLocal Call : '.$cam->getId().' '.$cam->getName());
        $_accountBlink=self::getConfigBlinkAccount($email,'accountId');

        $netId=$cam->getConfiguration('network_id');
        $syncId=$cam->getConfiguration('sync_id');
        $lastManifest=$cam->getConfiguration('manifest');
        $fileExists=blink_camera::searchForFile(dirname(__FILE__) . '/../../medias/'.$equipement_id.'/'.$clip_id_req.'*.*');
        if ($fileExists!==false)
        {
            self::logdebug('getMediaLocal FILE ALREADY EXISTS FOR CLIP '.$fileExists);
            return $fileExists;
        }
        if (!$syncId =="") {
self::logdebug('getMediaLocal PHASE 1 - syncId=: '.$syncId);
            if (!isset($lastManifest) || $lastManifest=='') {
                $cam->requestNewManifest($_accountBlink,$netId,$syncId);
            }
            $lastManifest=$cam->getConfiguration('manifest');
            if (isset($lastManifest) && $lastManifest!=='') {
//$folderJson=__DIR__.'/../../medias/'.$cam->getId().'-localStorage_ph1.json';
//file_put_contents($folderJson,json_encode($jsonrep));
//jeedomUtils.sleep(1);
self::logdebug('getMediaLocal PHASE 1 - syncId=: '.$syncId.' - result: '.print_r($lastManifest,true));
                $url_manifest='/api/v1/accounts/'.$_accountBlink.'/networks/'.$netId.'/sync_modules/'.$syncId.'/local_storage/manifest';
                $url_manifest_req=$url_manifest.'/request';
                $url=$url_manifest_req.'/'.$lastManifest;
                try {
                    $flagToRelease=self::checkAndGetLock('getMediaLocal-Phase2-syncId-'.$syncId,10);
                    $jsonrep=self::queryGet($url,$email);
                    self::releaseLock($flagToRelease);
                } catch (TransferException $e) {
                    if (null !=$e->getMessage())
                    {
                        $jsonException=json_decode($e->getMessage());
                        if (isset($jsonException['code']) && $jsonException['code']==2102) {
                            self::logdebug('MANIFEST STALE (syncId: '.$syncId.'): '.$lastManifest);
                        } else if (isset($jsonException['code']) && $jsonException['code']==1700) {
                            self::logdebug('MANIFEST RETRIEVAL ERROR (syncId: '.$syncId.'): '.$lastManifest);
                        } else {
                            self::logdebug('An error occured during GET MANIFEST (syncId: '.$syncId.'): '.$lastManifest. ' - ERROR:'.print_r($e->getMessage(), true));
                        }
                    }
                    self::releaseLock($flagToRelease);
                    $cam->requestNewManifest($_accountBlink,$netId,$syncId);
                    $lastManifest=$cam->getConfiguration('manifest');
                    $url=$url_manifest_req.'/'.$lastManifest;
                    $flagToRelease=self::checkAndGetLock('getMediaLocal-Phase2-syncId-'.$syncId,10);
                    $jsonrep=self::queryGet($url,$email);
                    self::releaseLock($flagToRelease);
                }
                if (isset($jsonrep)) {
//$folderJson=__DIR__.'/../../medias/'.$cam->getId().'-localStorage_ph2.json';
//file_put_contents($folderJson,json_encode($jsonrep));
self::logdebug('getMediaLocal PHASE 2 syncId=: '.$syncId.' - result: '.print_r($jsonrep,true));
                    $manifest_id=$jsonrep['manifest_id'];
                    if (isset($manifest_id)) {
                        foreach ($jsonrep['clips'] as $clips) {
                            $clip_id=$clips['id'];
                            self::logdebug('getMediaLocal PHASE 3 - syncId=: '.$syncId.' - clip_id_req : '.$clip_id_req.' VERSUS clip_id : '.$clip_id);
                            if ($clip_id_req=="" || $clip_id_req==$clip_id) {
                                $camera_name=$clips['camera_name'];
                                $clip_date=$clips['created_at'];
                                $filename=$clip_id.'-'.self::getDateJeedomTimezone($clip_date);
                                $cameraApiName=$cam->getConfiguration('camera_name');
                                self::logdebug('getMediaLocal PHASE 3 - syncId=: '.$syncId.' - clip_id : '.$clip_id.' - camera_name : '.strtolower($camera_name).' ('.self::cleanSpecialCharacters(str_replace(" ","",strtolower($cameraApiName)),'').') - created_at : ' .$clip_date);
                                if (strtolower($camera_name)===self::cleanSpecialCharacters(str_replace(" ","",strtolower($cameraApiName)),'')) {
                                    $url_media=$url_manifest.'/'.$manifest_id.'/clip/request/'.$clip_id;
                                    self::logdebug('getMediaLocal PHASE 3 - syncId=: '.$syncId.' - clip_id : '.$clip_id.' will be downloaded');
                                    try {
                                        $flagToRelease=self::checkAndGetLock('getMediaLocal-Phase3-syncId-'.$syncId,100);
                                        $jsonrep=self::queryPost($url_media,$email);
                                        self::releaseLock($flagToRelease);
                                    } catch (Exception $e) {
                                        if (null !=$e->getMessage())
                                        {
                                            $jsonException=json_decode($e->getMessage());
                                            self::releaseLock($flagToRelease);
                                            if (isset($jsonException['code']) && $jsonException['code']==307) {
                                                self::logdebug('System busy...');
                                                return "";
                                            } else {
                                                self::logdebug('An error occured during call API LOCAL STORAGE POST: '.$url_media. ' - ERROR:'.print_r($e->getMessage(), true));
                                                self::logdebug("blink_camera->getMediaLocal() : error.png 1");
                                                return self::ERROR_IMG;
                                            }
                                        }      
                                    }
                                    self::logdebug('getMediaLocal PHASE 3 - syncId=: '.$syncId.' - result : '.$jsonrep);
//$folderJson=__DIR__.'/../../medias/'.$cam->getId().'-localStorage_ph3.json';
//file_put_contents($folderJson,json_encode($jsonrep));
                                    $flagToRelease=self::checkAndGetLock('getMediaLocal-getMediaForce-syncId-'.$syncId.'-clip_id-'.$clip_id,10);
                                    $resultMedia= self::getMediaForce($url_media, $cam->getId(), $filename,'mp4',false);
                                    self::releaseLock($flagToRelease);
                                    return $resultMedia;
                                }
                            }
                        }
                        self::logdebug('getMediaLocal clip_id not found in clips list !');
                    } else {
                        self::logdebug('getMediaLocal pas de manifest !');
                    }
                } else {
                    self::logdebug('getMediaLocal pas de réponse de API manifest !');
                }
            } else {
                self::logdebug('getMediaLocal pas de réponse de API manifest REQUEST !');
            }
        } else {
            self::logdebug('getMediaLocal syncID not found !');
        }
        self::logdebug("blink_camera->getMediaLocal() : error.png 2");
        return self::ERROR_IMG;
    }
    private static function checkAndGetLock($ident='all', $attente_maxi=self::ATTENTE_MAXI_DEFAUT) {
        $previousCaller=config::byKey('api_last_call_caller','blink_camera');
        $newCaller=$ident.'-'.self::generateRandomString(10);
        //self::logdebug('checkAndGetLock('.$newCaller.') START');
        $idx=1;
        while (isset ($previousCaller) && $previousCaller <> $newCaller && $previousCaller <> '' && $idx <= $attente_maxi) {
            if ($idx==1) {
                self::logdebug('checkAndGetLock('.$newCaller.') Debut attente de '.$previousCaller);
            } else {
                //self::logdebug('checkAndGetLock('.$newCaller.') Suite attente de '.$previousCaller.' ('.($idx-1).'s)');
            }
            sleep(1);
            $previousCaller=config::byKey('api_last_call_caller','blink_camera');
            $idx++;
        }
        if ($idx>=2) {
            self::logdebug('checkAndGetLock('.$newCaller.') Fin attente de '.$previousCaller. ' ('.($idx-1).'s)');
        }
        config::save('api_last_call_caller', $newCaller,'blink_camera');

        //config::remove('api_last_call_caller','blink_camera');
        //self::logdebug('checkAndGetLock('.$newCaller.') END');
        return $newCaller;

    }
    private static function releaseLock($caller) {
        if (config::byKey('api_last_call_caller','blink_camera')!='local') {
            $previousCaller=config::byKey('api_last_call_caller','blink_camera');
            if ($previousCaller==$caller) {
                self::logdebug('releaseLock('.$caller.') DONE');
                config::remove('api_last_call_caller','blink_camera');
            }
        }
    }
    public function isConfigured()
    {
        $email = $this->getConfiguration("network_id");
        $network_id = $this->getConfiguration("network_id");
        $camera_id = $this->getConfiguration("camera_id");
        if ($email!=="" && $network_id!=="" && $camera_id!=="") {
            return true;
        }
        return false;
    }
 
	public function getCameraInfo() {
        $jsonrep=json_decode('{"message":erreur"}',true);
        $email=$this->getConfiguration('email');
        if (self::isConnected($email) && $this->isConfigured()) {
            $url='/network/'.$this->getConfiguration('network_id').'/camera/'.$this->getConfiguration('camera_id');
            try {
               self::logDebugBlinkAPIRequest("CALL[getCameraInfo] -->");
               $jsonrep=self::queryGet($url,$email);
               #$folderJson=__DIR__.'/../../medias/getCameraInfoOwl.json';
                #file_put_contents($folderJson,json_encode($jsonrep));
            } catch (TransferException $e) {
                self::logdebug('getCameraInfo (type device='.$this->getBlinkDeviceType().')- An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                return $jsonrep;
            }
            //self::logdebug('getCameraInfo  '.$url. ' - response:'.print_r($jsonrep, true));
            return $jsonrep;
        }
	}

	public function getCameraThumbnail($forceDownload=false) {
        self::logdebug('blink_camera->getCameraThumbnail() '.$this->getId().' START ' );
        $email=$this->getConfiguration('email');
		if ($this->getBlinkDeviceType()!=="owlZZ") {
	      	$lastThumbnailTime = $this->getConfiguration("last_camera_thumb_time");
	      	$newtime=time();
	      	if ($forceDownload || ($newtime-$lastThumbnailTime)>5*6) {
		        $datas=self::getHomescreenData("getCameraThumbnail",$email);
                
                //self::logdebug('getHomescreenData - responce: '.print_r($datas, true));
                
                $camera_id = $this->getConfiguration("camera_id");

                //self::logdebug('getCameraThumbnail (Camera id:'.$this->getId().')- refresh thumbnail URL- previous time: '.$lastThumbnailTime.' - new time:'.$newtime.' - path:'.$path);
	        	foreach ($datas['cameras'] as $device) {
                    if ("".$device['id']==="".$camera_id) {
                        $timestamp_thumb=$device['thumbnail'];
                        $pattern="/.*ts=([0-9]*).*/";
                        
                        if (preg_match($pattern, $timestamp_thumb, $matches)) {
                            $timestamp_thumb="-".self::timestampToBlinkDate($matches[1]);
                        } else {
                            $timestamp_thumb="";
                        }
                        $path=self::getMediaForce($device['thumbnail'].'.jpg', $this->getId(),"thumbnail".$timestamp_thumb,"jpg",false);
                    }
                }	  
                foreach ($datas['owls'] as $device) {
                    if ("".$device['id']==="".$camera_id) {
                        $timestamp_thumb=$device['thumbnail'];
                        $pattern="/.*ts=([0-9]*).*/";
                        
                        if (preg_match($pattern, $timestamp_thumb, $matches)) {
                            $timestamp_thumb="-".self::timestampToBlinkDate($matches[1]);
                        } else {
                            $timestamp_thumb="";
                        }
                        $path=self::getMediaForce($device['thumbnail'].'.jpg', $this->getId(),"thumbnail".$timestamp_thumb,"jpg",false);
                    }
                }
                foreach ($datas['doorbells'] as $device) {
                    if ("".$device['id']==="".$camera_id) {
                        $timestamp_thumb=$device['thumbnail'];
                        $pattern="/.*ts=([0-9]*).*/";
                        
                        if (preg_match($pattern, $timestamp_thumb, $matches)) {
                            $timestamp_thumb="-".self::timestampToBlinkDate($matches[1]);
                        } else {
                            $timestamp_thumb="";
                        }
                        $path=self::getMediaForce($device['thumbnail'].'.jpg', $this->getId(),"thumbnail".$timestamp_thumb,"jpg",false);
                    }
                }

           		$pathRandom=trim(network::getNetworkAccess(config::byKey('blink_base_url', 'blink_camera'), '', '', false), '/').str_replace(" ","%20",self::GET_RESOURCE.$path."&".$this->generateRandomString());
          		if (isset($path) && $path <> "") {
          		}
          		$this->setConfiguration("last_camera_thumb_time",$newtime);
          		$this->setConfiguration("camera_thumb_url",$pathRandom);
                $this->checkAndUpdateCmd('camera_thumb_url',$pathRandom);
                $this->checkAndUpdateCmd('camera_thumb_path',$path);
                $path=$pathRandom;

			} else {
          		$path= $this->getConfiguration("camera_thumb_url");
	      		//self::logdebug('getCameraThumbnail (Camera id:'.$this->getId().')- not need to refresh thumbnail URL- previous time: '.$lastThumbnailTime.' - new time:'.$newtime.' - path:'.$path);
        	}
		}
        self::logdebug('blink_camera->getCameraThumbnail() '.$this->getId().' END '.$path );
		return $path;
	}

    public function getVideoList(int $page=1)
    {
        if ($this->getConfiguration('storage')=='local') {

            $result=$this->getVideoListLocal($page);
        } else {
            $result=$this->getVideoListCloud($page);
        }
        //$folderJson=__DIR__.'/../../medias/'.$this->getId().'/getVideoList_'.$this->getConfiguration('storage').'.json';
        //file_put_contents($folderJson,$result);
        return json_decode($result,true);        
    }
    public function getVideoListCloud(int $page=1)
    {
        $network_id = $this->getConfiguration("network_id");
        $camera_id = $this->getConfiguration("camera_id");
        $camera_name = $this->getConfiguration("camera_name");
        $email = $this->getConfiguration("email");
        $jsonstr="erreur_cloud";
        if (self::isConnected($email) && $this->isConfigured()) {
            $_tokenBlink=self::getConfigBlinkAccount($email,'token');

            $_accountBlink=self::getConfigBlinkAccount($email,'accountId');
            $_regionBlink=self::getConfigBlinkAccount($email,'region');
            $url='/api/v2/accounts/'.$_accountBlink.'/media/changed?since=2021-04-19T00:00:00+0000&page='.$page;
            
            try {
                self::logDebugBlinkAPIRequest("CALL[getVideoListCloud] -->");
//                self::checkAndGetLock('net-'.$network_id,2);
                $jsonrep=self::queryGet($url,$email);

                if (isset($jsonrep)) {
                    $jsonstr =self::reformatVideoDatas($jsonrep);
//                    $folderJson=__DIR__.'/../../medias/'.$this->getId().'/getlistvideocloud_result.json';
//                    file_put_contents($folderJson,json_encode($jsonstr));

                }
            } catch (TransferException $e) {
                self::logdebug('An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                return $jsonstr;
            }
            return $jsonstr;
        }
	}
    public function getVideoListLocal($page)
    { 
        $network_id = $this->getConfiguration("network_id");
        $camera_id = $this->getConfiguration("camera_id");
        $email = $this->getConfiguration("email");
        $result="erreur_local";
        if (self::isConnected($email) && $this->isConfigured()) {
            $_accountBlink=self::getConfigBlinkAccount($email,'accountId');
            $_regionBlink=self::getConfigBlinkAccount($email,'region');
            $syncId=$this->getConfiguration('sync_id');
            $lastManifest=$this->getConfiguration('manifest');
            $cameraApiName=$this->getConfiguration('camera_name');

            if (!$syncId =="") {
                self::logdebug('getVideoListLocal '.$this->getName().' syncId=: '.$syncId .' - lastManifest:'.$lastManifest);
                //if (!isset($lastManifest) || $lastManifest=='') {
                $lastRequestTime=$this->getConfiguration('manifest_timestamp');
                if ((date_timestamp_get(date_create())-$lastRequestTime) > 10) {
                    $this->requestNewManifest($_accountBlink,$network_id,$syncId);
                }
                //}
                $lastManifest=$this->getConfiguration('manifest');
                if (isset($lastManifest) && $lastManifest!=='') {
    jeedomUtils.sleep(1);
    self::logdebug('getVideoListLocal '.$this->getName().' ('.$cameraApiName.') Phase 1 : '.print_r($lastManifest,true));
                    $url_manifest='/api/v1/accounts/'.$_accountBlink.'/networks/'.$network_id.'/sync_modules/'.$syncId.'/local_storage/manifest';
                    $url_manifest_req=$url_manifest.'/request';
                    $url=$url_manifest_req.'/'.$lastManifest;
                    try {
                        $jsonrep=self::queryGet($url);
                    } catch (TransferException $e) {
                        if (null !=$e->getMessage())
                        {
                            $jsonException=json_decode($e->getMessage());
                            if (isset($jsonException['code']) && $jsonException['code']==2102) {
                                self::logdebug('MANIFEST STALE (syncId: '.$syncId.'): '.$lastManifest);
                                $this->requestNewManifest($_accountBlink,$network_id,$syncId);
                                $lastManifest=$this->getConfiguration('manifest');
                            } else if (isset($jsonException['code']) && $jsonException['code']==1700) {
                                self::logdebug('MANIFEST RETRIEVAL ERROR (syncId: '.$syncId.'): '.$lastManifest);
                            } else {
                                self::logdebug('An error occured during GET MANIFEST (syncId: '.$syncId.'): '.$lastManifest. ' - ERROR:'.print_r($e->getMessage(), true));
                            }
                        }             
                        $url=$url_manifest_req.'/'.$lastManifest;
                        try {
                            jeedomUtils.sleep(5);
                            $jsonrep=self::queryGet($url);
                        } catch (TransferException $e) {
                            self::logdebug('An error occured during RETRY OF GET MANIFEST (syncId: '.$syncId.'): '.$lastManifest. ' - ERROR:'.print_r($e->getMessage(), true));
                            unset($jsonrep);
                        }
                    }
                    if (isset($jsonrep)) {
    //$folderJson=__DIR__.'/../../medias/'.$this->getId().'/getlistvideolocal_ph2.json';
    //file_put_contents($folderJson,json_encode($jsonrep));
                        self::logdebug('getVideoListLocal '.$this->getName().' ('.$cameraApiName.') ('.self::cleanSpecialCharacters(str_replace(" ","",strtolower($cameraApiName)),'').') Phase 2 : '.print_r($jsonrep,true));
                        $manifest_id=$jsonrep['manifest_id'];
                        if (isset($manifest_id)) {
                            $result= array();
                            $idx=0;
                            foreach ($jsonrep['clips'] as $clip) {
                                if (strtolower($clip['camera_name'])===self::cleanSpecialCharacters(str_replace(" ","",strtolower($cameraApiName)),'')) {
                                    $clip['media']=$clip['id'];
                                    $clip['thumbnail']=$clip['id'];
                                    $clip['deleted']=(bool) 0;
                                    $clip['source']="-";
                                    $clip['device_id']=$this->getConfiguration('camera_id');
                                    $clip['device_name']=$clip['camera_name'];
                                    $result[$idx]=$clip;
                                    $idx++;
                                }
                            }
                            if ($idx>0) {
                                //$folderJson=__DIR__.'/../../medias/'.$this->getId().'/getlistvideolocal_result.json';
                                //file_put_contents($folderJson,json_encode($result));
                                self::logdebug('getVideoListLocal '.$this->getName().' ('.$cameraApiName.') result  : '.print_r($result,true));
                                return json_encode($result);
                            } else {
                                $result="no video";
                            }
                            
                        } else {
                            self::logdebug('getVideoListLocal pas de manifest !');
                        }
                    } else {
                        self::logdebug('getVideoListLocal pas de réponse de API manifest !');
                    }
                } else {
                    self::logdebug('getVideoListLocal pas de réponse de API manifest REQUEST !');
                }
            } else {
                self::logdebug('getVideoListLocal syncID not found !');
            }
        }
        return json_encode($result);
    }

    function requestNewManifest($_accountBlink,$network_id,$syncId) {
           $email = $this->getConfiguration("email");
            self::logdebug('REQUEST NEW MANISFEST for syncId: '.$syncId);
            $flagToRelease=self::checkAndGetLock('requestNewManifest-syncId-'.$syncId);
            $url_manifest='/api/v1/accounts/'.$_accountBlink.'/networks/'.$network_id.'/sync_modules/'.$syncId.'/local_storage/manifest';
            $url_manifest_req=$url_manifest.'/request';
            try {
                $jsonReqManisfest=self::queryPost($url_manifest_req,$email);
                //$folderJson=__DIR__.'/../../medias/'.$this->getId().'/requestNewManifest_ph1.json';
                //file_put_contents($folderJson,json_encode($jsonReqManisfest));
                $this->setConfiguration('manifest',$jsonReqManisfest['id']);
                $this->setConfiguration('manifest_timestamp',date_timestamp_get(date_create()));
                self::logdebug('FOUND NEW MANISFEST for syncId: '.$syncId.' New manisfest id: '.$jsonReqManisfest['id']);
                self::propagateManifest($syncId,$jsonReqManisfest['id']);
                self::releaseLock($flagToRelease);
            } catch (Exception $e) {
                self::logdebug('An error occured during Blink Cloud call POST : '.$url_manifest_req. ' - ERROR:'.print_r($e->getMessage(), true));
                if (method_exists($e,'getResponse')) {
                    $response = $e->getResponse();
                    $responseJson = json_decode($response->getBody()->getContents(),true);
                    if($responseJson['code']===307) {
        //                        sleep(5);
        //                        self::releaseLock('getVideoListLocal-syncId-'.$syncId);
        //                        self::checkAndGetLock('getVideoListLocal-syncId-'.$syncId,10);
        //                        $jsonReqManisfest=self::queryPost($url_manifest_req);
                    };
                }
                self::releaseLock($flagToRelease);
            }
    }
    public static function propagateManifest($syncId,$newManisfetId) {
        $eqLogics = self::byType('blink_camera', true);
        foreach ($eqLogics as $cam) {
            if ($syncId==$cam->getConfiguration('sync_id')) {
                $cam->setConfiguration('manifest',$newManisfetId);
                $cam->setConfiguration('manifest_timestamp',date_timestamp_get(date_create()));
                self::logdebug('NEW MANISFEST PROPAGATED TO: '.$cam->getName().' - '.$cam->getId().' - New manisfest id: '.$newManisfetId);
            }
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
        $email = $this->getConfiguration("email");
        $jsonrep=json_decode('["message":"erreur"]');
        if (($type==="clip" || $type ==="thumbnail" ) &&self::isConnected($email) && $this->isConfigured()) {
            $_accountBlink=self::getConfigBlinkAccount($email,'accountId');
                    if ($typeDevice==='owl') {
                        // https://rest.prde.immedia-semi.com/api/v1/accounts/{{accountid}}/networks/194881/owls/3287/clip
                        $url='/api/v1/accounts/'.$_accountBlink.'/networks/'.$this->getConfiguration('network_id').'/owls/'.$this->getConfiguration('camera_id').'/'.$type;
                    } else if ($typeDevice==='doorbells')  {
                        // https://rest.prde.immedia-semi.com/api/v1/accounts/{{accountid}}/networks/194881/owls/3287/clip
                        $url='/api/v1/accounts/'.$_accountBlink.'/networks/'.$this->getConfiguration('network_id').'/doorbells/'.$this->getConfiguration('camera_id').'/'.$type;
                    } else  {
                        $url='/network/'.$this->getConfiguration('network_id').'/'.$typeDevice.'/'.$this->getConfiguration('camera_id').'/'.$type;
                    }
                    self::logDebugBlinkAPIRequest("CALL[requestNewMedia]: --> ");
                try {
                    $jsonrep=self::queryPost($url,$email);
                } catch (TransferException $e) {
                    self::logdebug('An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                    $response = $e->getResponse();
                    $responseJson = json_decode($response->getBody()->getContents(),true);
                    self::logDebugBlinkResponse($responseJson['message']);
                    return false;
                }
            return $jsonrep;
        }
	}


    public function forceCleanup($download=false)
    {
        $email = $this->getConfiguration("email");
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
        if ($this->isConnected($email)) {
            $pageVide=0;
            $pageMax=100;
            $storage=$this->getConfiguration('storage');
            if ($storage=='local') {
                $pageMax=1;
            }
            for ($page=1;$page<=$pageMax;$page++) {
                $videosJson=$this->getVideoList($page);
                if (isset($videosJson)) {
                    self::logdebug( 'blink_camera->forceCleanup() list videos  : '. print_r($videosJson,true));            
                }
                $existVideoInPage=false;
                // Si en cherchant des videos on a rencontré 10 pages vides, on arrete de rechercher (perfo)
                if ($pageVide>=5) {
                    break;
                }
                foreach ($videosJson as $videoApi) {
                    $existVideoInPage=true;
                    //self::logdebug( 'blink_camera->forceCleanup() video dans page : '. $page);
                    break;
                }
                if ($existVideoInPage) {
                    //self::logdebug( 'blink_camera->forceCleanup() process videos of page : '. $page);            
                    $existVideoInPage=false;
                    foreach ($existingFilesOnJeedom as $file) {
                        if (($key = array_search($file, $fileOnCloudAndOnJeedom)) == false) {
                            if ($file!=="." && $file!=="..") {
                                $filename="";
                                foreach ($videosJson as $videoApi) {
//                                    self::logdebug( 'blink_camera->forceCleanup() videoApi : '. print_r($videoApi,true));            
                                    if ($storage==='local' || !$videoApi['deleted']) {
                                        $filename=$videoApi['id'].'-'.self::getDateJeedomTimezone($videoApi['created_at']).'.mp4';
                                        if (($key = array_search($filename, $fileToDownload)) == false) {
                                            $fileToDownload[$filename]=$videoApi['media'];
                                            $cptVideo++;
                                            if ($file === $filename && ($key = array_search($filename, $fileOnCloudAndOnJeedom)) == false) {
                                                $fileOnCloudAndOnJeedom[]=$filename;
  //self::logdebug( 'blink_camera->forceCleanup() fichier existant trouve sur le cloud : '. $filename);
                                            }
                                            $filename=$videoApi['id'].'-'.self::getDateJeedomTimezone($videoApi['created_at']).'.jpg';
                                            $fileCloudThumb[$filename]=$videoApi['thumbnail'];
                                            if ($file === $filename && ($key = array_search($filename, $fileOnCloudAndOnJeedom)) == false) {
                                                $fileOnCloudAndOnJeedom[]=$filename;
                    //self::logdebug( 'blink_camera->forceCleanup() fichier existant trouve sur le cloud : '. $filename);
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
            //self::logdebug( 'blink_camera->forceCleanup() Videos listed on cloud : '. count($fileToDownload));     
                   
            $cptVideo=0;

            // TELECHARGEMENT DES FICHIERS MANQUANTS
            $fileToDownload=array_unique($fileToDownload);
            arsort($fileToDownload);
            
            // Récupération des videos
            foreach ($fileToDownload as $filename => $urlMedia) {
                if ($nbMax>0 && $cptVideo>=$nbMax) {
                    break;
                } 
                if (($key = array_search($filename, $fileOnCloudAndOnJeedom)) == false) {
                    if ($download) { // Si demandé, on télécharge les vidéos disponibles
                        $path=$this->getMedia($urlMedia, $this->getId(), $filename);
                        //self::logdebug( 'blink_camera->forceCleanup() download file: '. $filename);
                        $filenameThumb=str_replace(".mp4",".jpg",$filename);
                        // Fix du 29 03 2022 : 
                        //$path=$this->getMedia($fileCloudThumb[$filenameThumb], $this->getId(), $filenameThumb);                        
                        $path=$this->getMedia($fileCloudThumb[$filenameThumb], $this->getId(), $filenameThumb,"jpg");                        
                        //self::logdebug( 'blink_camera->forceCleanup() download file: '. $filenameThumb);
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
                        if ($storage!=='local' && preg_match("#.*".self::PREFIX_THUMBNAIL."-.*\.jpg$#",strtolower($file))==false){
                            $fileToDelete[]=$file;
                        }
                    }
                }
            }
        }

        // Nettoyage des fichiers du dossier medias
        foreach ($fileToDelete as $file) {
            if ($file!=="." && $file!=="..") {
                //self::logdebug( 'blink_camera->forceCleanup() Delete file: '. $this->getMediaDir().'/'.$file);
                self::deleteMedia($this->getMediaDir().'/'.$file);
            }
        }

        $temp =$this->getLastEvent(false);
        //self::getMedia($temp['media'], $this->getId(), 'last','mp4');
        $info = $this->getCmd(null, 'source_last_event');
        if (is_object($info)) {
            $this->checkAndUpdateCmd('source_last_event', $temp['source']);
        }
		// récup thumbnail de la caméra
		$this->getCameraThumbnail(true);
    }

    public function getLastEvent($include_deleted=false)
    {
        self::logdebug('blink_camera->getLastEvent() '.$this->getName().' START');
        // on boucle sur les pages au cas ou les premières pages ne contiendraient que des event supprimés
        $storage=$this->getConfiguration('storage');
        $pageMax=50;
        if ($storage=='local') {
            $pageMax=1;
        }
        for ($page=1;$page<=$pageMax;$page++) {
            $jsonvideo=$this->getVideoList($page);
            foreach ($jsonvideo as $event) {
                if ($storage=='local' || $include_deleted || $event['deleted']===false) {
                    //self::logdebug('blink_camera->getLastEvent() '.$this->getName().' '.$event['created_at']);
                    if (!isset($last_event) || $last_event['created_at']<$event['created_at']) {
                        $last_event=$event;
                    }
                }
            }
            if (isset($last_event)) {
                self::logdebug('blink_camera->getLastEvent() '.$this->getName().' return an event:'.$last_event['created_at']);
                self::logdebug('blink_camera->getLastEvent() '.$this->getName().' return an event:'.$last_event['media']);
                self::getMedia($last_event['media'], $this->getId(), 'last','mp4');
                return $last_event;
            } else {
                return null;
            }
        }
        $jsonstr='[{"id":"error",deleted":false,"device_id":"xxxxx","device_name":"xxxx","media":"xxxxxxx","thumbnail":"/plugins/blink_camera/medias/x0.png","created_at":"2019-01-01T00:00:01+0000"}]';
        self::logdebug('blink_camera->getLastEvent() '.$this->getName().' END '.json_decode($jsonstr, true));
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
        self::logdebug('blink_camera->getLastEventDate() START');
        if ($this->isConfigured()) {
            $event = $this->getLastEvent(false);
            self::logdebug('blink_camera->getLastEventDate() POST getLastEvent');
            if (!isset($event)) {
                self::logdebug('blink_camera->getLastEventDate() '.$this->getId().' pas d\'event');
                /*
                $this->checkAndUpdateCmd('last_event', "-");
                $this->checkAndUpdateCmd('thumb_path',"-");
                $this->checkAndUpdateCmd('thumb_url',"-");
                $this->checkAndUpdateCmd('clip_path',"-");
                $this->checkAndUpdateCmd('clip_url',"-");
                */
            } else {
                self::logdebug('blink_camera->getLastEventDate() '.$this->getId().' event trouvé');
                $infoCmd=$this->getCmd(null, 'last_event');
                if (is_object($infoCmd) && isset($event)) {
                    $previous=$infoCmd->execCmd();
                    self::logdebug('blink_camera->getLastEventDate() '.$this->getId().' previous='.$previous);
                    $new=self::getDateJeedomTimezone($event['created_at']);
                    self::logdebug('blink_camera->getLastEventDate() '.$this->getId().' new='.$new);
                    $infoCmd=$this->getCmd(null, 'clip_path');
                    $clip_path=$infoCmd->execCmd();
                    if ((isset($new) && $new!="" && ($new>$previous || $ignorePrevious)) || $clip_path==blink_camera::ERROR_IMG) {
                        self::logdebug('New event detected:'.$new. ' (previous:'.$previous.')');
                        $this->checkAndUpdateCmd('last_event', $new);
                        $pathThumb=self::getMedia($event['thumbnail'],$this->getId(),$event['id'].'-'.self::getDateJeedomTimezone($event['created_at']),'jpg');
                        $this->checkAndUpdateCmd('thumb_path',$pathThumb);
                        $urlThumb=trim(network::getNetworkAccess(config::byKey('blink_base_url', 'blink_camera'), '', '', false), '/').str_replace(" ","%20",self::GET_RESOURCE.$pathThumb);
                        $this->checkAndUpdateCmd('thumb_url',$urlThumb);
                        $pathLastVideo=self::getMedia($event['media'],$this->getId(),$event['id'].'-'.self::getDateJeedomTimezone($event['created_at']));
                        if ($pathLastVideo!=='') {
                            $this->checkAndUpdateCmd('clip_path',$pathLastVideo);
                            $urlLastVideo=trim(network::getNetworkAccess(config::byKey('blink_base_url', 'blink_camera'), '', '', false), '/').str_replace(" ","%20",self::GET_RESOURCE.$pathLastVideo);
                            $this->checkAndUpdateCmd('clip_url',$urlLastVideo);
                        }
                    }
                }
                $info = $this->getCmd(null, 'source_last_event');
                if (is_object($info) && isset($event)) {
                    //self::logdebug('blink_camera->getLastEvent() init last event source '.print_r($event,true) );
                    $this->checkAndUpdateCmd('source_last_event', $event['source']);
                }
            }
            self::logdebug('blink_camera->getLastEventDate() '.$this->getId().' recalcul vignette START '.print_r($event,true) );
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
                $urlLine ='<video class="displayVideo vignette" '. $hauteurVignette.' controls data-src="#urlFile#" style="display:block;padding:5px;cursor:pointer"><source src="#urlFile#">Your browser does not support the video tag.</video>';
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
                    $urlFile=self::getNoEventImg();
                }
            }
            if (!isset($urlFile) || $urlFile ==="") {
                $urlFile=self::getNoEventImg();
            }
            if ($urlFile!==self::getNoEventImg()) {
                $urlFile=self::GET_RESOURCE.$urlFile;
            }
            $replace['#urlFile#']=$urlFile;
            self::logdebug('blink_camera->getLastEventDate() '.$this->getId().' recalcul vignette END '.print_r($replace,true) );
            $this->checkAndUpdateCmd('thumbnail',template_replace($replace, $urlLine));
        }
        self::logdebug('blink_camera->getLastEventDate() '.$this->getId().' END');
	}
	public function refreshCameraInfos($callOrig="") {
        $email=$this->getConfiguration('email');
		if ($this->isConfigured()&& $this->isConnected($email)) {
            $this->getCameraThumbnail();
            //$this->emptyCacheWidget();
            
            if ($this->getBlinkDeviceType()!=="owl" && $this->getBlinkDeviceType()!=="lotus") {
                $datas=$this->getCameraInfo();
                if (!$datas['message']) {
                   /* // MAJ Température 
                    $tempe=(float) $datas['camera_status']['temperature'];
                    self::logdebug('refreshCameraInfos() '.$this->getConfiguration('camera_id').' - temperature = '.print_r($tempe,true));
                    $blink_tempUnit=config::byKey('blink_tempUnit', 'blink_camera');
                    if ($blink_tempUnit==="C") {
                        $tempe =($tempe - 32) / 1.8;
                    }
                   
                    $this->checkAndUpdateCmd('temperature', $tempe);
                    $this->setConfiguration('camera_temperature',$tempe);
                    */
                    self::logdebug('refreshCameraInfos() '.$this->getConfiguration('camera_id').' - cameraInfo: = '.print_r($datas,true));
                    
                    $ac_power=(boolean) $datas['camera_status']['ac_power'];
                    // MAJ Power 
                    if (!$ac_power) {
                        $power=(float) $datas['camera_status']['battery_voltage'];
                        //$this->checkAndUpdateCmd('power', ($power/100));
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
                        $this->setConfiguration('noBatterieCheck', 0);
                    } else {
                        $this->checkAndUpdateCmd('battery', '');
                        //$this->checkAndUpdateCmd('power', '');
                        $this->setConfiguration('battery',100);
                        $this->batteryStatus(100);
                        $this->setConfiguration('noBatterieCheck', 1);
                    }
                    // MAJ WIFI
                    $wifi=(float) $datas['camera_status']['wifi_strength'];
                    $this->checkAndUpdateCmd('wifi_strength', $wifi);
                    $this->setConfiguration('camera_wifi',$wifi);
                }
            } else {
                $this->checkAndUpdateCmd('battery', 100);
                //$this->checkAndUpdateCmd('power', 100);    
                $this->setConfiguration('battery',100);
                $this->batteryStatus(100);
                $this->setConfiguration('noBatterieCheck', 1);
            }
            $datas=self::getHomescreenData("refreshCameraInfos - ".$callOrig,$email);
            if (!$datas['message']) {
                $this->setConfiguration('storage', 'cloud');
                foreach($datas['cameras'] as $camera) {
                     if ($camera['id']==$this->getConfiguration('camera_id')) {
                        self::logdebug('refreshCameraInfos() CAMERA '.$this->getConfiguration('camera_name').' '.$this->getConfiguration('camera_id').' - '.print_r($camera,true));
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
                        //self::logdebug('refreshCameraInfos() '.$this->getConfiguration('camera_id').' - temperature = '.print_r($tempe,true));
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
                    if ($camera['id']==$this->getConfiguration('camera_id')) {
                        self::logdebug('refreshCameraInfos() OWL '.$this->getConfiguration('camera_name').' '.$this->getConfiguration('camera_id').' - '.print_r($camera,true));
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
                        //$this->setConfiguration('camera_battery_status',$camera['battery']);
                        break;
                    }
                }
                foreach($datas['doorbells'] as $camera) {
                    if ($camera['id']==$this->getConfiguration('camera_id')) {
                        self::logdebug('refreshCameraInfos() DOORBELLS '.$this->getConfiguration('camera_name').' '.$this->getConfiguration('camera_id').' - '.print_r($camera,true));
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
                        self::logdebug('refreshCameraInfos() NETWORKS '.$this->getConfiguration('camera_name').' '.$this->getConfiguration('camera_id').' - '.print_r($network,true));

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
                        self::logdebug('refreshCameraInfos() SYNC_MODULES '.$this->getConfiguration('camera_name').' '.$this->getConfiguration('camera_id').' - '.print_r($syncMod,true));
                        self::logdebug('refreshCameraInfos: '.$this->getName().' sync module - local_storage_enabled='.$syncMod['local_storage_enabled'].' - local_storage_compatible='.$syncMod['local_storage_compatible'].' - local_storage_status='.$syncMod['local_storage_status']);
                        $this->setConfiguration('storage', 'cloud');
                        if ($syncMod['local_storage_enabled'] && $syncMod['local_storage_compatible'] && $syncMod['local_storage_status']==='active') {
                            $this->setConfiguration('storage', 'local');
                        }
                        $this->setConfiguration('sync_id',$syncMod['id']);
                        self::logdebug('refreshCameraInfos: sync_id='.$syncMod['id']);
                        break;
                    }
                }
                self::logdebug('refreshCameraInfos: storage='.$this->getConfiguration('storage'));
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
    public static function isModeEco() {
        return (boolean) config::byKey('mode_eco', 'blink_camera');
    }
    public function networkArm()
    {
        $email=$this->getConfiguration('email');
        if (self::isConnected($email) && $this->isConfigured()) {
                $url='/network/'.$this->getConfiguration('network_id').'/arm';
                try {
                    self::queryPost($url);
                    jeedomUtils.sleep(1);
                    $this->refreshCameraInfos("networkArm");
                    return true;
                } catch (TransferException $e) {
                    self::logdebug('An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                    $response = $e->getResponse();
                    $responseJson = json_decode($response->getBody()->getContents(),true);
                    self::logDebugBlinkResponse($responseJson['message']);
                    return false;
                }
        }
        return false;
    }
    public function networkDisarm()
    {
        $email=$this->getConfiguration('email');
        if (self::isConnected($email) && $this->isConfigured()) {
            $url='/network/'.$this->getConfiguration('network_id').'/disarm';
            try {
                self::queryPost($url);
                jeedomUtils.sleep(1);
                $this->refreshCameraInfos("networkDisarm");
                return true;
            } catch (TransferException $e) {
                self::logdebug('An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                $response = $e->getResponse();
                $responseJson = json_decode($response->getBody()->getContents(),true);
                self::logDebugBlinkResponse($responseJson['message']);
                return false;
            }
        }
        return false;
    }



    public function cameraArm()
    {
        $email=$this->getConfiguration('email');
        if (self::isConnected($email) && $this->isConfigured()) {
            $account_id=self::getConfigBlinkAccount($email,'token');
            $network_id=$this->getConfiguration('network_id');
            $camera_id=$this->getConfiguration('camera_id');
            $datas = "{\"enabled\":true}";
            if ($this->getBlinkDeviceType()=='owl') {
                $url="/api/v1/accounts/".$account_id."/networks/".$network_id."/owls/".$camera_id."/config";
            } else if ($this->getBlinkDeviceType()=='lotus') {
                $url="/api/v1/accounts/".$account_id."/networks/".$network_id."/doorbells/".$camera_id."/config";
            } else {
                $url="/network/".$network_id."/camera/".$camera_id."/enable";
                $datas = "{}";
            }
            try {
                self::queryPost($url,$datas);
                jeedomUtils.sleep(1);
                $this->refreshCameraInfos("cameraArm");
                return true;
            } catch (TransferException $e) {
                self::logdebug('An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                $response = $e->getResponse();
                $responseJson = json_decode($response->getBody()->getContents(),true);
                self::logDebugBlinkResponse($responseJson['message']);
                return false;
            }
        }
        return false;
    }

    public function cameraDisarm()
    {
        $email=$this->getConfiguration('email');
        if (self::isConnected($email) && $this->isConfigured()) {
            $account_id=self::getConfigBlinkAccount($email,'accountId');
            $network_id=$this->getConfiguration('network_id');
            $camera_id=$this->getConfiguration('camera_id');
            $datas = "{\"enabled\":false}";
            if ($this->getBlinkDeviceType()=='owl') {
                $url="/api/v1/accounts/".$account_id."/networks/".$network_id."/owls/".$camera_id."/config";
            } else if ($this->getBlinkDeviceType()=='lotus') {
                $url="/api/v1/accounts/".$account_id."/networks/".$network_id."/doorbells/".$camera_id."/config";
            } else {
                $url="/network/".$network_id."/camera/".$camera_id."/disable";
                $datas = "{}";
            }
            try {
                self::queryPost($url,$datas);
                jeedomUtils.sleep(1);
                $this->refreshCameraInfos("cameraDisarm");
                return true;
            } catch (TransferException $e) {
                self::logdebug('An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                $response = $e->getResponse();
                $responseJson = json_decode($response->getBody()->getContents(),true);
                self::logDebugBlinkResponse($responseJson['message']);
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
                //self::logdebug('delete: '.$commande);
                shell_exec($commande);
            } else {
                self::logdebug('Plugin blink camera try to delete file not in "medias" folder : '.$filepath);
            }
        }
    }
    
    public static function deleteMediaCloud($filepath,$idEquipment) {
        $filepath=realpath($filepath);
        $cam=self::byId($idEquipment);
        $email=$cam->getConfiguration('email');
        //self::logdebug('deleteMediaCloud(filepath='.$filepath.') START');
        // On controle que l'on soit bien dans le dossier de stockage des medias du plugin !                
        if (strpos($filepath, '/plugins/blink_camera/') !== false && strpos($filepath, '/medias/') !== false) {
            $mediaId=explode('-',basename($filepath))[0];
            if (isset($mediaId) && $mediaId!="" && self::isConnected($email)) {
                $_accountBlink=self::getConfigBlinkAccount($email,'accountId');
                $datas='{"media_list":['.$mediaId.']}';
                $url='/api/v1/accounts/'.$_accountBlink.'/media/delete';
                try {
                    $jsonrep=self::queryPost($url,$datas);
                    $infoCmd=$cam->getCmd(null, 'last_event');
                    if (is_object($infoCmd)) {
                        $previous=$infoCmd->execCmd();
                        $fichier=basename($filepath);
                        if (strpos($fichier, $previous) !== false) {
                            // Si on a supprimé le dernier event, on force le recalcul de la date de dernier event
                            $cam->getLastEventDate(true);
                        }
                    }
                    if ($jsonrep['code']==='711') {
                        return true;
                    } 
                    return false;
                } catch (TransferException $e) {
                    $response = $e->getResponse();
                    $responseJson = json_decode($response->getBody()->getContents(),true);
                    self::logDebugBlinkResponse($responseJson['message']);
                    self::logdebug('An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                    return false;
                }
            }
            return false;
        } else {
            self::logdebug('Plugin blink camera try to delete file in Blink cloud but not in "medias" folder');
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
        if (!(boolean)config::byKey('include_medias_in_backup', 'blink_camera')) {
            $pathToExclude[]="medias";
        }
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
        
                /* NETTOYAGE */
        $download_local = $this->getCmd(null, 'download_local');
        if (is_object($download_local)) {
            $download_local->remove();
        }
        $power = $this->getCmd(null, 'power');
        if (is_object($power)) {
            $power->remove();
        }

        /* COMMANDES COMMUNES A TOUS LES MODELES */
        $info = $this->getCmd(null, 'arm_status');
        if (!is_object($info)) {
            self::loginfo( 'Create new information : arm_status (network)');
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
        $info = $this->getCmd(null, 'last_event');
        if (!is_object($info)) {
            self::loginfo( 'Create new information : last_event');
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
            self::loginfo( 'Create new information : thumbnail');
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
            self::loginfo( 'Create new information : clip_path');
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
            self::loginfo( 'Create new information : thumb_path');
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
            self::loginfo( 'Create new information : camera_thumb_path');
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
            self::loginfo( 'Create new information : thumb_url');
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
            self::loginfo( 'Create new information : camera_thumb_url');
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
            self::loginfo( 'Create new information : clip_url');
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
        $refresh = $this->getCmd(null, 'refresh');
        if (!is_object($refresh)) {
            self::loginfo( 'Create new action : refresh');
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
            self::loginfo( 'Create new action : arm_network');
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
            self::loginfo( 'Create new action : disarm_network');
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

        $history = $this->getCmd(null, 'history');
        if (!is_object($history)) {
            self::loginfo( 'Create new action : history');
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
            self::loginfo( 'Create new action : new_clip');
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
            self::loginfo( 'Create new action : new_thumbnail');
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
            self::loginfo( 'Create new action : force_download');
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

        $arm_camera = $this->getCmd(null, 'arm_camera');
        if (!is_object($arm_camera)) {
            self::loginfo( 'Create new action : arm_camera');
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
            self::loginfo( 'Create new action : disarm_camera');
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
        $arm_status_camera = $this->getCmd(null, 'arm_status_camera');
        if (!is_object($arm_status_camera)) {
            self::loginfo( 'Create new information : arm_status_camera');
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
        $battery = $this->getCmd(null, 'battery');
        if (!is_object($battery)) {
            self::loginfo( 'Create new information : battery');
            $battery = new blink_cameraCmd();
            $battery->setName(__('Pile', __FILE__));
            $battery->setTemplate('dashboard', 'badge');
            $battery->setDisplay("showNameOndashboard", 1);
            $battery->setConfiguration('historizeRound',"2");
            $battery->setUnite('%');
            $battery->setIsVisible(0);
            $battery->setLogicalId('battery');
            $battery->setEqLogic_id($this->getId());
            $battery->setType('info');
            $battery->setSubType('numeric');
            $battery->setOrder(12);
            $battery->save();
        }


        /* COMMANDES NON DISPONIBLES SUR owl et lotus  */
        if ($typeDevice!="" and $typeDevice!="owl" and $typeDevice!="lotus") {

            $temperature = $this->getCmd(null, 'temperature');
            if (!is_object($temperature)) {
                self::loginfo( 'Create new information : temperature');
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

            $wifi_strength = $this->getCmd(null, 'wifi_strength');
            if (!is_object($wifi_strength)) {
                self::loginfo( 'Create new information : wifi_strength');
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
        } else {
            $cmdToHide = $this->getCmd(null, 'wifi_strength');
            if (is_object($cmdToHide)) {
                //$cmdToHide->remove();
                $cmdToHide->setIsVisible(0);
                $cmdToHide->save();
            }
            $cmdToHide = $this->getCmd(null, 'temperature');
            if (is_object($cmdToHide)) {
                //$cmdToHide->remove();
                $cmdToHide->setIsVisible(0);
                $cmdToHide->save();
            }
        }
        
         /* COMMANDES DISPONIBLES UNIQUEMENT SUR lotus (Doorbell) */
        if ($typeDevice!="" && $typeDevice=="lotus") {
            $info = $this->getCmd(null, 'source_last_event');
            if (!is_object($info)) {
                self::loginfo( 'Create new information : source_last_event');
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
        } else {
            $cmdToHide = $this->getCmd(null, 'source_last_event');
            if (is_object($cmdToHide)) {
                //$cmdToHide->remove();
                $cmdToHide->setIsVisible(0);
                $cmdToHide->save();
            }
        }

        /* construction des clés uniques pour les API Blink */
        $email=$this->getConfiguration('email');
        if ($email !=="") {
            self::setConfiguration($email,'pwd',utils::encrypt(config::byKey('param2')));
            $notification_key=self::getConfigBlinkAccount($email,'notification_key');
            $unique_id=self::getConfigBlinkAccount($email,'uniqId');;
            //$notification_key="";
            //$unique_id="";    
            if (!isset($notification_key) || $notification_key==="" || strlen($notification_key) <> 152) {
                $notification_key=self::genererIdAleatoire(152);
                self::setConfigBlinkAccount($email,'notification_key', $notification_key);
            }
            if (!isset($unique_id) || $unique_id==="" || strlen($unique_id) <> 16) {
                $unique_id=self::genererIdAleatoire(16);
                self::setConfigBlinkAccount($email,'uniqId', $unique_id);
            }
        }

    }

    public function preUpdate()
    {
    }

    public function postUpdate()
    {

    }

    public function preRemove()
    {
        self::deleteMedia($this->getMediaDir(),true);
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
    public function dumpConfiguration() {
        $values = array(
            'plugin' => 'blink_camera',
            'id' => $this->getId()
        );
        $sql = 'SELECT `id`, `configuration` FROM eqLogic where `eqtype_name` =:plugin and `id` = :id';
        $values = DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL);
        foreach ($values as $value) {
            self::logdebug($this->getName().' ('.$this->getID().') dumpConfiguration :');
            self::logdebug($value['configuration']);
        }
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
            $email=$bl_cam->getConfiguration('email');
            if ($bl_cam->isConnected($email) && $bl_cam->isConfigured()) {
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
                if ($bl_cam->isConfigured()) {
                $result=parent::toHtml($_version,$_options,$_cmdColor);
                
                $result.='<script> $(\'.cmd[data-cmd_id='.$this->getId().']\').off(\'click\').on(\'click\', function () {';
                $result.='$(\'#md_modal\').dialog({title: "Historique '.$bl_cam->getName().'"});';
#               $result.='$(\'#md_modal\').load(\'index.php?v=d&plugin=blink_camera&modal=blink_camera.history&id='.$bl_cam->getId().'&mode='.$bl_cam->getConfigHistory().'\').dialog(\'open\');});';
                $result.='$(\'#md_modal\').load(\'index.php?v=d&plugin=blink_camera&modal=blink_camera.history&id='.$bl_cam->getId().'\').dialog(\'open\');});';
                $result.="</script>";

                return $result;
            } else {
                return "";
            }

        }else if ($this->getLogicalId() == 'battery') {
            $result=parent::toHtml($_version,$_options,$_cmdColor);
            // blink_camera::logdebug('toHtml battery avant custo : '.print_r($result,true));
               
            $bl_cam=$this->getEqLogic();
            $email=$bl_cam->getConfiguration('email');
            if ($bl_cam->getConfiguration('noBatterieCheck', 0) == 1) {
               //$result= '<span class="label label-primary" style="font-size : 1em;" title="{{Secteur}}"><i class="fa fa-plug"></i></span>';
            }
            return $result;
        } else if ($this->getLogicalId()==='last_event') {
            //blink_camera::logdebug('toHtml last_event avant custo : '.print_r($result,true));
            $valeurLastEvent=$this->execCmd();
            $params = array(
                state => blink_camera::getDatetimeLocaleJeedom($valeurLastEvent)
            );
            $this->setDisplay('parameters',$params);

            return parent::toHtml($_version,$_options,$_cmdColor);
            //return str_replace($result,$valeurLastEvent,blink_camera::getDatetimeLocaleJeedom($valeurLastEvent));
        }else if ($this->getType()!=='action') {
            $bl_cam=$this->getEqLogic();
            $email=$bl_cam->getConfiguration('email');
            if ($this->getLogicalId()==='thumbnail' && !$bl_cam->isConnected($email) ) {
                return "<div class=\"badge text-warning text-wrap\">".__('Mode hors ligne', __FILE__)."</div>".parent::toHtml($_version, $_options, $_cmdColor);
            } else if ($bl_cam->isConfigured()) {
                return parent::toHtml($_version, $_options, $_cmdColor);
            } else {
                return "";
            }
        } else {
            $bl_cam=$this->getEqLogic();
            $email=$bl_cam->getConfiguration('email');
            if ($bl_cam->isConnected($email) && $bl_cam->isConfigured()) {
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
        $eqlogic = $this->getEqLogic(); //récupère l'éqlogic de la commande $this
        $email=$eqlogic->getConfiguration('email');
        switch ($this->getLogicalId()) {	//vérifie le logicalid de la commande
            case 'refresh':
                if ($eqlogic->isConfigured() && blink_camera::isConnected($email)) {
                    //rafraichissement de la datetime du dernier event
                    $eqlogic->getLastEventDate();
                    $eqlogic->refreshCameraInfos("execute refresh");
                }
                blink_camera::initNewConfigAccount();
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
                break;

            case 'disarm_camera':
                $eqlogic->cameraDisarm();
                break;
        }
    }

}