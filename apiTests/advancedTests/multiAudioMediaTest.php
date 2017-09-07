<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');

$entryIdToDelete;
/**
 * 1. upload entry via xml bulk upload with 3 streams - russian , french, english
 * 2. wait for entry to be ready with all audio flavors
 * 3. upload replace entry via xml bulk upload with 3 streams - russian , french, english in different order in trackIndex
 * 4. verify that flavors were converted and now they are in the new order
 */
function multiAudioTest1($client)
{
	global $entryIdToDelete;

	$ret = 0;
	$mediaUrl = 'http://allinone-be.dev.kaltura.com/content/tests/multiAudioTest.mp4';
	$input = "<mrss xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:noNamespaceSchemaLocation=\"ingestion.xsd\">
		        <channel>
		                <item>
		                        <action>add</action>
		                        <type>1</type>
		                        <userId>example</userId>
		                        <name>TestingMultiAudioEntry</name>
		                        <description>All-Audios</description>
		                        <tags>
		                                <tag>Homepage</tag>
		                                <tag>Creative</tag>
		                        </tags>
		                        <categories>
		                                <category>examples>example1</category>
		                                <category>examples>example2</category>
		                        </categories>
		                        <conversionProfileId>47896</conversionProfileId>
		                        <media>
		                                <mediaType>1</mediaType>
		                        </media>
		                        <contentAssets>
		                                <content>
		                <urlContentResource url=\"$mediaUrl\"/>
		                <streams>
		                        <stream type=\"audio\" trackIndex=\"2\" language=\"rus\" label=\"Russian\"></stream>
		                        <stream type=\"audio\" trackIndex=\"1\" language=\"fre\" label=\"French\"></stream>
		                        <stream type=\"audio\" trackIndex=\"3\" language=\"eng\" label=\"English\"></stream>
		                </streams>
		                                </content>
		                        </contentAssets>
		                </item>
		        </channel>
			</mrss>";

	$createdEntry = addXmlBulkUpload($client, $input);
	$entryIdToDelete = $createdEntry;

	info("Waiting for entry $createdEntry to be ready");
	while(isEntryReady($client,$createdEntry)!=true)
	{
		sleep(2);
		print (".");
	}
	info("Entry $createdEntry is ready");

	$contextDataParams = new KalturaPlaybackContextOptions();
	$contextDataParams->mediaProtocol = 'http';
	$result = $client->baseEntry->getPlaybackContext($createdEntry, $contextDataParams);

	if (!$result)
	{
		return fail(__FUNCTION__." Command: getPlaybackContext for entry $createdEntry->id failed.");
	}

	$url = null;
	//get playmanifest url format
	foreach ($result->sources as $item)
	{
		if ($item->format == 'applehttp')
		{
			$url = $item->url;
			break;
		}
	}

	$command = 'curl -i "'.$url.'"';
	info("\n\r getting playmanifest info. executing the following request: $command");
	exec($command, $output, $result);
	if ($result != 0){
		return fail(__FUNCTION__." Command: $command failed.");
	}
	info(print_r($output,true));

	$streams = array();
	foreach ($output as $row)
	{
		if (0 === strpos($row, '#EXT-X-MEDIA:TYPE=AUDIO')){
			info("Found stream: $row");
			$streams[]= $row;
		}
	}

	if ( count($streams) !=3)
	{
		return fail(__FUNCTION__." Didnt match audio streams. expected [3] actual [ ".count($streams) ."]");
	}
	info("Found ". count($streams) . " as expected");

	if ( !strpos($streams[0], "LANGUAGE=\"fre\"") || !strpos($streams[1], "LANGUAGE=\"rus\"") || !strpos($streams[2], "LANGUAGE=\"eng\""))
	{
		return fail(__FUNCTION__." Original audio streams are not in the right order.");
	}

	//update reference id and use it in the replacement entry.
	$baseEntry = new KalturaBaseEntry();
	$refId = 'multiAudioTest'.rand(0,1000000);
	$baseEntry->referenceId = $refId;
	$result = $client->baseEntry->update($createdEntry, $baseEntry);

	if (!$result)
	{
		return fail(__FUNCTION__." Command: update for entry $createdEntry->id failed.");
	}

	$inputReplace ="<mrss xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:noNamespaceSchemaLocation=\"ingestion.xsd\">
        <channel>
                <item>
                        <type>1</type>
            <referenceId>$refId</referenceId>
                        <userId>example</userId>
                        <conversionProfileId>47896</conversionProfileId>
                        <media>
                                <mediaType>1</mediaType>
                        </media>
                        <contentAssets>
                        <action>replace</action>
                        <content>
                                <urlContentResource url=\"$mediaUrl\"/>
                                        <streams>
                                                        <stream type=\"audio\" trackIndex=\"3\" language=\"eng\" label=\"English\"></stream>
                                                        <stream type=\"audio\" trackIndex=\"1\" language=\"rus\" label=\"Russian\"></stream>
                                                        <stream type=\"audio\" trackIndex=\"2\" language=\"fre\" label=\"French\"></stream>
                                        </streams>
                                </content>
                        </contentAssets>
                </item>
      	  </channel>
		</mrss>";

	$tempRes = addXmlBulkUpload($client, $inputReplace);

	sleep(10);
	$originalEntry = $client->baseEntry->get($createdEntry, $baseEntry);
	$replacementEntry = $originalEntry->replacingEntryId;
	info("Waiting for replacement entry $replacementEntry to be ready");
	while(isEntryReady($client,$replacementEntry)!=true)
	{
		sleep(2);
		print (".");
	}
	info("Entry $replacementEntry is ready");
	sleep(60);

	$contextDataParams = new KalturaPlaybackContextOptions();
	$contextDataParams->mediaProtocol = 'http';
	$result = $client->baseEntry->getPlaybackContext($createdEntry, $contextDataParams);

	if (!$result)
	{
		return fail(__FUNCTION__." Command: getPlaybackContext for entry $createdEntry->id failed.");
	}

	$url = null;
	foreach ($result->sources as $item)
	{
		if ($item->format == 'applehttp')
		{
			$url = $item->url;
			break;
		}
	}

	$command = 'curl -i "'.$url.'"';
	info("\n\r getting playmanifest info. executing the following request: $command");
	exec($command, $output1, $result1);
	if ($result1 != 0){
		return fail(__FUNCTION__." Command: $command failed.");
	}
	info(print_r($output1,true));

	$streamsAfterReplacement = array();
	foreach ($output1 as $row)
	{
		if (0 === strpos($row, '#EXT-X-MEDIA:TYPE=AUDIO')){
			info("Found stream $row");
			$streamsAfterReplacement[]= $row;
		}
	}

	if ( count($streamsAfterReplacement) !=3)
	{
		return fail(__FUNCTION__." Didn't match audio streams. expected [3] actual [ ".count($streamsAfterReplacement) ."]");
	}

	info("Found ". count($streamsAfterReplacement) . " as expected");
	if ( !strpos($streamsAfterReplacement[1], "LANGUAGE=\"fre\"") || !strpos($streamsAfterReplacement[0], "LANGUAGE=\"rus\"") || !strpos($streamsAfterReplacement[2], "LANGUAGE=\"eng\""))
	{
		return fail(__FUNCTION__." Original audio streams are not in the right order.");
	}

	if ($ret != 0)
	{
		info("*************************************************");
		return fail(__FUNCTION__);
	}

	return success(__FUNCTION__ . ". \n\r finished successfully");
}

function addXmlBulkUpload($client, $input)
{
	$filename = tempnam(sys_get_temp_dir(), 'bulk.') . '.xml';
	file_put_contents($filename, $input);

	$bulkUploadData = new KalturaBulkUploadXmlJobData();
	$bulkUpload = $client->media->bulkUploadAdd($filename, $bulkUploadData);
	$logUrl = $client->bulkUpload->serveLog($bulkUpload->id);

	$command = 'curl -i "'.$logUrl.'"';
	print("\n\r getting bulkUpload info. executing the following request: $command");

	exec($command, $output1, $result);
	if ($result != 0){
		return fail(__FUNCTION__." Command: $command failed.");
	}

	$output = null;
	while(!$output)
	{
		exec($command, $output1, $result);
		if ($result != 0){
			return fail(__FUNCTION__." Command: $command failed.");
		}
		$output1 = implode(' ", " ', $output1);
		$match = null;
		preg_match('/\<entryId\>(.*?)\<\/entryId\>/', $output1, $match);
		if($match && $match[1])
			$output = $match[1];
	}

	return $output;
}

function tearDown($client, $entryId)
{
	info("Deleting Entry $entryId");
	$client->baseEntry->delete($entryId);
}

function main($dc,$partnerId,$adminSecret)
{
	global $entryIdToDelete;
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$ret  = multiAudioTest1($client);
    tearDown($client, $entryIdToDelete);
	return ($ret);
}

goMain2();

function goMain2()
{
	if ($GLOBALS['argc']!=4 )
	{
		printUsage2();
		exit (1);
	}

	$dcUrl                  =       $GLOBALS['argv'][1];
	$partnerId              =       $GLOBALS['argv'][2];
	$adminSecret    =       $GLOBALS['argv'][3];
	$res =  main($dcUrl,$partnerId,$adminSecret);
	exit($res);
}
