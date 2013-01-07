/*
 * Script that adapts behavior from the inherited
 * Upload Field behavior
 */
(function($) {
	
	"use strict";
	
	var
	
	FIELD_SELECTOR = '.field-enhanced_upload',
	KEY = 'enhanced-select',
	EVENT = 'click.admin',
	
	_preventProp = function (e) {
		e.stopPropagation();
		return false;
	},
	
	_hasOptions = function ($select) {
		return $select.find('option').length > 1;
	},
	
	_reCreateSelect = function (e) {
		var 
		select = e.data.elem.data(KEY),
		$select = e.data.frame.append(select).find('select');
		
		if (!_hasOptions($select)) {
			$select.hide();
		}
	},
	
	_eachField = function (index, elem) {
		var 
		$elem = $(elem),
		$frame = $elem.find('span.frame'),
		$select = $frame.find('select'),
		$a = $frame.find('>span>a');
		
		// prevent clicks on the select box from opening the file dialog
		$select.on(EVENT, _preventProp);
		// capture our select to be able to re-create it
		$elem.data(KEY, $select.clone().wrap('<div>').parent().html());
		// attach to remove event
		$frame.on(EVENT, 'em', {elem:$elem, frame:$frame}, _reCreateSelect);
		// if there is a file
		if ($a.length === 1) {
			// remove our select
			$select.remove();
		} 
		// if there is no sub-directory
		else if (!_hasOptions($select)) {
			// hide the select
			// do not remove it since we need its value
			// back in the POST body.
			$select.hide();
		}
	},
	
	init = function() {
		// init each field
		$('#contents').find(FIELD_SELECTOR).each(_eachField);
	};
	
	// Kick off magic
	$(init);
	
})(jQuery);