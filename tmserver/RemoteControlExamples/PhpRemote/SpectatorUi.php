#!/usr/bin/php

<?php
//=====================================================================
// this is a *work-in-progress* sample simplified spectator interface.
//i To be run on the command line "php SpectatorUi.php", see (1) to set the options.
//=====================================================================

require("GbxRemote.inc.php");

// Utility functions
$client = new IXR_Client_Gbx;
function ConnectToServer($Ip='localhost', $Port=5000, $AuthLogin='Admin', $AuthPassword='Admin')
{
       global $client;
	if (!$client->InitWithIp($Ip, $Port)) {
		trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}

	DoQuerry("Authenticate", $AuthLogin, $AuthPassword);
	DoQuerry("EnableCallbacks", True);
}

function DoQuerry($querry /*...*/)
{
	global $client;
	$args = func_get_args();
	if (!call_user_func_array(array($client, 'query'), $args)) {
		trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
		exit(1);
	}
	return $client->getResponse();
}

function TryQuerry($querry /*...*/)
{
	global $client;
	$args = func_get_args();
	if (!call_user_func_array(array($client, 'query'), $args)) {
		print ("Warning: '$querry' -> " . "[".$client->getErrorCode()."] ".$client->getErrorMessage());
		return False;
	}
	return $client->getResponse();
}

function XMLEscapeString( $String )
{
	return htmlspecialchars( $String, ENT_NOQUOTES ) ;
}


function MwTimeToString($MwTime)
{
	if ($MwTime == -1) {
		return "???";
	} else {
		$minutes = floor($MwTime/(1000*60));
		$seconds = floor(($MwTime-$minutes*60*1000)/1000);
		$millisec = floor(($MwTime - $minutes*60*1000 - $seconds*1000)/10);
		return $minutes.":".$seconds.".".$millisec;
	}
}

// UI construction:
$ActionChangeSpec = 1*1000;

function MakePlayerFrame($PlayerRanking, $CurTarget, $GameInfos, &$posx, &$posy)
{
	global $ActionChangeSpec;

	$NickName = XMLEscapeString($PlayerRanking['NickName']);
	$Rank = $PlayerRanking['Rank'];
	$Score = $PlayerRanking['Score'];
	$Time = MwTimeToString($PlayerRanking["BestTime"]);
	$PlayerUId = $PlayerRanking['PlayerId'];

	$Action = $ActionChangeSpec + $PlayerUId;

	$Frame = "<frame posn='$posx $posy 0'>";
	$Frame .= "<quad  sizen='32 10'  posn='-1 1 0' halign='top'  valign='left'	style='Bgs1InRace' substyle='BgWindow3' action='$Action'/>";
	$Frame .= "<quad  sizen='30 5'   posn='0 0 1'  halign='left' valign='top'	style='Bgs1InRace' substyle='BgTitle2'/>";
	$Frame .= "<label sizen='28 4'   posn='1 -3 2' halign='left' valign='center2'	style='TextTitle2'>$Rank. $NickName</label>";
	
	if ($GameInfos["GameMode"] == 5) {  // Cup Mode
		$Limit = $GameInfos["CupPointsLimit"];
		if ($Score == $Limit) {
			$Frame .= "<label sizen='30 5'   posn='0 -5 0' halign='left' style='TextStaticSmall'>\$FF0\$oFinalist!</label>";
		} else if ($Score > $Limit) {
			$Frame .= "<label sizen='30 5'   posn='0 -5 0' halign='left' style='TextStaticSmall'>\$0F0\$oWinner</label>";
		} else {
			$Frame .= "<label sizen='30 5'   posn='0 -5 0' halign='left' style='TextStaticSmall'>Score: \$o$Score </label>";
		}

	} else if ($GameInfos["GameMode"] == 0) { // Rounds
		$Frame .= "<label sizen='30 5'   posn='0 -5 0' halign='left' style='TextStaticSmall'>Score: \$o$Score </label>";

	} else if ($GameInfos["GameMode"] == 1) { // TimeAttack
		$Frame .= "<label sizen='30 5'   posn='0 -5 0' halign='left' style='TextStaticSmall'>Best: \$o$Time </label>";

	} else {
		// mode not supported.
	}

	if ($PlayerUId == $CurTarget) {
		$Frame .= "<label sizen='5 5' posn='28 -5 0' halign='right' style='TextStaticSmall'>\$0F0(target)</label>";
	}
	
	$Frame .= "</frame>";

	$posy -= 10;
	return $Frame;
}

function BuildUiForSpectator($Login, $Target, $Rankings, $GameInfos)
{
	$Page = '<?xml version="1.0" encoding="utf-8"?><manialinks>';

	// rankings and scores.
	$Page .= '<manialink id="0"><frame>';
	$posx=-63;
	$posy=37;
	foreach ($Rankings as $Ranking) {
		if ($Ranking['Rank'] == 0)
			break;	// there aren't 5 players in the top 5, so cut up earlier.
		$Page .= MakePlayerFrame($Ranking, $Target, $GameInfos, $posx, $posy);
	}
	$Page .= '</frame></manialink>';

	// customui
	$Page .= '<custom_ui>i<challenge_info visible="false"/><net_infos visible="false"/><scoretable visible="false"/></custom_ui>';
	
	$Page .= '</manialinks>';

	return $Page;
}

// SpectatorManager
$SpectatorManager_Status = array();
$SpectatorManager_GlobalDirty = False;
function SpectatorManager_Add($Login) 
{
	global $SpectatorManager_Status, $SpectatorManager_GlobalDirty;
	$SpectatorManager_GlobalDirty = True;
	$SpectatorManager_Status[$Login]=
	array( 	"uptodate" => False,
		"page" => "", 
		"target" => "" );
}

function SpectatorManager_Remove($Login)
{
	global $SpectatorManager_Status, $SpectatorManager_GlobalDirty;
	unset($SpectatorManager_Status[$Login]);
}

function SpectatorManager_SetDirtyAll()
{
	global $SpectatorManager_Status, $SpectatorManager_GlobalDirty;
	$SpectatorManager_GlobalDirty = True;
	foreach($SpectatorManager_Status as $s) {
		$s["uptodate"] = False;
	}
}

function SpectatorManager_Update()
{
	global $SpectatorManager_Status, $SpectatorManager_GlobalDirty;

	if (!$SpectatorManager_GlobalDirty)
		return;
	$SpectatorManager_GlobalDirty = False;

	$GameInfos = DoQuerry('GetCurrentGameInfo', 1);
	$Rankings = DoQuerry('GetCurrentRanking', 5, 0);

	foreach($SpectatorManager_Status as $Login => $s) {
		if ($s["uptodate"])
			continue;
		$NewPage = BuildUiForSpectator($Login, $s["target"], $Rankings, $GameInfos);
		if ($s["page"] != $NewPage) {
			$s["page"] = $NewPage;
			DoQuerry('SendDisplayManialinkPageToLogin', $Login, $NewPage, 0, False);
		}
		$s["uptodate"] = True;
	}
}

// Callbacks
function OnManialinkPageAnswer($PlayerUId, $Login, $Answer)
{
	global $SpectatorManager_Status, $SpectatorManager_GlobalDirty;
	global $ActionChangeSpec;
	$Argument = $Answer%1000;
	$Action = $Answer - $Argument ;

	if ($Action == $ActionChangeSpec) {
		$SpectatorManager_Status[$Login]["target"] = $Argument;
		$SpectatorManager_Status[$Login]["uptodate"] = False;
		$SpectatorManager_GlobalDirty = True;
		TryQuerry('ForceSpectatorTargetId', $PlayerUId, $Argument, 0);
	}
}

function OnPlayerInfoChanged($PlayerInfo)
{
	return;
}

// ------------------------------------------------------
// -- (1) Server Adress here!---------------------------------
// ------------------------------------------------------
ConnectToServer("localhost", 5000, "Admin", "Admin");

// register already connected specs.
$PlayerList = DoQuerry("GetPlayerList", 250, 0, 1);
foreach ($PlayerList as $Player) {
	$Login = $Player["Login"];
	$IsPureSpec = ($Player["SpectatorStatus"]/100)%10 != 0;
	if ($IsPureSpec)
		SpectatorManager_Add($Login);
}


while (true) {
	// FIXME
	Sleep(1);
	$client->readCB(5*1000*1000);

	$calls = $client->getCBResponses();
	if (!empty($calls)) {
		foreach ($calls as $call) {
			switch($call[0]){
				case 'TrackMania.BeginRace':
				case 'TrackMania.EndRace':
				case 'TrackMania.EndRound':
					SpectatorManager_SetDirtyAll();
					break;

				case 'TrackMania.PlayerManialinkPageAnswer':
					OnManialinkPageAnswer($call[1][0], $call[1][1], $call[1][2]);
					break;

				case 'TrackMania.PlayerConnect':
					if ($call[1][1])	// is spectator
						SpectatorManager_Add($call[1][0]);
					break;

				case 'TrackMania.PlayerDisconnect':
					SpectatorManager_Remove($call[1][0]);
					break;
				case 'TrackMania.PlayerInfoChanged':
					OnPlayerInfoChanged($call[1][0]);
					break;
			}
		}
	}
	SpectatorManager_Update();
	flush();
}

$client->Terminate();

?>

