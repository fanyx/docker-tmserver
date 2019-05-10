<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

////////////////////////////////////////////////////////////////
//
// File:      WEB ACCESS 2.1.3
// Date:      13.10.2011
// Author:    Gilles Masson
// Updated:   Xymph
//
////////////////////////////////////////////////////////////////

// This class and functions can be used to make asynchronous xml or http
// (POST or GET) queries.
// This means that you call a function to send the query, and a callback
// function will automatically be called when the response has arrived,
// without having your program waiting for the response.
// You can also use it for synchronous queries (see below).
// The class handles (for each URL) keepalive and compression (when possible).
// It supports Cookies, and so can use sessions like php one (anyway the cookie
// is not stored, so its maximal life is the life of the program).
//
//
// usage:  $_webaccess = new Webaccess();
//         $_webaccess->request($url, array('func_name',xxx), $datas, $is_xmlrpc, $keepalive_min_timeout);
//    $url: the web script URL.
//    $datas: string to send in http body (xml, xml_rpc or POST data)
//    $is_xmlrpc: true if it's an xml or xml-rpc request, false if it's a
//                standard html GET or POST
//    $keepalive_min_timeout: minimal value of server keepalive timeout to
//                            send a keepalive request,
//                            else make a request with close connection.
//    func_name is the callback function name, which will be called this way:
//       func_name(array('Code'=>code,'Reason'=>reason,'Headers'=>headers,'Message'=>message), xxx)
//    where:
//           xxx is the same as given previously in callback description.
//           code is the returned http code
//             (http://www.w3.org/Protocols/rfc2616/rfc2616-sec6.html#sec6)
//           reason is the returned http reason
//             (http://www.w3.org/Protocols/rfc2616/rfc2616-sec6.html#sec6)
//           headers are the http headers of the reply
//           message is the returned text body
//
// IMPORTANT: to have this work, the main part of your program must include a
// $_webaccess->select() call periodically, which work exactly like stream_select().
// This is because the send and receive are asynchronous and will be completed
// later in the select() when datas are ready to be received and sent.


// This class can be used to make a synchronous query too. For that use null
// for the callback, so make the request this way:
//   $response = $_webaccess->request($url, null, $datas, $is_xmlrpc, $keepalive_min_timeout);
// where $response is an array('Code'=>code,'Reason'=>reason,'Headers'=>headers,'Message'=>message)
// like the one passed to the callback function of the asynchronous request.
// If you use only synchronous queries then there is no need to call select()
// as the function will return when the reply will be fully returned.
// If the connection itself fail, the array response will include a 'Error' string.


// Other functions:
//   list($host, $port, $path) = getHostPortPath($url);
//   gzdecode() workaround


global $_web_access_compress_xmlrpc_request, $_web_access_compress_reply,
       $_web_access_keepalive, $_web_access_keepalive_timeout,
       $_web_access_keepalive_max, $_web_access_retry_timeout,
       $_web_access_retry_timeout_max, $_web_access_post_xmlrpc;


// Will compress xmlrpc request ('never','accept','force','force-gzip','force-deflate')
// If set to 'accept' the first request will be made without, and the eventual
// 'Accept-Encoding' in reply will permit to decide if request compression can
// be used (and if gzip or deflate)
$_web_access_compress_xmlrpc_request = 'accept';

// Will ask server for compressed reply (false, true)
// If true then will add a 'Accept-Encoding' header to tell the server to
// compress the reply if it supports it.
$_web_access_compress_reply = true;


// Keep alive connection ? else close it after the reply.
// Unless false, first request will be with keepalive, to get server timeout
// and max values after timeout will be compared with the request
// $keepalive_min_timeout value to decide if keepalive have to be used or not.
// Note that Apache2 timeout is short (about 15s).
// The classes will open, re-open or use existing connection as needed.
$_web_access_keepalive = true;
// timeout(s) without request before close, for keepalive
$_web_access_keepalive_timeout = 600;
// max requests before close, for keepalive
$_web_access_keepalive_max = 2000;


// For asynchronous call, in case of error, timeout before retrying.
// It will be x2 for each error (on request or auto retry) until max,
// then stop automatic retry, and next request calls will return false.
// When stopped, a retry() or synchronous request will force a retry.
$_web_access_retry_timeout = 20;
$_web_access_retry_timeout_max = 5*60;


// Use text/html with xmlrpc=, instead of of pure text/xml request (false, true)
// Standard xml-rpc use pure text/xml request, where the xml is simply the body
// of the http request (and it's how the xml-rpc reply will be made). As a
// facility Dedimania also supports to get the xml in a html GET or POST,
// where xmlrpc= will contain a urlsafe base64 of the xml. Default to false,
// so use pure text/xml.
$_web_access_post_xmlrpc = false;

// Note that in each request the text/xml or xmlrpc= will be used only if
// $is_xmlrpc is true. If false then the request will be a standard
// application/x-www-form-urlencoded html GET or POST request; in that case
// you have to build the URL (GET) and/or body data (POST) yourself.
// If $is_xmlrpc is a string, then it's used as the Content-type: value.


require_once('includes/urlsafebase64.php');

class Webaccess {

	var $_WebaccessList;

	function Webaccess() {
		$this->_WebaccessList = array();
	}


	function request($url, $callback, $datas, $is_xmlrpc = false, $keepalive_min_timeout = 300, $opentimeout = 3, $waittimeout = 5, $agent = 'XMLaccess') {
		global $aseco, $_web_access_keepalive, $_web_access_keepalive_timeout, $_web_access_keepalive_max;

		list($host, $port, $path) = getHostPortPath($url);

		if ($host === false)
			$aseco->console('Webaccess request(): Bad URL: ' . $url);

		else {
			$server = $host . ':' . $port;
			// create object if needed
			if (!isset($this->_WebaccessList[$server]) || $this->_WebaccessList[$server] === null) {
				$this->_WebaccessList[$server] = new WebaccessUrl($this, $host, $port,
				                                                  $_web_access_keepalive,
				                                                  $_web_access_keepalive_timeout,
				                                                  $_web_access_keepalive_max,
				                                                  $agent);
			}

			// increase the default timeout for sync/wait request
			if ($callback == null && $waittimeout == 5)
				$waittimeout = 12;

			// call request
			if ($this->_WebaccessList[$server] !== null) {
				$query = array('Path' => $path,
				               'Callback' => $callback,
				               'QueryData' => $datas,
				               'IsXmlrpc' => $is_xmlrpc,
				               'KeepaliveMinTimeout' => $keepalive_min_timeout,
				               'OpenTimeout' => $opentimeout,
				               'WaitTimeout' => $waittimeout
				              );

				return $this->_WebaccessList[$server]->request($query);
			}
		}
		return false;
	}  // request


	function retry($url) {
		global $aseco;

		list($host, $port, $path) = getHostPortPath($url);
		if ($host === false) {
			$aseco->console('Webaccess retry(): Bad URL: ' . $url);
		} else {
			$server = $host . ':' . $port;
			if (isset($this->_WebaccessList[$server]))
				$this->_WebaccessList[$server]->retry();
		}
	}  // retry


	function select(&$read, &$write, &$except, $tv_sec, $tv_usec = 0) {

		$timeout = (int)($tv_sec*1000000 + $tv_usec);
		if ($read == null)
			$read = array();
		if ($write == null)
			$write = array();
		if ($except == null)
			$except = array();

		$read = $this->_getWebaccessReadSockets($read);
		$write = $this->_getWebaccessWriteSockets($write);
		//$except = $this->_getWebaccessReadSockets($except);

		//print_r($this->_WebaccessList);
		//print_r($read);
		//print_r($write);
		//print_r($except);

		// if no socket to select then return
		if (count($read) + count($write) + count($except) == 0) {
			// sleep the asked timeout...
			if ($timeout > 1000)
				usleep($timeout);
			return 0;
		}

		$utime = (int)(microtime(true)*1000000);
		$nb = @stream_select($read, $write, $except, $tv_sec, $tv_usec);
		if ($nb === false) {
			// in case stream_select "forgot" to wait, sleep the remaining asked timeout...
			$dtime = (int)(microtime(true)*1000000) - $utime;
			$timeout -= $dtime;
			if ($timeout > 1000)
				usleep($timeout);
			return false;
		}

		$this->_manageWebaccessSockets($read, $write, $except);
		// workaround for stream_select bug with amd64, replace $nb with sum of arrays
		return count($read) + count($write) + count($except);
	}  // select


	private function _manageWebaccessSockets(&$receive, &$send, &$except) {

		// send pending data on all webaccess sockets
		if (is_array($send) && count($send) > 0) {
			foreach ($send as $key => $socket) {
				$i = $this->_findWebaccessSocket($socket);
				if ($i !== false) {
					if (isset($this->_WebaccessList[$i]->_spool[0]['State']) &&
					    $this->_WebaccessList[$i]->_spool[0]['State'] == 'OPEN')
						$this->_WebaccessList[$i]->_open();
					else
						$this->_WebaccessList[$i]->_send();
					unset($send[$key]);
				}
			}
		}

		// read data from all needed webaccess sockets
		if (is_array($receive) && count($receive) > 0) {
			foreach ($receive as $key => $socket) {
				$i = $this->_findWebaccessSocket($socket);
				if ($i !== false) {
					$this->_WebaccessList[$i]->_receive();
					unset($receive[$key]);
				}
			}
		}
	}  // _manageWebaccessSockets


	private function _findWebaccessSocket($socket) {

		foreach ($this->_WebaccessList as $key => $wau) {
			if ($wau->_socket == $socket)
				return $key;
		}
		return false;
	}  // _findWebaccessSocket


	private function _getWebaccessReadSockets($socks) {

		foreach ($this->_WebaccessList as $key => $wau) {
			if ($wau->_state == 'OPENED' && $wau->_socket)
				$socks[] = $wau->_socket;
		}
		return $socks;
	}  // _getWebaccessReadSockets


	private function _getWebaccessWriteSockets($socks) {

		foreach ($this->_WebaccessList as $key => $wau) {
			if (isset($wau->_spool[0]['State']) &&
			    ($wau->_spool[0]['State'] == 'OPEN' ||
			     $wau->_spool[0]['State'] == 'BAD' ||
			     $wau->_spool[0]['State'] == 'SEND')) {

				if (($wau->_state == 'CLOSED' || $wau->_state == 'BAD') && !$wau->_socket)
					$wau->_open();

				if ($wau->_state == 'OPENED' && $wau->_socket)
					$socks[] = $wau->_socket;
			}
		}
		return $socks;
	}  // _getWebaccessWriteSockets


	function getAllSpools() {

		$num = 0;
		$bad = 0;
		foreach ($this->_WebaccessList as $key => $wau) {
			if ($wau->_state == 'OPENED' || $wau->_state == 'CLOSED')
				$num += count($wau->_spool);
			elseif ($wau->_state == 'BAD')
				$bad += count($wau->_spool);
		}
		return array($num, $bad);
	}  // getAllSpools
}  // class Webaccess


// useful data to handle received headers
$_wa_header_separator = array('cookie' => ';', 'set-cookie' => ';');
$_wa_header_multi = array('set-cookie' => true);


class WebaccessUrl {
	//-----------------------------
	// Fields
	//-----------------------------

	var $wa;
	var $_host;
	var $_port;
	var $_compress_request;
	var $_socket;
	var $_state;
	var $_keepalive;
	var $_keepalive_timeout;
	var $_keepalive_max;
	var $_serv_keepalive_timeout;
	var $_serv_keepalive_max;
	var $_spool;
	var $_wait;
	var $_response;
	var $_query_num;
	var $_request_time;
	var $_cookies;
	var $_webaccess_str;
	var $_bad_time;
	var $_bad_timeout;
	var $_read_time;
	var $_agent;

	// $_state values:
	//    'OPENED' : socket is opened
	//    'CLOSED' : socket is closed (asked, completed, or closed by server)
	//    'BAD'    : socket is closed, bad/error or beginning state

	// $query['State'] values: (note: $query is added in $_spool, so $this->_spool[0] is the first $query to handle)
	//    'BAD'    : bad/error or beginning state
	//    'OPEN'   : should prepare request data then send them
	//    'SEND'   : request data are prepared, send them
	//    'RECEIVE': request data are sent, receive reply data
	//    'DONE'   : request completed

	//-----------------------------
	// Methods
	//-----------------------------

	function WebaccessUrl(&$wa, $host, $port, $keepalive = true, $keepalive_timeout = 600, $keepalive_max = 300, $agent = 'XMLaccess') {
		global $_web_access_compress_xmlrpc_request, $_web_access_retry_timeout;

		$this->wa = &$wa;
		$this->_host = $host;
		$this->_port = $port;
		$this->_webaccess_str = 'Webaccess (' . $this->_host . ':' . $this->_port . '): ';
		$this->_agent = $agent;

		// request compression setting
		if ($_web_access_compress_xmlrpc_request == 'accept')
			$this->_compress_request = 'accept';
		elseif ($_web_access_compress_xmlrpc_request == 'force') {
			if (function_exists('gzencode'))
				$this->_compress_request = 'gzip';
			elseif (function_exists('gzdeflate'))
				$this->_compress_request = 'deflate';
			else
				$this->_compress_request = false;
		}
		elseif ($_web_access_compress_xmlrpc_request == 'force-gzip' && function_exists('gzencode'))
			$this->_compress_request = 'gzip';
		elseif ($_web_access_compress_xmlrpc_request == 'force-deflate' && function_exists('gzdeflate'))
			$this->_compress_request = 'deflate';
		else
			$this->_compress_request = false;

		$this->_socket = null;
		$this->_state = 'CLOSED';
		$this->_keepalive = $keepalive;
		$this->_keepalive_timeout = $keepalive_timeout;
		$this->_keepalive_max = $keepalive_max;
		$this->_serv_keepalive_timeout = $keepalive_timeout;
		$this->_serv_keepalive_max = $keepalive_max;
		$this->_spool = array();
		$this->_wait = false;
		$this->_response = '';
		$this->_query_num = 0;
		$this->_query_time = time();
		$this->_cookies = array();
		$this->_bad_time = time();
		$this->_bad_timeout = 0;
		$this->_read_time = 0;
	}  // WebaccessUrl


	// put connection in BAD state
	function _bad($errstr, $isbad = true) {
		global $aseco, $_web_access_retry_timeout;

		$aseco->console($this->_webaccess_str . $errstr);
		$this->infos();

		if ($this->_socket)
			@fclose($this->_socket);
		$this->_socket = null;

		if ($isbad) {
			if (isset($this->_spool[0]['State']))
				$this->_spool[0]['State'] = 'BAD';
			$this->_state = 'BAD';

			$this->_bad_time = time();
			if ($this->_bad_timeout < $_web_access_retry_timeout)
				$this->_bad_timeout = $_web_access_retry_timeout;
			else
				$this->_bad_timeout *= 2;

		} else {
			if (isset($this->_spool[0]['State']))
				$this->_spool[0]['State'] = 'OPEN';
			$this->_state = 'CLOSED';
		}
		$this->_callCallback($this->_webaccess_str . $errstr);
	}  // _bad


	function retry() {
		global $_web_access_retry_timeout;

		if ($this->_state == 'BAD') {
			$this->_bad_time = time();
			$this->_bad_timeout = 0;
		}
	}  // retry


	//$query = array('Path' => $path,
	//               'Callback' => $callback,
	//               'QueryData' => $datas,
	//               'IsXmlrpc' => $is_xmlrpc,
	//               'KeepaliveMinTimeout' => $keepalive_min_timeout,
	//               'OpenTimeout' => $opentimeout,
	//               'WaitTimeout' => $waittimeout );
	// will add:     'State', 'HDatas', 'Datas', 'DatasSize', 'DatasSent',
	//               'Response', 'ResponseSize', 'Headers', 'Close', 'Times'
	function request(&$query) {
		global $aseco, $_web_access_compress_reply, $_web_access_post_xmlrpc, $_web_access_retry_timeout, $_web_access_retry_timeout_max;

		$query['State'] = 'BAD';
		$query['HDatas'] = '';
		$query['Datas'] = '';
		$query['DatasSize'] = 0;
		$query['DatasSent'] = 0;
		$query['Response'] = '';
		$query['ResponseSize'] = 0;
		$query['Headers'] = array();
		$query['Close'] = false;
		$query['Times'] = array('open' => array(-1.0,-1.0), 'send' => array(-1.0,-1.0), 'receive' => array(-1.0,-1.0,0));

		// if asynch, in error, and maximal timeout, then forget the request and return false
		if (($query['Callback'] != null) && ($this->_state == 'BAD')) {
			if ($this->_bad_timeout > $_web_access_retry_timeout_max) {
				$aseco->console($this->_webaccess_str . 'Request refused for consecutive errors (' . $this->_bad_timeout . ' / ' . $_web_access_retry_timeout_max . ')');
				return false;

			} else {
				// if not max then accept the request and try a request (minimum $_web_access_retry_timeout/2 after previous try)
				$time = time();
				$timeout = ($this->_bad_timeout / 2) - ($time - $this->_bad_time);
				if ($timeout < 0)
					$timeout = 0;
				$this->_bad_time = $time - $this->_bad_timeout + $timeout;
			}
		}

		// build data to send
		if (($query['Callback'] == null) || (is_array($query['Callback']) &&
		                                     isset($query['Callback'][0]) &&
		                                     is_callable($query['Callback'][0]))) {

			if (is_string($query['QueryData']) && strlen($query['QueryData']) > 0) {
				$msg = "POST " . $query['Path'] . " HTTP/1.1\r\n";
				$msg .= "Host: " . $this->_host . "\r\n";
				$msg .= "User-Agent: " . $this->_agent . "\r\n";
				$msg .= "Cache-Control: no-cache\r\n";

				if ($_web_access_compress_reply) {
					// ask compression of response if gzdecode() and/or gzinflate() is available
					if (function_exists('gzdecode') && function_exists('gzinflate'))
						$msg .= "Accept-Encoding: deflate, gzip\r\n";
					elseif (function_exists('gzdecode'))
						$msg .= "Accept-Encoding: gzip\r\n";
					elseif (function_exists('gzinflate'))
						$msg .= "Accept-Encoding: deflate\r\n";
				}

				//echo "\nData:\n\n" . $query['QueryData'] . "\n";

				if ($query['IsXmlrpc'] === true) {
					if ($_web_access_post_xmlrpc) {
						$msg .= "Content-type: application/x-www-form-urlencoded; charset=UTF-8\r\n";

						//echo "\n=========================== Data =================================\n\n" . $datas . "\n";
						//$d2 = urlsafe_base64_encode($datas);
						//$d3 = urlsafe_base64_decode($d2);
						//echo "\n--------------------------- Data ---------------------------------\n\n" . $d3 . "\n";

						$query['QueryData'] = 'xmlrpc=' . urlsafe_base64_encode($query['QueryData']);
					}
					else {
						$msg .= "Content-type: text/xml; charset=UTF-8\r\n";
					}

					if ($this->_compress_request == 'gzip' && function_exists('gzencode')) {
						$msg .= "Content-Encoding: gzip\r\n";
						$query['QueryData'] = gzencode($query['QueryData']);
					} elseif ($this->_compress_request == 'deflate' && function_exists('gzdeflate')) {
						$msg .= "Content-Encoding: deflate\r\n";
						$query['QueryData'] = gzdeflate($query['QueryData']);
					}

				}
				elseif (is_string($query['IsXmlrpc'])) {
					$msg .= "Content-type: " . $query['IsXmlrpc'] . "\r\n";
					$msg .= "Accept: */*\r\n";
				} else {
					$msg .= "Content-type: application/x-www-form-urlencoded; charset=UTF-8\r\n";
				}
				$msg .= "Content-length: " . strlen($query['QueryData']) . "\r\n";
				$query['HDatas'] = $msg;
				$query['State'] = 'OPEN';
				$query['Retries'] = 0;

				//print_r($msg);

				// add the query in spool
				$this->_spool[] = &$query;

				if ($query['Callback'] == null) {
					$this->_wait = true;
					$this->_open($query['OpenTimeout'], $query['WaitTimeout']);  // wait more in not callback mode
					$this->_spool = array();
					$this->_wait = false;
					return $query['Response'];
				} else
					$this->_open();

			} else {
				$aseco->console($this->_webaccess_str . 'Bad data');
				return false;
			}

		} else {
			$aseco->console($this->_webaccess_str . 'Bad callback function: ' . $query['Callback']);
			return false;
		}
		return true;
	}  // request


	// open the socket (close it before if needed)
	private function _open_socket($opentimeout = 0.0) {
		global $aseco;

		// if socket not opened, then open it (2 tries)
		if (!$this->_socket || $this->_state != 'OPENED') {
			$time = microtime(true);
			$this->_spool[0]['Times']['open'][0] = $time;

			$errno = '';
			$errstr = '';
			$this->_socket = @fsockopen($this->_host, $this->_port, $errno, $errstr, 1.8);  // first try
			if (!$this->_socket) {

				if ($opentimeout >= 1.0)
					$this->_socket = @fsockopen($this->_host, $this->_port, $errno, $errstr, $opentimeout);
				if (!$this->_socket) {
					$this->_bad('Error(' . $errno . ') ' . $errstr . ', connection failed!');
					return;
				}
			}
			$this->_state = 'OPENED';
			//$aseco->console($this->_webaccess_str . 'connection opened!');

			// new socket connection: reset all pending request original values
			for ($i = 0; $i < count($this->_spool); $i++) {
				$this->_spool[$i]['State'] = 'OPEN';
				$this->_spool[$i]['DatasSent'] = 0;
				$this->_spool[$i]['Response'] = '';
				$this->_spool[$i]['Headers'] = array();
			}
			$this->_response = '';
			$this->_query_num = 0;
			$this->_query_time = time();
			$time = microtime(true);
			$this->_spool[0]['Times']['open'][1] = $time - $this->_spool[0]['Times']['open'][0];
		}
	}  // _open_socket


	// open the connection (if not already opened) and send
	function _open($opentimeout = 0.0, $waittimeout = 5.0) {
		global $aseco, $_web_access_retry_timeout_max;

		if (!isset($this->_spool[0]['State']))
			return false;
		$time = time();

		// if asynch, in error, then return false until timeout or if >max)
		if (!$this->_wait && $this->_state == 'BAD' &&
		    (($this->_bad_timeout > $_web_access_retry_timeout_max) ||
		    (($time - $this->_bad_time) < $this->_bad_timeout))) {
			//$aseco->console($this->_webaccess_str . 'wait to retry (' . ($time - $this->_bad_time) . ' / ' . $this->_bad_timeout . ')');
			return false;
		}

		// if the socket is probably in timeout, close it
		if ($this->_socket && $this->_state == 'OPENED' &&
		    ($this->_serv_keepalive_timeout <= ($time - $this->_query_time))) {
			//$aseco->console($this->_webaccess_str . 'timeout, close it!');
			$this->_state = 'CLOSED';
			@fclose($this->_socket);
			$this->_socket = null;
		}

		// if socket is not opened, open it
		if (!$this->_socket || $this->_state != 'OPENED')
			$this->_open_socket($opentimeout);

		// if socket is open, send data if possible
		if ($this->_socket) {
			$this->_read_time = microtime(true);

			// if wait (synchronous query) then go on all pending write/read until the last
			if ($this->_wait) {
				@stream_set_timeout($this->_socket, 0, 10000);  // timeout 10 ms

				while (isset($this->_spool[0]['State']) &&
				       ($this->_spool[0]['State'] == 'OPEN' ||
				        $this->_spool[0]['State'] == 'SEND' ||
				        $this->_spool[0]['State'] == 'RECEIVE')) {
					//echo 'State=' . $this->_spool[0]['State'] . " (" . count($this->_spool) . ")\n";
					if (!$this->_socket || $this->_state != 'OPENED')
						$this->_open_socket($opentimeout);

					if ($this->_spool[0]['State'] == 'OPEN') {
						$time = microtime(true);
						$this->_spool[0]['Times']['send'][0] = $time;
						$this->_send($waittimeout);
					}
					elseif ($this->_spool[0]['State'] == 'SEND')
						$this->_send($waittimeout);
					elseif ($this->_spool[0]['State'] == 'RECEIVE')
						$this->_receive($waittimeout*4);

					// if timeout then error
					if (($difftime = microtime(true) - $this->_read_time) > $waittimeout) {
						$this->_bad('Request timeout, in _open (' . round($difftime) . ' > ' . $waittimeout . 's) state=' . $this->_spool[0]['State']);
						return;
					}
				}
				if ($this->_socket)
					@stream_set_timeout($this->_socket, 0, 2000);  // timeout 2 ms
			}

			// else just do a send on the current
			elseif (isset($this->_spool[0]['State']) && $this->_spool[0]['State'] == 'OPEN') {
				@stream_set_timeout($this->_socket, 0, 2000);  // timeout 2 ms
				$this->_send($waittimeout);
			}
		}
	}  // _open


	function _send($waittimeout = 20) {

		if (!isset($this->_spool[0]['State']))
			return;

		// if OPEN then become SEND
		if ($this->_spool[0]['State'] == 'OPEN') {

			$this->_spool[0]['State'] = 'SEND';
			$time = microtime(true);
			$this->_spool[0]['Times']['send'][0] = $time;
			$this->_spool[0]['Response'] = '';
			$this->_spool[0]['Headers'] = array();

			// finish to prepare header and data to send
			$msg = $this->_spool[0]['HDatas'];
			if (!$this->_keepalive || ($this->_spool[0]['KeepaliveMinTimeout'] < 0) ||
			    ($this->_serv_keepalive_timeout < $this->_spool[0]['KeepaliveMinTimeout']) ||
			    ($this->_serv_keepalive_max <= ($this->_query_num + 2)) ||
			    ($this->_serv_keepalive_timeout <= (time() - $this->_query_time + 2))) {
				$msg .= "Connection: close\r\n";
				$this->_spool[0]['Close'] = true;
			}
			else {
				$msg .= 'Keep-Alive: timeout=' . $this->_keepalive_timeout . ', max=' . $this->_keepalive_max
				      . "\r\nConnection: Keep-Alive\r\n";
			}

			// add cookie header
			if (count($this->_cookies) > 0) {
				$cookie_msg = '';
				$sep = '';
				foreach ($this->_cookies as $name => $cookie) {
					if (!isset($cookie['path']) ||
					    strncmp($this->_spool[0]['Path'], $cookie['path'], strlen($cookie['path'])) == 0) {
						$cookie_msg .= $sep . $name . '=' . $cookie['Value'];
						$sep = '; ';
					}
				}
				if ($cookie_msg != '')
					$msg .= "Cookie: $cookie_msg\r\n";
			}

			$msg .= "\r\n";
			$msg .= $this->_spool[0]['QueryData'];
			$this->_spool[0]['Datas'] = $msg;
			$this->_spool[0]['DatasSize'] = strlen($msg);
			$this->_spool[0]['DatasSent'] = 0;

			//print_r($msg);
		}

		// if not SEND then stop
		if ($this->_spool[0]['State'] != 'SEND')
			return;

		do {
			$sent = @stream_socket_sendto($this->_socket,
			                              substr($this->_spool[0]['Datas'], $this->_spool[0]['DatasSent'],
			                                     ($this->_spool[0]['DatasSize'] - $this->_spool[0]['DatasSent'])));
			if ($sent == false) {

				$time = microtime(true);
				$this->_spool[0]['Times']['send'][1] = $time - $this->_spool[0]['Times']['send'][0];
				//var_dump($this->_spool[0]['Datas']);
				$this->_bad('Error(' . $errno . ') ' . $errstr . ', could not send data! ('
				            . $sent . ' / ' . ($this->_spool[0]['DatasSize'] - $this->_spool[0]['DatasSent']) . ', '
				            . $this->_spool[0]['DatasSent'] . ' / ' . $this->_spool[0]['DatasSize'] . ')');
				if ($this->_wait)
					return;
				break;

			} else {
				$this->_spool[0]['DatasSent'] += $sent;
				if ($this->_spool[0]['DatasSent'] >= $this->_spool[0]['DatasSize']) {
					// All is sent, prepare to receive the reply
					$this->_query_num++;
					$this->_query_time = time();

					$time = microtime(true);
					$this->_spool[0]['Times']['send'][1] = $time - $this->_spool[0]['Times']['send'][0];

					//@stream_set_blocking($this->_socket, 0);
					$this->_spool[0]['State'] = 'RECEIVE';
					$this->_spool[0]['Times']['receive'][0] = $time;
				}

				// if timeout then error
				elseif (($difftime = microtime(true) - $this->_read_time) > $waittimeout) {
					$this->_bad('Request timeout, in _send (' . round($difftime) . ' > ' . $waittimeout . 's)');
				}
			}

			// if not async-callback then continue until all is sent
		} while ($this->_wait && isset($this->_spool[0]['State']) && ($this->_spool[0]['State'] == 'SEND'));
	}  // _send


	function _receive($waittimeout = 40) {
		global $aseco, $_Webaccess_last_response;

		if (!$this->_socket || $this->_state != 'OPENED')
			return;

		$state = false;
		$time0 = microtime(true);
		$timeout = ($this->_wait) ? $waittimeout : 0;
		do {
			$r = array($this->_socket);
			$w = null;
			$e = null;
			$nb = @stream_select($r, $w, $e, $timeout);
			if ($nb === 0)
				$nb = count($r);

			while (!@feof($this->_socket) && $nb !== false && $nb > 0) {
				$timeout = 0;

				if (count($r) > 0) {
					$res = @stream_socket_recvfrom($this->_socket, 8192);

					if ($res == '') {  // should not happen habitually, but...
						break;
					} elseif ($res !== false) {
						$this->_response .= $res;
					}
					else {
						if (isset($this->_spool[0])) {
							$time = microtime(true);
							$this->_spool[0]['Times']['receive'][1] = $time - $this->_spool[0]['Times']['receive'][0];
						}
						$this->_bad('Error(' . $errno . ') ' . $errstr . ', could not read all data!');
						return;
					}
				}

				// if timeout then error
				if (($difftime = microtime(true) - $this->_read_time) > $waittimeout) {
					$this->_bad('Request timeout, in _receive (' . round($difftime) . ' > ' . $waittimeout . 's)');
					break;
				}

				$r = array($this->_socket);
				$w = null;
				$e = null;
				$nb = @stream_select($r, $w, $e, $timeout);
				if ($nb === 0)
					$nb = count($r);
			}

			if (isset($this->_spool[0]['Times']['receive'][2])) {
				$time = microtime(true);
				$this->_spool[0]['Times']['receive'][2] += ($time - $time0);
			}

			// get headers and full message
			$state = $this->_handleHeaders();
			//echo "receive9\n";
			//var_dump($state);

		} while ($this->_wait && $state === false && $this->_socket && !@feof($this->_socket));

		if (!isset($this->_spool[0]['State']) || $this->_spool[0]['State'] != 'RECEIVE') {
			// in case of (probably keepalive) connection closed by server
			if ($this->_socket && @feof($this->_socket)){
				//$aseco->console($this->_webaccess_str . 'Socket closed by server (' . $this->_host . ')');
				$this->_state = 'CLOSED';
				@fclose($this->_socket);
				$this->_socket = null;
			}
			return;
		}

		// terminated but incomplete! more than probably closed by server...
		if ($state === false && $this->_socket && @feof($this->_socket)) {
			$this->_state = 'CLOSED';
			if (isset($this->_spool[0])) {
				$time = microtime(true);
				$this->_spool[0]['State'] = 'OPEN';
				$this->_spool[0]['Times']['receive'][1] = $time - $this->_spool[0]['Times']['receive'][0];
			}
			if (strlen($this->_response) > 0)  // if not 0 sized then show error message
				$this->_bad('Error: closed with incomplete read: re-open socket and re-send! (' . strlen($this->_response) . ')');
			else
				$this->_bad('Closed by server when reading: re-open socket and re-send! (' . strlen($this->_response) . ')', false);

			$this->_spool[0]['Retries']++;
			if ($this->_spool[0]['Retries'] > 2) {
				// 3 tries failed, remove entry from spool
				$aseco->console($this->_webaccess_str . "failed {$this->_spool[0]['Retries']} times: skip current request");
				array_shift($this->_spool);
			}

			return;
		}

		// reply is complete :)
		if ($state === true) {
			$this->_bad_timeout = 0;  // reset error timeout

			$this->_spool[0]['Times']['receive'][1] = $time - $this->_spool[0]['Times']['receive'][0];
			$this->_spool[0]['State'] = 'DONE';

			// store http/xml response in global $_Webaccess_last_response for debugging use
			$_Webaccess_last_response = $this->_spool[0]['Response'];
			//debugPrint('Webaccess->_receive - Response', $_Webaccess_last_response);

			// call callback func
			$this->_callCallback();
			$this->_query_time = time();

			if (!$this->_keepalive || $this->_spool[0]['Close']) {
				//if ($this->_spool[0]['Close'])
				// $aseco->console($this->_webaccess_str . 'close connection (asked in headers)');
				$this->_state = 'CLOSED';
				@fclose($this->_socket);
				$this->_socket = null;
			}

			$this->infos();

			// request completed, remove it from spool!
			array_shift($this->_spool);
		}
	}  // _receive

	private function _callCallback($error = null) {

		// store optional error message
		if ($error !== null)
			$this->_spool[0]['Response']['Error'] = $error;

		// call callback func
		if (isset($this->_spool[0]['Callback'])) {
			$callbackinfo = $this->_spool[0]['Callback'];
			if (isset($callbackinfo[0]) && is_callable($callbackinfo[0])) {
				$callback_func = $callbackinfo[0];
				$callbackinfo[0] = $this->_spool[0]['Response'];
				call_user_func_array($callback_func, $callbackinfo);
			}
		}
	}

	private function _handleHeaders() {
		global $aseco, $_wa_header_separator, $_wa_header_multi;

		if (!isset($this->_spool[0]['State']))
			return false;

		if (strlen($this->_response) < 8)  // not enough data, continue read
			return false;
		if (strncmp($this->_response, 'HTTP/', 5) != 0) {  // not HTTP!
			$this->_bad("Error, not HTTP response ! **********\n" . substr($this->_response, 0, 300) . "\n***************\n");
			return null;
		}

		// separate headers and data
		$datas = explode("\r\n\r\n", $this->_response, 2);
		if (count($datas) < 2) {
			$datas = explode("\n\n", $this->_response, 2);
			if (count($datas) < 2) {
				$datas = explode("\r\r", $this->_response, 2);
				if (count($datas) < 2)
					return false;  // not complete headers, continue read
			}
		}

		// get headers if not done on previous read
		if (!isset($this->_spool[0]['Headers']['Command'][0])) {
			// separate headers
			//echo "Get Headers! (" . strlen($datas[0]) . ")\n";

			$headers = array();
			$heads = explode("\n", str_replace("\r", "\n", str_replace("\r\n", "\n", $datas[0])));
			if (count($heads) < 2) {
				$this->_bad("Error, uncomplete headers! **********\n" . $datas[0] . "\n***************\n");
				return null;
			}

			$headers['Command'] = explode(' ', $heads[0], 3);

			for ($i = 1; $i < count($heads); $i++) {
				$header = explode(':', $heads[$i], 2);
				if (count($header) > 1) {
					$headername = strtolower(trim($header[0]));
					if (isset($_wa_header_separator[$headername]))
						$sep = $_wa_header_separator[$headername];
					else
						$sep = ',';
					if (isset($_wa_header_multi[$headername]) && $_wa_header_multi[$headername]) {
						if (!isset($headers[$headername]))
							$headers[$headername] = array();
						$headers[$headername][] = explode($sep, trim($header[1]));
					} else
						$headers[$headername] = explode($sep, trim($header[1]));
				}
			}

			if (isset($headers['content-length'][0]))
				$headers['content-length'][0] += 0;  // convert to int

			$this->_spool[0]['Headers'] = $headers;

			// add header specific info in case of Dedimania reply
			if (isset($headers['server'][0]))
				$this->_webaccess_str = 'Webaccess (' . $this->_host . ':' . $this->_port .'/'. $headers['server'][0] . '): ';
		}
		else {
			$headers = &$this->_spool[0]['Headers'];
			//echo "Previous Headers! (" . strlen($datas[0]) . ")\n";
		}

		// get real message
		$datasize = strlen($datas[1]);
		if (isset($headers['content-length'][0]) && $headers['content-length'][0] >= 0) {
			//echo 'mess_size0=' . strlen($datas[1]) . "\n";

			if ($headers['content-length'][0] > $datasize)  // incomplete message
				return false;

			elseif ($headers['content-length'][0] < $datasize) {
				$message = substr($datas[1], 0, $headers['content-length'][0]);
				// remaining buffer for next reply
				$this->_response = substr($datas[1], $headers['content-length'][0]);
			}
			else {
				$message = $datas[1];
				$this->_response = '';
			}
			$this->_spool[0]['ResponseSize'] = strlen($datas[0]) + 4 + $headers['content-length'][0];
		}

		// get real message when reply is chunked
		elseif (isset($headers['transfer-encoding'][0]) && $headers['transfer-encoding'][0] == 'chunked') {

			// get chunk size and make message with chunks data
			$size = -1;
			$chunkpos = 0;
			if (($datapos = strpos($datas[1], "\r\n", $chunkpos)) !== false) {
				$message = '';
				$chunk = explode(';', substr($datas[1], $chunkpos, $datapos - $chunkpos));
				$size = hexdec($chunk[0]);
				//debugPrint("Webaccess->Response - chunk - $chunkpos, $datapos, $size (" . strlen($datas[1]) . ")", $chunk);
				while ($size > 0) {
					if ($datapos + 2 + $size > $datasize)  // incomplete message
						return false;
					$message .= substr($datas[1], $datapos + 2, $size);
					$chunkpos = $datapos + 2 + $size + 2;
					if (($datapos = strpos($datas[1], "\r\n", $chunkpos)) !== false) {
						$chunk = explode(';', substr($datas[1], $chunkpos, $datapos - $chunkpos));
						$size = hexdec($chunk[0]);
					} else
						$size = -1;
					//debugPrint("Webaccess->Response - chunk - $chunkpos, $datapos, $size (" . strlen($datas[1]) . ")", $chunk);
				}

			}
			if ($size < 0)  // error bad size or incomplete message
				return false;

			if (strpos($datas[1], "\r\n\r\n", $chunkpos) === false)  // incomplete message: end is missing
				return false;

			// store complete message size
			$msize = strlen($message);
			$headers['transfer-encoding'][1] = 'total_size=' . $msize;  // add message size after 'chunked' for information
			$this->_spool[0]['ResponseSize'] = strlen($datas[0]) + 4 + $msize;

			// after the message itself...
			$message_end = explode("\r\n\r\n", substr($datas[1], $chunkpos), 2);

			// add end headers if any
			$heads = explode("\n", str_replace("\r", "\n", str_replace("\r\n", "\n", $message_end[0])));
			for ($i = 1; $i < count($heads); $i++) {
				$header = explode(':', $heads[$i], 2);
				if (count($header) > 1) {
					$headername = strtolower(trim($header[0]));
					if (isset($_wa_header_separator[$headername]))
						$sep = $_wa_header_separator[$headername];
					else
						$sep = ',';
					if (isset($_wa_header_multi[$headername]) && $_wa_header_multi[$headername]) {
						if (!isset($headers[$headername]))
							$headers[$headername] = array();
						$headers[$headername][] = explode($sep, trim($header[1]));
					} else
						$headers[$headername] = explode($sep, trim($header[1]));
				}
			}
			$this->_spool[0]['Headers'] = $headers;

			// remaining buffer for next reply
			if (isset($message_end[1]) && strlen($message_end[1]) > 0) {
				$this->_response = $message_end[1];
			}
			else
				$this->_response = '';
		}
		// no content-length and not chunked!
		else {
			$this->_bad("Error, bad http, no content-length and not chunked! **********\n" . $datas[0] . "\n***************\n");
			return null;
		}

		//echo 'mess_size1=' . strlen($message) . "\n";

		// if Content-Encoding: gzip  or  Content-Encoding: deflate
		if (isset($headers['content-encoding'][0])) {
			if ($headers['content-encoding'][0] == 'gzip')
				$message = @gzdecode($message);
			elseif ($headers['content-encoding'][0] == 'deflate')
				$message = @gzinflate($message);
		}

		// if Accept-Encoding: gzip or deflate
		if ($this->_compress_request == 'accept' && isset($headers['accept-encoding'][0])) {
			foreach ($headers['accept-encoding'] as $comp) {
				$comp = trim($comp);
				if ($comp == 'gzip' && function_exists('gzencode')) {
					$this->_compress_request = 'gzip';
					break;
				}
				elseif ($comp == 'deflate' && function_exists('gzdeflate')) {
					$this->_compress_request = 'deflate';
					break;
				}
			}
			if ($this->_compress_request == 'accept')
				$this->_compress_request = false;

			$aseco->console($this->_webaccess_str . 'send: ' . ($this->_compress_request === false ? 'no compression' : $this->_compress_request)
							. ', receive: ' . (isset($headers['content-encoding'][0]) ? $headers['content-encoding'][0] : 'no compression'));
		}

		// get cookies values
		if (isset($headers['set-cookie'])) {
			foreach ($headers['set-cookie'] as $cookie) {
				$cook = explode('=', $cookie[0], 2);
				if (count($cook) > 1) {
					// set main cookie value
					$cookname = trim($cook[0]);
					if (!isset($this->_cookies[$cookname]))
						$this->_cookies[$cookname] = array();
					$this->_cookies[$cookname]['Value'] = trim($cook[1]);

					// set cookie options
					for ($i = 1; $i < count($cookie); $i++) {
						$cook = explode('=', $cookie[$i], 2);
						$cookarg = strtolower(trim($cook[0]));
						if (isset($cook[1]))
							$this->_cookies[$cookname][$cookarg] = trim($cook[1]);
					}
				}
			}
			//debugPrint('SET-COOKIES: ', $headers['set-cookie']);
			//debugPrint('STORED COOKIES: ', $this->_cookies);
		}

		// if the server reply ask to close, then close
		if (!isset($headers['connection'][0]) || $headers['connection'][0] == 'close') {
			//if (!$this->_spool[0]['Close'])
			// $aseco->console($this->_webaccess_str . 'server ask to close connection');
			$this->_spool[0]['Close'] = true;
		}

		// verify server keepalive value and use them if lower
		if (isset($headers['keep-alive'])) {
			$kasize = count($headers['keep-alive']);
			for ($i = 0; $i < $kasize; $i++) {
				$keep = explode('=', $headers['keep-alive'][$i], 2);
				if (count($keep) > 1)
					$headers['keep-alive'][trim(strtolower($keep[0]))] = intval(trim($keep[1]));
			}
			if (isset($headers['keep-alive']['timeout']))
				$this->_serv_keepalive_timeout = $headers['keep-alive']['timeout'];
			if (isset($headers['keep-alive']['max']))
				$this->_serv_keepalive_max = $headers['keep-alive']['max'];
			//$aseco->console($this->_webaccess_str . 'max=' . $this->_serv_keepalive_max . ', timeout=' . $this->_serv_keepalive_timeout . "\n");
		}

		// store complete reply message for the request
		$this->_spool[0]['Response'] = array('Code' => intval($headers['Command'][1]),
		                                     'Reason' => $headers['Command'][2],
		                                     'Headers' => $headers,
		                                     'Message' => $message
		                                    );
		//echo 'mess_size2=' . strlen($message) . "\n";
		return true;
	}  // _handleHeaders


	function infos() {
		global $aseco;

		$size = (isset($this->_spool[0]['Response']['Message'])) ? strlen($this->_spool[0]['Response']['Message']) : 0;
		$msg = $this->_webaccess_str
			. sprintf('[%s,%s]: %0.3f / %0.3f / %0.3f (%0.3f) / %d [%d,%d,%d]',
			          $this->_state, $this->_spool[0]['State'],
			          $this->_spool[0]['Times']['open'][1],
			          $this->_spool[0]['Times']['send'][1],
			          $this->_spool[0]['Times']['receive'][1],
			          $this->_spool[0]['Times']['receive'][2],
			          $this->_query_num, $this->_spool[0]['DatasSize'],
			          $size, $this->_spool[0]['ResponseSize']);
		//$aseco->console($msg);
	}  // infos
}  // class WebaccessUrl


// use: list($host, $port, $path) = getHostPortPath($url);
function getHostPortPath($url) {

	$http_pos = strpos($url, 'http://');
	if ($http_pos !== false) {
		$script = explode('/', substr($url, $http_pos + 7), 2);
		if (isset($script[1]))
			$path = '/' . $script[1];
		else
			$path = '/';
		$serv = explode(':', $script[0], 2);
		$host = $serv[0];
		if (isset($serv[1]))
			$port = (int)$serv[1];
		else
			$port = 80;
		if (strlen($host) > 2)
			return array($host, $port, $path);
	}
	return array(false, false, false);
}  // getHostPortPath


// gzdecode() workaround
if (!function_exists('gzdecode') && function_exists('gzinflate')) {

	function gzdecode($data) {

		$len = strlen($data);
		if ($len < 18 || strcmp(substr($data, 0, 2), "\x1f\x8b")) {
			return null;  // Not GZIP format (See RFC 1952)
		}
		$method = ord(substr($data, 2, 1));  // Compression method
		$flags  = ord(substr($data, 3, 1));  // Flags
		if ($flags & 31 != $flags) {
			// Reserved bits are set -- NOT ALLOWED by RFC 1952
			return null;
		}
		// NOTE: $mtime may be negative (PHP integer limitations)
		$mtime = unpack('V', substr($data, 4, 4));
		$mtime = $mtime[1];
		$xfl = substr($data, 8, 1);
		$os  = substr($data, 8, 1);
		$headerlen = 10;
		$extralen  = 0;
		$extra     = '';
		if ($flags & 4) {
			// 2-byte length prefixed EXTRA data in header
			if ($len - $headerlen - 2 < 8) {
				return false;  // Invalid format
			}
			$extralen = unpack('v', substr($data, 8, 2));
			$extralen = $extralen[1];
			if ($len - $headerlen - 2 - $extralen < 8) {
				return false;  // Invalid format
			}
			$extra = substr($data, 10, $extralen);
			$headerlen += $extralen + 2;
		}

		$filenamelen = 0;
		$filename = '';
		if ($flags & 8) {
			// C-style string file NAME data in header
			if ($len - $headerlen - 1 < 8) {
				return false;  // Invalid format
			}
			$filenamelen = strpos(substr($data, 8 + $extralen), chr(0));
			if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
				return false;  // Invalid format
			}
			$filename = substr($data, $headerlen, $filenamelen);
			$headerlen += $filenamelen + 1;
		}

		$commentlen = 0;
		$comment = '';
		if ($flags & 16) {
			// C-style string COMMENT data in header
			if ($len - $headerlen - 1 < 8) {
				return false;  // Invalid format
			}
			$commentlen = strpos(substr($data, 8 + $extralen + $filenamelen), chr(0));
			if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
				return false;  // Invalid header format
			}
			$comment = substr($data, $headerlen, $commentlen);
			$headerlen += $commentlen + 1;
		}

		$headercrc = '';
		if ($flags & 1) {
			// 2-bytes (lowest order) of CRC32 on header present
			if ($len - $headerlen - 2 < 8) {
				return false;  // Invalid format
			}
			$calccrc = crc32(substr($data, 0, $headerlen)) & 0xffff;
			$headercrc = unpack('v', substr($data, $headerlen, 2));
			$headercrc = $headercrc[1];
			if ($headercrc != $calccrc) {
				return false;  // Bad header CRC
			}
			$headerlen += 2;
		}

		// GZIP FOOTER - These be negative due to PHP's limitations
		$datacrc = unpack('V', substr($data, -8, 4));
		$datacrc = $datacrc[1];
		$isize = unpack('V', substr($data, -4));
		$isize = $isize[1];

		// Perform the decompression:
		$bodylen = $len - $headerlen - 8;
		if ($bodylen < 1) {
			// This should never happen - IMPLEMENTATION BUG!
			return null;
		}
		$body = substr($data, $headerlen, $bodylen);
		$data = '';
		if ($bodylen > 0) {
			switch ($method) {
			case 8:
				// Currently the only supported compression method:
				$data = gzinflate($body);
				break;
			default:
				// Unknown compression method
				return false;
			}
		} else {
			// I'm not sure if zero-byte body content is allowed.
			// Allow it for now...  Do nothing...
		}

		// Verify decompressed size and CRC32:
		// NOTE: This may fail with large data sizes depending on how
		//       PHP's integer limitations affect strlen() since $isize
		//       may be negative for large sizes
		if ($isize != strlen($data) || crc32($data) != $datacrc) {
			// Bad format!  Length or CRC doesn't match!
			return false;
		}
		return $data;
	}  // gzdecode
}
?>
