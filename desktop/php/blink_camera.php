<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('blink_camera');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>
<?php //include_file('desktop', 'blink_camera_config2', 'js', 'blink_camera');?>

<div class="row row-overflow">
    <!--div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un équipement}}</a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php/*
                blink_camera::getToken();
foreach ($eqLogics as $eqLogic) {
    $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '" style="' . $opacity .'"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
}
           */ ?>
           </ul>
       </div>
   </div-->

   <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
  <legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
  <div class="eqLogicThumbnailContainer">
    <div class="cursor eqLogicAction" data-action="add" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 200px;margin-left : 10px;" >
        <i class="fa fa-plus-circle" style="font-size : 6em;color:#00A9EC;"></i>
        <br>
        <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#00A9EC;">{{Ajouter}}</span>
      </div>
      <div class="cursor eqLogicAction" data-action="scan" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 200px;margin-left : 10px;" >
        <i class="fa fa-search-plus" style="font-size : 6em;color:#00A900;"></i>
        <br>
        <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#00A900;">{{Ajouter toutes les caméras}}</span>
      </div>
      <div class="cursor eqLogicAction" data-action="account" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 200px;margin-left : 10px;" >
        <i class="icon mdi-shield-account" style="font-size : 6em;color:#767676;"></i>
        <br>
        <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676;">{{Comptes Blink}}</span>
      </div>
      <div class="cursor eqLogicAction" data-action="gotoPluginConf" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 200px;margin-left : 10px;">
        <i class="fa fa-wrench" style="font-size : 6em;color:#767676;"></i>
        <br>
        <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676">{{Configuration}}</span>
    </div> 
  </div>
  <legend><i class="fa fa-table"></i> {{Mes équipements}}</legend>
<div class="eqLogicThumbnailContainer">
    <?php
    

foreach ($eqLogics as $eqLogic) {
                //echo "<!--".print_r(jeedom::getConfiguration('eqLogic:style:noactive'),true)."-->";
                //$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive').'!important';
                $opacityClass = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
                echo '<div class="eqLogicDisplayCard cursor '.$opacityClass.'" data-eqLogic_id="' . $eqLogic->getId() . '" style="text-align: center; background-color : #ffffff; height : 230px!important;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
                echo '<br><img src="' . $plugin->getPathImgIcon() . '" height="105" width="95" />';
                echo "<br>";
                echo '<span style="font-size : 0.8em;position:relative; top : 5px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">(' . $eqLogic->getBlinkHumanDeviceType() . ')</span>';
                echo "<br>";
                echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">' . $eqLogic->getHumanName(true, true) . '</span>';
                echo "<br>";
                echo '<span style="font-size : 0.8em;position:relative; top : 20px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">({{Stockage}} ' . $eqLogic->getConfiguration("storage") . ')</span>';
                
                echo '</div>';
            }
?>
</div>
</div>

<div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">


	<a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
  <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
  <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
    <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab" id="eqlogictabId"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
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
                    <input id="ideq" type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
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
            <button type="button" class="btn btn-default" id="bt_refresh_blink_cfg">{{Recharger la configuration}}</button>
        </div>
    </div>
    <div class="form-group blink_cfg">
        <label class="col-sm-3 control-label" >{{Compte Blink}}</label>
        <div id="liste" class="col-sm-3">
        <!--select id="select_email" class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="email"></select-->
        <select id="select_email" class="form-control" ></select>
        </div>
    </div>
    <div class="form-group blink_cfg">
        <label class="col-sm-3 control-label" >{{Système}}</label>
        <div id="liste" class="col-sm-3">
        <!--select id="select_reseau" class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="network_id"></select-->
        <select id="select_reseau" class="form-control"></select>
        </div>
    </div>
    <div class="form-group blink_cfg">
        <label class="col-sm-3 control-label" >{{Caméra}}</label>
        <div id="liste" class="col-sm-3">         
        <!--select id="select_camera" class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="camera_id"></select-->
        <select id="select_camera" class="form-control"></select>
        </div>
    </div>

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
<?php include_file('desktop', 'blink_camera', 'css', 'blink_camera');?>
<?php include_file('desktop', 'blink_camera_config2', 'js', 'blink_camera');?>
<script>
  
 function onVisible(element, callback) {
  new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if(entry.intersectionRatio > 0) {
        callback(element);
        observer.disconnect();
      }
    });
  }).observe(element);
  if(!callback) return new Promise(r => callback=r);
}
//onVisible(document.querySelector("#select_email"), () => initSelect());
//onVisible(document.querySelector("#select_reseau"), () => getNetworks());
onVisible(document.querySelector("#select_camera"), () => {
  if ( document.getElementById('ideq').value!="") {
    initSelect();
  }
});
document.querySelector("#select_email").addEventListener("change", (event) => {
    setEmail();
     document.getElementById('select_reseau').find('option').remove();
    getNetworks(true);
     document.getElementById('select_camera').find('option').remove();
    getCameras(true);

});
document.querySelector("#select_reseau").addEventListener("change", (event) => {
    setNetwork();
     document.getElementById('select_camera').find('option').remove();
    getCameras(true);
});
document.querySelector("#select_camera").addEventListener("change", (event) => {
    setCamera();
});
document.querySelector("#ideq").addEventListener("change", (event) => {
  if ( document.getElementById('ideq').value!="") {
    initSelect();
  }
});

document.querySelector("#bt_refresh_blink_cfg").addEventListener("click", function (e) {
  initSelect();
});
document.querySelectorAll('.cmdAttr[data-l1key=id]').forEach(function (key, value) {key.unseen();})
document.querySelectorAll('.cmdAttr[data-l1key=logicalId]').forEach(function (key, value) {key.disabled=true;})
document.querySelectorAll('.cmdAttr[data-l1key=type]').forEach(function (key, value) {key.disabled=true;})
document.querySelectorAll('.cmdAttr[data-l1key=subType]').forEach(function (key, value) {key.disabled=true;})
</script>