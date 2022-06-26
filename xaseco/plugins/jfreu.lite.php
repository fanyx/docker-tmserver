<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Jfreu's lite plugin.
 * Provides only the player join/leave messages and the INFO messages.
 * Add this to plugins.xml instead of jfreu.plugin.php if you don't
 * need the rest of the Jfreu features.  If you don't want the INFO
 * messages, set $infomessages = 0 in jfreu.config.php.
 * Created by Xymph
 *
 * Dependencies: none
 */

Aseco::registerEvent('onStartup', 'init_jfreu');
Aseco::registerEvent('onEndRace', 'info_message');
Aseco::registerEvent('onPlayerConnect', 'player_connect');
Aseco::registerEvent('onPlayerDisconnect', 'player_disconnect');

Aseco::addChatCommand('message', 'Shows random informational message');

class Jfreu
{
	//** Jfreu's plugin version **//
	var $version;

	//** colors **// <-- use aseco colors ?
	var $white;
	var $yellow;
	var $red;
	var $blue;
	var $green;
	var $admin;

	//** random info message **//
	var $messages;
	var $nbmessages;
	var $infomessages;
	var $message_start;

	//** player join/leave messages **//
	var $player_join;
	var $player_joins;
	var $player_left;
}  // class Jfreu

// called @ onStartup
function init_jfreu($aseco, $command)
{
	include_once('includes/jfreu.config.php');
	$version = '0.14';
	$aseco->server->jfreu = new Jfreu();
	$aseco->server->jfreu->version = $version;
	$aseco->server->jfreu->message_start = $message_start;

	$aseco->server->jfreu->player_join = $player_join;
	$aseco->server->jfreu->player_joins = $player_joins;
	$aseco->server->jfreu->player_left = $player_left;

	//** random information messages **//
	$aseco->server->jfreu->infomessages = $infomessages;
	$i = 1;
	while (isset(${'message'.$i}) && $i < 1000) {
		$aseco->server->jfreu->messages[$i] = ${'message'.$i};
		$i++;
	}
	if ($i != 1000) {
		$aseco->server->jfreu->nbmessages = $i - 1;
	} else {
		$aseco->server->jfreu->nbmessages = 0;
	}

	//** colors **//
	$whi = $aseco->server->jfreu->white = $aseco->formatColors('$z$s{#highlite}');
	$yel = $aseco->server->jfreu->yellow = $aseco->formatColors('$z$s{#server}');
	$red = $aseco->server->jfreu->red = $aseco->formatColors('$z$s{#error}');
	$blu = $aseco->server->jfreu->blue = $aseco->formatColors('$z$s{#message}');
	$gre = $aseco->server->jfreu->green = $aseco->formatColors('$z$s{#record}');
	$adm = $aseco->server->jfreu->admin = $aseco->formatColors('$z$s{#logina}');

	//** Loaded message **//
	$message = $yel.'>> '.$whi.'Jfreu'.$blu.'\'s lite plugin '.$gre.$version.$blu.': '.$whi.'Loaded'.$blu.'.';
	$aseco->client->query('ChatSendServerMessage', $message);
}  // init_jfreu

// called @ onEndRace
function info_message($aseco, $data)
{
	// if no info messages, bail out
	if ($aseco->server->jfreu->infomessages == 0) return;

	// get random message
	$i = rand(1, $aseco->server->jfreu->nbmessages);
	$message = $aseco->formatColors($aseco->server->jfreu->message_start . $aseco->server->jfreu->messages[$i]);
	// hyperlink release page on TMF
	if ($aseco->server->getGame() == 'TMF') {
		$message = preg_replace('|' . XASECO_TMN . '|', '$l[$0]$0$l', $message);
		$message = preg_replace('|' . XASECO_ORG . '|', '$l[$0]$0$l', $message);
	}

	// send the message & test for scoreboard or /message command
	if ($aseco->server->getGame() == 'TMF' && $aseco->server->jfreu->infomessages == 2 &&
	    function_exists('send_window_message'))
		send_window_message($aseco, $message, ($data !== false));
	else
		$aseco->client->query('ChatSendServerMessage', $message);
}  // info_message

function chat_message($aseco, $command)
{
	info_message($aseco, false);
}  // chat_message

// called @ onPlayerConnect
function player_connect($aseco, $player)
{
	global $rasp, $feature_ranks;

	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	// if starting up, bail out immediately
	if ($aseco->startup_phase) return;

	// define admin/player title
	$title = $aseco->isMasterAdmin($player) ? $adm.$aseco->titles['MASTERADMIN'][0] :
	         ($aseco->isAdmin($player) ? $adm.$aseco->titles['ADMIN'][0] :
	          ($aseco->isOperator($player) ? $adm.$aseco->titles['OPERATOR'][0] :
	           $blu.'New Player'));
	// format ladder rank with narrow spaces between the thousands
	$rank = str_replace(' ', '$n $m', number_format($player->ladderrank, 0, ' ', ' '));
	// abbreviate long nations
	$nation = $player->nation;
	if (strlen($nation) > 14)
		$nation = mapCountry($nation);
	if ($feature_ranks) {
		$message = formatText($aseco->server->jfreu->player_joins,
		                      $title, clean_nick($player->nickname),
		                      $nation, $rank, $rasp->getRank($player->login));
	} else {
		$message = formatText($aseco->server->jfreu->player_join,
		                      $title, clean_nick($player->nickname),
		                      $nation, $rank);
	}
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
}  // player_connect

// called @ onPlayerDisconnect
function player_disconnect($aseco, $player)
{
	$message = formatText($aseco->server->jfreu->player_left,
	                      clean_nick($player->nickname),
	                      formatTimeH($player->getTimeOnline() * 1000, false));
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
}  // player_disconnect

function clean_nick($nick)
{
	global $aseco;

	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	$propre = stripColors($nick);
	if ($propre == '') {
		return $red.'ERROR';
	}
	return $propre;
}  // clean_nick
?>
