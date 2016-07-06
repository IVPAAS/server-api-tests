<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');



function myAddEntry($client,$parentEntryId=null,$templateEntryId=null)
{
	$entry                                  = new KalturaMediaEntry();
	$type                                   = KalturaEntryType::MEDIA_CLIP;
	$entry->name                            = "Dummy";
	$entry->mediaType                       = KalturaMediaType::VIDEO;
	if($parentEntryId)
		$entry->parentEntryId = $parentEntryId;
	if($templateEntryId)
		$entry->templateEntryId = $templateEntryId;
	$result                                 = $client->baseEntry->add($entry, $type);
	//print ("\nAdd entry ID:".$result->id);
	return $result;
}

function test1CreateEntryWithParent($client)
{
	$parentEntry = myAddEntry($client);
	$childEntry = myAddEntry($client,$parentEntry->id);

	info("Created entries: parent - $parentEntry->id child - $childEntry->id\n");

	$filter = new KalturaMediaEntryFilter();
	$filter->orderBy = '-createdAt';
	$filter->statusIn = '-1,-2,0,1,2,7,4';
	$pager = null;
	$result = $client->baseEntry->listAction($filter, $pager);

	$foundParent=false;

	foreach($result->objects as $entry)
	{
		if($entry->id == $childEntry->id)
			return (fail(__FUNCTION__));
		if($entry->id == $parentEntry->id)
			$foundParent=true;
	}
	if(!$foundParent)
		fail(__FUNCTION__."Should have found parent entry but it did not");

	success(__FUNCTION__);
	//search the entries
}

function test2CreateEntryWithParentAndTempalte($client)
{
	$templateEntry = myAddEntry($client);
	$parentEntry = myAddEntry($client);
	$childEntry = myAddEntry($client,$parentEntry->id,$templateEntry->id);

	info("Created entries: parent - $$parentEntry->id child - $childEntry->id template $templateEntry->id\n");

	$filter = new KalturaMediaEntryFilter();
        $filter->orderBy = '-createdAt';
        $filter->statusIn = '-1,-2,0,1,2,7,4';
        $result = $client->baseEntry->listAction($filter, null);

	$foundParent=false;
        foreach($result->objects as $entry)
        {
                if($entry->id == $childEntry->id)
                        return (fail(__FUNCTION__));
                if($entry->id == $parentEntry->id)
                        $foundParent=true;
        }
        if(!$foundParent)
                fail(__FUNCTION__."Should have found parent entry but it did not");

        success(__FUNCTION__);
}

function test3ChangeExsitingEntry($client)
{
        $templateEntry = myAddEntry($client);
        $parentEntry = myAddEntry($client);
        $childEntry = myAddEntry($client,null,$templateEntry->id);

        info("Created entries: parent - $parentEntry->id child(not yes connected)- $childEntry->id template $templateEntry->id\n");

        $filter = new KalturaMediaEntryFilter();
        $filter->orderBy = '-createdAt';
        $filter->statusIn = '-1,-2,0,1,2,7,4';
        $result = $client->baseEntry->listAction($filter, null);

	$foundInSearch=false;

        foreach($result->objects as $entry)
                if($entry->id == $childEntry->id)
			$foundInSearch=true;

	if($foundInSearch!=true)
		return (fail(__FUNCTION__."Entry should have been found but it was not"));

	$baseEntry = new KalturaBaseEntry();
	$baseEntry->parentEntryId = $parentEntry->id;
	$result = $client->baseEntry->update($childEntry->id, $baseEntry);

	$result = $client->baseEntry->listAction($filter, null);
	foreach($result->objects as $entry)
                if($entry->id == $childEntry->id)
                	return (fail(__FUNCTION__."Entry should not have been found but it was"));
 
	return (success(__FUNCTION__));
}


function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	//todo - create entry
	test1CreateEntryWithParent($client);
	test2CreateEntryWithParentAndTempalte($client);
	test3ChangeExsitingEntry($client);
	return ;
}

goMain();
