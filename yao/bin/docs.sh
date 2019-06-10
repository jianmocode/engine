#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" >/dev/null 2>&1 && pwd )"
 
if ! /usr/bin/phpdoc list > /dev/null; then
    wget http://phpdoc.org/phpDocumentor.phar -O /usr/bin/phpdoc
    chmod +x /usr/bin/phpdoc
fi


cd $DIR && rm -rf docs/*
cd $DIR && /usr/bin/phpdoc -d src -t tmp/ --template="xml"
cd $DIR && vendor/bin/phpdocmd tmp/structure.xml docs/ --index README.md 
cd $DIR && rm -rf tmp/*