#!/bin/bash -x
RED='\033[0;31m'
GREEN='\033[0;32m'
PURPLE='\033[0;35m'
BROWN='\033[0;33m'
NC='\033[0m' # No Color
ECHO='echo -ne'

function rawurlencode() {
  local string="${1}"
  local strlen=${#string}
  local encoded=""

  for (( pos=0 ; pos<strlen ; pos++ )); do
     c=${string:$pos:1}
     case "$c" in
        [-_.~a-zA-Z0-9] ) o="${c}" ;;
        * )               
        printf -v o '%%%02x' "'$c"
     esac
     encoded+="${o}"
  done
  rawurlencode_RESULT="${encoded}"   #+or echo the result (EASIER)... or both... :p
}


if [[ $# -lt 1 ]]; then
    $ECHO "${PURPLE}Usage: test_default_provider_sign_key.sh <udrm_server_address> \n"
    $ECHO "${NC}"
    exit 0
fi

CUSTOM_DATA_DECODED='{"ca_system":"OVP","user_token":"804200","account_id":14141414141414,"content_id":"superman-a-eng-Web.ism","files":"","udid":"test"}'

CUSTOM_DATA_ENCODED=`echo -n $CUSTOM_DATA_DECODED|base64 -w2000`
rawurlencode $CUSTOM_DATA_ENCODED
CUSTOM_DATA_ENCODED=$rawurlencode_RESULT
SIGNATURE="xp29huY/IcpaaxGXnYsKNv+sx1g="
rawurlencode $SIGNATURE
SIGNATURE=$rawurlencode_RESULT

###################################################
############## PLAYREADY test #####################
###################################################

URL="${1}/playready/encryption?signature=$SIGNATURE&"
PAYLOAD=$CUSTOM_DATA_DECODED
response=`curl -s -X POST -d "$PAYLOAD" -H"Content-Type: application/json" "$URL"`
$ECHO 'Playready encryption with non-existing partner test:'
if [[ "$response" =~ "pssh" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
    $ECHO "response is [$response]\n"
fi

URL="${1}/playready/license?custom_data=$CUSTOM_DATA_ENCODED&signature=$SIGNATURE&"
response=`curl -s --data-urlencode -X POST "$URL"`
#$ECHO "playready license response [$response]\n"
$ECHO 'Playready license with non-existing partner test:'
if [[ "$response" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
    $ECHO "response is [$response]\n"
fi

###################################################
############## CENC Playready test #################
###################################################

SIGNATURE="NDut3DyFLaCxBJ6We7Hu8JCNL7A="
rawurlencode $SIGNATURE
SIGNATURE=$rawurlencode_RESULT

URL="${1}/cenc/playready/encryption?signature=$SIGNATURE&"
PAYLOAD=$CUSTOM_DATA_DECODED
response=`curl -s -X POST -d "$PAYLOAD" -H"Content-Type: application/json" "$URL"`
$ECHO 'CENC encryption with non-existing partner test:'
if [[ "$response" =~ "pssh" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
    $ECHO "response is [$response]\n"
fi

URL="${1}/cenc/playready/license?custom_data=$CUSTOM_DATA_ENCODED&signature=$SIGNATURE&"
response=`curl -s --data-urlencode -X POST "$URL"`
$ECHO "CENC license response [$response]\n"
$ECHO 'CENC license with non-existing partner test:'
if [[ "$response" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
    $ECHO "response is [$response]\n"
fi

exit 0







URL="${1}/widevine/license?"
PAYLOAD="custom_data=$BAD_CUSTOM_DATA_ENCODED&signature=MTIzNA==&"
response=`curl -s --data-urlencode -X POST -d "$PAYLOAD" "$URL"`
# echo "widevine license response [$response]\n"
$ECHO 'Widevine license with non-existing partner test:'
if [[ "$response" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
    $ECHO "response is [$response]\n"
fi

URL="${1}/widevine/encryption?signature=MTIzNA%3D%3D"
PAYLOAD=$BAD_CUSTOM_DATA_DECODED
response=`curl -s -X POST -d "$PAYLOAD" -H"Content-Type: application/json" "$URL"`
# echo "widevine encryption response [$response]\n"
$ECHO 'Widevine encryption with non-existing partner test:'
if [[ "$response" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
    $ECHO "response is [$response]\n"
fi

URL="${1}/cenc/license?custom_data=$BAD_CUSTOM_DATA_ENCODED&signature=MTIzNA==&"
#PAYLOAD="custom_data=$BAD_CUSTOM_DATA_ENCODED&signature=MTIzNA==&"
response=`curl -s --data-urlencode -X POST "$URL"`
# echo "cenc license response [$response]\n"
$ECHO 'cenc license with non-existing partner test:'
if [[ "$response" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
    $ECHO "response is [$response]\n"
fi

URL="${1}/cenc/encryption?signature=MTIzNA%3D%3D"
PAYLOAD=$BAD_CUSTOM_DATA_DECODED
response=`curl -s -X POST -d "$PAYLOAD" -H"Content-Type: application/json" "$URL"`
# echo "cenc encryption response [$response]\n"
$ECHO 'cenc encryption with non-existing partner test:'
if [[ "$response" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
    $ECHO "response is [$response]\n"
fi