#!/usr/bin/php -q
<?php
// vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2:

// Update a panel setting for all players in a XASECO[2] database
// Created Sep 2011 by Xymph <tm@gamers.org>

	$panelpath = '/home/tmf/aseco/panels';

	if (!isset($argv[1]) || !isset($argv[2])) {
		echo 'usage: ' . basename($argv[0]) . ' {admin|donate|records|vote} PanelName' . "\n";
		exit;
	}
	if (!file_exists($panelpath)) {
		echo "Panel path '$panelpath' not found\n";
		exit;
	}
	if ($argv[1] != 'admin' && $argv[1] != 'donate' &&
	    $argv[1] != 'records' && $argv[1] != 'vote') {
		echo "unknown panel type\n";
		exit;
	}
	$panelpath = rtrim($panelpath, '/');
	if (!file_exists($panelpath . '/' . ucfirst($argv[1]) . $argv[2] . '.xml')) {
		echo "unknown panel name\n";
		exit;
	}

	if (!mysql_connect('localhost','YOUR_MYSQL_LOGIN','YOUR_MYSQL_PASSWORD')) {
		echo "could not connect\n";
		exit;
	}
	if (!mysql_select_db('aseco')) {
		echo "could not select\n";
		exit;
	}

	$query = 'SELECT PlayerID,Panels FROM players_extra ORDER BY PlayerID';
	$resply = mysql_query($query);

	if (mysql_num_rows($resply) > 0) {
		echo 'Updating players_extra entries: ' . mysql_num_rows($resply) . " ...\n";

		while ($rowply = mysql_fetch_object($resply)) {
			$panels = explode('/', $rowply->Panels);
			switch ($argv[1]) {
			case 'admin':
				$panels[0] = ucfirst($argv[1]) . $argv[2];
				break;
			case 'donate':
				$panels[1] = ucfirst($argv[1]) . $argv[2];
				break;
			case 'records':
				$panels[2] = ucfirst($argv[1]) . $argv[2];
				break;
			case 'vote':
				$panels[3] = ucfirst($argv[1]) . $argv[2];
				break;
			}

			$query = "UPDATE players_extra SET Panels = '" . implode('/', $panels) . "' WHERE PlayerID = " . $rowply->PlayerID;
			$result = mysql_query($query);
			if (mysql_affected_rows() == -1) {
				mysql_free_result($resply);
				echo "couldn't update panels for player ID " . $rowply->PlayerID . ":\n";
				echo mysql_error() . "\n";
				exit;
			}
		}
		echo "Done\n";

		mysql_free_result($resply);
	} else {
		echo "no players_extra!\n";
	}
?>
