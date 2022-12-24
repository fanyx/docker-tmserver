#!/command/with-contenv bash

echo "INFO | Parsing custom playlist..."

[[ -z "${CUSTOM_PLAYLIST}" ]] && \
    echo "INFO | Custom Playlist is not enabled, keeping default or user-edited playlist" && \
    exit 0

PLAYLIST_PATH=${PLAYLIST_PATH:-playlist.txt}
PLAYLIST_FILE='GameData/Tracks/MatchSettings/playlist.xml'

if [[ -f "${PLAYLIST_PATH}" ]]; then
    count=1
    while read l; do
        xmlstarlet ed -L -s /playlist -t elem -n challenge $PLAYLIST_FILE
        xmlstarlet ed -L -s "/playlist/challenge[${count}]" -t elem -n file -v "${l}" $PLAYLIST_FILE
        count=$((count+1))
    done < $PLAYLIST_PATH
else
   xmlstarlet ed -L -s /playlist -t elem -n challenge $PLAYLIST_FILE
   xmlstarlet ed -L -s "playlist/challenge[1]" -t elem -n file -v "Challenges/Nadeo/A01-Race.Challenge.Gbx" $PLAYLIST_FILE
fi

echo "INFO | Finished parsing playlist files"
