<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * TMX info plugin.
 * Displays TMX track info & records, and provides world record message
 * at start of each track.
 * Created by Xymph
 *
 * Dependencies: none
 */

require_once('includes/tmxinfofetcher.inc.php');  // provides access to TMX info

Aseco::registerEvent('onNewChallenge2', 'tmx_worldrec');

Aseco::addChatCommand('tmxinfo', 'Displays TMX info {Track_ID/TMX_ID} {sec}');
Aseco::addChatCommand('tmxrecs', 'Displays TMX records {Track_ID/TMX_ID} {sec}');

global $tmxdata;  // cached TMX data

// called @ onNewChallenge2
function tmx_worldrec($aseco, $data) {
	global $tmxdata;

	// obtain TMX records
	$tmxdata = $aseco->server->challenge->tmx;
	if ($tmxdata && !empty($tmxdata->recordlist)) {
		// check whether to show TMX record at start of track
		if ($aseco->settings['show_tmxrec'] > 0) {
			$message = formatText($aseco->getChatMessage('TMXREC'),
			                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
			                       $tmxdata->recordlist[0]['time'] :
			                       formatTime($tmxdata->recordlist[0]['time'])),
			                      $tmxdata->recordlist[0]['name']);
			if ($aseco->server->getGame() == 'TMF' && $aseco->settings['show_tmxrec'] == 2 &&
			    function_exists('send_window_message'))
				send_window_message($aseco, $message, false);
			else
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		}

		// notify records panel
		if ($aseco->server->getGame() == 'TMF') {
			setRecordsPanel('tmx', ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
			                        str_pad($tmxdata->recordlist[0]['time'], 5, ' ', STR_PAD_LEFT) :
			                        formatTime($tmxdata->recordlist[0]['time'])));
		}
	} else {
		// notify records panel
		if ($aseco->server->getGame() == 'TMF') {
			setRecordsPanel('tmx', ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
			                        '  ---' : '   --.--'));
		}
	}
}  // tmx_worldrec

function chat_tmxinfo($aseco, $command) {
	global $tmxdata;

	$player = $command['author'];
	$login = $player->login;

	$sections = array('TMO', 'TMS', 'TMN', 'TMU', 'TMNF');
	$command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));

	// check for optional Track/TMX ID parameter
	$id = $aseco->server->challenge->uid;
	$name = $aseco->server->challenge->name;
	$game = $aseco->server->getGame();
	if ($command['params'][0] != '') {
		if (is_numeric($command['params'][0]) && $command['params'][0] > 0) {
			$tid = ltrim($command['params'][0], '0');
			// check for possible track ID
			if ($tid <= count($player->tracklist)) {
				// find UID by given track ID
				$tid--;
				$id = $player->tracklist[$tid]['uid'];
				$name = $player->tracklist[$tid]['name'];
			} else {
				// consider it a TMX ID
				$id = $tid;
				$name = '';
			}

			// check for optional TMX section parameter
			if ($game == 'TMF' && isset($command['params'][1])) {
				$game = strtoupper($command['params'][1]);
				if (!in_array($game, $sections)) {
					$message = '{#server}> {#error}No such section on TMX!';
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					return;
				}
			} else {  // TMN/TMS/TMO or no section
				if ($game == 'TMF') {
					if ($aseco->server->packmask == 'Stadium')
						$game = 'TMNF';
					else
						$game = 'TMU';
				}
			}
		} else {
			$message = '{#server}> {#highlite}' . $command['params'][0] . '{#error} is not a valid Track/TMX ID!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			return;
		}
	} else {
		if ($game == 'TMF') {
			if ($aseco->server->packmask == 'Stadium')
				$game = 'TMNF';
			else
				$game = 'TMU';
		}
	}

	// obtain TMX info
	if (isset($tmxdata->uid) && $tmxdata->uid == $id) {
		$data = $tmxdata;  // use cached data
	} else {
		$data = new TMXInfoFetcher($game, $id, false);
	}
	if (!$data->name) {
		$message = '{#server}> {#highlite}' . ($name != '' ? stripColors($name) : $id) .
		           '{#error} is not a known TMX track, or wrong TMX section, or TMX is down!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// compile & send message
	if ($aseco->server->getGame() == 'TMN') {
		$stats = 'TMX Info for: {#black}' . $data->name . LF . LF;
		$stats .= '$gTMX ID       : {#black}' . $data->id . LF;
		$stats .= '$gUID             : {#black}$n' . $data->uid . '$m' . LF;
		$stats .= '$gAuthor        : {#black}' . $data->author . LF;
		$stats .= '$gUploaded   : {#black}' . preg_replace('/^\d\d\d\d/', '\$n$0\$m', preg_replace('/:\d\d$/', '', $data->uploaded)) . LF;
		$stats .= '$gUpdated     : {#black}' . preg_replace('/^\d\d\d\d/', '\$n$0\$m', preg_replace('/:\d\d$/', '', $data->updated)) . LF;
		$stats .= '$gType/Style: {#black}' . $data->type . '$g / {#black}' . $data->style . LF;
		$stats .= '$gEnv/Mood : {#black}' . $data->envir . '$g / {#black}' . $data->mood . LF;
		$stats .= '$gRoutes       : {#black}' . $data->routes . LF;
		$stats .= '$gDifficulty    : {#black}' . $data->diffic . LF;
		$stats .= '$gLength       : {#black}' . $data->length . LF;
		$stats .= '$gAwards      : {#black}' . $data->awards . LF;
		$stats .= '$gLB Rating   : {#black}' . $data->lbrating;

		// display popup message
		$aseco->client->query('SendDisplayServerMessageToLogin', $login, $aseco->formatColors($stats), 'OK', '', 0);

	} elseif ($aseco->server->getGame() == 'TMF') {
		$header = 'TMX Info for: {#black}' . $data->name;
		$links = array($data->imageurl . '&.jpg', false,
		               '$l[' . $data->pageurl . ']Visit TMX Page',
		               '$l[' . $data->dloadurl . ']Download Track');
		$stats = array();
		$stats[] = array('TMX ID', '{#black}' . $data->id,
		                 'Type/Style', '{#black}' . $data->type . '$g / {#black}' . $data->style);
		$stats[] = array('Section', '{#black}' . $data->section,
		                 'Env/Mood', '{#black}' . $data->envir . '$g / {#black}' . $data->mood);
		$stats[] = array('UID', '{#black}$n' . $data->uid,
		                 'Routes', '{#black}' . $data->routes);
		$stats[] = array('Author', '{#black}' . $data->author,
		                 'Difficulty', '{#black}' . $data->diffic);
		$stats[] = array('Uploaded', '{#black}' . preg_replace('/:\d\d$/', '', $data->uploaded),
		                 'Length', '{#black}' . $data->length);
		$stats[] = array('Updated', '{#black}' . preg_replace('/:\d\d$/', '', $data->updated),
		                 'Awards', '{#black}' . $data->awards);
		$stats[] = array('LB Rating', '{#black}' . $data->lbrating,
		                 'Replay', ($data->replayurl ?
		                 '{#black}$l[' . $data->replayurl . ']Download$l' : '<none>'));

		// display custom ManiaLink message
		display_manialink_track($login, $header, array('Icons64x64_1', 'Maximize', -0.01), $links, $stats, array(1.15, 0.2, 0.45, 0.2, 0.3), 'OK');

	} else {  // TMS/TMO
		$stats = '{#server}TMX Info for: {#highlite}' . $data->name . LF;
		$stats .= '{#server}TMX ID   : {#highlite}' . $data->id . LF;
		$stats .= '{#server}UID         : {#highlite}' . $data->uid . LF;
		$stats .= '{#server}Author    : {#highlite}' . $data->author . LF;
		$stats .= '{#server}LB Rating: {#highlite}' . $data->lbrating;

		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($stats), $login);
	}
}  // chat_tmxinfo

function chat_tmxrecs($aseco, $command) {
	global $tmxdata;

	$player = $command['author'];
	$login = $player->login;

	$sections = array('TMO', 'TMS', 'TMN', 'TMU', 'TMNF');
	$command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));

	// check for optional Track/TMX ID parameter
	$id = $aseco->server->challenge->uid;
	$name = $aseco->server->challenge->name;
	$game = $aseco->server->getGame();
	if ($command['params'][0] != '') {
		if (is_numeric($command['params'][0]) && $command['params'][0] > 0) {
			$tid = ltrim($command['params'][0], '0');
			// check for possible track ID
			if ($tid <= count($player->tracklist)) {
				// find UID by given track ID
				$tid--;
				$id = $player->tracklist[$tid]['uid'];
				$name = $player->tracklist[$tid]['name'];
			} else {
				// consider it a TMX ID
				$id = $tid;
				$name = '';
			}

			// check for optional TMX section parameter
			if ($game == 'TMF' && isset($command['params'][1])) {
				$game = strtoupper($command['params'][1]);
				if (!in_array($game, $sections)) {
					$message = '{#server}> {#error}No such section on TMX!';
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					return;
				}
			} else {  // TMN/TMS/TMO or no section
				if ($game == 'TMF') {
					if ($aseco->server->packmask == 'Stadium')
						$game = 'TMNF';
					else
						$game = 'TMU';
				}
			}
		} else {
			$message = '{#server}> {#highlite}' . $tid . '{#error} is not a valid Track/TMX ID!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			return;
		}
	} else {
		if ($game == 'TMF') {
			if ($aseco->server->packmask == 'Stadium')
				$game = 'TMNF';
			else
				$game = 'TMU';
		}
	}

	// obtain TMX records
	if (isset($tmxdata->uid) && $tmxdata->uid == $id) {
		$data = $tmxdata;  // use cached data
	} else {
		$data = new TMXInfoFetcher($game, $id, true);
	}
	if (!$data->name) {
		$message = '{#server}> {#highlite}' . ($name != '' ? stripColors($name) : $id) .
		           '{#error} is not a known TMX track, or wrong TMX section, or TMX is down!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if (empty($data->recordlist)) {
		$message = '{#server}> {#error}No TMX records found for {#highlite}$i ' . $data->name;
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// compile message
	if ($aseco->server->getGame() == 'TMN') {
		$recs = 'TMX Top-10 Records: {#black}' . $data->name;
		$top = 10;
		$bgn = '{#black}';  // name begin
		$end = '$g';  // ... & end colors

		for ($i = 0; $i < count($data->recordlist) && $i < $top; $i++) {
			$recs .= LF . '$g' . str_pad($i+1, 2, '0', STR_PAD_LEFT) . '.  ' . $bgn
			         . str_pad($data->recordlist[$i]['name'], 15) . $end . ' - '
			         . formatTime($data->recordlist[$i]['time']);
		}

		// display popup message
		$aseco->client->query('SendDisplayServerMessageToLogin', $login, $aseco->formatColors($recs), 'OK', '', 0);

	} elseif ($aseco->server->getGame() == 'TMF') {
		$header = 'TMX Top-10 Records: {#black}' . $data->name;
		$recs = array();
		$top = 10;
		$bgn = '{#black}';  // name begin

		for ($i = 0; $i < count($data->recordlist) && $i < $top; $i++) {
			$recs[] = array(str_pad($i+1, 2, '0', STR_PAD_LEFT) . '.',
			                $bgn . $data->recordlist[$i]['name'],
			                ($data->type == 'Stunts' ?
			                 $data->recordlist[$i]['time'] :
			                 formatTime($data->recordlist[$i]['time'])));
		}

		// display ManiaLink message
		display_manialink($player->login, $header, array('BgRaceScore2', 'Podium'), $recs, array(0.9, 0.1, 0.5, 0.3), 'OK');

	} else {  // TMS/TMO
		$recs = '{#server}> TMX Top-4 Records: {#highlite}' . $data->name;
		$top = 4;
		$bgn = '{#highlite}';
		$end = '{#highlite}';

		for ($i = 0; $i < count($data->recordlist) && $i < $top; $i++) {
			$recs .= LF . '$g' . str_pad($i+1, 2, '0', STR_PAD_LEFT) . '.  ' . $bgn
			         . str_pad($data->recordlist[$i]['name'], 15) . $end . ' - '
			         . ($data->type == 'Stunts' ?
			            $data->recordlist[$i]['time'] :
			            formatTime($data->recordlist[$i]['time']));
		}

		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($recs), $login);
	}
}  // chat_tmxrecs
?>
