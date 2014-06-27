<?php

function rfs_configuration() {
	$private = null;
	$public = null;

	if (!empty(getenv("REMOTEFS_PRIVATE_URL"))) {
		$private = getenv("REMOTEFS_PRIVATE_URL");
		$public = getenv("REMOTEFS_PUBLIC_URL");
	} else {
		$private = get_option("rfs-private-url");
		$public = get_option("rfs-public-url");
	}

	if (empty($private) || empty($public)) return null;

	return array(
		"private" => $private,
		"public" => $public
	);
}

function rfs_local2remote($localPath) {
	$uploadDir = wp_upload_dir();
	return substr($localPath, strlen($uploadDir['basedir']) + 1);
}

$RFS_2BFLUSHED = array();

function rfs_put($localPath) {
	global $RFS;
	$path = rfs_local2remote($localPath);
	
	if ($RFS->put($path, $localPath)) {
		global $RFS_2BFLUSHED;
		if (count($RFS_2BFLUSHED) == 0) register_shutdown_function("rfs_flush_local_uploads");
		$RFS_2BFLUSHED[] = $localPath;

		return rfs_configuration()['public'] . '/' . $path;
	} else {
		die("RFS: failed put. Errors: " . print_r($RFS->errors(), true));
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

function rfs_flush_local_uploads() {
	global $RFS_2BFLUSHED;
	foreach ($RFS_2BFLUSHED as $path) {
		unlink($path);
	}
}
