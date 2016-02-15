<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
require_once('apiTestHelper.php');

function helper_createEntryAndUploaDmp4Content($client)
{
	$FILE_NAME_MP4 = dirname ( __FILE__ ).'/../resources/Kaltura Test Upload.mp4';
	$entry = addEntry($client,'tvinciDistributionTest');
	$uploadTokenObj = new KalturaUploadToken();
	$uploadTokenObj->fileName = $FILE_NAME_MP4;
	$uploadToken = $client->uploadToken->add($uploadTokenObj);
	$fileData = $FILE_NAME_MP4;
	$result = $client->uploadToken->upload($uploadToken->id,$fileData ,null,null,null);
	$resource = new KalturaUploadedFileTokenResource();
	$resource->token = $uploadToken->id;
	$result = $client->baseEntry->addcontent($entry->id, $resource);
	return $result;
}

function isEntryReady($client,$id)
{
	$result = $client->baseEntry->get($id, null);
	if ($result->status == 2)
		return true;
	return false;
}

function isSubmitting($client, $id)
{
	$result = $client->entryDistribution->get($id);
	if ($result->status == 4) // status submitting
		return true;
	return false;
}

function isRemoving($client, $id)
{
	$result = $client->entryDistribution->get($id);
	if ($result->status == 6) // status submitting
		return true;
	return false;
}

function Test1_DistributeEntry($client, $profileId)
{
	info("Create entry and upload content");
	$MediaEntry = helper_createEntryAndUploaDmp4Content($client);

	info("Wait for entry to be ready id =".$MediaEntry->id);
	while(isEntryReady($client,$MediaEntry->id)!=true)
	{
		sleep(1);
		print (".");
	}

	//start tvinci distribution
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

	//delete distribution
	$entryDistribution = $client->entryDistribution->submitDelete($entryDistribution->id);

	info("Removing distribution entry ".$MediaEntry->id);
	while(isRemoving($client,$entryDistribution->id))
	{
		sleep(1);
		print (".");
	}

	$entryDistribution = $client->entryDistribution->get($entryDistribution->id);
	if ($entryDistribution->status != 10)
	{
		return fail(__FUNCTION__." Removing Distribution Failed");
	}

	info($MediaEntry->id . "Removing Distribution Succeeded" );


	return success(__FUNCTION__);
}


function printTestUsage()
{
	print ("\n\rUsage: " .$GLOBALS['argv'][0] . " <DC URL> <partner ID> <admin secret> <tvinci distribution profile ID>");
	print ("\n\r for distribution entry to tvinci.\r\n");
}


function main( $dc, $partnerId, $adminSecret,$profileId )
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);

	$ret  = Test1_DistributeEntry($client, $profileId);
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

	$res 				=  main($dcUrl,$partnerId,$adminSecret,$profileId);
	exit($res);
}

go();