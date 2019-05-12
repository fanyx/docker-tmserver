<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * ShareMania info plugin.
 * Displays ShareMania track info
 * Created by Xymph
 *
 * Dependencies: none
 */

require_once('includes/sminfofetcher.inc.php');  // provides access to SM info

Aseco::addChatCommand('sminfo', 'Displays ShareMania info {Track_ID/SM_ID}');

function chat_sminfo($aseco, $command) {

	$player = $command['author'];
	$login = $player->login;
	$command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));

	// check for optional Track/SM ID parameter
	$id = $aseco->server->challenge->uid;
	$name = $aseco->server->challenge->name;
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
				// consider it an SM ID
				$id = $tid;
				$name = '';
			}
		} else {
			$message = '{#server}> {#highlite}' . $tid . '{#error} is not a valid Track/SM ID!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			return;
		}
	}

	// obtain SM info
	$data = new SMInfoFetcher($id);
	if (!$data->name) {
		$message = '{#server}> {#highlite}' . ($name != '' ? stripColors($name) : $id) .
		           '{#error} is not a known SM track, or ShareMania is down!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}
	$data->name = stripNewlines($data->name);

	// compile & send message
	if ($aseco->server->getGame() == 'TMN') {
		$stats = 'SM Info for: {#black}' . $data->name . '$z' . LF . LF;
		$stats .= '$gSM ID       : {#black}' . $data->id . LF;
		$stats .= '$gUID           : {#black}$n' . $data->uid . '$m' . LF;
		$stats .= '$gAuthor      : {#black}' . $data->author . LF;
		$stats .= '$gUploaded  : {#black}' . preg_replace('/^\d\d\d\d/', '\$n$0\$m', strftime('%Y-%m-%d %H:%M', $data->uploaded)) . LF;
	if ($data->type == 'Stunts')
		$stats .= '$gAuthorSc  : {#black}' . $data->authorsc . LF;
	else
		$stats .= '$gAuthorTm : {#black}' . formatTime($data->authortm) . LF;
		$stats .= '$gGame        : {#black}' . $data->game . LF;
		$stats .= '$gType         : {#black}' . $data->type . LF;
		$stats .= '$gEnviron     : {#black}' . $data->envir . LF;
		$stats .= '$gMood        : {#black}' . $data->mood . LF;
		$stats .= '$gNumLaps  : {#black}' . $data->nblaps . LF;
		$stats .= '$gCoppers    : {#black}' . $data->coppers . LF;
		$stats .= '$gRating       : {#black}' . $data->rating . LF;
		$stats .= '$gVotes        : {#black}' . $data->votes . LF;
		$stats .= '$gDownloads: {#black}' . $data->dnloads;

		// display popup message
		$aseco->client->query('SendDisplayServerMessageToLogin', $login, $aseco->formatColors($stats), 'OK', '', 0);

	} elseif ($aseco->server->getGame() == 'TMF') {
		$header = 'SM Info for: {#black}' . $data->name;
		$links = array($data->imageurl, true,
		               '$l[' . $data->pageurl . ']Visit SM Page',
		               '$l[' . $data->dloadurl . ']Download Track');
		$stats = array();
		$stats[] = array('SM ID', '{#black}' . $data->id,
		                 'Game', '{#black}' . $data->game);
		$stats[] = array('UID', '{#black}$n' . $data->uid,
		                 'Type', '{#black}' . $data->type);
		$stats[] = array('Author', '{#black}' . $data->author,
		                 'Environ', '{#black}' . $data->envir);
		$stats[] = array('Uploaded', '{#black}' . strftime('%Y-%m-%d %H:%M', $data->uploaded),
		                 'Mood', '{#black}' . $data->mood);
	if ($data->type == 'Stunts')
		$stats[] = array('AuthorSc', '{#black}' . $data->authorsc,
		                 'NumLaps', '{#black}' . $data->nblaps);
	else
		$stats[] = array('AuthorTm', '{#black}' . formatTime($data->authortm),
		                 'NumLaps', '{#black}' . $data->nblaps);
		$stats[] = array('Rating', '{#black}' . $data->rating,
		                 'Coppers', '{#black}' . $data->coppers);
		$stats[] = array('Votes', '{#black}' . $data->votes,
		                 'Downloads', '{#black}' . $data->dnloads);

		// display custom ManiaLink message
		display_manialink_track($login, $header, array('Icons64x64_1', 'Maximize', -0.01), $links, $stats, array(1.15, 0.2, 0.45, 0.2, 0.3), 'OK');

	} else {  // TMS/TMO
		$stats = '{#server}ShareMania Info for: {#highlite}' . $data->name . '$z' . LF;
		$stats .= '{#server}SM ID  : {#highlite}' . $data->id . LF;
		$stats .= '{#server}UID      : {#highlite}' . $data->uid . LF;
		$stats .= '{#server}Author : {#highlite}' . $data->author . LF;
		$stats .= '{#server}Rating  : {#highlite}' . $data->rating;

		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($stats), $login);
	}
}  // chat_sminfo
?>
