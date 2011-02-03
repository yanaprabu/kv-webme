var Notice=new Class({
	initialize:function(message){
		var id=_Notices++;
    $j('<div id="notice_'+id+'" class="notice ui-state-highlight ui-corner-all" style="display:none;">')
      .append(message)
      .appendTo(document.body).show('normal',function(){
        setTimeout('$j("#notice_'+id+'").hide("normal");', 2000);
    });
	}
});
var _Notices=0;
