<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
require_once('apiTestHelper.php');




function helper_createEntryAndUploaDmp4Content($client)
{
	$FILE_NAME_MP4 = dirname ( __FILE__ ).'/../resources/KalturaTestUpload.mp4';
	$entry = addEntry($client,__FUNCTION__);
	$uploadTokenObj = new KalturaUploadToken();
	$uploadTokenObj->fileName = $FILE_NAME_MP4;
	$uploadToken = $client->uploadToken->add($uploadTokenObj);
	$fileData = $FILE_NAME_MP4;
	$result = $client->uploadToken->upload($uploadToken->id,$fileData ,null,null,null);
	$resource = new KalturaUploadedFileTokenResource();
	$resource->token = $uploadToken->id;
	$result = $client->baseEntry->addcontent($entry->id, $resource);
	return $result;
}

function isEntryReady($client,$id)
{
	$result = $client->baseEntry->get($id, null);
	if ($result->status == 2)
		return true;
	return false;
}

function Test1_UploadEntryAndTransferToRemoteStorage($client)
{
	info("Create entry and upload content");
	$MediaEntry = helper_createEntryAndUploaDmp4Content($client);
	info("Wait for entry to be ready id =".$MediaEntry->id);
	while(isEntryReady($client,$MediaEntry->id)!=true)
	{
		sleep(1);
		print (".");
	}
	info("Check That entry exists in remote storage");

	$output = array();
    exec("sshpass -p 'Kaltura12#' ssh root@allinone-be.dev.kaltura.com find .* -name $MediaEntry->id*", $output, $result);
	$res = count($output);
	print ("\r\n array count is: $res");

	if ( $res<1 )
	{
		return fail(__FUNCTION__."Entry was not copied to remote Storage.");
	}
	return success(__FUNCTION__ . "Entry found in remote storage");
}


function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$ret  = Test1_UploadEntryAndTransferToRemoteStorage($client);
	$ret += Test2_CloneAPendingEntry($client);
	return ($ret);
}

goMain();
