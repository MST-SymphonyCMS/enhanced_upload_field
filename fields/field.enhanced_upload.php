<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	//require_once(TOOLKIT.'/fields/field.upload.php');
	require_once(TOOLKIT . '/fields/field.upload.php');

	Class fieldEnhanced_Upload extends FieldUpload {
        
		
		/**
		 *
		 * Name of the field table
		 * @var string
		 */
		const FIELD_TBL_NAME = 'tbl_fields_enhanced_upload';
		
		
		public function __construct(){
			parent::__construct();

			$this->_name = __('Enhanced File Upload');
			$this->_required = true;

			$this->set('location', 'sidebar');
			$this->set('required', 'no');
			$this->set('override', 'no');
	
		}
		

	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/

		public function entryDataCleanup($entry_id, $data=NULL){
			$file_location = WORKSPACE . '/' . ltrim($data['file'], '/');

			if(is_file($file_location)){
				General::deleteFile($file_location);
			}

			parent::entryDataCleanup($entry_id);

			return true;
		}

		public static function getMetaInfo($file, $type){
			$meta = array();

			if(!file_exists($file) || !is_readable($file)) return $meta;

			$meta['creation'] = DateTimeObj::get('c', filemtime($file));

			if(General::in_iarray($type, fieldUpload::$imageMimeTypes) && $array = @getimagesize($file)){
				$meta['width'] = $array[0];
				$meta['height'] = $array[1];
			}

			return $meta;
		}

		
		
		/*-------------------------------------------------------------------------
			Settings:
		-------------------------------------------------------------------------*/

        public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
        	
        	parent::displaySettingsPanel($wrapper, $errors);
			// Destination Folder
			$ignore = array(
				'/workspace/events',
				'/workspace/data-sources',
				'/workspace/text-formatters',
				'/workspace/pages',
				'/workspace/utilities'
			);
			
			$directories = General::listDirStructure(WORKSPACE, null, true, DOCROOT, $ignore);

			$label = Widget::Label(__('Destination Directory'));

			$options = array();
			$options[] = array('/workspace', false, '/workspace');
			if(!empty($directories) && is_array($directories)){
				foreach($directories as $d) {
					$d = '/' . trim($d, '/');
					if(!in_array($d, $ignore)) $options[] = array($d, ($this->get('destination') == $d), $d);
				}
			}

			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][destination]', $options));
			
            $input = Widget::Input("fields[{$this->get('sortorder')}][override]", 'yes', 'checkbox');
			if( $this->get('override') == 'yes' ) $input->setAttribute('checked', 'checked');
			$label->setValue(__('%s Allow overriding of upload directory in entries', array($input->generate())));
			
			if(isset($errors['destination'])) $wrapper->appendChild(Widget::Error($label, $errors['destination']));
			else $wrapper->appendChild($label);
			
			$this->buildValidationSelect($wrapper, $this->get('validator'), 'fields['.$this->get('sortorder').'][validator]', 'upload');

			$div = new XMLElement('div', NULL, array('class' => 'two columns'));
			$this->appendRequiredCheckbox($div);
			$this->appendShowColumnCheckbox($div);
			$wrapper->appendChild($div);

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
			$directories = General::listDirStructure(WORKSPACE, null, true, DOCROOT, $ignore);

			$options = array();
			$options[] = array($this->get('destination'), false, $this->get('destination'));
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
			if($this->get('override') != 'no') $span->appendChild($choosefolder);
			
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
			if(!$file = $data['file']){
				if($link) return parent::prepareTableValue(null, $link);
				else return parent::prepareTableValue(null);
			}

			if($link){
				$link->setValue(basename($file));
				return $link->generate();
			}

			else{
				$link = Widget::Anchor(basename($file), URL . '/workspace' . $file);
				return $link->generate();
			}
		}
	
		
}