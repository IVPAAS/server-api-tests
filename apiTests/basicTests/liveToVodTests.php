<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__).'/../testsHelpers/apiTestHelper.php');


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

function helper_createCuePoints($client, $EntryId = null, $time = 0)
{
	// end time : 1468768326.697, start time : 1468768265.697, cue point creation time : 1468768320
	// KalturaThumbCuePoint and KalturaCodeCuePoint has implementation of copyFromLiveToVodEntry

	$CcuePoint = new KalturaCodeCuePoint();
	$CcuePoint->code = "bla_bla";
	$CcuePoint->entryId = $EntryId;
	$CcuePoint->partnerData  = "test_PD";

//	$TcuePoint = new KalturaThumbCuePoint();
//	$TcuePoint->entryId = $EntryId;
//	$TcuePoint->partnerData  = "test_PD_2";

	$AcuePoint = new KalturaAnnotation();
	$AcuePoint->entryId = $EntryId;
	$AcuePoint->partnerData  = "test_PD_3";
	// enforce creatAt to be past time
	$str = "date +%s -s @" .$time;
	exec($str);
	//exec("date +%s -s @1468768320");
	for ($i = 0; $i < 1; $i++) {
		$code = "test code cue num " .$i;
		$CcuePoint->code = $code;
		$result = $client->cuePoint->add($CcuePoint);
	}
	//$result = $client->cuePoint->add($TcuePoint);
	$result = $client->cuePoint->add($AcuePoint);
	info("Add Cue point id: " .$result->id ." on entry: " .$EntryId . " on time: " .$result->createdAt );
	exec("ntpdate -u pool.ntp.org");
	return $result;
}



function helper_createVideoToken($client,$index=0)
{
	//$videoAsset = array(dirname(__FILE__).'/../../resources/1.mp4',dirname(__FILE__).'/../../resources/2.mp4',dirname(__FILE__).'/../../resources/3.mp4',dirname(__FILE__).'/../../resources/4.mp4',dirname(__FILE__).'/../../resources/5.mp4',dirname(__FILE__).'/../../resources/6.mp4');
	$videoAsset = array(dirname(__FILE__).'/../../resources/part_4.mp4',dirname(__FILE__).'/../../resources/part_4.mp4',dirname(__FILE__).'/../../resources/part_4.mp4', dirname(__FILE__).'/../../resources/part_4.mp4');
	// duration of part_4.mp4 is 00:01:02.96
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
/* from apiTestHelper
function isEntryReady($client,$id) {
	if($id!=null) {
		try {
			$result = $client->baseEntry->get($id, null);
			if ($result->status == 2)
				return true;
		} catch(Exception $e) {return true;}
	}
	return false;
}*/
function getCuePoints($client,$entryId, $pageSize = 100, $pageIndex = 1) {
	$filter = new KalturaCuePointFilter();
	$filter->entryIdEqual = $entryId;
	$filter->statusIn = 1;
	$filter->createdAtLessThanOrEqual = time();
	
	$pager = new KalturaFilterPager();
	$pager->pageSize = $pageSize;
	$pager->pageIndex = $pageIndex++;
	$result = $client->cuePoint->listAction($filter, $pager);
	return $result;
}

function hasCuePoint($client,$id) {
	$result = getCuePoints($client,$id);
	if (!$result)
		return false;
	elseif ($result->totalCount < 1)
		return false;
	else return true;
}

function helper_appendRecording($client, $entry, $flavorAssetId, $resource) {
	return $client->liveStream->appendRecording($entry->id, $flavorAssetId, 0 , $resource, $entry->duration , false);
}

function helper_getEntryFlavorAssets($client, $entryId) {
	$filter = new KalturaAssetFilter();
	$filter->entryIdEqual = $entryId;
	return $client->flavorAsset->listAction($filter);
}

function helper_CreateAndAppend($clientMS, $clientServer, $liveEntry,  $flavorAssetIds, $duration, $i, $isLastChunk = false) {
	//info("Appending chunk [$i] for live entry [$liveEntry->id]");
	foreach ($flavorAssetIds as $flavorAsset ) {
		$resource = helper_createVideoToken($clientMS,$i);
		$liveEntry = helper_appendRecording($clientMS, $liveEntry,  $flavorAsset->id, $resource, $duration , $isLastChunk);
		//info ("Flavor asset ".$flavorAsset->id);
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
	$numOfSegments = 3;
	info("Create live entry");
	$liveEntry = helper_createLiveEntry($clientServer);
	info("Get the flavor assets for the uploaded entry [$liveEntry->id]");
	$response = helper_getEntryFlavorAssets($clientMS, $liveEntry->id);
	$flavorAssetIds = $response->objects;
	for ($i=0; $i<$numOfSegments ; $i++)
	{
		helper_CreateAndAppend($clientMS, $clientServer, $liveEntry, $flavorAssetIds, 4, $i, $i == ($numOfSegments-1));
	}
	return success(__FUNCTION__);
	//fail(__FUNCTION__ . "     -  Error: no such a user with id: " .$userId);
	//return $liveEntry;
}

function Test2_AppenRecordingandValidate($clientMS, $clientServer)
{
	info("Create live entry");
	$liveEntry = helper_createLiveEntry($clientServer);
	info("Get the flavor assets for the uploaded entry [$liveEntry->id]");
	$flavorAssetIds = helper_getEntryFlavorAssets($clientMS, $liveEntry->id);
	for ($i=0; $i<8 ; $i++)
	{
		$liveEntry =  helper_CreateAndAppend($clientMS, $clientServer, $liveEntry, $flavorAssetIds->objects, 4, $i,  $i == 7);
        if ($i != 7 && $i == 0) helper_ValidateAppend($clientServer,$liveEntry);
	}
	return $liveEntry;
}

function Test3_AddCuePointToLive($clientMS, $clientServer)
{
	$numOfSegments = 3;
	info("Create live entry with " .$numOfSegments ." appended segments");
	$liveEntry = helper_createLiveEntry($clientServer);
	info("Get the flavor assets for the uploaded entry [$liveEntry->id]");
	$flavorAssetIds = helper_getEntryFlavorAssets($clientMS, $liveEntry->id);
	// end time : 1468768326.697, start time : 1468768265.697, cue point creation time : 1468768320
	for ($i=0; $i<$numOfSegments; $i++) {
		$liveEntry =  helper_CreateAndAppend($clientMS, $clientServer, $liveEntry, $flavorAssetIds->objects, 4, $i,  $i == ($numOfSegments-1));
		if ($i == 0) {
			//helper_createCuePoints($clientServer, $liveEntry->id, 1468768250);
			//helper_createCuePoints($clientServer, $liveEntry->id, 1468768270);
		}
		if ($i == 1) {
			helper_createCuePoints($clientServer, $liveEntry->id, 1468768300);
			helper_createCuePoints($clientServer, $liveEntry->id, 1468768310);
		}
		if ($i == 2) {
			//helper_createCuePoints($clientServer, $liveEntry->id, 1468768320);
			//helper_createCuePoints($clientServer, $liveEntry->id, 1468768340);
		}
	}
	$recordEntryID = $liveEntry->recordedEntryId;
	info("waiting for recorded id: " .$recordEntryID ." to be ready");

	do sleep(10);
	while (!isEntryReady($clientServer,$recordEntryID));
	info("recordedEntry ready!");

	sleep(20);


	if (hasCuePoint($clientServer,$recordEntryID))
		return success(__FUNCTION__);
	else return fail(__FUNCTION__ . "     -  No cuePoint on entry id: " .$recordEntryID);
}


function main($dc,$partnerId,$adminSecret,$mediaServerSecret)
{
	warning("This test require admin secret and media server secret (partner -5)");
	$clientAdmin = startKalturaSession($partnerId,$adminSecret,$dc);
	$clientMediaServer = startKalturaSession(-5,$mediaServerSecret,$dc);
	$ret = '';


	$ret += Test3_AddCuePointToLive($clientMediaServer, $clientAdmin);
	return ($ret);




	//$ret += Test1_AppenRecording($clientMediaServer, $clientAdmin);
	//$entry = Test2_AppenRecordingandValidate($clientMediaServer, $clientAdmin);
	//$result = $clientAdmin->baseEntry->get($entry->recordedEntryId, null);
	//info("Final duration is [$result->duration]");
}

goMain();
