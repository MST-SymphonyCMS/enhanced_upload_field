<?php
	
	require_once(TOOLKIT . '/class.alert.php');
	
    class Extension_Enhanced_Upload_Field extends Extension {

    const FIELD_TABLE = 'tbl_fields_enhanced_upload';
	const FIELD_NAME = 'enhanced_upload';

    public function getSubscribedDelegates(){
			return array(
						array(
							'page' => '/backend/',
							'delegate' => 'AdminPagePreGenerate',
							'callback' => '__appendAssets'
						),
					);
    }
	
	public static function hasInstance($ext_name=NULL, $section_handle)
    {
		
        $sid  = SectionManager::fetchIDFromHandle($section_handle);
        $section = SectionManager::fetch($sid);
        $fm = $section->fetchFields($ext_name);
        return is_array($fm) && !empty($fm);
    }
	
	/**
	* append needed css an js files to the document head
    */
    public function __appendAssets($context)
    {
        $callback = Symphony::Engine()->getPageCallback();
		
        // Append styles for publish area
        if ($callback['driver'] == 'publish' && $callback['context']['page'] != 'index') {
            if (self::hasInstance('enhanced_upload', $callback['context']['section_handle'])) {
                Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/enhanced_upload_field/assets/style.css', 'screen', 100, false);
				Administration::instance()->Page->addScriptToHead(URL . '/extensions/enhanced_upload_field/assets/script.enhanced_upload_field.js', 80, false);
            }
        }
    }

    public function uninstall(){
    	
			try{
				Symphony::Database()->query(sprintf(
					"DROP TABLE `%s`",
					self::FIELD_TABLE
				));
			}
			catch( DatabaseException $dbe ){
				// table deosn't exist
			}

			return true;
							
	}

	public function install(){
			return Symphony::Database()->query(sprintf(
				"CREATE TABLE `%s` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`destination` varchar(255) NOT NULL,
					`override` enum('yes','no') default 'yes',
					`validator` varchar(50),
					`unique` enum('yes','no') default 'yes',
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
				self::FIELD_TABLE
			));
			
    }
}