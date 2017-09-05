<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');

function InitData($client)
{
	$fileNameMp4 = dirname ( __FILE__ ).'/../../resources/KalturaTestUpload.mp4';
	$fileNameSRT = '/../../resources/KalturaTestClipping.srt';
	return createEntryWithCaptions($client, $fileNameSRT, $fileNameMp4);
}

function helper_CreateEmptyMedia($client, $name, $mediaType)
{
	$mediaEntry = new KalturaMediaEntry();
	$mediaEntry->name = $name;
	$mediaEntry->mediaType = $mediaType;
	return $client->media->add($mediaEntry);
}

function testClippingVideoAlsoClipCaptions($client, $originalEntryId)
{
	info('start ' .  __FUNCTION__);
	helper_CreateEmptyMedia($client, "testClipping",KalturaMediaType::VIDEO);
	$clipAttribute = new KalturaClipAttribute();
	$clipAttribute->offset = 30 * 1000;//30 seconds
	$clipAttribute->duration = 3 * 60 * 1000;//3 minutes
	$contentResource = new KalturaContentResource();
	$contentResource->entryId=$originalEntryId;
	$operationResource = new KalturaOperationResource();
	$operationResource->resource=$contentResource;
	$operationResource->KalturaOperationAttributes=array($clipAttribute);
	$clippedResultEntryId = $client->media->updateContent('testID', $operationResource);
	$assetFilter = new KalturaAssetFilter();
	$assetFilter->entryIdEqual = $clippedResultEntryId;
	$captionAssetListResponse = $client->captionAsset->list($assetFilter);
	$captionId = $captionAssetListResponse->objects[0]->id;
	$captionUrl = $client->captionAsset->serve($captionId);
}

function main($dc,$partnerId, $adminSecret, $userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$originalEntryId = InitData($client);
	$ret = testClippingVideoAlsoClipCaptions($client, $originalEntryId);

	return $ret;
}

goMain();