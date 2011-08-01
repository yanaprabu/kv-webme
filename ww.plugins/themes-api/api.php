<?php

/**
 * api.php, KV-Webme Themes API
 *
 * provides information on themes in the repository
 *
 * paramaters that can be given to the api:
 *
 * theme	-	id of the theme						//working
 * tags		-	comma seperated, search by keywords
 * rated	-	by highest rated
 * count	-	int, number of themes to return
 * recent	-	if set as true will return recently added themes	//working
 * downloads	-	most downloaded
 * name		-	search by name						//working
 * start	- 	int, start searching themes at this position
 * download	-	if set to true will download a file with the id provided //working
 * id		-	int, id of theme					//working
 * screenshot	-	if set to true will display screenshoot			//working
 * variant 	-	will display screenshot of a particular variant		//working
 *
 * @author     Conor Mac Aoidh <conormacaoidh@gmail.com>
 * @license    GPL 2.0
 * @version    1.0
 */

require '../../ww.incs/basics.php';
require SCRIPTBASE . 'ww.plugins/themes-api/api/funcs.php';

if (!empty ($_GET['theme'])) {
	require SCRIPTBASE . 'ww.plugins/themes-api/api/theme.php';
	exit;
}

if (!empty ($_GET['screenshot'])) {
	require SCRIPTBASE . 'ww.plugins/themes-api/api/screenshot.php';
	exit;
}

if (!empty ($_GET['tags'])) {
        require SCRIPTBASE . 'ww.plugins/themes-api/api/tags.php';
	exit;
}

if (!empty ($_GET['recent'])) {
        require SCRIPTBASE . 'ww.plugins/themes-api/api/recent.php';
        exit;
}

if (!empty ($_GET['rating'])) {
        require SCRIPTBASE . 'ww.plugins/themes-api/api/rating.php';
        exit;
}

if (!empty ($_GET['downloads'])) {
        require SCRIPTBASE . 'ww.plugins/themes-api/api/downloads.php';
        exit;
}

if (!empty ($_GET['name'])) {
        require SCRIPTBASE . 'ww.plugins/themes-api/api/name.php';
        exit;
}

if (!empty($_GET['download'])) {
	require SCRIPTBASE . 'ww.plugins/themes-api/api/download.php';
	exit;
}

?>
