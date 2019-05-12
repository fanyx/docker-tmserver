<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Ogg_Comments - Extract comments (ID3 tags) from .ogg files
 * Created by Xymph <tm@gamers.org>
 * Derived from Ogg.class.php v1.3e by Nicolas Ricquemaque <f1iqf@hotmail.com>
 * For more info see: http://opensource.grisambre.net/ogg/
 * Ogg format: http://en.wikipedia.org/wiki/Ogg
 * Comments  : http://www.xiph.org/vorbis/doc/v-comment.html
 *
 * v1.1: Improved get_1block URL parsing; added User-Agent to the GET request
 * v1.0: Initial release
 */

define('BLOCKSIZE', 512);

class Ogg_Comments {

	public $comments = array();

	/**
	 * Fetches all comments from a .ogg local file or URL
	 *
	 * @param String $path
	 *        The .ogg file or URL
	 * @param Boolean $utf8
	 *        If true, return fields in UTF-8, otherwise in ASCII/ISO-8859-1
	 * @return Ogg_Comments
	 *        If $comments is empty, no .ogg file
	 */
	public function Ogg_Comments($path, $utf8 = false) {

		// check for local file or URL
		if (strpos($path, '://') === false) {
			if (!$fp = @fopen($path, 'rb'))
				return false;
			$file = fread($fp, BLOCKSIZE);
			fclose($fp);
			if ($file === false)
				return false;
		} else {
			$file = $this->get_1block($path);
			if ($file === false || $file == -1)
				return false;
		}

		// read OGG pages
		for ($pos = 0; ($pos = strpos($file, 'OggS', $pos)) !== false; $pos++) {
			// check stream version
			if (ord($file[$pos+4]) != 0)
				continue;

			// compute offset of packet after header
			$offset = $pos + 27 + ord($file[$pos+26]);
			// check for second (== comments) packet
			if ($this->read_intle($file, $pos+18) == 1) {
				// check for vorbis comments
				if (ord($file[$offset]) != 0x03 ||
				    substr($file, $offset+1, 6) != 'vorbis')
					continue;

				// read vendor string
				$offset += 7;
				$vndlen = $this->read_intle($file, $offset);
				$this->comments['VENDOR'] = $this->decode_field(substr($file, $offset+4, $vndlen), $utf8);
				// read comments count
				$offset += 4 + $vndlen;
				$cmtcnt = $this->read_intle($file, $offset);

				// read/parse all comment fields
				$offset += 4;
				for ($i = 0; $i < $cmtcnt; $i++) {
					$cmtlen = $this->read_intle($file, $offset);
					$comment = substr($file, $offset+4, $cmtlen);
					$offset += 4 + $cmtlen;
					// store field name=value pair
					$comment = explode('=', $comment, 2);
					// check for repeated field & append
					if (isset($this->comments[strtoupper($comment[0])]))
						$this->comments[strtoupper($comment[0])] .= ', ' . $this->decode_field($comment[1], $utf8);
					else
						$this->comments[strtoupper($comment[0])] = $this->decode_field($comment[1], $utf8);
				}
			}
			// check whether done
			if (isset($this->comments['VENDOR']))
				break;
		}
	}  // Ogg_Comments

	// Read 32-bits Little Endian integer
	private function read_intle(&$buf, $pos) {

		return (ord($buf[$pos+0]) + (ord($buf[$pos+1]) << 8) +
		        (ord($buf[$pos+2]) << 16) + (ord($buf[$pos+3]) << 24));
	}  // read_intle

	// Decode comment field into specified charset
	private function decode_field($str, $utf8) {

		if ($utf8)
			// return UTF8 or encode it in UTF8
			return ((utf8_encode(utf8_decode($str)) == $str) ?
			        $str : utf8_encode($str));
		else
			// decode it to ASCII or return ASCII
			return ((utf8_encode(utf8_decode($str)) == $str) ?
			        utf8_decode($str) : $str);
	}  // decode_field

	// Simple HTTP Get 1 Block function with timeout
	// ok: return string || error: return false || timeout: return -1
	private function get_1block($url) {

		$url = parse_url($url);
		$port = isset($url['port']) ? $url['port'] : 80;
		$query = isset($url['query']) ? '?' . $url['query'] : '';

		$fp = @fsockopen($url['host'], $port, $errno, $errstr, 4);
		if (!$fp)
			return false;

		$uri = '';
		foreach (explode('/', $url['path']) as $subpath)
			$uri .= rawurlencode($subpath) . '/';
		$uri = substr($uri, 0, strlen($uri)-1); // strip trailing '/'

		fwrite($fp, 'GET ' . $uri . $query . " HTTP/1.0\r\n" .
		            'Host: ' . $url['host'] . "\r\n" .
		            'User-Agent: Ogg_Comments (' . PHP_OS . ")\r\n\r\n");
		stream_set_timeout($fp, 2);
		$res = '';
		$info['timed_out'] = false;
		for ($i = 0; $i < 2; $i++)
			if (feof($fp) || $info['timed_out']) {
				break;
			} else {
				$res .= fread($fp, BLOCKSIZE);
				$info = stream_get_meta_data($fp);
			}
		fclose($fp);

		if ($info['timed_out']) {
			return -1;
		} else {
			if (substr($res, 9, 3) != '200')
				return false;
			$page = explode("\r\n\r\n", $res, 2);
			return trim($page[1]);
		}
	}  // get_1block
}  // class Ogg_Comments
?>
