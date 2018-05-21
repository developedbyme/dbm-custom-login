<?php
/*
	Plugin Name: Dbm Custom login
	Plugin URI:  http://littlebearabroad.com
	Description: Custom login pages with Dbm content
	Version:     0.1.0
	Author:      Mattias Ekendahl
	Author URI:  http://developedbyme.com
	License:     GPL3
	License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/



/* ====================================================================
|  SETUP AND GENERAL
|  General features and setup actions
'---------------------------------------------------------------------- */

define("DBM_CUSTOM_LOGIN_VERSION", "0.1.0");
define("DBM_CUSTOM_LOGIN_DOMAIN", "dbm-custom-login");
define("DBM_CUSTOM_LOGIN_TEXTDOMAIN", "dbm-custom-login");
define("DBM_CUSTOM_LOGIN_MAIN_FILE", __FILE__);
define("DBM_CUSTOM_LOGIN_DIR", untrailingslashit( dirname( __FILE__ )  ) );
define("DBM_CUSTOM_LOGIN_URL", untrailingslashit( plugins_url('',  __FILE__ )  ) );

// Plugin textdomain: dbm-custom-login
function dbm_custom_login_load_textdomain() {
	
	load_plugin_textdomain( DBM_CUSTOM_LOGIN_TEXTDOMAIN, false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

}
add_action( 'plugins_loaded', 'dbm_custom_login_load_textdomain' );

require_once( DBM_CUSTOM_LOGIN_DIR . "/libs/DbmCustomLogin/bootstrap.php" );
//require_once('vendor/autoload.php');

global $DbmCustomLoginPlugin;
$DbmCustomLoginPlugin = new \DbmCustomLogin\Plugin();

require_once( DBM_CUSTOM_LOGIN_DIR . "/external-functions.php" );

function dbm_custom_login_plugin_activate() {
	global $DbmCustomLoginPlugin;
	$DbmCustomLoginPlugin->activation_setup();
}
register_activation_hook( __FILE__, 'dbm_custom_login_plugin_activate' );

?>