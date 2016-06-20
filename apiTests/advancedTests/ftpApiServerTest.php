<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');

function connectAndLoginToFtpServer($dc, $partnerId, $userName, $password)
{
	info("Connecting to FTP server on $dc");
	$conn_id = ftp_connect($dc);
	if (!$conn_id)
	{
		return fail(__FUNCTION__ . " Couldn't connect to FTP-api-server. Please check connection.");
	}
	success("Connected to FTP server");

	// login with username and password
	info("Trying to login to FTP server on $dc");
	$login_result = ftp_login($conn_id, "$partnerId/$userName", $password);
	if (!$login_result)
	{
		return fail(__FUNCTION__ . " Couldn't Login to FTP-api-server. Please check login info and ftp-api-server configuration.");
	}
	success("Successful login to FTP server $conn_id");
	return $conn_id;
}

function testValidFtpRequests($client, $dc,$partnerId, $userName, $password)
{
	info("\nStarting testInValidFtpRequests \n");

	$entry = helper_createEntryAndUploadJpgContent($client);
	$scheduleEvent = createEmptyScheduleEvent($client);

	while (isEmptyEntryUploaded($client, $entry->id) != true)
	{
		sleep(1);
		print (".");
	}

	while (isScheduleEventUploaded($client, $scheduleEvent->id) != true)
	{
		sleep(1);
		print (".");
	}

	$conn_id = connectAndLoginToFtpServer($dc, $partnerId, $userName, $password);
	info("connection is: $conn_id");
	if (!$conn_id || $conn_id == null || $conn_id == -1)
	{
		return fail(__FUNCTION__);
	}

	$formatsArray = array("json", "xml", "ical");
	$result = 0;

	info(" Testing url /");
	$res = ftp_chdir($conn_id, "/");
	if (!$res)
	{
		$result += fail("Error while testing ftp url '/'. ");
	} else
	{
		success("Successful url request: / ");
	}

	$contents = ftp_nlist($conn_id, "/");
	if (!isset($contents) || !isset($contents[0]) || $contents[0] != 'format' || count($contents) != 1)
	{
		$result += fail("Error while testing ftp url '/'. result is: $contents");
	}

	info(" Testing url /format/");
	$res = ftp_chdir($conn_id, "/format/");
	if (!$res)
	{
		$result += fail("Error while testing ftp url '/format'.");
	} else
	{
		success("Successful url request: /format/ ");
	}

	info(" Testing url /format");
	$res = ftp_chdir($conn_id, "/format");
	if (!$res)
	{
		$result += fail("Error while testing ftp url '/format'.");
	} else
	{
		success("Successful url request: /format ");
	}

	$contents = ftp_nlist($conn_id, "/format");
	if (!isset($contents))
	{
		$result += fail("Error while testing ftp url '/format', Expecting to receive format types list but received:  ");
		print_r($contents);
	} else
	{
		info("Retrieved the following formats:");
		print_r($contents);
		foreach ($formatsArray as $formatType)
		{
			if (!in_array($formatType, $contents))
			{
				$result += fail("Error while testing ftp url '/format/', Expecting to receive formats json, xml or ical but received:  ");
				print_r($contents);
			} else
			{
				success("Successful url response found format: $formatType ");
			}
		}
	}

	$contents = ftp_nlist($conn_id, "/format/");
	if (!isset($contents))
	{
		$result += fail("Error while testing ftp url '/format/', Expecting to receive format types list but received:  ");
		print_r($contents);
	} else
	{
		info("Retrieved the following formats:");
		print_r($contents);
		foreach ($formatsArray as $formatType)
		{
			if (!in_array($formatType, $contents))
			{
				$result += fail("Error while testing ftp url '/format/', Expecting to receive formats json, xml or ical but received:  ");
				print_r($contents);
			} else
			{
				success("Successful url response found format: $formatType ");
			}
		}
	}

	foreach ($formatsArray as $formatType)
	{
		$url = "/format/$formatType";
		info(" Testing url $url");
		$res = ftp_chdir($conn_id, $url);
		if (!$res)
		{
			$result += fail("Error while testing ftp url $url.");
		} else
		{
			success("Successful url request: $url ");
		}

		$contents = ftp_nlist($conn_id, $url);
		for ($i = 0; $i < 3; $i++)
		{
			if (!isset($contents) || count($contents) < 20)
			{
				sleep(6);
				$contents = ftp_nlist($conn_id, $url);
			} else
			{
				break;
			}
		}
		if (!isset($contents) || count($contents) < 20)
		{
			$result += fail("Error while testing ftp url $url, Expecting to receive a list of services for format [$formatType] but received: ");
			print_r($contents);
		} else
		{
			success("Successful url response. Retrieved Services list successfully.");
		}

		$urlWithService = "/format/$formatType/baseEntry";
		info(" Testing url $urlWithService");
		$res = ftp_chdir($conn_id, $urlWithService);
		if (!$res)
		{
			$result += fail("Error while testing ftp url $urlWithService.");
		} else
		{
			success("Successful url request: $urlWithService ");
		}
		$contents = ftp_nlist($conn_id, $urlWithService);
		if (!isset($contents) || count($contents) < 1)
		{
			if ($formatType != 'ical')
			{
				$result += fail("Error while testing ftp url $urlWithService, Expecting to receive a list of baseEntries for format [$formatType] but received: ");
				print_r($contents);
			}
		} else
		{
			$baseEntryFileName = "$entry->id.$formatType";
			$fileUrl = "/format/$formatType/baseEntry/$baseEntryFileName";
			info(" Trying to get file $fileUrl ");
			$contents = ftp_get($conn_id, "temp.$formatType", $fileUrl, FTP_TEXT);
			for ($i = 0; $i < 3; $i++)
			{
				if (!isset($contents) || $contents == null)
				{
					sleep(4);
					$contents = ftp_get($conn_id, "temp.$formatType", $fileUrl, FTP_TEXT);
				} else
				{
					break;
				}
			}
			if (!isset($contents) || $contents == null)
			{
				$result += fail("Error while testing ftp url $fileUrl, Fail to get a baseEntry file.");
				print_r($contents);
			} else
			{
				success("Successfully retrieved file $baseEntryFileName with url: $fileUrl ");
			}
		}

		$urlWithScheduleEventService = "/format/$formatType/scheduleEvent";
		info(" Testing url $urlWithScheduleEventService");
		$res = ftp_chdir($conn_id, $urlWithScheduleEventService);
		if (!$res)
		{
			$result += fail("Error while testing ftp url $urlWithScheduleEventService.");
		} else
		{
			success("Successful url request: $urlWithScheduleEventService ");
		}
		$contents = ftp_nlist($conn_id, $urlWithScheduleEventService);
		if (!isset($contents) || count($contents) == 0)
		{
			$result += fail("Error while testing ftp url $urlWithScheduleEventService, Expecting to receive a list of scheduleEvents for format [$formatType] but received: ");
			print_r($contents);
		}

		$scheduleEventFileName = "$scheduleEvent->id.$formatType";
		$fileUrl = "/format/$formatType/scheduleEvent/$scheduleEventFileName";
		info(" Trying to get file $fileUrl ");
		$contents = ftp_get($conn_id, "temp.$formatType", $fileUrl, FTP_TEXT);
		for ($i = 0; $i < 3; $i++)
		{
			if (!isset($contents) || $contents == null)
			{
				sleep(4);
				$contents = ftp_get($conn_id, "temp.$formatType", $fileUrl, FTP_TEXT);
			} else
			{
				break;
			}
		}
		if (!isset($contents) || $contents == null)
		{
			$result += fail("Error while testing ftp url $fileUrl, Fail to get a scheduleEvent file.");
			print_r($contents);
		} else
		{
			success("Successfully retrieved file $scheduleEventFileName with url: $fileUrl ");
		}
	}
//	close the connection
	ftp_close($conn_id);

	return ($result);

}

function testInValidFtpRequests($client, $dc,$partnerId, $userName, $password)
{
	info("\nStarting testInValidFtpRequests \n");

	$conn_id = connectAndLoginToFtpServer($dc, $partnerId, $userName, $password);
	info("connection is: $conn_id");
	if (!$conn_id || $conn_id == null || $conn_id == -1)
	{
		return fail(__FUNCTION__);
	}
	ftp_pasv($conn_id, true);

	$formatsArray = array("json", "xml", "ical");
	$result = 0;


	$invalidUrls = array("InValidFolder", "InValidFolder/", "/format/InValidFolder", "/format/InValidFolder/", "/format/json/InValidFolder", "/format/ical/InValidFolder", "/format/xml/InValidFolder", "/format/json/scheduleEvent/InValidFolder");
	foreach ($invalidUrls as $invalidUrl)
	{
		info(" Testing url directory $invalidUrl \n");
		$res = ftp_chdir($conn_id, $invalidUrl);
		if (!$res)
		{
			success("Expected failure while testing ftp invalid url $invalidUrl. ");
		} else
		{
			$result += fail("Didn't receive error while testing ftp invalid url $invalidUrl. result is: $res");
		}

		info(" Testing url list $invalidUrl \n");
		$contents = ftp_nlist($conn_id, $invalidUrl);
		if (!isset($contents) || count($contents) != 0)
		{
			success("Expected failure while testing ftp invalid url $invalidUrl. ");
		} else
		{
			$result += fail("Error while testing ftp invalid url $invalidUrl ");
		}
	}

	$invalidFilesUrls = array("/format/xml/scheduleEvent/inValidFileName.xml", "/format/xml/scheduleEvent/inValidFileName.ics", "/format/ical/scheduleEvent/inValidFileName.json", "/format/json/baseEntry/inValidFileName.xml", "/format/xml/baseEntry/inValidFileName.ics", "/format/ical/baseEntry/inValidFileName.json"
	, "/format/json/scheduleEvent/inValidFileName.json", "/format/xml/scheduleEvent/inValidFileName.xml", "/format/ical/scheduleEvent/inValidFileName.ics");
	foreach ($invalidFilesUrls as $invalidUrl)
	{
		info(" Testing invalid file url $invalidUrl \n");
		$contents = ftp_get($conn_id, "temp.tmp", $invalidUrl, FTP_TEXT);
		if (!isset($contents) || $contents == null)
		{
			success("Expected Failure in retrieving invalid file url $invalidUrl. ");
		} else
		{
			$result += fail("Successfully retrieved invalid file url $invalidUrl.");

		}
	}
	return ($result);
}

function createEmptyScheduleEvent($client)
{
	info("Creating empty scheduleEvent");
	$scheduleEvent = new KalturaLiveStreamScheduleEvent();
	$scheduleEvent->summary = 'testScheduleEvent';
	$scheduleEvent->startDate = 1584914400000;
	$scheduleEvent->endDate = 1584914700000;
	$scheduleEvent->recurrenceType = KalturaScheduleEventRecurrenceType::NONE;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->add($scheduleEvent);
	info("Created scheduleEvent id =" . $result->id);

	return $result;
}

function main($dc,$partnerId, $adminSecret, $userName, $password)
{
	$client = startKalturaSession($partnerId, $adminSecret, $dc);
	$ret = testValidFtpRequests($client, $dc, $partnerId, $userName, $password);
	$ret += testInValidFtpRequests($client, $dc, $partnerId, $userName, $password);
	return ($ret);
}


function printTestUsage()
{
	print ("\n\rUsage: " . $GLOBALS['argv'][0] . " <DC URL> <partner ID> <admin secret> <username> <password>");
	print ("\n\r * Note: Kaltura FTP-api-server must be on and configured properly to run this test.\r\n");
}

function go()
{
	if ($GLOBALS['argc'] != 6)
	{
		printTestUsage();
		exit (1);
	}

	$dcUrl = $GLOBALS['argv'][1];
	$partnerId = $GLOBALS['argv'][2];
	$adminSecret = $GLOBALS['argv'][3];
	$userName = $GLOBALS['argv'][4];
	$password = $GLOBALS['argv'][5];

	$res = main($dcUrl, $partnerId, $adminSecret, $userName, $password);
	exit($res);
}

go();


function isScheduleEventUploaded($client,$id)
{
	if ($id != null)
	{
		try
		{
			$result = $client->scheduleEvent->get($id, null);
			if ($result)
				return true;
		} catch (Exception $e)
		{
			return true;
		}
	}
	return false;
}

function isEmptyEntryUploaded($client,$id)
{
	if ($id != null)
	{
		try
		{
			$result = $client->baseEntry->get($id, null);
			if ($result)
				return true;
		} catch (Exception $e)
		{
			return true;
		}
	}
	return false;
}