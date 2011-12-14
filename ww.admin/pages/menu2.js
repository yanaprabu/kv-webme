$(function(){
	$.jstree._themes='/j/jstree/themes/';
	$('#pages-wrapper')
		.jstree({
			'contextmenu': {
				'items': {
					'rename':false,
					'ccp':false,
					'create' : {
						'label'	: "Create Page", 
						'visible'	: function (NODE, TREE_OBJ) { 
							if (NODE.length != 1) {
								return 0;
							}
							return TREE_OBJ.check("creatable", NODE); 
						}, 
						'action':addNew,
						'separator_after' : true
					},
					'remove' : {
						'label'	: "Delete Page", 
						'visible'	: function (NODE, TREE_OBJ) { 
							if (NODE.length != 1) {
								return 0;
							}
							return TREE_OBJ.check("deletable", NODE); 
						}, 
						'action':pages_delete,
						'separator_after' : true
					},
					'copy' : {
						'label'	: "Copy Page", 
						'visible'	: function (NODE, TREE_OBJ) { 
							return true;
						}, 
						'action':pages_copy
					},
					'view' : {
						'label' : "View Page",
						'action':function(node) {
							window.open(
								'/?pageid='+node[0].id.replace(/.*_/,''),
								'_blank'
							);
						}
					}
				}
			},
			'dnd': {
				'drag_target': false,
				'drop_target': false
			},
			"json_data" : {
				"ajax" : {
					"url" : "/a/f=adminPageChildnodes",
					"data" : function (n) {
						return { id : n.attr ? n.attr("id") : 0 };
					}
				},
				"progressive_render" : true,
				"progressive_unload" : true
			},
			'plugins': [
				"themes", "json_data", "ui", "crrm", "contextmenu", "dnd"
			]
		})
		.bind('move_node.jstree',function(e, ref){
			var data=ref.args[0];
			var node=data.o[0];
			setTimeout(function(){
				var p=node.parentNode.parentNode;
				var nodes=$(p).find('>ul>li');
				if (p.tagName=='DIV') {
					p=-1;
				}
				var new_order=[];
				for (var i=0;i<nodes.length;++i) {
					new_order.push(nodes[i].id.replace(/.*_/,''));
				}
				$.post('/a/f=adminPageMove', {
					'id':node.id.replace(/.*_/,''),
					'parent_id':(p==-1?0:p.id.replace(/.*_/,'')),
					'order':new_order
				});
			},1);
		});
	var div=$('<div><i>right-click for options</i><br /><br /></div>');
	$('<button>add main page</button>')
		.click(addNew)
		.appendTo(div);
	div.appendTo('div.left-menu');
	$('#pages-wrapper a').live('click',function(e){
		var node=e.target.parentNode;
		document.getElementById('page-form-wrapper')
			.src="pages/form.php?id="+node.id.replace(/.*_/,'');
		$('#pages-wrapper').jstree('select_node',node);
	});
	$('<div class="resize-bar-w"/>')
		.css('cursor','e-resize')
		.draggable({
			helper:function(){
				return document.createElement('span');
			},
			start:function(e){
				this.offsetStart=e.pageX;
				this.hasLeftOffsetStart=parseInt(
					$('div.has-left-menu').css('left')
				);
				this.menuWidthStart=parseInt(
					$(this).closest('div.left-menu').css('width')
				);
			},
			drag:function(e){
				var offset=e.pageX-this.offsetStart;
				$(this).closest('div.left-menu').css('width', this.menuWidthStart+offset);
				$('div.has-left-menu').css('left', this.hasLeftOffsetStart+offset);
			},
			stop:function(){
			}
		})
		.appendTo('div.left-menu');
	function addNew(node) {
		var pid=node[0]?node[0].id.replace(/.*_/,''):0;
		$('<table id="newpage-dialog">'
			+'<tr><th>Name</th><td><input name="name"/></td></tr>'
			+'<tr><th>Page Type</th><td><select name="type">'
			+'<option value="0">normal</option></select></td></tr>'
			+'</table>'
		).dialog({
			modal:true,
			close:function(){
				$('#newpage-dialog').remove();
			},
			buttons:{
				'Create Page': function() {
					var name=$('#newpage-dialog input[name="name"]').val();
					if (name=='') {
						return alert('Name must be provided');
					}
					$.post('/a/f=adminPageEdit', {
						'parent':pid,
						'name':name,
						'type':$('#newpage-dialog select[name="type"]').val()
					}, function(ret) {
						pages_add_node(ret.alias, ret.id, ret.pid);
						$('#page-form-wrapper').attr('src', 'pages/form.php?id='+ret.id);
					});
					$(this).dialog('close');
				},
				'Cancel': function() {
					$(this).dialog('close');
				}
			}
		});
		$('#newpage-dialog select[name=type]')
			.remoteselectoptions({url:'/a/f=adminPageTypesList'});
		return false;
	}
});

function pages_copy(node, tree) {
	$.post('/a/f=adminPageCopy', {
		'id':node[0].id.replace(/.*_/,'')
	}, function(ret){
		pages_add_node(ret.name, ret.id, ret.pid);
		document.getElementById('page-form-wrapper')
			.src="pages/form.php?id="+ret.id;
	}, 'json');
}
function pages_delete(node,tree){
	if (!confirm("Are you sure you want to delete this page?")) {
		return;
	}
	$.post('/a/f=adminPageDelete/id='+node[0].id.replace(/.*_/, ''), function(){
		if (node.find('li').length) {
			document.location=document.location.toString();
		}
		else {
			$('#pages-wrapper').jstree('remove', node);
		}
	});
}
function pages_add_node(name,id,pid){
	var pel=null;
	var $jstree=$('#pages-wrapper');
	if (pid) {
		pel='#page_'+pid;
	}
	else{
		pel='#pages-wrapper';
	}
	var node=$jstree.jstree(
		'create',
		pel,
		"last",
		{'attr':{'id':'page_'+id},'data':name},
		function(){
			$jstree.jstree('deselect_all');
			$jstree.jstree('select_node','#page_'+id);
		},
		true
	);
}