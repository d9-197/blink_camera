<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
//include_file('desktop', 'blink_camera_config', 'js', 'blink_camera');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>
<form class="form-horizontal">
    <div style ="float: right; width:300px;margin: 0px; border-radius: 5px; background-color: transparent; padding: 1em;border:0">
        <span style ="vertical-align : top;">
            <a href="https://fr.tipeee.com/duke-9" target="_new">
                <span style ="vertical-align : center; align:right"><img width="30px" src="plugins/blink_camera/plugin_info/tipeee_tip_btn.svg"/>&nbsp;&nbsp;Merci aux tipeurs qui soutiennent les développements</span>
                <!--iframe style ="margin: 0px; border-radius: 5px; background-color: transparent;padding: 1em;border:0" allowtransparency = "true" src="https://fr.tipeee.com/widgets/OwIPwBrn6nRpx3LOa74tH0tRSEHwZz7ULWeP24z6AU7oEpOFiSagO5NFo1erbqPm?api_key=E3ms55Lt3Mp826M7eSHhmLDH2oAd2KDcqMipf3H7XQ1G5QgRJLbsA6HKrZqmcgw3&v=1693497960833"></iframe-->
            </a>
        </span>
    </div>
    <fieldset>
        <h4 class="icon_blue"><i class="fa fa-user"></i> {{Compte Blink}}</h4>
        <!--div class="form-group">
             <label class="col-lg-6 control-label">{{Email}}</label>
            <div class="col-lg-3">
                <input class="configKey form-control" id="email" data-l1key="param1" />
            </div>
            <div class="col-lg-1">
                <a id="bt_test_blink" class="btn btn-success btn-xs">{{Sauvegarder et tester la connexion Blink}}</a>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-6 control-label">{{Mot de passe}}</label>
            <div class="col-lg-3">
                <input type="password" class="configKey form-control" data-l1key="param2"/>
            </div>
            <div class="col-lg-3">
                <div id="div_test_blink_result"></div>
            </div>
        </div>

        <div id="verifdiv">

        <div class="form-group">
            <label class="col-lg-9 control-label text-danger">{{Vous allez recevoir un SMS de Blink avec un code pin, vous devez le renseigner ici et cliquer sur le bouton "Envoyer".}}</label>
        </div>
        <div class="form-group">
            <label class="col-lg-6 control-label">{{Code PIN de vérification}}</label>
            <div class="col-lg-3">
                <input  class="form-control" id="pincode"/>
            </div>
            <div class="col-lg-3">
                <a id="bt_verifypin" class="btn btn-success btn-xs">{{Envoyer}}</a>
            </div>
            <div id ="pinstatus"></div>
        </div>
        </div-->
        <div class="form-group">
            <label class="col-lg-6 control-label">{{Unité de température}}</label>
            <div class="col-lg-3">
                <select  class="configKey form-control" data-l1key="blink_tempUnit">
                    <option value="C">{{° C}}</option>
                    <option value="F">{{° F}}</option>
                </select>
            </div>
        </div>
        <h4 class="icon_blue"><i class="fa fa-lock"></i> {{Sécurité}}</h4>
        <div class="form-group" id="medias_security">
            <label class="col-lg-8 control-label">{{Bloquer l'accès aux URLs des vidéos et images sans être authentifié dans Jeedom ?}}</label>
            <div class="col-lg-1">
                <input  type="checkbox"class="configKey form-control" data-l1key="medias_security"/>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-6 control-label">{{Adresse de Jeedom à utiliser pour les URL}}</label>
            <div class="col-lg-3">
                <select  class="configKey form-control" data-l1key="blink_base_url">
                    <option value="internal">{{Interne}}</option>
                    <option value="external">{{Externe}}</option>
                </select>
            </div>
        </div>

        <h4 class="icon_blue"><i class="fa fa-eye"></i> {{Widget}}</h4>
        <div class="form-group">
            <label class="col-lg-6 control-label">{{Contenu de la vignette}}</label>
            <div class="col-lg-3">
                <select  id="thumb_type_select" class="configKey form-control" data-l1key="blink_dashboard_content_type">
                    <option value="1">{{Vignette de la caméra}}</option>
                    <option value="2">{{Vignette de la dernière vidéo}}</option>
                    <option value="3">{{Dernière vidéo}}</option>
                </select>
            </div>
        </div>
        <div class="form-group" id="fallback_thumb">
            <label class="col-lg-7 control-label">{{Afficher la vignette de caméra s'il n'y a pas de vidéo ?}}</label>
            <div class="col-lg-1">
                <input  id='fallback_checkbox' type="checkbox"class="configKey form-control" data-l1key="fallback_to_thumbnail"/>
            </div>
            <div id="warning_thumb" class="warning ">
            {{Les vignettes de vidéos ne sont pas disponibles si vous utilisez le stockage local (USB).}}
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-6 control-label">{{Taille de la vignette}}</label>
            <div class="col-lg-3">
                <select  class="configKey form-control" data-l1key="blink_size_thumbnail">
                    <option value="-1.0">{{Largeur du widget}}</option>
                    <option value="0.1">10%</option>
                    <option value="0.2">20%</option>
                    <option value="0.3">30%</option>
                    <option value="0.4">40%</option>
                    <option value="0.5">50%</option>
                </select>
            </div>
        </div>
        
        <h4 class="icon_blue"><i class="fa fa-folder-open"></i> {{Vue historique}}</h4>
        <div class="form-group">
            <label class="col-lg-6 control-label">{{Nombre maximum de vidéos téléchargées}}</label>
            <div class="col-lg-3">
                <input type="number" class="configKey form-control" data-l1key="nb_max_video" min="0" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-6 control-label">{{Taille des aperçus des vidéos}}</label>
            <div class="col-lg-3">
                <select  class="configKey form-control" data-l1key="blink_size_videos">
                    <option value="0.1">10%</option>
                    <option value="0.2">20%</option>
                    <option value="0.3">30%</option>
                    <option value="0.4">40%</option>
                    <option value="0.5">50%</option>
                    <option value="0.6">60%</option>
                    <option value="0.7">70%</option>
                    <option value="0.8">80%</option>
                    <option value="0.9">90%</option>
                    <option value="1">100%</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-6 control-label">{{Mode eco}}</label>
            <div class="col-lg-1">
                <input  id='mode_eco_checkbox' type="checkbox"class="configKey form-control" data-l1key="mode_eco"/>
            </div>
            <label class="col-lg-5">{{Mode eco desc}}</label>
        </div>
        <h4 class="icon_blue"><i class="fa fa-lock"></i> {{Sauvegarde}}</h4>
        <div class="form-group" id="medias_backup">
            <label class="col-lg-6 control-label">{{Inclure les vidéos/images des caméras dans la sauvegarde Jeedom ?}}</label>
            <div class="col-lg-1">
                <input  type="checkbox"class="configKey form-control" data-l1key="include_medias_in_backup"/>
            </div>
            <label class="col-lg-5">{{backup desc}}</label>
        </div>
  </fieldset>
</form>
<script>
    /*function checkConnexionBlink() {
//        alert ($email);
        $.ajax({
                    type: "POST",
                    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
                    data: {
                        action: "test_blink",
                        email : document.getElementById("email").value,
                    },
                    dataType: 'json',
                    error: function(request, status, error) {
                        handleAjaxError(request, status, error,$('#div_alert'));
                    },
                    success: function(data) {
                        $res = JSON.parse(JSON.parse(data.result));
                        if ($res.token == "true") {
                            $.fn.showAlert({message: "{{Connexion à votre compte Blink OK}}", level: 'info'});
                            $('#verifdiv').hide();
                            $('.blink_cfg').show();
                            //checkBlinkCameraConfig();
                            sessionStorage.setItem("blink_camera_refresh","REFRESH");
                        } else if ($res.token == "verif") {
                            $.fn.showAlert({message: "{{Connexion à votre compte Blink OK mais un code de vérification est nécessaire}}", level: 'warning'});
                            //$.fn.showAlert({message: "{{Connexion à votre compte Blink OK - Email de vérification nécessaire}}", level: 'info'});
                            $('#verifdiv').show();
                        } else if ($res.token == "limit") {
                            $.fn.showAlert({message: "{{limite connexion}}", level: 'danger'});
                            $('#verifdiv').hide();
                            //$.fn.showAlert({message: "{{Erreur de connexion à votre compte Blink}}", level: 'danger'});
                            $('.blink_cfg').hide();
                        } else {
                            $.fn.showAlert({message: "{{Erreur de connexion à votre compte Blink}}", level: 'danger'});
                            $('#verifdiv').hide();
                            //$.fn.showAlert({message: "{{Erreur de connexion à votre compte Blink}}", level: 'danger'});
                            $('.blink_cfg').hide();
                        }
                        return;
                    }
                });
    }; 
    function reinitConfig() {
        $.ajax({
                    type: "POST",
                    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
                    data: {
                        action: "reinitConfig",
                    },
                    dataType: 'json',
                    error: function(request, status, error) {
                        handleAjaxError(request, status, error,$('#div_alert'));
                    },
                    success: function(data) {
                        checkConnexionBlink(); 
                        return;
                    }
                });
    }; 
   // $('#verifdiv').hide();
    $('#bt_verifypin').on('click', function() {
        $.ajax({
            type: 'POST',
            url: 'plugins/blink_camera/core/ajax/blink_camera.ajax.php',
            data: {
                action: 'verifyPinCode',
                pin: document.getElementById("pincode").value,
                email: document.getElementById("email").value,

            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'verifyPinCode' !<br>Erreur lors l'envoi du code de vérification à Blink.");
            },
            success: function (json_res) {
                checkConnexionBlink();   
            }
        });
    });
    $('#bt_test_blink').on('click', function() {
            $('#div_test_blink_result').hideAlert();
            //savePluginConfig();
			jeeFrontEnd.plugin.savePluginConfig();
            jeedomUtils.sleep(1000);
            checkConnexionBlink();

    })
    $('#bt_reinit_blink').on('click', function() {
            reinitConfig();

    })
    */  
        document.querySelector('#thumb_type_select').addEventListener('change', function(event) {
            if (document.querySelector('#thumb_type_select').value==2 || document.querySelector('#thumb_type_select').value==3) {
                document.querySelector('#fallback_thumb').style.display = 'block';
            } else {
                document.querySelector('#fallback_thumb').style.display = 'none';
            }
            if (document.querySelector('#thumb_type_select').value==2 ) {
                document.querySelector('#warning_thumb').style.display = 'block';
                document.querySelector('#fallback_checkbox').checked = true;
            } else {
                document.querySelector('#warning_thumb').style.display = 'none';
            }
        })
    //checkConnexionBlink();
    </script>
<?php include_file('desktop', 'blink_camera', 'js', 'blink_camera');?>
