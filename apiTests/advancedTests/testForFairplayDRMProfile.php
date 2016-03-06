<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
//require_once(dirname(__FILE__).'/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__).'/../testsHelpers/runAllTestsHelper.php');

function Test1_AddFairplayDRMProfile(KalturaClient $client, $parnterId)
{
	info("Create new fairplay ");
	$certificateFile = dirname ( __FILE__ ).'/../../resources/fairplay_certificate.der';
	
	$fairplayDRMProfile = new KalturaFairplayDrmProfile();
	$fairplayDRMProfile->name = "test drm profile";
	$fairplayDRMProfile->description = "description of test drm profile";
	$fairplayDRMProfile->partnerId = $parnterId;
	$fairplayDRMProfile->publicCertificate = base64_encode(file_get_contents($certificateFile));
	$fairplayDRMProfile->status = KalturaDrmProfileStatus::ACTIVE;
	$fairplayDRMProfile->provider = KalturaDrmProviderType::FAIRPLAY;
	
	$drmPlugin = KalturaDrmClientPlugin::get($client);

	try
	{
		$client->setPartnerId($parnterId);
		$drmAddResult = $drmPlugin->drmProfile->add($fairplayDRMProfile);
		return success(__FUNCTION__);
	}
	catch (Exception $e)
	{
		return fail(__FUNCTION__);
	}
	
}

function go()
{
	if ($GLOBALS['argc'] != 4)
	{
		print ("\n\rUsage: " .$GLOBALS['argv'][0] . " <DC URL> 	<admin_console user> <admin_console password>");
	}
	$dc = $GLOBALS['argv'][1];
	$adminConsoleUser = $GLOBALS['argv'][2];
	$adminConsolePass =	$GLOBALS['argv'][3];

	$client = login($dc, $adminConsoleUser, $adminConsolePass, -2);
	$testPartner = createTestPartner($client, "testUser");
	addPartnerPermissions($client, $testPartner, "DRM_PLUGIN_PERMISSION", KalturaPermissionStatus::ACTIVE);
	$ret = Test1_AddFairplayDRMProfile($client, $testPartner->id);
	removePartner($dc, $client, $testPartner);
	return ($ret);
}

go();
