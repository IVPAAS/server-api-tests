#!/bin/bash
#===============================================================================
#          FILE: packer.sh
#         USAGE: ./packer.sh
#   DESCRIPTION: Creates a full version of Kaltura RPMs 
#       OPTIONS: ---
#  REQUIREMENTS: ---
#          BUGS: ---
#         NOTES: ---
#        AUTHOR: Kobi Michaeli, <kobi.michaeli@kaltura.com>
#  ORGANIZATION: Kaltura, inc.
#       CREATED: ---
#      REVISION: ---
#===============================================================================


function check_mandatory_files() {
    MANDATORY_FILE_LIST="$CONFIG_FILE"
    echo $MANDATORY_FILE_LIST
    for item in $MANDATORY_FILE_LIST; do
        echo "$item"
        if [ ! -r "$item" ]; then
            error_handling "The file [$item] was not found. Unable to proceed" 20
        fi
    done
}

function error_handling() {
    log_to_file "$1"
    echo -e "
    \rMessage: $1
    \rExit RC: $2
    "
    exit $2
}

function log_to_file() {
    STR_INPUT=$1
    LOG_STR="[$DATE_STR]-[`date +"%H-%M-%S"`] $STR_INPUT"
    echo "$LOG_STR" >> $LOG_DIR/$LOG_FILE_NAME
    echo "$LOG_STR"
}

function createdqeate_log_file () {
    if [ ! -d $LOG_DIR ]; then
        mkdir -p $LOG_DIR
    fi
    
    log_to_file "Log file created. Beginning packing script"
}


function clone_platform_install_from_git() {
    if [ -d $TEMP_CLONE_DIR ]; then
        rm -rvf $TEMP_CLONE_DIR
    fi
    $GIT_COMMAND clone $GIT_REPO $TEMP_CLONE_DIR/
    RC=$?
    [[ $RC -ne 0 ]] && error_handling "FATAL: clone_platform_install_from_git failed. Existing with " $RC
}


function replace_rpmmacros_tokens_from_input () {
    cp $RPMMACROS_TEMPLATE $TEMP_RPMMACROS_FILE
    for var in clipapp_version html5_version kdp3_wrapper_version kmc_login_version kcw_uiconf_ver kdp3_vers;
    do
        if [ ! -z "${!var}" ]; then
            sed -i s/@rpmmacros_"$var"@/"${!var}"/g $TEMP_RPMMACROS_FILE
        fi
    done
}

function replace_rpmmacros_tokens_from_git_source () {
    while read line; 
    do 
        temp_line=`echo -e "$line" | tr -s '%' '\0'`
        key=`echo $temp_line | awk '{print $1}'`
        value=`echo $temp_line | awk '{print $2}'`
        echo @rpmmacros$key@
        sed -i -e "s/@rpmmacros_$key@/$value/g" -e "s/@rpmmacros$key@/$value/g" $TEMP_RPMMACROS_FILE
    done <$CLONED_RPMMACROS_FILE
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


function copy_update_files () {
    cp $TEMP_RPMMACROS_FILE $USER_RPMMACRO_FILE && [[ $? -ne 0 ]] && error_handling "Was not able to update rpmmacros file." $22
    
    for dir in SPECS SOURCES; do 
        if [ -d $RPMBUILD_DIR/$dir ]; then
            unlink $RPMBUILD_DIR/$dir && [[ $? -ne 0 ]] && error_handling "Could not unlink $RPMBUILD_DIR/$dir." $23
        fi
        ln -s $SPEC_FILE_DIR $RPMBUILD_DIR/$dir  && [[ $? -ne 0 ]] && error_handling "Could not create new link between $SPEC_FILE_DIR & $RPMBUILD_DIR/$dir." $24
    done

    if [ -d $PLATFORM_INSTALL_DIR_BUILD ]; then
        unlink $PLATFORM_INSTALL_DIR_BUILD && [[ $? -ne 0 ]] && error_handling "Could not unlink $PLATFORM_INSTALL_DIR_BUILD." $23
    fi
    ln -s $PACKAGING_SCRIPTS_DIR $CLONED_PACKAGING_SCRIPT_DIR && [[ $? -ne 0 ]] && error_handling "Could not create new link between $PACKAGING_SCRIPTS_DIR & $CLONED_PACKAGING_SCRIPT_DIR." $24    

}


check_mandatory_files
. ./packer.config

# TEMP_CLONE_DIR="/tmp/temp_platform_install_clone"
# GIT_COMMAND=`which git`
# GIT_REPO='https://github.com/kaltura/platform-install-packages.git'
# CLONED_RPMMACROS_FILE="$TEMP_CLONE_DIR/RPM/.rpmmacros"
# RPMMACROS_TEMPLATE="rpmmacros.template"
# TEMP_RPMMACROS_FILE="./rpmmacros.altered"
# USER_RPMMACRO_FILE=/home/$PACKING_USER/.rpmmacros
# DATE_STR=`date +"%m-%d-%y"`
# LOG_DIR='./packing_logs'
# LOG_FILE_NAME="packing_log-"$DATE_STR'--'`date +%H-%M-%S`".log"
# CONFIG_FILE="packer.config"
# RPMBUILD=`which rpmbuild`




## MAIN-------------------------------------------------------------------------------------------------------------

copy_update_files
exit 

# Parse arguments
PARSED_ARGS=$(getopt -n "$0"  -o h --long "help,kmc:,clipapp:,html5:,kdp_wrapper:,kcw:,kdp3:,kmc_login:"  -- "$@")
 
#Bad arguments, something has gone wrong with the getopt command.
if [ $? -ne 0 ];
then
    echo "Bad arguments provided"
    exit 1
fi

eval set -- "$PARSED_ARGS"
while true;
do
    case "$1" in
    -h|--help)
        echo "USAGE"
        shift;;
    --kmc)
        echo "KMC"
        kmc_version=$2
        shift 2;;
    --clipapp)
        echo "CLIPAPP"
        clipapp_version=$2
        shift 2;;
    --html5)
        echo "HTML5"
        html5_version=$2
        shift 2;;
    --kdp_wrapper)
        echo "KDP_WRAPPER"
        kdp3_wrapper_version=$2
        shift 2;;
    --kmc_login)
        echo "KMC_LOGIN"
        kmc_login_version=$2
        shift 2;;           
    --kcw)
        echo "KCW"
        kcw_uiconf_ver=$2
        shift 2;;
    --kdp3)
        echo "KDP3"
        kdp3_vers=$2
        shift 2;;
    --)    
      shift
      break;;
    esac
done

shift $((OPTIND-1))
[ "$1" = "--" ] && shift

# clone_platform_install_from_git
replace_rpmmacros_tokens_from_input
replace_rpmmacros_tokens_from_git_source
cat $TEMP_RPMMACROS_FILE
exit 


# create_log_file