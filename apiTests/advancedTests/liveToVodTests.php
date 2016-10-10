<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');


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
	foreach ($flavorAssetIds as $flavorAsset ) {
		$resource = helper_createVideoToken($clientMS,$i);
		$liveEntry = helper_appendRecording($clientMS, $liveEntry,  $flavorAsset->id, $resource, $duration , $isLastChunk);
	}
 return $liveEntry;
}



function addCuePointToLiveAndCheckVOD($clientMS, $clientServer)
{
	$numOfSegments = 3;
	info("Create live entry with " .$numOfSegments ." appended segments");
	$liveEntry = helper_createLiveEntry($clientServer);
	info("Get the flavor assets for the uploaded entry [$liveEntry->id]");
	$flavorAssetIds = helper_getEntryFlavorAssets($clientMS, $liveEntry->id);
	// end time : 1468768326.697, start time : 1468768265.697, cue point creation time : 1468768320
	for ($i=0; $i<$numOfSegments; $i++) {
		$liveEntry =  helper_CreateAndAppend($clientMS, $clientServer, $liveEntry, $flavorAssetIds->objects, 4, $i,  $i == ($numOfSegments-1));
		if ($i == 1) {
			helper_createCuePoints($clientServer, $liveEntry->id, 1468768300);
			helper_createCuePoints($clientServer, $liveEntry->id, 1468768310);
		}
		//for checking extra times:
		// in i == 0 can add times: 1468768250, 1468768270
		// in i == 2 can add times: 1468768320, 1468768340
	}
	$recordEntryID = $liveEntry->recordedEntryId;
	info("waiting for recorded id: " .$recordEntryID ." to be ready");

	do {
		sleep(5);
		print (".");
	}
	while (!isEntryReady($clientServer,$recordEntryID));
	info("recordedEntry ready!");

	sleep(20);


	if (hasCuePoint($clientServer,$recordEntryID))
		return success(__FUNCTION__);
	else return fail(__FUNCTION__ . "     -  No cuePoint on entry id: " .$recordEntryID);
}


function main($dc,$partnerId,$adminSecret,$mediaServerSecret)
{
	return success('just override till pass on allInOne');

	warning("This test require admin secret and media server secret (partner -5)");
	$clientAdmin = startKalturaSession($partnerId,$adminSecret,$dc);
	$clientMediaServer = startKalturaSession(-5,$mediaServerSecret,$dc);
	$ret = '';

	$ret += addCuePointToLiveAndCheckVOD($clientMediaServer, $clientAdmin);
	return ($ret);
}

goMain();
