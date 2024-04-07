<?php include_file('desktop', 'blink_camera', 'css', 'blink_camera');?>
<?php include_file('desktop', 'blink_camera_config2', 'js', 'blink_camera');?>
<?php
if (!isConnect()) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

$email="";

?>
<?php
    $config = blink_camera::getAccountConfigDatas(false,false);
    if ($config==null) {
        throw new Exception(__('Unable to load Blink Camera configuration.', __FILE__));
    }
    blink_camera::logdebug("blink_camera.scan - config: ". print_r($config,true));
    if (count($config['emails'])==0) {
        throw new Exception(__('Configurez un compte Blink avant.', __FILE__));
    }
    $nbNew=0;
    $return=json_encode($config);
    $cptAccount=0;
    echo '<TABLE width="100%"><TR valign="top">';
    foreach ($config['emails'] as $email) {
        if ($cptAccount!=0) {
            echo '<TD width="20px">&nbsp;</TD>';
        }
        echo '<TD><div class="panel panel-info" style="width:100%;text-align: center !important;">';
        echo '  <div class="label-primary panel-title panel-heading" style="width:auto;text-align: center !important;">';
        echo '      <i class="icon mdi-shield-account"></i> {{compte}} '.$email['email'];
        echo '  </div>';
        echo '  <input type="hidden" id="email_'.$cptAccount.'" value="'.$email['email'].'"/>';
        //echo '  <div class="panel">';
        foreach ($email['networks'] as $network) {
            echo '      <div style="width:100%;text-align: center !important;"><label style="width:100%;text-align: center !important;" class="label-success"><i class="fa fa-layer-group"></i> '.$network['network_name'].' - '.count($network['camera']).' {{caméra(s)}}</label></div>';
            echo '      <div style="heigth:20px;text-align: center !important;">&nbsp;</div>';
            foreach ($network['camera'] as $camera) {
                echo '       <span style="text-align: center !important;">';
                $eqLogics = blink_camera::byType('blink_camera', false);
                $toCreate=true;
                foreach ($eqLogics as $existingCamera) {
                    if ($existingCamera->getConfiguration('network_id')==$network['network_id'] && $existingCamera->getConfiguration('camera_id')==$camera['device_id']) {
                        $toCreate=false;
                        $cameraId=$existingCamera->getId();
                    }
                }
                if ($toCreate) {
                    $newCamera=new blink_camera();
                    $newCamera->setEqType_name('blink_camera');
                    $newCamera->setName($camera['device_name']);
                    $newCamera->setConfiguration('email',$email['email']);
                    $newCamera->setConfiguration('network_id',$network['network_id']);
                    $newCamera->setConfiguration('camera_id',$camera['device_id']);
                    $newCamera->save();
                    $cameraId=$newCamera->getId();
                    echo '              <button id="camera_'.$cameraId.'" class="btn btn-success btn-bg"><i class="icon kiko-medical-cross icon_orange"></i> '.$camera['device_name'].' <i class="icon kiko-medical-cross icon_orange"></i></button> : {{caméra ajoutée}}';
                } else {
                    echo '              <button id="camera_'.$cameraId.'" class="btn btn-primary btn-bg"><i class="fa fa-video icon_green"></i> '.$camera['device_name'].'</button> : {{caméra existe déja}}';
                }
                echo "              <script>document.querySelector('#camera_".$cameraId."').addEventListener('click',function (event) {event.preventDefault(); loadCameraPage(".$cameraId.")});</script>";
                echo '          </span>';
            }
            echo '      <div style="heigth:20px;text-align: center !important;">&nbsp;</div>';
            //echo '      </div>';
        }
        echo '</div></TD>';
        $cptAccount++;
    } 
    echo '</TR></TABLE>';

?>
</div>
<script>
    if (document.querySelector('.btClose')!=null) {
        document.querySelector('.btClose').addEventListener('click', reloadParentPage,{ once: true });
    }
</script>
