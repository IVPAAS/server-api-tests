<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');

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

function createEntryWithCaptions( $client, $captionsPath, $clipFullPath)
{
	$entryId = createEntryAndUploadContent($client, __FUNCTION__, $clipFullPath);
	$caption = $client->captionAsset->add($entryId, new KalturaCaptionAsset());
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

function reateEntryWithTranscript( $client, $transcriptPath, $fileName)
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

function createEmptyEntry($client, $testName)
{
	$entry = addEntry($client, $testName);
	return $entry;
}