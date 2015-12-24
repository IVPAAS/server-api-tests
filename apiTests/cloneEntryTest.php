<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
require_once('apiTestHelper.php');




function helper_createEntryAndUploaDmp4Content($client)
{
    $FILE_NAME_MP4 = dirname ( __FILE__ ).'/../resources/KalturaTestUpload.mp4';
    $entry = addEntry($client,__FUNCTION__);
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
function isEntryReady($client,$id)
{
    $result = $client->baseEntry->get($id, null);
    if ($result->status == 2)
        return true;
    return false;
}
function Test1_CloneAReadyEntry($client)
{
    info("Create entry and upload content");
    $MediaEntry = helper_createEntryAndUploaDmp4Content($client);
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
    $MediaEntry = helper_createEntryAndUploaDmp4Content($client);
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


function main($dc,$partnerId,$adminSecret,$userSecret)
{
  $client = startKalturaSession($partnerId,$adminSecret,$dc);
  $ret  = Test1_CloneAReadyEntry($client);
  $ret += Test2_CloneAPendingEntry($client);
  $ret += Test3_ClonePlaylistEntry( $client );
  $ret += Test4_CloneImageEntry($client);
  return ($ret);
}

goMain();
