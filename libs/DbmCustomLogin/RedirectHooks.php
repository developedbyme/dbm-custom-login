<?php
	namespace DbmCustomLogin;
	
	use \WP_Query;
	
	// \DbmCustomLogin\RedirectHooks
	class RedirectHooks {
		
		protected $settings = null;
		
		function __construct() {
			//echo("\DbmCustomLogin\RedirectHooks::__construct<br />");
			
			
		}
		
		public function register() {
			//echo("\DbmCustomLogin\RedirectHooks::register<br />");
			
			if(isset($_GET['ignoreRedirects']) && $_GET['ignoreRedirects']) {
				return;
			}
			
			add_action('template_redirect', array($this, 'hook_template_redirect'));
			add_action('login_form_login', array($this, 'hook_login_form_login'));
			add_filter('authenticate', array($this, 'filter_maybe_redirect_at_authenticate'), 101, 3);
			add_action('wp_logout', array($this, 'hook_redirect_after_logout'));
			add_filter('login_redirect', array($this, 'filter_redirect_after_login'), 10, 3);
			
			add_action('login_form_lostpassword', array($this, 'hook_redirect_to_custom_lostpassword'));
			add_action('login_form_lostpassword', array($this, 'hook_do_password_lost'));
			add_filter('retrieve_password_message', array($this, 'filter_replace_retrieve_password_message' ), 10, 4);
			add_action('login_form_rp', array($this, 'hook_redirect_to_custom_password_reset'));
			add_action('login_form_resetpass', array($this, 'hook_redirect_to_custom_password_reset'));
			add_action('login_form_rp', array($this, 'hook_do_password_reset'));
			add_action('login_form_resetpass', array($this, 'hook_do_password_reset'));
			
		}
		
		protected function has_any_relation($terms) {
			foreach($terms as $term) {
				if(isset($term) && has_term($term->term_id, 'dbm_relation')) {
					return true;
				}
			}
			
			return false;
		}
		
		public function hook_template_redirect() {
			//echo("\DbmCustomLogin\RedirectHooks::hook_template_redirect<br />");
			
			$is_logged_in = is_user_logged_in();
			
			if(function_exists('dbm_get_relation')) {
				$my_account_term = dbm_get_relation(array('global-pages', 'my-account'));
				$sign_in_term = dbm_get_relation(array('global-pages', 'sign-in'));
				$sign_up_term = dbm_get_relation(array('global-pages', 'sign-in', 'sign-up'));
			
				if(isset($my_account_term) && has_term($my_account_term->term_id, 'dbm_relation') && !$is_logged_in) {
					wp_redirect($this->get_global_page_url(array('global-pages', 'sign-in')), 302);
					exit;
				}
			
				if(isset($sign_in_term) && has_term($sign_in_term->term_id, 'dbm_relation') && $is_logged_in) {
					wp_redirect($this->get_global_page_url(array('global-pages', 'start-page')), 302);
					exit;
				}
			
				if(isset($sign_up_term) && has_term($sign_up_term->term_id, 'dbm_relation') && $is_logged_in) {
					wp_redirect($this->get_global_page_url(array('global-pages', 'my-account')), 302);
					exit;
				}
				
				if(isset($sign_in_term) && !$is_logged_in) {
					
					$forgot_term = dbm_get_relation(array('global-pages', 'reset-password'));
					$reset_term = dbm_get_relation(array('global-pages', 'lost-password'));
					$ignore_term = dbm_get_relation_by_path('restrict-access/ignore-login-restriction');
					
					if(!$this->has_any_relation(array($sign_in_term, $sign_up_term, $forgot_term, $reset_term, $ignore_term))) {
						
						$require_sign_in_term = dbm_get_relation_by_path('restrict-access/require-signed-in');
					
						$require_sign_in = (isset($require_sign_in_term) && has_term($require_sign_in_term->term_id, 'dbm_relation')) || apply_filters('dbm_custom_login/require_sign_in_for_all_pages', false, get_the_ID(), get_post());
					
						if($require_sign_in) {
							$sign_in_url = $this->get_global_page_url(array('global-pages', 'sign-in'));
							
							$requested_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
							
							$sign_in_url .= '?redirect_to='.urlencode($requested_url);
							wp_redirect($sign_in_url, 302);
							exit;
						}
					}
				}
			}
		}
		
		public function hook_login_form_login() {
			//echo("\DbmCustomLogin\RedirectHooks::hook_login_form_login<br />");
			
			if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
				$redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : null;
 
				if ( is_user_logged_in() ) {
					$this->redirect_logged_in_user( $redirect_to );
					exit;
				}
				
				$sign_in_page_url = $this->get_global_page_url(array('global-pages', 'sign-in'));
				
				if($sign_in_page_url !== null) {
					wp_redirect($sign_in_page_url);
					exit;
				}
			}
		}
		
		protected function get_global_page_url($term_path) {
			$page_term = dbm_get_relation($term_path);
			if($page_term) {
				$pages_ids = get_posts(array(
					'post_type' => 'page',
					'fields' => 'ids',
					'posts_per_page' => 1,
					'tax_query' => array(
						array(
							'taxonomy' => 'dbm_relation',
							'field' => 'id',
							'terms' => array($page_term->term_id),
							'include_children' => false
						)
					)
				));
				
				if(count($pages_ids) > 0) {
					$page_id = $pages_ids[0];
					return get_permalink($page_id);
				}
			}
			
			return null;
		}
		
		protected function redirect_logged_in_user($redirect_to = null) {
			$user = wp_get_current_user();
			if(user_can($user, 'edit_posts')) {
				if($redirect_to) {
					wp_safe_redirect($redirect_to);
				}
				else {
					wp_redirect(admin_url());
				}
			}
			else {
				$redirect_url = home_url();
				
				$start_page_url = $this->get_global_page_url(array('global-pages', 'sign-in', 'start-page'));
				if($start_page_url !== null) {
					$redirect_url = $start_page_url;
				}
				
				wp_redirect($redirect_url);
			}
		}
		
		public function filter_maybe_redirect_at_authenticate($user, $username, $password) {
			// Check if the earlier authenticate filter (most likely, 
			// the default WordPress authentication) functions have found errors
			if($_SERVER['REQUEST_METHOD'] === 'POST') {
				if(is_wp_error($user)) {
					
					$sign_in_page_url = $this->get_global_page_url(array('global-pages', 'sign-in'));
					
					if($sign_in_page_url !== null) {
						$error_codes = join(',', $user->get_error_codes());
						
						$sign_in_page_url = add_query_arg('notice', $error_codes, $sign_in_page_url);
						
						wp_redirect($sign_in_page_url);
						exit;
					}
				}
			}
			
			return $user;
		}
		
		public function hook_redirect_after_logout() {
			
			$signed_out_page_url = $this->get_global_page_url(array('global-pages', 'sign-in', 'signed-out'));
			
			if($signed_out_page_url !== null) {
				$signed_out_page_url = add_query_arg('notice', 'signed_out', $signed_out_page_url);
				wp_safe_redirect($signed_out_page_url);
				exit;
			}
		}
		
		public function filter_redirect_after_login($redirect_to, $requested_redirect_to, $user) {
			$redirect_url = home_url();
			
			if(!isset($user->ID)) {
				return $redirect_url;
			}
			
			if(user_can($user, 'edit_posts')) {
				// Use the redirect_to parameter if one is set, otherwise redirect to admin dashboard.
				if($requested_redirect_to == '' || $requested_redirect_to == home_url()) {
					$redirect_url = admin_url();
				}
				else {
					$redirect_url = $requested_redirect_to;
				}
			}
			else {
				// Non-admin users always go to their account page after login
				$start_page_url = $this->get_global_page_url(array('global-pages', 'sign-in', 'start-page'));
				if($start_page_url !== null) {
					$redirect_url = $start_page_url;
				}
				
			}
			
			return wp_validate_redirect($redirect_url, home_url());
		}
		
		public function hook_redirect_to_custom_lostpassword() {
			if('GET' == $_SERVER['REQUEST_METHOD']) {
				if ( is_user_logged_in() ) {
					$this->redirect_logged_in_user();
					exit;
				}
				
				$lost_password_url = $this->get_global_page_url(array('global-pages', 'lost-password'));
				if($lost_password_url) {
					wp_redirect($lost_password_url);
					exit;
				}
			}
		}
		
		public function hook_do_password_lost() {
			if('POST' == $_SERVER['REQUEST_METHOD']) {
				$errors = retrieve_password();
				
				if(is_wp_error($errors)) {
					// Errors found
					
					$lost_password_url = $this->get_global_page_url(array('global-pages', 'lost-password'));
					if($lost_password_url) {
						$lost_password_url = add_query_arg('notice', join(',' , $errors->get_error_codes()), $lost_password_url);
						wp_redirect($lost_password_url);
						exit;
					}
				}
				else {
					// Email sent
					$sign_in_page_url = $this->get_global_page_url(array('global-pages', 'sign-in'));
					if($sign_in_page_url) {
						$sign_in_page_url = add_query_arg('notice', 'check_email_confirm', $sign_in_page_url);
						wp_redirect($sign_in_page_url);
						exit;
					}
				}
			}
		}
		
		public function filter_replace_retrieve_password_message( $message, $key, $user_login, $user_data ) {
			
			add_filter( 'wp_mail_content_type', function() {
				return "text/html";
			});
			
			// Create new message
			$msg  = __( 'Hello!', 'personalize-login' ) . "<br /><br />";
			$msg .= sprintf( __( 'You asked us to reset your password for your account using the email address %s.', 'personalize-login' ), $user_login ) . "<br /><br />";
			$msg .= __( "If this was a mistake, or you didn't ask for a password reset, just ignore this email and nothing will happen.", 'personalize-login' ) . "<br /><br />";
			$msg .= __( 'To reset your password, visit the following address:', 'personalize-login' ) . "<br /><br />";
			$msg .= site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . "<br /><br />";
			$msg .= __( 'Thanks!', 'personalize-login' ) . "<br />";
 
			return $msg;
		}
		
		public function hook_redirect_to_custom_password_reset() {
			if('GET' == $_SERVER['REQUEST_METHOD']) {
				// Verify key / login combo
				$user = check_password_reset_key($_REQUEST['key'], $_REQUEST['login']);
				if (!$user || is_wp_error($user)) {
					$sign_in_page_url = $this->get_global_page_url(array('global-pages', 'sign-in'));
					if($sign_in_page_url) {
						if ($user && $user->get_error_code() === 'expired_key') {
							$sign_in_page_url = add_query_arg('notice', 'expired_key', $sign_in_page_url);
						}
						else {
							$sign_in_page_url = add_query_arg('notice', 'invalid_key', $sign_in_page_url);
						}
						
						wp_redirect($sign_in_page_url);
						exit;
					}
				}
				else {
					$reset_password_page_url = $this->get_global_page_url(array('global-pages', 'reset-password'));
					
					$reset_password_page_url = add_query_arg('login', esc_attr($_REQUEST['login']), $reset_password_page_url);
					$reset_password_page_url = add_query_arg('key', esc_attr($_REQUEST['key']), $reset_password_page_url);
					
					wp_redirect($reset_password_page_url);
					exit;
				}
			}
		}
		
		public function hook_do_password_reset() {
			if('POST' == $_SERVER['REQUEST_METHOD'] && isset($_REQUEST['rp_key']) && isset($_REQUEST['rp_login'])) {
				$rp_key = $_REQUEST['rp_key'];
				$rp_login = $_REQUEST['rp_login'];
				
				$user = check_password_reset_key( $rp_key, $rp_login );
				
				if ( ! $user || is_wp_error( $user ) ) {
					$sign_in_page_url = $this->get_global_page_url(array('global-pages', 'sign-in'));
					if($sign_in_page_url) {
						if ($user && $user->get_error_code() === 'expired_key') {
							$sign_in_page_url = add_query_arg('notice', 'expired_key', $sign_in_page_url);
						}
						else {
							$sign_in_page_url = add_query_arg('notice', 'invalid_key', $sign_in_page_url);
						}
					
						wp_redirect($sign_in_page_url);
						exit;
					}
				}
				
				if ( isset( $_POST['pass1'] ) ) {
					if ( empty( $_POST['pass1'] ) ) {
						// Password is empty
						$reset_password_page_url = $this->get_global_page_url(array('global-pages', 'reset-password'));
						
						if($reset_password_page_url) {
						
							$reset_password_page_url = add_query_arg( 'key', $rp_key, $reset_password_page_url );
							$reset_password_page_url = add_query_arg( 'login', $rp_login, $reset_password_page_url );
							$reset_password_page_url = add_query_arg( 'notice', 'password_reset_empty', $reset_password_page_url );
						
							wp_redirect( $reset_password_page_url );
							exit;
						}
					}
					else {
						// Parameter checks OK, reset password
						reset_password( $user, $_POST['pass1'] );
					
						$sign_in_page_url = $this->get_global_page_url(array('global-pages', 'sign-in'));
						if($sign_in_page_url) {
							$sign_in_page_url = add_query_arg('notice', 'password_changed', $sign_in_page_url);
							wp_redirect( $sign_in_page_url );
							exit;
						}
					}
				}
				else {
					echo "Invalid request.";
					exit;
				}
			}
		}
		
		public static function test_import() {
			echo("Imported \DbmCustomLogin\RedirectHooks<br />");
		}
	}
?>