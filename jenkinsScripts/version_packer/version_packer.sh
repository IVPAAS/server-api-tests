#!/bin/bash

RPM_REPLICATE_DIR='/tmp/rpm_temp_replication'
TEMP_DIR_NAME='downloaded'
FULL_RPM_REPLICATE_DIR="$RPM_REPLICATE_DIR/$TEMP_DIR_NAME"

REPOSYNC=`which reposync`
REPO_FILE='/etc/yum.repos.d/kaltura_repo_for_replication.repo'
CREATEREPO='/usr/bin/createrepo'
WORKERS=2
ON_PREM_PATCH_RPM='on-prem-patch.rpm'
KEY='/root/.ssh/id_rsa.root'
SCP=`which scp`

TARGET_REPO_MACHINE='onpremrepo.dev.kaltura.com'
TARGET_REPO_MACHINE_DIR='/var/www/html/onPremRepository/Jupiter/'
# TARGET_REPO_MACHINE_DIR='/tmp/copy_test'


# function check_needed_packages {
# }

function clean_up {
    echo -n "Intial clean up..."
    rm -rf $RPM_REPLICATE_DIR
    echo "done"
}


function create_local_repo {
    echo "Creating repository from rpm files at FULL_RPM_REPLICATE_DIR. Please be patient."
    $CREATEREPO --update --workers=$WORKERS $FULL_RPM_REPLICATE_DIR/
    if [ $? -ne 0 ];then
        echo "Problem occured during repository creation under $RPM_REPLICATE_DIR."
        exit 3
    fi    
}


function scp_to_target {
    identify_version CURRENT_VERSION
    SCP_FULL_TARGET="$RPM_REPLICATE_DIR/$VERSION"
    mv "$FULL_RPM_REPLICATE_DIR" "$SCP_FULL_TARGET"
    echo "copying repository to target."
    scp_command="$SCP -r -i $KEY $RPM_REPLICATE_DIR/* `whoami`@$TARGET_REPO_MACHINE:$TARGET_REPO_MACHINE_DIR/"
    echo $scp_command
}


function identify_version {
    VERSION=`ls -l $FULL_RPM_REPLICATE_DIR/noarch/ | grep kaltura-base | awk '{print $9}' | awk -F '-' '{print $3}' | uniq`
    if [ -z "$VERSION" ]; then
        echo "The version could not be determined. Check if kaltura-base rpm exists in $RPM_REPLICATE_DIR/noarch"
        exit 1
    fi       
    echo "Identified version is $VERSION"
    eval "$1=$VERSION"
}


function add_on_prem_patch_rpm {
    echo "copying $ON_PREM_PATCH_RPM to $RPM_REPLICATE_DIR/noarch"
}


function replicate_repository {
    if [ ! -r "$REPO_FILE" ]; then
        echo "Was not able to find $REPO_FILE. Exiting"
        exit 1
    fi
    mkdir $RPM_REPLICATE_DIR
    echo "Replicating noarch to $FULL_RPM_REPLICATE_DIR/noarch"
    replicate_command="$REPOSYNC --repoid=kaltura-noarch-replication --norepopath -p $FULL_RPM_REPLICATE_DIR/noarch"
    # $replicate_command
    if [ $? -ne 0 ];then
        echo "Problem occured during 'kaltura-noarch-replication' replication into $FULL_RPM_REPLICATE_DIR/noarch"
        exit 2
    fi
    echo "Replicating x86_64 to $FULL_RPM_REPLICATE_DIR/x86_64"
    replicate_command="$REPOSYNC --repoid=kaltura-replication --norepopath -p $FULL_RPM_REPLICATE_DIR/x86_64"
    # $replicate_command
    if [ $? -ne 0 ];then
        echo "Problem occured during 'kaltura-replication' replication into $FULL_RPM_REPLICATE_DIR/x86_64"
        exit 2
    fi
}



#clean_up
replicate_repository
add_on_prem_patch_rpm
create_local_repo
scp_to_target