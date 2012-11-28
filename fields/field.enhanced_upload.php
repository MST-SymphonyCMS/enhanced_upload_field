<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT . '/fields/field.upload.php');

	Class fieldEnhanced_Upload extends FieldUpload {
		
		public function __construct(){
			parent::__construct();

			$this->_name = __('Enhanced File Upload');
			$this->set('override', 'no');
		}
		
		/*-------------------------------------------------------------------------
		Definition:
		-------------------------------------------------------------------------*/

		/*public function canFilter() {
			return true;
		}

		public function canPrePopulate(){
			return true;
		}

		public function isSortable(){
			return true;
		}
		*/
		
		/*-------------------------------------------------------------------------
		Utilities:
		-------------------------------------------------------------------------*/

		/*public function entryDataCleanup($entry_id, $data=NULL){
		
			// clean the parent first
			parent::entryDataCleanup($entry_id);
		
			$file_location = WORKSPACE . '/' . ltrim($data['file'], '/');

			if(is_file($file_location)) {
				// inform caller that our operation as been successful
				return General::deleteFile($file_location);
			}

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
		}*/
		
		/*-------------------------------------------------------------------------
			Settings:
		-------------------------------------------------------------------------*/

		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			
			parent::displaySettingsPanel($wrapper, $errors);
			
			// append our own settings
			$label = new XMLElement('label');
			$input = Widget::Input("fields[{$this->get('sortorder')}][override]", 'yes', 'checkbox');
			if( $this->get('override') == 'yes' ) $input->setAttribute('checked', 'checked');
			$label->setValue(__('%s Allow overriding of upload directory in entries', array($input->generate())));

			$wrapper->appendChild($label);

		}

		/*-------------------------------------------------------------------------
			Publish:
		-------------------------------------------------------------------------*/

		private function getChildrenWithClass(XMLElement &$rootElement, $tagName, $className) {
			if ($rootElement == NULL ) {
				return NULL;
			}
			
			// contains the right css class and the right node name
			if (strpos($rootElement->getAttribute('class'), $className) > -1 && $rootElement->getName() == $tagName) {
				return $rootElement;
			}
			
			// recursive search in child elements
			foreach ($rootElement->getChildren() as $key => $child) {
				
				$res = $this->getChildrenWithClass($child, $tagName, $className);
				
				if ($res != NULL) {
					return $res;
				}
			}
			
			return NULL;
		}
		
		private function getSubDirectoriesOptions() {
			// Destination Folder
			$ignore = array(
				'/workspace/events',
				'/workspace/data-sources',
				'/workspace/text-formatters',
				'/workspace/pages',
				'/workspace/utilities'
			);
			
			//Select only the Child directories of the Section Editor Chosen Directory
			$overridedirectories = str_replace('/workspace', '', $this->get('destination'));
			
			$directories = General::listDirStructure(WORKSPACE . $overridedirectories, null, true, DOCROOT, $ignore);
			
			$options = array();
			$options[] = array($this->get('destination'), false, $this->get('destination'));
			
			if(!empty($directories) && is_array($directories)){
				foreach($directories as $d) {
					$d = '/' . trim($d, '/');
					if(!in_array($d, $ignore)) $options[] = array($d, ($this->get('destination') == $d), $d);
				}
			}
			return $options;
		}
		
		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){

			// Let the upload field do it's job
			parent::displayPublishPanel($wrapper, $data, $flagWithError, $fieldnamePrefix, $fieldnamePostfix, $entry_id);
			
			// the override setting is set
			if ($this->get('override') == 'yes') {

				// recursive find our span.frame
				$span = $this->getChildrenWithClass($wrapper, 'span', 'frame');
				
				// if we found it
				if ($span != NULL) {
				
					// get subdirectories
					$options = $this->getSubDirectoriesOptions();
				
					//Allow selection of a child folder to upload the image
					$choosefolder = Widget::Select(
						'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix.'[directory]',
						$options
						/*array(
							'class' => 'enhanced_upload_select_'.(!$data['file'] ? 'show':'hidden')
						)*/
					);
					// append it to the frame
					$span->appendChild($choosefolder);
				}
			
			}
		}
		
		/**
		 * This function permits parsing different field settings values.
		 * Called just before commit
		 *
		 * @param array $settings
		 *	the data array to initialize if necessary.
		 */
		public function setFromPOST(Array $settings = array()) {

			// call the default behavior
			parent::setFromPOST($settings);

			// declare a new setting array
			$new_settings = array();

			// set new settings
			//$new_settings['field-classes'] = 	( $settings['field-handles'] );
			
			//var_dump($settings);die;
			
			// save it into the array
			//$this->setArray($new_settings);
		}

		public function commit() {
			
			// commit the parent's data
			if(!parent::commit()) return false;
			
			// add our own
			$fields = array();
			
			// those next lines are weird (since it is part of the original field)
			// but is necessary
			$fields['destination'] = $this->get('destination');
			$fields['validator'] = $this->get('destination');
			
			$fields['override'] = $this->get('override');

			return FieldManager::saveSettings($this->get('id'), $fields);
		}
	
		/*public function checkFields(array &$errors, $checkForDuplicates = true) {
			parent::checkFields($errors, $checkForDuplicates);
		}*/
	
		
		public function prepareTableValue($data, XMLElement $link=NULL, $entry_id = null){
			
			if(!$file = $data['file']) {
				return parent::prepareTableValue(null, $link);
			}

			if($link){
				$link->setValue(basename($file));
				return $link->generate();
			}

			else {
				$link = Widget::Anchor(basename($file), URL . '/workspace' . $file);
				return $link->generate();
			}
			
		}
			
		public function checkPostFieldData($data, &$message, $entry_id=NULL) {
			
			$dir = $data['directory'];
			
			// validate our part
			if (empty($dir)) {
				$message = __('‘%s’ needs to have a directory setted.', array($this->get('label')));

				return self::__MISSING_FIELDS__;
			}
			else {
				// make the parent think this is the good directory
				$dest = $this->get('destination') . '/' . $dir;
				$this->set('destination', $dest);
			
				// let the parent do its job
				parent::checkPostFieldData($data, $message, $entry_id);
			}
		}
		
		
		public function processRawFieldData($data, &$status, &$message=null, $simulate=false, $entry_id=NULL) {
			/*$status = self::__OK__;
			
			//fixes bug where files are deleted, but their database entries are not.
			if($data === NULL) {
				return array(
					'file' => NULL,
					'mimetype' => NULL,
					'size' => NULL,
					'meta' => NULL
				);
			}
			
			// It's not an array, so just retain the current data and return
			if(!is_array($data)) {
				// Ensure the file exists in the `WORKSPACE` directory
				// @link http://symphony-cms.com/discuss/issues/view/610/
				$file = WORKSPACE . preg_replace(array('%/+%', '%(^|/)\.\./%'), '/', $data);

				$result = array(
					'file' => $data,
					'mimetype' => NULL,
					'size' => NULL,
					'meta' => NULL
				);

				// Grab the existing entry data to preserve the MIME type and size information
				if(isset($entry_id) && !is_null($entry_id)) {
					$row = Symphony::Database()->fetchRow(0, sprintf(
						"SELECT `file`, `mimetype`, `size`, `meta` FROM `tbl_entries_data_%d` WHERE `entry_id` = %d",
						$this->get('id'),
						$entry_id
					));

					if(!empty($row)) $result = $row;
				}

				if(!file_exists($file) || !is_readable($file)) {
					$message = __('The file uploaded is no longer available. Please check that it exists, and is readable.');
					$status = self::__INVALID_FIELDS__;
					return $result;
				}
				else {
					if(empty($result['mimetype'])) $result['mimetype'] = (function_exists('mime_content_type') ? mime_content_type($file) : 'application/octet-stream');
					if(empty($result['size'])) $result['size'] = filesize($file);
					if(empty($result['meta'])) $result['meta'] = serialize(self::getMetaInfo($file, $result['mimetype']));
				}
				
				return $result;
			}

			if($simulate && is_null($entry_id)) return $data;
			*/
			
			//My special Select box alteration :P
			
			//var_dump($_POST['fields']['enhanced_upload_field'][$this->get('element_name')]['directory'],$_POST);die;
			//var_dump($_POST);
			// Upload the new file
			$override_path = $this->get('override') == 'yes' ? $_POST['fields']['enhanced_upload_field'][$this->get('element_name')]['directory'] : trim($this->get('destination'));
			$abs_path = DOCROOT . $override_path . '/';
			$rel_path = str_replace('/workspace', '', $override_path);
			/*$existing_file = NULL;
			
			if(!is_null($entry_id)) {
				$row = Symphony::Database()->fetchRow(0, sprintf(
					"SELECT * FROM `tbl_entries_data_%s` WHERE `entry_id` = %d LIMIT 1",
					$this->get('id'),
					$entry_id
				));

				$existing_file = '/' . trim($row['file'], '/');

				// File was removed
				if($data['error'] == UPLOAD_ERR_NO_FILE && !is_null($existing_file) && is_file(WORKSPACE . $existing_file)) {
					General::deleteFile(WORKSPACE . $existing_file);
				}
			}

			if($data['error'] == UPLOAD_ERR_NO_FILE || $data['error'] != UPLOAD_ERR_OK) {
				return false;
			}

			// If a file already exists, then rename the file being uploaded by
			// adding `_1` to the filename. If `_1` already exists, the logic
			// will keep adding 1 until a filename is available (#672)
			$new_file = $abs_path . '/' . $data['name'];
			if(file_exists($new_file)) {
				$i = 1;
				$extension = General::getExtension($data['name']);
				$renamed_file = $new_file;

				do {
					$renamed_file = General::left($new_file, -strlen($extension) - 1) . '_' . $i . '.' . $extension;
					$i++;
				} while (file_exists($renamed_file));

				// Extract the name filename from `$renamed_file`.
				$data['name'] = str_replace($abs_path . '/', '', $renamed_file);
			}

			// Sanitize the filename
			$data['name'] = Lang::createFilename($data['name']);

			// Actually upload the file, moving it from PHP's temporary store to the desired destination
			if(!General::uploadFile($abs_path, $data['name'], $data['tmp_name'], Symphony::Configuration()->get('write_mode', 'file'))) {
				$message = __('There was an error while trying to upload the file %1$s to the target directory %2$s.', array('<code>' . $data['name'] . '</code>', '<code>workspace/'.ltrim($rel_path, '/') . '</code>'));
				$status = self::__ERROR_CUSTOM__;
				return false;
			}

			$file = rtrim($rel_path, '/') . '/' . trim($data['name'], '/');

			// File has been replaced
			if(!is_null($existing_file) && (strtolower($existing_file) != strtolower($file)) && is_file(WORKSPACE . $existing_file)) {
				General::deleteFile(WORKSPACE . $existing_file);
			}

			// If browser doesn't send MIME type (e.g. .flv in Safari)
			if (strlen(trim($data['type'])) == 0) {
				$data['type'] = (function_exists('mime_content_type') ? mime_content_type($file) : 'application/octet-stream');
			}

			//var_dump($_POST);
			
			return array(
				'file' => $file,
				'size' => $data['size'],
				'mimetype' => $data['type'],
				'meta' => serialize(self::getMetaInfo(WORKSPACE . $file, $data['type']))
			);
			*/
			
			// let the parent to its job
			$values = parent::processRawFieldData($data, $status, $message, $simulate, $entry_id);
			
			// add our own value
			$values['file'] = $rel_path;
		}
	
		
}