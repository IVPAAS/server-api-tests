<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
require_once('apiTestHelper.php');

function helper_createEntryAndUploadContent($client)
{
    $entry = addEntry($client,__FUNCTION__);

    $uploadTokenObj = new KalturaUploadToken();
    $uploadTokenObj->fileName = '..\resources\Kaltura Test Upload.mp4';
    $uploadToken = $client->uploadToken->add($uploadTokenObj);
    $fileData = '../resources/Kaltura Test Upload.mp4';
    $result = $client->uploadToken->upload($uploadToken->id,$fileData ,null,null,null);
    $resource = new KalturaUploadedFileTokenResource();
    $resource->token = $uploadToken->id;
    $result = $client->baseEntry->addcontent($entry->id, $resource);
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
    $MediaEntry = helper_createEntryAndUploadContent($client);
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
    $MediaEntry = helper_createEntryAndUploadContent($client);
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


function main($dc,$partnerId,$adminSecret,$userSecret)
{
  $client = startKalturaSession($partnerId,$adminSecret,$dc); 
  $ret  = Test1_CloneAReadyEntry($client);
  $ret += Test2_CloneAPendingEntry($client);

  return ($ret);
}

goMain();