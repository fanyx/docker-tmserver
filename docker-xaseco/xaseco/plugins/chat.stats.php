<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Chat plugin.
 * Displays player statistics & personal settings.
 * Updated by Xymph
 *
 * Dependencies: requires chat.records2.php
 */

require_once('includes/tmndatafetcher.inc.php');  // provides access to TMN world stats

Aseco::addChatCommand('stats', 'Displays statistics of current player');
Aseco::addChatCommand('statsall', 'Displays world statistics of a player');
Aseco::addChatCommand('settings', 'Displays your personal settings');

// calls function get_recs() from chat.records2.php
function chat_stats($aseco, $command) {
	global $rasp, $feature_ranks, $maxrecs;

	$player = $command['author'];
	$target = $player;

	// check for optional player parameter
	if ($command['params'] != '')
		if (!$target = $aseco->getPlayerParam($player, $command['params'], true))
			return;

	// showing stats for TMN
	if ($aseco->server->getGame() == 'TMN') {

		// get current player info
		$aseco->client->resetError();
		$aseco->client->query('GetPlayerInfo', $target->login);
		$info = $aseco->client->getResponse();
		if ($aseco->client->isError()) {
			$rank = 0;
			$score = 0;
		} else {
			$rank = $info['LadderStats']['Ranking'];
			$score = $info['LadderStats']['Score'];
		}
		// format ladder rank with narrow spaces between the thousands
		$rank = str_replace(' ', '$n $m', number_format($rank, 0, ' ', ' '));

		// obtain last online timestamp
		$query = 'SELECT UpdatedAt FROM players
		          WHERE login=' . quotedString($target->login);
		$result = mysql_query($query);
		$laston = mysql_fetch_row($result);
		mysql_free_result($result);

		$records = 0;
		if ($list = get_recs($target->id)) {  // from chat.records2.php
			// sort for best records
			asort($list);
			// count total ranked records
			foreach ($list as $name => $rec) {
				// stop upon unranked record
				if ($rec > $maxrecs) break;
				// count ranked record
				$records++;
			}
		}

		$stats = 'Stats for: ' . $target->nickname . '$z / {#login}' . $target->login . LF . LF;
		$stats .= '$gServer Date : {#black}' . date('M d, Y') . LF;
		$stats .= '$gServer Time : {#black}' . date('H:i:s T') . LF;
		$stats .= '$gTime Played : {#black}' . formatTimeH($target->getTimePlayed() * 1000, false) . LF;
		$stats .= '$gLast Online   : {#black}' . preg_replace('/^\d\d\d\d/', '\$n$0\$m', preg_replace('/:\d\d$/', '', $laston[0])) . LF;
	if ($feature_ranks) {
		$stats .= '$gServer Rank : {#black}' . $rasp->getRank($target->login) . LF;
	}
		$stats .= '$gRecords        : {#black}' . $records . LF;
		$stats .= '$gRaces Won   : {#black}' . ($target->getWins() > $target->wins ? $target->getWins() : $target->wins) . LF;
		$stats .= '$gLadder Rank : {#black}' . $rank . LF;
		$stats .= '$gLadder Score: {#black}' . round($score, 1) . LF;
		$stats .= '$gNation           : {#black}' . $target->nation . LF;
		$stats .= '$gClan              : {#black}' . ($target->teamname ? $target->teamname . '$z' : '<none>') . LF;
	if ($aseco->allowAbility($player, 'chat_statsip')) {
		$stats .= '$gIP                  : {#black}' . $target->ipport;
	}

		// display popup message
		$aseco->client->query('SendDisplayServerMessageToLogin', $player->login, $aseco->formatColors($stats), 'OK', '', 0);

	// showing stats for TMF
	} elseif ($aseco->server->getGame() == 'TMF') {

		// get current player info
		$aseco->client->resetError();
		$aseco->client->query('GetDetailedPlayerInfo', $target->login);
		$info = $aseco->client->getResponse();
		if ($aseco->client->isError()) {
			$rank = 0;
			$score = 0;
			$lastm = 0;
			$wins = 0;
			$draws = 0;
			$losses = 0;
			$zone = '';
			$inscrdays = 0;
			$inscrhours = 0;
		} else {
			$rank = $info['LadderStats']['PlayerRankings'][0]['Ranking'];
			$score = $info['LadderStats']['PlayerRankings'][0]['Score'];
			$lastm = $info['LadderStats']['LastMatchScore'];
			$wins = $info['LadderStats']['NbrMatchWins'];
			$draws = $info['LadderStats']['NbrMatchDraws'];
			$losses = $info['LadderStats']['NbrMatchLosses'];

			// get zone info
			$zone = substr($info['Path'], 6);  // strip 'World|'
			$inscr = $info['HoursSinceZoneInscription'];
			$inscrdays = floor($inscr / 24);
			$inscrhours = $inscr - ($inscrdays * 24);
		}

		// format numbers with narrow spaces between the thousands
		$frank = str_replace(' ', '$n $m', number_format($rank, 0, ' ', ' '));
		$fwins = str_replace(' ', '$n $m', number_format($wins, 0, ' ', ' '));
		$fdraws = str_replace(' ', '$n $m', number_format($draws, 0, ' ', ' '));
		$flosses = str_replace(' ', '$n $m', number_format($losses, 0, ' ', ' '));

		// obtain last online timestamp
		$query = 'SELECT UpdatedAt FROM players
		          WHERE login=' . quotedString($target->login);
		$result = mysql_query($query);
		$laston = mysql_fetch_row($result);
		mysql_free_result($result);

		$records = 0;
		if ($list = get_recs($target->id)) {  // from chat.records2.php
			// sort for best records
			asort($list);
			// count total ranked records
			foreach ($list as $name => $rec) {
				// stop upon unranked record
				if ($rec > $maxrecs) break;
				// count ranked record
				$records++;
			}
		}

		$header = 'Stats for: ' . $target->nickname . '$z / {#login}' . $target->login;
		$stats = array();
		$stats[] = array('Server Date', '{#black}' . date('M d, Y'));
		$stats[] = array('Server Time', '{#black}' . date('H:i:s T'));
		$value = '{#black}' . formatTimeH($target->getTimePlayed() * 1000, false);
		// add clickable button
		if ($aseco->settings['clickable_lists'])
			$value = array($value, -5);  // action id
		$stats[] = array('Time Played', $value);
		$stats[] = array('Last Online', '{#black}' . preg_replace('/:\d\d$/', '', $laston[0]));
	if ($feature_ranks) {
		$value = '{#black}' . $rasp->getRank($target->login);
		// add clickable button
		if ($aseco->settings['clickable_lists'])
			$value = array($value, -6);  // action id
		$stats[] = array('Server Rank', $value);
	}
		$value = '{#black}' . $records;
		// add clickable button
		if ($aseco->settings['clickable_lists'])
			$value = array($value, 5);  // action id
		$stats[] = array('Records', $value);
		$value = '{#black}' . ($target->getWins() > $target->wins ? $target->getWins() : $target->wins);
		// add clickable button
		if ($aseco->settings['clickable_lists'])
			$value = array($value, 6);  // action id
		$stats[] = array('Races Won', $value);
		$stats[] = array('Ladder Rank', '{#black}' . $frank);
		$stats[] = array('Ladder Score', '{#black}' . round($score, 1));
		$stats[] = array('Last Match', '{#black}' . round($lastm, 1));
		$stats[] = array('Wins', '{#black}' . $fwins);
		$stats[] = array('Draws', '{#black}' . $fdraws . ($losses != 0 ?
		                          '   $gW/L: {#black}' . round($wins / $losses, 3) : ''));
		$stats[] = array('Losses', '{#black}' . $flosses);
		$stats[] = array('Zone', '{#black}' . $zone);
		$stats[] = array('Inscribed', '{#black}' . $inscrdays . ' day' . ($inscrdays == 1 ? ' ' : 's ') . $inscrhours . ' hours');
		$stats[] = array('Rights', '{#black}' . ($target->rights ? 'United' : 'Nations'));
	if ($aseco->server->rights) {
		$stats[] = array('Donations', '{#black}' . ($target->rights ? ldb_getDonations($aseco, $target->login) : 'N/A'));
	}
		$stats[] = array('Clan', '{#black}' . ($target->teamname ? $target->teamname . '$z' : '<none>'));
		$stats[] = array('Client', '{#black}' . $target->client);
	if ($aseco->allowAbility($player, 'chat_statsip')) {
		$stats[] = array('IP', '{#black}' . $target->ipport);
	}

		// display ManiaLink message
		display_manialink($player->login, $header, array('Icons128x128_1', 'Statistics', 0.03), $stats, array(1.0, 0.3, 0.7), 'OK');

	} else {  // TMS/TMO
		$stats = '{#server}> XASECO Stats' . LF;
		$stats .= 'Showing stats of ' . $target->nickname . LF;
		$stats .= 'Time Played: ' . formatTimeH($target->getTimePlayed() * 1000, false) . LF;
		$stats .= 'Races Won: ' . $target->getWins();

		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($stats), $player->login);
	}
}  // chat_stats

function chat_statsall($aseco, $command) {

	$player = $command['author'];
	$target = $player;

	// showing stats for TMN only
	if ($aseco->server->getGame() == 'TMN') {
		// check for optional player parameter
		if ($command['params'] != '') {
			$login = $command['params'];
			if (is_numeric($login)) {
				if (!$target = $aseco->getPlayerParam($player, $login, true)) {
					return;
				} else {
					$login = $target->login;
				}
			}
		} else {
			$login = $player->login;
		}

		// obtain external stats
		$data = new TMNDataFetcher($login, true);
		if (!$data->nickname) {
			$message = '{#server}> {#highlite}' . $login . '{#error} is not a valid player!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
			return;
		}

		// format ranks & stats with narrow spaces between the thousands
		$wrank = str_replace(' ', '$n $m', number_format($data->worldrank, 0, ' ', ' '));
		$nrank = str_replace(' ', '$n $m', number_format($data->nationrank, 0, ' ', ' '));
		$ntotal = str_replace(' ', '$n $m', number_format($data->nationplayers, 0, ' ', ' '));
		$ptotal = str_replace(' ', '$n $m', number_format($data->totalplayers, 0, ' ', ' '));
		$wins = str_replace(' ', '$n $m', number_format($data->wins, 0, ' ', ' '));
		$draws = str_replace(' ', '$n $m', number_format($data->draws, 0, ' ', ' '));
		$losses = str_replace(' ', '$n $m', number_format($data->losses, 0, ' ', ' '));
		$trank = str_replace(' ', '$n $m', number_format($data->teamrank, 0, ' ', ' '));
		$ttotal = str_replace(' ', '$n $m', number_format($data->totalteams, 0, ' ', ' '));

		$stats = 'Stats for: {#black}' . $data->nickname . '$z / {#login}' . $data->login . LF . LF;
		$stats .= '$gLadder Rank  : {#black}' . $wrank . '$g / {#black}' . $ptotal . LF;
		$stats .= '$gLadder Score : {#black}' . round($data->points, 1) . LF;
		$stats .= '$gLast Match     : {#black}' . round($data->lastmatch, 1) . LF;
		$stats .= '$gNation            : {#black}' . $data->nation . LF;
		$stats .= '$gNation Rank   : {#black}' . $nrank . '$g / {#black}' . $ntotal . LF;
		$stats .= '$gWins               : {#black}' . $wins . LF;
		$stats .= '$gDraws             : {#black}' . $draws;
	if ($data->losses != 0) {
		$stats .= '   $gW/L: {#black}' . round($data->wins / $data->losses, 3);
	}
		$stats .= LF;
		$stats .= '$gLosses           : {#black}' . $losses . LF;
		$stats .= '$gStars / Days  : {#black}' . $data->stars . '$g / {#black}' . $data->stardays . LF;
		$stats .= '$gTeam              : {#black}' . ($data->teamname ? $data->teamname . '$z' : '<none>') . LF;
	if ($data->teamname) {
		$stats .= '$gTeam Rank     : {#black}' . $trank . '$g / {#black}' . $ttotal . LF;
	}
		$stats .= '$gOnline            : {#black}' . ($data->online ? $data->serverlogin . '$g / {#black}' . $data->servernation : '<no>') . LF;
		$stats .= '$gNations Score: {#black}' . $data->nationpoints . LF;
		$stats .= '$gNations Rank : {#black}' . $data->nationpos . '$g / {#black}' . $data->totalnations;

		// display popup message
		$aseco->client->query('SendDisplayServerMessageToLogin', $player->login, $aseco->formatColors($stats), 'OK', '', 0);
	} else {
		$message = '{#server}> {#error}Command unavailable, use {#highlite}$i /stats {#error}instead.';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
	}
}  // chat_statsall

function chat_settings($aseco, $command) {

	$player = $command['author'];
	$target = $player;

	// check for optional login parameter if any admin
	if ($command['params'] != '' && $aseco->allowAbility($player, 'chat_settings'))
		if (!$target = $aseco->getPlayerParam($player, $command['params'], true))
			return;

	// get CPs settings
	if (function_exists('chat_cps'))
		$cps = ldb_getCPs($aseco, $target->login);
	else
		$cps = false;

	// showing settings for TMN
	if ($aseco->server->getGame() == 'TMN') {
		if ($cps) {
			$settings = 'Settings for: ' . $target->nickname . '$z / {#login}' . $target->login . LF . LF;
			$settings .= '$gLocal CPS        : {#black}' . $cps['cps'] . LF;
			$settings .= '$gDedimania CPS: {#black}' . $cps['dedicps'] . LF;

			// display popup message
			$aseco->client->query('SendDisplayServerMessageToLogin', $player->login, $aseco->formatColors($settings), 'OK', '', 0);
		} else {
			$message = '{#server}> {#error}No personal settings available';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		}

	// showing settings for TMF
	} elseif ($aseco->server->getGame() == 'TMF') {
		// get style setting
		if (function_exists('style_default'))
			$style = ldb_getStyle($aseco, $target->login);
		else
			$style = false;

		// get panel settings
		if (function_exists('panels_default'))
			$panels = ldb_getPanels($aseco, $target->login);
		else
			$panels = false;

		if ($cps || $style || $panels) {
			$header = 'Settings for: ' . $target->nickname . '$z / {#login}' . $target->login;
			$settings = array();

			// collect available settings
			if ($cps) {
				$settings[] = array('Local CPS', '{#black}' . $cps['cps']);
				$settings[] = array('Dedimania CPS', '{#black}' . $cps['dedicps']);
				if ($style || $panels)
					$settings[] = array();
			}

			if ($style) {
				$settings[] = array('Window Style', '{#black}' . $style);
				if ($panels)
					$settings[] = array();
			}

			if ($panels) {
				if ($aseco->isAnyAdmin($target))
					$settings[] = array('Admin Panel', '{#black}' . substr($panels['admin'], 5));
				$settings[] = array('Donate Panel', '{#black}' . substr($panels['donate'], 6));
				$settings[] = array('Records Panel', '{#black}' . substr($panels['records'], 7));
				$settings[] = array('Vote Panel', '{#black}' . substr($panels['vote'], 4));
			}

			// display ManiaLink message
			display_manialink($player->login, $header, array('Icons128x128_1', 'Inputs', 0.03), $settings, array(1.0, 0.3, 0.7), 'OK');
		} else {
			$message = '{#server}> {#error}No personal settings available';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		}
	} else {  // TMO/TMS
		$message = '{#server}> {#error}No personal settings available';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
	}
}  // chat_settings
?>
