<?php

// avoid file duplicates with adding _counter to filenames
add_filter('wp_handle_upload_prefilter', 'rfs_wphook_handle_upload_prefilter');
function rfs_wphook_handle_upload_prefilter($file) {
	while (rfs_exists($file['name'])) {
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
add_filter('wp_update_attachment_metadata', 'rfs_wphook_update_attachment_metadata');
function rfs_wphook_update_attachment_metadata($data) {
	$uploadDir = wp_upload_dir();
	file_put_contents("/tmp/wp-debug", print_r($data, true) . "\n\n" . print_r($uploadDir, true));
	foreach ($data['sizes'] as $size => &$sizedata) {
		$localPath = $uploadDir['basedir'] . $uploadDir['subdir'] . '/' . $sizedata['file'];
		if (file_exists($localPath)) {
			$sizedata['url'] = rfs_put($localPath);
			unlink($localPath);
		}
	}
	$localPath = $uploadDir['basedir'] . '/' . $data['file'];
	if (file_exists($localPath)) {
		$data['url'] = rfs_put($localPath);
		unlink($localPath);
	}
	return $data;
}

// hooked upload dir
add_filter('upload_dir', 'rfs_wphook_upload_dir');
function rfs_wphook_upload_dir($data) {
	$rfsConfig = rfs_configuration();
	$data['baseurl'] = $rfsConfig['public'];
	$data['url'] = $data['baseurl'] . $data['subdir'];
	return $data;
}
