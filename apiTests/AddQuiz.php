<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
function printUsage()
{
    print ("\n\rUsage: " .$GLOBALS['argv'][0] . " <DC URL> 	<parnter id> <admin secret>");
    print ("\n\r for adding quiz.\r\n");
}
//start session and setting KS function
function startKalturaSession($partnerId,$secret,$destUrl) 
{
	try
	{
		$config = new KalturaConfiguration($partnerId);
		$config->serviceUrl = $destUrl;
		$type = KalturaSessionType::ADMIN;	
		$client = new KalturaClient($config);
		$result = $client->session->start($secret, null, $type, $partnerId, null, null);
		$client->setKs($result);
		
		print("Started session successfully with KS [$result]\n");
		return $client;
	}
	catch (KalturaException $e)
	{
		$msg = $e->getMessage();
		shout("Problem starting session with message: [$msg]\n");
		die("ERROR - cannot generate session with partner id [$partnerId] and secret [$secret]");
	}
}	

function createNewQuiz($client,$entryId)
{
  $quiz = new KalturaQuiz();
  $quizPlugin = KalturaQuizClientPlugin::get($client);
  $result = $quizPlugin->quiz->add($entryId, $quiz);
  return $result;
}

function addKalturaUser($client,$userId)
{
  
  $filter = new KalturaUserFilter();
  $filter->idEqual = $userId;
  $pager = null;
  $result = $client->user->listAction($filter, $pager);
  
  if($result->totalCount==0)
  {
    $user = new KalturaUser();
    $user->id = $userId;
    $user->type = KalturaUserType::USER;
    $user->screenName = $userId;
    $user->fullName = $userId;
    $user->email = $userId."@m.com";
    $result = $client->user->add($user);
  }
  else
  {
    $result = $result->objects[0];
  }
  
  print ("\nAdd User ID:".$result->id);
  return $result;
}

function addQuizUserEntry($client,$userId,$quizEntryId)
{
  $userEntry = new KalturaQuizUserEntry();
  $userEntry->entryId = $quizEntryId;
  $userEntry->userId = $userId;
  $result = $client->userEntry->add($userEntry);
  print ("\nAdd UserEntry ID:".$result->id);
  return $result;
}

function addQuestionsOnQuiz($client,$QuizEntryId,$str)
{
  $cuePoint = new KalturaQuestionCuePoint();
  $cuepointPlugin = KalturaCuepointClientPlugin::get($client);
  $cuePoint->hint = 'h'+$str;
  $cuePoint->question = 'q'+$str;
  $cuePoint->explanation = 'r'+$str;
  $cuePoint->entryId = $QuizEntryId;  
  try 
  {
    $result = $cuepointPlugin->cuePoint->add($cuePoint);
  }
  catch (Exception $e)
  {
    print_r($e);
  }
  
  print ("\nQuestion ID:".$result->id);
  
  return $result; 
}

function addAnswer($client,$quizEntryId,$questionId,$quizUserEntry,$answerTxt)
{
  $cuePoint = new KalturaAnswerCuePoint();
  $cuePoint->entryId = $quizEntryId;
  $cuePoint->parentId = $questionId;
  $cuePoint->quizUserEntryId = $quizUserEntry;
  $cuepointPlugin = KalturaCuepointClientPlugin::get($client);
  $result = $cuepointPlugin->cuePoint->add($cuePoint);
  print (" , ".$result->id);
  return ($result);
}
function addEntry($client)
{
  $entry = new KalturaBaseEntry();
  $type = KalturaEntryType::MEDIA_CLIP;
  $result = $client->baseEntry->add($entry, $type);
  print ("\nAdd entry ID:".$result->id);
  return $result;
}

function submitQuiz($client,$userEntryId)
{
  $result = $client->userEntry->submitquiz($userEntryId);
  print("\nSubmit Quiz:".$userEntryId);
}

function report1($client,$userEntryId)
{
  $reportType = KalturaReportType::QUIZ_USER_AGGREGATE_BY_QUESTION;
  $reportInputFilter = new KalturaReportInputFilter();
  $pager = new KalturaFilterPager();
  $order = null;
  $objectIds = $userEntryId;
  $result = $client->report->gettable($reportType, $reportInputFilter, $pager, $order, $objectIds);
  print ("QUIZ_USER_AGGREGATE_BY_QUESTION");
  print_r($result);
}

function mainStory($dc,$partnerId,$adminSecret)
{
  $client = startKalturaSession($partnerId,$adminSecret,$dc); 
  $res=addEntry($client);
  $entryId=$res->id;
  createNewQuiz($client,$entryId);
  $questions = array();
  for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
  {
      $questionCue = addQuestionsOnQuiz($client,$entryId,"Question".$questionIndex);
      $questions[$questionIndex]=$questionCue->id;
  }     
      
  for ( $questionIndex=0 ; $questionIndex < 4 ; $questionIndex ++)
  {
      for ($userIndex=0;$userIndex<10;$userIndex++)
      {
        $user = addKalturaUser($client,"UU".$userIndex);
        $quizUserEntry = addQuizUserEntry($client,$user->id,$entryId);
        for ( $answerIndex=0 ; $answerIndex < 4 ; $answerIndex ++)
        {
          $answerCue = addAnswer($client,$entryId,$questions[$questionIndex],$quizUserEntry->id,"Answer".$questionIndex.$answerIndex);
        }
        submitQuiz($client,$quizUserEntry->id);
      }
  }
  

  print("/nEntryId :".$entryId);
  report1($client,$quizUserEntry->id);
  
}

if ($argc!=4 )
{
    printUsage();
	exit (1);
}

$dcUrl 			= 	$argv[1];
$partnerId 		= 	$argv[2];
$adminSecret	= 	$argv[3];
mainStory($dcUrl,$partnerId,$adminSecret);

exit(0);



