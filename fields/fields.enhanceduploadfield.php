<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT . '/fields/field.upload.php');

	class FieldEnhancedUpload extends FieldUpload {
		public function __construct(){
			parent::__construct();
			$this->_name = __('Enhanced File Upload');
		}

		public function displaySettingsPanel(&$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

			$folder_override = new XMLElement('div', NULL, array('class' => 'group'));
			$label = new XMLElement('label', __('Allow override of upload folder in section entry <i>Optional</i>'));
			$label->appendChild(Widget::Input('fields[enhanced_upload_field][allow_override]', $this->get('max_width')?$this->get('max_width'):''), 'checkbox');
			$wrapper->appendChild($folder_override);

		}
		
		function commit(){
			
			if(!parent::commit()) return false;
			
			$id = $this->get('id');

			if($id === false) return false;
			
			$fields = array();
			
			$fields['field_id'] = $id;
			$fields['destination'] = $this->get('destination');
					
		}		

		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){
			
			$status = self::__OK__;
			
			## Its not an array, so just retain the current data and return
			if(!is_array($data)){
				
				$status = self::__OK__;
				
				// Do a simple reconstruction of the file meta information. This is a workaround for
				// bug which causes all meta information to be dropped
				return array(
					'file' => $data,
					'mimetype' => self::__sniffMIMEType($data),
					'size' => filesize(WORKSPACE . $data),
					'meta' => serialize(self::getMetaInfo(WORKSPACE . $data, self::__sniffMIMEType($data)))
				);
	
			}

			if($simulate) return;
			
			if($data['error'] == UPLOAD_ERR_NO_FILE || $data['error'] != UPLOAD_ERR_OK) return;
			
			## Sanitize the filename
			$data['name'] = Lang::createFilename($data['name']);

			## Upload the new file
			$abs_path = DOCROOT . '/' . trim($this->get('destination'), '/');
			$rel_path = str_replace('/workspace', '', $this->get('destination'));

			if(!General::uploadFile($abs_path, $data['name'], $data['tmp_name'], Symphony::Configuration()->get('write_mode', 'file'))){
				
				$message = __('There was an error while trying to upload the file <code>%1$s</code> to the target directory <code>%2$s</code>.', array($data['name'], 'workspace/'.ltrim($rel_path, '/')));
				$status = self::__ERROR_CUSTOM__;
				return;
			}

			$status = self::__OK__;
			
			$file = rtrim($rel_path, '/') . '/' . trim($data['name'], '/');

			if($entry_id){
				$row = $this->Database->fetchRow(0, "SELECT * FROM `tbl_entries_data_".$this->get('id')."` WHERE `entry_id` = '$entry_id' LIMIT 1");
				$existing_file = rtrim($rel_path, '/') . '/' . trim(basename($row['file']), '/');

				if((strtolower($existing_file) != strtolower($file)) && file_exists(WORKSPACE . $existing_file)){
					General::deleteFile(WORKSPACE . $existing_file);
				}
			}

			## If browser doesn't send MIME type (e.g. .flv in Safari)
			if (strlen(trim($data['type'])) == 0){
				$data['type'] = 'unknown';
			}

			return array(
				'file' => $file,
				'size' => $data['size'],
				'mimetype' => $data['type'],
				'meta' => serialize(self::getMetaInfo(WORKSPACE . $file, $data['type']))
			);
			
		}

		private static function __sniffMIMEType($file){
			
			$ext = strtolower(General::getExtension($file));
			
			$imageMimeTypes = array(
				'image/gif',
				'image/jpg',
				'image/jpeg',
				'image/png',
			);
			
			if(General::in_iarray("image/{$ext}", $imageMimeTypes)) return "image/{$ext}";
			
			return 'unknown';
		}
		
		
	}