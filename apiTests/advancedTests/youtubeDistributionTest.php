<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__).'/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');

function addDistribution($client, $mediaId, $DistributionProfileId)
{
    $entryDistribution = new KalturaEntryDistribution();
    $entryDistribution->entryId = $mediaId;
    $entryDistribution->distributionProfileId = $DistributionProfileId;
    $entryDistribution = $client->entryDistribution->add($entryDistribution);
    return $client->entryDistribution->submitAdd($entryDistribution->id);
}

function waitWhileSubmitting($client,$entryDistributionId)
{
    for ($i=0; $i< 120; $i++)
    {
        if (!entryDistributionIsSubmitting($client,$entryDistributionId))
            break;
        sleep(1);
        print (".");
    }
}

function checkDistributionForEntry($client, $entryId, $DistributionProfileId)
{
    //start youtube distribution
    $entryDistribution = addDistribution($client, $entryId, $DistributionProfileId);
    $entryDistributionId = $entryDistribution->id;
    info("Distributing To Youtube ". $entryId . " with entryDistributionId [$entryDistributionId]");
    waitWhileSubmitting($client, $entryDistributionId);

    $entryDistribution = $client->entryDistribution->get($entryDistributionId);
    if ($entryDistribution->status != 2)
        return fail(__FUNCTION__." Distribution Failed");

    info("Deleting youtube entry distribution");
    $client->entryDistribution->submitDelete($entryDistributionId);

    info($entryId . " Youtube Distribution Succeeded" );
    return success(__FUNCTION__);
}

function Test2_YoutubeEntryDistributeWithCaption($client, $DistributionProfileId)
{
    info("Create entry and upload content caption");
    $MediaEntry = createEntryAndUploaDmp4Content($client, 'youTubeDistributionTest');
    addCaptionToEntry($client, $MediaEntry->id, '/../../resources/KalturaTestCaption.srt');
    waitForEntry($client,$MediaEntry->id);

    return checkDistributionForEntry($client, $MediaEntry->id, $DistributionProfileId);
}

function Test3_YoutubeEntryDistributeWithThumbAsset($client, $DistributionProfileId)
{
    info("Create entry and upload content with 300X150 thumb asset");
    $MediaEntry = createEntryAndUploaDmp4Content($client, 'youTubeDistributionTest');
    uploadThumbAsset($client, $MediaEntry->id);
    waitForEntry($client,$MediaEntry->id);

    return checkDistributionForEntry($client, $MediaEntry->id, $DistributionProfileId);
}

function Test1_YoutubeEntryDistribute($client, $DistributionProfileId)
{
    info("Create entry and upload content");
    $MediaEntry = createEntryAndUploaDmp4Content($client, 'youTubeDistributionTest');
    return checkDistributionForEntry($client, $MediaEntry->id, $DistributionProfileId);
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
    $ret += Test2_YoutubeEntryDistributeWithCaption($client, $distributionProfileId);
    $ret += Test3_YoutubeEntryDistributeWithThumbAsset($client, $distributionProfileId);
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
