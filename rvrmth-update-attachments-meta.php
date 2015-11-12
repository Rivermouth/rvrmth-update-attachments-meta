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

class rvrmth_update_attachments_Thread extends Thread 
{
	public function __construct() 
	{
	}

	public function run() 
	{
		$update_count = 0;
		$offset = 0;
		$posts_per_page = 10;
		$has_more_posts = true;
		while ($has_more_posts) {
			$posts_array = get_posts(array(
				'posts_per_page' => $posts_per_page,
				'offset' => $offset,
				'post_type' => 'attachment',
				'meta_key' => '_wp_attachment_metadata',
				'meta_compare' => 'NOT EXISTS'
			));
			if (count($posts_array) == 0) {
				$has_more_posts = false;
				continue;
			}
			foreach ($posts_array as $post_object) {
				$meta_data = wp_generate_attachment_metadata($post_object->ID, WP_CONTENT_DIR . '/uploads/' . get_post_meta($post_object->ID, '_wp_attached_file', true));
				$result = wp_update_attachment_metadata($post_object->ID, $meta_data);
				if ($result) {
					$update_count++;
				}
			}
			$offset += $posts_per_page;
		}
		return $update_count;
	}
}

function rvrmth_update_attachments_meta_do_update() 
{
	$worker = new rvrmth_update_attachments_Thread();
	return $worker->run();
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