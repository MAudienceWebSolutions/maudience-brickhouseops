(function ($) {
$(function () {

var __WDS_TITLE_COUNT = l10nWdsCounters.title_limit,
	__WDS_META_COUNT = l10nWdsCounters.metad_limit
;

function _replace (what, current) {
	return l10nWdsCounters[what]
		.replace(/\{MAX_COUNT\}/, __WDS_TITLE_COUNT)
		.replace(/\{CURRENT_COUNT\}/, current)
		.replace(/\{TOTAL_LEFT\}/, (__WDS_TITLE_COUNT - current))
	;
}

function checkTitleLength () {
	var txt = $('#wds_title').val(),
		res = txt ? txt.length : false
	;
	$('#wds_title_counter_result').html(
		res > __WDS_TITLE_COUNT
			? '<span style="color:red">' + _replace('title_longer', res) + '</span>'
			: _replace('title_length', res)
	);
}
function checkMainTitleLength () {
	var txt = $('#title').val(),
		res = txt ? txt.length : false
	;
	$('#wds_main_title_counter_result').html(
		res > __WDS_TITLE_COUNT
			? '<span style="color:red">' + _replace('main_title_longer', res) + '</span>'
			: _replace('title_length', res)
	);

}
function checkMetaLength () {
	var txt = $('#wds_metadesc').val()
		res = txt ? txt.length : false
	;
	if (res > __WDS_META_COUNT) {
		$('#wds_metadesc').val( $('#wds_metadesc').val().substr(0, __WDS_META_COUNT) );
		return false;
	}
	$('#wds_meta_counter_result').text( (__WDS_META_COUNT - res) + ' characters left');
}

function setUpCounters () {
	var $title = $('#wds_title');
	if (!$title.length) return false;
	$title.parents('td').append('<p id="wds_title_counter_result">' + __WDS_TITLE_COUNT + ' characters left</p>');
	$title.keyup(checkTitleLength);
	$title.change(checkTitleLength);
	checkTitleLength();

	var $meta = $('#wds_metadesc');
	if (!$meta.length) return false;
	$meta.parents('td').append('<p id="wds_meta_counter_result">' + __WDS_META_COUNT + ' characters left</p>');
	$meta.keyup(checkMetaLength);
	$meta.change(checkMetaLength);
	checkMetaLength();

	if (l10nWdsCounters.main_title_warning) {
		var $main_title = $("#title"),
			$main_title_root = $("#titlewrap")
		;
		$main_title_root.append('<p id="wds_main_title_counter_result">' + __WDS_TITLE_COUNT + ' characters left</p>');
		$main_title.keyup(checkMainTitleLength);
		$main_title.change(checkMainTitleLength);
		checkMainTitleLength();
	}
}

setUpCounters();

// Set overflow for SEO metabox
$("#wds-wds-meta-box .inside").css('overflow-x', 'scroll');

});
})(jQuery);