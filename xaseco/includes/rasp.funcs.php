<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Common functions for RASP 0.4.1 and above
 * Updated by Xymph
 */

require_once('includes/gbxdatafetcher.inc.php');  // provides access to GBX data

Aseco::registerEvent('onPlayerServerMessageAnswer', 'event_multi_message');
Aseco::registerEvent('onChallengeListModified', 'clearChallengesCache');
Aseco::registerEvent('onTracklistChanged', 'clearChallengesCache2');
Aseco::registerEvent('onNewChallenge2', 'initChallengesCache');

global $challengeListCache;
$challengeListCache = array();

// called @ onChallengeListModified & TMF
function clearChallengesCache($aseco, $data) {
	global $challengeListCache;

	// clear cache if challenge list modified
	if ($data[2]) {
		$challengeListCache = array();
		if ($aseco->debug)
			$aseco->console_text('challenges cache cleared');
	}
}  // clearChallengesCache

// called @ onTracklistChanged & !TMF
function clearChallengesCache2($aseco, $event) {
	global $challengeListCache;

	// clear cache on add/remove/read/juke/unjuke events if not TMF
	if ($aseco->server->getGame() != 'TMF' &&
	    $event[0] != 'rename' && $event[0] != 'write') {
		$challengeListCache = array();
		if ($aseco->debug)
			$aseco->console_text('challenges cache cleared upon: ' . $event[0]);
	}
}  // clearChallengesCache2

// called @ onNewChallenge2
function initChallengesCache($aseco, $challenge) {
	global $challengeListCache, $reset_cache_start;

	if ($reset_cache_start) {
		$challengeListCache = array();
		if ($aseco->debug)
			$aseco->console_text('challenges cache reset');
	}
	getChallengesCache($aseco);
	if ($aseco->debug)
		$aseco->console_text('challenges cache inited: ' . count($challengeListCache));
}  // initChallengesCache

function getChallengesCache($aseco) {
	global $challengeListCache;

	if (empty($challengeListCache)) {
		if ($aseco->debug)
			$aseco->console_text('challenges cache loading...');
		// get new list of all tracks
		$aseco->client->resetError();
		$newlist = array();
		$done = false;
		$size = 300;
		$i = 0;
		while (!$done) {
			$aseco->client->query('GetChallengeList', $size, $i);
			$tracks = $aseco->client->getResponse();
			if (!empty($tracks)) {
				if ($aseco->client->isError()) {
					// warning if no tracks found
					if (empty($newlist))
						trigger_error('[' . $aseco->client->getErrorCode() . '] GetChallengeList - ' . $aseco->client->getErrorMessage() . ' - No tracks found!', E_USER_WARNING);
					$done = true;
					break;
				}
				foreach ($tracks as $trow) {
					// obtain various author fields too
					$trackinfo = getChallengeData($aseco->server->trackdir . $trow['FileName'], false);
					if ($trackinfo['name'] != 'file not found') {
						if ($aseco->server->getGame() != 'TMF')
							$trow['Author']    = $trackinfo['author'];
						$trow['AuthorTime']  = $trackinfo['authortime'];
						$trow['AuthorScore'] = $trackinfo['authorscore'];
					}
					$trow['Name'] = stripNewlines($trow['Name']);
					$newlist[$trow['UId']] = $trow;
				}
				if (count($tracks) < $size) {
					// got less than 300 tracks, might as well leave
					$done = true;
				} else {
					$i += $size;
				}
			} else {
				$done = true;
			}
		}

		$challengeListCache = $newlist;
		if ($aseco->debug)
			$aseco->console_text('challenges cache loaded: ' . count($challengeListCache));
	}

	return $challengeListCache;
}  // getChallengesCache


// calls function get_recs() from chat.records2.php
function getAllChallenges($player, $wildcard, $env) {
	global $aseco, $jb_buffer, $maxrecs;

	$player->tracklist = array();

	// get list of ranked records
	$reclist = get_recs($player->id);
	// get new/cached list of tracks
	$newlist = getChallengesCache($aseco);

	if ($aseco->server->getGame() == 'TMN') {
		$head = 'Tracks On This Server:' . LF . 'Id        Rec   Name' . LF;
		$msg = '';
		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = 1;

		foreach ($newlist as $row) {
			// check for wildcard, track name or author name
			if ($wildcard == '*') {
				$pos = 0;
			} else {
				$pos = stripos(stripColors($row['Name']), $wildcard);
				if ($pos === false) {
					$pos = stripos($row['Author'], $wildcard);
				}
			}
			// check for any match
			if ($pos !== false) {
				// store track in player object for jukeboxing
				$trkarr = array();
				$trkarr['name'] = $row['Name'];
				$trkarr['environment'] = $row['Environnement'];
				$trkarr['filename'] = $row['FileName'];
				$trkarr['uid'] = $row['UId'];
				$player->tracklist[] = $trkarr;

				// format track name
				$trackname = $row['Name'];
				if (!$aseco->settings['lists_colortracks'])
					$trackname = stripColors($trackname);
				// grey out if in history
				if (in_array($row['UId'], $jb_buffer))
					$trackname = '{#grey}' . stripColors($trackname);
				else
					$trackname = '{#black}' . $trackname;

				// get corresponding record
				$pos = isset($reclist[$row['UId']]) ? $reclist[$row['UId']] : 0;
				$pos = ($pos >= 1 && $pos <= $maxrecs) ? str_pad($pos, 2, '0', STR_PAD_LEFT) : ' -- ';

				$msg .= '$z' . str_pad($tid, 3, '0', STR_PAD_LEFT) . '.   ' . $pos . '.    '
				        . $trackname . LF;
				$tid++;
				if (++$lines > 9) {
					$player->msgs[] = $aseco->formatColors($head . $msg);
					$lines = 0;
					$msg = '';
				}
			}
		}
		// add if last batch exists
		if ($msg != '')
			$player->msgs[] = $aseco->formatColors($head . $msg);

	} elseif ($aseco->server->getGame() == 'TMF') {
		$envids = array('Stadium' => 11, 'Alpine' => 12, 'Bay' => 13, 'Coast' => 14, 'Island' => 15, 'Rally' => 16, 'Speed' => 17);
		$head = 'Tracks On This Server:';
		$msg = array();
		if ($aseco->server->packmask != 'Stadium')
			$msg[] = array('Id', 'Rec', 'Name', 'Author', 'Env');
		else
			$msg[] = array('Id', 'Rec', 'Name', 'Author');
		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		// reserve extra width for $w tags
		$extra = ($aseco->settings['lists_colortracks'] ? 0.2 : 0);
		if ($aseco->server->packmask != 'Stadium')
			$player->msgs[0] = array(1, $head, array(1.39+$extra, 0.12, 0.1, 0.6+$extra, 0.4, 0.17), array('Icons128x128_1', 'NewTrack', 0.02));
		else
			$player->msgs[0] = array(1, $head, array(1.22+$extra, 0.12, 0.1, 0.6+$extra, 0.4), array('Icons128x128_1', 'NewTrack', 0.02));

		foreach ($newlist as $row) {
			// check for wildcard, track name or author name
			if ($wildcard == '*') {
				$pos = 0;
			} else {
				$pos = stripos(stripColors($row['Name']), $wildcard);
				if ($pos === false) {
					$pos = stripos($row['Author'], $wildcard);
				}
			}
			// check for environment
			if ($env == '*') {
				$pose = 0;
			} else {
				$pose = stripos($row['Environnement'], $env);
			}
			// check for any match
			if ($pos !== false && $pose !== false) {
				// store track in player object for jukeboxing
				$trkarr = array();
				$trkarr['name'] = $row['Name'];
				$trkarr['author'] = $row['Author'];
				$trkarr['environment'] = $row['Environnement'];
				$trkarr['filename'] = $row['FileName'];
				$trkarr['uid'] = $row['UId'];
				$player->tracklist[] = $trkarr;

				// format track name
				$trackname = $row['Name'];
				if (!$aseco->settings['lists_colortracks'])
					$trackname = stripColors($trackname);
				// grey out if in history
				if (in_array($row['UId'], $jb_buffer))
					$trackname = '{#grey}' . stripColors($trackname);
				else {
					$trackname = '{#black}' . $trackname;
					// add clickable button
					if ($aseco->settings['clickable_lists'] && $tid <= 1900)
						$trackname = array($trackname, $tid+100);  // action id
				}
				// format author name
				$trackauthor = $row['Author'];
				// add clickable button
				if ($aseco->settings['clickable_lists'] && $tid <= 1900)
					$trackauthor = array($trackauthor, -100-$tid);  // action id
				// format env name
				$trackenv = $row['Environnement'];
				// add clickable button
				if ($aseco->settings['clickable_lists'])
					$trackenv = array($trackenv, $envids[$row['Environnement']]);  // action id

				// get corresponding record
				$pos = isset($reclist[$row['UId']]) ? $reclist[$row['UId']] : 0;
				$pos = ($pos >= 1 && $pos <= $maxrecs) ? str_pad($pos, 2, '0', STR_PAD_LEFT) : '-- ';

				if ($aseco->server->packmask != 'Stadium')
					$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
					               $pos . '.', $trackname, $trackauthor, $trackenv);
				else
					$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
					               $pos . '.', $trackname, $trackauthor);
				$tid++;
				if (++$lines > 14) {
					$player->msgs[] = $msg;
					$lines = 0;
					$msg = array();
					if ($aseco->server->packmask != 'Stadium')
						$msg[] = array('Id', 'Rec', 'Name', 'Author', 'Env');
					else
						$msg[] = array('Id', 'Rec', 'Name', 'Author');
				}
			}
		}
		// add if last batch exists
		if (count($msg) > 1)
			$player->msgs[] = $msg;
	}
}  // getAllChallenges

function getChallengesByKarma($player, $karmaval) {
	global $aseco, $jb_buffer;

	$player->tracklist = array();

	// get list of karma values for all matching tracks
	$order = ($karmaval <= 0 ? 'ASC' : 'DESC');
	if ($karmaval == 0) {
		$sql = '(SELECT uid, SUM(score) AS karma FROM challenges, rs_karma
		         WHERE challenges.id=rs_karma.challengeid
		         GROUP BY uid HAVING karma = 0)
		        UNION
		        (SELECT uid, 0 FROM challenges WHERE id NOT IN
		         (SELECT DISTINCT challengeid FROM rs_karma))
		        ORDER BY karma ' . $order;
	} else {
		$sql = 'SELECT uid, SUM(score) AS karma FROM challenges, rs_karma
		        WHERE challenges.id=rs_karma.challengeid
		        GROUP BY uid
		        HAVING karma ' . ($karmaval < 0 ? "<= $karmaval" : ">= $karmaval") . '
		        ORDER BY karma ' . $order;
	}
	$result = mysql_query($sql);
	if (mysql_num_rows($result) == 0) {
		mysql_free_result($result);
		return;
	}

	// get new/cached list of tracks
	$newlist = getChallengesCache($aseco);

	if ($aseco->server->getGame() == 'TMN') {
		$head = 'Tracks by Karma (' . $order . '):' . LF . 'Id     Karma  Name' . LF;
		$msg = '';
		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = 1;

		while ($dbrow = mysql_fetch_array($result)) {
			// does the uid exist in the current server track list?
			if (array_key_exists($dbrow[0], $newlist)) {
				$row = $newlist[$dbrow[0]];
				// store track in player object for jukeboxing
				$trkarr = array();
				$trkarr['name'] = $row['Name'];
				$trkarr['environment'] = $row['Environnement'];
				$trkarr['filename'] = $row['FileName'];
				$trkarr['uid'] = $row['UId'];
				$player->tracklist[] = $trkarr;

				// format track name
				$trackname = $row['Name'];
				if (!$aseco->settings['lists_colortracks'])
					$trackname = stripColors($trackname);
				// grey out if in history
				if (in_array($row['UId'], $jb_buffer))
					$trackname = '{#grey}' . stripColors($trackname);
				else
					$trackname = '{#black}' . $trackname;

				$msg .= '$z' . str_pad($tid, 3, '0', STR_PAD_LEFT) . '.   '
				        . str_pad($dbrow[1], 4, '  ', STR_PAD_LEFT) . '    '
				        . $trackname . LF;
				$tid++;
				if (++$lines > 9) {
					$player->msgs[] = $aseco->formatColors($head . $msg);
					$lines = 0;
					$msg = '';
				}
			}
		}
		// add if last batch exists
		if ($msg != '')
			$player->msgs[] = $aseco->formatColors($head . $msg);

	} elseif ($aseco->server->getGame() == 'TMF') {
		$envids = array('Stadium' => 11, 'Alpine' => 12, 'Bay' => 13, 'Coast' => 14, 'Island' => 15, 'Rally' => 16, 'Speed' => 17);
		$head = 'Tracks by Karma (' . $order . '):';
		$msg = array();
		if ($aseco->server->packmask != 'Stadium')
			$msg[] = array('Id', 'Karma', 'Name', 'Author', 'Env');
		else
			$msg[] = array('Id', 'Karma', 'Name', 'Author');
		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		// reserve extra width for $w tags
		$extra = ($aseco->settings['lists_colortracks'] ? 0.2 : 0);
		if ($aseco->server->packmask != 'Stadium')
			$player->msgs[0] = array(1, $head, array(1.44+$extra, 0.12, 0.15, 0.6+$extra, 0.4, 0.17), array('Icons128x128_1', 'NewTrack', 0.02));
		else
			$player->msgs[0] = array(1, $head, array(1.27+$extra, 0.12, 0.15, 0.6+$extra, 0.4), array('Icons128x128_1', 'NewTrack', 0.02));

		while ($dbrow = mysql_fetch_array($result)) {
			// does the uid exist in the current server track list?
			if (array_key_exists($dbrow[0], $newlist)) {
				$row = $newlist[$dbrow[0]];
				// store track in player object for jukeboxing
				$trkarr = array();
				$trkarr['name'] = $row['Name'];
				$trkarr['author'] = $row['Author'];
				$trkarr['environment'] = $row['Environnement'];
				$trkarr['filename'] = $row['FileName'];
				$trkarr['uid'] = $row['UId'];
				$player->tracklist[] = $trkarr;

				// format track name
				$trackname = $row['Name'];
				if (!$aseco->settings['lists_colortracks'])
					$trackname = stripColors($trackname);
				// grey out if in history
				if (in_array($row['UId'], $jb_buffer))
					$trackname = '{#grey}' . stripColors($trackname);
				else {
					$trackname = '{#black}' . $trackname;
				}
				// format author name
				$trackauthor = $row['Author'];
				// format karma
				$trackkarma = str_pad($dbrow[1], 4, '  ', STR_PAD_LEFT);
				// format env name
				$trackenv = $row['Environnement'];
				// add clickable button
				if ($aseco->settings['clickable_lists'])
					$trackenv = array($trackenv, $envids[$row['Environnement']]);

				// add clickable buttons
				if ($aseco->settings['clickable_lists'] && $tid <= 1900) {
					$trackname = array($trackname, $tid+100);  // action ids
					$trackauthor = array($trackauthor, -100-$tid);
					$trackkarma = array($trackkarma, -6000-$tid);
				}

				if ($aseco->server->packmask != 'Stadium')
					$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
					               $trackkarma, $trackname, $trackauthor, $trackenv);
				else
					$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
					               $trackkarma, $trackname, $trackauthor);
				$tid++;
				if (++$lines > 14) {
					$player->msgs[] = $msg;
					$lines = 0;
					$msg = array();
					if ($aseco->server->packmask != 'Stadium')
						$msg[] = array('Id', 'Karma', 'Name', 'Author', 'Env');
					else
						$msg[] = array('Id', 'Karma', 'Name', 'Author');
				}
			}
		}
		// add if last batch exists
		if (count($msg) > 1)
			$player->msgs[] = $msg;
	}

	mysql_free_result($result);
}  // getChallengesByKarma

function getChallengesNoFinish($player) {
	global $aseco, $jb_buffer;

	$player->tracklist = array();

	// get list of finished tracks
	$sql = 'SELECT DISTINCT challengeID FROM rs_times
	        WHERE playerID=' . $player->id . ' ORDER BY challengeID';
	$result = mysql_query($sql);
	$finished = array();
	if (mysql_num_rows($result) > 0) {
		while ($dbrow = mysql_fetch_array($result))
			$finished[] = $dbrow[0];
	}
	mysql_free_result($result);

	// get list of unfinished tracks
	// simpler but less efficient query:
	// $sql = 'SELECT uid FROM challenges WHERE id NOT IN
	//         (SELECT DISTINCT challengeID FROM rs_times, players
	//          WHERE rs_times.playerID=players.id AND players.login=' . quotedString($player->login) . ')';
	$sql = 'SELECT uid FROM challenges';
	if (!empty($finished))
		$sql .= ' WHERE id NOT IN (' . implode(',', $finished) . ')';
	$result = mysql_query($sql);
	if (mysql_num_rows($result) == 0) {
		mysql_free_result($result);
		return;
	}

	// get new/cached list of tracks
	$newlist = getChallengesCache($aseco);

	if ($aseco->server->getGame() == 'TMN') {
		$head = 'Tracks You Haven\'t Finished:' . LF . 'Id        Name' . LF;
		$msg = '';
		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = 1;

		while ($dbrow = mysql_fetch_array($result)) {
			// does the uid exist in the current server track list?
			if (array_key_exists($dbrow[0], $newlist)) {
				$row = $newlist[$dbrow[0]];
				// store track in player object for jukeboxing
				$trkarr = array();
				$trkarr['name'] = $row['Name'];
				$trkarr['environment'] = $row['Environnement'];
				$trkarr['filename'] = $row['FileName'];
				$trkarr['uid'] = $row['UId'];
				$player->tracklist[] = $trkarr;

				// format track name
				$trackname = $row['Name'];
				if (!$aseco->settings['lists_colortracks'])
					$trackname = stripColors($trackname);
				// grey out if in history
				if (in_array($row['UId'], $jb_buffer))
					$trackname = '{#grey}' . stripColors($trackname);
				else
					$trackname = '{#black}' . $trackname;

				$msg .= '$z' . str_pad($tid, 3, '0', STR_PAD_LEFT) . '.   '
				        . $trackname . LF;
				$tid++;
				if (++$lines > 9) {
					$player->msgs[] = $aseco->formatColors($head . $msg);
					$lines = 0;
					$msg = '';
				}
			}
		}
		// add if last batch exists
		if ($msg != '')
			$player->msgs[] = $aseco->formatColors($head . $msg);

	} elseif ($aseco->server->getGame() == 'TMF') {
		$envids = array('Stadium' => 11, 'Alpine' => 12, 'Bay' => 13, 'Coast' => 14, 'Island' => 15, 'Rally' => 16, 'Speed' => 17);
		$head = 'Tracks You Haven\'t Finished:';
		$msg = array();
		if ($aseco->server->packmask != 'Stadium')
			$msg[] = array('Id', 'Name', 'Author', 'Env');
		else
			$msg[] = array('Id', 'Name', 'Author');
		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		// reserve extra width for $w tags
		$extra = ($aseco->settings['lists_colortracks'] ? 0.2 : 0);
		if ($aseco->server->packmask != 'Stadium')
			$player->msgs[0] = array(1, $head, array(1.29+$extra, 0.12, 0.6+$extra, 0.4, 0.17), array('Icons128x128_1', 'NewTrack', 0.02));
		else
			$player->msgs[0] = array(1, $head, array(1.12+$extra, 0.12, 0.6+$extra, 0.4), array('Icons128x128_1', 'NewTrack', 0.02));

		while ($dbrow = mysql_fetch_array($result)) {
			// does the uid exist in the current server track list?
			if (array_key_exists($dbrow[0], $newlist)) {
				$row = $newlist[$dbrow[0]];
				// store track in player object for jukeboxing
				$trkarr = array();
				$trkarr['name'] = $row['Name'];
				$trkarr['author'] = $row['Author'];
				$trkarr['environment'] = $row['Environnement'];
				$trkarr['filename'] = $row['FileName'];
				$trkarr['uid'] = $row['UId'];
				$player->tracklist[] = $trkarr;

				// format track name
				$trackname = $row['Name'];
				if (!$aseco->settings['lists_colortracks'])
					$trackname = stripColors($trackname);
				// grey out if in history
				if (in_array($row['UId'], $jb_buffer))
					$trackname = '{#grey}' . stripColors($trackname);
				else {
					$trackname = '{#black}' . $trackname;
					// add clickable button
					if ($aseco->settings['clickable_lists'] && $tid <= 1900)
						$trackname = array($trackname, $tid+100);  // action id
				}
				// format author name
				$trackauthor = $row['Author'];
				// add clickable button
				if ($aseco->settings['clickable_lists'] && $tid <= 1900)
					$trackauthor = array($trackauthor, -100-$tid);  // action id
				// format env name
				$trackenv = $row['Environnement'];
				// add clickable button
				if ($aseco->settings['clickable_lists'])
					$trackenv = array($trackenv, $envids[$row['Environnement']]);  // action id

				if ($aseco->server->packmask != 'Stadium')
					$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
					               $trackname, $trackauthor, $trackenv);
				else
					$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
					               $trackname, $trackauthor);
				$tid++;
				if (++$lines > 14) {
					$player->msgs[] = $msg;
					$lines = 0;
					$msg = array();
					if ($aseco->server->packmask != 'Stadium')
						$msg[] = array('Id', 'Name', 'Author', 'Env');
					else
						$msg[] = array('Id', 'Name', 'Author');
				}
			}
		}
		// add if last batch exists
		if (count($msg) > 1)
			$player->msgs[] = $msg;
	}

	mysql_free_result($result);
}  // getChallengesNoFinish

function getChallengesNoRank($player) {
	global $aseco, $jb_buffer, $maxrecs;

	$player->tracklist = array();

	// get list of finished tracks
	$sql = 'SELECT DISTINCT challengeID FROM rs_times
	        WHERE playerID=' . $player->id . ' ORDER BY challengeID';
	$result = mysql_query($sql);
	$finished = array();
	if (mysql_num_rows($result) > 0) {
		while ($dbrow = mysql_fetch_array($result))
			$finished[] = $dbrow[0];
	}
	mysql_free_result($result);

	// get list of finished tracks
	// simpler but less efficient query:
	// $sql = 'SELECT id,uid FROM challenges WHERE id IN
	//         (SELECT DISTINCT challengeID FROM rs_times, players
	//          WHERE rs_times.playerID=players.id AND players.login=' . quotedString($player->login) . ')';
	$sql = 'SELECT id,uid FROM challenges WHERE id ';
	if (!empty($finished))
		$sql .= 'IN (' . implode(',', $finished) . ')';
	else
		$sql .= '= 0';  // empty list
	$result = mysql_query($sql);
	if (mysql_num_rows($result) == 0) {
		mysql_free_result($result);
		return;
	}

	$order = ($aseco->server->gameinfo->mode == Gameinfo::STNT ? 'DESC' : 'ASC');
	$unranked = array();
	$i = 0;
	// check if player not in top $maxrecs on each track
	while ($dbrow = mysql_fetch_array($result)) {
		// more efficient but unsupported query: :(
		// $sql2 = 'SELECT id FROM players WHERE (id=' . $player->id . ') AND (id NOT IN
		//          (SELECT playerid FROM records WHERE challengeid=' . $dbrow[0] . ' ORDER by score, date LIMIT ' . $maxrecs . '))';
		$sql2 = 'SELECT playerid FROM records
		         WHERE challengeid=' . $dbrow[0] . '
		         ORDER by score ' . $order . ', date ASC LIMIT ' . $maxrecs;
		$result2 = mysql_query($sql2);
		$found = false;
		if (mysql_num_rows($result2) > 0) {
			while ($plrow = mysql_fetch_array($result2)) {
				if ($player->id == $plrow[0]) {
					$found = true;
					break;
				}
			}
		}
		if (!$found) {
			$unranked[$i++] = $dbrow[1];
		}
		mysql_free_result($result2);
	}
	if (empty($unranked)) {
		mysql_free_result($result);
		return;
	}

	// get new/cached list of tracks
	$newlist = getChallengesCache($aseco);

	if ($aseco->server->getGame() == 'TMN') {
		$head = 'Tracks You Have No Rank On:' . LF . 'Id        Name' . LF;
		$msg = '';
		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = 1;

		for ($i = 0; $i < count($unranked); $i++) {
			// does the uid exist in the current server track list?
			if (array_key_exists($unranked[$i], $newlist)) {
				$row = $newlist[$unranked[$i]];
				// store track in player object for jukeboxing
				$trkarr = array();
				$trkarr['name'] = $row['Name'];
				$trkarr['environment'] = $row['Environnement'];
				$trkarr['filename'] = $row['FileName'];
				$trkarr['uid'] = $row['UId'];
				$player->tracklist[] = $trkarr;

				// format track name
				$trackname = $row['Name'];
				if (!$aseco->settings['lists_colortracks'])
					$trackname = stripColors($trackname);
				// grey out if in history
				if (in_array($row['UId'], $jb_buffer))
					$trackname = '{#grey}' . stripColors($trackname);
				else
					$trackname = '{#black}' . $trackname;

				$msg .= '$z' . str_pad($tid, 3, '0', STR_PAD_LEFT) . '.   '
				        . $trackname . LF;
				$tid++;
				if (++$lines > 9) {
					$player->msgs[] = $aseco->formatColors($head . $msg);
					$lines = 0;
					$msg = '';
				}
			}
		}
		// add if last batch exists
		if ($msg != '')
			$player->msgs[] = $aseco->formatColors($head . $msg);

	} elseif ($aseco->server->getGame() == 'TMF') {
		$envids = array('Stadium' => 11, 'Alpine' => 12, 'Bay' => 13, 'Coast' => 14, 'Island' => 15, 'Rally' => 16, 'Speed' => 17);
		$head = 'Tracks You Have No Rank On:';
		$msg = array();
		if ($aseco->server->packmask != 'Stadium')
			$msg[] = array('Id', 'Name', 'Author', 'Env');
		else
			$msg[] = array('Id', 'Name', 'Author');
		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		// reserve extra width for $w tags
		$extra = ($aseco->settings['lists_colortracks'] ? 0.2 : 0);
		if ($aseco->server->packmask != 'Stadium')
			$player->msgs[0] = array(1, $head, array(1.29+$extra, 0.12, 0.6+$extra, 0.4, 0.17), array('Icons128x128_1', 'NewTrack', 0.02));
		else
			$player->msgs[0] = array(1, $head, array(1.12+$extra, 0.12, 0.6+$extra, 0.4), array('Icons128x128_1', 'NewTrack', 0.02));

		for ($i = 0; $i < count($unranked); $i++) {
			// does the uid exist in the current server track list?
			if (array_key_exists($unranked[$i], $newlist)) {
				$row = $newlist[$unranked[$i]];
				// store track in player object for jukeboxing
				$trkarr = array();
				$trkarr['name'] = $row['Name'];
				$trkarr['author'] = $row['Author'];
				$trkarr['environment'] = $row['Environnement'];
				$trkarr['filename'] = $row['FileName'];
				$trkarr['uid'] = $row['UId'];
				$player->tracklist[] = $trkarr;

				// format track name
				$trackname = $row['Name'];
				if (!$aseco->settings['lists_colortracks'])
					$trackname = stripColors($trackname);
				// grey out if in history
				if (in_array($row['UId'], $jb_buffer))
					$trackname = '{#grey}' . stripColors($trackname);
				else {
					$trackname = '{#black}' . $trackname;
					// add clickable button
					if ($aseco->settings['clickable_lists'] && $tid <= 1900)
						$trackname = array($trackname, $tid+100);  // action id
				}
				// format author name
				$trackauthor = $row['Author'];
				// add clickable button
				if ($aseco->settings['clickable_lists'] && $tid <= 1900)
					$trackauthor = array($trackauthor, -100-$tid);  // action id
				// format env name
				$trackenv = $row['Environnement'];
				// add clickable button
				if ($aseco->settings['clickable_lists'])
					$trackenv = array($trackenv, $envids[$row['Environnement']]);  // action id

				if ($aseco->server->packmask != 'Stadium')
					$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
					               $trackname, $trackauthor, $trackenv);
				else
					$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
					               $trackname, $trackauthor);
				$tid++;
				if (++$lines > 9) {
					$player->msgs[] = $msg;
					$lines = 0;
					$msg = array();
					if ($aseco->server->packmask != 'Stadium')
						$msg[] = array('Id', 'Name', 'Author', 'Env');
					else
						$msg[] = array('Id', 'Name', 'Author');
				}
			}
		}
		// add if last batch exists
		if (count($msg))
			$player->msgs[] = $msg;
	}

	mysql_free_result($result);
}  // getChallengesNoRank

function getChallengesNoGold($player) {
	global $aseco, $jb_buffer;

	$player->tracklist = array();

	// check for Stunts mode
	if ($aseco->server->gameinfo->mode != Gameinfo::STNT) {

		// get list of finished tracks with their best (minimum) times
		$sql = 'SELECT DISTINCT c.uid,t1.score FROM rs_times t1, challenges c
		        WHERE (playerID=' . $player->id . ' AND t1.challengeID=c.id AND
		               score=(SELECT MIN(t2.score) FROM rs_times t2
		                      WHERE playerID=' . $player->id . ' AND t1.challengeID=t2.challengeID))';
		$result = mysql_query($sql);
		if (mysql_num_rows($result) == 0) {
			mysql_free_result($result);
			return;
		}

		// get new/cached list of tracks
		$newlist = getChallengesCache($aseco);

		if ($aseco->server->getGame() == 'TMN') {
			$head = 'Tracks You Didn\'t Beat Gold Time On:' . LF . 'Id        Name $n(+Time)$m' . LF;
			$msg = '';
			$tid = 1;
			$lines = 0;
			$player->msgs = array();
			$player->msgs[0] = 1;

			while ($dbrow = mysql_fetch_array($result)) {
				// does the uid exist in the current server track list?
				if (array_key_exists($dbrow[0], $newlist)) {
					$row = $newlist[$dbrow[0]];
					// does best time beat track's Gold time?
					if ($dbrow[1] > $row['GoldTime']) {
						// store track in player object for jukeboxing
						$trkarr = array();
						$trkarr['name'] = $row['Name'];
						$trkarr['environment'] = $row['Environnement'];
						$trkarr['filename'] = $row['FileName'];
						$trkarr['uid'] = $row['UId'];
						$player->tracklist[] = $trkarr;

						// format track name
						$trackname = $row['Name'];
						if (!$aseco->settings['lists_colortracks'])
							$trackname = stripColors($trackname);
						// grey out if in history
						if (in_array($row['UId'], $jb_buffer))
							$trackname = '{#grey}' . stripColors($trackname);
						else
							$trackname = '{#black}' . $trackname;

						// compute difference to Gold time
						$diff = $dbrow[1] - $row['GoldTime'];
						$sec = floor($diff/1000);
						$hun = ($diff - ($sec * 1000)) / 10;

						$msg .= str_pad($tid, 3, '0', STR_PAD_LEFT) . '.   ' . $trackname
						        . ' $z$n(+' . sprintf("%d.%02d", $sec, $hun) . ')$m' . LF;
						$tid++;
						if (++$lines > 9) {
							$player->msgs[] = $aseco->formatColors($head . $msg);
							$lines = 0;
							$msg = '';
						}
					}
				}
			}
			// add if last batch exists
			if ($msg != '')
				$player->msgs[] = $aseco->formatColors($head . $msg);

		} elseif ($aseco->server->getGame() == 'TMF') {
			$envids = array('Stadium' => 11, 'Alpine' => 12, 'Bay' => 13, 'Coast' => 14, 'Island' => 15, 'Rally' => 16, 'Speed' => 17);
			$head = 'Tracks You Didn\'t Beat Gold Time On:';
			$msg = array();
			if ($aseco->server->packmask != 'Stadium')
				$msg[] = array('Id', 'Name', 'Author', 'Env', 'Time');
			else
				$msg[] = array('Id', 'Name', 'Author', 'Time');
			$tid = 1;
			$lines = 0;
			$player->msgs = array();
			// reserve extra width for $w tags
			$extra = ($aseco->settings['lists_colortracks'] ? 0.2 : 0);
			if ($aseco->server->packmask != 'Stadium')
				$player->msgs[0] = array(1, $head, array(1.42+$extra, 0.12, 0.6+$extra, 0.4, 0.15, 0.15), array('Icons128x128_1', 'NewTrack', 0.02));
			else
				$player->msgs[0] = array(1, $head, array(1.27+$extra, 0.12, 0.6+$extra, 0.4, 0.15), array('Icons128x128_1', 'NewTrack', 0.02));

			while ($dbrow = mysql_fetch_array($result)) {
				// does the uid exist in the current server track list?
				if (array_key_exists($dbrow[0], $newlist)) {
					$row = $newlist[$dbrow[0]];
					// does best time beat track's Gold time?
					if ($dbrow[1] > $row['GoldTime']) {
						// store track in player object for jukeboxing
						$trkarr = array();
						$trkarr['name'] = $row['Name'];
						$trkarr['author'] = $row['Author'];
						$trkarr['environment'] = $row['Environnement'];
						$trkarr['filename'] = $row['FileName'];
						$trkarr['uid'] = $row['UId'];
						$player->tracklist[] = $trkarr;

						// format track name
						$trackname = $row['Name'];
						if (!$aseco->settings['lists_colortracks'])
							$trackname = stripColors($trackname);
						// grey out if in history
						if (in_array($row['UId'], $jb_buffer))
							$trackname = '{#grey}' . stripColors($trackname);
						else {
							$trackname = '{#black}' . $trackname;
							// add clickable button
							if ($aseco->settings['clickable_lists'] && $tid <= 1900)
								$trackname = array($trackname, $tid+100);  // action id
						}
						// format author name
						$trackauthor = $row['Author'];
						// add clickable button
						if ($aseco->settings['clickable_lists'] && $tid <= 1900)
							$trackauthor = array($trackauthor, -100-$tid);  // action id
						// format env name
						$trackenv = $row['Environnement'];
						// add clickable button
						if ($aseco->settings['clickable_lists'])
							$trackenv = array($trackenv, $envids[$row['Environnement']]);  // action id

						// compute difference to Gold time
						$diff = $dbrow[1] - $row['GoldTime'];
						$sec = floor($diff/1000);
						$hun = ($diff - ($sec * 1000)) / 10;

						if ($aseco->server->packmask != 'Stadium')
							$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
							               $trackname, $trackauthor, $trackenv,
							               '+' . sprintf("%d.%02d", $sec, $hun));
						else
							$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
							               $trackname, $trackauthor,
							               '+' . sprintf("%d.%02d", $sec, $hun));
						$tid++;
						if (++$lines > 14) {
							$player->msgs[] = $msg;
							$lines = 0;
							$msg = array();
							if ($aseco->server->packmask != 'Stadium')
								$msg[] = array('Id', 'Name', 'Author', 'Env', 'Time');
							else
								$msg[] = array('Id', 'Name', 'Author', 'Time');
						}
					}
				}
			}
			// add if last batch exists
			if (count($msg) > 1)
				$player->msgs[] = $msg;
		}

	} else { // Stunts mode

		// get list of finished tracks with their best (maximum) scores
		$sql = 'SELECT DISTINCT c.uid,t1.score FROM rs_times t1, challenges c
		        WHERE (playerID=' . $player->id . ' AND t1.challengeID=c.id AND
		               score=(SELECT MAX(t2.score) FROM rs_times t2
		                      WHERE playerID=' . $player->id . ' AND t1.challengeID=t2.challengeID))';
		$result = mysql_query($sql);
		if (mysql_num_rows($result) == 0) {
			mysql_free_result($result);
			return;
		}

		// get new/cached list of tracks
		$newlist = getChallengesCache($aseco);

		// only in TMUF anyway
		{
			$head = 'Tracks You Didn\'t Beat Gold Score On:';
			$msg = array();
			$msg[] = array('Id', 'Name', 'Author', 'Env', 'Score');
			$tid = 1;
			$lines = 0;
			$player->msgs = array();
			// reserve extra width for $w tags
			$extra = ($aseco->settings['lists_colortracks'] ? 0.2 : 0);
			$player->msgs[0] = array(1, $head, array(1.42+$extra, 0.12, 0.6+$extra, 0.4, 0.15, 0.15), array('Icons128x128_1', 'NewTrack', 0.02));

			while ($dbrow = mysql_fetch_array($result)) {
				// does the uid exist in the current server track list?
				if (array_key_exists($dbrow[0], $newlist)) {
					$row = $newlist[$dbrow[0]];
					// does best score beat track's Gold score?
					if ($dbrow[1] < $row['GoldTime']) {
						// store track in player object for jukeboxing
						$trkarr = array();
						$trkarr['name'] = $row['Name'];
						$trkarr['author'] = $row['Author'];
						$trkarr['environment'] = $row['Environnement'];
						$trkarr['filename'] = $row['FileName'];
						$trkarr['uid'] = $row['UId'];
						$player->tracklist[] = $trkarr;

						// format track name
						$trackname = $row['Name'];
						if (!$aseco->settings['lists_colortracks'])
							$trackname = stripColors($trackname);
						// grey out if in history
						if (in_array($row['UId'], $jb_buffer))
							$trackname = '{#grey}' . stripColors($trackname);
						else {
							$trackname = '{#black}' . $trackname;
							// add clickable button
							if ($aseco->settings['clickable_lists'] && $tid <= 1900)
								$trackname = array($trackname, $tid+100);  // action id
						}
						// format author name
						$trackauthor = $row['Author'];
						// add clickable button
						if ($aseco->settings['clickable_lists'] && $tid <= 1900)
							$trackauthor = array($trackauthor, -100-$tid);  // action id
						// format env name
						$trackenv = $row['Environnement'];
						// add clickable button
						if ($aseco->settings['clickable_lists'])
							$trackenv = array($trackenv, $envids[$row['Environnement']]);  // action id

						// compute difference to Gold score
						$diff = $row['GoldTime'] - $dbrow[1];

						$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
						               $trackname, $trackauthor, $trackenv,
						               '-' . $diff);
						$tid++;
						if (++$lines > 14) {
							$player->msgs[] = $msg;
							$lines = 0;
							$msg = array();
							$msg[] = array('Id', 'Name', 'Author', 'Env', 'Score');
						}
					}
				}
			}
			// add if last batch exists
			if (count($msg) > 1)
				$player->msgs[] = $msg;
		}
	}

	mysql_free_result($result);
}  // getChallengesNoGold

function getChallengesNoAuthor($player) {
	global $aseco, $jb_buffer;

	$player->tracklist = array();

	// check for Stunts mode
	if ($aseco->server->gameinfo->mode != Gameinfo::STNT) {

		// get list of finished tracks with their best (minimum) times
		$sql = 'SELECT DISTINCT c.uid,t1.score FROM rs_times t1, challenges c
		        WHERE (playerID=' . $player->id . ' AND t1.challengeID=c.id AND
		               score=(SELECT MIN(t2.score) FROM rs_times t2
		                      WHERE playerID=' . $player->id . ' AND t1.challengeID=t2.challengeID))';
		$result = mysql_query($sql);
		if (mysql_num_rows($result) == 0) {
			mysql_free_result($result);
			return;
		}

		// get new/cached list of tracks
		$newlist = getChallengesCache($aseco);

		if ($aseco->server->getGame() == 'TMN') {
			$head = 'Tracks You Didn\'t Beat Author Time On:' . LF . 'Id        Name $n(+Time)$m' . LF;
			$msg = '';
			$tid = 1;
			$lines = 0;
			$player->msgs = array();
			$player->msgs[0] = 1;

			while ($dbrow = mysql_fetch_array($result)) {
				// does the uid exist in the current server track list?
				if (array_key_exists($dbrow[0], $newlist)) {
					$row = $newlist[$dbrow[0]];
					// does best time beat track's Author time?
					if ($dbrow[1] > $row['AuthorTime']) {
						// store track in player object for jukeboxing
						$trkarr = array();
						$trkarr['name'] = $row['Name'];
						$trkarr['environment'] = $row['Environnement'];
						$trkarr['filename'] = $row['FileName'];
						$trkarr['uid'] = $row['UId'];
						$player->tracklist[] = $trkarr;

						// format track name
						$trackname = $row['Name'];
						if (!$aseco->settings['lists_colortracks'])
							$trackname = stripColors($trackname);
						// grey out if in history
						if (in_array($row['UId'], $jb_buffer))
							$trackname = '{#grey}' . stripColors($trackname);
						else
							$trackname = '{#black}' . $trackname;

						// compute difference to Author time
						$diff = $dbrow[1] - $row['AuthorTime'];
						$sec = floor($diff/1000);
						$hun = ($diff - ($sec * 1000)) / 10;

						$msg .= str_pad($tid, 3, '0', STR_PAD_LEFT) . '.   ' . $trackname
						        . ' $z$n(+' . sprintf("%d.%02d", $sec, $hun) . ')$m' . LF;
						$tid++;
						if (++$lines > 9) {
							$player->msgs[] = $aseco->formatColors($head . $msg);
							$lines = 0;
							$msg = '';
						}
					}
				}
			}
			// add if last batch exists
			if ($msg != '')
				$player->msgs[] = $aseco->formatColors($head . $msg);

		} elseif ($aseco->server->getGame() == 'TMF') {
			$envids = array('Stadium' => 11, 'Alpine' => 12, 'Bay' => 13, 'Coast' => 14, 'Island' => 15, 'Rally' => 16, 'Speed' => 17);
			$head = 'Tracks You Didn\'t Beat Author Time On:';
			$msg = array();
			if ($aseco->server->packmask != 'Stadium')
				$msg[] = array('Id', 'Name', 'Author', 'Env', 'Time');
			else
				$msg[] = array('Id', 'Name', 'Author', 'Time');
			$tid = 1;
			$lines = 0;
			$player->msgs = array();
			// reserve extra width for $w tags
			$extra = ($aseco->settings['lists_colortracks'] ? 0.2 : 0);
			if ($aseco->server->packmask != 'Stadium')
				$player->msgs[0] = array(1, $head, array(1.42+$extra, 0.12, 0.6+$extra, 0.4, 0.15, 0.15), array('Icons128x128_1', 'NewTrack', 0.02));
			else
				$player->msgs[0] = array(1, $head, array(1.27+$extra, 0.12, 0.6+$extra, 0.4, 0.15), array('Icons128x128_1', 'NewTrack', 0.02));

			while ($dbrow = mysql_fetch_array($result)) {
				// does the uid exist in the current server track list?
				if (array_key_exists($dbrow[0], $newlist)) {
					$row = $newlist[$dbrow[0]];
					// does best time beat track's Author time?
					if ($dbrow[1] > $row['AuthorTime']) {
						// store track in player object for jukeboxing
						$trkarr = array();
						$trkarr['name'] = $row['Name'];
						$trkarr['author'] = $row['Author'];
						$trkarr['environment'] = $row['Environnement'];
						$trkarr['filename'] = $row['FileName'];
						$trkarr['uid'] = $row['UId'];
						$player->tracklist[] = $trkarr;

						// format track name
						$trackname = $row['Name'];
						if (!$aseco->settings['lists_colortracks'])
							$trackname = stripColors($trackname);
						// grey out if in history
						if (in_array($row['UId'], $jb_buffer))
							$trackname = '{#grey}' . stripColors($trackname);
						else {
							$trackname = '{#black}' . $trackname;
							// add clickable button
							if ($aseco->settings['clickable_lists'] && $tid <= 1900)
								$trackname = array($trackname, $tid+100);  // action id
						}
						// format author name
						$trackauthor = $row['Author'];
						// add clickable button
						if ($aseco->settings['clickable_lists'] && $tid <= 1900)
							$trackauthor = array($trackauthor, -100-$tid);  // action id
						// format env name
						$trackenv = $row['Environnement'];
						// add clickable button
						if ($aseco->settings['clickable_lists'])
							$trackenv = array($trackenv, $envids[$row['Environnement']]);  // action id

						// compute difference to Author time
						$diff = $dbrow[1] - $row['AuthorTime'];
						$sec = floor($diff/1000);
						$hun = ($diff - ($sec * 1000)) / 10;

						if ($aseco->server->packmask != 'Stadium')
							$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
							               $trackname, $trackauthor, $trackenv,
							               '+' . sprintf("%d.%02d", $sec, $hun));
						else
							$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
							               $trackname, $trackauthor,
							               '+' . sprintf("%d.%02d", $sec, $hun));
						$tid++;
						if (++$lines > 14) {
							$player->msgs[] = $msg;
							$lines = 0;
							$msg = array();
							if ($aseco->server->packmask != 'Stadium')
								$msg[] = array('Id', 'Name', 'Author', 'Env', 'Time');
							else
								$msg[] = array('Id', 'Name', 'Author', 'Time');
						}
					}
				}
			}
			// add if last batch exists
			if (count($msg) > 1)
				$player->msgs[] = $msg;
		}

	} else {  // Stunts mode

		// get list of finished tracks with their best (maximum) scores
		$sql = 'SELECT DISTINCT c.uid,t1.score FROM rs_times t1, challenges c
		        WHERE (playerID=' . $player->id . ' AND t1.challengeID=c.id AND
		               score=(SELECT MAX(t2.score) FROM rs_times t2
		                      WHERE playerID=' . $player->id . ' AND t1.challengeID=t2.challengeID))';
		$result = mysql_query($sql);
		if (mysql_num_rows($result) == 0) {
			mysql_free_result($result);
			return;
		}

		// get new/cached list of tracks
		$newlist = getChallengesCache($aseco);

		// only in TMUF anyway
		{
			$head = 'Tracks You Didn\'t Beat Author Score On:';
			$msg = array();
			$msg[] = array('Id', 'Name', 'Author', 'Env', 'Score');
			$tid = 1;
			$lines = 0;
			$player->msgs = array();
			// reserve extra width for $w tags
			$extra = ($aseco->settings['lists_colortracks'] ? 0.2 : 0);
			$player->msgs[0] = array(1, $head, array(1.42+$extra, 0.12, 0.6+$extra, 0.4, 0.15, 0.15), array('Icons128x128_1', 'NewTrack', 0.02));

			while ($dbrow = mysql_fetch_array($result)) {
				// does the uid exist in the current server track list?
				if (array_key_exists($dbrow[0], $newlist)) {
					$row = $newlist[$dbrow[0]];
					// does best score beat track's Author score?
					if ($dbrow[1] < $row['AuthorScore']) {
						// store track in player object for jukeboxing
						$trkarr = array();
						$trkarr['name'] = $row['Name'];
						$trkarr['author'] = $row['Author'];
						$trkarr['environment'] = $row['Environnement'];
						$trkarr['filename'] = $row['FileName'];
						$trkarr['uid'] = $row['UId'];
						$player->tracklist[] = $trkarr;

						// format track name
						$trackname = $row['Name'];
						if (!$aseco->settings['lists_colortracks'])
							$trackname = stripColors($trackname);
						// grey out if in history
						if (in_array($row['UId'], $jb_buffer))
							$trackname = '{#grey}' . stripColors($trackname);
						else {
							$trackname = '{#black}' . $trackname;
							// add clickable button
							if ($aseco->settings['clickable_lists'] && $tid <= 1900)
								$trackname = array($trackname, $tid+100);  // action id
						}
						// format author name
						$trackauthor = $row['Author'];
						// add clickable button
						if ($aseco->settings['clickable_lists'] && $tid <= 1900)
							$trackauthor = array($trackauthor, -100-$tid);  // action id
						// format env name
						$trackenv = $row['Environnement'];
						// add clickable button
						if ($aseco->settings['clickable_lists'])
							$trackenv = array($trackenv, $envids[$row['Environnement']]);  // action id

						// compute difference to Author score
						$diff = $row['AuthorScore'] - $dbrow[1];

						$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
						               $trackname, $trackauthor, $trackenv,
						               '-' . $diff);
						$tid++;
						if (++$lines > 14) {
							$player->msgs[] = $msg;
							$lines = 0;
							$msg = array();
							$msg[] = array('Id', 'Name', 'Author', 'Env', 'Score');
						}
					}
				}
			}
			// add if last batch exists
			if (count($msg) > 1)
				$player->msgs[] = $msg;
		}
	}

	mysql_free_result($result);
}  // getChallengesNoAuthor

// calls function get_recs() from chat.records2.php
function getChallengesNoRecent($player) {
	global $aseco, $jb_buffer, $maxrecs;

	$player->tracklist = array();

	// get list of finished tracks with their most recent (maximum) dates
	$sql = 'SELECT DISTINCT c.uid,t1.date FROM rs_times t1, challenges c
	        WHERE (playerID=' . $player->id . ' AND t1.challengeID=c.id AND
	               date=(SELECT MAX(t2.date) FROM rs_times t2
	                     WHERE playerID=' . $player->id . ' AND t1.challengeID=t2.challengeID))
	        ORDER BY t1.date';
	$result = mysql_query($sql);
	if (mysql_num_rows($result) == 0) {
		mysql_free_result($result);
		return;
	}

	// get list of ranked records
	$reclist = get_recs($player->id);
	// get new/cached list of tracks
	$newlist = getChallengesCache($aseco);

	if ($aseco->server->getGame() == 'TMN') {
		$head = 'Tracks You Didn\'t Play Recently:' . LF . 'Id        Rec   Name $n(Date)$m' . LF;
		$msg = '';
		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = 1;

		while ($dbrow = mysql_fetch_array($result)) {
			// does the uid exist in the current server track list?
			if (array_key_exists($dbrow[0], $newlist)) {
				$row = $newlist[$dbrow[0]];
				// store track in player object for jukeboxing
				$trkarr = array();
				$trkarr['name'] = $row['Name'];
				$trkarr['environment'] = $row['Environnement'];
				$trkarr['filename'] = $row['FileName'];
				$trkarr['uid'] = $row['UId'];
				$player->tracklist[] = $trkarr;

				// format track name
				$trackname = $row['Name'];
				if (!$aseco->settings['lists_colortracks'])
					$trackname = stripColors($trackname);
				// grey out if in history
				if (in_array($row['UId'], $jb_buffer))
					$trackname = '{#grey}' . stripColors($trackname);
				else
					$trackname = '{#black}' . $trackname;

				// get corresponding record
				$pos = isset($reclist[$dbrow[0]]) ? $reclist[$dbrow[0]] : 0;
				$pos = ($pos >= 1 && $pos <= $maxrecs) ? str_pad($pos, 2, '0', STR_PAD_LEFT) : ' -- ';

				$msg .= str_pad($tid, 3, '0', STR_PAD_LEFT) . '.   ' . $pos . '.    '
					      . $trackname . ' $z$n(' . date('Y/m/d', $dbrow[1]) . ')$m' . LF;
				$tid++;
				if (++$lines > 9) {
					$player->msgs[] = $aseco->formatColors($head . $msg);
					$lines = 0;
					$msg = '';
				}
			}
		}
		// add if last batch exists
		if ($msg != '')
			$player->msgs[] = $aseco->formatColors($head . $msg);

	} elseif ($aseco->server->getGame() == 'TMF') {
		$envids = array('Stadium' => 11, 'Alpine' => 12, 'Bay' => 13, 'Coast' => 14, 'Island' => 15, 'Rally' => 16, 'Speed' => 17);
		$head = 'Tracks You Didn\'t Play Recently:';
		$msg = array();
		if ($aseco->server->packmask != 'Stadium')
			$msg[] = array('Id', 'Rec', 'Name', 'Author', 'Env', 'Date');
		else
			$msg[] = array('Id', 'Rec', 'Name', 'Author', 'Date');
		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		// reserve extra width for $w tags
		$extra = ($aseco->settings['lists_colortracks'] ? 0.2 : 0);
		if ($aseco->server->packmask != 'Stadium')
			$player->msgs[0] = array(1, $head, array(1.58+$extra, 0.12, 0.1, 0.6+$extra, 0.4, 0.15, 0.21), array('Icons128x128_1', 'NewTrack', 0.02));
		else
			$player->msgs[0] = array(1, $head, array(1.43+$extra, 0.12, 0.1, 0.6+$extra, 0.4, 0.21), array('Icons128x128_1', 'NewTrack', 0.02));

		while ($dbrow = mysql_fetch_array($result)) {
			// does the uid exist in the current server track list?
			if (array_key_exists($dbrow[0], $newlist)) {
				$row = $newlist[$dbrow[0]];
				// store track in player object for jukeboxing
				$trkarr = array();
				$trkarr['name'] = $row['Name'];
				$trkarr['author'] = $row['Author'];
				$trkarr['environment'] = $row['Environnement'];
				$trkarr['filename'] = $row['FileName'];
				$trkarr['uid'] = $row['UId'];
				$player->tracklist[] = $trkarr;

				// format track name
				$trackname = $row['Name'];
				if (!$aseco->settings['lists_colortracks'])
					$trackname = stripColors($trackname);
				// grey out if in history
				if (in_array($row['UId'], $jb_buffer))
					$trackname = '{#grey}' . stripColors($trackname);
				else {
					$trackname = '{#black}' . $trackname;
					// add clickable button
					if ($aseco->settings['clickable_lists'] && $tid <= 1900)
						$trackname = array($trackname, $tid+100);  // action id
				}
				// format author name
				$trackauthor = $row['Author'];
				// add clickable button
				if ($aseco->settings['clickable_lists'] && $tid <= 1900)
					$trackauthor = array($trackauthor, -100-$tid);  // action id
				// format env name
				$trackenv = $row['Environnement'];
				// add clickable button
				if ($aseco->settings['clickable_lists'])
					$trackenv = array($trackenv, $envids[$row['Environnement']]);  // action id

				// get corresponding record
				$pos = isset($reclist[$dbrow[0]]) ? $reclist[$dbrow[0]] : 0;
				$pos = ($pos >= 1 && $pos <= $maxrecs) ? str_pad($pos, 2, '0', STR_PAD_LEFT) : '-- ';

				if ($aseco->server->packmask != 'Stadium')
					$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
					               $pos . '.', $trackname, $trackauthor, $trackenv,
					               date('Y/m/d', $dbrow[1]));
				else
					$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
					               $pos . '.', $trackname, $trackauthor,
					               date('Y/m/d', $dbrow[1]));
				$tid++;
				if (++$lines > 14) {
					$player->msgs[] = $msg;
					$lines = 0;
					$msg = array();
					if ($aseco->server->packmask != 'Stadium')
						$msg[] = array('Id', 'Rec', 'Name', 'Author', 'Env', 'Date');
					else
						$msg[] = array('Id', 'Rec', 'Name', 'Author', 'Date');
				}
			}
		}
		// add if last batch exists
		if (count($msg) > 1)
			$player->msgs[] = $msg;
	}

	mysql_free_result($result);
}  // getChallengesNoRecent

function getChallengesByLength($player, $order) {
	global $aseco, $jb_buffer;

	$player->tracklist = array();

	// if Stunts mode, bail out immediately
	if ($aseco->server->gameinfo->mode == Gameinfo::STNT) return;

	// get new/cached list of tracks
	$newlist = getChallengesCache($aseco);

	// build list of author times
	$times = array();
	foreach ($newlist as $uid => $row)
		$times[$uid] = $row['AuthorTime'];

	// sort for shortest or longest author times
	$order ? asort($times) : arsort($times);

	if ($aseco->server->getGame() == 'TMN') {
		$head = ($order ? 'Shortest' : 'Longest') . ' Tracks On This Server:' . LF . 'Id        Name $n(Author Time)$m' . LF;
		$msg = '';
		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = 1;

		foreach ($times as $uid => $time) {
			$row = $newlist[$uid];
			// store track in player object for jukeboxing
			$trkarr = array();
			$trkarr['name'] = $row['Name'];
			$trkarr['environment'] = $row['Environnement'];
			$trkarr['filename'] = $row['FileName'];
			$trkarr['uid'] = $row['UId'];
			$player->tracklist[] = $trkarr;

			// format track name
			$trackname = $row['Name'];
			if (!$aseco->settings['lists_colortracks'])
				$trackname = stripColors($trackname);
			// grey out if in history
			if (in_array($row['UId'], $jb_buffer))
				$trackname = '{#grey}' . stripColors($trackname);
			else
				$trackname = '{#black}' . $trackname;

			$msg .= str_pad($tid, 3, '0', STR_PAD_LEFT) . '.   ' . $trackname
			        . ' $z$n(' . formatTime($time) . ')$m' . LF;
			$tid++;
			if (++$lines > 9) {
				$player->msgs[] = $aseco->formatColors($head . $msg);
				$lines = 0;
				$msg = '';
			}
		}
		// add if last batch exists
		if ($msg != '')
			$player->msgs[] = $aseco->formatColors($head . $msg);

	} elseif ($aseco->server->getGame() == 'TMF') {
		$envids = array('Stadium' => 11, 'Alpine' => 12, 'Bay' => 13, 'Coast' => 14, 'Island' => 15, 'Rally' => 16, 'Speed' => 17);
		$head = ($order ? 'Shortest' : 'Longest') . ' Tracks On This Server:';
		$msg = array();
		if ($aseco->server->packmask != 'Stadium')
			$msg[] = array('Id', 'Name', 'Author', 'Env', 'AuthTime');
		else
			$msg[] = array('Id', 'Name', 'Author', 'AuthTime');
		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		// reserve extra width for $w tags
		$extra = ($aseco->settings['lists_colortracks'] ? 0.2 : 0);
		if ($aseco->server->packmask != 'Stadium')
			$player->msgs[0] = array(1, $head, array(1.44+$extra, 0.12, 0.6+$extra, 0.4, 0.15, 0.17), array('Icons128x128_1', 'NewTrack', 0.02));
		else
			$player->msgs[0] = array(1, $head, array(1.29+$extra, 0.12, 0.6+$extra, 0.4, 0.17), array('Icons128x128_1', 'NewTrack', 0.02));

		foreach ($times as $uid => $time) {
			$row = $newlist[$uid];
			// store track in player object for jukeboxing
			$trkarr = array();
			$trkarr['name'] = $row['Name'];
			$trkarr['author'] = $row['Author'];
			$trkarr['environment'] = $row['Environnement'];
			$trkarr['filename'] = $row['FileName'];
			$trkarr['uid'] = $row['UId'];
			$player->tracklist[] = $trkarr;

			// format track name
			$trackname = $row['Name'];
			if (!$aseco->settings['lists_colortracks'])
				$trackname = stripColors($trackname);
			// grey out if in history
			if (in_array($row['UId'], $jb_buffer))
				$trackname = '{#grey}' . stripColors($trackname);
			else {
				$trackname = '{#black}' . $trackname;
				// add clickable button
				if ($aseco->settings['clickable_lists'] && $tid <= 1900)
					$trackname = array($trackname, $tid+100);  // action id
			}
			// format author name
			$trackauthor = $row['Author'];
			// add clickable button
			if ($aseco->settings['clickable_lists'] && $tid <= 1900)
				$trackauthor = array($trackauthor, -100-$tid);  // action id
			// format env name
			$trackenv = $row['Environnement'];
			// add clickable button
			if ($aseco->settings['clickable_lists'])
				$trackenv = array($trackenv, $envids[$row['Environnement']]);  // action id

			if ($aseco->server->packmask != 'Stadium')
				$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
				               $trackname, $trackauthor, $trackenv, formatTime($time));
			else
				$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
				               $trackname, $trackauthor, formatTime($time));
			$tid++;
			if (++$lines > 14) {
				$player->msgs[] = $msg;
				$lines = 0;
				$msg = array();
				if ($aseco->server->packmask != 'Stadium')
					$msg[] = array('Id', 'Name', 'Author', 'Env', 'AuthTime');
				else
					$msg[] = array('Id', 'Name', 'Author', 'AuthTime');
			}
		}
		// add if last batch exists
		if (count($msg) > 1)
			$player->msgs[] = $msg;
	}
}  // getChallengesByLength

function getChallengesByAdd($player, $order, $count) {
	global $aseco, $jb_buffer;

	$player->tracklist = array();

	// get list of tracks in reverse order of addition
	$sql = 'SELECT uid FROM challenges
	        ORDER BY id ' . ($order ? 'DESC' : 'ASC');
	$result = mysql_query($sql);
	if (mysql_num_rows($result) == 0) {
		mysql_free_result($result);
		return;
	}

	// get new/cached list of tracks
	$newlist = getChallengesCache($aseco);

	$tcnt = 0;
	if ($aseco->server->getGame() == 'TMN') {
		$head = ($order ? 'Newest' : 'Oldest') . ' Tracks On This Server:' . LF . 'Id        Name' . LF;
		$msg = '';
		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = 1;

		while ($dbrow = mysql_fetch_array($result)) {
			// does the uid exist in the current server track list?
			if (array_key_exists($dbrow[0], $newlist)) {
				$row = $newlist[$dbrow[0]];
				// store track in player object for jukeboxing
				$trkarr = array();
				$trkarr['name'] = $row['Name'];
				$trkarr['environment'] = $row['Environnement'];
				$trkarr['filename'] = $row['FileName'];
				$trkarr['uid'] = $row['UId'];
				$player->tracklist[] = $trkarr;

				// format track name
				$trackname = $row['Name'];
				if (!$aseco->settings['lists_colortracks'])
					$trackname = stripColors($trackname);
				// grey out if in history
				if (in_array($row['UId'], $jb_buffer))
					$trackname = '{#grey}' . stripColors($trackname);
				else
					$trackname = '{#black}' . $trackname;

				$msg .= '$z' . str_pad($tid, 3, '0', STR_PAD_LEFT) . '.   '
				        . $trackname . LF;
				$tid++;
				if (++$lines > 9) {
					$player->msgs[] = $aseco->formatColors($head . $msg);
					$lines = 0;
					$msg = '';
				}
				// check if we have enough tracks already
				if (++$tcnt == $count) break;
			}
		}
		// add if last batch exists
		if ($msg != '')
			$player->msgs[] = $aseco->formatColors($head . $msg);

	} elseif ($aseco->server->getGame() == 'TMF') {
		$envids = array('Stadium' => 11, 'Alpine' => 12, 'Bay' => 13, 'Coast' => 14, 'Island' => 15, 'Rally' => 16, 'Speed' => 17);
		$head = ($order ? 'Newest' : 'Oldest') . ' Tracks On This Server:';
		$msg = array();
		if ($aseco->server->packmask != 'Stadium')
			$msg[] = array('Id', 'Name', 'Author', 'Env');
		else
			$msg[] = array('Id', 'Name', 'Author');
		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		// reserve extra width for $w tags
		$extra = ($aseco->settings['lists_colortracks'] ? 0.2 : 0);
		if ($aseco->server->packmask != 'Stadium')
			$player->msgs[0] = array(1, $head, array(1.29+$extra, 0.12, 0.6+$extra, 0.4, 0.17), array('Icons128x128_1', 'NewTrack', 0.02));
		else
			$player->msgs[0] = array(1, $head, array(1.12+$extra, 0.12, 0.6+$extra, 0.4), array('Icons128x128_1', 'NewTrack', 0.02));

		while ($dbrow = mysql_fetch_array($result)) {
			// does the uid exist in the current server track list?
			if (array_key_exists($dbrow[0], $newlist)) {
				$row = $newlist[$dbrow[0]];
				// store track in player object for jukeboxing
				$trkarr = array();
				$trkarr['name'] = $row['Name'];
				$trkarr['author'] = $row['Author'];
				$trkarr['environment'] = $row['Environnement'];
				$trkarr['filename'] = $row['FileName'];
				$trkarr['uid'] = $row['UId'];
				$player->tracklist[] = $trkarr;

				// format track name
				$trackname = $row['Name'];
				if (!$aseco->settings['lists_colortracks'])
					$trackname = stripColors($trackname);
				// grey out if in history
				if (in_array($row['UId'], $jb_buffer))
					$trackname = '{#grey}' . stripColors($trackname);
				else {
					$trackname = '{#black}' . $trackname;
					// add clickable button
					if ($aseco->settings['clickable_lists'] && $tid <= 1900)
						$trackname = array($trackname, $tid+100);  // action id
				}
				// format author name
				$trackauthor = $row['Author'];
				// add clickable button
				if ($aseco->settings['clickable_lists'] && $tid <= 1900)
					$trackauthor = array($trackauthor, -100-$tid);  // action id
				// format env name
				$trackenv = $row['Environnement'];
				// add clickable button
				if ($aseco->settings['clickable_lists'])
					$trackenv = array($trackenv, $envids[$row['Environnement']]);  // action id

				if ($aseco->server->packmask != 'Stadium')
					$msg[] =  array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
					                $trackname, $trackauthor, $trackenv);
				else
					$msg[] =  array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
					                $trackname, $trackauthor);
				$tid++;
				if (++$lines > 14) {
					$player->msgs[] = $msg;
					$lines = 0;
					$msg = array();
					if ($aseco->server->packmask != 'Stadium')
						$msg[] = array('Id', 'Name', 'Author', 'Env');
					else
						$msg[] = array('Id', 'Name', 'Author');
				}
				// check if we have enough tracks already
				if (++$tcnt == $count) break;
			}
		}
		// add if last batch exists
		if (count($msg) > 1)
			$player->msgs[] = $msg;
	}

	mysql_free_result($result);
}  // getChallengesByAdd

function getChallengesNoVote($player) {
	global $aseco, $jb_buffer, $maxrecs;

	$player->tracklist = array();

	// get list of ranked records
	$reclist = get_recs($player->id);

	// get new/cached list of tracks
	$newlist = getChallengesCache($aseco);

	// get list of voted tracks and remove those
	$sql = 'SELECT uid FROM challenges c, rs_karma k
	        WHERE c.id=k.challengeID AND k.playerID=' . $player->id;
	$result = mysql_query($sql);
	if (mysql_num_rows($result) > 0) {
		while ($dbrow = mysql_fetch_array($result))
			unset($newlist[$dbrow[0]]);
	}
	mysql_free_result($result);

	if ($aseco->server->getGame() == 'TMN') {
		$head = 'Tracks You Didn\'t Vote For:' . LF . 'Id        Rec   Name' . LF;
		$msg = '';
		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = 1;

		foreach ($newlist as $row) {
			// store track in player object for jukeboxing
			$trkarr = array();
			$trkarr['name'] = $row['Name'];
			$trkarr['environment'] = $row['Environnement'];
			$trkarr['filename'] = $row['FileName'];
			$trkarr['uid'] = $row['UId'];
			$player->tracklist[] = $trkarr;

			// format track name
			$trackname = $row['Name'];
			if (!$aseco->settings['lists_colortracks'])
				$trackname = stripColors($trackname);
			// grey out if in history
			if (in_array($row['UId'], $jb_buffer))
				$trackname = '{#grey}' . stripColors($trackname);
			else
				$trackname = '{#black}' . $trackname;

			// get corresponding record
			$pos = isset($reclist[$row['UId']]) ? $reclist[$row['UId']] : 0;
			$pos = ($pos >= 1 && $pos <= $maxrecs) ? str_pad($pos, 2, '0', STR_PAD_LEFT) : ' -- ';

			$msg .= '$z' . str_pad($tid, 3, '0', STR_PAD_LEFT) . '.   ' . $pos . '.    '
			        . $trackname . LF;
			$tid++;
			if (++$lines > 9) {
				$player->msgs[] = $aseco->formatColors($head . $msg);
				$lines = 0;
				$msg = '';
			}
		}
		// add if last batch exists
		if ($msg != '')
			$player->msgs[] = $aseco->formatColors($head . $msg);

	} elseif ($aseco->server->getGame() == 'TMF') {
		$envids = array('Stadium' => 11, 'Alpine' => 12, 'Bay' => 13, 'Coast' => 14, 'Island' => 15, 'Rally' => 16, 'Speed' => 17);
		$head = 'Tracks You Didn\'t Vote For:';
		$msg = array();
		if ($aseco->server->packmask != 'Stadium')
			$msg[] = array('Id', 'Rec', 'Name', 'Author', 'Env');
		else
			$msg[] = array('Id', 'Rec', 'Name', 'Author');
		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		// reserve extra width for $w tags
		$extra = ($aseco->settings['lists_colortracks'] ? 0.2 : 0);
		if ($aseco->server->packmask != 'Stadium')
			$player->msgs[0] = array(1, $head, array(1.39+$extra, 0.12, 0.1, 0.6+$extra, 0.4, 0.17), array('Icons128x128_1', 'NewTrack', 0.02));
		else
			$player->msgs[0] = array(1, $head, array(1.22+$extra, 0.12, 0.1, 0.6+$extra, 0.4), array('Icons128x128_1', 'NewTrack', 0.02));

		foreach ($newlist as $row) {
			// store track in player object for jukeboxing
			$trkarr = array();
			$trkarr['name'] = $row['Name'];
			$trkarr['author'] = $row['Author'];
			$trkarr['environment'] = $row['Environnement'];
			$trkarr['filename'] = $row['FileName'];
			$trkarr['uid'] = $row['UId'];
			$player->tracklist[] = $trkarr;

			// format track name
			$trackname = $row['Name'];
			if (!$aseco->settings['lists_colortracks'])
				$trackname = stripColors($trackname);
			// grey out if in history
			if (in_array($row['UId'], $jb_buffer))
				$trackname = '{#grey}' . stripColors($trackname);
			else {
				$trackname = '{#black}' . $trackname;
				// add clickable button
				if ($aseco->settings['clickable_lists'] && $tid <= 1900)
					$trackname = array($trackname, $tid+100);  // action id
			}
			// format author name
			$trackauthor = $row['Author'];
			// add clickable button
			if ($aseco->settings['clickable_lists'] && $tid <= 1900)
				$trackauthor = array($trackauthor, -100-$tid);  // action id
			// format env name
			$trackenv = $row['Environnement'];
			// add clickable button
			if ($aseco->settings['clickable_lists'])
				$trackenv = array($trackenv, $envids[$row['Environnement']]);  // action id

			// get corresponding record
			$pos = isset($reclist[$row['UId']]) ? $reclist[$row['UId']] : 0;
			$pos = ($pos >= 1 && $pos <= $maxrecs) ? str_pad($pos, 2, '0', STR_PAD_LEFT) : '-- ';

			if ($aseco->server->packmask != 'Stadium')
				$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
				               $pos . '.', $trackname, $trackauthor, $trackenv);
			else
				$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
				               $pos . '.', $trackname, $trackauthor);
			$tid++;
			if (++$lines > 14) {
				$player->msgs[] = $msg;
				$lines = 0;
				$msg = array();
				if ($aseco->server->packmask != 'Stadium')
					$msg[] = array('Id', 'Rec', 'Name', 'Author', 'Env');
				else
					$msg[] = array('Id', 'Rec', 'Name', 'Author');
			}
		}
		// add if last batch exists
		if (count($msg) > 1)
			$player->msgs[] = $msg;
	}
}  // getChallengesNoVote


// called @ onPlayerServerMessageAnswer
// handles all pop-up window responses
// [0]=PlayerUid, [1]=Login, [2]=Answer
function event_multi_message($aseco, $answer) {

	$login = $answer[1];
	$player = $aseco->server->players->getPlayer($login);
	$cnt = count($player->msgs);

	// check for 'Next' response
	if ($answer[2] == 2 && $cnt > 0) {
		$player->msgs[0]++;  // primed at 1 in the $player->msgs functions
		if (($player->msgs[0] + 1) < $cnt) {  // multiple pages to display
			$btn1 = 'Close';
			$btn2 = 'Next';
			$msg = $player->msgs[$player->msgs[0]];
		} elseif (($player->msgs[0] + 1) == $cnt) {  // last page to display
			$btn1 = 'OK';
			$btn2 = '';
			$msg = $player->msgs[$player->msgs[0]];
		} else {  // all done
			return;
		}
		// display the next page
		$aseco->client->query('SendDisplayServerMessageToLogin', $login, $msg, $btn1, $btn2, 0);
	// 'Close' response
	} else {
	}
}  // event_multi_message

function getChallengeData($filename, $rtnvotes) {
	global $aseco, $tmxvoteratio;

	$ret = array();
	if (!file_exists($filename)) {
		$ret['name'] = 'file not found';
		$ret['votes'] = 500;
		return $ret;
	}
	// check whether votes are needed
	if ($rtnvotes) {
		$ret['votes'] = required_votes($tmxvoteratio);  // from plugin.rasp_votes.php
		if ($aseco->debug) {
			$ret['votes'] = 1;
		}
	} else {
		$ret['votes'] = 1;
	}

	$gbx = new GBXChallMapFetcher();
	try
	{
		$gbx->processFile($filename);

		$ret['uid'] = $gbx->uid;
		$ret['name'] = stripNewlines($gbx->name);
		$ret['author'] = $gbx->author;
		$ret['environment'] = $gbx->envir;
		$ret['authortime'] = $gbx->authorTime;
		$ret['authorscore'] = $gbx->authorScore;
		$ret['coppers'] = $gbx->cost;
	}
	catch (Exception $e)
	{
		$ret['votes'] = 500;
		$ret['name'] = $e->getMessage();
	}
	return $ret;
}  // getChallengeData
?>
