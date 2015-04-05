<?php

// avoid file duplicates with adding _counter to filenames
add_filter('wp_handle_upload_prefilter', 'rfs_wphook_handle_upload_prefilter');
function rfs_wphook_handle_upload_prefilter($file) {
	$uploadDir = wp_upload_dir();
	$prefix = '';
	if (!empty($uploadDir['subdir']))
		$prefix = substr($uploadDir['subdir'], 1) . '/';
	
	while (rfs_exists($prefix . $file['name'])) {
		$info = pathinfo($file['name']);
		if (preg_match('|^(?<name>.*)_(?<counter>\d+)$|', $info['filename'], $matches)) {
			$counter = intval($matches['counter']);
			$counter++;
			$info['filename'] = $matches['name'] . '_' . $counter;
		} else {
			$info['filename'] .= '_1';
		}
		$file['name'] = $info['filename'] . '.' . $info['extension'];
	}
	return $file;
}

// handle file deletions
add_action('wp_delete_file', 'rfs_wphook_delete_file');
function rfs_wphook_delete_file($filename){
	if ($filename[0] == '/') {
		rfs_delete(rfs_local2remote($filename));
	} else {
		rfs_delete(preg_replace('|^\./|', '', $filename));
	}
}

// auto-upload local attachments to remote
add_filter('wp_update_attachment_metadata', 'rfs_wphook_update_attachment_metadata', 59172, 2);
function rfs_wphook_update_attachment_metadata($data, $post_id) {
	$post_id = (int) $post_id;
	if ( !$post = get_post( $post_id ) )
		return false;
	$uploadDir = wp_upload_dir();
	$file = get_post_meta( $post->ID, '_wp_attached_file', true);
	$subdir = dirname($file);
	$subdir = $subdir == '.' ? '' : "/$subdir";
	if (isset($data['sizes'])) {
		foreach ($data['sizes'] as $size => &$sizedata) {
			$localPath = $uploadDir['basedir'] . $subdir . '/' . $sizedata['file'];
			if (file_exists($localPath)) {
				$sizedata['url'] = rfs_put($localPath);
			}
		}
	}
	$localPath = $uploadDir['basedir'] . '/' . $file;
	if (file_exists($localPath)) {
		$data['url'] = rfs_put($localPath);
	}
	return $data;
}


// hooked upload dir
add_filter('upload_dir', 'rfs_wphook_upload_dir', 1);
function rfs_wphook_upload_dir($data) {
	$rfsConfig = rfs_configuration();
	$data['baseurl'] = $rfsConfig['public'];
	$data['url'] = $data['baseurl'] . $data['subdir'];
	return $data;
}
