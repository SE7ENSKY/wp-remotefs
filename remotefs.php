<?php
/*
Plugin Name: RemoteFS
Plugin URI: http://github.com/Se7enSky/wp-remotefs
Description: Detach attachments to remote filesystem. Stupid simple and minimalistic solution for Heroku, 12factor and CDN support. And also almost transparent for WP, plugins and for user.
Version: 1.4.0
Author: Ivan Kravchenko @ Se7enSky
Author URI: http://www.se7ensky.com/
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
	abstract public function errors(); // return errors
}

require_once "ftp.php";

require_once "helpers.php";

$RFS_NOTICES = array();

$rfsConfig = rfs_configuration();
$RFS = null;

if ($rfsConfig) {
	$RFS = RemoteFS::make($rfsConfig['private']);
	if ($RFS) {
		require_once "wp-hooks.php";
	} else {
		$RFS_NOTICES[] = 'not-implemented-private';
	}
} else {
	$RFS_NOTICES[] = 'not-configured';
}

require_once "admin-page.php";
