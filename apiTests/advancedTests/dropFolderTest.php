<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');

function Test1_UploadToDropFolder($dc,$scp_user_name,$scp_user_pass,$testPartnerId,$testPartnerAdminSecret,$dropFolderPath)
{
    info("Connecting to SCP server on $dc");
    $connection = ssh2_connect($dc, 22);
    if (!$connection)
        return fail(__FUNCTION__ . " Couldn't connect to SCP. Please check connection.");

    info("Authenticating user and pass in SCP server on $dc");
    $authenticate=ssh2_auth_password($connection, $scp_user_name, $scp_user_pass);
    if (!$authenticate)
        return fail(__FUNCTION__ . " Couldn't authenticate to server. Wrong username or password.");

    $filesPath = dirname(__FILE__) . '/../../resources/';
    $filesToUpload=getFilesToUpload();
    $countUploaded=0;
        foreach ($filesToUpload as $file)
        {
            $upload = uploadFileToDropFolder($connection,$filesPath.$file,$dropFolderPath."/".$file);
            if(!$upload)
               return fail(__FUNCTION__ . " Couldn't upload file ".$file." to drop folder: ".$dropFolderPath." on server: ".$dc);
            else {
                if(pathinfo($file,PATHINFO_EXTENSION) != 'xml')
                    $countUploaded++;

                info("Uploaded file: ".$file." ,to drop folder: ".$dropFolderPath);
            }
        }

    $filter = new KalturaBaseEntryFilter();
    $filter->freeText = 'searchDropFolderTest';
    
    $client = startKalturaSession($testPartnerId,$testPartnerAdminSecret,$dc);

    $retry = 0;
    $result = false;
    while($retry  <7)
    {
        info("waiting for entry to upload..\n");
        sleep(30);
        $entriesList = $client->baseEntry->listAction($filter);
        info("count objects [".count($entriesList->objects)."] , count uploaded [".$countUploaded."]" );
        if(count($entriesList->objects) != $countUploaded)
            $retry++;
        else{
            $result=true;
            break;
        }
    }

    if(!$result)
         return  fail(__FUNCTION__." Drop folder test Failed - count doesn't match");

    return success(__FUNCTION__);
}

function uploadFileToDropFolder($connection, $file,$target)
{
    return ssh2_scp_send($connection,$file,$target,0644);
}

function getFilesToUpload()
{ //files from resources dir
    $FILE_NAME_MP4 = 'KalturaTestUpload.mp4';
    $FILE_NAME_XML = 'dropFolderTestXml.xml';
    return array($FILE_NAME_MP4, $FILE_NAME_XML);
}

function printTestUsage()
{
    print ("\n\rUsage: " .$GLOBALS['argv'][0] . " <DC URL> <SCP Username> <SCP Password> <testPartnerID> <TestPartnerAdminSecret> <dropFolderPath>");
    print ("\n\r For drop folder Testing.\r\n");
}

function main($dc,$scp_user_name,$scp_user_pass,$testPartnerId,$testPartnerAdminSecret,$dropFolderPath)
{
    $ret = Test1_UploadToDropFolder($dc,$scp_user_name,$scp_user_pass,$testPartnerId,$testPartnerAdminSecret,$dropFolderPath);
    return ($ret);
}

function go()
{
    if ($GLOBALS['argc']!=7 )
    {
        printTestUsage();
        exit (1);
    }

    $dcUrl 			            = 	$GLOBALS['argv'][1];
    $storageUsername 		    = 	$GLOBALS['argv'][2];
    $storageUserPassword     	= 	$GLOBALS['argv'][3];
    $testPartnerId	   		    = 	$GLOBALS['argv'][4];
    $testPartnerAdminSecret	   	= 	$GLOBALS['argv'][5];
    $dropFolderPath             =   $GLOBALS['argv'][6];
    $res  =  main($dcUrl,$storageUsername,$storageUserPassword, $testPartnerId,$testPartnerAdminSecret,$dropFolderPath);
    exit($res);
}

go();
