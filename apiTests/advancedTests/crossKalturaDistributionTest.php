<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__).'/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');

function Test1_DistributeEntry($client, $targetClient, $profileId)
{
	info("Create entry and upload content");
	$MediaEntry = createEntryAndUploaDmp4Content($client, 'crossKalturaDistributionTest');

	info("Upload 300X150 thumb asset");
	uploadThumbAsset($client, $MediaEntry->id);
	waitForEntry($client,$MediaEntry->id);
    $thumbBundleArray = addCuePointToEntry($client, $MediaEntry);

	$ThumbCuePointID =$thumbBundleArray[0];
    $TimeThumbAssetID=$thumbBundleArray[1];

	//start cross Kaltura distribution
	$entryDistribution = new KalturaEntryDistribution();
	$entryDistribution->entryId = $MediaEntry->id;
	$entryDistribution->distributionProfileId = $profileId;
	$entryDistribution = $client->entryDistribution->add($entryDistribution);
	$entryDistribution = $client->entryDistribution->submitAdd($entryDistribution->id);

	info("Distributing ".$MediaEntry->id);
	while(entryDistributionIsSubmitting($client,$entryDistribution->id))
	{
		sleep(1);
        info (".");
	}

	$entryDistribution = $client->entryDistribution->get($entryDistribution->id);
	if ($entryDistribution->status != 2)
	{
		return fail(__FUNCTION__." Distribution Failed");
	}

	info($MediaEntry->id . " Distribution Succeeded" );
	//validate our 300X150 thumb asset was copied
	$assetFilter = new KalturaThumbAssetFilter();
	$assetFilter->entryIdEqual = $entryDistribution->remoteId;
	$thumbAssets = $targetClient->thumbAsset->listAction($assetFilter);
	$thumbFound = false;

	foreach ($thumbAssets->objects as $asset)
	{
		if ( $asset->width == 300 && $asset->height == 150 )
		{
			info("300X150 thumb asset was added properly" );
			$thumbFound = true;
			break;
		}
	}

	if ( !$thumbFound )
		return fail(__FUNCTION__." 300X150 Thumb asset wasn't added");

	$cuePointFilter = new KalturaCuePointFilter();
	$cuePointFilter->entryIdEqual = $entryDistribution->remoteId;
	$targetCuePointPlugin = KalturaCuepointClientPlugin::get($targetClient);
	$cuePoints = $targetCuePointPlugin->cuePoint->listAction($cuePointFilter);
	if ($cuePoints->totalCount != 2)
	{
		return fail(__FUNCTION__. $cuePoints->totalCount. " Cue points were found on target entry");
	}

    info("Testing distribution update.");
    //upload new thump asset on the same cue point and update

    $res = updateThumbCuePoint($client,$ThumbCuePointID,$MediaEntry->id,$TimeThumbAssetID);
    info (print_r($res,true));

    $entryDistribution = $client->entryDistribution->submitUpdate($entryDistribution->id);

    info("Distributing update on entry ".$MediaEntry->id);
	while(entryDistributionIsUpdating($client,$entryDistribution->id))
    {
        sleep(1);
        info (".");
    }

    $entryDistribution = $client->entryDistribution->get($entryDistribution->id);
    if ($entryDistribution->status != 2)
    {
        info("Distribution update fail".print_r($entryDistribution,true));
        return fail(__FUNCTION__." Distribution update Failed");
    }

    info($MediaEntry->id . " Distribution update Succeeded" );

	return success(__FUNCTION__);
}

function addCuePointToEntry($client, $mediaEntry)
{
	$cuepointPlugin = KalturaCuepointClientPlugin::get($client);
	$CcuePoint = new KalturaCodeCuePoint();
	$CcuePoint->code = "bla_bla";
	$CcuePoint->entryId = $mediaEntry->id;
    $CcuePoint->endTime = null;
	$cuepointPlugin->cuePoint->add($CcuePoint);

	$TcuePoint = new KalturaThumbCuePoint();
	$TcuePoint->entryId = $mediaEntry->id;
	$TcuePoint = $cuepointPlugin->cuePoint->add($TcuePoint);

	$thumbAsset = new KalturaTimedThumbAsset();
	$thumbAsset->cuePointId = $TcuePoint->id;
	$thumbAsset = $client->thumbAsset->add($mediaEntry->id, $thumbAsset);

	$THUMB_NAME = dirname ( __FILE__ ).'/../../resources/thumb_300_150.jpg';$uploadTokenObj = new KalturaUploadToken();
	$uploadTokenObj->fileName = $THUMB_NAME;
	$uploadToken = $client->uploadToken->add($uploadTokenObj);
	$fileData = $THUMB_NAME;
	$result = $client->uploadToken->upload($uploadToken->id,$fileData ,null,null,null);
	$resource = new KalturaUploadedFileTokenResource();
	$resource->token = $uploadToken->id;
	$client->thumbAsset->setContent($thumbAsset->id, $resource);
    return array($TcuePoint->id,$thumbAsset->id);
}


function updateThumbCuePoint($client, $cuePointId,$entryId,$TimeThumbAssetID)
{
    $client->thumbAsset->delete($TimeThumbAssetID);

    $thumbAsset = new KalturaTimedThumbAsset();
    $thumbAsset->cuePointId = $cuePointId;
    $thumbAsset = $client->thumbAsset->add($entryId, $thumbAsset);
    $THUMB_NAME = dirname ( __FILE__ ).'/../../resources/thumb_300_150.jpg';$uploadTokenObj = new KalturaUploadToken();
    $uploadTokenObj->fileName = $THUMB_NAME;
    $uploadToken = $client->uploadToken->add($uploadTokenObj);
    $fileData = $THUMB_NAME;
    $client->uploadToken->upload($uploadToken->id,$fileData ,null,null,null);
    $resource = new KalturaUploadedFileTokenResource();
    $resource->token = $uploadToken->id;
    $res = $client->thumbAsset->setContent($thumbAsset->id, $resource);
    return $res;
}


function printTestUsage()
{
	print ("\n\rUsage: " .$GLOBALS['argv'][0] . " <DC URL> <partner ID> <admin secret> <target partner ID> <target admin secret> <crossKaltura distribution profile ID>");
	print ("\n\r for distribution entry cross Kaltura.\r\n");
	print ("\n\r * Note: target partner ID should be identical to target account in given distribution profile\r\n");
	print ("\n\r * Note: Distribution profile should have 300X150 thumbnail dimensions\r\n");
}


function main( $dc, $partnerId, $adminSecret, $targetPId, $targetPSecret, $profileId )
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$targetClient = startKalturaSession($targetPId,$targetPSecret,$dc);

	$ret  = Test1_DistributeEntry($client, $targetClient, $profileId);
	return ($ret);
}

function go()
{
	if ($GLOBALS['argc']!=7 )
	{
		printTestUsage();
		exit (1);
	}

	$dcUrl 				= 	$GLOBALS['argv'][1];
	$partnerId 			= 	$GLOBALS['argv'][2];
	$adminSecret		= 	$GLOBALS['argv'][3];
	$targetPId 			= 	$GLOBALS['argv'][4];
	$targetPSecret		= 	$GLOBALS['argv'][5];
	$profileId	   		= 	$GLOBALS['argv'][6];

	$res 				=  main($dcUrl,$partnerId,$adminSecret, $targetPId, $targetPSecret, $profileId);
	exit($res);
}

go();
