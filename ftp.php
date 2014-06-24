<?php

class FTPRemoteFS extends RemoteFS {
	protected $connection = null;
	protected $secure = false;
	protected $host;
	protected $port = 21;
	protected $path = '/';
	protected $login = 'anonymous';
	protected $password = '';
	protected $config;
	protected $errors;

	public function __construct($config) {
		$this->config = $config;
		$url = parse_url($config);
		$this->secure = $url['scheme'] == 'ftps';
		$this->host = $url['host'];
		if (isset($url['port'])) $this->port = $url['port'];
		if (isset($url['user'])) $this->login = $url['user'];
		if (isset($url['pass'])) $this->password = $url['pass'];
		if (isset($url['path'])) $this->path = $url['path'];
		if ($this->path[strlen($this->path)-1] != '/') $this->path .= '/';
		register_shutdown_function(array($this, '_shutdown'));
	}

	protected function setupErrorHandler() {
		set_error_handler(array($this, '_errorHandler'), E_ALL);
	}

	protected function releaseErrorHandler() {
		restore_error_handler();
	}

	public function _errorHandler($code, $message, $file = null, $line = -1, $context = null) {
		$error = array(
			"code" => $code,
			"message" => $message,
			"file" => $file,
			"line" => $line,
			"context" => $context
		);

		if (!is_array($this->errors)) $this->errors = array($error);
		else $this->errors[] = $error;
	}

	public function _shutdown() {
		$this->disconnect();
	}

	public function errors() {
		return $this->errors;
	}

	protected function connect() {
		if ($this->connection) return true;
		$this->setupErrorHandler();
		$connection = $this->secure ? @ftp_ssl_connect($this->host, $this->port) : @ftp_connect($this->host, $this->port);
		if ($connection) {
			$logged = @ftp_login($connection, $this->login, $this->password);
			if ($logged) {
				$this->connection = $connection;
				$this->releaseErrorHandler();
				return true;
			} else {
				@ftp_close($connection);
			}
		}
		$this->releaseErrorHandler();
		return false;
	}

	protected function disconnect() {
		if ($this->connection) {
			@ftp_close($this->connection);
			$this->connection = null;
		}
	}

	protected function mkdirp($path) {
		$parts = explode("/", $path);
		@ftp_chdir($this->connection, "/");
		foreach ($parts as $part) {
			if (empty($part)) continue;
			if (!@ftp_chdir($this->connection, $part)) {
				@ftp_mkdir($this->connection, $part);
				if (!@ftp_chdir($this->connection, $part)) return false;
			}
		}
		return $this->isDirectory($path);
	}

	protected function isDirectory($path) {
		return @ftp_chdir($this->connection, $path);
	}

	public function get($path, $localPath) {
		if (!$this->connect()) return false;
		$this->setupErrorHandler();
		$result = @ftp_get($this->connection, $localPath, $this->path . $path, FTP_BINARY);
		$this->releaseErrorHandler();
		return $result;
	}

	public function put($path, $localPath) {
		if (!$this->connect()) return false;
		$dir = dirname($this->path . $path);
		$this->setupErrorHandler();
		if (!$this->isDirectory($dir) && !$this->mkdirp($dir)) {
			$this->releaseErrorHandler();
			return false;
		}
		$result = @ftp_put($this->connection, $this->path . $path, $localPath, FTP_BINARY);
		$this->releaseErrorHandler();
		return $result;
	}

	public function delete($path) {
		if (!$this->connect()) return false;
		$this->setupErrorHandler();
		$result = @ftp_delete($this->connection, $this->path . $path);
		$this->releaseErrorHandler();
		return $result;
	}

	public function exists($path) {
		if (!$this->connect()) return false;
		$this->setupErrorHandler();
		$result = @ftp_size($this->connection, $this->path . $path) != -1;
		$this->releaseErrorHandler();
		return $result;
	}

}

RemoteFS::register("FTPRemoteFS", function($config) {
	try {
		$url = parse_url($config);
		return in_array($url['scheme'], array('ftp', 'ftps'));
	} catch (Exception $e) {
		return false;
	}
});
