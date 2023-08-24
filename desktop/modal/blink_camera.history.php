<?php include_file('desktop', 'blink_camera', 'css', 'blink_camera');?>
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
$configMedia=init('mode');
if (!isset($configMedia) || $configMedia=='') {
//    $blink_camera->setConfigHistory();
	$configMedia=$blink_camera->getConfigHistory();
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
if ('<?=$formatMedia?>'=='.jpg') {
    //$('#showPhotos').prop('checked', true);
    if ('<?=$thumbFilter?>'=='') {
        $('#btn-jpg').removeClass('btn-secondary');
        $('#btn-jpg').addClass('btn-primary');
        $('#btn-mp4').removeClass('btn-primary');
        $('#btn-mp4').addClass('btn-secondary');
        $('#btn-thumb').removeClass('btn-primary');
        $('#btn-thumb').addClass('btn-secondary');
    } else {
        $('#btn-jpg').removeClass('btn-primary');
        $('#btn-jpg').addClass('btn-secondary');
        $('#btn-mp4').removeClass('btn-primary');
        $('#btn-mp4').addClass('btn-secondary');
        $('#btn-thumb').removeClass('btn-secondary');
        $('#btn-thumb').addClass('btn-primary');
    }
} else {
    //$('#showPhotos').prop('checked', false);
    $('#btn-jpg').removeClass('btn-primary');
    $('#btn-jpg').addClass('btn-secondary');
    $('#btn-mp4').removeClass('btn-secondary');
    $('#btn-mp4').addClass('btn-primary');
    $('#btn-thumb').removeClass('btn-primary');
    $('#btn-thumb').addClass('btn-secondary');
}


</script>
<div id='div_blink_cameraRecordAlert' style="display: none;"></div>
<?php
echo '<div>';
echo '<a class="btn btn-success  pull-right" target="_blank" href="plugins/blink_camera/core/php/downloadFiles.php?pathfile='. urlencode($dir) .'&filter='.urlencode($thumbFilter.'*'.$formatMedia).'&archive='.urlencode('blink_all').'"  ><i class="fas fa-download"></i> {{Tout télécharger}}</a>';                                                        
echo '</div>';

?>
<!-- 
<label class="switch">
  <input type="checkbox" id="showPhotos">
  <span class="slider round"></span>
</label><span>{{Afficher les vignettes des vidéos}}</span>
-->
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
    $storage=$blink_camera->getConfiguration('storage');
    if ($storage!='local' && $blink_camera->isConnected() && $blink_camera->getToken()) {
        $pageVide=0;
        $maxPage=50;
        if ($storage=='local') {
            $maxPage=1;
        }
        for ($page=1;$page<=$maxPage;$page++) {
            if ($pageVide>=3) {
                break;
            }
            if ($nbMax>0 && $cptVideo>=($nbMax)) {
                break;
            };
            $videos=$blink_camera->getVideoList($page);
            $pageVide++;
            foreach ($videos as $video) {
                $pageVide--;
                if ($nbMax>0 && $cptVideo>=($nbMax)) {
                    break;
                };
                if ($storage=='local' || !$video['deleted']) {
                    $cptVideo++;
                    $datetime = explode("_", blink_camera::getDateJeedomTimezone($video['created_at']));
                    $date=$datetime[0];
                    log::add('blink_camera','debug','History['.$blink_camera->getId().'] video found with API('.$storage.'): '.print_r($video,true));
                    if (array_key_exists($date, $videoFiltered)) {
                        array_push($videoFiltered[$date], $video);
                    } else {
                        $videoFiltered[$date]=array($video);
                    }
                }
            }
        } 
    } else {
         //liste les thumbnail*.jpg dans jeedom
        $scandir = scandir($dir);
        foreach($scandir as $fichier){
            if(preg_match("#[0-9]*-.*\.mp4$#",strtolower($fichier))){
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
$tailleVideo=720*$facteur;

$cptVideo=0;
$cptDate=0;
krsort($videoFiltered);
foreach ($videoFiltered as $date => $videoByDate) {
    if ($nbMax>0 && $cptVideo>=($nbMax)) {
        break;
    };
    $cptDate++;
    echo '<div class="div_dayContainer">';
    echo '<legend>';
    echo ' <a class="btn btn-xs btn-default toggleList"><i class="fa fa-chevron-down"></i></a> ';
    echo '<span class="blink_cameraHistoryDate spacer-left-5">'.$date.'</span>';
    echo '<a class="btn btn-xs btn-success spacer-left-5" target="_blank" href="plugins/blink_camera/core/php/downloadFiles.php?pathfile='. urlencode($dir) .'&filter='.urlencode($thumbFilter.'*'.$date.'*'.$formatMedia).'&archive='.urlencode($date).'" ><i class="fas fa-download"></i></a>';
    echo '</legend>';
    if ($cptDate==1) {
        echo '<div class="blink_cameraThumbnailContainer blink_cameraThumbnailContainer_'.$cptDate.'" >';
    } else {
        echo '<div class="blink_cameraThumbnailContainer blink_cameraThumbnailContainer_'.$cptDate.'" style="display:none;">'; 
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
            $path=$blink_camera->getMedia($video['media'], $blink_camera->getId(), $filename);
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
        echo '<div class="panel panel-primary blink_cardVideo">';
        echo '<div class="panel-heading blink_cameraHistoryDate">'.$time;
        echo '<a target="_blank" href="core/php/downloadFile.php?pathfile=' . urlencode($path) . '" class="btn btn-success btn-xs pull-right" style="color : white"><i class="fas fa-download"></i></a>';
        echo ' <a class="btn btn-danger bt_removefile btn-xs pull-right" style="color : white" data-day="1" data-dirname="'.$dir.'" data-filename="/'  . $file . '"><i class="fas fa-trash"></i></a>';
        echo '</div>';
        //echo '<div  class="blink_cameraThumbnailContainer2">';
        echo '<div style="padding:auto !important ;">';
        echo '<center style="margin-top:5px;">';
        if (strpos($file, '.mp4')) {
            $strVideo= '<video class="displayVideo"';
            if ($cptDate==1) {
                $strVideo.= ' preload ';
            }
            $strVideo.= ' height="'.$tailleVideo.'" controls loop data-src="core/php/downloadFile.php?pathfile=' . urlencode($dir . '/' . $file) . '" style="cursor:pointer"><source src="core/php/downloadFile.php?pathfile=' . urlencode($dir . '/' . $file) . '">Your browser does not support the video tag.</video>';
            echo $strVideo;
        } else {
            //echo '<center><img class="img-responsive cursor displayImage lazy" src="plugins/blink_camera/core/img/no-image.png" data-original="core/php/downloadFile.php?pathfile=' . urlencode($dir . '/' . $file) .  '"  style="max-height:'.$tailleVideo.'px;"/></center>';
            echo '<img class="displayImage" loading="eager" src="core/php/downloadFile.php?pathfile=' . urlencode($dir . '/' . $file) .  '" width=360/>';
        }
        echo '</center>';
        echo '</div>';
        //echo '</div>';
        echo '</div>';
    }
    echo '</div>';
    echo '</div>';
}
?>
<script>
$('.blink_cameraThumbnailContainer').packery({itemSelector:'.blink_cardVideo',gutter : 5,resize:true});
$('.bt_removefile').on('click', function() {
	var filename = $(this).attr('data-filename');
	var direct = $(this).attr('data-dirname');
	var card = $(this).closest('.blink_cardVideo');
	if($(this).attr('data-day') == 1){
		card = $(this).closest('.blink_cardVideo');
	}
	if($(this).attr('data-all') == 1){
		card = $('.div_dayContainer');
	}
	$.ajax({
		type: "POST",
		url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
		data: {
			action: "removeRecord",
			file: filename,
			dir: direct,
		},
		dataType: 'json',
		error: function(request, status, error) {
			handleAjaxError(request, status, error,$('#div_blink_cameraRecordAlert'));
		},
		success: function(data) {
			if (data.state != 'ok') {
				$('#div_blink_cameraRecordAlert').showAlert({message: data.result, level: 'danger'});
				return;
			}
			card.remove();
			$(".blink_cameraThumbnailContainer").slideToggle(1);
			$(".blink_cameraThumbnailContainer").slideToggle(1);
            $('.blink_cameraThumbnailContainer').packery({itemSelector:'.blink_cardVideo',gutter : 5,resize:true});
		}
	});
});

$('.toggleList').on('click', function() {
	$(this).closest('.div_dayContainer').find(".blink_cameraThumbnailContainer").slideToggle("slow");
    $('.blink_cameraThumbnailContainer').packery({itemSelector:'.blink_cardVideo',gutter : 5,resize:true});
});
  
/*$("img.lazy").lazyload({
	container: $("#md_modal")
});*/
$('.ui-resizable').resizable({
      resize: function( event, ui ) {$('.blink_cameraThumbnailContainer').packery({itemSelector:'.blink_cardVideo',gutter : 5,resize:true});}
});

$( document ).ready(function() {
    $('.blink_cameraThumbnailContainer').packery({itemSelector:'.blink_cardVideo',gutter : 5,resize:true});
});
/*$('#showPhotos').change(function() {
   if($(this).is(":checked")) {
    $('#md_modal').dialog({title: "Historique <?=$blink_camera->getName()?>"});
    $('#md_modal').load('index.php?v=d&plugin=blink_camera&modal=blink_camera.history&id=<?=$blink_camera->getId()?>&mode=jpg').dialog('open');

    
    return;
   }
    $('#md_modal').dialog({title: "Historique <?=$blink_camera->getName()?>"});
    $('#md_modal').load('index.php?v=d&plugin=blink_camera&modal=blink_camera.history&id=<?=$blink_camera->getId()?>&mode=mp4').dialog('open');
});*/
$('#btn-thumb').click(function() {
    $('#md_modal').dialog({title: "Historique <?=$blink_camera->getName()?>"});
    $('#md_modal').load('index.php?v=d&plugin=blink_camera&modal=blink_camera.history&id=<?=$blink_camera->getId()?>&mode=thumb').dialog('open');
});
$('#btn-mp4').click(function() {
    $('#md_modal').dialog({title: "Historique <?=$blink_camera->getName()?>"});
    $('#md_modal').load('index.php?v=d&plugin=blink_camera&modal=blink_camera.history&id=<?=$blink_camera->getId()?>&mode=mp4').dialog('open');
});
$('#btn-jpg').click(function() {
    $('#md_modal').dialog({title: "Historique <?=$blink_camera->getName()?>"});
    $('#md_modal').load('index.php?v=d&plugin=blink_camera&modal=blink_camera.history&id=<?=$blink_camera->getId()?>&mode=jpg').dialog('open');
});
Promise.all(Array.from(document.images).map(img => {
    if (img.complete)
        return Promise.resolve(img.naturalHeight !== 0);
    return new Promise(resolve => {
        img.addEventListener('load', () => resolve(true));
        img.addEventListener('error', () => resolve(false));
    });
})).then(results => {
    /*if (results.every(res => res))
        console.log('all images loaded successfully');
    else
        console.log('some images failed to load, all finished loading');
        */
    $('.blink_cameraThumbnailContainer').packery({itemSelector:'.blink_cardVideo',gutter : 5,resize:true});
});
</script>
