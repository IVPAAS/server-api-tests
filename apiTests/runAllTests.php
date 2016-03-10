<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__).'/testsHelpers/runAllTestsHelper.php');

main();

$TotalCount = 0;
$failedCount = 0;
$paramsMap = array();

function runAllTests($dc,$userName,$userPassword)
{
  global $TotalCount, $failedCount, $paramsMap;

  HTMLLogger::init();
  initParams($dc, $userName, $userPassword);

  $time_start = microtime(true);

  $res = 0;

  try {

    print("\n*********************************************");
    printInfoAndlogOutput("Running All Tests - " . date("F j, Y, g:i a"));
    print("\n*********************************************\n");

    // Run all basic tests that require only partner creation

    $di = new RecursiveDirectoryIterator('basicTests');
    foreach (new RecursiveIteratorIterator($di) as $filename => $file) {
      if (is_file($filename))  {
        $TotalCount++;
        $failedCount = $failedCount + runBasicTest($dc, $userName, $userPassword, basename($filename, ".php"), $filename);
      }
    }

    // Run Advanced Tests
    $ini = parse_ini_file(dirname(__FILE__).'/testsConfig.ini');
    foreach ($ini['advanced-test'] as $advanced){
      print ($advanced. "\n");
      runTest($advanced, array( $paramsMap["dc"], $paramsMap["username"], $paramsMap["userPass"]));
    }

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

function initParams($dc, $userName, $userPassword){
  global $paramsMap;

  $paramsMap["dc"] = $dc;
  $paramsMap["username"] = $userName;
  $paramsMap["userPass"] = $userPassword;
  $paramsMap["remoteStorageBaseDir"] = '../var/www/html/testingStorage/';
  $paramsMap["remoteStoragePassword"] = 'Kaltura12#';
  $paramsMap["remoteStorageUser"] = 'root';
  $paramsMap["remoteStorageHost"] = 'allinone-be.dev.kaltura.com';
}

function runTest($testName, $testParams)
{
  global $TotalCount,$failedCount;
  info("\n********* $testName ******************");
  $TotalCount++;

  $failedCount = $failedCount + call_user_func_array($testName, $testParams);
}

function runBasicTest($dc,$userName,$userPassword, $testName, $testPath)
{
  try {
    info("\n********** Running  $testName **************");
    print("\n\r $testName init.");
    $client = login($dc, $userName, $userPassword);
    $testPartner = createTestPartner($client, "testPartner");

    info(" executing $testName...");
    $output = array();
    exec("php $testPath $dc $testPartner->id $testPartner->adminSecret  $testPartner->secret", $output, $result);
    foreach ($output as $item) {
      print("\n\r $item");
    }
  } catch (Exception $e) {
    fail(" $testName failed: $e");
    $result = 1;
  }
  // finally{
  if ($testPartner != null) {
    info(" $testName tear down.");
    $client = login($dc, $userName, $userPassword);
    removePartner($dc, $client, $testPartner);
  }
  // }
  if ($result) {
    printFailAndlogOutput("$testName");
    return FAIL;
  }
  printSuccessAndlogOutput("$testName");
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
    exec("php advancedTests/liveEntryTests.php $dc $testPartner->id $testPartner->adminSecret $liveStreamPartner->adminSecret", $output, $result);
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
    exec("php advancedTests/crossKalturaDistributionTest.php $dc $sourceTestPartner->id $sourceTestPartner->adminSecret $targetTestPartner->id $targetTestPartner->adminSecret $distributionProfile->id", $output, $result);
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


function runRemoteStorageExportAndImportTest($dc,$userName,$userPassword)
{
  global $paramsMap;
  try {
    $remoteHost = $paramsMap["remoteStorageHost"];
    $storageUsername = $paramsMap["remoteStorageUser"];
    $storageUserPassword = $paramsMap["remoteStoragePassword"];
    $storageBaseDir = $paramsMap["remoteStorageBaseDir"] ;

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
    exec("php advancedTests/remoteStorageTest.php $dc $testPartner->id $testPartner->adminSecret $remoteHost $storageUsername $storageUserPassword $storageUrl $storageBaseDir", $output, $result);
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
    exec("php advancedTests/youtubeDistributionTest.php $dc $testPartner->id $testPartner->adminSecret $youTubeDistributionProfileId ", $output, $result);
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


function runTvinciDistributionTest($dc,$userName,$userPassword)
{
  try {
    print("\n\r tvinciDistributionTest init.");
    $client = login($dc, $userName, $userPassword);
    $testPartner = createTestPartner($client, "testUser");

    addPartnerPermissions($client, $testPartner, "CONTENTDISTRIBUTION_PLUGIN_PERMISSION", KalturaPermissionStatus::ACTIVE);

    $tvinciDistributionProfile = createTvinciDistributionProfile($client, $testPartner);

    info(" executing tvinciDistributionTest ...");
    $output = array();
    exec("php advancedTests/tvinciDistributionTest.php $dc $testPartner->id $testPartner->adminSecret $tvinciDistributionProfile->id ", $output, $result);
    foreach ($output as $item) {
      print("\n\r $item");
    }
  }
  catch (Exception $e) {
    fail(" tvinciDistributionTest failed: $e");
    $result = 1;
  }
  //finally {
  info(" tvinciDistributionTest tear down.");
  if ($testPartner != null) {
    $client = login($dc, $userName, $userPassword);
    //removePartner($dc, $client, $testPartner);
  }
  //}
  if ($result) {
    printFailAndlogOutput("tvinciDistributionTest");
    return FAIL;
  }
  printSuccessAndlogOutput("tvinciDistributionTest");
}

function runFairplayDRMProfileTest($dc,$userName,$userPassword)
{
  try {
    print("\n\r fairplayDrmProfileTest init.");
    info(" executing fairplayDrmProfileTest ...");
    $output = array();
    exec("php advancedTests/testForFairplayDRMProfile.php $dc $userName $userPassword", $output, $result);
  } catch (Exception $e) {
    fail("fairplayDrmProfileTest failed: $e");
    $result = 1;
  }
  if ($result) {
    printFailAndlogOutput("fairplayDrmProfileTest");
    return FAIL;
  }
  printSuccessAndlogOutput("fairplayDrmProfileTest");
}
