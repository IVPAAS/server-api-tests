<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');

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
  $cuePoint->hint                         = 'hint '.$str;
  $cuePoint->question                     = 'question '.$str;
  $cuePoint->explanation                  = 'explanation '.$str;
  $cuePoint->entryId                      = $QuizEntryId;
  $cuePoint->startTime                    = time().microtime ();
  $cuePoint->optionalAnswers              = array();
  for($i=0;$i<4;$i++)
  {
      $optionalAnswer                        = new KalturaOptionalAnswer();
      $optionalAnswer->text                 = "Answer $i ".$str;
      $optionalAnswer->isCorrect            = 0;
      $optionalAnswer->key                  = $str;
      $cuePoint->optionalAnswers[]          = $optionalAnswer;
  }
	$cuePoint->optionalAnswers[3]->isCorrect= 1;	
//  $cuePoint->hint                         = 'Hint';
//  $cuePoint->question                     = 'What is my name?';
//  $cuePoint->explanation                  = 'My Name';
  $cuepointPlugin                         = KalturaCuepointClientPlugin::get($client);
  //try
  {
    $result                               = $cuepointPlugin->cuePoint->add($cuePoint);
  }
  //catch (Exception $e)
  {
    //pddEntry($client,__FUNCTION__);rint_r($e);
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

function submitQuiz($client,$userEntryId)
{
  $result                                 = $client->userEntry->submitquiz($userEntryId);
  //print("\nSubmit Quiz:".$userEntryId);
  return $result;
}
