/*
 * jQuery toggleSwitch plugin
 * @author Tom Kay - oridan82@gmail.com - tomkay.me
 * @version 1.0
 * @date March 14, 2013
 * @category jQuery plugin
 * @copyright (c) 2013 oridan82@gmail.com (www.tomkay.me)
 * @license CC Attribution-Share Alike 3.0 - http://creativecommons.org/licenses/by-sa/3.0/
 */
(function($){
	$.fn.extend({ 
		toggleSwitch: function(classes) {
			function UpdateStatus(obj) {
				if ($(obj).is(':checked')) $(obj).data('sw').removeClass('switch-false').addClass('switch-true');
				else $(obj).data('sw').removeClass('switch-true').addClass('switch-false');
				
				if ($(obj).is(':radio')) {
					var name = $(obj).attr('name');
					$('input[name="'+name+'"]'+' + span').each(function() {
						UpdateStatus(this);
					});
				}
			}
    		return this.each(function() {
				var obj = $(this).hide();
				var sw = $('<span class="switch"></span>').addClass(classes).data('obj',obj);
				sw.insertAfter(obj);
				obj.data('sw',sw);
				
				if($(this).attr('disabled')) sw.addClass('disabled');

				sw.on('click',function(){
					$(this).data('obj').trigger('click');
				});
				obj.on('change',function(){
					UpdateStatus(this);
				});

				UpdateStatus(obj); // set initial status
  			});
    	}
	});
})(jQuery);
