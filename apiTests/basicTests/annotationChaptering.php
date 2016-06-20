<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/InVideoQuizHelper.php');

function updateCuePoints($client)
{
	//todo list of entries until not found
	$pageIndex=4;

	do
	{

		$pager = new KalturaFilterPager();
		$pager->pageSize = 100;
		$pager->pageIndex = $pageIndex;
		$pageIndex=$pageIndex+1;
		$filter = null;
		$result = $client->baseEntry->listAction($filter, $pager);
		$numberOfFoundObjects= count($result->objects);
		info(" page index {$pager->pageIndex} found - ".$numberOfFoundObjects);
		if($numberOfFoundObjects===0)
			break;

		for ($index=0; $index<$numberOfFoundObjects;$index++)
		{
			$request2filter = new KalturaCuePointFilter();
			$entryObject =  $result->objects[$index];
			$request2filter->entryIdEqual =$entryObject->id;
			$request2filter->cuePointTypeEqual = KalturaCuePointType::ANNOTATION;
			$cuepointPlugin = KalturaCuepointClientPlugin::get($client);
			$pager->pageIndex = 1;
			$pager->pageSize = 100;
			$results = $cuepointPlugin->cuePoint->listAction($request2filter, $pager);
			$numofAnnotationCuePoint=count($results->objects);
			for ($i=0 ; $i <$numofAnnotationCuePoint ;$i++)
			{
				warning("Handling entry {$request2filter->entryIdEqual} cuepoint {$results->objects[$i]->id}  {$results->objects[$i]->text}");
				file_put_contents ( "/tmp/inc_annotatation" , "{$results->objects[$i]->id}\n",$flags =FILE_APPEND );
				//updateCuePoint($client,$results->objects[$i]);
			}
			print(".");
		}

	}while(1);
	//update its name - add . to the text and then remove the .
}

function updateCuePoint($client,$cuepointObject)
{
	$cuePoint = new KalturaAnnotation();
	$cuePoint->isPublic = KalturaNullableBoolean::TRUE_VALUE;
	$cuepointPlugin = KalturaCuepointClientPlugin::get($client);
	info("Updating cue point {$cuepointObject->id}");
	logOutput(print_r($cuepointObject,true));
	$result = $cuepointPlugin->cuePoint->update($cuepointObject->id, $cuePoint);

	return $result;
}

function createAnnontation($client,$entryId,$tags=null,$isPublic=null)
{
	$cuepointPlugin = KalturaCuepointClientPlugin::get($client);
	$cuePoint = new KalturaAnnotation();
	$cuePoint->isPublic = $isPublic;
	$cuePoint->tags = $tags;
	$cuePoint->entryId = $entryId;
	$res = $cuepointPlugin->cuePoint->add($cuePoint);
	return $res;
}

function getAnnotation($client,$entryid,$annotaionid,$tags=null)
{
	$requestfilter = new KalturaCuePointFilter();
	$requestfilter->entryIdEqual =$entryid;
	$requestfilter->cuePointTypeEqual = KalturaCuePointType::ANNOTATION;
	$cuepointPlugin = KalturaCuepointClientPlugin::get($client);
	$requestfilter->idEqual = $annotaionid;
	$requestfilter->tagsLike = $tags;

	$results = $cuepointPlugin->cuePoint->listAction($requestfilter, null);
	return $results;
}

function validateNotEmpty($arraysList)
{
	 if (count($arraysList->objects) == 0)
		 throw new exception ("Not good");
}

function validateEmpty($arraysList)
{
	if (count($arraysList->objects) > 0)
		throw new exception ("Not good");
}


function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$annotaionArray=array();

	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$entry  = helper_createEmptyEntry($client,__FILE__);

	//create annotation + tags = chaptering - ispublic = true
	//create annotation + tags = chaptering - ispublic = false
	//create annotation + tags = chaptering - ispublic = not set

	$annotaionArray[] = createAnnontation($client ,$entry->id,'chaptering', true);
	$annotaionArray[] = createAnnontation($client ,$entry->id, 'chaptering', false);
	$annotaionArray[] = createAnnontation($client ,$entry->id, 'chaptering');


	//create anootation + ispublic = true
	//create anootation +  ispublic = false
	//create anootation +  ispublic = not set
	$annotaionArray[] = createAnnontation($client ,$entry->id, null, true);
	$annotaionArray[] = createAnnontation($client ,$entry->id, null, false);
	$annotaionArray[] = createAnnontation($client ,$entry->id, null);


	//create anootation + ispublic = true
	//create anootation +  ispublic = false
	//create anootation +  ispublic = not set
	$annotaionArray[] = createAnnontation($client ,$entry->id, 'BOO', true);
	$annotaionArray[] = createAnnontation($client ,$entry->id, 'BOO', false);
	$annotaionArray[] = createAnnontation($client ,$entry->id, 'BOO');


	//Widget KS
	$widgetClient = startWidgetSession($dc,$partnerId);



	//try to get annotaion - with
		//tags = chaptering

		  // public  = true should work
	info("try to get annotaion - with tags = chaptering");
	info("public  = true should work");
	validateNotEmpty(getAnnotation($widgetClient,$entry->id,$annotaionArray[0]->id,'chaptering'));
	info("public  = false should work");
		  //  public  = false should not work
	validateNotEmpty(getAnnotation($widgetClient,$entry->id,$annotaionArray[1]->id,'chaptering'));
		//  public not set should work
	info("public not set should work");
	validateNotEmpty(getAnnotation($widgetClient,$entry->id,$annotaionArray[2]->id,'chaptering'));


	//try to get annotaion - with
	//no tags
	// public  = true should work
	info("try to get annotaion - with no tags");
	info("public  = true should work");
	validateNotEmpty(getAnnotation($widgetClient,$entry->id,$annotaionArray[3]->id));
	info(" public  = false should not work");
	//  public  = false should not work
	validateEmpty(getAnnotation($widgetClient,$entry->id,$annotaionArray[4]->id));
	info("public not set should work");
	//  public not set should work
	validateEmpty(getAnnotation($widgetClient,$entry->id,$annotaionArray[5]->id));

	//try to get annotaion - with
	// tags = something
	info("try to get annotaion - tags = something");
	// public  = true should work
	info("public  = true should work");
	validateNotEmpty(getAnnotation($widgetClient,$entry->id,$annotaionArray[6]->id,'BOO'));
	info("public  = false should not work");
	//  public  = false should not work
	validateEmpty(getAnnotation($widgetClient,$entry->id,$annotaionArray[7]->id,'BOO'));
	info("public not set should work");
	//  public not set should work
	validateEmpty(getAnnotation($widgetClient,$entry->id,$annotaionArray[8]->id,'BOO'));

	//try to get annotaion - with admin KS
	// tags = something
	// public  = true should work
	//  public  = false should work
	//  public not set should work

	//try to get annotaion - with user KS = owners
	// public  = true should work
	//  public  = false should work
	//  public not set should work

	//try to get annotaion - with user KS = someone
	// public  = true should work
	//  public  = false should not work
	//  public not set should not work


	//start w

	updateCuePoints($client);
}

goMain();



