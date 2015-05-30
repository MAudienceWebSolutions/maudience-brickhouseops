<?php

// Based on Jigsaw plugin by Jared Novack (http://jigsaw.upstatement.com/)
class TheLib_1_1_2 extends TheLib_1_1_2_Base {

	/**
	 * Holds the HTML Helper component
	 *
	 * @since 1.1.0
	 *
	 * @var   TheLib_Html
	 */
	public $html = null;

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		// Check for persistent data from last request that needs to be processed.
		$this->check_persistent_data();

		// Create a new HTML helper component.
		$class_name = __CLASS__ . '_Html';
		$this->html = new $class_name();
	}

	/**
	 * Enqueue core UI files (CSS/JS).
	 *
	 * Defined modules:
	 *  - core
	 *  - scrollbar
	 *  - select
	 *  - vnav
	 *
	 * @since  1.0.0
	 * @param  string $modules The module to load.
	 * @param  string $onpage A page hook; files will only be loaded on this page.
	 */
	public function add_ui( $module = 'core', $onpage = 'all' ) {
		switch ( $module ) {
			case 'core':
				$this->add_css( $this->_css_url( 'wpmu-ui.min.css' ), $onpage );
				$this->add_js( $this->_js_url( 'wpmu-ui.min.js' ), $onpage );
				break;

			case 'scrollbar':
				$this->add_js( $this->_js_url( 'tiny-scrollbar.min.js' ), $onpage );
				break;

			case 'select':
				$this->add_css( $this->_css_url( 'select2.min.css' ), $onpage );
				$this->add_js( $this->_js_url( 'select2.min.js' ), $onpage );
				break;

			case 'vnav':
				$this->add_css( $this->_css_url( 'wpmu-vnav.min.css' ), $onpage );
				$this->add_js( $this->_js_url( 'wpmu-vnav.min.js' ), $onpage );
				break;

			case 'card-list':
			case 'card_list':
				$this->add_css( $this->_css_url( 'wpmu-card-list.min.css' ), $onpage );
				$this->add_js( $this->_js_url( 'wpmu-card-list.min.js' ), $onpage );
				break;

			case 'html-element':
			case 'html_element':
				$this->add_css( $this->_css_url( 'wpmu-html.min.css' ), $onpage );
				break;

			case 'media':
				$this->add_js( 'wpmu:media', $onpage );
				break;

			case 'fontawesome':
				$this->add_css( $this->_css_url( 'fontawesome.min.css' ), $onpage );
				break;

			case 'jquery-ui':
				$this->add_js( 'jquery-ui-core', $onpage );
				$this->add_js( 'jquery-ui-datepicker', $onpage );
				$this->add_css( $this->_css_url( 'jquery-ui.wpmui.min.css' ), $onpage );
				break;

			default:
				$ext = strrchr( $module, '.' );

				if ( defined( 'WDEV_UNMINIFIED' ) && WDEV_UNMINIFIED ) {
					$module = str_replace( '.min' . $ext, $ext, $module );
				}
				if ( '.css' === $ext ) {
					$this->add_css( $module, $onpage, 20 );
				} else if ( '.js' === $ext ) {
					$this->add_js( $module, $onpage, 20 );
				}
		}
	}

	/**
	 * Adds a variable to javascript.
	 *
	 * @since 1.0.7
	 *
	 * @param string $name Name of the variable
	 * @param mixed $data Value of the variable
	 */
	public function add_data( $name, $data ) {
		$hooked = $this->_have( 'js_data_hook' );
		$this->_add( 'js_data_hook', true );

		// Determine which hook should print the data.
		$hook = ( is_admin() ? 'admin_head' : 'wp_head' );

		// Enqueue the data for output with javascript sources.
		$this->_add( 'js_data', array( $name, $data ) );

		if ( ! $hooked && ! did_action( $hook ) ) {
			add_action(
				$hook,
				array( $this, '_print_script_data' )
			);
		}

		if ( did_action( $hook ) ) {
			// Javascript sources already enqueued:
			// Directly output the data right now.
			$this->_print_script_data();
		}
	}

	/**
	 * Enqueue a javascript file.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $url Full URL to the javascript file.
	 * @param  string $onpage A page hook; files will only be loaded on this page.
	 * @param  int $priority Loading order. The higher the number, the later it is loaded.
	 */
	public function add_js( $url, $onpage = 'all', $priority = 10 ) {
		$this->_prepare_js_or_css( $url, 'js', $onpage, $priority );
	}

	/**
	 * Enqueue a css file.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $url Full URL to the css filename.
	 * @param  string $onpage A page hook; files will only be loaded on this page.
	 * @param  int $priority Loading order. The higher the number, the later it is loaded.
	 */
	public function add_css( $url, $onpage = 'all', $priority = 10 ) {
		$this->_prepare_js_or_css( $url, 'css', $onpage, $priority );
	}

	/**
	 * Prepare to enqueue a javascript or css file.
	 *
	 * @since  1.0.7
	 * @private
	 *
	 * @param  string $url Full URL to the javascript/css file.
	 * @param  string $type 'css' or 'js'
	 * @param  string $onpage A page hook; files will only be loaded on this page.
	 * @param  int $priority Loading order. The higher the number, the later it is loaded.
	 */
	protected function _prepare_js_or_css( $url, $type, $onpage, $priority ) {
		$hooked = $this->_have( 'js_or_css' );
		$this->_add( 'js_or_css', compact( 'url', 'type', 'onpage', 'priority' ) );

		if ( ! did_action( 'init' ) ) {
			$hooked || add_action(
				'init',
				array( $this, '_add_js_or_css' )
			);
		} else {
			$this->_add_js_or_css();
		}
	}

	/**
	 * Returns the JS/CSS handle of the item.
	 * This is a private helper function used by array_map()
	 *
	 * @since  1.0.7
	 * @private
	 */
	public function _get_script_handle( $item ) {
		return @$item->handle;
	}

	/**
	 * Enqueues either a css or javascript file
	 *
	 * @since  1.0.0
	 * @private
	 */
	public function _add_js_or_css() {
		global $wp_styles, $wp_scripts;

		$scripts = $this->_get( 'js_or_css' );
		$this->_clear( 'js_or_css' );

		// Prevent adding the same URL twice.
		$done_urls = array();

		foreach ( $scripts as $script ) {
			extract( $script ); // url, type, onpage, priority

			// Skip Front-End files in Admin Dashboard.
			if ( 'front' === $onpage && is_admin() ) { continue; }

			// Prevent adding the same URL twice.
			if ( in_array( $url, $done_urls ) ) { continue; }
			$done_urls[] = $url;

			$type = ( 'css' === $type || 'style' === $type ? 'css' : 'js' );

			// The $handle values are intentionally not cached:
			// Any plugin/theme could add new handles at any moment...
			$handles = array();
			if ( 'css' == $type ) {
				if ( ! is_a( $wp_styles, 'WP_Styles' ) ) {
					$wp_styles = new WP_Styles();
				}
				$handles = array_values(
					array_map(
						array( $this, '_get_script_handle' ),
						$wp_styles->registered
					)
				);
				$type_callback = '_enqueue_style_callback';
			} else {
				if ( ! is_a( $wp_scripts, 'WP_Scripts' ) ) {
					$wp_scripts = new WP_Scripts();
				}
				$handles = array_values(
					array_map(
						array( $this, '_get_script_handle' ),
						$wp_scripts->registered
					)
				);
				$type_callback = '_enqueue_script_callback';
			}

			if ( in_array( $url, $handles ) ) {
				$alias = $url;
				$url = '';
			} else {
				// Get the filename from the URL, then sanitize it and prefix "wpmu-"
				$urlparts = explode( '?', $url, 2 );
				$alias = 'wpmu-' . sanitize_title( basename( $urlparts[0] ) );
			}
			$onpage = empty( $onpage ) ? 'all' : $onpage;

			if ( ! is_admin() ) {
				$hook = 'wp_enqueue_scripts';
			} else {
				$hook = 'admin_enqueue_scripts';
			}

			$item = compact( 'url', 'alias', 'onpage' );
			$hooked = $this->_have( $type );
			$this->_add( $type, $item );

			if ( ! did_action( $hook ) ) {
				$hooked || add_action(
					$hook,
					array( $this, $type_callback ),
					100 + $priority // Load custom styles a bit later than core styles.
				);
			} else {
				$this->$type_callback();
			}
		}
	}

	/**
	 * Action hook for enqueue style (for PHP <5.3 only)
	 *
	 * @since  1.0.1
	 * @private
	 *
	 * @param  string $hook The current admin page that is rendered.
	 */
	public function _enqueue_style_callback( $hook = '' ) {
		$items = $this->_get( 'css' );

		if ( empty( $hook ) ) { $hook = 'front'; }

		foreach ( $items as $item ) {
			extract( $item ); // url, alias, onpage

			if ( empty( $onpage ) ) { $onpage = 'all'; }

			// onpage == 'all' will always load the script.
			// otherwise onpage must match the enqueue-hook.
			if ( 'all' == $onpage || $hook == $onpage ) {
				if ( empty( $url ) ) {
					wp_enqueue_style( $alias );
				} else {
					wp_enqueue_style( $alias, $url );
				}
			}
		}
	}

	/**
	 * Action hook for enqueue script (for PHP <5.3 only)
	 *
	 * @since  1.0.1
	 * @private
	 *
	 * @param  string $hook The current admin page that is rendered.
	 */
	public function _enqueue_script_callback( $hook = '' ) {
		$items = $this->_get( 'js' );

		if ( empty( $hook ) ) { $hook = 'front'; }

		foreach ( $items as $item ) {
			extract( $item ); // url, alias, onpage

			if ( empty( $onpage ) ) { $onpage = 'all'; }

			// onpage == 'all' will always load the script.
			// otherwise onpage must match the enqueue-hook.
			if ( 'all' == $onpage || $hook == $onpage ) {
				// Load the Media-library functions.
				if ( 'wpmu:media' === $url ) {
					wp_enqueue_media();
					continue;
				}

				// Register script if it has an URL.
				if ( ! empty( $url ) ) {
					wp_register_script( $alias, $url, array( 'jquery' ), false, true );
				}

				// Enqueue the script for output in the page footer.
				wp_enqueue_script( $alias );
			}
		}
	}

	/**
	 * Prints extra script data to the page.
	 *
	 * @action `wp_head`
	 * @since  1.1.1
	 * @private
	 */
	public function _print_script_data() {
		$data = $this->_get( 'js_data' );
		$this->_clear( 'js_data' );

		// Append javascript data to the script output.
		if ( is_array( $data ) ) {
			foreach ( $data as $item ) {
				if ( ! is_array( $item ) ) { continue; }

				printf(
					'<script>window.%1$s = %2$s;</script>',
					sanitize_html_class( $item[0] ),
					json_encode( $item[1] )
				);
			}
		}
	}


	/**
	 * Displays a WordPress pointer on the current admin screen.
	 *
	 * @since  1.0.0
	 * @param  string $pointer_id Internal ID of the pointer, make sure it is unique!
	 * @param  string $html_el HTML element to point to (e.g. '#menu-appearance')
	 * @param  string $title The title of the pointer.
	 * @param  string $body Text of the pointer.
	 */
	public function pointer( $pointer_id, $html_el, $title, $body ) {
		$this->html->pointer( $pointer_id, $html_el, $title, $body );
	}



	/**
	 * Display an admin notice.
	 *
	 * @since  1.0.0
	 * @param  string $text Text to display.
	 * @param  string $class Message-type [updated|error]
	 * @param  string $screen Limit message to this screen-ID
	 * @param  string $id Message ID. Prevents adding duplicate messages.
	 */
	public function message( $text, $class = '', $screen = '', $id = '' ) {
		if ( 'red' == $class || 'err' == $class || 'error' == $class ) {
			$class = 'error';
		} else {
			$class = 'updated';
		}

		// Check if the message is already queued...
		$items = self::_sess_get( 'message' );
		foreach ( $items as $key => $data ) {
			if (
				$data['text'] == $text &&
				$data['class'] == $class &&
				$data['screen'] == $screen
			) {
				return; // Don't add duplicate message to queue.
			}

			/**
			 * `$id` prevents adding duplicate messages.
			 *
			 * @since 1.1.0
			 */
			if ( ! empty( $id ) && $data['id'] == $id ) {
				return; // Don't add duplicate message to queue.
			}
		}

		self::_sess_add( 'message', compact( 'text', 'class', 'screen', 'id' ) );

		if ( did_action( 'admin_notices' ) ) {
			$this->_admin_notice_callback();
		} else {
			$this->_have( '_admin_notice' ) || add_action(
				'admin_notices',
				array( $this, '_admin_notice_callback' ),
				1
			);
			$this->_add( '_admin_notice', true );
		}
	}

	/**
	 * Action hook for admin notices (for PHP <5.3 only)
	 *
	 * @since  1.0.1
	 * @private
	 */
	public function _admin_notice_callback() {
		$items = self::_sess_get( 'message' );
		self::_sess_clear( 'message' );
		$screen_info = get_current_screen();
		$screen_id = $screen_info->id;

		foreach ( $items as $item ) {
			extract( $item ); // text, class, screen, id
			if ( empty( $screen ) || $screen_id == $screen ) {
				printf(
					'<div class="%1$s %3$s"><p>%2$s</p></div>',
					esc_attr( $class ),
					$text,
					esc_attr( $id )
				);
			}
		}
	}



	/**
	 * Short way to load the textdomain of a plugin.
	 *
	 * @since  1.0.0
	 * @param  string $domain Translations will be mapped to this domain.
	 * @param  string $rel_dir Path to the dictionary folder; relative to ABSPATH.
	 */
	public function translate_plugin( $domain, $rel_dir ) {
		$hooked = $this->_have( 'textdomain' );

		$this->_add( 'textdomain', compact( 'domain', 'rel_dir' ) );

		if ( ! did_action( 'plugins_loaded' ) ) {
			$hooked || add_action(
				'plugins_loaded',
				array( $this, '_translate_plugin_callback' )
			);
		} else {
			$this->_translate_plugin_callback();
		}
	}

	/**
	 * Create function callback for load textdomain (for PHP <5.3 only)
	 *
	 * @since  1.0.1
	 * @private
	 */
	public function _translate_plugin_callback() {
		$items = $this->_get( 'textdomain' );
		foreach ( $items as $item ) {
			extract( $item ); // domain, rel_dir
			load_plugin_textdomain( $domain, false, $rel_dir );
		}
	}



	/**
	 * Checks the DB for persistent data from last request.
	 * If persistent data exists the appropriate hooks are set to process them.
	 *
	 * @since  1.0.7
	 */
	public function check_persistent_data() {
		// $this->message()
		if ( self::_sess_have( 'message' ) ) {
			$this->_have( '_admin_notice' ) || add_action(
				'admin_notices',
				array( $this, '_admin_notice_callback' ),
				1
			);
			$this->_add( '_admin_notice', true );
		}
	}

	/**
	 * Returns the current URL.
	 * This URL is not guaranteed to look exactly same as the user sees it.
	 * E.g. Hashtags are missing ("index.php#section-a")
	 *
	 * @since  1.0.7
	 * @param  string $protocol Optional. Define URL protocol ('http', 'https')
	 * @return string Full URL to current page.
	 */
	public function current_url( $protocol = null ) {
		static $Url = array();

		if ( null !== $protocol ) {
			// Remove the "://" part, if it was provided
			$protocol = array_shift( explode( ':', $protocol ) );
		}

		if ( ! isset( $Url[$protocol] ) ) {
			if ( null === $protocol ) {
				$cur_url = 'http';

				if ( isset( $_SERVER['HTTPS'] )
					&& strtolower( $_SERVER['HTTPS'] ) === 'on'
				) {
					$cur_url .= 's';
				}
			} else {
				$cur_url = $protocol;
			}

			$is_ssl = 'https' === $cur_url;
			$cur_url .= '://';
			$cur_url .= $_SERVER['SERVER_NAME'];

			if ( ( ! $is_ssl && $_SERVER['SERVER_PORT'] != '80' ) ||
				( $is_ssl && $_SERVER['SERVER_PORT'] != '443' )
			) {
				$cur_url .= ':' . $_SERVER['SERVER_PORT'];
			}

			if ( empty( $_SERVER['REQUEST_URI'] ) ) {
				$cur_url = trailingslashit( $cur_url );
			} else {
				$cur_url .= $_SERVER['REQUEST_URI'];
			}

			$Url[$protocol] = $cur_url;
		}

		return $Url[$protocol];
	}

	/**
	 * Adds a value to the data collection in the user session.
	 *
	 * @since  1.0.15
	 * @param  string $key The key of the value.
	 * @param  mixed $value Value to store.
	 */
	public function store_add( $key, $value ) {
		self::_sess_add( 'store:' . $key, $value );
	}

	/**
	 * Returns the current data array of the specified value from user session.
	 *
	 * @since  1.0.15
	 * @param  string $key The key of the value.
	 * @return array The value, or an empty array if no value was assigned yet.
	 */
	public function store_get( $key ) {
		$vals = self::_sess_get( 'store:' . $key );
		foreach ( $vals as $key => $val ) {
			if ( null === $val ) { unset( $vals[ $key ] ); }
		}
		$vals = array_values( $vals );
		return $vals;
	}

	/**
	 * Returns the current data array of the specified value from user session
	 * and then clears the values from the session.
	 *
	 * @since  1.0.15
	 * @param  string $key The key of the value.
	 * @return array The value, or an empty array if no value was assigned yet.
	 */
	public function store_get_clear( $key ) {
		$val = $this->store_get( $key );
		self::_sess_clear( 'store:' . $key );
		return $val;
	}

	/**
	 * If the specified variable is an array it will be returned. Otherwise
	 * an empty array is returned.
	 *
	 * @since  1.0.14
	 * @param  mixed $val1 Value that maybe is an array.
	 * @param  mixed $val2 Optional, Second value that maybe is an array.
	 * @return array
	 */
	public function get_array( &$val1, $val2 = array() ) {
		if ( is_array( $val1 ) ) {
			return $val1;
		} else if ( is_array( $val2 ) ) {
			return $val2;
		} else {
			return array();
		}
	}

	/**
	 * Checks if the given array contains all the specified fields.
	 * If fields are not defined then they will be added to the source array
	 * with the boolean value false.
	 *
	 * This function is used to initialize optional fields.
	 * It is optimized and tested to yield best performance.
	 *
	 * @since  1.0.14
	 * @param  Array|Object $arr The array or object to check.
	 * @param  strings|Array $fields List of fields to check for.
	 * @return int Number of missing fields that were initialized.
	 */
	public function load_fields( &$arr, $fields ) {
		$missing = 0;
		$is_obj = false;

		if ( is_object( $arr ) ) { $is_obj = true; }
		else if ( ! is_array( $arr ) ) { return -1; }

		if ( ! is_array( $fields ) ) {
			$fields = func_get_args();
			array_shift( $fields ); // Remove $arr from the field list.
		}

		foreach ( $fields as $field ) {
			if ( $is_obj ) {
				if ( ! isset( $arr->$field ) ) {
					$arr->$field = false;
					$missing += 1;
				}
			} else {
				if ( ! isset( $arr[ $field ] ) ) {
					$arr[ $field ] = false;
					$missing += 1;
				}
			}
		}

		return $missing;
	}

	/**
	 * Short function for WDev()->load_fields( $_POST, ... )
	 *
	 * @since  1.0.14
	 * @param  strings|Array <param list>
	 * @return int Number of missing fields that were initialized.
	 */
	public function load_post_fields( $fields ) {
		$fields = is_array( $fields ) ? $fields : func_get_args();
		return $this->load_fields( $_POST, $fields );
	}

	/**
	 * Short function for WDev()->load_fields( $_REQUEST, ... )
	 *
	 * @since  1.0.14
	 * @param  strings|Array <param list>
	 * @return int Number of missing fields that were initialized.
	 */
	public function load_request_fields( $fields ) {
		$fields = is_array( $fields ) ? $fields : func_get_args();
		return $this->load_fields( $_REQUEST, $fields );
	}

	/**
	 * Starts a file download and terminates the current request.
	 * Note that this does not work inside Ajax requests!
	 *
	 * @since  1.1.0
	 * @param  string $contents The file contents (text file).
	 * @param  string $filename The file name.
	 */
	public function file_download( $contents, $filename ) {
		// Send the download headers.
		header( 'Pragma: public' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Cache-Control: private', false ); // required for certain browsers
		header( 'Content-type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Length: ' . strlen( $contents ) );

		// Finally send the export-file content.
		echo '' . $contents;

		exit;
	}

	/**
	 * Checks if the provided value evaluates to a boolean TRUE.
	 *
	 * Following values are considered true:
	 *  - Boolean: true
	 *  - Number: anything except 0
	 *  - Strings: true, yes, on (case insensitive)
	 *
	 * @since  1.1.0
	 * @param  [type] $value [description]
	 * @return bool [description]
	 */
	public function is_true( $value ) {
		if ( $value === false || $value === null || $value === '' ) {
			return false;
		} elseif ( $value === true ) {
			return true;
		} elseif ( is_numeric( $value ) ) {
			$value = intval( $value );
			return $value != 0;
		} elseif ( is_string( $value ) ) {
			$value = strtolower( trim( $value ) );
			return in_array(
				$value,
				array( 'true', 'yes', 'on', '1' )
			);
		}
		return false;
	}

	/**
	 * Checks if the specified URL is publicly reachable.
	 *
	 * @since  1.1.0
	 * @param  string $url The URL to check.
	 * @return bool If URL is online or not.
	 */
	public function is_online( $url ) {
		static $Checked = array();

		if ( ! isset( $Checked[$url] ) ) {
			$check = 'http://www.isup.me/' . $url;
			$res = wp_remote_get( $check );

			if ( is_wp_error( $res ) ) {
				$state = false;
			} else {
				$state = ( false === stripos( $res['body'], 'not just you' ) );
			}

			$Checked[$url] = $state;
		}

		return $Checked[$url];
	}

	/**
	 * Displays a debug message at the current position on the page.
	 *
	 * @since  1.0.14
	 * @param mixed <dynamic> Each param will be dumped
	 */
	public function debug() {
		static $Need_styles = true;

		if ( ( ! defined( 'WDEV_DEBUG' ) || ! WDEV_DEBUG )
			&& ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG )
		) { return; }

		if ( $Need_styles ) {
			?>
			<style>
			.wdev-debug {
				clear: both;
				border: 1px solid #C00;
				background: rgba(255, 200, 200, 1);
				padding: 10px;
				margin: 10px;
				position: relative;
				z-index: 99999;
				box-shadow: 0 1px 5px rgba(0,0,0,0.3);
				font-size: 12px;
			}
			.wdev-debug-clear {
				clear: both;
				display: table;
				padding: 0;
				margin: 0;
			}
			.wdev-debug-label {
				font-size: 11px;
				float: right;
				margin: -10px;
				color: #FFF;
				background-color: #D88;
				padding: 2px 8px;
				cursor: pointer;
			}
			.wdev-debug-label:hover {
				background-color: #E66;
			}
			.wdev-debug pre {
				font-size: 12px !important;
				margin: 1px 0 !important;
				background: rgba(255, 200, 200, 0.8);
			}
			.wdev-debug .wdev-param {
				background: rgba( 0,0,0,0.1 );
				padding: 0 3px;
				font-size: 11px;
			}
			.wdev-debug table td {
				padding: 1px 2px !important;
				font-size: 12px;
			}
			.wdev-debug table {
				margin: 4px 0 0 0;
				background: #EBB;
			}
			</style>
			<?php
			$Need_styles = false;
		}

		if ( ! defined( '__DEBUG_SCRIPT' ) ) {
			define( '__DEBUG_SCRIPT', true );
			echo '<script>function toggleBlock(item) {';
			echo 'var block = jQuery(item).closest(".wdev-debug");';
			echo 'var wrap = block.find(".wdev-debug-wrap");';
			echo 'wrap.toggle();';
			echo '}</script>';
		}

		echo '<div class="wdev-debug">';
		echo '<span class="wdev-debug-label" onclick="toggleBlock(this)">DEBUG</span>';
		echo '<div class="wdev-debug-wrap">';
		foreach ( func_get_args() as $param ) {
			$this->dump( $param );
		}
		echo '<table class="wdev-trace" cellspacing="0" cellpadding="3" border="1">';

		// Display the backtrace.
		$trace = debug_backtrace();
		$trace_num = count( $trace );
		for ( $i = 0; $i < $trace_num; $i += 1 ) {
			$item = $trace[$i];
			$line_item = $item;
			$j = $i;
			while ( empty( $line_item['line'] ) && $j < $trace_num ) {
				$line_item = $trace[$j];
				$j += 1;
			}

			$args = '';
			$arg_num = '';
			$this->load_fields( $item, 'args' );

			if ( $i > 0 && is_array( $item['args'] ) ) {
				$argnum = count( $item['args'] );

				if ( $argnum > 0 ) {
					if ( is_scalar( $item['args'][0] ) ) {
						$first = '"' . strval( $item['args'][0] ) . '"';
					} else {
						$first = '...';
					}

					if ( $argnum > 1 ) {
						$dummy = array_fill( 0, $argnum - 1, '...' );
					} else {
						$dummy = array();
					}

					array_unshift( $dummy, $first );
					$args = implode( '</span>, <span class="wdev-param">', $dummy );
					$args = '<span class="wdev-param">' . $args . '</span>';
				}
			}

			printf(
				'<tr><td>%1$s</td><td>%2$s</td><td>%3$s</td></tr>',
				$i,
				@$line_item['file'] . ': ' . @$line_item['line'],
				@$item['class'] . @$item['type'] . @$item['function'] . '(' . $args . ')'
			);
		}
		echo '</table>';
		echo '</div>';
		echo '<div class="wdev-debug-clear"></div>';
		echo '</div>';
	}

	/**
	 * Outputs an advanced var dump.
	 *
	 * @since  1.1.0
	 * @param  any $input The variable/object/value to dump.
	 * @param  int $default_depth Deeper items will be collapsed
	 * @param  int $level Do not change this value!
	 */
	public function dump( $data, $default_depth = 2, $level = 0 ) {
		if ( $level === 0 && ! defined( 'DUMP_DEBUG_SCRIPT' ) ) {
			define( 'DUMP_DEBUG_SCRIPT', true );

			echo '<script>function toggleDisplay(id,display) {';
			echo 'var data = document.getElementById("container"+id);';
			echo 'var plus = document.getElementById("plus"+id);';
			echo 'var state = data.style.display;';
			echo 'data.style.display = state == display ? "none" : display;';
			echo 'if (plus) plus.style.display = state == "inline" ? "inline" : "none";';
			echo '}</script>';
		}

		if ( ! is_string( $data ) && is_callable( $data ) ) {
			$type = 'Callable';
		} else {
			$type = ucfirst( gettype( $data ) );
		}

		$collapsed = $level >= $default_depth;

		$type_data = null;
		$type_color = null;
		$type_length = null;
		$full_dump = false;

		switch ( $type ) {
			case 'String':
				$type_color = 'green';
				$type_length = strlen( $data );
				$type_data = '"' . htmlentities( $data ) . '"';
				break;

			case 'Double':
			case 'Float':
				$type = 'Float';
				$type_color = '#0099c5';
				$type_length = strlen( $data );
				$type_data = htmlentities( $data );
				break;

			case 'Integer':
				$type_color = 'red';
				$type_length = strlen( $data );
				$type_data = htmlentities( $data );
				break;

			case 'Boolean':
				$type_color = '#92008d';
				$type_length = strlen( $data );
				$type_data = $data ? 'TRUE' : 'FALSE';
				break;

			case 'NULL':
				$type_length = 0;
				$type_color = '#AAA';
				$type_data = 'NULL';
				break;

			case 'Array':
				$type_length = count( $data );
				break;

			case 'Object':
				$full_dump = true;
				break;
		}

		$type_label = $type . ( $type_length !== null ? '(' . $type_length . ')' : '' );

		if ( in_array( $type, array( 'Object', 'Array' ) ) ) {
			$populated = false;

			foreach ( $data as $key => $value ) {
				if ( ! $populated ) {
					$populated = true;

					$id = substr( md5( rand() . ':' . $key . ':' . $level ), 0, 8 );

					echo '<a href="javascript:toggleDisplay(\''. $id . '\',\'inline\');" style="text-decoration:none">';
					echo '<span style="color:#666666">' . $type_label . '</span>';
					echo '</a>';

					echo '<span id="plus' . $id . '" style="display: ' . ( $collapsed ? 'inline' : 'none' ) . ';">&nbsp;&#10549;</span>';
					echo '<div id="container' . $id . '" style="display: ' . ( $collapsed ? 'none' : 'inline' ) . ';">';
					echo '<br />';

					for ( $i = 0; $i <= $level; $i++ ) {
						echo '&nbsp;&nbsp;<span style="color:black">|</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					}
					if ( $full_dump ) {
						echo '<a href="javascript:toggleDisplay(\''. $id . '-dump\',\'block\');" style="text-decoration:none;color:#66A">';
						echo '( full dump )';
						echo '</a>';
						echo '<div id="container' . $id . '-dump" style="display: none;">';
						var_dump( $data );
						echo '</div>';
					}

					echo '<br />';
				}


				for ( $i = 0; $i <= $level; $i++ ) {
					echo '&nbsp;&nbsp;<span style="color:black">|</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				}

				echo '<span style="color:black">[' . $key . ']&nbsp;=>&nbsp;</span>';

				$this->dump( $value, $default_depth, $level + 1 );
			}

			if ( $populated ) {
				for ( $i = 0; $i <= $level; $i++ ) {
					echo '&nbsp;&nbsp;<span style="color:black">|</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				}

				echo '</div>';

			} else {
				echo '<span style="color:#666666">' . $type_label . '</span>&nbsp;&nbsp;';
			}
		} else {
			echo '<span style="color:#666666">' . $type_label . '</span>&nbsp;&nbsp;';

			if ( $type_data != null ) {
				echo '<span style="color:' . $type_color . '">' . $type_data . '</span>';
			}
		}

		echo '<br />';
	}
};