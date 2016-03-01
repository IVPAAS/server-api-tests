<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
require_once(dirname(__FILE__).'/../testsHelpers/apiTestHelper.php');

function helper_createEntryWithTranscript( $client, $transcriptPath)
{
	$entryId = helper_createEntryAndUploadContent($client,__FUNCTION__);

	//add transcript
	$transcript = $client->attachmentAsset->add($entryId, new KalturaTranscriptAsset());
	$NAME = dirname ( __FILE__ ). $transcriptPath;
	$uploadTokenObj = new KalturaUploadToken();
	$uploadTokenObj->fileName = $NAME;
	$uploadToken = $client->uploadToken->add($uploadTokenObj);
	$fileData = $NAME;
	$client->uploadToken->upload($uploadToken->id,$fileData ,null,null,null);
	$resource = new KalturaUploadedFileTokenResource();
	$resource->token = $uploadToken->id;
	$client->attachmentAsset->setContent($transcript->id, $resource);

	info ("transcript asset: ". $transcript->id);

	return $entryId;
}

function helper_createEntryWithCaptions( $client, $captionsPath)
{
	$entryId = helper_createEntryAndUploadContent($client,__FUNCTION__);

	//add transcript
	$caption = $client->captionAsset->add($entryId, new KalturaCaptionAsset());
	$NAME = dirname ( __FILE__ ). $captionsPath;
	$uploadTokenObj = new KalturaUploadToken();
	$uploadTokenObj->fileName = $NAME;
	$uploadToken = $client->uploadToken->add($uploadTokenObj);
	$fileData = $NAME;
	$client->uploadToken->upload($uploadToken->id,$fileData ,null,null,null);
	$resource = new KalturaUploadedFileTokenResource();
	$resource->token = $uploadToken->id;
	$client->captionAsset->setContent($caption->id, $resource);

	info ("caption asset: ". $caption->id);

	return $entryId;
}

function helper_createEntryAndUploadContent($client, $entryName)
{
	$FILE_NAME_MP4 = dirname ( __FILE__ ).'/../../resources/KalturaTestUpload.mp4';
	$entry = addEntry($client,$entryName);
	$uploadTokenObj = new KalturaUploadToken();
	$uploadTokenObj->fileName = $FILE_NAME_MP4;
	$uploadToken = $client->uploadToken->add($uploadTokenObj);
	$fileData = $FILE_NAME_MP4;
	$result = $client->uploadToken->upload($uploadToken->id,$fileData ,null,null,null);
	$resource = new KalturaUploadedFileTokenResource();
	$resource->token = $uploadToken->id;
	$result = $client->baseEntry->addcontent($entry->id, $resource);
	info ("entry ". $entry->id . " was created");
	return $result->id;
}

function helper_validateEntryList( $client, $filter, $goodEntries, $badEntries )
{
	$totalCreated = array_merge($goodEntries, $badEntries);
	info("wait for entries to be ready");
	while(true)
	{
		$readyCount = 0;
		foreach ( $totalCreated as $entryId)
		{
			if ( isEntryReady($client, $entryId) )
				$readyCount++;
		}
		if ( $readyCount == count($totalCreated) )
			break;

		sleep(1);
		print (".");
	}

	$entriesList = $client->baseEntry->listAction($filter);
	$fail = false;
	$matchCount = 0;
	foreach ( $entriesList->objects as $entry )
	{
		if ( in_array($entry->id, $badEntries))
		{
			$fail = true;
			break;
		}

		if ( in_array($entry->id, $goodEntries))
			$matchCount++;

		info ("matching entry id: ". $entry->id . " name:" . $entry->name);
	}

	if ( $fail || $matchCount!=count($goodEntries) )
		return false;

	return true;

}

function Test1_BaseEntryList( $client, $keyWord )
{
	$goodEntries = array();
	$badEntries = array();

	//add entries
	$goodEntries[] = helper_createEntryAndUploadContent($client,"test " . $keyWord);
	$badEntries[] = helper_createEntryAndUploadContent($client,"test no keyword");

	$filter = new KalturaBaseEntryFilter();
	//entry metadata
	$filter->freeText = $keyWord;

	if ( !helper_validateEntryList($client, $filter, $goodEntries, $badEntries))
		return fail(__FUNCTION__);

	return success(__FUNCTION__);
}

function Test2_EntryTranscriptSearchFilter( $client, $keyWord )
{
	$goodEntries = array();
	$badEntries = array();

	//entries with transcript
	$goodEntries[] = helper_createEntryWithTranscript( $client, '/../../resources/transcriptWithKeyword.txt');
	$badEntries[] = helper_createEntryWithTranscript( $client, '/../../resources/transcriptWithoutKeyword.txt');

	$filter = new KalturaBaseEntryFilter();
	$transcriptSearchItem = new KalturaEntryTranscriptAssetSearchItem();
	$transcriptSearchItem->contentLike = $keyWord;

	$filter->advancedSearch = $transcriptSearchItem;

	if ( !helper_validateEntryList($client, $filter, $goodEntries, $badEntries))
		return fail(__FUNCTION__);

	return success(__FUNCTION__);
}

function Test3_EntryCaptionSearchFilter( $client, $keyWord )
{
	$goodEntries = array();
	$badEntries = array();

	//entries with captions
	$goodEntries[] = helper_createEntryWithCaptions( $client, '/../../resources/KalturaTestCaptionWithKeyword.srt');
	$badEntries[] = helper_createEntryWithCaptions( $client, '/../../resources/KalturaTestCaption.srt');

	$filter = new KalturaBaseEntryFilter();
	$captionsSearchItem = new KalturaEntryCaptionAssetSearchItem();
	$captionsSearchItem->contentLike = $keyWord;

	$filter->advancedSearch = $captionsSearchItem;

	if ( !helper_validateEntryList($client, $filter, $goodEntries, $badEntries))
		return fail(__FUNCTION__);

	return success(__FUNCTION__);
}

function main($dc,$partnerId,$adminSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$keyWord = 'searchKeyWord';
	$ret = Test1_BaseEntryList($client, $keyWord);
	$ret  += Test2_EntryTranscriptSearchFilter($client, $keyWord);
	$ret  += Test3_EntryCaptionSearchFilter($client, $keyWord);

	return ($ret);
}

goMain();