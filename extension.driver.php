<?php
    class Extension_Enhanced_Upload_Field extends Extension {

    const FIELD_TABLE = 'tbl_fields_enhanced_upload';

    public function getSubscribedDelegates(){
			return array(
						array(
							'page' => '/system/preferences/',
							'delegate' => 'AddCustomPreferenceFieldsets',
							'callback' => 'appendPreferences'
						),
						
						array(
							'page' => '/system/preferences/',
							'delegate' => 'Save',
							'callback' => 'savePreferences'
						),						
					);
    }
		
    public function savePreferences($context){
			
			$pairs = array();
			
			$conf = array_map('trim', $context['settings']['enhanced-upload-field']);
			
			$context['settings']['enhanced-upload-field'] = array(
				'override-path' => (isset($conf['override-path']) ? 'yes' : 'no')		
			);

    }

    public function appendPreferences($context){
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Enhanced Upload Field')));
			$ul = new XMLElement('ul');
			$ul->setAttribute('class', 'group');
			$li = new XMLElement('li');
			$label = Widget::Label();
			$input = Widget::Input('settings[enhanced-upload-field][override-path]', 'yes', 'checkbox');
			if(Symphony::Configuration()->get('override-path', 'enhanced-upload-field') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . __('Allow ability to override'));
			$li->appendChild($label);		
			$ul->appendChild($li);			
			$fieldset->appendChild($ul);
			
			$context['wrapper']->appendChild($fieldset);				
    }


    public function uninstall(){
    	
			try{
				Symphony::Database()->query(sprintf(
					"DROP TABLE `%s`",
					self::FIELD_TABLE
				));
			}
			catch( DatabaseException $dbe ){
				// table deosn't exist
			}

			return true;
				
			Symphony::Configuration()->remove('enhanced-upload-field');			
			
			Administration::instance()->saveConfig();
							
	}


		public function install(){
			return Symphony::Database()->query(sprintf(
				"CREATE TABLE `%s` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`destination` varchar(255) NOT NULL,
					`override` enum('yes','no') default 'yes',
					`validator` varchar(50),
					`unique` enum('yes','no') default 'yes',
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
				self::FIELD_TABLE
			));
	
			Symphony::Configuration()->set('override-path', 'no', 'enhanced-upload-field');
		
			Administration::instance()->saveConfig();
			
    }
}