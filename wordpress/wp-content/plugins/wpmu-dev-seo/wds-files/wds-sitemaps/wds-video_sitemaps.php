<?php

class WDS_XML_VideoSitemap {

	const VIDEO_SITEMAP_LIMIT = 50000;
	const FALLBACK_VIDEO_THUMBNAIL = '';

	private $_provider_regex = array();
	private $_no_video_oembed = array(
		'polldaddy',
		'smugmug',
		'flickr',
		'scribd',
		'photobucket',
		'twitter',
	);

	private $_items = array();

	private function __construct () {
		if (!is_admin()) $this->_provider_regex = $this->_get_provider_regexen();
	}

	public static function serve () {
		$me = new WDS_XML_VideoSitemap;
		$me->_add_hooks();

		add_action('save_post', array($me, 'clean_posts_cache'));
		add_action('deleted_post', array($me, 'clean_posts_cache'));
		add_action('wp_trash_post', array($me, 'clean_posts_cache'));
	}

	function clean_posts_cache( $post_id ) {
		wp_cache_delete( "wds-select-items-list", 'wds-video-sitemaps' );
	}

	private function _add_hooks () {
		// ... hooks

		if (is_admin()) return false;
		if (preg_match('~' . preg_quote('/video-sitemap.xml') . '(\.gz)?$~i', $_SERVER['REQUEST_URI'])) {
			$this->update_items_list();
			$this->serve_video_sitemap();
		}
	}

	function serve_video_sitemap () {
		if (!$this->_items) return false;
		$map = $this->_prepare_sitemap();
		if (!$map) return false;
		header("Content-type: text/xml");
		echo $map;
		die;
	}

	private function _prepare_sitemap () {
		if (!$this->_items) return false;
		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";

		foreach ($this->_items as $loc => $item) {
			$xml .= '<url>';
			$xml .= "<loc>{$loc}</loc>";

			$keys = array_keys($item);
			$xml .= '<video>';
			foreach ($keys as $key) {
				$value = htmlspecialchars($item[$key]);
				$xml .= "<video:{$key}>{$value}</video:{$key}>\n";
			}
			$xml .= '</video>';

			$xml .= '</url>';
		}

		$xml .= '</urlset>';
		return $xml;
	}

	function update_items_list () {
		global $wpdb;

		$posts = wp_cache_get( "wds-select-items-list", 'wds-video-sitemaps' );
		if ( ! $posts ) {
			$likes = join("' OR post_content REGEXP '", $this->_provider_regex);
			$limit = self::VIDEO_SITEMAP_LIMIT;
			$sql = "SELECT * FROM {$wpdb->posts} WHERE post_status='publish' AND (post_content REGEXP '{$likes}') LIMIT {$limit}";
			$posts = $wpdb->get_results($sql);
		}
		wp_cache_add( "wds-select-items-list", $posts, 'wds-video-sitemaps', 86400 );

		foreach ($posts as $post) {
			// Exclude by post types
			// ...

			// Exclude by taxonomies
			// ...

			$this->_add_video_item($post);
		}
	}

	private function _add_video_item ($raw) {
		$player = $this->_extract_player_loc($raw->ID, $raw->post_content);
		if (!$player) return false;

		$image = apply_filters('wds-video_sitemaps-thumbnail_url-default', '');
		if (!$image) {
			$image = $this->_extract_thumbnail_from_player_src($raw->ID, $player);
			$image = apply_filters('wds-video_sitemaps-thumbnail_url', $image, $raw->ID, $player);
			$image = $image ? $image : apply_filters('wds-video_sitemaps-thumbnail_url-fallback', $image);
		}
		if (!$image) return false; // No thumbnail image, we can't add this itemc

		$loc = get_permalink($raw->ID);
		$this->_items[$loc] = array(
			'title' => $raw->post_title,
			'description' => substr(wp_strip_all_tags($raw->post_content), 0, 12),
			'thumbnail_loc' => $image,
			'player_loc' => $player,
			'publication_date' => mysql2date('Y-m-d', $raw->post_date),
		);
	}

	private function _extract_thumbnail_from_player_src ($post_id, $src) {
		$host = parse_url($src, PHP_URL_HOST);
		$path = parse_url($src, PHP_URL_PATH);

		if (preg_match('/youtu\.?be/', $host)) {
		// YouTube
			$video_id = end(explode('/', $path));
			return $video_id
				? "http://img.youtube.com/vi/{$video_id}/hqdefault.jpg"
				: ''
			;
		} else if (preg_match('/vimeo/', $host)) {
		// Vimeo
			// Vimeo requires us to to an API call, so...

			// First find out video ID and check cache.
			$video_id = end(explode('/', $path));
			$thumbnail = get_post_meta($post_id, '_vimeo_thumbnail_id-' . $video_id, true);
			if ($thumbnail) return $thumbnail;

			// Next, check if we're to do this
			if (!(defined('WDS_VIDEO_SITEMAP_ALLOW_API_CALLS') && WDS_VIDEO_SITEMAP_ALLOW_API_CALLS)) return false;

			// No cache - fetch from remote API and update cache for next time
			$response = wp_remote_get("http://vimeo.com/api/v2/video/{$video_id}.php");
			if (200 != wp_remote_retrieve_response_code($response)) return false;
			$body = unserialize(wp_remote_retrieve_body($response));
			if (!empty($body[0]['thumbnail_medium'])) {
				$thumbnail = $body[0]['thumbnail_medium'];
				update_post_meta($post_id, '_vimeo_thumbnail_id-' . $video_id, $thumbnail);
			}
			return $thumbnail;
		} else if (preg_match('/blip\.tv/', $host)) {
		// Blip.tv

			$video_id = end(explode('/', $path));
			$thumbnail = get_post_meta($post_id, '_blip_thumbnail_id-' . $video_id, true);
			if ($thumbnail) return $thumbnail;

			// Blip.tv - same deal as Vimeo, remote call is needed
			if (!(defined('WDS_VIDEO_SITEMAP_ALLOW_API_CALLS') && WDS_VIDEO_SITEMAP_ALLOW_API_CALLS)) return false;

			// No cache - fetch from remote API and update cache for next time
			$response = wp_remote_get("http://blip.tv/players/episode/{$video_id}?skin=rss");
			if (200 != wp_remote_retrieve_response_code($response)) return false;
			$body = wp_remote_retrieve_body($response);
			preg_match('/<blip:picture>(.*)<\/blip:picture>/i', $body, $matches);
			if (!empty($matches[1])) {
				$thumbnail = $matches[1];
				update_post_meta($post_id, '_blip_thumbnail_id-' . $video_id, $thumbnail);
			}
			return $thumbnail;
		} else if (preg_match('/wordpress\./', $host)) {
		// WordPress.tv

			$video_id = end(explode('/', $path));
			$thumbnail = get_post_meta($post_id, '_wordpresstv_thumbnail_id-' . $video_id, true);
			if ($thumbnail) return $thumbnail;

			// Dispatch an oEmbed call, if allowed and needed
			if (!(defined('WDS_VIDEO_SITEMAP_ALLOW_API_CALLS') && WDS_VIDEO_SITEMAP_ALLOW_API_CALLS)) return false;

			$post = get_post($post_id);
			$src = preg_match('#(http://wordpress.tv/[-_/.a-z0-9]+)#i', $post->post_content, $matches);
			if (empty($matches[1])) return false;

			$response = wp_remote_get("http://public-api.wordpress.com/oembed/1.0/?format=json&url=" . urlencode($matches[1]) . '&for=' . urlencode(site_url()));
			if (200 != wp_remote_retrieve_response_code($response)) return false;
			$body = json_decode(wp_remote_retrieve_body($response), true);
			if (!$body) return false;

			if (!empty($body['thumbnail_url'])) {
				$thumbnail = $body['thumbnail_url'];
				update_post_meta($post_id, '_wordpresstv_thumbnail_id-' . $video_id, $thumbnail);
			}
			return $thumbnail;
		}

		// Default
		return apply_filters('wds-video_sitemaps-thumbnail_url-' . $host, '', $src, $post_id);
	}

	private function _extract_player_loc ($post_id, $body) {
		global $wpdb;
		$post_id = (int)$post_id;
		$markup = $src = false;

		// We get all the post meta
		$all_post_meta = get_post_meta( $post_id );

		// Now iterate through the meta and see if we can find an _oembed* element
		while ( ( list( $key, $value ) = each( $all_post_meta ) ) && $markup === false ) {
			if ( preg_match( '/^_oembed.*/', $key ) ) {
				// We have found an oembed meta, let's save it
				$markup = $all_post_meta[ $key ];
			}
		}

		if ($markup) {
			$matches = array();
			preg_match('/src=[\'"](.*?)[\'"]/', $markup, $matches);
			if (empty($matches[1])) return apply_filters('wds-video_sitemaps-player_loc', false, $post_id, $body);
			$src = $matches[1];
		} else  {
		// No eombed video found, heuristics should kick in
			foreach ($this->_provider_regex as $rx) {
				$matches = array();
				if (!preg_match('#(' . $rx . '?)[\'"]#i', $body, $matches)) continue;
				$src = substr($matches[0], 0, -1);
				break;
			}
		}
		return apply_filters('wds-video_sitemaps-player_loc', $src, $post_id, $body);
	}

	private function _get_provider_regexen () {
		$oembed = $this->_get_oembed_providers();
		$static = $this->_get_static_providers();
		$supplements = $this->_get_supplement_providers();
		return array_merge(
			$oembed,
			$static,
			$supplements
		);
	}

	private function _get_supplement_providers () {
		return array(
			'http://(www\\.)?youtube.com/.*', // For IFRAME embeds
		);
	}

	/**
	 * Static list - not used yet.
	 */
	private function _get_static_providers () {
		return array(
			'http://(www\\.)?youtube.com/watch.*',
			'http://youtu.be/.*',
			'http://blip.tv/.*',
			'http://(.*\\.)?vimeo\\.com/.*',
			'http://(www\\.)?dailymotion\\.com/.*',
			'http://(www\\.)?hulu\\.com/watch/.*',
			'http://(www\\.)?viddler\\.com/.*',
			'http://qik.com/.*',
			'http://revision3.com/.*',
			'http://wordpress.tv/.*',
			'http://(www\\.)?funnyordie\\.com/videos/.*',
		);
	}

	private function _get_oembed_providers () {
		if (!class_exists('WP_oEmbed')) include ABSPATH . WPINC . '/class-oembed.php'; // Short out if not available
		$embed = new WP_oEmbed;
		$providers = array();
		foreach ($embed->providers as $rx => $provider) {
			if (empty($provider[0])) continue;
			if (!$this->filter_non_video_provider_callback($provider[0])) continue;
			if (!empty($provider[1])) {
				// Kill front delimiter
				$rx = substr($rx, 1);
				// Kill end delimiter and possibly the "i" modifier
				$end = ('i' == substr($rx, -1)) ? 2 : 1;
				$rx = substr($rx, 0, strlen($rx) - $end);
			}
			$providers[] = $rx;
		}
		return $providers;
	}

	function filter_non_video_provider_callback ($provider) {
		foreach ($this->_no_video_oembed as $skip) {
			if (preg_match('/' . preg_quote($skip, '/') . '/i', $provider)) return false;
		}
		return true;
	}

}
add_action('init', array('WDS_XML_VideoSitemap', 'serve'));

if (!defined('WDS_VIDEO_SITEMAP_ALLOW_API_CALLS')) define('WDS_VIDEO_SITEMAP_ALLOW_API_CALLS', true);