<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
require_once('apiTestHelper.php');


/**
 * @KalturaClient $client
 * @param $recordedEntryId
 * @return mixed
 */
function helper_createLiveEntry($client, $recordedEntryId = null)
{
	$entry                                  = new KalturaLiveStreamEntry();
	$entry->type                            = KalturaEntryType::LIVE_STREAM;
	$entry->name                            = __FUNCTION__;
	$entry->mediaType                       = KalturaMediaType::LIVE_STREAM_FLASH;
	if ($recordedEntryId != null)
		$entry->recordedEntryId					= $recordedEntryId;
	$entry->conversionProfileId				= 17;
	$entry->dvrStatus						= 0;
	$entry->recordStatus					= 1;
	$result                                 = $client->liveStream->add($entry, KalturaSourceType::FILE);
	//print ("\nAdd entry ID:".$result->id);
	return $result;
}

function helper_createRecordedEntry($client)
{
	return addEntry($client,__FUNCTION__, 27);
}

function helper_createVideoToken($client)
{
	$uploadTokenObj = new KalturaUploadToken();
	$uploadTokenObj->fileName = '..\resources\Kaltura Test Upload.mp4';
	$uploadToken = $client->uploadToken->add($uploadTokenObj);
	$fileData = '../resources/Kaltura Test Upload.mp4';
	$result = $client->uploadToken->upload($uploadToken->id,$fileData ,null,null,null);
	$resource = new KalturaUploadedFileTokenResource();
	$resource->token = $uploadToken->id;
	return $resource;
}

function isEntryReady($client,$id)
{
	$result = $client->baseEntry->get($id, null);
	if ($result->status == 2)
		return true;
	return false;
}

function helper_addContentToEntry($client, $entry, $resource)
{
	return $client->baseEntry->addcontent($entry->id, $resource);
}

function helper_appendRecording($client, $entry, $flavorAssetId, $resource)
{
	return $client->liveStream->appendRecording($entry->id, $flavorAssetId, 0 , $resource, $entry->duration , false);
}

function helper_getEntryFlavorAssets($client, $entryId)
{
	$filter = new KalturaAssetFilter();
	$filter->entryIdEqual = $entryId;
	return $client->flavorAsset->listAction($filter);
}


function Test1_CreateLiveStreamEntry($client)
{
	info("Created recorded entry to use");
	$recordedEntry = helper_createRecordedEntry($client);

	info("Create live entry and upload content");
	$liveEntry = helper_createLiveEntry($client, $recordedEntry->id);

	return $liveEntry;
}

function helper_appendRecordingAndValidate($clientMS, $clientServer, $liveEntry,  $flavorAssetId, $duration, $i, $isLastChunk = false)
{
	info("Create a resource to use");
	$resource = helper_createVideoToken($clientMS);

	info("Appending chunk [$i]");
	helper_appendRecording($clientMS, $liveEntry,  $flavorAssetId, $resource, $duration , $isLastChunk);

	info("Waiting for live entry [$liveEntry->id] and recorded entry [$liveEntry->recordedEntryId]");
	//sleep(15);
/*	while(isEntryReady($clientServer,$liveEntry->recordedEntryId)!=true)
	{
		sleep(1);
		print (".");
	}*/
/*
	do {
		$recordedEntry = $clientServer->baseEntry->get($liveEntry->recordedEntryId, null);
		info("Waiting for live entry [$liveEntry->id] and recorded entry [$liveEntry->recordedEntryId]" .
			"and replacing entry [$recordedEntry->replacingEntryId] and replcaed entry [$recordedEntry->replacedEntryId]");
		sleep(1);print (".");
	}while($i!= 0 && !$recordedEntry->replacingEntryId);
	while($recordedEntry->replacingEntryId && isEntryReady($clientServer,$recordedEntry->replacingEntryId)!=true)
	{
		sleep(1);
		print (".");
	}*/
}

function Test2_AppenRecording($clientMS, $liveEntry, $clientServer)
{
	info("Get the flavor assets for the uploaded entry");
	$response = helper_getEntryFlavorAssets($clientMS, $liveEntry->id);

//	print_r($response);

	$flavorAssetId = $response->objects[0]->id;
	info("Now try to append the resource to the first asset ID ");

	for ($i=0 ; $i<10 ; $i++)
	{
		helper_appendRecordingAndValidate($clientMS, $clientServer, $liveEntry, $flavorAssetId, 4, $i);
	}

	helper_appendRecordingAndValidate($clientMS, $clientServer, $liveEntry, $flavorAssetId, 4, $i, true);


}




function main($dc,$partnerId,$adminSecret,$mediaServerSecret)
{
	$clientServer = startKalturaSession($partnerId,$adminSecret,$dc);
	$clientMediaServer = startKalturaSession(-5,$mediaServerSecret,$dc);

	$entry  = Test1_CreateLiveStreamEntry($clientServer);
	$ret  = Test2_AppenRecording($clientMediaServer, $entry, $clientServer);
	$result = $clientServer->baseEntry->get($entry->recordedEntryId, null);
	info("Final duration is [$result->duration]");
	return ($ret);
}

goMain();