#!/bin/bash
#

set -e

# Evaluate all the available environment variables
if [[ -z "${SERVER_LOGIN}" ]]; then
	echo "Server account login is missing. Server cannot start."
	exit 9
fi
if [[ -z "${SERVER_LOGIN_PASSWORD}" ]]; then
	echo "Server account password is missing. Server cannot start."
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
fi
if [[ -z "${SERVER_ADM_PASSWORD}" ]]; then
	echo "No Admin password was set. Generating a random one. You can change it afterwards but it's also not that important"
	echo "Might aswell leave it as randomly generated"
	SERVER_ADM_PASSWORD=$(pwgen -s 12)
	echo "Admin password: ${SERVER_ADM_PASSWORD}"
fi
if [[ -z "${SERVER_NAME}" ]]; then
	echo "No server name was set. Defaulting to 'Trackmania Server'"
	SERVER_NAME="Trackmania Server"
fi
if [[ -z "${SERVER_COMMENT}" ]]; then
	echo "No server description was set. Defaulting to 'This is a Trackmania Server'"
	SERVER_COMMENT="This is a Trackmania Server"
fi

# Evaluation over
# Commencing substition in config files

#Trackmania Files

envsubst > GameData/Config/config.txt < GameData/Config/_config.txt

exec "./TrackmaniaServer" "/nodaemon" "/internet" "/game_settings=MatchSettings/playlist.txt" "/dedicated_cfg=config.txt"
