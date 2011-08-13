
<?php
/**
  * forum api
  *
  * PHP Version 5
  *
  * @category   Whatever
  * @package    WebworksWebme
  * @subpackage Forum
  * @author     Kae Verens <kae@kvsites.ie>
  * @license    GPL Version 2
  * @link       www.kvweb.me
 */

function Forum_post() {
	if (!isset($_SESSION['userdata']) || !$_SESSION['userdata']['id']) {
		exit;
	}
	$title=$_REQUEST['title'];
	$body=$_REQUEST['body'];
	$forum_id=(int)@$_REQUEST['forum_id'];
	$thread_id=(int)@$_REQUEST['thread_id'];
	$errs=array();
	if (!$body) {
		$errs[]='no post body supplied';
	}
	if (!$forum_id) {
		$errs[]='no forum selected';
	}
	else {
		$forum=dbRow('select * from forums where id='.$forum_id);
		if (!$forum || !count($forum)) {
			$errs[]='forum does not exist';
		}
		else {
			if ($thread_id) {
				$title='';
				$thread=dbRow(
					'select * from forums_threads where id='
					.$thread_id.' and forum_id='.$forum_id
				);
				if (!$thread || !count($thread)) {
					$errs[]='thread does not exist or doesn\'t belong to that forum';
				}
			}
			else {
				if (!$title) {
					$errs[]='no thread title supplied';
				}
			}
		}
	}
	if (count($errs)) {
		return array('errors'=>$errs);
	}
	if (!$thread_id) {
		dbQuery(
			'insert into forums_threads values(0,'
			.$forum_id.',0,"'.addslashes($title).'",'
			.$_SESSION['userdata']['id'].',now(),0,now(),0,'
			.$_SESSION['userdata']['id'].')'
		);
		$thread_id=dbLastInsertId();
	}
	else { // add user to the subscribers list
		$subscribers=dbOne(
			'select subscribers from forums_threads where id='.$thread_id,
			'subscribers'
		);
		$subscribers=explode(',', $subscribers);
		if (!in_array($_SESSION['userdata']['id'], $subscribers)) {
			$subscribers[]=$_SESSION['userdata']['id'];
			dbQuery(
				'update forums_threads set subscribers="'.join(',', $subscribers)
				.'" where id='.$thread_id
			);
		}
	}
	// { insert the post into the thread
	$moderated=1-$forum['is_moderated'];
	dbQuery(
		'insert into forums_posts set thread_id='.$thread_id
		.',author_id='.$_SESSION['userdata']['id'].',created_date=now()'
		.',body="'.addslashes($body).'",moderated='.$moderated
	);
	$post_id=(int)dbLastInsertId();
	
	dbQuery(
		'update forums_threads set num_posts=num_posts+1,'
		.'last_post_date=now(),last_post_by='.$_SESSION['userdata']['id']
		.' where id='.$thread_id
	);
	// }
	// { alert subscribers that a new post is available
	$post_author=User::getInstance($_SESSION['userdata']['id']);
	$row=dbRow(
		'select subscribers,name from forums_threads where id='.$thread_id
	);
	$subscribers=explode(',', $row['subscribers']);
	$url=Page::getInstance($forum['page_id'])->getRelativeUrl()
		.'?forum-f='.$forum_id
		.'&forum-t='.$thread_id.'&'.$post_id.'#forum-c-'.$post_id;
	foreach ($subscribers as $subscriber) {
		$user=User::getInstance($subscriber);
		mail(
			$user->get('email'),
			'['.$_SERVER['HTTP_HOST'].'] '.$row['name'],
			"A new post has been added to this forum thread which you are subscribed"
			" to.\n\n"
			.'http://www.'.$_SERVER['HTTP_HOST'].$url."\n\n"
			.$post_author->get('name')." said:\n".str_repeat('=', 80)."\n".$body."\n"
			.str_repeat('=', 80),
			'From: no-reply@'.$_SERVER['HTTP_HOST']."\nReply-to: no-reply@"
			.$_SERVER['HTTP_HOST']
		);
	}
	// }
	return array(
		'forum_id'=>$forum_id,
		'thread_id'=>$thread_id,
		'post_id'=>$post_id
	)
}
/**
  * delete a message from a forum
  *
	* @return array
	*/
function Forum_delete() {
	if (!isset($_SESSION['userdata']) || !$_SESSION['userdata']['id']) {
		exit;
	}
	$post_id=(int)$_REQUEST['id'];
	$errs=array();
	if (!$post_id) {
		$errs[]='no post selected';
	}
	if (!Core_isAdmin()
		&& dbOne(
			'select author_id from forums_posts where id='.$post_id,
			'author_id'
		) != $_SESSION['userdata']['id']
	) {
		$errs[]='this is not your post, or post does not exist';
	}
	if (count($errs)) {
		echo json_encode(
			array(
				'errors'=>$errs
			)
		);
		exit;
	}
	dbQuery('delete from forums_posts where id='.$post_id);
	dbQuery(
		'update forums_threads set num_posts='
		.'(select count(id) as ids from forums_posts '
		.'where thread_id=forums_threads.id)'
	);
	return array(
		'ok'=>1
	);
}