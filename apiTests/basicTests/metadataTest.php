<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');

$metadataProfileId = null;
$entryId = null;
$metadataId = null;
$cuePointId = null;

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
	global $metadataProfileId;
	$metadataProfileId = $result->id;
	return $result;
}

function addMetaData($client, $metadataProfileId, $objectId)
{
	$xmlData = "<metadata><FirstAttr>adding attr in meta</FirstAttr></metadata>";
	$metadataPlugin = KalturaMetadataClientPlugin::get($client);
	$result = $metadataPlugin->metadata->add($metadataProfileId, KalturaMetadataObjectType::CODE_CUE_POINT, $objectId, $xmlData);
	global $metadataId;
	$metadataId = $result->id;
	return $result;

}


function createCodeCuePointWithMetaData($client, $entryId)
{
	//$codeCuePoint = createCodeCue($client, $entryId, 'testCode1');
	//$meta = createMetadataProfile($client);
	//info("created metadataProfile with ID $meta->id");


}

function cloneCuePointWithMetadata($client)
{
	info('start ' .  __FUNCTION__);
	$res = addMetaData($client, 6, '0_gne54tsy');
	info("created metadata with ID $res->id");
	/*
	$entry  = helper_createEmptyEntry($client,__FILE__);
	info("created cue point with ID $entry->id");
	$codeCuePoint = createCodeCue($client, $entry->id, 'testCode1');
	*/

	//$codeCuePoint = createCodeCue($client, '0_ggiyjwa1', 'testCode1'); //as om prem src
	//$cuePointid = '0_gne54tsy';
	//info("created cue point with ID $cuePointid");
	return (success(__FUNCTION__ ));
}

function deleteAll($client)
{
	global $metadataProfileId;
	$metadataPlugin = KalturaMetadataClientPlugin::get($client);
	$metadataPlugin->metadataProfile->delete(9);
}


function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);


	$ret = cloneCuePointWithMetadata($client);
	return $ret;
}
goMain();