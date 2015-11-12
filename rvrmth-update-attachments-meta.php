<?php
/*
Plugin Name: Update attachments meta data
Plugin URI:  http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Update attachments meta data where meta data is not present
Version:     0.1
Author:      Rivermouth Ltd
Author URI:  http://rivermouth.fi
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: rvrmth-update-attachments-meta
*/

include( ABSPATH . 'wp-admin/includes/image.php' );

function rvrmth_update_attachments_meta_do_update() 
{
	$update_count = 0;
	$posts_array = get_posts(array(
		'posts_per_page' => -1,
		'offset' => 0,
		'post_type' => 'attachment',
		'meta_key' => '_wp_attachment_metadata',
		'meta_compare' => 'NOT EXISTS'
	));
	foreach ($posts_array as $post_object) {
		$meta_data = wp_generate_attachment_metadata($post_object->ID, WP_CONTENT_DIR . '/uploads/' . get_post_meta($post_object->ID, '_wp_attached_file', true));
		$result = wp_update_attachment_metadata($post_object->ID, $meta_data);
		if ($result) {
			$update_count++;
		}
	}
	return $update_count;
}


add_action( 'admin_init', 'rvrmth_update_attachments_meta_settings_init' );

function rvrmth_update_attachments_meta_settings_init() 
{
	add_settings_section(
		'rvrmth_update_attachments_meta_section', 
		__( 'Update attachments meta data', 'rvrmth-update-attachments-meta' ), 
		'rvrmth_update_attachments_meta_settings_section_callback', 
		'media'
	);

	add_settings_field( 
		'rvrmth_update_attachments_meta_checkbox_field_0', 
		__( 'Generate missing media meta data', 'rvrmth-update-attachments-meta' ), 
		'rvrmth_update_attachments_meta_checkbox_field_0_render', 
		'media', 
		'rvrmth_update_attachments_meta_section' 
	);

	register_setting( 'media', 'rvrmth_update_attachments_meta_checkbox_field_0' );
}

function rvrmth_update_attachments_meta_checkbox_field_0_render() 
{ 
	$update_requested = get_option( 'rvrmth_update_attachments_meta_checkbox_field_0' );
	if ($update_requested) {
		$update_count = rvrmth_update_attachments_meta_do_update();
		update_option( 'rvrmth_update_attachments_meta_checkbox_field_0', false );
		echo '<b>Media meta data generated for ' . $update_count . ' posts!</b>';
	}
	?>
	<input type='checkbox' name='rvrmth_update_attachments_meta_checkbox_field_0' <?php echo checked(1, get_option( 'rvrmth_update_attachments_meta_checkbox_field_0' ), false); ?> value='1'>
	<?php

}

function rvrmth_update_attachments_meta_settings_section_callback($arg) 
{ 
	echo __( 'Settings for plugin "Update attachments meta data"', 'rvrmth-update-attachments-meta' );
}

?>