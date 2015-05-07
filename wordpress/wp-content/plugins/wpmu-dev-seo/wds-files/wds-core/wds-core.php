<?php
/**
 * wds_get_value(), wds_replace_vars(), wds_get_term_meta()
 * inspired by WordPress SEO by Joost de Valk (http://yoast.com/wordpress/seo/).
 */

function wds_get_value ($val, $post_id=false) {
	if (!$post_id) {
		global $post;
		$post_id = isset($post) ? $post->ID : false;
	}
	if (!$post_id) return false;

	$custom = get_post_custom($post_id);
	return ( !empty($custom['_wds_'.$val][0]) ) ?
		maybe_unserialize($custom['_wds_'.$val][0])
		:
		false
	;
}

function wds_set_value ($meta, $val, $post_id) {
	update_post_meta($post_id, "_wds_{$meta}", $val);
}

function wds_value ($val, $filter=false) {
	$val = wds_get_value($val);
	$val = $filter ? apply_filters('the_content', $val) : $val;
	echo $val;
}

function get_wds_options () {
	if( is_multisite() && WDS_SITEWIDE == true ) {
		return array_merge(
			(array) get_site_option( 'wds_settings_options' ),
			(array) get_site_option( 'wds_autolinks_options' ),
			(array) get_site_option( 'wds_onpage_options' ),
			//(array) get_site_option( 'wds_sitemaps_options' ), // Removed plural
			(array) get_site_option( 'wds_sitemap_options' ), // Added singular
			(array) get_site_option( 'wds_seomoz_options' )
		);
	} else if (is_multisite() && !WDS_SITEWIDE) {
		$settings = (array) (wds_is_allowed_tab('wds_settings') ? get_option('wds_settings_options') : get_site_option('wds_settings_options'));
		$autolinks = (array) (wds_is_allowed_tab('wds_autolinks') ? get_option('wds_autolinks_options') : get_site_option('wds_autolinks_options'));
		$onpage = (array) (wds_is_allowed_tab('wds_onpage') ? get_option('wds_onpage_options') : get_site_option('wds_onpage_options'));
		$sitemap = (array) (wds_is_allowed_tab('wds_sitemap') ? get_option('wds_sitemap_options') : get_site_option('wds_sitemap_options'));
		$seomoz = (array) (wds_is_allowed_tab('wds_seomoz') ? get_option('wds_seomoz_options') : get_site_option('wds_seomoz_options'));
		return array_merge(
			$settings,
			$autolinks,
			$onpage,
			$sitemap,
			$seomoz
		);
	} else {
		return array_merge(
			(array) get_option( 'wds_settings_options' ),
			(array) get_option( 'wds_autolinks_options' ),
			(array) get_option( 'wds_onpage_options' ),
			//(array) get_option( 'wds_sitemaps_options' ), // Removed plural
			(array) get_option( 'wds_sitemap_options' ), // Added singular
			(array) get_option( 'wds_seomoz_options' )
		);
	}
}

function wds_replace_vars ($string, $args=array()) {
	global $wp_query;

	$defaults = array(
		'ID' => '',
		'name' => '',
		'post_author' => '',
		'post_content' => '',
		'post_date' => '',
		'post_excerpt' => '',
		'post_modified' => '',
		'post_title' => '',
		'taxonomy' => '',
		'description' => '',
		'username' => '',
		'full_name' => '',
	);

	$pagenum = get_query_var('paged');
	if ($pagenum === 0) {
		$pagenum = ($wp_query->max_num_pages > 1) ? 1 : '';
	}

	$r = wp_parse_args($args, $defaults);

	$replacements = array(
		'%%date%%' 					=> $r['post_date'],
		'%%title%%'					=> stripslashes($r['post_title']),
		'%%sitename%%'				=> get_bloginfo('name'),
		'%%sitedesc%%'				=> get_bloginfo('description'),
		'%%excerpt%%'				=> wds_get_trimmed_excerpt($r['post_excerpt'], $r['post_content']),
		'%%excerpt_only%%'			=> $r['post_excerpt'],
		'%%category%%'				=> (get_the_category_list('','',$r['ID']) != '') ? get_the_category_list('','',$r['ID']) : $r['name'],
		'%%category_description%%'	=> !empty($r['taxonomy']) ? trim(strip_tags(get_term_field( 'description', $r['term_id'], $r['taxonomy'] ))) : '',
		'%%tag_description%%'		=> !empty($r['taxonomy']) ? trim(strip_tags(get_term_field( 'description', $r['term_id'], $r['taxonomy'] ))) : '',
		'%%term_description%%'		=> !empty($r['taxonomy']) ? trim(strip_tags(get_term_field( 'description', $r['term_id'], $r['taxonomy'] ))) : '',
		'%%term_title%%'			=> $r['name'],
		'%%tag%%'					=> $r['name'],
		'%%modified%%'				=> $r['post_modified'],
		'%%id%%'					=> $r['ID'],
		'%%name%%'					=> get_the_author_meta('display_name', !empty($r['post_author']) ? $r['post_author'] : get_query_var('author')),
		'%%userid%%'				=> !empty($r['post_author']) ? $r['post_author'] : get_query_var('author'),
		'%%searchphrase%%'			=> esc_html(get_query_var('s')),
		'%%currenttime%%'			=> date('H:i'),
		'%%currentdate%%'			=> date('M jS Y'),
		'%%currentmonth%%'			=> date('F'),
		'%%currentyear%%'			=> date('Y'),
		'%%page%%'		 			=> (get_query_var('paged') != 0) ? 'Page ' . get_query_var('paged') . ' of ' . $wp_query->max_num_pages : '',
		'%%spell_page%%'		 	=> (get_query_var('paged') != 0) ? 'Page '. wds_spell_number(get_query_var('paged')) . ' of ' . wds_spell_number($wp_query->max_num_pages) : '',
		'%%pagetotal%%'	 			=> ($wp_query->max_num_pages > 1) ? $wp_query->max_num_pages : '',
		'%%spell_pagetotal%%'	 	=> ($wp_query->max_num_pages > 1) ? wds_spell_number($wp_query->max_num_pages) : '',
		'%%pagenumber%%' 			=> $pagenum,
		'%%spell_pagenumber%%' 		=> wds_spell_number($pagenum),
		'%%caption%%'				=> $r['post_excerpt'],
		'%%bp_group_name%%'			=> $r['name'],
		'%%bp_group_description%%'	=> wds_get_trimmed_excerpt('', $r['description']),
		'%%bp_user_username%%'	=> $r['username'],
		'%%bp_user_full_name%%'	=> $r['full_name'],
	);

	foreach ($replacements as $var => $repl) {
		$repl = apply_filters('wds-macro-variable_replacement', $repl, $var);
		$string = str_replace($var, $repl, $string);
	}

	return $string;
}

/**
 * Returns the number as an anglicized string
 * Adapted from original code by Hugh Bothwell (hugh_bothwell@hotmail.com)
 */
function wds_spell_number ($num) {
	$num = (int) $num;    // make sure it's an integer

	if ($num < 0) return "negative"._wds_hb_convert_tri(-$num, 0);
	if ($num == 0) return "zero";

	return _wds_hb_convert_tri($num, 0);
}
/**
 * Recursive fn, converts three digits per pass
 * Adapted from original code by Hugh Bothwell (hugh_bothwell@hotmail.com)
 */
function _wds_hb_convert_tri ($num, $tri) {
	$ones = array(
		"",
		" one",
		" two",
		" three",
		" four",
		" five",
		" six",
		" seven",
		" eight",
		" nine",
		" ten",
		" eleven",
		" twelve",
		" thirteen",
		" fourteen",
		" fifteen",
		" sixteen",
		" seventeen",
		" eighteen",
		" nineteen"
	);

	$tens = array(
		"",
		"",
		" twenty",
		" thirty",
		" forty",
		" fifty",
		" sixty",
		" seventy",
		" eighty",
		" ninety"
	);

	$triplets = array(
		"",
		" thousand",
		" million",
		" billion",
		" trillion",
		" quadrillion",
		" quintillion",
		" sextillion",
		" septillion",
		" octillion",
		" nonillion"
	);

	// chunk the number, ...rxyy
	$r = (int) ($num / 1000);
	$x = ($num / 100) % 10;
	$y = $num % 100;

	// init the output string
	$str = "";

	// do hundreds
	if ($x > 0) $str = $ones[$x] . " hundred";

	// do ones and tens
	if ($y < 20) $str .= $ones[$y];
	else $str .= $tens[(int) ($y / 10)] . $ones[$y % 10];

	// add triplet modifier only if there
	// is some output to be modified...
	if ($str != "") $str .= $triplets[$tri];

	// continue recursing?
	if ($r > 0) return _wds_hb_convert_tri($r, $tri+1).$str;
	else return $str;
}

function wds_get_trimmed_excerpt ($excerpt, $contents) {
	$string = $excerpt ? $excerpt : $contents;
	$string = trim(preg_replace('/\r|\n/', ' ', strip_shortcodes(htmlspecialchars(wp_strip_all_tags(strip_shortcodes($string)), ENT_QUOTES))));
	return (preg_match('/.{156,}/um', $string))
		? preg_replace('/(.{0,152}).*/um', '$1', $string) . '...'
		: $string
	;
}

function wds_get_term_meta ($term, $taxonomy, $meta) {
	$term = (is_object($term)) ? $term->term_id : get_term_by('slug', $term, $taxonomy);
	$tax_meta = get_option('wds_taxonomy_meta');

	return (isset($tax_meta[$taxonomy][$term][$meta])) ? $tax_meta[$taxonomy][$term][$meta] : false;
}

function wds_hide_blog_public_warning () {
	$wds_options = get_option('wds');
	$wds_options['blog_public_warning'] = 'nolonger';
	update_option('wds', $wds_options);
	echo 'nolonger';
}

function wds_set_option () {
	$option = $_POST['option'];
	$newval = $_POST['newval'];

	return update_option($option, $newval);
}

function save_options_sitewide ($whitelist_options) {
	global $action;

	//if ( is_multisite() && WDS_SITEWIDE == true && 'update' == $action && isset( $_POST['option_page'] ) && in_array( $_POST['option_page'], array( 'wds_settings_options', 'wds_autolinks_options', 'wds_onpage_options', 'wds_sitemaps_options', 'wds_seomoz_options' ) ) ) { // Removed plural
	if ( is_multisite() && WDS_SITEWIDE == true && 'update' == $action && isset( $_POST['option_page'] ) && in_array( $_POST['option_page'], array( 'wds_settings_options', 'wds_autolinks_options', 'wds_onpage_options', 'wds_sitemap_options', 'wds_seomoz_options' ) ) ) { // Added singular
		global $option_page;

		$unregistered = false;
		check_admin_referer( $option_page . '-options' );

		if ( !isset( $whitelist_options[ $option_page ] ) )
			wp_die( __( 'Error: options page not found.' , 'wds') );

		$options = $whitelist_options[ $option_page ];

		if ( $options ) {
			foreach ( $options as $option ) {
				$option = trim($option);
				$value = null;
				if ( isset($_POST[$option]) )
					$value = $_POST[$option];
				if ( !is_array($value) )
					$value = trim($value);
				$value = stripslashes_deep($value);
				update_site_option($option, $value);
			}
		}

		if ( !count( get_settings_errors() ) )
			add_settings_error('general', 'settings_updated', __( 'Settings saved.' , 'wds'), 'updated');
		set_transient( 'settings_errors' , get_settings_errors(), 30 );

		$goback = add_query_arg( 'updated', 'true', wp_get_referer() );
		wp_redirect( $goback );
		die;
	}

	return $whitelist_options;
}
add_filter( 'whitelist_options', 'save_options_sitewide', 20 );

function wds_blog_template_settings ($and) {
	//$and .= " AND `option_name` != 'wds_sitemaps_options'"; // Removed plural
	$and .= " AND `option_name` != 'wds_sitemap_options'"; // Added singular
	return $and;
}
add_filter( 'blog_template_exclude_settings', 'wds_blog_template_settings' );

function wds_is_wizard_step ($step) {
	if (!is_admin()) return false;
	$current_page = isset( $_GET['page'] ) ? $_GET['page'] : '';
	$current_step = isset( $_GET['step'] ) ? $_GET['step'] : WDS_Core_Admin_Tabs::get_first_allowed_step();

	if ( ( 'wds_wizard' == $current_page && $step == $current_step )
		|| ( 'wds_wizard' !== $current_page && '1' == $step )
		|| ( ! empty( $_REQUEST['_wp_http_referer'] ) && strpos( admin_url( "admin.php?page=wds_wizard&step=$step" ), remove_query_arg( 'updated', $_REQUEST['_wp_http_referer'] ) ) )
		|| ( ! empty( $_REQUEST['_wp_http_referer'] ) && strpos( network_admin_url( "admin.php?page=wds_wizard&step=$step" ), remove_query_arg( 'updated', $_REQUEST['_wp_http_referer'] ) ) ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Checks user persmission level against minumum requirement
 * for displaying SEO metabox.
 *
 * @return bool
 */
function user_can_see_seo_metabox () {
	global $wds_options;
	$capability = (defined('WDS_SEO_METABOX_ROLE') && WDS_SEO_METABOX_ROLE)
		? WDS_SEO_METABOX_ROLE
		: (!empty($wds_options['seo_metabox_permission_level']) ? $wds_options['seo_metabox_permission_level'] : false)
	;
	return current_user_can(apply_filters('wds-capabilities-seo_metabox', $capability));
}

/**
 * Checks user persmission level against minumum requirement
 * for displaying Moz urlmetrics metabox.
 *
 * @return bool
 */
function user_can_see_urlmetrics_metabox () {
	global $wds_options;
	$capability = (defined('WDS_URLMETRICS_METABOX_ROLE') && WDS_URLMETRICS_METABOX_ROLE)
		? WDS_URLMETRICS_METABOX_ROLE
		: (!empty($wds_options['urlmetrics_metabox_permission_level']) ? $wds_options['urlmetrics_metabox_permission_level'] : false)
	;
	return current_user_can(apply_filters('wds-capabilities-urlmetrics_metabox', $capability));
}

/**
 * Checks user persmission level against minumum requirement
 * for displaying the 301 redirection field within SEO metabox.
 *
 * @return bool
 */
function user_can_see_seo_metabox_301_redirect () {
	global $wds_options;
	$capability = (defined('WDS_SEO_METABOX_301_ROLE') && WDS_SEO_METABOX_301_ROLE)
		? WDS_SEO_METABOX_301_ROLE
		: (!empty($wds_options['seo_metabox_301_permission_level']) ? $wds_options['seo_metabox_301_permission_level'] : false)
	;
	return current_user_can(apply_filters('wds-capabilities-seo_metabox_301_redirect', $capability));
}

/**
 * Attempt to hide metaboxes by default by adding them to "hidden" array.
 * Metaboxes are still added to "Screen Options".
 * If user chooses to show/hide them, respect her decision.
 *
 * DEPRECATED as of version 1.0.9
 */
function wds_process_default_hidden_meta_boxes ($arg) {
	global $wds_options;
	$arg[] = 'wds-wds-meta-box';
	$arg[] = 'wds_seomoz_urlmetrics';
	return $arg;
}
//add_filter('default_hidden_meta_boxes', 'wds_process_default_hidden_meta_boxes');


/**
 * Hide ALL wds metaboxes.
 * Respect wishes for other metaboxes.
 * Still accessble from "Screen Options".
 */
function wds_hide_metaboxes ($arg) {
	// Hide WP defaults, if nothing else:
	if (empty($arg)) $arg = array('slugdiv', 'trackbacksdiv', 'postcustom', 'postexcerpt', 'commentstatusdiv', 'commentsdiv', 'authordiv', 'revisionsdiv');
	$arg[] = 'wds-wds-meta-box';
	$arg[] = 'wds_seomoz_urlmetrics';
	return $arg;
}
/**
 * Register metabox hiding for other boxes.
 */
function wds_register_metabox_hiding () {
	$post_types = get_post_types();
	foreach ($post_types as $type) add_filter('get_user_option_metaboxhidden_' . $type, 'wds_hide_metaboxes');

}
//add_action('admin_init', 'wds_register_metabox_hiding');

/**
 * Forces metaboxes to start collapsed.
 * It properly merges the WDS boxes with the rest of the users collapsed boxes.
 * For info on registering, see `register_metabox_collapsed_state`.
 */
function force_metabox_collapsed_state ($closed) {
	$closed = is_array($closed) ? $closed : array();
	return array_merge($closed, array(
		'wds-wds-meta-box', 'wds_seomoz_urlmetrics'
	));
}

/**
 * Registers WDS boxes state.
 * Collapsed state is tracked per post type.
 * This is why we have this separate hook to register state change processing.
 */
function register_metabox_collapsed_state () {
	global $post;
	if ($post && $post->post_type) {
		add_filter('get_user_option_closedpostboxes_' . $post->post_type, 'force_metabox_collapsed_state');
	}
}
add_filter('post_edit_form_tag', 'register_metabox_collapsed_state');


/**
 * Checks the page tab slug against permitted ones.
 * This applies only for multisite, non-sitewide setups.
 */
function wds_is_allowed_tab ($slug) {
	$blog_tabs = get_site_option('wds_blog_tabs');
	$blog_tabs = is_array($blog_tabs) ? $blog_tabs : array();
	$allowed = true;
	if (is_multisite() && !WDS_SITEWIDE) {
		$allowed = in_array($slug, $blog_tabs) ? true : false;
	}
	return $allowed;
}

/**
 * Checks if transient is stuck (has no expiry time) and
 * if so, removes it.
 */
function wds_kill_stuck_transient ($key) {
	global $_wp_using_ext_object_cache;
	if ($_wp_using_ext_object_cache) return true; // In object cache, nothing to do

	$key = "_transient_{$key}";
	$alloptions = wp_load_alloptions();
	// If option is in alloptions, it is autoloaded and thus has no timeout - kill it
	if (isset($alloptions[$key])) return delete_option($key);

	return true;
}

/**
 * Check for boolean define switches and their values.
 */
function wds_is_switch_active ($switch) {
	return defined($switch) ? constant($switch) : false;
}

/**
 * Check if we're on main BuddyPress site - BuddyPress root blog check.
 * @return bool Are we on the main BuddyPress site.
 */
function wds_is_main_bp_site () {
	if (is_multisite() && defined('BP_VERSION') && (defined('BP_ROOT_BLOG') && BP_ROOT_BLOG)) {
		global $blog_id;
		return BP_ROOT_BLOG == $blog_id;
	}
	return is_main_site();
}

/**
 * Converts an argument map to HTML attributes string.
 * @param array $args A hash of arguments
 * @return string Constructed attributes string
 */
function wds_autolinks_construct_attributes ($args=array()) {
	$ret = array();
	if (empty($args)) return '';
	foreach ($args as $key => $value) {
		if (empty($key) || empty($value)) continue; // Only accept properly formatted members.
		$ret[] = esc_html($key) . '="' . esc_attr($value) . '"';
	}
	return apply_filters('wds_autolinks_attributes', trim(join(' ', $ret)));
}

function wds_get_mp_global_term_name ($mp_term) {
	if (empty($mp_term)) return '';

	$key = 'mp_term_name-' . preg_replace('/[^-_a-z0-9]i/', '', $mp_term);
	$mp_term_name = wp_cache_get($key, 'wds');
	if (empty($mp_term_name)) {
		global $wpdb;
		$mp_term_name = $wpdb->get_var( $wpdb->prepare("SELECT name FROM {$wpdb->base_prefix}mp_terms WHERE slug = %s", $mp_term) ); // Yanked from MarketPress_MS, no accessor
		wp_cache_set($key, $mp_term_name, 'wds');
	}

	return $mp_term_name;
}