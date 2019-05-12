<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Chat plugin.
 * Shows new/online Dedimania world records and their relations on the
 * current track.
 * Created by Xymph
 *
 * Dependencies: requires plugin.dedimania.php, plugin.checkpoints.php
 *               used by plugin.dedimania.php
 */

Aseco::addChatCommand('helpdedi', 'Displays info about the Dedimania records system');
Aseco::addChatCommand('dedihelp', 'Displays info about the Dedimania records system');
Aseco::addChatCommand('dedirecs', 'Displays all Dedimania records on current track');
if (!INHIBIT_RECCMDS) {
	Aseco::addChatCommand('dedinew', 'Shows newly driven Dedimania records');
	Aseco::addChatCommand('dedilive', 'Shows Dedimania records of online players');
	Aseco::addChatCommand('dedipb', 'Shows your Dedimania personal best on current track');
	Aseco::addChatCommand('dedifirst', 'Shows first Dedimania record on current track');
	Aseco::addChatCommand('dedilast', 'Shows last Dedimania record on current track');
	Aseco::addChatCommand('dedinext', 'Shows next better Dedimania record to beat');
	Aseco::addChatCommand('dedidiff', 'Shows your difference to first Dedimania record');
	Aseco::addChatCommand('dedirange', 'Shows difference first to last Dedimania record');
}
Aseco::addChatCommand('dedicps', 'Sets Dedimania record checkspoints tracking');
Aseco::addChatCommand('dedistats', 'Displays Dedimania track statistics');
Aseco::addChatCommand('dedicptms', 'Displays all Dedimania records\' checkpoint times');
Aseco::addChatCommand('dedisectms', 'Displays all Dedimania records\' sector times');

function chat_dedihelp($aseco, $command) { chat_helpdedi($aseco, $command); }
function chat_helpdedi($aseco, $command) {

	// compile & display help message
	if ($aseco->server->getGame() == 'TMN') {
		$help = '{#dedimsg}Dedimania$g is an online World Records database for {#black}all$g' . LF;
		$help .= 'TrackMania games.  See its official site at:' . LF;
		$help .= '{#black}http://www.dedimania.com/SITE/$g and the records database:' . LF;
		$help .= '{#black}http://www.dedimania.com/tmstats/?do=stat$g .' . LF . LF;
		$help .= 'Dedimania records are stored per game (TMN, TMU, etc)' . LF;
		$help .= 'and mode (TimeAttack, Rounds, etc) and shared between' . LF;
		$help .= 'all servers that operate with Dedimania support.' . LF . LF;
		$help .= 'The available Dedimania commands are similar to local' . LF;
		$help .= 'record commands:' . LF;
		$help .= '{#black}/dedirecs$g, {#black}/dedinew$g, {#black}/dedilive$g, {#black}/dedipb$g, {#black}/dedicps$g, {#black}/dedistats$g,' . LF;
		$help .= '{#black}/dedifirst$g, {#black}/dedilast$g, {#black}/dedinext$g, {#black}/dedidiff$g, {#black}/dedirange$g' . LF;
		$help .= 'See the {#black}/helpall$g command for detailed descriptions.';

		// display popup message
		$aseco->client->query('SendDisplayServerMessageToLogin', $command['author']->login, $aseco->formatColors($help), 'OK', '', 0);

	} elseif ($aseco->server->getGame() == 'TMF') {
		$header = 'Dedimania information:';
		$data = array();
		$data[] = array('{#dedimsg}Dedimania$g is an online World Records database for {#black}all');
		$data[] = array('TrackMania games.  See its official site at:');
		$data[] = array('{#black}$l[http://www.dedimania.com/SITE/]http://www.dedimania.com/SITE/$l$g and the records database:');
		$data[] = array('{#black}$l[http://www.dedimania.com/tmstats/?do=stat]http://www.dedimania.com/tmstats/?do=stat$l$g .');
		$data[] = array();
		$data[] = array('Dedimania records are stored per game (TMN, TMU, etc)');
		$data[] = array('and mode (TimeAttack, Rounds, etc) and shared between');
		$data[] = array('all servers that operate with Dedimania support.');
		$data[] = array();
		$data[] = array('The available Dedimania commands are similar to local');
		$data[] = array('record commands:');
		$data[] = array('{#black}/dedirecs$g, {#black}/dedinew$g, {#black}/dedilive$g, {#black}/dedipb$g, {#black}/dedicps$g, {#black}/dedistats$g,');
		$data[] = array('{#black}/dedifirst$g, {#black}/dedilast$g, {#black}/dedinext$g, {#black}/dedidiff$g, {#black}/dedirange$g');
		$data[] = array();
		$data[] = array('See the {#black}/helpall$g command for detailed descriptions.');

		// display ManiaLink message
		display_manialink($command['author']->login, $header, array('Icons64x64_1', 'TrackInfo', -0.01), $data, array(0.95), 'OK');
	}
}  // chat_helpdedi

function chat_dedirecs($aseco, $command) {
	global $dedi_db;

	$player = $command['author'];
	$login = $player->login;
	$dedi_recs = $dedi_db['Challenge']['Records'];

	// split params into array
	$arglist = explode(' ', strtolower(preg_replace('/ +/', ' ', $command['params'])));

	// process optional relations commands
	if ($arglist[0] == 'help') {
		if ($aseco->server->getGame() == 'TMN') {
			$help = '{#black}/dedirecs <option>$g shows Dedimania records and relations' . LF;
			$help .= '  - {#black}help$g, displays this help information' . LF;
			$help .= '  - {#black}pb$g, your personal best on current track' . LF;
			$help .= '  - {#black}new$g, newly driven records' . LF;
			$help .= '  - {#black}live$g, records of online players' . LF;
			$help .= '  - {#black}first$g, first ranked record on current track' . LF;
			$help .= '  - {#black}last$g, last ranked record on current track' . LF;
			$help .= '  - {#black}next$g, next better ranked record to beat' . LF;
			$help .= '  - {#black}diff$g, your difference to first ranked record' . LF;
			$help .= '  - {#black}range$g, difference first to last ranked record' . LF;
			$help .= LF . 'Without an option, the normal records list is displayed.';

			// display popup message
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $aseco->formatColors($help), 'OK', '', 0);
		} elseif ($aseco->server->getGame() == 'TMF') {
			$header = '{#black}/dedirecs <option>$g shows Dedimania records and relations:';
			$help = array();
			$help[] = array('...', '{#black}help',
			                'Displays this help information');
			$help[] = array('...', '{#black}pb',
			                'Shows your personal best on current track');
			$help[] = array('...', '{#black}new',
			                'Shows newly driven records');
			$help[] = array('...', '{#black}live',
			                'Shows records of online players');
			$help[] = array('...', '{#black}first',
			                'Shows first ranked record on current track');
			$help[] = array('...', '{#black}last',
			                'Shows last ranked record on current track');
			$help[] = array('...', '{#black}next',
			                'Shows next better ranked record to beat');
			$help[] = array('...', '{#black}diff',
			                'Shows your difference to first ranked record');
			$help[] = array('...', '{#black}range',
			                'Shows difference first to last ranked record');
			$help[] = array();
			$help[] = array('Without an option, the normal records list is displayed.');

			// display ManiaLink message
			display_manialink($login, $header, array('Icons64x64_1', 'TrackInfo', -0.01), $help, array(1.2, 0.05, 0.3, 0.85), 'OK');
		}
		return;
	}
	elseif ($arglist[0] == 'pb') {
		chat_dedipb($aseco, $command);
		return;
	}
	elseif ($arglist[0] == 'new') {
		chat_dedinew($aseco, $command);
		return;
	}
	elseif ($arglist[0] == 'live') {
		chat_dedilive($aseco, $command);
		return;
	}
	elseif ($arglist[0] == 'first') {
		chat_dedifirst($aseco, $command);
		return;
	}
	elseif ($arglist[0] == 'last') {
		chat_dedilast($aseco, $command);
		return;
	}
	elseif ($arglist[0] == 'next') {
		chat_dedinext($aseco, $command);
		return;
	}
	elseif ($arglist[0] == 'diff') {
		chat_dedidiff($aseco, $command);
		return;
	}
	elseif ($arglist[0] == 'range') {
		chat_dedirange($aseco, $command);
		return;
	}

	if (!$total = count($dedi_recs)) {
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No Dedimania records found!'), $login);
		return;
	}
	$maxrank = max($dedi_db['ServerMaxRank'], $player->dedirank);

	// display popup window for TMN
	if ($aseco->server->getGame() == 'TMN') {
		$head = 'Current TOP ' . $maxrank . ' Dedimania Records:' . LF;
		$msg = '';
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = 1;

		// create list of records
		for ($i = 0; $i < $total; $i++) {
			$cur_record = $dedi_recs[$i];
			$nick = $cur_record['NickName'];
			if (!$aseco->settings['lists_colornicks'])
				$nick = stripColors($nick);
			$msg .= str_pad($i+1, 2, '0', STR_PAD_LEFT) . '.  {#black}'
			        . str_pad($nick, 20) . '$z - '
			        . ((isset($cur_record['NewBest']) && $cur_record['NewBest']) ? '{#black}': '')
			        . formatTime($cur_record['Best']) . LF;
			if (++$lines > 9) {
				$player->msgs[] = $aseco->formatColors($head . $msg);
				$lines = 0;
				$msg = '';
			}
		}
		// add if last batch exists
		if ($msg != '')
			$player->msgs[] = $aseco->formatColors($head . $msg);

		// display popup message
		if (count($player->msgs) == 2) {
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $player->msgs[1], 'OK', '', 0);
		} else {  // > 2
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $player->msgs[1], 'Close', 'Next', 0);
		}

	// display ManiaLink window for TMF
	} elseif ($aseco->server->getGame() == 'TMF') {
		$head = 'Current TOP ' . $maxrank . ' Dedimania Records:';
		$msg = array();
		$lines = 0;
		$player->msgs = array();
		// reserve extra width for $w tags
		$extra = ($aseco->settings['lists_colornicks'] ? 0.2 : 0);
		if ($dedi_db['ShowRecLogins'])
			$player->msgs[0] = array(1, $head, array(1.2+$extra, 0.1, 0.45+$extra, 0.4, 0.25), array('BgRaceScore2', 'Podium'));
		else
			$player->msgs[0] = array(1, $head, array(0.8+$extra, 0.1, 0.45+$extra, 0.25), array('BgRaceScore2', 'Podium'));

		// create list of records
		for ($i = 0; $i < $total; $i++) {
			$cur_record = $dedi_recs[$i];
			$nick = $cur_record['NickName'];
			if (!$aseco->settings['lists_colornicks'])
				$nick = stripColors($nick);
			if ($dedi_db['ShowRecLogins']) {
				$msg[] = array(str_pad($i+1, 2, '0', STR_PAD_LEFT) . '.',
				               '{#black}' . $nick,
				               '{#login}' . $cur_record['Login'],
				               ((isset($cur_record['NewBest']) && $cur_record['NewBest']) ? '{#black}': '') .
				               ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
				                $cur_record['Best'] : formatTime($cur_record['Best'])));
			} else {
				$msg[] = array(str_pad($i+1, 2, '0', STR_PAD_LEFT) . '.',
				               '{#black}' . $nick,
				               ((isset($cur_record['NewBest']) && $cur_record['NewBest']) ? '{#black}': '') .
				               ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
				                $cur_record['Best'] : formatTime($cur_record['Best'])));
			}
			if (++$lines > 14) {
				$player->msgs[] = $msg;
				$lines = 0;
				$msg = array();
			}
		}
		// add if last batch exists
		if (!empty($msg))
			$player->msgs[] = $msg;

		// display ManiaLink message
		display_manialink_multi($player);

	// show chat message for TMO & TMS
	} else {
		$top = 4;
		$msg = $aseco->formatColors("{#server}> Current TOP $top Dedimania Records:{#highlite}");
		// create list of records
		$total = ($total <= $top ? $total : $top);
		for ($i = 0; $i < $total; $i++) {
			$cur_record = $dedi_recs[$i];
			$msg .= LF . ($i+1) . '.  ' . str_pad(stripColors($cur_record['NickName']), 15)
			        . ' - ' . formatTime($cur_record['Best']);
		}
		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $msg, $login);
	}
}  // chat_dedirecs


/*
 * Universal function to generate list of Dedimania records for current track.
 * Called by chat_dedinew, chat_dedilive, endRace & beginRace (plugin.dedimania.php).
 * Show to a player if $login defined, otherwise show to all players.
 * $mode = 0 (only new), 1 (top-8 & online players at start of track),
 *         2 (top-6 & online during track), 3 (top-8 & new at end of track)
 * In modes 1/2/3 the last Dedimania record is also shown
 * top-8 is configurable via $dedi_db['ShowMinRecs']; top-6 is ShowMinRecs-2
 */
function show_dedirecs($aseco, $name, $uid, $dedi_recs, $login, $mode, $window) {
	global $dedi_db, $dedi_debug;

	$records = '$n';  // use narrow font

	if ($dedi_debug > 2)
		$aseco->console_text('show_dedirecs - dedi_recs' . CRLF . print_r($dedi_recs, true));

	// check for records
	if (!isset($dedi_recs) || ($total = count($dedi_recs)) == 0) {
		$totalnew = -1;
	} else {
		// check whether to show range
		if ($dedi_db['ShowRecsRange']) {
			// get the first & last Dedimania records
			$first = $dedi_recs[0];
			$last = $dedi_recs[$total-1];
			// compute difference between records
			if ($aseco->server->gameinfo->mode != Gameinfo::STNT) {
				$diff = $last['Best'] - $first['Best'];
				$sec = floor($diff/1000);
				$hun = ($diff - ($sec * 1000)) / 10;
			} else {  // Stunts
				$diff = $first['Best'] - $last['Best'];
			}
		}

		// get list of online players
		$players = array();
		foreach ($aseco->server->players->player_list as $pl) {
			$players[] = $pl->login;
		}

		// collect new records and records by online players
		$totalnew = 0;

		// go through each record
		for ($i = 0; $i < $total; $i++) {
			$cur_record = $dedi_recs[$i];

			// if the record is new then display it
			if (isset($cur_record['NewBest']) && $cur_record['NewBest']) {
				$totalnew++;
				$record_msg = formatText($aseco->getChatMessage('RANKING_RECORD_NEW_ON'),
				                         $i+1,
				                         stripColors($cur_record['NickName']),
				                         ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
				                          $cur_record['Best'] : formatTime($cur_record['Best'])));
				// always show new record
				$records .= $record_msg;
			} else {
				// check if player is online
				if (in_array($cur_record['Login'], $players) && $cur_record['Game'] ==
				    ($aseco->server->getGame() == 'TMF' ? 'TMU' : $aseco->server->getGame())) {
					$record_msg = formatText($aseco->getChatMessage('RANKING_RECORD_ON'),
					                         $i+1,
					                         stripColors($cur_record['NickName']),
					                         ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
					                          $cur_record['Best'] : formatTime($cur_record['Best'])));
					// check if last Dedimania record
					if ($mode != 0 && $i == $total-1) {
						$records .= $record_msg;
					}
					// check if always show (start of/during track)
					elseif ($mode == 1 || $mode == 2) {
						$records .= $record_msg;
					}
					else {
						// show record if < ShowMinRecs (end of track)
						if ($mode == 3 && $i < $dedi_db['ShowMinRecs']) {
							$records .= $record_msg;
						}
					}
				} else {
					$record_msg = formatText($aseco->getChatMessage('RANKING_RECORD'),
					                         $i+1,
					                         stripColors($cur_record['NickName']),
					                         ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
					                          $cur_record['Best'] : formatTime($cur_record['Best'])));
					// check if last Dedimania record
					if ($mode != 0 && $i == $total-1) {
						$records .= $record_msg;
					}
					// show offline record if < ShowMinRecs-2 (during track)
					elseif (($mode == 2 && $i < $dedi_db['ShowMinRecs']-2) ||
					// show offline record if < ShowMinRecs (start/end of track)
					        (($mode == 1 || $mode == 3) && $i < $dedi_db['ShowMinRecs'])) {
						$records .= $record_msg;
					}
				}
			}
		}
	}

	// define wording of the ranking message
	switch ($mode) {
	case 0:
		$timing = 'during';
		break;
	case 1:
		$timing = 'before';
		break;
	case 2:
		$timing = 'during';
		break;
	case 3:
		$timing = 'after';
		break;
	}

	// hyperlink track name
	$name = stripColors($name);
	if ($aseco->server->getGame() == 'TMF')
		$name = '$l[http://www.dedimania.com/tmstats/?do=stat&Show=RECORDS&RecOrder3=RANK-ASC&Uid=' . $uid . ']' . $name . '$l';

	// define the ranking message
	if ($totalnew > 0) {
		$message = formatText($dedi_db['Messages']['RANKING_NEW'][0],
		                      $name, $timing, $totalnew);
	}
	elseif ($totalnew == 0 && $records != '$n') {
		// check whether to show range
		if ($dedi_db['ShowRecsRange']) {
			$message = formatText($dedi_db['Messages']['RANKING_RANGE'][0],
			                      $name, $timing,
			                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
			                       $diff : sprintf("%d.%02d", $sec, $hun)));
		} else {
			$message = formatText($dedi_db['Messages']['RANKING'][0],
			                      $name, $timing);
		}
	}
	elseif ($totalnew == 0 && $records == '$n') {
		$message = formatText($dedi_db['Messages']['RANKING_NONEW'][0],
		                      $name, $timing);
	}
	else {  // $totalnew == -1
		$message = formatText($dedi_db['Messages']['RANKING_NONE'][0],
		                      $name, $timing);
	}

	// append the records if any
	if ($records != '$n') {
		$records = substr($records, 0, strlen($records)-2);  // strip trailing ", "
		$message .= LF . $records;
	}

	// show to player or all
	if ($login) {
		// strip 1 leading '>' to indicate a player message instead of system-wide
		$message = str_replace('{#server}>> ', '{#server}> ', $message);
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	} else {
		if ($window == 2 && function_exists('send_window_message'))
			send_window_message($aseco, $message, ($mode == 3));
		else
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
	}
}  // show_dedirecs

function chat_dedinew($aseco, $command) {
	global $dedi_db;

	// show only newly driven records
	show_dedirecs($aseco, $aseco->server->challenge->name, $aseco->server->challenge->uid, $dedi_db['Challenge']['Records'], $command['author']->login, 0, 0);
}  // chat_dedinew

function chat_dedilive($aseco, $command) {
	global $dedi_db;

	// show online & ShowMinRecs-2 records
	show_dedirecs($aseco, $aseco->server->challenge->name, $aseco->server->challenge->uid, $dedi_db['Challenge']['Records'], $command['author']->login, 2, 0);
}  // chat_dedilive

function chat_dedipb($aseco, $command) {
	global $dedi_db;

	$login = $command['author']->login;
	$dedi_recs = $dedi_db['Challenge']['Records'];

	$found = false;
	// find Dedimania record
	for ($i = 0; $i < count($dedi_recs); $i++) {
		$rec = $dedi_recs[$i];
		if ($rec['Login'] == $login && $rec['Game'] ==
		    ($aseco->server->getGame() == 'TMF' ? 'TMU' : $aseco->server->getGame())) {
			$score = $rec['Best'];
			$rank = $i;
			$found = true;
			break;
		}
	}

	if ($found) {
		$message = formatText($dedi_db['Messages']['PB'][0],
		                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
		                       $score : formatTime($score)), $rank+1);
		$message = $aseco->formatColors($message);
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
	} else {
		$message = $dedi_db['Messages']['PB_NONE'][0];
		$message = $aseco->formatColors($message);
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
	}
}  // chat_dedipb

function chat_dedifirst($aseco, $command) {
	global $dedi_db;

	$dedi_recs = $dedi_db['Challenge']['Records'];

	if (!empty($dedi_recs)) {
		// get the first Dedimania record
		$record = $dedi_recs[0];

		// show chat message
		$message = formatText($dedi_db['Messages']['FIRST_RECORD'][0])
		         . formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
		                                             1,
		                                             stripColors($record['NickName']),
		                                             ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
		                                              $record['Best'] : formatTime($record['Best'])));

		$message = substr($message, 0, strlen($message)-2);  // strip trailing ", "
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $command['author']->login);
	} else {
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No Dedimania records found!'), $command['author']->login);
	}
}  // chat_dedifirst

function chat_dedilast($aseco, $command) {
	global $dedi_db;

	$dedi_recs = $dedi_db['Challenge']['Records'];

	if ($total = count($dedi_recs)) {
		// get the last Dedimania record
		$record = $dedi_recs[$total-1];

		// show chat message
		$message = formatText($dedi_db['Messages']['LAST_RECORD'][0])
		         . formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
		                                             $total,
		                                             stripColors($record['NickName']),
		                                             ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
		                                              $record['Best'] : formatTime($record['Best'])));

		$message = substr($message, 0, strlen($message)-2);  // strip trailing ", "
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $command['author']->login);
	} else {
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No Dedimania records found!'), $command['author']->login);
	}
}  // chat_dedilast

function chat_dedinext($aseco, $command) {
	global $dedi_db;

	$login = $command['author']->login;
	$dedi_recs = $dedi_db['Challenge']['Records'];

	if ($total = count($dedi_recs)) {
		$found = false;
		// find Dedimania record
		for ($i = 0; $i < $total; $i++) {
			$rec = $dedi_recs[$i];
			if ($rec['Login'] == $login && $rec['Game'] ==
			    ($aseco->server->getGame() == 'TMF' ? 'TMU' : $aseco->server->getGame())) {
				$rank = $i;
				$found = true;
				break;
			}
		}

		if ($found) {
			// get current and next better Dedimania records
			$nextrank = ($rank > 0 ? $rank-1 : 0);
			$record = $dedi_recs[$rank];
			$next = $dedi_recs[$nextrank];

			// compute difference to next record
			if ($aseco->server->gameinfo->mode != Gameinfo::STNT) {
				$diff = $record['Best'] - $next['Best'];
				$sec = floor($diff/1000);
				$hun = ($diff - ($sec * 1000)) / 10;
			} else {  // Stunts mode
				$diff = $next['Best'] - $record['Best'];
			}

			// show chat message
			$message1 = formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
			                                              $rank+1,
			                                              stripColors($record['NickName']),
			                                              ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
			                                               $record['Best'] : formatTime($record['Best'])));
			$message1 = substr($message1, 0, strlen($message1)-2);  // strip trailing ", "
			$message2 = formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
			                                              $nextrank+1,
			                                              stripColors($next['NickName']),
			                                              ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
			                                               $next['Best'] : formatTime($next['Best'])));
			$message2 = substr($message2, 0, strlen($message2)-2);  // strip trailing ", "
			$message = formatText($dedi_db['Messages']['DIFF_RECORD'][0],
			                      $message1, $message2,
			                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
			                       $diff : sprintf("%d.%02d", $sec, $hun)));

			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		} else {
			// look for unranked time instead
			$order = ($aseco->server->gameinfo->mode == Gameinfo::STNT ? 'DESC' : 'ASC');
			$query = 'SELECT score FROM rs_times
			          WHERE playerID=' . $command['author']->id . ' AND
			                challengeID=' . $aseco->server->challenge->id . '
			          ORDER BY score ' . $order . ' LIMIT 1';
			$result = mysql_query($query);
			if (mysql_num_rows($result) > 0) {
				$unranked = mysql_fetch_object($result);
				$found = true;
			}
			mysql_free_result($result);

			if ($found) {
				// get the last Dedimania record
				$last = $dedi_recs[$total-1];

				// compute difference to next record
				if ($aseco->server->gameinfo->mode != Gameinfo::STNT) {
					$sign = ($unranked->score < $last['Best'] ? '-' : '');
					$diff = abs($unranked->score - $last['Best']);
					$sec = floor($diff/1000);
					$hun = ($diff - ($sec * 1000)) / 10;
				} else {  // Stunts mode
					$diff = $last['Best'] - $unranked->score;
				}

				// show chat message
				$message1 = formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
				                                              'PB',
				                                              stripColors($command['author']->nickname),
				                                              ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
				                                               $unranked->score : formatTime($unranked->score)));
				$message1 = substr($message1, 0, strlen($message1)-2);  // strip trailing ", "
				$message2 = formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
				                                              $total,
				                                              stripColors($last['NickName']),
				                                              ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
				                                               $last['Best'] : formatTime($last['Best'])));
				$message2 = substr($message2, 0, strlen($message2)-2);  // strip trailing ", "
				$message = formatText($dedi_db['Messages']['DIFF_RECORD'][0],
				                      $message1, $message2,
				                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
				                       $diff : sprintf("%s%d.%02d", $sign, $sec, $hun)));

				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			} else {
				$message = '{#server}> {#error}You don\'t have Dedimania a record on this track yet... use {#highlite}$i/dedilast';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		}
	} else {
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No Dedimania records found!'), $login);
	}
}  // chat_dedinext

function chat_dedidiff($aseco, $command) {
	global $dedi_db;

	$login = $command['author']->login;
	$dedi_recs = $dedi_db['Challenge']['Records'];

	if ($total = count($dedi_recs)) {
		$found = false;
		// find Dedimania record
		for ($i = 0; $i < $total; $i++) {
			$rec = $dedi_recs[$i];
			if ($rec['Login'] == $login && $rec['Game'] ==
			    ($aseco->server->getGame() == 'TMF' ? 'TMU' : $aseco->server->getGame())) {
				$rank = $i;
				$found = true;
				break;
			}
		}

		if ($found) {
			// get current and first Dedimania records
			$record = $dedi_recs[$rank];
			$first = $dedi_recs[0];

			// compute difference to first record
			if ($aseco->server->gameinfo->mode != Gameinfo::STNT) {
				$diff = $record['Best'] - $first['Best'];
				$sec = floor($diff/1000);
				$hun = ($diff - ($sec * 1000)) / 10;
			} else {  // Stunts mode
				$diff = $first['Best'] - $record['Best'];
			}

			// show chat message
			$message1 = formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
			                                              $rank+1,
			                                              stripColors($record['NickName']),
			                                              ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
			                                               $record['Best'] : formatTime($record['Best'])));
			$message1 = substr($message1, 0, strlen($message1)-2);  // strip trailing ", "
			$message2 = formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
			                                              1,
			                                              stripColors($first['NickName']),
			                                              ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
			                                               $first['Best'] : formatTime($first['Best'])));
			$message2 = substr($message2, 0, strlen($message2)-2);  // strip trailing ", "
			$message = formatText($dedi_db['Messages']['DIFF_RECORD'][0],
			                      $message1, $message2,
			                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
			                       $diff : sprintf("%d.%02d", $sec, $hun)));

			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		} else {
			$message = '{#server}> {#error}You don\'t have a Dedimania record on this track yet... use {#highlite}$i/dedilast';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	} else {
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No Dedimania records found!'), $login);
	}
}  // chat_dedidiff

function chat_dedirange($aseco, $command) {
	global $dedi_db;

	$dedi_recs = $dedi_db['Challenge']['Records'];

	if ($total = count($dedi_recs)) {
		// get the first & last Dedimania records
		$first = $dedi_recs[0];
		$last = $dedi_recs[$total-1];

		// compute difference between records
		if ($aseco->server->gameinfo->mode != Gameinfo::STNT) {
			$diff = $last['Best'] - $first['Best'];
			$sec = floor($diff/1000);
			$hun = ($diff - ($sec * 1000)) / 10;
		} else {  // Stunts mode
			$diff = $first['Best'] - $last['Best'];
		}

		// show chat message
		$message1 = formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
		                                              1,
		                                              stripColors($first['NickName']),
		                                              ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
		                                               $first['Best'] : formatTime($first['Best'])));
		$message1 = substr($message1, 0, strlen($message1)-2);  // strip trailing ", "
		$message2 = formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
		                                              $total,
		                                              stripColors($last['NickName']),
		                                              ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
		                                               $last['Best'] : formatTime($last['Best'])));
		$message2 = substr($message2, 0, strlen($message2)-2);  // strip trailing ", "
		$message = formatText($dedi_db['Messages']['DIFF_RECORD'][0],
		                      $message1, $message2,
		                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
		                       $diff : sprintf("%d.%02d", $sec, $hun)));

		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $command['author']->login);
	} else {
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No Dedimania records found!'), $command['author']->login);
	}
}  // chat_dedirange

function chat_dedicps($aseco, $command) {
	global $dedi_db, $checkpoints;  // from plugin.checkpoints.php

	$login = $command['author']->login;

	if ($aseco->settings['display_checkpoints']) {
		if (isset($checkpoints[$login]) && $checkpoints[$login]->loclrec != -1) {
			// set Dedimania checkpoints tracking
			$param = $command['params'];
			if (strtolower($param) == 'off') {
				$checkpoints[$login]->dedirec = -1;
				$message = '{#server}> {#dedimsg}Dedimania checkpoints tracking: {#highlite}OFF';
			}
			elseif ($param == '') {
				$checkpoints[$login]->dedirec = 0;
				$message = '{#server}> {#dedimsg}Dedimania checkpoints tracking: {#highlite}ON {#dedimsg}(your own or the last record)';
			}
			elseif (is_numeric($param) && $param > 0 && $param <= max($dedi_db['ServerMaxRank'], $command['author']->dedirank)) {
				$checkpoints[$login]->dedirec = intval($param);
				$message = '{#server}> {#dedimsg}Dedimania checkpoints tracking record: {#highlite}' . $checkpoints[$login]->dedirec;
			}
			else {
				$message = '{#server}> {#error}No such Dedimania record {#highlite}$i ' . $param;
			}
		} else {
			$message = '{#server}> {#error}You must first enable checkpoints tracking with {#highlite}$i /cps';
		}
	} else {
		$message = '{#server}> {#error}Dedimania checkpoints tracking permanently disabled by server';
	}
	// show chat message
	$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
}  // chat_dedicps

function chat_dedistats($aseco, $command) {
	global $dedi_db;

	$login = $command['author']->login;

	// compile & display stats message
	if ($aseco->server->getGame() == 'TMN') {
		$stats = '{#dedimsg}Dedimania$g Stats: {#black}' . stripColors($aseco->server->challenge->name) . LF . LF;
		$stats .= '$gServer MaxRank: {#black}$n' . $dedi_db['ServerMaxRank'] . '$m' . LF;
		$stats .= '$gYour MaxRank   : {#black}$n' . $command['author']->dedirank . '$m' . LF . LF;
		$stats .= '$gUID                : {#black}$n' . $dedi_db['Challenge']['Uid'] . '$m' . LF;
		$stats .= '$gTotal Races   : {#black}' . $dedi_db['Challenge']['TotalRaces'] . LF;
		$stats .= '$gTotal Players : {#black}' . $dedi_db['Challenge']['TotalPlayers'] . LF;
		$stats .= '$gAvg. Players  : {#black}' . ($dedi_db['Challenge']['TotalRaces'] > 0 ? round($dedi_db['Challenge']['TotalPlayers'] / $dedi_db['Challenge']['TotalRaces'], 2) : 0);

		// display popup message
		$aseco->client->query('SendDisplayServerMessageToLogin', $login, $aseco->formatColors($stats), 'OK', '', 0);

	} elseif ($aseco->server->getGame() == 'TMF') {
		$header = 'Dedimania Stats: {#black}' . stripColors($aseco->server->challenge->name);
		$stats = array();
		$stats[] = array('Server MaxRank', '{#black}' . $dedi_db['ServerMaxRank']);
		$stats[] = array('Your MaxRank', '{#black}' . $command['author']->dedirank);
		$stats[] = array();
		$stats[] = array('UID', '{#black}' . $dedi_db['Challenge']['Uid']);
		$stats[] = array('Total Races', '{#black}' . $dedi_db['Challenge']['TotalRaces']);
		$stats[] = array('Total Players', '{#black}' . $dedi_db['Challenge']['TotalPlayers']);
		$stats[] = array('Avg. Players', '{#black}' . ($dedi_db['Challenge']['TotalRaces'] > 0 ? round($dedi_db['Challenge']['TotalPlayers'] / $dedi_db['Challenge']['TotalRaces'], 2) : 0));
		$stats[] = array();
		$stats[] = array('               {#black}$l[http://dedimania.com/tmstats/?do=stat&RecOrder3=RANK-ASC&Uid=' . $dedi_db['Challenge']['Uid'] . '&Show=RECORDS]View all Dedimania records for this track$l');

		// display ManiaLink message
		display_manialink($login, $header, array('Icons64x64_1', 'Maximize', -0.01), $stats, array(1.0, 0.3, 0.7), 'OK');
	}
}  // chat_dedistats

function chat_dedicptms($aseco, $command) {
	chat_dedisectms($aseco, $command, false);
}  // chat_dedicptms

function chat_dedisectms($aseco, $command, $diff = true) {
	global $dedi_db;

	$player = $command['author'];
	$login = $player->login;
	$dedi_recs = $dedi_db['Challenge']['Records'];

	if (!$total = count($dedi_recs)) {
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No Dedimania records found!'), $login);
		return;
	}
	$maxrank = max($dedi_db['ServerMaxRank'], $player->dedirank);
	$cpscnt = count($dedi_recs[0]['Checks']);

	// display popup window for TMN
	if ($aseco->server->getGame() == 'TMN') {
		$head = 'Current TOP ' . $maxrank . ' Dedimania ' . ($diff ? 'Sector' : 'CP') . ' Times (' . $cpscnt . '):' . LF;
		$cpsmax = 9;
		$msg = '';
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = 1;

		// create list of records
		for ($i = 0; $i < $total; $i++) {
			$cur_record = $dedi_recs[$i];
			$msg .= str_pad($i+1, 2, '0', STR_PAD_LEFT) . '.  '
			        . ((isset($cur_record['NewBest']) && $cur_record['NewBest']) ? '{#black}' : '')
			        . formatTime($cur_record['Best']);
			// append up to $cpsmax sector/CP times
			if (!empty($cur_record['Checks'])) {
				$j = 1;
				$pr = 0;
				$msg .= '$n';
				foreach ($cur_record['Checks'] as $cp) {
					$msg .= ' ' . formatTime($cp - $pr);
					if ($diff) $pr = $cp;
					if (++$j > $cpsmax) {
						if ($cpscnt > $cpsmax) $msg .= ' +';
						break;
					}
				}
				$msg .= '$m';
			}
			$msg .= LF;
			if (++$lines > 9) {
				$player->msgs[] = $aseco->formatColors($head . $msg);
				$lines = 0;
				$msg = '';
			}
		}
		// add if last batch exists
		if ($msg != '')
			$player->msgs[] = $aseco->formatColors($head . $msg);

		// display popup message
		if (count($player->msgs) == 2) {
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $player->msgs[1], 'OK', '', 0);
		} else {  // > 2
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $player->msgs[1], 'Close', 'Next', 0);
		}

	// display ManiaLink window for TMF
	} elseif ($aseco->server->getGame() == 'TMF') {
		$head = 'Current TOP ' . $maxrank . ' Dedimania ' . ($diff ? 'Sector' : 'CP') . ' Times (' . $cpscnt . '):';
		$cpsmax = 12;
		// compute widths
		$width = 0.1 + 0.18 + min($cpscnt, $cpsmax) * 0.1 + ($cpscnt > $cpsmax ? 0.06 : 0.0);
		if ($width < 1.0) $width = 1.0;
		$widths = array($width, 0.1, 0.18);
		for ($i = 0; $i < min($cpscnt, $cpsmax); $i++)
			$widths[] = 0.1; // cp
		if ($cpscnt > $cpsmax)
			$widths[] = 0.06;

		$msg = array();
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = array(1, $head, $widths, array('BgRaceScore2', 'Podium'));

		// create list of records
		for ($i = 0; $i < $total; $i++) {
			$cur_record = $dedi_recs[$i];
			$line = array();
			$line[] = str_pad($i+1, 2, '0', STR_PAD_LEFT) . '.';
			$line[] = ((isset($cur_record['NewBest']) && $cur_record['NewBest']) ? '{#black}' : '')
			          . formatTime($cur_record['Best']);
			// append up to $cpsmax sector/CP times
			if (!empty($cur_record['Checks'])) {
				$j = 1;
				$pr = 0;
				foreach ($cur_record['Checks'] as $cp) {
					$line[] = '$n' . formatTime($cp - $pr);
					if ($diff) $pr = $cp;
					if (++$j > $cpsmax) {
						if ($cpscnt > $cpsmax) $line[] = '+';
						break;
					}
				}
			}
			$msg[] = $line;
			if (++$lines > 14) {
				$player->msgs[] = $msg;
				$lines = 0;
				$msg = array();
			}
		}
		// add if last batch exists
		if (!empty($msg))
			$player->msgs[] = $msg;

		// display ManiaLink message
		display_manialink_multi($player);

	// show chat message for TMO & TMS
	} else {
		$msg = $aseco->formatColors('{#server}> {#error}No Dedimania ' . ($diff ? 'sector' : 'CP') . ' times available');
		$aseco->client->query('ChatSendServerMessageToLogin', $msg, $login);
	}
}  // chat_dedisectms
?>
