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

		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){

			// BEWARE
			// LINE 186 in field.upload.php has a bug in 2.3.1
			// it need to get rid of the extra === false a the end of the line
			// https://github.com/symphonycms/symphony-2/blob/master/symphony/lib/toolkit/fields/field.upload.php#L186
			// you need to get it fixed because if not, errors
			// messages from checkPostFieldData will get ovewritten

			// Let the upload field do it's job
			parent::displayPublishPanel($wrapper, $data, $flagWithError, $fieldnamePrefix, $fieldnamePostfix, $entry_id);

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
		}

		public function commit() {

			// commit the parent's data
			if(!parent::commit()) return false;

			// get the commited data
			$fields = array();

			// set our own
			$fields['destination'] = rtrim(trim($this->get('destination')), '/');
			$fields['override'] = $this->get('override');
			// make sure we do not loose anything
			// the field managers wants them all, since it `delete`s
			// and `insert` instead of `update`
			// TODO: Use $this->get() and remove the base fields (label, handle, ...) ?
			$fields['validator'] = $this->get('validator');

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
		public function isDirectoryOverriable() {
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
			if ($this->isDirectoryOverriable() && !self::hasDir($dir)) {
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
			// execute logic only once est resuse
			// although this is pretty clear we will now
			// always have an array!
			$dataIsArray = is_array($data);
			// get our data
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
			if ($this->isDirectoryOverriable() && $hasDir) {
				// make the parent think this is the good directory
				$this->set('destination', $dir);
			}

			// Upload the new file
			// let the parent to its job
			$values = parent::processRawFieldData($data, $status, $message, $simulate, $entry_id);

			// reset parent value if we have to
			if ($this->get('override') == 'yes' && $hasDir) {
				$this->set('destination', $destination);
			}

			return $values;
		}

		// That's it ! Everything else is handled by the parent!
		// Happy coding!
}