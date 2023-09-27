<?php include_file('desktop', 'blink_camera', 'css', 'blink_camera');?>
<?php
if (!isConnect()) {
    throw new Exception('{{401 - Accès non autorisé}}');
}


if (!blink_camera::isConnected() || !blink_camera::getToken(false)) {
    throw new Exception('{{Erreur de connexion à votre compte Blink}}');
   
}

?>
<script>
$('.ui-dialog-titlebar-close').on('click', function (e) {
    var vars = getUrlVars()
    var url = 'index.php?'
    for (var i in vars) {
        if (i != 'id' && i != 'saveSuccessFull' && i != 'removeSuccessFull') {
            url += i + '=' + vars[i].replace('#', '') + '&'
        }
    }
    jeedomUtils.loadPage(url)
});
$('.bt_return_cfg').on('click', function (e) {
        var vars = getUrlVars()
    var url = 'index.php?'
    for (var i in vars) {
        if (i != 'id' && i != 'saveSuccessFull' && i != 'removeSuccessFull') {
            url += i + '=' + vars[i].replace('#', '') + '&'
        }
    }
    jeedomUtils.loadPage(url)
});
</script>

<div class="panel panel-success">
<div class="panel-heading"><i class="fa fa-table"></i> {{Recherche des caméras...}} </div>
</div>
<form class="form-horizontal">
<fieldset>
<?php
   
    $eqLogics = blink_camera::byType('blink_camera', true);
    $config = blink_camera::getAccountConfigDatas2(false,false);
    if ($config==null) {
        throw new Exception(__('Unable to load Blink Camera configuration.', __FILE__));
    }
      $return=json_encode($config);
      foreach ($config['networks'] as $network) {
        $nbDevice=0;
        $deviceList='<div class="col-sm6">';
        foreach ($network['camera'] as $camera) {
            $toCreate=true;
            $nbDevice++;
            foreach ($eqLogics as $existingCamera) {
              //pour debug de creation :   
                //if ($camera['device_name'] !=='Atelier' && $existingCamera->getConfiguration('network_id')==$network['network_id'] && $existingCamera->getConfiguration('camera_id')==$camera['device_id']) {
                if ($existingCamera->getConfiguration('network_id')==$network['network_id'] && $existingCamera->getConfiguration('camera_id')==$camera['device_id']) {
                    $toCreate=false;
                }
            }
            if ($toCreate) {

                $newCamera=new blink_camera();
                $newCamera->setEqType_name('blink_camera');
                $newCamera->setName($network['network_name']." - ".$camera['device_name']);
                $newCamera->setConfiguration('network_id',$network['network_id']);
                $newCamera->setConfiguration('camera_id',$camera['device_id']);
                $newCamera->save();
                $deviceList=$deviceList.'<div><i class="fa fa-video-slash icon_red"></i><b>'.$camera['device_name'].' : {{caméra ajoutée}}</b></div>';
            } else {
                $deviceList=$deviceList.'<div><i class="fa fa-video icon_green"></i> '.$camera['device_name'].' : {{caméra existe déja}}</div>';
            }
        }
        $deviceList=$deviceList.'</div>';
        //echo '<div class="row">';
        echo '<div class="form-group">';
        echo '<label class="col-sm-4 control-label"><i class="fa fa-layer-group"></i> '.$network['network_name'].' - '.$nbDevice.' {{caméra(s)}} </label>';
        echo "$deviceList";
        echo '</div>';
      } 
?>
</fieldset>
</form>
<button class="btn btn-info btn-bg  bt_return_cfg" style="color : white" ><i class="icon securite-exit7"></i> {{Fermer}}</button>
</div>
