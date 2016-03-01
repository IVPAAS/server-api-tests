<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
require_once(dirname(__FILE__).'/../testsHelpers/apiTestHelper.php');

function helper_createEntryWithCuePoint($client)
{
	//create a new entry
	$entry = addEntry($client,__FUNCTION__);
	info ("entry ". $entry->id . " was created");

	$adCuePoint = new KalturaAdCuePoint();
	$adCuePoint->entryId = $entry->id;
	$adCuePoint = $client->cuePoint->add($adCuePoint);
	info("ad cue point ". $adCuePoint->id ." was added");

	$codeCuePoint = new KalturaCodeCuePoint();
	$codeCuePoint->entryId = $entry->id;
	$codeCuePoint->code = "test";
	$codeCuePoint = $client->cuePoint->add($codeCuePoint);
	info("code cue point ". $codeCuePoint->id ." was added");

	$annotation = new KalturaAnnotation();
	$annotation->entryId = $entry->id;
	$annotation = $client->cuePoint->add($annotation);
	info("annotation cue point ". $annotation->id ." was added");

	$thumbCuePoint = new KalturaThumbCuePoint();
	$thumbCuePoint->entryId = $entry->id;
	$thumbCuePoint = $client->cuePoint->add($thumbCuePoint);
	$thumbAsset = new KalturaTimedThumbAsset();
	$thumbAsset->cuePointId = $thumbCuePoint->id;
	$thumbAsset = $client->thumbAsset->add($entry->id, $thumbAsset);
	info("thumb cue point ". $thumbCuePoint->id ." was added, thumb asset is " . $thumbAsset->id);


	return $entry;

}

function helper_cloneAndGetList($client, $itemType)
{
	$entry = helper_createEntryWithCuePoint($client);

	$cloneOptions = array();
	$cloneOptions[0] = new KalturaBaseEntryCloneOptionComponent();
	$cloneOptions[0]->itemType = $itemType;
	$cloneOptions[0]->rule = KalturaCloneComponentSelectorType::INCLUDE_COMPONENT;
	$newEntry = $client->baseEntry->cloneAction($entry->id, $cloneOptions);

	$filter = new KalturaCuePointFilter();
	$filter->entryIdEqual = $newEntry->id;
	$result = $client->cuePoint->listAction($filter, null);

	return $result;
}

function Test1_CloneEntryAdCuePoint($client)
{
	$result = helper_cloneAndGetList($client, KalturaBaseEntryCloneOptions::AD_CUE_POINTS);
    if ($result->totalCount == 1 && $result->objects[0] instanceof KalturaAdCuePoint)
    {
        return success(__FUNCTION__);
    }
    return fail(__FUNCTION__);
}

function Test2_CloneEntryCodeCuePoint($client)
{
	$result = helper_cloneAndGetList($client, KalturaBaseEntryCloneOptions::CODE_CUE_POINTS);
    if ($result->totalCount == 1 && $result->objects[0] instanceof KalturaCodeCuePoint)
    {
        return success(__FUNCTION__);
    }
    return fail(__FUNCTION__);
}

function Test3_CloneEntryAnnotationCuePoint($client)
{
	$result = helper_cloneAndGetList($client, KalturaBaseEntryCloneOptions::ANNOTATION_CUE_POINTS);
    if ($result->totalCount == 1 && $result->objects[0] instanceof KalturaAnnotation)
    {
        return success(__FUNCTION__);
    }
    return fail(__FUNCTION__);
}

function Test4_CloneEntryThumbCuePoint($client)
{
	$result = helper_cloneAndGetList($client, KalturaBaseEntryCloneOptions::THUMB_CUE_POINTS);
    if ($result->totalCount == 1 && $result->objects[0] instanceof KalturaThumbCuePoint
	&& !is_null($result->objects[0]->assetId))
    {
        return success(__FUNCTION__);
    }
    return fail(__FUNCTION__);
}


function main($dc,$partnerId,$adminSecret,$userSecret)
{
  $client = startKalturaSession($partnerId,$adminSecret,$dc);
  $ret  = Test1_CloneEntryAdCuePoint($client);
  $ret  += Test2_CloneEntryCodeCuePoint($client);
  $ret  += Test3_CloneEntryAnnotationCuePoint($client);
  $ret  += Test4_CloneEntryThumbCuePoint($client);

  return ($ret);
}

goMain();
