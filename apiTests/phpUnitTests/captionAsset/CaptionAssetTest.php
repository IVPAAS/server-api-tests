<?php

require_once __DIR__ . '/../KalturaApiTestCase.php';
require_once '/opt/kaltura/app/alpha/scripts/bootstrap.php';
require_once '/opt/kaltura/app/infra/content/kXml.class.php';
require_once '/opt/kaltura/app/plugins/content/caption/base/lib/kCaptionsContentManager.php';
require_once '/opt/kaltura/app/plugins/content/caption/base/lib/captionManagers/webVttCaptionsContentManager.php';


/**
 * category service test case.
 */
class CaptionAssetTest extends KalturaApiTestCase
{

	public function testWebVttTimeParser()
	{
		$webVttManager = new webVttCaptionsContentManager();
		$shortTime = $webVttManager->parseCaptionTime('00:00.123');
		$this->assertNotNull($shortTime);
		$longTime = $webVttManager->parseCaptionTime('00:00:00.123');
		$this->assertNotNull($longTime);
		$InvalidTime = $webVttManager->parseCaptionTime('00:00:00:00.123');
		$this->assertNull($InvalidTime);
		$InvalidTime2 = $webVttManager->parseCaptionTime('00');
		$this->assertNull($InvalidTime2);

	}

	public function testWebVttHeaderParser()
	{
		$webVttManager = new webVttCaptionsContentManager();
		$this->assertTrue($webVttManager->validateWebVttHeader('WEBVTT'));
		$this->assertTrue($webVttManager->validateWebVttHeader('WEBVTT '));
		$this->assertTrue($webVttManager->validateWebVttHeader("\xEF\xBB\xBF".'WEBVTT'));
		$this->assertTrue($webVttManager->validateWebVttHeader("\xEF\xBB\xBF".'WEBVTT '));
		$this->assertFalse($webVttManager->validateWebVttHeader('InvalidHeader'));
		$this->assertFalse($webVttManager->validateWebVttHeader('WEBVTTInvalid'));
		$this->assertFalse($webVttManager->validateWebVttHeader('WEBVTT -->'));
		$this->assertFalse($webVttManager->validateWebVttHeader("\xEF\xBB\xBF".'WEBVTTInvalid'));
		$this->assertFalse($webVttManager->validateWebVttHeader("\xEF\xBB\xBF".'WEBVTT -->'));
		$this->assertFalse($webVttManager->validateWebVttHeader(''));
		$this->assertFalse($webVttManager->validateWebVttHeader(null));
	}


	public function testValidWebVttParser1()
	{
		$webVttManager = new webVttCaptionsContentManager();
		$vtt = file_get_contents(dirname(__FILE__)."/validWebVTT.vtt");
		$itemsData = $webVttManager->parse($vtt);
		$textIndex = 1;
		foreach($itemsData as $itemData)
		{
			$content = null;
			$this->assertEquals($itemData['startTime'], 62300);
			$this->assertEquals($itemData['endTime'], 14706700);
			foreach ($itemData['content'] as $curChunk)
			{
				$content .= $curChunk['text'];
			}
			if (strcmp(trim($content), "Testing VTT") !== 0)
			{
				$this->fail("Error Parsing VTT file. expected [Testing VTT] but got [$content] in textIndex $textIndex");
			}
			$textIndex++;
		}
	}

	public function testValidWebVttParser2()
	{
		$webVttManager = new webVttCaptionsContentManager();
		$vtt = file_get_contents(dirname(__FILE__)."/validWebVTT2.vtt");
		$itemsData = $webVttManager->parse($vtt);
		$val = count($itemsData);
		$this->assertEquals($val, 4);
		$textIndex = 1;
		foreach($itemsData as $itemData)
		{
			$content = null;
			foreach ($itemData['content'] as $curChunk)
			{
				$content .= $curChunk['text'];
			}
			if (strcmp(trim($content), "Testing VTT") !== 0)
			{
				$this->fail("Error Parsing VTT file. expected [Testing VTT] but got [$content]in textIndex $textIndex");
			}
			$textIndex++;
		}
	}

	public function testValidWebVttParser3()
	{
		$webVttManager = new webVttCaptionsContentManager();
		$vtt = file_get_contents(dirname(__FILE__)."/validWebVTT3.vtt");
		$itemsData = $webVttManager->parse($vtt);
		$val = count($itemsData);
		$this->assertEquals($val, 4);
		$textIndex = 1;
		foreach($itemsData as $itemData)
		{
			$content = null;
			foreach ($itemData['content'] as $curChunk)
			{
				$content .= $curChunk['text'];
			}
			if (strcmp(trim($content), "Testing VTT") !== 0)
			{
				$this->fail("Error Parsing VTT file. expected [Testing VTT] but got [$content]in textIndex $textIndex");
			}
			$textIndex++;
		}
	}

	public function testValidWebVttParserWithoutTrimmingTextTags()
	{
		$webVttManager = new webVttCaptionsContentManager();
		$vtt = file_get_contents(dirname(__FILE__)."/validWebVTT4.vtt");
		$itemsData = $webVttManager->parseWebVTT($vtt);
		$val = count($itemsData);
		$this->assertEquals($val, 4);
		$textIndex = 1;
		foreach($itemsData as $itemData)
		{
			$content = null;
			foreach ($itemData['content'] as $curChunk)
			{
				$content .= $curChunk['text'];
			}
			if (strcmp(trim($content), "<b>Testing VTT</b>") !== 0)
			{
				$this->fail("Error Parsing VTT file. expected [<b>Testing VTT</b>] but got [$content]in textIndex $textIndex");
			}
			$textIndex++;
		}
	}

	public function testInvalidWebVttParser2()
	{
		$this->log("Test: invalid vtt (in header) - expected failure to parse");
		$webVttManager = new webVttCaptionsContentManager();
		$vtt = file_get_contents(dirname(__FILE__)."/invalidWebVTTHeader.vtt");
		$itemsData = $webVttManager->parse($vtt);
		$val = count($itemsData);

		$this->assertEquals($val, 0);
		if ($val == 0)
			$this->log("Test success : EXPECTED Failure to parse webVTT file");
	}

	public function testInvalidWebVttParser3()
	{
		$this->log("Test: invalid vtt (in header) - expected failure to parse");
		$webVttManager = new webVttCaptionsContentManager();
		$vtt = file_get_contents(dirname(__FILE__)."/invalidWebVTTDescription.vtt");
		$itemsData = $webVttManager->parse($vtt);
		$val = count($itemsData);

		$this->assertEquals($val, 0);
		if ($val == 0)
			$this->log("Test success : EXPECTED Failure to parse webVTT file");
	}
}

