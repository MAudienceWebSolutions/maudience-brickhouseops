<?php
/**
Plugin Name: WPMU Dev code library
Plugin URI:  http://premium.wpmudev.org/
Description: Framework to support creating WordPress plugins and themes.
Version:     1.1.2
Author:      WPMU DEV
Author URI:  http://premium.wpmudev.org/
Textdomain:  wpmu-lib
*/

/**
 * Constants for wp-config.php
 *
 * define( 'WDEV_UNMINIFIED', true ) // Load the unminified JS/CSS files
 * define( 'WDEV_DEBUG', true ) // Activate WDev()->debug() without having to enable WP_DEBUG
 */

$version = '1.1.2'; // Remember to update the class-name in functions-wpmulib.php!!

/**
 * Load TheLib class definition if not some other plugin already loaded it.
 */
$dirname = dirname( __FILE__ ) . '/inc/';
$file_ver = str_replace( '.', '_', $version );
$main_class = 'TheLib_' . $file_ver;

$files = array(
	$main_class . '_Base' => 'class-thelib-base.php',
	$main_class . '_Html' => 'class-thelib-html.php',
	$main_class           => 'class-thelib.php',
);

foreach ( $files as $class_name => $class_file ) {
	if ( ! class_exists( $class_name ) && file_exists( $dirname . $class_file ) ) {
		require_once( $dirname . $class_file );
	}
}

if ( ! class_exists( 'TheLibWrap' ) ) {
	/**
	 * The wrapper class is used to handle situations when some plugins include
	 * different versions of TheLib.
	 *
	 * TheLibWrap will always keep the latest version of TheLib for later usage.
	 */
	class TheLibWrap {
		static public $version = '0.0.0';
		static public $object = null;

		static public function set_obj( $version, $obj ) {
			if ( version_compare( $version, self::$version, '>' ) ) {
				self::$version = $version;
				self::$object = $obj;
			}
		}
	};
}
$obj = new $main_class();
TheLibWrap::set_obj( $version, $obj );

if ( ! function_exists( 'WDev' ) ) {
	/**
	 * This is a shortcut function to access the latest TheLib object.
	 *
	 * Usage:
	 *   WDev()->message();
	 */
	function WDev() {
		$obj = TheLibWrap::$object;

		if ( func_num_args() ) {
			$func = func_get_arg( 0 );
			$args = func_get_args();
			array_shift( $args );

			if ( is_callable( array( $obj, $func ) ) ) {
				return call_user_method_array( $func, $obj, $args );
			}
		}

		return $obj;
	}
}