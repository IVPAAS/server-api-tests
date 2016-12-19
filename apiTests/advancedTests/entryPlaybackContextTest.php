<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');

function Test1_VODentryPlaybackContext($client)
{
	$ret = 0;
	$sources = array
	(
		array("deliveryProfileId" => 1356, "format" => "url", "protocols" => "http,https", "flavorIds" => "0_6re07y3x,0_w9a5yj86", "drm" => array("widevine.WIDEVINE")),
		array("deliveryProfileId" => 2, "format" => "url", "protocols" => "http,https", "flavorIds" => "0_6re07y3x,0_w9a5yj86", "drm" => array("widevine.WIDEVINE")),
		array("deliveryProfileId" => 1, "format" => "applehttp", "protocols" => "http,https", "flavorIds" => "0_6re07y3x,0_w9a5yj86", "drm" => array("fairplay.FAIRPLAY")),
		array("deliveryProfileId" => 1001, "format" => "applehttp", "protocols" => "http,https", "flavorIds" => "0_6re07y3x,0_w9a5yj86", "drm" => array("fairplay.FAIRPLAY")),
		array("deliveryProfileId" => 1002, "format" => "hdnetworkmanifest", "protocols" => "http,https", "flavorIds" => "0_6re07y3x,0_w9a5yj86", "drm" => array()),
		array("deliveryProfileId" => 1003, "format" => "mpegdash", "protocols" => "http,https", "flavorIds" => "0_6re07y3x,0_w9a5yj86", "drm" => array("drm.WIDEVINE_CENC", "drm.PLAYREADY_CENC")),
		array("deliveryProfileId" => 1356, "format" => "url", "protocols" => "http,https", "flavorIds" => "0_6re07y3x,0_w9a5yj86", "drm" => array("widevine.WIDEVINE")),
		array("deliveryProfileId" => 2, "format" => "url", "protocols" => "http,https", "flavorIds" => "0_6re07y3x,0_w9a5yj86", "drm" => array("widevine.WIDEVINE")),
	);

	$entryId = '0_m7xhjapa';
	$kalturaPlaybackContextDataOptions = new KalturaPlaybackContextOptions();
	$kalturaPlaybackContextDataOptions->userAgent = "TestRule1";
	$result = $client->baseEntry->getPlaybackContext($entryId, $kalturaPlaybackContextDataOptions);

	if (empty($result))
		return fail(__FUNCTION__ . "entry getPlaybackContext returned empty result.");

	if (empty($result->sources) || empty($result->flavorAssets) || empty($result->messages) || empty($result->actions))
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

		if ($currentExpectedSource['protocols'] != $currentActualSource->protocols)
			$ret += fail(__FUNCTION__ . " Mismatch in source [$i] params:  expected protocols " . $currentExpectedSource['protocols'] . "- actual protocols " .$currentActualSource->protocols);

		if ($currentExpectedSource['flavorIds'] != $currentActualSource->flavorIds)
			$ret += fail(__FUNCTION__ . " Mismatch in source [$i] params:  expected flavorIds " . $currentExpectedSource['flavorIds'] . "- actual flavorIds " .$currentActualSource->flavorIds);

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

	info("Validating actions");
	if ($result->actions[0]->type != "DRM_POLICY")
		$ret += fail(__FUNCTION__ . " Incorrect Rule Action type. expected DRM_POLICY - actual " . $result->actions[0]->type);

	info("Validating messages");
	if ($result->messages[0]->message != "TestRuleMessage1" || $result->messages[0]->code != "code1")
		$ret += fail(__FUNCTION__ . " Incorrect restriction Object. expected [TestRuleMessage1, code1] - actual [" . $result->messages[0]->message . ", " . $result->messages[0]->code . "]");

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

function Test1_LIVEentryPlaybackContext($client)
{
	$ret = 0;
	$sources = array
	(
		array("deliveryProfileId" => 1355, "format" => "applehttp", "protocols" => "http,https", "flavorIds" => "0_ifmnf369,0_sgd1jyfb", "drm" => array("fairplay.FAIRPLAY")),
		array("deliveryProfileId" => 1353, "format" => "mpegdash", "protocols" => "http,https", "flavorIds" => "0_ifmnf369,0_sgd1jyfb", "drm" => array("drm.PLAYREADY_CENC", "drm.WIDEVINE_CENC")),
		array("deliveryProfileId" => 301, "format" => "mpegdash", "protocols" => "http,https", "flavorIds" => "0_ifmnf369,0_sgd1jyfb", "drm" => array("drm.PLAYREADY_CENC", "drm.WIDEVINE_CENC")),
		array("deliveryProfileId" => 4, "format" => "hls", "protocols" => "http,https", "flavorIds" => "0_ifmnf369,0_sgd1jyfb", "drm" => array()),
		array("deliveryProfileId" => 5, "format" => "applehttp_to_mc", "protocols" => "http,https", "flavorIds" => "0_ifmnf369,0_sgd1jyfb", "drm" => array()),
		array("deliveryProfileId" => 302, "format" => "hdnetworkmanifest", "protocols" => "http,https", "flavorIds" => "0_ifmnf369,0_sgd1jyfb", "drm" => array()),
		array("deliveryProfileId" => 303, "format" => "hds", "protocols" => "http,https", "flavorIds" => "0_ifmnf369,0_sgd1jyfb", "drm" => array()),
		array("deliveryProfileId" => 304, "format" => "rtmp", "protocols" => "rtmp", "flavorIds" => "0_ifmnf369,0_sgd1jyfb", "drm" => array()),
	);

	$entryId = '0_46x7qm0m';
	$kalturaPlaybackDataOptions = new KalturaPlaybackContextOptions();
	$kalturaPlaybackDataOptions->userAgent = "TestRule1";
	$result = $client->baseEntry->getPlaybackContext($entryId, $kalturaPlaybackDataOptions);

	if (empty($result))
		return fail(__FUNCTION__ . "entry getPlaybackContext returned empty result.");

	if (empty($result->sources) || empty($result->flavorAssets) || empty($result->messages) || empty($result->actions) )
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

		if ($currentExpectedSource['protocols'] != $currentActualSource->protocols)
			$ret += fail(__FUNCTION__ . " Mismatch in source [$i] params:  expected protocols " . $currentExpectedSource['protocols'] . "- actual protocols " .$currentActualSource->protocols);

		if ($currentExpectedSource['flavorIds'] != $currentActualSource->flavorIds)
			$ret += fail(__FUNCTION__ . " Mismatch in source [$i] params:  expected flavorIds " . $currentExpectedSource['flavorIds'] . "- actual flavorIds " .$currentActualSource->flavorIds);

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
		if (!in_array($flavor->id, array('0_ifmnf369', '0_sgd1jyfb')))
			$ret += fail(__FUNCTION__ . " Retrieved unexpected flavorAssets id $flavor->id)");
	}

	info("Validating actions");
	if ($result->actions[0]->type != "DRM_POLICY")
		$ret += fail(__FUNCTION__ . " Incorrect Rule Action type. expected DRM_POLICY - actual " . $result->actions[0]->type);

	info("Validating messages");
	if ($result->messages[0]->message != "TestRuleMessage1" || $result->messages[0]->code != "code1")
		$ret += fail(__FUNCTION__ . " Incorrect messages Object. expected [TestRuleMessage1, code1] - actual [" . $result->messages[0]->message . ", " . $result->messages[0]->code . "]");

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
	$ret  = Test1_VODentryPlaybackContext($client);
	$ret  += Test1_LIVEentryPlaybackContext($client);
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
