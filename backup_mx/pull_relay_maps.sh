#!/bin/sh

token=`cat ${HOME}/.backup_mx_token`
primary_host=`cat ${HOME}/.backup_mx_primary`
target=/etc/postfix

cd ${target}

for x in relay_domains relay_recipients
do
    curl -s -S -o ${x}.new "https://${primary_host}/email/BackupMXMaps.php?map=${x}&token=${token}"
    if [ $? -eq 0 ]
    then
        # Kill any zero-length files, they're obviously bogus
        if [ ! -s ${x}.new ]
        then
            rm -f ${x}.new
        fi
    else
        # Problem fetching file, clean up any remains
        rm -f ${x}.new
    fi

    diff -q ${x} ${x}.new >/dev/null
    if [ $? -ne 1 ]
    then
        # Files are the same or error diffing files
        rm -f ${x}.new
    else
        postmap hash:${x}.new
        if [ $? -eq 0 ]
        then
            cp ${x} ${x}.old
            mv ${x}.new ${x}
            mv ${x}.new.db ${x}.db
            systemctl restart postfix
        fi
    fi
done

exit 0
