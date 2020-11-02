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
			
			add_action('wprr/api_action/dbmcl/startIdentification', array($this, 'hook_startIdentification'), 10, 2);
			add_action('wprr/api_action/dbmcl/verifyIdentification', array($this, 'hook_verifyIdentification'), 10, 2);
			add_action('wprr/api_action/dbmcl/loginWithIdentification', array($this, 'hook_loginWithIdentification'), 10, 2);
			add_action('wprr/api_action/dbmcl/cancelIdentification', array($this, 'hook_cancelIdentification'), 10, 2);
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
			$response_data['roles'] = $user->roles;
			
			$nonce_data = dbm_custom_login_perform_login($user, $remember);
			
			$response_data['restNonce'] = $nonce_data['restNonce'];
			$response_data['restNonceGeneratedAt'] = $nonce_data['restNonceGeneratedAt'];
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
			
			$method = 'default';
			if(isset($data['method'])) {
				$method = $data['method'];
			}
			
			$can_register = apply_filters('dbm_custom_login/can_register', false, $data);
			$can_register = apply_filters('dbm_custom_login/can_register/'.$method, $can_register, $data);
			if($can_register) {
				$registration_is_verified = apply_filters('dbm_custom_login/registration_is_verified', true, $data);
				$registration_is_verified = apply_filters('dbm_custom_login/registration_is_verified/'.$method, $registration_is_verified, $data);
				$response_data['verified'] = $registration_is_verified;
				
				if($registration_is_verified) {
					$email = $data['email'];
					
					$user_exists = email_exists($email);
					if($user_exists) {
						$response_data['alreadyRegistered'] = true;
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
						
						if(is_wp_error($new_user_id)) {
							$error = $new_user_id;
							throw(new \Exception($error->get_error_message()));
						}
						
						wp_update_user(array(
							'ID' => $new_user_id,
							'first_name' => $data['firstName'],
							'last_name' => $data['lastName'],
							'display_name' => $data['firstName'].' '.$data['lastName'],
						));
						
						do_action('dbm_custom_login/set_new_user_data', $new_user_id, $data);
						do_action('dbm_custom_login/set_new_user_data/'.$method, $new_user_id, $data);
						do_action('dbm_custom_login/new_user_created', $new_user_id, $data);
						do_action('dbm_custom_login/new_user_created/'.$method, $new_user_id, $data);
						
						$registered = true;
						$response_data['userId'] = $new_user_id;
						
						$user = get_user_by('id', $new_user_id);
						$encoder = new \Wprr\WprrEncoder();
						$response_data['user'] = $encoder->encode_user_with_private_data($user);
						$response_data['roles'] = $user->roles;
						
						$login_after_new_user_created = apply_filters('dbm_custom_login/login_after_new_user_created', true, $new_user_id, $data);
						if($login_after_new_user_created) {
							
							$user = get_user_by('id', $new_user_id);
							$nonce_data = dbm_custom_login_perform_login($user);
							
							$response_data['restNonce'] = $nonce_data['restNonce'];
							$response_data['restNonceGeneratedAt'] = $nonce_data['restNonceGeneratedAt'];
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
		
		public function hook_startIdentification($data, &$response_data) {
			//echo("\DbmCustomLogin\ApiActionHooks::hook_startIdentification<br />");
			
			$type = $data['type'];
			$data = $data['data'];
			
			$type_term = dbm_get_relation_by_path('identification-type/'.$type);
			
			if(!$type_term) {
				throw(new \Exception('No type '.$type));
			}
			
			if(!has_action('dbmcl/identification/'.$type.'/start')) {
				throw(new \Exception('No start function for '.$type));
			}
			
			$can_identify = apply_filters('dbmcl/identification/'.$type.'/canIdentifyData', true, $data);
			if(!$can_identify) {
				throw(new \Exception('Can\'t identify data'));
			}
			
			$salt = 'v/,+Kfm(j({wb|+?[2OD>0=@ksOny5%4tl|DM#]B~dj-mlU2y.F!GO?%@gHz]$uq'; //METODO: add filter for this salt
			$time = time();
			$key = uniqid('', true);
			
			$post_id = dbm_create_data('New identification', 'identification', 'identifications');
			$post = dbm_get_post($post_id);
			
			dbm_add_post_relation($post_id, 'identification-type/'.$type);
			dbm_add_post_relation($post_id, 'identification-status/unverified');
			$post->update_meta('generatedAt', $time);
			
			$hashed_key = md5($post_id.$time.$key.$salt);
			$post->update_meta('hashedKey', $hashed_key);
			$post->update_meta('data', $data);
			
			do_action('dbmcl/identification/'.$type.'/start', $post_id);
			
			$response_data['key'] = $key;
			$encoded_identification = array('id' => $post_id);
			
			$encoded_identification = wprr_encode_item_as('identification', $encoded_identification, $post_id);
			$encoded_identification = wprr_encode_item_as('identification_'.$type, $encoded_identification, $post_id);
			
			$response_data['identification'] = $encoded_identification;
			
			return $response_data;
		}
		
		public function hook_verifyIdentification($data, &$response_data) {
			$post_id = $data['id'];
			$key = $data['key'];
			
			$post = dbmtc_get_group($post_id);
			$time = $post->get_meta('generatedAt');
			$stored_key = $post->get_meta('hashedKey');
			$salt = 'v/,+Kfm(j({wb|+?[2OD>0=@ksOny5%4tl|DM#]B~dj-mlU2y.F!GO?%@gHz]$uq'; //METODO: add filter for this salt
			
			$hashed_key = md5($post_id.$time.$key.$salt);
			
			if($hashed_key !== $stored_key) {
				throw(new \Exception('Incorrect key'));
			}
			
			$type = $post->get_field_value('type');
			$status = $post->get_field_value('status');
			if($status === 'unverified') {
				
				if(!has_action('dbmcl/identification/'.$type.'/verify')) {
					throw(new \Exception('No start function for '.$type));
				}
				
				do_action('dbmcl/identification/'.$type.'/verify', $post_id);
				
				$status = $post->get_field_value('status');
			}
			
			$response_data['status'] = $status;
			$encoded_identification = array('id' => $post_id);
			
			$encoded_identification = wprr_encode_item_as('identification', $encoded_identification, $post_id);
			$encoded_identification = wprr_encode_item_as('identification_'.$type, $encoded_identification, $post_id);
			
			$response_data['identification'] = $encoded_identification;
			
			return $response_data;
		}
		
		public function hook_loginWithIdentification($data, &$response_data) {
			$post_id = $data['id'];
			$key = $data['key'];
			
			$post = dbmtc_get_group($post_id);
			$time = $post->get_meta('generatedAt');
			$stored_key = $post->get_meta('hashedKey');
			$salt = 'v/,+Kfm(j({wb|+?[2OD>0=@ksOny5%4tl|DM#]B~dj-mlU2y.F!GO?%@gHz]$uq'; //METODO: add filter for this salt
			
			$hashed_key = md5($post_id.$time.$key.$salt);
			
			if($hashed_key !== $stored_key) {
				throw(new \Exception('Incorrect key'));
			}
			
			$type = $post->get_field_value('type');
			$status = $post->get_field_value('status');
			
			if($status !== 'verified') {
				throw(new \Exception('Not verified'));
			}
			
			$logged_in_user = apply_filters('dbmcl/identification/'.$type.'/get_user', 0, $post_id);
			
			if(!$logged_in_user) {
				throw(new \Exception('No user for identification'));
			}
			
			$can_login = apply_filters('dbmcl/identification/'.$type.'/can_login', false, $logged_in_user, $post_id);
			if(!$can_login) {
				throw(new \Exception('Log in not allowed'));
			}
			
			$user = get_user_by('id', $logged_in_user);
			$nonce_data = dbm_custom_login_perform_login($user);
			
			$encoder = new \Wprr\WprrEncoder();
			$response_data['user'] = $encoder->encode_user_with_private_data($user);
			$response_data['roles'] = $user->roles;
			
			$response_data['restNonce'] = $nonce_data['restNonce'];
			$response_data['restNonceGeneratedAt'] = $nonce_data['restNonceGeneratedAt'];
			
			do_action('dbmcl/identification/'.$type.'/logged_in', $post_id);
		}
		
		public function hook_cancelIdentification($data, &$response_data) {
			$post_id = $data['id'];
			$key = $data['key'];
			
			$post = dbmtc_get_group($post_id);
			$time = $post->get_meta('generatedAt');
			$stored_key = $post->get_meta('hashedKey');
			$salt = 'v/,+Kfm(j({wb|+?[2OD>0=@ksOny5%4tl|DM#]B~dj-mlU2y.F!GO?%@gHz]$uq'; //METODO: add filter for this salt
			
			$hashed_key = md5($post_id.$time.$key.$salt);
			
			if($hashed_key !== $stored_key) {
				throw(new \Exception('Incorrect key'));
			}
			
			$type = $post->get_field_value('type');
			$status = $post->get_field_value('status');
			if($status === 'unverified') {
				$status = $post->set_field('status', 'cancelled');
				do_action('dbmcl/identification/'.$type.'/cancel', $post_id);
			}
		}
		
		public static function test_import() {
			echo("Imported \DbmCustomLogin\ApiActionHooks<br />");
		}
	}
?>
