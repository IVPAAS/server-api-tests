<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
require_once('apiTestHelper.php');

// Missing prerequisites
// Configure Partner -5 to have live_stream_outputs of size 10
const MEDIA_SERVER_INDEX = 1 ; // can not automatically create it so using an existing one

function main($dc,$partnerId,$batchSecret,$mediaServerSecret)
{
	warning("This test require admin secret and media server secret (partner -5) and the batch one (-1)");
	$clientMediaServer = startKalturaSession(-5,$mediaServerSecret,$dc);
	$clientBatch = startKalturaSession(-1,$batchSecret,$dc);
	// we cannot use this function since the server node cannot be changed through API to active
	// validateServerNodeExists($clientMediaServer, $dc);
	$entry = Test_AddLiveStreamEntry($clientMediaServer);
	Test_RegisterMediaServer($clientMediaServer, $entry, $dc);
	Test_ValidateRegisterMediaServer($clientBatch, $entry);
	Test_AuthenticateMediaServer($clientMediaServer, $entry);
	Test_UnregisterMediaServer($clientMediaServer, $entry, $dc);
	Test_IsLive($clientMediaServer, $entry);
	Test_AddLiveStreamEntryWithLiveStatus($clientMediaServer);
	return 0;
}

function validateServerNodeExists(KalturaClient $client, $hostname)
{
	try{
		helper_get_server_node_id($client, $hostname);
	} catch(Exception $e) {
		helper_create_server_node($client, $hostname);
	}

}

function helper_create_server_node(KalturaClient $client, $hostname)
{
	$serverNode = new KalturaWowzaMediaServerNode();
	$serverNode->hostName = $hostname;
	$serverNode->name = "MyTestServer";
	return $client->serverNode->add($serverNode);
}

function helper_get_server_node_id(KalturaClient $client, $hostname)
{
	$serverNodeFilter = new KalturaWowzaMediaServerNodeFilter();
	$serverNodeFilter->hostNameLike = $hostname;
	$response = $client->serverNode->listAction($serverNodeFilter);
	if ($response->totalCount != 1)
		throw new Exception("Failed getting the server node");
	return $response->objects[0];
}

function Test_AddLiveStreamEntry(KalturaClient $client)
{
	$liveStreamEntry = new KalturaLiveStreamEntry();
	$liveStreamEntry->type = KalturaEntryType::LIVE_STREAM;
	$liveStreamEntry->mediaType = KalturaMediaType::LIVE_STREAM_REAL_MEDIA;
	$entry = $client->liveStream->add($liveStreamEntry, KalturaSourceType::LIVE_STREAM);
	success(__FUNCTION__);
	return $entry;
}

function Test_RegisterMediaServer(KalturaClient $client, KalturaLiveStreamEntry $liveStreamEntry, $hostname)
{
	 // prerequisite server node active (since you cannot create one with status active)
	$client->liveStream->registerMediaServer($liveStreamEntry->id, $hostname, MEDIA_SERVER_INDEX);
	return success(__FUNCTION__);
}

function Test_AuthenticateMediaServer(KalturaClient $client, KalturaLiveStreamEntry $liveStreamEntry)
{
	// prerequisite partner with live_stream_outputs of size 10
	$client->liveStream->authenticate($liveStreamEntry->id, $liveStreamEntry->streamPassword);
	return success(__FUNCTION__);
}

function Test_ValidateRegisterMediaServer(KalturaClient $client, KalturaLiveStreamEntry $liveStreamEntry)
{
	$client->liveStream->validateRegisteredMediaServers($liveStreamEntry->id);
	return success(__FUNCTION__);
}

function Test_UnregisterMediaServer(KalturaClient $client, KalturaLiveStreamEntry $liveStreamEntry, $hostname)
{
	$client->liveStream->unregisterMediaServer($liveStreamEntry->id, $hostname, MEDIA_SERVER_INDEX);
	return success(__FUNCTION__);
}

function Test_IsLive(KalturaClient $client, KalturaLiveStreamEntry $liveStreamEntry)
{
	$client->liveStream->isLive($liveStreamEntry->id, KalturaPlaybackProtocol::HTTP);
	return success(__FUNCTION__);
}

function Test_AddLiveStreamEntryWithLiveStatus(KalturaClient $client)
{
	$liveStreamEntry = new KalturaLiveStreamEntry();
	$liveStreamEntry->type = KalturaEntryType::LIVE_STREAM;
	$liveStreamEntry->mediaType = KalturaMediaType::LIVE_STREAM_REAL_MEDIA;
	$entry = $client->liveStream->add($liveStreamEntry, KalturaSourceType::LIVE_STREAM);
	success(__FUNCTION__);
	return $entry;
}

goMain();