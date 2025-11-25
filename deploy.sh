#!/bin/bash
set -x

FTP_HOST="45.132.157.76"
FTP_USER="u355470762.sig"
FTP_PASS="Souz@010521 "
LOCAL_DIR="."
REMOTE_DIR="."

lftp -u $FTP_USER,$FTP_PASS $FTP_HOST <<EOF
set ssl:verify-certificate no
mirror -R $LOCAL_DIR $REMOTE_DIR --overwrite --delete --verbose --parallel=5
quit
EOF
echo "Deploy concluído com sucesso!"

