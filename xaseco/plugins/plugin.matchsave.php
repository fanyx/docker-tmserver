<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

// ffMod v1.4
// Original version by Sloth, via tm-forum.com
// Hack & Slash by AssemblerManiac
// Another Hack & Slash by (OoR-F)~fuckfish (http://fish.oorf.de) (with the help of XXX-Max and some code by Basti504)
// Formatting cleanup & TMF ManiaLink popups by Xymph

// TeamForce part and Excerpts of Teamchat and List stuff by XXX El Fuego, Thanks a lot for that one =)
//    February 1st, 2007
//    Forum: http://walesxxx.forumco.com
//    Define the members of your team. Put logins of your team into the matchsave.xml to force them into main team
//    Everyone else is forced into the challenging team (teamname also defined in xml file)


/* template file layout
// header
// {DATE} {TIME} {TRACK}
// <!-- Player Data Begin ->  this tag not output to file
// whatever is here gets duplicated for each person in the race, ranked 1-n, where n = <max_player_count> in the matchsave.xml file
// {RANK} {NICK} {LOGIN} {TIME} {TEAM} {POINTS} are the reserved words
// <!-- Player Data End ->    neither is this one
// footer, tots for each team
// <!-- Team Data Begin ->
// {TEAM} {POINTS}
// <!-- Team Data End ->
// any remaining data will be written to the file from here to the end
*/

// if you want the teamname to show up properly when someone connects, make sure this plugin is AFTER the localdatabase plugin

global $matchVersionNumber;
$matchVersionNumber = 'v1.4';
global $matchDebug;
$matchDebug = false;

global $MatchSettings, $teamForceTeams, $matchAdminCommands;
global $matchTeamNameColorsAllowed, $matchOthersCanScore, $matchTeamNameMaxLength;
$MatchSettings = array();
$teamForceTeams = array();

Aseco::registerEvent('onStartup', 'match_startup');  // checks for existence of 2 tables & creates if they don't exist
Aseco::registerEvent('onNewChallenge', 'match_newChallenge');
Aseco::registerEvent('onEndRace', 'match_endrace');
Aseco::registerEvent('onPlayerConnect', 'match_playerconnect');
Aseco::registerEvent('onPlayerDisconnect', 'match_playerdisconnect');

Aseco::addChatCommand('teamname', 'Set your team name OR help for more options');
Aseco::addChatCommand('team', 'Same as teamname');
Aseco::addChatCommand('tc', 'Send a chat message to your team only.');
Aseco::addChatCommand('standings', 'See current match standings');
Aseco::addChatCommand('match', 'admin only match commands');

addMatchChatCommand('start', 'start match');
addMatchChatCommand('start x', 'start match for x rounds');
addMatchChatCommand('start pl x', 'start match with pointlimit x');
addMatchChatCommand('end', 'end match, write results');
// disabled because redundant with /admin readtracklist command - Xymph
//addMatchChatCommand('load x', 'load playlist x');
addMatchChatCommand('list x', 'shows playlist(s) x');
addMatchChatCommand('on/off', 'plugin on/off');
addMatchChatCommand('others on/off', 'teamless players score points');
addMatchChatCommand('force x', '$nx=on/off: TeamForce on/off, x=random: set opponent name$m');
addMatchChatCommand('assign x y', 'force player with login x into team y');
addMatchChatCommand('teams', '$nshow teams ("/match players" shows teams and players)$m');
addMatchChatCommand('tc on/off', 'Teamchat on/off');

global $matchRunning, $matchRound, $matchTotalRounds, $matchPointLimit, $matchPoints,
       $matchString, $matchTime, $matchAutoRestart, $betweenChallenges, $startedBetweenChallenges;

$matchRound = -1;
$matchTotalRounds = -1;
$matchTime = -1;
$matchPointLimit = -1;
$matchRunning = false;
$matchPoints = array();
$matchAutoRestart = false;
$betweenChallenges = false;

function addMatchChatCommand($name, $description) {
	global $matchAdminCommands;

	$i = count($matchAdminCommands);
	$matchAdminCommands[$i] = array();
	$matchAdminCommands[$i][0] = $name;
	$matchAdminCommands[$i][1] = $description;
}  // addMatchChatCommand

function getArrayFirstIndex($arr) {

	if (!$arr) {
		return false;
	}
	foreach ($arr as $key => $value)
	return $key;
}  // getArrayFirstIndex

/**
 * Show help
 */
function showMatchHelp($player) {
	global $aseco;
	global $matchAdminCommands, $MatchSettings, $matchRunning, $matchRound, $matchTotalRounds,
	       $matchPointLimit, $matchPoints, $matchAutoRestart, $matchOthersCanScore, $matchVersionNumber;

	$currentLeader = '';

	if ($matchRunning) {
		$runningMatch = 'Round '.$MatchSettings['col_window_highlite'].$matchRound;
		if ($matchTotalRounds != -1)
			$runningMatch .= ' '.$MatchSettings['col_window_default'].'of '.$MatchSettings['col_window_highlite'].$matchTotalRounds;
		if ($matchPointLimit != -1)
			$runningMatch .= ' '.$MatchSettings['col_window_default'].'- PL: '.$MatchSettings['col_window_highlite'].$matchPointLimit;
		if ($matchAutoRestart)
			$runningMatch .= ' '.$MatchSettings['col_window_special'].$MatchSettings['col_window_default'].'('.$MatchSettings['col_window_highlite'].'Auto'.$MatchSettings['col_window_default'].')$z';
		$leader = getArrayFirstIndex($matchPoints);
		if ($leader) {
			$leader = LF.$MatchSettings['col_window_default'].'Leader: '.$MatchSettings['col_window_highlite'].$leader.' '.$MatchSettings['col_window_default'].'('.$MatchSettings['col_window_highlite'].$matchPoints[$leader].$MatchSettings['col_window_default'].')';
		}

	} else {
		$runningMatch = $MatchSettings['col_window_highlite'].'none';
	}

	$matchStatus = '$f00OFF';
	if ($MatchSettings['enable'])
		$matchStatus = '$0f0ON';

	$othersStatus = '$f00OFF';
	if ($matchOthersCanScore)
		$othersStatus = '$0f0ON';

	$forceStatus = '$f00OFF';
	if ($MatchSettings['teamForceEnabled'])
		$forceStatus = '$0f0ON';

	$tcStatus = '$f00OFF';
	if ($MatchSettings['teamchatEnabled'])
		$tcStatus = '$0f0ON';

	if ($aseco->server->getGame() == 'TMN') {
		$help = $MatchSettings['col_window_default'].'Matchsave MOD '.$matchVersionNumber.' by fuckfish'. LF;
		$help .= $MatchSettings['col_window_separator'].'----------'.LF;
		$help .= $MatchSettings['col_window_default'].'Plugin is: '.$matchStatus.$MatchSettings['col_window_separator'].' | '.
		         $MatchSettings['col_window_default'].'Others score: '.$othersStatus.$MatchSettings['col_window_separator'].' | '.
		         $MatchSettings['col_window_default'].'Force: '.$forceStatus.$MatchSettings['col_window_separator'].' | '.
		         $MatchSettings['col_window_default'].'TC: '.$tcStatus.$MatchSettings['col_window_separator'].LF;
		$help .= $MatchSettings['col_window_default'].'Current match: '.$runningMatch.$leader.LF;
		$help .= $MatchSettings['col_window_separator'].'----------'.LF;
		$help .= $MatchSettings['col_window_default'].'Supported commands:' . LF;
		$help .= $MatchSettings['col_window_hint'].'(TIP: use '.$MatchSettings['col_window_default']
		         .'/match auto '.$MatchSettings['col_window_hint'].'instead of '.$MatchSettings['col_window_default']
		         .'/match start '.$MatchSettings['col_window_hint'].'to automate matches)$z'.LF;

		$padstg = $MatchSettings['col_window_highlite'].'... ';
		if (!empty($matchAdminCommands)) {
			foreach ($matchAdminCommands as $chat_command) {
				$help .= $padstg . $chat_command[0] . ' '.$MatchSettings['col_window_default'] . $chat_command[1] . LF;
			}
		}

		if (strlen($help) < 1025) {
			$aseco->addCall('SendDisplayServerMessageToLogin', array($player->login, $help, 'OK', '', 0));
		} else {
			$help = substr($help, 0, 1024);
			$aseco->addCall('SendDisplayServerMessageToLogin', array($player->login, $help, 'OK', '', 0));
			$aseco->addCall('ChatSendServerMessageToLogin', array($aseco->formatColors('{#error}Help message exceeded valid length, please contact (OoR-F)~fuckfish via tm-forum.com.'), $player->login));
		}

	} elseif ($aseco->server->getGame() == 'TMF') {
		$header = $MatchSettings['col_window_default'].'Matchsave MOD '.$matchVersionNumber.' by fuckfish';
		$help = array();
		$help[] = array($MatchSettings['col_window_default'].'Plugin is: '.$matchStatus.$MatchSettings['col_window_separator'].' | '.
		                $MatchSettings['col_window_default'].'Others score: '.$othersStatus.$MatchSettings['col_window_separator'].' | '.
		                $MatchSettings['col_window_default'].'Force: '.$forceStatus.$MatchSettings['col_window_separator'].' | '.
		                $MatchSettings['col_window_default'].'TC: '.$tcStatus.$MatchSettings['col_window_separator']);
		$help[] = array($MatchSettings['col_window_default'].'Current match: '.$runningMatch.$leader);
		$help[] = array($MatchSettings['col_window_separator'].'----------');
		$help[] = array($MatchSettings['col_window_default'].'Supported commands:');
		$help[] = array($MatchSettings['col_window_hint'].'(TIP: use '.$MatchSettings['col_window_default']
		                .'/match auto '.$MatchSettings['col_window_hint'].'instead of '.$MatchSettings['col_window_default']
		                .'/match start '.$MatchSettings['col_window_hint'].'to automate matches)$z');

		$padstg = $MatchSettings['col_window_highlite'].'... ';
		if (!empty($matchAdminCommands)) {
			foreach ($matchAdminCommands as $chat_command) {
				$help[] = array($padstg . $chat_command[0] . ' '.$MatchSettings['col_window_default'] . $chat_command[1]);
			}
		}

		// display ManiaLink message
		display_manialink($player->login, $header, array('Icons128x128_1', 'ProfileAdvanced', 0.02), $help, array(1.0), 'OK');
	}
}  // showMatchHelp

function matchDisplayStatus($aseco, $msg) {
	global $MatchSettings;

	$message = $MatchSettings['col_chat_highlite'].'>> '.$MatchSettings['col_chat_plugin'].'[Matchsave] '.$MatchSettings['col_chat_default'].$msg;
	$aseco->addCall('ChatSendServerMessage', array($aseco->formatColors($message)));
}  // matchDisplayStatus

// called @ onNewChallenge
function match_newChallenge($aseco, $challenge) {
	global $matchRunning, $matchRound, $matchTotalRounds, $MatchSettings, $matchPointLimit,
	       $matchPoints, $matchAutoRestart, $startedBetweenChallenges, $betweenChallenges;

	$betweenChallenges = false;
	if ($MatchSettings['enable']) {
		if ($matchRunning) {
			if ($matchRound == 1 && $startedBetweenChallenges) {
				$startedBetweenChallenges = false;
			} else {
				$matchRound++;
			}
			$leader = getArrayFirstIndex($matchPoints);
			if (($matchRound > $matchTotalRounds && $matchTotalRounds != -1) || ($leader && $matchPointLimit != -1 && $matchPointLimit <= $matchPoints[$leader])) {
				matchStop($aseco);
			} else {
				$autoText = '';
				if ($matchAutoRestart)
					$autoText = 'Auto';
				$msg = 'Current '.$autoText.'Match: Round '.$MatchSettings['col_chat_highlite'].$matchRound.$MatchSettings['col_chat_default'];
				if ($matchTotalRounds != -1) {
					$msg .= ' of '.$MatchSettings['col_chat_highlite'].$matchTotalRounds;
				}
				if ($matchPointLimit != -1) {
					$msg .= ' (Pointlimit '.$MatchSettings['col_chat_highlite'].$matchPointLimit.$MatchSettings['col_chat_default'].')';
				}
				matchDisplayStatus($aseco, $msg);
			}
		}
	}
}  // match_newChallenge

function matchGetCenteredLines($lines) {
	$maxlen = 0;
	for ($i = 0; $i < count($lines); $i++) {
		$length = strlen(stripColors($lines[$i]));
		if ($length > $maxlen)
			$maxlen = $length;
	}

	for ($i = 0; $i < count($lines); $i++) {
		while (strlen(stripColors($lines[$i])) < $maxlen) {
			$lines[$i] = ' '.$lines[$i].' ';
		}
	}
	return $lines;
}  // matchGetCenteredLines

function matchStart($aseco, $admin, $numRounds, $pointLimit, $autoRestart) {
	global $matchRunning, $betweenChallenges, $startedBetweenChallenges, $matchRound, $matchTotalRounds,
	       $matchTime, $matchPointLimit, $matchString, $matchPoints, $MatchSettings, $matchAutoRestart;

	if ($numRounds == -1) $numRounds = '';
	if ($pointLimit == -1) $pointLimit = '';

	$status = '';
	$statustpl = array();
	$autoText = '';
	if ($autoRestart)
		$autoText = 'Auto';
	$statustpl[] = $autoText.'Match starts now!';
	$statustpl[] = $autoText.'Match for '.$MatchSettings['col_chat_highlite'].'{NUMROUNDS} '.$MatchSettings['col_chat_default'].'rounds starts now!';
	$statustpl[] = $autoText.'Match starts now! Pointlimit is '.$MatchSettings['col_chat_highlite'].'{POINTLIMIT}'.$MatchSettings['col_chat_default'].'.';

	if (!$matchRunning) {
		$matchRound = 1;
		if ($numRounds) {
			if (is_numeric($numRounds)) {
				$matchTotalRounds = $numRounds;
				$status = str_replace('{NUMROUNDS}', $numRounds, $statustpl[1]);
			}
			elseif ($numRounds == 'pl') {
				if ($pointLimit) {
					if (is_numeric($pointLimit)) {
						$matchPointLimit = $pointLimit;
						$status = str_replace('{POINTLIMIT}', $pointLimit, $statustpl[2]);
					} else {
						if ($admin) $aseco->addCall('ChatSendToLogin', array($aseco->formatColors('{#error}{#message}'.$pointLimit.' {#error}is not a number. Normal match started'), $admin->login));
						$status = $statustpl[0];
					}
				} else {
					if ($admin) $aseco->addCall('ChatSendToLogin', array($aseco->formatColors('{#error}You did not specify the pointlimit. Normal match started'), $admin->login));
					$status = $statustpl[0];
				}
			}
			else {
				if ($admin) $aseco->addCall('ChatSendToLogin', array($aseco->formatColors('{#error}{#message}'.$numRounds.' {#error}is not a number. Normal match started'), $admin->login));
				$status = $statustpl[0];
			}
		} else {
			$status = $statustpl[0];
		}
		matchDisplayStatus($aseco, $status);
		$matchRunning = true;
		$matchPoints = array();
		$matchString = '';
		$matchTime = time();
		$matchAutoRestart = $autoRestart;
		$startedBetweenChallenges = $betweenChallenges;
	} else {
		if ($admin) $aseco->addCall('ChatSendToLogin', array($aseco->formatColors('{#error}There is already a match running!'), $admin->login));
	}
}  // matchStart

function chat_standings($aseco, $command) {
	global $matchRunning;

	$player = $command['author'];
	if ($matchRunning)
		showStandings($aseco, 'Current Match Standings', $player, 29);
}  // chat_standings

function chat_tc($aseco, $command) {
	global $MatchSettings;

	$author = $command['author'];
	$msg = $command['params'];
	$team = $author->teamname;
	if ($team) {
		if ($MatchSettings['teamchatEnabled']) {
			$msg = $aseco->formatColors($MatchSettings['teamchatPrefix'].'$g$m ['.$author->nickname.'$g$m] '.$msg);
			foreach ($aseco->server->players->player_list as $player) {
				if ($player->teamname == $team || ($aseco->allowAbility($player, 'chat_tc_listen') && $MatchSettings['bigBrother'])) {
					$aseco->addCall('ChatSendServerMessageToLogin', array($msg, $player->login));
				}
			}
		} else {
			$aseco->addCall('ChatSendServerMessageToLogin', array($aseco->formatColors('{#error}Teamchat is currently disabled by an Admin.'), $author->login));
		}
	} else {
		$aseco->addCall('ChatSendServerMessageToLogin', array($aseco->formatColors('{#error}You belong to no team; to join, type $fff/team yourteamname.'), $author->login));
	}
}  // chat_tc

function showStandings($aseco, $headline, $player, $separatorLength = 17) {
	global $MatchSettings, $matchPoints;

	if ($aseco->server->getGame() == 'TMN') {
		$result = array();
		$result[] = $MatchSettings['col_window_default'].$headline;
		$sep = '';
		for ($i = 0; $i < $separatorLength; $i++) $sep .= '-';
		$result[] = $MatchSettings['col_window_separator'].$sep;
		if (!$player->teamname) {
			$result[] = str_replace('{#server}', '$000', '$n'.$MatchSettings['hlpNoTeam'][0].LF);
		}

		$count = 1;
		foreach ($matchPoints as $key => $value) {
			if ($count <= 15) {
				$result[] = $MatchSettings['col_window_default'].$count++.'. '.$MatchSettings['col_window_highlite'].$key.' $z'.$MatchSettings['col_window_default'].'('.$MatchSettings['col_window_highlite'].$value.$MatchSettings['col_window_default'].')';
			}
		}

		$result = implode(LF, $result);
		$toReplace = $player->teamname;
		if (!$toReplace)
			$toReplace = 'OTHERS';
		$formattedMsg = $aseco->formatColors(str_replace(' '.$MatchSettings['col_window_highlite'].$toReplace.' ', ' '.$MatchSettings['col_window_highlite_team'].$toReplace.' ', $result));

		$aseco->addCall('SendDisplayServerMessageToLogin', array($player->login, $formattedMsg, 'OK', '', intval($MatchSettings['resultTimeout'])));

	} elseif ($aseco->server->getGame() == 'TMF') {
		$header = $MatchSettings['col_window_default'].$headline;
		$result = array();

		if (!$player->teamname) {
			$result[] = array(str_replace('{#server}', '$000', '$n'.$MatchSettings['hlpNoTeam'][0]));
		}

		$count = 1;
		foreach ($matchPoints as $key => $value) {
			if ($count <= 15) {
				$result[] = array($MatchSettings['col_window_default'].$count++.'. '.$MatchSettings['col_window_highlite'].$key.' $z'.$MatchSettings['col_window_default'].'('.$MatchSettings['col_window_highlite'].$value.$MatchSettings['col_window_default'].')');
			}
		}

		$toReplace = $player->teamname;
		if (!$toReplace)
			$toReplace = 'OTHERS';
		$formattedMsg = str_replace(' '.$MatchSettings['col_window_highlite'].$toReplace.' ', ' '.$MatchSettings['col_window_highlite_team'].$toReplace.' ', $result);

		// display ManiaLink message
		display_manialink($player->login, $header, array('BgRaceScore2', 'Podium'), $formattedMsg, array(0.8), 'OK');
	}
}  // showStandings

function matchStop($aseco, $manually = false) {
	global $matchRunning, $matchRound, $matchTotalRounds, $MatchSettings, $matchTime,
	       $matchString, $matchPointLimit, $matchPoints, $matchAutoRestart;

	if ($MatchSettings['enable']) {
		if ($manually) {
			$matchAutoRestart = false;
		}

		foreach ($aseco->server->players->player_list as $player) {
			showStandings($aseco, 'Match Results', $player);
		}

		$numRounds = $matchTotalRounds;
		$pointLimit = $matchPointLimit;
		if ($pointLimit > 0)
			$numRounds = 'pl';
		$autoRestart = $matchAutoRestart;

		$matchRound = -1;
		$matchTotalRounds = -1;
		$matchPointLimit = -1;
		$matchPoints = array();
		$matchRunning = false;
		$matchTime = -1;

		$fp = fopen($MatchSettings['outfilematch'], 'w');
		fwrite($fp, $matchString);
		fclose($fp);

		$autoText = '';
		if ($matchAutoRestart)
			$autoText = 'Auto';

		matchDisplayStatus($aseco, $autoText.'Match ended, scores saved.');

		if ($matchAutoRestart) {
			matchStart($aseco, null, $numRounds, $pointLimit, $autoRestart);
		}
	}
}  // matchStop


// This is where all the Setting-Magic happens

function chat_match($aseco, $command) {
	global $matchRunning, $matchRound, $matchTotalRounds, $matchTime, $matchPointLimit,
	       $matchPoints, $matchString, $MatchSettings, $matchDebug;

	$admin = $command['author'];

	// check if chat command was used by an admin
	if (!$matchDebug) {
		if (!$aseco->allowAbility($admin, 'chat_match')) {
			// writes warning in console
			$aseco->console($admin->login . ' tried to use match chat command (no permission!)');

			// sends chat message
			$message = $aseco->getChatMessage('NO_ADMIN');
			$aseco->addCall('ChatSendToLogin', array($aseco->formatColors($message), $admin->login));
			return false;
		}
	}

	// split params into array
	$arglist = explode(' ', $command['params'], 2);
	$command['params'] = explode(' ', $command['params']);

	$cmdcount = count($arglist);

	/**
	 * enable or disable the matchsave plugin
	 */
	if (strtolower($command['params'][0]) == 'on' || strtolower($command['params'][0]) == 'off') {

		if (strtolower($command['params'][0]) == 'on') {
			$MatchSettings['enable'] = true;
		} else {
			$MatchSettings['enable'] = false;
		}
		matchDisplayStatus($aseco, 'Plugin is now '.$MatchSettings['col_chat_highlite'].strtoupper($command['params'][0]));

	/**
	 * activate or disactivate the score tracking of teamless players
	 */
	} elseif ($command['params'][0] == 'others' && (strtolower($command['params'][1]) == 'on' || strtolower($command['params'][1]) == 'off')) {

		global $matchOthersCanScore;
		if (strtolower($command['params'][1]) == 'on') {
			$matchOthersCanScore = true;
		} else {
			$matchOthersCanScore = false;
		}
		matchDisplayStatus($aseco, 'Players without team get scores: '.$MatchSettings['col_chat_highlite'].strtoupper($command['params'][1]));

	/**
	 * activate or disactivate the score tracking of teamless players
	 */
	} elseif ($command['params'][0] == 'force' && $command['params'][1] != '') {

		$string = $command['params'][1];
		if ($string == 'on' || $string == 'off') {
			if ($string == 'on') {
				$MatchSettings['teamForceEnabled'] = true;
				// executeTeamForce($aseco);
			} else {
				$MatchSettings['teamForceEnabled'] = false;
			}
			matchDisplayStatus($aseco, 'Teamforce is now: '.$MatchSettings['col_chat_highlite'].strtoupper($command['params'][1]));
		} else {
			foreach ($aseco->server->players->player_list as $player) {
				if ($player->teamname == $MatchSettings['defaultTeam']) {
					$player->teamname = $string;
					$aseco->addCall('ChatSendServerMessageToLogin', array($aseco->formatColors($MatchSettings['col_chat_highlite'] .'>'.$MatchSettings['col_chat_plugin'].' Your Teamname was changed to '.$MatchSettings['col_chat_highlite'] . $string), $player->login));
				} else {
					$aseco->addCall('ChatSendServerMessageToLogin', array($aseco->formatColors($MatchSettings['col_chat_highlite'] .'>'.$MatchSettings['col_chat_plugin'].' Default Teamname is now: '.$MatchSettings['col_chat_highlite'] . $string), $player->login));
				}
			}
			$MatchSettings['defaultTeam'] = $string;
		}

	/**
	 * activate or disactivate the teamchat
	 */
	} elseif ($command['params'][0] == 'tc' && $command['params'][1] != '') {

		$string = $command['params'][1];
		if ($string == 'on' || $string == 'off') {
			if ($string == 'on') {
				$MatchSettings['teamchatEnabled'] = true;
			} else {
				$MatchSettings['teamchatEnabled'] = false;
			}
			matchDisplayStatus($aseco, 'Teamchat is now: '.$MatchSettings['col_chat_highlite'].strtoupper($command['params'][1]));
		}

	/**
	 * start a match (with our without a specified length)
	 */
	} elseif ($command['params'][0] == 'start' || $command['params'][0] == 'auto') {

		$numRounds = $command['params'][1];
		$pointLimit = $command['params'][2];
		$autoRestart = false;
		if ($command['params'][0] == 'auto')
			$autoRestart = true;

		matchStart($aseco, $admin, $numRounds, $pointLimit, $autoRestart);

	/**
	 * stop or end a match and display the results
	 */
	} elseif ($command['params'][0] == 'end') {

		if ($matchRunning) {
			matchStop($aseco, true);
		}

	/**
	 * load playlist txt file
	 */
/* disabled because redundant with /admin readtracklist command - Xymph
	} elseif ($command['params'][0] == 'load' && $command['params'][1] != '') {

		$filename = $command['params'][1];
		if (!strstr($filename, '.txt')) {
			$filename .= '.txt';
		}

		$filepath = $MatchSettings['playlistDir'] . '/' . $filename;

		//CHECK IF FILE EXISTS
		//YES ...
		if (file_exists($filepath)) {

			//CONSOLE MESSAGE ADD
			$aseco->console('admin '.$command['author']->login.' loads new Playlist: '.$filename);

			//SUCCESS MESSAGE
			$rtn = $aseco->client->query('LoadMatchSettings', $filepath);
			if (!$rtn) {
				trigger_error('[' . $aseco->client->getErrorCode() . '] LoadMatchSettings - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
				$message = '{#server}> {#error}Error reading {#highlite}$i '.$filepath.' {#error}!';
			} else {
				$cnt = $aseco->client->getResponse();
				$message = 'Successfully set new Playlist: '.$MatchSettings['col_chat_highlite'].$filename.$MatchSettings['col_chat_default'].' with '.$MatchSettings['col_chat_highlite'].$cnt.$MatchSettings['col_chat_default'].' tracks';
			}
			$aseco->addCall('ChatSendToLogin', array($aseco->formatColors($message), $admin->login));

		//NO ...
		} else {
			//CONSOLE MESSAGE ADD
			$aseco->console('admin '.$command['author']->login.' tried to load unavailable Playlist.');

			//SENDS A FAILED MESSAGE
			$aseco->addCall('ChatSendToLogin', array($aseco->formatColors('{#error}Playlist file unavailable'), $admin->login));
		}
disabled */

	/**
	 * show a list of the available playlists
	 */
	} elseif ($command['params'][0] == 'list') {

		$dir = $MatchSettings['playlistDir'] . '//';
		$tmp = array();
		$tmp = scandir($dir);
		$files = array();

		$search = '';
		if ($command['params'][1])
			$search = $command['params'][1];

		foreach ($tmp as $file) {
			if (stristr($file, '.txt'))
				$files[] = str_ireplace('.txt', '', $file);
		}

		showPlaylistList($aseco, $admin, $files, $search);

	/**
	 * Show Lists of teams and players
	 */
	} elseif ($command['params'][0] == 'teams' || $command['params'][0] == 'players') {

		$search = '';
		if ($command['params'][0] == 'players')
			$full = true;
		showTeamList($aseco, $admin, $full);

	/**
	 * force player into a team
	 */
	} elseif ($command['params'][0] == 'assign' && $command['params'][1] != '') {

		$victim = null;
		foreach ($aseco->server->players->player_list as $player) {
			if ($player->login == $command['params'][1])
				$victim = $player;
		}

		if ($victim) {
			$victim->teamname = $command['params'][2];

			$msgend = ' To change type "'.$MatchSettings['col_chat_default'].'/teamname yourteamname$fff$m".';
			$msgend = $aseco->formatColors($msgend);
			$message = 'An Admin assigned <insert> to team '.$MatchSettings['col_chat_highlite'].$victim->teamname.'$fff$m.';
			$message = $aseco->formatColors($message);

			$message2 = 'An Admin cleared <insert> teamname.';
			$message2 = $aseco->formatColors($message2);

			if ($victim->teamname) {
				foreach ($aseco->server->players->player_list as $player) {
					if ($player->login == $victim->login) {
						$msg = str_replace('<insert>', 'you', $message) . $msgend;
					} else {
						$msg = str_replace('<insert>', stripColors($victim->nickname), $message);
					}
					$aseco->addCall('ChatSendServerMessageToLogin', array($msg, $player->login));
				}
			} else {
				foreach ($aseco->server->players->player_list as $player) {
					if ($player->login == $victim->login) {
						$msg = str_replace('<insert>', 'your', $message2).$msgend;
					} else {
						$msg = str_replace('<insert>', stripColors($victim->nickname)."'s", $message2);
					}
					$aseco->addCall('ChatSendServerMessageToLogin', array($msg, $player->login));
				}
			}
		} else {
			$aseco->addCall('ChatSendServerMessageToLogin', array($aseco->formatColors('{#error}'.$command['params'][1].' is not a valid login.'), $admin->login));
		}

	/**
	 * display help
	 */
	} else {
		showMatchHelp($admin);
	}
}  // chat_match

function showListWindow($aseco, $player, $messageLines, $headline) {
	global $MatchSettings;

	if ($aseco->server->getGame() == 'TMN') {
		$msg = '';
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = 1;
		foreach ($messageLines as $message) {
			$msg .= $message . LF;
			if (++$lines > 9) {
				$player->msgs[] = $MatchSettings['col_window_default'].$headline.LF.$MatchSettings['col_window_separator'].'---------------------'.$MatchSettings['col_window_default'].LF. $msg;
				$lines = 0;
				$msg = '';
			}
		}
		if ($msg != '') {
			$player->msgs[] = $MatchSettings['col_window_default'].$headline.LF.$MatchSettings['col_window_separator'].'---------------------'.$MatchSettings['col_window_default'].LF. $msg;
		}
		if (count($player->msgs) == 2) {
			$aseco->addCall('SendDisplayServerMessageToLogin', array($player->login, $player->msgs[1], 'Ok', '', 0));
		}
		elseif (count($player->msgs) > 2) {
			$aseco->addCall('SendDisplayServerMessageToLogin', array($player->login, $player->msgs[1], 'Close', 'Next', 0));
		}  // else == 1, no message

	} elseif ($aseco->server->getGame() == 'TMF') {
		$head = $MatchSettings['col_window_default'].$headline;
		$msg = array();
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = array(1, $head, array(0.8), array('Icons64x64_1', 'GenericButton'));
		foreach ($messageLines as $message) {
			$msg[] = array($message);
			if (++$lines > 14) {
				$player->msgs[] = $msg;
				$lines = 0;
				$msg = array();
			}
		}
		if (!empty($msg)) {
			$player->msgs[] = $msg;
		}
		// display ManiaLink message
		display_manialink_multi($player);
	}
}  // showListWindow

function showTeamList($aseco, $player, $full) {
	global $MatchSettings;

	$teams = array();
	$teamsMembers = array();
	foreach ($aseco->server->players->player_list as $playa) {
		$teams[$playa->teamname]++;
		$teamsMembers[$playa->teamname][] = $playa;
	}
	arsort($teams);

	if (!$full) {
		$header = 'Num .. Teamname';
	} else {
		$header = 'Team .. Nickname (Login)';
	}

	$helper = array();
	foreach ($teams as $team => $members) {
		if (!$full) {
			$members = str_pad($members, 2, '0', STR_PAD_LEFT);
			if ($team == '') $team = 'OTHERS';
			$helper[] = '$g$m   '.$members.' .. '.$team;
		} else {
			$mbrs = $teamsMembers[$team];
			if ($team == '') $team = 'OTHERS';
			foreach ($mbrs as $mbr) {
				$helper[] = '$g$m'.$team.'$g$m .. '.$mbr->nickname.'$g$m ('.$mbr->login.'$g$m)';
			}
		}
	}

	showListWindow($aseco, $player, $helper, $header);
}  // showTeamList

function showPlaylistList($aseco, $player, $files, $search) {

	$helper = array();
	foreach ($files as $file) {
		if (!$search || strstr($file, $search))
			$helper[] = $file;
	}

	showListWindow($aseco, $player, $helper, 'Available Playlists');
}  // showPlaylistList

function checkTables() {

	$query = 'CREATE TABLE IF NOT EXISTS `match_main` (
							`ID` mediumint(9) NOT NULL auto_increment,
							`trackID` mediumint(9) NOT NULL default 0,
							`dttmrun` timestamp NOT NULL default Now(),
							PRIMARY KEY	(`ID`)
						) ENGINE=MyISAM';
	mysql_query($query);

	$query = 'CREATE TABLE IF NOT EXISTS `match_details` (
							`matchID` mediumint(9) NOT NULL,
							`playerID` mediumint(9) NOT NULL default 0,
							`teamname` varchar(40),
							`points` tinyint default 0,
							`score` mediumint(9),
							PRIMARY KEY (`matchID`,`playerID`)
						) ENGINE=MyISAM';
	mysql_query($query);

	$tables = array();
	$res = mysql_query('SHOW TABLES');
	while ($row = mysql_fetch_row($res))
		$tables[] = $row[0];
	mysql_free_result($res);

	$check = array();
	$check[1] = in_array('match_main', $tables);
	$check[2] = in_array('match_details', $tables);

	// add 'teamname' column if not yet done
	$res = mysql_query('SELECT teamname FROM players limit 1');
	if ($res == false) {
		if (mysql_errno() == 1054) {
			mysql_query('ALTER TABLE players ADD TeamName char(60)');
		}
	} else {
		mysql_free_result($res);
	}

	return ($check[1] && $check[2]);
}  // checkTables

function executeTeamForce($aseco) {
	global $MatchSettings;

	foreach ($aseco->server->players->player_list as $player) {
		$team = $MatchSettings['defaultTeam'];
		if ($teamForceTeams[$player->login]) $team = $teamForceTeams[$player->login];
		$player->teamname = $team;
		foreach ($aseco->server->players->player_list as $recipient) {
			if ($recipient->login == $player->login) {
				$msg = $MatchSettings['col_chat_plugin'].'You have been auto-assigned to team '.$MatchSettings['col_chat_highlite']. $team.LF. $MatchSettings['col_chat_default']. 'Type '.$MatchSettings['col_chat_highlite'].'/team yourteamname '.$MatchSettings['col_chat_default'].'to change your team.';
			} else {
				$msg = $MatchSettings['col_chat_highlite'].stripColors($player->nickname) . $MatchSettings['col_chat_plugin'].' has been auto-assigned to team '.$MatchSettings['col_chat_highlite']. $team;
			}
			$aseco->addCall('ChatSendServerMessageToLogin', array($aseco->formatColors($msg), $recipient->login));
		}
	}
}  // executeTeamForce

// called @ onPlayerConnect
function match_playerconnect($aseco, $player) {
	global $MatchSettings, $teamForceTeams;

	$teamname = '';
	if ($MatchSettings['teamForceEnabled']) {
		if ($teamForceTeams[$player->login]) {
			$teamname = $teamForceTeams[$player->login];
			$player->teamname = $teamname;
		} else {
			$query = 'SELECT teamname FROM players WHERE Login=' . quotedString($player->login) . ' AND Game=' . quotedString($aseco->server->getGame());
			$result = mysql_query($query);
			if (mysql_num_rows($result) > 0) {
				$teamname = mysql_result($result, 0, 'teamname');
			}
			mysql_free_result($result);
			$player->teamname = $teamname;
		}
		foreach ($aseco->server->players->player_list as $recipient) {
			if ($recipient->login == $player->login) {
				$msg = $MatchSettings['col_chat_plugin'].'You have been auto-assigned to team '.$MatchSettings['col_chat_highlite']. $teamname.LF. $MatchSettings['col_chat_default']. 'Type '.$MatchSettings['col_chat_highlite'].'/team yourteamname '.$MatchSettings['col_chat_default'].'to change your team.';
			} else {
				$msg = $MatchSettings['col_chat_highlite'].stripColors($player->nickname) . $MatchSettings['col_chat_plugin'].' has been auto-assigned to team '.$MatchSettings['col_chat_highlite']. $teamname;
			}
			$aseco->addCall('ChatSendServerMessageToLogin', array($aseco->formatColors($msg), $recipient->login));
		}
	} else {
		if ($player->teamname == '') {
			$query = 'SELECT teamname FROM players WHERE Login=' . quotedString($player->login) . ' AND Game=' . quotedString($aseco->server->getGame());
			$result = mysql_query($query);
			if (mysql_num_rows($result) > 0) {
				$teamname = mysql_result($result, 0, 'teamname');
			}
			mysql_free_result($result);
			$player->teamname = $teamname;
		}
	}

	if ($MatchSettings['enable'] && $player->teamname != '') {
		$aseco->addCall('ChatSendServerMessageToLogin', array($aseco->formatColors($MatchSettings['col_chat_highlite'] .'>'.$MatchSettings['col_chat_plugin'].' Your Team is currently '.$MatchSettings['col_chat_highlite'] . $player->teamname), $player->login));
	}
}  // match_playerconnect

// called @ onPlayerDisconnect
function match_playerdisconnect($aseco, $player) {

	if ($player->teamname != '') {
		$query = 'SELECT teamname FROM players WHERE Login=' . quotedString($player->login) . ' AND Game=' . quotedString($aseco->server->getGame());
		$result = mysql_query($query);
		if (mysql_num_rows($result) > 0) {
			$teamname = mysql_result($result, 0, 'teamname');
			if ($teamname == '') {
				$sql = 'UPDATE players SET teamname=' . quotedString($player->teamname) . ' WHERE login=' . quotedString($player->login);
				mysql_query($sql);
			}
		}
		mysql_free_result($result);
	}
}  // match_playerdisconnect

// called @ onStartup
function match_startup($aseco) {
	global $MatchSettings, $matchVersionNumber;

	$aseco->addCall('ChatSendServerMessage', array('Now Loading Matchsave ffMod '.$matchVersionNumber));
	match_loadsettings();
	if ($MatchSettings['savedb']) {
		checkTables();
	}
}  // match_startup

function chat_team($aseco, $command) {

	chat_teamname($aseco, $command);
}  // chat_team

function chat_teamname($aseco, $command) {
	global $matchTeamNameColorsAllowed, $matchOthersCanScore, $matchTeamNameMaxLength, $MatchSettings;

	$player = $command['author'];
	$teamname = $command['params'];
	$oldTeamName = $player->teamname;

	if ($teamname == 'help' || trim($teamname) == '') {

		if ($aseco->server->getGame() == 'TMN') {
			if ($player->teamname) {
				$msg = 'You currently belong to team '.$MatchSettings['col_window_highlite_team'].$player->teamname.'$z'.LF;
				$msg .= str_replace('{#server}', '$000', '$n'.$MatchSettings['hlp'][0].'$z'.LF);
			} else {
				$msg = 'You currently belong to no team.'.LF;
				$msg .= str_replace('{#server}', '$000', '$n'.$MatchSettings['hlpNoTeam'][0].'$z'.LF);
			}

			$msg .= '$fff---------------------------------------$z' . LF;
			$msg .= 'Optional commands for /team (/teamname works too):' . LF;
			$msg .= '     $f00dbsave$z - save your teamname permanently on this server' . LF;
			$msg .= '     $f00dbclear$z - erase your permanent teamname from this server' . LF;
			$msg .= '     $f00dbget$z - get your teamname for this server' . LF;
			$msg .= '     $f00clear$z - clear your current teamname' . LF;
			$msg .= '     $f00players$z - show a list of players in your team' . LF;
			$msg .= '$fff---------------------------------------' . LF;
			$msg .= '$000Use $f00/standings$000 to see current match standings.' . LF;
			$msg .= '$000Also use $f00/tc {#message}Hello Teammates $000to chat only with your team.';
			if (!$MatchSettings['teamchatEnabled']) {
				$msg .= LF.$MatchSettings['col_window_highlite'].'     Currently disabled by an Admin.';
			}
			$aseco->addCall('SendDisplayServerMessageToLogin', array($player->login, $aseco->formatColors($msg), 'OK', '', 0));

		} elseif ($aseco->server->getGame() == 'TMF') {
			$msg = array();
			if ($player->teamname) {
				$header = 'You currently belong to team '.$MatchSettings['col_window_highlite_team'].$player->teamname;
				$msg[] = array(str_replace('{#server}', '$000', '$n'.$MatchSettings['hlp'][0]));
			} else {
				$header = 'You currently belong to no team.';
				$msg[] = array(str_replace('{#server}', '$000', '$n'.$MatchSettings['hlpNoTeam'][0]));
			}

			$msg[] = array('$ddd---------------------------------------');
			$msg[] = array('Optional commands for /team (/teamname works too):');
			$msg[] = array('$f00dbsave', 'save your teamname permanently on this server');
			$msg[] = array('$f00dbclear', 'erase your permanent teamname from this server');
			$msg[] = array('$f00dbget', 'get your teamname for this server');
			$msg[] = array('$f00clear', 'clear your current teamname');
			$msg[] = array('$f00players', 'show a list of players in your team');
			$msg[] = array('$ddd---------------------------------------');
			$msg[] = array('$000Use $f00/standings$000 to see current match standings.');
			$msg[] = array('$000Also use $f00/tc {#message}Hello Teammates $000to chat only with your team.');
			if (!$MatchSettings['teamchatEnabled']) {
				$msg[] = array($MatchSettings['col_window_highlite'].'     Currently disabled by an Admin.');
			}

			// display ManiaLink message
			display_manialink($player->login, $header, array('Icons64x64_1', 'TrackInfo', -0.01), $msg, array(1.0, 0.2, 0.8), 'OK');
		}
	}
	elseif ($teamname == 'clear') {

		$player->teamname = '';
		$aseco->addCall('ChatSendServerMessageToLogin', array('You are no longer a member of a team.', $player->login));
	}
	elseif ($teamname == 'players') {

		if ($player->teamname) {
			$helper = array();
			foreach ($aseco->server->players->player_list as $playa) {
				if ($playa->teamname == $player->teamname) $helper[] = $playa->nickname.'$z';
			}
			sort($helper);
			$header = 'Team '.$MatchSettings['col_window_highlite_team'].$player->teamname.'$z';
			showListWindow($aseco, $player, $helper, $header);
		} else {
			$aseco->addCall('ChatSendServerMessageToLogin', array($aseco->formatColors('{#error}You belong to no team; to join, type $fff/team yourteamname.'), $player->login));
		}
	}
	elseif ($teamname == 'dbsave') {

		$sql = 'UPDATE players SET teamname=' . quotedString($player->teamname) . ' WHERE login=' . quotedString($player->login);
		mysql_query($sql);
		$aseco->addCall('ChatSendServerMessageToLogin', array('Teamname saved to database.', $player->login));
	}
	elseif ($teamname == 'dbclear') {

		$sql = 'UPDATE players SET teamname=\'\' WHERE login=' . quotedString($player->login);
		mysql_query($sql);
		$aseco->addCall('ChatSendServerMessageToLogin', array('Teamname cleared from database.', $player->login));
	}
	elseif ($teamname == 'dbget') {

		$query = 'SELECT teamname FROM players WHERE Login=' . quotedString($player->login) . ' AND Game=' . quotedString($aseco->server->getGame());
		$result = mysql_query($query);
		if (!$result) {
			$aseco->addCall('ChatSendServerMessageToLogin', array('MySQL error = ' . mysql_error() . ', teamname not changed', $player->login));
		} else {
			$row = mysql_fetch_row($result);
			$player->teamname = $row[0];
			if (!$matchTeamNameColorsAllowed) {
				$player->teamname = stripColors($player->teamname);
			}
			$aseco->addCall('ChatSendServerMessageToLogin', array('You have joined team ' . $player->teamname . '.', $player->login));
			mysql_free_result($result);
		}
	}
	else {
		if (!$matchTeamNameColorsAllowed) {
			$teamname = stripColors($teamname);
		}
		$teamname = substr($teamname, 0, $matchTeamNameMaxLength);

		$player->teamname = $teamname;
		$aseco->addCall('ChatSendServerMessageToLogin', array('You have joined team ' . $player->teamname . '.', $player->login));
		$command['params'] = 'dbsave';
		chat_teamname($aseco, $command);
	}

	if ($MatchSettings['showTeamChanges']) {
		$newTeamName = $player->teamname;
		if ($newTeamName != $oldTeamName) {
			foreach ($aseco->server->players->player_list as $playa) {
				if ($playa->login != $player->login) {
					if ($newTeamName == '') {
						$msg = stripColors($player->nickname).' left his team.';
					} else {
						$msg = stripColors($player->nickname).' joined team '.$newTeamName;
					}
					$aseco->addCall('ChatSendServerMessageToLogin', array($msg, $playa->login));
				}
			}
		}
	}
}  // chat_teamname

// called @ onEndRace
function match_endrace($aseco, $info) {
	global $rasp;
	global $matchRunning, $matchRound, $matchTotalRounds, $matchPoints, $matchString,
	       $matchTime, $betweenChallenges, $MatchSettings, $matchOthersCanScore;

	$betweenChallenges = true;

	if (!$MatchSettings['enable']) {
		return;
	}

	$ranking = $info[0];
	if ($ranking[0]['Login'] == '') {
		return;
	}

	$TeamPoints = array();
	$challenge = $info[1];
	$db_challenge_id = $aseco->getChallengeId($challenge['UId']);

	$sql = 'INSERT INTO match_main (trackID) VALUES (' . $db_challenge_id . ')';
	mysql_query($sql);
	$newID = mysql_insert_id();

	$template = $MatchSettings['template'];
	$stgout = str_replace('{HEADER}', '', $template['header']);

	if ($matchRound == 1) {
		$stgout = str_replace('{HEADER}', '<h2>Match - Started at '.date($MatchSettings['format_date'], $matchTime).' - '.date($MatchSettings['format_time'], $matchTime).'</h2>', $template['header']);
		$matchString = '';
	}

	$ctr = 0;
	for ($i = 0; $i < $MatchSettings['pointcount']; $i++) {
		if ($ranking[$i]['Login'] > '') {
			$player = $aseco->server->players->getPlayer($ranking[$i]['Login']);
			if ($player->teamname != '' || $matchOthersCanScore) {
				// if two people have the same time, they both get the same points
				if (($i > 0 && $ranking[$i]['BestTime'] != $ranking[$i - 1]['BestTime'])) {
					$ctr++;
				}

				$rank = $ranking[$i]['Rank'];
				$bt = $ranking[$i]['BestTime'];
				if ($bt != -1) {
					$bt = formattime($bt);
					$pts = $MatchSettings['points'][$ctr];
				} else {
					$bt = 'DNF';
					$pts = 0;
				}

				$TeamPoints[$player->teamname] += $pts;

				if ($MatchSettings['savefile']) {
					$nickname = stripcolors($ranking[$i]['NickName']);
					// RANK, NICK, TIME, TEAM, POINTS are the substituted words for output
					$s = $template['detail'];
					$s = str_replace('{RANK}', $rank, $s);
					$s = str_replace('{NICK}', $nickname, $s);
					$s = str_replace('{TIME}', $bt, $s);
					$s = str_replace('{TEAM}', $player->teamname, $s);
					$s = str_replace('{POINTS}', $pts, $s);

					$stgout .= $s;
				}

				if ($MatchSettings['savedb']) {
					$sql = 'SELECT Id FROM players WHERE Login=' . quotedString($player->login) . ' AND Game=' . quotedString($aseco->server->getGame());
					$result = mysql_query($sql);
					$db_player = mysql_fetch_array($result);
					$db_player_id = $db_player['Id'];
					mysql_free_result($result);
					$sql = 'INSERT INTO match_details (matchID, playerID, teamname, points, score) VALUES (' . $newID . ', ' . $db_player_id . ', ' . quotedString($player->teamname) . ', ' . $pts . ', ' . $ranking[$i]['BestTime'] . ')';
					mysql_query($sql);
				}
			}
		}
	}

	$matchcell = '';
	if ($matchRunning)
		$matchcell = '<th>This Match</th>';
	$stgout .= str_replace('{MATCHCELL}', $matchcell, $template['middle']);

	$tots = '';
	$msg = '';

	if ($matchRound == 1 || !$matchRunning) {
		$matchPoints = array();
	}
	$matchmsg = '';

	foreach ($TeamPoints as $key => $value) {
		if ($key == '') {
			$key = 'OTHERS';
		}
		if ($value != 0) {
			if (!isset($matchPoints[$key])) {
				$matchPoints[$key] = 0;
			}
			$matchPoints[$key] += $value;
		}
	}

	arsort($matchPoints, SORT_NUMERIC);
	foreach ($matchPoints as $key => $value) {
		$s = $template['teamdetail'];

		$matchmsg .= ' '.$MatchSettings['col_teamname_match'] . $key . ' $z'.$MatchSettings['col_match_points'] . $value . ' ';

		$s = str_replace('{TEAM}', $key, $s);
		if ($key == 'OTHERS') {
			$pts = $TeamPoints[''];
		} else {
			$pts = $TeamPoints[$key];
		}
		if (!$pts)
			$pts = 0;
		$s = str_replace('{POINTS}', $pts, $s);

		$matchpts = '';
		if ($matchRunning) {
			$matchpts = '<td>'.$value.'</td>';
		}
		$s = str_replace('{MATCHPOINTS}', $matchpts, $s);
		$stgout .= $s;

		$msg = $msg. ' ' .$MatchSettings['col_teamname_round'] . $key . ' $z'.$MatchSettings['col_round_points'] . $pts . ' ';
	}

	if ($MatchSettings['savefile']) {
		$stgout .= $template['footer'];
		$stgout = str_replace('{TRACK}', stripcolors($challenge['Name']), $stgout);
		$stgout = str_replace('{DATE}', date($MatchSettings['format_date'], time()), $stgout);
		$stgout = str_replace('{TIME}', date($MatchSettings['format_time'], time()), $stgout);

		$fp = fopen($MatchSettings['outfile'], 'a');
		fwrite($fp, $stgout);
		fclose($fp);

		$fp = fopen($MatchSettings['outfilelast'], 'w');
		fwrite($fp, $stgout);
		fclose($fp);

		$matchString .= $stgout;
	}

	$noFinishRace = false;
	if ($msg == '') {
		$noFinishRace = true;
		$msg = '$z'.$MatchSettings['col_round_points'].'Nobody in a team finished.';
	}

	$noFinishMatch = false;
	if ($matchmsg == '') {
		$noFinishMatch = true;
		$matchmsg = '$z'.$MatchSettings['col_match_points'].'Nobody in a team finished';
	}

	foreach ($aseco->server->players->player_list as $player) {
		$toReplace = $player->teamname;
		if (!$toReplace)
			$toReplace = 'OTHERS';

		if (!$noFinishRace) {
			$formattedMsg = str_replace(' '.$MatchSettings['col_teamname_round'].$toReplace.' ', ' '.$MatchSettings['col_teamname_round_highlite'].$toReplace.' ', $msg);
		} else {
			$formattedMsg = $msg;
		}

		if (!$noFinishMatch) {
			$formattedMatchMsg = str_replace(' '.$MatchSettings['col_teamname_match'].$toReplace.' ', ' '.$MatchSettings['col_teamname_match_highlite'].$toReplace.' ', $matchmsg);
		} else {
			$formattedMatchMsg = $matchmsg;
		}
		$formattedMsg = $aseco->formatColors($MatchSettings['str_round'].'$z  ' . $formattedMsg);
		$formattedMatchMsg = $aseco->formatColors($MatchSettings['str_match'].'$z  ' . $formattedMatchMsg);

		$aseco->addCall('ChatSendServerMessageToLogin', array($formattedMsg, $player->login));
		if ($matchRunning) {
			$aseco->addCall('ChatSendServerMessageToLogin', array($formattedMatchMsg, $player->login));
		}
		showHint($aseco, $player);
	}
}  // match_endrace

function showHint($aseco, $player) {
	global $MatchSettings;

	if ($MatchSettings['helpEnabled']) {
		if ($player->teamname) {
			$array = $MatchSettings['hlp'];
			$array[] = $array[0];
			$firstprob = 5;
			$prob = 10;
		} else {
			$array = $MatchSettings['hlpNoTeam'];
			$firstprob = 2;
			$prob = 4;
		}

		srand(microtime()*1000000);
		$showhint = rand(1,10);
		if ($showhint > $firstprob) {
			$hint = '';
			srand(microtime()*100000);
			$showmainhint = (rand(1,10)) > $prob;
			if ($showmainhint) {
				$hint = $array[0];
			} else {
				srand(microtime()*10000000);
				$hint = $array[rand(1,count($array)-1)];
			}
			$aseco->addCall('ChatSendServerMessageToLogin', array($aseco->formatColors('[Hint] '.$hint), $player->login));
		}
	}
}  // showHint

function match_loadsettings() {
	global $aseco, $MatchSettings, $matchOthersCanScore, $matchTeamNameColorsAllowed, $matchTeamNameMaxLength, $teamForceTeams;

	if (!$settings = $aseco->xml_parser->parseXml('matchsave.xml')) {
		trigger_error('Could not read/parse Matchsave config file matchsave.xml !', E_USER_ERROR);
	}
	$settings = $settings['MATCHSAVE_SETTINGS'];
	$MatchSettings['savedb'] = strtolower($settings['SAVE_TO_DB'][0]) == 'true';
	$MatchSettings['savefile'] = strtolower($settings['SAVE_TO_FILE'][0]) == 'true';
	$MatchSettings['template'] = $settings['TEMPLATE_NAME'][0];
	$MatchSettings['outfile'] = $settings['OUTPUT_NAME'][0];
	$MatchSettings['outfilelast'] = $settings['OUTPUT_NAME_LAST'][0];
	$MatchSettings['outfilematch'] = $settings['OUTPUT_NAME_MATCH'][0];
	$MatchSettings['pointcount'] = $settings['MAX_PLAYER_COUNT'][0];
	$MatchSettings['format_date'] = $settings['FORMAT_DATE'][0];
	$MatchSettings['format_time'] = $settings['FORMAT_TIME'][0];
	$MatchSettings['enable'] = strtolower($settings['ENABLED'][0]) == 'true';
	$MatchSettings['teamForceEnabled'] = strtolower($settings['TEAM_FORCE_ENABLED'][0]) == 'true';
	$MatchSettings['teamchatEnabled'] = strtolower($settings['TEAMCHAT_ENABLED'][0]) == 'true';
	$MatchSettings['helpEnabled'] = strtolower($settings['HELP_ENABLED'][0]) == 'true';
	$MatchSettings['playlistDir'] = $settings['PLAYLIST_DIR'][0];
	$MatchSettings['str_round'] = $settings['STR_POINTS_THIS_ROUND'][0];
	$MatchSettings['str_match'] = $settings['STR_POINTS_THIS_MATCH'][0];
	$MatchSettings['col_round_points'] = $settings['COL_ROUND_POINTS'][0];
	$MatchSettings['col_match_points'] = $settings['COL_MATCH_POINTS'][0];
	$MatchSettings['col_teamname_round'] = $settings['COL_TEAMNAME_ROUND'][0];
	$MatchSettings['col_teamname_round_highlite'] = $settings['COL_TEAMNAME_ROUND_HIGHLITE'][0];
	$MatchSettings['col_teamname_match'] = $settings['COL_TEAMNAME_MATCH'][0];
	$MatchSettings['col_teamname_match_highlite'] = $settings['COL_TEAMNAME_MATCH_HIGHLITE'][0];
	$MatchSettings['col_chat_plugin'] = $settings['COL_CHAT_PLUGIN'][0];
	$MatchSettings['col_chat_default'] = $settings['COL_CHAT_DEFAULT'][0];
	$MatchSettings['col_chat_highlite'] = $settings['COL_CHAT_HIGHLITE'][0];
	$MatchSettings['col_window_default'] = $settings['COL_WINDOW_DEFAULT'][0];
	$MatchSettings['col_window_highlite'] = $settings['COL_WINDOW_HIGHLITE'][0];
	$MatchSettings['col_window_highlite_team'] = $settings['COL_WINDOW_HIGHLITE_TEAM'][0];
	$MatchSettings['col_window_special'] = $settings['COL_WINDOW_SPECIAL'][0];
	$MatchSettings['col_window_separator'] = $settings['COL_WINDOW_SEPARATOR'][0];
	$MatchSettings['col_window_hint'] = $settings['COL_WINDOW_HINT'][0];
	$MatchSettings['teamchatPrefix'] = $settings['TEAMCHAT_PREFIX'][0];
	$MatchSettings['resultTimeout'] = $settings['MATCH_RESULTS_TIMEOUT'][0];
	$MatchSettings['bigBrother'] = strtolower($settings['ADMIN_CAN_READ_TEAMCHAT'][0]) == 'true';
	$MatchSettings['showTeamChanges'] = strtolower($settings['SHOW_TEAMNAME_CHANGES_TO_PUBLIC'][0]) == 'true';
	$matchOthersCanScore = strtolower($settings['OTHERS_CAN_SCORE'][0]) == 'true';
	$matchTeamNameMaxLength = intval($settings['TEAMNAME_MAX_LENGTH'][0]);
	$matchTeamNameColorsAllowed = strtolower($settings['TEAMNAME_COLORS_ALLOWED'][0]) == 'true';

	$MatchSettings['hlpNoTeam'] = array();
	$MatchSettings['hlpNoTeam'][] = $settings['HLP_NO_TEAM'][0];
	foreach ($settings['HLP_RANDOM_MSG_NO_TEAM'] as $hlp) {
		$MatchSettings['hlpNoTeam'][] = $hlp;
	}

	$MatchSettings['hlp'] = array();
	$MatchSettings['hlp'][] = $settings['HLP_CHANGE_TEAM'][0];
	foreach ($settings['HLP_RANDOM_MSG'] as $hlp) {
		$MatchSettings['hlp'][] = $hlp;
	}

	//Team Force addition
	$MatchSettings['defaultTeam'] = $settings['TEAMFORCE_TEAMS'][0]['DEFAULT_TEAM_NAME'][0];
	if ($settings['TEAMFORCE_TEAMS'][0]['TEAM']) {
		foreach ($settings['TEAMFORCE_TEAMS'][0]['TEAM'] as $index => $team) {
			if ($team['NAME'][0] && $team['LOGINS'][0]['LOGIN']) {
				foreach ($team['LOGINS'][0]['LOGIN'] as $login) {
					$teamForceTeams[$login] = $team['NAME'][0];
				}
			}
		}
	}

	$s = $settings['POINTS'][0];
	$s = str_replace(' ', '', $s);
	$MatchPoints = explode(',', $s);

	foreach ($MatchPoints as $key => $value) {
		$MatchPoints[$key] = intval($value);
	}

	$MatchSettings['points'] = $MatchPoints;
	$MatchOutput = array();

	if (!$MatchSettings['savefile']) {
		return;
	}
	$fp = fopen($MatchSettings['template'], 'r');
	$data = fread($fp, 32767);  // get whole template
	fclose($fp);
	$i = strpos($data, '<!-- Player Data Begin ->');  // strlen = 25
	$j = strpos($data, '<!-- Player Data End ->');    // strlen = 23
	$k = strpos($data, '<!-- Team Data Begin ->');    // strlen = 23
	$l = strpos($data, '<!-- Team Data End ->');      // strlen = 21
	$header = substr($data, 0, $i);  // strpos returns 0 based, no adj to $i necessary
	$detail = substr($data, $i + 25, $j - ($i + 26) + 1);
	$middle = substr($data, $j + 23, $k - ($j + 24) + 1);

	$teamdetail = substr($data, $k + 23, $l - ($k + 24) + 1);
	$footer = substr($data, $l + 21);

	$MatchOutput['header'] = $header;
	$MatchOutput['detail'] = $detail;
	$MatchOutput['middle'] = $middle;
	$MatchOutput['teamdetail'] = $teamdetail;
	$MatchOutput['footer'] = $footer;

	$MatchSettings['template'] = $MatchOutput;

}  // match_loadsettings
?>
