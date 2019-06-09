#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" >/dev/null 2>&1 && pwd )"
cd $DIR && rm -rf docs/*
cd $DIR && vendor/bin/phpdoc -d src -t tmp/ --template="xml"
cd $DIR && vendor/bin/phpdocmd tmp/structure.xml docs/ --index README.md 
cd $DIR && rm -rf tmp/*