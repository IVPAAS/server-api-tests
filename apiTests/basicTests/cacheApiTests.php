<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/InVideoQuizHelper.php');

function CreateNewQuizAnonimousUser($wgClient,$entryId)

{
	$quizUserEntry1 = addQuizUserEntry($wgClient, 0, $entryId);
	$quizUserEntry2 = addQuizUserEntry($wgClient, 0, $entryId);
	if ($quizUserEntry1->id == $quizUserEntry2->id)
		return false;
	return true;
}

function testAnonymousCache($dc,$partnerId,$client,$entryId)
{
	info(__FUNCTION__."$dc $partnerId $entryId");
	$widgetId = helper_create_widget($client,"IVQ_WIDGET_SESSION_ROLE");
	$wgClient = startWidgetSession($dc, $partnerId, $widgetId);
	info("New widget ID with non API cache role {$widgetId}");
	if(CreateNewQuizAnonimousUser($wgClient,$entryId)==false)
		return fail(__FUNCTION__." Should get new value, but got value from cache! ");
	$widgetId = helper_create_widget($client,"PLAYBACK_BASE_ROLE");
	$wgClient = startWidgetSession($dc, $partnerId, $widgetId);
	info("New widget ID with API cache role {$widgetId}");
	if(CreateNewQuizAnonimousUser($wgClient,$entryId)==true)
		return fail(__FUNCTION__." Should get value from cache, but got new value!");
	$widgetId = helper_create_widget($client);
	$wgClient = startWidgetSession($dc, $partnerId, $widgetId);
	info("New widget ID with no role {$widgetId}");
	if(CreateNewQuizAnonimousUser($wgClient,$entryId)==true)
		return fail(__FUNCTION__." Should get value from cache, but got new value!");
	return success(__FUNCTION__);
}


function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$ret = 0;
$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$entry=addEntry($client,__FUNCTION__);
createNewQuiz($client,$entry->id,null,null,null,null,KalturaNullableBoolean::TRUE_VALUE,null);
	$ret += testAnonymousCache($dc,$partnerId,$client,$entry->id);
	return ($ret);
}

goMain();



