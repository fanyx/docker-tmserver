#!/bin/bash
#

set -e

if [[ "$(id -u)" = "0" ]]; then
    chown -R trackmania /opt/tmserver
    exec su-exec trackmania "$0"
fi

# Change dir to /opt/tmserver
cd /opt/tmserver

# Evaluate all the available environment variables
if [[ -z "${SERVER_LOGIN}" ]]; then
	echo "Server account login is missing. Server cannot start."
	echo "Please set environment variable SERVER_LOGIN."
	exit 9
fi
if [[ -z "${SERVER_LOGIN_PASSWORD}" ]]; then
	echo "Server account password is missing. Server cannot start."
	echo "Please set environment variable SERVER_LOGIN_PASSWORD."
	exit 9
fi
if [[ -z "${SERVER_PORT}" ]]; then
	echo "No server port was set. Defaulting to port 2350"
	SERVER_PORT="2350"
fi
if [[ -z "${SERVER_P2P_PORT}" ]]; then
	echo "No server peer2peer port was set. Defaulting to port 3450"
	SERVER_P2P_PORT="3450"
fi
if [[ -z "${SERVER_SA_PASSWORD}" ]]; then
	echo "No SuperAdmin password was set. Generating a random one. You can change it afterwards but it's also not that important"
	echo "Might aswell leave it as randomly generated"
	SERVER_SA_PASSWORD=$(pwgen -s 12)
	echo "SuperAdmin password: ${SERVER_SA_PASSWORD}"
	echo "Please write this down or pipe the docker logs to a file."
fi
if [[ -z "${SERVER_ADM_PASSWORD}" ]]; then
	echo "No Admin password was set. Generating a random one. You can change it afterwards but it's also not that important"
	echo "Might aswell leave it as randomly generated"
	SERVER_ADM_PASSWORD=$(pwgen -s 12)
	echo "Admin password: ${SERVER_ADM_PASSWORD}"
	echo "Please write this down or pipe the docker logs to a file."
fi
if [[ -z "${SERVER_NAME}" ]]; then
	echo "No server name was set. Defaulting to 'Trackmania Server'"
	SERVER_NAME="Trackmania Server"
fi
if [[ -z "${SERVER_COMMENT}" ]]; then
	echo "No server description was set. Defaulting to 'This is a Trackmania Server'"
	SERVER_COMMENT="This is a Trackmania Server"
fi

if [[ -z "${GAMEMODE}" ]]; then
    echo "No gamemode was specified. Defaulting to TimeAttack."
    GAMEMODE="1"
fi

if [[ -z "${CHATTIME}" ]]; then
    echo "No chat timeout was specified. Defaulting to 10000 ms."
    CHATTIME="10000"
fi

if [[ -z "${FINISHTIMEOUT}" ]]; then
    echo "No finish timeout was specified. Defaulting to adaptive mode."
    FINISHTIMEOUT="1"
fi

if [[ -z "${DISABLERESPAWN}" ]]; then
    echo "Respawns were not specified. Defaulting to enabled."
    DISABLERESPAWN="0"
fi

if [[ -z "${ROUNDS_POINTSLIMIT}" ]]; then
    echo "No points limit was specified for rounds mode. Defaulting to 30."
    ROUNDS_POINTSLIMIT="30"
fi

if [[ -z "${TIMEATTACK_LIMIT}" ]]; then
    echo "No time limit was specified for time attack mode. Defaulting to 180000 ms."
    TIMEATTACK_LIMIT="180000"
fi

if [[ -z "${TEAM_POINTSLIMIT}" ]]; then
    echo "No points limit was specified for team mode. Defaulting to 50."
    TEAM_POINTSLIMIT="50"
fi

if [[ -z "${TEAM_MAXPOINTS}" ]]; then
    echo "No number of maximum points per round was specified for team mode. Defaulting to 6."
    TEAM_MAXPOINTS="6"
fi

if [[ -z "${LAPS_NBLAPS}" ]]; then
    echo "No number of laps was specified for laps mode. Defaulting to 5."
    LAPS_NBLAPS="5"
fi

if [[ -z "${LAPS_TIMELIMIT}" ]]; then
    echo "No time limit was specified for laps mode. Defaulting to no limit."
    LAPS_TIMELIMIT="0"
fi


echo "Evaluation over"
echo "Substition in config files"

#Trackmania Files

envsubst < GameData/Config/_config.txt > GameData/Config/config.txt 
envsubst < GameData/Tracks/MatchSettings/_playlist.txt > GameData/Tracks/MatchSettings/playlist.txt 

exec "./TrackmaniaServer" "/nodaemon" "/internet" "/game_settings=MatchSettings/playlist.txt" "/dedicated_cfg=config.txt"
