<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname( __FILE__ ).'/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');

function Test1_DistributeEntry($client, $profileId)
{
	info("Create entry and upload content");
	$MediaEntry = createEntryAndUploaDmp4Content($client, 'tvinciDistributionTest');

	waitForEntry($client, $MediaEntry->id);
	//start tvinci distribution
	$entryDistribution = new KalturaEntryDistribution();
	$entryDistribution->entryId = $MediaEntry->id;
	$entryDistribution->distributionProfileId = $profileId;
	$entryDistribution = $client->entryDistribution->add($entryDistribution);
	$entryDistribution = $client->entryDistribution->submitAdd($entryDistribution->id);

	info("Distributing ".$MediaEntry->id);
	while(entryDistributionIsSubmitting($client,$entryDistribution->id))
	{
		sleep(1);
		print (".");
	}

	$timeout = 20;
	do
	{
        	$entryDistribution = $client->entryDistribution->get($entryDistribution->id);
        	sleep(1);
        	print (".".$timeout);
        	$timeout--;
	}
	while($entryDistribution->status==5 && $timeout>0 );

	if ($entryDistribution->status != 2)
	{
		return fail(__FUNCTION__." Distribution Failed");
	}

	//delete distribution
	$entryDistribution = $client->entryDistribution->submitDelete($entryDistribution->id);

	info("Removing distribution entry ".$MediaEntry->id);
	while(isRemoving($client,$entryDistribution->id))
	{
		sleep(1);
		print (".");
	}

	$entryDistribution = $client->entryDistribution->get($entryDistribution->id);
	if ($entryDistribution->status != 10)
	{
		return fail(__FUNCTION__." Removing Distribution Failed");
	}

	info($MediaEntry->id . "Removing Distribution Succeeded" );


	return success(__FUNCTION__);
}


function printTestUsage()
{
	print ("\n\rUsage: " .$GLOBALS['argv'][0] . " <DC URL> <partner ID> <admin secret> <tvinci distribution profile ID>");
	print ("\n\r for distribution entry to tvinci.\r\n");
}


function main( $dc, $partnerId, $adminSecret,$profileId )
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);

	$ret  = Test1_DistributeEntry($client, $profileId);
	return ($ret);
}

function go()
{
	if ($GLOBALS['argc']!=5 )
	{
		printTestUsage();
		exit (1);
	}

	$dcUrl 				= 	$GLOBALS['argv'][1];
	$partnerId 			= 	$GLOBALS['argv'][2];
	$adminSecret		= 	$GLOBALS['argv'][3];
	$profileId	   		= 	$GLOBALS['argv'][4];

	$res 				=  main($dcUrl,$partnerId,$adminSecret,$profileId);
	exit($res);
}

go();
