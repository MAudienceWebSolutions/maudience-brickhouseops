<?php
/**
 * WDS_OnPage::wds_title(), WDS_OnPage::wds_head(), WDS_OnPage::wds_metadesc()
 * inspired by WordPress SEO by Joost de Valk (http://yoast.com/wordpress/seo/).
 */


class WDS_OnPage {

	function WDS_OnPage() {
		global $wds_options;

		if (defined('SF_PREFIX') && function_exists('sf_get_option')) {
			add_action('template_redirect', array($this, 'postpone_for_simplepress'), 1);
			return;
		}
		$this->_init();
	}

	function _init () {
		global $wds_options;

		remove_action('wp_head', 'rel_canonical');

		add_action('wp_head', array(&$this, 'wds_head'), 10, 1);
		add_filter('wp_title', array(&$this, 'wds_title'), 10, 3); // wp_title isn't enough. We'll do it anyway: suspenders and belt approach.
		add_action('init', array($this, 'wds_start_title_buffer')); // Buffer the header output and process it instead.

		add_filter('bp_page_title', array(&$this, 'wds_title'), 10, 3); // This should now work with BuddyPress as well.

		add_action('wp',array(&$this,'wds_page_redirect'),99,1);
	}

	/**
	 * Can't fully handle SimplePress installs properly.
	 * For non-forum pages, do our thing all the way.
	 * For forum pages, do nothing.
	 */
	function postpone_for_simplepress () {
		global $wp_query;
		if ((int)sf_get_option('sfpage') != $wp_query->post->ID) {
			$this->_init();
		}
	}

	/**
	 * Starts buffering the header.
	 * The buffer output will be used to replace the title.
	 */
	function wds_start_title_buffer () {
		ob_start(array($this, 'wds_process_title_buffer'));
	}

	/**
	 * Stops buffering the output - the title should now be in the buffer.
	 */
	function wds_stop_title_buffer () {
		if (function_exists('ob_list_handlers')) {
			$active_handlers = ob_list_handlers();
		} else {
			$active_handlers = array();
		}
		if (count($active_handlers) > 0 && preg_match('/::wds_process_title_buffer$/', trim($active_handlers[count($active_handlers) - 1]))) {
			ob_end_flush();
		}
	}

	/**
	 * Handles the title buffer.
	 * Replaces the title with what we get from the old wds_title method.
	 * If we get nothing from it, do nothing.
	 */
	function wds_process_title_buffer ($head) {
		$title_rx = '<title[^>]*?>.*?' . preg_quote('</title>');
		$head_rx = '<head [^>]*? >';
		$head = preg_replace('/\n/', '__WDS_NL__', $head);
		// Dollar signs throw off replacement...
		$title = preg_replace('/\$/', '__WDS_DOLLAR__', $this->wds_title('')); // ... so temporarily escape them, then
		$head = ($title && preg_match("~{$head_rx}~ix", $head)) ? // Make sure we're replacing TITLE that's actually in the HEAD
			preg_replace("~{$title_rx}~i", "<title>{$title}</title>", $head)
			:
			$head
		;
		return preg_replace('/__WDS_NL__/', "\n", preg_replace('/__WDS_DOLLAR__/', '\$', $head));
	}

	function wds_title( $title, $sep = '', $seplocation = '', $postid = '' ) {
		global $post, $wp_query;
		if ( empty($post) && is_singular() ) {
			$post = get_post($postid);
		}

		//global $wds_options;
		$wds_options = get_wds_options();

		if ( is_front_page() && 'posts' == get_option('show_on_front') ) {
			$title = wds_replace_vars($wds_options['title-home'], (array) $post );
		} else if ( is_home() && 'posts' != get_option('show_on_front') ) {
			$post = get_post(get_option('page_for_posts'));
			$fixed_title = wds_get_value('title');
			if ( $fixed_title ) {
				$title = $fixed_title;
			} else if (isset($wds_options['title-'.$post->post_type]) && !empty($wds_options['title-'.$post->post_type]) ) {
				$title = wds_replace_vars($wds_options['title-'.$post->post_type], (array) $post );
			}
		} else if ( is_category() || is_tag() || is_tax() ) {
			$term = $wp_query->get_queried_object();
			$title = wds_get_term_meta( $term, $term->taxonomy, 'wds_title' );
			if ( !$title && isset($wds_options['title-'.$term->taxonomy]) && !empty($wds_options['title-'.$term->taxonomy]) )
				$title = wds_replace_vars($wds_options['title-'.$term->taxonomy], (array) $term );
		} else if ( is_search() && isset($wds_options['title-search']) && !empty($wds_options['title-search']) ) {
			$title = wds_replace_vars($wds_options['title-search'], (array) $wp_query->get_queried_object() );
		} else if ( is_author() ) {
			$author_id = get_query_var('author');
			$title = get_the_author_meta('wds_title', $author_id);
			if ( empty($title) && isset($wds_options['title-author']) && !empty($wds_options['title-author']) ) {
				$title = wds_replace_vars($wds_options['title-author'], array() );
			}
		} else if ( is_archive() && isset($wds_options['title-archive']) && !empty($wds_options['title-archive']) ) {
			$title = wds_replace_vars($wds_options['title-archive'], array('post_title' => $title) );
		} else if ( is_404() && isset($wds_options['title-404']) && !empty($wds_options['title-404']) ) {
			$title = wds_replace_vars($wds_options['title-404'], array('post_title' => $title) );
		} else if (function_exists('groups_get_current_group') && 'groups' == bp_current_component() && $group = groups_get_current_group()) {
			$title = wds_replace_vars($wds_options['title-bp_groups'], array(
				'name' => $group->name,
				'description' => $group->description
			));
		} else if (function_exists('bp_current_component') && 'profile' == bp_current_component()) {
			$title = wds_replace_vars($wds_options['title-bp_profile'], array(
				'full_name' => bp_get_displayed_user_fullname(),
				'username' => bp_get_displayed_user_username(),
			));
		} else if ( is_singular() ) {
			if (class_exists('MarketPress_MS') && 'mp_global_products' == $wp_query->get('pagename')) {
				// Global MarketPress products
				if (!empty($wds_options['title-mp_marketplace-base'])) $title = wds_replace_vars($wds_options['title-mp_marketplace-base']);
			} else if (class_exists('MarketPress_MS') && in_array($wp_query->get('pagename'), array('mp_global_tags', 'mp_global_categories'))) {
				// Global MarketPress Tags / Categories
				$mp_term = $wp_query->get('global_taxonomy');
				$args = array();
				$raw_title = 'mp_global_tags' == $wp_query->get('pagename')
					? $wds_options['title-mp_marketplace-tags']
					: $wds_options['title-mp_marketplace-categories']
				;
				if (!empty($mp_term)) {
					$mp_term_name = wds_get_mp_global_term_name($mp_term);
					if (!empty($mp_term_name)) $args['name'] = $mp_term_name;
				}
				if (!empty($raw_title)) $title = wds_replace_vars($raw_title, $args);
			} else {
				$object = get_queried_object();
				$post_id = !empty($post->ID)
					? $post->ID
					: (!empty($object->ID) ? $object->ID : false)
				;
				$fixed_title = wds_get_value('title', $post_id);
				if ( $fixed_title ) {
					$title = $fixed_title;
				} else if (!empty($post->post_type) && isset($wds_options['title-'.$post->post_type]) && !empty($wds_options['title-'.$post->post_type]) ) {
					$title = wds_replace_vars($wds_options['title-'.$post->post_type], (array) $post );
				}
			} // end singular fallback
		} else if (function_exists('is_shop') && is_shop() && function_exists('woocommerce_get_page_id')) { // WooCommerce shop page
			$post_id = woocommerce_get_page_id('shop');
			$fixed_title = wds_get_value('title', $post_id);
			if ( $fixed_title ) {
				$title = $fixed_title;
			} else if (isset($wds_options['title-'.$post->post_type]) && !empty($wds_options['title-'.$post->post_type]) ) {
				$title = wds_replace_vars($wds_options['title-'.$post->post_type], (array) $post );
			}
		}

		return esc_html( strip_tags( stripslashes( apply_filters('wds_title', $title) ) ) );
	}

	function wds_head() {
		global $wds_options;
		global $wp_query, $paged;

		$this->wds_stop_title_buffer(); // STOP processing the buffer.

		$robots = '';

		$this->wds_canonical();
		$this->wds_rel_links();
		$this->wds_robots();
		$this->wds_metadesc();
		$this->wds_meta_keywords();

		// Verification codes
		if (!empty($wds_options['verification-google'])) {
			if (
				empty($wds_options['verification-pages']) // No specificity
				||
				!empty($wds_options['verification-pages']) && 'home' === $wds_options['verification-pages'] && is_front_page() // Front-page only
			) echo '<meta name="google-site-verification" content="' . esc_attr($wds_options['verification-google']) . '" />' . "\n";
		}
		if (!empty($wds_options['verification-bing'])) {
			if (
				empty($wds_options['verification-pages']) // No specificity
				||
				!empty($wds_options['verification-pages']) && 'home' === $wds_options['verification-pages'] && is_front_page() // Front-page only
			) echo '<meta name="msvalidate.01" content="' . esc_attr($wds_options['verification-bing']) . '" />' . "\n";
		}
	}

	function wds_canonical () {
		global $wds_options;
		global $wp_query, $paged;

		if (
			function_exists('bp_is_blog_page') // If we have BuddyPress ...
			&& // ... and
			!(bp_is_blog_page() || is_404()) // ... we're on a BP page
		) return false;

		if (!apply_filters('wds_process_canonical', true)) return false; // Allow optional filtering out

		// Set decent canonicals for homepage, singulars and taxonomy pages
		if ( wds_get_value('canonical') && wds_get_value('canonical') != '' ) {
			echo "\t".'<link rel="canonical" href="'.wds_get_value('canonical').'" />'."\n";
		} else {
			if (is_singular()) {
				echo "\t";
				rel_canonical();
			} else {
				$canonical = '';
				if (is_front_page()) {
					$canonical = trailingslashit(get_bloginfo('url'));
				} else if (is_tax() || is_tag() || is_category()) {
					$term = $wp_query->get_queried_object();
					$canonical = wds_get_term_meta($term, $term->taxonomy, 'wds_canonical');
					$canonical = $canonical	? $canonical : get_term_link( $term, $term->taxonomy );
				} else if (is_date()) {
					$requested_year = get_query_var('year');
					$requested_month = get_query_var('monthnum');
					$date_callback = !empty($requested_year) && empty($requested_month)
						? 'get_year_link'
						: 'get_month_link'
					;
					$canonical = $date_callback($requested_year, $requested_month);
				}

				//only show id not error object
				if ($canonical && !is_wp_error($canonical)) {
					if ($paged && !is_wp_error($paged))
						$canonical .= trailingslashit('page/'.$paged);

					echo "\t".'<link rel="canonical" href="'.$canonical.'" />'."\n";
				}
			}
		}
	}

	function wds_rel_links () {
		global $wds_options;
		global $wp_query, $paged;

		if (!$wp_query->max_num_pages) return false; // Short out on missing max page number

		if (!apply_filters('wds_process_rel_links', true)) return false; // Allow optional filtering out

		$is_taxonomy = (is_tax() || is_tag() || is_category() || is_date());
		$requested_year = get_query_var('year');
		$requested_month = get_query_var('monthnum');
		$is_date = is_date() && !empty($requested_year);
		$date_callback = !empty($requested_year) && empty($requested_month)
			? 'get_year_link'
			: 'get_month_link'
		;
		$pageable = ($is_taxonomy || (is_home() && 'posts' == get_option('show_on_front')));
		if (!$pageable) return false;

		$term = $wp_query->get_queried_object();
		$canonical = !empty($term->taxonomy) && $is_taxonomy ? wds_get_term_meta( $term, $term->taxonomy, 'wds_canonical' ) : false;
		if (!$canonical) {
			if ((int)$paged > 1) {
				$prev = is_home() ? home_url() : (
					$is_date
						? $date_callback($requested_year, $requested_month)
						: get_term_link($term, $term->taxonomy)
				);
				$prev = ('' == get_option('permalink_structure'))
					? (($paged > 2) ? add_query_arg('page', $paged-1, $prev) : $prev)
					: (($paged > 2) ? trailingslashit($prev) . 'page/' . ($paged-1) : $prev)
				;
				$prev = trailingslashit($prev);
				echo "\t<link rel='prev' href='{$prev}' />\n";
			}
			$is_paged = (int)$paged ? (int)$paged : 1;
			if ($is_paged && $is_paged < $wp_query->max_num_pages) {
				$next = is_home() ? home_url() : (
					$is_date
						? $date_callback($requested_year, $requested_month)
						: get_term_link($term, $term->taxonomy)
				);
				$next_page = $is_paged+1;
				$next = ('' == get_option('permalink_structure'))
					? add_query_arg('page', $next_page, $next)
					: trailingslashit($next) . 'page/' . $next_page
				;
				$next = trailingslashit($next);
				echo "\t<link rel='next' href='{$next}' />\n";
			}
		}
	}

	function wds_robots () {
		global $wds_options;
		global $wp_query, $paged;

		if (!apply_filters('wds_process_robots', true)) return false; // Allow optional filtering out

		$robots = '';
		$term = is_tax() || is_tag() || is_category()
			? $wp_query->get_queried_object()
			: false
		;

		if (is_singular()) {
			$current_comments_page = (int)get_query_var('cpage');
			if ($current_comments_page) {
				$robots = 'noindex,';
			} else {
				$robots = wds_get_value('meta-robots-noindex') ? 'noindex,' : 'index,';
			}
			$robots .= wds_get_value('meta-robots-nofollow') ? 'nofollow' : 'follow';
			if ( wds_get_value('meta-robots-adv') && wds_get_value('meta-robots-adv') != 'none' ) {
				$robots .= ','.wds_get_value('meta-robots-adv');
			}
		} else if (function_exists('is_shop') && is_shop() && function_exists('woocommerce_get_page_id')) { // WooCommerce shop page
			$post_id = woocommerce_get_page_id('shop');
			$robots .= wds_get_value('meta-robots-noindex', $post_id) ? 'noindex,' : 'index,';
			$robots .= wds_get_value('meta-robots-nofollow', $post_id) ? 'nofollow' : 'follow';
			if ( wds_get_value('meta-robots-adv', $post_id) && wds_get_value('meta-robots-adv', $post_id) != 'none' ) {
				$robots .= ','.wds_get_value('meta-robots-adv', $post_id);
			}
		} else if (is_search()) {
			$global_noindex = !empty($wds_options['meta_robots-noindex-search'])
				? 'noindex'
				: 'index'
			;
			$global_nofollow = !empty($wds_options['meta_robots-nofollow-search'])
				? 'nofollow'
				: 'follow'
			;
			$robots = "{$global_noindex},{$global_nofollow}";
		} else if (is_home() && 'posts' == get_option('show_on_front')) {
			$global_noindex = !empty($wds_options['meta_robots-noindex-main_blog_archive'])
				? 'noindex'
				: 'index'
			;
			$global_nofollow = !empty($wds_options['meta_robots-nofollow-main_blog_archive'])
				? 'nofollow'
				: 'follow'
			;
			$robots = (empty($wds_options['meta_robots-main_blog_archive-subsequent_pages'])) || ($paged > 1 && !empty($wds_options['meta_robots-main_blog_archive-subsequent_pages']))
				? "{$global_noindex},{$global_nofollow}"
				: ''
			;
		} else {
			$taxonomy = ($term && is_object($term)) ? $term->taxonomy : false;
			if (!$taxonomy) {
				// Check for faux taxonomies (author, date)
				if (is_author()) $taxonomy = 'author';
				if (is_date()) $taxonomy = 'date';
			}
			if ($taxonomy) {
				$global_noindex = !empty($wds_options['meta_robots-noindex-' . $taxonomy])
					? $wds_options['meta_robots-noindex-' . $taxonomy]
					: false
				;
				$global_nofollow = !empty($wds_options['meta_robots-nofollow-' . $taxonomy])
					? $wds_options['meta_robots-nofollow-' . $taxonomy]
					: false
				;
				if (empty($wds_options["meta_robots-{$taxonomy}-subsequent_pages"]) || (!empty($wds_options["meta_robots-{$taxonomy}-subsequent_pages"]) && $paged > 1)) {
					$nofollow = $noindex = '';

					$noindex = (bool)$global_noindex;
					$noindex = $noindex
						? (isset($term) && is_object($term) ? !(bool)wds_get_term_meta($term, $taxonomy, 'wds_override_noindex') : $noindex)
						: (isset($term) && is_object($term) ? wds_get_term_meta($term, $taxonomy, 'wds_noindex') : false)
					;
					$nofollow = (bool)$global_nofollow;
					$nofollow = $nofollow
						? (isset($term) && is_object($term) ? !(bool)wds_get_term_meta($term, $taxonomy, 'wds_override_nofollow') : $nofollow)
						: (isset($term) && is_object($term) ? wds_get_term_meta($term, $taxonomy, 'wds_nofollow') : false)
					;
					$robots = join(',', array(
						($noindex ? 'noindex' : 'index'),
						($nofollow ? 'nofollow' : 'follow'),
					));
				}
			}
		}

		// Clean up, index, follow is the default and doesn't need to be in output. All other combinations should be.
		if ($robots == 'index,follow')
			$robots = '';
		if (strpos($robots, 'index,follow,') === 0)
			$robots = str_replace('index,follow,','',$robots);

		foreach (array('noodp','noydir','noarchive','nosnippet') as $robot) {
			if (isset($wds_options[$robot]) && $wds_options[$robot]) {
				if (!empty($robots) && substr($robots, -1) != ',')
					$robots .= ',';
				$robots .= $robot;
			}
		}

		$robots = rtrim($robots,',');
		if ($robots != '' && 1 == (int)get_option('blog_public')) {
			echo "\t".'<meta name="robots" content="'.$robots.'"/>'."\n";
		}

	}

	function wds_metadesc() {
		if (is_admin()) return false;
		global $post, $wp_query;
		//global $wds_options;
		$wds_options = get_wds_options();

		if (is_singular()) {
			if (function_exists('groups_get_current_group') && 'groups' == bp_current_component() && $group = groups_get_current_group()) { // BP group?
				$optvar = !empty($wds_options['metadesc-bp_groups']) ? $wds_options['metadesc-bp_groups'] : '';
				$metadesc = wds_replace_vars($optvar, array(
					'name' => $group->name,
					'description' => $group->description
				));
			} else if (function_exists('bp_current_component') && 'profile' == bp_current_component()) {
				$optvar = !empty($wds_options['metadesc-bp_profile']) ? $wds_options['metadesc-bp_profile'] : '';
				$metadesc = wds_replace_vars($optvar, array(
					'full_name' => bp_get_displayed_user_fullname(),
					'username' => bp_get_displayed_user_username(),
				));
			} else if (class_exists('MarketPress_MS') && 'mp_global_products' == $wp_query->get('pagename')) {
				// Global MarketPress products
				if (!empty($wds_options['metadesc-mp_marketplace-base'])) $metadesc = wds_replace_vars($wds_options['metadesc-mp_marketplace-base']);
			} else if (class_exists('MarketPress_MS') && in_array($wp_query->get('pagename'), array('mp_global_tags', 'mp_global_categories'))) {
				// Global MarketPress Tags / Categories
				$mp_term = $wp_query->get('global_taxonomy');
				$args = array();
				$raw_metadesc = 'mp_global_tags' == $wp_query->get('pagename')
					? $wds_options['metadesc-mp_marketplace-tags']
					: $wds_options['metadesc-mp_marketplace-categories']
				;
				if (!empty($mp_term)) {
					$mp_term_name = wds_get_mp_global_term_name($mp_term);
					if (!empty($mp_term_name)) $args['name'] = $mp_term_name;
				}
				if (!empty($raw_metadesc)) $metadesc = wds_replace_vars($raw_metadesc, $args);
			} else {
				$object = get_queried_object();
				$post_id = !empty($post->ID)
					? $post->ID
					: (!empty($object->ID) ? $object->ID : false)
				;
				$metadesc = wds_get_value('metadesc', $post_id);
				if (empty($metadesc)) {
					$optvar = !empty($wds_options['metadesc-'.$post->post_type]) ? $wds_options['metadesc-'.$post->post_type] : '';
					$metadesc = wds_replace_vars($optvar, (array) $post );
				}
			}
		} else if (function_exists('is_shop') && is_shop() && function_exists('woocommerce_get_page_id')) { // WooCommerce shop page
			$post_id = woocommerce_get_page_id('shop');
			$metadesc = wds_get_value('metadesc', $post_id);
			if (empty($metadesc)) {
				$optvar = !empty($wds_options['metadesc-'.$post->post_type]) ? $wds_options['metadesc-'.$post->post_type] : '';
				$metadesc = wds_replace_vars($optvar, (array) $post );
			}
		} else {
			if ( is_home() && 'posts' == get_option('show_on_front') && isset($wds_options['metadesc-home']) ) {
				$metadesc = wds_replace_vars($wds_options['metadesc-home'], array() );
			} else if ( is_home() && 'posts' != get_option('show_on_front') ) {
				$post = get_post( get_option('page_for_posts') );
				$metadesc = wds_get_value('metadesc');
				if ( ($metadesc == '' || !$metadesc) && isset($wds_options['metadesc-'.$post->post_type]) ) {
					$metadesc = wds_replace_vars($wds_options['metadesc-'.$post->post_type], (array) $post );
				}
			} else if ( is_category() || is_tag() || is_tax() ) {
				$term = $wp_query->get_queried_object();

				$metadesc = wds_get_term_meta( $term, $term->taxonomy, 'wds_desc' );
				if ( !$metadesc && isset($wds_options['metadesc-'.$term->taxonomy])) {
					$metadesc = wds_replace_vars($wds_options['metadesc-'.$term->taxonomy], (array) $term );
				}
			} else if ( is_author() ) {
				$author_id = get_query_var('author');
				$metadesc = get_the_author_meta('wds_metadesc', $author_id);
			} else if (function_exists('groups_get_current_group') && 'groups' == bp_current_component() && $group = groups_get_current_group()) { // BP group?
				$optvar = !empty($wds_options['metadesc-bp_groups']) ? $wds_options['metadesc-bp_groups'] : '';
				$metadesc = wds_replace_vars($optvar, array(
					'name' => $group->name,
					'description' => $group->description
				));
			} else if (function_exists('bp_current_component') && 'profile' == bp_current_component()) {
				$optvar = !empty($wds_options['metadesc-bp_profile']) ? $wds_options['metadesc-bp_profile'] : '';
				$metadesc = wds_replace_vars($optvar, array(
					'full_name' => bp_get_displayed_user_fullname(),
					'username' => bp_get_displayed_user_username(),
				));
			}
		}

		if (!empty($metadesc)) {
			echo "\t".'<meta name="description" content="'. esc_attr( strip_tags( stripslashes( apply_filters('wds_metadesc', $metadesc) ) ) ).'" />'."\n";
		}
	}

	/**
	* Output meta keywords, if any.
	*/
	function wds_meta_keywords () {
		if (is_admin()) return;

		if (!apply_filters('wds_process_keywords', true)) return false; // Allow optional filtering out

		global $post;
		global $wds_options;
		$metakey = false;
		if (is_home() && 'posts' == get_option('show_on_front') && isset($wds_options['keywords-home'])) {
			$metakey = wds_replace_vars($wds_options['keywords-home'], (array) $post );
		} else if (function_exists('is_shop') && is_shop() && function_exists('woocommerce_get_page_id')) { // WooCommerce shop page
			$post_id = woocommerce_get_page_id('shop');
			$metakey = wds_get_value('keywords', $post_id);
			$use_tags = wds_get_value('tags_to_keywords', $post_id);
			$metakey = $use_tags ? $this->_tags_to_keywords($metakey) : $metakey;
		} else {
			$metakey = is_singular() ? wds_get_value('keywords') : false;
			$use_tags = is_singular() ? wds_get_value('tags_to_keywords') : false;
			$metakey = $use_tags ? $this->_tags_to_keywords($metakey) : $metakey;
		}
		if ($metakey) echo "\t".'<meta name="keywords" content="'. esc_attr(stripslashes($metakey)).'" />'."\n";

		// News keywords
		$news_meta = is_singular() ? stripslashes(wds_get_value('news_keywords')) : false;
		$news_meta = trim(preg_replace('/\s\s+/', ' ', preg_replace('/[^-_,a-z0-9 ]/i', ' ', $news_meta)));
		if ($news_meta) echo "\t".'<meta name="news_keywords" content="'. esc_attr($news_meta).'" />'."\n";
	}

	/**
	 * Merges keywords (if any) and tags (if any) into one keyword string.
	 * Returned string is checked for duplicates.
	 *
	 * @access private
	 * @return mixed Keyword string if we found anything, false otherwise.
	 */
	function _tags_to_keywords ($kws) {
		$kw_array = $kws ? explode(',', trim($kws)) : array();
		$kw_array = is_array($kw_array) ? $kw_array : array();
		$kw_array = array_map('trim', $kw_array);

		$tags = array();
		$raw_tags = get_the_tags();
		if ($raw_tags) foreach($raw_tags as $tag) {
			$tags[] = $tag->name;
		}
		$result = array_filter(array_unique(array_merge($kw_array, $tags)));
		return count($result) ? join(',', $result) : false;
	}

	function wds_page_redirect( $input ) {
		global $post;

		if (!apply_filters('wds_process_redirect', true)) return false; // Allow optional filtering out

		if ($post && $redir = wds_get_value('redirect', $post->ID)) {
			wp_redirect( $redir, 301 );
			exit;
		}
	}
}

$wds_onpage = new WDS_OnPage;