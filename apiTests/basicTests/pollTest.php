<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');

function getPollId($client)
{
	return $client->poll->add();
}

function validatePollResultStructure($votes, $expectedVoters, $expectedPollId, $expectedCounters, $funcName)
{
	if (!$votes)
		return fail("Poll get votes Failed ");
	$votes = json_decode($votes);
	if (!$votes)
		return fail("Failed to parse returned results");
	if (!isset($votes->pollId))
		return fail("Poll id did not exist in results");
	if($votes->pollId !== $expectedPollId )
		return fail("Poll id ".$votes['pollId']." is not the same as expected ".$expectedPollId);
	if (!array_key_exists('numVoters', $votes))
		return fail("Num Voters did not exist in results");
	if (!$votes->numVoters === $expectedVoters)
		return fail("Num Voters ".$votes->numVoters." is different from expected ".$expectedVoters);
	if (!array_key_exists('answerCounters', $votes))
		return fail("No answer counters in returned structure ");
	foreach ($votes->answerCounters as $ansId => $counter)
	{
		if (!array_key_exists($ansId, $expectedCounters)){
			return fail("Found an unexpected answer id (".$ansId.") in the answer counters ");
		}
		if ($expectedCounters[$ansId] !== $counter)
		{
			return fail("Answer counter ".$counter." did not match expected counter ".$expectedCounters[$ansId]);
		}

	}
	return success($funcName);
}

function Test1_PollAdd($client)
{
    info('start ' .  __FUNCTION__);
	$result = $client->poll->add("SINGLE_ANONYMOUS");
	if (!strpos($result, 'SINGLE_ANONYMOUS') === 0)
		return fail("Poll Add DEFAULT Failed ");
	$result = $client->poll->add('SINGLE_ANONYMOUS');
	if (!strpos($result, 'SINGLE_ANONYMOUS') === 0)
		return fail("Poll Add SINGLE_ANONYMOUS Failed ");

	$result = $client->poll->add('SINGLE_RESTRICT');
	if (!strpos($result, 'SINGLE_RESTRICT') === 0)
		return fail("Poll Add SINGLE_RESTRICT Failed ");

	$result = $client->poll->add('MULTI_ANONYMOUS');
	if (!strpos($result, 'MULTI_ANONYMOUS') === 0)
		return fail("Poll Add MULTI_ANONYMOUS Failed ");

	$result = $client->poll->add('MULTI_RESTRICT');
	if (!strpos($result, 'MULTI_RESTRICT') === 0)
		return fail("Poll Add MULTI_RESTRICT Failed ");
	return success(__FUNCTION__);
}



function Test2_FullPollTestSingularAnsSingleUserOneVote($client)
{
    info('start ' .  __FUNCTION__);
	$pollId = $client->poll->add();
	$ret = $client->poll->vote(strval($pollId), "myUser1", "1");
	$votes = $client->poll->getVotes($pollId, "1");
	return validatePollResultStructure($votes, 3, $pollId, array(1 => 1), __FUNCTION__);
}

function Test3_FullPollTestSingularAnsMultiUserOneVote($client)
{
    info('start ' .  __FUNCTION__);
	$pollId = $client->poll->add();
	$client->poll->vote(strval($pollId), "myUser1", "1");
	$client->poll->vote(strval($pollId), "myUser2", "1");
	$client->poll->vote(strval($pollId), "myUser3", "1");
	$votes = $client->poll->getVotes($pollId, "1");
	return validatePollResultStructure($votes, 1, $pollId, array(1 => 3), __FUNCTION__);
}

function Test4_FullPollTestSingularAnsMultiUserSeveralVotes($client)
{
    info('start ' .  __FUNCTION__);
	$pollId = $client->poll->add();
	$client->poll->vote(strval($pollId), "myUser1", "1");
	$client->poll->vote(strval($pollId), "myUser2", "2");
	$client->poll->vote(strval($pollId), "myUser3", "3");
	$client->poll->vote(strval($pollId), "myUser4", "2");
	$client->poll->vote(strval($pollId), "myUser5", "1");
	$client->poll->vote(strval($pollId), "myUser6", "1");
	$votes = $client->poll->getVotes($pollId, "1,2,3");
	return validatePollResultStructure($votes, 6, $pollId, array(1 => 3, 2=>2, 3=>1), __FUNCTION__);
}

function Test5_FullPollTestSingularAnsMultiUserRevote($client)
{
    info('start ' .  __FUNCTION__);
	$pollId = $client->poll->add();
	$client->poll->vote(strval($pollId), "myUser1", "1");
	$client->poll->vote(strval($pollId), "myUser2", "2");
	$client->poll->vote(strval($pollId), "myUser3", "3");
	$client->poll->vote(strval($pollId), "myUser2", "1");
	$client->poll->vote(strval($pollId), "myUser3", "1");
	$votes = $client->poll->getVotes($pollId, "1,2,3");
    return validatePollResultStructure($votes, 3, $pollId, array(1 => 3, 2=>0, 3=>0), __FUNCTION__);
}

function Test6_FullPollTestMultiAnsMultiUserRevote($client,$userSecret,$dc,$partnerId)
{
    info('start ' .  __FUNCTION__);
	$pollId = $client->poll->add("MULTI_ANONYMOUS");
    $wgClient = startWidgetSession($dc,$partnerId,"_".$partnerId);
    $wgClient->poll->vote(strval($pollId), "myUser1", "1,2");
    $wgClient->poll->vote(strval($pollId), "myUser2", "2,4");
    $wgClient->poll->vote(strval($pollId), "myUser3", "3,1");
    $wgClient->poll->vote(strval($pollId), "myUser2", "1,4");
    $wgClient->poll->vote(strval($pollId), "myUser3", "1");
	$votes = $client->poll->getVotes($pollId, "1,2,3,4");
	return validatePollResultStructure($votes, 3, $pollId, array(1 => 3, 2=>1, 3=>0, 4=>1), __FUNCTION__);
}


function Test7_FullPollTestMultiAnsMultiUserRevoteWithZeroes($client)
{
    info('start ' .  __FUNCTION__);
	$pollId = $client->poll->add("MULTI_ANONYMOUS");
    $client->poll->vote(strval($pollId), "myUser1", "0,1,2");
	$client->poll->vote(strval($pollId), "myUser2", "2,4");
	$client->poll->vote(strval($pollId), "myUser3", "3,1,0");
	$client->poll->vote(strval($pollId), "myUser2", "1,4");
	$client->poll->vote(strval($pollId), "myUser3", "1,0");
	$votes = $client->poll->getVotes($pollId, "0,1,2,3,4");
	return validatePollResultStructure($votes, 3, $pollId, array(0=> 2, 1 => 3, 2=>1, 3=>0, 4=>1), __FUNCTION__);
}

function Test8_FullPollTestMediumScale($client)
{
    info('start ' .  __FUNCTION__);
	$max=51;
	$pollId = $client->poll->add("MULTI_ANONYMOUS");
	for ($index = 0 ; $index < $max ; $index++)
	{
        $client->poll->vote(strval($pollId), "myUser".$index, "0,1,2");
	}

	$revote=27;
	for ($index = 0 ; $index < $revote ; $index++)
	{
		$client->poll->vote(strval($pollId), "myUser".$index, "1,3");
	}
	$votes = $client->poll->getVotes($pollId, "0,1,2,3,4");
	return validatePollResultStructure($votes, $max, $pollId, array( 0=>$max-$revote, 1=>$max, 2=>$max-$revote, 3=>$revote, 4=>0), __FUNCTION__);

}


function Test9_FullPollTestSingularAnsSingleUserOneVote_withKs($client,$userSecret,$dc,$partnerId)
{
    info('start ' .  __FUNCTION__);
    $pollId = $client->poll->add("SINGLE_RESTRICT");
    //KS Based User
    $userId = "User".rand();
    $user = addKalturaUser($client,$userId);
    $userClient = startKalturaSession($partnerId,$userSecret,$dc,KalturaSessionType::USER,$userId);
    $userClient->poll->vote(strval($pollId), "myUser1", "1");
    $votes = $client->poll->getVotes($pollId, "1");
    return validatePollResultStructure($votes, 3, $pollId, array(1 => 1), __FUNCTION__);
}

function Test10_FullPollTestSingularAnsSingleUserOneVote_withKs($client,$userSecret,$dc,$partnerId)
{
    info('start ' .  __FUNCTION__);
    $pollId = $client->poll->add("SINGLE_RESTRICT");
    //KS Based User
    $wgClient = startWidgetSession($dc,$partnerId,"_".$partnerId);
    $wgClient->poll->vote(strval($pollId), "myUser1", "1");
    $votes = $client->poll->getVotes($pollId, "1");
    return validatePollResultStructure($votes, 3, $pollId, array(1 => 1), __FUNCTION__);
}


function main($dc,$partnerId,$adminSecret,$userSecret)
{
    $client = startKalturaSession($partnerId,$adminSecret,$dc);
    $ret  = Test1_PollAdd($client);
    $ret += Test2_FullPollTestSingularAnsSingleUserOneVote($client);
    $ret += Test3_FullPollTestSingularAnsMultiUserOneVote($client);
    $ret += Test4_FullPollTestSingularAnsMultiUserSeveralVotes($client);
    $ret += Test5_FullPollTestSingularAnsMultiUserRevote($client);
    $ret += Test6_FullPollTestMultiAnsMultiUserRevote($client,$userSecret,$dc,$partnerId);
    $ret += Test7_FullPollTestMultiAnsMultiUserRevoteWithZeroes($client);
    $ret += Test8_FullPollTestMediumScale($client);
    $ret += Test9_FullPollTestSingularAnsSingleUserOneVote_withKs($client,$userSecret,$dc,$partnerId);
    $ret += Test10_FullPollTestSingularAnsSingleUserOneVote_withKs($client,$userSecret,$dc,$partnerId);

    return ($ret);
}

goMain();
