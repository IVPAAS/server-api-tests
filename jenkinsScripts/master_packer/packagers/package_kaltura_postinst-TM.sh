#!/bin/bash -e 
#===============================================================================
#          FILE: package_kaltura_postinst.sh
#         USAGE: ./package_kaltura_postinst.sh 
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
SOURCES_RC=$GIT_PACKAGING_SCRIPTS_DIR/sources.rc
if [ ! -r $SOURCES_RC ];then
	echo "Could not find $SOURCES_RC"
	exit 1
fi
. $SOURCES_RC 
cd $BASE_CHECKOUT_DIR/RPM/scripts 
tar zcf $RPM_SOURCES_DIR/kaltura-postinst-TM-$KALTURA_POSTINST_VERSION.tar.gz postinst/
cd -
echo "Packaged into $RPM_SOURCES_DIR/kaltura-postinst-TM-$KALTURA_POSTINST_VERSION.tar.gz"
if [ -x "`which rpmbuild 2>/dev/null`" ];then
	rpmbuild -bb `dirname $0`/../spec_files/kaltura-postinst-TM.spec --define "_rpmdir $RPM_CREATION_DIR/"
fi
