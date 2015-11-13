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

add_action( 'admin_footer', 'rvrmth_update_attachments_meta_js' ); // Write our JS below here
add_action( 'wp_ajax_rvrmth_update_attachments_meta', 'rvrmth_update_attachments_meta_js_callback' );

function rvrmth_update_attachments_meta_js() { ?>
	<script type="text/javascript" >
	jQuery(document).ready(function($) {
		var totalUpdatedCount = 0;
		var statusTest = "";
		function doUpdate(wrapper, pageSize, offset, regenerateAll) {
			if (!wrapper) {
				return;
			}
			
			wrapper.html("<p><b>Updating</b>...<br>" + 
						 "<i>Sit tight, we will tell you as soon as whole updating process is done.</i><br>" + 
						 "<i><small>But if you close this page, no harm will be done - update process just will be stopped.</small></i></p>" + 
						 "<p>" + statusTest + "</p>");
			
			var data = {
				'action': 'rvrmth_update_attachments_meta',
				'pageSize': pageSize,
				'offset': offset, 
				'regenerateAll': regenerateAll ? true : false
			};

			// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			$.post(ajaxurl, data, function(response) {
				if (response == 0) {
					wrapper.append("DONE!");
					return;
				}
				totalUpdatedCount += parseInt(response);
				statusTest = "Latest batch: " + response + "<br>Total update count: " + totalUpdatedCount;
				setTimeout(function() {
					doUpdate(wrapper, pageSize, offset + pageSize, regenerateAll);
				}, 300);
			});
		}
		doUpdate($("#rvrmth_update_attachments_meta_checkbox_field_0"), 15, 0);
		doUpdate($("#rvrmth_update_attachments_meta_checkbox_field_1"), 15, 0, true);
	});
	</script> <?php
}


function rvrmth_update_attachments_meta_js_callback() {
	$offset = intval($_POST['offset']);
	$posts_per_page = intval($_POST['pageSize']);
	$regenerate_all = $_POST['regenerateAll'] == 'true' ? true : false;
	
	$options = array(
		'posts_per_page' => $posts_per_page,
		'offset' => $offset,
		'post_type' => 'attachment',
	);
	if (!$regenerate_all) {
		$options['meta_key'] = '_wp_attachment_metadata';
		$options['meta_compare'] = 'NOT EXISTS';
	}
	
	$update_count = 0;
	$posts_array = get_posts($options);
	foreach ($posts_array as $post_object) {
		$meta_data = wp_generate_attachment_metadata($post_object->ID, WP_CONTENT_DIR . '/uploads/' . get_post_meta($post_object->ID, '_wp_attached_file', true));
		$result = wp_update_attachment_metadata($post_object->ID, $meta_data);
		if ($result) {
			$update_count++;
		}
	}
	wp_die($update_count); // this is required to terminate immediately and return a proper response
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

	add_settings_field( 
		'rvrmth_update_attachments_meta_checkbox_field_1', 
		__( 'Regenerate media meta data', 'rvrmth-update-attachments-meta' ), 
		'rvrmth_update_attachments_meta_checkbox_field_1_render', 
		'media', 
		'rvrmth_update_attachments_meta_section' 
	);

	register_setting( 'media', 'rvrmth_update_attachments_meta_checkbox_field_0' );
	register_setting( 'media', 'rvrmth_update_attachments_meta_checkbox_field_1' );
}

function rvrmth_update_attachments_meta_checkbox_field_0_render() 
{ 
	$update_requested = get_option( 'rvrmth_update_attachments_meta_checkbox_field_0' );
	if ($update_requested) {
		update_option( 'rvrmth_update_attachments_meta_checkbox_field_0', false );
		echo '<div id="rvrmth_update_attachments_meta_checkbox_field_0"></div>';
	}
	?>
	<input type='checkbox' name='rvrmth_update_attachments_meta_checkbox_field_0' <?php echo checked(1, get_option( 'rvrmth_update_attachments_meta_checkbox_field_0' ), false); ?> value='1'>
	<?php

}

function rvrmth_update_attachments_meta_checkbox_field_1_render() 
{ 
	$update_requested = get_option( 'rvrmth_update_attachments_meta_checkbox_field_1' );
	if ($update_requested) {
		update_option( 'rvrmth_update_attachments_meta_checkbox_field_1', false );
		echo '<div id="rvrmth_update_attachments_meta_checkbox_field_1"></div>';
	}
	?>
	<input type='checkbox' name='rvrmth_update_attachments_meta_checkbox_field_1' <?php echo checked(1, get_option( 'rvrmth_update_attachments_meta_checkbox_field_1' ), false); ?> value='1'>
	<?php

}

function rvrmth_update_attachments_meta_settings_section_callback($arg) 
{ 
	echo __( 'Settings for plugin "Update attachments meta data"', 'rvrmth-update-attachments-meta' );
}

?>