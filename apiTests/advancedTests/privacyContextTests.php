<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');

/**
 * Created by IntelliJ IDEA.
 * User: David.Winder
 * Date: 9/26/2016
 * Time: 2:57 PM
 */
function createCategory($client,$categoryName, $privacyType = null, $hasPrivacyContext = false)
{
    $category = new KalturaCategory();
    $category->name = $categoryName;
    if ($privacyType)
        $category->privacy = $privacyType;
    if ($hasPrivacyContext)
        $category->privacyContext = 'MediaSpace';

    $result = $client->category->add($category);
    info("category $result->id was added [Type: $privacyType and PrivacyContext: $hasPrivacyContext]");
    return $result;
}

function createCuePoints($client, $EntryId = null)
{
    $cuePoint = new KalturaCodeCuePoint();
    $cuePoint->code = "test of privacy context";
    $cuePoint->entryId = $EntryId;
    $result = $client->cuePoint->add($cuePoint);
    info("Add Cue point id: " .$result->id ." on entry: " .$EntryId . " on time: " .$result->createdAt );
    return $result;
}

function checkEntryExist($client, $entryId = null)
{
    $filter = new KalturaBaseEntryFilter();
    //$filter->idEqual = $entryId;
    $filter->idIn = $entryId;
    $result = $client->baseEntry->listAction($filter, null);
    if ($result->totalCount > 0)
        return true;
    return false;
}

function checkCuePoints($client,$entryId) {
    $filter = new KalturaCuePointFilter();
    $filter->entryIdEqual = $entryId;
    $result = $client->cuePoint->listAction($filter, null);
    if ($result->totalCount > 0)
        return true;
    return false;
}

function checkCategory($client,$categoryId) {
    $filter = new KalturaCategoryFilter();
    $filter->idIn = $categoryId;
    $result = $client->category->listAction($filter, null);
    if ($result->totalCount > 0)
        return true;
    return false;
}

function categoryTest($client,$categoryName, $privacyType = null, $hasPrivacyContext = false)
{
    $flag = true;
    $category = createCategory($client,$categoryName,$privacyType, $hasPrivacyContext);
    if (!checkCategory($client, $category->id))
        $flag = false;
    $client->category->delete($category->id, null);
    info('delete the category' .$category->id);
    return $flag;
}

function runAllCategoryTests($client)
{
    try {
        if (!categoryTest($client, 'privacyContextTest_ALL_false', KalturaPrivacyType::ALL, false))
            throw new Exception('fail on category with privacyType::ALL and with no PrivacyContext');
        if (!categoryTest($client, 'privacyContextTest_MEMBERS_ONLY_true', KalturaPrivacyType::MEMBERS_ONLY, true))
            throw new Exception('fail on category with privacyType::MEMBERS_ONLY and with PrivacyContext');
        if (!categoryTest($client, 'privacyContextTest_ALL_true', KalturaPrivacyType::ALL, true))
            throw new Exception('fail on category with privacyType::ALL and with PrivacyContext');
        if (!categoryTest($client, 'privacyContextTest_AUTHENTICATED_USERS_true', KalturaPrivacyType::AUTHENTICATED_USERS, true))
            throw new Exception('fail on category with privacyType::AUTHENTICATED_USERS and with PrivacyContext');
    } catch (Exception $e) {
        fail('fail on category test with: ' .$e);
        return false;
    }
    /*
     cannot set privacy field when privacy context is not set on the categroy
     so can do:
      categoryTest($client, 'privacyContextTest_AUTHENTICATED_USERS_false', KalturaPrivacyType::AUTHENTICATED_USERS, false))
      categoryTest($client, 'privacyContextTest_MEMBERS_ONLY_false', KalturaPrivacyType::MEMBERS_ONLY, false))
    */
    return true;
}

function runAllTestWithClient($client, $ksType, $description = null)
{
    $clientDescription = "$ksType ks, $description";
    warning("running test with $clientDescription");
    $runCategory = ($description == 'MASTER'); // because default entitlement are enforced
    try {
        if ($runCategory && !runAllCategoryTests($client))
            throw new Exception('fail on category tests');

        $entry = createMediaEntry($client, null, 'privacyContextTest');
        // check list on Entry
        if (!checkEntryExist($client, $entry->id))
            throw new Exception('entry not existed');

        // check list on cue point
        createCuePoints($client, $entry->id);
        if (!checkCuePoints($client, $entry->id))
            throw new Exception('cue point not existed');
        $client->baseEntry->delete($entry->id);
        info('delete the entry');

    } catch (Exception $e) {
        fail("fail on client $clientDescription with: $e");
        return fail(__FUNCTION__ . " fail test in this client");
    }
    return success(__FUNCTION__);
}
function getUiConf($client) {
    $result = $client->uiConf->listAction(null, null);
    if ($result->totalCount <= 0)
        return fail("can't run test without uiconf");
    return $result->objects[0]->id;

}

function createWidget($client, $EE, $uiConfId) {
    $widget = new KalturaWidget();
    $widget->uiConfId = $uiConfId;
    $widget->privacyContext = 'MediaSpace';
    if ($EE)
        $widget->privacyContext = 'MediaSpace,enableentitlement';
    else
        $widget->enforceEntitlement = true;
    $result = $client->widget->add($widget);
    return $result->id;
}

$categoryId;
$entryId;
function createCategoryAndEntry($client, $privacyType) {
    $category = createCategory($client, 'privacyContextTest_MediaSpace', $privacyType, true);
    $entry = createMediaEntry($client, null,'current');
    addCategoryEntry($client, $category->id, $entry->id);
    info("created category with id $category->id and entry with id $entry->id");
    global $categoryId, $entryId;
    $categoryId = $category->id;
    $entryId = $entry->id;
}
function deleteCategoryAndEntry($client) {
    global $categoryId, $entryId;
    info("deleting category with id $categoryId and entry with id $entryId");
    $client->baseEntry->delete($entryId);
    $client->category->delete($categoryId, null);
}

function testShouldFind($clientList) {
    global $entryId;
    foreach ($clientList as $client)
        if (!checkEntryExist($client, $entryId))
            return fail(__FUNCTION__ . " fail test in this client");
    return success(__FUNCTION__);
}
function testShouldNotFind($clientList) {
    global $entryId;
    foreach ($clientList as $client)
        if (checkEntryExist($client, $entryId))
            return fail(__FUNCTION__ . " fail test in this client");
    return success(__FUNCTION__);
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


function main($dc,$partnerId,$adminSecret,$userSecret)
{
    info('starting test');
    $ret = "";
    
    $clientAdminMaster = startKalturaSession($partnerId,$adminSecret,$dc,KalturaSessionType::ADMIN, null, 'disableentitlement');
    $clientAdmin = startKalturaSession($partnerId,$adminSecret,$dc,KalturaSessionType::ADMIN, null, null);
    $clientAdminPC = startKalturaSession($partnerId,$adminSecret,$dc,KalturaSessionType::ADMIN, null, 'privacycontext:MediaSpace');
    $clientAdminEE = startKalturaSession($partnerId,$adminSecret,$dc,KalturaSessionType::ADMIN, null, 'enableentitlement');
    $clientAdminPCEE = startKalturaSession($partnerId,$adminSecret,$dc,KalturaSessionType::ADMIN, null, 'privacycontext:MediaSpace,enableentitlement');
    $clientUser = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER, null, null);
    $clientUserPC = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER, null, 'privacycontext:MediaSpace');
    $clientUserEE = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER, null, 'enableentitlement');
    $clientUserPCEE = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER, null, 'privacycontext:MediaSpace,enableentitlement');


    $uiConfId = getUiConf($clientAdminMaster);
    if (!$uiConfId)
        return fail("can't run test without uiconf");
    $widgetPC = createWidget($clientAdminMaster, false, $uiConfId);
    $widgetPCEE = createWidget($clientAdminMaster, true, $uiConfId);
    $clientWidgetPCEE = startWidgetSession($dc,$partnerId, $widgetPCEE);
    $clientWidgetPC = startWidgetSession($dc,$partnerId, $widgetPC); // enforce entitlement
    $clientWidget = startWidgetSession($dc,$partnerId);
    info('finish generate all clients');


    //in ALL:
    // all admin and user pass and also $clientWidgetPC, $clientWidgetPCEE only $clientWidget NOT found
    createCategoryAndEntry($clientAdminMaster, KalturaPrivacyType::ALL);
    warning('checking entry with PC and NO_RESTRICTION');
    $ret += testShouldFind(array($clientAdmin, $clientAdminPC, $clientAdminEE, $clientAdminPCEE, $clientUser, $clientUserPC, $clientUserEE, $clientUserPCEE, $clientWidgetPC, $clientWidgetPCEE));
    $ret += testShouldNotFind(array($clientWidget));
    deleteCategoryAndEntry($clientAdminMaster);

    //in AUTH:
    // all admin and user pass but all widget fail
    createCategoryAndEntry($clientAdminMaster, KalturaPrivacyType::AUTHENTICATED_USERS);
    warning('checking entry with PC and AUTHENTICATED_USERS_ONLY');
    $ret += testShouldFind(array($clientAdmin, $clientAdminPC, $clientAdminEE, $clientAdminPCEE, $clientUser, $clientUserPC, $clientUserEE, $clientUserPCEE));
    $ret += testShouldNotFind(array($clientWidget, $clientWidgetPC, $clientWidgetPCEE));
    deleteCategoryAndEntry($clientAdminMaster);

    //return $ret;


    warning('checking ADMIN and USER client with himself');
    $ret += runAllTestWithClient($clientAdminMaster, 'ADMIN', 'MASTER');
    $ret += runAllTestWithClient($clientAdmin, 'ADMIN', null);
    $ret += runAllTestWithClient($clientAdminPC, 'ADMIN', 'PC');
    $ret += runAllTestWithClient($clientAdminEE, 'ADMIN', 'EE');
    $ret += runAllTestWithClient($clientAdminPCEE, 'ADMIN', 'PC-EE');
    $ret += runAllTestWithClient($clientUser, 'USER', null);
    $ret += runAllTestWithClient($clientUserPC, 'USER', 'PC');
    $ret += runAllTestWithClient($clientUserEE, 'USER','EE');
    $ret += runAllTestWithClient($clientUserPCEE, 'USER', 'PC-EE');
    return $ret;



}


goMain();