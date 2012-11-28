(function($) {
	$(function(){
		
		// Upload fields
		/*$('<em>' + Symphony.Language.get('Remove File') + '</em>').appendTo('label.file:has(a) span.enhanced_frame').on('click.admin', function(event) {
			var span = $(this).parent(),
				name = span.find('input').attr('name');

			// Prevent clicktrough
			event.preventDefault();
			
			//Show selectbox
			span.find('select.enhanced_upload_select_hidden').addClass("show");
			// Add new empty file input
			span.find('a.enhanced_file, input.enhanced_file').remove();
			span.prepend('<input name="' + name + '" type="file">');
			span.find('em').remove();
		}); */
		
		// prevent clicks on the select box from opening the file dialog
		$('.field-enhanced_upload span.frame select').on('click.admin', function (e) {
			e.stopPropagation();
			return false;
		});
	
	});
	
})(jQuery);