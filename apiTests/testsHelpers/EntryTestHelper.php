<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');

const ENTRY_READY_TIMEOUT = 240;

function addEntry($client,$name,$mediaType=KalturaMediaType::VIDEO, $profileId = null, $userId='', $description = 'test media description', $tags = 'test tag', $referenceId = 'testRefID', $categories = null)
{
	$entry                                  = new KalturaMediaEntry();
	$type                                   = KalturaEntryType::MEDIA_CLIP;
	$entry->name                            = $name;
	$entry->mediaType                       = $mediaType;
	if ($profileId != null)
		$entry->conversionProfileId			= $profileId;
	$entry->userId                          = $userId;
	$entry->description                     = $description;
	$entry->tags                            = $tags;
	$entry->referenceId                     = $referenceId;
	$entry->categories                      = $categories;
	$result                                 = $client->baseEntry->add($entry, $type);
	//print ("\nAdd entry ID:".$result->id);
	return $result;
}

function addCategoryEntry($client, $categoryId, $entryId)
{
	//create a category entry
	$categoryEntry = new KalturaCategoryEntry();
	$categoryEntry->categoryId = $categoryId;
	$categoryEntry->entryId = $entryId;
	$result = $newCategoryEntry = $client->categoryEntry->add($categoryEntry);
	return $result;
}

function createMediaEntry($client, $refEntry = null, $entryName = null)
{
	info("Create entry and upload content");
	if($entryName === null)
	{
		$entryName = 'defaultName';
	}

	if ($refEntry)
	{
		$MediaEntry = createEntryWithReferenceIdAndUploaDmp4Content($client, $entryName, $refEntry->id, 'test');
	}
	else
	{
		$MediaEntry = createEntryAndUploaDmp4Content($client, $entryName, 'test');
	}

	waitForEntry($client, $MediaEntry->id);

	return $MediaEntry;
}

function createEntryAndUploaDmp4Content($client, $testName, $userId=null)
{
	if($testName == 'youTubeDistributionTest')
		cutRandomPartFromVideo(dirname ( __FILE__ ).'/../../resources/youtubeDistribTestRaw.mp4',dirname ( __FILE__ ).'/../../resources/youtubeDistribTestRand.mp4',3);

	$FILE_NAME_MP4 = ($testName == 'youTubeDistributionTest') ? dirname ( __FILE__ ).'/../../resources/youtubeDistribTestRand.mp4' : dirname ( __FILE__ ).'/../../resources/KalturaTestUpload.mp4';
	if($testName == 'youTubeDistributionTest')
	{
		$description = 'This is a test description with html tags and links .<br><br>Here is a &nbsp link: <a target="_blank" rel="nofollow noopener noreferrer" href="https://www.youtube.com/watch?v=gLqalzGiqPk">https://www.youtube.com/watch?v=gLqalzGiqPk</a>';
		$entry = addEntry($client, $testName, KalturaMediaType::VIDEO, null, $userId, $description);
	}
	else
		$entry = addEntry($client, $testName, KalturaMediaType::VIDEO, null, $userId);
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

function createEntryWithReferenceIdAndUploaDmp4Content($client, $testName, $refId=null, $userId=null)
{
	if($testName == 'youTubeDistributionTest')
		cutRandomPartFromVideo(dirname ( __FILE__ ).'/../../resources/youtubeDistribTestRaw.mp4',dirname ( __FILE__ ).'/../../resources/youtubeDistribTestRand.mp4',3);

	$FILE_NAME_MP4 = ($testName == 'youTubeDistributionTest') ? dirname ( __FILE__ ).'/../../resources/youtubeDistribTestRand.mp4' : dirname ( __FILE__ ).'/../../resources/KalturaTestUpload.mp4';
	$entry = addEntry($client, $testName, KalturaMediaType::VIDEO, null, $userId, 'test media description', 'test tag', $refId);
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

	info("Entry $entryId ready!");
}