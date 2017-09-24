<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__).'/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');

function Test1_YoutubeEntryDistribute($client, $DistributionProfileId)
{
    info("Create entry and upload content");
    $MediaEntry = createEntryAndUploaDmp4Content($client, 'youTubeDistributionTest');

    info("Upload 300X150 thumb asset");
    uploadThumbAsset($client, $MediaEntry->id);
	waitForEntry($client,$MediaEntry->id);
    //start youtube distribution
	$entryDistribution = new KalturaEntryDistribution();
	$entryDistribution->entryId = $MediaEntry->id;
	$entryDistribution->distributionProfileId = $DistributionProfileId;
	$entryDistribution = $client->entryDistribution->add($entryDistribution);
    $entryDistribution = $client->entryDistribution->submitAdd($entryDistribution->id);

    info("Distributing To Youtube ".$MediaEntry->id);
    while(entryDistributionIsSubmitting($client,$entryDistribution->id))
    {
        sleep(1);
        print (".");
    }
    $maxCount=30;

    $entryDistribution = $client->entryDistribution->get($entryDistribution->id);
    if ($entryDistribution->status != 2)
    {
        return fail(__FUNCTION__." Distribution Failed");
    }

    info("Deleting youtube entry distribution");
    $client->entryDistribution->submitDelete($entryDistribution->id);
    
    info($MediaEntry->id . " Youtube Distribution Succeeded" );
    return success(__FUNCTION__);
}

function printTestUsage()
{
    print ("\n\rUsage: " .$GLOBALS['argv'][0] . " <DC URL> <partner ID> <admin secret> <YouTube distribution profile ID>");
    print ("\n\r for distribution entry cross Kaltura.\r\n");
    print ("\n\r * Note: target partner ID should be identical to target account in given distribution profile\r\n");
    print ("\n\r * Note: Youtube distribution should be created ,authenticated and enabled, manually prior to running this test\r\n");
    print ("\n\r * For info on how to create a youtube distribution profile please see the following link:\r\n");
    print ("\n\r * https://kaltura.atlassian.net/wiki/display/QAC/How+to+create+a+YouTubeAPI+distribution+profile \r\n");
}


function main( $dc, $partnerId, $adminSecret, $distributionProfileId )
{
    $client = startKalturaSession($partnerId,$adminSecret,$dc);
    $ret  = Test1_YoutubeEntryDistribute($client, $distributionProfileId);
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

    $res 				=  main($dcUrl,$partnerId,$adminSecret, $profileId);
    exit($res);
}

go();
