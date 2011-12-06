<?php
function OnlineStore_adminCapture() {
	$ids=explode(',', $_REQUEST['ids']);
	$errors=array();
	$ok=array();
	foreach ($ids as $id) {
		$id=(int)$id;
		$r=dbRow(
			'select total,status, authorised, meta from online_store_orders'
			.' where id='.$id
		);
		if ($r['authorised']!=1) {
			$errors[]='transaction '.$id.' is no longer authorised.'
				.' maybe it was already captured?';
			continue;
		}
		$meta=json_decode($r['meta'], true);
		$merchantid=dbOne(
			'select value from page_vars,pages where page_id=pages.id and '
			.'pages.type="online-store" and '
			.'page_vars.name="online_stores_quickpay_merchantid"',
			'value'
		);
		$message=array(
			'protocol'=>4,
			'msgtype'=>'capture',
			'merchant'=>$merchantid,
			'amount'=>$meta['amount'],
			'transaction'=>$meta['transaction']
		);
		$md5fields=array(
			'protocol'=>4,
			'msgtype'=>'capture',
			'merchant'=>$merchantid,
			'amount'=>$meta['amount'],
			'transaction'=>$meta['transaction'],
			'secret'=>dbOne(
				'select value from page_vars,pages where page_id=pages.id and '
				.'pages.type="online-store" and '
				.'page_vars.name="online_stores_quickpay_secret"',
				'value'
			)
		);
		$message['md5check'] = md5(implode('', $md5fields));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://secure.quickpay.dk/api');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = str_replace("\n", '', curl_exec($ch));
		curl_close($ch);
		if (strpos($response, 'qpstat>000<')!==false) {
			$meta['qpsuccess']=$response;
			$status=$r['status']<1?1:$r['status'];
			dbQuery(
				'update online_store_orders set status='.$status.', authorised=0,'
				.'meta="'.addslashes(json_encode($meta)).'" where id='.$id
			);
			$ok[]=$id;
		}
		else {
			$meta['qpfail']=$response;
			dbQuery(
				'update online_store_orders set meta="'.addslashes(json_encode($meta))
				.'" where id='.$id
			);
			$switchkey=preg_replace('/.*<qpstat>([^<]*)<.*/', '\1', $response);
			switch ($switchkey) {
				case '004': // {
					$ok[]=$id;
					$errors[]='transaction '.$id.' has already been captured.';
					$status=$r['status']<1?1:$r['status'];
					dbQuery(
						'update online_store_orders set status='.$status.', authorised=0 '
						.'where id='.$id
					);
				break; // }
				default: // {
					$errors[]='unknown error on transaction '.$id.': '.$switchkey;
				// }
			}
		}
	}
	return array(
		'errors'=>$errors,
		'ok'=>$ok
	);
}
/**
	* retrieve a list of ordered items
	*
	* @return array
	*/
function OnlineStore_adminOrderItemsList() {
	$id=(int)$_REQUEST['id'];
	$r=dbRow('select * from online_store_orders where id='.$id);
	if (!$r || !$r['items']) {
		return array('error'=>'no such order');
	}
	$items=array();
	foreach (json_decode($r['items'], true) as $item) {
		$items[]=array(
			'id'=>$item['id'],
			'name'=>(@$item['name']?$item['name']:$item['short_desc']),
			'amt'=>$item['amt']
		);
	}
	return $items;
}

/**
	* change the payment status of an Online-Store order
	*
	* @return array status
	*/
function OnlineStore_adminChangeOrderStatus() {
	$id=(int)$_REQUEST['id'];
	$status=(int)$_REQUEST['status'];
	
	if ($status==1) {
		require dirname(__FILE__).'/verify/process-order.php';
		OnlineStore_processOrder($id);
	}
	else {
		dbQuery('update online_store_orders set status='.$status.' where id='.$id);
	}
	return array('ok'=>1);
}
function OnlineStore_adminRedeemVoucher() {
	$oid=(int)@$_REQUEST['oid'];
	$pid=@$_REQUEST['pid'];
	$order=dbRow('select * from online_store_orders where id='.$oid);
	$items=json_decode($order['items'], true);
	$item=$items[$pid];
	$items[$pid]['voucher_redeemed']=1;
	$order['items']=json_encode($items);
	dbQuery(
		'update online_store_orders set items="'.addslashes($order['items'])
		.'" where id='.$oid
	);
	echo '<p>This voucher has been marked as Redeemed.</p>';
	exit;
}