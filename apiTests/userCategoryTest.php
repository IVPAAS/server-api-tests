<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
require_once('apiTestHelper.php');

function createCategory($client,$categoryName)
{
    $category = new KalturaCategory();
    $category->name = $categoryName;
    $category->appearInList = KalturaAppearInListType::CATEGORY_MEMBERS_ONLY;
    $category->privacy = KalturaPrivacyType::MEMBERS_ONLY;
    $category->inheritanceType = KalturaInheritanceType::MANUAL;
    $category->defaultPermissionLevel = KalturaCategoryUserPermissionLevel::MEMBER;
    $category->contributionPolicy = KalturaContributionPolicyType::MEMBERS_WITH_CONTRIBUTION_PERMISSION;
    $category->privacyContext = 'PrivacyContext';
    $result = $client->category->add($category);
    return $result;
}

function createUser($client,$id)
{
    $user = new KalturaUser();
    $user->id = $id;
    $user->type = KalturaUserType::USER;
    $user->screenName = null;
    $user->email = $id.'@g.com';
    $result = $client->user->add($user);
    return $result;
}

function bindUserToCategory($client,$userId,$categoryId)
{
    $categoryUser = new KalturaCategoryUser();
    $categoryUser->categoryId = $categoryId;
    $categoryUser->userId = $userId;
    $categoryUser->permissionLevel = KalturaCategoryUserPermissionLevel::MEMBER;
    $result = $client->categoryUser->add($categoryUser);
    return $result;
}

function main($dc,$partnerId,$adminSecret,$userSecret)
{
  $client = startKalturaSession($partnerId,$adminSecret,$dc);
    //create category
    $categoryName = 'cat'.rand(0,1000000);
    $category = createCategory($client, $categoryName);

    //create users and bind them to category
    for ($i=0;$i<10;$i++)
    {
        $userId='user'.$i.'-'.rand(0,1000000);
        createUser($client,$userId);
        bindUserToCategory($client,$userId,$category->id);
    }
    success("Users were bounded to category successfully");

}


goMain();
