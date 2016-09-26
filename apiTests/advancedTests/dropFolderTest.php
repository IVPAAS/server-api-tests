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


function Test2_UploadWebVTTCatptionToDropFolder($dc,$scp_user_name,$scp_user_pass,$testPartnerId,$testPartnerAdminSecret,$dropFolderPath)
{
    info("Connecting to SCP server on $dc");
    $connection = ssh2_connect($dc, 22);
    if (!$connection)
        return fail(__FUNCTION__ . " Couldn't connect to SCP. Please check connection.");

    info("Authenticating user and pass in SCP server on $dc");
    $authenticate = ssh2_auth_password($connection, $scp_user_name, $scp_user_pass);
    if (!$authenticate)
        return fail(__FUNCTION__ . " Couldn't authenticate to server. Wrong username or password.");

    $filesPath = dirname(__FILE__) . '/../../resources/';
    $filesToUpload = getFilesWithCaptionsToUpload();
    $countUploaded = 0;
    foreach ($filesToUpload as $file)
    {
        $upload = uploadFileToDropFolder($connection, $filesPath . $file, $dropFolderPath . "/" . $file);
        if (!$upload)
            return fail(__FUNCTION__ . " Couldn't upload file " . $file . " to drop folder: " . $dropFolderPath . " on server: " . $dc);
        else
        {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'mp4')
                $countUploaded++;

            info("Uploaded file: " . $file . " ,to drop folder: " . $dropFolderPath);
        }
    }

    $filter = new KalturaBaseEntryFilter();
    $filter->freeText = 'WebVttCaptionSearchDropFolderTest';

    $client = startKalturaSession($testPartnerId, $testPartnerAdminSecret, $dc);

    $retry = 0;
    $result = false;
    while ($retry < 7)
    {
        info("waiting for entry to upload..\n");
        sleep(30);
        $entriesList = $client->baseEntry->listAction($filter);
        info("count objects [" . count($entriesList->objects) . "] , count uploaded [" . $countUploaded . "]");
        if (count($entriesList->objects) != $countUploaded)
            $retry++;
        else
        {
            $result = true;
            break;
        }
    }


    if (!$result)
        return fail(__FUNCTION__ . " Drop folder test Failed - count doesn't match");


    info("checking that webVtt caption assed was created successfully.\n");
    $entry = $entriesList->objects[0];

    $filter = new KalturaAssetFilter();
    $filter->entryIdIn = $entry->id;
    $pager = null;
    $captionPlugin = KalturaCaptionClientPlugin::get($client);
    $result = $captionPlugin->captionAsset->listAction($filter, $pager);

    if (count($result->objects) != 1)
        return fail(__FUNCTION__ . " Drop folder test Failed - Caption Asset wasn't created for entry $entry->id ");


    $captionAsset = $result->objects[0];
    if ($captionAsset->format != 3 || $captionAsset->fileExt != 'vtt')
        return fail(__FUNCTION__ . " Drop folder test Failed - Caption Asset for entry $entry->id isn't in VTT format or file extension is not .vtt");

    return success(__FUNCTION__);

    }

function Test3_UploadStreamsXmlToDropFolder($dc,$scp_user_name,$scp_user_pass,$testPartnerId,$testPartnerAdminSecret,$dropFolderPath)
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
    $filesToUpload=getStreamsFilesToUpload();
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
    $filter->freeText = 'testWithStreams';

    $client = startKalturaSession($testPartnerId,$testPartnerAdminSecret,$dc);

    $retry = 0;
    $result = false;
    while($retry  <7)
    {
        info("waiting for testWithStreams entry to upload..\n");
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

    $entry = $entriesList->objects[0];
    
    if(count($entry->streams) != 7)
        return fail(_FUNCTION__."Streams Drop folder test Failed - entry->streams count is [".count($entry->streams)."] but expected 7");

    //test without streams
    $filter->freeText = 'testWithoutStreams';
    $retry = 0;
    $result = false;
    while($retry  <7)
    {
        info("waiting for testWithoutStreams entry to upload..\n");
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

    if($result)
        return fail(_FUNCTION__."Streams Drop folder test Failed - entry with empty stream session is uploaded, but expected to fail");

    //test without type
    $filter->freeText = 'testWithoutType';
    $retry = 0;
    $result = false;
    while($retry  <7)
    {
        info("waiting for testWithoutType entry to upload..\n");
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

    if($result)
        return fail(_FUNCTION__."Streams Drop folder test Failed - entry without type in stream session is uploaded, but expected to fail");

    //test without TrackIndex
    $filter->freeText = 'testWithWithoutTrackIndex';
    $retry = 0;
    $result = false;
    while($retry  <7)
    {
        info("waiting for testWithWithoutTrackIndex entry to upload..\n");
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

    if($result)
        return fail(_FUNCTION__."Streams Drop folder test Failed - entry without TrackIndex in stream session is uploaded, but expected to fail");


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

function getFilesWithCaptionsToUpload()
{ //files from resources dir
    $FILE_NAME_MP4 = 'KalturaTestUpload.mp4';
    $FILE_NAME_WEB_VTT_CAPTIONS= 'webVttCaptionsTest.vtt';
    $FILE_NAME_XML = 'dropFolderWebVttCaptionsTestXml.xml';
    return array($FILE_NAME_MP4, $FILE_NAME_WEB_VTT_CAPTIONS, $FILE_NAME_XML);
}

function getStreamsFilesToUpload()
{ //files from resources dir
    $FILE_NAME_XML = 'dropFolderStreamsTestXml.xml';
    return array($FILE_NAME_XML);
}

function printTestUsage()
{
    print ("\n\rUsage: " .$GLOBALS['argv'][0] . " <DC URL> <SCP Username> <SCP Password> <testPartnerID> <TestPartnerAdminSecret> <dropFolderPath>");
    print ("\n\r For drop folder Testing.\r\n");
}

function main($dc,$scp_user_name,$scp_user_pass,$testPartnerId,$testPartnerAdminSecret,$dropFolderPath)
{
    $ret = Test1_UploadToDropFolder($dc,$scp_user_name,$scp_user_pass,$testPartnerId,$testPartnerAdminSecret,$dropFolderPath);
    $ret += Test2_UploadWebVTTCatptionToDropFolder($dc,$scp_user_name,$scp_user_pass,$testPartnerId,$testPartnerAdminSecret,$dropFolderPath);
    $ret += Test3_UploadStreamsXmlToDropFolder($dc,$scp_user_name,$scp_user_pass,$testPartnerId,$testPartnerAdminSecret,$dropFolderPath);
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
