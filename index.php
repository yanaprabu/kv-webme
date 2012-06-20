<?php
/**
	* front controller for WebME files
	*
	* PHP version 5.2
	*
	* @category None
	* @package  None
	* @author   Kae Verens <kae@kvsites.ie>
	* @license  GPL 2.0
	* @link     http://kvsites.ie/
	*/

$ww_startup = microtime(true);
// { common variables and functions

/**
  * retrieve a URL linking all added CSS sheets
  *
  * @return string HTML element with generated URL
  */
function WW_getCSS() {
	global $css_urls;
	$url='/css/';
	foreach ($css_urls as $s) {
		$url.='|'.$s;
	}
	return '<style>@import "'
		.htmlspecialchars($url).'";</style>';
}

/**
  * retrieve all inline JS scripts in a HTML element
  *
  * @return string HTML <script> element with inline JS scripts
  */
function WW_getInlineScripts() {
	global $scripts_inline;
	if (!count($scripts_inline)) {
		return '';
	}
	return '<script>'.join('', $scripts_inline).'</script>';
}

/**
  * retrieve a URL linking all added external JS scripts
  *
  * @return string generated URL
  */
function WW_getScripts() {
	global $scripts;
	$url='/js/'.filemtime(SCRIPTBASE.'j/js.js');
	foreach ($scripts as $s) {
		$url.='*'.$s;
	}
	return $url;
}

// load common functions for displaying pages
require_once 'ww.incs/common.php';
if (isset($https_required) && $https_required && !$_SERVER['HTTPS']) {
	$server=str_replace('www.', '', $_SERVER['HTTP_HOST']);
	redirect('https://www.'.$server.'/');
}
if (isset($DBVARS['canonical_name'])
	&& $_SERVER['HTTP_HOST']!=$DBVARS['canonical_name']
) {
	$url=(@$_SERVER['HTTPS']=='on'?'https':'http')
		.'://'.$DBVARS['canonical_name']
		.$_SERVER['REQUEST_URI'];
	redirect($url);
}
if (!isset($DBVARS['version']) || $DBVARS['version']<54) {
	redirect('/ww.incs/upgrade.php');
}
$id=(int)@$_REQUEST['pageid'];
$page=preg_replace('#/$#', '', @$_REQUEST['page']);
// }
// { is this a search?
if ($page=='' && isset($_GET['search']) || isset($_GET['s'])) {
	require_once 'ww.incs/search.php';
	$p=Search_getPage();
	$id=$p->id;
}
// }
// { check for Cron events
if (!isset($DBVARS['cron-next']) || $DBVARS['cron-next']<date('Y-m-d H:i:s')) {
	require_once dirname(__FILE__).'/ww.incs/cron.php';
}
// }
// { is maintenance mode enabled?
if (@$DBVARS['maintenance-mode']=='yes') {
	if (!Core_isAdmin()) {
		die($DBVARS['maintenance-mode-message']);
	}
}
// }
// { get current page id
if (!$id) {
	if ($page) {         // find using the page name
		if (strpos($page, '&')!==false) {
			$page=preg_replace('/&.*/', '', $page);
		}
		$r=Page::getInstanceByName($page);
		if ($r && isset($r->id)) {
			$id=$r->id;
			$PAGEDATA=Page::getInstance($id)->initValues();
			if (@$PAGEDATA->vars['_short_url']) {
				$s=dbOne('select short_url from short_urls where page_id='.$id, 'short_url');
				if ($s!=$page) {
					redirect('/'.$s);
				}
			}
		}
		if (!$id) {
			$id=(int)dbOne(
				'select page_id from short_urls where short_url="'
				.addslashes($page).'"',
				'page_id'
			);
		}
	}
	if (!$id) {          // or maybe it's a "special" or the home page
		$special=1;
		if (@$_GET['special']) {
			$special=$_GET['special'];
		}
		if (!$page) {
			$r=Page::getInstanceBySpecial($special);
			if ($r && isset($r->id)) {
				if ($special==1) {
					redirect($r->getRelativeUrl());
				}
				$id=$r->id;
			}
		}
	}
	if (!$id && $page) { // ok - find the nearest existing page then
		$unused_uri='';
		while (!$id && strpos($page, '/')!==false) {
			$l=strrpos($page, '/');
			$unused_uri=substr($page, $l+1).'/'.$unused_uri;
			$page=substr($page, 0, $l);
			$r=Page::getInstanceByName($page);
			if ($r && isset($r->id)) {
				$id=$r->id;
				$PAGE_UNUSED_URI=substr($unused_uri, 0, strlen($unused_uri)-1);
			}
		}
	}
}
// }
// { load page data
if ($id) {
    $PAGEDATA=Page::getInstance($id)->initValues();
}
else{
	if ($page!='') {
		redirect('/');
	}
	echo '<p>'
		.__('no page loaded. If this is a new site, then please <a href="/ww.admin/">log into the admin area</a> and create your first page.', 'core')
		.'</p>';
	exit;
}
$c=Core_trigger('page-object-loaded');
// }
// { if URL includes a plugin override, run that instead of displaying the page
if (isset($_REQUEST['_p'])
	&& isset($PLUGINS[$_REQUEST['_p']]['page-override'])
) {
	$PLUGINS[$_REQUEST['_p']]['page-override']($PAGEDATA);
	exit;
}
// }
// { main content
// { check if page is protected
$access_allowed=1;
foreach ($PLUGINS as $p) {
	if (!$access_allowed) {
		break;
	}
	if (isset($p['frontend']['page_display_test'])) {
		$access_allowed=$p['frontend']['page_display_test']($PAGEDATA);
	}
}
// }
if (!$access_allowed) {
	$c.='<h2>'.__('Permission Denied', 'core').'</h2>'
		.'<p>'.__('This is a protected document.', 'core').'</p>';
	if (isset($_SESSION['userdata'])) {
		$c.='<p>'
			.__('You are not in a user-group which has access to this page. If you think you should be, please contact the site administrator.', 'core')
			.'</p>';
	}
	else {
		$c.='<p><strong>'
			.__('If you have a user account, please <a href="/_r?type=loginpage">click here</a> to log in.', 'core')
			.'</strong></p>';
	}
	$c.='<p>'
		.__('If you do not have a user account, but have been supplied with a password for the page, please enter it here and submit the form:', 'core')
		.'</p>'
		.'<form method="post"><input type="password" name="privacy_password" />'
		.'<input type="submit" /></form>';
}
elseif (@$_REQUEST['cmsspecial']=='sitemap') {
	require_once 'ww.incs/sitemap-funcs.php';
	$c.=Sitemap_get();
}
else {
	switch($PAGEDATA->type) {
		case '0': // { normal page
			$c.=$PAGEDATA->render();
		break;
		// }
		case '1': // { redirect
			$redirect=@$PAGEDATA->vars['redirect_to'];
			if ($redirect) {
				redirect($redirect);
			}
		break; // }
		case '4': // { sub-page summaries
			require_once 'ww.incs/page.summaries.php';
			$c.=PageSummaries_getHtml($PAGEDATA->id);
		break; // }
		case '5': // { search results
			require_once 'ww.incs/search.php';
			$c.=$PAGEDATA->render().Search_showResults();
		break; // }
		case '9': // { table of contents
			require 'ww.incs/tableofcontents.php';
			$c.=TableOfContents_getContent($PAGEDATA);
		break; // }
		default: // { plugins, and unknown
			$not_found=true;
			if (isset($PLUGINS[$PAGEDATA->type])) {
				$p=$PLUGINS[$PAGEDATA->type];
				if (isset($p['frontend']['page_type'])
					&& function_exists($p['frontend']['page_type'])
				) {
					$c.=$p['frontend']['page_type']($PAGEDATA);
					$not_found=false;
				}
			}
			else {
				foreach ( $PLUGINS as $p ) {
					if (is_array(@$p[ 'frontend' ][ 'page_type' ])) {
						foreach ($p[ 'frontend' ][ 'page_type' ] as $name => $function) {
							if ($name == $PAGEDATA->type && function_exists($function)) {
								$c .= $function($PAGEDATA);
								$not_found = false;
								break;
							}
						}
					}
				}
			}
			if ($not_found) {
				$c.='<em><span>'
					.__('No plugin found to handle page type:', 'core')
					.'</span> <strong>'.htmlspecialchars($PAGEDATA->type)
					.'</strong>. '
					.__('Is the plugin installed and enabled?', 'core')
					.'</em>';
			}
			// }
	}
}
$c.=Core_trigger('page-content-created');
$pagecontent=$c.'<span class="end-of-page-content"></span>';
// }
// { load page template
if (isset($_REQUEST['__t']) && !preg_match('/[\.\/]/', $_REQUEST['__t'])) {
	$PAGEDATA->template=$_REQUEST['__t'];
}
if (file_exists(THEME_DIR.'/'.THEME.'/h/'.$PAGEDATA->template.'.html')) {
	$template=THEME_DIR.'/'.THEME.'/h/'.$PAGEDATA->template.'.html';
}
else if (file_exists(THEME_DIR.'/'.THEME.'/h/_default.html')) {
	$template=THEME_DIR.'/'.THEME.'/h/_default.html';
}
else {
	require_once dirname(__FILE__).'/ww.incs/template-find.php';
}
// }
// { set up smarty
$smarty=smarty_setup(USERBASE.'/ww.cache/pages');
$smarty->template_dir=THEME_DIR.'/'.THEME.'/h/';
$smarty->assign(
	'PAGECONTENT', '<div id="ww-pagecontent">'.$pagecontent.'</div>'
);
$smarty->assign('PAGEDATA', $PAGEDATA);
$smarty->assign('THEMEDIR', '/ww.skins/'.THEME);
// }
// { build metadata
// { page title
$title=$PAGEDATA->title
	?$PAGEDATA->title
	:str_replace('www.', '', $_SERVER['HTTP_HOST']).' > '.__FromJson($PAGEDATA->name);
$c='<title>'.htmlspecialchars($title).'</title>';
// }
// { show stylesheet and javascript links
$c.='WW_CSS_GOES_HERE';
$c.=Core_getJQueryScripts();
$c.='<script src="WW_SCRIPTS_GO_HERE"></script>';
// { generate inline javascript
$tmp='var pagedata={id:'.$PAGEDATA->id
	.Core_trigger('displaying-pagedata')
	.',ptop:'.$PAGEDATA->getTopParentId()
	.',sessid:"'.session_id().'"'
	.',lang:"'.@$_SESSION['language'].'"'
	.'},';
$wasAdmin=@$_SESSION['wasAdmin']?',wasAdmin:1':'';
if (isset($_SESSION['userdata'])) {
	$tmp.='userdata={isAdmin:'.(Core_isAdmin()?1:0)
		.',id:'.$_SESSION['userdata']['id']
		.$wasAdmin
		.',name:"'.addslashes($_SESSION['userdata']['name']).'"'
		.',lat:'.((float)@$_SESSION['userdata']['location_lat'])
		.',lng:'.((float)@$_SESSION['userdata']['location_lng']);
	if (isset($_SESSION['userdata']['discount'])) {
		$tmp.=',discount:'.(int)$_SESSION['userdata']['discount'];
	}
	if (isset($_SESSION['userdata']['address'])) {
		$tmp.=',address:1';
	}
	if ($_SESSION['userdata']['id']) {
		$tmp.=',groups:['.join(',', User::getInstance($_SESSION['userdata']['id'])->getGroups()).']';
	}
	$tmp.='};';
}
else {
	$tmp.='userdata={isAdmin:0'.$wasAdmin.'};';
}
array_unshift($scripts_inline, $tmp);
// }
if (Core_isAdmin()) {
	foreach ($GLOBALS['PLUGINS'] as $p) {
		if (isset($p['frontend']['admin-script'])) {
			WW_addScript($p['frontend']['admin-script']);
		}
	}
}
// }
// { meta tags
$c.='<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
if ($PAGEDATA->keywords) {
	$c.='<meta http-equiv="keywords" content="'
		.htmlspecialchars($PAGEDATA->keywords).'" />';
}
if ($PAGEDATA->description) {
	$c.='<meta http-equiv="description" content="'
		.htmlspecialchars($PAGEDATA->description).'"/>';
}
if (@$PAGEDATA->vars['google-site-verification']) {
	$c.='<meta name="google-site-verification" content="'
		.htmlspecialchars($PAGEDATA->vars['google-site-verification']).'" />';
}
$smarty->assign(
	'pagename',
	@$PAGEDATA->alias?$PAGEDATA->alias:$PAGEDATA->name
);
if (isset($DBVARS['theme_variant']) && $DBVARS['theme_variant']) {
	if (!file_exists(THEME_DIR.'/'.THEME.'/cs/'.$DBVARS['theme_variant'].'.css')) {
		unset($DBVARS['theme_variant']);
		Core_configRewrite();
	}
	else {
		$c.='<link rel="stylesheet" href="/ww.skins/'.THEME.'/cs/'
			.$DBVARS['theme_variant'].'.css" />';
	}
}
// }
// { favicon
if (file_exists(USERBASE.'/f/skin_files/favicon.png')) {
	$c.='<link rel="shortcut icon" href="/f/skin_files/favicon.png" />';
}
// }
$c.=Core_trigger('building-metadata');
$smarty->assign('METADATA', $c);
// }
// { display the document
ob_start();
if (strpos($template, '/')===false) {
	$template=THEME_DIR.'/'.THEME.'/h/'.$template.'.html';
}
$t=$smarty->fetch($template);
echo str_replace(
	array('WW_SCRIPTS_GO_HERE', 'WW_CSS_GOES_HERE', '</body>'),
	array(WW_getScripts(), WW_getCSS(), WW_getInlineScripts().'</body>'),
	$t
);

header('X-page-generation: '.(microtime(true)-$ww_startup).'s');

Core_flushBuffer('page', 'Content-type: text/html; Charset=utf-8');
// }
