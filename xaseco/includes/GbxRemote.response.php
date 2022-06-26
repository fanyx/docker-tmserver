<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

////////////////////////////////////////////////////////////////
//
// File:      Adds to GbxRemote.inc.php
// Date:      10.07.2007
// Author:    Gilles Masson
// Updated:   Xymph
//
// xmlrpc_request() or RequestStd class to build a methodCall request (xml-rpc string)
// xmlrpc_response() or ResponseStd class to build a method response (xml-rpc string)
// xmlrpc_error() to build an error response (xml-rpc string)
// rpc_method() to build a method array (for method call in a multicall)
// rpc_response() to build a response array (for method response in a multicall)
// rpc_error() to build an error array (for method error in a multicall)
// xml_decode_rpc() to build a data array from a xml-rpc string


// -----------------------------------------------------------------------
// Build a methodCall request in xml-rpc format from methodname and array params
// (methodname + array_params ==> xml-rpc string)
// -----------------------------------------------------------------------
// $method is the method name.
// $params should be an array containing the method parameters.
// For system.multicall, there should be one parameter, which is an array
//     containing the methods structs {'methodName':string, 'params':array}
// -----------------------------------------------------------------------

function xmlrpc_request($method, $params = null) {

	// in case the first level array was forgotten in a multicall then add it...
	if ($method == 'system.multicall' && isset($params[0]['methodName']))
		$params = array($params);

	$xml = '<?xml version="1.0" encoding="UTF-8" ?>'
	       . "\n<methodCall>\n<methodName>$method</methodName>\n<params>\n";

	if (is_array($params)) {
		foreach ($params as $param) {
			$xml .= "<param>\n<value>";
			$v = new IXR_Value($param);
			$xml .= $v->getXml();
			$xml .= "</value>\n</param>\n";
		}
	}
	$xml .= "</params>\n</methodCall>";
	return $xml;
}


// -----------------------------------------------------------------------
// Build a methodCall response in xml-rpc format from arrays args
// (array_args ==> xml-rpc string)
// -----------------------------------------------------------------------
// $args should be an array containing the response
// -----------------------------------------------------------------------

function xmlrpc_response($args = null) {

	$xml = '<?xml version="1.0" encoding="UTF-8" ?>'
	       . "\n<methodResponse>\n<params>\n<param>\n";

	if ($args !== null) {
		$xml .= '<value>';
		$v = new IXR_Value($args);
		$xml .= $v->getXml();
		$xml .= "</value>\n";
	}
	$xml .= "</param>\n</params>\n</methodResponse>";
	return $xml;
}


// -----------------------------------------------------------------------
// Build an error response in xml-rpc format
// (error code + message ==> xml-rpc string)
// -----------------------------------------------------------------------
// $code is the error code
// $message is the error string
// -----------------------------------------------------------------------

function xmlrpc_error($code, $message) {

	$xml = '<?xml version="1.0" encoding="UTF-8" ?>'
	       . "\n<methodResponse>\n<fault>\n"
	       . "<value><struct>\n"
	       . "<member><name>faultCode</name><value><int>$code</int></value>\n</member>\n"
	       . "<member><name>faultString</name><value><string>$message</string></value></member>\n"
	       . "</struct></value>\n"
	       . "</fault>\n</methodResponse>";
	return $xml;
}


// -----------------------------------------------------------------------
// Build a method struct (array) usable for a method call in a multicall
// (method name + params array ==> method struct array
// -----------------------------------------------------------------------
// $name is the method name
// $params is the params array
// -----------------------------------------------------------------------

function rpc_method($name, $params = null) {

	if (!is_array($params))
		$params = array();
	return array('methodName' => $name, 'params' => $params);
}


// -----------------------------------------------------------------------
// Build a response struct (array) usable as a reply for a method in a multicall
// (method name + params array ==> method struct array
// -----------------------------------------------------------------------
// $params is the methode response array
// -----------------------------------------------------------------------

function rpc_response($response = null) {

	if (!is_array($response))
		$response = array();
	return array($response);
}


// -----------------------------------------------------------------------
// Build an error struct (array) usable as an error for a method in a multicall
// (error code + message ==> error struct array)
// -----------------------------------------------------------------------
// $code is the error code
// $message is the error string
// -----------------------------------------------------------------------

function rpc_error($code, $message) {

	return array('faultCode' => $code, 'faultString' => $message);
}


// -----------------------------------------------------------------------
// Build a data array from a text xml-rpc
// (xml-rpc string ==> array)
// -----------------------------------------------------------------------
// $xml is the xml-rpc text to decode
// If there is an error then null is returned, you can then look infos
//   in global $_xmlrpc_decode_obj
// -----------------------------------------------------------------------

function xml_decode_rpc($xml) {
	global $_xmlrpc_decode_obj;

	$_xmlrpc_decode_obj = new IXR_Message($xml);
	if (!$_xmlrpc_decode_obj->parse() || !isset($_xmlrpc_decode_obj->params[0]))
		return null;
	return $_xmlrpc_decode_obj->params[0];
}


// -----------------------------------------------------------------------
// use this class constructor to build a methodCall request
// -----------------------------------------------------------------------

class IXR_RequestStd {
	var $method;
	var $params;
	var $xml;

	// see xmlrpc_request
	function IXR_RequestStd($method, $params = null) {

		$this->method = $method;
		// in case the first level array was forgotten in a multicall then add it...
		if ($method == 'system.multicall' && isset($params[0]['methodName']))
			$this->params = array($params);
		else
			$this->params = $params;

		$this->xml = xmlrpc_request($this->method, $this->params);
	}

	function getLength() {

		return strlen($this->xml);
	}

	function getXml() {

		return $this->xml;
	}
}


// -----------------------------------------------------------------------
// use this class constructor to build a methodCall response
// -----------------------------------------------------------------------
class IXR_ResponseStd {
	var $args;
	var $xml;

	// see xmlrpc_response
	function IXR_ResponseStd($args) {

		$this->args = $args;
		$this->xml = xmlrpc_response($this->args);
	}

	function getLength() {

		return strlen($this->xml);
	}

	function getXml() {

		return $this->xml;
	}
}
?>
