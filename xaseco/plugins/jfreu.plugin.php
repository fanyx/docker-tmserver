<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Jfreu's plugin 0.14
 * http://jfreu.servegame.com
 * Updated by Xymph
 *
 * Dependencies: requires jfreu.chat.php
 *
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published
 * by the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

Aseco::registerEvent('onPlayerConnect', 'player_connect');
Aseco::registerEvent('onPlayerDisconnect', 'player_disconnect');
Aseco::registerEvent('onStartup', 'init_jfreu');
Aseco::registerEvent('onEndRace', 'kick_hirank');
Aseco::registerEvent('onEndRace', 'vote_end');
Aseco::registerEvent('onEndRace', 'info_message');

/**
 * Jfreu-Unspec-Fix by (OoR-F)~fuckfish
 * Updated/integrated by Xymph
 *
 * This code fixes the bug in Jfreu that some highranked users are able to
 * join the race without launching an /unspec vote.
 *
 * How it works:
 * The code just checks on every checkpoint if the player passing that
 * checkpoint was unspec-ed or still is in spec mode. If s/he is,
 * s/he will be kicked; easy but working =)
 */
Aseco::registerEvent('onCheckpoint', 'kick_speconly');

//** novote **//
Aseco::registerEvent('onChat', 'novote');
//Aseco::registerEvent('onPlayerVote', 'novote_message');

//** BadWords **//
Aseco::registerEvent('onChat', 'BadWords');

//** BAD BAD BAD **//
Aseco::registerEvent('onPlayerFinish', 'pf_kick');

//** MISC **/
Aseco::addChatCommand('ranklimit', 'Shows the current rank limit');
Aseco::addChatCommand('password', 'Show server\'s player/spectator password');
// disabled childish /fake command - Xymph
//Aseco::addChatCommand('fake', 'blaguadeubal...');
// disabled /uptodate command, superseded in main system - Xymph
//Aseco::addChatCommand('uptodate', 'Check version of Jfreu plugin');

//** VOTES **//
Aseco::addChatCommand('unspec', 'Launches an unSpec vote');
Aseco::addChatCommand('yes', 'Votes Yes for unSpec');
Aseco::addChatCommand('no', 'Votes No for unSpec');
Aseco::addChatCommand('message', 'Shows random informational message');

//** Jfreu admin commands **//
Aseco::addChatCommand('jfreu', 'Jfreu admin commands (see: /jfreu help)');


class Jfreu
{
	//** Jfreu's plugin version **//
	var $version;

	//** config **//
	var $conf_file;
	var $vips_file;
	var $bans_file;
	var $servername;
	var $top;
	var $autochangename;

	//** ranklimit **//
	var $ranklimit;
	var $limit;
	var $hardlimit;
	var $autorank;
	var $offset;
	var $autolimit;
	var $autorankminplayers;
	var $autorankvip;
	var $maxplayers;
	var $kickhirank;

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

	//** votes **//
	var $current_vote;  // false: no vote | true: vote in progress
	var $vote_item;  // voting object

	//** novote **//
	var $novote;
	var $unspecvote;

	//** Jfreu's Player list **//
	var $playerlist;

	//** lists: VIP, VIP_Team **//
	var $vip_list;
	var $vip_team_list;

	//** BadWords **//
	var $badwords;
	var $badwordsban;
	var $badwordsnum;
	var $badwordstime;
	var $badwordslist;

	//** BAD BAD BAD **//
	var $pf;
	var $pf_list;

	//** jfreu admin commands *//
	var $admin_commands;
}  // class Jfreu

class joueur
{
	var $hasvoted;
	var $badwords;
	var $kicked;  // if kicked prevent 'left' message
	var $isvip;
	var $speconly;  // SpecOnly status
	var $banned;  // >0 = timestamp unban
}  // class joueur

class vote
{
	var $yes;
	var $no;
	var $total;
	var $nb_votes_needed;
	var $type;
	//** truc **//
	var $login;
}  // class vote

class jfreu_command
{
	var $name;
	var $help;
	var $isadmin;
}  // class jfreu_command

include('plugins/jfreu.chat.php');

// called @ onStartup
function init_jfreu($aseco, $command)
{
	include_once('includes/jfreu.config.php');
	$version = '0.14';
	$aseco->server->jfreu = new Jfreu();
	$aseco->server->jfreu->version = $version;
	$aseco->server->jfreu->conf_file = $conf_file;
	$aseco->server->jfreu->vips_file = $vips_file;
	$aseco->server->jfreu->bans_file = $bans_file;
	$aseco->server->jfreu->servername = $servername;
	$aseco->server->jfreu->top = $top;
	$aseco->server->jfreu->autochangename = ($autochangename != 0);  // insure boolean
	$aseco->server->jfreu->autorank = ($autorank != 0);  // insure boolean
	$aseco->server->jfreu->ranklimit = ($ranklimit != 0);  // insure boolean
	$aseco->server->jfreu->limit = $limit;
	$aseco->server->jfreu->autolimit = $limit;
	$aseco->server->jfreu->hardlimit = $hardlimit;
	$aseco->server->jfreu->offset = $offset;
	$aseco->server->jfreu->autorankvip = ($autorankvip != 0);  // insure boolean
	$aseco->server->jfreu->autorankminplayers = $autorankminplayers;
	$aseco->server->jfreu->maxplayers = $maxplayers;
	$aseco->server->jfreu->kickhirank = ($kickhirank != 0);  // insure boolean

	$aseco->server->jfreu->player_join = $player_join;
	$aseco->server->jfreu->player_joins = $player_joins;
	$aseco->server->jfreu->player_left = $player_left;

	//** colors **//
	$whi = $aseco->server->jfreu->white = $aseco->formatColors('$z$s{#highlite}');
	$yel = $aseco->server->jfreu->yellow = $aseco->formatColors('$z$s{#server}');
	$red = $aseco->server->jfreu->red = $aseco->formatColors('$z$s{#error}');
	$blu = $aseco->server->jfreu->blue = $aseco->formatColors('$z$s{#message}');
	$gre = $aseco->server->jfreu->green = $aseco->formatColors('$z$s{#record}');
	$adm = $aseco->server->jfreu->admin = $aseco->formatColors('$z$s{#logina}');

	//** random information messages **//
	$aseco->server->jfreu->infomessages = $infomessages;
	$aseco->server->jfreu->message_start = $message_start;
	$i = 1;
	while (isset(${'message'.$i}) && $i < 1000)
	{
		$aseco->server->jfreu->messages[$i] = ${'message'.$i};
		$i++;
	}
	if ($i != 1000)
	{
		$aseco->server->jfreu->nbmessages = $i - 1;
	}
	else  // info message overload
	{
		$aseco->server->jfreu->nbmessages = 0;
	}

	//** playerlist **//
	foreach ($aseco->server->players->player_list as $pl)
		ajouter_joueur_liste($aseco, $pl->login, false, false);

	//** badwords **//
	$aseco->server->jfreu->badwords = ($badwords != 0);  // insure boolean
	$aseco->server->jfreu->badwordsban = ($badwordsban != 0);  // insure boolean
	$aseco->server->jfreu->badwordsnum = $badwordsnum;
	$aseco->server->jfreu->badwordstime = $badwordstime;
	$aseco->server->jfreu->badwordslist = $badwordslist;

	//** BAD BAD BAD **//
	$aseco->server->jfreu->pf = 0;
	// $aseco->server->jfreu->pf_list['barcelona'] = 302880;

	//** Votes **//
	$aseco->server->jfreu->vote_item = new vote();
	$aseco->server->jfreu->vote_item->login = '';
	$aseco->server->jfreu->vote_item->yes = 0;
	$aseco->server->jfreu->vote_item->no = 0;
	$aseco->server->jfreu->vote_item->total = 0;
	$aseco->server->jfreu->vote_item->type = '';
	$aseco->server->jfreu->vote_item->nb_votes_needed = 0;
	$aseco->server->jfreu->current_vote = false;

	//** novote **//
	$aseco->server->jfreu->novote = ($novote != 0);  // insure boolean
	$aseco->server->jfreu->unspecvote = ($unspecvote != 0);  // insure boolean

	//** init jfreu admin commands **//
	$aseco->server->jfreu->admin_commands = array();
	init_jfreu_admin_commands($aseco);

	//** Permanent VIP & VIP_Team **//
	$aseco->server->jfreu->vip_list = array();
	$aseco->server->jfreu->vip_team_list = array();
	read_lists_xml($aseco);
	read_guest_list($aseco);
	//** Temporary bans **//
	read_bans_xml($aseco);

	//** Loaded message **//
	$message = $yel.'>> '.$whi.'Jfreu'.$blu.'\'s plugin '.$yel.$version.$blu.': '.$whi.'Loaded'.$blu.'.';
	$aseco->client->query('ChatSendServerMessage', $message);

	// start rank limiting
	set_ranklimit($aseco, $aseco->server->jfreu->autolimit, $autorank);  // pass $autorank as integer

	//** disabled up-to-date test, superseded in main system - Xymph **//
	// $aseco->client->query('ChatSendServerMessage', up_to_date($aseco));
}  // init_jfreu

function write_lists_xml($aseco)
{
	$lists = '<?xml version="1.0" encoding="utf-8" ?>' . CRLF
	       . "<lists>" . CRLF;
	$lists .= "\t<vip_list>" . CRLF;
	$nb = count($aseco->server->jfreu->vip_list);
	$i = 0;
	$empty = true;
	while ($i < $nb)
	{
		if ($aseco->server->jfreu->vip_list[$i] != '')
		{
			$lists .= "\t\t<login>" . $aseco->server->jfreu->vip_list[$i] . "</login>" . CRLF;
			$empty = false;
		}
		$i++;
	}
	if ($empty) {
		$list .= "<!-- format:" . CRLF;
		$list .= "\t\t<login></login>" . CRLF;
		$list .= "-->" . CRLF;
	}
	$lists .= "\t</vip_list>" . CRLF . CRLF
	        . "\t<vip_team_list>" . CRLF;
	$nb = count($aseco->server->jfreu->vip_team_list);
	$i = 0;
	$empty = true;
	while ($i < $nb)
	{
		if ($aseco->server->jfreu->vip_team_list[$i] != '')
		{
			$lists .= "\t\t<team>" . $aseco->server->jfreu->vip_team_list[$i] . "</team>" . CRLF;
			$empty = false;
		}
		$i++;
	}
	if ($empty) {
		$list .= "<!-- format:" . CRLF;
		$list .= "\t\t<team></team>" . CRLF;
		$list .= "-->" . CRLF;
	}
	$lists .= "\t</vip_team_list>" . CRLF
	        . "</lists>" . CRLF;

	//** write out XML file **//
	if (!@file_put_contents($aseco->server->jfreu->vips_file, $lists)) {
		trigger_error('Could not write Jfreu vips file ' . $aseco->server->jfreu->vips_file . ' !', E_USER_WARNING);
	}
}  // write_lists_xml

function read_lists_xml($aseco)
{
	if (!file_exists($aseco->server->jfreu->vips_file))
	{
		trigger_error('Could not find Jfreu vips file ' . $aseco->server->jfreu->vips_file . ' !', E_USER_WARNING);
		return false;
	}
	if (!$list = $aseco->xml_parser->parseXml($aseco->server->jfreu->vips_file))
	{
		trigger_error('Could not read/parse Jfreu vips file ' . $aseco->server->jfreu->vips_file . ' !', E_USER_WARNING);
		return false;
	}

	$vip = $list['LISTS']['VIP_LIST'][0];
	$vip_team = $list['LISTS']['VIP_TEAM_LIST'][0];
	// update VIP_List
	if (isset($vip['LOGIN']))
	{
		for ($i = 0; $i < count($vip['LOGIN']); $i++)
		{
			if (!in_array($vip['LOGIN'][$i], $aseco->server->jfreu->vip_list))
			{
				$aseco->server->jfreu->vip_list[] = $vip['LOGIN'][$i];
			}
		}
	}

	// update VIP_Team_List
	if (isset($vip_team['TEAM']))
	{
		for ($i = 0; $i < count($vip_team['TEAM']); $i++)
		{
			if (!in_array($vip_team['TEAM'][$i], $aseco->server->jfreu->vip_team_list))
			{
				$aseco->server->jfreu->vip_team_list[] = $vip_team['TEAM'][$i];
			}
		}
	}
}  // read_lists_xml

function read_guest_list($aseco)
{
	// get guests on the server (hardlimited to 300)
	if ($aseco->client->query('GetGuestList', 300, 0))
	{
		$guests = $aseco->client->getResponse();
		foreach ($guests as $player)
		{
			if ($player['Login'] != '' && !in_array($player['Login'], $aseco->server->jfreu->vip_list))
			{
				$aseco->server->jfreu->vip_list[] = $player['Login'];
			}
		}
	}
}  // read_guest_list

function write_config_xml($aseco)
{
	$config = '<?xml version="1.0" encoding="utf-8" ?>' . CRLF
	        . "<config>" . CRLF;
	$config .= "\t<server>" . CRLF;
	$config .= "\t\t<servername>" . $aseco->server->jfreu->servername . "</servername>" . CRLF;
	$config .= "\t\t<servertop>" . $aseco->server->jfreu->top . "</servertop>" . CRLF;
	$config .= "\t\t<autochangename>" . ($aseco->server->jfreu->autochangename ? "true" : "false") . "</autochangename>" . CRLF;
	$config .= "\t\t<infomessages>" . $aseco->server->jfreu->infomessages . "</infomessages>" . CRLF;
	$config .= "\t\t<badwords>" . ($aseco->server->jfreu->badwords ? "true" : "false") . "</badwords>" . CRLF;
	$config .= "\t\t<badwordsban>" . ($aseco->server->jfreu->badwordsban ? "true" : "false") . "</badwordsban>" . CRLF;
	$config .= "\t\t<badwordsnum>" . $aseco->server->jfreu->badwordsnum . "</badwordsnum>" . CRLF;
	$config .= "\t\t<badwordstime>" . $aseco->server->jfreu->badwordstime . "</badwordstime>" . CRLF;
	$config .= "\t\t<unspecvote>" . ($aseco->server->jfreu->unspecvote ? "true" : "false") . "</unspecvote>" . CRLF;
	$config .= "\t\t<novote>" . ($aseco->server->jfreu->novote ? "true" : "false") . "</novote>" . CRLF;
	$config .= "\t</server>" . CRLF . CRLF
	         . "\t<limits>" . CRLF;
	$config .= "\t\t<ranklimit>" . ($aseco->server->jfreu->ranklimit ? "true" : "false") . "</ranklimit>" . CRLF;
	$config .= "\t\t<limit>" . $aseco->server->jfreu->limit . "</limit>" . CRLF;
	$config .= "\t\t<hardlimit>" . $aseco->server->jfreu->hardlimit . "</hardlimit>" . CRLF;
	$config .= "\t\t<autorank>" . ($aseco->server->jfreu->autorank ? "true" : "false") . "</autorank>" . CRLF;
	$config .= "\t\t<offset>" . $aseco->server->jfreu->offset . "</offset>" . CRLF;
	$config .= "\t\t<autolimit>" . $aseco->server->jfreu->autolimit . "</autolimit>" . CRLF;
	$config .= "\t\t<autorankminplayers>" . $aseco->server->jfreu->autorankminplayers . "</autorankminplayers>" . CRLF;
	$config .= "\t\t<autorankvip>" . ($aseco->server->jfreu->autorankvip ? "true" : "false") . "</autorankvip>" . CRLF;
	$config .= "\t\t<maxplayers>" . $aseco->server->jfreu->maxplayers . "</maxplayers>" . CRLF;
	$config .= "\t\t<kickhirank>" . ($aseco->server->jfreu->kickhirank ? "true" : "false") . "</kickhirank>" . CRLF;
	$config .= "\t\t<pf>" . ($aseco->server->jfreu->pf ? $aseco->server->jfreu->pf : '0') . "</pf>" . CRLF;
	$config .= "\t</limits>" . CRLF
	         . "</config>" . CRLF;

	//** write out XML file **//
	if (!@file_put_contents($aseco->server->jfreu->conf_file, $config)) {
		trigger_error('Could not write Jfreu config file ' . $aseco->server->jfreu->conf_file . ' !', E_USER_WARNING);
	}
}  // write_config_xml

function read_config_xml($aseco)
{
	if (!file_exists($aseco->server->jfreu->conf_file))
	{
		trigger_error('Could not find Jfreu config file ' . $aseco->server->jfreu->conf_file . ' !', E_USER_WARNING);
		return false;
	}
	if (!$config = $aseco->xml_parser->parseXml($aseco->server->jfreu->conf_file))
	{
		trigger_error('Could not read/parse Jfreu config file ' . $aseco->server->jfreu->conf_file . ' !', E_USER_WARNING);
		return false;
	}

	$server = $config['CONFIG']['SERVER'][0];
	$limits = $config['CONFIG']['LIMITS'][0];

	$aseco->server->jfreu->servername = $server['SERVERNAME'][0];
	$aseco->server->jfreu->top = $server['SERVERTOP'][0];
	$aseco->server->jfreu->autochangename = (strtolower($server['AUTOCHANGENAME'][0]) == 'true' ? true : false);
	$aseco->server->jfreu->infomessages = $server['INFOMESSAGES'][0];
	$aseco->server->jfreu->badwords = (strtolower($server['BADWORDS'][0]) == 'true' ? true : false);
	$aseco->server->jfreu->badwordsban = (strtolower($server['BADWORDSBAN'][0]) == 'true' ? true : false);
	$aseco->server->jfreu->badwordsnum = $server['BADWORDSNUM'][0];
	$aseco->server->jfreu->badwordstime = $server['BADWORDSTIME'][0];
	$aseco->server->jfreu->unspecvote = (strtolower($server['UNSPECVOTE'][0]) == 'true' ? true : false);
	$aseco->server->jfreu->novote = (strtolower($server['NOVOTE'][0]) == 'true' ? true : false);

	$aseco->server->jfreu->ranklimit = (strtolower($limits['RANKLIMIT'][0]) == 'true' ? true : false);
	$aseco->server->jfreu->limit = $limits['LIMIT'][0];
	$aseco->server->jfreu->hardlimit = $limits['HARDLIMIT'][0];
	$aseco->server->jfreu->autorank = (strtolower($limits['AUTORANK'][0]) == 'true' ? true : false);
	$aseco->server->jfreu->offset = $limits['OFFSET'][0];
	$aseco->server->jfreu->autolimit = $limits['AUTOLIMIT'][0];
	$aseco->server->jfreu->autorankminplayers = $limits['AUTORANKMINPLAYERS'][0];
	$aseco->server->jfreu->autorankvip = (strtolower($limits['AUTORANKVIP'][0]) == 'true' ? true : false);
	$aseco->server->jfreu->maxplayers = $limits['MAXPLAYERS'][0];
	$aseco->server->jfreu->kickhirank = (strtolower($limits['KICKHIRANK'][0]) == 'true' ? true : false);
	$aseco->server->jfreu->pf = $limits['PF'][0];

	if ($aseco->server->jfreu->autochangename)
	{
		$limit = ($aseco->server->jfreu->autorank ? $aseco->server->jfreu->autolimit : $aseco->server->jfreu->limit);
		$servername = $aseco->server->jfreu->servername . $aseco->server->jfreu->top . $limit;
		$aseco->client->query('SetServerName', $servername);
	}
}  // read_config_xml

function write_bans_xml($aseco)
{
	$lists = '<?xml version="1.0" encoding="utf-8" ?>' . CRLF
	       . "<lists>" . CRLF;
	$lists .= "\t<ban_list>" . CRLF;
	$time = time();
	$empty = true;
	foreach ($aseco->server->jfreu->playerlist as $player => $entry)
	{
		if ($entry->banned > $time)
		{
			$lists .= "\t\t<login>" . $player . "</login> <time>" . $entry->banned . "</time>" . CRLF;
			$empty = false;
		}
	}
	if ($empty) {
		$list .= "<!-- format:" . CRLF;
		$list .= "\t\t<login></login> <time></time>" . CRLF;
		$list .= "-->" . CRLF;
	}
	$lists .= "\t</ban_list>" . CRLF
	        . "</lists>" . CRLF;

	//** write out XML file **//
	if (!@file_put_contents($aseco->server->jfreu->bans_file, $lists)) {
		trigger_error('Could not write Jfreu bans file ' . $aseco->server->jfreu->bans_file . ' !', E_USER_WARNING);
	}
}  // write_bans_xml

function read_bans_xml($aseco)
{
	if (!file_exists($aseco->server->jfreu->bans_file))
	{
		trigger_error('Could not find Jfreu bans file ' . $aseco->server->jfreu->bans_file . ' !', E_USER_WARNING);
		return false;
	}
	if (!$list = $aseco->xml_parser->parseXml($aseco->server->jfreu->bans_file))
	{
		trigger_error('Could not read/parse Jfreu bans file ' . $aseco->server->jfreu->bans_file . ' !', E_USER_WARNING);
		return false;
	}

	// restore temporary bans
	$time = time();
	$bans = $list['LISTS']['BAN_LIST'][0];
	if (isset($bans['LOGIN']))
	{
		for ($i = 0; $i < count($bans['LOGIN']); $i++)
		{
			// check if ban hasn't expired yet
			if ($bans['TIME'][$i] > $time) {
				ajouter_joueur_liste($aseco, $bans['LOGIN'][$i], false, false);
				$aseco->server->jfreu->playerlist[$bans['LOGIN'][$i]]->banned = $bans['TIME'][$i];
			}
		}
	}
}  // read_bans_xml

function chat_unspec($aseco, $command)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	$login = $command['author']->login;

	if ($aseco->server->jfreu->unspecvote)
	{
		if ($aseco->server->jfreu->playerlist[$login]->speconly)
		{
			if (!$aseco->server->jfreu->current_vote)
			{
				new_vote($aseco, 'unspec', $login);
			}
			else
			{
				$message = $yel.'> '.$blu.'Wait until the end of the current vote.';
				$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
			}
		}
		else
		{
			$message = $yel.'> '.$blu.'This command is only for SpecOnly players.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}
	else
	{
		$message = $yel.'> '.$whi.'/unspec'.$blu.' is not currently enabled on this server.';
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
	}
}  // chat_unspec

function chat_yes($aseco, $command)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	$login = $command['author']->login;
	if (!$aseco->server->jfreu->current_vote)
	{
		$message = $yel.'> '.$blu.'No current vote.';
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		return;
	}
	if ($aseco->server->jfreu->playerlist[$login]->speconly)
	{
		$message = $yel.'> '.$blu.'SpecOnly can\'t vote.';
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		return;
	}
	if ($aseco->server->jfreu->playerlist[$login]->hasvoted == 0)
	{
		vote_yes_no($aseco, true, false);
		$aseco->server->jfreu->playerlist[$login]->hasvoted = 1;
		$message = $yel.'> '.$blu.'You have voted '.$whi.'yes'.$blu.'.';
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
	}
	elseif ($aseco->server->jfreu->playerlist[$login]->hasvoted == 1)
	{
		$message = $yel.'> '.$blu.'You have already voted '.$whi.'yes'.$blu.'.';
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
	}
	elseif ($aseco->server->jfreu->playerlist[$login]->hasvoted == -1)
	{
		vote_yes_no($aseco, true, true);
		$aseco->server->jfreu->playerlist[$login]->hasvoted = 1;
		$message = $yel.'> '.$blu.'You change your vote to '.$whi.'yes'.$blu.'.';
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
	}
}  // chat_yes

function chat_no($aseco, $command)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	$login = $command['author']->login;
	if (!$aseco->server->jfreu->current_vote)
	{
		$message = $yel.'> '.$blu.'No current vote.';
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		return;
	}
	if ($aseco->server->jfreu->playerlist[$login]->speconly)
	{
		$message = $yel.'> '.$blu.'SpecOnly can\'t vote.';
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		return;
	}
	if ($aseco->server->jfreu->playerlist[$login]->hasvoted == 0)
	{
		vote_yes_no($aseco, false, false);
		$aseco->server->jfreu->playerlist[$login]->hasvoted = -1;
		$message = $yel.'> '.$blu.'You have voted '.$whi.'no'.$blu.'.';
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
	}
	elseif ($aseco->server->jfreu->playerlist[$login]->hasvoted == -1)
	{
		$message = $yel.'> '.$blu.'You have already voted '.$whi.'no'.$blu.'.';
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
	}
	elseif ($aseco->server->jfreu->playerlist[$login]->hasvoted == 1)
	{
		vote_yes_no($aseco, false, true);
		$aseco->server->jfreu->playerlist[$login]->hasvoted = -1;
		$message = $yel.'> '.$blu.'You change your vote to '.$whi.'no'.$blu.'.';
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
	}
}  // chat_no

function vote_yes_no($aseco, $yes, $change)  // change = true: Vote change (yes -> no | no -> yes)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	if ($aseco->server->jfreu->vote_item->nb_votes_needed == 0)
	{
		vote_end($aseco);
		return 0;
	}
	if ($yes)
	{
		$aseco->server->jfreu->vote_item->yes++;
		if ($change)
		{
			$aseco->server->jfreu->vote_item->no--;
		}
	}
	else
	{
		$aseco->server->jfreu->vote_item->no++;
		if ($change)
		{
			$aseco->server->jfreu->vote_item->yes--;
		}
	}
	if (!$change)
	{
		$aseco->server->jfreu->vote_item->total++;
	}
	if ($aseco->server->jfreu->vote_item->total >= $aseco->server->jfreu->vote_item->nb_votes_needed)
	{
		vote_finish($aseco);
		return;
	}
	$reste = $aseco->server->jfreu->vote_item->nb_votes_needed - $aseco->server->jfreu->vote_item->total;
	if ($reste > 0)
	{
		if ($aseco->server->jfreu->vote_item->type == 'unspec')
		{
			$player = $aseco->server->players->getPlayer($aseco->server->jfreu->vote_item->login);
			$message = $yel.'>> '.$whi.$reste.$blu.' vote'.($reste == 1 ? '' : 's').' left to unSpec '.$whi.clean_nick($player->nickname).$blu.'$n [ '.$gre.'$n/yes'.$blu.'$n | '.$red.'$n/no'.$blu.'$n ]';
		}
		$aseco->client->query('ChatSendServerMessage', $message);
	}
}  // vote_yes_no

function new_vote($aseco, $type, $login)  // type = unspec
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	$aseco->server->jfreu->vote_item->login = $login;
	$aseco->server->jfreu->vote_item->yes = 0;
	$aseco->server->jfreu->vote_item->no = 0;
	$aseco->server->jfreu->vote_item->total = 0;
	$aseco->server->jfreu->vote_item->type = $type;
	$aseco->server->jfreu->current_vote = true;

	$nbjoueurs = count($aseco->server->players->player_list);
	$aseco->server->jfreu->vote_item->nb_votes_needed = round($nbjoueurs / 4);

	$player = $aseco->server->players->getPlayer($login);
	// format ladder rank with narrow spaces between the thousands
	$rank = str_replace(' ', '$n $m', number_format($player->ladderrank, 0, ' ', ' '));
	if ($type == 'unspec')
	{
		$message = $yel.'>> '.$blu.'SpecOnly '.$whi.clean_nick($player->nickname).$blu.' (Rank: '.$whi.$rank.$blu.') wants to join the race.' . LF
		           . $yel.'>> '.$blu.'('.$gre.'/yes'.$blu.' | '.$red.'$i/no'.$blu.'): '.$whi.round($aseco->server->jfreu->vote_item->nb_votes_needed).$blu.' votes needed.';
		$aseco->client->query('ChatSendServerMessage', $message);
	}
	foreach ($aseco->server->players->player_list as $pl)
		$aseco->server->jfreu->playerlist[$pl->login]->hasvoted = 0;
}  // new_vote

function vote_finish($aseco)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	if ($aseco->server->jfreu->vote_item->type == 'unspec')
	{
		$login = $aseco->server->jfreu->vote_item->login;
		// check if player still online
		if ($player = $aseco->server->players->getPlayer($login)) {
			$nick = $player->nickname;

			$yes = $aseco->server->jfreu->vote_item->yes;
			$no = $aseco->server->jfreu->vote_item->no;
			$message = $yel.'>> '.$blu.'Vote result to unSpec '.$whi.clean_nick($nick).$blu.': '.$whi.$yes.$blu.' yes, '.$whi.$no.$blu.' no.';
			$aseco->client->query('ChatSendServerMessage', $message);
			if ($yes > $no)
			{
				$aseco->server->jfreu->playerlist[$login]->speconly = false;
				$aseco->server->jfreu->playerlist[$login]->isvip = true;
				$message = $yel.'>> '.$blu.'The server unSpecs '.$whi.clean_nick($nick).$blu.'.';
				$aseco->client->query('ChatSendServerMessage', $message);
				if ($aseco->server->jfreu->autorank)
				{
					autorank($aseco, $command);
				}
			}
			else
			{
				$message = $yel.'>> '.$blu.'The server banned '.$whi.clean_nick($nick).$blu.' for '.$whi.'5'.$blu.' mins.';
				$aseco->client->query('ChatSendServerMessage', $message);
				banfor($aseco, $login, 5);
			}
		}
	}
	$aseco->server->jfreu->vote_item->login = '';
	$aseco->server->jfreu->vote_item->yes = 0;
	$aseco->server->jfreu->vote_item->no = 0;
	$aseco->server->jfreu->vote_item->total = 0;
	$aseco->server->jfreu->vote_item->nb_votes_needed = 0;
	$aseco->server->jfreu->current_vote = false;
	$aseco->server->jfreu->type = '';
	foreach ($aseco->server->players->player_list as $pl)
		$aseco->server->jfreu->playerlist[$pl->login]->hasvoted = 0;
}  // vote_finish

// called @ onEndRace
function vote_end($aseco)
{
	if ($aseco->server->jfreu->current_vote)
	{
		$aseco->server->jfreu->vote_item->login = '';
		$aseco->server->jfreu->vote_item->yes = 0;
		$aseco->server->jfreu->vote_item->no = 0;
		$aseco->server->jfreu->vote_item->total = 0;
		$aseco->server->jfreu->vote_item->type = '';
		$aseco->server->jfreu->vote_item->nb_votes_needed = 0;
		$aseco->server->jfreu->current_vote = false;
	}
}  // vote_end

function ajouter_joueur_liste($aseco, $login, $isvip, $speconly)
{
	if (!isset($aseco->server->jfreu->playerlist[$login]))
	{
		$aseco->server->jfreu->playerlist[$login] = new joueur();
		$aseco->server->jfreu->playerlist[$login]->badwords = 0;
		$aseco->server->jfreu->playerlist[$login]->hasvoted = 0;
		$aseco->server->jfreu->playerlist[$login]->banned = 0;
	}
	$aseco->server->jfreu->playerlist[$login]->isvip = $isvip;
	$aseco->server->jfreu->playerlist[$login]->speconly = $speconly;
	$aseco->server->jfreu->playerlist[$login]->kicked = false;
}  // ajouter_joueur_liste

function banfor($aseco, $login, $time)  // time: mins
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	// format time value
	if ($time > 60) {  // check for >1 hour
		$ban = sprintf("%d ${red}hour%s  ${whi}%02d ${red}min%s",
		               $time / 60, (floor($time / 60) == 1 ? '' : 's'),
		               $time % 60, (($time % 60) == 1 ? '' : 's'));
	} else {
		$ban = sprintf('%d%s min%s', $time, $red, ($time == 1 ? '' : 's'));
	}

	// notify/kick banned player if (still) online
	$found = false;
	foreach ($aseco->server->players->player_list as $pl) {
		if ($pl->login == $login) {
			$message = $yel.'> '.$red.'You have been Banned for  '.$whi.$ban.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
			$aseco->client->addCall('Kick', array($login));
			$found = true;
			break;
		}
	}
	if (!$found)
		ajouter_joueur_liste($aseco, $login, false, false);

	// log console message
	$aseco->console('[BanFor] player "{1}" banned for {2}', $login, stripColors($ban));
	$absolute_time = time() + $time * 60;
	$aseco->server->jfreu->playerlist[$login]->banned = $absolute_time;
	$aseco->server->jfreu->playerlist[$login]->kicked = true;

	// update XML file
	write_bans_xml($aseco);
}  // banfor

function isbanned($aseco, $login)  // return mins left = banned, return 0 = not banned
{
	if (!isset($aseco->server->jfreu->playerlist[$login]))
	{
		return 0;
	}
	if ($aseco->server->jfreu->playerlist[$login]->banned == 0)
	{
		return 0;
	}
	$time = time();
	if ($aseco->server->jfreu->playerlist[$login]->banned > $time)
	{
		return round(($aseco->server->jfreu->playerlist[$login]->banned - $time) / 60);
	}
	else
	{
		$aseco->server->jfreu->playerlist[$login]->banned = 0;
		return 0;
	}
}  // isbanned

function kicker_login($aseco, $login)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	$aseco->server->jfreu->playerlist[$login]->kicked = true;
	$message = $yel.'> '.$red.'You\'ve been Kicked. Bye!';
	$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
	$aseco->client->addCall('Kick', array($login));
}  // kicker_login

function isvip($aseco, $login)
{
	if ($aseco->server->jfreu->playerlist[$login]->isvip || in_array($login, $aseco->server->jfreu->vip_list))
	{
		return true;
	}
	return false;
}  // isvip

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

	// abbreviate long nations
	$nation = $player->nation;
	if (strlen($nation) > 14)
		$nation = mapCountry($nation);

/* disabled spammy Loaded message - Xymph
	$welcome = $yel.'>> '.$whi.'Jfreu'.$blu.'\'s plugin '.$gre.$aseco->server->jfreu->version.$blu.': '.$whi.'Loaded'.$blu.'.';
	$aseco->client->query('ChatSendServerMessageToLogin', $welcome, $player->login);
disabled */
	if ($ban = isbanned($aseco, $player->login))
	{
		$title = $aseco->isMasterAdmin($player) ? $adm.$aseco->titles['MASTERADMIN'][0] :
		         ($aseco->isAdmin($player) ? $adm.$aseco->titles['ADMIN'][0] :
		          ($aseco->isOperator($player) ? $adm.$aseco->titles['OPERATOR'][0] :
		           $blu.'New Player'));
		// format ladder rank with narrow spaces between the thousands
		$rank = str_replace(' ', '$n $m', number_format($player->ladderrank, 0, ' ', ' '));
		// format time value
		if ($ban > 60) {  // check for >1 hour
			$ban = sprintf("%d ${red}hour%s  ${whi}%02d ${red}min%s",
			               $ban / 60, (floor($ban / 60) == 1 ? '' : 's'),
			               $ban % 60, (($ban % 60) == 1 ? '' : 's'));
		} else {
			$ban = sprintf('%d%s min%s', $ban, $red, ($ban == 1 ? '' : 's'));
		}
		$message = $yel.'>> '.$title.': '.$whi.clean_nick($player->nickname).$blu.' Nation: '.$whi.$nation.$blu.' Ladder: '.$whi.$rank.$blu.' ['.$red.'Banned for  '.$whi.$ban.$blu.']';
		$aseco->client->query('ChatSendServerMessage', $message);
		$message2 = $yel.'> '.$red.'Your ban will be over in  '.$whi.$ban.'!';
		$aseco->client->query('ChatSendServerMessageToLogin', $message2, $player->login);
		$aseco->server->jfreu->playerlist[$player->login]->kicked = true;
		$aseco->client->addCall('Kick', array($player->login));
		return 0;
	}
	if ($aseco->server->jfreu->ranklimit)
	{
		if (autokick($aseco, $player) && $aseco->server->jfreu->autorank && !$player->isspectator)
		{
			autorank($aseco, $player);
		}
	}
	else
	{
		ajouter_joueur_liste($aseco, $player->login, false, false);

		// if starting up, bail out immediately
		if ($aseco->startup_phase) return;

		// define admin/player title
		$title = $aseco->isMasterAdmin($player) ? $adm.$aseco->titles['MASTERADMIN'][0] :
		         ($aseco->isAdmin($player) ? $adm.$aseco->titles['ADMIN'][0] :
		          ($aseco->isOperator($player) ? $adm.$aseco->titles['OPERATOR'][0] :
		           $blu.'New Player'));
		// format ladder rank with narrow spaces between the thousands
		$rank = str_replace(' ', '$n $m', number_format($player->ladderrank, 0, ' ', ' '));
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
	}
}  // player_connect

// called @ onPlayerDisconnect
function player_disconnect($aseco, $player)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	if ($aseco->server->jfreu->current_vote)
	{
		if ($aseco->server->jfreu->vote_item->login == $player->login)
		{
			vote_end($aseco);
			$message = $yel.'>> '.$whi.clean_nick($player->nickname).$blu.'\'s vote cancelled.';
			$aseco->client->query('ChatSendServerMessage', $message);
		}
	}
	if (!$aseco->server->jfreu->playerlist[$player->login]->kicked)
	{
		$message = formatText($aseco->server->jfreu->player_left,
		                      clean_nick($player->nickname),
		                      formatTimeH($player->getTimeOnline() * 1000, false));
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		if ($aseco->server->jfreu->autorank && !$aseco->server->jfreu->playerlist[$player->login]->speconly)
		{
			autorank($aseco, $player);
		}
	}
}  // player_disconnect

function autokick($aseco, $player)  // returns true if no kick, false if kick
{
	global $rasp, $feature_ranks;

	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	// abbreviate long nations
	$nation = $player->nation;
	if (strlen($nation) > 14)
		$nation = mapCountry($nation);

	if ($aseco->server->jfreu->autorank)
	{
		$limit = $aseco->server->jfreu->autolimit;
	}
	else
	{
		$limit = $aseco->server->jfreu->limit;
	}
	// check if hardlimit active and player rank higher than hardlimit
	if ($aseco->server->jfreu->hardlimit != 0 && ($player->ladderrank > $aseco->server->jfreu->hardlimit || $player->ladderrank <= 0))
	{
		ajouter_joueur_liste($aseco, $player->login, false, false);
		// kick the player
		$message = $red.'This server is only for players with a rank lower than  '.$whi.$aseco->server->jfreu->hardlimit.$red.' !';
		$aseco->client->query('ChatSendServerMessageToLogin', $yel.'> '.$message, $player->login);
		// log console message
		$aseco->console('[HardLimit] player "{1}" kicked (rank: {2})', $player->login, $player->ladderrank);
		$aseco->server->jfreu->playerlist[$player->login]->kicked = true;
		if ($aseco->server->getGame() == 'TMF')
			$aseco->client->addCall('Kick', array($player->login, $message.' $z'));
		else
			$aseco->client->addCall('Kick', array($player->login));
		return false;
	}
	// check for high rank or no rank
	if ($player->ladderrank > $limit || $player->ladderrank <= 0)
	{
		// if not spectator, check for no VIP player or VIP_Team member
		if (!$player->isspectator &&
		    !in_array($player->login, $aseco->server->jfreu->vip_list) &&
		    !in_array($player->teamname, $aseco->server->jfreu->vip_team_list))
		{
			ajouter_joueur_liste($aseco, $player->login, false, false);
			// kick the player
			$message = $red.'This server is only for players with a rank lower than  '.$whi.$limit.$red.' !';
			$aseco->client->query('ChatSendServerMessageToLogin', $yel.'> '.$message, $player->login);

			if ($nick = clean_nick($player->nickname))
			{
				// define admin/player title
				$title = $aseco->isMasterAdmin($player) ? $adm.$aseco->titles['MASTERADMIN'][0] :
				         ($aseco->isAdmin($player) ? $adm.$aseco->titles['ADMIN'][0] :
				          ($aseco->isOperator($player) ? $adm.$aseco->titles['OPERATOR'][0] :
				           $blu.'New Player'));
				// format ladder rank with narrow spaces between the thousands
				$rank = str_replace(' ', '$n $m', number_format($player->ladderrank, 0, ' ', ' '));
				$message2 = $yel.'>> '.$title.': '.$whi.$nick.$blu.' Nation: '.$whi.$nation.$blu.' Ladder: '.$red.$rank.$blu.'  ['.$red.'Kicked'.$blu.' ]';
				$aseco->client->query('ChatSendServerMessage', $message2);
			}
			// log console message
			$aseco->console('[AutoRank] player "{1}" kicked (rank: {2})', $player->login, $player->ladderrank);
			$aseco->server->jfreu->playerlist[$player->login]->kicked = true;
			if ($aseco->server->getGame() == 'TMF')
				$aseco->client->addCall('Kick', array($player->login, $message.' $z'));
			else
				$aseco->client->addCall('Kick', array($player->login));
			return false;
		}
		// if spectator, check for no VIP player or VIP_Team member
		elseif ($player->isspectator &&
		        !in_array($player->login, $aseco->server->jfreu->vip_list) &&
		        !in_array($player->teamname, $aseco->server->jfreu->vip_team_list))
		{
			if ($nick = clean_nick($player->nickname))
			{
				ajouter_joueur_liste($aseco, $player->login, false, true);
				// define admin/player title
				$title = $aseco->isMasterAdmin($player) ? $adm.$aseco->titles['MASTERADMIN'][0] :
				         ($aseco->isAdmin($player) ? $adm.$aseco->titles['ADMIN'][0] :
				          ($aseco->isOperator($player) ? $adm.$aseco->titles['OPERATOR'][0] :
				           $blu.'New Player'));
				// format ladder rank with narrow spaces between the thousands
				$rank = str_replace(' ', '$n $m', number_format($player->ladderrank, 0, ' ', ' '));
				$message = $yel.'>> '.$title.': '.$whi.$nick.$blu.' Nation: '.$whi.$nation.$blu.' Ladder: '.$red.$rank.$blu.'  ['.$gre.'SpecOnly'.$blu.']';
				$aseco->client->query('ChatSendServerMessage', $message);
				spec_message($aseco, $player->login);
			}
		}
		else
		{
			// VIP player/spectator
			ajouter_joueur_liste($aseco, $player->login, true, false);
			if ($nick = clean_nick($player->nickname))
			{
				// define admin/player title
				$title = $aseco->isMasterAdmin($player) ? $adm.$aseco->titles['MASTERADMIN'][0] :
				         ($aseco->isAdmin($player) ? $adm.$aseco->titles['ADMIN'][0] :
				          ($aseco->isOperator($player) ? $adm.$aseco->titles['OPERATOR'][0] :
				           $blu.'New Player'));
				// format ladder rank with narrow spaces between the thousands
				$rank = str_replace(' ', '$n $m', number_format($player->ladderrank, 0, ' ', ' '));
				$message = $yel.'>> '.$title.': '.$whi.$nick.$blu.' Nation: '.$whi.$nation.$blu.' Ladder: '.$red.$rank.' ';
				if ($feature_ranks)
				{
					$message .= $blu.' Server: '.$whi.$rasp->getRank($player->login);
				}
				$message .= $blu.' ['.$gre.'VIP'.$blu.']';
				$aseco->client->query('ChatSendServerMessage', $message);
			}
		}
	}
	else
	{
		// normal player
		ajouter_joueur_liste($aseco, $player->login, false, false);
		if ($nick = clean_nick($player->nickname))
		{
			// define admin/player title
			$title = $aseco->isMasterAdmin($player) ? $adm.$aseco->titles['MASTERADMIN'][0] :
			         ($aseco->isAdmin($player) ? $adm.$aseco->titles['ADMIN'][0] :
			          ($aseco->isOperator($player) ? $adm.$aseco->titles['OPERATOR'][0] :
			           $blu.'New Player'));
			// format ladder rank with narrow spaces between the thousands
			$rank = str_replace(' ', '$n $m', number_format($player->ladderrank, 0, ' ', ' '));
			$message = $yel.'>> '.$title.': '.$whi.$nick.$blu.' Nation: '.$whi.$nation.$blu.' Ladder: '.$whi.$rank;
			if ($feature_ranks)
			{
				$message .= $blu.' Server: '.$whi.$rasp->getRank($player->login);
			}
			$message .= $blu.' ['.$gre.'OK'.$blu.']';
			$aseco->client->query('ChatSendServerMessage', $message);
		}
	}
	return true;
}  // autokick

function autorank($aseco, $command)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	$nbjoueurs = count($aseco->server->players->player_list);
	$nbjoueurs2 = 0;
	$total = 0;
	$limit = $aseco->server->jfreu->autolimit;
	if ($nbjoueurs > 0 && $aseco->server->jfreu->autorankminplayers <= $nbjoueurs)
	{
		foreach ($aseco->server->players->player_list as $pl)
		{
			if (isset($pl->ladderrank) && $pl->ladderrank > 0 && !$aseco->server->jfreu->playerlist[$pl->login]->speconly)
			{
				if (!$aseco->server->jfreu->autorankvip && isvip($aseco, $pl->login) && $pl->ladderrank > $limit)
				{
					// VIP (incl. unSpec) over auto-ranklimit & autorankvip OFF:
					// ignore in autorank calculation
				}
				else
				{
					$total += $pl->ladderrank;
					$nbjoueurs2++;
				}
			}
		}
		if ($total > 0)
		{
			$average = $total / $nbjoueurs2;
			$newlimit = round($average + $aseco->server->jfreu->offset);
			if ($newlimit <= 0)  // prevent negative limit
			{
				$newlimit = 1;
			}
			set_ranklimit($aseco, $newlimit, 1);
		}
	}
	else
	{
		$message = $yel.'>> '.$blu.'Not enough players: '.$whi.$nbjoueurs.$blu.'/'.$whi.$aseco->server->jfreu->autorankminplayers.$blu.' (autorank '.$red.'disabled'.$blu.')';
		$aseco->client->query('ChatSendServerMessage', $message);
		set_ranklimit($aseco, $aseco->server->jfreu->limit, 1);
	}
}  // autorank

function spec_message($aseco, $login)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	$message = $yel.'> '.$blu.'You are '.$whi.'SpecOnly'.$blu.', ask an admin to be unSpec.' . LF
	          .$yel.'> '.$blu.'Or use the '.$whi.'/unspec'.$blu.' command to launch a vote.';
	$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
}  // spec_message

// called @ onCheckpoint
function kick_speconly($aseco, $checkpt)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	$login = $checkpt[1];
	if (isset($aseco->server->jfreu->playerlist[$login]) &&
	    $aseco->server->jfreu->playerlist[$login]->speconly)
	{
		if ($player = $aseco->server->players->getPlayer($login))
		{
			$message = $yel.'>> '.$blu.'SpecOnly '.$whi.clean_nick($player->nickname).$blu.' tried to join the race ['.$red.'Kicked'.$blu.' ]';
			$aseco->client->query('ChatSendServerMessage', $message);
			// log console message
			$aseco->console('[SpecOnly] player "{1}" kicked (rank: {2})', $player->login, $player->ladderrank);
			kicker_login($aseco, $player->login);
		}
	}
}  // kick_speconly

// called @ onEndRace
function kick_hirank($aseco)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	if ($aseco->server->jfreu->kickhirank)
	{
		$nbjoueurs = count($aseco->server->players->player_list);
		$max = $aseco->server->jfreu->maxplayers;
		$diff = $nbjoueurs - $max;
		if ($diff > 0)
		{
			$message = $yel.'>> '.$blu.'Server is full ('.$red.$nbjoueurs.$blu.'/'.$whi.$max.$blu.'): $n'.$whi.$diff.$blu.' Hi-rank player'.($diff == 1 ? '' : 's').' will be kicked.';
			$aseco->client->query('ChatSendServerMessage', $message);
			kick_worst($aseco, $diff);
		}
		else
		{
			$message = $yel.'>> '.$blu.'Server is not full ('.$gre.$nbjoueurs.$blu.'/'.$whi.$max.$blu.'): '.$gre.'No kick'.$blu.'.';
			$aseco->client->query('ChatSendServerMessage', $message);
		}
	}
}  // kick_hirank

function kick_worst($aseco, $x)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	if (count($aseco->server->players->player_list) == 0)
	{
		return 0;
	}
	foreach ($aseco->server->players->player_list as $pl)
	{
		$listakicker[$pl->login] = ($pl->ladderrank > 0 ? $pl->ladderrank : PHP_INT_MAX);
	}
	arsort($listakicker);

	$nbjoueurs = count($listakicker);
	if ($x > $nbjoueurs)
	{
		$x = $nbjoueurs;
	}
	if ($x == 0)
	{
		$message = $yel.'>> '.$red.'No kick !';
		$aseco->client->query('ChatSendServerMessage', $message);
		return 0;
	}

	$i = 0;
	$nicknames = '';
	foreach ($listakicker as $login => $rank)
	{
		$playertemp = $aseco->server->players->getPlayer($login);
		$nicknames .= $whi.clean_nick($playertemp->nickname);
		// log console message
		$aseco->console('[KickWorst] player "{1}" kicked (rank: {2})', $login, $rank);
		kicker_login($aseco, $login);
		if (++$i == $x) break;  // stop if we've got enough
		$nicknames .= $blu.', ';
	}

	$message = $yel.'>> '.$blu.'Players: '.$nicknames.$red.' kicked'.$blu.'.';
	$aseco->client->query('ChatSendServerMessage', $message);
	if ($aseco->server->jfreu->autorank)
	{
		autorank($aseco, $x);
	}
	return $x;
}  // kick_worst

function chat_ranklimit($aseco, $command)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	$login = $command['author']->login;
	if ($aseco->server->jfreu->ranklimit)
	{
		if ($aseco->server->jfreu->autorank)
		{
			$message = $yel.'> '.$blu.'Auto-RankLimit: '.$whi.$aseco->server->jfreu->autolimit;
		}
		else
		{
			$message = $yel.'> '.$blu.'RankLimit: '.$whi.$aseco->server->jfreu->limit;
		}
	}
	else
	{
		$message = $yel.'> '.$blu.'RankLimit: '.$whi.'OFF'.$blu.'.';
	}
	$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
}  // chat_ranklimit

function set_ranklimit($aseco, $limit, $auto)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	if (isset($limit) && $limit != 0)
	{
		$message = '';
		if ($auto == 1)  // autorank ON --> limit changed by autorank function
		{
			$aseco->server->jfreu->autolimit = $limit;
			$message = $yel.'>> '.$blu.'Auto-RankLimit: '.$whi.$limit;
		}
		elseif ($auto == 0)  // autorank OFF --> limit changed by admin
		{
			$aseco->server->jfreu->limit = $limit;
			$message = $yel.'>> '.$blu.'New RankLimit: '.$whi.$limit;
		}
		elseif ($auto == 2)  // autorank ON --> limit forced by admin
		{
			$aseco->server->jfreu->autolimit = $limit;
			$message = $yel.'>> '.$blu.'Auto-RankLimit: '.$whi.$limit.$blu.' (forced by admin)';
		}

		if (!$aseco->server->jfreu->ranklimit)
		{
			$message = $yel.'>> '.$blu.'RankLimit: '.$whi.'OFF'.$blu.'.';
		}
		$aseco->client->query('ChatSendServerMessage', $message);
		if ($aseco->server->jfreu->autochangename)
		{
			$servername = $aseco->server->jfreu->servername . $aseco->server->jfreu->top . $limit;
			$aseco->client->query('SetServerName', $servername);
		}
	}
}  // set_ranklimit

function getpassword($aseco, $spec)  // spec = true: specPassword || false: playerPassword
{
	$aseco->client->query('GetServerOptions');
	$options = $aseco->client->getResponse();
	if ($spec)
	{
		return $options['PasswordForSpectator'];
	}
	else
	{
		return $options['Password'];
	}
}  // getpassword

function chat_password($aseco, $command)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	$login = $command['author']->login;
	// check for spectator or SpecOnly status
	if ($aseco->isSpectator($command['author']) ||
	    $aseco->server->jfreu->playerlist[$login]->speconly)
	{
		$pass = getpassword($aseco, true);
		$message = $yel.'> '.$blu.'Spectator password is: '.$whi.$pass.$blu.'.';
	}
	else
	{
		$pass = getpassword($aseco, false);
		$message = $yel.'> '.$blu.'Player password is: '.$whi.$pass.$blu.'.';
	}
	$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
}  // chat_password

/* disabled, this is too childish - Xymph
function chat_fake($aseco, $command)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	$rank = 1;
	$time1 = rand(20, 30);
	$time2 = rand(10, 99);
	$message = $yel.'>> '.$whi.clean_nick($command['author']->nickname).$gre.' took the '.$whi.$rank.'.'.$gre.' Local Record with a time of '.$whi.'00:'.$time1.'.'.$time2.$gre.'! $000(fake)';
	$aseco->client->query('ChatSendServerMessage', $message);
}  // chat_fake
disabled */

// called @ onEndRace
function info_message($aseco, $data)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	if ($aseco->server->jfreu->infomessages == 0)
	{
/* disabled, no need to spam that messages are off - Xymph
		$message = $yel.'>> '.$blu.'Messages: '.$whi.'OFF'.$blu.'.';
		$aseco->client->query('ChatSendServerMessage', $message);
disabled */
		return;
	}
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

// called @ onChat
function novote($aseco, $chat)
{
	global $feature_votes;  // from rasp.settings.php

	// disabled if chat-based votes are enabled
	if (!$feature_votes && $aseco->server->jfreu->novote)
	{
		$aseco->client->query('CancelVote');
	}
}  // novote

/* disabled as it's not related to /vote events - Xymph
// called @ onPlayerVote
function novote_message($aseco)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	if ($aseco->server->jfreu->novote)
	{
		$message = $yel.'>> '.$blu.'Vote canceled.';
		$aseco->client->query('ChatSendServerMessage', $message);
	}
}  // novote_message
disabled */

// called @ onChat
function BadWords($aseco, $chat)
{
	// if server message or no badwords checking, bail out immediately
	if ($chat[0] == $aseco->server->id) return;
	if (!$aseco->server->jfreu->badwords) return;

	$texte = clean_nick($chat[2]);
	$temp = '';
	$temp2 = ' ';
	$i = 0;
	while (isset($texte[$i]) && $i < 1000)
	{
		if ($texte[$i] != ' ' && $texte[$i] != $temp2)
		{
			$temp2 = $texte[$i];
			$temp .= $texte[$i];
		}
		$i++;
	}
	$texte = $temp;
	$texte = str_replace('|_|', 'u', $texte);
	$texte = str_replace('I<', 'k', $texte);
	$texte = str_replace('|<', 'k', $texte);
	$texte = str_replace('|', 'l', $texte);
	$texte = str_replace('@', 'a', $texte);
	$texte = str_replace('á', 'a', $texte);
	$texte = str_replace('à', 'a', $texte);
	$texte = str_replace('á', 'a', $texte);
	$texte = str_replace('â', 'a', $texte);
	$texte = str_replace('ä', 'a', $texte);
	$texte = str_replace('å', 'a', $texte);
	$texte = str_replace('é', 'e', $texte);
	$texte = str_replace('è', 'e', $texte);
	$texte = str_replace('ë', 'e', $texte);
	$texte = str_replace('ê', 'e', $texte);
	$texte = str_replace('!', 'i', $texte);
	$texte = str_replace('¡', 'i', $texte);
	$texte = str_replace('¿', 'i', $texte);
	$texte = str_replace('í', 'i', $texte);
	$texte = str_replace('ì', 'i', $texte);
	$texte = str_replace('ï', 'i', $texte);
	$texte = str_replace('î', 'i', $texte);
	$texte = str_replace('0', 'o', $texte);
	$texte = str_replace('ó', 'o', $texte);
	$texte = str_replace('ò', 'o', $texte);
	$texte = str_replace('ö', 'o', $texte);
	$texte = str_replace('ô', 'o', $texte);
	$texte = str_replace('ú', 'u', $texte);
	$texte = str_replace('ù', 'u', $texte);
	$texte = str_replace('ü', 'u', $texte);
	$texte = str_replace('û', 'u', $texte);
	$texte = str_replace('.', '', $texte);
	$texte = str_replace('*', '', $texte);
	$texte = str_replace('-', '', $texte);
	$texte = str_replace('_', '', $texte);
	$texte = str_replace('"', '', $texte);
	$texte = str_replace('\'', '', $texte);

	foreach ($aseco->server->jfreu->badwordslist as $mot)
	{
		if (stristr($texte, $mot))
		{
			// get offending player
			$login = $chat[1];
			if (!$player = $aseco->server->players->getPlayer($login))
				return;
			if (!$nick = $player->nickname)
				$nick = $login;

			badword_found($login, $nick, $mot);
			return;
		}
	}
}  // BadWords

function badword_found($login, $nick, $mot)
{
	global $aseco;

	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	$max = $aseco->server->jfreu->badwordsnum;

	$aseco->server->jfreu->playerlist[$login]->badwords++;
	$message = $yel.'>> '.$red.'Language plz !';
	if ($mot != '')
	{
		$message2 = $yel.'> '.$red.'[ '.$whi.'"'.$mot.'"'.$red.' is a forbidden word]';
	}
	else
	{
		$message2 = $yel.'> '.$red.'[That is a forbidden word too]';
	}
	$aseco->client->query('ChatSendServerMessageToLogin', $message2, $login);

	if ($aseco->server->jfreu->badwordsban && $aseco->server->jfreu->playerlist[$login]->badwords > $max)
	{
		$max2 = $max * 2;
		$message .= $blu.' ('.$whi.clean_nick($nick).$blu.' : ';
		$message .= $whi.$aseco->server->jfreu->playerlist[$login]->badwords.$blu.'/'.$whi.$max2.$blu;
		$message .= $red.' to ban'.$blu.') ';
	}
	else
	{
		$message .= $blu.' ('.$whi.clean_nick($nick).$blu.' : ';
		$message .= $whi.$aseco->server->jfreu->playerlist[$login]->badwords.$blu.'/'.$whi.$max.$blu;
		$message .= $red.' to kick'.$blu.') ';
	}
	if (($aseco->server->jfreu->playerlist[$login]->badwords % $max) == 0)
	{
		if ($aseco->server->jfreu->badwordsban)
		{
			if ($aseco->server->jfreu->playerlist[$login]->badwords > $max)
			{
				$message .= ' ['.$red.'Banned for  '.$whi.$aseco->server->jfreu->badwordstime.$red.' mins'.$blu.' ]';
				$aseco->client->query('ChatSendServerMessage', $message);
				$aseco->server->jfreu->playerlist[$login]->badwords = 0;
				banfor($aseco, $login, $aseco->server->jfreu->badwordstime);
			}
			else
			{
				$message .= ' ['.$red.'Kicked'.$blu.' ] ';
				$aseco->client->query('ChatSendServerMessage', $message);
				// log console message
				$aseco->console('[BadWords] player "{1}" kicked', $login);
				$aseco->server->jfreu->playerlist[$login]->kicked = true;
				$aseco->client->addCall('Kick', array($login));
			}
		}
		else
		{
			$message .= ' ['.$red.'Kicked'.$blu.' ] ';
			$aseco->client->query('ChatSendServerMessage', $message);
			$aseco->server->jfreu->playerlist[$login]->badwords = 0;
			// log console message
			$aseco->console('[BadWords] player "{1}" kicked', $login);
			$aseco->server->jfreu->playerlist[$login]->kicked = true;
			$aseco->client->addCall('Kick', array($login));
		}
	}
	else
	{
		$aseco->client->query('ChatSendServerMessage', $message);
	}
}  // badword_found

// called @ onPlayerFinish
function pf_kick($aseco, $finish)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	if ($aseco->server->jfreu->pf == 0)
	{
		return 0;
	}
	if ($finish->score == 0)
	{
		return 0;
	}
	// more than 0.01 sec faster than PF time?
	if ($finish->score < ($aseco->server->jfreu->pf - 10))
	{
		$message = $yel.'>> '.$blu.'Player '.$whi.clean_nick($finish->player->nickname).$blu.' did not PF. ('.$red.'Kicked'.$blu.')';
		$aseco->client->query('ChatSendServerMessage', $message);
		// log console message
		$aseco->console('[NoPfKick] player "{1}" kicked (rank: {2})', $player->login, $player->ladderrank);
		kicker_login($aseco, $finish->player->login);
	}
}  // pf_kick

/* disabled up_to_date function & /uptodate command, superseded in main system - Xymph
function up_to_date($aseco)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	if ($file = @fopen('http://reload.servegame.com/plugin/reload_data/version.php', 'rb'))
	{
		$last = trim(fgets($file));
		if ($last != $aseco->server->jfreu->version)
		{
			$message = $yel.'> '.$blu.'New version of Jfreu\'s plugin available: '.$gre.$last.$blu.'.'.LF.$yel.'> '.$red.'http://jfreu.servegame.com';
			return $message;
		}
		else
		{
			$message = $yel.'> '.$blu.'Your Jfreu\'s plugin version is up to date.';
			return $message;
		}
	}
	else
	{
		$message = $yel.'> '.$red.'Error: can\'t find the last version.';
		return $message;
	}
}  // up_to_date

function chat_uptodate($aseco, $command)
{
	$whi = $aseco->server->jfreu->white;
	$yel = $aseco->server->jfreu->yellow;
	$red = $aseco->server->jfreu->red;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;
	$adm = $aseco->server->jfreu->admin;

	$login = $command['author']->login;
	if ($aseco->isAnyAdmin($command['author']))
	{
		$aseco->client->query('ChatSendServerMessageToLogin', up_to_date($aseco), $login);
	}
	else
	{
		$message = $aseco->getChatMessage('NO_ADMIN');
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
	}
}  // chat_uptodate
disabled */

function addJfreuAdminChatCommand($name, $help)
{
	global $aseco;

	$i = count($aseco->server->jfreu->admin_commands);
	$aseco->server->jfreu->admin_commands[$i] = new jfreu_command();
	$aseco->server->jfreu->admin_commands[$i]->name = $name;
	$aseco->server->jfreu->admin_commands[$i]->help = $help;
	$aseco->server->jfreu->admin_commands[$i]->isadmin = true;  // always admin
}  // addJfreuAdminChatCommand

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
	if ($propre == '')
	{
		return $red.'ERROR';
	}
	return $propre;
}  // clean_nick
?>
