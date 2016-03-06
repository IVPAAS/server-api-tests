<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');

const LOG_FILE="./executionLog.txt";
//start session and setting KS function
class bcolors
{
    const OKBLUE = "\033[94m";
    const OKGREEN = "\033[92m";
    const WARNING = "\033[94m";
    const INFO = "\033[93m";
    const FAIL = "\033[91m";
    const ENDC = "\033[0m";
    const BOLD = "\033[1m";
    const UNDERLINE = "\033[4m";
}
function success($msg)
{
    $out = "\n".bcolors::OKGREEN.$msg." OK!".bcolors::ENDC;
    print($out);
    logOutput($out);
    return 0;
}
function fail($msg)
{
    $out = "\n".bcolors::FAIL.$msg." FAIL!".bcolors::ENDC;
    print($out);
    logOutput($out);
    return -1;
}
function info($msg)
{
    $out = "\n".bcolors::INFO.$msg.bcolors::ENDC;
    print($out);
    logOutput($out);
    return 0;
}
function warning($msg)
{
    $out = "\n".bcolors::WARNING.$msg.bcolors::ENDC;
    print($out);
    logOutput($out);
    return 0;
}


function logOutput($msg)
{
    file_put_contents ( LOG_FILE , $msg,$flags =FILE_APPEND );
}


function printUsage()
{
    print ("\n\rUsage: " .$GLOBALS['argv'][0] . " <DC URL> 	<parnter id> <admin secret> <user secret>");
}
function goMain()
{
    if ($GLOBALS['argc']!=5 )
    {
        printUsage();
        exit (1);
    }

    $dcUrl 			= 	$GLOBALS['argv'][1];
    $partnerId 		= 	$GLOBALS['argv'][2];
    $adminSecret	= 	$GLOBALS['argv'][3];
    $userSecret	    = 	$GLOBALS['argv'][4];
    $res =  main($dcUrl,$partnerId,$adminSecret,$userSecret);
    exit($res);
}
function startKalturaSession($partnerId,$secret,$destUrl,$type=KalturaSessionType::ADMIN,$userId=null) 
{
	try
	{
		$config = new KalturaConfiguration($partnerId);
		$config->serviceUrl = $destUrl;
        	$client = new KalturaClient($config);
		$result = $client->session->start($secret, $userId, $type, $partnerId, null, null);
		$client->setKs($result);
		//print("Started session successfully with KS [$result]\n");
		return $client;
	}
	catch (KalturaException $e)
	{
		$msg = $e->getMessage();
		fail("Problem starting session with message: [$msg]\n");
		die("ERROR - cannot generate session with partner id [$partnerId] and secret [$secret]");
	}
}	

function startWidgetSession($destUrl,$partnerId)
{
    try
    {
        $config = new KalturaConfiguration($partnerId);
        $config->serviceUrl = $destUrl;
        $client = new KalturaClient($config);
        $widgetId = "_".$partnerId;
        $expiry = null;
        $result = $client->session->startwidgetsession($widgetId, $expiry);  
        $client->setKs($result->ks);
        return $client;
    }
	catch (KalturaException $e)
	{
		$msg = $e->getMessage();
		shout("Problem starting widget sesseion with message: [$msg]\n");
		die("ERROR - cannot generate widget session with widgetId [$widgetId]");
	}
}




function addKalturaUser($client,$userId)
{
  
  $filter          = new KalturaUserFilter();
  $filter->idEqual = $userId;
  $pager           = null;
  $result          = $client->user->listAction($filter, $pager);
  
  if($result->totalCount==0)
  {
    $user             = new KalturaUser();
    $user->id         = $userId;
    $user->type       = KalturaUserType::USER;
    $user->screenName = $userId;
    $user->fullName   = $userId;
    $user->email      = $userId."@m.com";
    $result           = $client->user->add($user);
  }
  else
  {
    $result           = $result->objects[0];
  }
  
  //print ("\nAdd User ID:".$result->id);
  return $result;
}
function addEntry($client,$name,$mediaType=KalturaMediaType::VIDEO, $profileId = null, $userId='')
{
    $entry                                  = new KalturaMediaEntry();
    $type                                   = KalturaEntryType::MEDIA_CLIP;
    $entry->name                            = $name;
    $entry->mediaType                       = $mediaType;
    if ($profileId != null)
        $entry->conversionProfileId			= $profileId;
    $entry->userId                          = $userId;
    $result                                 = $client->baseEntry->add($entry, $type);
    //print ("\nAdd entry ID:".$result->id);
    return $result;
}

function addCategory($client, $categoryPrefixName, $categoryTag)
{
    $category = new KalturaCategory();
    $categoryName = uniqid($categoryPrefixName);
    /** @var string $categoryName */
    $category->name = $categoryName;
    $category->tags = $categoryTag;
    $result = $newCategory = $client->category->add($category);
    return $result;
}

function addCategoryEntry($client, $categoryId, $entryId)
{
    //create a category entry
    $categoryEntry = new KalturaCategoryEntry();
    $categoryEntry->categoryId = $categoryId;
    $categoryEntry->entryId = $entryId;
    $result = $newCategoryEntry = $client->categoryEntry->add($categoryEntry);
    return $result;
}

function helper_createEmptyEntry($client, $testName)
{
	$entry = addEntry($client, $testName);
	return $entry;
}


function helper_createEntryAndUploaDmp4Content($client, $testName)
{
	$FILE_NAME_MP4 = dirname ( __FILE__ ).'/../resources/Kaltura Test Upload.mp4';
	$entry = addEntry($client, $testName);
	$uploadTokenObj = new KalturaUploadToken();
	$uploadTokenObj->fileName = $FILE_NAME_MP4;
	$uploadToken = $client->uploadToken->add($uploadTokenObj);
	$fileData = $FILE_NAME_MP4;
	$result = $client->uploadToken->upload($uploadToken->id,$fileData ,null,null,null);
	$resource = new KalturaUploadedFileTokenResource();
	$resource->token = $uploadToken->id;
	$result = $client->baseEntry->addcontent($entry->id, $resource);
	return $result;
}

function helper_uploadThumbAsset($client, $entryId)
{
	$thumbAsset = $client->thumbAsset->add($entryId, new KalturaThumbAsset());

	$THUMB_NAME = dirname ( __FILE__ ).'/../resources/thumb_300_150.jpg';
	$uploadTokenObj = new KalturaUploadToken();
	$uploadTokenObj->fileName = $THUMB_NAME;
	$uploadToken = $client->uploadToken->add($uploadTokenObj);
	$fileData = $THUMB_NAME;
	$result = $client->uploadToken->upload($uploadToken->id,$fileData ,null,null,null);
	$resource = new KalturaUploadedFileTokenResource();
	$resource->token = $uploadToken->id;

	$client->thumbAsset->setContent($thumbAsset->id, $resource);
}

function isEntryReady($client,$id)
{
	$result = $client->baseEntry->get($id, null);
	if ($result->status == 2)
		return true;
	return false;
}

function isSubmitting($client, $id)
{
	$result = $client->entryDistribution->get($id);
	if ($result->status == 4) // status submitting
		return true;
	return false;
}

function helper_createEntryAndUploadJpgContent($client)
{
	$FILE_NAME_JPG = dirname ( __FILE__ ).'/../resources/kalturaIcon.jpg';
	$entry = addEntry($client,__FUNCTION__,KalturaMediaType::IMAGE);
	$uploadTokenObj = new KalturaUploadToken();
	$uploadTokenObj->fileName = $FILE_NAME_JPG;
	$uploadToken = $client->uploadToken->add($uploadTokenObj);
	$fileData = $FILE_NAME_JPG;
	$result = $client->uploadToken->upload($uploadToken->id,$fileData ,null,null,null);
	$resource = new KalturaUploadedFileTokenResource();
	$resource->token = $uploadToken->id;
	$result = $client->baseEntry->addcontent($entry->id, $resource);
	return $result;
}


function helper_createPlaylist($client)
{
	$entry = new KalturaPlaylist();
	$entry->type = KalturaEntryType::PLAYLIST;
	$entry->operationAttributes = array();
	$entry->totalResults = 1;
	$entry->playlistType = KalturaPlaylistType::DYNAMIC;
	$type = KalturaEntryType::PLAYLIST;
	$result = $client->baseEntry->add($entry, $type);
	return $result;
}