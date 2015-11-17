<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
function printUsage()
{
    print ("\n\rUsage: " .$GLOBALS['argv'][0] . " <DC URL> 	<parnter id> <admin secret>");
    print ("\n\r for adding quiz.\r\n");
}
//start session and setting KS function
class bcolors
{
    const OKBLUE = "\033[94m";
    const OKGREEN = "\033[92m";
    const WARNING = "\033[93m";
    const FAIL = "\033[91m";
    const ENDC = "\033[0m";
    const BOLD = "\033[1m";
    const UNDERLINE = "\033[4m";
}
function success($msg)
{
    print("\n".bcolors::OKGREEN.$msg." OK!".bcolors::ENDC);
    return 0;
}
function fail($msg)
{
    print("\n".bcolors::FAIL.$msg." FAIL!".bcolors::ENDC);
    return -1;
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
		shout("Problem starting session with message: [$msg]\n");
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

function createNewQuiz($client,
                       $entryId,
                       $showResultOnAnswer,
                       $showCorrectKeyOnAnswer,
                       $allowAnswerUpdate,
                       $showCorrectAfterSubmission,
                       $allowDownload,
                       $showGradeAfterSubmission)
{
  $quiz                               = new KalturaQuiz();
  $quiz->showResultOnAnswer           = $showResultOnAnswer;
  $quiz->showCorrectKeyOnAnswer       = $showCorrectKeyOnAnswer;
  $quiz->allowAnswerUpdate            = $allowAnswerUpdate;
  $quiz->showCorrectAfterSubmission   = $showCorrectAfterSubmission;
  $quiz->allowDownload                = $allowDownload;
  $quiz->showGradeAfterSubmission     = $showGradeAfterSubmission;
  $quizPlugin                         = KalturaQuizClientPlugin::get($client);
  $result                             = $quizPlugin->quiz->add($entryId, $quiz);
  return $result;
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

function addQuizUserEntry($client,$userId,$quizEntryId)
{
  $userEntry           = new KalturaQuizUserEntry();
  $userEntry->entryId  = $quizEntryId;
  $userEntry->userId   = $userId;
  $result              = $client->userEntry->add($userEntry);
  //print ("\nAdd UserEntry ID:".$result->id);
  return $result;
}

function addQuestionsOnQuiz($client,$QuizEntryId,$str)
{
  $cuePoint                               = new KalturaQuestionCuePoint();
  $cuepointPlugin                         = KalturaCuepointClientPlugin::get($client);
  $cuePoint->hint                         = 'h'+$str;
  $cuePoint->question                     = 'q'+$str;
  $cuePoint->explanation                  = 'r'+$str;
  $cuePoint->entryId                      = $QuizEntryId;  
  $cuePoint->optionalAnswers              = array();
  $cuePoint->optionalAnswers[0]           = new KalturaOptionalAnswer();
  $cuePoint->optionalAnswers[0]->key      = $str;
  $cuePoint->optionalAnswers[0]->isCorrect= 1;
  $cuePoint->optionalAnswers[1]           = new KalturaOptionalAnswer();
  $cuePoint->optionalAnswers[1]->key      = $str;
  $cuePoint->optionalAnswers[1]->isCorrect= 1;
  $cuePoint->hint                         = 'Hint';
  $cuePoint->question                     = 'What is my name?';
  $cuePoint->explanation                  = 'My Name';
  $cuepointPlugin                         = KalturaCuepointClientPlugin::get($client);
  try 
  {
    $result                               = $cuepointPlugin->cuePoint->add($cuePoint);
  }
  catch (Exception $e)
  {
    //print_r($e);
  }
  //print ("\nQuestion ID:".$result->id);

  return $result; 
}

function addAnswer($client,$quizEntryId,$questionId,$quizUserEntry,$answerTxt)
{
  $cuePoint                               = new KalturaAnswerCuePoint();
  $cuePoint->entryId                      = $quizEntryId;
  $cuePoint->parentId                     = $questionId;
  $cuePoint->quizUserEntryId              = $quizUserEntry;
  $cuepointPlugin                         = KalturaCuepointClientPlugin::get($client);
  $cuePoint->answerKey                    = $answerTxt; 
  $result                                 = $cuepointPlugin->cuePoint->add($cuePoint);
  //print (" , ".$result->id);
  return ($result);
}
function addEntry($client,$name)
{
  $entry                                  = new KalturaBaseEntry();
  $type                                   = KalturaEntryType::MEDIA_CLIP;
  $entry->name                            = $name;
  $result                                 = $client->baseEntry->add($entry, $type);
  print ("\nAdd entry ID:".$result->id);
  return $result;
}

function submitQuiz($client,$userEntryId)
{
  $result                                 = $client->userEntry->submitquiz($userEntryId);
  //print("\nSubmit Quiz:".$userEntryId);
  return $result;
}

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
  $entry=addEntry($client,__FUNCTION__);
  $quiz = createNewQuiz($client,$entry->id,null,null,null,null,null,null);
  $questions = array();
  for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
  {
      $questionCue = addQuestionsOnQuiz($client,$entry->id,"Q");
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
  
  success(__FUNCTION__);
  
  return 0;
}
function Test2_ValidateNoScoreUponSubmit($client,$partnerId,$userSecret,$dc)
{
  $entry=addEntry($client,__FUNCTION__);
  $quiz = createNewQuiz($client,$entry->id,null,null,null,null,null,KalturaNullableBoolean::FALSE_VALUE);
  $questions = array();
  for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
  {
      $questionCue = addQuestionsOnQuiz($client,$entry->id,"Q");
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
  $entry=addEntry($client,__FUNCTION__);
  $quiz = createNewQuiz($client,$entry->id,null,null,null,null,null,KalturaNullableBoolean::FALSE_VALUE);
  $questions = array();
  for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
  {
      $questionCue = addQuestionsOnQuiz($client,$entry->id,"Q");
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
  $entry=addEntry($client,__FUNCTION__);
  $quiz = createNewQuiz($client,$entry->id,null,null,null,null,null,KalturaNullableBoolean::TRUE_VALUE);
  $questions = array();
  for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
  {
      $questionCue = addQuestionsOnQuiz($client,$entry->id,"Q");
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
  $entry=addEntry($client,__FUNCTION__);
  $quiz = createNewQuiz($client,$entry->id,null,null,null,null,KalturaNullableBoolean::TRUE_VALUE,null);
  $questions = array();
  for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
  {
      $questionCue = addQuestionsOnQuiz($client,$entry->id,"Q");
      $questions[$questionIndex]=$questionCue->id;
  }     

  $quizOutputType = KalturaQuizOutputType::PDF;
  $quizPlugin = KalturaQuizClientPlugin::get($client);
  $result = $quizPlugin->quiz->geturl($entry->id, $quizOutputType);
  if(is_null($result))
  {
      fail(__FUNCTION__." Should get download URL ".$res->score);
  }
  
  $quiz = new KalturaQuiz();
  $quiz->allowDownload = KalturaNullableBoolean::FALSE_VALUE;
  $quizPlugin = KalturaQuizClientPlugin::get($client);
  $result = $quizPlugin->quiz->update($entry->id, $quiz);
  try
  {
    $result = $quizPlugin->quiz->geturl($entry->id, $quizOutputType);
    return fail(__FUNCTION__." Should not get download URL ".$result);
  }
  catch  (Exception $e)
  {
    return success(__FUNCTION__);
  }
}
function Test5_1_CheckAllowDownloadWithWidgetKs($client,$dc,$partnerId)
{
  $entry=addEntry($client,__FUNCTION__);
  $quiz = createNewQuiz($client,$entry->id,null,null,null,null,KalturaNullableBoolean::TRUE_VALUE,null);
  $questions = array();
  for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
  {
      $questionCue = addQuestionsOnQuiz($client,$entry->id,"Q");
      $questions[$questionIndex]=$questionCue->id;
  }     
  $wgClient = startWidgetSession($dc,$partnerId);  
  $quizOutputType = KalturaQuizOutputType::PDF;
  $quizPlugin = KalturaQuizClientPlugin::get($wgClient);
  $result = $quizPlugin->quiz->getUrl($entry->id, $quizOutputType);
  if(is_null($result))
  {
      fail(__FUNCTION__." Should get download URL ".$res->score);
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
  catch  (Exception $e)
  {
    return success(__FUNCTION__);
  }
}

function Test6_ValidateshowCorrectAfterSubmission($client)
{
  $entry=addEntry($client,__FUNCTION__);
  $quiz = createNewQuiz($client,$entry->id,KalturaNullableBoolean::FALSE_VALUE,KalturaNullableBoolean::FALSE_VALUE,null,KalturaNullableBoolean::FALSE_VALUE,null,KalturaNullableBoolean::TRUE_VALUE);
  $questions = array();
  for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
  {
      $questionCue = addQuestionsOnQuiz($client,$entry->id,"Q");
      $questions[$questionIndex]=$questionCue->id;
  }     
  $userId = "User".rand();
  //Create user session
  $user = addKalturaUser($client,$userId);
  $quizUserEntry = addQuizUserEntry($client,$user->id,$entry->id);
  $answerCue = addAnswer($client,$entry->id,$questions[0],$quizUserEntry->id,"Q");
  if(is_null($answerCue->isCorrect))
  {
    return fail(__FUNCTION__." Should get isCorrect value");
  }
  $quiz = new KalturaQuiz();
  $quiz->showCorrectAfterSubmission = KalturaNullableBoolean::FALSE_VALUE;
  $quizPlugin = KalturaQuizClientPlugin::get($client);
  $result = $quizPlugin->quiz->update($entry->id, $quiz);
  $answerCue = addAnswer($client,$entry->id,$questions[0],$quizUserEntry->id,"Q");
  if(!is_null($answerCue->isCorrect))
  {
    return fail(__FUNCTION__." Should not get isCorrect value current value is:".$answerCue->isCorrect );
  }
  
  return success(__FUNCTION__);
}

function test7_GetUserPercentageReport($client)
{
    $entry=addEntry($client,__FUNCTION__);
    $quiz = createNewQuiz($client,$entry->id,null,null,null,null,null,null);
    $questions = array();
    for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
    {
      $questionCue = addQuestionsOnQuiz($client,$entry->id,"Q");
      $questions[$questionIndex]=$questionCue->id;
    }     
    $user = addKalturaUser($client,"UU".rand(1,1000));
    $quizUserEntry = addQuizUserEntry($client,$user->id,$entry->id);
    for ( $answerIndex=0 ; $answerIndex < 2 ; $answerIndex ++)
    {
      $answerCue = addAnswer($client,$entry->id,$questions[$answerIndex],$quizUserEntry->id,"Q");
    }
    
    $reportType = KalturaReportType::QUIZ_USER_PERCENTAGE;
    $reportInputFilter = new KalturaEndUserReportInputFilter();
    $pager = new KalturaFilterPager();
    $order = null;
    $result = $client->report->gettable($reportType, $reportInputFilter, $pager, $order, $entry->id);
    if($result->data !=null)
    {
        fail(__FUNCTION__." report should be empty since quiz was not submitted");
    }
    $res = submitQuiz($client,$quizUserEntry->id);
    $result = $client->report->gettable($reportType, $reportInputFilter, $pager, $order, $entry->id);
    $scores = explode(",", $result->data );
    if($scores[1] !=50)
    {
        fail(__FUNCTION__." score is no calculated correct, should be 50 got - ".$scores[1]);
    }


    $user = addKalturaUser($client,"UU".rand(1,1000));
    $quizUserEntry = addQuizUserEntry($client,$user->id,$entry->id);
    for ( $answerIndex=0 ; $answerIndex < 4 ; $answerIndex ++)
    {
      $answerCue = addAnswer($client,$entry->id,$questions[$answerIndex],$quizUserEntry->id,"Q");
    }
    $res = submitQuiz($client,$quizUserEntry->id);
    $result = $client->report->gettable($reportType, $reportInputFilter, $pager, $order, $entry->id);
    $result = explode(";", $result->data );
    $scores = explode(",", $result[1] );
    if($scores[1] !=100)
    {
        fail(__FUNCTION__." score is no calculated correct, should be 100 got - ".$scores[1]);
    }

    return success(__FUNCTION__);
}


function mainStory($dc,$partnerId,$adminSecret,$userSecret)
{
  $client = startKalturaSession($partnerId,$adminSecret,$dc); 
  Test1_Basicflow($client);
  Test2_ValidateNoScoreUponSubmit($client,$partnerId,$userSecret,$dc);
  Test3_ValidateScoreUponSubmitWithAdminKS($client);
  Test4_ValidateScoreUponSubmit($client,$partnerId,$userSecret,$dc);
  Test5_CheckAllowDownload($client);
  Test5_1_CheckAllowDownloadWithWidgetKs($client,$dc,$partnerId);
  Test6_ValidateshowCorrectAfterSubmission($client);
  test7_GetUserPercentageReport($client);
  // report1($client,$quizUserEntry->id);
  print ("\n");
  
}



if ($argc!=5 )
{
    //printUsage();
	exit (1);
}

$dcUrl 			= 	$argv[1];
$partnerId 		= 	$argv[2];
$adminSecret	= 	$argv[3];
$userSecret	    = 	$argv[4];
mainStory($dcUrl,$partnerId,$adminSecret,$userSecret);

exit(0);



