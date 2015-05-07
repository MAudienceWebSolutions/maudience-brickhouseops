<?php

class WDS_Taxonomy {

	function WDS_Taxonomy() {

		if (is_admin() && isset($_GET['taxonomy']))
			add_action($_GET['taxonomy'] . '_edit_form', array(&$this,'term_additions_form'), 10, 2 );

		add_action('edit_term', array(&$this,'update_term'), 10, 3 );
	}

	function form_row( $id, $label, $desc, $tax_meta, $type = 'text' ) {
		$val = !empty($tax_meta[$id]) ? stripslashes( $tax_meta[$id] ) : '';

		echo '<tr class="form-field">'."\n";
		echo "\t".'<th scope="row" valign="top"><label for="'.$id.'">'.$label.':</label></th>'."\n";
		echo "\t".'<td>'."\n";
		if ( $type == 'text' ) {
?>
			<input name="<?php echo $id; ?>" id="<?php echo $id; ?>" type="text" value="<?php if (isset($val)) echo $val; ?>" size="40"/>
			<p class="description"><?php echo $desc; ?></p>
<?php
		} elseif ( $type == 'checkbox' ) {
?>
			<input name="<?php echo $id; ?>" id="<?php echo $id; ?>" type="checkbox" <?php checked($val); ?> style="width:5%;" />
<?php
		}
		echo "\t".'</td>'."\n";
		echo '</tr>'."\n";

	}

	function term_additions_form( $term, $taxonomy ) {
		global $wds_options;
		$tax_meta = get_option('wds_taxonomy_meta');

		if ( isset( $tax_meta[$taxonomy][$term->term_id] ) )
			$tax_meta = $tax_meta[$taxonomy][$term->term_id];

		$taxonomy_object = get_taxonomy( $taxonomy );
		$taxonomy_labels = $taxonomy_object->labels;

		$global_noindex = !empty($wds_options['meta_robots-noindex-' . $term->taxonomy])
			? $wds_options['meta_robots-noindex-' . $term->taxonomy]
			: false
		;
		$global_nofollow = !empty($wds_options['meta_robots-nofollow-' . $term->taxonomy])
			? $wds_options['meta_robots-nofollow-' . $term->taxonomy]
			: false
		;

		echo '<h3>' . __( 'Infinite SEO Settings ' , 'wds') . '</h3>';
		echo '<table class="form-table">';

		$this->form_row( 'wds_title', __( 'SEO Title' , 'wds'), __( 'The SEO title is used on the archive page for this term.' , 'wds'), $tax_meta );
		$this->form_row( 'wds_desc', __( 'SEO Description' , 'wds'), __( 'The SEO description is used for the meta description on the archive page for this term.' , 'wds'), $tax_meta );
		$this->form_row( 'wds_canonical', __( 'Canonical' , 'wds'), __( 'The canonical link is shown on the archive page for this term.' , 'wds'), $tax_meta );

		if ($global_noindex) $this->form_row('wds_override_noindex', sprintf(__('Index this %s' , 'wds'), strtolower($taxonomy_labels->singular_name)), '', $tax_meta, 'checkbox');
		else $this->form_row('wds_noindex', sprintf( __( 'Noindex this %s' , 'wds'), strtolower( $taxonomy_labels->singular_name ) ), '', $tax_meta, 'checkbox');

		if ($global_nofollow) $this->form_row('wds_override_nofollow', sprintf(__('Follow this %s' , 'wds'), strtolower($taxonomy_labels->singular_name)), '', $tax_meta, 'checkbox');
		else $this->form_row( 'wds_nofollow', sprintf( __( 'Nofollow this %s' , 'wds'), strtolower( $taxonomy_labels->singular_name ) ), '', $tax_meta, 'checkbox' );

		echo '</table>';
	}

	function update_term( $term_id, $tt_id, $taxonomy ) {
		global $wds_options;
		$tax_meta = get_option( 'wds_taxonomy_meta' );

		foreach (array('title', 'desc', 'bctitle', 'canonical') as $key) {
			$tax_meta[$taxonomy][$term_id]['wds_'.$key] 	= @$_POST['wds_'.$key];
		}

		foreach (array('noindex', 'nofollow') as $key) {
			$global = !empty($wds_options["meta_robots-{$key}-{$taxonomy}"]) ? (bool)$wds_options["meta_robots-{$key}-{$taxonomy}"] : false;

			if (!$global) $tax_meta[$taxonomy][$term_id]['wds_'.$key] = isset($_POST["wds_{$key}"]) ? (bool)$_POST["wds_{$key}"] : false;
			else $tax_meta[$taxonomy][$term_id]["wds_override_{$key}"] = isset($_POST["wds_override_{$key}"]) ? (bool)$_POST["wds_override_{$key}"] : false;
		}

		update_option( 'wds_taxonomy_meta', $tax_meta );

		if ( defined('W3TC_DIR') ) {
			require_once W3TC_DIR . '/lib/W3/ObjectCache.php';
			$w3_objectcache = & W3_ObjectCache::instance();

			$w3_objectcache->flush();
		}

	}
}
$wds_taxonomy = new WDS_Taxonomy();

?>