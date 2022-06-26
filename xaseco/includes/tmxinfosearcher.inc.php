<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * TMXInfoSearcher - Search TMX info for TMO/TMS/TMN/TMU(F)/TMNF tracks
 * Created by Xymph <tm@gamers.org>
 * Based on TMXInfoFetcher & http://united.tm-exchange.com/main.aspx?action=threadshow&id=619302
 *
 * v1.7: Added Countable interface to searcher class
 * v1.6: Fixed an error checking bug
 * v1.5: Improved error reporting via $error
 * v1.4: Added User-Agent to the GET request
 * v1.3: Renamed $worldrec into $custimg to correct its meaning; fixed
 *       $replayid check
 * v1.2: Fixed TMXInfo processing false $track
 * v1.1: Optimized get_file URL parsing
 * v1.0: Initial release
 */
class TMXInfoSearcher implements Iterator,Countable {

	public $error;
	protected $tracks = array();
	private $section;
	private $prefix;

	/**
	 * Searches TMX for tracks matching name, author and/or environment;
	 * or search TMX for the 10 most recent tracks
	 *
	 * @param String $game
	 *        TMX section for 'TMO', 'TMS', 'TMN', 'TMU', 'TMNF'
	 * @param String $name
	 *        The track name to search for (partial, case-insensitive match)
	 * @param String $author
	 *        The track author to search for (partial, case-insensitive match)
	 * @param String $env
	 *        The environment to search for (exact case-insensitive match
	 *        from: Desert, Snow, Rally, Bay, Coast, Island, Stadium);
	 *        ignored when searching TMN or TMNF
	 * @param Boolean $recent
	 *        If true, ignore search parameters and just return 10 newest tracks
	 *        (max. one per author)
	 * @return TMXInfoSearcher
	 *        If ->valid() is false, no matching track was found;
	 *        otherwise, an iterator of TMXInfo objects for a 'foreach' loop.
	 *        Returns at most 500 tracks ($maxpage * 20) for TMNF/TMU(F),
	 *        and at most 20 tracks for the other TMX sections.
	 */
	public function __construct($game, $name, $author, $env, $recent) {

		$this->section = $game;
		switch ($game) {
		case 'TMO':
			$this->prefix = 'original';
			break;
		case 'TMS':
			$this->prefix = 'sunrise';
			break;
		case 'TMN':
			$this->prefix = 'nations';
			$env = '';  // ignore possible environment
			break;
		case 'TMU':
			$this->prefix = 'united';
			break;
		case 'TMNF':
			$this->prefix = 'tmnforever';
			$env = '';  // ignore possible environment
			break;
		default:
			$this->prefix = '';
			$this->error = 'Unknown TMX section: ' . $game;
			return;
		}

		$this->error = '';
		if ($recent) {
			$this->tracks = $this->getRecent();
		} else {
			$this->tracks = $this->getList($name, $author, $env);
		}
	}  // __construct

	// define standard Iterator functions
	public function rewind() {
		reset($this->tracks);
	}
	public function current() {
		return new TMXInfo($this->section, $this->prefix, current($this->tracks));
	}
	public function next() {
		return new TMXInfo($this->section, $this->prefix, next($this->tracks));
	}
	public function key() {
		return key($this->tracks);
	}
	public function valid() {
		return (current($this->tracks) !== false);
	}
	// define standard Countable function
	public function count() {
		return count($this->tracks);
	}

	private function getRecent() {

		// get 10 most recent tracks
		$url = 'http://' . $this->prefix . '.tm-exchange.com/apiget.aspx?action=apirecent';
		$file = $this->get_file($url);
		if ($file === false) {
			$this->error = 'Connection or response error on ' . $url;
			return array();
		} else if ($file === -1) {
			$this->error = 'Timed out while reading data from ' . $url;
			return array();
		} else if ($file == '') {
			$this->error = 'No data returned from ' . $url;
			return array();
		}

		// check for API error message
		if (strpos($file, chr(27)) !== false) {
			$this->error = 'Cannot decode recent track info from ' . $url;
			return array();
		}

		// return list of tracks as array of strings
		return explode("\r\n", $file);
	}  // getRecent

	private function getList($name, $author, $env) {

		// older TMX sections don't support multi-page results :(
		$maxpage = 1;
		if ($this->prefix == 'tmnforever' || $this->prefix == 'united')
			$maxpage = 25;  // max. 500 tracks

		// compile search URL
		$url = 'http://' . $this->prefix . '.tm-exchange.com/apiget.aspx?action=apisearch';
		if ($name != '')
			$url .= '&track=' . $name;
		if ($author != '')
			$url .= '&author=' . $author;
		if ($env != '')
			$url .= '&env=' . $env;
		$url .= '&page=';

		$tracks = '';
		$page = 0;
		$done = false;

		// get results 20 tracks at a time
		while ($page < $maxpage && !$done) {
			$file = $this->get_file($url . $page);
			if ($file === false) {
				$this->error = 'Connection or response error on ' . $url;
				return array();
			} else if ($file === -1) {
				$this->error = 'Timed out while reading data from ' . $url;
				return array();
			} else if ($file == '') {
				if ($tracks == '') {
					$this->error = 'No data returned from ' . $url;
					return array();
				} else {
					break;
				}
			}

			// check for API error message
			if (strpos($file, chr(27)) !== false) {
				$this->error = 'Cannot decode searched track info from ' . $url;
				return array();
			}

			// check for results
			if ($file != '') {
				// no line break before first page
				$tracks .= ($page++ > 0 ? "\r\n" : '') . $file;
			} else {
				$done = true;
			}
		}

		// return list of tracks as array of strings
		if ($tracks != '')
			return explode("\r\n", $tracks);
		else
			return array();
	}  // getList

	// Simple HTTP Get function with timeout
	// ok: return string || error: return false || timeout: return -1
	private function get_file($url) {

		$url = parse_url($url);
		$port = isset($url['port']) ? $url['port'] : 80;
		$query = isset($url['query']) ? "?" . $url['query'] : "";

		$fp = @fsockopen($url['host'], $port, $errno, $errstr, 4);
		if (!$fp)
			return false;

		fwrite($fp, 'GET ' . $url['path'] . $query . " HTTP/1.0\r\n" .
		            'Host: ' . $url['host'] . "\r\n" .
		            'User-Agent: TMXInfoSearcher (' . PHP_OS . ")\r\n\r\n");
		stream_set_timeout($fp, 2);
		$res = '';
		$info['timed_out'] = false;
		while (!feof($fp) && !$info['timed_out']) {
			$res .= fread($fp, 512);
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
	}  // get_file
}  // class TMXInfoSearcher


class TMXInfo {

	public $section, $prefix, $id, $name, $userid, $author,
		$type, $envir, $mood, $style, $routes, $length, $diffic,
		$lbrating, $awards, $comments, $custimg, $game, $uploaded, $updated,
		$pageurl, $replayid, $replayurl, $imageurl, $thumburl, $dloadurl;

	/**
	 * Returns track object with a hell of a lot of data from TMX track string
	 *
	 * @param String $section
	 *        TMX section
	 * @param String $prefix
	 *        TMX URL prefix
	 * @param String $track
	 *        The TMX track string from TMXInfoSearcher
	 * @return TMXInfo
	 */
	public function TMXInfo($section, $prefix, $track) {

		$this->section  = $section;
		$this->prefix   = $prefix;
		if ($track) {
			// separate columns on Tabs
			$fields = explode(chr(9), $track);

			$this->id       = $fields[0];
			$this->name     = $fields[1];
			$this->userid   = $fields[2];
			$this->author   = $fields[3];
			$this->type     = $fields[4];
			$this->envir    = $fields[5];
			$this->mood     = $fields[6];
			$this->style    = $fields[7];
			$this->routes   = $fields[8];
			$this->length   = $fields[9];
			$this->diffic   = $fields[10];
			$this->lbrating = ($fields[11] > 0 ? $fields[11] : 'Classic!');
			$this->awards   = $fields[12];
			$this->comments = $fields[13];
			$this->custimg  = (strtolower($fields[14]) == 'true');
			$this->game     = $fields[15];
			$this->replayid = $fields[16];
			// unknown      = $fields[17-21];
			$this->uploaded = $fields[22];
			$this->updated  = $fields[23];

			$this->pageurl  = 'http://' . $prefix . '.tm-exchange.com/main.aspx?action=trackshow&id=' . $this->id;
			$this->imageurl = 'http://' . $prefix . '.tm-exchange.com/get.aspx?action=trackscreen&id=' . $this->id;
			$this->thumburl = 'http://' . $prefix . '.tm-exchange.com/get.aspx?action=trackscreensmall&id=' . $this->id;
			$this->dloadurl = 'http://' . $prefix . '.tm-exchange.com/get.aspx?action=trackgbx&id=' . $this->id;

			if ($this->replayid > 0) {
				$this->replayurl = 'http://' . $prefix . '.tm-exchange.com/get.aspx?action=recordgbx&id=' . $this->replayid;
			} else {
				$this->replayurl = '';
			}
		}
	}  // TMXInfo
}  // class TMXInfo
?>
