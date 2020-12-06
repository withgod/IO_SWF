#!/bin/bash

SCRIPT_DIR=$(cd $(dirname $0); pwd)
cd $SCRIPT_DIR/../

fi=0
for i in `find . -type f -name \*.php`; do
    ((fi+=1))
    php -l $i > /dev/null
    if [ $? -ne 0 ]; then
        echo "syntax error [$i]"
        exit 1;
    fi
done
phpver=`php -v | head -1 | awk '{print $2}'`
echo php ${phpver}, total ${fi} files. syntax error not found.

