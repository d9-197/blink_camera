<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('blink_camera');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un équipement}}</a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
                blink_camera::getToken();
foreach ($eqLogics as $eqLogic) {
    $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '" style="' . $opacity .'"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
}
            ?>
           </ul>
       </div>
   </div>

   <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
  <legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
  <div class="eqLogicThumbnailContainer">
    <div class="cursor eqLogicAction" data-action="add" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
        <i class="fa fa-plus-circle" style="font-size : 6em;color:#00A9EC;"></i>
        <br>
        <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#00A9EC;">{{Ajouter}}</span>
      </div>
      <div class="cursor eqLogicAction" data-action="gotoPluginConf" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
        <i class="fa fa-wrench" style="font-size : 6em;color:#767676;"></i>
        <br>
        <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676">{{Configuration}}</span>
    </div> 
  </div>
  <legend><i class="fa fa-table"></i> {{Mes équipements}}</legend>
<div class="eqLogicThumbnailContainer">
    <?php
    

foreach ($eqLogics as $eqLogic) {
                $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
                echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="text-align: center; background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
                echo '<img src="' . $plugin->getPathImgIcon() . '" height="105" width="95" />';
                echo "<br>";
                echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">' . $eqLogic->getHumanName(true, true) . '</span>';
                echo '</div>';
            }
?>
</div>
</div>

<div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
<?php
$datas=blink_camera::getAccountConfigDatas2(false);
$erreurBlink=false;
//log::add('blink_camera', 'debug', 'blink_camera.php $datas :'.print_r($datas, true));
if ($datas['message']) {
    echo '<script>';
    echo 'var tbl_reseau = [{}];';
    echo 'var tbl_camera = [{}];';
    echo '</script>';
    $erreurBlink=true;
} else {
    echo '<script>';
    foreach ($datas as $key => $value) {
        if (trim($key)=="networks") {
            echo 'var tbl_reseau = [';
            foreach ($value as $network) {
                echo '{"network_id":"' .  $network['network_id']. '","network_name":"' . $network['network_name'] . '"},';
            }
            echo '];';
        }
    }
    foreach ($datas as $key => $value) {
        if (trim($key)=="networks") {
            echo 'var tbl_camera = [';
            foreach ($value as $network) {
                foreach ($network as $key2 => $value2) {
                    if (trim($key2)=="camera") {
                        foreach ($value2 as $camera) {
                            echo '{"network_id":"' .  $network['network_id']. '","device_id":"' .  $camera['device_id']. '","device_name":"' . $camera['device_name'] . '"},';
                        }
                    }
                }
            }
            echo '];';
        }
    }
    echo '</script>';
    }
?>

	<a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
  <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
  <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
    <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
    <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
  </ul>
  <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
    <div role="tabpanel" class="tab-pane active" id="eqlogictab">
      <br/>
    <form class="form-horizontal">
        <fieldset> 
            <div class="form-group">
                <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
                <div class="col-sm-3">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                    <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label" >{{Objet parent}}</label>
                <div class="col-sm-3">
                    <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                        <option value="">{{Aucun}}</option>
                        <?php
                        foreach (jeeObject::all() as $object) {
                            echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                        }
                        ?>
                   </select>
               </div>
           </div>
	   <div class="form-group">
                <label class="col-sm-3 control-label">{{Catégorie}}</label>
                <div class="col-sm-9">
                 <?php
                    foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                        echo '<label class="checkbox-inline">';
                        echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                        echo '</label>';
                    }
                  ?>
               </div>
           </div>
	<div class="form-group">
		<label class="col-sm-3 control-label"></label>
		<div class="col-sm-9">
			<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
			<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
		</div>
	</div>

    <div class="form-group">
        <label class="col-sm-3 " ></label>
        <div class="col-sm-3">
            <button type="button" class="btn btn-primary" id="bt_refresh_blink">{{Réactualiser}}</button>
        </div>
    </div>
    <div class="form-group blink_cfg">
        <label class="col-sm-3 control-label" >{{ Système }}</label>
        <div id="liste" class="col-sm-3">
            <select id="select_reseau" class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="network_id"></select>
        </div>
    </div>
    <div class="form-group blink_cfg">
        <label class="col-sm-3 control-label" >{{ Caméra }}</label>
        <div id="liste" class="col-sm-3">         
            <select id="select_camera" class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="camera_id"></select>
        </div>
    </div>

    <script>
        <?php
        if ($erreurBlink) {
            echo '$(".blink_cfg").hide();';
            echo '$("#div_alert").showAlert({message: "'.str_replace("{{","",str_replace("}}","","".$datas['message'])).'", level: "warning"});';
        } else {
            echo '$(".blink_cfg").show();';
        }
        ?>
        $('#bt_refresh_blink').on('click', function() {
            $.ajax({
                type: "POST",
                url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
                data: {
                    action: "getConfig",
                },
                dataType: 'json',
                error: function(request, status, error) {
                    handleAjaxError(request, status, error,$('#div_alert'));
                },
                success: function(data) {
                    if (data.state != 'ok') {
//                        $('#div_alert').showAlert({message: data.result.replace('\{\{','').replace('\}\}',''), level: 'danger'});
                        $('#div_alert').showAlert({message: "'"+data.result+"'", level: 'danger'});
                        $('.blink_cfg').hide();
                        return;
                    } else {
                        tbl_camera = [];
                        dataParsed=$.parseJSON(data.result);
                        if (dataParsed.message) {
                            $('.blink_cfg').hide();
                            $('#div_alert').showAlert({message: dataParsed.message.replace('\{\{','{{').replace('\}\}','}}'), level: 'warning'});
                        } else {
                            $.each(dataParsed.networks,function(i, item)
                            {
                                if (dataParsed.networks[i].network_id!=null && dataParsed.networks[i].network_id!="") {
                                    tbl_reseau.push({"network_id":dataParsed.networks[i].network_id,"network_name":dataParsed.networks[i].network_name});
                                    $.each(dataParsed.networks[i].camera,function(j, itemc)
                                    {
                                        tbl_camera.push({"network_id":dataParsed.networks[i].network_id,"device_id":dataParsed.networks[i].camera[j].device_id,"device_name":dataParsed.networks[i].camera[j].device_name});
                                    });
                                }
                            });
                            if ($('.blink_cfg').is(":hidden")) {chainSelect('select_reseau');}
                            $('.blink_cfg').show();
                            $('#div_alert').showAlert({message: "{{Données réactualisées}}", level: 'info'});
                        }
                        return;
                    }
                }
            });
        })
    </script>

</fieldset>
</form>
</div>
      <div role="tabpanel" class="tab-pane" id="commandtab">
<a class="btn btn-success btn-sm cmdAction pull-left" data-action="add" style="margin-top:5px;"><i class="fa fa-plus-circle"></i> {{Commandes}}</a><br/><br/>
<table id="table_cmd" class="table table-bordered table-condensed">
    <thead>
      <tr>
        <th style="width: 350px;">{{Nom}}</th>
        <th style="width: 75px;">Type</th>
        <th style="width: 300px;">{{Liste de commandes}}</th>
        <th>{{Paramètres}}</th>
        <th style="width: 125px;">{{Options}}</th>
        <th style="width: 92px;">{{Actions}}</th>
      </tr>
    </thead>
    <tbody>
    </tbody>
</table>
</div>
</div>

</div>
</div>

<?php include_file('desktop', 'blink_camera', 'js', 'blink_camera');?>
<?php include_file('core', 'plugin.template', 'js');?>
<?php include_file('desktop', 'blink_camera_config', 'js', 'blink_camera');?>
<?php include_file('desktop', 'blink_camera', 'css', 'blink_camera');?>
