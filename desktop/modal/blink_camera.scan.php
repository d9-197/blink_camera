<?php include_file('desktop', 'blink_camera', 'css', 'blink_camera');?>
<?php include_file('desktop', 'blink_camera_config2', 'js', 'blink_camera');?>
<?php
if (!isConnect()) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

$email="";

?>
<script>
    needrefresh=0;
    document.querySelector('.btClose').addEventListener('click', function (e) {
    if (needrefresh==1) {
        var vars = getUrlVars()
        var url = 'index.php?'
        for (var i in vars) {
            if (i != 'id' && i != 'saveSuccessFull' && i != 'removeSuccessFull') {
                url += i + '=' + vars[i].replace('#', '') + '&'
            }
        }
        jeedomUtils.loadPage(url)
    }
});
</script>
<div class="float-right"><button class="btn btn-info btn-bg bt_return_cfg" style="color : white" ><i class="icon securite-exit7"></i> {{Fermer}}</button></div>

<hr>
<div class="panel panel-info">
    <div class="panel-heading"><i class="fa fa-table"></i> {{Recherche des caméras...}} </div>
</div>
</div>
<form class="form-horizontal">
<fieldset>
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
      foreach ($config['emails'] as $email) {
        echo '<div class="panel panel-secondary">';
        //echo '<div class="form-group h3">';
        echo '<div class="panel-heading">';
        echo '<i class="icon mdi-shield-account"></i> {{compte}} '.$email['email'];
        echo '</div>';
        echo '<input type="hidden" id="email_'.$cptAccount.'" value="'.$email['email'].'"/>';
        $deviceList='';
        //echo '<script>checkConnexionBlink('.$cptAccount.')</script>';
        foreach ($email['networks'] as $network) {
            $nbDevice=0;
            $deviceList='<div class="col-sm6">';
            foreach ($network['camera'] as $camera) {
                $toCreate=true;
                $nbDevice++;
                $eqLogics = blink_camera::byType('blink_camera', false);
                foreach ($eqLogics as $existingCamera) {
                    if ($existingCamera->getConfiguration('network_id')==$network['network_id'] && $existingCamera->getConfiguration('camera_id')==$camera['device_id']) {
                        $toCreate=false;
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
                    $deviceList=$deviceList.'<div><i class="fa fa-video-slash icon_red"></i><b>'.$camera['device_name'].' : {{caméra ajoutée}}  </b><i class="fa fa-video icon_green"></i></div>';
                    $nbNew++;
                } else {
                    $deviceList=$deviceList.'<div><i class="fa fa-video icon_green"></i> '.$camera['device_name'].' : {{caméra existe déja}}</div>';
                }
            }
            $deviceList=$deviceList.'</div>';
            echo '<div class="form-group">';
            echo '<label class="col-sm-4 control-label"><i class="fa fa-layer-group"></i> '.$network['network_name'].' - '.$nbDevice.' {{caméra(s)}} </label>';
            echo "$deviceList";
            echo '</div>';
            }
            $cptAccount++;
      } 
      if ($nbNew>0) {
        echo "<script>needrefresh=1;</script>";
      }
?>
</fieldset>
</form>
<button class="btn btn-info btn-bg  bt_return_cfg" style="color : white" ><i class="icon securite-exit7"></i> {{Fermer}}</button>
</div>

