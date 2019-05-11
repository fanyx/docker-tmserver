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
if [[ -z "${DB_HOST}" ]]; then
	echo "MySQL database host was not set. Defaulting to 'db' for the docker-compose configuration"
	DB_HOST="db"
fi
if [[ -z "${DB_NAME}" ]]; then
	echo "No database name was set. Defaulting to 'trackmania' for the docker-compose configuration"
	DB_NAME="trackmania"
fi
if [[ -z "${DB_LOGIN}" ]]; then
	echo "No database user was set. Defaulting to 'trackmania' for th docker-compose configuration"
	DB_LOGIN="trackmania"
fi
if [[ -z "${DB_LOGIN_PASSWORD}" ]]; then
	echo "No database user password was set. The server cannot connect to the database. Aborting"
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

#Xaseco files
cd /opt/xaseco

sed -i -e "s/--\$SERVER_SA_PASSWORD--/\${SERVER_SA_PASSWORD}/" /opt/xaseco/config.xml

sed -i -e "s/--\$DB_HOST--/\${DB_HOST}/" \
	-e "s/--\$DB_LOGIN--/\${DB_LOGIN}/" \
	-e "s/--\$DB_LOGIN_PASSWORD--/\${DB_LOGIN_PASSWORD}/" \
	-e "s/--\$DB_NAME--/\${DB_NAME}/" \
	/opt/xaseco/localdatabase.xml

#Trackmania Files
cd /opt/tmserver

sed -i -e "s/--\$SERVER_SA_PASSWORD--/\${SERVER_SA_PASSWORD}/" \
	-e "s/--\$SERVER_ADM_PASSWORD--\/${SERVER_ADM_PASSWORD}/" \
	-e "s/--\$SERVER_LOGIN--/\${SERVER_LOGIN}/" \
	-e "s/--\$SERVER_LOGIN_PASSWORD--/\${SERVER_LOGIN_PASSWORD}/" \
	-e "s/--\$SERVER_NAME--/\${SERVER_NAME}/" \
	-e "s/--\$SERVER_COMMENT--/\${SERVER_COMMENT}/" \
	-e "s/--\$SERVER_PASSWORD--/\${SERVER_PASSWORD}/" \
	-e "s/--\$SERVER_PORT--/\${SERVER_PORT}/" \
	-e "s/--\$SERVER_P2P_PORT--/\${SERVER_P2P_PORT}/" \
	/opt/tmserver/GameData/Config/default.txt
