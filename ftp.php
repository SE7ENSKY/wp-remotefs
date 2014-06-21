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
		register_shutdown_function(array($this, 'shutdown'));
	}

	public function shutdown() {
		$this->disconnect();
	}

	protected function connect() {
		if ($this->connection) return true;
		$connection = $this->secure ? @ftp_ssl_connect($this->host, $this->port) : @ftp_connect($this->host, $this->port);
		if ($connection) {
			$logged = @ftp_login($connection, $this->login, $this->password);
			if ($logged) {
				$this->connection = $connection;
				return true;
			} else {
				@ftp_close($connection);
			}
		}
		return false;
	}

	protected function disconnect() {
		if ($this->connection) {
			@ftp_close($this->connection);
			$this->connection = null;
		}
	}

	public function get($path, $localPath) {
		if (!$this->connect()) return false;
		return @ftp_get($this->connection, $localPath, $this->path . $path, FTP_BINARY);
	}

	public function put($path, $localPath) {
		if (!$this->connect()) return false;
		return @ftp_put($this->connection, $this->path . $path, $localPath, FTP_BINARY);
	}

	public function delete($path) {
		if (!$this->connect()) return false;
		@ftp_delete($this->connection, $this->path . $path);
	}

	public function exists($path) {
		if (!$this->connect()) return false;
		return @ftp_size($this->connection, $this->path . $path) != -1;
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
