function Products_screen(page) {
	Core_sidemenu(
		[
			'Products', 'Categories', 'Types',
			'Relation Types', 'Import', 'Export Data'
		],
		'products',
		page
	);
	window['Products_screen'+page]();
}
function Products_screenCategories() {
	document.location="/ww.admin/plugin.php?_plugin=products&_page=categories";
}
function Products_screenExportData() {
	$('#content')
		.html('<p>Your export should start downloading in a moment.</p>');
	document.location='/a/p=products/f=adminExport';
}
function Products_screenImport() {
	var $content=$('#content').empty(), $wrapper, html;
	// { wrapper
	var html='<div id="import-wrapper"><ul>'
		+'<li><a href="#import-file">Import File</a></li>'
		+'<li><a href="#import-images">Import Images</a></li>'
		+'</ul></div>';
	$wrapper=$(html).appendTo($content);
	// }
	// { import file
	var table='<div id="import-file">'
		+'<table id="import-table">'
		// { example file
		+'<tr id="product-types-example"><th>Download CSV Example</th>'
		+'<td><select><option value="0"> -- all product types --'
		+' </option></select></th>'
		+'<td><a href="#" class="__ ui-button" lang-context="core">Download</a>'
		+'</td></tr>'
		// }
		// { ___ 
		+'<tr><td colspan="3"><hr/></td></tr>'
		// }
		// { delimiter character
		+'<tr><th>Delimiter character</th>'
		+'<td><select id="product-types-delimiter"><option>,</option>'
		+'<option>;</option></select></td>'
		+'<td>The character used to separate values in the CSV file.</td>'
		+'</tr>'
		// }
		// { delete file after import
		+'<tr><th>Delete CSV file after import</th>'
		+'<td><input id="product-types-delete-after" type="checkbox"/></td>'
		+'<td>Delete the uploaded CSV file after import.</td>'
		+'</tr>'
		// }
		// { file url
		+'<tr id="product-types-upload"><th>Upload Products File</th>'
		+'<td><input id="product-types-file-url"'
		+' placeholder="leave blank for default"/></td>'
		+'<td><input type="button" class="upload"'
		+' id="product-types-upload-button" value="Select and Upload"/>'
		+'<span id="product-types-upload-button-uploaded"></span></td>'
		+'</tr>'
		// }
		// { images directory
		+'<tr><th>Images Directory</th>'
		+'<td><input id="product-types-images-dir"'
		+' placeholder="leave blank for default"/></td>'
		+'<td>Directory where images are placed. Images should be .jpg or .png'
		+' files with the stock number as the first part of the file name.</td>'
		+'</tr>'
		// }
		// { ___ 
		+'<tr><td colspan="3"><hr/></td></tr>'
		// }
		+'<tr><td><button>Import</td></tr>'
		+'</table></div>';
	$(table).appendTo($wrapper);
	// { populate fields
	$('#product-types-delimiter')
		.change(function() {
			Core_saveAdminVars('productsImportDelimiter', $(this).val());
		})
		.val(adminVars.productsImportDelimiter);
	$('#product-types-delete-after')
		.change(function() {
			Core_saveAdminVars('productsImportDeleteAfter', $(this).is(':checked'));
		})
		.attr('checked', adminVars.productsImportDeleteAfter);
	$('#product-types-file-url')
		.change(function() {
			Core_saveAdminVars('productsImportFileUrl', $(this).val());
		})
		.val(adminVars.productsImportFileUrl);
	$('#product-types-images-dir')
		.change(function() {
			Core_saveAdminVars('productsImportImagesDir', $(this).val());
		})
		.val(adminVars.productsImportImagesDir);
	var $select=$('#product-types-example select');
	$.post('/a/p=products/f=typesGet', function(ret) {
		for (var i=0;i<ret.iTotalRecords;++i) {
			$select.append('<option value="'+ret.aaData[i][1]+'">'
				+ret.aaData[i][0]+'</option>');
		}
	});
	$('#import-table a').click(function() {
		var ptype=+$select.val();
		document.location='/a/p=products/f=adminTypesGetSampleImport/ptypeid='
			+ptype;
	});
	// }
	// { setup upload button
	$('#product-types-upload-button')
		.css('height',20)
		.uploadify({
			'swf':'/j/jquery.uploadify/uploadify.swf',
			'auto':'true',
			'checkExisting':false,
			'cancelImage':'/i/blank.gif',
			'buttonImage':'/i/choose-file.png',
			'height':20,
			'width':81,
			'uploader':'/a/p=products/f=adminImportFileUpload',
			'postData':{
				'PHPSESSID':sessid
			},
			'upload_success_handler':function(file, data, response){
				ret=eval('('+data+')');
				if (ret.ok) {
					$('#product-types-upload-button-uploaded').text('file uploaded');
				}
			}
		});
	// }
	// { setup import button
	$('#content button').click(function() {
		$.post('/a/p=products/f=adminImportFile', function(ret) {
			var $dialog=$('<p>'+ret.message+'</p>').dialog({
				'modal':true,
				'close':function() {
					$dialog.remove();
				}
			});
		});
	});
	// }
	// }
	// { import images
	html='<div id="import-images"><table>'
		// { from
		+'<tr><th>Import from</th><td><select id="import-images-from">'
		+'<option value="local directory">Local Directory</option>'
		+'<option value="Amazon API">Amazon API</option>'
		+'</select></td></tr>'
		// }
		// { options
		+'<tr><th>Options</th><td id="import-images-options">&nbsp;</td></tr>'
		// }
		+'</table><hr/><button id="import-images-button">import</button>'
		+'<div id="import-images-status"/></div>';
	$(html).appendTo($wrapper);
	function updateImportImageOptions() {
		var $wrapper=$('#import-images-options').empty(),
			val=$('#import-images-from').val();
		switch (val) {
			case 'Amazon API': // {
				var html='<p>This option will import from products found in Amazon'
					+' which have the same EAN code.</p>'
					+'<table><tr><th>Access Key</th><td><input'
					+' id="import-amazon-public-key"/></td></tr>'
					+'<tr><th>Secret Key</th><td><input type="password"'
					+' id="import-amazon-private-key"/></td></tr>'
					+'<tr><th>Associate Tag</th><td><input'
					+' id="import-amazon-associate-tag"/></td></tr>'
					+'</table>';
				$(html).appendTo($wrapper);
				$('#import-amazon-private-key')
					.change(function() {
						Core_saveAdminVars('productsImportAmazonPrivateKey', $(this).val());
					})
					.val(adminVars.productsImportAmazonPrivateKey);
				$('#import-amazon-public-key')
					.change(function() {
						Core_saveAdminVars('productsImportAmazonPublicKey', $(this).val());
					})
					.val(adminVars.productsImportAmazonPublicKey);
				$('#import-amazon-associate-tag')
					.change(function() {
						Core_saveAdminVars(
							'productsImportAmazonAssociateTag',
							$(this).val()
						);
					})
					.val(adminVars.productsImportAmazonAssociateTag);
			break; // }
			default: // {
				$wrapper.append('todo');
			break; // }
		}
	}
	updateImportImageOptions();
	$('#import-images-from').change(updateImportImageOptions);
	$('#import-images-button').click(function() {
		var import_type=$('#import-images-from').val();
		switch(import_type) {
			case 'Amazon API': // {
				var $this=$(this);
				$this.attr('disabled', true);
				var $status=$('#import-images-status');
				$status.html('retrieving list of product EANs');
				$.post('/a/p=products/f=adminGetProductsWithEan', function(ret) {
					var i=0;
					var products=ret;
					function importImage() {
						var product=products[i];
						if (product.ean.length!=13
							|| product.ean.replace(/[0-9]*/, '')!=''
						) {
							i++;
							if (i<=products.length) {
								setTimeout(importImage, 1);
							}
							return;
						}
						$.post('/a/p=products/f=adminImportDataFromAmazon', {
							'id':product.id,
							'ean':product.ean,
							'access_key':adminVars.productsImportAmazonPublicKey,
							'secret_key':adminVars.productsImportAmazonPrivateKey,
							'associate_key':adminVars.productsImportAmazonAssociateTag
						}, function(ret) {
							i++;
							$status.html(
								'completed: '+parseInt((i/products.length)*100)+'%, '
								+product.ean+': '+ret.message
							);
							if (i<=products.length) {
								setTimeout(importImage, 1);
							}
						});
					}
					importImage();
				});
			break; // }
			default: // {
				return alert('todo');
			break; // }
		}
	});
	// }
	$wrapper.tabs();
}
function Products_screenProducts() {
	document.location="/ww.admin/plugin.php?_plugin=products&_page=products";
}
function Products_screenRelationTypes() {
	document.location="/ww.admin/plugin.php?_plugin=products&_page=relation-types";
}
function Products_screenTypes() {
	$('#content')
		.html('<button>add new product type</button>'
			+'<table id="product-types-list"><thead>'
			+'<tr><th>Name</th><th>edit</th><th>&nbsp;</th></tr>'
			+'</thead><tbody></tbody></table>');
	$('#content button').click(function() {
		function showAddNewDialog() {
			$('#dialog').remove();
			$.post('/a/p=products/f=typesTemplatesGet', function(ret) {
				var html='<div id="dialog"><strong>Product template to start from'
					+'</strong>';
				for (var i=0;i<ret.length;++i) {
					html+='<br/><button>'+ret[i]+'</button>';
				}
				$(html+'</div>').dialog({'modal':true});
				$('#dialog button').click(function(){
					$.post('/a/p=products/f=adminTypeCopy/id='+$(this).text(),
						function(ret) {
							Products_typeEdit(ret.id);
							$('#dialog').remove();
						}
					);
				});
			});
		}
		function showCopyDialog() {
			$('#dialog').remove();
			$.post('/a/p=products/f=typesGet', function(ret) {
				var html='<div id="dialog"><strong>Which type to copy</strong>';
				for (var i=0;i<ret.aaData.length;++i) {
					var item=ret.aaData[i];
					html+='<br/><button id="b'+item[1]+'">'+item[0]+'</button>'
				}
				$(html+'</div>').dialog({"modal":true});
				$('#dialog button').click(function() {
					var id=$(this).attr('id').replace(/b/, '');
					$.post('/a/p=products/f=adminTypeCopy/id='+id, function(ret) {
						Products_typeEdit(ret.id);
						$('#dialog').remove();
					});
				});
			});
		}
		if ($('#product-types-list .sorting_1').length) {
			$('<div id="dialog"><button class="new">from template</button>'
				+'<br /><button class="copy">copy existing type</button></div>'
			).dialog({"modal":true});
			$('#dialog .new').click(showAddNewDialog);
			$('#dialog .copy').click(showCopyDialog);
		}
		else {
			showAddNewDialog();
		}
	});
	var params={
		"sAjaxSource": '/a/p=products/f=typesGet',
		"bProcessing": true,
		"bServerSide": true,
		"fnRowCallback": function( nRow, aData, iDisplayIndex ) {
			var id=aData[1];
			nRow.id='product-types-list-row'+id;
			$('td:nth-child(2)', nRow)
				.html('<a href="javascript:Products_typeEdit('+id+');">edit</a>');
			$('td:nth-child(3)', nRow)
				.html('<a href="javascript:Products_typeDelete('+id+');">[x]</a>');
			return nRow;
		}
	};
	if (jsvars.datatables['product-types-list']) {
		params["iDisplayLength"]=jsvars.datatables['product-types-list'].show;
	}
	window.openDataTable=$('#product-types-list')
		.dataTable(params);
}
function Products_typeDelete(id) {
	var name=$('#product-types-list-row'+id).find('td:first-child').text();
	if (!confirm('Are you sure you want to remove the product type named "'
		+name+'"?')) {
		return;
	}
	$.post('/a/p=products/f=adminTypeDelete/id='+id, function() {
		window.openDataTable.fnDraw(1);
	});
}
function Products_typeEdit(id) {
	var activeTab=-1, tdata=false;
	function showDataFields(panel, index) {
		$(panel).empty();
		var fields=tdata.data_fields;
		var html='<div id="df1">';
		for (var i=0;i<fields.length;++i) {
			html+='<h3 id="f'+i+'"><a href="#">'+htmlspecialchars(fields[i].n)
				+'</a></h3><div/>';
		}
		$(html+'</div>')
			.appendTo(panel)
			.accordion({
				'changestart':function(e, ui) {
					updateDataFields();
					$('.product-field-panel').remove();
					if (!ui.newHeader.context) {
						return;
					}
					var index=+ui.newHeader.context.id.replace(/f/, '');
					var field=fields[index];
					field.e=field.e||'';
					var $wrapper=$(ui.newContent.context).next();
					$wrapper
						.append('<table class="product-field-panel wide">'
							+'<tr><th>Name</th><td class="pfp-name"></td>'
							+'<td rowspan="5" id="pfp-type-specific"></td></tr>'
							+'<tr><th>Type</th><td class="pfp-type"></td></tr>'
							+'<tr><th>Required</th><td class="pfp-required"></td></tr>'
							+'<tr><th>User-entered</th><td class="pfp-user-entered"></td>'
							+'</tr><tr><td colspan="2"><a href="javascript:;" id="pfp-delete"'
							+' title="delete">[x]</a></td></tr>'
							+'</table>'
						);
					$('<input/>').val(field.n).appendTo('.pfp-name', $wrapper);
					// { required
					$('<select><option value="0">No</option>'
						+'<option value="1">Yes</option></select>'
					)
						.val(field.r).appendTo('.pfp-required', $wrapper);
					// }
					// { user-entered
					$('<select><option value="0">No</option>'
						+'<option value="1">Yes</option></select>'
					)
						.val(field.u).appendTo('.pfp-user-entered', $wrapper);
					// }
					// { type
					$('<select><option>inputbox</option><option>textarea</option>'
						+'<option>date</option><option>checkbox</option>'
						+'<option>selectbox</option><option>selected-image</option>'
						+'<option>hidden</option><option>colour</option>'
						+'</select>'
					)
						.val(field.t).appendTo('.pfp-type', $wrapper);
					// }
					// { delete button
					$('#pfp-delete').click(function() {
						if (!confirm('are you sure you want to remove this?')) {
							return;
						}
						var dfs=[];
						for (var i=0;i<fields.length;++i) {
							if (i!=index) {
								dfs.push(fields[i]);
							}
						}
						tdata.data_fields=dfs;
						showDataFields(panel, -1);
					});
					// }
					switch (field.t) {
						case 'date': // {
							$('<p>What format should the date be in? '
								+'<a href="http://docs.jquery.com/UI/Datepicker/formatDate" '
								+'target="_blank">examples</a></p>')
								.appendTo('#pfp-type-specific');
							return $('<input/>')
								.val(field.e||'yy-mm-dd')
								.appendTo('#pfp-type-specific');
							// }
						case 'selectbox': // {
							return showExtrasSelectbox(field.e, field.tr);
							// }
						default: // { text
							// }
					}
				},
				'active':false,
				'autoHeight':false,
				'animated':false,
				'collapsible':true,
				'create':function() {
					if (index) {
						$('#df1').accordion('activate', index);
					}
				}
			});
		$('<button>add field</button>')
			.click(function() {
				var name=prompt('What do you want to name this field?', 'fieldname');
				if (name===false) {
					return;
				}
				tdata.data_fields.push({'n':name,'r':0,'t':'inputbox','u':0});
				showDataFields(panel, tdata.data_fields.length-1);
			})
			.appendTo(panel);
	}
	function showExtrasSelectbox(e, tr) {
		function addRow(opt, val) {
			var $row=$('<tr/>').appendTo('#pfp-type-specific-table');
			var bits=rows[i]?rows[i].split('|'):['', 0],
				$inp1=$('<input class="wide"/>').val(bits[0]).change(checkRows),
				$inp2=$('<input class="number"/>').val(+bits[1]||0);
			$('<td/>').append($inp1).appendTo($row);
			$('<td/>').append($inp2).appendTo($row);
		}
		function checkRows() {
			var empty=0;
			$('#pfp-type-specific-table td:first-child input').each(function() {
				if ($(this).val()=='') {
					empty=1;
				}
			});
			if (!empty) {
				addRow('', 0);
			}
		}
		var $td=$('#pfp-type-specific');
		$(
			'<table id="pfp-type-specific-table" class="wide tight">'
			+'<tr><th>Option</th>'
			+'<th title="how much this adds to the price of a product">$£€</th>'
			+'</tr></table>'
		).appendTo($td);
		var rows=e.split("\n");
		for (var i=0;i<rows.length;++i) {
			var bits=rows[i].split('|');
			addRow(bits[0], +bits[1]||0);
		}
		var $tr=$('<input type="checkbox" id="pfp-type-specific-tr"/>')
			.attr('checked', tr || false);
		$td.append($tr, 'are these options translateable words?');
		checkRows();
	}
	function showMain(panel) {
		$('<table class="wide">'
			+'<tr><th>Name</th><td id="pte1"></td></tr>'
			+'<tr><th>Are products of this type for sale?</th>'
			+'<td id="pte2"></td></tr><tr id="pte4"/><tr id="pte5"/>'
			+'<tr><th>Default Category</th><td><select id="pte6"/></td></tr>'
			+'<tr><th>If no image is uploaded for the product, what image should '
			+'be shown?</th><td id="pte3"></td></tr>'
			+'</table>'
		).appendTo(panel);
		// { name
		$('<input/>')
			.change(function(){tdata.name=$(this).val();})
			.val(tdata.name||"default")
			.appendTo('#pte1');
		// }
		// { for sale
		$('<select><option value="0">No</option><option value="1">Yes</option></select>')
			.change(function(){
				tdata.is_for_sale=$(this).val();
				if (+tdata.is_for_sale) {
					addIsVoucher();
					addStockControl();
				}
				else {
					$('#pte4,#pte5').empty();
				}
			})
			.val(tdata.is_for_sale)
			.appendTo('#pte2');
		function addIsVoucher() {
			$('<th>Is it a printable voucher?</th><td><select>'
				+'<option value="">No</option><option value="1">Yes</option>'
				+'</select></td>')
				.appendTo('#pte4');
			var $select=$('#pte4 select');
			if (+tdata.is_voucher) {
				$select.val(1);
			}
			$select
				.change(function() {
					var $this=$(this);
					var val=+$this.val();
					if (val) {
						$('<a href="#">template</a>')
							.click(showVoucherTemplate)
							.insertAfter($this);
					}
					else {
						$this.siblings('a').remove();
					}
				})
				.change();
		}
		function addStockControl() {
			$('<th>Use Stock Control?</th><td><select>'
				+'<option value="0">No</option><option value="1">Yes</option>'
				+'</select></td>')
				.appendTo('#pte5');
			$('#pte5 select')
				.change(function() {
					tdata.stock_control=$(this).val();
				})
				.val(tdata.stock_control);
		}
		if (+tdata.is_for_sale) {
			addIsVoucher();
			addStockControl();
		}
		// }
		// { default category
		$('#pte6')
			.html(
				'<option value="'+tdata.default_category+'">'
				+tdata.default_category_name+'</option>'
			)
			.remoteselectoptions({
				"url":'/a/p=products/f=adminCategoriesGetRecursiveList'
			});
		// }
		var src=id
			?'/a/f=getImg/w=64/h=64/products/types/'+id+'/image-not-found.png'
			:'/ww.plugins/products/i/not-found-64.png';
		$('<img id="pte3-img" src="'+src+'?'+Math.random()+'"/>'
			+'<input name="image_not_found" id="pte3-inp"/>'
		)
			.appendTo('#pte3');
		$('#pte3-inp')
			.uploadify({
				'swf':'/j/jquery.uploadify/uploadify.swf',
				'auto':'true',
				'checkExisting':false,
				'cancelImage':'/i/blank.gif',
				'height':20,
				'width':81,
				'buttonImage':'/i/choose-file.png',
				'uploader':'/a/p=products/f=adminTypeUploadMissingImage/id='+id,
				'postData':{
					'PHPSESSID':sessid
				},
				'upload_success_handler':function(file, data, response){
					$('#pte3-img').attr('src', data+'?'+Math.random());
				}
			});
	}
	function showMultiView(panel) {
		$('<div><ul><li><a href="#ts1">body</a></li>'
			+'<li><a href="#ts2">header</a></li><li><a href="#ts3">footer</a></li>'
			+'</ul><div id="ts1"/><div id="ts2"/><div id="ts3"/></div>')
			.appendTo(panel)
			.tabs();
		$('<textarea>')
			.val(tdata.multiview_template)
			.appendTo('#ts1')
			.ckeditor(CKEditor_config);
		$('<textarea>')
			.val(tdata.multiview_template_header)
			.appendTo('#ts2')
			.ckeditor(CKEditor_config);
		$('<textarea>')
			.val(tdata.multiview_template_footer)
			.appendTo('#ts3')
			.ckeditor(CKEditor_config);
		$('<a href="#" class="docs" page="/ww.plugins/products/docs/codes.html">codes</a>')
			.appendTo(panel);
	}
	function showSingleView(panel) {
		$('<textarea/>')
			.val(tdata.singleview_template)
			.appendTo(panel)
			.ckeditor(CKEditor_config);
		$('<a href="#" class="docs" page="/ww.plugins/products/docs/codes.html">codes</a>')
			.appendTo(panel);
	}
	function showVoucherTemplate() {
		var html=tdata.voucher_template||'';
		var $template=$('<textarea/>')
			.val(html)
			.dialog({
				"width":700,
				"height":400,
				"close":function() {
					$template.remove();
				},
				"buttons":{
					"save":function() {
						tdata.voucher_template=$template.val();
						$template.remove();
					}
				}
			});
		$template.ckeditor(CKEditor_config);
		if (html=='') {
			$.post('/a/p=products/f=adminProductTypeVoucherTemplateSample',
				function(ret) {
					$template.val(ret.html);
				});
		}
		return false;
	}
	function updateDataFields() {
		var $panel=$('#t1>div>div.ui-accordion-content-active');
		var index=$panel.index('#t1>div>div');
		if (index<0) {
			return;
		}
		tdata.data_fields[index].n=$('.pfp-name input').val();
		tdata.data_fields[index].r=$('.pfp-required select').val();
		tdata.data_fields[index].u=$('.pfp-user-entered select').val();
		switch (tdata.data_fields[index].t) {
			case 'date': // {
				tdata.data_fields[index].e=$('#pfp-type-specific input')
					.val()||'yy-mm-dd';
				break; // }
			case 'selectbox': // {
				var e=[];
				$('#pfp-type-specific tr').each(function() {
					var $inps=$(this).find('input');
					if ($inps.length && $inps[0].value!='') {
						e.push($inps[0].value+'|'+$inps[1].value);
					}
				});
				tdata.data_fields[index].e=e.join("\n");
				tdata.data_fields[index].tr=$('#pfp-type-specific-tr').attr('checked');
				break; // }
		}
		tdata.data_fields[index].t=$('.pfp-type select').val();
	}
	function updateMain() {
		tdata.name=$('#pte1 input').val();
		tdata.is_for_sale=+$('#pte2 select').val();
		tdata.stock_control=+$('#pte5 select').val();
		tdata.default_category=+$('#pte6').val();
		if (tdata.is_for_sale) {
			tdata.is_voucher=+$('#pte4 select').val();
		}
	}
	function updateMultiView() {
		tdata.multiview_template=$('#ts1 textarea').val();
		tdata.multiview_template_footer=$('#ts2 textarea').val();
		tdata.multiview_template_header=$('#ts3 textarea').val();
	}
	function updateSingleView() {
		tdata.singleview_template=$('#t3 textarea').val();
	}
	function updateValues() {
		switch(activeTab) {
			case 0: // { main
				return updateMain();
				// }
			case 1: // { data fields
				return updateDataFields();
				// }
			case 2: // { multiview
				return updateMultiView();
				// }
			case 3: // { singleview
				return updateSingleView();
				// }
		}
	}
	$.post('/a/p=products/f=typeGet/id='+id, function(res) {
		tdata=res;
		var $content=$('#content')
			.html('<a href="javascript:Products_screenTypes()">Product Types</a>');
		$('<div id="product-types-edit-form"><ul>'
			+'<li><a href="#t0">Main Details</a></li>'
			+'<li><a href="#t1">Data Fields</a></li>'
			+'<li><a href="#t2">Multi-View Template</a></li>'
			+'<li><a href="#t3">Single-View Template</a></li>'
			+'</ul><div id="t0"/><div id="t1"/><div id="t2"/><div id="t3"/></div>'
		)
			.appendTo($content)
			.tabs({
				'select':updateValues,
				'show':function(e, ui) {
					$('#product-types-edit-form>div').empty();
					activeTab=ui.index;
					switch (ui.index) {
						case 0: // { main
							return showMain(ui.panel);
							// }
						case 1: // { data fields
							return showDataFields(ui.panel);
							// }
						case 2: // { multiview
							return showMultiView(ui.panel);
							// }
						case 3: // { singleview
							return showSingleView(ui.panel);
							// }
					}
				}
			});
		$('<button>Save</button>')
			.click(function() {
				updateValues();
				$.post('/a/p=products/f=adminTypeEdit', {
					'data': tdata
				}, function(ret) {
					alert('product type saved');
				});
			})
			.appendTo($content);
	});
}
