#!/command/with-contenv bash

cd /var/lib/xaseco

XML_HEADER='<?xml version="1.0" encoding="utf-8" ?>\n<aseco_plugins>\n'
XML_FOOTER='</aseco_plugins>'

if ls plugins/custom/* &>/dev/null; then
    for i in plugins/custom/*
    do
        ln -sf ${i#*/} plugins/
    done
fi

PLUGINS_LIST=($(ls -d plugins/*.php | sed -e 's/plugins\///g'))

[[ -r ./blacklist.txt ]] && {
	BLACKLIST=($(cat ./blacklist.txt | tr '\r' ' ' | tr '\n' ' '))
	PLUGINS_LIST=($(echo "${PLUGINS_LIST[@]}" | tr ' ' '\n' |\
		grep -vf <(echo "${BLACKLIST[@]}" | tr ' ' '\n')))
}

{
    # open with header -- \n interpreted
    printf "%b" "$XML_HEADER"

    # main block -- parse plugin list
    {
        [[ "${PLUGINS_LIST[@]}" =~ "plugin.localdatabase.php" ]] && printf "  <plugin>plugin.localdatabase.php</plugin>\n"
        for plugin in "${PLUGINS_LIST[@]}"
        do
            case "${plugin}" in
                "plugin.localdatabase.php")
                    ;;
                "plugin.records_eyepiece.php")
                    ;;
                *)
                    printf "  <plugin>%s</plugin>\n" "${plugin}"
                    ;;
            esac
        done
        [[ "${PLUGINS_LIST[@]}" =~ "plugin.records_eyepiece.php" ]] && printf "  <plugin>plugin.records_eyepiece.php</plugin>\n"
    }

    # finish with footer -- \n interpreted
    printf "%b" "$XML_FOOTER"
} > plugins.xml
