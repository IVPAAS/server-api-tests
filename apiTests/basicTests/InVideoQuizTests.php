<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/InVideoQuizHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');

function report1($client,$userEntryId)
{
	$reportType = KalturaReportType::QUIZ_USER_AGGREGATE_BY_QUESTION;
	$reportInputFilter = new KalturaReportInputFilter();
	$pager = new KalturaFilterPager();
	$order = null;
	$objectIds = $userEntryId;
	$result = $client->report->gettable($reportType, $reportInputFilter, $pager, $order, $objectIds);
	//print ("QUIZ_USER_AGGREGATE_BY_QUESTION");
	//print_r($result);
}

function Test1_Basicflow($client)
{
	info('start ' .  __FUNCTION__);
	$entry=addEntry($client,__FUNCTION__);
	$quiz = createNewQuiz($client,$entry->id,null,null,null,null,null,null);
	$questions = array();
	for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
	{
		$questionCue = addQuestionsOnQuiz($client,$entry->id,"Question".$questionIndex);
		$questions[$questionIndex]=$questionCue->id;
	}		 
	for ($userIndex=0;$userIndex<2;$userIndex++)
	{
		$user = addKalturaUser($client,"UU".$userIndex);
		$quizUserEntry = addQuizUserEntry($client,$user->id,$entry->id);
		for ( $answerIndex=0 ; $answerIndex < 4 ; $answerIndex ++)
		{
			$answerCue = addAnswer($client,$entry->id,$questions[$answerIndex],$quizUserEntry->id,"Q");
		}
		$res = submitQuiz($client,$quizUserEntry->id);
		//print_r($res);
	}
	
	return success(__FUNCTION__);
}
function Test2_ValidateNoScoreUponSubmit($client,$partnerId,$userSecret,$dc)
{
	info('start ' .  __FUNCTION__);
	$entry=addEntry($client,__FUNCTION__);
	$quiz = createNewQuiz($client,$entry->id,null,null,null,null,null,KalturaNullableBoolean::FALSE_VALUE);
	$questions = array();
	for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
	{
		$questionCue = addQuestionsOnQuiz($client,$entry->id,"Question".$questionIndex);
		$questions[$questionIndex]=$questionCue->id;
	}		 
	for ($userIndex=0;$userIndex<2;$userIndex++)
	{
		$userId = "UU".$userIndex;
		//Create user session
		$user = addKalturaUser($client,$userId);
		$userClient = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER,$userId);
		
		$quizUserEntry = addQuizUserEntry($userClient,$user->id,$entry->id);
		for ( $answerIndex=0 ; $answerIndex < 4 ; $answerIndex ++)
		{
			$answerCue = addAnswer($userClient,$entry->id,$questions[$answerIndex],$quizUserEntry->id,"Q");
		}
		$res = submitQuiz($userClient,$quizUserEntry->id);
		if(is_null($res->score))
		{
			return success(__FUNCTION__);
		}
		else
		{						
			 return fail(__FUNCTION__." Score failure should be None while it is ".$res->score);
		}
	}
}
function Test3_ValidateScoreUponSubmitWithAdminKS($client)
{
	info('start ' .  __FUNCTION__);
	$entry=addEntry($client,__FUNCTION__);
	$quiz = createNewQuiz($client,$entry->id,null,null,null,null,null,KalturaNullableBoolean::FALSE_VALUE);
	$questions = array();
	for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
	{
		$questionCue = addQuestionsOnQuiz($client,$entry->id,"Question");
		$questions[$questionIndex]=$questionCue->id;
	}		 
	for ($userIndex=0;$userIndex<2;$userIndex++)
	{
		$userId = "UU".$userIndex;
		//Create user session
		$user = addKalturaUser($client,$userId);
		
		$quizUserEntry = addQuizUserEntry($client,$user->id,$entry->id);
		for ( $answerIndex=0 ; $answerIndex < 4 ; $answerIndex ++)
		{
			$answerCue = addAnswer($client,$entry->id,$questions[$answerIndex],$quizUserEntry->id,"Q");
		}
		$res = submitQuiz($client,$quizUserEntry->id);
		if(is_null($res->score))
		{
			return fail(__FUNCTION__." Should get score when using Admin KS ".$res->score);
			
		}
		else
		{						
			 return success(__FUNCTION__);
		}
	}
}
function Test4_ValidateScoreUponSubmit($client,$partnerId,$userSecret,$dc)
{
	info('start ' .  __FUNCTION__);
	$entry=addEntry($client,__FUNCTION__);
	$quiz = createNewQuiz($client,$entry->id,null,null,null,null,null,KalturaNullableBoolean::TRUE_VALUE);
	$questions = array();
	for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
	{
		$questionCue = addQuestionsOnQuiz($client,$entry->id,"Question".$questionIndex);
		$questions[$questionIndex]=$questionCue->id;
	}		 
	for ($userIndex=0;$userIndex<2;$userIndex++)
	{
		$userId = "UU".$userIndex;
		//Create user session
		$user = addKalturaUser($client,$userId);
		$userClient = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER,$userId);
		
		$quizUserEntry = addQuizUserEntry($userClient,$user->id,$entry->id);
		for ( $answerIndex=0 ; $answerIndex < 4 ; $answerIndex ++)
		{
			$answerCue = addAnswer($userClient,$entry->id,$questions[$answerIndex],$quizUserEntry->id,"Q");
		}
		$res = submitQuiz($userClient,$quizUserEntry->id);
		if(is_null($res->score))
		{
			 return fail(__FUNCTION__." Show get score since ShowGradeUponSubmit is true ".$res->score);
		}
		else
		{						
			 return success(__FUNCTION__);
		}
	}
}
function Test5_CheckAllowDownload($client)
{
	info('start ' .  __FUNCTION__);
	$entry=addEntry($client,__FUNCTION__.time());
	$quiz = createNewQuiz($client,$entry->id,null,null,null,null,KalturaNullableBoolean::TRUE_VALUE,null);
	$questions = array();
	for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
	{
		$questionCue = addQuestionsOnQuiz($client,$entry->id,"Question".$questionIndex);
		$questions[$questionIndex]=$questionCue->id;
	}		 

	$quizOutputType = KalturaQuizOutputType::PDF;
	$quizPlugin = KalturaQuizClientPlugin::get($client);
	$result = $quizPlugin->quiz->geturl($entry->id, $quizOutputType);
	if(is_null($result))
	{
		return fail(__FUNCTION__." Should get download URL ".$res->score);
	}
	//print_r($result);
	$entry=addEntry($client,__FUNCTION__);
	$quiz = createNewQuiz($client,$entry->id,null,null,null,null,KalturaNullableBoolean::FALSE_VALUE,null);
	$quizPlugin = KalturaQuizClientPlugin::get($client);
	$result = $quizPlugin->quiz->update($entry->id, $quiz);
	try
	{
	$result = $quizPlugin->quiz->geturl($entry->id, $quizOutputType);
	return fail(__FUNCTION__." Should not get download URL ".$result);
	}
	catch	(Exception $e)
	{
	return success(__FUNCTION__);
	}
}
function Test5_1_CheckAllowDownloadWithWidgetKs($client,$dc,$partnerId)
{
	info('start ' .  __FUNCTION__);
	$entry=addEntry($client,__FUNCTION__);
	$quiz = createNewQuiz($client,$entry->id,null,null,null,null,KalturaNullableBoolean::TRUE_VALUE,null);
	$questions = array();
	for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
	{
		$questionCue = addQuestionsOnQuiz($client,$entry->id,"Question".$questionIndex);
		$questions[$questionIndex]=$questionCue->id;
	}		 
	$wgClient = startWidgetSession($dc,$partnerId);	
	$quizOutputType = KalturaQuizOutputType::PDF;
	$quizPlugin = KalturaQuizClientPlugin::get($wgClient);
	$result = $quizPlugin->quiz->getUrl($entry->id, $quizOutputType);
	if(is_null($result))
	{
		return fail(__FUNCTION__." Should get download URL ".$res->score);
	}
	
	$quiz = new KalturaQuiz();
	$quiz->allowDownload = KalturaNullableBoolean::FALSE_VALUE;
	$quizPlugin = KalturaQuizClientPlugin::get($client);
	$result = $quizPlugin->quiz->update($entry->id, $quiz);
	$quizOutputType = KalturaQuizOutputType::PDF;
	$quizPlugin = KalturaQuizClientPlugin::get($wgClient);
	try
	{
		
	$result = $quizPlugin->quiz->geturl($entry->id, $quizOutputType);
	return fail(__FUNCTION__." Should not get download URL ".$result);
	}
	catch	(Exception $e)
	{
	return success(__FUNCTION__);
	}
}

function Test6_ValidateshowCorrectAfterSubmission($client,$partnerId,$userSecret,$dc,$wgClient)
{
	info('start ' .  __FUNCTION__);
	$entry=addEntry($client,__FUNCTION__);
	$quiz = createNewQuiz($client,$entry->id,KalturaNullableBoolean::FALSE_VALUE,KalturaNullableBoolean::FALSE_VALUE,null,KalturaNullableBoolean::FALSE_VALUE,null,KalturaNullableBoolean::TRUE_VALUE);
	$questions = array();
	for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
	{
		$questionCue = addQuestionsOnQuiz($client,$entry->id,"Question".$questionIndex);
		$questions[$questionIndex]=$questionCue->id;
	}		 
	$userId = "User".rand();
    //Create user session
	$user = addKalturaUser($client,$userId);
    $userClient = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER,$userId);
	$quizUserEntry = addQuizUserEntry($userClient,$user->id,$entry->id);
	$answerCue = addAnswer($userClient,$entry->id,$questions[0],$quizUserEntry->id,"Q");
	if(!is_null($answerCue->isCorrect))
	{
	return fail(__FUNCTION__." Should not get isCorrect value");
	}
	$quiz = new KalturaQuiz();
	$quiz->showCorrectAfterSubmission = KalturaNullableBoolean::FALSE_VALUE;
	$quizPlugin = KalturaQuizClientPlugin::get($client);
	$result = $quizPlugin->quiz->update($entry->id, $quiz);

	$answerCue = addAnswer($userClient,$entry->id,$questions[0],$quizUserEntry->id,"Q");
	if(!is_null($answerCue->isCorrect))
	{
	return fail(__FUNCTION__." Should not get isCorrect value current value is:".$answerCue->isCorrect );
	}
	
	return success(__FUNCTION__);
}

function Test6_1_ValidateSecurityAfterAnswer($client,$partnerId,$userSecret,$dc,$wgClient)
{
    info('start ' .  __FUNCTION__);
    $entry=addEntry($client,__FUNCTION__);
    $quiz = createNewQuiz($client,$entry->id,KalturaNullableBoolean::FALSE_VALUE,KalturaNullableBoolean::FALSE_VALUE,null,KalturaNullableBoolean::FALSE_VALUE,null,KalturaNullableBoolean::TRUE_VALUE);
    $questions = array();
    for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
    {
        $questionCue = addQuestionsOnQuiz($client,$entry->id,"Question".$questionIndex);
        $questions[$questionIndex]=$questionCue->id;
    }
    $userId = "User".rand();
    //Create user session
    $user = addKalturaUser($client,$userId);
    $userClient = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER,$userId);
    $quizUserEntry = addQuizUserEntry($userClient,$user->id,$entry->id);
    $answerCue = addAnswer($userClient,$entry->id,$questions[0],$quizUserEntry->id,"Q");
    if(!is_null($answerCue->isCorrect))
    {
        return fail(__FUNCTION__." Should not get isCorrect value");
    }

    if(!is_null($answerCue->correctAnswerKeys))
    {
        return fail(__FUNCTION__." Should not get correctAnswerKeys value");
    }

    return success(__FUNCTION__);
}

function Test6_2_ValidateSecurityAfterAnswer($client,$partnerId,$userSecret,$dc,$wgClient)
{
    info('start ' .  __FUNCTION__);
    $entry=addEntry($client,__FUNCTION__);
    $quiz = createNewQuiz($client,$entry->id,KalturaNullableBoolean::TRUE_VALUE,KalturaNullableBoolean::TRUE_VALUE,null,KalturaNullableBoolean::FALSE_VALUE,null,KalturaNullableBoolean::TRUE_VALUE);
    $questions = array();
    for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
    {
        $questionCue = addQuestionsOnQuiz($client,$entry->id,"Question".$questionIndex);
        $questions[$questionIndex]=$questionCue->id;
    }
    $userId = "User".rand();
    //Create user session
    $user = addKalturaUser($client,$userId);
    $userClient = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER,$userId);
    $quizUserEntry = addQuizUserEntry($userClient,$user->id,$entry->id);
    $answerCue = addAnswer($userClient,$entry->id,$questions[0],$quizUserEntry->id,"Q");
    if(is_null($answerCue->isCorrect))
    {
        return fail(__FUNCTION__." Should get isCorrect value");
    }

    if(is_null($answerCue->correctAnswerKeys))
    {
        return fail(__FUNCTION__." Should  get correctAnswerKeys value");
    }


    return success(__FUNCTION__);
}




function test7_GetUserPercentageReport($client)
{
	info('start ' .  __FUNCTION__);
	$entry=addEntry($client,__FUNCTION__);
	$quiz = createNewQuiz($client,$entry->id,null,null,null,null,null,null);
	$questions = array();
	for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
	{
		$questionCue = addQuestionsOnQuiz($client,$entry->id,"Question".$questionIndex);
		$questions[$questionIndex]=$questionCue->id;
	}		 
	$user = addKalturaUser($client,"UU".rand(1,1000));
	$quizUserEntry = addQuizUserEntry($client,$user->id,$entry->id);
	for ( $answerIndex=0 ; $answerIndex < 4 ; $answerIndex ++)
	{
		$answerCue = addAnswer($client,$entry->id,$questions[$answerIndex],$quizUserEntry->id,"Question2");
    }
	
	$reportType = KalturaReportType::QUIZ_USER_PERCENTAGE;
	$reportInputFilter = new KalturaEndUserReportInputFilter();
	$pager = new KalturaFilterPager();
	$order = null;
	$result = $client->report->gettable($reportType, $reportInputFilter, $pager, $order, $entry->id);
	if($result->data !=null)
	{
		return fail(__FUNCTION__." report should be empty since quiz was not submitted");
	}
	$res = submitQuiz($client,$quizUserEntry->id);
	$result = $client->report->gettable($reportType, $reportInputFilter, $pager, $order, $entry->id);
	$scores = explode(",", $result->data );
	if($scores[1] !=25)
	{
		return fail(__FUNCTION__." score is no calculated correct, should be 50 got - ".$scores[1]);
	}
	$user = addKalturaUser($client,"UU".rand(1,1000));
	$quizUserEntry = addQuizUserEntry($client,$user->id,$entry->id);
	for ( $answerIndex=0 ; $answerIndex < 4 ; $answerIndex ++)
	{
		$answerCue = addAnswer($client,$entry->id,$questions[$answerIndex],$quizUserEntry->id,"Question".$answerIndex);
	}
	$res = submitQuiz($client,$quizUserEntry->id);
	$result = $client->report->gettable($reportType, $reportInputFilter, $pager, $order, $entry->id);
	$result = explode(";", $result->data );
	$scores = explode(",", $result[1] );
	if($scores[1] !=100)
	{
		return fail(__FUNCTION__." score is no calculated correct, should be 100 got - ".$scores[1]);
	}

	return success(__FUNCTION__);
}

function test12_filterQuizUserEntry($client, $wgClient)
{
	$entry=addEntry($client,__FUNCTION__);
        $quiz = createNewQuiz($client,$entry->id,null,null,null,null,null,null);
        info('start ' .  __FUNCTION__);
        //Get list of all quiz user entry
        $filter = new KalturaQuizUserEntryFilter();
        $pager = null;
        $user = "UU".rand(1,1000);
        $user = addKalturaUser($client,"UU".rand(1,1000));

        addQuizUserEntry($client,$user->id,$entry->id);
        addQuizUserEntry($wgClient,0,$entry->id);
        //Get list of all quiz user entry without anonymous user
        $filter = new KalturaQuizUserEntryFilter();
        $filter->isAnonymous = KalturaNullableBoolean::FALSE_VALUE;
        $filter->entryIdIn = $entry->id;
        $result = $client->userEntry->listAction($filter, $pager);
        $items = $result->objects;
        foreach($items as $item)
        {
                if($item->userId=='0') {
                        return fail(__FUNCTION__.__LINE__." found anonymous user while should not" . print_r($item,true));
                }
        }

        //Get list of all quiz user entry with anonymous user
        $filter = new KalturaQuizUserEntryFilter();
        $filter->isAnonymous = KalturaNullableBoolean::TRUE_VALUE;
        $filter->entryIdIn = $entry->id;
        $result = $client->userEntry->listAction($filter, $pager);
        $items = $result->objects;

	$foundAnonymousUsres=0;
	foreach($items as $item)
	{
		if($item->userId=='0') {
			$foundAnonymousUsres--;
		}
	}
	if($foundAnonymousUsres==0)
	{
		return fail(__FUNCTION__.__LINE__." Did not found all anonymous users while it should, missing {$foundAnonymousUsres}" . print_r($items,true));
	}

	return success(__FUNCTION__);
}
function test9_addAnonimousUserQuiz($client,$dc,$partnerId,$widgetId)
{
	info('start ' .  __FUNCTION__);
	$entry=addEntry($client,__FUNCTION__);
	$quiz = createNewQuiz($client,$entry->id,null,null,null,null,KalturaNullableBoolean::TRUE_VALUE,null);
	$questions = array();
	for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
	{
		$questionCue = addQuestionsOnQuiz($client,$entry->id,"Question".$questionIndex);
		$questions[$questionIndex]=$questionCue->id;
	}
	$wgClient = startWidgetSession($dc,$partnerId,$widgetId);
	sleep(3);
	$quizUserEntry = addQuizUserEntry($wgClient,0,$entry->id);
	for ( $answerIndex=0 ; $answerIndex < 4 ; $answerIndex ++)
	{
		addAnswer($wgClient,$entry->id,$questions[$answerIndex],$quizUserEntry->id,"Q");
	}
	$res = submitQuiz($wgClient,$quizUserEntry->id);

	return success(__FUNCTION__);
}

function test10_anonmymousUserMultyRequest($client,$dc,$partnerId,$widgetId)

{
	info('start ' .  __FUNCTION__);
        $entry=addEntry($client,__FUNCTION__);
        $quiz = createNewQuiz($client,$entry->id,null,null,null,null,KalturaNullableBoolean::TRUE_VALUE,null);
        $questions = array();
        for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
        {
                $questionCue = addQuestionsOnQuiz($client,$entry->id,"Question".$questionIndex);
                $questions[$questionIndex]=$questionCue->id;
        }
        $wgClient = startWidgetSession($dc,$partnerId,$widgetId);
        $quizUserEntry = addQuizUserEntry($wgClient,0,$entry->id);
        for ( $answerIndex=0 ; $answerIndex < 4 ; $answerIndex ++)
        {
                addAnswer($wgClient,$entry->id,$questions[$answerIndex],$quizUserEntry->id,"Q");
        }
        $res = submitQuiz($wgClient,$quizUserEntry->id);

	$results = helper_getQuizUserEntryAnswerAndQuestion($wgClient,$entry->id,$quizUserEntry->id);
	if(count($results[0]->objects)!=4)
	{
		warning("Should have 4 questions in response, found - ".count($results[0]->objects));
		return fail(__FUNCTION__);
	}
	if(count($results[1]->objects)!=4)
	{
		warning("Should have 4 answers in response, found - ".count($results[1]->objects));
		return fail(__FUNCTION__);
	}

    return success(__FUNCTION__);
}


function helper_getQuizUserEntryAnswerAndQuestion($wgClient,$entryId,$quizUserEntryId)
{
	$wgClient->startMultiRequest();
	$request1filter = new KalturaQuestionCuePointFilter();
	$request1filter->cuePointTypeEqual = KalturaCuePointType::QUIZ_QUESTION;
	$request1filter->entryIdEqual = $entryId;
	$request1pager = new KalturaFilterPager();
	$cuepointPlugin = KalturaCuepointClientPlugin::get($wgClient);
	$cuepointPlugin->cuePoint->listAction($request1filter, $request1pager);
	$request2filter = new KalturaAnswerCuePointFilter();
	$request2pager = null;
	$request2filter->entryIdEqual =$entryId;
	$request2filter->cuePointTypeEqual = KalturaCuePointType::QUIZ_ANSWER;
	$request2filter->quizUserEntryIdEqual = $quizUserEntryId;
	$cuepointPlugin = KalturaCuepointClientPlugin::get($wgClient);
	$cuepointPlugin->cuePoint->listAction($request2filter, $request2pager);
	$results = $wgClient->doMultiRequest();
	return $results;
}


function test11_anonmymousUsersMultyQuiz($client,$dc,$partnerId,$widgetId)

{
	info('start ' .  __FUNCTION__);
	$entry=addEntry($client,__FUNCTION__);
	$quiz = createNewQuiz($client,$entry->id,null,null,null,null,KalturaNullableBoolean::TRUE_VALUE,null);
	$questions = array();
	for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
	{
		$questionCue = addQuestionsOnQuiz($client,$entry->id,"Question".$questionIndex);
		$questions[$questionIndex]=$questionCue->id;
	}
	$wgClient = startWidgetSession($dc,$partnerId,$widgetId);

	for ($i=1;$i<5;$i++)
	{
		$quizUserEntry = addQuizUserEntry($wgClient,0,$entry->id);
		info("Anonymous user - {$i} taking quiz id {$quizUserEntry->id}");
		$results = helper_getQuizUserEntryAnswerAndQuestion($wgClient, $entry->id,$quizUserEntry->id);
		$numQuestions = count($results[0]->objects);
		$numAnswers = count($results[1]->objects);
		if ($numQuestions != 4 || $numAnswers!= 0)
		{
			warning("Should have 4 questions and 0 answers in response, found - questions: {$numQuestions} answers:{$numAnswers}");
			return fail(__FUNCTION__);
		}

		for ($answerIndex = 0; $answerIndex < 4; $answerIndex++) {
			addAnswer($wgClient, $entry->id, $questions[$answerIndex], $quizUserEntry->id, "Q");
		}
		$res = submitQuiz($wgClient, $quizUserEntry->id);

		$results = helper_getQuizUserEntryAnswerAndQuestion($wgClient, $entry->id,$quizUserEntry->id);
		//Validate that there are questions and answers on the response

		$numQuestions = count($results[0]->objects);
		$numAnswers = count($results[1]->objects);
		if ($numQuestions != 4 || $numAnswers!= 4)
		{
			warning("Should have 4 questions and 4 answers in response, found - questions: {$numQuestions} answers:{$numAnswers}");
			return fail(__FUNCTION__);
		}
	}

	return success(__FUNCTION__);
}

function test11_dontGetWithoutQuizUserEntryId($client,$dc,$partnerId)
{
	info('start ' .  __FUNCTION__);
	$entry = addEntry($client, __FUNCTION__);
	$quiz = createNewQuiz($client, $entry->id, null, null, null, null, KalturaNullableBoolean::TRUE_VALUE, null);
	$questions = array();
	for ($questionIndex = 0; $questionIndex < 4; $questionIndex++)
	{
		$questionCue = addQuestionsOnQuiz($client, $entry->id, "Question" . $questionIndex);
		$questions[$questionIndex] = $questionCue->id;
	}
	$wgClient = startWidgetSession($dc, $partnerId);
	$quizUserEntry = addQuizUserEntry($wgClient, 0, $entry->id);
	for ($answerIndex = 0; $answerIndex < 4; $answerIndex++)
	{
		addAnswer($wgClient, $entry->id, $questions[$answerIndex], $quizUserEntry->id, "Q");
	}
	$res = submitQuiz($wgClient, $quizUserEntry->id);

	$request2filter = new KalturaAnswerCuePointFilter();
	$request2pager = null;
	$request2filter->entryIdEqual = $entry->id;
	$request2filter->cuePointTypeEqual = KalturaCuePointType::QUIZ_ANSWER;
	$cuepointPlugin = KalturaCuepointClientPlugin::get($wgClient);
	$results = $cuepointPlugin->cuePoint->listAction($request2filter, $request2pager);
	if (count($results->objects) != 0)
	{
		warning("Should have 0 answers in response, found - " . count($results->objects));
		return fail(__FUNCTION__);
	}

	return success(__FUNCTION__);
}


function test13_createTwoUserEntriesWithForSameUser($client,$dc,$partnerId)
{
	info('start ' .  __FUNCTION__);
	$entry = addEntry($client, __FUNCTION__);
	$quiz = createNewQuiz($client, $entry->id, null, null, null, null, KalturaNullableBoolean::TRUE_VALUE, null);

	$user = addKalturaUser($client,"UU1");
	$quizUserEntry1 = addQuizUserEntry($client,$user->id,$entry->id);
	try
	{
		sleep(5);
		$quizUserEntry2 = addQuizUserEntry($client, $user->id, $entry->id);
	}
	catch (Exception $e)
	{
		return success(__FUNCTION__);
	}
	info("Was able to create two user entries with the same user");
	return fail(__FUNCTION__);
}

function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$widgetId = create_widget($client,"IVQ_WIDGET_SESSION_ROLE" );
    $widgetId2 = create_widget($client );
    $wgClient = startWidgetSession($dc,$partnerId,$widgetId2);
	info("New widget ID {$widgetId}");
	$ret = Test1_Basicflow($client);
	$ret += Test2_ValidateNoScoreUponSubmit($client,$partnerId,$userSecret,$dc);
	$ret += Test3_ValidateScoreUponSubmitWithAdminKS($client);
	$ret += Test4_ValidateScoreUponSubmit($client,$partnerId,$userSecret,$dc);
	$ret += Test5_CheckAllowDownload($client);
	$ret += Test5_1_CheckAllowDownloadWithWidgetKs($client,$dc,$partnerId);
	$ret += Test6_ValidateshowCorrectAfterSubmission($client,$partnerId,$userSecret,$dc,$wgClient);
    $ret += Test6_1_ValidateSecurityAfterAnswer($client,$partnerId,$userSecret,$dc,$wgClient);
    $ret += Test6_2_ValidateSecurityAfterAnswer($client,$partnerId,$userSecret,$dc,$wgClient);
	$ret += test7_GetUserPercentageReport($client);
	$ret += test9_addAnonimousUserQuiz($client,$dc,$partnerId,$widgetId);
	$ret += test10_anonmymousUserMultyRequest($client,$dc,$partnerId,$widgetId);
	$ret += test11_anonmymousUsersMultyQuiz($client,$dc,$partnerId,$widgetId);
	$ret += test12_filterQuizUserEntry($client, $wgClient);
	$ret += test13_createTwoUserEntriesWithForSameUser($client, $dc, $partnerId);
	return ($ret);
}

goMain();



