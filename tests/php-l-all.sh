#!/bin/bash

# Ubuntu LTS php versions
# 14.04 5.5, 16.04 7.0, 18.04 7.2, 20.04 7.4
phps=(5.5 7.0 7.2 7.4 8.0)

for php in ${phps[@]}; do
    docker run --rm -it -v "${PWD}:/var/tmp/app" php:${php}-cli /var/tmp/app/tests/php-l.sh
done

