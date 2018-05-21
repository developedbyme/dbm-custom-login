<?php
	namespace DbmCustomLogin\OddCore\RestApi;
	
	use \WP_Query;
	use DbmCustomLogin\OddCore\RestApi\EndPoint as EndPoint;
	
	// \DbmCustomLogin\OddCore\RestApi\ReactivatePluginEndpoint
	class ReactivatePluginEndpoint extends EndPoint {
		
		protected $_plugin = null;
		
		function __construct() {
			//echo("\OddCore\RestApi\ReactivatePluginEndpoint::__construct<br />");
			
		}
		
		public function set_plugin($plugin) {
			
			$this->_plugin = $plugin;
			
			return $this;
		}
		
		public function perform_call($data) {
			//echo("\OddCore\RestApi\ReactivatePluginEndpoint::perform_call<br />");
			
			$this->_plugin->activation_setup();
			
			return $this->output_success(true);
		}
		
		public static function test_import() {
			echo("Imported \OddCore\RestApi\ReactivatePluginEndpoint<br />");
		}
	}
?>