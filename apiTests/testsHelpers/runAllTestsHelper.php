<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');

const HTML_LOG_FILE = "runAllTestsLog.html";

const FAIL=1;


function printInfoAndlogOutput($msg)
{
    info($msg);
    HTMLLogger::logInfoToHTML($msg);
}

function printFailAndlogOutput($msg)
{
    fail($msg);
    HTMLLogger::logFailToHTML($msg);
}

function printSuccessAndlogOutput($msg)
{
    success($msg);
    HTMLLogger::logSuccessToHTML($msg);
}

function printUsage()
{
    print ("\n\rUsage: " . $GLOBALS['argv'][0] . " <HOST> <ADMIN USER CREDENTIALS> <ADMIN USER PASSWORD>");
}

function main()
{
    if ($GLOBALS['argc'] != 4) {
        printUsage();
        exit (1);
    }
    $dcUrl = $GLOBALS['argv'][1];
    $userName = $GLOBALS['argv'][2];
    $userPassword = $GLOBALS['argv'][3];
    $res = runAllTests($dcUrl, $userName, $userPassword);
    exit($res);
}

function login($dc, $userName, $userPassword, $partnerId = null)
{
    print("\n\r login to get super user ks");
    $config = new KalturaConfiguration();
    $config->serviceUrl = $dc;
    $client = new KalturaClient($config);
    $loginId = $userName;
    $password = $userPassword;
//    $partnerId = null;
    $expiry = null;
    $privileges = null;
    $ks = $client->user->loginbyloginid($loginId, $password, $partnerId, $expiry, $privileges);
    $client->setKs($ks);
    print("\n\r successful login");
    return $client;
}

function startKalturaSession($partnerId, $secret, $destUrl, $type = KalturaSessionType::ADMIN, $userId = null)
{
    try {
        $config = new KalturaConfiguration($partnerId);
        $config->serviceUrl = $destUrl;
        $client = new KalturaClient($config);
        $result = $client->session->start($secret, $userId, $type, $partnerId, null, null);
        $client->setKs($result);
        //print("Started session successfully with KS [$result]\n");
        return $client;
    } catch (KalturaException $e) {
        $msg = $e->getMessage();
        fail("Problem starting session with message: [$msg]\n");
        die("ERROR - cannot generate session with partner id [$partnerId] and secret [$secret]");
    }
}

function createTestPartner($client, $name)
{
    print("\n\r start createTestPartner");
    $partner = new KalturaPartner();
    $partner->name = $name;
    $partner->adminName = $name;
    $partner->adminEmail = "$name@kaltura.com";
    $partner->description = 'myTestUser Description';
    #$partner->type = KalturaPartnerType::ADMIN_CONSOLE;
    $cmsPassword = '';
    $templatePartnerId = 99;
    $silent = null;
    $testPartner = $client->partner->register($partner, $cmsPassword, $templatePartnerId, $silent);

    $configuration = new KalturaSystemPartnerConfiguration();
    $configuration->partnerPackage = 1;
    $configuration->storageDeleteFromKaltura = 1;
    $configuration->storageServePriority = KalturaStorageServePriority::KALTURA_ONLY;
    $systempartnerPlugin = KalturaSystempartnerClientPlugin::get($client);
    $result = $systempartnerPlugin->systemPartner->updateConfiguration($testPartner->id, $configuration);

    print("\n\r createTestPartner finished successfully. partner created with id: $testPartner->id ");
    return $testPartner;
}

function getPartner($client, $partnerId)
{
    $systempartnerPlugin = KalturaSystempartnerClientPlugin::get($client);
    return $systempartnerPlugin->systemPartner->get($partnerId);
}

function addPartnerPermissions($client, $testPartner, $permissionName, $permissionType)
{
    print("\n\r start addPartnerPermissions. adding permissions to partner.");
    $configuration = new KalturaSystemPartnerConfiguration();
    $configuration->permissions = array();
    $configuration->permissions[0] = new KalturaPermission();
    $configuration->permissions[0]->name = $permissionName;
    $configuration->permissions[0]->status = $permissionType;
    $systempartnerPlugin = KalturaSystempartnerClientPlugin::get($client);
    $systempartnerPlugin->systemPartner->updateconfiguration($testPartner->id, $configuration);
    print("\n\r addPartnerPermissions finished successfully.");
}

function resetPartnerPassword($client, $testPartner, $newPassword)
{
    $userId = $testPartner->adminEmail;
    $partnerId = $testPartner->id;
    $newPassword = $newPassword;
    $systempartnerPlugin = KalturaSystempartnerClientPlugin::get($client);
    $systempartnerPlugin->systemPartner->resetuserpassword($userId, $partnerId, $newPassword);
}

function getConversionProfileForSpecficPartner($client, $partnerId, $conversionProfileName, $conversionProfileType = null)
{
    print("\n\r start getConversionProfile.");
    $client->setPartnerId($partnerId);
    $filter = new KalturaConversionProfileFilter();
    $filter->nameEqual = $conversionProfileName;
    $filter->typeEqual = $conversionProfileType;
    $pager = new KalturaFilterPager();
    $pager->pageSize = 10000;
    $result = $client->conversionProfile->listAction($filter, $pager);

    foreach ($result->objects as $item) {
        if ($partnerId == $item->partnerId) {
            print("\n\r getConversionProfile finished successfully.");
            return ($item);
        }
    }
    fail(" getConversionProfile failed. \n could not find conversion profile [name = $conversionProfileName ] for partnerId $partnerId ");
    return (null);
}

function createConversionProfileForSpecficPartner($dc, $partner, $conversionProfileName)
{
    $client = startKalturaSession($partner->id, $partner->adminSecret, $dc, KalturaSessionType::ADMIN, null);
    $conversionProfile = new KalturaConversionProfile();
    $conversionProfile->status = KalturaConversionProfileStatus::ENABLED;
    $conversionProfile->type = KalturaConversionProfileType::MEDIA;
    $conversionProfile->name = $conversionProfileName;
    $conversionProfile->isDefault = KalturaNullableBoolean::TRUE_VALUE;
    $conversionProfile->flavorParamsIds = '0';
    $conversionProfile->mediaParserType = KalturaMediaParserType::MEDIAINFO;
    $result = $client->conversionProfile->add($conversionProfile);
    return $result;
}

function setDefaultConversionProfile($dc, $partner, $conversionProfileId)
{
    print("\n\r start setDefaultConversionProfile");
    $client = startKalturaSession($partner->id, $partner->adminSecret, $dc, KalturaSessionType::ADMIN, null);
    $client->conversionProfile->setasdefault($conversionProfileId);
    print("\n\r setDefaultConversionProfile finished successfully");
}

function getDefaultAccessControlProfile($dc, $partner)
{
    $client = startKalturaSession($partner->id, $partner->adminSecret, $dc, KalturaSessionType::ADMIN, null);
    $filter = new KalturaAccessControlProfileFilter();
    $filter->systemNameEqual = 'Default';
    $pager = null;
    $result = $client->accessControlProfile->listAction($filter, $pager);
    return $result->objects[0];
}


function createCrossKalturaDistributionProfile($client, $dc, $sourceTestPartner, $targetTestPartner, $sourceAccessControlProfile, $targetAccessControlProfile, $sourceConversionControlProfile, $targetConversionControlProfile, $tempPartnerPassword)
{
    print("\n\r start createDistributionProfile");
    $client->setPartnerId($sourceTestPartner->id);
    $distributionProfile = new KalturaCrossKalturaDistributionProfile();
    $distributionProfile->providerType = KalturaDistributionProviderType::CROSS_KALTURA;
    $distributionProfile->name = 'testCrossKalturaDistributionProfile';
    $distributionProfile->status = KalturaDistributionProfileStatus::ENABLED;
    $distributionProfile->submitEnabled = KalturaDistributionProfileActionStatus::MANUAL;
    $distributionProfile->updateEnabled = KalturaDistributionProfileActionStatus::MANUAL;
    $distributionProfile->deleteEnabled = KalturaDistributionProfileActionStatus::MANUAL;
    $distributionProfile->reportEnabled = KalturaDistributionProfileActionStatus::MANUAL;
    $distributionProfile->optionalThumbDimensions = array();
    $distributionProfile->optionalThumbDimensions[0] = new KalturaDistributionThumbDimensions();
    $distributionProfile->optionalThumbDimensions[0]->width = 300;
    $distributionProfile->optionalThumbDimensions[0]->height = 150;
    $distributionProfile->targetServiceUrl = $dc;
    $distributionProfile->targetAccountId = $targetTestPartner->id;
    $distributionProfile->targetLoginId = $targetTestPartner->adminUserId;
    $distributionProfile->targetLoginPassword = $tempPartnerPassword;
    $distributionProfile->mapAccessControlProfileIds = array();
    $distributionProfile->mapAccessControlProfileIds[0] = new KalturaKeyValue();
    $distributionProfile->mapAccessControlProfileIds[0]->key = $sourceAccessControlProfile->id;
    $distributionProfile->mapAccessControlProfileIds[0]->value = $targetAccessControlProfile->id;
    $distributionProfile->mapConversionProfileIds = array();
    $distributionProfile->mapConversionProfileIds[0] = new KalturaKeyValue();
    $distributionProfile->mapConversionProfileIds[0]->key = $sourceConversionControlProfile->id;
    $distributionProfile->mapConversionProfileIds[0]->value = $targetConversionControlProfile->id;
    $contentdistributionPlugin = KalturaContentdistributionClientPlugin::get($client);
    $result = $contentdistributionPlugin->distributionProfile->add($distributionProfile);
    print("\n\r createDistributionProfile finished successfully");
    return $result;
}

function createTvinciDistributionProfile($client, $testPartner)
{

    print("\n\r start createTvinciDistributionProfile");
    $client->setPartnerId($testPartner->id);
    $distributionProfile = new KalturaTvinciDistributionProfile();
    $distributionProfile->providerType = KalturaDistributionProviderType::TVINCI;
    $distributionProfile->name = 'testTvinciDistributionProfile';
    $distributionProfile->status = KalturaDistributionProfileStatus::ENABLED;
    $distributionProfile->submitEnabled = KalturaDistributionProfileActionStatus::MANUAL;
    $distributionProfile->updateEnabled = KalturaDistributionProfileActionStatus::MANUAL;
    $distributionProfile->deleteEnabled = KalturaDistributionProfileActionStatus::MANUAL;
    $distributionProfile->reportEnabled = KalturaDistributionProfileActionStatus::MANUAL;
    $distributionProfile->ingestUrl = '54.72.1.39:8030/catalog_v3_4/service.svc';
    $distributionProfile->username = 'Kaltura Sus Regular-main';
    $distributionProfile->password = 'Kaltura Mus-main';
    $distributionProfile->ismFileName = 'Web HD';
    $distributionProfile->ismPpvModule = 'ism PPV-Module1 update 1';
    $distributionProfile->ipadnewFileName = 'Mobile_Devices_Main_HD';
    $distributionProfile->ipadnewPpvModule = 'PPV Main_HD _2 update2';
    $distributionProfile->iphonenewFileName = 'Mobile_Devices_Main_SD';
    $distributionProfile->iphonenewPpvModule = 'PPV Main_SD _3 update 3';
    $contentdistributionPlugin = KalturaContentdistributionClientPlugin::get($client);
    $result = $contentdistributionPlugin->distributionProfile->add($distributionProfile);
    print("\n\r createTvinciDistributionProfile finished successfully");
    return $result;
}

function removePartner($dc, $client, $partner)
{
    try {
        print("\n\r Start removePartner. Removing partner  $partner->id.");
        markBaseEntriesForPartnersAsDeleted($dc, $partner);
        removeDeletedBaseEntriesForPartnerFromFileSystem($partner); //can only be used on server side invocation
        markAPartnersAsDeleted($client, $partner);
        print(" removePartner finished successfully. Partner $partner->id removed. ");
    }
    catch(Exception $e)
    {
        fail("removePartner failed. $e");
        throw $e;
    }
}

function markAPartnersAsDeleted($client, $partner)
{
    try {
        print("\n\r start markAPartnersAsDeleted $partner->id.");
        $status = KalturaPartnerStatus::DELETED;
        $reason = "test user $partner->id to delete";
        $systempartnerPlugin = KalturaSystempartnerClientPlugin::get($client);
        $systempartnerPlugin->systemPartner->updatestatus($partner->id, $status, $reason);
        print("\n\r markAPartnersAsDeleted finished successfully. partner $partner->id marked as deleted.");
    } catch (Exception $e) {
        fail("markAPartnersAsDeleted failed");
        throw $e;
    }
}

/***
 * Remove deleted entries from file system
 * Use only on server side and not on client side since deleting from the file system is permitted only on server side.
 * @param $partner
 * @throws Exception
 */
function removeDeletedBaseEntriesForPartnerFromFileSystem( $partner )
{
    print("\n\r Start removeDeletedBaseEntriesForPartnerFromFileSystem for partner $partner->id .");
    try
    {
        print("\n\r Deleting base entries from file system for partner $partner->id");
//        exec("php /executionScripts/removeFilesForDeletedFileSyncs.php $partner->id");
        exec("php /opt/kaltura/app/alpha/scripts/utils/removeFilesForDeletedFileSyncs.php $partner->id");
        print("\n\r Finished removing partners base entry from file system.");
    }
    catch(Exception $e)
    {
        fail("removeDeletedBaseEntriesForPartnerFromFileSystem failed. $e");
        throw $e;
    }
}

function markBaseEntriesForPartnersAsDeleted($dc, $partner)
{
    print("\r\n Start markBaseEntriesForPartnersAsDeleted for partner $partner->id.");
    try {
        $client = startKalturaSession($partner->id, $partner->adminSecret, $dc, KalturaSessionType::ADMIN, null);
        $filter = null;
        $counter = 0;
        $pager = new KalturaFilterPager();
        $pager->pageSize = 500;
        $pageIndex = 0;
        $pager->pageIndex = $pageIndex;
        $result = $client->baseEntry->listAction($filter, $pager);
        $totalCount = $result->totalCount;
        print("\r\n Total Base Entries Count for partner $partner->id is: $totalCount");
        while ($totalCount > 0) {
            $result = $client->baseEntry->listAction($filter, $pager);
            foreach ($result->objects as $item) {
                print("\n\r Marking item $item->id as deleted");
                $client->baseEntry->delete($item->id);
                $counter = $counter + 1;
            }
            $totalCount = $totalCount - 500;
            $pageIndex = $pageIndex + 1;
            $pager->pageIndex = $pageIndex;
        }
        print("\n\r Total Base Entries marked as deleted for partner $partner->id: $counter");
    }
    catch(Exception $e)
    {
        fail("markBaseEntriesForPartnersAsDeleted failed. $e");
        throw $e;
    }
}

function removeAllNonDefaultPartners($dc, $client)
{
   try {
       print("\n\r remove all base entries from non default partners.");
       markBaseEntriesFromNonDefaultPartnersAsDeleted($dc, $client);
       removeDeletedBaseEntriesForNonDefaultPartnersFromFileSystem($client);
       markAllNonDefaultPartnersAsDeleted($client);
       print("\n\r removeAllNonDefaultPartners finished successfully. partners deleted. ");
   }
   catch(Exception $e)
    {
        fail("removePartner failed. $e");
        throw $e;
    }
}

function markAllNonDefaultPartnersAsDeleted($client)
{
    print("\n\r Start markAllNonDefaultPartnersAsDeleted .");
    try{
    $filter = new KalturaPartnerFilter();
    $counter = 0;
    $pager = new KalturaFilterPager();
    $pager->pageSize = 500;
    $pageIndex = 0;
    $pager->pageIndex = $pageIndex;
    $filter->statusEqual = KalturaPartnerStatus::ACTIVE;

        $systempartnerPlugin = KalturaSystempartnerClientPlugin::get($client);
        $result = $systempartnerPlugin->systemPartner->listAction($filter, $pager);
        $totalCount = $result->totalCount;
        print("\n\r Total Partner Count Is: $totalCount ");
        while ($totalCount >= 0) {
            $result = $systempartnerPlugin->systemPartner->listAction($filter, $pager);
            foreach ($result->objects as $partner) {
                if ($partner->id > 100) {
                    print("\n\r Marking partner $partner->id as deleted");
                    $status = KalturaPartnerStatus::DELETED;
                    $reason = "Partner $partner->id to mark as deleted";
                    $systempartnerPlugin = KalturaSystempartnerClientPlugin::get($client);
                    $systempartnerPlugin->systemPartner->updatestatus($partner->id, $status, $reason);
                    $counter = $counter + 1;
                }
            }
            $totalCount = $totalCount - 500;
            $pageIndex = $pageIndex+1;
            $pager->pageIndex = $pageIndex;
        }
        print("\n\r Total non default partners marked as deleted: $counter");
    }
    catch(Exception $e)
    {
        fail("markAllNonDefaultPartnersAsDeleted failed. $e");
        throw $e;
    }
}

/***
 * Removes deleted base entries from file system for all non default partners
 * Use only on server side and not on client side since deleting from the file system is permitted only on server side.
 * @param $client
 * @throws Exception
 */
function removeDeletedBaseEntriesForNonDefaultPartnersFromFileSystem($client)
{
    print("\n\r Start removeDeletedBaseEntriesForNonDefaultPartnersFromFileSystem .");
    try {
        $filter = new KalturaPartnerFilter();
        $counter = 0;
        $pager = new KalturaFilterPager();
        $pager->pageSize = 500;
        $pageIndex = 0;
        $pager->pageIndex = $pageIndex;
        $filter->statusEqual = KalturaPartnerStatus::ACTIVE;

        $systempartnerPlugin = KalturaSystempartnerClientPlugin::get($client);
        $result = $systempartnerPlugin->systemPartner->listAction($filter, $pager);
        $totalCount = $result->totalCount;
        print("\n\r Total Partner Count Is: $totalCount");
        while ($totalCount >= 0) {
            $result = $systempartnerPlugin->systemPartner->listAction($filter, $pager);
            foreach ($result->objects as $partner) {
                if ($partner->id > 100) {
                    print("\n\r Deleting base entries from file system for partner $partner->id");
                    exec("php /executionScripts/removeFilesForDeletedFileSyncs.php $partner->id");
                    $counter = $counter + 1;
                }
            }
            $totalCount = $totalCount - 500;
            $pageIndex = $pageIndex + 1;
            $pager->pageIndex = $pageIndex;
        }
        print("\n\r Finished removing partners base entry from file system.");
    }
    catch(Exception $e)
    {
        fail("removeDeletedBaseEntriesForNonDefaultPartnersFromFileSystem failed. $e");
        throw $e;
    }
}

function markBaseEntriesFromNonDefaultPartnersAsDeleted($dc, $client)
{
    print("\r\n Start markBaseEntriesFromNonDefaultPartnersAsDeleted.");
    try {
        $filter = null;
        $counter = 0;
        $pager = new KalturaFilterPager();
        $pager->pageSize = 500;
        $pageIndex = 0;
        $pager->pageIndex = $pageIndex;
        $result = $client->baseEntry->listAction($filter, $pager);
        $totalCount = $result->totalCount;
        print("\r\n Total Base Entries Count Is: $totalCount");
        while ($totalCount > 0) {
            $result = $client->baseEntry->listAction($filter, $pager);
            foreach ($result->objects as $item) {
                if ($item->partnerId > 100) {
                    $partner = getPartner($client, $item->partnerId);
                    $client1 = startKalturaSession($partner->id, $partner->adminSecret, $dc, KalturaSessionType::ADMIN, null);
                    print("\n\r Marking item $item->id as deleted");
                    $client1->baseEntry->delete($item->id);
                    $counter = $counter + 1;
                }
            }
            $totalCount = $totalCount - 500;
            $pageIndex = $pageIndex + 1;
            $pager->pageIndex = $pageIndex;
        }
        print("\n\r Total Base Entries marked as deleted for non default partners: $counter");
    }
    catch(Exception $e)
    {
        fail("markBaseEntriesFromNonDefaultPartnersAsDeleted failed. $e");
        throw $e;
    }
}

function updatePartnerWithRemoteStoragePriority ($client, $partnerId, $storageServePriority , $shouldDeleteFromKaltura )
{
    $configuration = new KalturaSystemPartnerConfiguration();
    $configuration->storageDeleteFromKaltura = $shouldDeleteFromKaltura;
    $configuration->storageServePriority = $storageServePriority;
    $systempartnerPlugin = KalturaSystempartnerClientPlugin::get($client);
    $systempartnerPlugin->systemPartner->updateconfiguration($partnerId, $configuration);
}

function createDeliveryProfile($client, $partnerId, $deliveryProfileName, $deliveryProfileType, $streamerType, $url, $deliveryProfileStatus)
{
    $client->setPartnerId($partnerId);
    $delivery = new KalturaDeliveryProfile();
    $delivery->name = $deliveryProfileName;
    $delivery->type = $deliveryProfileType;
    $delivery->streamerType = $streamerType;
    $delivery->url = $url;
    $delivery->status = $deliveryProfileStatus;
    $result = $client->deliveryProfile->add($delivery);
    return $result;
}

function createRemoteStorageProfile($client, $partnerId, $storageName, $status, $protocol, $storageUrl, $storageBaseDir, $storageUsername, $storageUserPassword,
$deliveryStatus, $pathManagerClass, $deliveryProfile)
{
    $client->setPartnerId($partnerId);
    $storageProfile = new KalturaStorageProfile();
    $storageProfile->name = $storageName;
    $storageProfile->status = $status;
    $storageProfile->protocol = $protocol;
    $storageProfile->storageUrl = $storageUrl;
    $storageProfile->storageBaseDir = $storageBaseDir;
    $storageProfile->storageUsername = $storageUsername;
    $storageProfile->storagePassword = $storageUserPassword;
//    $storageProfile->storageFtpPassiveMode = null;
    $storageProfile->deliveryStatus = $deliveryStatus;
//    $storageProfile->readyBehavior = null;
//    $storageProfile->allowAutoDelete = null;
    $storageProfile->pathManagerClass = $pathManagerClass;
    $storageProfile->deliveryProfileIds = array();
    $storageProfile->deliveryProfileIds[0] = new KalturaKeyValue();
    $storageProfile->deliveryProfileIds[0]->key = $deliveryProfile->streamerType;
    $storageProfile->deliveryProfileIds[0]->value = $deliveryProfile->id;
    $result = $client->storageProfile->add($storageProfile);
    return $result;
}

class textColors
{
    const OKBLUE = "\033[34;1m";
    const OKGREEN = "\033[32;1m";
    const FAIL = "\033[31;1m";
    const ENDC = "\033[0m";
}

function success($msg)
{
    $out = "\n" . textColors::OKGREEN . $msg . " OK!" . textColors::ENDC . "\n";
    print($out);
    return $out;
}

function fail($msg)
{
    $out = "\n" . textColors::FAIL . $msg . " FAIL!" . textColors::ENDC . "\n";
    print($out);
    return $out;
}

function info($msg)
{
    $out = "\n" . textColors::OKBLUE . $msg . textColors::ENDC . "\n";
    print($out);
    return $out;
}

Class HTMLLogger
{

    private static $logger;

    public static function logSuccessToHTML($text)
    {
        $out = "<p style=\"color:green\"> $text</p>";
        fwrite(self::$logger, $out);
    }

    public static function logFailToHTML($text)
    {
        $out = "<p style=\"color:red\"> $text</p>";
        fwrite(self::$logger, $out);
    }

    public static function logInfoToHTML($text)
    {
        $out = "<p style=\"color:darkblue\"> $text</p>";
        fwrite(self::$logger, $out);
    }

    public static function init(){
        self::$logger = fopen(HTML_LOG_FILE, "w") or die("Unable to create html log file!");
    }

    public static function close(){
        fclose(self::$logger);
    }
}

