<?php
/**
 * WDS_Core_Admin class can be used by any component. It helps build admin pages
 *
 * @package Infinite SEO Core
 */
class WDS_Core_Admin {

	var $slug = '';
	var $title = '';
	var $description = '';
	var $fields = array();
	var $parent_slug = '';
	var $capability = 'manage_options';
	var $options_name = '';
	var $options = '';
	var $contextual_help = '';
	var $additional = '';

	/* Add text to settings form */
	function add_section_text( $args ) {
		$class = $args['callback'][0];
		$fields = $class->fields;
		$section_id = $args['id'];
		echo $fields[$section_id]['intro'];
	}

	/* Add checkbox fields to settings form */
	function add_checkbox_field( $args = array() ) {
		$defaults = array(
			'name' => '', 'title' => '', 'items' => array(),
			'class' => ''
		);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		$options_name = $this->options_name;
		$options = $this->options;

		echo "<fieldset><legend class='screen-reader-text'><span>$title</span></legend>";
		foreach( $items as $item => $label ) {
			$options[$item] = isset( $options[$item] ) ? $options[$item] : '';
			$checked = ( !empty( $options[$item] ) ) ? " checked='checked'" : '';
			echo "<label for='$options_name-$name-$item'>
				<input$checked id='$options_name-$name-$item' name='{$options_name}[{$item}]' type='checkbox' /> $label
			</label><br>";
		}
		echo "</fieldset>";
	}

	/* Add dropdown field to settings form */
	function add_dropdown_field( $args = array() ) {
		$defaults = array(
			'name' => '', 'title' => '', 'description' => false, 'class' => 'postform',
			'items' => array()
		);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		$options_name = $this->options_name;
		$options = $this->options;
		$options[$name] = isset( $options[$name] ) ? $options[$name] : '';

		echo "<select class='$class' id='$name' name='{$options_name}[{$name}]'>";
			foreach( $items as $item => $label ) {
				$selected = ( $options[$name] == $item ) ? " selected='selected'" : '';
				echo "<option value='$item'$selected class='level-0'>$label</option>";
			}
		echo "</select>";
		if ($description) echo "<div class='description'>$description</div>";
	}

	/* Add textarea field to settings form */
	function add_textarea_field( $args = array() ) {
		$defaults = array(
			'name' => '', 'title' => '', 'description' => '',
			'class' => '', 'rows' => '2', 'cols' => '35'
		);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		$options_name = $this->options_name;
		$options = $this->options;
		$options[$name] = isset( $options[$name] ) ? $options[$name] : '';

		echo "<fieldset>
			<legend class='screen-reader-text'><span>$title</span></legend>
			<textarea class='$class' id='$name' name='{$options_name}[{$name}]' rows='$rows' cols='$cols' type='textarea'>{$options[$name]}</textarea>
			<div class='description'>$description</div>
		</fieldset>";
	}

	/* Add text field to settings form */
	function add_text_field( $args = array() ) {
		$defaults = array(
			'name' => '', 'title' => '', 'description' => '',
			'size' => '', 'type' => 'text', 'class' => 'regular-text'
		);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		$options_name = $this->options_name;
		$options = $this->options;
		$options[$name] = isset( $options[$name] ) ? $options[$name] : '';

		echo "<input id='$name' name='{$options_name}[{$name}]' size='$size' type='$type' class='{$class}' value='{$options[$name]}' /><p class='description'>$description</p>";
	}

	/* Add radio fields to settings form */
	function add_radio_field( $args = array() ) {
		$defaults = array(
			'name' => '', 'title' => '', 'items' => array(),
			'description' => '', 'class' => ''
		);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		$options_name = $this->options_name;
		$options = $this->options;
		$options[$name] = isset( $options[$name] ) ? $options[$name] : '';

		echo "<fieldset><legend class='screen-reader-text'><span>$title</span></legend>";
			if( !empty( $description ) ) echo "$description<br>";
				foreach( $items as $item => $label ) {
					$checked = ( $options[$name] == $item ) ? " checked='checked'" : '';
					echo "<label for='$item'><input$checked value='$item' name='{$options_name}[{$name}]' type='radio' /> $label</label><br>";
				}
		echo "</fieldset>";
	}

	/* Add text field to settings form */
	function add_content_field( $args = array() ) {
		$defaults = array(
			'text' => ''
		);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		echo $text;
	}

	/* Register our settings. Add the settings section, and settings fields */
	function init(){
		$options_name = $this->options_name;
		$slug = $this->slug;

		$this->register_setting( $options_name, $options_name, array( &$this, 'validate' ) );

		if ( $this->fields ) {
			foreach ( $this->fields as $section_slug => $section ) {
				add_settings_section( $section_slug, @$section['title'], array( &$this, 'add_section_text' ), $slug );
				foreach ( $section['options'] as $option ) {
					add_settings_field( $option['name'], $option['title'], array( &$this, "add_{$option['type']}_field" ),  $slug, $section_slug, $option );
				}
			}
		}

		wp_register_style( 'wdsstyle', WDS_PLUGIN_URL . 'css/admin.css', false, '0.1' );
		wp_register_script( 'wdsscript', WDS_PLUGIN_URL . 'js/admin.js', array( 'jquery' ), '0.1' );
	}

	function register_setting( $option_group, $option_name, $sanitize_callback = '' ) {
		if ( is_multisite() && 'wds_settings_options' == $option_group ) {
			if ( wds_is_wizard_step( 2 ) )
				$option_group = $option_name = 'wds_sitemap_options';

			if ( wds_is_wizard_step( 3 ) )
				$option_group = $option_name = 'wds_onpage_options';

			if ( wds_is_wizard_step( 4 ) )
				$option_group = $option_name = 'wds_seomoz_options';

			if ( wds_is_wizard_step( 5 ) )
				$option_group = $option_name = 'wds_autolinks_options';
		}

		register_setting( $option_group, $option_name, $sanitize_callback );
	}

	/* Add sub page to the Settings Menu */
	function add_page() {
		global $wds_page_hook, $wp_version;

		if ( ! is_multisite() || (is_multisite() && !WDS_SITEWIDE)) {
			$wds_page_hook = add_submenu_page( 'options-general.php', __( 'Infinite SEO Wizard' , 'wds'), __( 'Infinite SEO', 'wds' ), $this->capability, 'wds_wizard', array( &$this, 'options_page' ) );
		} else if (is_multisite() && version_compare( $wp_version , '3.0.9', '>' ) ) {
			$wds_page_hook = add_submenu_page( 'settings.php', __( 'Infinite SEO Wizard' , 'wds'), __( 'Infinite SEO', 'wds' ), $this->capability, 'wds_wizard', array( &$this, 'options_page' ) );
		} else if (is_multisite()) {
			$wds_page_hook = add_submenu_page( 'ms-admin.php', __( 'Infinite SEO Wizard' , 'wds'), __( 'Infinite SEO', 'wds' ), $this->capability, 'wds_wizard', array( &$this, 'options_page' ) );
		}

		add_action( "admin_print_styles-$wds_page_hook", array( &$this, 'admin_styles' ) );
		add_action( "admin_print_scripts-$wds_page_hook", array( &$this, 'admin_scripts' ) );
	}

	/* Enqueue styles */
	function admin_styles() {
		wp_enqueue_style( 'wdsstyle' );
	}

	/* Enqueue scripts */
	function admin_scripts() {
		wp_enqueue_script( 'wdsscript' );
	}

	/* Validate user data for some/all of your input fields */
	function validate($input) {
		return $input; // return validated input
	}

}

class WDS_Core_Admin_Tabs {

	private static $_sections = array();

	public static function register ($step, $name, $title, $description = '', $fields = array(), $contextual_help, $additional = '') {
		self::$_sections[intval($step)] = array(
			'slug' => $name,
			'title' => $title
		);
		ksort(self::$_sections);
		if (wds_is_wizard_step($step)) return new WDS_Core_Admin_Tab($name, $title, $description, $fields, 'wds', $contextual_help, $additional);
	}

	public static function get_sections () {
		return (is_multisite() && true === WDS_SITEWIDE)
			? self::_get_all_sections()
			: self::_get_allowed_sections()
		;
	}

	public static function get_step ($step) {
		if (empty($step) || !is_numeric($step)) return false;
		return self::$_sections[intval($step)];
	}

	public static function has_protected_tabs () {
		if (is_multisite() && true === WDS_SITEWIDE) return false;
		$blog_tabs = get_site_option('wds_blog_tabs');
		return !empty($blog_tabs);
	}

	public static function get_first_allowed_step () {
		$step = 1;
		if (!self::has_protected_tabs()) return $step;

		$sections = self::_get_allowed_sections();
		if (empty($sections)) return false; // No allowed sections (yet), so... no step to return

		return reset(array_keys($sections));
	}

	private static function _get_all_sections () {
		return self::$_sections;
	}

	private static function _get_allowed_sections () {
		$res = array();
		foreach (self::$_sections as $idx => $section) {
			if (empty($section['slug'])) continue;
			if (!wds_is_allowed_tab($section['slug'])) continue;
			$res[$idx] = $section;
		}
		return $res;
	}

}

class WDS_Core_Admin_Tab extends WDS_Core_Admin {


	/**
	* PHP5 constructor
	*/
	function __construct( $name, $title, $description = '', $fields = array(), $parent_slug = '', $contextual_help, $additional = '' ) {
		global $wds_options, $wp_version;

		$this->slug = $name;
		$this->name = str_replace( 'wds_', '', $name );
		$this->title = $title;
		$this->description = $description;
		$this->fields = $fields;
		$this->parent_slug = $parent_slug;
		$this->contextual_help = $contextual_help;
		$this->additional = $additional;

		$this->options_name = $options_name = $name . '_options';

		if( is_multisite() && WDS_SITEWIDE == true ) {
			$this->options = get_site_option( $options_name );
			$this->capability = 'manage_network_options';
		} else {
			$this->options = get_option( $options_name );
		}

		add_action( 'admin_init', array( &$this, 'init' ) );
		if ( is_multisite() && version_compare( $wp_version , '3.0.9', '>' ) )
			add_action( 'network_admin_menu', array( &$this, 'add_page' ) );
			if (!WDS_SITEWIDE) add_action( 'admin_menu', array( &$this, 'add_page' ) );
		else
			add_action( 'admin_menu', array( &$this, 'add_page' ) );
		add_filter( 'contextual_help', array( &$this, 'contextual_help' ), 10, 3 );
	}

	/**
	* PHP4 constructor
	*/
	function WDS_Core_Admin_Tab( $name, $title, $description = '', $fields = array(), $parent_slug = '', $contextual_help, $additional = '' ) {
		$this->__construct( $name, $title, $description = '', $fields = array(), $parent_slug = '', $contextual_help, $additional = '' );
	}

	/* Display the admin options page */
	function options_page() {
		global $wds_options;

		$slug = $this->slug;
		$title = $this->title;
		$description = $this->description;
		$options_name = $this->options_name;

		$msg = '';
		if (isset($_GET['updated']) && $_GET['updated'] == 'true') {
			$msg = __( 'Settings updated' , 'wds');

			if ( function_exists( 'w3tc_pgcache_flush' ) ) {
				w3tc_pgcache_flush();
				$msg .= __( ' &amp; W3 Total Cache Page Cache flushed' , 'wds');
			} else if ( function_exists( 'wp_cache_clear_cache' )) {
				wp_cache_clear_cache();
				$msg .= __( ' &amp; WP Super Cache flushed' , 'wds');
			}

			$msg = '<div id="message" style="width:94%;" class="message updated"><p><strong>' . $msg . '.</strong></p></div>';
		}

		$blog_tabs = get_site_option('wds_blog_tabs');
		$blog_tabs = is_array($blog_tabs) ? $blog_tabs : array();

		$tabs = '';
		$sections = WDS_Core_Admin_Tabs::get_sections();
		foreach ($sections as $idx => $section) {
			if (empty($section['slug']) || empty($section['title'])) continue;
			$active = wds_is_wizard_step( $idx ) ? ' nav-tab-active' : '';
			$tab_title = esc_attr($section['title']);
			$tab_text = WDS_Core_Admin_Tabs::has_protected_tabs()
				? $tab_title
				: esc_html(sprintf(__('Step %d: %s', 'wds'), $idx, $tab_title))
			;
			$tabs .= "<a href='admin.php?page=wds_wizard&step={$idx}' class='wds_tab_item nav-tab$active' title='{$tab_title}'>{$tab_text}</a>";
		}
		$action_url = admin_url( 'options.php' );

		echo "<div class='wrap'>$msg
			<div class='icon32' id='icon-options-general'><br></div>
			<h2>" . __( 'Infinite SEO Wizard', 'wds' ) . "</h2>
			<h3 class='nav-tab-wrapper'>$tabs</h3>
			<h2>$title</h2>";


		if (!wds_is_allowed_tab($slug)) {
			printf(__("Your network admin prevented access to '%s', please move onto next step.", 'wds'), $title);
		} else if ( 'settings' == $this->name || ( isset( $wds_options[$this->name] ) && $wds_options[$this->name] = 'on' ) ) {
			echo "$description
				<form action='$action_url' method='post'>";
				settings_fields( $options_name );
				@do_settings_sections( $slug );

				if (is_multisite() && WDS_SITEWIDE == true) {
					$checked_y = in_array($slug, $blog_tabs) ? 'checked="checked"' : '';
					$checked_n = in_array($slug, $blog_tabs) ? '' : 'checked="checked"';
					echo '<h3>' . __('Allow Site Admin access', 'wds') . '</h3>';
					_e('<p>If enabled, this tab will be available to Site Admins once you switch to per-blog mode.</p>', 'wds');
					echo
						__('Show this to Site Admins:', 'wds') .
						"&nbsp;" .
						"<input type='radio' id='wds_blog_tabs-yes' name='wds_blog_tabs[{$slug}]' value='1' {$checked_y} />" .
							'<label for="wds_blog_tabs-yes">' . __("Yes", "wds") . '</label>' .
						"&nbsp;" .
						"<input type='radio' id='wds_blog_tabs-no' name='wds_blog_tabs[{$slug}]' value='0' {$checked_n} />" .
							'<label for="wds_blog_tabs-no">' . __("No", "wds") . '</label>'
					;
				}

				echo "<p class='submit'>
					<input name='Submit' type='submit' class='button-primary' value='" . esc_attr( __( 'Save Settings' , 'wds') ) . "' />
				</p>
				</form>
				{$this->additional}";
		} else {
			printf(__("You've chosen not to set up '%s', please move onto next step.", 'wds'), $title);
		}

		echo "</div>";
	}

	function contextual_help($contextual_help, $screen_id, $screen) {

		if ( isset($_GET['page']) && $_GET['page'] == 'wds_wizard' && $this->contextual_help ) {
			$contextual_help = $this->contextual_help;
		}

		return $contextual_help;
	}


	/**** ADDED ****/
	/* Methods below this line are new. */


	/**
	 * Merges allowed blog tabs for when WDS_SITEWIDE === false
	 */
	function _merge_tabs ($data) {
		$key = current(array_keys($data));
		if (!$key) return false;

		$opts = get_site_option('wds_blog_tabs');
		$opts = is_array($opts) ? $opts : array();

		$opts_key = array_search($key, $opts);
		if (false === $opts_key) $opts_key = count($opts);

		if ((int)$data[$key] > 0) $opts[$opts_key] = $key;
		else unset($opts[$opts_key]);
		update_site_option('wds_blog_tabs', $opts);
	}

	/**
	 * Brute-register all the settings.
	 *
	 * If we got this far, this is a sane thing to do.
	 * This overrides the `WDS_Core_Admin::register_setting()`.
	 *
	 * In response to "Unable to save options multiple times" bug.
	 */
	function register_setting( $option_group, $option_name, $sanitize_callback = '' ) {
		if (is_multisite() && WDS_SITEWIDE == true && isset($_POST['wds_blog_tabs'])) $this->_merge_tabs($_POST['wds_blog_tabs']);

		register_setting( 'wds_settings_options', 'wds_settings_options', $sanitize_callback );
		register_setting( 'wds_sitemap_options', 'wds_sitemap_options', $sanitize_callback );
		register_setting( 'wds_onpage_options', 'wds_onpage_options', $sanitize_callback );
		register_setting( 'wds_seomoz_options', 'wds_seomoz_options', $sanitize_callback );
		register_setting( 'wds_autolinks_options', 'wds_autolinks_options', $sanitize_callback );
	}

	/**
	 * Add text field to settings form with an hidden input.
	 *
	 * This overrides the `WDS_Core_Admin::add_content_field()` by adding a hidden input with option value.
	 * This is done to complement the `register_setting()` change above.
	 * Together, the end result should be the same (sans multiple saves error).
	 */
	function add_content_field( $args = array() ) {
		$defaults = array(
			'text' => ''
		);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		$options_name = $this->options_name;
		$options = $this->options;
		$options[$name] = isset( $options[$name] ) ? $options[$name] : '';

		echo "{$text}<input id='$name' name='{$options_name}[{$name}]' type='hidden'value='{$options[$name]}' />";
	}
}

/**
 * Shows blog not being public notice.
 */
function wds_blog_not_public_notice () {
	if (!current_user_can('manage_options')) return false;
	echo '<div class="error"><p>' .
		sprintf(__('This site discourages search engines from indexing the pages, which will affect your SEO efforts. <a href="%s">You can fix this here</a>'), admin_url('/options-reading.php')) .
	'</p></div>';
}