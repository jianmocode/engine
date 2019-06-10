#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" >/dev/null 2>&1 && pwd )"
 
if ! phpdoc list > /dev/null; then
    cd /tmp && rm -f phpDocumentor-2.9.0.tgz && wget https://github.com/phpDocumentor/phpDocumentor2/releases/download/v2.9.0/phpDocumentor-2.9.0.tgz
    cd /tmp && pear install phpDocumentor-2.9.0.tgz
fi


cd $DIR && rm -rf docs/*
cd $DIR && phpdoc -d src -t tmp/ --template="xml"
cd $DIR && vendor/bin/phpdocmd tmp/structure.xml docs/ --index README.md 
cd $DIR && rm -rf tmp/*