#!/command/with-contenv bash

config=( )
database=( )

# Config
MASTERADMIN_LOGIN=${MASTERADMIN_LOGIN:?ERROR | One player needs to be assigned the MasterAdmin role.} && \
    config+=( "MASTERADMIN_LOGIN" )
SERVER_SA_PASSWORD=${SERVER_SA_PASSWORD:?ERROR | SuperAdminPassword was not given. Please refer to your TMServer configuration.} && \
    config+=( "SERVER_SA_PASSWORD" )

# Optional
TMSERVER_HOST=${TMSERVER_HOST:-localhost} && \
    config+=( "TMSERVER_HOST" )
echo "INFO | TMSERVER_HOST: ${TMSERVER_HOST}"
TMSERVER_PORT=${TMSERVER_PORT:-5000} && \
    config+=( "TMSERVER_PORT" )
echo "INFO | TMSERVER_PORT: ${TMSERVER_PORT}"

# Local Database
MYSQL_HOST=${MYSQL_HOST:-db} && \
    database+=( "MYSQL_HOST" )
echo "INFO | MYSQL_HOST: ${MYSQL_HOST}"
MYSQL_LOGIN=${MYSQL_LOGIN:?ERROR | MySQL username was not given...} && \
    database+=( "MYSQL_LOGIN" )
echo "INFO | MYSQL_LOGIN: ${MYSQL_LOGIN}"
MYSQL_PASSWORD=${MYSQL_PASSWORD:?ERROR | MySQL password was not given...} && \
    database+=( "MYSQL_PASSWORD" )
echo "INFO | MYSQL_PASSWORD: ${MYSQL_PASSWORD}"
MYSQL_DATABASE=${MYSQL_DATABASE:-trackmania} && \
    database+=( "MYSQL_DATABASE" )
echo "INFO | MYSQL_DATABASE: ${MYSQL_DATABASE}"

# Parse config.xml
for idx in "${!config[@]}"; do
    arg=${config[$idx]}
    sed -i -e "s/@$arg@/${!arg}/g" config.xml
done

# Parse localdatabase.xml
for idx in "${!database[@]}"; do
    arg=${database[$idx]}
    sed -i -e "s/@$arg@/${!arg}/g" localdatabase.xml
done
