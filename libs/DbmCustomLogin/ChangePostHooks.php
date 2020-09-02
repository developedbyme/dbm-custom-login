<?php
	namespace DbmCustomLogin;
	
	use \WP_Query;
	
	// \DbmCustomLogin\ChangePostHooks
	class ChangePostHooks {
		
		function __construct() {
			//echo("\DbmCustomLogin\ChangePostHooks::__construct<br />");
			
			
		}
		
		protected function register_hook_for_type($type, $hook_name) {
			add_action('wprr/admin/change_post/'.$type, array($this, $hook_name), 10, 3);
		}
		
		public function register() {
			//echo("\DbmCustomLogin\ChangePostHooks::register<br />");
			
			$this->register_hook_for_type('dbmcl/createInvite', 'hook_dbmcl_createInvite');
			
		}
		
		protected function update_message_meta($message, $meta) {
			foreach($meta as $key => $value) {
				$message->update_meta($key, $value);
			}
		}
		
		public function hook_dbmcl_createInvite($data, $post_id, $logger) {
			//var_dump('\DbmCustomLogin\ChangePostHooks::hook_dbmtc_commentChange');
			
			$for_id = $post_id;
			
			$new_id = dbm_create_data('Signup invite for '.$for_id, 'signup-invite', 'signup-invites');
			$post = dbm_get_post($new_id);
			$post->add_relation_by_name('invite-status/open');
			
			$token = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
			$post->update_meta('token', $token);
			
			$post->add_outgoing_relation_by_name($for_id, 'invite-for');
			
			$post->update_meta('data', $data['value']);
			
			$logger->add_return_data('inviteId', $new_id);
			$logger->add_return_data('token', $token);
			
			$post->change_status('private');
		}
		
		public static function test_import() {
			echo("Imported \DbmCustomLogin\ChangePostHooks<br />");
		}
	}
?>