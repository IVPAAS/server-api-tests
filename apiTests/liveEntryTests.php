<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
require_once('apiTestHelper.php');


/**
 * @KalturaClient $client (admin KS)
 * $recordedEntryId 
 * @return live entry 
 */
function helper_createLiveEntry($client, $recordedEntryId = null)
{
	$entry                                  = new KalturaLiveStreamEntry();
	$entry->type                            = KalturaEntryType::LIVE_STREAM;
	$entry->name                            = __FUNCTION__;
	$entry->mediaType                       = KalturaMediaType::LIVE_STREAM_FLASH;
	if ($recordedEntryId != null)
		$entry->recordedEntryId					= $recordedEntryId;
	$conversionProfile = $client->conversionProfile->getdefault(KalturaConversionProfileType::LIVE_STREAM);
	$entry->conversionProfileId	= $conversionProfile->id;
	$entry->dvrStatus						= 0;
	$entry->recordStatus					= 1;
	$result                                 = $client->liveStream->add($entry, KalturaSourceType::FILE);
	return $result;
}

function helper_createVideoToken($client,$index=0)
{
	$videoAsset = array('../resources/Countdown2.mp4',
						'../resources/KalturaTestUpload.mp4');
	$index = $index % count($videoAsset);
	$uploadTokenObj = new KalturaUploadToken();
	$uploadTokenObj->fileName = $videoAsset[$index];
	$uploadToken = $client->uploadToken->add($uploadTokenObj);
	$fileData = $videoAsset[$index];
	$result = $client->uploadToken->upload($uploadToken->id,$fileData ,false,true);
	$resource = new KalturaUploadedFileTokenResource();
	$resource->token = $uploadToken->id;
	return $resource;
}

function isEntryReady($client,$id)
{
	if($id!=null)
	{
		try{
			$result = $client->baseEntry->get($id, null);
			if ($result->status == 2)
			return true;
		}
		catch(Exception $e)
		{
			return true;
		}
	}
	return false;
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
function helper_CreateAndAppend($clientMS, $clientServer, $liveEntry,  $flavorAssetIds, $duration, $i, $isLastChunk = false)
{
	info("Appending chunk [$i] for live entry [$liveEntry->id]");
	foreach ($flavorAssetIds as $flavorAsset )
	{
		$resource = helper_createVideoToken($clientMS,$i);
		$liveEntry = helper_appendRecording($clientMS, $liveEntry,  $flavorAsset->id, $resource, $duration , $isLastChunk);
		info ("Flavor asset".$flavorAsset->id);
	}
 return $liveEntry;
}

function helper_ValidateAppend($clientServer, $liveEntry)
{
	if($liveEntry->recordedEntryId!=null)
	{
		info("Waiting for replacing entry to be created live entry [$liveEntry->id] recorded entry [$liveEntry->recordedEntryId]");
		do 
		{
			$recordedEntry = $clientServer->baseEntry->get($liveEntry->recordedEntryId, null);
			info("Waiting! live entry [$liveEntry->id] recorded entry [$liveEntry->recordedEntryId]" .
			" replacing entry [$recordedEntry->replacingEntryId] and replcaed entry [$recordedEntry->replacedEntryId]");
			sleep(1);
			print (".");
		}
		while(!$recordedEntry->replacingEntryId);
	info("Found replacing entry Id [$recordedEntry->replacedEntryId] waiting for it to be ready");
		while($recordedEntry->replacingEntryId && isEntryReady($clientServer,$recordedEntry->replacingEntryId)!=true)
		{
			sleep(1);
			print (".");
		}
	}
	return $liveEntry;
}

function Test1_AppenRecording($clientMS, $clientServer)
{
	info("Create live entry");
	$liveEntry = helper_createLiveEntry($clientServer);
	info("Get the flavor assets for the uploaded entry [$liveEntry->id]");
	$response = helper_getEntryFlavorAssets($clientMS, $liveEntry->id);
	$flavorAssetIds = $response->objects;
	for ($i=0; $i<7 ; $i++)
	{
		helper_CreateAndAppend($clientMS, $clientServer, $liveEntry, $flavorAssetIds, 4, $i);
	}
	$liveEntry = helper_CreateAndAppend($clientMS, $clientServer, $liveEntry, $flavorAssetIds, 4, $i, true);
	return $liveEntry;
}

function Test2_AppenRecordingandValidate($clientMS, $clientServer)
{
	info("Create live entry");
	$liveEntry = helper_createLiveEntry($clientServer);
	info("Get the flavor assets for the uploaded entry [$liveEntry->id]");
	$flavorAssetIds = helper_getEntryFlavorAssets($clientMS, $liveEntry->id);
	$liveEntry = helper_CreateAndAppend($clientMS, $clientServer, $liveEntry, $flavorAssetIds->objects, 4, 0);
	for ($i=1; $i<7 ; $i++)
	{
		$liveEntry =  helper_CreateAndAppend($clientMS, $clientServer, $liveEntry, $flavorAssetIds->objects, 4, $i);
        helper_ValidateAppend($clientServer,$liveEntry);
	}
	
	$liveEntry =  helper_CreateAndAppend($clientMS, $clientServer, $liveEntry, $flavorAssetIds->objects, 4, $i ,true);
	return $liveEntry;
}



function main($dc,$partnerId,$adminSecret,$mediaServerSecret)
{
	warning("This test require admin secret and media server secret (partner -5)");
	$clientAdmin = startKalturaSession($partnerId,$adminSecret,$dc);
	$clientMediaServer = startKalturaSession(-5,$mediaServerSecret,$dc);
	Test1_AppenRecording($clientMediaServer, $clientAdmin);
	$entry = Test2_AppenRecordingandValidate($clientMediaServer, $clientAdmin);
	$result = $clientAdmin->baseEntry->get($entry->recordedEntryId, null);
	info("Final duration is [$result->duration]");
	return 0;
}

goMain();
