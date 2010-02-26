<?php

if (!CMSRegistry::$instance->user->IsAdminMode()){
	return;
}
$brick = Brick::$builder->brick;

$modSitemap = CMSRegistry::$instance->modules->GetModule('sitemap');
if (empty($modSitemap)){
	print($brick->param->var['err1']);
	report_sitemap_exit();
}

$mm = $modSitemap->GetManager()->GetMenu(true);

print (report_print_sitemap_menu($mm->menu, $brick->param, null));

report_print_sitemap_page($mm->menu, $brick->param, null);

report_sitemap_exit();

function report_print_sitemap_page(CMSSitemapMenuItem $menu, $param, $parent){

	$lnk = CMSRegistry::$instance->adress->host."".$menu->link;
	
	print(
		Brick::ReplaceVarByData($param->var['h'], array(
			"link" => $menu->link,
			"tl" => $lnk
		))
	);
	
	
	$adress = new CMSAdress($menu->link);
	$page = CMSRegistry::$instance->modules->GetModule('sitemap')->GetManager()->GetPage($adress);
	if (is_null($page)){ return; }
	
	print(
		Brick::ReplaceVarByData($param->var['page'], array(
			"bd" => $page['bd']
		))
	);
	
	// $brick->content .= $page['bd'];
	
	foreach ($menu->child as $child){
		report_print_sitemap_page($child, $param, $menu);
	}
}

function report_print_sitemap_menu(CMSSitemapMenuItem $menu, $param, $parent){
	$lst = "";
	foreach ($menu->child as $child){
		$lst .= report_print_sitemap_menu($child, $param, $menu);
	}
	if (!empty($lst)){
		$tlst = Brick::ReplaceVarByData($param->var[($menu->id==0?"menuroot":"menu")], array(
			"lvl" => $menu->level
		)); 
		$lst = Brick::ReplaceVar($tlst, "rows", $lst);
	}
	if ($menu->id == 0){ return $lst; }
	
	$t = Brick::ReplaceVarByData($param->var['item'], array(
		"id" => $menu->id,
		"tl" => $menu->title,
		"link" => $menu->link,
		"child" => $lst
	));
	
	return $t;
}



function report_sitemap_exit(){
	Brick::$db->close();
	exit;
}

/*
$adress = Brick::$cms->adress;
if($adress->level > 2 && $adress->dir[1] == 'i'){
	// новая версия запроса файла: 
	// http://domain.tld/filemanager/i/1a0bd98db/w_16-h_16/brick-cms.png
	$p_filehash = $adress->dir[2];
	if ($adress->level == 5){
		$arr = explode('-', $adress->dir[3]);
		foreach ($arr as $p){
			$val = explode('_', $p);
			switch($val[0]){
				case 'w': $p_w = $val[1]; break;
				case 'h': $p_h = $val[1]; break;
				case 'cnv': $p_cnv = $val[1]; break;
			}
		}
	}
}else{
	// для совместимости предыдущих версий запроса файла:
	// http://domain.tld/filemanager/file.html?i=1a0bd98db&fn=brick-cms.png&w=16&h=16
	$p_filehash = Brick::$input->clean_gpc('g', 'i', TYPE_STR);
	$p_w = Brick::$input->clean_gpc('g', 'w', TYPE_STR);
	$p_h = Brick::$input->clean_gpc('g', 'h', TYPE_STR);
	$p_cnv = Brick::$input->clean_gpc('g', 'cnv', TYPE_STR);
}

$modFM = Brick::$modules->GetModule('filemanager');
$fileManager = $modFM->GetFileManager(); 

$p_filehash = $fileManager->ImageConvert($p_filehash, $p_w, $p_h, $p_cnv);

$fileinfo = $fileManager->GetFileData($p_filehash);

if (empty($fileinfo)){ EchoEmptyGif(); }

$etag = $p_filehash.'-'.$fileinfo['dateline'];

if (isset($_SERVER['HTTP_IF_NONE_MATCH'])){
	$client_etag = stripslashes(stripslashes($_SERVER['HTTP_IF_NONE_MATCH']));
} else 
	$client_etag = false;

// Обновить счетчик
CMSQFileManager::FileUpdateCounter(Brick::$db, $p_filehash);

if ($client_etag == $etag){
	@header('Not Modified', true, 304);
	exit;
}

header('Cache-control: max-age=31536000');
header('Last-Modified: '.gmdate('D, d M Y H:i:s', $fileinfo['dateline']).' GMT');
header('ETag: '.$etag.'');
header('Expires: ' . gmdate("D, d M Y H:i:s", time() + 3600 * 24 * 15) . ' GMT');

$filename = $fileinfo['filename'];
$extension = $fileinfo['extension'];

if (preg_match('~&#([0-9]+);~', $filename)){
	if (function_exists('iconv')) {
		$filename = @iconv(Brick::$cms->config['Misc']['charset'], 'UTF-8//IGNORE', $filename);
	}

	$filename = preg_replace(
		'~&#([0-9]+);~e',
		"convert_int_to_utf8('\\1')",
		$filename
	);
	$filename_charset = 'utf-8';
} else {
	$filename_charset = Brick::$cms->config['Misc']['charset'];
}


if (is_browser('mozilla')) {
	$filename = "filename*=" . $filename_charset . "''" . rawurlencode($filename);
} else {
	// other browsers seem to want names in UTF-8
	if ($filename_charset != 'utf-8' AND function_exists('iconv')) {
		$filename = @iconv($filename_charset, 'UTF-8//IGNORE', $filename);
	}

	if (is_browser('opera')) {
		// Opera does not support encoded file names
		$filename = 'filename="' . str_replace('"', '', $filename) . '"';
	} else {
		// encode the filename to stay within spec
		$filename = 'filename="' . rawurlencode($filename) . '"';
	}
}

if (in_array($extension, array('jpg', 'jpe', 'jpeg', 'gif', 'png'))) {
	header("Content-disposition: inline; ".$filename);
	header('Content-transfer-encoding: binary');
} else {
	// force txt files to be downloaded because of a possible XSS issue
	header("Content-disposition: attachment; $filename");
}

header('Content-Length: ' . $fileinfo['filesize']);

$fileExtList = $fileManager->GetFileExtensionList();

$mimetype = $fileExtList[$extension]['mimetype'];

if (!empty($mimetype)) {
	header('Content-type: '.$mimetype);
} else {
	header('Content-type: unknown/unknown');
}

$count = 1;
while (!empty($fileinfo['filedata']) && connection_status() == 0) {
	
	echo $fileinfo['filedata'];
	flush();

	if (strlen($fileinfo['filedata']) == 2097152) {

		$startat = (2097152 * $count) + 1;
		$fileinfo = $fileManager->GetFileData($p_filehash, $startat);
		$count++;
	} else {
		$fileinfo['filedata'] = '';
	}
}

Brick::$db->close();
exit;

function save_log($str){
	$handle = fopen("w:/tmp/log.log", 'w');
	fwrite($handle, $str);
	fclose($handle);
}


function EchoEmptyGif(){
	$filedata = base64_decode('R0lGODlhAQABAIAAAMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
	$filesize = strlen($filedata);
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');             // Date in the past
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
	header('Cache-Control: no-cache, must-revalidate');           // HTTP/1.1
	header('Pragma: no-cache');                                   // HTTP/1.0
	header("Content-disposition: inline; filename=clear.gif");
	header('Content-transfer-encoding: binary');
	header("Content-Length: $filesize");
	header('Content-type: image/gif');
	echo $filedata;
	exit;
}

/**/
?>