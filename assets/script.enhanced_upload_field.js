(function($) {
	$(document).ready(function(){
		
		// Upload fields
		$('<em>' + Symphony.Language.get('Remove File') + '</em>').appendTo('label.file:has(a) span.enhanced_frame').on('click.admin', function(event) {
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
		}); 

	
			
	});
})(jQuery.noConflict());