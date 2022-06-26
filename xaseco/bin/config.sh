#!/command/with-contenv bash

if ls config/* &>/dev/null; then
    echo "INFO | Linking custom configuration files..."    
    for i in config/*
    do
        ln -sf $i .
    done
    echo "INFO | Custom configuration done."
fi
