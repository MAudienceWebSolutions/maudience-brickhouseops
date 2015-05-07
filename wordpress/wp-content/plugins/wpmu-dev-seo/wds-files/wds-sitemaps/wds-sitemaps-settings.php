<?php

/**
 * BuddyPress settings fields helper.
 */
function _wds_get_buddypress_fields () {
	// BuddyPress Groups
	if (function_exists('groups_get_groups')) { // We have BuddyPress groups, so let's get some settings
		$opts = array(
			'title' => __('BuddyPress', 'wds'),
			'intro' => __('BuddyPress sitemaps integration.', 'wds'),
			'options' => array(
				array(
					'type' => 'radio',
					'name' => 'sitemap-buddypress-groups',
					'title' => __('Include BuddyPress groups in my sitemaps', 'wds'),
					'description' => __('Enabling this option will add all your BuddyPress groups to your sitemap.', 'wds'),
					'items' => array(
						__('No', 'wds'), __('Yes', 'wds')
					),
				),
			),
		);
		$groups = groups_get_groups(array('per_page' => WDS_BP_GROUPS_LIMIT));
		$groups = @$groups['groups'] ? $groups['groups'] : array();
		$exclude = array();
		foreach ($groups as $group) {
			$exclude["exclude-buddypress-group-{$group->slug}"] = $group->name;
		}
		if ($exclude) {
			$opts['options'][] = array (
				'type' => 'checkbox',
				'name' => 'sitemap-buddypress',
				'title' => __( 'Exclude these groups from my sitemap' , 'wds'),
				'items' => $exclude,
			);
		}
	}

	// BuddyPress profiles
	$opts['options'][] = array (
		'type' => 'radio',
		'name' => 'sitemap-buddypress-profiles',
		'title' => __('Include BuddyPress profiles in my sitemaps', 'wds'),
		'description' => __('Enabling this option will add all your BuddyPress profiles to your sitemap.', 'wds'),
		'items' => array(
			__('No', 'wds'), __('Yes', 'wds')
		),
	);
	$wp_roles = new WP_Roles();
	$wp_roles = $wp_roles->get_names();
	$wp_roles = $wp_roles ? $wp_roles : array();
	$exclude = array();
	foreach ($wp_roles as $key=>$label) {
		$exclude["exclude-profile-role-{$key}"] = $label;
	}
	if ($exclude) {
		$opts['options'][] = array (
			'type' => 'checkbox',
			'name' => 'sitemap-buddypress-roles',
			'title' => __( 'Exclude profiles with these roles from my sitemap' , 'wds'),
			'items' => $exclude,
		);
	}


	return $opts;
}

/* Add settings page */
function wds_sitemaps_settings() {
	//$name = 'wds_sitemaps'; // Removed plural
	global $wds_options;

	$name = 'wds_sitemap'; // Added singular
	$title = __( 'Sitemaps' , 'wds');
	$description = __( '<p>Here we will help you create a site map which are used to help search engines find all of the information on your site.</p>
	<p>This is one of the basics of SEO. A sitemap helps search engines like Google, Bing and Yahoo to better index your blog. Search engines are better able to crawl through your site with a structured sitemap of where your content leads. This plugin supports all kinds of WordPress-generated pages as well as custom URLs. Whenever you create a new post, it will notify major search engines to come crawl your new content.</p>
	<p>You may also choose to not include posts, pages, custom post types, categories, or tags from your sitemap - but most situations you will want to leave these in.</p>
	<p>(Leaving these off of a sitemap won\'t guarantee that a search engine won\'t find the information by other means!)</p>', 'wds' );

	$sitemap_options = get_option( 'wds_sitemap_options' );

	$fields = array();
	$fields['sitemap'] = array(
		'title' => __( 'XML Sitemap' , 'wds'),
		'intro' => '',
		'options' => array(
			array(
				'type' => 'text',
				'class' => 'widefat',
				'name' => 'sitemappath',
				'title' => __( 'Path to the XML Sitemap' , 'wds'),
				'description' => '',
				'text' => '<p><code>' . $sitemap_options['sitemappath'] . '</code></p>'
			),
			array(
				'type' => 'content',
				'name' => 'sitemapurl',
				'title' => __( 'URL to the XML Sitemap' , 'wds'),
				'description' => '',
				'text' => '<p><a href="' . $sitemap_options['sitemapurl'] . '" target="_blank">' . $sitemap_options['sitemapurl'] . '</a></p>' // Removed plain content type
			)
		)
	);

	foreach (get_post_types(array(
			'public' => true,
			'show_ui' => true,
		)) as $post_type) {
		if ( !in_array( $post_type, array('revision', 'nav_menu_item', 'attachment') ) ) {
			$pt = get_post_type_object($post_type);
			$post_types['post_types-' . $post_type . '-not_in_sitemap'] = $pt->labels->name;
		}
	}
	foreach (get_taxonomies(array(
			'public' => true,
			'show_ui' => true,
		)) as $taxonomy) {
		if ( !in_array( $taxonomy, array( 'nav_menu', 'link_category', 'post_format' ) ) ) {
			$tax = get_taxonomy($taxonomy);
			$taxonomies['taxonomies-' . $taxonomy . '-not_in_sitemap'] = $tax->labels->name;
		}
	}
	$fields['exclude'] = array(
		'title' => __('Exclude' , 'wds'),
		'intro' => '',
		'options' => array(
			array(
				'type' => 'checkbox',
				'name' => 'exclude_post_types',
				'title' => __( 'Exclude post types' , 'wds'),
				'items' => $post_types
			),
			array(
				'type' => 'checkbox',
				'name' => 'exclude_taxonomies',
				'title' => __( 'Exclude taxonomies' , 'wds'),
				'items' => $taxonomies
			)
		)
	);
	if (defined('BP_VERSION')) {
		$fields['buddypress'] = _wds_get_buddypress_fields();
	}
	$fields['options'] = array(
		'title' => __('Options', 'wds'),
		'intro' => __('Miscellaneous Sitemap related options.', 'wds'),
		'options' => array(
			array(
				'type' => 'radio',
				'name' => 'sitemap-images',
				'title' => __('Include image items with the sitemap', 'wds'),
				'description' => __('Enabling this option will considerably increase plugin memory consumption.', 'wds'),
				'items' => array(
					__('No', 'wds'), __('Yes', 'wds')
				),
			),
			array(
				'type' => 'radio',
				'name' => 'sitemap-stylesheet',
				'title' => __('Include stylesheet with the generated sitemap', 'wds'),
				'description' => __('Stylesheet does not affect your sitemap functionality in any way.', 'wds'),
				'items' => array(
					__('No', 'wds'), __('Yes', 'wds')
				),
			),
			array(
				'type' => 'radio',
				'name' => 'sitemap-dashboard-widget',
				'title' => __('Show dashboard widget', 'wds'),
				'description' => __('Enabling this option will add an Admin Dashboard widget that displays your sitemap information.', 'wds'),
				'items' => array(
					__('No', 'wds'), __('Yes', 'wds')
				),
			),
			array(
				'type' => 'radio',
				'name' => 'sitemap-disable-automatic-regeneration',
				'title' => __('Disable automatic sitemap updates', 'wds'),
				'description' => __('Enable this option if you wish to update your sitemaps manually (by using the Dashboard widget or visiting this page) only.', 'wds'),
				'items' => array(
					__('No', 'wds'), __('Yes', 'wds')
				),
			),
		)
	);
	$google_msg = @$wds_options['verification-google'] ? '<code>' . esc_html('<meta name="google-site-verification" value="') . esc_attr(@$wds_options['verification-google']) . esc_html('" />') . '</code>' : '<small>' . __('No META tag will be added', 'wds') . '</small>';
	$bing_msg = @$wds_options['verification-bing'] ? '<code>' . esc_html('<meta name="msvalidate.01" value="') . esc_attr(@$wds_options['verification-bing']) . esc_html('" />') . '</code>' : '<small>' . __('No META tag will be added', 'wds') . '</small>';
	$fields['search-engines'] = array(
		'title' => __('Search engines', 'wds'),
		'intro' => __('Options related to direct interaction with search engines.', 'wds'),
		'options' => array(
			array(
				'type' => 'text',
				'class' => 'widefat',
				'name' => 'verification-google',
				'title' => __( 'Google site verification code' , 'wds'),
				'description' => "<p>{$google_msg}</p>",
			),
			array(
				'type' => 'text',
				'class' => 'widefat',
				'name' => 'verification-bing',
				'title' => __( 'Bing site verification code' , 'wds'),
				'description' => "<p>{$bing_msg}</p>",
			),
			array(
				'type' => 'radio',
				'name' => 'verification-pages',
				'title' => __('Add verification code to:', 'wds'),
				'items' => array(
					'' => __('All pages', 'wds'),
					'home' => __('Home page', 'wds'),
				),
			),
			array(
				'type' => 'checkbox',
				'name' => 'engines',
				'title' => __('Automatically notify search engines when my sitemap updates' , 'wds'),
				'items' => array(
					'ping-google' => __('Google', 'wds'),
					'ping-bing' => __('Bing', 'wds'),
				),
			),
		)
	);

	$contextual_help = '';
/*
	if ( wds_is_wizard_step( '2' ) )
		$settings = new WDS_Core_Admin_Tab( $name, $title, $description, $fields, 'wds', $contextual_help );
*/
	if (class_exists('WDS_Core_Admin_Tabs')) WDS_Core_Admin_Tabs::register('2', $name, $title, $description, $fields, $contextual_help);

	require_once ( WDS_PLUGIN_DIR . 'wds-sitemaps/wds-sitemaps.php' );
}
add_action( 'init', 'wds_sitemaps_settings', 999 );

/* Default settings */
function wds_sitemaps_defaults() {
	$sitemap_options = get_option( 'wds_sitemap_options' );

	$dir = wp_upload_dir();
	$path = trailingslashit( $dir['basedir'] );

	if ( empty($sitemap_options['sitemappath']) )
		$sitemap_options['sitemappath'] = $path . 'sitemap.xml';

	if ( empty($sitemap_options['sitemapurl']) )
		$sitemap_options['sitemapurl'] = get_bloginfo( 'url' ) . '/sitemap.xml';

	if ( empty($sitemap_options['newssitemappath']) )
		$sitemap_options['newssitemappath'] = $path . 'news_sitemap.xml';

	if ( empty($sitemap_options['newssitemapurl']) )
		$sitemap_options['newssitemapurl'] = get_bloginfo( 'url' ) . '/news_sitemap.xml';

	if ( empty($sitemap_options['enablexmlsitemap']) )
		$sitemap_options['enablexmlsitemap'] = 1;

	update_option( 'wds_sitemap_options', $sitemap_options );
	/*
	if( is_multisite() && WDS_SITEWIDE == true ) {
		update_site_option( 'wds_sitemap_options', $sitemap_options );
	} else {
		update_option( 'wds_sitemap_options', $sitemap_options );
	}
	*/
}
add_action( 'init', 'wds_sitemaps_defaults', 999 );