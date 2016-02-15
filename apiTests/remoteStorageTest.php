<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
require_once('apiTestHelper.php');


function Test1_UploadEntryAndTransferToRemoteStorageAndRetriveViaHTTP($client, $storageHost, $storageUserName,$storageUserPassword, $storageUrl ,$storageBaseDir)
{
	info("Create entry and upload content");
	$MediaEntry = helper_createEntryAndUploaDmp4Content($client, 'Test1_UploadEntryAndTransferToRemoteStorageAndRetriveViaHTTP' );
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
	success("Entry  $MediaEntry->id exists in remote storage");

	foreach ($output as $item) {
		print("\n\r found entry location in remote storage: $item");
		$arr = explode($storageBaseDir, $item);
		$important = $arr[1];
	}

	$httpRequest = $storageUrl.$important ;
	$command = "curl --head $httpRequest | grep \"200 OK\"";
	print("\n\r Validate http request for uploaded media in remote server: \n executing the following request: $command");

	exec($command, $output1, $result);

	if ($result != 0){
		return fail(__FUNCTION__." Command: $command failed.");
	}
	return success(__FUNCTION__ .". \n\r Remote storage export and import for Entry $MediaEntry->id finished successfully");
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
	print ("\n\r This test should run when the following prerequisites are configured:\n\r
	1. Partner is configured.
	2. RemoteStorageProfile is configured and enabled on automatic export mode.\r\n
	3. DeliveryProfile is created, enabled and assigned to the remoteStorage \n\r
	4. DeliveryProfile and RemoteStorageProfile should be assigned to the created Partner \n\r
	5. Storage host is configured and web service is configured to allow permissions in the storageBaseDir configured to http requests \r\n");

}
