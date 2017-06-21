#!/bin/bash

if [ "$#" -ne 8]; then
    echo "You are missing arguments"
    echo "This is what is expected : x.sh <dc url> <partnerId> <elastic host> <elastic port> <index type> <index name> <number of drawn elastic docs per partner> <number of drawn partners> <max partner id>"
fi

for $partnerDraw in {1..$8}
do



done

