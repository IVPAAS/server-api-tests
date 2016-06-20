#!/bin/bash -e 
#===============================================================================
#          FILE: package_kaltura_core.sh
#         USAGE: ./package_kaltura_core.sh 
#   DESCRIPTION: 
#       OPTIONS: ---
# 	LICENSE: AGPLv3+
#  REQUIREMENTS: ---
#          BUGS: ---
#         NOTES: ---
#        AUTHOR: Jess Portnoy (), <jess.portnoy@kaltura.com>
#  ORGANIZATION: Kaltura, inc.
#       CREATED: 01/10/14 08:46:43 EST
#      REVISION:  ---
#===============================================================================

set -o nounset                              # Treat unset variables as an error

echo "$RPM_CREATION_DIR----------$version_to_pack-------------------------"


SOURCES_RC=$GIT_PACKAGING_SCRIPTS_DIR/sources.rc
if [ ! -r $SOURCES_RC ];then
    echo "Could not find $SOURCES_RC"
    exit 1
fi
. $SOURCES_RC 
if [ ! -x "`which wget 2>/dev/null`" ];then
    echo "Need to install wget."
    exit 2
fi

RPM_NAME=$RPM_SOURCES_DIR/$KALTURA_SERVER_VERSION-TM.zip
echo "Downloading $RPM_NAME.... "
wget -q $KALTURA_CORE_URI -O$RPM_NAME
echo "Packaged into $RPM_NAME"
if [ -x "`which rpmbuild 2>/dev/null`" ];then
	rpmbuild -bb `dirname $0`/../spec_files/kaltura-base-ad-hoc-build.spec --define "_rpmdir $RPM_CREATION_DIR/" --define "_version $version_to_pack" --define "_rc build_$BUILD_NUMBER" --define "_codename Kajam"
fi
