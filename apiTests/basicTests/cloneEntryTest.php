<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
require_once(dirname(__FILE__).'/../testsHelpers/apiTestHelper.php');


function Test1_CloneAReadyEntry($client)
{
    info("Create entry and upload content");
    $MediaEntry = helper_createEntryAndUploaDmp4Content($client, 'cloneEntryTest');
    info("Wait for entry to be ready id =".$MediaEntry->id);
    while(isEntryReady($client,$MediaEntry->id)!=true)
    {
        sleep(1);
        print (".");
    }
    info("Cloning entry entry");
    $newEntry = $client->baseEntry->cloneAction($MediaEntry->id);
    if (!isEntryReady($client,$newEntry->id))
    {
        return fail(__FUNCTION__."Cloned entry is not ready, while source entry was ready");
    }
    
    info("Cloned entry is in ready state. id =" .$newEntry->id );
    
    return success(__FUNCTION__);
}
function Test2_CloneAPendingEntry($client)
{
    info("Create entry and upload content");
    $MediaEntry = helper_createEntryAndUploaDmp4Content($client, 'cloneEntryTest');
    info("Make sure entry is not ready id =".$MediaEntry->id);
    if (isEntryReady($client,$MediaEntry->id)!=true)
    {
        info("Cloning entry");
        $newEntry = $client->baseEntry->cloneAction($MediaEntry->id);
    }
    else
    {
        return fail(__FUNCTION__."entry is ready too fast, cant test it!");
    }
    info("Wait for entry to be ready id =".$MediaEntry->id);
    while(isEntryReady($client,$MediaEntry->id)!=true)
    {
        sleep(1);
        print (".");
    }

    $maxWait=100;
    info("Wait for cloned entry to be ready id =".$newEntry->id);
    while(isEntryReady($client,$newEntry->id)!=true)
    {
        if($maxWait-- <0)
        {
            return fail(__FUNCTION__."Cloned entry is not ready, while source entry beacme ready");
        }
        sleep(1);
        print (".");
    }

    return success(__FUNCTION__);
}

function Test3_ClonePlaylistEntry($client)
{
    info("Create entry and upload content");
    $playList  = helper_createPlaylist($client);
    $newEntry = $client->baseEntry->cloneAction($playList->id);
    if( $playList -> status != $newEntry-> status)
    {
      return fail(__FUNCTION__);
    }

    return success(__FUNCTION__);
}

function Test4_CloneImageEntry($client)
{
    info("Create entry and upload content");
    $imageEntry  = helper_createEntryAndUploadJpgContent($client);
    
    info("Wait for entry to be ready id =".$imageEntry->id);
    while(isEntryReady($client,$imageEntry->id)!=true)
    {
        sleep(1);
        print (".");
    }
    $newEntry = $client->baseEntry->cloneAction($imageEntry->id);
    if( $imageEntry -> status != $newEntry-> status)
    {
      return fail(__FUNCTION__);
    }

    return success(__FUNCTION__);
}

function Test5_CloneEntryWithUsersAndCategories($client)
{
    info("Clone entry including its users and categories");

    //create a new user
    $user = new KalturaUser();
    $user->id = uniqid('clonedUser_');
    $newUser = $client->user->add($user);
    $profileId = null;

    //create a new entry
    $entry = addEntry($client,"UsersAndCategoriesOriginEntryOriginEntry", $mediaType=KalturaMediaType::VIDEO,
        $profileId, $newUser->id);

    $categoryPrefixName = uniqid('cloneCategory_');
    $categoryTag = "CLONE";
    $newCategory = addCategory($client, $categoryPrefixName, $categoryTag);

    $newCategoryEntry = addCategoryEntry($client, $newCategory->id, $entry->id);

    $cloneOptions = array();
    $cloneOptions[0] = new KalturaBaseEntryCloneOptionComponent();
    $cloneOptions[0]->itemType = KalturaBaseEntryCloneOptions::USERS;
    $cloneOptions[0]->rule = KalturaCloneComponentSelectorType::INCLUDE_COMPONENT;
    $cloneOptions[1] = new KalturaBaseEntryCloneOptionComponent();
    $cloneOptions[1]->itemType = KalturaBaseEntryCloneOptions::CATEGORIES;
    $cloneOptions[1]->rule = KalturaCloneComponentSelectorType::INCLUDE_COMPONENT;
    $newEntry = $client->baseEntry->cloneAction($entry->id, $cloneOptions);

//T.B.D test USERS option
//    if ($newEntry->userId != $entry->userId || $newEntry->creatorId != $entry->creatorId)
//    {
//        return fail(__FUNCTION__);
//    }

    $filter = new KalturaCategoryEntryFilter();
    $filter->categoryIdEqual = $newCategory->id;
    $filter->entryIdEqual = $newEntry->id;
    $pager = null;
    $result = $client->categoryEntry->listAction($filter, $pager);
    if ($result->totalCount == 1)
    {
        return success(__FUNCTION__);
    }
    return fail(__FUNCTION__);
}

function Test6_CloneEntryNoUsersAndNoCategories($client)
{
    info("Clone entry excluding its users and categories");
    //create a new user
    $user = new KalturaUser();
    $user->id = uniqid('clonedUser_');
    $newUser = $client->user->add($user);
    $profileId = null;

    //create a new entry
    $entry = addEntry($client,"UsersAndCategoriesOriginEntryOriginEntry", $mediaType=KalturaMediaType::VIDEO,
        $profileId, $newUser->id);



    $categoryPrefixName = uniqid('cloneCategory_');
    $categoryTag = "CLONE";
    $newCategory = addCategory($client, $categoryPrefixName, $categoryTag);

    $newCategoryEntry = addCategoryEntry($client, $newCategory->id, $entry->id);

    $cloneOptions = array();
    $cloneOptions[0] = new KalturaBaseEntryCloneOptionComponent();
    $cloneOptions[0]->itemType = KalturaBaseEntryCloneOptions::USERS;
    $cloneOptions[0]->rule = KalturaCloneComponentSelectorType::EXCLUDE_COMPONENT;
    $cloneOptions[1] = new KalturaBaseEntryCloneOptionComponent();
    $cloneOptions[1]->itemType = KalturaBaseEntryCloneOptions::CATEGORIES;
    $cloneOptions[1]->rule = KalturaCloneComponentSelectorType::EXCLUDE_COMPONENT;
    $newEntry = $client->baseEntry->cloneAction($entry->id, $cloneOptions);

    //T.B.D
    //    if ($newEntry->userId == $entry->userId && $newEntry->creatorId == $entry->creatorId)
    //    {
    //        echo("user is cloned");
    //        $bCloned = true;
    //    }

    $filter = new KalturaCategoryEntryFilter();
    $filter->categoryIdEqual = $newCategory->id;
    $filter->entryIdEqual = $newEntry->id;
    $pager = null;

    $result = $client->categoryEntry->listAction($filter, $pager);
    if ($result->totalCount == 1)
    {
        return fail(__FUNCTION__);
    }
    return success(__FUNCTION__);
}

function Test7_CloneEntryWithNullCloneOptions($client)
{
    info("Clone entry including its users and categories NullCloneOptions");

    //create a new user
    $user = new KalturaUser();
    $user->id = uniqid('clonedUser_');
    $newUser = $client->user->add($user);
    $profileId = null;

    //create a new entry
    $entry = addEntry($client,"UsersAndCategoriesOriginEntryOriginEntry", $mediaType=KalturaMediaType::VIDEO,
        $profileId, $newUser->id);

    $categoryPrefixName = uniqid('cloneCategory_');
    $categoryTag = "CLONE";
    $newCategory = addCategory($client, $categoryPrefixName, $categoryTag);

    $newCategoryEntry = addCategoryEntry($client, $newCategory->id, $entry->id);

    $newEntry = $client->baseEntry->cloneAction($entry->id, $cloneOptions=null);

//T.B.D test USERS option
//    if ($newEntry->userId != $entry->userId || $newEntry->creatorId != $entry->creatorId)
//    {
//        return fail(__FUNCTION__);
//    }

    $filter = new KalturaCategoryEntryFilter();
    $filter->categoryIdEqual = $newCategory->id;
    $filter->entryIdEqual = $newEntry->id;
    $pager = null;
    $result = $client->categoryEntry->listAction($filter, $pager);
    if ($result->totalCount == 1)
    {
        return success(__FUNCTION__);
    }
    return fail(__FUNCTION__);

}





function main($dc,$partnerId,$adminSecret,$userSecret)
{
  $client = startKalturaSession($partnerId,$adminSecret,$dc);
  $ret  = Test1_CloneAReadyEntry($client);
  $ret += Test2_CloneAPendingEntry($client);
  $ret += Test3_ClonePlaylistEntry( $client );
  $ret += Test4_CloneImageEntry($client);
  $ret += Test5_CloneEntryWithUsersAndCategories($client);
  $ret += Test6_CloneEntryNoUsersAndNoCategories($client);
    $ret += Test7_CloneEntryWithNullCloneOptions($client);

  return ($ret);
}

goMain();
