<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	//require_once(TOOLKIT.'/fields/field.upload.php');
	require_once(TOOLKIT . '/fields/field.upload.php');

	Class fieldEnhanced_Upload extends FieldUpload {
		
		public function __construct(){
			parent::__construct();

			$this->_name = __('Enhanced File Upload');
			$this->set('override', 'no');
	
		}
		
		
		/*-------------------------------------------------------------------------
			Settings:
		-------------------------------------------------------------------------*/

        public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
        	
        	parent::displaySettingsPanel($wrapper, $errors);
			
			$label = new XMLElement('label');
            $input = Widget::Input("fields[{$this->get('sortorder')}][override]", 'yes', 'checkbox');
			if( $this->get('override') == 'yes' ) $input->setAttribute('checked', 'checked');
			$label->setValue(__('%s Allow overriding of upload directory in entries', array($input->generate())));

			$wrapper->appendChild($label);

        }

    /*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){
			if(!is_dir(DOCROOT . $this->get('destination') . '/')){
				$flagWithError = __('The destination directory, %s, does not exist.', array('<code>' . $this->get('destination') . '</code>'));
			}

			elseif(!$flagWithError && !is_writable(DOCROOT . $this->get('destination') . '/')){
				$flagWithError = __('Destination folder is not writable.') . ' ' . __('Please check permissions on %s.', array('<code>' . $this->get('destination') . '</code>'));
			}

			$label = Widget::Label($this->get('label'));
			$label->setAttribute('class', 'file');

			// Destination Folder
			$ignore = array(
				'/workspace/events',
				'/workspace/data-sources',
				'/workspace/text-formatters',
				'/workspace/pages',
				'/workspace/utilities'
			);
			$directories = General::listDirStructure(WORKSPACE . $this->get('destination'), null, true, DOCROOT, $ignore);

			$options = array();
			$options[] = array($this->get('destination'), false, $this->get('destination'));
			var_dump($options);die;
			if(!empty($directories) && is_array($directories)){
				foreach($directories as $d) {
					$d = '/' . trim($d, '/');
					if(!in_array($d, $ignore)) $options[] = array($d, ($this->get('destination') == $d), $d);
				}
			}

			if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', __('Optional')));
			
			$span = new XMLElement('span', NULL, array('class' => 'frame'));
			
			//Allow selection of a child folder to upload the image
			$choosefolder = Widget::Select('fields['.$this->get('sortorder').'][destination]', $options);
			$choosefolder->setAttribute('class','enhanced_upload file');
			if($this->get('override') != 'no' ) $span->appendChild($choosefolder);
			// Add this back in when JS is figured out - && !$data['file']
			
			//Render the upload field or reflect the uploaded file stored in DB.
			if($data['file']) $span->appendChild(new XMLElement('span', Widget::Anchor('/workspace' . $data['file'], URL . '/workspace' . $data['file'])));			
			
			$span->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, $data['file'], ($data['file'] ? 'hidden' : 'file')));

			$label->appendChild($span);

			if($flagWithError != NULL) $wrapper->appendChild(Widget::Error($label, $flagWithError));
			else $wrapper->appendChild($label);
		
    }

    	public function commit(){
    	
		//var_dump($this->get());die;
		
		if(!parent::commit()) return false;
		
			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();

			$fields['destination'] = $this->get('destination');
			$fields['validator'] = ($fields['validator'] == 'custom' ? NULL : $this->get('validator'));
			$fields['override'] = $this->get('override');
			
			//var_dump($fields);die;

			return FieldManager::saveSettings($id, $fields);
		}
	
		public function prepareTableValue($data, XMLElement $link=NULL, $entry_id = null){
			
			//var_dump($data);
			
			if(!$file = $data['file']){
				if($link) return parent::prepareTableValue(null, $link);
				else return parent::prepareTableValue(null);
			}

			if($link){
				$link->setValue(basename($file));
				//var_dump($file);
				return $link->generate();
			}

			else{
				$link = Widget::Anchor(basename($file), URL . '/workspace' . $file);
				return $link->generate();
			}
			
			
		}
	
		
}