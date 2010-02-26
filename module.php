<?php 
/**
 * Модуль "Отчеты"
 * 
 * @version $Id: module.php 358 2010-02-24 11:12:05Z roosit $
 * @package Abricos  
 * @subpackage Report
 * @copyright Copyright (C) 2008 Abricos All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

$mod = new ReportModule();
CMSRegistry::$instance->modules->Register($mod);

class ReportModule extends CMSModule {
	
	public function ReportModule(){
		$this->version = "0.1";
		$this->name = "report";
		$this->takelink = "report";
	}
	
	
	public function GetContentName(){
		$adress = $this->registry->adress;
		$cname = "index";
		
		if($adress->level == 2){
			$cname = 'sitemap';
		}
		return $cname;
	}
}

?>