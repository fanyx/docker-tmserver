#!/command/with-contenv bash

set -e

[[ "$(id -u)" == 0 ]] && \
    chown -R trackmania:trackmania /var/lib/tmserver && \
    s6-setuidgid trackmania "$0"

cd /var/lib/tmserver

# Parse config files
./bin/eval_env.sh

# Parse playlist files
./bin/eval_playlist.sh

exec "./TrackmaniaServer" "/nodaemon" "/internet" "/game_settings=MatchSettings/playlist.xml" "/dedicated_cfg=config.xml"
