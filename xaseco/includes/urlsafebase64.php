<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

// Alternative base64 url compatible decode and encode functions
// Written by Ferdinand Dosser
// Updated by Xymph

// urlsafe base64 alternative encode
function urlsafe_base64_encode($string) {

	$data = base64_encode($string);
	$data = str_replace(array('+','/','='), array('-','_',''), $data);
	return $data;
}  // urlsafe_base64_encode

// urlsafe base64 alternative decode
function urlsafe_base64_decode($string) {

	$data = str_replace(array('-','_'), array('+','/'), $string);
	$mod4 = strlen($data) % 4;
	if ($mod4) {
		$data .= substr('====', $mod4);
	}
	return base64_decode($data);
}  // urlsafe_base64_decode
?>
