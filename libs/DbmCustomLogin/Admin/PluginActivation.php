<?php
	namespace DbmCustomLogin\Admin;

	// \DbmCustomLogin\Admin\PluginActivation
	class PluginActivation {
		
		static function create_page($slug, $title, $post_type = 'page', $parent_id = 0) {
			
			$args = array(
				'post_type' => $post_type,
				'name' => $slug,
				'post_parent' => $parent_id,
				'posts_per_page' => 1,
				'fields' => 'ids'
			);
			
			$post_ids = get_posts($args);
			
			if(count($post_ids) === 0) {
				$args = array(
					'post_type' => $post_type,
					'post_parent' => $parent_id,
					'name' => $slug,
					'post_title' => $title,
					'post_status' => 'publish'
				);
				
				$post_id = wp_insert_post($args);
			}
			else {
				$post_id = $post_ids[0];
			}
			
			return $post_id;
		}
		
		static function add_term($path, $name) {
			$temp_array = explode(':', $path);
			
			$taxonomy = $temp_array[0];
			$path = explode('/', $temp_array[1]);
			
			\DbmCustomLogin\OddCore\Utils\TaxonomyFunctions::add_term($name, $path, $taxonomy);
		}
		
		static function get_term_by_path($path) {
			$temp_array = explode(':', $path);
			
			$taxonomy = $temp_array[0];
			$path = explode('/', $temp_array[1]);
			
			return \DbmCustomLogin\OddCore\Utils\TaxonomyFunctions::get_term_by_slugs($path, $taxonomy);
		}
		
		static function add_terms_to_post($term_paths, $post_id) {
			foreach($term_paths as $term_path) {
				$current_term = self::get_term_by_path($term_path);
				if($current_term) {
					wp_set_post_terms($post_id, $current_term->term_id, $current_term->taxonomy, true);
				}
				else {
					//METODO: error message
				}
			}
		}
		
		public static function run_setup() {
			global $wp_roles;
			
			self::add_term('dbm_type:notice', 'Notice');
			self::add_term('dbm_type:admin-grouping', 'Admin grouping');
			self::add_term('dbm_type:admin-grouping/magic-links', 'Magic links');
			self::add_term('dbm_type:admin-grouping/api-keys', 'Api keys');
			self::add_term('dbm_type:admin-grouping/signup-invites', 'Signup invites');
			self::add_term('dbm_type:admin-grouping/identifications', 'Identifications');
			
			self::add_term('dbm_type:magic-link', 'Magic link');
			self::add_term('dbm_type:api-key', 'Api key');
			self::add_term('dbm_type:signup-invite', 'Signup invite');
			
			self::add_term('dbm_type:object-relation', 'Object relation');
			self::add_term('dbm_type:object-relation/invite-for', 'Invite for');
			self::add_term('dbm_type:object-user-relation', 'Object user relation');
			self::add_term('dbm_type:object-user-relation/invite-used-by', 'Invite used by');
			
			self::add_term('dbm_relation:global-pages', 'Global pages');
			self::add_term('dbm_relation:global-pages/lost-password', 'Lost password');
			self::add_term('dbm_relation:global-pages/my-account', 'My account');
			self::add_term('dbm_relation:global-pages/reset-password', 'Reset password');
			self::add_term('dbm_relation:global-pages/sign-in', 'Sign in');
			self::add_term('dbm_relation:global-pages/sign-in/sign-up', 'Sign up');
			self::add_term('dbm_relation:global-pages/sign-in/signed-out', 'Signed out');
			self::add_term('dbm_relation:global-pages/sign-in/start-page', 'Start page');
			self::add_term('dbm_relation:global-pages/sign-in/no-access-message', 'No access message');
			
			self::add_term('dbm_relation:restrict-access', 'Restrict access');
			self::add_term('dbm_relation:restrict-access/require-signed-in', 'Require signed in');
			self::add_term('dbm_relation:restrict-access/ignore-login-restriction', 'Ignore login restriction');
			
			self::add_term('dbm_relation:require-role', 'Require role');
			
			$roles = $wp_roles->roles;
			foreach($roles as $slug => $role) {
				self::add_term('dbm_relation:require-role/'.$slug, $role['name']);
			}
			
			self::add_term('dbm_relation:invite-status', 'Invite status');
			self::add_term('dbm_relation:invite-status/open', 'Open');
			self::add_term('dbm_relation:invite-status/cancelled', 'Cancelled');
			self::add_term('dbm_relation:invite-status/used', 'Used');
			
			self::add_term('dbm_relation:notice-types', 'Notice types');
			self::add_term('dbm_relation:notice-types/error-notice', 'Error notice');
			self::add_term('dbm_relation:notice-types/success-notice', 'Success notice');
			self::add_term('dbm_relation:notice-types/warning-notice', 'Warning notice');
			
			self::add_term('dbm_relation:notices', 'Notices');
			
			self::add_term('dbm_relation:magic-link-status', 'Magic link status');
			self::add_term('dbm_relation:magic-link-status/unused', 'Unused');
			self::add_term('dbm_relation:magic-link-status/used', 'Used');
			self::add_term('dbm_relation:magic-link-status/cancelled', 'Cancelled');
			
			self::add_term('dbm_relation:identification-status', 'Identification status');
			self::add_term('dbm_relation:identification-status/unverified', 'Unverified');
			self::add_term('dbm_relation:identification-status/verified', 'Verified');
			self::add_term('dbm_relation:identification-status/cancelled', 'Cancelled');
			self::add_term('dbm_relation:identification-status/failed-creation', 'Failed creation');
			self::add_term('dbm_relation:identification-status/failed', 'Failed');
			
			self::add_term('dbm_relation:identification-type', 'Identification type');
			self::add_term('dbm_relation:identification-type/text-message', 'Text message');
			
			if(isset($_GET['createPages']) && $_GET['createPages'] === "1") {
				$exisiting_id = dbm_new_query('page')->add_relation_by_path('global-pages/sign-in')->get_post_id();
				if(!$exisiting_id) {
					$current_page_id = self::create_page('sign-in', 'Sign in', 'page');
					self::add_terms_to_post(array('dbm_relation:global-pages/sign-in', 'dbm_relation:global-pages/sign-in/signed-out'), $current_page_id);
					$current_parent_id = $current_page_id;
				}
				else {
					$current_parent_id = $exisiting_id;
				}
			
				$exisiting_id = dbm_new_query('page')->add_relation_by_path('global-pages/lost-password')->get_post_id();
				if(!$exisiting_id) {
					$current_page_id = self::create_page('lost-password', 'Lost password', 'page', $current_parent_id);
					self::add_terms_to_post(array('dbm_relation:global-pages/lost-password'), $current_page_id);
				}
			
				$exisiting_id = dbm_new_query('page')->add_relation_by_path('global-pages/my-account')->get_post_id();
				if(!$exisiting_id) {
					$current_page_id = self::create_page('my-account', 'My account', 'page');
					self::add_terms_to_post(array('dbm_relation:global-pages/my-account', 'dbm_relation:global-pages/sign-in/start-page'), $current_page_id);
				}
			
				$exisiting_id = dbm_new_query('page')->add_relation_by_path('global-pages/reset-password')->get_post_id();
				if(!$exisiting_id) {
					$current_page_id = self::create_page('reset-password', 'Reset password', 'page', $current_parent_id);
					self::add_terms_to_post(array('dbm_relation:global-pages/reset-password'), $current_page_id);
				}
			
				$exisiting_id = dbm_new_query('page')->add_relation_by_path('global-pages/sign-in/no-access-message')->get_post_id();
				if(!$exisiting_id) {
					$current_page_id = self::create_page('no-access', 'No access', 'page', $current_parent_id);
					self::add_terms_to_post(array('dbm_relation:global-pages/sign-in/no-access-message'), $current_page_id);
				}
		
				$current_page_id = self::create_page('magic-links', 'Magic links', 'dbm_additional');
				self::add_terms_to_post(array('dbm_type:admin-grouping', 'dbm_type:admin-grouping/magic-links'), $current_page_id);
			}
			
			$current_page_id = self::create_page('api-keys', 'Api keys', 'dbm_data');
			self::add_terms_to_post(array('dbm_type:admin-grouping', 'dbm_type:admin-grouping/api-keys'), $current_page_id);
			
			$current_page_id = self::create_page('signup-invites', 'Signup invites', 'dbm_data');
			self::add_terms_to_post(array('dbm_type:admin-grouping', 'dbm_type:admin-grouping/signup-invites'), $current_page_id);
			
			$current_page_id = self::create_page('identifications', 'Identifications', 'dbm_data');
			self::add_terms_to_post(array('dbm_type:admin-grouping', 'dbm_type:admin-grouping/identifications'), $current_page_id);
			
			$notice_parent_id = self::create_page('notices', 'Notices', 'dbm_area');
			
			$error_notices = array(
				'empty_password' => __('You need to enter a password to login.', DBM_CUSTOM_LOGIN_TEXTDOMAIN),
				'invalid_username' => __("We don't have any users with that email address. Maybe you used a different one when signing up?", DBM_CUSTOM_LOGIN_TEXTDOMAIN),
				'incorrect_password' => __("The password you entered wasn't quite right.", DBM_CUSTOM_LOGIN_TEXTDOMAIN),
				'empty_username' => __('You need to enter your email address to continue.', DBM_CUSTOM_LOGIN_TEXTDOMAIN),
				'invalid_email' => __('There are no users registered with this email address.', DBM_CUSTOM_LOGIN_TEXTDOMAIN),
				'invalidcombo' => __('There are no users registered with this email address.', DBM_CUSTOM_LOGIN_TEXTDOMAIN),
				'expired_key' => __('The password reset link you used is not valid anymore.', DBM_CUSTOM_LOGIN_TEXTDOMAIN),
				'invalid_key' => __('The password reset link you used is not valid anymore.', DBM_CUSTOM_LOGIN_TEXTDOMAIN),
				'password_reset_mismatch' => __("The two passwords you entered don't match.", DBM_CUSTOM_LOGIN_TEXTDOMAIN),
				'password_reset_empty' => __("Sorry, we don't accept empty passwords.", DBM_CUSTOM_LOGIN_TEXTDOMAIN),
			);
			
			$success_notices = array(
				'password_changed' => __('Your password has been updated.', DBM_CUSTOM_LOGIN_TEXTDOMAIN),
				'signed_out' => __('You are now signed out.', DBM_CUSTOM_LOGIN_TEXTDOMAIN),
				'check_email_confirm' => __('Check your email for a link to reset your password.', DBM_CUSTOM_LOGIN_TEXTDOMAIN),
			);
			
			foreach($error_notices as $key => $value) {
				$current_page_id = self::create_page($key, $key, 'dbm_area', $notice_parent_id);
				
				self::add_terms_to_post(
					array(
						'dbm_type:notice',
						'dbm_relation:notice-types/error-notice'
					),
					$current_page_id
				);
				
				$args = array(
					'ID' => $current_page_id,
					'post_content' => $value,
				);
				wp_update_post($args);
			}
			
			foreach($success_notices as $key => $value) {
				$current_page_id = self::create_page($key, $key, 'dbm_area', $notice_parent_id);
				
				self::add_terms_to_post(
					array(
						'dbm_type:notice',
						'dbm_relation:notice-types/success-notice'
					),
					$current_page_id
				);
				
				$args = array(
					'ID' => $current_page_id,
					'post_content' => $value,
				);
				wp_update_post($args);
			}
			
			$setup_manager = dbm_setup_get_manager();
			
			$current_type = $setup_manager->create_data_type('identification')->set_name('Identification');
			$current_type->add_field("status")->setup_single_relation_storage('identification-status', true);
			$current_type->add_field("type")->setup_single_relation_storage('identification-type', true);
			$current_type->add_field("data")->set_type('json')->setup_meta_storage();
			
			$setup_manager->save_all();
		}
		
		public static function test_import() {
			echo("Imported \Admin\CustomPostTypes\PluginActivation<br />");
		}
	}
?>
