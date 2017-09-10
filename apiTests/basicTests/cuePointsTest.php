<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/InVideoQuizHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');

function createAnnontation($client,$entryId,$tags=null,$isPublic=null)
{
	$cuepointPlugin = KalturaCuepointClientPlugin::get($client);
	$cuePoint = new KalturaAnnotation();
	$cuePoint->isPublic = $isPublic;
	$cuePoint->tags = $tags;
	$cuePoint->entryId = $entryId;
	$res = $cuepointPlugin->cuePoint->add($cuePoint);
	return $res;
}

function createCodeCue($client, $entryId, $code="test", $tags=null)
{
	$cuepointPlugin = KalturaCuepointClientPlugin::get($client);
	$cuePoint = new KalturaCodeCuePoint();
	$cuePoint->tags = $tags;
	$cuePoint->code = $code;
	$cuePoint->entryId = $entryId;
	$res = $cuepointPlugin->cuePoint->add($cuePoint);
	return $res;
}

function testRetrievePrivateAnnotationCueForDifferentUserWhenHaveEntryPrivilage($dc,$partnerId,$adminSecret,$userSecret)
{
	info('start ' .  __FUNCTION__);
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$entry  = createEmptyEntry($client,__FILE__);

	$weakClient1 = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER, "a@gmail.com");
	$weakClient2 = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER, "b@gmail.com");

	$annotationCuePoint = createAnnontation($weakClient1, $entry->id, 'annotation', false);

	$filter = new KalturaAnnotationFilter();
	$filter->entryIdEqual = $entry->id;
	$filter->cuePointTypeEqual = KalturaCuePointType::ANNOTATION;
	$cuepointPlugin = KalturaCuepointClientPlugin::get($weakClient2);

	$result = $cuepointPlugin->cuePoint->listAction($filter, null);
	if (count($result->objects) > 0)
		return (fail(__FUNCTION__ . " Retrieved Annotation cuePoint when not supposed to retrieve it"));

	$weakClient2 = startKalturaSession($partnerId, $userSecret, $dc, KalturaSessionType::USER, "b@gmail.com", "list:" . $entry->id);
	$cuepointPlugin = KalturaCuepointClientPlugin::get($weakClient2);
	$result = $cuepointPlugin->cuePoint->listAction($filter, null);
	if (count($result->objects) == 1)
	{
		if ($result->objects[0]->id == $annotationCuePoint->id)
			return (success(__FUNCTION__ ));
		else
			return (fail(__FUNCTION__ . " Retrieved Annotation cuePoint [".$result->objects[0]->id ."] when expected annotation was [".$annotationCuePoint->id ."]"));
	}

	return (fail(__FUNCTION__ . " Retrieved [" .count($result->objects) ."] Annotation cuePoint when only 1 was expected."));
}

function testRetrievePrivateAndPublicAnnotationCueForDifferentUserWhenHaveEntryPrivilage($dc,$partnerId,$adminSecret,$userSecret)
{
	info('start ' .  __FUNCTION__);
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$entry  = createEmptyEntry($client,__FILE__);

	$weakClient1 = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER, "a@gmail.com");
	$weakClient2 = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER, "b@gmail.com");

	$annotationCuePoint1 = createAnnontation($weakClient1, $entry->id, 'annotation', true);
	$annotationCuePoint2 = createAnnontation($weakClient2, $entry->id, 'annotation', false);

	$filter = new KalturaAnnotationFilter();
	$filter->entryIdEqual = $entry->id;
	$filter->cuePointTypeEqual = KalturaCuePointType::ANNOTATION;
	$cuepointPlugin = KalturaCuepointClientPlugin::get($weakClient2);

	$result = $cuepointPlugin->cuePoint->listAction($filter, null);
	if (count($result->objects) != 2)
		return (fail(__FUNCTION__ . " Retrieved [" .count($result->objects) ."] Annotation cuePoint when 2 were expected."));

	$weakClient2 = startKalturaSession($partnerId, $userSecret, $dc, KalturaSessionType::USER, "b@gmail.com", "list:" . $entry->id);
	$cuepointPlugin = KalturaCuepointClientPlugin::get($weakClient2);
	$result = $cuepointPlugin->cuePoint->listAction($filter, null);

	if (count($result->objects) != 2)
		return (fail(__FUNCTION__ . " Retrieved [" .count($result->objects) ."] Annotation cuePoint when 2 were expected."));

	return (success(__FUNCTION__ ));
}



function testRetrievePrivateAndPublicAnnotationCueForDifferentUserWhenHaveInvalidEntryPrivilage($dc,$partnerId,$adminSecret,$userSecret)
{
	info('start ' .  __FUNCTION__);
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$entry  = createEmptyEntry($client,__FILE__);

	$weakClient1 = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER, "a@gmail.com");
	$weakClient2 = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER, "b@gmail.com");

	$annotationCuePoint1 = createAnnontation($weakClient1, $entry->id, 'annotation', true);
	$annotationCuePoint2 = createAnnontation($weakClient1, $entry->id, 'annotation', false);

	$filter = new KalturaAnnotationFilter();
	$filter->entryIdEqual = $entry->id;
	$filter->cuePointTypeEqual = KalturaCuePointType::ANNOTATION;
	$cuepointPlugin = KalturaCuepointClientPlugin::get($weakClient2);

	$result = $cuepointPlugin->cuePoint->listAction($filter, null);
	if (count($result->objects) == 1 )
	{
		if ($result->objects[0]->id != $annotationCuePoint1->id)
			return (fail(__FUNCTION__ . " Retrieved Annotation cuePoint [" . $result->objects[0]->id . "] when expected annotation was [" . $annotationCuePoint1->id . "]"));
	}
	else
		return (fail(__FUNCTION__ . " Retrieved [" .count($result->objects) ."] Annotation cuePoint when 1 was expected."));

	$weakClient2 = startKalturaSession($partnerId, $userSecret, $dc, KalturaSessionType::USER, "b@gmail.com", "list:InvalidEntryId");
	$cuepointPlugin = KalturaCuepointClientPlugin::get($weakClient2);
	$result = $cuepointPlugin->cuePoint->listAction($filter, null);

	if (count($result->objects) == 1 )
	{
		if ($result->objects[0]->id != $annotationCuePoint1->id)
			return (fail(__FUNCTION__ . " Retrieved Annotation cuePoint [" . $result->objects[0]->id . "] when expected annotation was [" . $annotationCuePoint1->id . "]"));
	}
	else
		return (fail(__FUNCTION__ . " Retrieved [" .count($result->objects) ."] Annotation cuePoint when 1 was expected."));

	return (success(__FUNCTION__ ));
}


function testRetrievePrivateAndPublicAnnotationCueForDifferentUserWhenHaveEntryPrivilageAll($dc,$partnerId,$adminSecret,$userSecret)
{
	info('start ' .  __FUNCTION__);
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$entry  = createEmptyEntry($client,__FILE__);

	$weakClient1 = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER, "a@gmail.com");
	$weakClient2 = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER, "b@gmail.com");

	$annotationCuePoint1 = createAnnontation($weakClient1, $entry->id, 'annotation', true);
	$annotationCuePoint2 = createAnnontation($weakClient1, $entry->id, 'annotation', false);

	$filter = new KalturaAnnotationFilter();
	$filter->entryIdEqual = $entry->id;
	$filter->cuePointTypeEqual = KalturaCuePointType::ANNOTATION;
	$cuepointPlugin = KalturaCuepointClientPlugin::get($weakClient2);

	$result = $cuepointPlugin->cuePoint->listAction($filter, null);
	if (count($result->objects) == 1 )
	{
		if ($result->objects[0]->id != $annotationCuePoint1->id)
			return (fail(__FUNCTION__ . " Retrieved Annotation cuePoint [" . $result->objects[0]->id . "] when expected annotation was [" . $annotationCuePoint1->id . "]"));
	}
	else
		return (fail(__FUNCTION__ . " Retrieved [" .count($result->objects) ."] Annotation cuePoint when 1 was expected."));

	$weakClient2 = startKalturaSession($partnerId, $userSecret, $dc, KalturaSessionType::USER, "b@gmail.com", "list:*");
	$cuepointPlugin = KalturaCuepointClientPlugin::get($weakClient2);
	$result = $cuepointPlugin->cuePoint->listAction($filter, null);

	if (count($result->objects) != 2 )
        {
                return (fail(__FUNCTION__ . " Retrieved  [" .count($result->objects) ."] Annotation cuePoint when 2 were expected."));

        }
        else
        {
                if (($result->objects[0]->id != $annotationCuePoint1->id) && ($result->objects[1]->id != $annotationCuePoint1->id))
                        return (fail(__FUNCTION__ . " Retrieved Annotation cuePoint [" . $result->objects[0]->id . "] and [" . $result->objects[1]->id . "] when expected at least one annotation to be equal to [" . $annotationCuePoint1->id . "]"));
        }

	return (success(__FUNCTION__ ));
}


function testRetrievePrivateAndPublicAnnotationCueForDifferentUserWithPagination($dc,$partnerId,$adminSecret,$userSecret)
{
	info('start ' .  __FUNCTION__);
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$entry  = createEmptyEntry($client,__FILE__);

	$weakClient1 = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER, "a@gmail.com");
	$weakClient2 = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER, "b@gmail.com");

	$annotationCuePoint2 = createAnnontation($weakClient1, $entry->id, 'annotation', false);
	$annotationCuePoint1 = createAnnontation($weakClient1, $entry->id, 'annotation', true);

	$filter = new KalturaAnnotationFilter();
	$filter->entryIdEqual = $entry->id;
	$filter->cuePointTypeEqual = KalturaCuePointType::ANNOTATION;
	$filter->orderBy = '-createdAt';
	$pager = new KalturaFilterPager();
	$pager->pageSize = 1;
	$pager->pageIndex = 0;
	$cuepointPlugin = KalturaCuepointClientPlugin::get($weakClient2);

	$result = $cuepointPlugin->cuePoint->listAction($filter, $pager);
	if (count($result->objects) == 1 )
	{
		if ($result->objects[0]->id != $annotationCuePoint1->id)
			return (fail(__FUNCTION__ . " Retrieved Annotation cuePoint [" . $result->objects[0]->id . "] when expected annotation was [" . $annotationCuePoint1->id . "]"));
	}
	else
		return (fail(__FUNCTION__ . " Retrieved [" .count($result->objects) ."] Annotation cuePoint when 1 was expected."));

	$weakClient2 = startKalturaSession($partnerId, $userSecret, $dc, KalturaSessionType::USER, "b@gmail.com", "list:*");
	$cuepointPlugin = KalturaCuepointClientPlugin::get($weakClient2);
	$result = $cuepointPlugin->cuePoint->listAction($filter, $pager);

	if ($result->totalCount != 2 )
	{
		return (fail(__FUNCTION__ . " Retrieved [" . $result->totalCount ."] Annotation cuePoint when 2 was expected."));
	}

	return (success(__FUNCTION__ ));
}


function testRetrievePrivateAndPublicCodeCueForDifferentUserWithPrivilages($dc,$partnerId,$adminSecret,$userSecret)
{
	info('start ' .  __FUNCTION__);
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$entry  = createEmptyEntry($client,__FILE__);

	$weakClient1 = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER, "a@gmail.com");
	$weakClient2 = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER, "b@gmail.com");

	$codeCuePoint1 = createCodeCue($weakClient1, $entry->id, 'testCode1');
	$codeCuePoint2 = createCodeCue($weakClient1, $entry->id, 'testCode2');

	$filter = new KalturaCodeCuePointFilter();
	$filter->entryIdEqual = $entry->id;
	$cuepointPlugin = KalturaCuepointClientPlugin::get($weakClient2);

	$result = $cuepointPlugin->cuePoint->listAction($filter, null);
	if (count($result->objects) != 2 )
		return (fail(__FUNCTION__ . " Retrieved [" .count($result->objects) ."] Annotation cuePoint when 2 were expected."));

	$weakClient2 = startKalturaSession($partnerId, $userSecret, $dc, KalturaSessionType::USER, "b@gmail.com", "list:".$entry->id);
	$cuepointPlugin = KalturaCuepointClientPlugin::get($weakClient2);
	$result = $cuepointPlugin->cuePoint->listAction($filter, null);

	if (count($result->objects) != 2 )
		return (fail(__FUNCTION__ . " Retrieved [" .count($result->objects) ."] Annotation cuePoint when 2 were expected."));

	return (success(__FUNCTION__ ));
}

function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$ret = testRetrievePrivateAnnotationCueForDifferentUserWhenHaveEntryPrivilage($dc,$partnerId,$adminSecret,$userSecret);
	$ret += testRetrievePrivateAndPublicAnnotationCueForDifferentUserWhenHaveEntryPrivilage($dc,$partnerId,$adminSecret,$userSecret);
	$ret += testRetrievePrivateAndPublicAnnotationCueForDifferentUserWhenHaveInvalidEntryPrivilage($dc,$partnerId,$adminSecret,$userSecret);
	$ret += testRetrievePrivateAndPublicAnnotationCueForDifferentUserWhenHaveEntryPrivilageAll($dc,$partnerId,$adminSecret,$userSecret);
	$ret += testRetrievePrivateAndPublicAnnotationCueForDifferentUserWithPagination($dc,$partnerId,$adminSecret,$userSecret);
	$ret += testRetrievePrivateAndPublicCodeCueForDifferentUserWithPrivilages($dc,$partnerId,$adminSecret,$userSecret);

	return $ret;

}

goMain();



