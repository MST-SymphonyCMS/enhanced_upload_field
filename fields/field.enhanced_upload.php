<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT.'/fields/field.upload.php');

	final class fieldEnhanced_Upload extends fieldUpload {
        
		public function __construct(){
			parent::__construct();

			$this->_name = __('Enhanced File Upload');
			$this->_required = true;

			$this->set('location', 'sidebar');
			$this->set('required', 'no');
			$this->set('override', 'no');
		}

	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function canFilter() {
			return true;
		}

		public function canPrePopulate(){
			return true;
		}

		public function isSortable(){
			return true;
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
			//Allow selection of a child folder to upload the image
			if($this->get('override') != 'no') $label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][destination]', $options));

			$span = new XMLElement('span', NULL, array('class' => 'frame'));
			
			
			//Render the upload field or reflect the uploaded file stored in DB.
			if($data['file']) $span->appendChild(new XMLElement('span', Widget::Anchor('/workspace' . $data['file'], URL . '/workspace' . $data['file'])));

			$span->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, $data['file'], ($data['file'] ? 'hidden' : 'file')));

			$label->appendChild($span);

			if($flagWithError != NULL) $wrapper->appendChild(Widget::Error($label, $flagWithError));
			else $wrapper->appendChild($label);
    }

    public function commit(){
			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();

			$fields['destination'] = $this->get('override') ? '/workspace/uploads/newdirectory': $this->get('destination');
			$fields['validator'] = ($fields['validator'] == 'custom' ? NULL : $this->get('validator'));

			return FieldManager::saveSettings($id, $fields);
		}
		
		
}