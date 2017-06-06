<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');

function getPollId($client)
{
	return $client->poll->add();
}

function comparePollVote($string, $arrayString)
{
	$explodedArray = explode(',', $string);
	$diffArray = array_diff(json_decode($arrayString), $explodedArray);
	if (count($diffArray) !==0 )
		return false;
	return true;
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
	$result = $client->poll->add();
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
	$pollId = $client->poll->add();
	$client->poll->vote($pollId, "myUser1", "1");
	$votes = $client->poll->getVotes($pollId, "1");
	return validatePollResultStructure($votes, 3, $pollId, array(1 => 1), __FUNCTION__);
}

function Test3_FullPollTestSingularAnsMultiUserOneVote($client)
{
	$pollId = $client->poll->add();
	$client->poll->vote($pollId, "myUser1", "1");
	$client->poll->vote($pollId, "myUser2", "1");
	$client->poll->vote($pollId, "myUser3", "1");
	$votes = $client->poll->getVotes($pollId, "1");
	return validatePollResultStructure($votes, 1, $pollId, array(1 => 3), __FUNCTION__);
}

function Test4_FullPollTestSingularAnsMultiUserSeveralVotes($client)
{
	$pollId = $client->poll->add();
	$client->poll->vote($pollId, "myUser1", "1");
	$client->poll->vote($pollId, "myUser2", "2");
	$client->poll->vote($pollId, "myUser3", "3");
	$client->poll->vote($pollId, "myUser4", "2");
	$client->poll->vote($pollId, "myUser5", "1");
	$client->poll->vote($pollId, "myUser6", "1");
	$votes = $client->poll->getVotes($pollId, "1,2,3");
	return validatePollResultStructure($votes, 6, $pollId, array(1 => 3, 2=>2, 3=>1), __FUNCTION__);
}

function Test5_FullPollTestSingularAnsMultiUserRevote($client)
{
	$pollId = $client->poll->add();
	$client->poll->vote($pollId, "myUser1", "1");
	$client->poll->vote($pollId, "myUser2", "2");
	$client->poll->vote($pollId, "myUser3", "3");
	$client->poll->vote($pollId, "myUser2", "1");
	$client->poll->vote($pollId, "myUser3", "1");
	$votes = $client->poll->getVotes($pollId, "1,2,3");
	return validatePollResultStructure($votes, 3, $pollId, array(1 => 3, 2=>0, 3=>0), __FUNCTION__);
}

function Test6_FullPollTestMultiAnsMultiUserRevote($client)
{
	$pollId = $client->poll->add();
	$client->poll->vote($pollId, "myUser1", "1,2");
	$client->poll->vote($pollId, "myUser2", "2,4");
	$client->poll->vote($pollId, "myUser3", "3,1");
	$client->poll->vote($pollId, "myUser2", "1,4");
	$client->poll->vote($pollId, "myUser3", "1");
	$votes = $client->poll->getVotes($pollId, "1,2,3,4");
	return validatePollResultStructure($votes, 3, $pollId, array(1 => 3, 2=>1, 3=>0, 4=>1), __FUNCTION__);
}

function Test7_FullPollTestMultiAnsMultiUserRevoteWithZeroes($client)
{
	$pollId = $client->poll->add();
	$client->poll->vote($pollId, "myUser1", "0,1,2");
	$client->poll->vote($pollId, "myUser2", "2,4");
	$client->poll->vote($pollId, "myUser3", "3,1,0");
	$client->poll->vote($pollId, "myUser2", "1,4");
	$client->poll->vote($pollId, "myUser3", "1,0");
	$votes = $client->poll->getVotes($pollId, "0,1,2,3,4");
	return validatePollResultStructure($votes, 3, $pollId, array(0=> 2, 1 => 3, 2=>1, 3=>0, 4=>1), __FUNCTION__);
}

function Test8_FullPollTestMediumScale($client)
{
	$max=5000;
	$pollId = $client->poll->add();
	for ($index = 0 ; $index < $max ; $index++)
	{
		$client->poll->vote($pollId, "myUser".$index, "0,1,2");
	}

	$revote=751;
	for ($index = 0 ; $index < $revote ; $index++)
	{
		$client->poll->vote($pollId, "myUser".$index, "1,3");
	}
	$votes = $client->poll->getVotes($pollId, "0,1,2,3,4");
	return validatePollResultStructure($votes, $max, $pollId, array( 0=>$max-$revote, 1=>$max, 2=>$max-$revote, 3=>$revote, 4=>0), __FUNCTION__);
}

function Test9_GetEarlierVoteTest($client)
{
	$pollId = $client->poll->add();
	$client->poll->vote($pollId, "myUser1", "0,1,2");
	$userVote = $client->poll->getVote($pollId, "myUser1");
	if (!comparePollVote("0,1,2", $userVote))
		return fail("Get vote gave different result: $userVote than expected : 0,1,2 ");
	$client->poll->vote($pollId, "myUser1", "2,4");
	$client->poll->vote($pollId, "myUser1", "3,1,0");
	$userVote = $client->poll->getVote($pollId, "myUser1");
	if (!comparePollVote("3,1,0", $userVote))
		return fail("Get vote gave different result: $userVote than expected : 3,1,0 ");

	$client->poll->vote($pollId, "myUser2", "1");
	$userVote = $client->poll->getVote($pollId, "myUser2");
	if (!comparePollVote("1", $userVote))
		return fail("Get vote gave different result: $userVote than expected : 1 ");
	$client->poll->vote($pollId, "myUser2", "0");
	$userVote = $client->poll->getVote($pollId, "myUser2");
	if (!comparePollVote("0", $userVote))
		return fail("Get vote gave different result: $userVote than expected : 0 ");

	return success(__FUNCTION__);
}

function Test10_VoteForSameAnswer($client)
{
	$pollId = $client->poll->add();
	$client->poll->vote($pollId, "myUser1", "1,1,1,1,2,3,2,3,1,1,1,1,2,3");
	$votes = $client->poll->getVotes($pollId, "1");
	return validatePollResultStructure($votes, 1, $pollId, array(1 => 1, 2=>1, 3=>1), __FUNCTION__);
}



function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$ret  = Test1_PollAdd($client);
	$ret += Test2_FullPollTestSingularAnsSingleUserOneVote($client);
	$ret += Test3_FullPollTestSingularAnsMultiUserOneVote($client);
	$ret += Test4_FullPollTestSingularAnsMultiUserSeveralVotes($client);
	$ret += Test5_FullPollTestSingularAnsMultiUserRevote($client);
	$ret += Test6_FullPollTestMultiAnsMultiUserRevote($client);
	$ret += Test7_FullPollTestMultiAnsMultiUserRevoteWithZeroes($client);
	$ret += Test8_FullPollTestMediumScale($client);
	$ret += Test9_GetEarlierVoteTest($client);
	$ret += Test10_VoteForSameAnswer($client);

	return ($ret);
}

goMain();
