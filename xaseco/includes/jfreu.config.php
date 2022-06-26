<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Jfreu's plugin 0.13d
 * Configuration settings.
 * This file is included by jfreu.plugin.php or jfreu.lite.php, so don't
 * list it in plugins.xml!
 * Updated by Xymph
 */

	//-> paths to config, vip/vip_team & bans files
	$conf_file = 'plugins/jfreu/jfreu.config.xml';
	$vips_file = 'plugins/jfreu/jfreu.vips.xml';
	$bans_file = 'plugins/jfreu/jfreu.bans.xml';

	//-> Server's base name: (ex: '$000Jfreu')
	//   Max. length: 26 chars (incl. colors & tags, and optional "TopXXX")
	$servername = 'YOUR SERVER NAME';
	//-> Word between the servername and the limit (usually " Top")
	$top = ' $449TOP';
	//-> Change the servername when the limit changes: "Servername TopXXX" (0 = OFF, 1 = ON)
	$autochangename = 0;

	//-> ranklimit: ranklimiting default state (0 = OFF, 1 = ON)
	$ranklimit = 0;

	//-> limit: ranklimit default value (when autorank is OFF)
	$limit = 500000;

	//-> spec ranklimit
	$hardlimit = 1000000;

	//-> autorank: autorank default state (0 = OFF, 1 = ON)
	$autorank = 0;

	//-> offset (average + offset = Auto-RankLimit)
	$offset = 999;

	//-> autorankminplayers (autorank disabled when not enough players)
	$autorankminplayers = 10;
	//-> autorankvip: include VIP/unSpec in autorank calculation (0 = OFF, 1 = ON)
	$autorankvip = 0;

	//-> kick hirank when server is full and new player arrives (0 = OFF, 1 = ON)
	$kickhirank = 0;
	//-> maxplayers value for kickhirank (must be less than server's <max_players>)
	$maxplayers = 20;

	//-> allow user /unspec vote (0 = OFF, 1 = ON)
	$unspecvote = 1;

	//-> player join/leave messages
	$player_join  = '{#server}>> {1}: {#highlite}{2}$z$s{#message} Nation: {#highlite}{3}{#message} Ladder: {#highlite}{4}';
	$player_joins = '{#server}>> {1}: {#highlite}{2}$z$s{#message} Nation: {#highlite}{3}{#message} Ladder: {#highlite}{4}{#message} Server: {#highlite}{5}';
	$player_left  = '{#server}>> {#highlite}{1}$z$s{#message} has left the game. Played: {#highlite}{2}';

	//-> random info messages at the end of the race (0 = OFF, 1 = in chat, 2 = in TMF message window)
	$infomessages = 1;
	//-> prefix for info messages
	$message_start = '$z$s$ff0>> [$f00INFO$ff0] $fff';

	//-> random information messages (if you add a message don't forget to change the number) (999 messages max :-P)
	// $message1 = 'Jfreu\'s plugin: "http://reload.servegame.com/plugin/"';
	$message1 = 'Information about and download of this XASECO on ' . XASECO_TMN;
	$message2 = 'Use "/list" -> "/jukebox ##" to add a track in the jukebox.';
	$message3 = 'Please don\'t sound your horn throughout the entire track.';
	$message4 = 'When going AFK, please set your car to Spectator mode.';
	$message5 = 'Don\'t use Enter to skip intros - instead use Space & Enter';
	$message6 = 'For player & server info use the "/stats" and "/server" commands.';
	$message7 = 'Looking for the name of this server?  Use the "/server" command.';
	$message8 = 'Use "/list nofinish" to find tracks you haven\'t completed yet, then /jukebox them!';
	$message9 = 'Use "/list norank" to find tracks you aren\'t ranked on, then /jukebox them!';
	$message10 = 'Can you beat the Gold time on all tracks?  Use "/list nogold" to find out!';
	$message11 = 'Can you beat the Author time on all tracks?  Use "/list noauthor" to find out!';
	$message12 = 'Wondering which tracks you haven\'t played recently?  Use "/list norecent" to find out!';
	$message13 = 'Use the "/best" & "/worst" commands to find your best and worst records!';
	$message14 = 'Use the "/clans" & "/topclans" commands to see clan members and ranks!';
	$message15 = 'Use the "/ranks" commands to see the server ranks of all online players!';
	$message16 = 'Who is the most victorious player?  Use "/topwins" to find out!';
	$message17 = 'Who has the most ranked records?  Use "/toprecs" to find out!';
	$message18 = 'Wondering what tracks were played recently?  Use the "/history" command.';
	$message19 = 'Looking for the next better ranked record to beat?  Use "/nextrec"!';
	$message20 = 'Find the difference between your personal best and the track record with the "/diffrec" command!';
	$message21 = 'Check how many records were driven on the current track with the "/newrecs" command!';
	$message22 = 'Check how many records, and the 3 best ones, you have with the "/summary" command!';
	$message23 = 'Who has the most top-3 ranked records?  Use "/topsums" to find out!';
	$message24 = 'Jukeboxed the wrong track?  Use "/jukebox drop" to remove it!';
	$message25 = 'Forgot what someone said?  Use "/chatlog" to check the chat history!';
	$message26 = 'Forgot what someone pm-ed you?  Use "/pmlog" to check your PM history!';
	$message27 = 'Looking for the next better ranked player to beat?  Use "/nextrank"!';
	$message28 = 'Use "/list newest <#>" to find the newest tracks added to the server, then /jukebox them!';
	$message29 = 'Find the longest and shortest tracks with the "/list longest / shortest" commands!';
	$message30 = 'Use "/mute" and "/unmute" to mute / unmute other players, and "/mutelist" to list them!';
	$message31 = 'Wondering when a player was last online?  Use "/laston <login>" to find out!';
	$message32 = 'Looking for any player\'s world stats?  Use the "/statsall <login>" command!';
	$message33 = 'Use checkpoints tracking in Rounds/Team/Cup modes with the "/cps" command!';
	$message34 = 'Find the TMX info & records for a track with the "/tmxinfo" & "/tmxrecs" commands!';
	$message35 = 'Looking for the name of the current track\'s song?  Use the "/song" command!';
	$message36 = 'Looking for the name of the current track\'s mod?  Use the "/mod" command!';
	$message37 = 'Use the "/style" command to select your personal window style!';
	$message38 = 'Use the "/recpanel" command to select your personal records panel!';
	$message39 = 'Use the "/votepanel" command to select your personal vote panel!';
	$message40 = 'Find out all about the Dedimania world records system with "/helpdedi"!';
	$message41 = 'Check out the XASECO[2] site at ' . XASECO_ORG . ' !';
	global $feature_votes;
	if ($feature_votes) {
	$message42 = 'Find out all about the chat-based voting commands with "/helpvote"!';
	}
	if (function_exists('send_window_message')) {
	$message43 = 'Missed a system message?  Use "/msglog" to check the message history!';
	}

	//-> Badwords checking (0 = OFF, 1 = ON)
	$badwords = 0;
	//-> Badwords banning (0 = OFF, 1 = ON)
	$badwordsban = 0;
	//-> Number of badwords allowed
	$badwordsnum = 3;
	//-> Banning period (minutes)
	$badwordstime = 10;

	//-> Badwords to check for
	$badwordslist = array(
		'putain','ptain','klote','kIote','kanker','kenker',
		'arschl','wichs','fick','fikk','salop','siktirgit','gvd',
		'hitler','nutte','dick','cock','faitchier','bordel','shit',
		'encul','sucks','a.q','conerie','scheise','scheiße','scheis',
		'baskasole','cocugu','kodugumun','cazo','hoer','bitch',
		'penis','fotze','maul','frese','pizda','gay','fuck','tyfus',
		'sugi','cacat','pisat','labagiu','gaozar','muist','orospu',
		'pédé','cunt','godve','godfe','kut','kudt','lul','iui');

	//-> novote (auto-cancel votes) (0 = OFF, 1 = ON)
	$novote = 0;
?>
