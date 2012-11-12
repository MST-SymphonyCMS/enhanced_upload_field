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
			
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/enhanced_upload_field/assets/style.css', 'screen');
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/enhanced_upload_field/assets/script.enhanced_upload_field.js', 120);
		
			//These 2 functions will need to be addressed as they refer to the destination directory in the field table.. we need a foreach on every upload field table entry to check the folder they refer to exists.
		
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
			
			$directories = General::listDirStructure(WORKSPACE , null, true, DOCROOT, $ignore);
			
			$options = array();
			$options[] = array($this->get('destination'), false, $this->get('destination'));
			
			if(!empty($directories) && is_array($directories)){
				foreach($directories as $d) {
					$d = '/' . trim($d, '/');
					if(!in_array($d, $ignore)) $options[] = array($d, ($this->get('destination') == $d), $d);
				}
			}

			if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', __('Optional')));
			
			if($this->get('override') == 'yes'){
				
				$override = new XMLELement('span', NULL, array('class' => 'enhanced_upload'));
				//Allow selection of a child folder to upload the image
				$choosefolder = Widget::Select('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix.'[directory]', $options, array('class' => 'enhanced_upload_select_'.(!$data['file'] ? 'show':'hidden')));
				//$choosefolder = Widget::Select('directory', $options, array('class' => 'enhanced_upload_select_'.(!$data['file'] ? 'show':'hidden')));
				$override->appendChild($choosefolder);
				$label->appendChild($override);
			
			}
			
			$span = new XMLElement('span', NULL, array('class' => 'frame enhanced_upload'));
			
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

			$fields['destination'] = $this->get('destination');
			$fields['validator'] = ($fields['validator'] == 'custom' ? NULL : $this->get('validator'));
			$fields['override'] = $this->get('override');

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
			
		public function checkPostFieldData($data, &$message, $entry_id=NULL){
			/**
			 * For information about PHPs upload error constants see:
			 * @link http://php.net/manual/en/features.file-upload.errors.php
			 */
			$message = null;
			
			if (
				empty($data)
				|| (
					is_array($data)
					&& isset($data['error'])
					&& $data['error'] == UPLOAD_ERR_NO_FILE
				)
			) {
				if ($this->get('required') == 'yes') {
					$message = __('‘%s’ is a required field.', array($this->get('label')));

					return self::__MISSING_FIELDS__;
				}

				return self::__OK__;
			}

			// Its not an array, so just retain the current data and return
			if (is_array($data) === false) {
				/**
				 * Ensure the file exists in the `WORKSPACE` directory
				 * @link http://symphony-cms.com/discuss/issues/view/610/
				 */
				$file = WORKSPACE . preg_replace(array('%/+%', '%(^|/)\.\./%'), '/', $data);

				if (file_exists($file) === false || !is_readable($file)) {
					$message = __('The file uploaded is no longer available. Please check that it exists, and is readable.');

					return self::__INVALID_FIELDS__;
				}

				// Ensure that the file still matches the validator and hasn't
				// changed since it was uploaded.
				if ($this->get('validator') != null) {
					$rule = $this->get('validator');

					if (General::validateString($file, $rule) === false) {
						$message = __('File chosen in ‘%s’ does not match allowable file types for that field.', array(
							$this->get('label')
						));

						return self::__INVALID_FIELDS__;
					}
				}

				return self::__OK__;
			}

			if (is_dir(DOCROOT . $this->get('destination') . '/') === false) {
				$message = __('The destination directory, %s, does not exist.', array(
					'<code>' . $this->get('destination') . '</code>'
				));

				return self::__ERROR__;
			}

			else if (is_writable(DOCROOT . $this->get('destination') . '/') === false) {
				$message = __('Destination folder is not writable.')
					. ' '
					. __('Please check permissions on %s.', array(
						'<code>' . $this->get('destination') . '</code>'
					));

				return self::__ERROR__;
			}

			if ($data['error'] != UPLOAD_ERR_NO_FILE && $data['error'] != UPLOAD_ERR_OK) {
				switch ($data['error']) {
					case UPLOAD_ERR_INI_SIZE:
						$message = __('File chosen in ‘%1$s’ exceeds the maximum allowed upload size of %2$s specified by your host.', array($this->get('label'), (is_numeric(ini_get('upload_max_filesize')) ? General::formatFilesize(ini_get('upload_max_filesize')) : ini_get('upload_max_filesize'))));
						break;

					case UPLOAD_ERR_FORM_SIZE:
						$message = __('File chosen in ‘%1$s’ exceeds the maximum allowed upload size of %2$s, specified by Symphony.', array($this->get('label'), General::formatFilesize($_POST['MAX_FILE_SIZE'])));
						break;

					case UPLOAD_ERR_PARTIAL:
					case UPLOAD_ERR_NO_TMP_DIR:
						$message = __('File chosen in ‘%s’ was only partially uploaded due to an error.', array($this->get('label')));
						break;

					case UPLOAD_ERR_CANT_WRITE:
						$message = __('Uploading ‘%s’ failed. Could not write temporary file to disk.', array($this->get('label')));
						break;

					case UPLOAD_ERR_EXTENSION:
						$message = __('Uploading ‘%s’ failed. File upload stopped by extension.', array($this->get('label')));
						break;
				}

				return self::__ERROR_CUSTOM__;
			}

			// Sanitize the filename
			$data['name'] = Lang::createFilename($data['name']);

			if ($this->get('validator') != null) {
				$rule = $this->get('validator');

				if (!General::validateString($data['name'], $rule)) {
					$message = __('File chosen in ‘%s’ does not match allowable file types for that field.', array($this->get('label')));

					return self::__INVALID_FIELDS__;
				}
			}

			return self::__OK__;
		}
		
		
		public function processRawFieldData($data, &$status, &$message=null, $simulate=false, $entry_id=NULL){
			$status = self::__OK__;
			
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
			
			
			//My special Select box alteration :P
			
			// Upload the new file
			$override_path = $this->get('override') == 'yes' ? $_POST['fields'][$this->get('element_name')]['directory'] : trim($this->get('destination'));
			$abs_path = DOCROOT . $override_path . '/';
			$rel_path = str_replace('/workspace', '', $override_path);
			$existing_file = NULL;
			
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

			//var_dump($data,$data[$this->get('element_name')]['directory']);die;
			
			return array(
				'file' => $file,
				'size' => $data['size'],
				'mimetype' => $data['type'],
				'meta' => serialize(self::getMetaInfo(WORKSPACE . $file, $data['type']))
			);
			
			
		}
	
		
}