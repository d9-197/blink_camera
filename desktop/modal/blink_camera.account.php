<?php include_file('desktop', 'blink_camera', 'css', 'blink_camera');?>


<?php
if (!isConnect()) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

?>
<script>
document.querySelector('.btClose').addEventListener('click', function (e) {
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

<button class="btn btn-info btn-bg  bt_return_cfg" style="color : white" ><i class="icon securite-exit7"></i> {{Fermer}}</button>
<HR>
<div class="panel panel-info">
        <div class="panel-heading"><i class="fa fa-table"></i> {{Ajouter un compte Blink}} </div>
    </div>
<form class="form-horizontal">
<fieldset>

    <div class="form-group">
        <label class="col-sm-3 control-label">{{Email}}</label>
        <div class="col-sm-3">
            <input id="new_email" type="text" class="blink_account form-control"/>
        </div>
        <div class="col-lg-3">
            <a id="bt_new_account" class="btn btn-success btn-xs" onclick="addAccount(document.getElementById('new_email').value)">{{Ajouter un compte}}</a>
        </div>
        <div class="col-lg-3">
            <a id="bt_reinit" class="btn btn-success btn-xs" onclick="reinitAccounts()">{{Supprimer tous les comptes}}</a>
        </div>
    </div>
    </fieldset>
</form>
<HR>
    <div class="panel panel-info">
        <div class="panel-heading"><i class="fa fa-table"></i> {{Comptes Blink}} </div>
    <?php
    $eqLogics = blink_camera::byType('blink_camera', false);
    $config = blink_camera::getAccountConfigDatas(false,false);
    if ($config==null) {
        throw new Exception(__('Unable to load Blink Camera configuration.', __FILE__));
    }
      $return=json_encode($config);
      $cptAccount=0;
      ?>
            <form class="form-horizontal">
                <fieldset> 
                <?php 
                foreach ($config['emails'] as $email) {
                $cryptedPwd=blink_camera::getConfigBlinkAccount($email['email'],'pwd');
                $pwd=utils::decrypt($cryptedPwd);
                ?>
                    <div class="form-group">
                        <div class="col-sm-3">
                            <input id="email_<?=$cptAccount?>" disabled  class="blink_account form-control" placeholder="{{Compte Blink}}"/>
                        </div>
                        <label class="col-sm-2 control-label">{{Mot de passe}}</label>
                        <div class="col-sm-3">
                            <input id="pwd_<?=$cptAccount?>" type="password" class="blink_pwd form-control" placeholder="{{Mot de Passe}}"/>
                        </div>
                        <div class="col-sm-2">
                            <a id="bt_verify_pwd_<?=$cptAccount?>" class="btn btn-success btn-xs" onclick="checkConnexionBlink(<?=$cptAccount?>)">{{Tester}}</a>
                        </div>
                        <div class="col-sm-2">
                            <a id="bt_del_account_<?=$cptAccount?>" class="btn btn-danger btn-xs" onclick="removeConfigAccount(document.getElementById('email_<?=$cptAccount?>').value)">{{Supprimer le compte}}</a>
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
                        document.querySelector('#verifdiv_<?=$cptAccount?>').unseen();
                        checkConnexionBlink(<?=$cptAccount?>);
                    </script>

                <?php
                        $cptAccount++;
                    } 
                ?>
                </fieldset>
            </form>
            <br>
        </div>
    </div>

<button class="btn btn-info btn-bg  bt_return_cfg" style="color : white" ><i class="icon securite-exit7"></i> {{Fermer}}</button>

<?php include_file('desktop', 'blink_camera_config2', 'js', 'blink_camera');?>
