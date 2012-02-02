<?php

if (!Abricos::$user->IsAdminMode()){
	return;
}
$brick = Brick::$builder->brick;

$modSitemap = Abricos::GetModule('sitemap');
if (empty($modSitemap)){
	print($brick->param->var['err1']);
	report_sitemap_exit();
}

$mm = $modSitemap->GetManager()->GetMenu(true);

print (report_print_sitemap_menu($mm->menu, $brick->param, null));

report_print_sitemap_page($mm->menu, $brick->param, null);

report_sitemap_exit();

function report_print_sitemap_page(CMSSitemapMenuItem $menu, $param, $parent){

	$lnk = Abricos::$adress->host."".$menu->link;
	
	print(
		Brick::ReplaceVarByData($param->var['h'], array(
			"link" => $menu->link,
			"tl" => $lnk
		))
	);
	
	
	$adress = new Ab_URI($menu->link);
	$page = Abricos::GetModule('sitemap')->GetManager()->GetPage($adress);
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

?>