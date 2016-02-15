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

function Test1_UploadEntryAndTransferToRemoteStorageAndRetriveViaHTTP($client, $storageHost, $storageUserName,$storageUserPassword, $storageUrl ,$storageBaseDir)
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
    exec("sshpass -p '$storageUserPassword' ssh $storageUserName@$storageHost find .* -name $MediaEntry->id*", $output, $result);
	$res = count($output);

	if ( $res<1 )
	{
		return fail(__FUNCTION__."Entry $MediaEntry->id was not copied to remote Storage.");
	}

	foreach ($output as $item) {
		print("\n\r found entry location in remote storage: $item");
		$arr = explode($storageBaseDir, $item);
		$important = $arr[1];
		print("Stroage baseDir is : $storageBaseDir, output of splitting is: $item");
	}

	$command = "curl --head $storageUrl$important | grep \"200 OK\"";
	exec($command, $output, $result);
	if ($result != 0){
		return fail(__FUNCTION__." Command: $command failed.");
	}
	return success(__FUNCTION__ ." Entry $MediaEntry->id remote storage export and import finished successfully");
}


function main($dc,$partnerId,$adminSecret, $storageHost, $storageUserName,$storageUserPassword, $storageUrl, $storageBaseDir)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$ret  = Test1_UploadEntryAndTransferToRemoteStorageAndRetriveViaHTTP($client, $storageHost, $storageUserName,$storageUserPassword, $storageUrl, $storageBaseDir );
	return ($ret);
}

goMain2();

function goMain2()
{
	if ($GLOBALS['argc']!=9 )
	{
		printUsage2();
		exit (1);
	}

	$dcUrl 			= 	$GLOBALS['argv'][1];
	$partnerId 		= 	$GLOBALS['argv'][2];
	$adminSecret	= 	$GLOBALS['argv'][3];
	$storageHost    = 	$GLOBALS['argv'][4];
	$storageUserName  = 	$GLOBALS['argv'][5];
	$storageUserPassword  = 	$GLOBALS['argv'][6];
	$storageUrl 	 = 	$GLOBALS['argv'][7];
	$storageBaseDir  = 	$GLOBALS['argv'][8];
	$res =  main($dcUrl,$partnerId,$adminSecret, $storageHost, $storageUserName,$storageUserPassword, $storageUrl, $storageBaseDir);
	exit($res);
}

function printUsage2()
{
	print ("\n\rUsage: " .$GLOBALS['argv'][0] . " <DC URL> 	<parnter id> <storageHost> <storageUserName> <storageUserPassword> <storageUrl> <storageBaseDir>");
	print ("\n\r for remotoe Storage Testing.\r\n");
	print ("\n\r This test should run when the following prerequisits are configured:\n\r
	1. Partner is configured.
	2. RemoteStorageProfile is configured and enabled on automatic export mode.\r\n
	3. DeliveryProfile is created, enabled and assigned to the remoteStorage \n\r
	4. ");


}
