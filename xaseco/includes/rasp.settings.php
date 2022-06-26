<?php

//##################################################################
//#------------------------- Features -----------------------------#
//#  Specify here which features you would like to be activated    #
//#  You must enter true or false in lowercase only!               #
//##################################################################

//Set to true if you want the rank system active
$feature_ranks = true;
//Set to true if you want /nextrank to show the difference in record positions,
// i.e. the combined number of positions that your records need to be improved
// in order to catch up with the next better ranked player
$nextrank_show_rp = true;

//Set to true if you want all times recorded, and /pb command to be active
$feature_stats = true;
//Set to true to always show PB at track start
//If false and <show_recs_before> is 2 or 6 AND player has ranked record, OR
// player uses the records panel, then PB message is not shown at track start
$always_show_pb = true;

//Set to true ONLY if you use the karma feature.
//If you set this to true when you are not, it will produce errors
$feature_karma = true;
//Set to true if you allow ++ & -- votes as well as /++ & /--
$allow_public_karma = true;
//Set to true if you want to show the karma message at the start of each track
$karma_show_start = true;
//Set to true if you want to show vote counts & percentages
$karma_show_details = true;
//Set to true if you want to show players their actual votes
$karma_show_votes = true;
//Set to the number of times a player should have finished a track before
//being allowed to karma vote for it
//Note: this is the total number of finishes since the first time a player
//tried a track, not the number in the current session
$karma_require_finish = 0;
//Remind player to vote karma if [s]he hasn't yet
$remind_karma = 0;  // 2 = every finish; 1 = at end of race; 0 = none

//Set to true if you want jukebox functionality
$feature_jukebox = true;
//Set to true if you want jukebox to be extended to include the TMX /add feature
$feature_tmxadd = false;
//Set to true if you want jukebox to skip tracks requested by players that left
$jukebox_skipleft = true;
//Set to true if you want jukebox to _not_ skip tracks requested by admins
//(any tier) that left (and $jukebox_skipleft is true)
$jukebox_adminnoskip = false;
//Set to true if you want /add to permanently add tracks to the server
$jukebox_permadd = false;
//Set to true if you want /admin add to automatically jukebox the downloaded track (just like a passed /add vote)
$jukebox_adminadd = true;
//Set to true if you want jukebox messages diverted to TMF message window
$jukebox_in_window = false;

//Set to true to reset the challenges list cache at the start of each map
$reset_cache_start = true;

//Set to contact (email, ICQ, etc) to show in /server command, leave empty to skip entry
$admin_contact = 'YOUR@EMAIL.COM';

//Set to filename to enable autosaving matchsettings upon every track switch
$autosave_matchsettings = '';  // e.g. 'autosave.txt'

//Set to true if you want start-up to prune records/rs_times for players and
// challenges deleted from database, and for tracks deleted from the server
//Only enable this if you know what you're doing!
$prune_records_times = false;

//Set to true if you want to disable normal CallVotes & enable chat-based votes
$feature_votes = false;

//Set to true to perform XASECO version check at start-up & MasterAdmin connect
$uptodate_check = true;

//Set to true to perform global blacklist merge at MasterAdmin connect
$globalbl_merge = false;

//Set to true to process only United accounts in global blacklist merge
$globalbl_united = false;

//Set to global blacklist in XML format, same as <blacklist_url> in dedicated_cfg.txt (TMF)
// e.g. http://www.gamers.org/tmf/dedimania_blacklist.txt (TMF-only)
//On TMN <blacklist_url> in dedicated.cfg isn't loaded at start-up, so no need to define that
$globalbl_url = '';

//##################################################################
//#-------------------- Performance Variables ---------------------#
//#  These variables are used in the main plugin.                  #
//#  They specify how much data should be used for calculations    #
//#                                                                #
//#  If your server slows down considerably when calculating       #
//#  ranks it is recommended that you lower/increase these values  #
//##################################################################

//Sets the maximum number of records stored per track
// Lower = Faster
$maxrecs = 50;

//Sets the minimum amount of records required for a player to be ranked
// Higher = Faster
$minrank = 3;

//Sets the number of times used to calculate a player's average
// Lower = Faster
$maxavg = 10;

//##################################################################
//#-------------------- Jukebox Variables -------------------------#
//#  These variables are used by the jukebox.                      #
//##################################################################

//Specifies how large the track history buffer is.
//If a track that is in the buffer gets requested, it won't be jukeboxed.
$buffersize = 20;

//Specifies the required vote ratio for a TMX /add request to be successful.
$tmxvoteratio = 0.66;

//The location of the tracks folders for saving TMX tracks, relative
//to the dedicated server's GameData/Tracks/ directory:
//$tmxdir for tracks downloaded via /admin add, and user tracks approved
//  via /admin addthis.
//$tmxtmpdir for tracks downloaded via /add user votes.
//There must be full write permissions on these folders.
//In linux the command will be:  chmod 777.
//Regardless of OS, use the / character for pathing.
$tmxdir = 'Challenges/TMX';
$tmxtmpdir = 'Challenges/TMXtmp';

//##################################################################
//#------------------------ IRC Variables -------------------------#
//#  These variables are used by the IRC plugin.                   #
//##################################################################

$CONFIG = array();
$CONFIG['server'] = 'localhost';  // server (i.e. irc.gamesnet.net)
$CONFIG['nick'] = 'botname';  // nick (i.e. demonbot)
$CONFIG['port'] = 6667;  // port (standard: 6667)
$CONFIG['channel'] = '#channel';  // channel (i.e. #php)
$CONFIG['name'] = 'botlogin';  // bot name (i.e. demonbot)
$show_connect = false;  //If set to true, the IRC connection messages will be displayed in the console.

//-----------------------------------------
//Do not modify anything below this line...
//-----------------------------------------
$linesbuffer = array();
$ircmsgs = array();
$outbuffer = array();
$con = array();
$jukebox = array();
$jb_buffer = array();
$tmxadd = array();
$tmxplaying = false;
$tmxplayed = false;
?>
