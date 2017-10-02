<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');

function Test1_UploadEntryAndTransferToRemoteStorageAndRetriveViaHTTP($client, $storageHost, $storageUserName,$storageUserPassword, $storageUrl ,$storageBaseDir)
{
	info("Create entry and upload content");
	$MediaEntry = createEntryAndUploaDmp4Content($client, 'Test1_UploadEntryAndTransferToRemoteStorageAndRetriveViaHTTP' );
	waitForEntry($client, $MediaEntry->id);
	info("Check That entry exists in remote storage");

	info("Running command to locate entry on remote storage: sshpass -p '$storageUserPassword' ssh $storageUserName@$storageHost find $storageBaseDir -name '$MediaEntry->id*'");
	$output = array();
	sleep(20); // waiting for the SFTP to end as well
	exec("sshpass -p '$storageUserPassword' ssh $storageUserName@$storageHost find $storageBaseDir -name '$MediaEntry->id*'", $output, $result);
	$res = count($output);

	if ( $res<2 ) // for checking SCP and SFTP
		return fail(__FUNCTION__." Entry $MediaEntry->id was not copied to remote Storage. Output is ". print_r($output, true));
	success("Entry $MediaEntry->id exists in 2 remote storage ");

	$result = true;
	foreach($output as $path)
	{
		print("\n\r found entry location in remote storage: $path");		
		list($var, $value) = explode($storageBaseDir, $path);
		$result &= checkRemoteStorageFile($storageUrl. $value);
	}

	if (!$result){
		return fail(__FUNCTION__." Didnt found 2 file in the remote storage.");
	}
	return success(__FUNCTION__ .". \n\r Remote storage export and import for Entry $MediaEntry->id finished successfully");
}

function checkRemoteStorageFile($httpRequest)
{
	$command = "curl --head $httpRequest | grep \"200 OK\"";
	print("\n\r Validate http request for uploaded media in remote server: \n executing the following request: $command");
	exec($command, $output1, $result);	
	return !$result;
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
