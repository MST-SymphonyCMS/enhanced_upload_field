(function($) {
    $('#filetree').fileTree({ root: '/workspace/', script: 'http://localhost:8090/registration/extensions/enhanced_upload_field/lib/filetree/connectors/jqueryFileTree.php' }, function(file) {
        alert(file);
    });
	
	alert('Shit iss real yall');
	
})(jQuery);