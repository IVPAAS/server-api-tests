<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');

function helper_createEntryWithCaptions( $client, $captionsPath)
{
	$entry = addEntry($client,__FUNCTION__);
	info ("entryId: ". $entry->id);
	//add transcript
	$caption = $client->captionAsset->add($entry->id, new KalturaCaptionAsset());
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

	return $entry->id;
}

function testEntriesWithCaptionItem( $client )
{
	$entryId = helper_createEntryWithCaptions( $client, '/../../resources/KalturaTestCaption.srt');

	$captionItemFilter = new KalturaCaptionAssetItemFilter();
	$captionMapping = new KalturaResponseProfileMapping();
	$captionMapping->filterProperty = 'entryIdEqual';
	$captionMapping->parentProperty = 'id';

	$captionPager = new KalturaFilterPager();

	$captionResponseProfile = new KalturaDetachedResponseProfile();
	$captionResponseProfile->systemName = uniqid('test_');
	$captionResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
	$captionResponseProfile->fields = 'content';
	$captionResponseProfile->filter = $captionItemFilter;
	$captionResponseProfile->pager = $captionPager;
	$captionResponseProfile->mappings = array($captionMapping);

	$responseProfile = new KalturaResponseProfile();
	$responseProfile->systemName = uniqid('test_');
	$responseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
	$responseProfile->fields = 'id,name';
	$responseProfile->relatedProfiles = array($captionResponseProfile);

	$responseProfile = $client->responseProfile->add($responseProfile);

	$nestedResponseProfile = new KalturaResponseProfileHolder();
	$nestedResponseProfile->id = $responseProfile->id;

	$client->setResponseProfile($nestedResponseProfile);


	//wait for index sphinx
	$retries = 24;
	$relatedList = null;
	for ($i=0; $i<$retries; $i++) {
		info("sleep 20 seconds");
		for ($j = 0; $j < 20; $j++) {
			sleep(1);
			print(".");
		}

		$entry = $client->baseEntry->get($entryId);
		$relatedList = $entry->relatedObjects[0];
		if ($relatedList instanceof KalturaCaptionAssetItemListResponse) {
			if ($relatedList->totalCount > 0 && ($relatedList->objects[0] instanceof KalturaCaptionAssetItem))
				return success(__FUNCTION__);
		}
	}
	return fail ( __FUNCTION__ .($relatedList != null  ? $relatedList->totalCount : 0) );
}

function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$ret=testEntriesWithCaptionItem( $client );
	return ($ret);
}

goMain();