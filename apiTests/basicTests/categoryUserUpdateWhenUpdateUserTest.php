<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__).'/../testsHelpers/apiTestHelper.php');

function createCategory($client,$categoryName, $parentId = null, $inheritanceType = KalturaInheritanceType::INHERIT)
{
	
	$category = new KalturaCategory();
	$category->name = $categoryName;
	/*
	$category->appearInList = KalturaAppearInListType::CATEGORY_MEMBERS_ONLY;
	$category->privacy = KalturaPrivacyType::MEMBERS_ONLY;
	$category->defaultPermissionLevel = KalturaCategoryUserPermissionLevel::MEMBER;
	$category->contributionPolicy = KalturaContributionPolicyType::MEMBERS_WITH_CONTRIBUTION_PERMISSION;
	$category->privacyContext = 'PrivacyContext';
	*/
	$category->inheritanceType = KalturaInheritanceType::MANUAL;

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
	$user->email = $id.'@h.com';
	$result = $client->user->add($user);
	return $result;
}
function deleteUser($client,$id)
{
	$result = $client->user->delete($id);
	return $result;
}
function deleteCategory($client,$categoryId)
{
	$result = $client->category->delete($categoryId);
	return $result;
}

function bindUserToCategory($client,$userId,$categoryId)
{
	$categoryUser = new KalturaCategoryUser();
	$categoryUser->categoryId = $categoryId;
	$categoryUser->userId = $userId;
	//$categoryUser->permissionLevel = KalturaCategoryUserPermissionLevel::MEMBER;
	$result = $client->categoryUser->add($categoryUser);
	return $result;
}

function init($client, $userId, $categoryName)
{
	$user = createUser($client,$userId);
	$category = createCategory($client,$categoryName);
	$categoryUser = bindUserToCategory($client, $userId, $category->id);
	echo "\nnew User id: " .$user->id ." is bind to Category: " .$category->id ."\n";
	return $category->id;
}

function done($client, $userId, $categoryID)
{
	$user = deleteUser($client,$userId);
	$category = deleteCategory($client, $categoryID);
	echo "\ndelete User id: " .$userId ." and Category: " .$categoryID ."\n";
}





function updateUserId($client, $oldUserId, $newUserId) {
	try {
		$user = new KalturaUser();
		$user->id = $newUserId;
		$result = $client->user->update($oldUserId, $user);
		echo "\nUser id was update from: " .$oldUserId ." to: " .$newUserId ."\n";
		return true;
	}catch(Exception $e) {
		return false;
	}
	$result = $client->user->update($oldUserId, $newUserId);

}


function validateUserAndScreenNameInKuser($client, $userId, $screenName = null) {
	try {
		$ans = $client->user->get($userId);
		if (!$screenName || $ans->screenName == $screenName)
			return true;
		else return false;
	} catch(Exception $e) {
		return false;
	}
}


function validateuserInCategoryKuser($client, $userId, $categoryId = null ) {
	$filter = new KalturaCategoryUserFilter();
	//$filter->categoryIdEqual = $categoryId;
	$filter->userIdEqual  = $userId;
	$categoriesUserList = $client->categoryUser->listAction($filter);
	if ($categoriesUserList->totalCount > 0) return true;
	else return false;
	/*
	try {
		$ans = $client->categoryUser->get($categoryId, $userId);
		return true;
	} catch(Exception $e) {
		return false;
	}
	*/
}




function newInCategoryKuser ($client,$userId,$categoryID){
	if (validateuserInCategoryKuser($client, $userId,$categoryID))
		return success(__FUNCTION__);
	else return fail(__FUNCTION__ . "     -  Error: no such a user with id: " .$userId);
}
function oldInCategoryKuser ($client,$userId,$categoryID){
	if (!validateuserInCategoryKuser($client, $userId,$categoryID))
		return success(__FUNCTION__);
	else return fail(__FUNCTION__ . "     -  Error: found a user with id: " .$userId);
}




function newInKuser ($client,$userId){
	if (validateUserAndScreenNameInKuser($client, $userId))
		return success(__FUNCTION__);
	else return fail(__FUNCTION__ . "     -  Error: no such a user with id: " .$userId);
}
function oldInKuser ($client,$userId){
	if (!validateUserAndScreenNameInKuser($client, $userId))
		return success(__FUNCTION__);
	else return fail(__FUNCTION__ . "     -  Error: found a user with id: " .$userId);
}



function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$categoryName = "privateForTest";
	$userOldId = "tempUser";
	$newUserId = "newTempUser";

	//$ret = newInKuser($client, $newUserId);
	//return ($ret);

	try {
		$categoryID = init($client, $userOldId, $categoryName);
		$ret = newInKuser($client, $userOldId);
		$ret += newInCategoryKuser($client, $userOldId,$categoryID);
		echo "\nupdate userId \n";
		updateUserId($client, $userOldId, $newUserId);
		sleep(3);
		echo "search for old ID";
		$ret = oldInKuser($client, $userOldId);
		$ret += oldInCategoryKuser($client, $userOldId,$categoryID);
		echo "\nsearch for New ID";
		$ret = newInKuser($client, $newUserId);
		$ret += newInCategoryKuser($client, $newUserId,$categoryID);

		done($client, $newUserId, $categoryID);
		return ($ret);
	} catch (Exception $e) { //finally
		done($client, $newUserId, $categoryID);
	}







}


goMain();
