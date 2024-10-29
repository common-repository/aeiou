<?php
 /**
 * AEIOU Parser that makes use of the SimpleXML PHP extension.
 * Adapted from Wordpress-Importer ver. 0.6 plugin (http://wordpress.org/extend/plugins/wordpress-importer/)
 */
class AEIOU_Parser_SimpleXML {
	function parse( $file ) {

		$internal_errors = libxml_use_internal_errors(true);
		$xml = simplexml_load_file( $file );
		
		// halt if loading produces an error
		if ( ! $xml ){
			echo __( 'There was an error when reading this AEIOU file', AEIOU::LANG_DOMAIN ) . "\n";
			return false;
		}
		$aeiou_version = $xml->xpath('/users/@version');
		
		if ( ! $aeiou_version ){
			echo __( 'This does not appear to be a AEIOU file, missing/invalid AEIOU version number', AEIOU::LANG_DOMAIN ) . "\n";
			return false;
		}

		$aeiou_version = (string) trim( $aeiou_version[0] );

		// confirm that we are dealing with the correct file format
		if ( ! preg_match( '/^\d+\.\d+$/', $aeiou_version ) ){
			echo __( 'This does not appear to be a AEIOU file, missing/invalid AEIOU version number', AEIOU::LANG_DOMAIN ) . "\n";
			return false;
		}

		$users = array();
		
		// grab users
		$count = 0;
		foreach ( $xml->xpath('/users/user') as $userArray ) {
			$count +=1;
			$md = array();
			$opt = array();
			$user = $userArray->children( );
			if(isset($user->metadata)){
				
				foreach($user->metadata->meta as  $meta){
					
					$metaArr = (array)$meta;
					$key = (string) $metaArr['@attributes']['key'];
					$value = (string) $meta;
					/*
					 * Make differences between options and settings
					 */
					if ( isset($metaArr['@attributes']['option']) ) {
						$isGlobalOption = ((string)$metaArr['@attributes']['global']) == 'true';
						$isOption		= true;
						$opt[$key] = array(base64_decode( $value ), $isGlobalOption);
					}else{
						$isOption		= false;
						$md[$key] =base64_decode( $value );
					}
					
				}
				
			}
			/*
			 * Since ver. 0.2 (xprofile data extraction)
			 */
			$xp = array();
			if(isset($user->xprofile)){
				
				foreach($user->xprofile->field as $xField){
						
					$fieldArr = (array)$xField;
					$name = (string) $fieldArr['@attributes']['name'];
					
					if(version_compare($aeiou_version, '0.7')==-1) 
						$value = (string) $xField;
					else
						$value = (string) base64_decode($xField);
					
					$group = (string) $fieldArr['@attributes']['group'];
					
				
					$xp[$name] = array( $value , $group );
					
				}
				
			}
			
			$userRow =  array(
				
				'user_login' 			=> (string) $user->user_login,
				'user_pass' 			=> (string) (isset($user->user_pass)?$user->user_pass:''),
				'user_nicename' 		=> (string) $user->user_nicename,
				'user_email' 			=> (string) $user->user_email,
				'user_url' 				=> (string) $user->user_url,
				'user_registered' 		=> (string) $user->user_registered,
				'user_activation_key' 	=> (string) $user->user_activation_key,
				'user_status' 			=> (string) $user->user_status,
				'display_name' 			=> (string) $user->display_name,
				'metadata'				=> $md,
				'options'				=> $opt,
				'xprofile'				=> $xp,
			);
			/*
			 * Since version 0.2 (can extend import object threating further informations from xml file)
			 */
			
			$userRow = apply_filters('aeiou_filter_row', $userRow, $user);
			
			if(is_array($userRow))
				$users[] = $userRow;
		}
		
		echo sprintf(__("Found %d users in the import file", AEIOU::LANG_DOMAIN), $count ) . "\n";
		
		return $users;
	}
}

