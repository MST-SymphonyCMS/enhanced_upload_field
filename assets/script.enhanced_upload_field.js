/*
 * Script that adapts behavior from the inherited
 * Upload Field behavior
 */
(function($) {
	
	"use strict";
	
	var
	
	init = function(){
		
		// prevent clicks on the select box from opening the file dialog
		$('.field-enhanced_upload span.frame select').on('click.admin', function (e) {
			e.stopPropagation();
			return false;
		});
	
	};
	
	$(init);
	
})(jQuery);