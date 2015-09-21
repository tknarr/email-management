#!/bin/bash

u=`echo "$1" | tr -dc '[:alnum:]_.'`

if [ ! -e /home/vmail/${u} ]
then
    mkdir /home/vmail/${u} 2>&1 && chmod u=rwx,go= /home/vmail/${u} 2>&1
else
    echo "Already exists: /home/vmail/${u}"
    exit 1
fi
