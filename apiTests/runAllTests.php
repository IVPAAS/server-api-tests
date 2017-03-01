<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__).'/testsHelpers/runAllTestsHelper.php');

main();

$TotalCount = 0;
$failedCount = 0;
$skipCount = 0;

$paramsMap = array();

function runAllTests($dc,$userName,$userPassword)
{
  global $TotalCount, $failedCount, $skipCount, $paramsMap;

  HTMLLogger::init();
  initParams($dc, $userName, $userPassword);

  $time_start = microtime(true);

  $res = 0;

  try
  {
    $ini = parse_ini_file(dirname(__FILE__) . '/testsConfig.ini');
    print("\n*********************************************");
    printInfoAndlogOutput("Running All Tests - " . date("F j, Y, g:i a"));
    print("\n*********************************************\n");

    // Run all basic tests that require only partner creation
    $di = new RecursiveDirectoryIterator('basicTests');
    foreach (new RecursiveIteratorIterator($di) as $filename => $file)
    {
      $testName = basename($filename, ".php");
      if (is_file($filename))
      {
        if (shouldRun($ini, $testName))
        {
          $TotalCount++;
          $failedCount = $failedCount + runBasicTest($dc, $userName, $userPassword, $testName, $filename);
        } else
        {
          print ("$testName is disabled in configuration file. Skipping test \n");
          $skipCount++;
        }
      }
    }
  

    // Run Advanced Tests
    foreach ($ini['advanced-test'] as $advanced)
    {
      if (shouldRun($ini, $advanced))
      {
        print ($advanced . "\n");
        runTest($advanced, array($paramsMap["dc"], $paramsMap["username"], $paramsMap["userPass"]));
      } else
      {
        print ("$advanced is disabled in configuration file. Skipping test \n");
        $skipCount++;
      }
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

function shouldRun($configFile, $testName){
  foreach ($configFile['disabled'] as $disabled)
  {
    if ($disabled == $testName)
        return false;
  }
      return true;
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
    $testPartner = createTestPartner($client, "Kaltura.testapp1");

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
    $testPartner = createTestPartner($client, "Kaltura.testapp1");
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
    $sourceTestPartner = createTestPartner($client, "Kaltura.testapp1");
    $targetTestPartner = createTestPartner($client, "Kaltura.testapp2");

    addPartnerPermissions($client, $sourceTestPartner, "CONTENTDISTRIBUTION_PLUGIN_PERMISSION", KalturaPermissionStatus::ACTIVE);
    addPartnerPermissions($client, $targetTestPartner, "CONTENTDISTRIBUTION_PLUGIN_PERMISSION", KalturaPermissionStatus::ACTIVE);
    
    $tempPartnerPassword = '1';
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
    $testPartner = createTestPartner($client, "Kaltura.testapp1");
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
    $testPartner = createTestPartner($client, "Kaltura.testapp1");

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
    removePartner($dc, $client, $testPartner);
  }
  //}
  if ($result) {
    printFailAndlogOutput("tvinciDistributionTest");
    return FAIL;
  }
  printSuccessAndlogOutput("tvinciDistributionTest");
}

function runPrivacyContextTest($dc,$userName,$userPassword)
{
  $testName = 'privacyContextTest';
  try {
    print("\n\r $testName init.");
    $client = login($dc, $userName, $userPassword);
    $testPartner = createTestPartner($client, "Kaltura.testapp1");

    //setting the default entitlement as true in order to "hide" context from widget
    $configuration = new KalturaSystemPartnerConfiguration();
    $configuration->defaultEntitlementEnforcement = true;
    $systempartnerPlugin = KalturaSystempartnerClientPlugin::get($client);
    $systempartnerPlugin->systemPartner->updateconfiguration($testPartner->id, $configuration);

    info(" executing $testName ...");
    $output = array();
    exec("php advancedTests/privacyContextTests.php $dc $testPartner->id $testPartner->adminSecret $testPartner->secret ", $output, $result);
    foreach ($output as $item) {
      print("\n\r $item");
    }
  }
  catch (Exception $e) {
    fail(" $testName failed: $e");
    $result = 1;
  }
  //finally {
  info(" $testName tear down.");
  if ($testPartner != null) {
    $client = login($dc, $userName, $userPassword);
    removePartner($dc, $client, $testPartner);
  }
  //}
  if ($result) {
    printFailAndlogOutput($testName);
    return FAIL;
  }
  printSuccessAndlogOutput($testName);
}

function runLiveToVodTest($dc,$userName,$userPassword)
{
  $testName = 'liveToVod';
  try {
    print("\n\r $testName init.");
    $client = login($dc, $userName, $userPassword);
    $testPartner = createTestPartner($client, "Kaltura.testapp1");

    $conversionProfile = getConversionProfileForSpecficPartner($client, $testPartner->id, 'Passthrough', KalturaConversionProfileType::LIVE_STREAM);
    setDefaultConversionProfile($dc, $testPartner, $conversionProfile->id);

    $client = login($dc, $userName, $userPassword);
    $liveStreamPartner = getPartner($client, '-5'); //get the live streaming partner required for the test
    info(" executing $testName...");
    $output = array();
    exec("php advancedTests/liveToVodTests.php $dc $testPartner->id $testPartner->adminSecret $liveStreamPartner->adminSecret", $output, $result);

    foreach ($output as $item) {
      print("\n\r $item");
    }
  }
  catch (Exception $e) {
    fail(" $testName failed: $e");
    $result = 1;
  }
  //finally {
  info(" $testName tear down.");
  if ($testPartner != null) {
    $client = login($dc, $userName, $userPassword);
    removePartner($dc, $client, $testPartner);
  }
  //}
  if ($result) {
    printFailAndlogOutput($testName);
    return FAIL;
  }
  printSuccessAndlogOutput($testName);
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

function runFtpApiServerTest($dc,$userName,$userPassword)
{
  try
  {
    print("\n\r runFtpApiServerTest init.");
    $client = login($dc, $userName, $userPassword);

    $sourceTestPartner = createTestPartner($client, "Kaltura.testapp1");
    $tempPartnerPassword = '1';
    resetPartnerPassword($client, $sourceTestPartner, $tempPartnerPassword);

    sleep(15);
    info(" executing runFtpApiServerTest ...");
    $output = array();
    $ftp_port = 36;
    exec("php advancedTests/ftpApiServerTest.php $dc $sourceTestPartner->id $sourceTestPartner->adminSecret $sourceTestPartner->adminEmail $tempPartnerPassword $ftp_port", $output, $result);
    foreach ($output as $item)
    {
      print("\n\r $item");
    }
  } catch (Exception $e)
  {
    fail(" runFtpApiServerTest failed: $e");
    $result = 1;
  }
  //finally {
  info(" runFtpApiServerTest tear down.");
  if ($sourceTestPartner != null)
  {
    $client = login($dc, $userName, $userPassword);
    removePartner($dc, $client, $sourceTestPartner);
  }
  //}
  if ($result)
  {
    printFailAndlogOutput("runFtpApiServcerTest");
    return FAIL;
  }
  printSuccessAndlogOutput("runFtpApiServcerTest");
}

function runDropFolderTest($dc,$userName,$userPassword)
{
  global $paramsMap;
  try
  {
    $storageUsername = $paramsMap['remoteStorageUser'];
    $storageUserPassword = $paramsMap['remoteStoragePassword'];
    $baseDropFolderPath = '/opt/kaltura/web/scp/testDropFolder/';

    print("\n\r runDropFolderTest init.");
    $client = login($dc, $userName, $userPassword);
    $testPartner = createTestPartner($client, 'Kaltura.testapp1');
    addPartnerPermissions($client, $testPartner, 'DROPFOLDER_PLUGIN_PERMISSION', KalturaPermissionStatus::ACTIVE);

    $dropFolderPath=$baseDropFolderPath.date("F-j-Y-g-i-a");
    $kalturaDropFolder = createDropFolder($client,$testPartner,'Test drop folder',$dropFolderPath);

    info(" executing dropFolderTest ...");
    $output = array();
    exec("php advancedTests/dropFolderTest.php $dc $storageUsername $storageUserPassword $testPartner->id $testPartner->adminSecret $dropFolderPath ", $output, $result);
    foreach ($output as $item)
    {
      print("\n\r $item");
    }

  } catch (Exception $e)
    {
      fail(" dropFolderTest failed: $e");
      $result = 1;
    }

  //finally
  if($kalturaDropFolder!= null){
    info('drop folder tear down');
    removeDropFolder($client,$kalturaDropFolder->id);
  }
  if ($testPartner != null) {
    info(" DropFolderTest partner tear down.");
    removePartner($dc, $client, $testPartner);
  }

  if ($result) {
    printFailAndlogOutput("dropFolderTest");
    return FAIL;
  }
  printSuccessAndlogOutput("dropFolderTest");
}

function runEntryPlaybackContextTest($dc,$userName,$userPassword)
{
  try {

    $testPartnerId = 7449;
    $testPartnerAdminSecret = '899b3a00c3c4a340a0215db0450ad8ca';
    info(" executing EntryPlaybackContextTest...");
    $output = array();
    exec("php advancedTests/entryPlaybackContextTest.php $dc $testPartnerId $testPartnerAdminSecret", $output, $result);
    foreach ($output as $item) {
      print("\n\r $item");
    }
  } catch (Exception $e) {
    fail(" EntryPlaybackContextTest failed: $e");
    $result = 1;
  }
  if ($result) {
    printFailAndlogOutput("EntryPlaybackContextTest");
    return FAIL;
  }
  printSuccessAndlogOutput("EntryPlaybackContextTest");
}
