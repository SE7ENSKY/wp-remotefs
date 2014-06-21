<?php
/*
Plugin Name: RemoteFS
Plugin URI: http://github.com/Se7enSky/wp-remotefs
Description: Detach uploads to remote server. Supports for FTP, FTPs. Amazon S3, SFTP and others planned.
Version: 1.0
Author: Ivan Kravchenko @ Se7enSky
Author URI: http://www.se7ensky.com
License: MIT
*/

abstract class RemoteFS {
	protected static $registered = array();
	static public function register($className, $detectFn) {
		self::$registered[$className] = $detectFn;
	}

	static public function make($config) {
		foreach (self::$registered as $className => $detectFn) {
			if ($detectFn($config)) {
				return new $className($config);
			}
		}
		return null;
	}

	abstract public function __construct($config);
	abstract public function get($path, $localPath); // -> bool fetch result
	abstract public function put($path, $localPath); // -> string URL
	abstract public function delete($path); // -> bool deletion result
	abstract public function exists($path); // -> bool existence result
}

require_once "ftp.php";

// ensure configuration
$RFS_UPLOAD = getenv("RFS_UPLOAD");
$RFS_ENDPOINT = getenv("RFS_ENDPOINT");
if (empty($RFS_UPLOAD) || empty($RFS_ENDPOINT))
	throw new Exception("RFS: not configured RFS_UPLOAD and RFS_ENDPOINT");

// make RFS instance
$RFS = RemoteFS::make($RFS_UPLOAD);
if (!$RFS)
	throw new Exception("RFS: no implementation detected for configured RFS_UPLOAD");

//
// proxy functions
//

function rfs_local2remote($localPath) {
	$uploadDir = wp_upload_dir();
	return substr($localPath, strlen($uploadDir['path']) + 1);
}

function rfs_put($localPath) {
	global $RFS_ENDPOINT;
	global $RFS;
	$path = rfs_local2remote($localPath);
	if ($RFS->put($path, $localPath)) {
		return $RFS_ENDPOINT . '/' . $path;
	} else {
		throw new Exception("RFS: failed put");
	}
}

function rfs_delete($path) {
	global $RFS;
	return $RFS->delete($path);
}

function rfs_exists($path) {
	global $RFS;
	return $RFS->exists($path);
}

//
// WP hook magic
//

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
	$localDirPath = $uploadDir['basedir'] . '/' . preg_replace('/^(.+\/)?.+$/', '\\1', $data['file']);
	foreach ($data['sizes'] as $size => &$sizedata) {
		$localPath = $localDirPath . $sizedata['file'];
		if (file_exists($localPath)) {
			$sizedata['url'] = rfs_put($localPath);
			unlink($localPath);
		}
	}
	$localPath = $localDirPath . $data['file'];
	if (file_exists($localPath)) {
		$data['url'] = rfs_put($localPath);
		unlink($localPath);
	}
	return $data;
}

// hooked upload dir
add_filter('upload_dir', 'rfs_wphook_upload_dir');
function rfs_wphook_upload_dir($data) {
	global $RFS_ENDPOINT;
	$data['baseurl'] = $RFS_ENDPOINT;
	$data['url'] = $data['baseurl'] . $data['subdir'];
	return $data;
}
