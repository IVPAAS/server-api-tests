<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
require_once('apiTestHelper.php');

function Test1_DistributeEntry($client, $targetClient, $profileId)
{
	info("Create entry and upload content");
	$MediaEntry = helper_createEntryAndUploaDmp4Content($client, 'crossKalturaDistributionTest');

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
	$entryDistribution->distributionProfileId = $profileId;
	$entryDistribution = $client->entryDistribution->add($entryDistribution);
	$entryDistribution = $client->entryDistribution->submitAdd($entryDistribution->id);

	info("Distributing ".$MediaEntry->id);
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

	return success(__FUNCTION__);
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
