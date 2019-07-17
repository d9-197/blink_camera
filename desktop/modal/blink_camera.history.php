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

$dir= dirname(__FILE__) ."/../../medias/" . $blink_camera->getId();
?>
<div id='div_blink_cameraRecordAlert' style="display: none;"></div>
<?php
echo '<a class="btn btn-danger bt_removefile pull-right" data-all="1" data-dirname="'.$dir.'" data-filename="/'.$date.'*.mp4"><i class="fas fa-trash"></i> {{Tout supprimer}}</a>';
echo '<a class="btn btn-success  pull-right" target="_blank" href="core/php/downloadFile.php?pathfile=' . urlencode($dir .'/*.mp4') . '" ><i class="fas fa-download"></i> {{Tout télécharger}}</a>';
?>
<?php
$i = 0;
$videoFiltered=array();
for ($page=1;$page<=10;$page++) {
$videos=$blink_camera->getVideoList($page);
	foreach (json_decode($videos,true) as $video) {
		if (!$video['deleted']) {
			$datetime = explode(" ",blink_camera::getDateJeedomTimezone($video['created_at']));
			$date=$datetime[0];
			if (array_key_exists($date, $videoFiltered)) {
				array_push( $videoFiltered[$date], $video );
			} else {
				$videoFiltered[$date]=array($video);
			}
		}
	}
}
$nbMax= (int) config::byKey('nb_max_video', 'blink_camera');
$facteur= (float) config::byKey('blink_size_videos', 'blink_camera');
$tailleVideo=720*$facteur;
echo '<!-- NB VIDEO MAX : '.$nbMax.' -->';
if ($nbMax <= 0) {
 $nbMax=-1;
} 
$cptVideo=0;
foreach ($videoFiltered as $date => $videoByDate) {
	if ($nbMax>0 && $cptVideo>=($nbMax)) {
		break;
	};
	echo '<div class="div_dayContainer reveal">';
	echo '<legend>';
	echo '<span class="blink_cameraHistoryDate">'.$date.'</span>';
	echo ' <a class="btn btn-xs btn-default toggleList"><i class="fa fa-chevron-down"></i></a> ';
	echo '</legend>';
	echo '<div class="blink_cameraThumbnailContainer" >';
	foreach ($videoByDate as $video) {
		$cptVideo++;
		if ($nbMax>0 && $cptVideo>$nbMax) {
			break;
		};
		$filename=blink_camera::getDateJeedomTimezone($video['created_at']);
		$datetime = explode(" ",$filename);
		$time=$datetime[1];
		$path=$blink_camera->getMedia($video['media'],init('id'),$filename);
		$reversedParts = explode('/', strrev($path), 2);
		$file= strrev($reversedParts[0]);
		$path=$dir.'/'.$file;
		$nom = $video['created_at'];
		$blink_cameraName = str_replace(' ', '-', $blink_camera->getName());
		//echo '<div class="panel panel-primary blink_cardVideo" style="width:402px" >';
		echo '<div class="panel panel-primary blink_cardVideo reveal">';
		echo '<div class="panel-heading blink_cameraHistoryDate">'.$time.'</div>';
		/*echo '<legend>';
		echo '<a class="btn btn-xs btn-danger bt_removefile" data-day="1" data-dirname="'.$dir.'" data-filename="/*"><i class="fas fa-trash"></i> {{Supprimer}}</a> ';
		echo '<a class="btn btn-xs btn-success" target="_blank"  href="core/php/downloadFile.php?pathfile=' . urlencode($path) . '" ><i class="fas fa-download"></i> {{Télécharger}}</a> ';
		echo '</legend>';
		*/
		echo '<div  class="blink_cameraThumbnailContainer2">';
		$fontType = 'fas-camera';
		if (strpos($file, '.mp4')) {
			$fontType = 'fas-video-camera';
			$i++;
		}
		echo '<div class="cameraDisplayCard" style="padding:auto !important ;">';
		echo '<center style="margin-top:5px;">';
		if (strpos($file, '.mp4')) {
			echo '<video class="displayVideo" height="'.$tailleVideo.'" controls loop data-src="core/php/downloadFile.php?pathfile=' . urlencode($dir . '/' . $file) . '" style="cursor:pointer"><source src="core/php/downloadFile.php?pathfile=' . urlencode($dir . '/' . $file) . '">Your browser does not support the video tag.</video>';
		} else {
			echo '<center><img class="img-responsive cursor displayImage lazy" src="plugins/blink_camera/core/img/no-image.png" data-original="core/php/downloadFile.php?pathfile=' . urlencode($dir . '/' . $file) .  '" width="150" style="max-height:80px;"/></center>';
		}
		echo '</center>';
		echo '<center style="margin-top:5px;"><a target="_blank" href="core/php/downloadFile.php?pathfile=' . urlencode($path) . '" class="btn btn-success btn-xs" style="color : white"><i class="fas fa-download"></i></a>';
		echo ' <a class="btn btn-danger bt_removefile btn-xs" style="color : white" data-day="1" data-dirname="'.$dir.'" data-filename="/'  . $file . '"><i class="fas fa-trash"></i></a></center>';
		echo '</div>';
		echo '</div>';
		echo '</div>';

	}
	echo '</div>';
	echo '</div>';
}
?>
<script>
$('.blink_cameraThumbnailContainer').packery({itemSelector:'.blink_cardVideo',gutter : 5,resize:true});
/*$('.displayImage').on('click', function() {
	$('#md_modal2').dialog({title: "Image"});
	$('#md_modal2').load('index.php?v=d&plugin=blink_camera&modal=blink_camera.displayImage&src='+ $(this).attr('src')).dialog('open');
});
$('.displayVideo').on('click', function() {
	$('#md_modal2').dialog({title: "Vidéo"});
	$('#md_modal2').load('index.php?v=d&plugin=blink_camera&modal=blink_camera.displayVideo&src='+ $(this).attr('data-src')).dialog('open');
});*/
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
			$('.blink_cameraThumbnailContainer').packery({gutter : 5});
			$(".blink_cameraThumbnailContainer").slideToggle(1);
		}
	});
});

$(".blink_cameraThumbnailContainer").slideToggle(1);
$(".blink_cameraThumbnailContainer").eq(0).slideToggle(1);
$('.toggleList').on('click', function() {
	$(this).closest('.div_dayContainer').find(".blink_cameraThumbnailContainer").slideToggle("slow");
});
  
$("img.lazy").lazyload({
	container: $("#md_modal")
});
$('.ui-resizable').resizable({
      resize: function( event, ui ) {$('.blink_cameraThumbnailContainer').packery({itemSelector:'.blink_cardVideo',gutter : 5,resize:true});}
    });
</script>