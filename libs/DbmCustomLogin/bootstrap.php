<?php

function DbmCustomLogin_Autoloader( $class ) {
	//echo("DbmCustomLogin_Autoloader<br />");
	
	$namespace_length = strlen("DbmCustomLogin");
	
	// Is a DbmCustomLogin class
	if ( substr( $class, 0, $namespace_length ) != "DbmCustomLogin" ) {
		return false;
	}

	// Uses namespace
	if ( substr( $class, 0, $namespace_length+1 ) == "DbmCustomLogin\\" ) {

		$path = explode( "\\", $class );
		unset( $path[0] );

		$class_file = trailingslashit( dirname( __FILE__ ) ) . implode( "/", $path ) . ".php";

	}

	// Doesn't use namespaces
	elseIf ( substr( $class, 0, $namespace_length+1 ) == "DbmCustomLogin_" ) {

		$path = explode( "_", $class );
		unset( $path[0] );

		$class_file = trailingslashit( dirname( __FILE__ ) ) . implode( "/", $path ) . ".php";

	}

	// Get class
	if ( isset($class_file) && is_file( $class_file ) ) {

		require_once( $class_file );
		return true;

	}

	// Fallback to error
	return false;

}

spl_autoload_register("DbmCustomLogin_Autoloader"); // Register autoloader