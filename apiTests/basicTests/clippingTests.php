<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');
require_once('/opt/kaltura/app/infra/general/KCurlWrapper.class.php');

function InitData($client)
{
	info('start ' . __FUNCTION__);
	$fileNameMp4 = dirname ( __FILE__ ).'/../../resources/youtubeDistribTestRaw.mp4';
	$fileNameSRT = '/../../resources/KalturaTestClipping.srt';
	$entryId = createEntryWithCaptions($client, $fileNameSRT, $fileNameMp4);
	waitForEntry($client, $entryId);
	info('end ' . __FUNCTION__);
	return $entryId;
}

/**
 * @param KalturaClient $client
 * @param string $name
 * @param KalturaMediaType $mediaType
 * @return mixed
 */
function helper_CreateEmptyMedia($client, $name, $mediaType)
{
	info('start ' . __FUNCTION__);
	$mediaEntry = new KalturaMediaEntry();
	$mediaEntry->name = $name;
	$mediaEntry->mediaType = $mediaType;
	$emptyMedia = $client->media->add($mediaEntry);
	info("entry " . $emptyMedia->id . " was created");
	info('end ' . __FUNCTION__);
	return $emptyMedia;
}

/*
 * @param KalturaClient $client
 * @param string $captionId
 */
function helper_GetCaption($captionsPlugin, $captionId)
{
	$captionUrl = $captionsPlugin->captionAsset->serve($captionId);
	return KCurlWrapper::getContent($captionUrl);
}

function helper_GetClippingOperationAttribute($originalEntryId, $offset, $duration)
{
	$clipAttribute = new KalturaClipAttributes();
	$clipAttribute->offset = $offset;
	$clipAttribute->duration = $duration;
	$contentResource = new KalturaEntryResource();
	$contentResource->entryId = $originalEntryId;
	$operationResource = new KalturaOperationResource();
	$operationResource->resource = $contentResource;
	$operationResource->operationAttributes = array($clipAttribute);
	return $operationResource;
}

/**
 * @param KalturaClient $client
 * @param string $originalEntryId
 * @return int passed/failed
 */
function testClippingVideoAlsoClipCaptions($client, $originalEntryId)
{
	try {
		info('start ' . __FUNCTION__);
		$emptyMediaId = helper_CreateEmptyMedia($client, "testClipping", KalturaMediaType::VIDEO)->id;
		$operationResource = helper_GetClippingOperationAttribute($originalEntryId,30 * 1000, 3 * 60 * 1000 );
		$client->media->updateContent($emptyMediaId, $operationResource);
		waitForEntry($client, $emptyMediaId);
		$assetFilter = new KalturaAssetFilter();
		$assetFilter->entryIdEqual = $emptyMediaId;
		$captionsPlugin = KalturaCaptionClientPlugin::get($client);
		$captionAssetListResponse = $captionsPlugin->captionAsset->listAction($assetFilter);
		if (count($captionAssetListResponse->objects) != 1) {
			return (fail(__FUNCTION__ . " Retrieved caption for the clipped entry should only return 1 caption\n"));
		}

		$srt = helper_GetCaption($captionsPlugin, $captionAssetListResponse->objects[0]->id);
		$pos0 = strpos($srt, 'This is index 0');
		$pos8 = strpos($srt, 'This is index 8');
		$pos1 = strpos($srt, 'This is index 1');
		$pos7 = strpos($srt, 'This is index 7');
		if($pos0 !== false || $pos8 !== false || $pos1 === false || $pos7 === false)
		{
			return (fail(__FUNCTION__ . " Caption wasn't clipped the way expected\n"));
		}

		return (success(__FUNCTION__ . " test finished successfully\n"));
	}
	catch (KalturaClientException $e)
	{
		$msg = $e->getMessage();
		$functionName = __FUNCTION__;
		fail("[$functionName] failed because of : [$msg]\n");
	}
}

/**
 * @param KalturaClient $client
 * @return int passed/failed
 */
function testCaptionClippingOnNonReadyVideo($client)
{
	try {
		info('start ' . __FUNCTION__);
		$fileNameMp4 = dirname ( __FILE__ ).'/../../resources/youtubeDistribTestRaw.mp4';
		$fileNameSRT = '/../../resources/KalturaTestClipping.srt';
		$originalEntryId = createEntryWithCaptions($client, $fileNameSRT, $fileNameMp4);
		$emptyMediaId = helper_CreateEmptyMedia($client, "testClipping", KalturaMediaType::VIDEO)->id;
		$operationResource = helper_GetClippingOperationAttribute($originalEntryId,30 * 1000, 3 * 60 * 1000 );
		$client->media->updateContent($emptyMediaId, $operationResource);
		waitForEntry($client, $emptyMediaId);
		$assetFilter = new KalturaAssetFilter();
		$assetFilter->entryIdEqual = $emptyMediaId;
		$captionsPlugin = KalturaCaptionClientPlugin::get($client);
		$captionAssetListResponse = $captionsPlugin->captionAsset->listAction($assetFilter);
		if (count($captionAssetListResponse->objects) != 1) {
			return (fail(__FUNCTION__ . " Retrieved caption for the clipped entry should only return 1 caption\n"));
		}

		$srt = helper_GetCaption($captionsPlugin, $captionAssetListResponse->objects[0]->id);
		$pos0 = strpos($srt, 'This is index 0');
		$pos8 = strpos($srt, 'This is index 8');
		$pos1 = strpos($srt, 'This is index 1');
		$pos7 = strpos($srt, 'This is index 7');
		if($pos0 !== false || $pos8 !== false || $pos1 === false || $pos7 === false)
		{
			return (fail(__FUNCTION__ . " Caption wasn't clipped the way expected\n"));
		}

		return (success(__FUNCTION__ . " test finished successfully\n"));
	}
	catch (KalturaClientException $e)
	{
		$msg = $e->getMessage();
		$functionName = __FUNCTION__;
		fail("[$functionName] failed because of : [$msg]\n");
	}
}

function main($dc,$partnerId, $adminSecret, $userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$originalEntryId = InitData($client);
	$ret = testClippingVideoAlsoClipCaptions($client, $originalEntryId);
	$ret += testCaptionClippingOnNonReadyVideo($client);
	return $ret;
}

goMain();