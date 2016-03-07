<?php
require_once('/opt/kaltura/web/content/clientlibs/testClient/KalturaClient.php');
require_once(dirname(__FILE__).'/../testsHelpers/apiTestHelper.php');

function Test1_SimpleUploadEntry(KalturaClient $client)
{
	info("Simple upload to verify that file extensions are good");
	$mediaEntry = helper_createEntryAndUploaDmp4Content($client, 'simpleUploadTest');
	info("Wait for entry to be ready id =".$mediaEntry->id);
	while(isEntryReady($client,$mediaEntry->id)!=true)
	{
		sleep(1);
		print (".");
	}

	$sourceFilter = new KalturaFlavorAssetFilter();
	$sourceFilter->entryIdEqual = $mediaEntry->id;
	$sourceFilter->tagsLike = "Source";
	$flavorAssets = $client->flavorAsset->listAction($sourceFilter);

	$outUrl = $client->flavorAsset->getUrl($flavorAssets->objects[0]->id);
	if (substr_compare($outUrl, "mp4", strrpos($outUrl,".")+1) == 0)
	{
		return success(__FUNCTION__);
	}
	else
	{
		return fail(__FUNCTION__);
	}

}

function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$ret = Test1_SimpleUploadEntry($client);
	return ($ret);
}

goMain();
