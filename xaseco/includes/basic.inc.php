<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

// Updated by Xymph

/**
 * Writes a logfile of all output messages, either in a single big file
 * or in monthly chunks inside the logs/ directory.
 */
function doLog($text) {
	global $logfile;

	if (MONTHLY_LOGSDIR) {
		// create logs/ directory if needed
		$dir = './logs';
		if (!file_exists($dir)) mkdir($dir);

		// define monthly file inside dir
		$file = $dir . '/logfile-' . date('Ym') . '.txt';

		// if new monthly file, close old logfile
		if (!file_exists($file) && $logfile) {
			fclose($logfile);
			$logfile = false;
		}
	} else {
		// original single big file
		$file = 'logfile.txt';
	}

	if (!$logfile) {
		$logfile = fopen($file, 'a+');
	}
	fwrite($logfile, $text);
}  // doLog

/**
 * Case-insensitive file_exists replacement function.
 * Returns matching path, otherwise false.
 * Created by Xymph
 */
function file_exists_nocase($filepath) {

	// try case-sensitive path first
	if (file_exists($filepath)) return $filepath;

	// extract directory path
	if (DIRECTORY_SEPARATOR == '/')
		preg_match('|^(.+/)([^/]+)$|', $filepath, $paths);
	else  // '\'
		preg_match('|^(.+\\\\)([^\\\\]+)$|', $filepath, $paths);
	$dirpath = $paths[1];
	// $filename = $paths[2];

	// collect all files inside directory
	$checkpaths = glob($dirpath . '*');
	if ($checkpaths === false || empty($checkpaths)) return false;

	// check case-insensitive paths
	foreach ($checkpaths as $path)
		if (strtolower($filepath) == strtolower($path))
			return $path;

	return false;
}  // file_exists_nocase

/**
 * Puts an element at a specific position into an array.
 * Increases original size by one element.
 */
function insertArrayElement(&$array, $value, $pos) {

	// get current size
	$size = count($array);

	// if position is in array range
	if ($pos < 0 && $pos >= $size) {
		return false;
	}

	// shift values down
	for ($i = $size-1; $i >= $pos; $i--) {
		$array[$i+1] = $array[$i];
	}

	// now put in the new element
	$array[$pos] = $value;
	return true;
}  // insertArrayElement

/**
 * Removes an element from a specific position in an array.
 * Decreases original size by one element.
 */
function removeArrayElement(&$array, $pos) {

	// get current size
	$size = count($array);

	// if position is in array range
	if ($pos < 0 && $pos >= $size) {
		return false;
	}

	// remove specified element
	unset($array[$pos]);
	// shift values up
	$array = array_values($array);
	return true;
}  // removeArrayElement

/**
 * Moves an element from one position to the other.
 * All items between are shifted down or up as needed.
 */
function moveArrayElement(&$array, $from, $to) {

	// get current size
	$size = count($array);

	// destination and source have to be among the array borders!
	if ($from < 0 || $from >= $size || $to < 0 || $to >= $size) {
		return false;
	}

	// backup the element we have to move
	$moving_element = $array[$from];

	if ($from > $to) {
		// shift values between downwards
		for ($i = $from-1; $i >= $to; $i--) {
			$array[$i+1] = $array[$i];
		}
	} else {  // $from < $to
		// shift values between upwards
		for ($i = $from; $i <= $to; $i++) {
			$array[$i] = $array[$i+1];
		}
	}

	// now put in the element which was to move
	$array[$to] = $moving_element;
	return true;
}  // moveArrayElement

/**
 * Formats a string from the format sssshh0
 * into the format mmm:ss.hh (or mmm:ss if $hsec is false)
 */
function formatTime($MwTime, $hsec = true) {

	if ($MwTime == -1) {
		return '???';
	} else {
		$minutes = floor($MwTime/(1000*60));
		$seconds = floor(($MwTime - $minutes*60*1000)/1000);
		$hseconds = substr($MwTime, strlen($MwTime)-3, 2);
		if ($hsec) {
			$tm = sprintf('%02d:%02d.%02d', $minutes, $seconds, $hseconds);
		} else {
			$tm = sprintf('%02d:%02d', $minutes, $seconds);
		}
	}
	if ($tm[0] == '0') {
		$tm = substr($tm, 1);
	}
	return $tm;
}  // formatTime

/**
 * Formats a string from the format sssshh0
 * into the format hh:mm:ss.hh (or hh:mm:ss if $hsec is false)
 */
function formatTimeH($MwTime, $hsec = true) {

	if ($MwTime == -1) {
		return '???';
	} else {
		$hseconds = substr($MwTime, strlen($MwTime)-3, 2);
		$MwTime = substr($MwTime, 0, strlen($MwTime)-3);
		$hours = floor($MwTime / 3600);
		$MwTime = $MwTime - ($hours * 3600);
		$minutes = floor($MwTime / 60);
		$MwTime = $MwTime - ($minutes * 60);
		$seconds = floor($MwTime);
		if ($hsec) {
			return sprintf('%02d:%02d:%02d.%02d', $hours, $minutes, $seconds, $hseconds);
		} else {
			return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
		}
	}
}  // formatTimeH

/**
 * Formats a text.
 * Replaces parameters in the text which are marked with {n}
 */
function formatText($text) {

	// get all function's parameters
	$args = func_get_args();

	// first parameter is the text to format
	$text = array_shift($args);

	// further parameters will be replaced in the text
	$i = 1;
	foreach ($args as $param)
		$text = str_replace('{' . $i++ . '}', $param, $text);

	// and return the modified text
	return $text;
}  // formatText

/**
 * Make String for SQL use that single quoted & got special chars replaced.
 */
function quotedString($input) {

	return "'" . mysql_real_escape_string($input) . "'";
}  // quotedString

/**
 * Check login string for LAN postfix (pre/post v2.11.21).
 */
function isLANLogin($login) {

	$n="(25[0-5]|2[0-4]\d|[01]?\d\d|\d)";
	return (preg_match("/(\/{$n}\\.{$n}\\.{$n}\\.{$n}:\d+)$/", $login) ||
	        preg_match("/(_{$n}\\.{$n}\\.{$n}\\.{$n}_\d+)$/", $login));
}  // isLANLogin

/**
 * Summary: Strips all display formatting from an input string, suitable for display
 *          within the game ('$$' escape pairs are preserved) and for logging
 * Params : $input - The input string to strip formatting from
 *          $for_tm - Optional flag to double up '$' into '$$' (default, for TM) or not (for logs, etc)
 * Returns: The content portions of $input without formatting
 * Authors: Bilge/Assembler Maniac/Xymph/Slig
 *
 * "$af0Brat$s$fffwurst" will become "Bratwurst".
 * 2007-08-27 Xymph - replaced with Bilge/AM's code (w/o the H&L tags bit)
 *                    http://www.tm-forum.com/viewtopic.php?p=55867#p55867
 * 2008-04-24 Xymph - extended to handle the H/L/P tags for TMF
 *                    http://www.tm-forum.com/viewtopic.php?p=112856#p112856
 * 2009-05-16 Slig  - extended to emit non-TM variant & handle incomplete colors
 *                    http://www.tm-forum.com/viewtopic.php?p=153368#p153368
 * 2010-10-05 Slig  - updated to handle incomplete colors & tags better
 *                    http://www.tm-forum.com/viewtopic.php?p=183410#p183410
 * 2010-10-09 Xymph - updated to handle $[ and $] properly
 *                    http://www.tm-forum.com/viewtopic.php?p=183410#p183410
 */
function stripColors($input, $for_tm = true) {

	return
		//Replace all occurrences of a null character back with a pair of dollar
		//signs for displaying in TM, or a single dollar for log messages etc.
		str_replace("\0", ($for_tm ? '$$' : '$'),
			//Replace links (introduced in TMU)
			preg_replace(
				'/
				#Strip TMF H, L & P links by stripping everything between each square
				#bracket pair until another $H, $L or $P sequence (or EoS) is found;
				#this allows a $H to close a $L and vice versa, as does the game
				\\$[hlp](.*?)(?:\\[.*?\\](.*?))*(?:\\$[hlp]|$)
				/ixu',
				//Keep the first and third capturing groups if present
				'$1$2',
				//Replace various patterns beginning with an unescaped dollar
				preg_replace(
					'/
					#Match a single dollar sign and any of the following:
					\\$
					(?:
						#Strip color codes by matching any hexadecimal character and
						#any other two characters following it (except $)
						[0-9a-f][^$][^$]
						#Strip any incomplete color codes by matching any hexadecimal
						#character followed by another character (except $)
						|[0-9a-f][^$]
						#Strip any single style code (including an invisible UTF8 char)
						#that is not an H, L or P link or a bracket ($[ and $])
						|[^][hlp]
						#Strip the dollar sign if it is followed by [ or ], but do not
						#strip the brackets themselves
						|(?=[][])
						#Strip the dollar sign if it is at the end of the string
						|$
					)
					#Ignore alphabet case, ignore whitespace in pattern & use UTF-8 mode
					/ixu',
					//Replace any matches with nothing (i.e. strip matches)
					'',
					//Replace all occurrences of dollar sign pairs with a null character
					str_replace('$$', "\0", $input)
				)
			)
		)
	;
}  // stripColors

/**
 * Strips only size tags from TM strings.
 * "$w$af0Brat$n$fffwurst" will become "$af0Brat$fffwurst".
 * 2009-03-27 Xymph - derived from stripColors above
 *                    http://www.tm-forum.com/viewtopic.php?f=127&t=20602
 * 2009-05-16 Slig  - extended to emit non-TM variant
 *                    http://www.tm-forum.com/viewtopic.php?p=153368#p153368
 */
function stripSizes($input, $for_tm = true) {

	return
		//Replace all occurrences of a null character back with a pair of dollar
		//signs for displaying in TM, or a single dollar for log messages etc.
		str_replace("\0", ($for_tm ? '$$' : '$'),
			//Replace various patterns beginning with an unescaped dollar
			preg_replace(
				'/
				#Match a single dollar sign and any of the following:
				\\$
				(?:
					#Strip any size code
					[nwo]
					#Strip the dollar sign if it is at the end of the string
					|$
				)
				#Ignore alphabet case, ignore whitespace in pattern & use UTF-8 mode
				/ixu',
				//Replace any matches with nothing (i.e. strip matches)
				'',
				//Replace all occurrences of dollar sign pairs with a null character
				str_replace('$$', "\0", $input)
			)
		)
	;
}  // stripSizes

/**
 * Strips only newlines from TM strings.
 */
function stripNewlines($input) {

	return str_replace(array("\n\n", "\r", "\n"),
	                   array(' ', '', ''), $input);
}  // stripNewlines


/**
 * Univeral show help for user, admin & Jfreu commands.
 * Created by Xymph
 *
 * $width is the width of the first column in the ManiaLink window on TMF
 */
function showHelp($player, $chat_commands, $head,
                  $showadmin = false, $dispall = false, $width = 0.3) {
	global $aseco;

	// display full help for TMN
	if ($aseco->server->getGame() == 'TMN' && $dispall) {
		$head = "Currently supported $head commands:" . LF;

		if (!empty($chat_commands)) {
			// define admin or non-admin padding string
			$pad = ($showadmin ? '$f00... ' : '$f00/');
			$help = '';
			$lines = 0;
			$player->msgs = array();
			$player->msgs[0] = 1;
			// create list of chat commands
			foreach ($chat_commands as $cc) {
				// collect either admin or non-admin commands
				if ($cc->isadmin == $showadmin) {
					$help .= $pad . $cc->name . ' $000' . $cc->help . LF;
					if (++$lines > 14) {
						$player->msgs[] = $head . $help;
						$lines = 0;
						$help = '';
					}
				}
			}
			// add if last batch exists
			if ($help != '')
				$player->msgs[] = $head . $help;

			// display popup message
			if (count($player->msgs) == 2) {
				$aseco->client->query('SendDisplayServerMessageToLogin', $player->login, $player->msgs[1], 'OK', '', 0);
			} else {  // > 2
				$aseco->client->query('SendDisplayServerMessageToLogin', $player->login, $player->msgs[1], 'Close', 'Next', 0);
			}
		}

	// display full help for TMF
	} elseif ($aseco->server->getGame() == 'TMF' && $dispall) {
		$head = "Currently supported $head commands:";

		if (!empty($chat_commands)) {
			// define admin or non-admin padding string
			$pad = ($showadmin ? '$f00... ' : '$f00/');
			$help = array();
			$lines = 0;
			$player->msgs = array();
			$player->msgs[0] = array(1, $head, array(1.3, $width, 1.3 - $width), array('Icons64x64_1', 'TrackInfo', -0.01));
			// create list of chat commands
			foreach ($chat_commands as $cc) {
				// collect either admin or non-admin commands
				if ($cc->isadmin == $showadmin) {
					$help[] = array($pad . $cc->name, $cc->help);
					if (++$lines > 14) {
						$player->msgs[] = $help;
						$lines = 0;
						$help = array();
					}
				}
			}
			// add if last batch exists
			if (!empty($help))
				$player->msgs[] = $help;

			// display ManiaLink message
			display_manialink_multi($player);
		}

	// show help for TMS or TMO, and plain help for TMF/TMN
	} else {
		$head = "Currently supported $head commands:" . LF;
		$help = $aseco->formatColors('{#interact}' . $head);
		foreach ($chat_commands as $cc) {
			// collect either admin or non-admin commands
			if ($cc->isadmin == $showadmin) {
				$help .= $cc->name . ', ';
			}
		}
		// show chat message
		$help = substr($help, 0, strlen($help) - 2);  // strip trailing ", "
		$aseco->client->query('ChatSendToLogin', $help, $player->login);
	}
}  // showHelp

/**
 * Map country names to 3-letter Nation abbreviations
 * Created by Xymph
 * Based on http://en.wikipedia.org/wiki/List_of_IOC_country_codes
 * See also http://en.wikipedia.org/wiki/Comparison_of_IOC,_FIFA,_and_ISO_3166_country_codes
 */
function mapCountry($country) {

	$nations = array(
		'Afghanistan' => 'AFG',
		'Albania' => 'ALB',
		'Algeria' => 'ALG',
		'Andorra' => 'AND',
		'Angola' => 'ANG',
		'Argentina' => 'ARG',
		'Armenia' => 'ARM',
		'Aruba' => 'ARU',
		'Australia' => 'AUS',
		'Austria' => 'AUT',
		'Azerbaijan' => 'AZE',
		'Bahamas' => 'BAH',
		'Bahrain' => 'BRN',
		'Bangladesh' => 'BAN',
		'Barbados' => 'BAR',
		'Belarus' => 'BLR',
		'Belgium' => 'BEL',
		'Belize' => 'BIZ',
		'Benin' => 'BEN',
		'Bermuda' => 'BER',
		'Bhutan' => 'BHU',
		'Bolivia' => 'BOL',
		'Bosnia&Herzegovina' => 'BIH',
		'Botswana' => 'BOT',
		'Brazil' => 'BRA',
		'Brunei' => 'BRU',
		'Bulgaria' => 'BUL',
		'Burkina Faso' => 'BUR',
		'Burundi' => 'BDI',
		'Cambodia' => 'CAM',
		'Cameroon' => 'CAR',  // actually CMR
		'Canada' => 'CAN',
		'Cape Verde' => 'CPV',
		'Central African Republic' => 'CAF',
		'Chad' => 'CHA',
		'Chile' => 'CHI',
		'China' => 'CHN',
		'Chinese Taipei' => 'TPE',
		'Colombia' => 'COL',
		'Congo' => 'CGO',
		'Costa Rica' => 'CRC',
		'Croatia' => 'CRO',
		'Cuba' => 'CUB',
		'Cyprus' => 'CYP',
		'Czech Republic' => 'CZE',
		'Czech republic' => 'CZE',
		'DR Congo' => 'COD',
		'Denmark' => 'DEN',
		'Djibouti' => 'DJI',
		'Dominica' => 'DMA',
		'Dominican Republic' => 'DOM',
		'Ecuador' => 'ECU',
		'Egypt' => 'EGY',
		'El Salvador' => 'ESA',
		'Eritrea' => 'ERI',
		'Estonia' => 'EST',
		'Ethiopia' => 'ETH',
		'Fiji' => 'FIJ',
		'Finland' => 'FIN',
		'France' => 'FRA',
		'Gabon' => 'GAB',
		'Gambia' => 'GAM',
		'Georgia' => 'GEO',
		'Germany' => 'GER',
		'Ghana' => 'GHA',
		'Greece' => 'GRE',
		'Grenada' => 'GRN',
		'Guam' => 'GUM',
		'Guatemala' => 'GUA',
		'Guinea' => 'GUI',
		'Guinea-Bissau' => 'GBS',
		'Guyana' => 'GUY',
		'Haiti' => 'HAI',
		'Honduras' => 'HON',
		'Hong Kong' => 'HKG',
		'Hungary' => 'HUN',
		'Iceland' => 'ISL',
		'India' => 'IND',
		'Indonesia' => 'INA',
		'Iran' => 'IRI',
		'Iraq' => 'IRQ',
		'Ireland' => 'IRL',
		'Israel' => 'ISR',
		'Italy' => 'ITA',
		'Ivory Coast' => 'CIV',
		'Jamaica' => 'JAM',
		'Japan' => 'JPN',
		'Jordan' => 'JOR',
		'Kazakhstan' => 'KAZ',
		'Kenya' => 'KEN',
		'Kiribati' => 'KIR',
		'Korea' => 'KOR',
		'Kuwait' => 'KUW',
		'Kyrgyzstan' => 'KGZ',
		'Laos' => 'LAO',
		'Latvia' => 'LAT',
		'Lebanon' => 'LIB',
		'Lesotho' => 'LES',
		'Liberia' => 'LBR',
		'Libya' => 'LBA',
		'Liechtenstein' => 'LIE',
		'Lithuania' => 'LTU',
		'Luxembourg' => 'LUX',
		'Macedonia' => 'MKD',
		'Malawi' => 'MAW',
		'Malaysia' => 'MAS',
		'Mali' => 'MLI',
		'Malta' => 'MLT',
		'Mauritania' => 'MTN',
		'Mauritius' => 'MRI',
		'Mexico' => 'MEX',
		'Moldova' => 'MDA',
		'Monaco' => 'MON',
		'Mongolia' => 'MGL',
		'Montenegro' => 'MNE',
		'Morocco' => 'MAR',
		'Mozambique' => 'MOZ',
		'Myanmar' => 'MYA',
		'Namibia' => 'NAM',
		'Nauru' => 'NRU',
		'Nepal' => 'NEP',
		'Netherlands' => 'NED',
		'New Zealand' => 'NZL',
		'Nicaragua' => 'NCA',
		'Niger' => 'NIG',
		'Nigeria' => 'NGR',
		'Norway' => 'NOR',
		'Oman' => 'OMA',
		'Other Countries' => 'OTH',
		'Pakistan' => 'PAK',
		'Palau' => 'PLW',
		'Palestine' => 'PLE',
		'Panama' => 'PAN',
		'Paraguay' => 'PAR',
		'Peru' => 'PER',
		'Philippines' => 'PHI',
		'Poland' => 'POL',
		'Portugal' => 'POR',
		'Puerto Rico' => 'PUR',
		'Qatar' => 'QAT',
		'Romania' => 'ROM',  // actually ROU
		'Russia' => 'RUS',
		'Rwanda' => 'RWA',
		'Samoa' => 'SAM',
		'San Marino' => 'SMR',
		'Saudi Arabia' => 'KSA',
		'Senegal' => 'SEN',
		'Serbia' => 'SCG',  // actually SRB
		'Sierra Leone' => 'SLE',
		'Singapore' => 'SIN',
		'Slovakia' => 'SVK',
		'Slovenia' => 'SLO',
		'Somalia' => 'SOM',
		'South Africa' => 'RSA',
		'Spain' => 'ESP',
		'Sri Lanka' => 'SRI',
		'Sudan' => 'SUD',
		'Suriname' => 'SUR',
		'Swaziland' => 'SWZ',
		'Sweden' => 'SWE',
		'Switzerland' => 'SUI',
		'Syria' => 'SYR',
		'Taiwan' => 'TWN',
		'Tajikistan' => 'TJK',
		'Tanzania' => 'TAN',
		'Thailand' => 'THA',
		'Togo' => 'TOG',
		'Tonga' => 'TGA',
		'Trinidad and Tobago' => 'TRI',
		'Tunisia' => 'TUN',
		'Turkey' => 'TUR',
		'Turkmenistan' => 'TKM',
		'Tuvalu' => 'TUV',
		'Uganda' => 'UGA',
		'Ukraine' => 'UKR',
		'United Arab Emirates' => 'UAE',
		'United Kingdom' => 'GBR',
		'United States of America' => 'USA',
		'Uruguay' => 'URU',
		'Uzbekistan' => 'UZB',
		'Vanuatu' => 'VAN',
		'Venezuela' => 'VEN',
		'Vietnam' => 'VIE',
		'Yemen' => 'YEM',
		'Zambia' => 'ZAM',
		'Zimbabwe' => 'ZIM',
	);

	if (array_key_exists($country, $nations)) {
		$nation = $nations[$country];
	} else {
		$nation = 'OTH';
		if ($country != '')
			trigger_error('Could not map country: ' . $country, E_USER_WARNING);
	}
	return $nation;
}  // mapCountry

/**
 * Find TMX data for the given track
 * Created by Xymph
 */
require_once('includes/tmxinfofetcher.inc.php');  // provides access to TMX info
function findTMXdata($uid, $envir, $exever, $records = false) {

	// determine likely search order
	if ($envir == 'Stadium') {
		// check for old TMN
		if (strcmp($exever, '0.1.8.0') < 0)
			$sections = array('TMN', 'TMNF', 'TMU');
		// check for new TMF
		elseif (strcmp($exever, '2.11.0') >= 0)
			$sections = array('TMNF', 'TMU');
		else
			$sections = array('TMU');  // TMNF section opened after TMF beta
	} elseif ($envir == 'Bay' || $envir == 'Coast' || $envir == 'Island') {
		// check for old TMS
		if (strcmp($exever, '0.1.5.0') <= 0)
			$sections = array('TMS', 'TMU');
		else
			$sections = array('TMU');  // TMS section closed after TMU release
	} else { // $envir == 'Alpine' || 'Snow' || 'Desert' || 'Speed' || 'Rally'
		// check for old TMO
		if (strcmp($exever, '0.1.5.0') <= 0)
			$sections = array('TMO', 'TMU');
		else
			$sections = array('TMU');  // TMO section closed after TMU release
	}

	// search TMX for track
	foreach ($sections as $section) {
		$tmxdata = new TMXInfoFetcher($section, $uid, $records);
		if ($tmxdata->name) {
			return $tmxdata;
		}
	}
	return false;
}  // findTMXdata

/**
 * Simple HTTP Get function with timeout
 * ok: return string || error: return false || timeout: return -1
 * if $openonly == true, don't read data but return true upon connect
 */
function http_get_file($url, $openonly = false) {
	global $aseco;

	$url = parse_url($url);
	$port = isset($url['port']) ? $url['port'] : 80;
	$query = isset($url['query']) ? '?' . $url['query'] : '';

	$fp = @fsockopen($url['host'], $port, $errno, $errstr, 4);
	if (!$fp)
		return false;
	if ($openonly) {
		fclose($fp);
		return true;
	}

	$uri = '';
	foreach (explode('/', $url['path']) as $subpath)
		$uri .= rawurlencode($subpath) . '/';
	$uri = substr($uri, 0, strlen($uri)-1); // strip trailing '/'

	fwrite($fp, 'GET ' . $uri . $query . " HTTP/1.0\r\n" .
	            'Host: ' . $url['host'] . "\r\n" .
	            'User-Agent: XASECO-' . XASECO_VERSION . ' (' . PHP_OS . '; ' .
	                         $aseco->server->game . ")\r\n\r\n");
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
		return $page[1];
	}
}  // http_get_file

/**
 * Return valid UTF-8 string, replacing faulty byte values with a given string
 * Created by (OoR-F)~fuckfish (fish@stabb.de)
 * http://www.tm-forum.com/viewtopic.php?p=117639#p117639
 * Based on the original tm_substr function by Slig (slig@free.fr)
 * Updated by Xymph;  More info: http://en.wikipedia.org/wiki/UTF-8
 */
function validateUTF8String($input, $invalidRepl = '') {

	$str = (string) $input;
	$len = strlen($str);  // byte string length
	$pos = 0;  // current byte pos in string
	$new = '';

	while ($pos < $len) {
		$co = ord($str[$pos]);

		// 4-6 bytes UTF8 => unsupported
		if ($co >= 240) {
			// bad multibyte char
			$new .= $invalidRepl;
			$pos++;

		// 3 bytes UTF8 => 1110bbbb 10bbbbbb 10bbbbbb
		} elseif ($co >= 224) {
			if (($pos+2 < $len) &&
			    (ord($str[$pos+1]) >= 128 && ord($str[$pos+1]) < 192) &&
			    (ord($str[$pos+2]) >= 128 && ord($str[$pos+2]) < 192)) {
				// ok, it was 1 character, increase counters
				$new .= substr($str, $pos, 3);
				$pos += 3;
			} else {
				// bad multibyte char
				$new .= $invalidRepl;
				$pos++;
			}

		// 2 bytes UTF8 => 110bbbbb 10bbbbbb
		} elseif ($co >= 194) {
			if (($pos+1 < $len) &&
			    (ord($str[$pos+1]) >= 128 && ord($str[$pos+1]) < 192)) {
				// ok, it was 1 character, increase counters
				$new .= substr($str, $pos, 2);
				$pos += 2;
			} else {
				// bad multibyte char
				$new .= $invalidRepl;
				$pos++;
			}

		// 2 bytes overlong encoding => unsupported
		} elseif ($co >= 192) {
			// bad multibyte char 1100000b
			$new .= $invalidRepl;
			$pos++;

		// 1 byte ASCII => 0bbbbbbb, or invalid => 10bbbbbb or 11111bbb
		} else {  // $co < 192
			// erroneous middle multibyte char?
			if ($co >= 128 || $co == 0)
				$new .= $invalidRepl;
			else
				$new .= $str[$pos];

			$pos++;
		}
	}
	return $new;
}  // validateUTF8String

/**
 * Convert php.ini memory shorthand string to integer bytes
 * http://www.php.net/manual/en/function.ini-get.php#96996
 */
function shorthand2bytes($size_str) {

	switch (substr($size_str, -1)) {
		case 'M': case 'm': return (int)$size_str * 1048576;
		case 'K': case 'k': return (int)$size_str * 1024;
		case 'G': case 'g': return (int)$size_str * 1073741824;
		default: return (int)$size_str;
	}
}  // return_bytes

/**
 * Convert boolean value to text string
 */
function bool2text($boolval) {

	if ($boolval)
		return 'True';
	else
		return 'False';
}  // bool2text
?>
