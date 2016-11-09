<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');

function helper_createEntryWithCuePoint($client)
{
	$entry = helper_createEntryAndUploaDmp4Content($client,__FUNCTION__);
	info ("entry ". $entry->id . " was created");
	waitForEntry($client, $entry->id);

	$thumbCuePoint = new KalturaThumbCuePoint();
	$thumbCuePoint->entryId = $entry->id;
	$thumbCuePoint = $client->cuePoint->add($thumbCuePoint);

	$thumbAsset = new KalturaTimedThumbAsset();
	$thumbAsset->cuePointId = $thumbCuePoint->id;
	$thumbAsset = $client->thumbAsset->add($entry->id, $thumbAsset);
	info("thumb cue point ". $thumbCuePoint->id ." was added, thumb asset is " . $thumbAsset->id);

	return $entry;

}

function getCuePointOnEntry($client, $entryId)
{
	$filter = new KalturaCuePointFilter();
	$filter->entryIdEqual = $entryId;
	$result = $client->cuePoint->listAction($filter, null);

	info("got TotalCunt of cue points as $result->totalCount on entry $entryId");
	if ($result->totalCount > 0)
		info("first cue point id is " .$result->objects[0]->id. " and her asset is " .$result->objects[0]->assetId);
	return $result;
}

function cloneEntryWithOption($client, $entryId)
{
	$cloneOptions = array();
	$cloneOptions[0] = new KalturaBaseEntryCloneOptionComponent();
	$cloneOptions[0]->itemType = KalturaBaseEntryCloneOptions::THUMB_CUE_POINTS;
	$cloneOptions[0]->rule = KalturaCloneComponentSelectorType::INCLUDE_COMPONENT;

	info ("cloning $entryId with option of ThumbCuePoint");
	$newEntry = $client->baseEntry->cloneAction($entryId, $cloneOptions);
	warning ("created new entry [$newEntry->id] ");

	getCuePointOnEntry($client, $newEntry->id);
	return $newEntry;
}

function deleteEntry($client, $entryId)
{
	warning("deleting entryID [$entryId]");
	$client->baseEntry->delete($entryId);
}



function Test_CloneEntryWithCuePointAndDelete($client)
{
	$entry = helper_createEntryWithCuePoint($client);
	$newEntry = cloneEntryWithOption($client, $entry->id);
	deleteEntry($client, $newEntry->id);

	info("check original entryId $entry->id");
	$result = getCuePointOnEntry($client, $entry->id);

	deleteEntry($client, $entry->id);

    if ($result->totalCount == 1 && $result->objects[0] instanceof KalturaThumbCuePoint
	&& !is_null($result->objects[0]->assetId) && $result->objects[0]->assetId)
    {
        return success(__FUNCTION__);
    }
    return fail(__FUNCTION__);
}


function main($dc,$partnerId,$adminSecret,$userSecret)
{
  $client = startKalturaSession($partnerId,$adminSecret,$dc);
  $ret  = Test_CloneEntryWithCuePointAndDelete($client);

  return ($ret);
}

goMain();
