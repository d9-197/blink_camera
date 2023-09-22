<?php
class blink_camera_api {

public static function queryGet(string $url) {
        $_tokenBlink=config::byKey('token', 'blink_camera');
        $_accountBlink=config::byKey('account', 'blink_camera');
        $_regionBlink=config::byKey('region', 'blink_camera');
        $jsonrep=null;
        if (!$_tokenBlink=="" && !$_accountBlink=="" && !$_regionBlink=="") {
            $lock=self::checkAndGetLock('getQuery');
            blink_camera::logDebugBlinkAPIRequest("CALL[queryGet]: ".$url);
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
            self::releaseLock($lock);
            $jsonrep= json_decode($r->getBody(), true);
            blink_camera::logDebugBlinkAPIResponse(print_r($jsonrep,true));
        }    
        return $jsonrep;
    }
 
    public static function queryGetMedia(string $url, string $file_path) {
        $_tokenBlink=config::byKey('token', 'blink_camera');
        $_accountBlink=config::byKey('account', 'blink_camera');
        $_regionBlink=config::byKey('region', 'blink_camera');
        $jsonrep=null;
        if (!$_tokenBlink=="" && !$_accountBlink=="" && !$_regionBlink=="") {
            $lock=self::checkAndGetLock('queryGetMedia');
            blink_camera::logDebugBlinkAPIRequest("CALL[queryGetMedia]: ".$url);
            try {
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
                self::releaseLock($lock);
                $jsonrep= json_decode($r->getBody(), true);
            
                blink_camera::logDebugBlinkAPIResponse(print_r($jsonrep,true));
            }  catch (Exception $e) {
                self::releaseLock($lock);
                blink_camera::logdebug('ERROR:'.print_r($e->getTraceAsString(), true));
                blink_camera::logdebug('ERROR:'.print_r($e->getMessage(), true));
                return 1;
            }
        }    
        return $jsonrep;
    }
    
    public static function queryPostLogin(string $url, string $datas) {
        //blink_camera::logdebug('queryPostLogin(url='.$url.',datas='.$datas.') START');
        
        blink_camera::logDebugBlinkAPIRequest("CALL[queryPostLogin]: ".$url);
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
                    'User-Agent' =>  blink_camera::BLINK_DEFAULT_USER_AGENT,
                    'Accept' => '/'
                ],
                'json' => json_decode($datas)
            ]);
            self::releaseLock($lock);
            $jsonrep= json_decode($r->getBody(), true);
            blink_camera::logDebugBlinkAPIResponse(print_r($jsonrep,true));
            config::save('limit_login', 'false', 'blink_camera');
            return $jsonrep;
        }  catch (Exception $e) {
            self::releaseLock($lock);
            //{"message":"Login limit exceeded. Please disable any 3rd party automation and try again in 60 minutes."
            $response = $e->getResponse();
            $responseJson = json_decode($response->getBody()->getContents(),true);
            if (isset($responseJson['message'])) {
                blink_camera::logDebugBlinkResponse($responseJson['message']);
                if (blink_camera::startwith(strtolower($responseJson['message']),"Login limit exceeded")) {
                    config::save('limit_login', 'true', 'blink_camera');
                }
            }
            blink_camera::logdebug('ERROR:'.print_r($e->getTraceAsString(), true));
            blink_camera::logdebug('ERROR:'.print_r($e->getMessage(), true));
            throw $e;
        }
    }
    // 
    public static function queryPostPinVerify(string $pin) {
        //blink_camera::logdebug('queryPostPinVerify(pin='.$pin.') START');
        $client_id=config::byKey('client', 'blink_camera');
        $account_id=config::byKey('account', 'blink_camera');
        $_regionBlink=config::byKey('region', 'blink_camera');
        $_tokenBlink=config::byKey('token', 'blink_camera');

        $url='https://rest-'.$_regionBlink.'.immedia-semi.com/api/v4/account/'.$account_id.'/client/'.$client_id.'/pin/verify';
        blink_camera::logDebugBlinkAPIRequest("CALL[queryPostPinVerify]: ".$url);
        $lock=self::checkAndGetLock('queryPostPinVerify');  
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
            self::releaseLock($lock);
            $jsonrep= json_decode($r->getBody(), true);
            blink_camera::logDebugBlinkAPIResponse(print_r($jsonrep,true));

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
            self::releaseLock($lock);
            blink_camera::logdebug('ERROR:'.print_r($e->getTraceAsString(), true));
            blink_camera::logdebug('ERROR:'.print_r($e->getMessage(), true));
            return 1;
        }
        return 0;
    }
    public static function queryPost(string $url, string $datas="{}") {
        blink_camera::logdebug('queryPost(url='.$url.') START');
        blink_camera::logdebug('queryPost datas:'.$datas);
        $_regionBlink=config::byKey('region', 'blink_camera');
        $_tokenBlink=config::byKey('token', 'blink_camera');
        blink_camera::logDebugBlinkAPIRequest("CALL[queryPost]: ".$url);
        $lock=self::checkAndGetLock('queryPost'); 
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
            //self::releaseLock($lock);
            $jsonrep= json_decode($r->getBody(), true);
            blink_camera::logDebugBlinkAPIResponse(print_r($jsonrep,true));
            blink_camera::logdebug('queryPost(url='.$url.') END');
            return $jsonrep;

        }  catch (Exception $e) {
            self::releaseLock($lock);
            $response = $e->getResponse();
            $responseJson = json_decode($response->getBody()->getContents(),true);
            self::logDebugBlinkResponse($responseJson['message']);

/*            blink_camera::logdebug('ERROR:'.print_r($e->getTraceAsString(), true));
            blink_camera::logdebug('ERROR:'.print_r($e->getMessage(), true));*/
            /*$response = $e->getResponse();
            $responseJson = json_decode($response->getBody()->getContents(),true);
            if ($responseJson['code']=='307') {
                blink_camera::logdebugBlinkResponse($responseJson['message']);
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
    }*/
    public static function getToken(bool $forceReinit=false )
    {
        
        $argu='FALSE';
        if ($forceReinit) {
            $argu='TRUE';
        }
        $updFlag=$argu;
        blink_camera::logdebug('blink_camera_api::getToken('.$argu.') START');
        $date = date_create();
        $tstamp1=date_timestamp_get($date);
        $email=config::byKey('param1', 'blink_camera');
        $pwd=config::byKey('param2', 'blink_camera');
        $email_prev=config::byKey('param1_prev', 'blink_camera');
        $pwd_prev=config::byKey('param2_prev', 'blink_camera');
        if (!$forceReinit) {
            $forceReinit=($email!==$email_prev || $pwd!==$pwd_prev);
            if (!$forceReinit) {
                $updFlag='FALSE';
            }
        }
        blink_camera::logdebug('blink_camera_api::getToken('.$argu.') '.$updFlag);

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
                    blink_camera::logDebugBlinkAPIRequest("CALL[queryToken] -->");
                    $jsonrep=self::queryGet($url);
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
            
            blink_camera::logdebug('blink_camera_api::getToken('.$argu.') '.$updFlag. ' : Nouveau TOKEN');
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
                $jsonrep=self::queryPostLogin(blink_camera::BLINK_URL_LOGIN,$data);
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
                //$date = date_create();
                //$tstamp2=date_timestamp_get($date);
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
            //$date = date_create();
            //$tstamp2=date_timestamp_get($date);
            //blink_camera::logdebug('getToken()-4 END : '.($tstamp2-$tstamp1).' ms');
        }
        return true;
    }
	public static function getCameraThumbnail(blink_camera $camEq,$forceDownload=false) {
        blink_camera::logdebug('blink_camera_api::getCameraThumbnail() '.$camEq->getId().' START ' );
        $path="";
		if ($camEq->getBlinkDeviceType()!=="owlZZ") {
	      	$lastThumbnailTime = $camEq->getConfiguration("last_camera_thumb_time");
	      	$newtime=time();
            $path= $camEq->getConfiguration("camera_thumb_url");
	      	if ($forceDownload || ($newtime-$lastThumbnailTime)>5*6 || $path==="") {
		        $datas=self::getHomescreenData("getCameraThumbnail");
                blink_camera::logdebug('blink_camera_api::getCameraThumbnail() '.$camEq->getId().' getHomescreenData : '.print_r($datas, true));
                $camera_id = $camEq->getConfiguration("camera_id");
                blink_camera::logdebug('blink_camera_api::getCameraThumbnail() '.$camEq->getId().' camera_id : '.print_r($camera_id, true));

                //blink_camera::logdebug('getCameraThumbnail (Camera id:'.$this->getId().')- refresh thumbnail URL- previous time: '.$lastThumbnailTime.' - new time:'.$newtime.' - path:'.$path);
	        	foreach ($datas['cameras'] as $device) {
                    if ("".$device['id']==="".$camera_id) {
                        $timestamp_thumb=$device['thumbnail'];
                        $pattern="/.*ts=([0-9]*).*/";
                        
                        if (preg_match($pattern, $timestamp_thumb, $matches)) {
                            $timestamp_thumb="-".self::timestampToBlinkDate($matches[1]);
                        } else {
                            $timestamp_thumb="";
                        }
                        $path=self::getMediaForce($device['thumbnail'].'.jpg', $camEq->getId(),"thumbnail".$timestamp_thumb,"jpg",true);
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
                        $path=self::getMediaForce($device['thumbnail'].'.jpg', $camEq->getId(),"thumbnail".$timestamp_thumb,"jpg",true);
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
                        $path=self::getMediaForce($device['thumbnail'].'.jpg', $camEq->getId(),"thumbnail".$timestamp_thumb,"jpg",true);
                    }
                }

           		$pathRandom=trim(network::getNetworkAccess(config::byKey('blink_base_url', 'blink_camera'), '', '', false), '/').str_replace(" ","%20",blink_camera::GET_RESOURCE.$path."&".blink_camera::generateRandomString());

          		if (isset($path) && $path <> "") {
          		} 
                blink_camera::logdebug('blink_camera_api::getCameraThumbnail() '.$camEq->getId().' pathRandom : '.print_r($pathRandom, true));
          		$camEq->setConfiguration("last_camera_thumb_time",$newtime);
          		$camEq->setConfiguration("camera_thumb_url",$pathRandom);
                $camEq->checkAndUpdateCmd('camera_thumb_url',$pathRandom);
                $camEq->checkAndUpdateCmd('camera_thumb_path',$path);
                $path=$pathRandom;

			} else {
          		$path= $camEq->getConfiguration("camera_thumb_url");
        	}
		}
        blink_camera::logdebug('blink_camera_api::getCameraThumbnail() '.$camEq->getId().' END '.$path );
		//return $path;
	}
	public static function getCameraInfo(blink_camera $camEq) {
        blink_camera::logdebug('blink_camera_api::getCameraInfo  '.$camEq->getId().' START');
        $jsonrep=json_decode('{"message":erreur"}',true);
        if (blink_camera::isConnected() && $camEq->isConfigured()) {
            blink_camera::logdebug('blink_camera_api::getCameraInfo  '.$camEq->getId().' camera_id: '.$camEq->getConfiguration('camera_id'));
            
            $url='/network/'.$camEq->getConfiguration('network_id').'/camera/'.$camEq->getConfiguration('camera_id');
            try {
                blink_camera::logDebugBlinkAPIRequest("CALL[getCameraInfo] -->");
               $jsonrep=self::queryGet($url);
               #$folderJson=__DIR__.'/../../medias/getCameraInfoOwl.json';
                #file_put_contents($folderJson,json_encode($jsonrep));
            } catch (TransferException $e) {
                blink_camera::logdebug('getCameraInfo (type device='.$camEq->getBlinkDeviceType().')- An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                return $jsonrep;
            }
            blink_camera::logdebug('blink_camera_api::getCameraInfo  '.$camEq->getId().' END '.print_r($jsonrep, true));
            return $jsonrep;
        }
	}
    public static function getVideoList(blink_camera $eqLogic,int $page=1)
    {
        if ($eqLogic->getConfiguration('storage')=='local') {
            blink_camera::logdebug('blink_camera_api getVideoList (Camera id:'.$eqLogic->getId().') LOCAL');
            $result=self::getVideoListLocal($eqLogic,$page);
        } else {
            blink_camera::logdebug('blink_camera_api getVideoList (Camera id:'.$eqLogic->getId().') CLOUD');
            $result=self::getVideoListCloud($eqLogic,$page);
        }
        blink_camera::logdebug('blink_camera_api getVideoList (Camera id:'.$eqLogic->getId().') RESULT '.print_r($result,true));

        //$folderJson=__DIR__.'/../../medias/'.$this->getId().'/getVideoList_'.$this->getConfiguration('storage').'.json';
        //file_put_contents($folderJson,$result);
        return json_decode($result,true);        
    }
    public static function getVideoListCloud(blink_camera $eqLogic,int $page=1)
    {
        $network_id = $eqLogic->getConfiguration("network_id");
        $camera_id = $eqLogic->getConfiguration("camera_id");
        $jsonstr="erreur_cloud";
        if (blink_camera::isConnected() && $eqLogic->isConfigured()) {
            $_tokenBlink=config::byKey('token', 'blink_camera');
            $_accountBlink=config::byKey('account', 'blink_camera');
            $_regionBlink=config::byKey('region', 'blink_camera');
            $url='/api/v1/accounts/'.$_accountBlink.'/media/changed?since=2019-04-19T23:11:20+0000&page='.$page;
            
            try {
                blink_camera::logdebugBlinkAPIRequest("CALL[getVideoListCloud] -->");
//                self::checkAndGetLock('net-'.$network_id,2);
                $jsonrep=self::queryGet($url);

                if (isset($jsonrep)) {
                    $jsonstr =self::reformatVideoDatas($eqLogic,$jsonrep);
//                    $folderJson=__DIR__.'/../../medias/'.$this->getId().'/getlistvideocloud_result.json';
//                    file_put_contents($folderJson,json_encode($jsonstr));

                }
            } catch (TransferException $e) {
                blink_camera::logdebug('An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                return $jsonstr;
            }
            return $jsonstr;
        }
	}
    public static function getVideoListLocal(blink_camera $eqLogic,$page)
    { 
        $network_id = $eqLogic->getConfiguration("network_id");
        $camera_id = $eqLogic->getConfiguration("camera_id");
        $result="erreur_local";
        if (blink_camera::isConnected() && $eqLogic->isConfigured()) {
            $_accountBlink=config::byKey('account', 'blink_camera');
            $_regionBlink=config::byKey('region', 'blink_camera');
            $syncId=$eqLogic->getConfiguration('sync_id');
            $cameraApiName=$eqLogic->getConfiguration('camera_name');


            if (!$syncId =="") {
    blink_camera::logdebug('getVideoListLocal '.$eqLogic->getName().' syncId=: '.$syncId);
//                self::checkAndGetLock('syncId-'.$syncId);
                $url_manifest='/api/v1/accounts/'.$_accountBlink.'/networks/'.$network_id.'/sync_modules/'.$syncId.'/local_storage/manifest';
                $url_manifest_req=$url_manifest.'/request';
                try {
                    $jsonrep=self::queryPost($url_manifest_req);
                } catch (TransferException $e) {
                    blink_camera::logdebug('An error occured during Blink Cloud call POST : '.$url_manifest_req. ' - ERROR:'.print_r($e->getMessage(), true));
                    return $result;
                }
                if (isset($jsonrep)) {
    //$folderJson=__DIR__.'/../../medias/'.$this->getId().'/getlistvideolocal_ph1.json';
    //file_put_contents($folderJson,json_encode($jsonrep));
    jeedomUtils.sleep(1);
    blink_camera::logdebug('getVideoListLocal '.$eqLogic->getName().' Phase 1 : '.print_r($jsonrep,true));
                    $manifest_req_id=$jsonrep['id'];
                    $url=$url_manifest_req.'/'.$manifest_req_id;
                    try {
                        $jsonrep=self::queryGet($url);
                    } catch (TransferException $e) {
                        blink_camera::logdebug('An error occured during Blink Cloud call GET : '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                        return $result;
                    }
                    if (isset($jsonrep)) {
    //$folderJson=__DIR__.'/../../medias/'.$this->getId().'/getlistvideolocal_ph2.json';
    //file_put_contents($folderJson,json_encode($jsonrep));
    blink_camera::logdebug('getVideoListLocal '.$eqLogic->getName().' Phase 2 : '.print_r($jsonrep,true));
                        $manifest_id=$jsonrep['manifest_id'];
                        if (isset($manifest_id)) {
                            $result= array();
                            $idx=0;
                            foreach ($jsonrep['clips'] as $clip) {
                                if (strtolower($clip['camera_name'])===strtolower($cameraApiName)) {
                                    $clip['media']=$clip['id'];
                                    $clip['thumbnail']=$clip['id'];
                                    $clip['deleted']=(bool) 0;
                                    $clip['source']="-";
                                    $clip['device_id']=$eqLogic->getConfiguration('camera_id');
                                    $clip['device_name']=$clip['camera_name'];
                                    $result[$idx]=$clip;
                                    $idx++;
                                }
                            }
                            if ($idx>0) {
//                                $folderJson=__DIR__.'/../../medias/'.$this->getId().'/getlistvideolocal_result.json';
//                                file_put_contents($folderJson,json_encode($result));
                                blink_camera::logdebug('getVideoListLocal '.$eqLogic->getName().' result  : '.print_r($result,true));
                                return json_encode($result);
                            } else {
                                $result="no video";
                            }
                            
                        } else {
                            blink_camera::logdebug('getVideoListLocal pas de manifest !');
                        }
                    } else {
                        blink_camera::logdebug('getVideoListLocal pas de réponse de API manifest !');
                    }
                } else {
                    blink_camera::logdebug('getVideoListLocal pas de réponse de API manifest REQUEST !');
                }
            } else {
                blink_camera::logdebug('getVideoListLocal syncID not found !');
            }
        }
        return json_encode($result);
    }
    public static function requestNewMediaCamera(blink_camera $eqLogic,$type="clip")
    {
        return self::requestNewMedia($eqLogic,$type,"camera");
    }
    public static function requestNewMediaDoorbell(blink_camera $eqLogic,$type="clip")
    {
        return self::requestNewMedia($eqLogic,$type,"doorbells");
    }
    public static function requestNewMediaMini(blink_camera $eqLogic,$type="clip")
    {
        return self::requestNewMedia($eqLogic,$type,"owl");
    }
	public static function requestNewMedia(blink_camera $eqLogic,$type="clip",$typeDevice="camera")
    {
        $jsonrep=json_decode('["message":"erreur"]');
        if (($type==="clip" || $type ==="thumbnail" ) &&blink_camera::isConnected() && $eqLogic->isConfigured()) {
            $_accountBlink=config::byKey('account', 'blink_camera');
                    if ($typeDevice==='owl') {
                        // https://rest.prde.immedia-semi.com/api/v1/accounts/{{accountid}}/networks/194881/owls/3287/clip
                        $url='/api/v1/accounts/'.$_accountBlink.'/networks/'.$eqLogic->getConfiguration('network_id').'/owls/'.$eqLogic->getConfiguration('camera_id').'/'.$type;
                    } else if ($typeDevice==='doorbells')  {
                        // https://rest.prde.immedia-semi.com/api/v1/accounts/{{accountid}}/networks/194881/owls/3287/clip
                        $url='/api/v1/accounts/'.$_accountBlink.'/networks/'.$eqLogic->getConfiguration('network_id').'/doorbells/'.$eqLogic->getConfiguration('camera_id').'/'.$type;
                    } else  {
                        $url='/network/'.$eqLogic->getConfiguration('network_id').'/'.$typeDevice.'/'.$eqLogic->getConfiguration('camera_id').'/'.$type;
                    }
                    blink_camera::logdebugBlinkAPIRequest("CALL[requestNewMedia]: --> ");
                try {
                    $jsonrep=self::queryPost($url);
                } catch (TransferException $e) {
                    blink_camera::logdebug('An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                    $response = $e->getResponse();
                    $responseJson = json_decode($response->getBody()->getContents(),true);
                    blink_camera::logdebugBlinkResponse($responseJson['message']);
                    return false;
                }
            return $jsonrep;
        }
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
    public static function getAccountConfigDatas2($force_json_string=false,$forceReinitToken=false) {
        if (blink_camera::isConnected()) {
            $datas=self::getHomescreenData("getAccountConfigDatas2");
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


    public static function reformatVideoDatas(blink_camera $cam,array $jsonin)
    {
        $jsonstr= "[";
        $cpt=0;
        foreach ($jsonin['media'] as $media) {
            if ($cam->getConfiguration('network_id')==$media['network_id']) {
                if ($cam->getConfiguration('camera_id')==$media['device_id']) {
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
        $cam = blink_camera::byId($equipement_id);
        if ($cam->getConfiguration('storage')=='local') {
            return self::getMediaLocal($urlMedia,$equipement_id);
        } else {
            return self::getMediaForce($urlMedia, $equipement_id, $filename,$format,false);
        }
    }
    private static function getMediaForce($urlMedia, $equipement_id, $filename="default",$format="mp4",$overwrite=false)
    {
        //blink_camera::logdebug('blink_camera->getMediaForce() url : '.$urlMedia);
        if (!empty($urlMedia)) {
                $_tokenBlink=config::byKey('token', 'blink_camera');
                $_accountBlink=config::byKey('account', 'blink_camera');
                $_regionBlink=config::byKey('region', 'blink_camera');
                $filenameTab = explode('/', $urlMedia);
               // blink_camera::logdebug("blink_camera->getMediaForce() split : ".print_r($filenameTab,true));
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
            if ((!file_exists($folderBase.$filename) || $overwrite) && blink_camera::isConnected()) {
                //blink_camera::logdebug("blink_camera->getMediaForce() url : $urlMedia - path : $filename");
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
                            blink_camera::logDebugBlinkAPIRequest("CALL[getMediaForce] -->");
                            self::queryGetMedia($urlMedia,$folderBase.$filename);
                            if (file_exists($folderBase.$filename)) {
                                chmod($folderBase.$filename, 0775);
                            }
                        } 
                        catch (TransferException $e) {
                            blink_camera::logdebug('An error occured during Blink Cloud call: '.$urlMedia. ' - ERROR:'.print_r($e->getMessage(), true));
                            self::deleteMedia($folderBase.$filename);
                            return blink_camera::ERROR_IMG;
                        }
                    }
                } else {
                    //blink_camera::logdebug("blink_camera->getMediaForce() url : $urlMedia - path : error.png");
                    return blink_camera::ERROR_IMG;
                }
            }
                //blink_camera::logdebug("blink_camera->getMediaForce() url : $urlMedia - path : $filename");
                return '/plugins/blink_camera/medias/'.$equipement_id.'/'.$filename;
        }
        return blink_camera::ERROR_IMG;
    }

    public static function getHomescreenData($callOrig="")
    {
        $jsonrep=json_decode('{"message":"error"}',true);
        if (blink_camera::isConnected()) {
            $_accountBlink=config::byKey('account', 'blink_camera');
            $url='/api/v3/accounts/'.$_accountBlink.'/homescreen';
            try {
                blink_camera::logDebugBlinkAPIRequest("CALL[getHomescreenData from ".$callOrig."] -->");
                $jsonrep=self::queryGet($url);
                #$folderJson=__DIR__.'/../../medias/getHomescreenData.json';
                #file_put_contents($folderJson,json_encode($jsonrep));
            }
            catch (TransferException $e) {
                $errorTxt='ERROR: getHomescreenData - '.print_r($e->getMessage(), true);
                blink_camera::logwarn($errorTxt);
                    $jsonrep=json_decode('{"message":"'.$errorTxt.'"}',true);

            }
            blink_camera::logdebug('getHomescreenData :\n'.print_r($jsonrep,true));
            return $jsonrep;
        }
    }
        
    private static function getMediaLocal($clip_id_req="",$equipement_id=null) {
        $cam = blink_camera::byId($equipement_id);
        blink_camera::logdebug('getMediaLocal Call : '.$cam->getId().' '.$cam->getName());
        $_accountBlink=config::byKey('account', 'blink_camera');
        $netId=$cam->getConfiguration('network_id');
        $syncId=$cam->getConfiguration('sync_id');
        if (!$syncId =="") {
blink_camera::logdebug('getMediaLocal syncId=: '.$syncId);
            $url_manifest='/api/v1/accounts/'.$_accountBlink.'/networks/'.$netId.'/sync_modules/'.$syncId.'/local_storage/manifest';
            $url_manifest_req=$url_manifest.'/request';
            try {
//                self::checkAndGetLock('syncId-'.$syncId);
                $jsonrep=self::queryPost($url_manifest_req);
            } catch (TransferException $e) {
                blink_camera::logdebug('An error occured during call API LOCAL STORAGE POST: '.$url_manifest_req. ' - ERROR:'.print_r($e->getMessage(), true));
                return blink_camera::ERROR_IMG;
            }
            if (isset($jsonrep)) {
//$folderJson=__DIR__.'/../../medias/'.$cam->getId().'/localStorage_ph1.json';
//file_put_contents($folderJson,json_encode($jsonrep));
jeedomUtils.sleep(1);
blink_camera::logdebug('getMediaLocal Phase 1 : '.print_r($jsonrep,true));
                $manifest_req_id=$jsonrep['id'];
                $url=$url_manifest_req.'/'.$manifest_req_id;
                $jsonrep=self::queryGet($url);
                if (isset($jsonrep)) {
//$folderJson=__DIR__.'/../../medias/'.$cam->getId().'/localStorage_ph2.json';
//file_put_contents($folderJson,json_encode($jsonrep));
blink_camera::logdebug('getMediaLocal Phase 2 : '.print_r($jsonrep,true));
                    $manifest_id=$jsonrep['manifest_id'];
                    if (isset($manifest_id)) {
                        foreach ($jsonrep['clips'] as $clips) {
                            $clip_id=$clips['id'];
                            if ($clip_id_req=="" || $clip_id_req==$clip_id) {
                                $camera_name=$clips['camera_name'];
                                $clip_date=$clips['created_at'];
                                $filename=$clip_id.'-'.self::getDateJeedomTimezone($clip_date);
blink_camera::logdebug('getMediaLocal clip_id : '.$clip_id.' - camera_name : '.$camera_name.' ('.$cam->getName().') - created_at : ' .$clip_date);
                                if (strtolower($camera_name)===strtolower($cam->getName())) {
                                    $url_media=$url_manifest.'/'.$manifest_id.'/clip/request/'.$clip_id;
blink_camera::logdebug('getMediaLocal URL MEDIA : '.$url_media);
                                    try {
                                        $jsonrep=self::queryPost($url_media);
                                    } catch (TransferException $e) {
                                        blink_camera::logdebug('An error occured during call API LOCAL STORAGE POST: '.$url_media. ' - ERROR:'.print_r($e->getMessage(), true));
                                        return blink_camera::ERROR_IMG;
                                    }
//$folderJson=__DIR__.'/../../medias/'.$cam->getId().'/localStorage_ph3.json';
//file_put_contents($folderJson,json_encode($jsonrep));
                                    jeedomUtils.sleep(1);
                                    return self::getMediaForce($url_media, $cam->getId(), $filename,'mp4',true);
                                }
                            }
                        }
                        blink_camera::logdebug('getMediaLocal clip_id not found in clips list !');
                    } else {
                        blink_camera::logdebug('getMediaLocal pas de manifest !');
                    }
                } else {
                    blink_camera::logdebug('getMediaLocal pas de réponse de API manifest !');
                }
            } else {
                blink_camera::logdebug('getMediaLocal pas de réponse de API manifest REQUEST !');
            }
        } else {
            blink_camera::logdebug('getMediaLocal syncID not found !');
        }
        return blink_camera::ERROR_IMG;
    }
    private static function checkAndGetLock($ident='all', $attente_maxi=blink_camera::ATTENTE_MAXI_DEFAUT) {
        $previousCaller=config::byKey('api_last_call_caller','blink_camera');
        $newCaller=$ident.'-'.blink_camera::generateRandomString(10);
        //blink_camera::logdebug('checkAndGetLock('.$newCaller.') START');
        $idx=1;
        while (isset ($previousCaller) && $previousCaller <> $newCaller && $previousCaller <> '' && $idx <= $attente_maxi) {
            if ($idx==1) {
                blink_camera::logdebug('checkAndGetLock('.$newCaller.') Debut attente de '.$previousCaller);
            } else {
                //blink_camera::logdebug('checkAndGetLock('.$newCaller.') Suite attente de '.$previousCaller.' ('.($idx-1).'s)');
            }
            sleep(1);
            $previousCaller=config::byKey('api_last_call_caller','blink_camera');
            $idx++;
        }
        if ($idx>=2) {
            blink_camera::logdebug('checkAndGetLock('.$newCaller.') Fin attente de '.$previousCaller. ' ('.($idx-1).'s)');
        }
        config::save('api_last_call_caller', $newCaller,'blink_camera');

        //config::remove('api_last_call_caller','blink_camera');
        //blink_camera::logdebug('checkAndGetLock('.$newCaller.') END');
        return $newCaller;

    }
    private static function releaseLock($caller) {
        if (config::byKey('api_last_call_caller','blink_camera')!='local') {
            $previousCaller=config::byKey('api_last_call_caller','blink_camera');
            if ($previousCaller==$caller) {
                blink_camera::logdebug('releaseLock('.$caller.') DONE');
                config::remove('api_last_call_caller','blink_camera');
            }
        }
    }

    public static function deleteMediaCloud($filepath) {
        $filepath=realpath($filepath);
        //blink_camera::logdebug('deleteMediaCloud(filepath='.$filepath.') START');
        // On controle que l'on soit bien dans le dossier de stockage des medias du plugin !                
        if (strpos($filepath, '/plugins/blink_camera/') !== false && strpos($filepath, '/medias/') !== false) {
            $mediaId=explode('-',basename($filepath))[0];

           
            if (isset($mediaId) && $mediaId!="" && blink_camera::isConnected()) {
                $_accountBlink=config::byKey('account', 'blink_camera');
                $datas='{"media_list":['.$mediaId.']}';
                $url='/api/v1/accounts/'.$_accountBlink.'/media/delete';
                try {
                    $jsonrep=self::queryPost($url,$datas);
                    // Recup de la camera concernée pour vérifier si on est sur la suppression du dernier event
                    $cameraId=explode('/',explode('/medias/',$filepath)[1])[0];
                    $eqLogics = blink_camera::byType('blink_camera', true);
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
                    $response = $e->getResponse();
                    $responseJson = json_decode($response->getBody()->getContents(),true);
                    blink_camera::logdebugBlinkResponse($responseJson['message']);
                    blink_camera::logdebug('An error occured during Blink Cloud call: '.$url. ' - ERROR:'.print_r($e->getMessage(), true));
                    return false;
                }
            }
            return false;
        } else {
            blink_camera::logdebug('Plugin blink camera try to delete file in Blink cloud but not in "medias" folder');
        }
    }
}  
?>