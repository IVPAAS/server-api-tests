<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
require_once('testsHelpers/apiTestHelper.php');

function createCategory($client,$categoryName, $parentId = null)
{
    $category = new KalturaCategory();
    $category->name = $categoryName;
    $category->appearInList = KalturaAppearInListType::CATEGORY_MEMBERS_ONLY;
    $category->privacy = KalturaPrivacyType::MEMBERS_ONLY;
    $category->inheritanceType = KalturaInheritanceType::MANUAL;
 //   $category->defaultPermissionLevel = KalturaCategoryUserPermissionLevel::MEMBER;
    $category->contributionPolicy = KalturaContributionPolicyType::MEMBERS_WITH_CONTRIBUTION_PERMISSION;
    $category->privacyContext = 'PrivacyContext';
	if ( $parentId )
	{
		$category->inheritanceType = KalturaInheritanceType::INHERIT;
		$category->parentId = $parentId;
	}

    $result = $client->category->add($category);
	info("category $result->id was added, parent: " . $parentId);

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


function createCategoryAndBindUsers($client)
{
    //create category
    $categoryName = 'cat'.rand(0,1000000);
    $category = createCategory($client, $categoryName);

    //create users and bind them to category
    for ($i=0;$i<10;$i++)
    {
		AddUserToCategory( $client, $category->id,'user'.$i.'-'.rand(0,1000000) . $categoryName);
    }

    info("10 Users were bounded to category $category->id successfully");
	return success(__FUNCTION__);

}

function AddUserToCategory( $client, $categoryId, $userId )
{
	createUser($client,$userId);
	bindUserToCategory($client,$userId,$categoryId);
}


function createCategoryTree( $client )
{
	$rootCat = createCategory($client, 'rootCat'.rand(0,1000000));
	$cat = createCategory($client, "childOf $rootCat->name", $rootCat->id);
	$catChild = createCategory($client, "childOf $cat->name", $cat->id);

	$userId = 'user' .rand(0,1000000) . $rootCat->id;
	AddUserToCategory( $client, $rootCat->id, $userId );

	$filter = new KalturaCategoryFilter();
	$filter->memberEqual = $userId;

	$retries = 10;
	for ($i=0; $i<$retries; $i++) {
		info("sleep 30 seconds");
		for ($j = 0; $j < 30; $j++) {
			sleep(1);
			print(".");
		}
		$categoriesList = $client->category->listAction($filter);
		info("total categories for userId $userId: $categoriesList->totalCount");
		if ($categoriesList->totalCount == 3)
		{
			break;
		}
	}

	if ( $categoriesList->totalCount != 3 )
		return fail(__FUNCTION__ . " categoryUsers weren't inherited!");

	return success(__FUNCTION__);
}

function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$ret = createCategoryAndBindUsers($client);
	$ret += createCategoryTree($client);
	return ($ret);
}


goMain();
