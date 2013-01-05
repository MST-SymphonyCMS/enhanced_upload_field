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

				$res = $this->getChildrenWithClass($child, $tagName, $className);

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
					$destination,  // text
					false,         // selected
					$destination   // value
				)
			);

			// If we have found some sub-directories of the destination
			if(!empty($directories) && is_array($directories)){
				foreach($directories as $d) {
					$d = '/' . trim($d, '/');
					if(!in_array($d, $ignore)) {
						$options[] = array($d, ($this->get('destination') == $d), $d);
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
				$span = $this->getChildrenWithClass($wrapper, 'span', 'frame');

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
			$fields = $this->get();

			// add our own
			$fields['destination'] = $this->get('destination');
			$fields['override'] = $this->get('override');

			// save
			return FieldManager::saveSettings($this->get('id'), $fields);
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
			// if we do not have enought data to play with
			if ( !is_array($data) || empty($data) ) {
				// let the parent do its job
				return parent::processRawFieldData($data, $status, $message, $simulate, $entry_id);
			}

			$status = self::__OK__;

			var_dump($data);

			// Upload the new file
			$override_path = $this->get('override') == 'yes' ?
				$_POST['fields']['enhanced_upload_field'][$this->get('element_name')]['directory'] :
				trim($this->get('destination'));
			$abs_path = DOCROOT . $override_path . '/';
			$rel_path = str_replace('/workspace', '', $override_path);

			// let the parent to its job
			$values = parent::processRawFieldData($data, $status, $message, $simulate, $entry_id);

			// add our own value
			$values['file'] = $rel_path;

			return $values;
		}

		// That's it ! Everything else is handled by the parent!
		// Happy coding!
}