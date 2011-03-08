<?php
/**
  * upgrade script for user-location plugin
  *
  * PHP Version 5
  *
	* @category   Whatever
  * @package    Webme
  * @subpackage
  * @author     Kae Verens <kae@kvsites.ie>
  * @license    GPL Version 2
  * @link       www.kvsites.ie
 */

if ($version==0) { // add long/lat fields to table
	dbQuery('alter table user_accounts add longitude float default 0');
	dbQuery('alter table user_accounts add latitude float default 0');
	$version=1;
}

$DBVARS[$pname.'|version']=$version;
config_rewrite();
