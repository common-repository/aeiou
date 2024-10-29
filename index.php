<?php
/*
Plugin Name: AEIOU - Advanced Export/Import (WordPress) Object Users
Plugin URI: http://wordpress.org/extend/plugins/aeiou/
Description: AEIOU is a powerfull plugin that export all wordpress users info into an XML file including user metadatas and allow to import the same xml file into a new wordpress keeping the metadata of each user. The plugin is able to export buddypress xprofile data too. 
Author: toSend.it di Luisa Marra
Version: 0.7
Author URI: http://tosend.it
*/

if(!class_exists('AEIOU')){
	class AEIOU{
		const VERSION = '0.7';
		const LANG_DOMAIN = 'AEIOU';
		
		private $verbose;
		private $exists;
		private $importMetadata;
		private $importOptions;
		private $importXProfile;
		
		private $exportMetadata;
		private $exportOptions;
		private $exportXProfile;
		
		private static $instance;
		
		public static function outputLog($data, $verbose = false){
			if(!isset(self::$instance) ) new AEIOU();
			
			$verbose = $verbose && self::$instance->verbose;
			if($verbose && self::$instance->verbose || !$verbose && !self::$instance->verbose){
				echo($data);
			}
			flush();
			$theDirectory = dirname(__FILE__) . '/logs/';
			if(is_dir($theDirectory)){
				$fileName = date('Y-m-d') . '.log';
				
				file_put_contents($theDirectory . $fileName, $data,  FILE_APPEND );
			}
			
		}
		
		public function __construct(){
			if(is_null(self::$instance) ){
				/*
				 * Singleton pattern: The hooks will be invoked once!
				 */
				add_action('admin_init', array($this , 'init'));
				add_action('admin_menu', array($this, 'createPages'));
				self::$instance = $this;
			}
			return self::$instance;
		}

		public function init(){
			load_plugin_textdomain(self::LANG_DOMAIN, false, basename(dirname( __FILE__ )).'/languages/' );
			if(isset($_GET['aeiou']) && $_GET['aeiou'] == 'export'){
				$this->exportData();
			}
		}
		
		private function toXMLItem($user){
			echo("\t<user base_id=\"{$user->ID}\">\n");
			echo("\t\t<user_login><![CDATA[{$user->user_login}]]></user_login>\n");
			/*
			 * I should export Password too (as requested by user)
			*/
			if(isset($_GET['pwd'])) echo("<user_pass><![CDATA[{$user->user_pass}]]></user_pass>\n");
			
			echo("\t\t<user_nicename><![CDATA[{$user->user_nicename}]]></user_nicename>\n");
			echo("\t\t<user_email><![CDATA[{$user->user_email}]]></user_email>\n");
			echo("\t\t<user_url><![CDATA[{$user->user_url}]]></user_url>\n");
			echo("\t\t<user_registered><![CDATA[{$user->user_registered}]]></user_registered>\n");
			echo("\t\t<user_activation_key><![CDATA[{$user->user_activation_key}]]></user_activation_key>\n");
			echo("\t\t<user_status><![CDATA[{$user->user_status}]]></user_status>\n");
			echo("\t\t<display_name><![CDATA[{$user->display_name}]]></display_name>\n");
			
			if($this->exportMetadata || $this->exportOptions){
				/*
				 * I should export metadata (as requested by user)
				*/
				echo("\t\t<metadata>");
				global $wpdb;
				$umeta = get_user_meta($user->ID);
				foreach($umeta as $key => $value){
					$isOption = preg_match("#^" . preg_quote($wpdb->prefix) ."#", $key);
					if( ($isOption && $this->exportOptions) || (!$isOption && $this->exportMetadata) ){
						
						echo("\t\t\t<meta ");
						
						if(preg_match("#^" . preg_quote($wpdb->prefix) ."#", $key)){
							/*
							 * If the value is an option i should remove the db prefix from the option
							 * name and add the attribute option to allow the importer to understand
							 * when a node is an option or a setting.
							 */
							echo('option="true" ');
							echo('global="false" ');
							$key =substr($key, strlen($wpdb->prefix));
						}
						echo("key=\"$key\"><![CDATA[");
						$value = $value[0];
						echo base64_encode($value); 
						echo("]]></meta>\n");
					}
					
				}
					
				echo("\t\t</metadata>");
			}
			#if( class_exists('BuddyPress') ){
				
				if(class_exists('BP_XProfile_Component') && $this->exportXProfile ){ 
					/*
					 * I should export xprofile data too.
					 */
					$groups = BP_XProfile_Group::get( array(
						'fetch_fields' => true
					) );
					/*
					 * Fetching all groups
					 */
					echo("\t\t<xprofile>");
					foreach($groups as $group){
						$groupName = $group->name;
						if(!empty($group->fields)){
							
							/*
							 * Has fields in group
							 */
							foreach($group->fields as $field){
								$value = xprofile_get_field_data($field->id, $user->ID, 'array');
								echo("<field group=\"$groupName\" name=\"{$field->name}\">");
								$value = serialize($value);
								# Ver 0.7
								$value = base64_encode($value); 
								echo $value;
								echo("</field>");
							}
						}
					}
					echo("\t\t</xprofile>");
					
				}
			#}
			
			do_action('aeiou_export_extra_data', $user);
			echo("\t</user>\n\n");
		}
		
		public function exportData(){
			if(isset($_GET['aeiou']) && $_GET['aeiou'] == 'export'){
				$u = new WP_User_Query(array('fields' => 'all'));
				$users = $u->get_results();
				/*
				 * Make the XML but ensure it will be downloaded
				*/
				
				$url = site_url();
				$url = preg_replace('#[^a-z0-9_\-.]+#i','_', $url);
				$date = date('Y-m-d-h-i-s');
				
				header("Cache-Control: public");
				header("Content-Description: File Transfer");
				header('Content-type: application/xml; encoding=utf-8');
				header("Content-Disposition: attachment; filename=aeiou_{$url}_{$date}.xml");
				header("Content-Transfer-Encoding: binary");
				
				echo("<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n");
				echo("<!-- Export made with Wordpress AEIOU by toSend.it di Luisa Marra (http://tosend.it/) -->\n\n");
				
				echo("<users version=\"". self::VERSION . "\">\n");
				
				$this->exportMetadata 	= isset($_GET['metadata']);
				$this->exportOptions 	= isset($_GET['options']);
				$this->exportXProfile	= isset($_GET['xprofile']);
				
				
				foreach($users as $user){
						
					echo $this->toXMLItem($user);
						
				}
				echo("</users>");
				exit();
			}
		}
		
		
		public function createPages(){
			add_users_page("AEIOU - Panel", 'AEIOU', 'add_users', 'aeiou', array($this, 'thePage'));
		}
		
		
		public function thePage(){
			global $wpdb;
			$screen = WP_Screen::get();
			
			add_meta_box('div-export-options-' .$screen->id, __('Export Users', self::LANG_DOMAIN),  array($this,'addExportMetabox'), 	$screen, 'normal',	'high');
			add_meta_box('div-import-options' . $screen->id, __('Import Users', self::LANG_DOMAIN),  array($this,'addImportMetabox'),  	$screen, 'normal', 	'high');

			wp_enqueue_script('post');
			add_screen_option('layout_columns', array('max' => 1, 'default' => 1) );
			global $wp_meta_boxes;
			?>
			<div class="wrap">
				<h2><?php _e('AEIOU - Panel', self::LANG_DOMAIN) ?></h2>
				<h3><?php _e('Advanced Export/Import (WordPress) Object Users', self::LANG_DOMAIN) ?></h3>
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
						<div id="postbox-container-1" class="postbox-container">
							<h2><?php _e('AEIOU is free!', self::LANG_DOMAIN); ?></h2>
							<p>
								<?php _e('If this plugin is useful to your purpose, please <strong>consider a donation</strong>. ', self::LANG_DOMAIN); ?>
								<?php echo sprintf(
									__('We\'re not asking you for <a href="%s">a lunch</a>.', self::LANG_DOMAIN),
								 	'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=info@tosend.it&item_name=Donation%20for%20AEIOU&item_number=1&amount=30%2e00&currency_code=EUR'	
								); 
								?>
							</p>
							<p>
								<?php 
								$u = new WP_User_Query(array('fields' => 'all'));
								$userCount = $u->get_total() * 10;
								$amt = intval($userCount / 100);
								$dec = "00".($userCount % 100);
								$dec = substr($dec, -2);
								?>
								<?php _e('Feel free to donate as much as you want.', self::LANG_DOMAIN); ?>
								<?php echo sprintf(
									__('We think that <strong><a href="%s">0,10 € (euro) per user</a></strong> could be the right ammount.', self::LANG_DOMAIN),
									"https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=info@tosend.it&item_name=Donation%20for%20AEIOU&item_number=1&amount=$amt%2e$dec&currency_code=EUR") ;
								?>
							</p>
							<h2><?php _e('Premium support', self::LANG_DOMAIN);?></h2>
							<p>
								<?php _e('We are available for a premium support. If you need you can <a href="info@tosend.it">contact us</a> for a consulting.', self::LANG_DOMAIN)?>
							</p>
							<h2><?php _e('Translate AEIOU', self::LANG_DOMAIN)?></h2>
							<p>
								<?php _e('We would like to translate AEIOU in all languages, but we have not knowledge in more than Italian and... a little bit of English. So if you want contribute with your transaltion don\'t hesitate, send us and we will put into the next release!', self::LANG_DOMAIN); ?>
							</p>
							<p>
								<?php _e('This transaltion was made by:', self::LANG_DOMAIN); ?>
								<strong> 
									<a href="<?php _e('http://tosend.it', self::LANG_DOMAIN)?>"><?php _e('Luisa Marra', self::LANG_DOMAIN)?></a> 
								</strong>
							</p>
							<h2><?php _e('How to?', self::LANG_DOMAIN)?></h2>
							<p>
								<?php echo sprintf(__('Need some help? You can use the <a href="%s">WordPress support forum</a> for this plugin.', self::LANG_DOMAIN), 'http://wordpress.org/support/plugin/aeiou'); ?>
							</p>
						</div>
						<div id="postbox-container-2" class="postbox-container">
							<?php do_meta_boxes($screen, 'normal', $screen); ?>
						</div>
					</div>
				</div>
			</div>
			<?php 
		}

		
		public function addExportMetabox(){
			?>
			<form method="get" action="">
				<?php do_action('aeiou_before_export_form'); ?>
				<div>
					<input type="hidden" name="page" value="aeiou" />
					<input type="hidden" name="aeiou" value="export" />
					
					<input type="checkbox" name="pwd" id="aeiou_pwd" />
					<label for="aeiou_pwd"><?php _e('Export password fields too', self::LANG_DOMAIN) ?></label>
					
					<p class="help">
						<?php _e('Remeber that the password would be valid in the new website only if you keep the following defined constants:', self::LANG_DOMAIN); ?>
						<code>AUTH_KEY</code>, <code>SECURE_AUTH_KEY</code> <?php _e('and', self::LANG_DOMAIN); ?> <code>LOGGED_IN_KEY</code>.
					</p>
				</div>
				<div>
					<p>
						<input type="checkbox" name="metadata" id="aeiou_md" />
						<label for="aeiou_md"><?php _e('Export users metadata', self::LANG_DOMAIN) ?></label>
					</p>
					<p>
						<input type="checkbox" name="options" id="aeiou_opt" />
						<label for="aeiou_opt"><?php _e('Export users options', self::LANG_DOMAIN) ?></label>
					</p>
				</div>
				<div>
					<p>
						<input type="checkbox" name="xprofile" id="aeiou_xprofile" />
						<label for="aeiou_xprofile"><?php _e('Include the BuddyPress Extended Profile data too', self::LANG_DOMAIN) ?></label>
					</p>
				</div>
				<?php do_action('aeiou_after_export_form'); ?>
				<p>
					<input type="submit" class="primary" value="<?php _e("Export users", self::LANG_DOMAIN); ?>" />
				</p>
			</form>
			<?php 
		}
		
		private function decodeFileError($errorCode){
			
		}
		
		public function addImportMetabox(){

			?>
			<form method="post" action="?page=aeiou&aeiou=import" enctype="multipart/form-data">
				<?php do_action('aeiou_before_import_form'); ?>
				<div>
					<p>
						<strong><?php _e('If user exists (<em>found with the same username</em>):', self::LANG_DOMAIN)?></strong>
					</p>
					<p>
						<input type="radio" name="exists" id="aeiou_skip" value="skip" />
						<label for="aeiou_skip"><?php _e('Skip user if exists', self::LANG_DOMAIN) ?></label>
					</p>
					<p class="help">
						<?php _e( "it will be skipped and neither user data nor metadata will be imported", self::LANG_DOMAIN )?>
					</p>
					<p>
						<input type="radio" name="exists" id="aeiou_replace" value="replace" />
						<label for="aeiou_replace"><?php _e('Replace user if exists', self::LANG_DOMAIN) ?></label>
					</p>
					<p class="help">
						<?php _e( "it will be removed and imported as new user", self::LANG_DOMAIN )?>
					</p>
					<p>
						<input type="radio" name="exists" id="aeiou_update" value="update" />
						<label for="aeiou_update"><?php _e('Update only empty fields', self::LANG_DOMAIN) ?></label><br />
					</p>
					<p class="help">
						<?php _e( "the import will uppdate only the data and metadata that are empty", self::LANG_DOMAIN )?>
					</p>
					
				</div>
				<hr />
				<p>
					<input type="checkbox" name="metadata" id="aeiou_mdi" />
					<label for="aeiou_mdi"><?php _e('Import users metadata', self::LANG_DOMAIN); ?></label>
				</p>
				<p>
					<input type="checkbox" name="options" id="aeiou_opti" />
					<label for="aeiou_opti"><?php _e('Import users options', self::LANG_DOMAIN); ?></label>
				</p>
				<p>
					<input type="checkbox" name="xprofile" id="aeiou_xp" />
					<label for="aeiou_xp"><?php _e('Import BuddyPress XProfile user fields, data and groups', self::LANG_DOMAIN); ?></label>
				</p>
				<p>
					<input type="checkbox" name="verbose" id="aeiou_verb" />
					<label for="aeiou_verb"><?php _e('Verbose output', self::LANG_DOMAIN); ?></label>
				</p>
				<hr />
				<p>
					<label for="the_file"><?php _e('The XML AEIOU User file:')?></label>
					<input type="file" name="the_file" id="the_file" />
				</p>
				<?php do_action('aeiou_after_import_form'); ?>
				<p>
					<input type="submit" class="primary" value="<?php _e("Import users", self::LANG_DOMAIN); ?>" />
				</p>
			</form>
			<pre class="output"><?php 
				
				if(isset($_GET['aeiou']) && $_GET['aeiou'] =='import'){
					/*
					 * Import requested
					 */
					require dirname(__FILE__) . '/parsers.php';
					
					if(isset($_FILES) && isset($_FILES['the_file'])){
						/*
						 * The request is correct
						 */					
						$theFile = $_FILES['the_file'];
						
						/*
						 * Setting the Class variables for import
						 */
						
						/*
						 * If the import should loud all it is doing
						 */
						$this->verbose 	= isset($_POST['verbose']);
						
						/*
						 * The behavior when a wordpress user is found could be:
						 * - skip
						 * - replace
						 * - update
						 */
						$this->exists			= isset($_POST['exists'])?$_POST['exists']:'skip';
						
						
						$this->importMetadata 	= isset($_POST['metadata']);
						$this->importOptions 	= isset($_POST['options']);
						$this->importXProfile	= isset($_POST['xprofile']);
						
						
						if($theFile['error'] != 0){
							/*
							 * Some errors occours
							 */
							echo $this->decodeFileError($theFile['error']);
						}else{
							/*
							 * Starting import
							 */
							$this->outputLog( __('Import started.', self::LANG_DOMAIN) . "\n\n" );
							_e('Please do not refresh the page and not go anywhere.'); echo "\n";
							_e('Interruption of page execution could cause data loss and user table to break!'); echo "\n"; 
							$parser = new AEIOU_Parser_SimpleXML();
							$users = $parser->parse($theFile['tmp_name']);
							
							foreach($users as $user){
								/*
								 * Increase the time limit up to 10 seconds per user
								 */
								set_time_limit(10);
								
								$login = $user['user_login'];
								$this->outputLog("\n[$login] ");
								$usr = get_user_by('login', $login);
								
								$userID = 0;
								
								$userMeta 		= (isset($user['metadata'])) 	? $user['metadata'] : array();
								/*
								 * v0.2: Added user options and xprofile import
								 */
								$userOptions 	= (isset($user['options'])) 	? $user['options'] : array();
								$xprofile 		= (isset($user['xprofile'])) 	? $user['xprofile'] : array();
								
								/*
								 * Cleanup extra keys
								 */
								if(isset($user['metadata'])) 	unset($user['metadata']);
								if(isset($user['options'])) 	unset($user['options']);
								if(isset($user['xprofile'])) 	unset($user['xprofile']);
								
								do_action('aeiou_before_import_user', $user);
								
								if($usr && $this->exists != 'skip'){
									$usr = (array) $usr->data;
									
									if($this->exists == 'update'){
										
										/*
										 * Keeping metadata and removing from array
										 */
										
										foreach($user as $key => $value){
											/*
											 * Removing all non empty key from import
											 */
											if(isset($usr[$key]) && !empty($usr[$key])){
												unset($user[$key]);
											}
										}
									}
									
									if(count($user)>0){
										$changes = array_keys($user);
										$changes = "`".implode("`, `", $changes)."`";
										
										$this->outputLog( sprintf(__("has new %s value(s).", self::LANG_DOMAIN),$changes) . "\n");
										$user['ID'] = $usr['ID'];
										/*
										 * If password is defined in the XML file the script uses it (as is)!
										 */
										wp_update_user($user);
										global $wpdb;
											
										if(isset($usr['user_pass']))
											$wpdb->query(
												$wpdb->prepare("update {$wpdb->prefix}_users set user_pass='%s' where ID=%d", $user['user_pass'], $user['ID'])
											);
										
									}else{
										_e("unchanged.", self::LANG_DOMAIN) . "\n";
									}
									$userID = $user['ID'];
									
								}else{
								
									if(!$usr){
										/*
										 * New User must be created
										 */
										if(!isset($user['user_pass'])) $user['user_pass'] = ''; 
										$userID = wp_insert_user($user);
										
										/*
										 * If i was unable to import the user I will give the error message to the admin.
										 */
										if(is_wp_error($userID)){
											
											$this->outputLog( $userID->get_error_message() . "\n" );
											
										}else{
											$user['ID'] = $userID;
											/*
											 * If password is defined in the XML file we should to import it
											 */
											if(isset($usr['user_pass']))
												$wpdb->query(
														$wpdb->prepare("update {$wpdb->prefix}_users set user_pass='%s' where ID=%d", $user['user_pass'], $user['ID'])
														);

											$this->outputLog( sprintf(__("inserted with ID %d", self::LANG_DOMAIN),$userID) . "\n" );
										}
									}else{
										$this->outputLog( __("skipped due it's already in the database", self::LANG_DOMAIN) . "\n" );
										$userID = $usr->ID;
									}
								}
								if(!is_wp_error($userID)){
									
									if($this->importMetadata && $userID != 0){
										do_action('aeiou_before_import_metadata', $user);
										$this->importUserMetaData($userMeta, $userID, $login);
									}
									
									if($this->importOptions && $userID != 0){
										do_action('aeiou_before_import_options', $user);
										$this->outputLog( __("Importing user options\n", self::LANG_DOMAIN) );
										$this->importUserOptions($userOptions, $userID, $login);
									}
									
									if($this->importXProfile && $userID != 0 && class_exists('BP_XProfile_Component')){
										do_action('aeiou_before_import_xprofile', $user);
										$this->outputLog( __("Importing XProfile data\n", self::LANG_DOMAIN) );
										$this->importXProfileData($xprofile, $userID);
									}
									do_action('aeiou_after_import_user', $user);
								}
								
							}
							
							
						}
					}
				}
				?>
			</pre>
			<?php 
		}
		
		private function importUserMetaData($userMeta, $userID, $login){
			
			/*
			 * Admin asked for metadata import
			*/
			
			$count = 0;
			foreach($userMeta as $key => $value){
				$metaKey = get_user_meta($userID, $key);
			
				if(empty($metaKey[0])) $metaKey = false;
				if(!$metaKey || $this->exists=='replace'){
					$value = maybe_unserialize($value);
					delete_user_meta($userID, $key);
					if( !empty( $value ) ){
			
						$count+=1;
						$this->outputLog( sprintf(__("Metadata `%s` updated for user [%s]", self::LANG_DOMAIN), $key, $login) . "\n", true);
			
						update_user_meta($userID, $key, $value );
					}else{
						if($metaKey) $this->outputLog( sprintf(__("Metadata `%s` removed for user [%s]", self::LANG_DOMAIN), $key, $login) . "\n", true);
					}
				}
			}
			
			if($count>0)
				$this->outputLog( sprintf(__("Updated %d metadatas for user [%s]", self::LANG_DOMAIN), $count, $login) . "\n");
			
		}

		private function importUserOptions($userOptions, $userID, $login){
				
			/*
			 * Admin asked for options import
			*/
				
			$count = 0;
			foreach($userOptions as $key => $data){
				
				list($value, $global) = $data;
				
				$option = get_user_option( $key, $userID);
					
				$optExists = !empty($option);
				$value = maybe_unserialize($value);

				if(!$optExists || $this->exists=='replace'){
					delete_user_option($userID, $key);
					if( !empty( $value ) ){
							
						$count+=1;
						$this->outputLog( sprintf(__("Option `%s` updated for user [%s]", self::LANG_DOMAIN), $key, $login) . "\n" , true);
						
						
						update_user_option($userID, $key, $value, $global );
					}else{
						if($optExists) 
							$this->outputLog( sprintf(__("Option `%s` removed for user [%s]", self::LANG_DOMAIN), $key, $login) . "\n" );
					}
				}
			}
				
			if($count>0)
				$this->outputLog( sprintf(__("Updated %d metadatas for user [%s]", self::LANG_DOMAIN), $count, $login) . "\n");
				
		}
		
		
		
		private function importXProfileData($xprofile, $userID){
			/*
			 * Admin asked for xprofile import
			*/
			$count = 0;
			
			foreach($xprofile as $name => $data){
				$value = $data[0];
				$group = $data[1];
				
				$this->outputLog( sprintf(__("Importing field `%s` of group `%s`", self::LANG_DOMAIN), $name, $group) . "\n", true);
				
				/*
				 * Get the XProfile Fields Group ID
				 * If it's not available the will be created as new.
				 */
				$groupId = $this->getXProfileGroupIDByName($group);
				
				/*
				 * Get the XProfile Field ID
				 * If it's not available the will be created as new.
				 */
				$fieldId = $this->getXProfileFieldIDByName($name, $groupId);
				$fieldData = maybe_unserialize($value);
				$oldValue = BP_XProfile_ProfileData::get_value_byid($fieldId, $userID);
				if(is_null($oldValue) || ($oldValue == '' && $this->exists == 'update') || ($this->exists == 'replace') ){
					/*
					 * Saving data if not exists or need to be updated
					 */	
					$data = new BP_XProfile_ProfileData();
					$data->field_id = $fieldId;
					$data->user_id = $userID; 
					$data->value = maybe_unserialize($value);
					$data->save();
					
				}
			}
		} 
		private function getXProfileFieldIDByName($name, $groupId = 0, $create = true){
			$out = BP_XProfile_Field::get_id_from_name($name);
			if(is_null($out) && $create){
				
				$field 				= new BP_XProfile_Field();
				$field->name 		= $name;
				$field->group_id 	= $groupId;
				/*
				 *  TODO: Store in the XML the type.
				 *  Aactually i will create a generic text type if the field is not available.
				 */
				$field->type = "text"; 
				$out = $field->save();
			}
			return $out;
		}
		
		private function getXProfileGroupIDByName($name, $create = true){
			global $wpdb, $bp;
			
			$sql = "select id from {$bp->profile->table_name_groups} where name = %s";
			$sql = $wpdb->prepare($sql, $name);
			$out = $wpdb->get_var($sql);
			
			if( is_null( $out ) && $create ){
				$newGroup = new BP_XProfile_Group();
				$newGroup->name = $name;
				$out = $newGroup->save();
			}
			return $out;
			
		}
	}
	
	$aeiou = new AEIOU();
		
	
}	
?>