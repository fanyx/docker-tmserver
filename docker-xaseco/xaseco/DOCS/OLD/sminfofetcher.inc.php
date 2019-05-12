<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * SMInfoFetcher - Fetch ShareMania info for TM tracks
 * Created by Xymph <tm@gamers.org>
 * Based on TMXInfoFetcher & http://www.sharemania.eu/api_tutorial.php
 *
 * v1.2: Added magic __set_state function to support var_export()
 * v1.1: Optimized get_file URL parsing
 * v1.0: Initial release
 */
class SMInfoFetcher {

	public $uid, $id,
		$name, $stname, $author,
		$game, $type, $envir, $mood, $nblaps, $coppers,
		$bronzetm, $silvertm, $goldtm, $authortm, $authorsc,
		$rating, $votes, $dnloads, $uploaded,
		$pageurl, $imageurl, $dloadurl;

	/**
	 * Fetches a hell of a lot of data about a SM track
	 *
	 * @param String $id
	 *        The challenge UID to search for (if a 26/27-char alphanum string),
	 *        otherwise the SM ID to search for (if a number)
	 * @return SMInfoFetcher
	 *        If $name is empty, track was not found
	 */
	public function SMInfoFetcher($id) {

		// check for UID string
		if (preg_match('/^\w{26,27}$/', $id)) {
			$this->uid = $id;
			$this->getData(true);
		// check for SM ID
		} elseif (is_numeric($id) && $id > 0) {
			$this->id = floor($id);
			$this->getData(false);
		}
	}  // SMInfoFetcher

	public function __set_state($import) {

		$sm = new SMInfoFetcher(0);

		$sm->uid      = $import['uid'];
		$sm->id       = $import['id'];
		$sm->name     = $import['name'];
		$sm->stname   = $import['stname'];
		$sm->author   = $import['author'];
		$sm->game     = $import['game'];
		$sm->type     = $import['type'];
		$sm->envir    = $import['envir'];
		$sm->mood     = $import['mood'];
		$sm->nblaps   = $import['nblaps'];
		$sm->coppers  = $import['coppers'];
		$sm->bronzetm = $import['bronzetm'];
		$sm->silvertm = $import['silvertm'];
		$sm->goldtm   = $import['goldtm'];
		$sm->authortm = $import['authortm'];
		$sm->authorsc = $import['authorsc'];
		$sm->rating   = $import['rating'];
		$sm->votes    = $import['votes'];
		$sm->dnloads  = $import['dnloads'];
		$sm->uploaded = $import['uploaded'];
		$sm->pageurl  = $import['pageurl'];
		$sm->imageurl = $import['imageurl'];
		$sm->dloadurl = $import['dloadurl'];

		return $sm;
	}  // __set_state

	private function getData($isuid) {

		// get all track info
		$file = $this->get_file('http://www.sharemania.eu/api.php?i&u&n&sn&a&gv&e&m&ty&nbl&c&t&p&pa&id=' . ($isuid ? $this->uid : $this->id));
		if ($file === false || $file == -1)
			return false;

		// parse XML info
		if (!$xml = @simplexml_load_string($file))
			return false;

		// extract all track info
		if ($isuid)
			$this->id     = (string) $xml->header->i;
		else
			$this->uid    = (string) $xml->header->u;

		$this->name     = (string) $xml->header->n;
		$this->stname   = (string) $xml->header->sn;
		$this->author   = (string) $xml->header->a;
		$this->type     = (string) $xml->header->ty;
		$this->game     = (string) $xml->header->gv;
		$this->envir    = (string) $xml->header->e;
		$this->mood     = (string) $xml->header->m;
		$this->nblaps   = (string) $xml->header->nbl;
		$this->coppers  = (string) $xml->header->c;
		$this->bronzetm = (string) $xml->times->b;
		$this->silvertm = (string) $xml->times->s;
		$this->goldtm   = (string) $xml->times->g;
		$this->authortm = (string) $xml->times->at;
		$this->authorsc = (string) $xml->times->as;
		$this->rating   = (string) $xml->infos->r;
		$this->votes    = (string) $xml->infos->v;
		$this->dnloads  = (string) $xml->infos->d;
		$this->uploaded = (string) $xml->infos->ud;

		$this->imageurl = (string) $xml->pic;
		$this->pageurl  = 'http://www.sharemania.eu/track.php?id=' . $this->id;
		$this->dloadurl = 'http://www.sharemania.eu/download.php?id=' . $this->id;
	}  // getData

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
		            'Host: ' . $url['host'] . "\r\n\r\n");
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
}  // class SMInfoFetcher
?>
