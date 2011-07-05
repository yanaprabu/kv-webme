<?php
$cssurl=false;
$c = '<div id="image-gallery-tabs">';
$c.= '<ul>';
$c.= '<li><a href="#image-gallery-images">Images</a></li>';
$c.= '<li><a href="#image-gallery-template">Gallery Template</a></li>';
$c.= '<li><a href="#image-gallery-header">Header</a></li>';
$c.= '<li><a href="#image-gallery-footer">Footer</a></li>';
$c.= '<li><a href="#image-gallery-advanced">Advanced Settings</a></li>';
$c.= '</ul>';
// { images
$c.='<div id="image-gallery-images">';
// { image gallery directory - if not exists create it
if (!$vars['image_gallery_directory']||!is_dir(USERBASE.'f/'.$vars['image_gallery_directory'])){
	if(!is_dir(USERBASE.'f/image-galleries')) {
		mkdir(USERBASE.'f/image-galleries');
	}
	$invalid='/[^A-Za-z0-9_\-]/';
	$p=Page::getInstance($page['id']);
	$name=preg_replace($invalid, '_', $p->getRelativeUrl());
	$vars['image_gallery_directory']='/image-galleries/page-'.$name;
	$dir=USERBASE.'f/'.$vars['image_gallery_directory'];
	if (!file_exists($dir)){
		mkdir($dir);
	}
}
$c.='<input type="hidden" name="page_vars[image_gallery_directory]" value="';
$c.=$vars['image_gallery_directory'].'"/>';
// }
// { uploader
WW_addScript('/ww.plugins/image-gallery/files/swfobject.js');
WW_addScript('/ww.plugins/image-gallery/files/uploadify.jquery.min.js');
WW_addCSS('/ww.plugins/image-gallery/files/uploadify.css');
$script='
$(function(){
	$("#uploader").uploadify({
		"uploader":"/ww.plugins/image-gallery/files/uploadify.swf",
		"script":"/ww.plugins/image-gallery/admin/upload.php",
		"cancelImg":"/ww.plugins/image-gallery/files/cancel.png",
		"multi":"true",
		"buttonText":"Upload Files",
		"removeCompleted":"true",
		"fileDataName":"file_upload",
		"scriptData":{
			"PHPSESSID":"'.session_id().'",
			"gallery_id":"'.$page['id'].'"
		},
		"onComplete":function(event,ID,fileObj,response,data){
			console.log("test");
			$.post(
				"/ww.plugins/image-gallery/admin/new-files.php",
				{
					"gallery_id":"'.$page['id'].'",
					"id":response
				},
				function(html){
					$("#image-gallery-wrapper").append(html);
				}
			);
		},
		"fileExt":"*.jpg,*.jpeg,*.png,*.gif",
		"auto":true
	});
});
';
WW_addInlineScript($script);
$c.='<div id="upload">';
$c.='<input type="file" name="file_upload" id="uploader"/>';
$c.='</div>';
// }
$images=dbAll(
	'select * from image_gallery where gallery_id='.$page['id'].' order by position asc'
);
$n=count($images);
if($n){
	$c.='<p>Note: Drag the images to reorder them</p>';
	$c.='<ul id="image-gallery-wrapper">';
	for($i=0;$i<$n;$i++){
		$id=$images[$i]['id'];
		$meta=json_decode($images[$i]['meta'],true);
		$caption=(isset($meta['caption'])&&$meta['caption']!='')?
			' title="'.$meta['caption'].'"':
			'';
		$c.='<li class="gallery-image-container" id="image_'.$id.'">'
			.'<img src="/ww.plugins/image-gallery/get-image.php?uri='.$vars['image_gallery_directory'].'/'.$meta['name'].',width=64,height=64"'
			.$caption.' id="image-gallery-image'.$id.'"/><br/>'
			.'<a href="javascript:;" class="delete-img" id="'.$id.'">'
			.'Delete</a><br/>'
			.'<a href="javascript:;" class="edit-img" id="'.$id.'">';
		$c.=(isset($meta['caption'])&&$meta['caption']!='')?
			'Edit':'Add';
		$c.=' Caption</a>'
			.'</li>';
	}
	$c.='</ul><br style="clear:both"/>';
}
else{
	$c.='<em>no images yet. please upload some.</em>';
}
$c.='</div>';
$c.='<br style="clear:both"/>';
// }
// { gallery template
$types=array(
	'---',
	'List',
	'Grid',
	'Simple'
);
$c.='<div id="image-gallery-template">'
	.'<p>This controls how the gallery is displayed, choose from some of the '
	.'layouts listed below as a starting point. These layouts are only a guide, you may change them.</p>'
	.'Gallery Layout: <select'
	.' id="gallery-template-type">';
foreach($types as $type){
	$c.='<option value="'.strtolower($type).'">'.$type.'</option>';
}
$c.='</select>';
$content=$vars['gallery-template'];
$c.=ckeditor('page_vars[gallery-template]',$content,0);
$c.='</div>';
// }
// { header
$c.='<div id="image-gallery-header">'
	.'<p>This text will appear above the gallery.</p>';
$c.=ckeditor('body',$page['body'],0,$cssurl);
$c.='</div>';
// }
// { footer
$c.='<div id="image-gallery-footer">'
	.'<p>This text will appear below the gallery.</p>';
$c
	.=ckeditor(
		'page_vars[footer]',
		(isset($vars['footer'])?$vars['footer']:''),
		0,
		$cssurl
	);
$c.='</div>';
// }
// { advanced settings
$c.='<div id="image-gallery-advanced">';
$c.='<table>';
// { columns
$c.='<tr><th>Columns</th><td>'
	.'<input name="page_vars[image_gallery_x]" value="'
	.(int)$vars['image_gallery_x'].'" /></td>';
// }
// { main image height
$height=(int)@$vars['image_gallery_image_y'];
$height=($height==0)?350:$height;
$c.='<th>Main Image Height</th>';
$c.='<td><input name="page_vars[image_gallery_image_y]"';
$c.=' value="'.$height.'"/>';
// }
// { rows
$c.='<tr><th>Rows</th><td>'
	.'<input name="page_vars[image_gallery_y]" value="'
	.(int)$vars['image_gallery_y'].'" /></td>';
// }
// { main image width
$width=(int)@$vars['image_gallery_image_x'];
$width=($width==0)?350:$width;
$c.='<th>Main Image Width</th>';
$c.='<td><input name="page_vars[image_gallery_image_x]"';
$c.=' value="'.$width.'"/>';
// }
// { thumbnail size
$ts=(int)@$vars['image_gallery_thumbsize'];
$ts=$ts?$ts:150;
$c.='<tr><th>Thumb Size</th><td>'
	.'<input name="page_vars[image_gallery_thumbsize]" value="'.$ts.'" />'
	.'</td>';
// }
// { main image effects
$effects=array(
	'fade',
	'slideVertical',
	'slideHorizontal',
);
$width=($width==0)?350:$width;
$c.='<th>Main Image Effects</th>';
$c.='<td><select name="page_vars[image_gallery_effect]">';
foreach($effects as $effect){
	$c.='<option';
	if($effect==@$vars['image_gallery_effect'])
		$c.=' selected="selected"';
	$c.='>'.$effect.'</option>';
}
$c.='</select></td></tr>';
// }
// { hover
$options=array('opacity'=>'Opacity','zoom'=>'Zoom','popup'=>'Popup');
$c.='<tr><th>Images on hover:</th>';
$c.='<td><select name="page_vars[image_gallery_hover]">';
foreach($options as $value=>$option){
	$c.='<option value="'.$value.'"';
	if($value==@$vars['image_gallery_hover'])
		$c.=' selected="selected"';
	$c.='>'.$option.'</option>';
}
$c.='</select></td>';
// }
// { ratio crop/normal
$options=array('normal'=>'Normal','crop'=>'Crop');
$c.='<th>Thumbnail Ratio:</th>';
$c.='<td><select name="page_vars[image_gallery_ratio]">';
foreach($options as $value=>$option){
	$c.='<option value="'.$value.'"';
	if($value==@$vars['image_gallery_ratio'])
		$c.=' selected="selected"';
	$c.='>'.$option.'</option>';
}
$c.='</select></td></tr>';
// }
// { slideshow
$options=array('false'=>'No','true'=>'Yes');
$c.='<tr><th>Autostart Slide-show:</th>';
$c.='<td><select name="page_vars[image_gallery_autostart]">';
foreach($options as $value=>$option){
	$c.='<option value="'.$value.'"';
	if($value==@$vars['image_gallery_autostart'])
		$c.=' selected="selected"';
	$c.='>'.$option.'</option>';
}
$time=(isset($vars['image_gallery_slidedelay']))?
	$vars['image_gallery_slidedelay']:
	2500;
$c.='</select></td>';
$c.='<th>Slide delay:</th>';
$c.='<td><input type="text" name="page_vars[image_gallery_slidedelay]" ';
$c.=' value="'.$time.'"/> ms</td></tr>';
// }
// }
$c.='</table>';
$c.='</div>';
$c.='</div>';
if (!is_dir(USERBASE.'ww.cache/image-gallery')) {
  mkdir(USERBASE.'ww.cache/image-gallery');
}
if (file_exists(USERBASE.'ww.cache/image-gallery/'.$page['id'])) {
  unlink(USERBASE.'ww.cache/image-gallery/'.$page['id']);
} 
file_put_contents(
  USERBASE.'ww.cache/image-gallery/'.$page['id'],
  @$vars['gallery-template']
);
ww_addScript('/ww.plugins/image-gallery/admin/admin.js');
$c.='<link rel="stylesheet" href="/ww.plugins/image-gallery/admin/admin.css" />';
