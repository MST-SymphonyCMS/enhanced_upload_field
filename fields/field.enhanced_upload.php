<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	// Require the parent class, if not already loaded
	require_once(TOOLKIT . '/fields/field.upload.php');
	require_once(EXTENSIONS . '/enhanced_upload_field/lib/phpthumb/ThumbLib.inc.php');

	// Our new class extends the core one
	Class fieldEnhanced_Upload extends FieldUpload {

		public function __construct(){
			// use parent class
			parent::__construct();
			// overwrite the name
			$this->_name = __('Enhanced File Upload');
			// set defaults
			$this->set('override', 'no');
		}

		/*-------------------------------------------------------------------------
			Settings:
		-------------------------------------------------------------------------*/

		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {

			parent::displaySettingsPanel($wrapper, $errors);
			
			$div = new XMLElement('div');
			$div->setAttribute('class','two columns');
			
			// append our own settings
			$label = new XMLElement('label');
			$label->setAttribute('class','column');
			$input = Widget::Input("fields[{$this->get('sortorder')}][override]", 'yes', 'checkbox');
			if( $this->get('override') == 'yes' ) {
				$input->setAttribute('checked', 'checked');
			}
			
			$label->setValue(__('%s Allow overriding of upload directory in entries', array($input->generate())));
			$div->appendChild($label);
			
			//Check if user wants to hash the field or not.
			$hashlabel = new XMLElement('label');
			$hashlabel->setAttribute('class','column');
			$hashed = Widget::Input("fields[{$this->get('sortorder')}][hashname]", 'yes', 'checkbox');
			if( $this->get('hashname') == 'yes' ) {
				$hashed->setAttribute('checked', 'checked');
			}
			
			$hashlabel->setValue(__('%s Hash the filename using MD5 hashing mechanism', array($hashed->generate())));
			$div->appendChild($hashlabel);
			
			$wrapper->appendChild($div);
			
			//Image preview/Crop settings
			$fieldset = new XMLElement('fieldset');
			$fieldsetTitle = new XMLElement('legend',__('Preview Image in Entry Editor'));
			$fieldset->appendChild($fieldsetTitle);
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$label = Widget::Label('Width');
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][width]', $this->get('width')));
			if(isset($errors['width'])) $group->appendChild(Widget::wrapFormElementWithError($label, $errors['width']));
			else $group->appendChild($label);
			
			$label = Widget::Label('Height');
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][height]', $this->get('height')));
			if(isset($errors['height'])) $group->appendChild(Widget::wrapFormElementWithError($label, $errors['height']));
			else $group->appendChild($label);
			
			$fieldset->appendChild($group);
			
			$label = Widget::Label('Crop mode');
			$selected = $this->get('crop');
			$options = array(
				array('1', $selected == '1', 'Top left'),
				array('2', $selected == '2', 'Top centre'),
				array('3', $selected == '3', 'Top right'),
				array('4', $selected == '4', 'Middle left'),
				array('5', $selected == '5', 'Centre'),
				array('6', $selected == '6', 'Middle right'),
				array('7', $selected == '7', 'Bottom left'),
				array('8', $selected == '8', 'Bottom centre'),
				array('9', $selected == '9', 'Bottom right')
			);
			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][crop]', $options));
			
			
			$fieldset->appendChild($label);
			
			$wrapper->appendChild($fieldset);
			
			//Add Max width and Max height for images
			//Take code from Klaftertiefs Advance-Upload-Field extension: https://github.com/klaftertief/Advanced-Upload-Field
			$fieldset = new XMLElement('fieldset');
			$fieldsetTitle = new XMLElement('legend',__('Set maximum Width and Height for Image files'));
			$fieldset->appendChild($fieldsetTitle);
			
			$max_dimensions = new XMLElement('div', NULL, array('class' => 'group'));
			$label = new XMLElement('label', __('Maximum image width <i>Optional</i>'));
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][max_width]', $this->get('max_width')?$this->get('max_width'):''));
			if(isset($errors['max_width'])) {
				$max_dimensions->appendChild(Widget::wrapFormElementWithError($label, $errors['max_width']));
			} else {
				$max_dimensions->appendChild($label);
			};
			$label = new XMLElement('label', __('Maximum image height <i>Optional</i>'));
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][max_height]', $this->get('max_height')?$this->get('max_height'):''));
			if(isset($errors['max_height'])) {
				$max_dimensions->appendChild(Widget::wrapFormElementWithError($label, $errors['max_height']));
			} else {
				$max_dimensions->appendChild($label);
			};
			$fieldset->appendChild($max_dimensions);
			
			$wrapper->appendChild($fieldset);
		
		}

		/*-------------------------------------------------------------------------
			Publish:
		-------------------------------------------------------------------------*/

		// TODO @nitriques: Find time to push this to the XMLElement class
		private function getChildrenWithClass(XMLElement &$rootElement, $className, $tagName = NULL) {
			
			if ($rootElement == NULL) {
				return NULL;
			}

			// contains the right css class and the right node name (if any)
			// TODO: Use word bondaries instead of strpos
			if (
				(!$className || strpos($rootElement->getAttribute('class'), $className) > -1)
				&&
				(!$tagName || $rootElement->getName() == $tagName)
				) {
				return $rootElement;
			}

			// recursive search in child elements
			foreach ($rootElement->getChildren() as $key => $child) {

				$res = $this->getChildrenWithClass($child, $className, $tagName);

				if ($res != NULL) {
					return $res;
				}
			}

			return NULL;
		}

		// from: http://stackoverflow.com/questions/834303/php-startswith-and-endswith-functions
		private static function endsWith($haystack,$needle,$case=true) {
			$expectedPosition = strlen($haystack) - strlen($needle);

			if($case) {
				return strrpos($haystack, $needle, 0) === $expectedPosition;
			}
			return strripos($haystack, $needle, 0) === $expectedPosition;
		}

		// Utility function to build the select box's options
		private function getSubDirectoriesOptions($data) {
			// Ignored Folder
			$ignore = array(
				'/workspace/events',
				'/workspace/data-sources',
				'/workspace/text-formatters',
				'/workspace/pages',
				'/workspace/utilities'
			);

			// Destination Folder
			$destination = $this->get('destination');

			// Trim the destination
			$overridedirectories = str_replace('/workspace', '', $destination);

			// Select only the Child directories of the Section Editor Chosen Directory
			$directories = General::listDirStructure(WORKSPACE . $overridedirectories, null, true, DOCROOT, $ignore);

			// Options tags
			$options = array(
				// Include the destination itself
				array(
					$destination, // value
					false,        // selected
					'/'           // text
				)
			);

			// If we have found some sub-directories of the destination
			if(!empty($directories) && is_array($directories)){
				foreach($directories as $d) {
					// remove all (begin and end) and assure
					// we have the proper pattern
					$d = '/' . trim($d, '/');
					if(!in_array($d, $ignore)) {

						$isSelected = false;

						// if we have data
						if (!empty($data) && isset($data['file'])) {
							$path = dirname($data['file']);
							$isSelected = self::endsWith($d, $path);
						}

						$options[] = array(
							$d,
							$isSelected,
							str_replace($destination, '', $d)
						);
					}
				}
			}
			return $options;
		}
		
		//Get Hashed File name
		private function getHashedFilename($filename) {
			preg_match('/\.[^\.]+$/', $filename, $meta);
			return md5(time() . $filename) . $meta[0];
		}
		
		
		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){

			// BEWARE
			// LINE 186 in field.upload.php has a bug in 2.3.1
			// it need to get rid of the extra === false a the end of the line
			// https://github.com/symphonycms/symphony-2/blob/master/symphony/lib/toolkit/fields/field.upload.php#L186
			// you need to get it fixed because if not, errors
			// messages from checkPostFieldData will get ovewritten
			
			//var_dump($data);
			//var_dump($wrapper);
			
			// Let the upload field do it's job
			parent::displayPublishPanel($wrapper, $data, $flagWithError, $fieldnamePrefix, $fieldnamePostfix, $entry_id);
			
			//check mime type in order to render preview image
			if(preg_match('/image/',$data['mimetype'])){
				
				$span = $this->getChildrenWithClass($wrapper, 'frame');
				//Render an image preview instead of link.
				
				//var_dump($wrapper);
				//Find the A string in array and replace it with an A and IMG file.
				$imgspan = new XMLElement('span');
				$a = new XMLElement('a');
				$a->setAttribute('src',URL . '/workspace'. $data['file']);
				$img = new XMLElement('img');
				$width = $this->get('width') ? $this->get('width') : '100';
				$height = $this->get('height') ? $this->get('height') : '100'; 
				$crop = $this->get('crop') ? $this->get('crop') : '5';
				$img->setAttribute('src',URL.'/image/2/'.$width.'/'.$height.'/'.$crop.''.$data['file']);
				$img->setAttribute('title','View Full size image');
				$img->setAttribute('class','prettyPhoto');
				$a->appendChild($img);
				$imgspan->appendChild($a);
				
				$span->removeChildAt(0);
				//Add Fieldset and Legend markup to contain the Preview and Max Height/Width settings!
				//var_dump($wrapper,$getChild);
				
				$span->appendChild($imgspan);
				
			}
			
			// the override setting is set
			if ($this->get('override') == 'yes') {

				// recursive find our span.frame
				$span = $this->getChildrenWithClass($wrapper, 'frame');

				// if we found it
				if ($span != NULL) {

					// get subdirectories
					$options = $this->getSubDirectoriesOptions($data);

					// allow selection of a child folder to upload the image
					$choosefolder = Widget::Select(
						'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix.'[directory]',
						$options
					);
					// append it to the frame
					$span->appendChild($choosefolder);
				}

				// recursive find the input
				$input = $this->getChildrenWithClass($span, null, 'input');

				// if we found it
				if ($input != NULL) {
					// change its name
					// N.B. this is really important because of the
					// way Symphony parses the $_POST array. You can't have
					// both a literal value AND sub-values with the same key.
					// Keys are either literals or containers. The upload field
					// uses the fields[label] for it's value. We now have two values,
					// file and directory, so we need to update the html accordingly.
					$input->setAttribute('name', $input->getAttribute('name') . '[file]');
				}
			}
			
			/*$label_text = $this->get('label');
			if ($data['file']) {
				$label_text .= " (" . $this->get("width") . "x" . $this->get("height") . " preview, <a href=\"" . URL . "/workspace" . $data['file'] . "\" style=\"float:none;\">view original</a>)";
			}
			
			$label = Widget::Label($label_text);
			$class = 'file';
			$label->setAttribute('class', $class);
			if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', 'Optional'));
			
			$span = new XMLElement('span');
			if ($data['file']) {
				$img = new XMLElement("img");
				$img->setAttribute("alt", "");
				$img->setAttribute("src", URL . '/image/2/' . $this->get("width") . '/' . $this->get("height") . '/' . $this->get("crop") . '' . $data['file']);
				$span->appendChild($img);
			}
			
			$span->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, $data['file'], ($data['file'] ? 'hidden' : 'file')));
			
			$label->appendChild($span);
			
			if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);*/
			
			//return self::appendFormattedElement($wrapper,$data);
		}

		public function commit() {

			// commit the parent's data
			if(!parent::commit()) return false;

			// get the commited data
			$fields = array();
			//var_dump($fields);die;
			// set our own
			$fields['destination'] = rtrim(trim($this->get('destination')), '/');
			$fields['override'] = $this->get('override');
			$fields['hashname'] = $this->get('hashname');
			// make sure we do not loose anything
			// the field managers wants them all, since it `delete`s
			// and `insert` instead of `update`
			// TODO: Use $this->get() and remove the base fields (label, handle, ...) ?
			$fields['validator'] = $this->get('validator');
			$fields['width'] = $this->get('width');
			$fields['height'] = $this->get('height');
			$fields['crop'] = $this->get('crop');
			$fields['max_width'] = $this->get('max_width');
			$fields['max_height'] = $this->get('max_height');

			// save
			return FieldManager::saveSettings($this->get('id'), $fields);
		}

		private function revertData(&$data) {
			$count = is_array($data) ? count($data) : 0;
			// check to see if there is really a file
			if ($count == 1 && isset($data['file'])) {
				// revert to what the parent is expecting
				// the original 'file' array or string
				$data = $data['file'];
			} else if ($count <= 1) {
				$data = null;
			}
			return $data;
		}

		/**
		 * Check to see if the 'override' option is
		 * set to 'yes'.
		 * @return boolean
		 */
		public function isDirectoryOverridable() {
			return $this->get('override') == 'yes';
		}

		/**
		 * Returns true if the $dir value is valid.
		 * @param string $dir
		 * @return boolean
		 */
		private static function hasDir($dir) {
			return strlen(trim($dir)) > 0;
		}

		/**
		 *
		 * Validates input
		 * Called before <code>processRawFieldData</code>
		 * @param $data
		 * @param $message
		 * @param $entry_id
		 */
		public function checkPostFieldData($data, &$message, $entry_id=NULL) {
					
			// the parent destination
			$destination = $this->get('destination');
			// our custom directory
			$dir = $data['directory'];
			// validation status - assume it's ok
			$status = self::__OK__;

			// Remove our data to make the parent validation works
			// Since we receive $data by copy (not reference) we won't
			// affect any other methods.
			unset($data['directory']);

			// revert to what the parent is expecting
			$data = $this->revertData($data);

			// validate our part
			if ($this->isDirectoryOverridable() && !self::hasDir($dir)) {
				$message = __('‘%s’ needs to have a directory setted.', array($this->get('label')));

				$status = self::__MISSING_FIELDS__;
			}
			else {
				// make the parent think this is the good directory
				$this->set('destination', $dir);

				// let the parent do its job
				$status = parent::checkPostFieldData($data, $message, $entry_id);

				// reset to old value in order to prevent a bug
				// in the display method
				$this->set('destination', $destination);
			}
			
			return $status;
		}

		/**
		 *
		 * Process data before saving into databse.
		 * Also,
		 * this saves the uploaded file in the file system.
		 *
		 * @param array $data
		 * @param int $status
		 * @param boolean $simulate
		 * @param int $entry_id
		 *
		 * @return Array - data to be inserted into DB
		 */
		public function processRawFieldData($data, &$status, &$message=null, $simulate=false, $entry_id=NULL) {
		
			
			//if (is_array($data) and isset($data['name'])) $data['name'] = self::getHashedFilename($data['name']);
			// execute logic only once est resuse
			// although this is pretty clear we will now
			// always have an array!
			$dataIsArray = is_array($data);
			// get our data
			
			## Sanitize the filename
			$data['file']['name'] = Lang::createFilename($data['file']['name']);
			
			## Resize image, if it's an image
			if (getimagesize($data['file']['tmp_name'])) {
				try {
					$thumb = PhpThumbFactory::create($data['file']['tmp_name']);
				} catch (Exception $e) {
					$message = __('There was an error while trying to resize the image <code>%1$s</code>.', array($data['file']['name']));
					$status = self::__ERROR_CUSTOM__;
					return;
				}
				$thumb->resize($this->get('max_width'), $this->get('max_height'))->save($data['file']['tmp_name']);
			}
			
			
			if($this->get('hashname') =='yes'){
				$data['file']['name'] = self::getHashedFilename($data['file']['name']);
			}
			
			$dir = $dataIsArray ? $data['directory'] : '';
			
			// check if we have dir
			$hasDir = self::hasDir($dir);
			// remove our data from the array
			if ($dataIsArray) {
				unset($data['directory']);
			}

			// revert to what the parent is expecting
			$data = $this->revertData($data);

			$status = self::__OK__;
			$destination = $this->get('destination');

			// Change the destination if we have to
			if ($this->isDirectoryOverridable() && $hasDir) {
				// make the parent think this is the good directory
				$this->set('destination', $dir);
			}

			// Upload the new file
			// let the parent do its job
			$values = parent::processRawFieldData($data, $status, $message, $simulate, $entry_id);

			// reset parent value if we have to
			if ($this->get('override') == 'yes' && $hasDir) {
				$this->set('destination', $destination);
			}

			return $values;
		}
		
		public function checkFields(&$errors, $checkForDuplicates=true){
			
			if(strlen($this->get('max_width')) > 0 && !is_numeric($this->get('max_width'))){
				$errors['max_width'] = __('Must be a number.');
			}
			if(strlen($this->get('max_height')) > 0 && !is_numeric($this->get('max_height'))){
				$errors['max_height'] = __('Must be a number.');
			}

			parent::checkFields($errors, $checkForDuplicates);
		}
		
		//Check the fields for required or not?
		/*public function checkFields(&$errors, $checkForDuplicates=true){
			
			if(!is_array($errors)) $errors = array();
			
			if($this->get('label') == '') $errors['label'] = 'This is a required field.';
			if($this->get('width') == '') $errors['width'] = 'This is a required field.';
			if($this->get('height') == '') $errors['height'] = 'This is a required field.';

			if($this->get('element_name') == '') $errors['element_name'] = 'This is a required field.';
			elseif(!preg_match('/^[A-z]([\w\d-_\.]+)?$/i', $this->get('element_name'))){
				$errors['element_name'] = 'Invalid element name. Must be valid QName.';
			}
			
			elseif($checkForDuplicates){
				$sql = "SELECT * FROM `tbl_fields` 
						WHERE `element_name` = '" . $this->get('element_name') . "'
						".($this->get('id') ? " AND `id` != '".$this->get('id')."' " : '')." 
						AND `parent_section` = '". $this->get('parent_section') ."' LIMIT 1";

				if($this->Database->fetchRow(0, $sql)){
					$errors['element_name'] = 'A field with that element name already exists. Please choose another.';
				}
			}

			return (is_array($errors) && !empty($errors) ? self::__ERROR__ : self::__OK__);
			
		}*/
		
		function appendFormattedElement(&$wrapper, $data){
			
			parent::appendFormattedElement($wrapper, $data);
			
			$item = new XMLElement($this->get('element_name'));
			
			$item->appendChild(new XMLElement('filename', General::sanitize(basename($data['file']))));
			
			$item->setAttributeArray(array(
				'size' => General::formatFilesize(filesize(WORKSPACE . $data['file'])),
			 	'path' => str_replace(WORKSPACE, NULL, dirname(WORKSPACE . $data['file'])),
				'type' => $data['mimetype'],
			));
						
			$m = unserialize($data['meta']);
			
			if(is_array($m) && !empty($m)){
				$item->appendChild(new XMLElement('meta', NULL, $m));
			}
			
			$preview_info = Symphony::Database()->fetchRow(0, "SELECT width, height, crop FROM ".Extension_Enhanced_Upload_Field::FIELD_TABLE." WHERE field_id='{$this->_fields['id']}'");
			//var_dump($preview_info);
			$preview = new XMLElement('preview');
			$preview->setAttributeArray(array(
				'width' => $preview_info['width'],
				'height' => $preview_info['height'],
				'crop' => $preview_info['crop']
			));
			$item->appendChild($preview);
			
			//var_dump($preview);
			
			$wrapper->appendChild($item);
			//var_dump($wrapper);
		}
		
		// That's it ! Everything else is handled by the parent!
		// Happy coding!
}