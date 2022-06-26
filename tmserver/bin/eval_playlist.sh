#!/command/with-contenv bash

echo "INFO | Parsing custom playlist..."

CUSTOM_PLAYLIST=${CUSTOM_PLAYLIST:-playlist.txt}
PLAYLIST_FILE='GameData/Tracks/MatchSettings/playlist.xml'

if [[ -f "${CUSTOM_PLAYLIST}" ]]; then
    count=1
    while read l; do
        xmlstarlet ed -L -s /playlist -t elem -n challenge $PLAYLIST_FILE
        xmlstarlet ed -L -s "/playlist/challenge[${count}]" -t elem -n file -v "${l}" $PLAYLIST_FILE
        count=$((count+1))
    done < $CUSTOM_PLAYLIST
else
   xmlstarlet ed -L -s /playlist -t elem -n challenge $PLAYLIST_FILE
   xmlstarlet ed -L -s "playlist/challenge[1]" -t elem -n file -v "Challenges/Nadeo/A01-Race.Challenge.Gbx" $PLAYLIST_FILE
fi

echo "INFO | Finished parsing playlist files."
