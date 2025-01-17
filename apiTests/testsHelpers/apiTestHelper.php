<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');

const LOG_FILE="./executionLog.txt";
//start session and setting KS function
class bcolors
{
    const OKBLUE = "\033[34;1m";
    const OKGREEN = "\033[32;1m";
    const FAIL = "\033[31;1m";
    const ENDC = "\033[0m";
    const WARNING = "\033[34;1m";
    const INFO = "\033[33;1m";
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
    if ($GLOBALS['argc'] < 5 )
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

function startKalturaSession($partnerId,$secret,$destUrl,$type=KalturaSessionType::ADMIN,$userId=null, $privileges=null)
{
	try
	{
		$config = new KalturaConfiguration($partnerId);
		$config->serviceUrl = $destUrl;
        $client = new KalturaClient($config);
        $result = $client->session->start($secret, $userId, $type, $partnerId, null, $privileges);
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

function startKSSession($partnerId, $url, $ks)
{
    $config = new KalturaConfiguration($partnerId);
    $config->serviceUrl = $url;
    $client = new KalturaClient($config);
    $client->setKs($ks);
    return $client;
}

function startWidgetSession($destUrl,$partnerId,$widgetId=0)
{
    try
    {
        $config = new KalturaConfiguration($partnerId);
        $config->serviceUrl = $destUrl;
        $client = new KalturaClient($config);
        if($widgetId===0)
			$widgetId = "_".$partnerId;
        $expiry = null;
        $result = $client->session->startwidgetsession($widgetId, $expiry);
        $client->setKs($result->ks);
        return $client;
    }
	catch (KalturaException $e)
	{
		$msg = $e->getMessage();
		fail("Problem starting widget sesseion with message: [$msg]\n");
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

function cutRandomPartFromVideo($sourceFile, $outputFile, $duration)
{
    $sourceVideoLength = getVideoLength($sourceFile);
    $startSec = rand(0,$sourceVideoLength-$duration);
    shell_exec("ffmpeg -ss ".$startSec." -i ".$sourceFile." -c copy -f mp4 -t ".$duration." -y ".$outputFile);
}

function getVideoLength($sourceFileName)
{
    $duration = shell_exec("ffprobe -i ".$sourceFileName." -show_entries format=Duration -v quiet -of csv=\"p=0\"");
    return $duration;
}

function uploadThumbAssetByName($client, $entryId, $fileName)
{
    $thumbAsset = $client->thumbAsset->add($entryId, new KalturaThumbAsset());
    $THUMB_NAME = $fileName;
    $uploadTokenObj = new KalturaUploadToken();
    $uploadTokenObj->fileName = $THUMB_NAME;
    $uploadToken = $client->uploadToken->add($uploadTokenObj);
    $fileData = $THUMB_NAME;
    $result = $client->uploadToken->upload($uploadToken->id,$fileData ,null,null,null);
    $resource = new KalturaUploadedFileTokenResource();
    $resource->token = $uploadToken->id;

    $client->thumbAsset->setContent($thumbAsset->id, $resource);
}

function uploadThumbAsset($client, $entryId)
{
    $THUMB_NAME = dirname ( __FILE__ ).'/../../resources/thumb_300_150.jpg';
    uploadThumbAssetByName($client, $entryId,$THUMB_NAME);
}

function uploadThumbAsset2($client, $entryId)
{
    $THUMB_NAME = dirname ( __FILE__ ).'/../../resources/kalturaIcon.jpg';
    uploadThumbAssetByName($client, $entryId,$THUMB_NAME);
}

function entryDistributionIsSubmitting($client, $id)
{
    return checkDistributionStatus($client, $id,4);
}

function entryDistributionIsUpdating($client, $id)
{
    return checkDistributionStatus($client, $id,5);
}

function checkDistributionStatus($client, $id,$status)
{
    $result = $client->entryDistribution->get($id);
    $result = $client->entryDistribution->get($id);
    info("entryDistribution status : ".$result->status);
    if ($result->status == $status) // status submitting
        return true;
    return false;

}

function isRemoving($client, $id)
{
    $result = $client->entryDistribution->get($id);
    if ($result->status == 6) // status submitting
        return true;
    return false;
}

function createPlaylist($client)
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

function create_widget($client, $role=null)
{
	$filter = new KalturaUiConfFilter();
	$result = $client->uiConf->listAction($filter, null);
	$uiconfId = $result->objects[0]->id;
	$widget = new KalturaWidget();
	$widget->uiConfId = $uiconfId;
	$widget->roles = $role;
	$result = $client->widget->add($widget);
	return $result->id;
}
