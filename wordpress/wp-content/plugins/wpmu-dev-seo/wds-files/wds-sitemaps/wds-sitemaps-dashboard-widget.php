<?php
function wds_sitemaps_dashboard_widget () {
	$sitemap = get_option('wds_sitemap_options');
	$opts = get_option('wds_sitemap_dashboard');
	$engines = get_option('wds_engine_notification');

	$date = @$opts['time'] ? date(get_option('date_format'), $opts['time']) : false;
	$time = @$opts['time'] ? date(get_option('time_format'), $opts['time']) : false;

	$datetime = ($date && $time) ? sprintf(__('It was last updated on %s, at %s.', 'wds'), $date, $time) : __("Your sitemap hasn't been updated recently.", 'wds');
	$update_sitemap = __('Update sitemap now', 'wds');
	$update_engines = __('Force search engines notification', 'wds');
	$working = __('Updating...', 'wds');

	$sitemap_url = wds_get_sitemap_url();

	echo "<div style='width:45%;float:left'>";
		echo '<div>' . sprintf(__('Your sitemap contains <a href="%s" target="_blank"><b>%d</b> items</a>.', 'wds'), /*$sitemap['sitemapurl']*/$sitemap_url, (int)@$opts['items']) . '</div>';
		echo "<br />{$datetime}";
		echo "<p><a href='#update_sitemap' id='wds_update_now'>{$update_sitemap}</a></p>";
	echo "</div>";
	echo "<div style='width:45%;float:right'>";
	if ($engines) {
		echo "<ul>";
		foreach ($engines as $key => $engine) {
			$service = ucfirst($key);
			$edate = @$engine['time'] ? date(get_option('date_format'), $engine['time']) : false;
			$etime = @$engine['time'] ? date(get_option('time_format'), $engine['time']) : false;
			$edatetime = ($edate && $etime) ? sprintf(__('Last notified on %s, at %s.', 'wds'), $date, $time) : __("Not notified", 'wds');
			echo "<li><b>{$service}:</b> {$edatetime}</li>";
		}
		echo "</ul>";
	} else _e("<div>Search engines haven't been recently updated</div>", 'wds');
	echo "<p><a href='#update_search_engines' id='wds_update_engines'>{$update_engines}</a></p>";
	echo "</div>";
	echo "<div style='clear:both'></div>";
	echo <<<EOSitemapsWidgetJs
<script type="text/javascript">
(function ($) {
$(function () {

$("#wds_update_now").click(function () {
	var me = $(this);
	me.html("{$working}");
	$.post(ajaxurl, {"action": "wds_update_sitemap"}, function () {
		me.html("{$update_sitemap}");
		window.location.reload();
	});
	return false;
});
$("#wds_update_engines").click(function () {
	var me = $(this);
	me.html("{$working}");
	$.post(ajaxurl, {"action": "wds_update_engines"}, function () {
		me.html("{$update_engines}");
		window.location.reload();
	});
	return false;
});

});
})(jQuery);
</script>
EOSitemapsWidgetJs;
}

function wds_add_sitemaps_dashboard_widget () {
	if (!current_user_can('edit_posts')) return false;
	wp_add_dashboard_widget('wds_sitemaps_dashboard_widget', __('Sitemaps', 'wds'), 'wds_sitemaps_dashboard_widget');
}
add_action('wp_dashboard_setup', 'wds_add_sitemaps_dashboard_widget' );