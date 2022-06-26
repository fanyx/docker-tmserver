<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

////////////////////////////////////////////////////////////////
//
// File:      XMLRPC DB ACCESS 2.1
// Date:      28.05.2009
// Author:    Gilles Masson
// Updated:   Xymph
//
////////////////////////////////////////////////////////////////

class XmlrpcDB {

	//-----------------------------
	// Fields
	//-----------------------------

	var $_webaccess;
	var $_url;
	var $_server;
	var $_callbacks;
	var $_requests;
	var $_auth_cb;
	var $_bad;
	var $_bad_time;
	var $_debug;

	//-----------------------------
	// Methods
	//-----------------------------

	function XmlrpcDB($webaccess, $url, $game, $login, $password, $tool, $version, $nation, $packmask = '') {

		$this->_debug = 0;  // max debug level = 3
		$this->_webaccess = $webaccess;
		$this->_url = $url;
		$this->_server = array('Game' => $game,
		                       'Login' => $login,
		                       'Password' => $password,
		                       'Tool' => $tool,
		                       'Version' => $version,
		                       'Nation' => $nation,
		                       'Packmask' => $packmask,
		                       'PlayersGame' => true
		                      );
		$this->_auth_cb = array('xmlrpc_auth_cb');

		$this->_bad = false;
		$this->_bad_time = -1;
		// in case webaccess URL connection was previously in error, ask to retry
		$this->_webaccess->retry($this->_url);

		// prepare to add requests
		$this->_initRequest();
	}  // XmlrpcDB

	// change the packmask value
	function setPackmask($packmask = '') {

		$this->_server['Packmask'] = $packmask;
	}  // setPackmask

	// is the connection in recurrent error?
	function isBad() {

		return $this->_bad;
	}  // isBad

	// get time since the error state was set
	function badTime() {

		return (time() - $this->_bad_time);
	}  // badTime

	// stop the bad state: will try again at next RequestWait(),
	// sendRequestsWait() or sendRequests()
	function retry() {

		$this->_bad = false;
		$this->_bad_time = -1;
		// set webaccess object to retry on that URL too
		$this->_webaccess->retry($this->_url);
	}  // retry

	// clear all requests, and get them if asked
	function clearRequests($get_requests = false) {

		if ($get_requests) {
			$return = array($_requests, $_callbacks);
			$this->_initRequest();
			return $return;
		}
		$this->_initRequest();
	}  // clearRequests

	// add a request
	function addRequest($callback, $method) {

		$args = func_get_args();
		$callback = array_shift($args);
		$method = array_shift($args);
		return $this->addRequestArray($callback, $method, $args);
	}  // addRequest

	// add a request
	function addRequestArray($callback, $method, $args) {

		$this->_callbacks[] = $callback;
		$this->_requests[] = array('methodName' => $method, 'params' => $args);
		return count($this->_requests) - 1;
	}  // addRequestArray

	// send added requests, callbacks will be called when response come
	function sendRequests() {
		global $aseco;

		if (count($this->_callbacks) > 1) {
			$this->addRequest(null, 'dedimania.WarningsAndTTR');
			$webdatas = $this->_makeXMLdatas();
			$response = $this->_webaccess->request($this->_url,
					array( array($this, '_callCB'), $this->_callbacks, $this->_requests),
					$webdatas, true);
			$this->_initRequest();
			if ($response === false) {
				if (!$this->_bad) {
					$this->_bad = true;
					$this->_bad_time = time();
				}
				if ($this->_debug > 2)
					$aseco->console_text('XmlrpcDB->sendRequests - this' . CRLF . print_r($this, true));
				return false;
			}
		}
		return true;
	}  // sendRequests

	// send added requests, wait response, then call callbacks
	function sendRequestsWait() {
		global $aseco;

		if (count($this->_callbacks) > 1) {
			$this->addRequest(null, 'dedimania.WarningsAndTTR');
			$webdatas = $this->_makeXMLdatas();
			$response = $this->_webaccess->request($this->_url,
					null, $webdatas, true);
			if ($response === false) {
				if (!$this->_bad) {
					$this->_bad = true;
					$this->_bad_time = time();
				}
				if ($this->_debug > 0)
					$aseco->console_text('XmlrpcDB->sendRequestsWait - this' . CRLF . print_r($this, true));
				$this->_initRequest();
				return false;
			} else {
				$this->_callCB($response, $this->_callbacks, $this->_requests);
				$this->_initRequest();
			}
		}
		return true;
	}  // sendRequestsWait

	// send a request, wait response, and return the response
	function RequestWait($method) {

		$args = func_get_args();
		$method = array_shift($args);
		return $this->RequestWaitArray($method, $args);
	}  // RequestWait

	// send a request, wait response, and return the response
	function RequestWaitArray($method, $args) {
		global $aseco;

		if ($this->sendRequestsWait() === false) {
			if (!$this->_bad) {
				$this->_bad = true;
				$this->_bad_time = time();
			}
			return false;
		}

		$reqnum = $this->addRequestArray(null, $method, $args);

		$this->addRequest(null, 'dedimania.WarningsAndTTR');
		$webdatas = $this->_makeXMLdatas();
		$response = $this->_webaccess->request($this->_url, null, $webdatas, true);
		if (isset($response['Message']) && is_string($response['Message'])) {
			if ($this->_debug > 1)
				$aseco->console_text('XmlrpcDB->RequestWaitArray() - response[Message]' . CRLF . print_r($response['Message'], true));

			$xmlrpc_message = new IXR_Message($response['Message']);
			if ($xmlrpc_message->parse() && $xmlrpc_message->messageType != 'fault') {
				if ($this->_debug > 1) {
					$aseco->console_text('XmlrpcDB->RequestWaitArray() - message' . CRLF . print_r($xmlrpc_message->message, true));
					$aseco->console_text('XmlrpcDB->RequestWaitArray() - params' . CRLF . print_r($xmlrpc_message->params, true));
				}

				//$datas = array('methodName' => $xmlrpc_message->methodName, 'params' => $xmlrpc_message->params);
				$datas = $this->_makeResponseDatas($xmlrpc_message->methodName, $xmlrpc_message->params, $this->_requests);
			} else {
				if ($this->_debug > 0)
					$aseco->console_text('XmlrpcDB->RequestWaitArray() - message fault' . CRLF . print_r($xmlrpc_message->message, true));
				$datas = array();
			}
		} else {
			$datas = array();
		}

		if ($this->_debug > 0)
			$aseco->console_text('XmlrpcDB->RequestWaitArray() - datas' . CRLF . print_r($datas, true));
		if (isset($datas['params']) && isset($datas['params'][$reqnum])) {
			$response['Data'] = $datas['params'][$reqnum];
			$param_end = end($datas['params']);
			if (isset($param_end['globalTTR']) && !isset($response['Data']['globalTTR']))
				$response['Data']['globalTTR'] = $param_end['globalTTR'];
		} else {
			$response['Data'] = $datas;
		}
		if ($this->_debug > 0)
			$aseco->console_text('XmlrpcDB->RequestWaitArray() - response[Data]' . CRLF . print_r($response['Data'], true));

		$this->_initRequest();
		return $response;
	}  // RequestWaitArray

	// init the request and callback array
	function _initRequest() {

		$this->_callbacks = array();
		$this->_requests = array();
		$this->addRequest($this->_auth_cb, 'dedimania.Authenticate', $this->_server);
	}  // _initRequest

	// make the xmlrpc string, encode it in base64, and pass it as value
	// to xmlrpc URL post parameter
	function _makeXMLdatas() {
		global $aseco;

		$xmlrpc_request = new IXR_RequestStd('system.multicall', $this->_requests);
		if ($this->_debug > 1)
			$aseco->console_text('XmlrpcDB->_makeXMLdatas() - getXml()' . CRLF . print_r($xmlrpc_request->getXml(), true));
		return $xmlrpc_request->getXml();
	}  // _makeXMLdatas

	function _callCB($response, $callbacks, $requests) {
		global $aseco;

		$globalTTR = 0;
		if (isset($response['Message']) && is_string($response['Message'])) {
			$xmlrpc_message = new IXR_Message($response['Message']);
			if ($xmlrpc_message->parse() && $xmlrpc_message->messageType != 'fault') {
				if ($this->_debug > 1)
					$aseco->console_text('XmlrpcDB->_callCB() - message' . CRLF . print_r($xmlrpc_message->message, true));

				//$datas = array('methodName' => $xmlrpc_message->methodName, 'params' => $xmlrpc_message->params);
				$datas = $this->_makeResponseDatas($xmlrpc_message->methodName, $xmlrpc_message->params, $requests);

				if (isset($datas['params']) && is_array($datas['params'])) {
					$param_end = end($datas['params']);
					if (isset($param_end['globalTTR']))
						$globalTTR = $param_end['globalTTR'];
				} else {
					if ($this->_debug > 0)
						$aseco->console_text('XmlrpcDB->_callCB() - message fault' . CRLF . print_r($xmlrpc_message->message, true));
					$datas = array();
				}
			} else {
				if ($this->_debug > 0)
					$aseco->console_text('XmlrpcDB->_callCB() - message fault' . CRLF . print_r($xmlrpc_message->message, true));
				$datas = array();
			}
		} else {
			if (!isset($response['Error']) ||
			    (strpos($response['Error'], 'connection failed') === false && strpos($response['Error'], 'Request timeout') === false)) {
				$infos = array('Url'=>$this->_url, 'Requests'=>$requests, 'Callbacks'=>$callbacks, 'Response'=>$response);
				$serinfos = serialize($infos);
				$aseco->console_text('XmlrpcDB->_callCB() - no response message:' . CRLF . $serinfos);
			}
			$datas = array();
		}

		for ($i = 0; $i < count($callbacks); $i++) {
			if ($callbacks[$i] != null) {
				$callback = $callbacks[$i][0];
				if (isset($datas['params']) && isset($datas['params'][$i])) {
					$response['Data'] = $datas['params'][$i];
					if (!isset($response['Data']['globalTTR']))
						$response['Data']['globalTTR'] = $globalTTR;
				} else {
					$response['Data'] = $datas;
				}
				$callbacks[$i][0] = $response;
				call_user_func_array($callback, $callbacks[$i]);
			}
		}
	}  // _callCB

	// build the datas array from XAseco or Dedimania server
	//   remove the first array level into params if needed
	//   add methodResponse name if needed
	//   rename sub responses params array from [0] to ['params'] if needed
	function _makeResponseDatas($methodname, $params, $requests) {
		global $aseco;

		if (is_array($params) && count($params) == 1 && is_array($params[0]))
			$params = $params[0];
		if ($this->_debug > 2)
			$aseco->console_text('XmlrpcDB->_makeResponseDatas() - requests' . CRLF . print_r($requests, true));
		if ($this->_debug > 1)
			$aseco->console_text('XmlrpcDB->_makeResponseDatas() - params' . CRLF . print_r($params, true));

		if (is_array($params) && is_array($params[0]) && !isset($params[0]['methodResponse'])) {
			$params2 = array();
			foreach ($params as $key => $param) {
				$errors = null;
				if (isset($param['faultCode'])) {
					$errors[] = array('Code' => $param['faultCode'], 'Message' => $param['faultString']);
				}

				if (isset($requests[$key]['methodName']))
					$methodresponse = $requests[$key]['methodName'];
				else
					$methodresponse = 'Unknown';

				$ttr = 0.000001;

				if (isset($param[0]))
					$param = $param[0];
				else
					$param = array();

				$params2[$key] = array('methodResponse' => $methodresponse,
				                       'params' => $param,
				                       'errors' => $errors,
				                       'TTR' => $ttr,
				                       'globalTTR' => $ttr
				                      );

				if ($methodresponse == 'dedimania.WarningsAndTTR') {
					if ($this->_debug > 1) {
						$aseco->console_text('XmlrpcDB->_makeResponseDatas() - param' . CRLF . print_r($param, true));
						$aseco->console_text('XmlrpcDB->_makeResponseDatas() - params2' . CRLF . print_r($params2, true));
					}
					$globalTTR = $param['globalTTR'];
					foreach ($param['methods'] as $key3 => $param3) {
						$key2 = 0;
						while ($key2 < count($params2) && $params2[$key2]['methodResponse'] != $param3['methodName']) {
							$params2[$key2]['globalTTR'] = $globalTTR;
							$key2++;
						}
						if ($this->_debug > 1)
							$aseco->console_text("XmlrpcDB->_makeResponseDatas() - key2=$key2 - key3=$key3 - param3" . CRLF . print_r($param3, true));
						if ($key2 < count($params2)) {
							$params2[$key2]['errors'] = $param3['errors'];
							$params2[$key2]['TTR'] = $param3['TTR'];
							$params2[$key2]['globalTTR'] = $globalTTR;
						}
					}
				}
			}
			if ($this->_debug > 1)
				$aseco->console_text('XmlrpcDB->_makeResponseDatas() - params2' . CRLF . print_r($params2, true));
			return array('methodName' => $methodname, 'params' => $params2);

		} else {
			return array('methodName' => $methodname, 'params' => $params);
		}
	}  // _makeResponseDatas
}  // class XmlrpcDB


// Dedimania.Authenticate callback used to catch errors
function xmlrpc_auth_cb($response) {
	global $aseco;

	if (isset($response['Data']['errors']) && $response['Data']['errors'] != '') {
		if (is_array($response['Data']['errors']))
			$aseco->console('xmlrpc_auth_cb() - response[Data][errors]' . CRLF . print_r($response['Data']['errors'], true));
		else
			$aseco->console('xmlrpc_auth_cb() - response[Data][errors]' . CRLF . print_r($response, true));
	}
}  // xmlrpc_auth_cb
?>
