<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Chat-based voting configuration options.
 * This file is included by plugin.rasp_votes.php.
 * Created by Xymph
 */

	// if true, vote command automatically votes for starter
	// if false, the old way remains where starter has to vote /y too
	// this option also applies to TMX /add votes
	$auto_vote_starter = false;

	// can spectators start votes or vote for another player's votes?
	// these options also apply to TMX /add votes
	$allow_spec_startvote = true;
	$allow_spec_voting = false;

	// as votes scroll (quickly) out of the chat window, and most users
	// are not (yet) familiar with them, votes can keep on lingering
	// until the end of the track (except /endround votes which are
	// cancelled at the end of the round) or simply fail to pass because
	// not enough players agree, and thus block players from starting a
	// different type of vote;
	// therefore limits can be set how long a vote runs in Rounds/Team and
	// TimeAttack/Laps/Stunts modes, expiring it after a certain number of
	// rounds or a certain amount of time, respectively;
	// in TA this uses Checkpoint events as a trigger, so it depends on
	// players regularly crossing them;
	// these limits also apply to TMX /add votes

	// maximum number of rounds before a vote expires
	$r_expire_limit = array(
		0 => 1,  // endround
		1 => 2,  // ladder
		2 => 3,  // replay
		3 => 2,  // skip
		4 => 3,  // kick
		5 => 3,  // add
		6 => 3,  // ignore
	);
	// set to true to show a vote reminder at each of those rounds
	$r_show_reminder = true;

	// maximum number of seconds before a vote expires
	$ta_expire_limit = array(  // seconds
		0 => 0,    // endround, N/A
		1 => 90,   // ladder
		2 => 120,  // replay
		3 => 90,   // skip
		4 => 120,  // kick
		5 => 120,  // add
		6 => 120,  // ignore
	);
	// set to true to show a vote reminder at an (approx.) interval
	$ta_show_reminder = true;
	// interval length at which to (approx.) repeat reminder
	$ta_show_interval = 30;  // seconds

	// check for active voting system
	if ($feature_votes) {
		// disable CallVotes
		$aseco->client->query('SetCallVoteRatio', 1.0);

		// really disable all CallVotes on TMF
		if ($aseco->server->getGame() == 'TMF') {
			$ratios = array(array('Command' => '*', 'Ratio' => -1.0));
			$aseco->client->query('SetCallVoteRatios', $ratios);
		}

		// if 2, the voting explanation is sent to all players when one
		//       new player joins; use this during an introduction period
		// if 1, the voting explanation is only sent to the new player
		//       upon joining
		// if 0, no explanations are sent at all
		$global_explain = 2;

		// define the vote ratios for all types
		$vote_ratios = array(
			0 => 0.4,  // endround
			1 => 0.5,  // ladder
			2 => 0.6,  // replay
			3 => 0.6,  // skip
			4 => 0.7,  // kick
			5 => 1.0,  // add - ignored, defined by $tmxvoteratio
			6 => 0.6,  // ignore
		);

		// divert vote messages to TMF message window?
		$vote_in_window = false;

		// disable voting commands while an admin (any tier) is online?
		$disable_upon_admin = false;

		// disable voting commands during scoreboard at end of track?
		$disable_while_sb = true;

		// allow kicks & allow user to kick-vote any admin?
		$allow_kickvotes = true;
		$allow_admin_kick = false;
		// allow ignores & allow user to ignore-vote any admin?
		$allow_ignorevotes = true;
		$allow_admin_ignore = false;

		// maximum number of these votes per track; set to 0 to disable a
		// vote type, or to some really high number for unlimited votes
		$max_laddervotes = 2;
		$max_replayvotes = 2;
		$max_skipvotes   = 2;

		// limit the number of times a track can be /replay-ed; 0 = unlimited
		$replays_limit = 0;

		// if true,  does restart via quick ChallengeRestart
		//           this is what most users are accustomed to, but it stops
		//           a track's music (if in use)
		// if false, does restart via jukebox prepend & NextChallenge
		//           this takes longer and may confuse users into thinking
		//           the restart is actually loading the next track, but
		//           it insures music resumes playing
		$ladder_fast_restart = true;

		// enable Rounds points limits?  use this to restrict the use of the
		// track-related votes if the _first_ player already has reached a
		// specific percentage of the server's Rounds points limit
		$r_points_limits = true;

		// percentage of Rounds points limit _after_ which /ladder is disabled
		$r_ladder_max = 0.4;
		// percentage of Rounds points limit _before_ which /replay is disabled
		$r_replay_min = 0.5;
		// percentage of Rounds points limit _after_ which /skip is disabled
		$r_skip_max   = 0.5;

		// enable Time Attack time limits?  use this to restrict the use of the
		// track-related votes if the current track is already _running_ for a
		// specific percentage of the server's TA time limit
		// this requires  function time_playing()  from plugin.track.php
		$ta_time_limits = true;

		// percentage of TA time limit _after_ which /ladder is disabled
		$ta_ladder_max = 0.4;
		// percentage of TA time limit _before_ which /replay is disabled
		$ta_replay_min = 0.5;
		// percentage of TA time limit _after_ which /skip is disabled
		$ta_skip_max   = 0.5;

		// no restrictions in other modes
	}
?>
