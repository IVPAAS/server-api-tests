<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__).'/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');

function createCategory($client,$categoryName, $parentId = null, $inheritanceType = KalturaInheritanceType::INHERIT)
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
		$category->inheritanceType = $inheritanceType;
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

function createCategoryTreeAndDeleteChildWithoutMovingEntries( $client )
{
	$rootCat = null;
	$CategoryChildLeaf = null;
	$CategoryChild = null;
	createCategoryTreeWithEntry($client, $rootCat, $CategoryChildLeaf, $CategoryChild,true);

	if (validateCategoryTreeCreation($client, $rootCat, 14))
		return false;

	if (validateCategoryEntriesCreation($client, $rootCat, 9))
		return false;

	if (validateCategoryUsersCreation($client, $rootCat, 9))
		return false;

	info("Deleting child category of root category and not moving entries to parent category");
	$parentCategory = deleteCategoryAndRetrieveParentId($client, $CategoryChild, false);

	if (validateCategoryTreeCount($client, $parentCategory, 1))
		return fail(__FUNCTION__ . " Category leaf $CategoryChild->id wasn't deleted!");

	if (validateCategoryTreeEntryCount($client, $parentCategory, 0))
		return fail(__FUNCTION__ . " Category entries weren't deleted successfully !");

	if (validateCategoryEntryCountForSpecificCategory($client, $parentCategory, 0))
		return fail(__FUNCTION__ . " Category entries moved to category when requested not to be moved!");

	if (validateCategoryUsersCount($client, $parentCategory, 0))
		return fail(__FUNCTION__ . " Category users weren't deleted successfully !");

	return success(__FUNCTION__);
}


function createCategoryTreeAndDeleteChildWithMovingEntries( $client )
{
	$rootCat = null;
	$CategoryChildLeaf = null;
	$CategoryChildLevel1 = null;
	createCategoryTreeWithEntry($client, $rootCat, $CategoryChildLeaf, $CategoryChildLevel1,true );

	if (validateCategoryTreeCreation($client, $rootCat, 14))
		return false;

	if (validateCategoryEntriesCreation($client, $rootCat, 9))
		return false;

	if (validateCategoryUsersCreation($client, $rootCat, 9))
		return false;

	info("Deleting child category of root category");
	$parentCategory = deleteCategoryAndRetrieveParentId($client, $CategoryChildLevel1, true);

	if (validateCategoryTreeCount($client, $parentCategory, 1))
		return fail(__FUNCTION__ . " Category $CategoryChildLevel1->id wasn't deleted!");

	if (validateCategoryEntryCountForSpecificCategory($client, $parentCategory, 1))
		return fail(__FUNCTION__ . " Category entries didn't move to parent category when requested to be moved!");

	 if (validateCategoryTreeEntryCount($client, $parentCategory, 1))
                return fail(__FUNCTION__ . " Category entries weren't deleted successfully !");

	if (validateCategoryUsersCount($client, $parentCategory, 0))
		return fail(__FUNCTION__ . " Category users weren't deleted successfully !");

	return success(__FUNCTION__);
}


function createCategoryTreeAndLeafDeleteWithMovingEntries( $client )
{
	$rootCat1 = null;
	$CategoryChildLeaf1 = null;
	$CategoryChildLevel1 = null;
	createCategoryTreeWithEntry($client, $rootCat, $CategoryChildLeaf1, $CategoryChildLevel1, true);

	if (validateCategoryTreeCreation($client, $rootCat, 14))
		return false;

	if (validateCategoryEntriesCreation($client, $rootCat, 9))
		return false;

	if (validateCategoryUsersCreation($client, $rootCat, 9))
		return false;

	$parent = deleteCategoryAndRetrieveParentId($client, $CategoryChildLeaf1, true);

	if (validateCategoryTreeCount($client, $parent, 3))
		return fail(__FUNCTION__ . " Category leaf $CategoryChildLeaf1->id wasn't deleted!");

	if (validateCategoryTreeEntryCount($client, $parent, 3))
		return fail(__FUNCTION__ . " Category entries weren't deleted successfully !");

	if (validateCategoryUsersCount($client, $parent, 2))
		return fail(__FUNCTION__ . " Category users weren't deleted successfully !");

	if (validateCategoryEntryCountForSpecificCategory($client, $parent, 1))
		return fail(__FUNCTION__ . " Category entries didn't move to category when requested to.");

	return success(__FUNCTION__);

}

function createCategoryTreeAndLeafDeleteWithoutMovingEntries( $client )
{
	$rootCat2 = null;
	$CategoryChildLeaf2 = null;
	$CategoryChild2 = null;
	createCategoryTreeWithEntry($client, $rootCat2, $CategoryChildLeaf2, $CategoryChild2, true);


	if (validateCategoryTreeCreation($client, $rootCat2, 14))
		return false;

	if (validateCategoryEntriesCreation($client, $rootCat2, 9))
		return false;

	$parentCategory = deleteCategoryAndRetrieveParentId($client, $CategoryChildLeaf2, false);

	if (validateCategoryTreeCount($client, $parentCategory, 3))
		return fail(__FUNCTION__ . " Category leaf $CategoryChildLeaf2->id wasn't deleted!");

	if (validateCategoryEntryCountForSpecificCategory($client, $parentCategory, 0))
		return fail(__FUNCTION__ . " Category entries moved to category when requested not to be moved!");

	if (validateCategoryTreeEntryCount($client, $parentCategory, 2))
		return fail(__FUNCTION__ . " Category entries weren't deleted successfully !");

	if (validateCategoryUsersCount($client, $parentCategory, 2))
                return fail(__FUNCTION__ . " Category users weren't deleted successfully !");

	return success(__FUNCTION__);
}

/**
 * @param $client
 * @param $category
 * @param $count
 * @return int|string
 */
function validateCategoryEntryCountForSpecificCategory($client, $category, $count)
{
	$filter = new KalturaCategoryEntryFilter();
	$filter->categoryIdEqual = $category->id;

	$retries = 10;
	$categoryEntryList = 0;
	for ($i = 0; $i < $retries; $i++) {
		info("sleep 30 seconds");
		for ($j = 0; $j < 30; $j++) {
			sleep(1);
			print(".");
		}
		$categoryEntryList = $client->categoryEntry->listAction($filter);
		if ($categoryEntryList->totalCount == $count)
			break;
	}

	info("Total categories entries count for category $category->id is $categoryEntryList->totalCount");
	if ($categoryEntryList->totalCount != $count)
		return fail(__FUNCTION__ . " Category entry count doesn't match expected $count and got $categoryEntryList->totalCount !");
	else
		return info("Category tree entry count match.");
}

/**
 * @param $client
 * @param $topCategory
 * @param $count
 * @return int|string
 */
function validateCategoryTreeEntryCount($client, $topCategory, $count)
{
	$filter = new KalturaCategoryEntryFilter();
	$filter->categoryFullIdsStartsWith = $topCategory->fullIds;

	$retries = 3;
	$categoriesEntryList = 0;
	for ($i = 0; $i < $retries; $i++) {
		info("sleep 30 seconds");
		for ($j = 0; $j < 30; $j++) {
			sleep(1);
			print(".");
		}
		$categoriesEntryList = $client->categoryEntry->listAction($filter);
		if ($categoriesEntryList->totalCount == $count)
			break;
	}

	info("Total categories entries for category tree starting at category $topCategory->id is: $categoriesEntryList->totalCount");
	if ($categoriesEntryList->totalCount != $count)
		return fail(__FUNCTION__ . " Category tree entry count doesn't match expected $count and got $categoriesEntryList->totalCount !");
	else
		return info("Category tree entry count match.");
}

/**
 * @param $client
 * @param $parentCategoryOfLeaf
 * @return int|string
 */
function validateCategoryUsersCount($client, $topCategory, $count)
{
	$filter = new KalturaCategoryUserFilter();
	$filter->categoryFullIdsStartsWith = $topCategory->fullIds;

	$retries = 3;
	$categoriesUsersList = 0;
	for ($i = 0; $i < $retries; $i++) {
		info("sleep 30 seconds");
		for ($j = 0; $j < 30; $j++) {
			sleep(1);
			print(".");
		}
		$categoriesUsersList = $client->categoryUser->listAction($filter);
		if ($categoriesUsersList->totalCount == $count)
			break;
	}

	info("Total categories users for category tree starting at category $topCategory->id is: $categoriesUsersList->totalCount");
	if ($categoriesUsersList->totalCount != $count)
		return fail(__FUNCTION__ . " Category tree user count doesn't match expected $count and got $categoriesUsersList->totalCount !");
	else
		return info("Category tree entry count match.");
}

/**
 * @param $client
 * @param $topCategory
 * @param $count
 * @return int|string
 */
function validateCategoryTreeCount($client, $topCategory, $count)
{
	$filter = new KalturaCategoryFilter();
	$filter->fullIdsStartsWith = $topCategory->fullIds;
	$categoriesList = $client->category->listAction($filter);

	info("Total categories left after deletion $categoriesList->totalCount");

	if ($categoriesList->totalCount != $count)
		return fail(__FUNCTION__ . " Category tree count doesn't match expected $count and got $categoriesList->totalCount !");
	else
		return info("Category tree count match.");
}

/**
 * @param $client
 * @param $category
 * @param $moveEntriesToParentCategory
 * @return mixed
 */
function deleteCategoryAndRetrieveParentId($client, $category, $moveEntriesToParentCategory)
{
	info("Deleting category $category->id");
	$parentCategoryOfLeaf = $client->category->get($category->parentId);
	$client->category->delete($category->id, $moveEntriesToParentCategory);
	return $parentCategoryOfLeaf;
}

/**
 * @param $client
 * @param $rootCat
 * @param $count
 * @return int|string
 */
function validateCategoryEntriesCreation($client, $rootCat ,$count)
{
	$filter = new KalturaCategoryEntryFilter();
	$filter->categoryFullIdsStartsWith = $rootCat->fullIds;;
	$categoryEntryList = $client->categoryEntry->listAction($filter);
	info("total categories entries created $categoryEntryList->totalCount");
	if ($categoryEntryList->totalCount != $count)
		return fail(__FUNCTION__ . " Category entries weren't created!");
	else
		return info("Category entries created successfully");
}

/**
 * @param $client
 * @param $rootCat
 * @param $count
 * @return int|string
 */
function validateCategoryUsersCreation($client, $rootCat ,$count)
{
	$filter = new KalturaCategoryUserFilter();
	$filter->categoryFullIdsStartsWith = $rootCat->fullIds;;
	$categoryUserList = $client->categoryUser->listAction($filter ,null);
	info("total categories users created $categoryUserList->totalCount");
	if ($categoryUserList->totalCount != $count)
		return fail(__FUNCTION__ . " Category users weren't created!");
	else
		return info("Category users created successfully");
}


/**
 * @param $client
 * @param $rootCat
 * @param $count
 * @return int|string
 */
function validateCategoryTreeCreation($client, $rootCat, $count)
{
	$filter = new KalturaCategoryFilter();
	$filter->fullIdsStartsWith = $rootCat->fullIds;

	$categoriesList = $client->category->listAction($filter);
	info("total categories tree node created $categoriesList->totalCount");

	if ($categoriesList->totalCount != $count)
		return fail(__FUNCTION__ . " Category tree wasn't created!");
	else
		return info("Category tree created successfully");
}

/**
 * @param $client
 * @param $rootCat
 * @param $CategoryLeaf
 * @param $CategoryChild
 * @param $shouldAddUserAndBindToCategroy
 */
function createCategoryTreeWithEntry($client, &$rootCat, &$CategoryLeaf, &$CategoryChild, $shouldAddUserAndBindToCategroy = false )
{
	$rootCat = createCategory($client, 'rootCat' . rand(0, 1000000), null, KalturaInheritanceType::MANUAL);
	$CategoryChildLevel1 = createCategory($client, "childOf $rootCat->name", $rootCat->id, KalturaInheritanceType::MANUAL);
	$CategoryChild = $CategoryChildLevel1;
	$MediaEntry = createEntryAndUploaDmp4Content($client, 'categoryEntryTest');
	info("Wait for entry to be ready id =" . $MediaEntry->id);
	while (isEntryReady($client, $MediaEntry->id) != true) {
		sleep(1);
		print (".");
	}

	$CategoryChildLeaf = null;
	info("Creating category tree with entries");
	for ($i = 0; $i < 3; $i++) {
		$CategoryChildLevel2 = createCategory($client, rand(0, 1000000000000000), $CategoryChildLevel1->id, KalturaInheritanceType::MANUAL);
		for ($j = 0; $j < 3; $j++) {
			$CategoryChildLeaf = createCategory($client, rand(0, 1000000000000000), $CategoryChildLevel2->id, KalturaInheritanceType::MANUAL);
			addCategoryEntry($client, $CategoryChildLeaf->id, $MediaEntry->id);
			if ($shouldAddUserAndBindToCategroy)
			{
				$userId = 'user' .rand(0,1000000) . $rootCat->id;
				AddUserToCategory($client, $CategoryChildLeaf->id, $userId);
			}
		}
	}
	$CategoryLeaf = $CategoryChildLeaf;
}


function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$ret = createCategoryAndBindUsers($client);
	$ret += createCategoryTree($client);
	$ret += createCategoryTreeAndLeafDeleteWithMovingEntries($client);
	$ret += createCategoryTreeAndLeafDeleteWithoutMovingEntries( $client );
	$ret += createCategoryTreeAndDeleteChildWithMovingEntries($client);
	$ret += createCategoryTreeAndDeleteChildWithoutMovingEntries($client);
	return ($ret);
}


goMain();
