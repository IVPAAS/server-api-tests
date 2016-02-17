<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
require_once('apiTestHelper.php');

function Test1_YoutubeEntryDistribute($client, $DistributionProfileId)
{
    info("Create entry and upload content");
    $MediaEntry = helper_createEntryAndUploaDmp4Content($client, 'youTubeDistributionTest');

//    // update mandatory description and tags in media entry meta data required for youtube distribution.
//    $MediaEntry->description = 'youtube media description';
//    $MediaEntry->tags = 'youtubeTag';
//    $client->baseEntry->update($MediaEntry->id, $MediaEntry);

    info("Upload 300X150 thumb asset");
    helper_uploadThumbAsset($client, $MediaEntry->id);

    info("Wait for entry to be ready id =".$MediaEntry->id);
    while(isEntryReady($client,$MediaEntry->id)!=true)
    {
        sleep(1);
        print (".");
    }

    //start cross Kaltura distribution
	$entryDistribution = new KalturaEntryDistribution();
	$entryDistribution->entryId = $MediaEntry->id;
	$entryDistribution->distributionProfileId = $DistributionProfileId;
	$entryDistribution = $client->entryDistribution->add($entryDistribution);
    $entryDistribution = $client->entryDistribution->submitAdd($entryDistribution->id);

    info("Distributing To Youtube ".$MediaEntry->id);
    while(isSubmitting($client,$entryDistribution->id))
    {
        sleep(1);
        print (".");
    }

    $entryDistribution = $client->entryDistribution->get($entryDistribution->id);
    if ($entryDistribution->status != 2)
    {
        return fail(__FUNCTION__." Distribution Failed");
    }

    info($MediaEntry->id . " Youtube Distribution Succeeded" );
    return success(__FUNCTION__);
}

//function createYoutubeEntryAndUploaDmp4Content($client, $testName)
//{
//    $FILE_NAME_MP4 = dirname ( __FILE__ ).'/../resources/KalturaTestUpload.mp4';
//    $entry = addYoutubeEntry($client, $testName);
//    $uploadTokenObj = new KalturaUploadToken();
//    $uploadTokenObj->fileName = $FILE_NAME_MP4;
//    $uploadToken = $client->uploadToken->add($uploadTokenObj);
//    $fileData = $FILE_NAME_MP4;
//    $result = $client->uploadToken->upload($uploadToken->id,$fileData ,null,null,null);
//    $resource = new KalturaUploadedFileTokenResource();
//    $resource->token = $uploadToken->id;
//    $result = $client->baseEntry->addcontent($entry->id, $resource);
//    return $result;
//}
//
//function addYoutubeEntry($client,$name,$mediaType=KalturaMediaType::VIDEO, $profileId = null, $userId='', $description = 'test media description', $tags = 'test tag')
//{
//    $entry                                  = new KalturaMediaEntry();
//    $type                                   = KalturaEntryType::MEDIA_CLIP;
//    $entry->name                            = $name;
//    $entry->mediaType                       = $mediaType;
//    if ($profileId != null)
//        $entry->conversionProfileId			= $profileId;
//    $entry->userId                          = $userId;
//    $entry->description                     = $description;
//    $entry->tags                            = $tags;
//    $result                                 = $client->baseEntry->add($entry, $type);
//    //print ("\nAdd entry ID:".$result->id);
//    return $result;
//}


function printTestUsage()
{
    print ("\n\rUsage: " .$GLOBALS['argv'][0] . " <DC URL> <partner ID> <admin secret> <YouTube distribution profile ID>");
    print ("\n\r for distribution entry cross Kaltura.\r\n");
    print ("\n\r * Note: target partner ID should be identical to target account in given distribution profile\r\n");
    print ("\n\r * Note: Youtube distribution should be created ,authenticated and enabled, manually prior to running this test\r\n");
    print ("\n\r * For info on how to create a youtube distribution profile please see the following link:\r\n");
    print ("\n\r * https://kaltura.atlassian.net/wiki/display/QAC/How+to+create+a+YouTubeAPI+distribution+profile \r\n");
}


function main( $dc, $partnerId, $adminSecret, $distributionProfileId )
{
    $client = startKalturaSession($partnerId,$adminSecret,$dc);
    $ret  = Test1_YoutubeEntryDistribute($client, $distributionProfileId);
    return ($ret);
}

function go()
{
    if ($GLOBALS['argc']!=5 )
    {
        printTestUsage();
        exit (1);
    }

    $dcUrl 				= 	$GLOBALS['argv'][1];
    $partnerId 			= 	$GLOBALS['argv'][2];
    $adminSecret		= 	$GLOBALS['argv'][3];
    $profileId	   		= 	$GLOBALS['argv'][4];

    $res 				=  main($dcUrl,$partnerId,$adminSecret, $profileId);
    exit($res);
}

go();
