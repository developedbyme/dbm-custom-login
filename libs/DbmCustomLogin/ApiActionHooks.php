<?php
	namespace DbmCustomLogin;
	
	// \DbmCustomLogin\ApiActionHooks
	class ApiActionHooks {

		function __construct() {
			//echo("\DbmCustomLogin\ApiActionHooks::__construct<br />");
			
		}

		public function register() {
			//echo("\DbmCustomLogin\ApiActionHooks::register<br />");

			add_action('wprr/api_action/has-user', array($this, 'hook_has_user'), 10, 2);
			add_action('wprr/api_action/register-user', array($this, 'hook_register_user'), 10, 2);
			
		}

		public function hook_has_user($data, &$response_data) {
			//echo("\DbmCustomLogin\ApiActionHooks::hook_has_user<br />");
			
			$email = $data['email'];
			
			$id = email_exists($email);
			if($id) {
				$response_data['hasUser'] = true;
				$response_data['userId'] = $id;
			}
			else {
				$response_data['hasUser'] = false;
			}
		}
		
		public function hook_register_user($data, &$response_data) {
			//echo("\DbmCustomLogin\ApiActionHooks::hook_register_user<br />");
			
			$registered = false;
			
			$can_register = apply_filters('dbm_custom_login/can_register', false, $data);
			if($can_register) {
				$registration_is_verified = apply_filters('dbm_custom_login/registration_is_verified', true, $data);
				$response_data['verified'] = $registration_is_verified;
				
				if($registration_is_verified) {
					$email = $data['email'];
					
					$user_exists = email_exists($email);
					if($user_exists) {
						$response_data['alreadyRegistered'] = $registered;
						$response_data['userId'] = $user_exists;
					}
					else {
						$new_user_id = wp_create_user($email, $data['password'], $email);
						unset($data['password']);
						
						wp_update_user(array(
							'ID' => $new_user_id,
							'display_name' => $data['firstName'],
							'first_name' => $data['firstName'],
							'last_name' => $data['lastName'],
						));
						
						do_action('dbm_custom_login/set_new_user_data', $new_user_id, $data);
						do_action('dbm_custom_login/new_user_created', $new_user_id, $data);
						
						$registered = true;
						$response_data['userId'] = $new_user_id;
					}
				}
			}
			
			$response_data['registered'] = $registered;
		}
		
		public static function test_import() {
			echo("Imported \DbmCustomLogin\ApiActionHooks<br />");
		}
	}
?>
