<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Music Server plugin (TMF).
 * Handles all server-controlled music.
 * Created by Xymph
 *
 * Dependencies: none
 */

Aseco::registerEvent('onSync', 'music_loadsettings');
Aseco::registerEvent('onShutdown', 'music_shutdown');
Aseco::registerEvent('onEndRace', 'music_nextsong');

// handles action id's "-2101"-"-4000" for selecting from max. 1900 songs
Aseco::registerEvent('onPlayerManialinkPageAnswer', 'event_music');

Aseco::addChatCommand('music', 'Handles server music (see: /music help)');

require_once('includes/ogg_comments.inc.php');  // provides .OGG comments

class Music {
	var $server;
	var $songs;
	var $tags;
	var $current;
	var $override;
	var $autonext;
	var $autoshuffle;
	var $allowjb;
	var $stripdirs;
	var $stripexts;
	var $cachetags;
	var $cacheread;
	var $cachefile;
	var $mannext;
	var $jukebox;
	var $messages;

	function Music($settings) {

		if (isset($settings['OVERRIDE_TRACK'][0]))
			$this->override = (strtolower($settings['OVERRIDE_TRACK'][0]) == 'true');
		else
			$this->override = false;
		if (isset($settings['AUTO_NEXTSONG'][0]))
			$this->autonext = (strtolower($settings['AUTO_NEXTSONG'][0]) == 'true');
		else
			$this->autonext = false;
		if (isset($settings['AUTO_SHUFFLE'][0]))
			$this->autoshuffle = (strtolower($settings['AUTO_SHUFFLE'][0]) == 'true');
		else
			$this->autoshuffle = false;
		if (isset($settings['ALLOW_JUKEBOX'][0]))
			$this->allowjb = (strtolower($settings['ALLOW_JUKEBOX'][0]) == 'true');
		else
			$this->allowjb = false;
		if (isset($settings['STRIP_SUBDIRS'][0]))
			$this->stripdirs = (strtolower($settings['STRIP_SUBDIRS'][0]) == 'true');
		else
			$this->stripdirs = false;
		if (isset($settings['STRIP_EXTS'][0]))
			$this->stripexts = (strtolower($settings['STRIP_EXTS'][0]) == 'true');
		else
			$this->stripexts = false;
		if (isset($settings['CACHE_TAGS'][0]))
			$this->cachetags = (strtolower($settings['CACHE_TAGS'][0]) == 'true');
		else
			$this->cachetags = false;
		if (isset($settings['CACHE_READONLY'][0]))
			$this->cacheread = (strtolower($settings['CACHE_READONLY'][0]) == 'true');
		else
			$this->cacheread = false;

		$this->cachefile = $settings['CACHE_FILE'][0];
		$this->server = $settings['MUSIC_SERVER'][0];
		// check for remote or local path
		if (substr($this->server, 0, 7) == 'http://') {
			// append / if missing
			if (substr($this->server, -1) != '/')
				$this->server .= '/';
		} else {
			// append DIRSEP if missing
			if (substr($this->server, -1) != DIRECTORY_SEPARATOR)
				$this->server .= DIRECTORY_SEPARATOR;
		}

		$this->songs = array();
		foreach ($settings['SONG_FILES'][0]['SONG'] as $song)
			$this->songs[] = $song;
		// remove duplicates
		$this->songs = array_values(array_unique($this->songs));
		// randomize list
		if ($this->autoshuffle)
			shuffle($this->songs);

		$this->messages = $settings['MESSAGES'][0];
		$this->mannext = false;
		$this->current = 0;
		$this->jukebox = array();
		$this->tags = array();
	}

	function currentSong($sid = false) {

		if ($sid === false)
			return $this->songs[$this->current];
		else
			return $this->songs[--$sid];
	}

	function nextSong() {

		$this->current++;
		if ($this->current == count($this->songs))
			$this->current = 0;

		return $this->songs[$this->current];
	}

	function selectSong($sid) {

		$sid--;
		$this->current++;
		if ($this->current == count($this->songs))
			$this->current = 0;

		if ($sid != $this->current)
			moveArrayElement($this->songs, $sid, $this->current);

		return $this->songs[$this->current];
	}
}  // class Music

global $music_server;


// called @ onSync
function music_loadsettings($aseco) {
	global $music_server;

	// read & parse config file
	$aseco->console('Load music server config [musicserver.xml]');
	if (!$settings = $aseco->xml_parser->parseXml('musicserver.xml')) {
		trigger_error('Could not read/parse Music server config file musicserver.xml !', E_USER_ERROR);
	}
	$music_server = new Music($settings['SETTINGS']);

	if ($music_server->cachetags)
		refresh_tags($aseco, $music_server);
}  // music_loadsettings

// called @ onShutdown
function music_shutdown($aseco) {
	global $music_server;

	// disable music
	$aseco->client->query('SetForcedMusic', $music_server->override, '');
}  // music_loadsettings

function refresh_tags($aseco, $music) {

	// read tags cache, if present
	if ($cache = $aseco->xml_parser->parseXml($music->cachefile)) {
		if (isset($cache['TAGS']['SONG']))
			foreach ($cache['TAGS']['SONG'] as $song)
				$music->tags[$song['FILE'][0]] = array('Title' => $song['TITLE'][0],
				                                       'Artist' => $song['ARTIST'][0]);
	}

	// define full path to server
	$server = $music->server;
	if (substr($server, 0, 7) != 'http://')
		$server = $aseco->server->gamedir . $server;

	// check all .OGG songs for cached or new tags
	foreach ($music->songs as $song) {
		if (strtoupper(substr($song, -4)) == '.OGG') {
			if (!isset($music->tags[$song])) {
				$tags = new Ogg_Comments($server . $song, true);
				if (!empty($tags->comments) && isset($tags->comments['TITLE']) && isset($tags->comments['ARTIST']))
					$music->tags[$song] = array('Title' => $tags->comments['TITLE'],
					                            'Artist' => $tags->comments['ARTIST']);
			}
		}
	}

	// check for read-only cache
	if ($music->cacheread) return;

	// compile updated tags cache
	$list = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>" . CRLF
	      . "<tags>" . CRLF;
	foreach ($music->tags as $song => $tags)
		$list .= "\t<song>" . CRLF
		       . "\t\t<file>" . $song . "</file>" . CRLF
		       . "\t\t<title>" . utf8_encode($tags['Title']) . "</title>" . CRLF
		       . "\t\t<artist>" . utf8_encode($tags['Artist']) . "</artist>" . CRLF
		       . "\t</song>" . CRLF;
	$list .= "</tags>" . CRLF;

	// write out cache file
	if (!@file_put_contents($music->cachefile, $list)) {
		trigger_error('Could not write music tags cache ' . $music->cachefile . ' !', E_USER_WARNING);
	}
}  // refresh_tags


// called @ onEndRace
function music_nextsong($aseco, $data) {
	global $music_server;

	// if restart, bail out immediately
	if ($aseco->restarting != 0) return;

	// check for manual next song by admin
	if ($music_server->mannext) {
		$music_server->mannext = false;
		return;
	}

	// check for jukeboxed song
	if (!empty($music_server->jukebox)) {
		$next = array_shift($music_server->jukebox);

		// check remote or local song access
		$song = $music_server->server . $music_server->selectSong($next);
		if (!http_get_file($song, true) &&
		    !file_exists($aseco->server->gamedir . $song)) {
			trigger_error('Could not access song ' . $song . ' !', E_USER_WARNING);
		} else {
			// log console message
			$aseco->console('[Music] Setting next song to: ' . $song);

			// load next song
			$aseco->client->query('SetForcedMusic', $music_server->override, $song);
			return;
		}
	}

	// check for automatic next song
	if ($music_server->autonext) {
		// check remote or local song access
		$song = $music_server->server . $music_server->nextSong();
		if (!http_get_file($song, true) &&
		    !file_exists($aseco->server->gamedir . $song)) {
			trigger_error('Could not access song ' . $song . ' !', E_USER_WARNING);
		} else {
			// log console message
			$aseco->console('[Music] Setting next song to: ' . $song);

			// load next song
			$aseco->client->query('SetForcedMusic', $music_server->override, $song);
		}
	} else {
		// disable next song
		$aseco->client->query('SetForcedMusic', $music_server->override, '');
	}
}  // music_nextsong


function chat_music($aseco, $command) {
	global $music_server;

	if ($aseco->server->getGame() != 'TMF') {
		$message = $aseco->getChatMessage('FOREVER_ONLY');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	$player = $command['author'];
	$login = $player->login;
	$arglist = $command['params'];
	$command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));
	if (!isset($command['params'][1])) $command['params'][1] = '';

	// get masteradmin/admin/operator titles
	if ($aseco->isMasterAdmin($player)) {
		$logtitle = 'MasterAdmin';
		$chattitle = $aseco->titles['MASTERADMIN'][0];
	} else {
		if ($aseco->isAdmin($player)) {
			$logtitle = 'Admin';
			$chattitle = $aseco->titles['ADMIN'][0];
		} else {
			if ($aseco->isOperator($player)) {
				$logtitle = 'Operator';
				$chattitle = $aseco->titles['OPERATOR'][0];
			}
		}
	}

	if ($command['params'][0] == 'help') {

		$header = '{#black}/music$g handles server music:';
		$help = array();
		$help[] = array('...', '{#black}help',
		                'Displays this help information');
		$help[] = array('...', '{#black}settings',
		                'Displays current music settings');
		$help[] = array('...', '{#black}list',
		                'Displays all available songs');
		$help[] = array('...', '{#black}list <xxx>',
		                'Searches song names/tags for <xxx>');
		$help[] = array('...', '{#black}current',
		                'Shows the current song');
	if ($aseco->allowAbility($player, 'chat_musicadmin')) {
		$help[] = array('...', '{#black}reload',
		                'Reloads musicserver.xml config file');
		$help[] = array('...', '{#black}next',
		                'Skips to next song (upon next track)');
		$help[] = array('...', '{#black}sort',
		                'Sorts the song list');
		$help[] = array('...', '{#black}shuffle',
		                'Randomizes the song list');
		$help[] = array('...', '{#black}override',
		                'Changes track override setting');
		$help[] = array('...', '{#black}autonext',
		                'Changes automatic next song setting');
		$help[] = array('...', '{#black}autoshuffle',
		                'Changes automatic shuffle setting');
		$help[] = array('...', '{#black}allowjb',
		                'Changes allow jukebox setting');
		$help[] = array('...', '{#black}stripdirs',
		                'Changes strip subdirs setting');
		$help[] = array('...', '{#black}stripexts',
		                'Changes strip extensions setting');
		$help[] = array('...', '{#black}off',
		                'Disables music, auto next & jukebox');
	}
		$help[] = array('...', '{#black}jukebox/jb',
		                'Displays upcoming songs in jukebox');
		$help[] = array('...', '{#black}drop',
		                'Drops your currently added song');
		$help[] = array('...', '{#black}##',
		                'Adds a song to jukebox where ## is');
		$help[] = array('', '',
		                'the song Id from {#black}/music list');
		// display ManiaLink message
		display_manialink($login, $header, array('Icons64x64_1', 'TrackInfo', -0.01), $help, array(0.9, 0.05, 0.2, 0.65), 'OK');
	}
	elseif ($command['params'][0] == 'settings') {

		$header = 'Music server settings:';
		$info = array();
		if ($aseco->allowAbility($player, 'chat_musicadmin')) {
			$info[] = array('Server', $music_server->server);
		}
		// get current song and strip server path
		$aseco->client->query('GetForcedMusic');
		$current = $aseco->client->getResponse();
		if ($current['Url'] != '' || $current['File'] != '') {
			//$current = preg_replace('|^' . $music_server->server . '|', '',
			$current = str_replace($music_server->server, '',
			                       ($current['Url'] != '' ? $current['Url'] : $current['File']));
			if ($music_server->cachetags && isset($music_server->tags[$current]))
				$tags = $music_server->tags[$current];
			if ($music_server->stripdirs)
				$current = preg_replace('|.*[/\\\\]|', '', $current);
			if ($music_server->stripexts)
				$current = preg_replace('|\.[^.]+$|', '', $current);
		} else {
			$current = 'In-game music';
		}

		$info[] = array('Current', $current);
		if ($music_server->cachetags && isset($tags))
			$info[] = array('', $tags['Title'] . '{#black} by $g' . $tags['Artist']);
		$info[] = array('Override', bool2text($music_server->override));
		$info[] = array('AutoNext', bool2text($music_server->autonext));
		$info[] = array('AutoShuffle', bool2text($music_server->autoshuffle));
		$info[] = array('AllowJB', bool2text($music_server->allowjb));
		$info[] = array('StripDirs', bool2text($music_server->stripdirs));
		$info[] = array('StripExts', bool2text($music_server->stripexts));
		$info[] = array('CacheTags', bool2text($music_server->cachetags));
		$info[] = array('CacheRead', bool2text($music_server->cacheread));
		$info[] = array('CacheFile', $music_server->cachefile);
		// display ManiaLink message
		display_manialink($login, $header, array('Icons64x64_1', 'Sound'), $info, array(1.0, 0.23, 0.77), 'OK');
	}
	elseif ($command['params'][0] == 'list') {

		// check for search parameter
		if (isset($command['params'][1]))
			$search = $command['params'][1];
		else
			$search = '';

		$head = 'Songs On This Server:';
		$page = array();
		$page[] = array('Id', 'Filename');
		$sid = 1;
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = array(1, $head, array(1.0, 0.1, 0.9), array('Icons64x64_1', 'Music'));
		foreach ($music_server->songs as $song) {
			if ($music_server->cachetags && isset($music_server->tags[$song]))
				$tags = $music_server->tags[$song];

			// check for match in filename or, if available, title & artist tags
			if ($search == '') {
				$pos = 0;
			} else {
				$pos = stripos($song, $search);
				if ($pos === false && isset($tags)) {
					$pos = stripos($tags['Title'], $search);
					if ($pos === false)
						$pos = stripos($tags['Artist'], $search);
				}
			}

			if ($pos !== false) {
				if ($music_server->stripdirs)
					$song = preg_replace('|.*[/\\\\]|', '', $song);
				if ($music_server->stripexts)
					$song = preg_replace('|\.[^.]+$|', '', $song);
				$page[] = array(str_pad($sid, 2, '0', STR_PAD_LEFT) . '.',
				                // add clickable button
				                (($aseco->settings['clickable_lists'] && $sid <= 1900) ?
				                 array('{#black}' . $song, -2100-$sid) :  // action id
				                 '{#black}' . $song));
				if ($music_server->cachetags) {
					if (isset($tags))
						$page[] = array('', $tags['Title'] . '{#black} by $g' . $tags['Artist']);
					else
						$page[] = array();
				}

				if (++$lines > ($music_server->cachetags ? 7 : 14)) {
					$player->msgs[] = $page;
					$lines = 0;
					$page = array();
					$page[] = array('Id', 'Filename');
				}
			}
			$sid++;
			unset($tags);
		}
		// add if last batch exists
		if (count($page) > 1)
			$player->msgs[] = $page;

		if (count($player->msgs) > 1) {
			// display ManiaLink message
			display_manialink_multi($player);
		} else {
			$message = '{#server}> {#error}No songs found, try again!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}
	elseif ($command['params'][0] == 'current') {

		// get current song and strip server path
		$aseco->client->query('GetForcedMusic');
		$current = $aseco->client->getResponse();
		if ($current['Url'] != '' || $current['File'] != '') {
			//$current = preg_replace('|^' . $music_server->server . '|', '',
			$current = str_replace($music_server->server, '',
			                       ($current['Url'] != '' ? $current['Url'] : $current['File']));
			if ($music_server->cachetags && isset($music_server->tags[$current]))
				$tags = $music_server->tags[$current];
			if ($music_server->stripdirs)
				$current = preg_replace('|.*[/\\\\]|', '', $current);
			if ($music_server->stripexts)
				$current = preg_replace('|\.[^.]+$|', '', $current);
			if ($music_server->cachetags && isset($tags))
				$current .= '{#music} : {#highlite}' . $tags['Title'] . '{#music} by {#highlite}' . $tags['Artist'];
		} else {
			$current = 'In-game music';
		}

		// show chat message
		$message = formatText($music_server->messages['CURRENT'][0],
		                      $current);
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
	elseif ($command['params'][0] == 'reload') {

		// check for admin ability
		if ($aseco->allowAbility($player, 'chat_musicadmin')) {
			// read & parse config file
			if (!$settings = $aseco->xml_parser->parseXml('musicserver.xml')) {
				trigger_error('Could not read/parse Music server config file musicserver.xml !', E_USER_WARNING);
				$message = '{#server}> {#error}Could not read/parse Music server config file!';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				return;
			}
			$music_server = new Music($settings['SETTINGS']);
			if ($music_server->cachetags)
				refresh_tags($aseco, $music_server);

			// log console message
			$aseco->console('{1} [{2}] reloaded config {3} !', $logtitle, $login, 'musicserver.xml');

			// show chat message
			$message = formatText($music_server->messages['RELOADED'][0],
			                      $chattitle, $player->nickname);
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

			// throw 'musicbox reloaded' event
			$aseco->releaseEvent('onMusicboxReloaded', null);
		} else {
			$message = $aseco->getChatMessage('NO_ADMIN');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}
	elseif ($command['params'][0] == 'next') {

		// check for admin ability
		if ($aseco->allowAbility($player, 'chat_musicadmin')) {
			// check remote or local song access
			$song = $music_server->server . $music_server->nextSong();
			if (!http_get_file($song, true) &&
			    !file_exists($aseco->server->gamedir . $song)) {
				trigger_error('Could not access song ' . $song . ' !', E_USER_WARNING);
			} else {
				// load next song
				$aseco->client->query('SetForcedMusic', $music_server->override, $song);
				$music_server->mannext = true;
				$song = $music_server->currentSong();

				// log console message
				$aseco->console('{1} [{2}] loaded next song {3} !', $logtitle, $login, $song);

				// show chat message
				if ($music_server->stripdirs)
					$song = preg_replace('|.*[/\\\\]|', '', $song);
				if ($music_server->stripexts)
					$song = preg_replace('|\.[^.]+$|', '', $song);
				$message = formatText($music_server->messages['NEXT'][0],
				                      $chattitle, $player->nickname, $song);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			}
		} else {
			$message = $aseco->getChatMessage('NO_ADMIN');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}
	elseif ($command['params'][0] == 'sort') {

		// check for admin ability
		if ($aseco->allowAbility($player, 'chat_musicadmin')) {
			// sort songs list and clear jukebox
			sort($music_server->songs);
			$music_server->jukebox = array();

			// log console message
			$aseco->console('{1} [{2}] sorted song list!', $logtitle, $login);

			// show chat message
			$message = formatText($music_server->messages['SORTED'][0],
			                      $chattitle, $player->nickname);
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		} else {
			$message = $aseco->getChatMessage('NO_ADMIN');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}
	elseif ($command['params'][0] == 'shuffle') {

		// check for admin ability
		if ($aseco->allowAbility($player, 'chat_musicadmin')) {
			// randomize songs list and clear jukebox
			shuffle($music_server->songs);
			$music_server->jukebox = array();

			// log console message
			$aseco->console('{1} [{2}] shuffled song list!', $logtitle, $login);

			// show chat message
			$message = formatText($music_server->messages['SHUFFLED'][0],
			                      $chattitle, $player->nickname);
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		} else {
			$message = $aseco->getChatMessage('NO_ADMIN');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}
	elseif ($command['params'][0] == 'override' && $command['params'][1] != '') {

		// check for admin ability
		if ($aseco->allowAbility($player, 'chat_musicadmin')) {
			$param = strtoupper($command['params'][1]);
			if ($param == 'ON' || $param == 'OFF') {
				$music_server->override = ($param == 'ON');

				// log console message
				$aseco->console('{1} [{2}] set music override {3} !', $logtitle, $login, ($music_server->override ? 'ON' : 'OFF'));

				// show chat message
				$message = '{#server}> {#music}Music override set to ' . ($music_server->override ? 'Enabled' : 'Disabled');
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $aseco->getChatMessage('NO_ADMIN');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}
	elseif ($command['params'][0] == 'autonext' && $command['params'][1] != '') {

		// check for admin ability
		if ($aseco->allowAbility($player, 'chat_musicadmin')) {
			$param = strtoupper($command['params'][1]);
			if ($param == 'ON' || $param == 'OFF') {
				$music_server->autonext = ($param == 'ON');

				// log console message
				$aseco->console('{1} [{2}] set music autonext {3} !', $logtitle, $login, ($music_server->autonext ? 'ON' : 'OFF'));

				// show chat message
				$message = '{#server}> {#music}Music autonext set to ' . ($music_server->autonext ? 'Enabled' : 'Disabled');
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $aseco->getChatMessage('NO_ADMIN');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}
	elseif ($command['params'][0] == 'autoshuffle' && $command['params'][1] != '') {

		// check for admin ability
		if ($aseco->allowAbility($player, 'chat_musicadmin')) {
			$param = strtoupper($command['params'][1]);
			if ($param == 'ON' || $param == 'OFF') {
				$music_server->autoshuffle = ($param == 'ON');

				// log console message
				$aseco->console('{1} [{2}] set music autoshuffle {3} !', $logtitle, $login, ($music_server->autoshuffle ? 'ON' : 'OFF'));

				// show chat message
				$message = '{#server}> {#music}Music autoshuffle set to ' . ($music_server->autoshuffle ? 'Enabled' : 'Disabled');
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $aseco->getChatMessage('NO_ADMIN');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}
	elseif ($command['params'][0] == 'allowjb' && $command['params'][1] != '') {

		// check for admin ability
		if ($aseco->allowAbility($player, 'chat_musicadmin')) {
			$param = strtoupper($command['params'][1]);
			if ($param == 'ON' || $param == 'OFF') {
				$music_server->allowjb = ($param == 'ON');

				// log console message
				$aseco->console('{1} [{2}] set allow music jukebox {3} !', $logtitle, $login, ($music_server->allowjb ? 'ON' : 'OFF'));

				// show chat message
				$message = '{#server}> {#music}Allow music jukebox set to ' . ($music_server->allowjb ? 'Enabled' : 'Disabled');
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $aseco->getChatMessage('NO_ADMIN');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}
	elseif ($command['params'][0] == 'stripdirs' && $command['params'][1] != '') {

		// check for admin ability
		if ($aseco->allowAbility($player, 'chat_musicadmin')) {
			$param = strtoupper($command['params'][1]);
			if ($param == 'ON' || $param == 'OFF') {
				$music_server->stripdirs = ($param == 'ON');

				// log console message
				$aseco->console('{1} [{2}] set strip subdirs {3} !', $logtitle, $login, ($music_server->stripdirs ? 'ON' : 'OFF'));

				// show chat message
				$message = '{#server}> {#music}Strip subdirs set to ' . ($music_server->stripdirs ? 'Enabled' : 'Disabled');
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $aseco->getChatMessage('NO_ADMIN');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}
	elseif ($command['params'][0] == 'stripexts' && $command['params'][1] != '') {

		// check for admin ability
		if ($aseco->allowAbility($player, 'chat_musicadmin')) {
			$param = strtoupper($command['params'][1]);
			if ($param == 'ON' || $param == 'OFF') {
				$music_server->stripexts = ($param == 'ON');

				// log console message
				$aseco->console('{1} [{2}] set strip extensions {3} !', $logtitle, $login, ($music_server->stripexts ? 'ON' : 'OFF'));

				// show chat message
				$message = '{#server}> {#music}Strip extensions set to ' . ($music_server->stripexts ? 'Enabled' : 'Disabled');
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $aseco->getChatMessage('NO_ADMIN');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}
	elseif ($command['params'][0] == 'off') {

		// check for admin ability
		if ($aseco->allowAbility($player, 'chat_musicadmin')) {
			// disable music
			$aseco->client->query('SetForcedMusic', $music_server->override, '');
			// disable autonext and jukebox
			$music_server->autonext = false;
			$music_server->allowjb = false;
			$music_server->jukebox = array();

			// log console message
			$aseco->console('{1} [{2}] disabled music & song jukebox!', $logtitle, $login);

			// show chat message
			$message = formatText($music_server->messages['SHUTDOWN'][0],
			                      $chattitle, $player->nickname);
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		} else {
			$message = $aseco->getChatMessage('NO_ADMIN');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}
	elseif ($command['params'][0] == 'jukebox' ||
	        $command['params'][0] == 'jb') {

		if (!empty($music_server->jukebox)) {
			$head = 'Upcoming songs in the jukebox:';
			$page = array();
			$page[] = array('Id', 'Filename');
			$sid = 1;
			$lines = 0;
			$player->msgs = array();
			$player->msgs[0] = array(1, $head, array(0.9, 0.1, 0.8), array('Icons64x64_1', 'Music'));
			foreach ($music_server->jukebox as $sid) {
				$song = $music_server->currentSong($sid);
				if ($music_server->stripdirs)
					$song = preg_replace('|.*[/\\\\]|', '', $song);
				if ($music_server->stripexts)
					$song = preg_replace('|\.[^.]+$|', '', $song);
				$page[] = array(str_pad($sid, 2, '0', STR_PAD_LEFT) . '.',
				                '{#black}' . $song);
				$sid++;
				if (++$lines > 14) {
					$player->msgs[] = $page;
					$lines = 0;
					$page = array();
					$page[] = array('Id', 'Filename');
				}
			}
			// add if last batch exists
			if (count($page) > 1)
				$player->msgs[] = $page;

			// display ManiaLink message
			display_manialink_multi($player);
		} else {
			$message = $music_server->messages['JUKEBOX_EMPTY'][0];
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}
	elseif ($command['params'][0] == 'drop') {

		// check for a song by this player
		if (array_key_exists($login, $music_server->jukebox)) {
			// delete song from jukebox
			$sid = $music_server->jukebox[$login];
			unset($music_server->jukebox[$login]);
			$song = $music_server->currentSong($sid);

			// show chat message
			if ($music_server->stripdirs)
				$song = preg_replace('|.*[/\\\\]|', '', $song);
			if ($music_server->stripexts)
				$song = preg_replace('|\.[^.]+$|', '', $song);
			$message = formatText($music_server->messages['JUKEBOX_DROP'][0],
			                      stripColors($player->nickname), $song);
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		} else {
			$message = $music_server->messages['JUKEBOX_NODROP'][0];
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}
	elseif (is_numeric($command['params'][0])) {

		// check whether jukeboxing is allowed
		if ($music_server->allowjb) {
			// check song ID
			$sid = intval($command['params'][0]);
			if ($sid > 0 && $sid <= count($music_server->songs)) {
				// check for song by this player in jukebox
				if (!array_key_exists($login, $music_server->jukebox)) {
					// check if song is already queued in jukebox
					if (!in_array($sid, $music_server->jukebox)) {
						// jukebox song
						$music_server->jukebox[$login] = $sid;
						$song = $music_server->currentSong($sid);

						// show chat message
						if ($music_server->stripdirs)
							$song = preg_replace('|.*[/\\\\]|', '', $song);
						if ($music_server->stripexts)
							$song = preg_replace('|\.[^.]+$|', '', $song);
						$message = formatText($music_server->messages['JUKEBOX'][0],
						                      stripColors($player->nickname), $song);
						$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
					} else {
						$message = $music_server->messages['JUKEBOX_DUPL'][0];
						$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					}
				} else {
					$message = $music_server->messages['JUKEBOX_ALREADY'][0];
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				}
			} else {
				$message = $music_server->messages['JUKEBOX_NOTFOUND'][0];
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $music_server->messages['NO_JUKEBOX'][0];
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}
	else {
		$message = '{#server}> {#error}Unknown music command or missing parameter: {#highlite}$i ' . $arglist;
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
}  // chat_music


// called @ onPlayerManialinkPageAnswer
// Handles ManiaLink music responses
// [0]=PlayerUid, [1]=Login, [2]=Answer
function event_music($aseco, $answer) {

	// leave actions outside -4000 - -2101 to other handlers
	if ($answer[2] >= -4000 && $answer[2] <= -2101) {
		// get player
		$player = $aseco->server->players->getPlayer($answer[1]);

		// log clicked command
		$aseco->console('player {1} clicked command "/music {2}"',
		                $player->login, abs($answer[2])-2100);

		// jukebox selected song
		$command = array();
		$command['author'] = $player;
		$command['params'] = abs($answer[2])-2100;
		chat_music($aseco, $command);
	}
}  // event_music
?>
