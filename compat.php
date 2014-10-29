<?php

// compatibility with root-relative-urls plugin
add_action("plugins_loaded", "rfs_compat_root_relative_urls", 2);
function rfs_compat_root_relative_urls() {
	$rru_fix_upload_paths = array(
		'MP_WP_Root_Relative_URLS',
		'fix_upload_paths'
	);
	$rru_proper_root_relative_url = array(
		'MP_WP_Root_Relative_URLS',
		'proper_root_relative_url'
	);
	$rru_root_relative_image_urls = array(
		'MP_WP_Root_Relative_URLS',
		'root_relative_image_urls'
	);
	$rru_root_relative_media_urls = array(
		'MP_WP_Root_Relative_URLS',
		'root_relative_media_urls'
	);
	
	remove_filter('upload_dir', $rru_fix_upload_paths, 1);
	remove_filter('attachment_link', $rru_proper_root_relative_url, 1);
	remove_filter('wp_get_attachment_url', $rru_proper_root_relative_url, 1);
	remove_filter('image_send_to_editor', $rru_root_relative_image_urls, 1);
	remove_filter('media_send_to_editor', $rru_root_relative_image_urls, 1);
	remove_filter('media_send_to_editor', $rru_root_relative_media_urls, 1);
}
