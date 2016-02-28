<?php
//require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
require_once('/opt/kaltura/web/content/clientlibs/php5API_Testing/KalturaClient.php');
require_once('runAllTestsHelper.php');

main();

function runAllTests($dc,$userName,$userPassword)
{

  HTMLLogger::init();
  $time_start = microtime(true);

  $TotalCount = 0;
  $failedCount = 0;
  $res = 0;

  try {

    print("\n*********************************************");
    printInfoAndlogOutput("Running All Tests - " . date("F j, Y, g:i a"));
    print("\n*********************************************\n");
    info("\n********** runUserCategoryTest **************");
    $TotalCount++;
    $failedCount = $failedCount + runUserCategoryTest($dc, $userName, $userPassword);

    info("\n********** runCrossKalturaDistributionTest **");
    $TotalCount++;
    $failedCount = $failedCount + runCrossKalturaDistributionTest($dc, $userName, $userPassword);

    info("\n********** runYoutubeDistributionTest **");
    $TotalCount++;
    $failedCount = $failedCount + runYoutubeDistributionTest($dc, $userName, $userPassword);

    info("\n********** runRemoteStorageDistributionTest **");
    $TotalCount++;
    $failedCount = $failedCount + runRemoteStorageExportAndImportTest($dc,$userName,$userPassword, 'allinone-be.dev.kaltura.com', 'root', 'Kaltura12#', '../var/www/html/testingStorage/');

    info("\n********** runVideoQuizTest *****************");
    $TotalCount++;
    $failedCount = $failedCount + runInVideoQuizTest($dc, $userName, $userPassword);

    info("\n********** runListEntriesTest ***************");
    $TotalCount++;
    $failedCount = $failedCount + runListEntriesTest($dc, $userName, $userPassword);

    info("\n********** runCloneEntryTest ****************");
    $TotalCount++;
    $failedCount = $failedCount + runCloneEntryTest($dc, $userName, $userPassword);

    info("\n********** runLiveEntryTest *****************");
    $TotalCount++;
    $failedCount = $failedCount + runLiveEntryTest($dc, $userName, $userPassword);

    info("\n********** runCloneEntryWithCuePointsTest ******");
    $TotalCount++;
    $failedCount = $failedCount + runCloneEntryWithCuePointsTest($dc, $userName, $userPassword);

    print("\n*********************************************");
    printInfoAndlogOutput("\nRunning All Tests Finished - " . date("F j, Y, g:i a"));
    print("\n*********************************************\n");

  }
  catch(Exception $e)
  {
    $failedCount++;
    $res = FAIL;
  }

  $time_end = microtime(true);
  $execution_time = ($time_end - $time_start)/60;
  printInfoAndlogOutput("Total Tests:$TotalCount      Successful:".($TotalCount-$failedCount)."           Failed:$failedCount   Execution-Time:".$execution_time. " Mins\n");

  HTMLLogger::close();
  if ($res) {
    exit(FAIL);
  }
}

function runInVideoQuizTest($dc,$userName,$userPassword)
{
  try {
    info("InVideoQuizTests init.");
    $client = login($dc, $userName, $userPassword);
    $testPartner = createTestPartner($client, "testPartner");
    addPartnerPermissions($client, $testPartner, "QUIZ_PLUGIN_PERMISSION", KalturaPermissionStatus::ACTIVE);

    info("Executing InVideoQuizTests...");
    $output = array();
    exec("php InVideoQuizTests.php $dc $testPartner->id $testPartner->adminSecret $testPartner->secret", $output, $result);
    foreach ($output as $item) {
      print("\n\r $item");
    }
  } catch (Exception $e) {
    fail(" InVideoQuizTests failed: $e");
    $result = 1;
  }
  //finally{
  if ($testPartner != null) {
    info("\n\r InVideoQuizTests tear down.");
    $client = login($dc, $userName, $userPassword);
    removePartner($dc, $client, $testPartner);
  }
  //}
  if ($result) {
    printFailAndlogOutput("InVideoQuizTests");
    return FAIL;
  }
  printSuccessAndlogOutput("InVideoQuizTests");
}

function runLiveEntryTest($dc,$userName,$userPassword)
{
  try {
    print("\n\r liveEntryTests init.");
    $client = login($dc, $userName, $userPassword);
    $testPartner = createTestPartner($client, "testPartner");
    addPartnerPermissions($client, $testPartner, "QUIZ_PLUGIN_PERMISSION", KalturaPermissionStatus::ACTIVE);
    $conversionProfile = getConversionProfileForSpecficPartner($client, $testPartner->id, 'Passthrough', KalturaConversionProfileType::LIVE_STREAM);
    setDefaultConversionProfile($dc, $testPartner, $conversionProfile->id);

    info(" executing liveEntryTests...");
    $client = login($dc, $userName, $userPassword);
    $liveStreamPartner = getPartner($client, '-5'); //get the live streaming partner required for the test
    $output = array();
    exec("php liveEntryTests.php $dc $testPartner->id $testPartner->adminSecret $liveStreamPartner->adminSecret", $output, $result);
    foreach ($output as $item) {
      print("\n\r $item");
    }
  } catch (Exception $e) {
    fail(" liveEntryTests failed: $e");
    $result = 1;
  }
  // finally{
  if ($testPartner != null) {
    info(" liveEntryTest tear down.");
    $client = login($dc, $userName, $userPassword);
    removePartner($dc, $client, $testPartner);
  }
  //}
  if ($result) {
    printFailAndlogOutput("liveEntryTests");
    return FAIL;
  }
  printSuccessAndlogOutput("liveEntryTests");
}


function runListEntriesTest($dc,$userName,$userPassword)
{
  try {
    print("\n\r listEntriesTest init.");
    $client = login($dc, $userName, $userPassword);
    $testPartner = createTestPartner($client, "testPartner");

    info(" executing listEntriesTest...");
    $output = array();
    exec("php listEntriesTest.php $dc $testPartner->id $testPartner->adminSecret  $testPartner->secret", $output, $result);
    foreach ($output as $item) {
      print("\n\r $item");
    }
  } catch (Exception $e) {
    fail(" listEntriesTest failed: $e");
    $result = 1;
  }
  // finally{
  if ($testPartner != null) {
    info(" listEntriesTest tear down.");
    $client = login($dc, $userName, $userPassword);
    removePartner($dc, $client, $testPartner);
  }
  // }
  if ($result) {
    printFailAndlogOutput("listEntriesTest");
    return FAIL;
  }
  printSuccessAndlogOutput("listEntriesTest");
}

function runCloneEntryTest($dc,$userName,$userPassword)
{
  try {
    print("\n\r cloneEntryTest init.");
    $client = login($dc, $userName, $userPassword);
    $testPartner = createTestPartner($client, "testPartner");

    info(" executing cloneEntryTest...");
    $output = array();
    exec("php cloneEntryTest.php $dc $testPartner->id $testPartner->adminSecret  $testPartner->secret", $output, $result);
    foreach ($output as $item) {
      print("\n\r $item");
    }
  } catch (Exception $e) {
    fail(" cloneEntryTest failed: $e");
    $result = 1;
  }
  //finally{
  if ($testPartner != null) {
    info(" cloneEntryTest tear down.");
    $client = login($dc, $userName, $userPassword);
    removePartner($dc, $client, $testPartner);
  }
  //}
  if ($result) {
    printFailAndlogOutput("cloneEntryTest");
    return FAIL;
  }
  printSuccessAndlogOutput("cloneEntryTest");
}

function runUserCategoryTest($dc,$userName,$userPassword)
{
  try {
    print("\n\r userCategoryTest init.");
    $client = login($dc, $userName, $userPassword);
    $testPartner = createTestPartner($client, "testPartner");

    info(" executing userCategoryTest...");
    $output = array();
    exec("php userCategoryTest.php $dc $testPartner->id $testPartner->adminSecret  $testPartner->secret", $output, $result);
    foreach ($output as $item) {
      print("\n\r $item");
    }
  } catch (Exception $e) {
    fail(" userCategoryTest failed: $e");
    $result = 1;
  }
  // finally{
  if ($testPartner != null) {
    info(" userCategoryTest tear down.");
    $client = login($dc, $userName, $userPassword);
    removePartner($dc, $client, $testPartner);
  }
  //}
  if ($result) {
    printFailAndlogOutput("userCategoryTest");
    return FAIL;
  }
  printSuccessAndlogOutput("userCategoryTest");
}

function runCloneEntryWithCuePointsTest($dc,$userName,$userPassword)
{
  try {
    print("\n\r cloneEntryWithCuePointsTest init.");
    $client = login($dc, $userName, $userPassword);
    $testPartner = createTestPartner($client, "testPartner");

    info(" executing cloneEntryWithCuePointsTest ...");
    $output = array();
    exec("php cloneEntrywithCuePointsTest.php $dc $testPartner->id $testPartner->adminSecret  $testPartner->secret", $output, $result);
    foreach ($output as $item) {
      print("\n\r $item");
    }
  } catch (Exception $e) {
    fail(" cloneEntryWithCuePointsTest failed:  $e");
    $result = 1;
  }
  //finally {
  if ($testPartner != null) {
    info(" cloneEntryWithCuePointsTest tear down.");
    $client = login($dc, $userName, $userPassword);
    removePartner($dc, $client, $testPartner);
  }
  // }
  if ($result) {
    printFailAndlogOutput("cloneEntryWithCuePointsTest");
    return FAIL;
  }
  printSuccessAndlogOutput("cloneEntryWithCuePointsTest");
}

function runCrossKalturaDistributionTest($dc,$userName,$userPassword)
{
  try {
    print("\n\r crossKalturaDistributionTest init.");
    $client = login($dc, $userName, $userPassword);
    $sourceTestPartner = createTestPartner($client, "testUser1");
    $targetTestPartner = createTestPartner($client, "testUser2");

    addPartnerPermissions($client, $sourceTestPartner, "CONTENTDISTRIBUTION_PLUGIN_PERMISSION", KalturaPermissionStatus::ACTIVE);
    addPartnerPermissions($client, $targetTestPartner, "CONTENTDISTRIBUTION_PLUGIN_PERMISSION", KalturaPermissionStatus::ACTIVE);

    $tempPartnerPassword = '!Trz271985';
    resetPartnerPassword($client, $targetTestPartner, $tempPartnerPassword);

    $sourceConversionProfile = createConversionProfileForSpecficPartner($dc, $sourceTestPartner, 'testConversionProfile1');
    $targetConversionProfile = createConversionProfileForSpecficPartner($dc, $targetTestPartner, 'testConversionProfile2');

    $sourceAccessControlProfile = getDefaultAccessControlProfile($dc, $sourceTestPartner);
    $targetAccessControlProfile = getDefaultAccessControlProfile($dc, $targetTestPartner);

    $distributionProfile = createCrossKalturaDistributionProfile($client, $dc, $sourceTestPartner, $targetTestPartner, $sourceAccessControlProfile, $targetAccessControlProfile, $sourceConversionProfile, $targetConversionProfile, $tempPartnerPassword);

    info(" executing crossKalturaDistributionTest ...");
    $output = array();
    exec("php crossKalturaDistributionTest.php $dc $sourceTestPartner->id $sourceTestPartner->adminSecret $targetTestPartner->id $targetTestPartner->adminSecret $distributionProfile->id", $output, $result);
    foreach ($output as $item) {
      print("\n\r $item");
    }
  }
catch (Exception $e) {
    fail(" crossKalturaDistributionTest failed: $e");
    $result = 1;
  }
  //finally {
  info(" crossKalturaDistributionTest tear down.");
  if ($sourceTestPartner != null) {
    $client = login($dc, $userName, $userPassword);
    removePartner($dc, $client, $sourceTestPartner);
  }
  if ($targetTestPartner != null) {
    $client = login($dc, $userName, $userPassword);
    removePartner($dc, $client, $targetTestPartner);
  }
  //}
  if ($result) {
    printFailAndlogOutput("crossKalturaDistributionTest");
    return FAIL;
  }
  printSuccessAndlogOutput("crossKalturaDistributionTest");
}


function runRemoteStorageExportAndImportTest($dc,$userName,$userPassword, $remoteHost, $storageUsername, $storageUserPassword, $storageBaseDir)
{
  try {
    print("\n\r remoteStorageTest init.");
    $client = login($dc, $userName, $userPassword);
    $testPartner = createTestPartner($client, "testPartner");
    $storageUrl = 'http://'.$remoteHost.':90/testingStorage/';
    updatePartnerWithRemoteStoragePriority ($client, $testPartner->id, KalturaStorageServePriority::EXTERNAL_ONLY , 1 );
    $deliveryProfile = createDeliveryProfile($client, $testPartner->id, "testDeliveryProfile", KalturaDeliveryProfileType::HTTP, KalturaPlaybackProtocol::HTTP, $storageUrl , KalturaDeliveryStatus::ACTIVE);
    print ("\n\r delivery profile id: $deliveryProfile->id");
    $remoteStorageProfile = createRemoteStorageProfile($client, $testPartner->id, "testStorage", KalturaStorageProfileStatus::AUTOMATIC, KalturaStorageProfileProtocol::SCP, $remoteHost,
        $storageBaseDir, $storageUsername, $storageUserPassword, KalturaStorageProfileDeliveryStatus::ACTIVE, 'kExternalPathManager', $deliveryProfile);

    print ("\n\r remote profile id: $remoteStorageProfile->id");
    info(" executing remoteStorageTest...");
    $output = array();
    exec("php remoteStorageTest.php $dc $testPartner->id $testPartner->adminSecret $remoteHost $storageUsername $storageUserPassword $storageUrl $storageBaseDir", $output, $result);
    foreach ($output as $item) {
      print("\n\r $item");
    }
  } catch (Exception $e) {
    fail(" remoteStorageTest failed: $e");
    $result = 1;
  }
   //finally{
  if ($testPartner != null) {
    info(" remoteStorageTest tear down.");
    $client = login($dc, $userName, $userPassword);
    removePartner($dc, $client, $testPartner);
  }
  //}
  if ($result) {
    printFailAndlogOutput("remoteStorageTest");
    return FAIL;
  }
  printSuccessAndlogOutput("remoteStorageTest");
}


function runYoutubeDistributionTest($dc,$userName,$userPassword)
{
  try {
    print("\n\r youtubeDistributionTest init.");
    $client = login($dc, $userName, $userPassword);

    $testPartner = getPartner($client, '99'); //partner 99 should be manually configured to have a enabled youTubeDistributionProfile with a connection a youtube account for video upload.
    // follow the wiki page for info on how to create the distribution profile
    // https://kaltura.atlassian.net/wiki/display/QAC/How+to+create+a+YouTubeAPI+distribution+profile
    $youTubeDistributionProfileId = 39; // the youtube distribution profile configured manually

    addPartnerPermissions($client, $testPartner, "CONTENTDISTRIBUTION_PLUGIN_PERMISSION", KalturaPermissionStatus::ACTIVE);

    info(" executing youtubeDistributionTest ...");
    $output = array();
    exec("php youtubeDistributionTest.php $dc $testPartner->id $testPartner->adminSecret $youTubeDistributionProfileId ", $output, $result);
    foreach ($output as $item) {
      print("\n\r $item");
    }
  } catch (Exception $e) {
    fail(" youtubeDistributionTest failed: $e");
    $result = 1;
  }
  // No need to remove the default template partner (99) but we need to remove the entries we upladed
  markBaseEntriesForPartnersAsDeleted($dc, $testPartner);
  removeDeletedBaseEntriesForPartnerFromFileSystem($testPartner); //can only be used on server side invocation
  if ($result) {
    printFailAndlogOutput("youtubeDistributionTest");
    return FAIL;
  }
  printSuccessAndlogOutput("youtubeDistributionTest");
}



