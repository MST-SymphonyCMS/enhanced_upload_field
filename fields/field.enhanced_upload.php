<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	// Require the parent class, if not already loaded
	require_once(TOOLKIT . '/fields/field.upload.php');

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

			// append our own settings
			$label = new XMLElement('label');
			$input = Widget::Input("fields[{$this->get('sortorder')}][override]", 'yes', 'checkbox');
			if( $this->get('override') == 'yes' ) {
				$input->setAttribute('checked', 'checked');
			}
			$label->setValue(__('%s Allow overriding of upload directory in entries', array($input->generate())));

			$wrapper->appendChild($label);
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
			if (strpos($rootElement->getAttribute('class'), $className) > -1 &&
			   (!$tagName || $rootElement->getName() == $tagName)) {
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

		// Utility function to build the select box's options
		private function getSubDirectoriesOptions() {
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
					$d = '/' . trim($d, '/') . '/';
					if(!in_array($d, $ignore)) {
						$options[] = array(
							$d,
							$destination == $d,
							str_replace($destination, '', $d)
						);
					}
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
				$span = $this->getChildrenWithClass($wrapper, 'frame');

				// if we found it
				if ($span != NULL) {

					// get subdirectories
					$options = $this->getSubDirectoriesOptions();

					//Allow selection of a child folder to upload the image
					$choosefolder = Widget::Select(
						'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix.'[directory]',
						$options
					);
					// append it to the frame
					$span->appendChild($choosefolder);
				}

			}
		}

		public function commit() {

			// commit the parent's data
			if(!parent::commit()) return false;

			// get the commited data
			$fields = array();

			// add our own
			$fields['destination'] = rtrim(trim($this->get('destination')), '/');
			$fields['override'] = $this->get('override');
			$fields['validator'] = $this->get('validator');

			// save
			return FieldManager::saveSettings($this->get('id'), $fields);
		}

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

			// validate our part
			if (strlen(trim($dir)) == 0) {
				$message = __('‘%s’ needs to have a directory setted.', array($this->get('label')));

				$status = self::__MISSING_FIELDS__;
			}
			else {
				// make the parent think this is the good directory
				$this->set('destination', $dir);

				// let the parent do its job
				$status = parent::checkPostFieldData($data, $message, $entry_id);

				// reset to old value in order to prevent a bug
				// in the display methods
				$this->set('destination', $destination);
			}

			var_dump($status);var_dump($message);//die;

			return $status;
		}


		public function processRawFieldData($data, &$status, &$message=null, $simulate=false, $entry_id=NULL) {
			// execute logic only once est resuse
			$dataIsArray = is_array($data);
			// get our data
			$dir = $dataIsArray ? $data['directory'] : '';
			// check if we have dir
			$hasDir = isset($dir) && strlen(trim($dir)) > 0;
			// remove our data from the array
			if ($dataIsArray) {
				unset($data['directory']);
			}

			// if we do not have enought data to play with
			if ( !is_array($data) || empty($data) || !$hasDir) {
				// let the parent do its job
				return parent::processRawFieldData($data, $status, $message, $simulate, $entry_id);
			}

			var_dump($data);

			$status = self::__OK__;
			$destination = $this->get('destination');

			// Upload the new file
			if ($this->get('override') == 'yes' && $hasDir) {
				// make the parent think this is the good directory
				$this->set('destination', $dir);
			}

			// let the parent to its job
			$values = parent::processRawFieldData($data, $status, $message, $simulate, $entry_id);

			// reset parent value
			$this->set('destination', $destination);

			//var_dump($values);die;

			return $values;
		}

		// That's it ! Everything else is handled by the parent!
		// Happy coding!
}