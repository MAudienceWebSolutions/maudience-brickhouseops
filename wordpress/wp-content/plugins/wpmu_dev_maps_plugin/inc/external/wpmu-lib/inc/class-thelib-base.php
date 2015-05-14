<?php

/**
 * Implement uniform data storage and sharing among all child classes.
 *
 * @since  1.1.0
 */
class TheLib_1_1_2_Base {

	// --- Start of 5.2 compatibility functions

	/**
	 * Internal data collection used to pass arguments to callback functions.
	 * Only used for 5.2 version as alternative to closures.
	 *
	 * @var array
	 */
	static protected $data = array();


	protected function _have( $key ) {
		return isset( self::$data[ $key ] );
	}

	protected function _add( $key, $value ) {
		if ( ! isset( self::$data[ $key ] )
			|| ! is_array( self::$data[ $key ] )
		) {
			self::$data[ $key ] = array();
		}

		self::$data[ $key ][] = $value;
	}

	protected function _get( $key ) {
		if ( ! isset( self::$data[ $key ] )
			|| ! is_array( self::$data[ $key ] )
		) {
			self::$data[ $key ] = array();
		}

		return self::$data[ $key ];
	}

	protected function _clear( $key ) {
		self::$data[ $key ] = array();
	}


	// --- End of 5.2 compatibility functions

	// --- Start of Session access

	/**
	 * Flag if we can use the $_SESSION variable
	 *
	 * @var bool
	 */
	static protected $_have_session = null;

	static private function _sess_init() {
		if ( null !== self::$_have_session ) { return; }

		self::$_have_session = false;

		if ( ! session_id() ) {
			if ( ! headers_sent() ) {
				session_start();
				self::$_have_session = true;
			}
		} else {
			self::$_have_session = true;
		}
	}

	static protected function _sess_have( $key ) {
		if ( null === self::$_have_session ) { self::_sess_init(); }
		if ( ! self::$_have_session ) { return false; }

		return isset( $_SESSION[ '_lib_persist_' . $key ] );
	}

	static protected function _sess_add( $key, $value ) {
		if ( null === self::$_have_session ) { self::_sess_init(); }
		if ( ! self::$_have_session ) { return; }

		if ( ! isset( $_SESSION[ '_lib_persist_' . $key ] )
			|| ! is_array( $_SESSION[ '_lib_persist_' . $key ] )
		) {
			$_SESSION[ '_lib_persist_' . $key ] = array();
		}

		$_SESSION[ '_lib_persist_' . $key ][] = $value;
	}

	static protected function _sess_get( $key ) {
		if ( null === self::$_have_session ) { self::_sess_init(); }
		if ( ! self::$_have_session ) { return array(); }

		if ( ! isset( $_SESSION[ '_lib_persist_' . $key ] )
			|| ! is_array( $_SESSION[ '_lib_persist_' . $key ] )
		) {
			$_SESSION[ '_lib_persist_' . $key ] = array();
		}

		return $_SESSION[ '_lib_persist_' . $key ];
	}

	static protected function _sess_clear( $key ) {
		if ( null === self::$_have_session ) { self::_sess_init(); }
		if ( ! self::$_have_session ) { return; }

		unset( $_SESSION[ '_lib_persist_' . $key ] );
	}

	// --- End of Session access

	/**
	 * Base constructor. Initialize the session if not already done.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		self::_sess_init();
	}

	/**
	 * Returns the full URL to an internal CSS file of the code library.
	 *
	 * @since  1.0.0
	 *
	 * @private
	 * @param  string $file The filename, relative to this plugins folder.
	 * @return string
	 */
	protected function _css_url( $file ) {
		static $Url = null;

		if ( defined( 'WDEV_UNMINIFIED' ) && WDEV_UNMINIFIED ) {
			$file = str_replace( '.min.css', '.css', $file );
		}
		if ( null === $Url ) {
			$Url = plugins_url( 'css/', dirname( __FILE__ ) );
		}

		return $Url . $file;
	}

	/**
	 * Returns the full URL to an internal JS file of the code library.
	 *
	 * @since  1.0.0
	 *
	 * @private
	 * @param  string $file The filename, relative to this plugins folder.
	 * @return string
	 */
	protected function _js_url( $file ) {
		static $Url = null;

		if ( defined( 'WDEV_UNMINIFIED' ) && WDEV_UNMINIFIED ) {
			$file = str_replace( '.min.js', '.js', $file );
		}
		if ( null === $Url ) {
			$Url = plugins_url( 'js/', dirname( __FILE__ ) );
		}

		return $Url . $file;
	}

	/**
	 * Returns the full path to an internal php partial of the code library.
	 *
	 * @since  1.0.0
	 *
	 * @private
	 * @param  string $file The filename, relative to this plugins folder.
	 * @return string
	 */
	protected function _view_path( $file ) {
		static $Path = null;

		if ( null === $Path ) {
			$basedir = dirname( dirname( __FILE__ ) ) . '/';
			$Path = $basedir . 'view/';
		}

		return $Path . $file;
	}

};