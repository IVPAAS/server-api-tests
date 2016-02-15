<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');

const LOG_FILE="./executionLog.txt";
//start session and setting KS function
class bcolors
{
    const OKBLUE = "\033[34;1m";
    const OKGREEN = "\033[32;1m";
    const FAIL = "\033[31;1m";
    const ENDC = "\033[0m";
    const WARNING = "\033[34;1m";
    const INFO = "\033[33:1m";
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
    print ("\n\r for adding quiz.\r\n");
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
