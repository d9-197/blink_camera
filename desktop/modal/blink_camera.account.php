<?php include_file('desktop', 'blink_camera', 'css', 'blink_camera');?>
<?php include_file('desktop', 'blink_camera_config2', 'js', 'blink_camera');?>


<?php
if (!isConnect()) {
    throw new Exception('{{401 - Accès non autorisé}}');
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
<div class="panel-heading"><i class="fa fa-table"></i> {{Comptes Blink}} </div>
</div>
<form class="form-horizontal">
<fieldset>
<?php
   
    $eqLogics = blink_camera::byType('blink_camera', false);
    $config = blink_camera::getAccountConfigDatas(false,false);
    if ($config==null) {
        throw new Exception(__('Unable to load Blink Camera configuration.', __FILE__));
    }
      $return=json_encode($config);
      $cptAccount=0;
      foreach ($config['emails'] as $email) {
        echo '<div class="form-group">';
        echo '<label class="col-sm-4 control-label"><i class="icon mdi-shield-account"></i> '.$email['email'].'</label>';
        echo '</div>';
        /*echo '<table>';
        echo '<TR><TH>{{key}}</TH><TH>{{value}}</TH></TR>';

        foreach (blink_camera::getConfigBlinkAccountAll($email['email']) as $key=>$value) {
            if ($key!='email') {
                echo '<TR><TD>'.$key.'</TD>';
                echo '<TD>'.$value.'</TD></TR>';
            }
        }
        echo '</table>';*/
        $cryptedPwd=blink_camera::getConfigBlinkAccount($email['email'],'pwd');
        $pwd=utils::decrypt($cryptedPwd);
    ?>
    <form class="form-horizontal">
        <fieldset> 
            <div class="form-group">
                <label class="col-sm-3 control-label">{{Email}}</label>
                <div class="col-sm-3">
                    <input id="email_<?=$cptAccount?>" type="text" class="blink_account form-control" placeholder="{{compte}}"/>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">{{Mot de passe}}</label>
                <div class="col-sm-3">
                    <input id="pwd_<?=$cptAccount?>" type="password" class="blink_pwd form-control" placeholder="{{mdp}}"/>
                </div>
                <div class="col-lg-3">
                    <a id="bt_save_pwd_<?=$cptAccount?>" class="btn btn-success btn-xs" onclick="savePwd(<?=$cptAccount?>)">{{Sauvegarder le mot de passe}}</a>
                </div>
                <div class="col-lg-3">
                    <a id="bt_verify_pwd_<?=$cptAccount?>" class="btn btn-success btn-xs" onclick="checkConnexionBlink(<?=$cptAccount?>)">{{Tester}}</a>
                </div>
            </div>
            <div id="verifdiv_<?=$cptAccount?>">
                <div class="form-group">
                    <label class="col-lg-9 control-label text-danger">{{Vous allez recevoir un SMS de Blink avec un code pin, vous devez le renseigner ici et cliquer sur le bouton "Envoyer".}}</label>
                </div>
                <div class="form-group">
                    <label class="col-lg-3 control-label">{{Code PIN de vérification}}</label>
                    <div class="col-lg-3">
                        <input  class="form-control" id="pincode_<?=$cptAccount?>"/>
                    </div>
                    <div class="col-lg-3">
                        <a id="bt_verifypin_<?=$cptAccount?>" class="btn btn-success btn-xs" onclick="verifyPin(<?=$cptAccount?>)">{{Envoyer}}</a>
                    </div>
                </div>
            </div>
            <script>
                document.getElementById("email_<?=$cptAccount?>").value='<?=$email['email']?>';
                document.getElementById("pwd_<?=$cptAccount?>").value='<?=$pwd?>';
                checkConnexionBlink(<?=$cptAccount?>);
            </script>
        </fieldset>
    </form>
<?php
        $cptAccount++;
      } 
?>
</fieldset>
</form>
<button class="btn btn-info btn-bg  bt_return_cfg" style="color : white" ><i class="icon securite-exit7"></i> {{Fermer}}</button>
</div>
