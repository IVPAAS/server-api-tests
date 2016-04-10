#!/bin/bash
PACKING_USER="rpmbuilder"
RPMMACROS_TEMPLATE="/opt/sandbox/master_packer/nightly_rpmmacros.template"
TEMP_RPMMACROS_FILE="/opt/sandbox/master_packer/nightly_rpmmacros.altered"
PLATFORM_INSTALL_DIR="/home/$PACKING_USER/platform-install-packages"
SOURCE_RC_DIR="$PLATFORM_INSTALL_DIR/build"
# PACKAGING_SCRIPTS_DIR="$PLATFORM_INSTALL_DIR/build"
PACKAGING_SCRIPTS_DIR="`dirname $0`/packagers"
GIT_PACKAGING_SCRIPTS_DIR="/home/$PACKING_USER/platform-install-packages/build"
RPM_CREATION_DIR="/home/$PACKING_USER/rpmbuild/RPMS/nightly/"
FINAL_REMOTE_RPM_PATH="/var/www/html/onPremRepository/autobuilds/nightly"
REMOTE_RPM_REPO_MACHINE="onpremrepo.dev.kaltura.com"
PACKAGES_SCRIPT_LIST="package_kaltura_core.sh package_kaltura_postinst-TM.sh"
CE_REPO_URL="http://installrepo.origin.kaltura.org/repo/releases/@VERSION@/RPMS/"
CE_RPM_TEMP_DOWNLOAD="/tmp/ce_temp_download/nightly"
MINIMAL_SPACE_NEEDED=7 # in Gigs
RPM_LIST_FILE="`dirname $0`/rpm_list.list"

function replace_rpmmacros_tokens_from_input () {
    cp $RPMMACROS_TEMPLATE $TEMP_RPMMACROS_FILE
    # kdp3_vers
    for var in kmc_version clipapp_version html5_version kdp3_wrapper_version kmc_login_version kcw_uiconf_ver ;
    do
        if [ ! -z "${!var}" ]; then
            echo $var "${!var}" @rpmmacros_"$var"@
            sed -i -e s/@rpmmacros_"$var"@/"${!var}"/g -e s/@rpmmacros__"$var"@/"${!var}"/g $TEMP_RPMMACROS_FILE
        fi
    done
}

function check_for_needed_space () {
    space=`df -h / | tail -1 | awk '{print $3}' | tr -s 'G' '\n'`
    if [ `echo $space'>'$MINIMAL_SPACE_NEEDED | bc -l` -eq 0 ];
    then
        echo "Not enough space. Minimum needed is: [$MINIMAL_SPACE_NEEDED]. Found: [$space]"
        exit 11
    fi
    echo "Space check passed: ["$space"G]"
}

function replace_sources_rc_versions () {
    # cp $PACKAGING_SCRIPTS_DIR/sources.rc $PACKAGING_SCRIPTS_DIR/sources.rc.orig
     sed -i -e "s/KDP3_LATEST_VERSION=.*/KDP3_LATEST_VERSION=\"$kmc_version\"/g" \
            -e "s/CLIPAPP_NO_V_VERSION=.*/CLIPAPP_NO_V_VERSION=\"$clipapp_version\"/g" \
            -e "s/HTML5LIB_LATEST_VERSION=.*/HTML5LIB_LATEST_VERSION=\"$html5_version\"/g" \
            -e "s/KDPWRAPPER_VERSION=.*/KDPWRAPPER_VERSION=\"$kdp3_wrapper_version\"/g" \
            -e "s/KMC_MINUS_V_VERSION=.*/KMC_MINUS_V_VERSION=\"$kmc_login_version\"/g" \
            -e "s/KCW_UICONF_VERSIONS=.*/KCW_UICONF_VERSIONS=\"$kcw_uiconf_ver\"/g" \
        $GIT_PACKAGING_SCRIPTS_DIR/sources.rc
            # -e "s/KDP3_VERSIONS=.*/KDP3_VERSIONS=$kdp3_vers/g" \     
}

function pack_specific_rpm () {
    if [ -z $1 ]; then
        error_handling "No argument was provided for building the RPM (pack_specific_rpm)" $21
    fi
    RPM_NAME=$1
    packaging_command="$PACKAGING_SCRIPTS_DIR/$RPM_NAME"
    echo $packaging_command
    `runuser rpmbuilder -c "$packaging_command"`
}

function checkout_needed_branch () {
    echo "Updating $PLATFORM_INSTALL_DIR git pull -v $branch_id"
    # echo "Updating $PLATFORM_INSTALL_DIR git pull -v --dry-run $branch_id"
    cd $PLATFORM_INSTALL_DIR
    git pull -v origin $branch_id 
    cd -
}

function pack_all_rpm_list () {
    version_to_pack=`echo $branch_id | awk -F '-' '{print $2}'`
    export version_to_pack
    for script in $PACKAGES_SCRIPT_LIST; do
        $PACKAGING_SCRIPTS_DIR/$script 
        if [ $? -ne 0 ]; then
            echo "Result: FAIL"
            exit 30
        fi
        echo "Result: OK"
        echo -e "--------------------------------------\n"
    done
}


function cp_from_ce () {
    LYNX_COMMAND="lynx -dump -listonly"
    needed_version=`echo $1 | awk -F '-' '{print $2}'`
    actual_link=`echo $CE_REPO_URL | sed s/@VERSION@/$needed_version/g`
    
    # if [ ! -r $RPM_LIST_FILE ];then
    #     echo "Was not able to find the rpm list file ($RPM_LIST_FILE). Exiting"
    # fi
    # . $RPM_LIST_FILE
    
    # package_list="kaltura-batch-11.10.0-2.noarch.rpm kaltura-clipapp-1.3-2.noarch.rpm kaltura-dwh-9.5.0-2.noarch.rpm kaltura-flexwrapper-v1.2-1.noarch.rpm kaltura-front-11.10.0-1.noarch.rpm kaltura-html5-studio-v2.0.2-1.noarch.rpm kaltura-html5lib-v2.40-1.noarch.rpm kaltura-kclip-v1.1.2.1-2.noarch.rpm kaltura-kcw-1.0.0-6.noarch.rpm kaltura-kdp-v2.7.0-1.noarch.rpm kaltura-kdp3-v3.9.9-2.noarch.rpm kaltura-kdp3wrapper-v37.0-1.noarch.rpm kaltura-kdpwrapper-v11.0-1.noarch.rpm kaltura-kmc-v5.40.1-3.noarch.rpm kaltura-krecord-1.0.0-1.noarch.rpm kaltura-ksr-v1.0.44-1.noarch.rpm kaltura-kupload-1.0.0-1.noarch.rpm kaltura-kvpm-v1.0.6-1.noarch.rpm kaltura-media-server-3.2.0-1.noarch.rpm kaltura-play-server-1.1-4.noarch.rpm kaltura-release-11.10.0-1.noarch.rpm kaltura-server-11.10.0-1.noarch.rpm kaltura-widgets-1.0.0-8.noarch.rpm enca-1.13-1.el6.x86_64.rpm fribidi-0.19.2-2.el6.x86_64.rpm jre-7u45-linux-x64.rpm kaltura-a52dec-0.7.4-11.x86_64.rpm kaltura-fdk-acc-0.1.3-1.x86_64.rpm kaltura-ffmpeg-2.7.2-2.x86_64.rpm kaltura-ffmpeg-aux-2.1.3-1.x86_64.rpm kaltura-ffmpeg-aux-devel-2.1.3-1.x86_64.rpm kaltura-ffmpeg-devel-2.1.3-2.x86_64.rpm kaltura-lame-3.99.5-3.x86_64.rpm kaltura-lame-devel-3.99.5-2.x86_64.rpm kaltura-libass-0.9.11-2.x86_64.rpm kaltura-libfaac-1.26-1.x86_64.rpm kaltura-libmcrypt-2.5.7-5.x86_64.rpm kaltura-libmcrypt-devel-2.5.7-5.x86_64.rpm kaltura-libmemcached-1.0.16-2.x86_64.rpm kaltura-libmemcached-1.0.16-2.x86_64.rpm kaltura-libopencore-amr-0.1.2-2.x86_64.rpm kaltura-librtmp-2.3-2.x86_64.rpm kaltura-mencoder-3.4.6-9.x86_64.rpm kaltura-monit-5.13-1.x86_64.rpm kaltura-nginx-1.8.0-12.x86_64.rpm kaltura-nginx-debug-1.6.2-3.x86_64.rpm kaltura-pentaho-4.2.1-2.x86_64.rpm kaltura-red5-1.0.6-1.x86_64.rpm kaltura-rtmpdump-2.3-2.x86_64.rpm kaltura-segmenter-1.0-2.x86_64.rpm kaltura-sphinx-2.2.1-17.x86_64.rpm kaltura-sshpass-1.05-1.x86_64.rpm kaltura-sshpass-1.05-1.x86_64.rpm kaltura-x264-devel-0.140-2.20140104.x86_64.rpm kaltura-x264-0.140-2.20140104.x86_64.rpm libass-0.10.0-1.el6.x86_64.rpm libxvidcore4-1.3.2-15.el6.x86_64.rpm php-jam-1.0.0-1.x86_64.rpm php-jam-elasticsearch-1.0.0-1.x86_64.rpm php-jam-email-1.0.0-1.x86_64.rpm php-jam-files-1.0.0-2.x86_64.rpm php-jam-snmp-1.0.0-2.x86_64.rpm php-mcrypt-5.3.3-4.x86_64.rpm php-pecl-memcached-2.1.0-1.x86_64.rpm php-pecl-ssh2-0.12-2.x86_64.rpm php-pecl-zendopcache-7.0.5-2.x86_64.rpm schroedinger-1.0.8-1.el6.rf.x86_64.rpm mediainfo-0.7.67-1.x86_64.CentOS_6.rpm mediainfo-0.7.65-1.i686.CentOS_6.rpm libzen-0.4.29-2.el6.x86_64.rpm libzen0-0.4.29-1.x86_64.CentOS_6.rpm"
    for arch in noarch x86_64;
    do
        packages=`echo $package_list | tr -s ' ' '\n' | grep $arch`
        for package in $packages;
        do
            echo -n "Downloading $actual_link/$arch/$package to $CE_RPM_TEMP_DOWNLOAD/$arch .... "
            wget -q $actual_link/$arch/$package -P $CE_RPM_TEMP_DOWNLOAD/$arch/
            [[ $? -ne 0 ]] && echo "Problem downloading $rpm. Exiting" && exit 10
            echo "ok"
        done
    done   
}


# function cp_from_ce () {
#     LYNX_COMMAND="lynx -dump -listonly"
#     needed_version=`echo $1 | awk -F '-' '{print $2}'`
#     actual_link=`echo $CE_REPO_URL | sed s/@VERSION@/$needed_version/g`
    
#     for dir in noarch x86_64; do
#         LIST=`$LYNX_COMMAND $actual_link/$dir/ | egrep -v "kaltura-base|kaltura-postinst" | awk '{print $2}' | grep \.rpm`
#         for rpm in $LIST; 
#         do
#             echo -n "Downloading $rpm to $CE_RPM_TEMP_DOWNLOAD/$dir .... "
#             wget -q $rpm -P $CE_RPM_TEMP_DOWNLOAD/$dir
#             [[ $? -ne 0 ]] && echo "Problem downloading $rpm. Exiting" && exit 10
#             echo "ok"
#         done
#     done
# } 

function clean_up_dirs () {
    for dir in $RPM_CREATION_DIR $CE_RPM_TEMP_DOWNLOAD; do
        echo -n "Clearing any data in $dir ..."
        rm --preserve-root -rf $dir
        echo "done"
    done
    # rm -rf $RPM_CREATION_DIR/* $CE_RPM_TEMP_DOWNLOAD/*
    # echo $RPM_CREATION_DIR/
    
}

function move_to_final_repository () {
    build_tag=$1
    RSYNC=`which rsync`
    RSYNC_COMMAND="$RSYNC -i -v -r $CE_RPM_TEMP_DOWNLOAD/* $RPM_CREATION_DIR/* root@$REMOTE_RPM_REPO_MACHINE:$FINAL_REMOTE_RPM_PATH/$build_tag/"
    echo $RSYNC_COMMAND
    $RSYNC_COMMAND
}


if [ ! -r $RPM_LIST_FILE ];then
    echo "Was not able to find the rpm list file ($RPM_LIST_FILE). Exiting"
fi
. $RPM_LIST_FILE

# echo "kmc_version $kmc_version | clipapp_version $clipapp_version | html5_version $html5_version | kdp3_wrapper_version $kdp3_wrapper_version | kmc_login_version $kmc_login_version | kcw_uiconf_ver $kcw_uiconf_ver"
check_for_needed_space
clean_up_dirs
checkout_needed_branch
replace_sources_rc_versions
replace_rpmmacros_tokens_from_input
export GIT_PACKAGING_SCRIPTS_DIR
export RPM_CREATION_DIR
pack_all_rpm_list
cp_from_ce $branch_id
move_to_final_repository $BUILD_NUMBER-$branch_id-`date +"%m-%d-%y"`
clean_up_dirs