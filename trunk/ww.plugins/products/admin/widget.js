function Products_widgetTypeChanged() {
	$('form.products').each(function(){
		var $this=$(this);
		if ($this.find('select[name=widget_type]').val() == 'Pie-Chart') {
			$this.find('.diameter').css('display', 'block');
		}
		else {
			$this.find('.diameter').css('display', 'none');
		}
	});
}
$('form.products select[name=widget_type]').live('change', Products_widgetTypeChanged);
