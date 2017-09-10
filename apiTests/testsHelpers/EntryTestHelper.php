<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');

const ENTRY_READY_TIMEOUT = 240;

function createEntryAndUploadContent($client, $entryName, $fileName, $referenceId='testRefID')
{
	$entry = addEntry($client, $entryName, KalturaMediaType::VIDEO, null, '', 'test media description', 'test tag', $referenceId);
	$uploadTokenObj = new KalturaUploadToken();
	$uploadTokenObj->fileName = $fileName;
	$uploadToken = $client->uploadToken->add($uploadTokenObj);
	$fileData = $fileName;
	$client->uploadToken->upload($uploadToken->id, $fileData, null, null, null);
	$resource = new KalturaUploadedFileTokenResource();
	$resource->token = $uploadToken->id;
	$result = $client->baseEntry->addcontent($entry->id, $resource);
	info("entry " . $entry->id . " was created");
	return $result->id;
}

function createEntryWithCaptions($client, $captionsPath, $clipFullPath, $language = KalturaLanguage::EN)
{
	$entryId = createEntryAndUploadContent($client, __FUNCTION__, $clipFullPath);
	$captionAsset =  new KalturaCaptionAsset();
	$captionAsset->language = $language;
	$caption = $client->captionAsset->add($entryId,$captionAsset);
	$NAME = dirname(__FILE__) . $captionsPath;
	$uploadTokenObj = new KalturaUploadToken();
	$uploadTokenObj->fileName = $NAME;
	$uploadToken = $client->uploadToken->add($uploadTokenObj);
	$fileData = $NAME;
	$client->uploadToken->upload($uploadToken->id, $fileData, null, null, null);
	$resource = new KalturaUploadedFileTokenResource();
	$resource->token = $uploadToken->id;
	$client->captionAsset->setContent($caption->id, $resource);
	info("caption asset: " . $caption->id);
	return $entryId;
}

function createEntryWithTranscript($client, $transcriptPath, $fileName)
{
	$entryId = createEntryAndUploadContent($client,__FUNCTION__, $fileName);

	//add transcript
	$transcript = $client->attachmentAsset->add($entryId, new KalturaTranscriptAsset());
	$transcriptFullPath = dirname ( __FILE__ ). $transcriptPath;
	$uploadTokenObj = new KalturaUploadToken();
	$uploadTokenObj->fileName = $transcriptFullPath;
	$uploadToken = $client->uploadToken->add($uploadTokenObj);
	$fileData = $transcriptFullPath;
	$client->uploadToken->upload($uploadToken->id,$fileData ,null,null,null);
	$resource = new KalturaUploadedFileTokenResource();
	$resource->token = $uploadToken->id;
	$client->attachmentAsset->setContent($transcript->id, $resource);

	info ("transcript asset: ". $transcript->id);

	return $entryId;
}

function createEmptyEntry($client, $entryName)
{
	$entry = addEntry($client, $entryName);
	return $entry;
}

function createEntryAndUploadJpgContent($client)
{
	$FILE_NAME_JPG = dirname ( __FILE__ ).'/../../resources/kalturaIcon.jpg';
	$entry = addEntry($client,__FUNCTION__,KalturaMediaType::IMAGE);
	$uploadTokenObj = new KalturaUploadToken();
	$uploadTokenObj->fileName = $FILE_NAME_JPG;
	$uploadToken = $client->uploadToken->add($uploadTokenObj);
	$fileData = $FILE_NAME_JPG;
	$result = $client->uploadToken->upload($uploadToken->id,$fileData ,null,null,null);
	$resource = new KalturaUploadedFileTokenResource();
	$resource->token = $uploadToken->id;
	$result = $client->baseEntry->addcontent($entry->id, $resource);
	return $result;
}

function waitForEntry($client, $entryId)
{
	info("Wait for entry to be ready id = $entryId");
	$counter = 0;
	while(isEntryReady($client,$entryId)!=true && $counter <= ENTRY_READY_TIMEOUT)
	{
		sleep(1);
		print (".");
	}

	if($counter > ENTRY_READY_TIMEOUT)
	{
		fail("Entry is not ready after more then ".ENTRY_READY_TIMEOUT." seconds");
		throw new KalturaClientException("Test failed");
	}

	info("Entry ready!");
}