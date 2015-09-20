#!/bin/bash

u=`echo "$1" | tr -dc '[:alnum:]_.'`

if [ ! -e /home/vmail/${u} ]
then
    mkdir /home/vmail/${u} && chmod u=rwx,go= /home/vmail/${u} && chown vmail:vmail /home/vmail/${u}
else
    echo "Already exists: /home/vmail/${u}"
    exit 1
fi
