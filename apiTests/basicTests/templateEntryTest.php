<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');

function createTranscodingProfileWithTempalte($client, $name, $templateEntryId)
{
	$conversionProfile = new KalturaConversionProfile();
	$conversionProfile->status = KalturaConversionProfileStatus::ENABLED;
	$conversionProfile->name = $name;
	$conversionProfile->defaultEntryId = $templateEntryId;
	$result = $client->conversionProfile->add($conversionProfile);
	return $result->id;
}

function createEXTmedia($client, $name, $type)
{
	$entry = new KalturaExternalMediaEntry();
	$entry->name = $name;
	$entry->description = 'EXT template';
	$entry->mediaType = KalturaMediaType::VIDEO;
	if ($type)
		$entry->externalSourceType = $type;
	$externalmediaPlugin = KalturaExternalmediaClientPlugin::get($client);
	$result = $externalmediaPlugin->externalMedia->add($entry);
	return $result;
}
function createAndGetEXTMediaEntry($client, $name, $sourceType)
{
	$externalMediaEntry = createEXTmedia($client, $name, $sourceType);
	$externalmediaPlugin = KalturaExternalmediaClientPlugin::get($client);
	return $externalmediaPlugin->externalMedia->get($externalMediaEntry->id);
}

function Test1_newExtWithRegularTemplate($client)
{
	global $regularTPid;
	$client->conversionProfile->setAsDefault($regularTPid);
	$result = createAndGetEXTMediaEntry($client, 'newEXTmediaEntry', KalturaExternalMediaSourceType::YOUTUBE);

	if ($result && $result->externalSourceType == 'YouTube')
		return success(__FUNCTION__);
	else return fail(__FUNCTION__);
}

function Test2_newExtWithEXTTemplate($client)
{
	global $EXTmediaTPid;
	$client->conversionProfile->setAsDefault($EXTmediaTPid);
	$result = createAndGetEXTMediaEntry($client, 'newEXTmediaEntry', KalturaExternalMediaSourceType::INTERCALL);

	if ($result && $result->externalSourceType == 'InterCall')
		return success(__FUNCTION__);
	else return fail(__FUNCTION__);
}

function Test3_newRegularWithEXTTemplate($client)
{
	global $EXTmediaTPid;
	$client->conversionProfile->setAsDefault($EXTmediaTPid);
	$entry = new KalturaMediaEntry();
	$entry->name = 'RegularEntry';
	$newEntry = $client->baseEntry->add($entry, KalturaEntryType::MEDIA_CLIP);
	$result = $client->baseEntry->get($newEntry->id);

	if ($result && $result->description == 'EXT template')
		return success(__FUNCTION__);
	else return fail(__FUNCTION__);
}

$regularEntry = null;
$externalMediaEntry = null;
$regularTPid = null;
$EXTmediaTPid = null;
$defaultTPid = null;

function init($client) 
{
	global $regularEntry, $externalMediaEntry, $regularTPid, $EXTmediaTPid, $defaultTPid;
	$defaultTP = $client->conversionProfile->getDefault(KalturaConversionProfileType::MEDIA);
	$defaultTPid = $defaultTP->id;
	$regularEntry = addEntry($client, 'RegularEntry');
	$externalMediaEntry = createEXTmedia($client, 'EXTmediaEntry', KalturaExternalMediaSourceType::YOUTUBE);
	$regularTPid = createTranscodingProfileWithTempalte($client, 'regularTP', $regularEntry->id);
	$EXTmediaTPid = createTranscodingProfileWithTempalte($client, 'EXTmediaTP', $externalMediaEntry->id);
	warning('create regularEntry ' .$regularEntry->id . ' with TP of ' .$regularTPid .
		' and create externalMediaEntry ' .$externalMediaEntry->id . ' with TP of ' .$EXTmediaTPid);

}

function tearDown($client)
{
	global $regularEntry, $externalMediaEntry, $regularTPid, $EXTmediaTPid, $defaultTPid;
	$client->conversionProfile->setAsDefault($defaultTPid);
	$client->baseEntry->delete($regularEntry->id);
	$client->baseEntry->delete($externalMediaEntry->id);
	$client->conversionProfile->delete($regularTPid);
	$client->conversionProfile->delete($EXTmediaTPid);
	warning('tear all down');
}

function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);

	init($client);

	$ret  = Test1_newExtWithRegularTemplate($client);
	$ret += Test2_newExtWithEXTTemplate($client);
	$ret +=Test3_newRegularWithEXTTemplate($client);

	tearDown($client);
	return ($ret);
}

goMain();
