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
		}
		
		protected function create_additional_hooks() {
			//echo("\DbmCustomLogin\Plugin::create_additional_hooks<br />");
			
			$this->add_additional_hook(new \DbmCustomLogin\RedirectHooks());
			$this->add_additional_hook(new \DbmCustomLogin\ApiActionHooks());
			
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

			$custom_range_filters = new \DbmCustomLogin\CustomRangeFilters();
			
			add_filter('wprr/range_encoding/notice', array($custom_range_filters, 'encode_notice'), 10, 2);
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
		
		public function activation_setup() {
			\DbmCustomLogin\Admin\PluginActivation::run_setup();
		}
		
		public static function test_import() {
			echo("Imported \DbmCustomLogin\Plugin<br />");
		}
	}
?>