<?php
	namespace DbmCustomLogin;
	
	use \DbmCustomLogin\OddCore\PluginBase;
	
	class Plugin extends PluginBase {
		
		function __construct() {
			//echo("\DbmCustomLogin\Plugin::__construct<br />");
			
			$this->add_additional_hook(new \DbmCustomLogin\RedirectHooks());
			
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
			
			
		}
		
		protected function create_rest_api_end_points() {
			//echo("\DbmCustomLogin\Plugin::create_rest_api_end_points<br />");
			
			$api_namespace = 'dbm-custom-login';
			
		}
		
		
		
		protected function create_filters() {
			//echo("\DbmCustomLogin\Plugin::create_filters<br />");

			$custom_range_filters = new \DbmCustomLogin\CustomRangeFilters();
			
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