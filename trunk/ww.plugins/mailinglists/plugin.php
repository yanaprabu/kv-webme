<?php
/**
	* definition file for the WebME mailing lists plugin
	*
	* PHP version 5.2
	*
	* @category None
	* @package  None
	* @author   Kae Verens <kae@kvsites.ie>
	* @license  GPL 2.0
	* @link     http://kvsites.ie/
	*/

$plugin=array(
	'name' => 'Mailing Lists',
	'admin' => array(
		'menu' => array(
			'Communication>Mailing Lists'=>'javascript:Core_screen(\'mailinglists\',\'Dashboard\')'
		)
	),
	'description' => 'Mailing lists',
	'frontend' => array(
		'widget' => 'MailingLists_widget'
	),
	'version' => '2'
);

function MailingLists_widget($vars) {
	$html='<div id="mailinglists-subscribe">'
		.'<input type="email" placeholder="'.__('enter email address').'"/>';
	$lists=dbAll('select * from mailinglists_lists');
	if (count($lists)>1) {
		$html.='<select><option value="">'.__('Mailing List').'</option>';
		foreach ($lists as $list) {
			$html.='<option value="'.$list['id'].'">'
				.htmlspecialchars($list['name']).'</option>';
		}
		$html.='</select>';
	}
	$html.='<button>'.__('Subscribe').'</button></div>';
	WW_addScript('mailinglists/js.js');
	return $html;
}
function Mailinglists_xmlrpcClient($username, $password, $request) {
	$url='https://'.$username.'.clients.ubivox.com/xmlrpc/';
	$header=array('Content-type: text/xml', 'Content-length: '.strlen($request));
	$ch=curl_init();
	@mkdir(USERBASE.'ww.cache/cookies');
	$cookie_file=USERBASE.'ww.cache/cookies/'.md5($url).'.txt';
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
	curl_setopt($ch, CURLOPT_REFERER, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$data = curl_exec($ch);       
	if (curl_errno($ch)) {
		print curl_error($ch);
	}
	else {
		curl_close($ch);
		return $data;
	}
}
