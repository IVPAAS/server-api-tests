<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');

$metadataProfileId = null;

function createCodeCue($client, $entryId, $code="test", $tags=null)
{
	$cuepointPlugin = KalturaCuepointClientPlugin::get($client);
	$cuePoint = new KalturaCodeCuePoint();
	$cuePoint->tags = $tags;
	$cuePoint->code = $code;
	$cuePoint->entryId = $entryId;
	$res = $cuepointPlugin->cuePoint->add($cuePoint);
	global $cuePointId;
	$cuePointId = $res->id;
	return $res;
}

function createMetadataProfile($client) {
	$metadataProfile = new KalturaMetadataProfile();
	$metadataProfile->metadataObjectType = KalturaMetadataObjectType::CODE_CUE_POINT;
	$metadataProfile->name = 'TestMetadataProfile';
	$metadataPlugin = KalturaMetadataClientPlugin::get($client);
	$path = dirname ( __FILE__ ).'/../../resources/metadata1.xml';
	$xsdData = file_get_contents($path);
	$result = $metadataPlugin->metadataProfile->add($metadataProfile, $xsdData, null);
	return $result;
}

function addMetaData($client, $metadataProfileId, $objectId)
{
	$xmlData = "<metadata><FirstAttr>adding attr in meta</FirstAttr></metadata>";
	$metadataPlugin = KalturaMetadataClientPlugin::get($client);
	$result = $metadataPlugin->metadata->add($metadataProfileId, KalturaMetadataObjectType::CODE_CUE_POINT, $objectId, $xmlData);
	return $result;

}
function cloneCuepoint($client, $cuepointId, $dstEntryId)
{
	$cuepointPlugin = KalturaCuepointClientPlugin::get($client);
	$result = $cuepointPlugin->cuePoint->cloneAction($cuepointId, $dstEntryId);
	return $result;
}

function createCodeCuePointWithMetaData($client, $entryId)
{
	$codeCuePoint = createCodeCue($client, $entryId, 'testCode1');
	$metadataProfile = createMetadataProfile($client);
	info("created metadataProfile with ID $metadataProfile->id");
	addMetaData($client, $metadataProfile->id, $codeCuePoint->id);
	global $metadataProfileId;
	$metadataProfileId = $metadataProfile->id;
	return $codeCuePoint;
}

function checkIfCuePointHasMetada($client, $cuepointId)
{
	$filter = new KalturaMetadataFilter();
	$filter->metadataObjectTypeEqual = KalturaMetadataObjectType::CODE_CUE_POINT;
	$filter->objectIdEqual = $cuepointId;
	$metadataPlugin = KalturaMetadataClientPlugin::get($client);
	$result = $metadataPlugin->metadata->listAction($filter, null);
	
	if ($result->totalCount > 0)
		return true;
	return false;
}

function cloneCuePointWithMetadata($client)
{
	info('start ' .  __FUNCTION__);
	$entrySrc  = helper_createEmptyEntry($client,'test_cloning_cuepoint_with_metadata_src');
	$entryDst  = helper_createEmptyEntry($client,'test_cloning_cuepoint_with_metadata_dst');
	info("created entries with ID $entrySrc->id (src) and $entryDst->id (dst)");
	$codeCuePoint = createCodeCuePointWithMetaData($client, $entrySrc->id);

	$cloneCuePoint = cloneCuepoint($client, $codeCuePoint->id, $entryDst->id);
	info("clone cuepoint with ID $cloneCuePoint->id ");

	$flag = checkIfCuePointHasMetada($client, $cloneCuePoint->id);
	info("flag is $flag - deleting data");

	deleteEntryAndCuePoint($client, $entrySrc->id, $codeCuePoint->id);
	deleteEntryAndCuePoint($client, $entryDst->id, $cloneCuePoint->id);
	deleteGlobal($client);

	if ($flag)
		return (success(__FUNCTION__ ));
	return fail(__FUNCTION__." not metadata on cloned cue point");

}

function deleteEntryAndCuePoint($client, $entryId, $cuePointId)
{
	$cuepointPlugin = KalturaCuepointClientPlugin::get($client);
	$res = $cuepointPlugin->cuePoint->delete($cuePointId);
	$client->baseEntry->delete($entryId);
}

function deleteGlobal($client)
{
	global $metadataProfileId;
	$metadataPlugin = KalturaMetadataClientPlugin::get($client);
	$metadataPlugin->metadataProfile->delete($metadataProfileId);
}


function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	
	$ret = cloneCuePointWithMetadata($client);
	return $ret;
}
goMain();