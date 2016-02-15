<?php
/**
 * Created by IntelliJ IDEA.
 * User: elad.cohen
 * Date: 2/14/2016
 * Time: 10:28 AM
 */

require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaEnums.php');
require_once('../apiTestHelper.php');

const SERVER_ID = 2;


function main($dc,$partnerId,$adminSecret,$mediaServerSecret)
{
	warning("This test require admin secret and media server secret (partner -5)");
	$clientAdmin = startKalturaSession($partnerId,$adminSecret,$dc);
	$clientMediaServer = startKalturaSession(-5,$mediaServerSecret,$dc);
	$entryServerNode = Test1_Add($clientAdmin, $clientMediaServer);
	Test2_Get( $clientMediaServer, $entryServerNode->id);
	Test3_Update( $clientMediaServer, $entryServerNode);
	Test4_List( $clientAdmin, $entryServerNode->entryId);
	Test5_Delete( $clientMediaServer, $entryServerNode->id);
	return (0);
}

function Test1_Add($clientAdmin, $clientMediaServer)
{
	info("Create new entry and generate from it add call");
	$emptyEntry = helper_createEmptyEntry($clientAdmin, 'EntryServerNodeTest');
	$entryServerNode = new KalturaLiveEntryServerNode();
	$entryServerNode->entryId = $emptyEntry->id;
	$entryServerNode->serverType = KalturaEntryServerNodeType::LIVE_PRIMARY;
	$returnedObject = $clientMediaServer->entryServerNode->add($entryServerNode);
	success(__FUNCTION__);
	return $returnedObject;
}

function Test2_Get($clientMediaServer, $id)
{
	$clientMediaServer->entryServerNode->get($id);
	return success(__FUNCTION__);
}

function Test3_Update($clientMediaServer, $entryServerNode)
{
	$newEntryServerNode = new KalturaLiveEntryServerNode();
	$newEntryServerNode->serverNodeId = $entryServerNode->serverNodeId + 1;
	$clientMediaServer->entryServerNode->update($entryServerNode->id, $newEntryServerNode);
	return success(__FUNCTION__);
}

function Test4_List($client, $entryId)
{
	$filter = new KalturaLiveEntryServerNodeFilter();
	$filter->entryIdEqual = $entryId;

	$response = $client->entryServerNode->listAction($filter);
	if ( count($response->objects ) > 0 )
		return success(__FUNCTION__);
	else
		return fail(__FUNCTION__);
}

function Test5_Delete($clientMediaServer, $id)
{
	$clientMediaServer->entryServerNode->delete($id);
	return success(__FUNCTION__);
}


goMain();