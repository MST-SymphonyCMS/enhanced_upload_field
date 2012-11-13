(function($) {
	$(document).ready(function(){
		
		// Upload fields
		$('em').click(function(){
		
			var poop = $(this).parent();
		//$(this).parent().parent().children().addClass("clicked");

		//alert(poop);
		
			poop.closest('.enhanced_upload select.enhanced_upload_select_hidden').addClass("clicked");
			$('.enhanced_upload select.enhanced_upload_select_hidden').addClass('show');
		
		});
		
		// Upload fields
		/*$('<em>' + Symphony.Language.get('Remove File') + '</em>').appendTo('label.file:has(a) span.frame').on('click.admin', function(event) {
			var span = $(this).parent(),
				name = span.find('input').attr('name');

			// Prevent clicktrough
			event.preventDefault();
			
			//Show selectbox
			span.closest('.enhanced_upload select.enhanced_upload_select_hidden').addClass("clicked");
			// Add new empty file input
			span.empty().append('<input name="' + name + '" type="file">');
		});*/

	
			
	});
})(jQuery.noConflict());