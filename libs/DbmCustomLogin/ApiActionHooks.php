<?php
	namespace DbmCustomLogin;
	
	// \DbmCustomLogin\ApiActionHooks
	class ApiActionHooks {

		function __construct() {
			//echo("\DbmCustomLogin\ApiActionHooks::__construct<br />");
			
		}

		public function register() {
			//echo("\DbmCustomLogin\ApiActionHooks::register<br />");
			
			add_action('wprr/api_action/login', array($this, 'hook_login'), 10, 2);
			add_action('wprr/api_action/logout', array($this, 'hook_logout'), 10, 2);
			
			add_action('wprr/api_action/has-user', array($this, 'hook_has_user'), 10, 2);
			add_action('wprr/api_action/register-user', array($this, 'hook_register_user'), 10, 2);
			
			add_action('wprr/api_action/get-api-key', array($this, 'hook_get_api_key'), 10, 2);
			add_action('wprr/api_action/test-api-key', array($this, 'hook_test_api_key'), 10, 2);
			
			add_action('wprr/api_action/generate-magic-link', array($this, 'hook_generate_magic_link'), 10, 2);
		}
		
		public function hook_catch_token($logged_in_cookie, $expire, $expiration, $user_id, $type, $token) {
			global $new_token;
			
			$new_token = $token;
		}
		
		public function hook_login($data, &$response_data) {
			
			$login = $data['log'];
			$password = $data['pwd'];
			$remember = isset($data['remember']) ? $data['remember'] : false;
			
			$user = wp_authenticate($login, $password);
			
			if(is_wp_error($user)) {
				$error = $user;
				throw(new \Exception($error->get_error_message()));
			}
			
			$encoder = new \Wprr\WprrEncoder();
			
			$response_data['authenticated'] = true;
			$response_data['user'] = $encoder->encode_user_with_private_data($user);
			
			add_action('set_logged_in_cookie', array($this, 'hook_catch_token'), 10, 6);
			
			wp_clear_auth_cookie();
			wp_set_current_user($user->ID);
			wp_set_auth_cookie($user->ID, $remember);
			
			$response_data['roles'] = $user->roles;
			
			//MENOTE: this is modified from wp_create_nonce('wp_rest'); as the session is not in the cookie variable
			global $new_token;
			
			$action = 'wp_rest';
			$uid = $user->ID;
			$token = $new_token;
			$i = wp_nonce_tick();
			
			$nonce = substr( wp_hash( $i . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), -12, 10 );
			
			$response_data['restNonce'] = $nonce;
			$response_data['restNonceGeneratedAt'] = time();
		}
		
		public function hook_logout($data, &$response_data) {
			
			$response_data['authenticated'] = false;
			$response_data['loggedOutUser'] = get_current_user_id();
			
			wp_destroy_current_session();
			wp_clear_auth_cookie();
			wp_set_current_user(0);
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
						
						if(isset($data['password'])) {
							$password = $data['password'];
							unset($data['password']);
						}
						else {
							$password = wp_generate_password();
						}
						
						$new_user_id = wp_create_user($email, $password, $email);
						
						
						wp_update_user(array(
							'ID' => $new_user_id,
							'first_name' => $data['firstName'],
							'last_name' => $data['lastName'],
							'display_name' => $data['firstName'].' '.$data['lastName'],
						));
						
						do_action('dbm_custom_login/set_new_user_data', $new_user_id, $data);
						do_action('dbm_custom_login/new_user_created', $new_user_id, $data);
						
						$registered = true;
						$response_data['userId'] = $new_user_id;
						
						$login_after_new_user_created = apply_filters('dbm_custom_login/login_after_new_user_created', true, $new_user_id, $data);
						if($login_after_new_user_created) {
							wp_clear_auth_cookie();
							wp_set_current_user($new_user_id);
							wp_set_auth_cookie($new_user_id);
						}
					}
				}
			}
			
			$response_data['registered'] = $registered;
		}
		
		public function hook_get_api_key($data, &$response_data) {
			//echo("\DbmCustomLogin\ApiActionHooks::hook_get_api_key<br />");
			
			$login = $data['log'];
			$password = $data['pwd'];
			
			$user = wp_authenticate($login, $password);
			
			if(is_wp_error($user)) {
				$error = $user;
				throw(new \Exception($error->get_error_message()));
			}
			
			$encoder = new \Wprr\WprrEncoder();
			
			$response_data['user'] = $encoder->encode_user_with_private_data($user);
			
			wp_set_current_user($user->ID);
			
			$response_data['roles'] = $user->roles;
			$response_data['restNonce'] = wp_create_nonce('wp_rest');
			
			$key = create_api_key($user->ID);
			
			$response_data['key'] = $key;
		}
		
		public function hook_test_api_key($data, &$response_data) {
			//echo("\DbmCustomLogin\ApiActionHooks::hook_test_api_key<br />");
			
			$userId = (int)$data['userId'];
			$key = $data['apiKey'];
			$password = $data['password'];
			
			$salt = 'S<KUn@DHY/JY.M9X)zh0<dJ-H~89j}Ge>-H?;r@Pr:k=~_R^GX?(}Gdqji[+~i_+';
		
			$encoded_password = md5($userId.$key.$password.$salt);
			
			$key_post_id = dbm_new_query('dbm_data')->set_field('post_status', array('publish', 'private'))->add_type_by_path('api-key')->add_meta_query('userId', $userId)->add_meta_query('key', $key)->add_meta_query('password', $encoded_password)->get_post_id();
			
			$response_data['isValid'] = ($key_post_id > 0);
			$response_data['userId'] = get_current_user_id();
		}
		
		public function hook_generate_magic_link($data, &$response_data) {
			//echo("\DbmCustomLogin\ApiActionHooks::hook_generate_magic_link<br />");
			
			$user_id = $data['userId'];
			$link = $data['link'];
			
			$is_admin = current_user_can('administrator');
			
			$can_generate = apply_filters(DBM_CUSTOM_LOGIN_DOMAIN.'/can_generate_magic_link', $is_admin, $data);
			
			if(!$can_generate) {
				throw(new \Exception('Not permitted'));
			}
			
			$response_data['link'] = dbm_custom_login_create_magic_link($user_id, $link);
			
		}
		
		public static function test_import() {
			echo("Imported \DbmCustomLogin\ApiActionHooks<br />");
		}
	}
?>
