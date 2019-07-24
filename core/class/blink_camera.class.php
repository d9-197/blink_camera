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
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

class blink_camera extends eqLogic
{
    /*     * *************************Attributs****************************** */
    const FORMAT_DATETIME="Y-m-d\TH:i:sT" ;//2019-07-15T18:40:44+00:00
    const FORMAT_DATETIME_OUT="Y-m-d H:i:s" ;//2019-07-15T18:40:44+00:00
    const ERROR_IMG="/plugins/blink_camera/img/error.png";
    public static $_widgetPossibility = array('custom' => true, 'custom::layout' => true);

    /*     * ***********************Methode static*************************** */

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
     * */
    public static function cron5($_eqLogic_id = null)
    {
        if ($_eqLogic_id == null) { // La fonction n’a pas d’argument donc on recherche tous les équipements du plugin
            $eqLogics = self::byType('blink_camera', true);
        } else {// La fonction a l’argument id(unique) d’un équipement(eqLogic
            $eqLogics = array(self::byId($_eqLogic_id));
        }
        foreach ($eqLogics as $blink_camera) {//parcours tous les équipements du plugin blink_camera
            if ($blink_camera->getIsEnable() == 1) {//vérifie que l'équipement est acitf
                //$blink_camera->getLastEventDate();
				$blink_camera->getNetworkArmStatus();
                $blink_camera->refreshCameraInfos();
            }
        }
    }
     
    public static function cron($_eqLogic_id = null)
    {
        if ($_eqLogic_id == null) { // La fonction n’a pas d’argument donc on recherche tous les équipements du plugin
            $eqLogics = self::byType('blink_camera', true);
        } else {// La fonction a l’argument id(unique) d’un équipement(eqLogic
            $eqLogics = array(self::byId($_eqLogic_id));
        }
        foreach ($eqLogics as $blink_camera) {//parcours tous les équipements du plugin blink_camera
            if ($blink_camera->getIsEnable() == 1) {//vérifie que l'équipement est acitf
                $blink_camera->getLastEventDate();
            }
        }
    }


    public static function cronHourly($_eqLogic_id = null)
    {
        if ($_eqLogic_id == null) { // La fonction n’a pas d’argument donc on recherche tous les équipements du plugin
            $eqLogics = self::byType('blink_camera', true);
        } else {// La fonction a l’argument id(unique) d’un équipement(eqLogic
            $eqLogics = array(self::byId($_eqLogic_id));
        }
    
        foreach ($eqLogics as $blink_camera) {//parcours tous les équipements du plugin blink_camera
            if ($blink_camera->getIsEnable() == 1) {//vérifie que l'équipement est acitf
                $cmd = $blink_camera->getCmd(null, 'refresh');//retourne la commande "refresh si elle exxiste
                if (!is_object($cmd)) {//Si la commande n'existe pas
                  continue; //continue la boucle
                }
                $cmd->execCmd(); // la commande existe on la lance
            }
        }
    }



    public static function getToken()
    {
        $email=config::byKey('param1', 'blink_camera');
        $pwd=config::byKey('param2', 'blink_camera');
        $email_prev=config::byKey('param1_prev', 'blink_camera');
        $pwd_prev=config::byKey('param2_prev', 'blink_camera');
        $forceReinit=($email!==$email_prev || $pwd!==$pwd_prev);

        /* Test de validité du token deja existant */
        $need_new_token=false;
        $_tokenBlink=config::byKey('token', 'blink_camera');
        $_accountBlink=config::byKey('account', 'blink_camera');
        $_regionBlink=config::byKey('region', 'blink_camera');
        if (!$forceReinit) {
            if (!$_tokenBlink=="" && !$_accountBlink=="" && !$_regionBlink=="") {
                $client = new GuzzleHttp\Client(['base_uri' => 'https://rest.'.$_regionBlink.'.immedia-semi.com']);
                $url='/api/v1/accounts/'.$_accountBlink.'/media/changed?since=2019-04-19T23:11:20+0000&page=0';
                try {
                    $r = $client->request('GET', $url, [
                        'headers' => [
                            'Host'=> 'prod.immedia-semi.com',
                            'TOKEN_AUTH'=> $_tokenBlink
                        ]
                    ]);
                } catch (ClientException $e) {
                    $need_new_token=true;
                }
            } else {
                $need_new_token=true;
            }
            if (!$need_new_token) {
                //log::add('blink_camera', 'debug', 'blink_camera->getToken() Reuse existing token');
                return true;
            }
        } else {
            config::save('token', '', blink_camera);
            config::save('account', '', blink_camera);
            config::save('region', '', blink_camera);
        }
        config::save('param1_prev', $email, blink_camera);
        config::save('param2_prev', $pwd, blink_camera);
        $data = "{\"email\" : \"".$email."\",\"password\": \"".$pwd."\", \"client_specifier\" : \"iPhone 9.2 | 2.2 | 222\"}";
        $client = new GuzzleHttp\Client(['base_uri' => 'https://rest.prod.immedia-semi.com/']);
        try {
            $r = $client->request('POST', 'login', [
            'headers' => [
                'Host'=> 'prod.immedia-semi.com',
                'Content-Type'=> 'application/json'
            ],
            'json' => json_decode($data)
            ]);
        } catch (ClientException $e) {
            log::add('blink_camera', 'error', 'blink_camera->getToken() ERROR : '.print_r($e->getResponse()->getStatusCode(), true));
            return false;
        }
        $jsonrep= json_decode($r->getBody(), true);
        $_tokenBlink=$jsonrep['authtoken']['authtoken'];
        $_accountBlink=$jsonrep['account']['id'];
        foreach ($jsonrep['region'] as $key => $val) {
            $_regionBlink= $key;
            config::save('region', $_regionBlink, blink_camera);
        }
        config::save('token', $_tokenBlink, blink_camera);
        config::save('account', $_accountBlink, blink_camera);
        config::save('region', $_regionBlink, blink_camera);
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
    
    public static function getConfigDatas()
    {
        if (self::getToken()) {
            $_tokenBlink=config::byKey('token', 'blink_camera');
            $_accountBlink=config::byKey('account', 'blink_camera');
            $_regionBlink=config::byKey('region', 'blink_camera');
            if (!$_tokenBlink=="" && !$_accountBlink=="" && !$_regionBlink=="") {
                $client = new GuzzleHttp\Client(['base_uri' => 'https://rest.'.$_regionBlink.'.immedia-semi.com']);
                $url='/api/v1/accounts/'.$_accountBlink.'/media/changed?since=2019-04-19T23:11:20+0000&page=0';
                
                $r = $client->request('GET', $url, [
                    'headers' => [
                        'Host'=> 'prod.immedia-semi.com',
                        'TOKEN_AUTH'=> $_tokenBlink
                    ]
                ]);
                $jsonrep= json_decode($r->getBody(), true);
                $jsonstr =self::reformatConfigDatas($jsonrep, $_regionBlink, $_accountBlink);
                $jsonout=json_decode($jsonstr, true);
                //log::add('blink_camera', 'debug', 'blink_camera->getConfigDatas()'.$jsonstr);
                return $jsonout;
            }
        }
        return json_decode('{"message":"{{Erreur lors de la prise de token !}}"}', true);
    }

    public static function reformatVideoDatas(array $jsonin, string $network_id, string $camera_id)
    {
        $jsonstr= "[";
        $nets= array();
        $cpt=0;
        foreach ($jsonin['media'] as $media) {
            if ($network_id==$media['network_id']) {
                if ($camera_id==$media['device_id']) {
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
                    $jsonstr=$jsonstr.",\"device_id\":\"".$media['device_id']."\",\"device_name\":\"".$media['device_name']."\",\"media\":\"".$media['media']."\",\"thumbnail\":\"".$media['thumbnail']."\",\"created_at\":\"".$media['created_at']."\"}";
                }
            }
        }
        $jsonstr=$jsonstr."]";
        return $jsonstr;
    }
    
    public static function getDateJeedomTimezone(string $date="")
    {
        $dtim = date_create_from_format(blink_camera::FORMAT_DATETIME, $date);
        $dtim=date_add($dtim, new DateInterval("PT".getTZoffsetMin()."M"));
        return date_format($dtim, blink_camera::FORMAT_DATETIME_OUT);
    }
    public static function getMedia($urlMedia, $equipement_id, $filename="default",$format="mp4")
    {
        //log::add('blink_camera', 'debug', 'blink_camera->getMedia() url : '.$urlMedia);
        if (!empty($urlMedia)) {
            if (self::getToken()) {
                $_tokenBlink=config::byKey('token', 'blink_camera');
                $_accountBlink=config::byKey('account', 'blink_camera');
                $_regionBlink=config::byKey('region', 'blink_camera');
                $filenameTab = explode('/', $urlMedia);
                //log::add('blink_camera', 'debug', "blink_camera->getMedia() split : ".print_r($filenameTab,true));
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

                //log::add('blink_camera', 'debug', "blink_camera->getMedia() url : $urlMedia - path : $filename");
                if (!empty($_tokenBlink) && !empty($_accountBlink) && !empty($_regionBlink)) {
                    $folderBase=__DIR__.'/../../medias/'.$equipement_id.'/';
                    if (!file_exists($folderBase)) {
                        mkdir($folderBase, 0775);
                        chmod($folderBase, 0775);
                    }
                    if (!file_exists($folderBase.$filename)) {
                        $file_path = fopen($folderBase.$filename, 'w');
                        if (file_exists($folderBase.$filename)) {
                            chmod($folderBase.$filename, 0775);
                        }
                        $client = new GuzzleHttp\Client(['base_uri' => 'https://rest.'.$_regionBlink.'.immedia-semi.com']);
                        try {
                            $r = $client->request(
                                'GET',
                                $urlMedia,
                                ['sink' => $file_path,
                                'headers' => [
                                    'Host'=> 'prod.immedia-semi.com',
                                    'TOKEN_AUTH'=> $_tokenBlink
                                ]
                                ]
                            );
                            if (file_exists($folderBase.$filename)) {
                                chmod($folderBase.$filename, 0775);
                            }
                        } catch (RequestException $e) {
                            shell_exec('rm -rf ' . $folderBase.$filename);
                            log::add('blink_camera', 'debug', "blink_camera->getMedia() url : $urlMedia - path : error.png");
                            return blink_camera::ERROR_IMG;
                        }
                    }
                } else {
                    log::add('blink_camera', 'debug', "blink_camera->getMedia() url : $urlMedia - path : error.png");
                    return blink_camera::ERROR_IMG;
                }
                log::add('blink_camera', 'debug', "blink_camera->getMedia() url : $urlMedia - path : $filename");
                return '/plugins/blink_camera/medias/'.$equipement_id.'/'.$filename;
            }
        }
        return blink_camera::ERROR_IMG;
    }
    
    /*     * *********************Méthodes d'instance************************* */
    public function isConfigured()
    {
        $network_id = $this->getConfiguration("network_id");
        $camera_id = $this->getConfiguration("camera_id");
        if ($network_id!=="" && $camera_id!=="") {
            return true;
        }
        return false;
    }

    public function getHomescreenData()
    {
        $network_id = $this->getConfiguration("network_id");
        $camera_id = $this->getConfiguration("camera_id");
        $jsonstr="erreur";
        if (self::getToken() && $this->isConfigured()) {
            $_tokenBlink=config::byKey('token', 'blink_camera');
            $_accountBlink=config::byKey('account', 'blink_camera');
            $_regionBlink=config::byKey('region', 'blink_camera');
            if (!$_tokenBlink=="" && !$_accountBlink=="" && !$_regionBlink=="") {
                $client = new GuzzleHttp\Client(['base_uri' => 'https://rest.'.$_regionBlink.'.immedia-semi.com']);
				//$url='/homescreen';
				$url='/network/'.$network_id.'/homescreen';
				$r = $client->request('GET', $url, [
                    'headers' => [
                        'Host'=> 'prod.immedia-semi.com',
                        'TOKEN_AUTH'=> $_tokenBlink
                    ]
                ]);
                $jsonrep= json_decode($r->getBody(), true);
            }
            return $jsonrep;
        }
	}
	public function getCameraInfo() {
		$network_id = $this->getConfiguration("network_id");
        $camera_id = $this->getConfiguration("camera_id");
        $jsonstr="erreur";
        if (self::getToken() && $this->isConfigured()) {
            $_tokenBlink=config::byKey('token', 'blink_camera');
            $_accountBlink=config::byKey('account', 'blink_camera');
            $_regionBlink=config::byKey('region', 'blink_camera');
            if (!$_tokenBlink=="" && !$_accountBlink=="" && !$_regionBlink=="") {
                $client = new GuzzleHttp\Client(['base_uri' => 'https://rest.'.$_regionBlink.'.immedia-semi.com']);
				
				$url='/network/'.$network_id.'/camera/'.$camera_id;
				$r = $client->request('GET', $url, [
                    'headers' => [
                        'Host'=> 'prod.immedia-semi.com',
                        'TOKEN_AUTH'=> $_tokenBlink
                    ]
                ]);
                $jsonrep= json_decode($r->getBody(), true);
            }
            return $jsonrep;
        }
	}

	public function getCameraThumbnail() {
		$datas=$this->getHomescreenData();
		$camera_id = $this->getConfiguration("camera_id");
        foreach ($datas['devices'] as $device) {
			log::add('blink_camera','debug','devices='.$camera_id.' vs '.print_r( $device['device_id'],true));
                
            if ("".$device['device_id']==="".$camera_id) {
				//log::add('blink_camera','debug','devices='.$camera_id.' vs '.print_r( $device['device_id'],true));
                $path=$this->getMedia($device['thumbnail'], $this->getId(),"thumbnail","jpg");
            }
        }
		return $path;
	}


    public function getVideoList(int $page=1)
    {
        $network_id = $this->getConfiguration("network_id");
        $camera_id = $this->getConfiguration("camera_id");
        $jsonstr="erreur";
        if (self::getToken() && $this->isConfigured()) {
            $_tokenBlink=config::byKey('token', 'blink_camera');
            $_accountBlink=config::byKey('account', 'blink_camera');
            $_regionBlink=config::byKey('region', 'blink_camera');
            if (!$_tokenBlink=="" && !$_accountBlink=="" && !$_regionBlink=="") {
                $client = new GuzzleHttp\Client(['base_uri' => 'https://rest.'.$_regionBlink.'.immedia-semi.com']);
                $url='/api/v1/accounts/'.$_accountBlink.'/media/changed?since=2019-04-19T23:11:20+0000&page='.$page;
                $r = $client->request('GET', $url, [
                    'headers' => [
                        'Host'=> 'prod.immedia-semi.com',
                        'TOKEN_AUTH'=> $_tokenBlink
                    ]
                ]);
                $jsonrep= json_decode($r->getBody(), true);
                $jsonstr =self::reformatVideoDatas($jsonrep, $network_id, $camera_id);
            }
            return $jsonstr;
        }
	}
	
	public function requestNewMedia($type="clip")
    {
        $network_id = $this->getConfiguration("network_id");
        $camera_id = $this->getConfiguration("camera_id");
        $jsonrep=json_decode('["message":"erreur"]');
        if (($type==="clip" || $type ==="thumbnail" ) &&self::getToken() && $this->isConfigured()) {
            $_tokenBlink=config::byKey('token', 'blink_camera');
            $_accountBlink=config::byKey('account', 'blink_camera');
            $_regionBlink=config::byKey('region', 'blink_camera');
            if (!$_tokenBlink=="" && !$_accountBlink=="" && !$_regionBlink=="") {
                $client = new GuzzleHttp\Client(['base_uri' => 'https://rest.'.$_regionBlink.'.immedia-semi.com']);
                $url='/network/'.$network_id.'/camera/'.$camera_id.'/'.$type;
                $r = $client->request('POST', $url, [
                    'headers' => [
                        'Host'=> 'prod.immedia-semi.com',
                        'TOKEN_AUTH'=> $_tokenBlink
                    ]
                ]);
                $jsonrep= json_decode($r->getBody(), true);
            }
            return $jsonrep;
        }
	}


    public function forceCleanup($download=false)
    {
        // Nettoyage des fichiers du dossier medias
        $videos=$this->getVideoList();
        $files = scandir($this->getMediaDir());
        $fich="";
        foreach ($files as $file) {
            $trouve=false;
            $filename="";
            foreach (json_decode($videos, true) as $video) {
                $dtim = date_create_from_format(blink_camera::FORMAT_DATETIME, $video['created_at']);
                $dtim=date_add($dtim, new DateInterval("PT".getTZoffsetMin()."M"));
                $filename=date_format($dtim, blink_camera::FORMAT_DATETIME_OUT);
                $length = strlen($filename);
                if (substr($file, 0, $length) === $filename) {
                    $trouve=true;
                }
            }
            if ($trouve===false) {
				log::add('blink_camera', 'debug', 'blink_camera->forceCleanup() Delete file: '. $this->getMediaDir().'/'.$file);
                shell_exec('rm -rf ' . $this->getMediaDir().'/'.$file);
            }
        }
        // Récupération des videos
        //$videos=$this->getVideoList();
        foreach (json_decode($videos, true) as $video) {
            $filename=blink_camera::getDateJeedomTimezone($video['created_at']);
            if (!$video['deleted']) {
				if ($download) { // Si demandé, on télécharge les vidéos disponibles
					$path=$this->getMedia($video['media'], $this->getId(), $filename);
					log::add('blink_camera', 'debug', 'blink_camera->forceCleanup() Download file: '. $path);
				}
            } else {
				log::add('blink_camera', 'debug', 'blink_camera->forceCleanup() Delete file: '. $this->getMediaDir().'/'.$file);
                shell_exec('rm -rf ' . $this->getMediaDir().'/'.$filename);
            }
		}
		$this->getLastEvent();
		// récup thumbnail de la caméra
		///$this->getCameraThumbnail();
    }
    public function getLastEvent($include_deleted=true)
    {
        log::add('blink_camera', 'debug', 'blink_camera->getLastEvent() start');
        // on boucle sur les pages au cas ou les premières pages ne contiendraient que des event supprimés
        for ($page=1;$page<=10;$page++) {
            $jsonvideo=$this->getVideoList($page);
            foreach (json_decode($jsonvideo, true) as $event) {
                if ($include_deleted || $event['deleted']===false) {
                    //log::add('blink_camera', 'debug', 'blink_camera->getLastEvent() '.$event['created_at']);
                    if (!isset($last_event)) {
                        $last_event=$event;
                        //log::add('blink_camera', 'debug', 'blink_camera->getLastEvent() init with first event'.$event['created_at']);
                    }
                    if ($last_event['created_at']<$event['created_at']) {
                        //log::add('blink_camera', 'debug', 'blink_camera->getLastEvent() more early :'.$event['created_at']);
                        $last_event=$event;
                    }
                }
            }
            if (isset($last_event)) {
                log::add('blink_camera', 'debug', 'blink_camera->getLastEvent() return an event:'.$last_event['created_at']);
                return $last_event;
            }
        }
        $jsonstr='[{"deleted":false,"device_id":"xxxxx","device_name":"xxxx","media":"xxxxxxx","thumbnail":"/plugins/blink_camera/medias/x0.png","created_at":"2019-01-01T00:00:01+0000"}]';
        return json_decode($jsonstr, true);
    }

    public function getLastEventDate()
    {
        if ($this->isConfigured()) {
			$event = $this->getLastEvent(false);
			$infoCmd=$this->getCmd(null, 'last_event');
            $previous=$infoCmd->execCmd();
            $dtim = date_create_from_format(blink_camera::FORMAT_DATETIME, $event['created_at']);
            $dtim=date_add($dtim, new DateInterval("PT".getTZoffsetMin()."M"));
			$new=date_format($dtim, blink_camera::FORMAT_DATETIME_OUT);
			if (isset($new) && $new!="" && $new>$previous) {
				log::add('blink_camera','debug','New event detected:'.$new. ' (previous:'.$previous.')');
                $this->checkAndUpdateCmd('last_event', $new);
            }
        }
	}
	public function refreshCameraInfos() {
		if ($this->isConfigured()) {
            $datas=$this->getCameraInfo();
            /* MAJ Température */
			$tempe=(float) $datas['camera_status']['temperature'];
			$blink_tempUnit=config::byKey('blink_tempUnit', 'blink_camera');
			if ($blink_tempUnit==="C") {
				$tempe =($tempe - 32) / 1.8;
			}
            $this->checkAndUpdateCmd('temperature', $tempe);
            /* MAJ Power */
            $power=(float) $datas['camera_status']['battery_voltage'];
            $this->checkAndUpdateCmd('power', ($power/100));
            /* MAJ WIFI */
            $wifi=(float) $datas['camera_status']['wifi_strength'];
			$this->checkAndUpdateCmd('wifi_strength', $wifi);
		}
    }
    
    public function getMediaDir()
    {
        return __DIR__.'/../../medias/'.$this->getId();
    }

    public function networkArm()
    {
        $network_id = $this->getConfiguration("network_id");
        if (self::getToken() && $this->isConfigured()) {
            $_tokenBlink=config::byKey('token', 'blink_camera');
            $_accountBlink=config::byKey('account', 'blink_camera');
            $_regionBlink=config::byKey('region', 'blink_camera');
            if (!$_tokenBlink=="" && !$_accountBlink=="" && !$_regionBlink=="") {
                $client = new GuzzleHttp\Client(['base_uri' => 'https://rest.'.$_regionBlink.'.immedia-semi.com']);
                $url="/network/$network_id/arm";
                $r = $client->request('POST', $url, [
                    'headers' => [
                        'Host'=> 'prod.immedia-semi.com',
                        'TOKEN_AUTH'=> $_tokenBlink
                    ]
                ]);
                $jsonrep= json_decode($r->getBody(), true);
               /* sleep(2);
                $this->getNetworkArmStatus();*/
                return true;
            }
        }
        return false;
    }
    public function networkDisarm()
    {
        $network_id = $this->getConfiguration("network_id");
        if (self::getToken() && $this->isConfigured()) {
            $_tokenBlink=config::byKey('token', 'blink_camera');
            $_accountBlink=config::byKey('account', 'blink_camera');
            $_regionBlink=config::byKey('region', 'blink_camera');
            if (!$_tokenBlink=="" && !$_accountBlink=="" && !$_regionBlink=="") {
                $client = new GuzzleHttp\Client(['base_uri' => 'https://rest.'.$_regionBlink.'.immedia-semi.com']);
                $url="/network/$network_id/disarm";
                $r = $client->request('POST', $url, [
                    'headers' => [
                        'Host'=> 'prod.immedia-semi.com',
                        'TOKEN_AUTH'=> $_tokenBlink
                    ]
                ]);
                $jsonrep= json_decode($r->getBody(), true);
               /* sleep(2);
                $this->getNetworkArmStatus();*/
                return true;
            }
        }
        return false;
    }
    public function getNetworkArmStatus()
    {
        $network_id = $this->getConfiguration("network_id");
        if (self::getToken() && $this->isConfigured()) {
            $_tokenBlink=config::byKey('token', 'blink_camera');
            $_accountBlink=config::byKey('account', 'blink_camera');
            $_regionBlink=config::byKey('region', 'blink_camera');
            if (!$_tokenBlink=="" && !$_accountBlink=="" && !$_regionBlink=="") {
                $client = new GuzzleHttp\Client(['base_uri' => 'https://rest.'.$_regionBlink.'.immedia-semi.com']);
                $url='/network/'.$network_id;
                $r = $client->request('GET', $url, [
                    'headers' => [
                        'Host'=> 'prod.immedia-semi.com',
                        'TOKEN_AUTH'=> $_tokenBlink
                    ]
                ]);
                $jsonrep= json_decode($r->getBody(), true);
                if ($jsonrep['network']['armed']===true) {
                    $this->checkAndUpdateCmd('arm_status', 1);
                } else {
                    $this->checkAndUpdateCmd('arm_status', 0);
                }
                return true;
            }
        }
        $this->checkAndUpdateCmd('arm_status', 0);
        return false;
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
        $this->setDisplay("width", "400px");
        $this->setDisplay("showNameOndashboard", 0);
    }

    public function postSave()
    {
        $info = $this->getCmd(null, 'arm_status');
        if (!is_object($info)) {
            $info = new blink_cameraCmd();
            $info->setName(__('Réseau armé ?', __FILE__));
        }
        $info->setLogicalId('arm_status');
        $info->setEqLogic_id($this->getId());
        $info->setType('info');
        $info->setIsVisible(true);
        $info->setTemplate('dashboard', 'default');
        $info->setDisplay("showNameOndashboard", 0);
		$info->setSubType('binary');
		$info->setOrder(1);
		$info->save();
        
        $info = $this->getCmd(null, 'thumbnail');
        if (!is_object($info)) {
            $info = new blink_cameraCmd();
            $info->setName(__('Vignette', __FILE__));
        }
        $info->setLogicalId('thumbnail');
        $info->setEqLogic_id($this->getId());
        $info->setType('info');
        $info->setIsVisible(true);
        $info->setTemplate('dashboard', 'default');
        $info->setDisplay("showNameOndashboard", 0);
		$info->setSubType('string');
		$info->setOrder(9);
		$info->save();

        $info = $this->getCmd(null, 'temperature');
        if (!is_object($info)) {
            $info = new blink_cameraCmd();
			$info->setName(__('Température', __FILE__));
			$info->setTemplate('dashboard', 'badge');
			$info->setDisplay("showNameOndashboard", 1);
			$info->setConfiguration('generic_type',"TEMPERATURE");
			$info->setConfiguration('historizeRound',"1");
		}
		$info->setConfiguration('generic_type',"TEMPERATURE");
		$info->setConfiguration('historizeRound',"1");
		$info->setUnite('°'.config::byKey('blink_tempUnit', 'blink_camera'));
		$info->setIsVisible(true);
        $info->setLogicalId('temperature');
        $info->setEqLogic_id($this->getId());
        $info->setType('info');
		$info->setSubType('numeric');
		$info->setOrder(2);
        $info->save();
        
        $info = $this->getCmd(null, 'power');
        if (!is_object($info)) {
            $info = new blink_cameraCmd();
			$info->setName(__('Pile', __FILE__));
			$info->setTemplate('dashboard', 'badge');
			$info->setDisplay("showNameOndashboard", 1);
			//$info->setConfiguration('generic_type',"TEMPERATURE");
			$info->setConfiguration('historizeRound',"2");
		}
		//$info->setConfiguration('generic_type',"TEMPERATURE");
		$info->setConfiguration('historizeRound',"2");
		$info->setUnite('V');
		$info->setIsVisible(true);
        $info->setLogicalId('power');
        $info->setEqLogic_id($this->getId());
        $info->setType('info');
		$info->setSubType('numeric');
		$info->setOrder(3);
		$info->save();
 
        $info = $this->getCmd(null, 'wifi_strength');
        if (!is_object($info)) {
            $info = new blink_cameraCmd();
			$info->setName(__('Puissance Wifi', __FILE__));
			$info->setTemplate('dashboard', 'badge');
			$info->setDisplay("showNameOndashboard", 1);
			//$info->setConfiguration('generic_type',"TEMPERATURE");
			$info->setConfiguration('historizeRound',"0");
		}
		//$info->setConfiguration('generic_type',"TEMPERATURE");
		$info->setConfiguration('historizeRound',"0");
		$info->setUnite('dB');
		$info->setIsVisible(true);
        $info->setLogicalId('wifi_strength');
        $info->setEqLogic_id($this->getId());
        $info->setType('info');
		$info->setSubType('numeric');
		$info->setOrder(4);
        $info->save();
        
        $info = $this->getCmd(null, 'last_event');
        if (!is_object($info)) {
            $info = new blink_cameraCmd();
            $info->setName(__('Dernier événement', __FILE__));
        }
        $info->setLogicalId('last_event');
        $info->setEqLogic_id($this->getId());
        $info->setType('info');
        $info->setIsVisible(true);
        $info->setTemplate('dashboard', 'default');
        $info->setDisplay("showNameOndashboard", 1);
		$info->setSubType('string');
		$info->setOrder(4);
        $info->save();
        
        $refresh = $this->getCmd(null, 'refresh');
        if (!is_object($refresh)) {
            $refresh = new blink_cameraCmd();
            $refresh->setName(__('Rafraichir', __FILE__));
        }
        $refresh->setEqLogic_id($this->getId());
        $refresh->setLogicalId('refresh');
        $refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->setOrder(100);
        $refresh->save();
        
        $arm_network = $this->getCmd(null, 'arm_network');
        if (!is_object($arm_network)) {
            $arm_network = new blink_cameraCmd();
            $arm_network->setName(__('Armer le réseau', __FILE__));
        }
        $arm_network->setEqLogic_id($this->getId());
        $arm_network->setLogicalId('arm_network');
        $arm_network->setType('action');
		$arm_network->setSubType('other');
		$arm_network->setOrder(10);
		$arm_network->setDisplay('icon','<i class="jeedom jeedom-lock-ferme"></i>'); 
		$arm_network->useIconAndName();
		$arm_network->save();
        
        $arm_network = $this->getCmd(null, 'disarm_network');
        if (!is_object($arm_network)) {
            $arm_network = new blink_cameraCmd();
            $arm_network->setName(__('Désarmer le réseau', __FILE__));
        }
        $arm_network->setEqLogic_id($this->getId());
        $arm_network->setLogicalId('disarm_network');
        $arm_network->setType('action');
		$arm_network->setSubType('other');
		$arm_network->setOrder(11);
		$arm_network->setDisplay('icon','<i class="jeedom jeedom-lock-ouvert"></i>'); 
		$arm_network->useIconAndName();
		$arm_network->save();
		

		$force_download = $this->getCmd(null, 'force_download');
        if (!is_object($force_download)) {
            $force_download = new blink_cameraCmd();
            $force_download->setName(__('Forcer le téléchargement', __FILE__));
        }
        $force_download->setEqLogic_id($this->getId());
        $force_download->setLogicalId('force_download');
        $force_download->setType('action');
		$force_download->setSubType('other');
		$force_download->setDisplay('icon','<i class="nature nature-planet5"></i>'); 
		$force_download->useIconAndName();
		$force_download->setOrder(13);
		$force_download->save();


		$newClip = $this->getCmd(null, 'new_clip');
        if (!is_object($newClip)) {
            $newClip = new blink_cameraCmd();
            $newClip->setName(__('Prendre une vidéo', __FILE__));
        }
        $newClip->setEqLogic_id($this->getId());
        $newClip->setLogicalId('new_clip');
        $newClip->setType('action');
		$newClip->setSubType('other');
		$newClip->setDisplay('icon','<i class="fa fa-video-camera"></i>'); 
		$newClip->useIconAndName();
		$newClip->setOrder(12);
		$newClip->save();

        $history = $this->getCmd(null, 'history');
        if (!is_object($history)) {
            $history = new blink_cameraCmd();
            $history->setName(__('Historique', __FILE__));
        }
        $history->setDisplay('icon','<i class="fa fa-folder-open"></i>'); 
        $history->setEqLogic_id($this->getId());
        $history->setLogicalId('history');
        $history->setType('action');
        $history->setSubType('other');
        $history->setOrder(14);
        $history->useIconAndName();
        $history->save();

        $this::refreshCameraInfos();
        $this::getLastEventDate();
		$this::getNetworkArmStatus();
        $this::emptyCacheWidget();
    }

    public function preUpdate()
    {
    }

    public function postUpdate()
    {
		$cmd = $this->getCmd(null, 'refresh'); 
		if (is_object($cmd)) { 
			 $cmd->execCmd();
		}
        //self::cronHourly($this->getId());
    }

    public function preRemove()
    {
        shell_exec('rm -rf ' . $this->getMediaDir());
    }

    public function postRemove()
    {
    }
    
    public function toHtml($_version = 'dashboard', $_fluxOnly = false)
    {
        if ($_version=="dashboard" && $this->getConfiguration("blink_dashboard_custom_widget")==="1") {
            if ($_fluxOnly) {
                $replace = $this->preToHtml($_version, array(), true);
            } else {
                $replace = $this->preToHtml($_version);
            }
            if (!is_array($replace)) {
                return $replace;
            }
            $version = jeedom::versionAlias($_version);
            $version2 = jeedom::versionAlias($_version, false);
            $action = '';
            $info = '';
           
            foreach ($this->getCmd() as $cmd) {
                if ($cmd->getIsVisible() == 1) {
                    if ($cmd->getLogicalId() != 'refresh') {
                        if ($cmd->getType() == 'action' && $cmd->getSubType() == 'other') {
                            $replaceCmd = array(
                                '#id#' => $cmd->getId(),
                                '#name#' => ($cmd->getDisplay('icon') != '') ? $cmd->getDisplay('icon') : $cmd->getName(),
                            );
                            if ($cmd->getDisplay('showNameOn' . $version2, 1) == 0) {
                                $replaceCmd['#hideCmdName#'] = 'display:none;';
                            }
                            if ($cmd->getDisplay('showIconAndName' . $version2, 0) == 1) {
                                $replaceCmd['#name#'] = $cmd->getDisplay('icon') . ' ' . $cmd->getName();
                            }
                            $action .= template_replace($replaceCmd, getTemplate('core', $version, 'blink_camera_action', 'blink_camera')) . ' ';
                        } else {
                            if ($cmd->getType() == 'info') {
                                $info .= $cmd->toHtml($_version);
                            } else {
                                $action .= $cmd->toHtml($_version);
                            }
                        }
                        
                        if ($cmd->getDisplay('forceReturnLineAfter', 0) == 1) {
                            $action .= '<br/>';
                        }
                    }
                }
            }
    
            $replace['#action#'] = $action;
            $replace['#info#'] = $info;
            $urlLine ='  <img src="#urlFile#" class="vignette" style="display:block;padding:5px;" data-eqLogic_id="#id#"/>';
            $replace['#urlFile#']=blink_camera::ERROR_IMG;
            if ($this->isConfigured()) {
                if (config::byKey('blink_dashboard_content_type', 'blink_camera')==="1") {
                    // On affiche la vignette de la caméra
                        $urlLine ='  <img src="#urlFile#" class="vignette" style="display:block;padding:5px;" data-eqLogic_id="#id#"/>';
                        $replace['#urlFile#']=$this->getCameraThumbnail();
                } else {
                    $temp=$this->getLastEvent(false);
                    //log::add('blink_camera', 'debug', 'blink_camera->toHtml() after last event '.$temp[$media_type]);
                    if (isset($temp) && isset($temp['created_at'])) {
                        if (config::byKey('blink_dashboard_content_type', 'blink_camera')==="3") {
                            //On affiche la video
                            $facteur= (float) config::byKey('blink_size_videos', 'blink_camera');
                            $tailleVideo=720*$facteur;
                            $dir= dirname(__FILE__).'/../../../../';
                            $media_type='media';
                            $urlLine ='<video class="displayVideo vignette" height="'.$tailleVideo.'" controls loop data-src="core/php/downloadFile.php?pathfile=#urlFile#" style="display:block;padding:5px;cursor:pointer"><source src="core/php/downloadFile.php?pathfile=#urlFile#">Your browser does not support the video tag.</video>';
                            $replace['#urlFile#']=urlencode($dir.self::getMedia($temp[$media_type], $replace['#id#'], blink_camera::getDateJeedomTimezone($temp['created_at'])));
                        } else {
                            //On affiche la vignette de la derniere video
                            $media_type='thumbnail';
                            $urlLine ='  <img src="#urlFile#" class="vignette" style="display:block;padding:5px;" data-eqLogic_id="#id#"/>';
                            $replace['#urlFile#']=self::getMedia($temp[$media_type], $replace['#id#'], blink_camera::getDateJeedomTimezone($temp['created_at']));
                        }
                    }
                }
                $replace['#urlLine#']=template_replace($replace, $urlLine);
            }
            $replace['#limite_nb_video#']="";
            $nbMax= (int) config::byKey('nb_max_video', 'blink_camera');
            if ($nbMax > 0) {
                $replace['#limite_nb_video#']="- ".$nbMax." dernières vidéos";
            }
            //log::add('blink_camera','debug','toHtml() REPLACE VALUES: '.print_r($replace,true));
            if ($this->isConfigured()) {
                if (!$_fluxOnly) {
                    return $this->postToHtml($_version, template_replace($replace, getTemplate('core', jeedom::versionAlias($version), 'blink_camera', 'blink_camera')));
                } else {
                    return template_replace($replace, getTemplate('core', jeedom::versionAlias($version), 'blink_camera_flux_only', 'blink_camera'));
                }
            } else {
                return $this->postToHtml($_version, template_replace($replace, getTemplate('core', jeedom::versionAlias($version), 'blink_camera_not_config', 'blink_camera')));
            }
        } else {
            return parent::toHtml($_version);
        }
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
        $eqLogics = self::byType('blink_camera', true);
        foreach ($eqLogics as $blink_camera) {
            if ($blink_camera->getIsEnable() == 1) {
                $blink_camera->emptyCacheWidget();
                /*$cmd = $blink_camera->getCmd(null, 'refresh');
                if (is_object($cmd)) {
                    $cmd->execCmd();
				}*/
            }
        }
    }

    /*     * **********************Getteur Setteur*************************** */
}

class blink_cameraCmd extends cmd
{
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */
    public function toHtml($_version = 'dashboard', $_options = '', $_cmdColor = null) {
        if ($this->getLogicalId()==='thumbnail') {
            $bl_cam=$this->getEqLogic();
            $urlLine ='  <img src="#urlFile#" class="vignette" style="display:block;padding:5px;" data-eqLogic_id="'.$bl_cam->getId().'"/>';
            $urlFile=blink_camera::ERROR_IMG;
            if ($bl_cam->isConfigured()) {
                if (config::byKey('blink_dashboard_content_type', 'blink_camera')==="1") {
                    // On affiche la vignette de la caméra
                    $urlFile=$bl_cam->getCameraThumbnail();
                } else {
                    $temp=$bl_cam->getLastEvent(false);
                    if (isset($temp) && isset($temp['created_at'])) {
                        if (config::byKey('blink_dashboard_content_type', 'blink_camera')==="3") {
                            //On affiche la video
                            $facteur= (float) config::byKey('blink_size_videos', 'blink_camera');
                            $tailleVideo=720*$facteur;
                            $dir= dirname(__FILE__).'/../../../../';
                            $media_type='media';
                            $urlLine ='<video class="displayVideo vignette" height="'.$tailleVideo.'"  data-eqLogic_id="'.$bl_cam->getId().'" controls loop data-src="core/php/downloadFile.php?pathfile=#urlFile#" style="display:block;padding:5px;cursor:pointer"><source src="core/php/downloadFile.php?pathfile=#urlFile#">Your browser does not support the video tag.</video>';
                            $urlFile=urlencode($dir.blink_camera::getMedia($temp[$media_type], $bl_cam->getId(), blink_camera::getDateJeedomTimezone($temp['created_at'])));
                        } else {
                            //On affiche la vignette de la derniere video
                            $media_type='thumbnail';
                            $urlFile=blink_camera::getMedia($temp[$media_type], $bl_cam->getId(), blink_camera::getDateJeedomTimezone($temp['created_at']));
                        }
                    }
                }
            }
            $replace['#urlFile#']=$urlFile;
            return template_replace($replace, $urlLine);
        } else if ($this->getLogicalId()==='history') {
            $bl_cam=$this->getEqLogic();
            if ($_cmdColor === null && $version != 'scenario') {
                $eqLogic = $this->getEqLogic();
                $vcolor = ($version == 'mobile') ? 'mcmdColor' : 'cmdColor';
                if ($eqLogic->getPrimaryCategory() == '') {
                    $_cmdColor = jeedom::getConfiguration('eqLogic:category:default:' . $vcolor);
                } else {
                    $_cmdColor = jeedom::getConfiguration('eqLogic:category:' . $eqLogic->getPrimaryCategory() . ':' . $vcolor);
                }
            }
            $name_display =($this->getDisplay('icon') != '') ? $this->getDisplay('icon') : $this->getName();
            if ($this->getDisplay('showIconAndName' . $_version, 0) == 1) {
                $name_display = $this->getDisplay('icon') . ' ' . $this->getName();
            }
            $result='<span class="cmd reportModeHidden cmd-widget camera_history" style="display: inline !important;margin-right: 2px;" data-type="action" data-subtype="other" data-cmd_id="'.$this->getId().'" data-cmd_uid="'.'cmd' . $this->getId() . eqLogic::UIDDELIMITER . mt_rand() . eqLogic::UIDDELIMITER.'" data-version="'.$_version.'" data-eqLogic_id="'.$bl_cam->getId().'">';
            $result.='<a class="btn btn-sm btn-default action cmdName tooltips" title="'.$this->getName().'" style="background-color:'.$_cmdColor.' !important;border-color : transparent !important;margin-top: 2px;">'.$name_display.'</a>';
            $result.='<script>$(\'.camera_history[data-eqLogic_id="'.$bl_cam->getId().'"]\').off().on(\'click\', function () {';
            $result.='$(\'#md_modal\').dialog({title: "Historique '.$bl_cam->getName().'"});';
            $result.='$(\'#md_modal\').load(\'index.php?v=d&plugin=blink_camera&modal=blink_camera.history&id='.$bl_cam->getId().'\').dialog(\'open\');});';
            $result.="</script>";

            return $result;
        } else {
            return parent::toHtml($_version, $_options, $_cmdColor);
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
                // Nettoyage des fichiers du dossier medias
				//$eqlogic->forceCleanup();
				//$eqlogic->getLastEvent();
                //rafraichissement de la datetime du dernier event
                $eqlogic->getLastEventDate();

                // Refresh du statut du reseau
				$eqlogic->getNetworkArmStatus();
				
                $eqlogic->refreshCameraInfos();
				break;
			case 'force_download':
                // Nettoyage des fichiers du dossier medias
				$eqlogic->forceCleanup(true);
                 
                //rafraichissement de la datetime du dernier event
                $eqlogic->getLastEventDate();

                // Refresh du statut du reseau
                $eqlogic->getNetworkArmStatus();
                break;
			
			case 'new_clip':
				// Nettoyage des fichiers du dossier medias
				$eqlogic->requestNewMedia("clip");
				//rafraichissement de la datetime du dernier event
				//$eqlogic->getLastEventDate();
				break;
	
            case 'arm_network':
                $eqlogic->networkArm();
                break;

            case 'disarm_network':
                $eqlogic->networkDisarm();
                break;
        }
    }
    /*     * **********************Getteur Setteur*************************** */
}
