#!/bin/bash 
RED='\033[0;31m'
GREEN='\033[0;32m'
PURPLE='\033[0;35m'
BROWN='\033[0;33m'
NC='\033[0m' # No Color
ECHO='echo -ne'
if [[ $# -lt 1 ]]; then
    $ECHO "${PURPLE}Usage: test_default_provider_sign_key.sh <udrm_server_address>\n"
    $ECHO "${NC}"
    exit 0
fi

CUSTOM_DATA_ENCODED="eyJjYV9zeXN0ZW0iOiJPVFQiLCJ1c2VyX3Rva2VuIjoiODA0MjAwIiwiYWNjb3VudF9pZCI6MTY3LCJjb250ZW50X2lkIjoic3VwZXJtYW4tYS1lbmctV2ViLmlzbSIsImZpbGVzIjoiIiwidWRpZCI6InRlc3QifQ=="
CUSTOM_DATA_DECODED=`echo $CUSTOM_DATA_ENCODED|base64 -d`
URL="${1}/playready/license?custom_data=$CUSTOM_DATA_ENCODED&signature=1234&"
playreadyLicenseResponse=`curl -s --data-urlencode -X POST "$URL"`
# echo "playready license response [$playreadyLicenseResponse]"
$ECHO 'Playready license test:'
if [[ "$playreadyLicenseResponse" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
fi

URL="${1}/playready/encryption?signature=1234&"
PAYLOAD=$CUSTOM_DATA_DECODED
playreadyEncryptionResponse=`curl -s -X POST -d "$PAYLOAD" -H"Content-Type: application/json" "$URL"`
# echo "playready license response [$playreadyEncryptionResponse]"
$ECHO 'Playready encryption test:'
if [[ "$playreadyEncryptionResponse" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
fi

URL="${1}/widevine/license?"
PAYLOAD="custom_data=$CUSTOM_DATA_ENCODED&signature=MTIzNA==&"
widevineLicenseResponse=`curl -s --data-urlencode -X POST -d "$PAYLOAD" "$URL"`
# echo "widevine license response [$widevineLicenseResponse]"
$ECHO 'Widevine license test:'
if [[ "$widevineLicenseResponse" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
fi

URL="${1}/widevine/encryption?signature=MTIzNA%3D%3D"
PAYLOAD=$CUSTOM_DATA_DECODED
widevineEncryptionResponse=`curl -s -X POST -d "$PAYLOAD" -H"Content-Type: application/json" "$URL"`
# echo "widevine encryption response [$widevineEncryptionResponse]"
$ECHO 'Widevine encryption test:'
if [[ "$widevineEncryptionResponse" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
fi

URL="${1}/cenc/license?custom_data=$CUSTOM_DATA_ENCODED&signature=MTIzNA==&"
#PAYLOAD="custom_data=$CUSTOM_DATA_ENCODED&signature=MTIzNA==&"
cencLicenseResponse=`curl -s --data-urlencode -X POST "$URL"`
# echo "cenc license response [$cencLicenseResponse]"
$ECHO 'cenc license test:'
if [[ "$cencLicenseResponse" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
fi

URL="${1}/cenc/encryption?signature=MTIzNA%3D%3D"
PAYLOAD=$CUSTOM_DATA_DECODED
cencEncryptionResponse=`curl -s -X POST -d "$PAYLOAD" -H"Content-Type: application/json" "$URL"`
# echo "cenc encryption response [$cencEncryptionResponse]"
$ECHO 'cenc encryption test:'
if [[ "$cencEncryptionResponse" =~ "Forbidden" ]]; then
    $ECHO "${GREEN} Pass${NC}\n"
else
    $ECHO "${RED} Fail${NC}\n"
fi


$ECHO "${BROWN}Finished all tests${NC}\n"
exit 0