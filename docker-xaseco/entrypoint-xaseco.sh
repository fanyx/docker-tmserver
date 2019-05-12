#!/bin/bash
#

set -e

# Sleep for 30 seconds to wait for trackmania server
sleep 30


if [[ -e /etc/xaseco/env ]]
then
	. /etc/tmserver/env
fi

# Evaluate the available environment variables
if [[ -z "${MASTERADMIN_LOGIN}" ]]
then
	echo "No ingame MasterAdmin was specified."
fi
if [[ -z "${SERVER_SA_PASSWORD}" ]]
then
	echo "No SuperAdmin password was specified. Xaseco cannot build a connection without this information."
	exit 9
fi
if [[ -z "${DB_HOST}" ]]
then
	echo "No database host was specified. Defaulting to 'db' for docker-compose configuration."
	DB_HOST="db"
fi
if [[ -z "${DB_LOGIN}" ]]
then
	echo "No database user was specified. Defaulting to 'trackmania' for docker-compose configuration."
	DB_LOGIN="trackmania"
fi
if [[ -z "${DB_LOGIN_PASSWORD}" ]]
then
	echo "No database user password was specified. Please configure."
	echo "The database connection cannot be established otherwise."
fi
if [[ -z "${DB_NAME}" ]]
then
	echo "No database was specified. Defaulting to 'trackmania' for docker-compose configuration."
	DB_NAME="trackmania"
fi

#Evaluation over
#Commencing substition in config files

#Xaseco Files

sed -i -e "s/--\$MASTERADMIN_LOGIN--/$MASTERADMIN_LOGIN/" \
	-e "s/--\$SERVER_SA_PASSWORD--/$SERVER_SA_PASSWORD/" \
	/opt/xaseco/config.xml
sed -i -e "s/--\$DB_HOST--/$DB_HOST/" \
	-e "s/--\$DB_LOGIN--/$DB_LOGIN/" \
	-e "s/--\$DB_LOGIN_PASSWORD--/$DB_LOGIN_PASSWORD/" \
	-e "s/--\$DB_NAME--/$DB_NAME/" \
	/opt/xaseco/localdatabase.xml

exec "php" "opt/xaseco/aseco.php" "TMNF" "</dev/null" ">aseco.log" "2>&1"
