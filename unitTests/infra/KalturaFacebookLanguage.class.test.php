<?php

#Mock-ups -
require __DIR__.'/../mock/KalturaLog.mock.php';

require_once('plugins/content_distribution/providers/facebook/lib/api/KalturaFacebookLanguageMatch.php');
require_once('api_v3/lib/types/IKalturaEnum.php');
require_once('api_v3/lib/types/KalturaStringEnum.php');
require_once('api_v3/lib/types/enums/KalturaLanguage.php');

/**
 * @group infra
 * @subpackage util
 */
class FacebookLanguageUtilTest extends PHPUnit_Framework_TestCase {

	function testAllMatches()
	{
		$kalturaLang = new ReflectionClass('KalturaLanguage');

		$allLanguages  = $kalturaLang->getConstants();

		KalturaLog::info("found ".count($allLanguages)." languages");
		$matches = 0;
		foreach ($allLanguages as $language) {
			try {
				KalturaFacebookLanguageMatch::getFacebookCodeForKalturaLanguage($language);
				$matches += 1;
			} catch (Exception $e) {
				KalturaLog::err("Failed to find match for language : " . $language);
			}
		}
		KalturaLog::info("Found match for ".$matches." languages ");
	}

}

