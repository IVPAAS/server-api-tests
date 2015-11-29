#!/bin/bash
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


if [[ $# -lt 2 ]]; then
    $ECHO "${PURPLE}Usage: test_default_provider_sign_key.sh <udrm_server_address> <partner_that_exists_in_CB>\n"
    $ECHO "${NC}"
    exit 0
fi

GOOD_CUSTOM_DATA_DECODED='{"ca_system":"OTT","user_token":"804200","account_id":'${2}',"content_id":"superman-a-eng-Web.ism","files":"","udid":"test"}'
BAD_CUSTOM_DATA_DECODED='{"ca_system":"OTT","user_token":"804200","account_id":14141414141414,"content_id":"superman-a-eng-Web.ism","files":"","udid":"test"}'

GOOD_CUSTOM_DATA_ENCODED=`echo -n $GOOD_CUSTOM_DATA_DECODED|base64 -w2000`
rawurlencode $GOOD_CUSTOM_DATA_ENCODED
GOOD_CUSTOM_DATA_ENCODED=$rawurlencode_RESULT

BAD_CUSTOM_DATA_ENCODED=`echo -n $BAD_CUSTOM_DATA_DECODED|base64 -w2000`
rawurlencode $BAD_CUSTOM_DATA_ENCODED
BAD_CUSTOM_DATA_ENCODED=$rawurlencode_RESULT


URL="${1}/playready/license?custom_data=$BAD_CUSTOM_DATA_ENCODED&signature=1234&"
response=`curl -s --data-urlencode -X POST "$URL"`
# echo "playready license response [$response]\n"
$ECHO 'Playready license with non-existing partner test:'
if [[ "$response" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
    $ECHO "response is [$response]\n"
fi

URL="${1}/playready/encryption?signature=1234&"
PAYLOAD=$BAD_CUSTOM_DATA_DECODED
response=`curl -s -X POST -d "$PAYLOAD" -H"Content-Type: application/json" "$URL"`
# echo "playready license response [$response]\n"
$ECHO 'Playready encryption with non-existing partner test:'
if [[ "$response" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
    $ECHO "response is [$response]\n"
fi

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


##################################
URL="${1}/playready/license?custom_data=$GOOD_CUSTOM_DATA_ENCODED&signature=1234&"
response=`curl -s --data-urlencode -X POST "$URL"`
# echo "playready license response [$response]\n"
$ECHO 'Playready license with existing partner test:'
if [[ "$response" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
    $ECHO "response is [$response]\n"
fi

URL="${1}/playready/encryption?signature=1234&"
PAYLOAD=$GOOD_CUSTOM_DATA_DECODED
response=`curl -s -X POST -d "$PAYLOAD" -H"Content-Type: application/json" "$URL"`
# echo "playready license response [$response]\n"
$ECHO 'Playready encryption with existing partner test:'
if [[ "$response" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
    $ECHO "response is [$response]\n"
fi

URL="${1}/widevine/license?"
PAYLOAD="custom_data=$GOOD_CUSTOM_DATA_ENCODED&signature=MTIzNA==&"
response=`curl -s --data-urlencode -X POST -d "$PAYLOAD" "$URL"`
# echo "widevine license response [$response]\n"
$ECHO 'Widevine license with existing partner test:'
if [[ "$response" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
    $ECHO "response is [$response]\n"
fi

URL="${1}/widevine/encryption?signature=MTIzNA%3D%3D"
PAYLOAD=$GOOD_CUSTOM_DATA_DECODED
response=`curl -s -X POST -d "$PAYLOAD" -H"Content-Type: application/json" "$URL"`
# echo "widevine encryption response [$response]\n"
$ECHO 'Widevine encryption with existing partner test:'
if [[ "$response" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
    $ECHO "response is [$response]\n"
fi

URL="${1}/cenc/license?custom_data=$GOOD_CUSTOM_DATA_ENCODED&signature=MTIzNA==&"
#PAYLOAD="custom_data=$GOOD_CUSTOM_DATA_ENCODED&signature=MTIzNA==&"
response=`curl -s --data-urlencode -X POST "$URL"`
# echo "cenc license response [$response]\n"
$ECHO 'cenc license with existing partner test:'
if [[ "$response" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
    $ECHO "response is [$response]\n"
fi

URL="${1}/cenc/encryption?signature=MTIzNA%3D%3D"
PAYLOAD=$GOOD_CUSTOM_DATA_DECODED
response=`curl -s -X POST -d "$PAYLOAD" -H"Content-Type: application/json" "$URL"`
# echo "cenc encryption response [$response]\n"
$ECHO 'cenc encryption with existing partner test:'
if [[ "$response" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
    $ECHO "response is [$response]\n"
fi



$ECHO "${BROWN}Finished all tests${NC}\n"

exit 0
