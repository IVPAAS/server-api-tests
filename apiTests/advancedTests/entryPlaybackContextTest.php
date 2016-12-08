<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');

function Test1_entryPlaybackContext($client)
{
	$ret = 0;
	$sources = array
	(
		array("deliveryProfileId" => 1346, "format" => "http", "priority" => "local_http_1", "protocols" => array("http", "https"), "flavors" => array("0_6re07y3x", "0_w9a5yj86"), "drm" => array("widevine")),
		array("deliveryProfileId" => 2, "format" => "http", "priority" => "local_http_2", "protocols" => array("http", "https"), "flavors" => array("0_6re07y3x", "0_w9a5yj86"), "drm" => array("widevine")),
		array("deliveryProfileId" => 1, "format" => "applehttp", "priority" => "local_applehttp_1", "protocols" => array("http", "https"), "flavors" => array("0_6re07y3x", "0_w9a5yj86"), "drm" => array("fps")),
		array("deliveryProfileId" => 1001, "format" => "applehttp", "priority" => "local_applehttp_2", "protocols" => array("http", "https"), "flavors" => array("0_6re07y3x", "0_w9a5yj86"), "drm" => array("fps")),
		array("deliveryProfileId" => 1002, "format" => "hdnetworkmanifest", "priority" => "local_hdnetworkmanifest_1", "protocols" => array("http", "https"), "flavors" => array("0_6re07y3x", "0_w9a5yj86"), "drm" => array()),
		array("deliveryProfileId" => 1003, "format" => "mpegdash", "priority" => "local_mpegdash_1", "protocols" => array("http", "https"), "flavors" => array("0_6re07y3x", "0_w9a5yj86"), "drm" => array("cenc/widevine", "cenc/playready")),
		array("deliveryProfileId" => 1346, "format" => "http", "priority" => "remote_http_1", "protocols" => array("http", "https"), "flavors" => array("0_6re07y3x", "0_w9a5yj86"), "drm" => array("widevine")),
		array("deliveryProfileId" => 2, "format" => "http", "priority" => "remote_http_2", "protocols" => array("http", "https"), "flavors" => array("0_6re07y3x", "0_w9a5yj86"), "drm" => array("widevine")),
	);

	$entryId = '0_m7xhjapa';
	$kalturaEntryContextDataParams = new KalturaEntryContextDataParams();
	$kalturaEntryContextDataParams->userAgent = "TestRule1";
	$result = $client->baseEntry->getPlaybackContext($entryId, $kalturaEntryContextDataParams);

	if (empty($result))
		return fail(__FUNCTION__ . "entry getPlaybackContext returned empty result.");

	if (empty($result->sources) || empty($result->flavorAssets) || empty($result->messages) || empty($result->actions) || empty($result->restrictions))
	{
		print(print_r($result, true));
		return fail(__FUNCTION__ . "entry getPlaybackContext result is not complete. Objects are missing from result.");
	}

	if (count($result->sources) != 8)
		$ret += fail(__FUNCTION__ . " sources count fail. expected 8 - actual " . count($result->sources));
	if (count($result->flavorAssets) != 2)
		$ret += fail(__FUNCTION__ . " flavorAssets count fail. expected 2 - actual " . count($result->flavorAssets));
	if (count($result->messages) != 1)
		$ret += fail(__FUNCTION__ . " messages count fail. expected 1 - actual " . count($result->messages));
	if (count($result->actions) != 1)
		$ret += fail(__FUNCTION__ . " actions count fail. expected 1 - actual " . count($result->actions));
	if (count($result->restrictions) != 1)
		$ret += fail(__FUNCTION__ . " restrictions count fail. expected 1 - actual " . count($result->restrictions));

	info("Validating sources");
	for ($i = 0; $i < count($result->sources); $i++)
	{
		info("Validating source $i");
		$currentActualSource = $result->sources[$i];
		$currentExpectedSource = $sources[$i];
		if ($currentExpectedSource['deliveryProfileId'] != $currentActualSource->deliveryProfileId)
			$ret += fail(__FUNCTION__ . " Mismatch in source [$i] params:  expected deliveryProfileId " . $currentExpectedSource['deliveryProfileId'] . "- actual deliveryProfileId " . $currentActualSource->deliveryProfileId);
		if ($currentExpectedSource['format'] != $currentActualSource->format)
			$ret += fail(__FUNCTION__ . " Mismatch in source [$i] params:  expected format " . $currentExpectedSource['format'] . "- actual format " . $currentActualSource->format);
		if ($currentExpectedSource['priority'] != $currentActualSource->priority)
			$ret += fail(__FUNCTION__ . " Mismatch in source [$i] params:  expected priority " . $currentExpectedSource['priority'] . "- actual priority " . $currentActualSource->priority);

		$protocols = array();
		foreach ($currentActualSource->protocols as $protocol)
			$protocols[] = $protocol->value;
		if (count(array_diff($currentExpectedSource['protocols'], $protocols)))
			$ret += fail(__FUNCTION__ . " Mismatch in source [$i] params:  expected protocols " . print_r($currentExpectedSource['format'], true) . "- actual protocols " . print_r($currentActualSource->protocols, true));

		$flavorsData = array();
		foreach ($currentActualSource->flavors as $flavors)
			$flavorsData [] = $flavors->value;
		if (count(array_diff($currentExpectedSource['flavors'], $flavorsData)))
			$ret += fail(__FUNCTION__ . " Mismatch in source [$i] params:  expected flavors " . print_r($currentExpectedSource['flavors'], true) . "- actual flavors " . print_r($currentActualSource->flavors, true));

		$drmSchems = array();
		foreach ($currentActualSource->drm as $drm)
		{
			$drmSchems[] = $drm->scheme;
			if (empty($drm->licenseURL))
				$ret += fail(__FUNCTION__ . " Mismatch in source [$i] params:  expected drm $drm->scheme Url To exist but actual is ");
		}

		if (count(array_diff($currentExpectedSource['drm'], $drmSchems)))
			$ret += fail(__FUNCTION__ . " Mismatch in source [$i] params:  expected drm " . print_r($currentExpectedSource['drm'], true) . "- actual drm " . print_r($currentActualSource->drm, true));

		if (empty($currentActualSource->url))
			$ret += fail(__FUNCTION__ . " Mismatch in source [$i] params:  expected source to have a url - actual url is: " . $currentActualSource->url);
	}

	info("Validating flavors");
	foreach ($result->flavorAssets as $flavor)
	{
		if (!in_array($flavor->id, array('0_w9a5yj86', '0_6re07y3x')))
			$ret += fail(__FUNCTION__ . " Retrieved unexpected flavorAssets id $flavor->id)");
	}

	info("Validating messages");
	if ($result->messages[0]->value != "TestRuleMessage1")
		$ret += fail(__FUNCTION__ . " Incorrect Rule Message. expected TestRuleMessage1 - actual " . $result->messages[0]->value);

	info("Validating actions");
	if ($result->actions[0]->type != "DRM_POLICY")
		$ret += fail(__FUNCTION__ . " Incorrect Rule Action type. expected DRM_POLICY - actual " . $result->actions[0]->type);

	info("Validating restrictions");
	if ($result->restrictions[0]->message != "TestRuleMessage1" || $result->restrictions[0]->code != "code1")
		$ret += fail(__FUNCTION__ . " Incorrect restriction Object. expected [TestRuleMessage1, code1] - actual [" . $result->restrictions[0]->message . ", " . $result->restrictions[0]->code . "]");

	if ($ret != 0)
	{
		info("*************************************************");
		fail("Failed to Validate the following result");
		info(print_r($result, true));
		info("*************************************************");
		return fail(__FUNCTION__);
	}
	return success(__FUNCTION__ . ". \n\r Entry playback context for Entry $entryId finished successfully");

}


function main($dc,$partnerId,$adminSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$ret  = Test1_entryPlaybackContext($client);
	return ($ret);
}

goMain2();

function goMain2()
{
	if ($GLOBALS['argc']!=4 )
	{
		printUsage2();
		exit (1);
	}

	$dcUrl 			= 	$GLOBALS['argv'][1];
	$partnerId 		= 	$GLOBALS['argv'][2];
	$adminSecret	= 	$GLOBALS['argv'][3];
	$res =  main($dcUrl,$partnerId,$adminSecret);
	exit($res);
}

function printUsage2()
{
	print ("\n\rUsage: " .$GLOBALS['argv'][0] . " <DC URL> 	<parnter id> <partner admin secret> ");
}
