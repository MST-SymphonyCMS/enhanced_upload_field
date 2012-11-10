<?php
    class extension_enhanced_upload_field extends Extension {

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
			
			$force_include = preg_split('/[\r\n]+/i', $conf['force-include'], -1, PREG_SPLIT_NO_EMPTY);
			$force_include = array_map('trim', $force_include);
			
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
				
			Symphony::Configuration()->remove('enhanced-upload-field');			
			
			Administration::instance()->saveConfig();
							
	}


    public function install(){
			
			Symphony::Configuration()->set('override-path', 'no', 'enhanced-upload-field');
		
			Administration::instance()->saveConfig();
			
    }
}