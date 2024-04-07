<?php include_file('desktop', 'blink_camera', 'css', 'blink_camera');?>
<?php include_file('desktop', 'blink_camera_history', 'js', 'blink_camera');?>
<?php
if (!isConnect()) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
if (init('id') == '') {
    throw new Exception(__('L\'id ne peut etre vide', __FILE__));
}

$blink_camera = blink_camera::byId(init('id'));
if (!is_object($blink_camera)) {
    throw new Exception(__('L\'équipement est introuvable : ', __FILE__) . init('id'));
}
if ($blink_camera->getEqType_name() != 'blink_camera') {
    throw new Exception(__('Cet équipement n\'est pas de type blink_camera : ', __FILE__) . $blink_camera->getEqType_name());
}
log::add('blink_camera','debug','History['.$blink_camera->getId().'] START');

$email=$blink_camera->getConfiguration('email');

if (blink_camera::isConnected($email) && $blink_camera->isConfigured()) {
    $cameraConnected=true;
}

$storage=$blink_camera->getConfiguration('storage');
$configMedia=init('mode');
if (!isset($configMedia) || $configMedia=='') {
	$configMedia=$blink_camera->getConfigHistory();
}
if ($storage=='local' && $configMedia=='jpg') {
    $configMedia='thumb';
}
if ($configMedia!='') {
    $blink_camera->setConfigHistory($configMedia);
    $thumbFilter="";
    if ($configMedia=='thumb') {
        $thumbFilter=blink_camera::PREFIX_THUMBNAIL;
        $configMedia="jpg";
    }
    $formatMedia='.'.$configMedia;
}
$dir= realpath(dirname(__FILE__) ."/../../medias/" . $blink_camera->getId().'/');
?>
<script>


var resizers = ['top', 'top-right', 'right', 'bottom-right', 'bottom', 'bottom-left', 'left', 'top-left']

if ('<?=$formatMedia?>'=='.jpg') {
    if ('<?=$thumbFilter?>'=='') {
        if ('<?=$storage?>'!='local') {
            document.querySelector('#btn-jpg').classList.remove('btn-secondary');
            document.querySelector('#btn-jpg').classList.add('btn-primary');
        }
        document.querySelector('#btn-mp4').classList.remove('btn-primary');
        document.querySelector('#btn-mp4').classList.add('btn-secondary');
        document.querySelector('#btn-thumb').classList.remove('btn-primary');
        document.querySelector('#btn-thumb').classList.add('btn-secondary');
    } else {
        if ('<?=$storage?>'!='local') {
            document.querySelector('#btn-jpg').classList.remove('btn-primary');
            document.querySelector('#btn-jpg').classList.add('btn-secondary');
        }
        document.querySelector('#btn-mp4').classList.remove('btn-primary');
        document.querySelector('#btn-mp4').classList.add('btn-secondary');
        document.querySelector('#btn-thumb').classList.remove('btn-secondary');
        document.querySelector('#btn-thumb').classList.add('btn-primary');
    }
} else {
    document.querySelector('#btn-jpg').classList.remove('btn-primary');
    document.querySelector('#btn-jpg').classList.add('btn-secondary');
    document.querySelector('#btn-mp4').classList.remove('btn-secondary');
    document.querySelector('#btn-mp4').classList.add('btn-primary');
    document.querySelector('#btn-thumb').classList.remove('btn-primary');
    document.querySelector('#btn-thumb').classList.add('btn-secondary');
}
if ('<?=$storage?>'=='local') {
    document.querySelector('#btn-jpg').unseen();
}



</script>
<?php
$archiver="downloadFilesZip.php";
if (!extension_loaded('zip')) {
    $archiver="downloadFiles.php";
}
$dirEncoded=urlencode($dir);
$filterAllEncoded=urlencode($thumbFilter.'.*'.$formatMedia);
$cameraNameEncoded=urlencode($blink_camera->getName());
?>

<div>
    <a class="btn btn-success  pull-right" target="_blank" href="plugins/blink_camera/core/php/<?=$archiver?>?pathfile=<?=$dirEncoded?>&filter=<?=$filterEncoded?>&archive=<?=$cameraNameEncoded?>">
        <i class="fas fa-download"></i> {{Tout télécharger}}
    </a>                                                        
</div>
<div>
    <button type="button" class="btn btn-secondary" id="btn-mp4">{{Vidéos}}</button>
    <button type="button" class="btn btn-secondary" id="btn-jpg">{{Vignettes des vidéos}}</button>
    <button type="button" class="btn btn-secondary" id="btn-thumb">{{Vignettes de la caméra}}</button>
</div>
<?php
$videoFiltered=array();
$nbMax= (int) config::byKey('nb_max_video', 'blink_camera');
if ($nbMax <= 0) {
    $nbMax=-1;
}
$cptVideo=0;
if ($thumbFilter=='') {
    if ($storage!='local' && $blink_camera->isConnected() && $blink_camera->getToken() && blink_camera::isModeEco()===false) {
        $blink_camera->forceCleanup(true);
    }
    log::add('blink_camera','debug','History['.$blink_camera->getId().'] Avant scandir');

    $scandir = scandir($dir);
    log::add('blink_camera','debug','History['.$blink_camera->getId().'] Après scandir');
    foreach($scandir as $fichier){
        if ($formatMedia==".mp4") {
            if(preg_match("#[0-9]*-.*\.mp4$#",strtolower($fichier))){
                $datetime = explode("-", $fichier);
                $date=$datetime[1].'-'.$datetime[2].'-'.explode("_",$datetime[3])[0];
                if (array_key_exists($date, $videoFiltered)) {
                    array_push($videoFiltered[$date], json_decode("{\"id\":\"".$fichier."\"}",true));
                } else {
                    $videoFiltered[$date]=array(json_decode("{\"id\":\"".$fichier."\"}",true));
                }
            }
        } else {
            if(preg_match("#[0-9]*-.*\.jpg$#",strtolower($fichier)) && strpos(strtolower($fichier),blink_camera::PREFIX_THUMBNAIL)===false) {
                $datetime = explode("-", $fichier);
                $date=$datetime[1].'-'.$datetime[2].'-'.explode("_",$datetime[3])[0];
                if (array_key_exists($date, $videoFiltered)) {
                    array_push($videoFiltered[$date], json_decode("{\"id\":\"".$fichier."\"}",true));
                } else {
                    $videoFiltered[$date]=array(json_decode("{\"id\":\"".$fichier."\"}",true));
                }
            }
        }
    }
    log::add('blink_camera','debug','History['.$blink_camera->getId().'] Après boucle sur scandir');

} else {
    //liste les thumbnail*.jpg dans jeedom
    $scandir = scandir($dir);
    foreach($scandir as $fichier){
        if(preg_match("#".blink_camera::PREFIX_THUMBNAIL."-.*\.jpg$#",strtolower($fichier))){
            $datetime = explode("_", $fichier);
            $date=str_replace(blink_camera::PREFIX_THUMBNAIL."-","",$datetime[0]);
        if (array_key_exists($date, $videoFiltered)) {
            array_push($videoFiltered[$date], json_decode("{\"id\":\"".$fichier."\"}",true));
        } else {
                $videoFiltered[$date]=array(json_decode("{\"id\":\"".$fichier."\"}",true));
            }
        }
    }
}
$facteur= (float) config::byKey('blink_size_videos', 'blink_camera');
$newHeight=720*$facteur;
$newWidth=2*$newHeight;

$cptVideo=0;
$cptDate=0;
krsort($videoFiltered);
?>
<?php
foreach ($videoFiltered as $date => $videoByDate) {
    if ($nbMax>0 && $cptVideo>=($nbMax)) {
        break;
    };
    $cptDate++;
    $filterDateEncoded=urlencode($thumbFilter.'.*'.$date.'.*'.$formatMedia);
    $archiveName=urlencode($blink_camera->getName().'-'.$date);
    $dateLabel=blink_camera::getDateLocaleJeedom($date);
    if ($cptDate==1) {
        $icone="fa fa-minus";
    } else {
        $icone="fa fa-plus";
    }

?>
    <div class="div_dayContainer">
        <legend>
            <a class="btn btn-xs btn-default toggleList toggleList_<?=$cptDate?>"><i class="<?=$icone?>"></i></a>
            <span class="blink_cameraHistoryDate spacer-left-5"><?=$dateLabel?></span>
            <a class="btn btn-xs btn-success spacer-left-5" target="_blank" href="plugins/blink_camera/core/php/<?=$archiver?>?pathfile=<?=$dirEncoded?>&filter=<?=$filterDateEncoded?>&archive=<?=$archiveName?>" ><i class="fas fa-download"></i></a>
        </legend>
<?php
    if ($cptDate==1) {
?>
       <div class="blink_cameraThumbnailContainer blink_cameraThumbnailContainer_<?=$cptDate?> active" >
<?php
    } else {
?>
        <div class="blink_cameraThumbnailContainer blink_cameraThumbnailContainer_<?=$cptDate?>" style="display:none;"> 
<?php   
    }
    rsort($videoByDate);
    foreach ($videoByDate as $video) {
        log::add('blink_camera','debug','History['.$blink_camera->getId().'] Display video by date ('.$storage.'): '.print_r($video,true));
        $cptVideo++;
        if ($nbMax>0 && $cptVideo>$nbMax) {
            break;
        };
        if (isset($video['created_at'])) {
            $filename=$video['id'].'-'.blink_camera::getDateJeedomTimezone($video['created_at']);
            log::add('blink_camera','debug','History['.$blink_camera->getId().'] Display video by date ('.$storage.'): before getMedia : '.$video['media']);
//            $path=$blink_camera->getMedia($video['media'], $blink_camera->getId(), $filename);
            log::add('blink_camera','debug','History['.$blink_camera->getId().'] Display video by date ('.$storage.'): after getMedia');
        } else {
            $filename=$video['id'];
            $path=$dir.'/'.$filename;

        }
        $datetime = explode("_", $filename);
        $time=$datetime[1];
        $time=substr($time,0,2)."h".substr($time,2,2)."m".substr($time,4,2)."s";
        $reversedParts = explode('/', strrev($path), 2);
        $file= strrev($reversedParts[0]);
        if ($formatMedia=='.jpg') {
            $file=str_replace('mp4', 'jpg',$file); 
        }
        $path=$dir.'/'.$file;
        $nom = $video['created_at'];
        $blink_cameraName = str_replace(' ', '-', $blink_camera->getName());
        $pathEncoded=urlencode($path);
?>
            <div class="panel panel-primary blink_cardVideo">
                <div class="panel-heading blink_cameraHistoryDate">
                    <?=$time?>
                    <a target="_blank" href="core/php/downloadFile.php?pathfile=<?=$pathEncoded?>" class="btn btn-success btn-xs pull-right" style="color : white"><i class="fas fa-download"></i></a>
                    <?php
                    if ($cameraConnected) {
                            echo ' <a class="btn btn-danger bt_removefile_'.$cptVideo.' btn-xs pull-right" style="color : white" data-day="1" data-ideq="'.$blink_camera->getId().'" data-dirname="'.$dir.'" data-filename="/'  . $file . '"><i class="fas fa-trash"></i></a>';
                    }
                    ?>
                    <script>
                        document.querySelector('.bt_removefile_<?=$cptVideo?>').addEventListener('click', function(event) {
                            var filename = "/<?=$file?>";
                            var idEquipment = "<?=$blink_camera->getId()?>";
                            var direct = "<?=$dir?>";
                            var card = event.target.closest('.blink_cardVideo');
                            if(event.target.getAttribute('data-day') == 1){
                                card = event.target.closest('.blink_cardVideo');
                            }
                            if(event.target.getAttribute('data-all') == 1){
                                card = document.querySelector('.div_dayContainer');
                            }
                            domUtils.ajax({
                                type: "POST",
                                url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
                                data: {
                                    action: "removeRecord",
                                    file: filename,
                                    dir: direct,
                                    ideq: idEquipment,
                                },
                                dataType: 'json',
                                error: function(request, status, error) {
                                    handleAjaxError(request, status, error,$('#div_blink_cameraRecordAlert'));
                                },
                                success: function(data) {
                                    if (data.state != 'ok') {
                                        jeedomUtils.showAlert({message: data.result, level: 'danger'});
                                        return;
                                    }
                                    card.remove();
                                    //event.target.closest('.div_dayContainer').querySelector(".blink_cameraThumbnailContainer").packery({itemSelector:'.blink_cardVideo',gutter : 5,resize:true});
                                    relayout_<?=$cptDate?>();
                                }
                            });
                        });
                    </script>
                </div>
                <?php
                    if (strpos($file, '.mp4')) {
                        $strVideo="";
                        if (blink_camera::isModeEco()) {
                            $strVideo.="<div id=\"video-overlay-".$cptVideo."\">";
                            if (file_exists($dir . '/' . str_replace(".mp4",".jpg",$file))) {
                                $overlay=$dir . '/' . str_replace(".mp4",".jpg",$file);
                            } else {
                                $overlay="/plugins/blink_camera/img/play.png";
                            }
                            $strVideo.="<img height=\"".$newHeight."\" src=\"". blink_camera::GET_RESOURCE . urlencode($overlay)."\"/>";
                            //$strVideo.="<span style=\" position: absolute;top: 50%;left: 50%;transform: translate(-50%, -50%);\"><i style=\"font-size:5em;\" class=\"icon font-awesome-play-circle icon_green\"></i></span>";
                            $strVideo.="</div>";
                        }
                        $strVideo.= "<video id=\"video-".$cptVideo."\" class=\"displayVideo\"";
                        $strVideo.= " preload=\"none\" poster=\"". blink_camera::GET_RESOURCE . urlencode($overlay) . "\" ";
                        $strVideo.=" height=\"".$newHeight."\" controls loop data-src=\"". blink_camera::GET_RESOURCE.urlencode($dir . '/' . $file)."\" style=\"cursor:pointer\"><source src=\"". blink_camera::GET_RESOURCE.urlencode($dir.'/'.$file)."\">Your browser does not support the video tag.</video>";
                        echo $strVideo;
                        if (blink_camera::isModeEco()) {
                ?>
                        <script>
                                var overlay<?=$cptVideo?>         = document.getElementById('video-overlay-<?=$cptVideo?>'),
                                video<?=$cptVideo?>         = document.getElementById('video-<?=$cptVideo?>'),
                                videoPlaying<?=$cptVideo?>    = false;
                                function hideOverlay<?=$cptVideo?>() {
                                    overlay<?=$cptVideo?>.style.display = "none";
                                    video<?=$cptVideo?>.style.display = "block";
                                    videoPlaying<?=$cptVideo?> = true;
                                    video<?=$cptVideo?>.play();
                                    if ($('#video-<?=$cptVideo?>').is(":visible")) {
                                      //  $(".blink_cameraThumbnailContainer_<?=$cptDate?>").packery({itemSelector:'.blink_cardVideo',gutter : 5,resize:true});
                                     };
    
                                }
                                function showOverlay<?=$cptVideo?>() {
                                    // this check is to differentiate seek and actual pause 
                                    if (video<?=$cptVideo?>.readyState === 4) {
                                        overlay<?=$cptVideo?>.style.display = "block";
                                        videoPlaying<?=$cptVideo?> = true;
                                    }
                                    video<?=$cptVideo?>.style.display = "none";
                                }
                                video<?=$cptVideo?>.style.display = "none";
                               // video<?=$cptVideo?>.addEventListener('pause', showOverlay<?=$cptVideo?>);
                                overlay<?=$cptVideo?>.addEventListener('click', hideOverlay<?=$cptVideo?>);
                                
                            </script>
                <?php
                        }
                    } else {
                        list($width, $height, $type, $attr) = getimagesize($dir . '/' . $file);
                        if ($height<=200) {
                            $facteurImg=1;
                            $idIMG='';
                        } else {
                            $facteurImg=$facteur;
                            $idIMG=' id="'.$file.'" ';
                        }
                        $newHeight=$height*$facteurImg;
                        $newWidth=$width*$facteurImg;
                        echo '<!--ORIG HEIGHT: '.$height.' ORIG WIDTH: '.$width.' factor: '.$facteurImg.' HEIGTH: '.$newHeight.' WIDTH: '.$newWidth. ' -->';
                        echo '<div><img '.$idIMG.' class="displayImage" loading="eager" src="'. blink_camera::GET_RESOURCE . urlencode($dir . '/' . $file) .  '" height="'.$newHeight.'" width="'.$newWidth.'"/></div>';
                    }
                ?>
            </div>

<?php

    } // FIN VIDEOS
?>
        </div>
    </div>
    <script>
        pckry_<?=$cptDate?> = new Packery( '.blink_cameraThumbnailContainer_<?=$cptDate?>', {itemSelector:'.blink_cardVideo',gutter : 5,resize:true});
        pckry_<?=$cptDate?>.layout();
        function relayout_<?=$cptDate?>() {
            pckry_<?=$cptDate?>.layout();
        }
        new ResizeObserver(relayout_<?=$cptDate?>).observe(document.querySelector('.blink_cameraThumbnailContainer_<?=$cptDate?>'));
        document.querySelector('.toggleList_<?=$cptDate?>').addEventListener('click', function(event) {
            event.preventDefault();
            container=document.querySelector(".blink_cameraThumbnailContainer_<?=$cptDate?>")
            if (!container.classList.contains('active')) {
                container.classList.add('active');
                container.style.display = 'block';
                document.querySelector('.toggleList_<?=$cptDate?>').querySelector('i').classList.add('fa-minus');
                document.querySelector('.toggleList_<?=$cptDate?>').querySelector('i').classList.remove('fa-plus');
            } else {
                container.style.display = 'none';
                document.querySelector('.toggleList_<?=$cptDate?>').querySelector('i').classList.add('fa-plus');
                document.querySelector('.toggleList_<?=$cptDate?>').querySelector('i').classList.remove('fa-minus');
                container.classList.remove('active');
            }
        });
    </script>
<?php 
} // FIN DATES
?>
<script>
 
document.querySelector('#btn-thumb').addEventListener('click', function(event) {
    jeeDialog.dialog({title: "Historique <?=$blink_camera->getName()?>",contentUrl: 'index.php?v=d&plugin=blink_camera&modal=blink_camera.history&id=<?=$blink_camera->getId()?>&mode=thumb'});
});
document.querySelector('#btn-mp4').addEventListener('click', function(event) {
    jeeDialog.dialog({title: "Historique <?=$blink_camera->getName()?>",contentUrl: 'index.php?v=d&plugin=blink_camera&modal=blink_camera.history&id=<?=$blink_camera->getId()?>&mode=mp4'});
});
if ('<?=$storage?>'!='local') {
    document.querySelector('#btn-jpg').addEventListener('click', function(event) {
        jeeDialog.dialog({title: "Historique <?=$blink_camera->getName()?>",contentUrl: 'index.php?v=d&plugin=blink_camera&modal=blink_camera.history&id=<?=$blink_camera->getId()?>&mode=jpg'});
    });
}


/*document.querySelectorAll("img.displayImage").forEach(function (vignette) {
    magnify(vignette.id, (1/<?=$facteur?>));
});*/


</script>
