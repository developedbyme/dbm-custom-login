<?php
	namespace DbmCustomLogin;
	
	use \DbmCustomLogin\OddCore\PluginBase;
	
	class Plugin extends PluginBase {
		
		function __construct() {
			//echo("\DbmCustomLogin\Plugin::__construct<br />");
			
			$this->_default_hook_priority = 20;
			
			parent::__construct();
			
			//$this->add_javascript('dbm-custom-login-main', DBM_CUSTOM_LOGIN_URL.'/assets/js/main.js');
		}
		
		protected function create_pages() {
			//echo("\DbmCustomLogin\Plugin::create_pages<br />");
			
		}
		
		protected function create_custom_post_types() {
			//echo("\DbmCustomLogin\Plugin::create_custom_post_types<br />");
			
			
		}
		
		public function register_hooks() {
			parent::register_hooks();
			
			if(function_exists('dbm_content_add_owned_relationship')) {
				dbm_content_add_owned_relationship_with_auto_add('notice', 'notices');
			}
			
			add_action('wprr/prepare_api_user', array($this, 'hook_wprr_prepare_api_user'), $this->_default_hook_priority, 1);
			add_action('dbm_custom_login/set_new_user_data/fromInvite', array($this, 'hook_dbm_custom_login_set_new_user_data_fromInvite'), 10, 2);
		}
		
		protected function create_additional_hooks() {
			//echo("\DbmCustomLogin\Plugin::create_additional_hooks<br />");
			
			$this->add_additional_hook(new \DbmCustomLogin\RedirectHooks());
			$this->add_additional_hook(new \DbmCustomLogin\ApiActionHooks());
			$this->add_additional_hook(new \DbmCustomLogin\ChangePostHooks());
			$this->add_additional_hook(new \DbmCustomLogin\CustomRangeFilters());
			
		}
		
		protected function create_rest_api_end_points() {
			//echo("\DbmCustomLogin\Plugin::create_rest_api_end_points<br />");
			
			$api_namespace = 'dbm-custom-login';
			
			//Utils
			$current_end_point = new \DbmCustomLogin\OddCore\RestApi\ReactivatePluginEndpoint();
			$current_end_point->set_plugin($this);
			$current_end_point->add_headers(array('Access-Control-Allow-Origin' => '*'));
			$current_end_point->setup('reactivate-plugin', $api_namespace, 1, 'GET');
			$this->_rest_api_end_points[] = $current_end_point;
			
		}
		
		
		
		protected function create_filters() {
			//echo("\DbmCustomLogin\Plugin::create_filters<br />");
			
			add_filter('dbm_custom_login/can_register/fromInvite', array($this, 'filter_dbm_custom_login_can_register_fromInvite'), 10, 2);
			add_filter('dbm_custom_login/registration_is_verified/fromInvite', '__return_true');
			
		}
		
		protected function create_shortcodes() {
			//echo("\DbmCustomLogin\OddCore\PluginBase::create_shortcodes<br />");
			
			$current_shortcode = new \DbmCustomLogin\Shortcode\WprrShortcode();
			$this->add_shortcode($current_shortcode);
		}
		
		
		public function hook_admin_enqueue_scripts() {
			//echo("\DbmCustomLogin\Plugin::hook_admin_enqueue_scripts<br />");
			
			parent::hook_admin_enqueue_scripts();
			
		}
		
		public function hook_wprr_prepare_api_user($data) {
			//echo("\DbmCustomLogin\Plugin::hook_wprr_prepare_api_user<br />");
			
			$key = $data->get_header('X-dbm-api-key');
			if($key) {
				
				$key_post_id = dbm_new_query('dbm_data')->set_field('post_status', array('publish', 'private'))->add_type_by_path('api-key')->add_meta_query('key', $key)->get_post_id();
				if($key_post_id) {
					$userId = (int)get_post_meta($key_post_id, 'userId', true);
					$password = $data->get_header('X-dbm-api-password');
				
					$salt = 'S<KUn@DHY/JY.M9X)zh0<dJ-H~89j}Ge>-H?;r@Pr:k=~_R^GX?(}Gdqji[+~i_+';
		
					$encoded_password = md5($userId.$key.$password.$salt);
					
					$stored_encoded_password = get_post_meta($key_post_id, 'password', true);
					
					if($encoded_password === $stored_encoded_password) {
						wp_set_current_user($userId);
					}
					else {
						throw(new \Exception("Invalid credentials"));
					}
				}
				else {
					throw(new \Exception("Invalid key"));
				}
			
			}
		}
		
		public function filter_dbm_custom_login_can_register_fromInvite($can_register, $data) {
			
			$invite_id = dbm_new_query('dbm_data')->set_field('post_status', array('private', 'publish'))->set_field('post__in', array($data['inviteId']))->add_type_by_path('signup-invite')->add_relation_by_path('invite-status/open')->add_meta_query('token', $data['token'])->get_post_id();
			if($invite_id) {
				return true;
			}
			else {
				return false;
			}
		}
		
		public function hook_dbm_custom_login_set_new_user_data_fromInvite($user_id, $data) {

			$post = dbm_get_post($data['inviteId']);
			$post->add_user_relation($user_id, 'invite-used-by');
			//$post->set_single_relation_by_name('invite-status/used'); //MEDEBUG: //
			
		}
		
		public function activation_setup() {
			\DbmCustomLogin\Admin\PluginActivation::run_setup();
		}
		
		public static function test_import() {
			echo("Imported \DbmCustomLogin\Plugin<br />");
		}
	}
?>