<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__).'/../testsHelpers/apiTestHelper.php');

function createCategory($client,$categoryName, $parentCategoryId = null)
{
	
	$category = new KalturaCategory();
	$category->name = $categoryName;
	$category->inheritanceType = KalturaInheritanceType::MANUAL;
	$category->privacyContext = 'PrivacyContext';
	if ($parentCategoryId)
		$category->parentId = $parentCategoryId;

	$result = $client->category->add($category);
	info("category $result->id was added, parent: " . $parentCategoryId);
	return $result;
	
}

function changeCategoryInheritance($client,$categoryId, $dest) {
	$category = new KalturaCategory();
	//KalturaInheritanceType::INHERIT;
	//KalturaInheritanceType::MANUAL;
	$category->inheritanceType = $dest;
	$result = $client->category->update($categoryId, $category);
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
	echo "\ncreated user with id: " .$id ."\n";
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

function init($client, $userId, $categoryName,$subCategoryName)
{
	$user = createUser($client,$userId);
	$category = createCategory($client,$categoryName);
	$subCategory = createCategory($client,$subCategoryName,$category->id);
	$categoryUser = bindUserToCategory($client, $userId, $category->id);
	echo "\nnew User id: " .$user->id ." is bind to Category: " .$category->id ."\n";
	$array = array($category->id,$subCategory->id);
	return $array;
}
function done($client, $userId, $categoryID, $subCategoryId)
{
	$user = deleteUser($client,$userId);
	$category = deleteCategory($client, $subCategoryId);
	$category = deleteCategory($client, $categoryID);
	echo "\ndelete User id: " .$userId ." and Category: " .$categoryID ."\n";
}


function validateuserInCategoryKuser($client, $userId, $categoryId = null ) {
	try {
		$ans = $client->categoryUser->get($categoryId, $userId);
		return true;
	} catch(Exception $e) {
		return false;
	}

}


function test1_basicChangeAndRun($client,$userId, $categoryId,$subCategoryId) {
	for ($i = 0; $i < 50; $i++) {
		changeCategoryInheritance($client, $subCategoryId, KalturaInheritanceType::INHERIT);
		if (!validateuserInCategoryKuser($client,$userId, $subCategoryId))
			return fail(__FUNCTION__ . "     -  Error: in subCat no user even INHERIT in test " .$i);
		if (!validateuserInCategoryKuser($client,$userId, $categoryId))
			return fail(__FUNCTION__ . "     -  Error: in parent (when sub is inherit) - no user! in test " .$i);
		changeCategoryInheritance($client, $subCategoryId, KalturaInheritanceType::MANUAL);
		if (!validateuserInCategoryKuser($client,$userId, $categoryId))
			return fail(__FUNCTION__ . "     -  Error: in parent (when sub is manual) - no user! in test " .$i);
		echo ".";
	}
	echo "finished " .$i ." tests\n";
	return success(__FUNCTION__);
}

function test2_addClientToSub($client,$userId, $categoryId,$subCategoryId) {
	$userId2 = "testServerUser2@i.com";
	$user = createUser($client,$userId2);
	$categoryUser = bindUserToCategory($client, $userId2, $subCategoryId);
	for ($i = 0; $i < 10; $i++) {
		changeCategoryInheritance($client, $subCategoryId, KalturaInheritanceType::INHERIT);
		if (!validateuserInCategoryKuser($client,$userId, $subCategoryId))
			return fail(__FUNCTION__ . "     -  Error: in subCat no user even INHERIT in test " .$i);
		if (!validateuserInCategoryKuser($client,$userId, $categoryId))
			return fail(__FUNCTION__ . "     -  Error: in parent (when sub is inherit) - no user! in test " .$i);
		if (validateuserInCategoryKuser($client,$userId2, $subCategoryId))
			return fail(__FUNCTION__ . "     -  Error: in subCat (when sub is inherit) - sub Private user shown - never deleted in test " .$i);
		if (validateuserInCategoryKuser($client,$userId2, $categoryId))
			return fail(__FUNCTION__ . "     -  Error: in parent (when sub is inherit) - sub Private user shown! in test " .$i);

		changeCategoryInheritance($client, $subCategoryId, KalturaInheritanceType::MANUAL);
		sleep(60);

		if (validateuserInCategoryKuser($client,$userId, $subCategoryId))
			return fail(__FUNCTION__ . "     -  Error: in subCat (when sub is manual) - have user from parent in test " .$i);
		if (validateuserInCategoryKuser($client,$userId2, $subCategoryId))
			return fail(__FUNCTION__ . "     -  Error: in subCat (when sub is manual) -  sub Private user shown! in test " .$i);

		$categoryUser = bindUserToCategory($client, $userId2, $subCategoryId);
		if (!validateuserInCategoryKuser($client,$userId, $categoryId))
			return fail(__FUNCTION__ . "     -  Error: in parent (when sub is manual) - no user! in test " .$i);
		if (!validateuserInCategoryKuser($client,$userId2, $subCategoryId))
			return fail(__FUNCTION__ . "     -  Error: in subCat (when sub is manual) -  no sub Private user shown! in test " .$i);
		echo ".";
	}
	$user = deleteUser($client,$userId2);
	echo "finished " .$i ." tests\n";
	return success(__FUNCTION__);
}

function test3_addExtraUserToParent($client,$userId, $categoryId,$subCategoryId) {
	$userId3 = "testServerUser3@i.com";
	$user = createUser($client,$userId3);
	$categoryUser = bindUserToCategory($client, $userId3, $categoryId);
	for ($i = 0; $i < 50; $i++) {
		changeCategoryInheritance($client, $subCategoryId, KalturaInheritanceType::INHERIT);
		if (!validateuserInCategoryKuser($client,$userId, $subCategoryId))
			return fail(__FUNCTION__ . "     -  Error: in subCat (when sub is inherit) -no user num1! in test " .$i);
		if (!validateuserInCategoryKuser($client,$userId, $categoryId))
			return fail(__FUNCTION__ . "     -  Error: in parent (when sub is inherit) - no user num1! in test " .$i);
		if (!validateuserInCategoryKuser($client,$userId3, $subCategoryId))
			return fail(__FUNCTION__ . "     -  Error: in subCat (when sub is inherit) -no user num2! in test " .$i);
		if (!validateuserInCategoryKuser($client,$userId3, $categoryId))
			return fail(__FUNCTION__ . "     -  Error: in parent (when sub is inherit) - no user num2! in test " .$i);

		changeCategoryInheritance($client, $subCategoryId, KalturaInheritanceType::MANUAL);

		if (!validateuserInCategoryKuser($client,$userId, $categoryId))
			return fail(__FUNCTION__ . "     -  Error: in parent (when sub is manual) - no user num1! in test " .$i);
		if (!validateuserInCategoryKuser($client,$userId3, $categoryId))
			return fail(__FUNCTION__ . "     -  Error: in parent (when sub is manual) - no user num2! in test " .$i);
		if (validateuserInCategoryKuser($client,$userId, $subCategoryId))
			return fail(__FUNCTION__ . "     -  Error: in subCat (when sub is manual) -  parent user num1 shown! in test " .$i);
		if (validateuserInCategoryKuser($client,$userId3, $subCategoryId))
			return fail(__FUNCTION__ . "     -  Error: in subCat (when sub is manual) -  parent user num2 shown! in test " .$i);
		echo ".";
	}
	$user = deleteUser($client,$userId3);
	echo "finished " .$i ." tests\n";
	return success(__FUNCTION__);
}



function searchInCategoryKuser ($client,$userId, $categoryId){
	if (validateuserInCategoryKuser($client, $userId,$categoryId))
		return success(__FUNCTION__);
	else return fail(__FUNCTION__ . "     -  Error: no such a user with id: " .$userId ."for category: " .$categoryId);
}



function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$categoryName = "testServerCategory";
	$subCategoryName = "sub" .$categoryName;
	$userId = "testServerUser@i.com";
	//echo "\n";

	try {
		$array = init($client, $userId, $categoryName,$subCategoryName);

		$ret = test1_basicChangeAndRun($client,$userId, $array[0],$array[1]);
		$ret += test2_addClientToSub($client,$userId, $array[0],$array[1]);
		$ret += test3_addExtraUserToParent($client,$userId, $array[0],$array[1]);

		done($client, $userId, $array[0],$array[1]);
		return ($ret);
	} catch (Exception $e) { //finally
		done($client, $userId, $array[0],$array[1]);
		return ($ret);
	}







}


goMain();
