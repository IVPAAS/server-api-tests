<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');

$FILE_NAME_MP4 = dirname ( __FILE__ ).'/../../resources/KalturaTestUpload.mp4';

function helper_validateEntryList( $client, $filter, $goodEntries, $badEntries)
{
	$totalCreated = array_merge($goodEntries, $badEntries);
	info("waiting for entries to be ready");
	foreach ( $totalCreated as $entryId)
	{
		waitForEntry($client, $entryId);
	}

	info("All entries are ready");
	$entriesList = $client->baseEntry->listAction($filter);
	$fail = false;
	$matchCount = 0;
	foreach ( $entriesList->objects as $entry )
	{
		if ( in_array($entry->id, $badEntries))
		{
			$fail = true;
			break;
		}

		if ( in_array($entry->id, $goodEntries))
			$matchCount++;

		info ("matching entry id: ". $entry->id . " name:" . $entry->name);
	}

	if ( $fail || $matchCount!=count($goodEntries) )
		return false;

	return true;

}

function Test1_BaseEntryList( $client, $keyWord )
{
	$goodEntries = array();
	$badEntries = array();

	//add entries
	$goodEntries[] = createEntryAndUploadContent($client,"test " . $keyWord, $GLOBALS['FILE_NAME_MP4']);
	$badEntries[] = createEntryAndUploadContent($client,"test no keyword", $GLOBALS['FILE_NAME_MP4']);

	$filter = new KalturaBaseEntryFilter();
	//entry metadata
	$filter->freeText = $keyWord;

	if ( !helper_validateEntryList($client, $filter, $goodEntries, $badEntries))
		return fail(__FUNCTION__);

	return success(__FUNCTION__);
}

function Test2_EntryTranscriptSearchFilter( $client, $keyWord )
{
	$goodEntries = array();
	$badEntries = array();

	//entries with transcript
	$goodEntries[] = createEntryWithTranscript( $client, '/../../resources/transcriptWithKeyword.txt', $GLOBALS['FILE_NAME_MP4']);
	$badEntries[] = createEntryWithTranscript( $client, '/../../resources/transcriptWithoutKeyword.txt', $GLOBALS['FILE_NAME_MP4']);

	$filter = new KalturaBaseEntryFilter();
	$transcriptSearchItem = new KalturaEntryTranscriptAssetSearchItem();
	$transcriptSearchItem->contentLike = $keyWord;

	$filter->advancedSearch = $transcriptSearchItem;

	if ( !helper_validateEntryList($client, $filter, $goodEntries, $badEntries))
		return fail(__FUNCTION__);

	return success(__FUNCTION__);
}

function Test3_EntryCaptionSearchFilter( $client, $keyWord )
{
	$goodEntries = array();
	$badEntries = array();

	//entries with captions
	$goodEntries[] = createEntryWithCaptions( $client, '/../../resources/KalturaTestCaptionWithKeyword.srt', $GLOBALS['FILE_NAME_MP4']);
	$badEntries[] = createEntryWithCaptions( $client, '/../../resources/KalturaTestCaption.srt', $GLOBALS['FILE_NAME_MP4']);

	$filter = new KalturaBaseEntryFilter();
	$captionsSearchItem = new KalturaEntryCaptionAssetSearchItem();
	$captionsSearchItem->contentLike = $keyWord;

	$filter->advancedSearch = $captionsSearchItem;

	if ( !helper_validateEntryList($client, $filter, $goodEntries, $badEntries))
		return fail(__FUNCTION__);

	return success(__FUNCTION__);
}

function Test4_referenceIdFilter( $client )
{
	$referenceIds = array( '9780133965803-9780133965803-2016-03-11-27-06-07-562135', ' with space ', 'abc', 'HelLo WorlD', 'TEST', '#specialChars!@#$%^&*()<>~_|\\/' );
	$goodEntries = array();
	$badEntries = array();

	foreach ( $referenceIds as $refId ) {
		$goodEntries[] = createEntryAndUploadContent( $client, "correctReferenceId", $GLOBALS['FILE_NAME_MP4'], $refId);
		$badEntries[] = createEntryAndUploadContent( $client, "wrongReferenceId", $GLOBALS['FILE_NAME_MP4'], $refId . 'aa' );
	}

	$filter = new KalturaBaseEntryFilter();
	$filter->referenceIdIn = implode( ",", $referenceIds);

	if ( !helper_validateEntryList($client, $filter, $goodEntries, $badEntries))
		return fail(__FUNCTION__);

	return success(__FUNCTION__);
}

function main($dc,$partnerId,$adminSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$keyWord = 'searchKeyWord';
	$ret = Test1_BaseEntryList($client, $keyWord);
	$ret  += Test2_EntryTranscriptSearchFilter($client, $keyWord);
	$ret  += Test3_EntryCaptionSearchFilter($client, $keyWord);
	$ret += Test4_referenceIdFilter($client);
	return ($ret);
}

goMain();
