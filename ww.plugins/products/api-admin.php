<?php
/**
	* admin functions for Products
	*
	* PHP version 5.2
	*
	* @category None
	* @package  None
	* @author   Kae Verens <kae@kvsites.ie>
	* @license  GPL 2.0
	* @link     http://kvsites.ie/
	*/

// { Products_adminCategoriesGetRecursiveList

/**
	* get a recursive list of all categories
	*
	* @return array categories
	*/
function Products_adminCategoriesGetRecursiveList(
	$params=array(),
	$pid=0,
	$level=0
) {
	$sql='select id,name from products_categories where parent_id='.$pid
		.' order by name';
	$cats=dbAll($sql);
	$arr=array();
	foreach ($cats as $cat) {
		$arr[' '.$cat['id']]=str_repeat(' - ', $level).$cat['name'];
		$arr=array_merge(
			$arr,
			Products_adminCategoriesGetRecursiveList($params, $cat['id'], $level+1)
		);
	}
	return $arr;
}

// }
// { Products_adminCategoryDelete

/**
	* delete a category
	*
	* @return null
	*/
function Products_adminCategoryDelete() {
	if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) {
		exit;
	}
	$id=(int)$_REQUEST['id'];
	if ($id==1) {
		return array('status'=>0);
	}
	$parent=dbOne(
		'select parent_id from products_categories where id='.$id,
		'parent_id'
	);
	dbQuery(
		'update products_categories set parent_id='.$parent.' where parent='.$id
	);
	dbQuery('delete from products_categories where id='.$id);
	return array('status'=>1);
}

// }
// { Products_adminCategoryEdit

/**
	* edit a category
	*
	* @return array the category data
	*/
function Products_adminCategoryEdit() {
	if (!is_numeric(@$_REQUEST['id']) || @$_REQUEST['name']==''
		|| strlen(@$_REQUEST['associated_colour'])!=6
	) {
		exit;
	}
	dbQuery(
		'update products_categories set name="'.addslashes($_REQUEST['name']).'"'
		.',enabled="'.((int)$_REQUEST['enabled']).'"'
		.',associated_colour="'.addslashes($_REQUEST['associated_colour']).'"'
		.' where id='.$_REQUEST['id']
	);
	Core_cacheClear('products');
	$pageid=dbOne(
		'select page_id from page_vars where name="products_category_to_show" '
		.'and value='.$_REQUEST['id'],
		'page_id'
	);
	if ($pageid) {
		dbQuery('update pages set special = special|2 where id='.$pageid);
	}
	$data=Products_adminCategoryGetFromID($_REQUEST['id']);
	return $data;
}

// }
// { Products_adminCategoryGetFromID

/**
	* get a category row from its id
	*
	* @param int $id the category ID
	*
	* @return array the data
	*/
function Products_adminCategoryGetFromID($id) {
	$ps=dbAll(
		'select product_id from products_categories_products where category_id='
		.$id
	);
	$products=array();
	$pageid= dbOne(
		'select page_id from page_vars where name="products_category_to_show" a'
		.'nd value='.$id, 'page_id'
	);
	foreach ($ps as $p) {
		$products[]=$p['product_id'];
	}
	$data=array(
		'attrs'=>dbRow(
			'select id,associated_colour,name,enabled,parent_id from products_cat'
			.'egories where id='.$id
		),
		'products'=>$products,
		'hasIcon'=>file_exists(USERBASE.'/f/products/categories/'.$id.'/icon.png')
	);
	if (isset($pageid)) {
		$page= Page::getInstance($pageid);
		if ($page) {
			$url= $page->getRelativeUrl();
			$data['page']= $url;
		}
	}
	return $data;
}

// }
// { Products_adminCategoryGet

/**
	* get details about a category
	*
	* @return array the details
	*/
function Products_adminCategoryGet() {
	if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) {
		exit;
	}
	return Products_adminCategoryGetFromID($_REQUEST['id']);
}

// }
// { Products_adminCategoryNew

/**
	* add a new category
	*
	* @return array category data
	*/
function Products_adminCategoryNew() {
	if (!is_numeric(@$_REQUEST['parent_id']) || @$_REQUEST['name']=='') {
		exit;
	}
	dbQuery(
		'insert into products_categories set name="'.addslashes($_REQUEST['name'])
		.'",enabled=1,parent_id='.$_REQUEST['parent_id']
	);
	$id=dbOne('select last_insert_id() as id', 'id');
	$data=Products_adminCategoryGetFromID($id);
	return $data;
}

// }
// { Products_adminCategoryMove

/**
	* move a category
	*
	* @return array status of the move
	*/
function Products_adminCategoryMove() {
	$id=(int)$_REQUEST['id'];
	$p_id=(int)$_REQUEST['parent_id'];
	dbQuery('update products_categories set parent_id='.$p_id.' where id='.$id);
	if (isset($_REQUEST['order'])) {
		$order=explode(',', $_REQUEST['order']);
		for ($i=0;$i<count($order);++$i) {
			$id=(int)$order[$i];
			dbQuery('update products_categories set sortNum='.$i.' where id='.$id);
		}
	}
	return Products_adminCategoryGetFromID($id);
}

// }
// { Products_adminCategoryProductsEdit

/**
	* edit a category's contained products
	*
	* @return null
	*/
function Products_adminCategoryProductsEdit() {
	if (!is_numeric(@$_REQUEST['id'])) {
		exit;
	}
	dbQuery(
		'delete from products_categories_products where category_id='
		.$_REQUEST['id']
	);
	foreach ($_REQUEST['s'] as $p) {
		dbQuery(
			'insert into products_categories_products set product_id='
			.((int)$p).',category_id='.$_REQUEST['id']
		);
	}
	Core_cacheClear('products');
	return Products_adminCategoryGetFromID($_REQUEST['id']);
}

// }
// { Products_adminCategorySetIcon

/**
	* set the icon of a category
	*
	* @return array result of upload
	*/
function Products_adminCategorySetIcon() {
	$cat_id=(int)$_REQUEST['cat_id'];
	$dir=USERBASE.'/f/products/categories/'.$cat_id;
	@mkdir($dir, 0777, true);
	$tmpname=$_FILES['file_upload']['tmp_name'];
	CoreGraphics::resize($tmpname, $dir.'/icon.png', 128, 128);
	return array('ok'=>1);
}

// }
// { Products_adminDatafieldsList

/**
	* get data fields in <option> format
	*
	* @return null
	*/
function Products_adminDatafieldsList() {
	$fields=array('_name');
	$filter='';
	if ($_REQUEST['other_GET_params']) {
		if (is_numeric($_REQUEST['other_GET_params'])) { // product type
			$filter=' where id='.(int)$_REQUEST['other_GET_params'];
		}
		elseif (strpos($_REQUEST['other_GET_params'], 'c')===0) {
			$cat=(int)str_replace('c', '', $_REQUEST['other_GET_params']);
			if ($cat==0) {
				$rs=dbAll('select distinct product_type_id from products');
			}
			else {
				$rs=dbAll(
					'select product_id from products_categories_products where category_id='
					.$cat
				);
				$arr=array();
				foreach ($rs as $r) {
					$arr[]=$r['product_id'];
				}
				if (!count($arr)) {
					exit;
				}
				$rs=dbAll(
					'select distinct product_type_id from products where id in ('
					.join(',', $arr).')'
				);
			}
			$arr=array();
			foreach ($rs as $r) {
				$arr[]=$r['product_type_id'];
			}
			if (!count($arr)) {
				exit;
			}
			$filter=' where id in ('.join(',', $arr).')';
		}
	}
	$rs=dbAll('select data_fields from products_types'.$filter);
	foreach ($rs as $r) {
		$fs=json_decode($r['data_fields']);
		foreach ($fs as $f) {
			$fields[]=$f->n;
		}
	}
	$fields=array_unique($fields);
	asort($fields);
	$arr=array();
	foreach ($fields as $field) {
		$arr[$field]=$field;
	}
	return $arr;
}


// }
// { Products_adminExport

/**
  * Gets the data for all the products and prompts the user to save it
	*
	* @return null
	*/
function Products_adminExport() {
	$filename = 'webme_products_export_'.date('Y-m-d').'.csv';
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	// { Get the headers
	$fields = dbAll('describe products');
	$row = '';
	foreach ($fields as $field) {
	    $row.= '"_'.$field['Field'].'",';
	}
	$row.="\"_categories\"\n";
	$contents = $row;
	// } 
	// { Get the data
	$results = dbAll('select * from products');
	foreach ($results as $product) {
		$row = '';
		foreach ($fields as $field) {
			$row.= '"'.str_replace('"', '""', $product[$field['Field']]).'",';
		}
		$cats = dbAll(
			'select category_id from products_categories_products '
			.'where product_id = '.$product['id']
		);
		$stringCats = '';
		foreach ($cats as $cat) {
			$info
				= dbRow(
					'select name, parent_id 
					from products_categories
					where id ='.$cat['category_id']
				);
			$thisCat = '';
			$catName = $info['name'];
			$thisCat.=$catName.',';
			$parent = $info['parent_id'];
			while ($parent>0) {
				$info = dbRow(
					'select name,parent_id from products_categories where id='.$parent
				);
				$parentName = $info['name'];
				$thisCat = $parentName.'>'.$thisCat;
				$parent = $info['parent_id'];
			}
			$stringCats.= $thisCat;
		}
		$stringCats = substr($stringCats, 0, (strrpos(',', $stringCats)-1));
		$stringCats= '"'.$stringCats.'"';
		$row.= $stringCats;
		$contents.=$row."\n";
	}
	echo $contents;
	// }
}

// }
// { Products_adminImportFile

/**
	* import from an uploaded file
	*
	* @return status
	*/
function Products_adminImportFile() {
	// { get import vals
	$vars=(object)dbAll(
		'select varname,varvalue from admin_vars where admin_id='
		.$_SESSION['userdata']['id'].' and varname like "productsImport%"',
		'varname'
	);
	if (!@$vars->productsImportDeleteAfter['varvalue']) {
		$vars->productsImportDeleteAfter=array(
			'varvalue'=>false
		);
	}
	if (!@$vars->productsImportDelimiter['varvalue']) {
		$vars->productsImportDelimiter=array(
			'varvalue'=>','
		);
	}
	if (!@$vars->productsImportFileUrl['varvalue']) {
		$vars->productsImportFileUrl=array(
			'varvalue'=>'ww.cache/products/import.csv'
		);
	}
	if (!@$vars->productsImportImagesDir['varvalue']) {
		$vars->productsImportImagesDir=array(
			'varvalue'=>'ww.cache/products/images'
		);
	}
	// }
	$fname=USERBASE.$vars->productsImportFileUrl['varvalue'];
	if (strpos($fname, '..')!==false) {
		return array('message'=>'invalid file url');
	}
	if (!file_exists($fname)) {
		return array('message'=>'file not uploaded');
	}
	$handle=fopen($fname, 'r');
	$row=fgetcsv($handle, 1000, $vars->productsImportDelimiter['varvalue']);
	$headers=array();
	foreach ($row as $k=>$v) {
		if ($v) {
			$headers[$v]=$k;
		}
	}
	if (!isset($headers['_name'])
		|| !isset($headers['_ean'])
		|| !isset($headers['_stocknumber'])
		|| !isset($headers['_type'])
		|| !isset($headers['_categories'])
	) {
		return array(
			'message'=>'missing required headers. please use the Download link'
			.' to get a sample import file'
		);
	}
	$product_types=array();
	$imported=0;
	while (
		($data=fgetcsv(
			$handle, 1000, $vars->productsImportDelimiter['varvalue']
		))!==false
	) {
		$id=0;
		$stocknumber=$data[$headers['_stocknumber']];
		$type=$data[$headers['_type']];
		if (!$type) {
			$type='default';
		}
		if ($product_types[$type]) {
			$type_id=$product_types[$type];
		}
		else {
			$type_id=(int)dbOne(
				'select id from products_types where name="'.addslashes($type).'"',
				'id'
			);
			if (!$type_id) {
				$type_id=(int)dbOne('select id from products_types limit 1', 'id');
			}
			$product_types[$type]=$type_id;
		}
		$name=$data[$headers['_name']];
		$ean=$data[$headers['_ean']];
		$categories=$data[$headers['_categories']];
		if ($stocknumber) {
			$id=(int)dbOne(
				'select id from products where stock_number="'
				.addslashes($stocknumber)
				.'"', 'id'
			);
			if ($id) {
				dbQuery(
					'update products set ean="'.addslashes($ean).'"'
					.',product_type_id='.$type_id
					.',name="'.addslashes($name).'"'
					.' where id='.$id
				);
			}
		}
		if (!$id) {
			$sql='insert into products set '
				.'stock_number="'.addslashes($stocknumber).'"'
				.',product_type_id='.$type_id
				.',name="'.addslashes($name).'"'
				.',ean="'.addslashes($ean).'"'
				.',date_created=now()'
				.',enabled=1'
				.',data_fields="{}"'
				.',online_store_fields="{}"';
			dbQuery($sql);
			$id=dbLastInsertId();
		}
		$row=dbRow(
			'select data_fields,online_store_fields from products where id='.$id
		);
		$data_fields=json_decode($row['data_fields'], true);
		$os_fields=json_decode($row['online_store_fields'], true);
		foreach ($headers as $k=>$v) {
			if (preg_match('/^_/', $k)) {
				continue;
			}
			foreach ($data_fields as $k2=>$v2) {
				if ($v2['n']==$k) {
					unset($data_fields[$k2]);
				}
			}
			$data_fields[]=array(
				'n'=>$k,
				'v'=>$data[$v]
			);
		}
		if (@$data[$headers['_price']]) {
			$os_fields['_price']=(float)@$data[$headers['_price']];
			$os_fields['_saleprice']=(float)@$data[$headers['_saleprice']];
			$os_fields['_bulkprice']=(float)@$data[$headers['_bulkprice']];
			$os_fields['_bulkamount']=(int)@$data[$headers['_bulkamount']];
		}
		else {
			$os_fields=array();
		}
		dbQuery(
			'update products set '
			.'data_fields="'.addslashes(json_encode($data_fields)).'"'
			.',online_store_fields="'.addslashes(json_encode($os_fields)).'"'
			.' where id='.$id
		);
		$imported++;
	}
	Core_cacheClear('products');
	if ($imported) {
		return array('message'=>'Imported '.$imported.' products');
	}
	return array('message'=>'No products imported');
}

// }
// { Products_adminImportFileUpload

/**
	* handle an uploaded file for import
	*
	* @return status
	*/
function Products_adminImportFileUpload() {
	$vars=(object)dbAll(
		'select varname,varvalue from admin_vars where admin_id='
		.$_SESSION['userdata']['id'].' and varname like "productsImport%"',
		'varname'
	);
	if (!@$vars->productsImportFileUrl['varvalue']) {
		$vars->productsImportFileUrl=array(
			'varvalue'=>'ww.cache/products/import.csv'
		);
	}
	$fname=USERBASE.$vars->productsImportFileUrl['varvalue'];
	if (strpos($fname, '..')!==false) {
		return array('message'=>'invalid file url');
	}
	@mkdir(dirname($fname), 0777, true);
	$from=$_FILES['Filedata']['tmp_name'];
	move_uploaded_file($from, $fname);
	return array('ok'=>1);
}

// }
// { Products_adminImportDataFromAmazon

/**
	* retrieve an image from amazon for a product
	*
	* @return array array of products
	*/
function Products_adminImportDataFromAmazon() {
	$pid=(int)$_REQUEST['id'];
	$ean=$_REQUEST['ean'];
	if (strlen($ean)!=13) {
		return array('message'=>'EAN too short');
	}
	$access_key=$_REQUEST['access_key'];
	$private_key=$_REQUEST['secret_key'];
	$associate_tag=$_REQUEST['associate_key'];
	$pdata=Product::getInstance($pid);
	// { image
	if (!isset($pdata->images_directory) 
		|| !$pdata->images_directory
		|| $pdata->images_directory=='/'
		|| !is_dir(USERBASE.'/f/'.$pdata->images_directory)
	) {
		if (!is_dir(USERBASE.'/f/products/product-images')) {
			mkdir(USERBASE.'/f/products/product-images', 0777, true);
		}
		$pdata->images_directory='/products/product-images/'
			.md5(rand().microtime());
		mkdir(USERBASE.'/f'.$pdata->images_directory);
		dbQuery(
			'update products set images_directory="'.$pdata->images_directory
			.'" where id='.$pid
		);
	}
	$image_exists=0;
	$dir=new DirectoryIterator(USERBASE.'/f'.$pdata->images_directory);
	foreach ($dir as $f) {
		if ($f->isDot()) {
			continue;
		}
		$image_exists++;
	}
	// }
	if ($image_exists) {
		return array('message'=>'already_exists');
	}
	$obj=new AmazonProductAPI($access_key, $private_key, $associate_tag);
	try{
		$result=$obj->getItemByEan($ean, '');
		if (!@$result->Items->Item) {
			return array('message'=>'not found');
		}
		// { description
		$description=(array)$result->Items->Item->EditorialReviews->EditorialReview->Content;
		$description=$description[0];
		$do_description=1;
		if ($description) {
			$meta=json_decode(dbOne(
				'select data_fields from products where id='.$pid,
				'data_fields'
			), true);
			foreach ($meta as $k=>$v) {
				if (!isset($v['n'])) {
					unset($meta[$k]);
					continue;
				}
				if ($v['n']=='description') {
					if ($v['v']) {
						$do_description=0;
					}
					else {
						unset($meta[$k]);
					}
				}
			}
			if ($do_description) {
				$meta[]=array(
					'n'=>'description',
					'v'=>$description
				);
			}
			dbQuery(
				'update products set data_fields="'.addslashes(json_encode($meta))
				.'" where id='.$pid
			);
		}
		// }
		// { image
		$img=(array)$result->Items->Item->LargeImage->URL;
		$img=$img[0];
		if (!$image_exists) {
			copy($img, USERBASE.'/f/'.$pdata->images_directory.'/default.jpg');
		}
		// }
		return array('message'=>'found and imported');
	}
	catch(Exception $e) {
		return array('message'=>'error... '.$e->getMessage());
	}
}

// }
// { Products_adminGetProductsWithEan

/**
	* get a list of all products that have an EAN
	*
	* @return array array of products
	*/
function Products_adminGetProductsWithEan() {
	return dbAll('select id,ean from products where ean');
}

// }
// { Products_adminPageDelete

/**
	* delete a product's page
	*
	* @return array status
	*/
function Products_adminPageDelete() {
	$pid=(int)$_REQUEST['pid'];
	$pageID=dbOne(
		'select page_id from page_vars where name= "products_product_to_show" '
		.'and value='.$pid.' limit 1', 
		'page_id'
	);
	dbQuery('delete from pages where id='.$pageID);
	dbQuery('delete from page_vars where page_id='.$pageID);
	Core_cacheClear();
	return array('ok'=>1);
}

// }
// { Products_adminProductDatafieldsGet

/**
	* get details about the data fields a product has
	*
	* @return array data fields
	*/
function Products_adminProductDatafieldsGet() {
	$typeID = $_REQUEST['type'];
	$productID = $_REQUEST['product'];
	if (!is_numeric($typeID)||!is_numeric($productID)) {
		exit('Invalid arguments');
	}
	if (!dbOne('select id from products_types where id = '.$typeID, 'id')) {
		return array('status'=>0, 'message'=>'Could not find this type');
	}
	$data = array();
	$typeData = dbRow(
		'select data_fields, is_for_sale from products_types '
		.'where id = '.$typeID
	);
	$typeFields = json_decode($typeData['data_fields']);
	$data['type'] = $typeFields;
	$data['isForSale'] = $typeData['is_for_sale'];
	if ($productID != 0) {
		$product 
			= dbRow(
				'select data_fields, product_type_id 
				from products where id = '.$productID
			);
		$productFields = json_decode($product['data_fields']);
		$oldType 
			= dbOne(
				'select data_fields 
				from products_types 
				where id = '.$product['product_type_id'],
				'data_fields'
			);
		$oldType = json_decode($oldType);
		$data['product'] = $productFields;
		$data['oldType'] = $oldType;
	}
	return $data;
}

// }
// { Products_adminProductsList

/**
	* get products in <option> format
	*
	* @return null
	*/
function Products_adminProductsList() {
	$ps=dbAll('select id,name from products order by name');
	$arr=array();
	foreach ($ps as $v) {
		$arr[$v['id']]=$v['name'];
	}
	return $arr;
}

// }
// { Products_adminProductTypeVoucherTemplateSample

/**
	* retrieve an example template for a product of type voucher
	*
	* @return the sample template
	*/
function Products_adminProductTypeVoucherTemplateSample() {
	return array(
		'html'=>file_get_contents(
			dirname(__FILE__).'/templates/product-type-voucher.html'
		)
	);
}

// }
// { Products_adminTypeCopy

/**
	* copy a product type
	*
	* @return array status of the copy
	*/
function Products_adminTypeCopy() {
	if (is_numeric($_REQUEST['id'])) {
		$id=(int)$_REQUEST['id'];
		$r=dbRow('select * from products_types where id='.$id);
	}
	else {
		$n=$_REQUEST['id'];
		if (strpos($n, '..')!==false) {
			exit;
		}
		$r=json_decode(
			file_get_contents(dirname(__FILE__).'/templates/'.$n.'.json'), true
		);
		$r['data_fields']=json_encode($r['data_fields']);
	}
	dbQuery(
		'insert into products_types set name="'.addslashes($r['name'].' (copy)')
		.'",multiview_template="'.addslashes($r['multiview_template']).'",'
		.'singleview_template="'.addslashes($r['singleview_template']).'",'
		.'data_fields="'.addslashes($r['data_fields']).'",'
		.'is_for_sale='.((int)$r['is_for_sale']).','
		.'is_voucher='.((int)$r['is_voucher']).','
		.'default_category='.((int)$r['default_category']).','
		.'voucher_template="'.addslashes($r['voucher_template']).'",'
		.'multiview_template_header="'.addslashes($r['multiview_template_header'])
		.'",'
		.'multiview_template_footer="'.addslashes($r['multiview_template_footer'])
		.'",meta="'.addslashes($r['meta']).'"'
	);
	Core_cacheClear();
	return array(
		'id'=>dbLastInsertId()
	);
}

// }
// { Products_adminTypeDelete

/**
	* delete a product type
	*
	* @return null
	*/
function Products_adminTypeDelete() {
	$id=(int)$_REQUEST['id'];
	dbQuery("delete from products_types where id=$id");
	Core_cacheClear();
	return true;
}

// }
// { Products_adminTypeEdit

/**
	* edit a product type
	*
	* @return array
	*/
function Products_adminTypeEdit() {
	$d=$_REQUEST['data'];
	$data_fields=json_encode($d['data_fields']);
	dbQuery(
		'update products_types set name="'.addslashes($d['name'])
		.'",multiview_template="'
		.addslashes(Core_sanitiseHtmlEssential($d['multiview_template']))
		.'",singleview_template="'
		.addslashes(Core_sanitiseHtmlEssential($d['singleview_template']))
		.'",data_fields="'.addslashes($data_fields).'",'
		.'is_for_sale='.(int)$d['is_for_sale'].','
		.'is_voucher='.(int)$d['is_voucher'].','
		.'stock_control='.(int)$d['stock_control'].','
		.'default_category='.(int)$d['default_category'].','
		.'voucher_template="'
		.addslashes(Core_sanitiseHtmlEssential($d['voucher_template'])).'",'
		.'prices_based_on_usergroup="'
		.addslashes($d['prices_based_on_usergroup'])
		.'",multiview_template_header="'
		.addslashes(Core_sanitiseHtmlEssential($d['multiview_template_header']))
		.'",multiview_template_footer="'
		.addslashes(Core_sanitiseHtmlEssential($d['multiview_template_footer']))
		.'" where id='.(int)$d['id']
	);
	Core_cacheClear();
	return array('ok'=>1);
}

// }
// { Products_adminTypeUploadMissingImage

/**
	* upload a new image to mark products that have no uploaded image
	*
	* @return null
	*/
function Products_adminTypeUploadMissingImage() {
	$id=(int)$_REQUEST['id'];
	if (!file_exists(USERBASE.'/f/products/types/'.$id)) {
		mkdir(USERBASE.'/f/products/types/'.$id, 0777, true);
	}
	$imgs=new DirectoryIterator(USERBASE.'/f/products/types/'.$id);
	foreach ($imgs as $img) {
		if ($img->isDot()) {
			continue;
		}
		unlink($img->getPathname());
	}
	$from=$_FILES['Filedata']['tmp_name'];
	$to=USERBASE.'/f/products/types/'.$id.'/image-not-found.png';
	move_uploaded_file($from, $to);
	Core_cacheClear();
	echo '/a/f=getImg/w=64/h=64/products/types/'.$id.'/image-not-found.png';
	exit;
}

// }
// { Products_adminTypesGetSampleImport

/**
	* download a CSV version of a product type in importable format
	*
	* @return null
	*/
function Products_adminTypesGetSampleImport() {
	$ptypeid=(int)$_REQUEST['ptypeid'];
	if ($ptypeid) {
		$ptypes=dbAll('select * from products_types where id='.$ptypeid);
	}
	else {
		$ptypes=dbAll('select * from products_types');
	}
	$are_any_for_sale=0;
	// { get list of data field names
	$names=array();
	foreach ($ptypes as $p) {
		if ($p['is_for_sale']) {
			$are_any_for_sale=1;
		}
		$dfs=json_decode($p['data_fields']);
		foreach ($dfs as $df) {
			if (!in_array($df->n, $names)) {
				$names[]=$df->n;
			}
		}
	}
	// }
	header('Content-type: text/csv; Charset=utf-8');
	header(
		'Content-Disposition: attachment; filename="product-types-'.$ptypeid.'.csv"'
	);
	// { header
	$row=array('_stocknumber', '_name', '_ean');
	if ($are_any_for_sale) {
		$row[]='_price';
		$row[]='_sale_price';
		$row[]='_bulk_price';
		$row[]='_bulk_amount';
	}
	foreach ($names as $n) {
		$row[]=$n;
	}
	$row[]='_type';
	$row[]='_categories';
	echo Products_arrayToCSV($row);
	// }
	// { sample rows
	foreach ($ptypes as $p) {
		$row=array('stock_number', 'name', 'barcode');
		if ($are_any_for_sale) {
			$row[]='0.00';
			$row[]='0.00';
			$row[]='0.00';
			$row[]='0';
		}
		foreach ($names as $n) {
			$row[]='';
		}
		$row[]=$p['name'];
		$row[]='';
		echo Products_arrayToCSV($row);
	}
	// }
	exit;
}

// }
