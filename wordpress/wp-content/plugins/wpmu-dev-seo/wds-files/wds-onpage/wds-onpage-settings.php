<?php

/* Add settings page */
function wds_seoopt_settings() {
	$name = 'wds_onpage';
	$title =  __( 'Title & Meta' , 'wds' );
	$description = sprintf( __( '<p>Modify the fields below to customize the titles and meta descriptions of your site pages. <a href="#contextual-help" class="toggle-contextual-help">Click here to see the list of the supported macros.</a></p>
	<p>Search engines read the title and description for each element of your site.  The fields below are set by macros to fill in default information.  You can customize them as you wish and refer to the supported macros, by clicking the Help button.</p>
	<p>It seems to be generally agreed that the "title" and the "description" meta tags are important to write effectively, since several major search engines use them in their indices.   Use relevant keywords in your title, and vary the titles on the different pages that make up your website, in order to target as many keywords as possible.  As for the "description" meta tag, some search engines will use it as their short summary of your url, so make sure your description is one that will entice surfers to your site.</p>
	<p>The "description" meta tag is generally held to be the most valuable, and the most likely to be indexed, so pay special attention to this one.</p>
	<p>Here\'s an example of how it\'s been customized on WPMU DEV.</p>
	<p>The site description (tagline) is:
	<blockquote>WPMU DEV WordPress, Multisite & BuddyPress</blockquote>
	but it has been customized so that the Home Meta Description is WPMU DEV Premium is:
	<blockquote>devoted to plugins, themes, resources and support to assist you in creating the absolute best WordPress MU (WPMU) site you can.</blockquote>
	<p><img src="%s" alt="title and description sample" /></p>
	<p>This plugin also adds a Infinite SEO module below the Write Post / Page editor which you can use to customise SEO options for individual posts and pages.</p>' , 'wds' ), WDS_PLUGIN_URL . 'images/onpagesample.png' );

	$fields = array();
	if ( WDS_SITEWIDE || 'posts' == get_option('show_on_front') ) {
		$fields['home'] = array(
			'title' =>  __( 'Home' , 'wds' ),
			'intro' =>  '',
			'options' => array(
				array(
					'type' => 'text',
					'name' => 'title-home',
					'title' =>  __( 'Home Title' , 'wds' ),
					'description' => ''
				),
				array(
					'type' => 'textarea',
					'name' => 'metadesc-home',
					'title' =>  __( 'Home Meta Description' , 'wds' ),
					'description' => ''
				),
				array(
					'type' => 'text',
					'name' => 'keywords-home',
					'title' =>  __( 'Home page keywords' , 'wds' ),
					'description' => __('Comma-separated keywords, e.g. <code>word1,word2</code>', 'wds'),
				),
				array(
					'type' => 'checkbox',
					'name' => 'meta_robots-main_blog_archive',
					'title' => __( 'Main Blog archive Meta Robots' , 'wds'),
					'items' => array(
						"meta_robots-noindex-main_blog_archive" => __('Noindex', 'wds'),
						"meta_robots-nofollow-main_blog_archive" => __('Nofollow', 'wds'),
						"meta_robots-main_blog_archive-subsequent_pages" => __('Leave the first page alone, but apply to subsequent pages', 'wds'),
					),
				)
			)
		);
	} else {
		$intro = '<p>' . __('You seem to be using a static front page. You can tweak its SEO settings using the Infinite SEO metabox in your Page editor.', 'wds') . '</p>';
		if ((int)get_option('page_for_posts')) {
			$intro .= '<p>' . __('You can do the same for your selected posts page', 'wds') . '</p>';
		}
		$fields['home'] = array(
			'title' => __('Home', 'wds'),
			'intro' => $intro,
			'options' => array(),
		);
	}
	foreach (get_post_types() as $posttype) {
		if (in_array($posttype, array('revision','nav_menu_item'))) continue;
		if (isset($wds_options['redirectattachment']) && $wds_options['redirectattachment'] && $posttype == 'attachment') continue;

		$type_obj = get_post_type_object($posttype);
		if (!is_object($type_obj)) continue;

		$fields[$posttype] = array(
			'title' => $type_obj->labels->name,
			'intro' => '',
			'options' => array(
				array(
					'type' => 'text',
					'name' => 'title-' . $posttype,
					'title' => sprintf(__( '%s Title' , 'wds'), $type_obj->labels->singular_name ),
					'description' => ''
				),
				array(
					'type' => 'textarea',
					'name' => 'metadesc-' . $posttype,
					'title' => sprintf(__( '%s Meta Description' , 'wds'), $type_obj->labels->singular_name),
					'description' => ''
				)
			)
		);
	}

	foreach (get_taxonomies(array('_builtin'=>false),'objects') as $taxonomy) {
		$fields[$taxonomy->name] = array(
			'title' => $taxonomy->label,
			'intro' => '',
			'options' => array(
				array(
					'type' => 'text',
					'name' => 'title-' . $taxonomy->name,
					'title' => sprintf( __( '%s Title' , 'wds'), ucfirst( $taxonomy->label ) ),
					'description' => ''
				),
				array(
					'type' => 'textarea',
					'name' => 'metadesc-' . $taxonomy->name,
					'title' => sprintf( __( '%s Meta Description' , 'wds'), ucfirst( $taxonomy->label ) ),
					'description' => ''
				),
				array(
					'type' => 'checkbox',
					'name' => 'meta_robots-' . $taxonomy->name,
					'title' => sprintf( __( '%s Meta Robots' , 'wds'), ucfirst( $taxonomy->label ) ),
					'items' => array(
						"meta_robots-noindex-{$taxonomy->name}" => __('Noindex', 'wds'),
						"meta_robots-nofollow-{$taxonomy->name}" => __('Nofollow', 'wds'),
						"meta_robots-{$taxonomy->name}-subsequent_pages" => __('Leave the first page alone, but apply to subsequent pages', 'wds'),
					),
				)
			)
		);
	}
	// Adding the builtin ones we need
	$fields['category'] = array(
		'title' => __('Post Categories', 'wds'),
		'intro' => '',
		'options' => array(
			array(
				'type' => 'text',
				'name' => 'title-category',
				'title' => __( 'Category Title' , 'wds'),
				'description' => ''
			),
			array(
				'type' => 'textarea',
				'name' => 'metadesc-category',
				'title' => __( 'Category Meta Description' , 'wds'),
				'description' => ''
			),
			array(
				'type' => 'checkbox',
				'name' => 'meta_robots-category',
				'title' => sprintf(__( 'Category Meta Robots' , 'wds')),
				'items' => array(
					"meta_robots-noindex-category" => __('Noindex', 'wds'),
					"meta_robots-nofollow-category" => __('Nofollow', 'wds'),
					"meta_robots-category-subsequent_pages" => __('Leave the first page alone, but apply to subsequent pages', 'wds'),
				),
			)
		)
	);
	$fields['post_tag'] = array(
		'title' => __('Post Tags', 'wds'),
		'intro' => '',
		'options' => array(
			array(
				'type' => 'text',
				'name' => 'title-post_tag',
				'title' => __( 'Tag Title' , 'wds'),
				'description' => ''
			),
			array(
				'type' => 'textarea',
				'name' => 'metadesc-post_tag',
				'title' => __( 'Tag Meta Description' , 'wds'),
				'description' => ''
			),
			array(
				'type' => 'checkbox',
				'name' => 'meta_robots-post_tag',
				'title' => sprintf(__( 'Tag Meta Robots' , 'wds')),
				'items' => array(
					"meta_robots-noindex-post_tag" => __('Noindex', 'wds'),
					"meta_robots-nofollow-post_tag" => __('Nofollow', 'wds'),
					"meta_robots-post_tag-subsequent_pages" => __('Leave the first page alone, but apply to subsequent pages', 'wds'),
				),
			)
		)
	);

	$fields['author'] = array(
		'title' => __( 'Author Archive' , 'wds'),
		'intro' => '',
		'options' => array(
			array(
				'type' => 'text',
				'name' => 'title-author',
				'title' => __( 'Author Archive Title' , 'wds'),
				'description' => ''
			),
			array(
				'type' => 'textarea',
				'name' => 'metadesc-author',
				'title' => __( 'Author Archive Meta Description' , 'wds'),
				'description' => ''
			),
			array(
				'type' => 'checkbox',
				'name' => 'meta_robots-author',
				'title' => sprintf(__( 'Author Meta Robots' , 'wds')),
				'items' => array(
					"meta_robots-noindex-author" => __('Noindex', 'wds'),
					"meta_robots-nofollow-author" => __('Nofollow', 'wds'),
					"meta_robots-author-subsequent_pages" => __('Leave the first page alone, but apply to subsequent pages', 'wds'),
				),
			)
		)
	);
	$fields['date'] = array(
		'title' => __( 'Date Archives' , 'wds'),
		'intro' => '',
		'options' => array(
			array(
				'type' => 'text',
				'name' => 'title-date',
				'title' => __( 'Date Archives Title' , 'wds'),
				'description' => ''
			),
			array(
				'type' => 'textarea',
				'name' => 'metadesc-date',
				'title' => __( 'Date Archives Description' , 'wds'),
				'description' => ''
			),
			array(
				'type' => 'checkbox',
				'name' => 'meta_robots-date',
				'title' => sprintf(__( 'Date Meta Robots' , 'wds')),
				'items' => array(
					"meta_robots-noindex-date" => __('Noindex', 'wds'),
					"meta_robots-nofollow-date" => __('Nofollow', 'wds'),
					"meta_robots-date-subsequent_pages" => __('Leave the first page alone, but apply to subsequent pages', 'wds'),
				),
			)
		)
	);
	$fields['search'] = array(
		'title' => __( 'Search Page' , 'wds'),
		'intro' => '',
		'options' => array(
			array(
				'type' => 'text',
				'name' => 'title-search',
				'title' => __( 'Search Page Title' , 'wds'),
				'description' => ''
			),
			array(
				'type' => 'textarea',
				'name' => 'metadesc-search',
				'title' => __( 'Search Page Description' , 'wds'),
				'description' => ''
			),
			array(
				'type' => 'checkbox',
				'name' => 'meta_robots-search',
				'title' => __( 'Search results Meta Robots' , 'wds'),
				'items' => array(
					"meta_robots-noindex-search" => __('Noindex', 'wds'),
					"meta_robots-nofollow-search" => __('Nofollow', 'wds'),
				),
			)
		)
	);
	$fields['404'] = array(
		'title' => __( '404 Page' , 'wds'),
		'intro' => '',
		'options' => array(
			array(
				'type' => 'text',
				'name' => 'title-404',
				'title' => __( '404 Page Title' , 'wds'),
				'description' => ''
			),
			array(
				'type' => 'textarea',
				'name' => 'metadesc-404',
				'title' => __( '404 Page Description' , 'wds'),
				'description' => ''
			)
		)
	);
	// BuddyPress groups
	if (function_exists('groups_get_groups') && (is_network_admin() || is_main_site())) {
		$fields['bp_groups'] = array(
			'title' => __( 'BuddyPress Groups' , 'wds'),
			'intro' => '',
			'options' => array(
				array(
					'type' => 'text',
					'name' => 'title-bp_groups',
					'title' => __( 'BuddyPress Group Title' , 'wds'),
					'description' => ''
				),
				array(
					'type' => 'textarea',
					'name' => 'metadesc-bp_groups',
					'title' => __( 'BuddyPress Group Description' , 'wds'),
					'description' => ''
				)
			)
		);
	}
	// BuddyPress profiles
	if (defined('BP_VERSION') && (is_network_admin() || is_main_site())) {
		$fields['bp_profile'] = array(
			'title' => __( 'BuddyPress Profile' , 'wds'),
			'intro' => '',
			'options' => array(
				array(
					'type' => 'text',
					'name' => 'title-bp_profile',
					'title' => __( 'BuddyPress Profile Title' , 'wds'),
					'description' => ''
				),
				array(
					'type' => 'textarea',
					'name' => 'metadesc-bp_profile',
					'title' => __( 'BuddyPress Profile Description' , 'wds'),
					'description' => ''
				)
			)
		);
	}
	// MarketPress global products/taxonomies
	if (class_exists('MarketPress_MS') && (is_network_admin() || is_main_site())) {
		$fields['mp_marketplace'] = array(
			'title' => __( 'MarketPress Marketplace' , 'wds'),
			'intro' => '',
			'options' => array(
			// Base
				array(
					'type' => 'text',
					'name' => 'title-mp_marketplace-base',
					'title' => __( 'Marketplace Base Title' , 'wds'),
					'description' => ''
				),
				array(
					'type' => 'textarea',
					'name' => 'metadesc-mp_marketplace-base',
					'title' => __( 'Marketplace Base Description' , 'wds'),
					'description' => ''
				),
			// Global Categories
				array(
					'type' => 'text',
					'name' => 'title-mp_marketplace-categories',
					'title' => __( 'Marketplace Categories Title' , 'wds'),
					'description' => ''
				),
				array(
					'type' => 'textarea',
					'name' => 'metadesc-mp_marketplace-categories',
					'title' => __( 'Marketplace Categories Description' , 'wds'),
					'description' => ''
				),
				// ...
			/// Global Tags
				array(
					'type' => 'text',
					'name' => 'title-mp_marketplace-tags',
					'title' => __( 'Marketplace Tags Title' , 'wds'),
					'description' => ''
				),
				array(
					'type' => 'textarea',
					'name' => 'metadesc-mp_marketplace-tags',
					'title' => __( 'Marketplace Tags Description' , 'wds'),
					'description' => ''
				),
				// ...
			)
		);
	}

	$contextual_help = __( '
<p>The following macros are supported:</p>
<table class="widefat">
	<thead>
		<tr>
			<th>Tag</th>
			<th>Description</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th>Tag</th>
			<th>Description</th>
		</tr>
	</tfoot>
	<tbody>
		<tr>
			<th>%%date%%</th>
			<td>Replaced with the date of the post/page</td>
		</tr>
		<tr class="alt">
			<th>%%title%%</th>
			<td>Replaced with the title of the post/page</td>
		</tr>
		<tr>
			<th>%%sitename%%</th>
			<td>The site\'s name</td>
		</tr>
		<tr class="alt">
			<th>%%sitedesc%%</th>
			<td>The site\'s tagline / description</td>
		</tr>
		<tr>
			<th>%%excerpt%%</th>
			<td>Replaced with the post/page excerpt (or auto-generated if it does not exist)</td>
		</tr>
		<tr class="alt">
			<th>%%excerpt_only%%</th>
			<td>Replaced with the post/page excerpt (without auto-generation)</td>
		</tr>
		<tr>
			<th>%%tag%%</th>
			<td>Replaced with the current tag/tags</td>
		</tr>
		<tr class="alt">
			<th>%%category%%</th>
			<td>Replaced with the post categories (comma separated)</td>
		</tr>
		<tr>
			<th>%%category_description%%</th>
			<td>Replaced with the category description</td>
		</tr>
		<tr class="alt">
			<th>%%tag_description%%</th>
			<td>Replaced with the tag description</td>
		</tr>
		<tr>
			<th>%%term_description%%</th>
			<td>Replaced with the term description</td>
		</tr>
		<tr class="alt">
			<th>%%term_title%%</th>
			<td>Replaced with the term name</td>
		</tr>
		<tr>
			<th>%%modified%%</th>
			<td>Replaced with the post/page modified time</td>
		</tr>
		<tr class="alt">
			<th>%%id%%</th>
			<td>Replaced with the post/page ID</td>
		</tr>
		<tr>
			<th>%%name%%</th>
			<td>Replaced with the post/page author\'s \'nicename\'</td>
		</tr>
		<tr class="alt">
			<th>%%userid%%</th>
			<td>Replaced with the post/page author\'s userid</td>
		</tr>
		<tr>
			<th>%%searchphrase%%</th>
			<td>Replaced with the current search phrase</td>
		</tr>
		<tr class="alt">
			<th>%%currenttime%%</th>
			<td>Replaced with the current time</td>
		</tr>
		<tr>
			<th>%%currentdate%%</th>
			<td>Replaced with the current date</td>
		</tr>
		<tr class="alt">
			<th>%%currentmonth%%</th>
			<td>Replaced with the current month</td>
		</tr>
		<tr>
			<th>%%currentyear%%</th>
			<td>Replaced with the current year</td>
		</tr>
		<tr class="alt">
			<th>%%page%%</th>
			<td>Replaced with the current page number (i.e. page 2 of 4)</td>
		</tr>
		<tr>
			<th>%%pagetotal%%</th>
			<td>Replaced with the current page total</td>
		</tr>
		<tr class="alt">
			<th>%%pagenumber%%</th>
			<td>Replaced with the current page number</td>
		</tr>
		<tr>
			<th>%%caption%%</th>
			<td>Attachment caption</td>
		</tr>
		<tr class="alt">
			<th>%%spell_pagenumber%%</th>
			<td>Replaced with the current page number, spelled out as numeral in English</td>
		</tr>
		<tr>
			<th>%%spell_pagetotal%%</th>
			<td>Replaced with the current page total, spelled out as numeral in English</td>
		</tr>
		<tr class="alt">
			<th>%%spell_page%%</th>
			<td>Replaced with the current page number, spelled out as numeral in English</td>
		</tr>
	</tbody>
</table>' , 'wds');
/*
	if ( wds_is_wizard_step( '3' ) )
		$settings = new WDS_Core_Admin_Tab( $name, $title, $description, $fields, 'wds', $contextual_help );
*/
		WDS_Core_Admin_Tabs::register('3', $name, $title, $description, $fields, $contextual_help);
}
add_action( 'init', 'wds_seoopt_settings', 999 ); // Ensure we're registered late enough

/* Default settings */
function wds_seoopt_defaults() {
	if( is_multisite() && WDS_SITEWIDE == true ) {
		$onpage_options = get_site_option( 'wds_onpage_options' );
	} else {
		$onpage_options = get_option( 'wds_onpage_options' );
	}

	if ( empty($onpage_options['title-home']) )
		$onpage_options['title-home'] = '%%sitename%%';

	if ( empty($onpage_options['metadesc-home']) )
		$onpage_options['metadesc-home'] = '%%sitedesc%%';

	if ( empty($onpage_options['title-post']) )
		$onpage_options['title-post'] = '%%title%% | %%sitename%%';

	if ( empty($onpage_options['metadesc-post']) )
		$onpage_options['metadesc-post'] = '%%excerpt%%';

	if ( empty($onpage_options['title-page']) )
		$onpage_options['title-page'] = '%%title%% | %%sitename%%';

	if ( empty($onpage_options['metadesc-page']) )
		$onpage_options['metadesc-page'] = '%%excerpt%%';

	if ( empty($onpage_options['title-attachment']) )
		$onpage_options['title-attachment'] = '%%title%% | %%sitename%%';

	if ( empty($onpage_options['metadesc-attachment']) )
		$onpage_options['metadesc-attachment'] = '%%caption%%';

	if ( empty($onpage_options['title-category']) )
		$onpage_options['title-category'] = '%%category%% | %%sitename%%';

	if ( empty($onpage_options['metadesc-category']) )
		$onpage_options['metadesc-category'] = '%%category_description%%';

	if ( empty($onpage_options['title-post_tag']) )
		$onpage_options['title-post_tag'] = '%%tag%% | %%sitename%%';

	if ( empty($onpage_options['metadesc-post_tag']) )
		$onpage_options['metadesc-post_tag'] = '%%tag_description%%';

	if ( empty($onpage_options['title-author']) )
		$onpage_options['title-author'] = '%%name%% | %%sitename%%';

	if ( empty($onpage_options['metadesc-author']) )
		$onpage_options['metadesc-author'] = '';

	if ( empty($onpage_options['title-date']) )
		$onpage_options['title-date'] = '%%currentdate%% | %%sitename%%';

	if ( empty($onpage_options['metadesc-date']) )
		$onpage_options['metadesc-date'] = '';

	if ( empty($onpage_options['title-search']) )
		$onpage_options['title-search'] = '%%searchphrase%% | %%sitename%%';

	if ( empty($onpage_options['metadesc-search']) )
		$onpage_options['metadesc-search'] = '';

	if ( empty($onpage_options['title-404']) )
		$onpage_options['title-404'] = 'Page not found | %%sitename%%';

	if ( empty($onpage_options['metadesc-404']) )
		$onpage_options['metadesc-404'] = '';

	if ( empty($onpage_options['title-bp_groups']) )
		$onpage_options['title-bp_groups'] = '%%bp_group_name%% | %%sitename%%';

	if ( empty($onpage_options['metadesc-bp_groups']) )
		$onpage_options['metadesc-bp_groups'] = '%%bp_group_description%%';

	if ( empty($onpage_options['title-bp_profile']) )
		$onpage_options['title-bp_profile'] = '%%bp_user_username%% | %%sitename%%';

	if ( empty($onpage_options['metadesc-bp_profile']) )
		$onpage_options['metadesc-bp_profile'] = '%%bp_user_full_name%%';

	if( is_multisite() && WDS_SITEWIDE == true ) {
		update_site_option( 'wds_onpage_options', $onpage_options );
	} else {
		update_option( 'wds_onpage_options', $onpage_options );
	}

}
add_action( 'init', 'wds_seoopt_defaults' );