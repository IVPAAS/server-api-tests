<?php

define('KALTURA_ROOT_PATH','.');

#Mock-ups -
require __DIR__.'/../mock/KalturaLog.mock.php';
#Tested file -
require_once('infra/general/FacebookGraphSdkUtils.php');
require_once('infra/storage/kFile.class.php');
require_once('alpha/config/kConf.php');
require_once('plugins/content_distribution/providers/facebook/lib/model/FacebookRequestParameters.php');
require_once('vendor/facebook-sdk-php-v5-customized/FileUpload/FacebookFile.php');


/**
 * @group infra
 * @subpackage util
 */
class FacebookGraphSdkUtilTest extends PHPUnit_Framework_TestCase {

	// taken from facebook.ini
	private static $app_id = null;
	private static $app_secret = null;

	// taken from the DB after manually allowed access for page
	const PAGE_ID = "418148898381715";
	// these credentials are not confedential - they belong to the Kaltura Facebook APP tester account
	const PAGE_ACCESS_TOKEN = "CAAK1eY6KsfYBANNRWNtjJVXI5LqQRZAzhYOP8KhIuswatl2aZCjIXEQ6Vua82kO6BoAcyUpRRTLq40KsiIE9uSuVNuMgduNzJK9P5KSopOXeekN1Vg57ZCSUy6d9PkA5U74529Rwn5GdlposAZCRmLGnzqki1e7VA069eLJz2ab3GYFXbEuKzqTculpWyiQZD";
	const USER_ACCESS_TOKEN = "CAAK1eY6KsfYBAHZAGwOEXrnwLfbNxNzEFMTv8NE8XaV5e4OyTwRoJaM6ed4QxzsZBC64SykSqIqeXmLoeOZCKTvFj5JZAzzU6pAmtCngSxZBO6cH6v9dsQHqhh70GtkWhS3foT21LHMI7YFUAZCvVwFm4CfMFpWsrDfPkaUXdHCoCNJAncIwUFPKeLzC983H8ZD";

	public static $uploadedVideoId ;

	/*
	 * @beforeClass
	 */
	public static function setUpBeforeClass()
	{
		KalturaLog::debug("Getting configuration from server");
		self::$app_id = kConf::get(FacebookRequestParameters::FACEBOOK_APP_ID_REQUEST_PARAM, 'facebook',null);
		self::$app_secret = kConf::get(FacebookRequestParameters::FACEBOOK_APP_SECRET_REQUEST_PARAM, 'facebook',null);
		KalturaLog::info("Got configuration from server app id: ".self::$app_id." secret: ".self::$app_secret );
	}


	public function testCreateFacebookInstance()
	{
		KalturaLog::debug("testing ".__METHOD__);
		FacebookGraphSdkUtils::createFacebookInstance(self::$app_id, self::$app_secret);
	}

	/*
	 * @depends testCreateFacebookInstance
	 */
	public function testUploadVideo()
	{
		// first lets validate that the file exists
		$file_path = "/opt/kaltura/server-api-tests/resources/KalturaTestUpload.mp4";
		KalturaLog::debug("testing ".__METHOD__." before checking sizes");
		$fileSize = kFile::fileSize($file_path);
		KalturaLog::info("Working on file of size : ".$fileSize);

		$metadata = array();
		$metadata['title'] = "Video from :".date('l jS \of F Y h:i:s A');
		$metadata['description'] = "Al, rub my feet -  to which Al replied: not even if a genie popped out of them. ";
		$metadata['call_to_action'] =
			json_encode(
				array('type' => 'WATCH_MORE',
					'value' => array(
					'link' => 'http://videos.kaltura.com',
					'link_caption' => 'Kaltura Videos Portal'
			)));
		$metadata['scheduled_publish_time'] = time() + 1000; // passing the 6 minutes rule
		$metadata['published'] = 'false';
		$metadata['targeting'] = json_encode(
			array(
				'countries' => array('AG')));
		KalturaLog::debug("testing ".__METHOD__." uploading video");
		self::$uploadedVideoId =  FacebookGraphSdkUtils::uploadVideo(
			self::$app_id,
			self::$app_secret,
			self::PAGE_ID,
			self::PAGE_ACCESS_TOKEN,
			$file_path,
			"/opt/kaltura/server-api-tests/resources/kalturaIcon.jpg",
			$fileSize,
			".",
			$metadata);
		KalturaLog::info("After creating video id is : ".self::$uploadedVideoId);
	}

	/*
	 * @depends testUploadVideo
	 */
	public function testUpdateVideo()
	{
		$metadata['title'] = "Updated Video :".date('l jS \of F Y h:i:s A');
		$metadata['description'] = "Changed Description";

		KalturaLog::debug("Updating video ".self::$uploadedVideoId);
		$result = FacebookGraphSdkUtils::updateUploadedVideo(
			self::$app_id,
			self::$app_secret,
			self::PAGE_ACCESS_TOKEN,
			$metadata,
			self::$uploadedVideoId
		);
		KalturaLog::debug("Updating video ".self::$uploadedVideoId." returned with: ".print_r($result, true));
	}

	/*
	 * @depends testUploadVideo
	 */
	public function testDeleteVideo()
	{

		KalturaLog::debug("Deleting video ".self::$uploadedVideoId);
		$result = FacebookGraphSdkUtils::deleteUploadedVideo(
			self::$app_id,
			self::$app_secret,
			self::PAGE_ACCESS_TOKEN,
			self::$uploadedVideoId
			);
		KalturaLog::debug("Deleting video ".self::$uploadedVideoId." returned with: ".print_r($result, true));
	}

	/*
	 * @depends testUploadVideo
	 */
	public function testUploadCaption()
	{
		// first lets validate that the file exists
		$file_path = "/opt/kaltura/server-api-tests/resources/KalturaTestCaption.srt";
		kFile::fileSize($file_path);

		KalturaLog::debug("testing ".__METHOD__." Adding captions to video ".self::$uploadedVideoId);
		FacebookGraphSdkUtils::uploadCaptions(
			self::$app_id,
			self::$app_secret,
			self::PAGE_ACCESS_TOKEN,
			self::$uploadedVideoId,
			$file_path,
			'en_US');
	}

//	/*
//	 * @depends testUploadVideo
//	 */
//	public function testUpdatingTags()
//	{
//		KalturaLog::debug("testing ".__METHOD__." Adding tags to video ".self::$uploadedVideoId);
//		FacebookGraphSdkUtils::uploadCaptions(
//			self::$app_id,
//			self::$app_secret,
//			self::PAGE_ACCESS_TOKEN,
//			self::$uploadedVideoId,
//			);
//	}

	/*
	 * @depends testUploadCaption
	 */
	public function testDeleteCaption()
	{
		// first lets validate that the file exists
		KalturaLog::debug("testing ".__METHOD__." Deleting captions from video ".self::$uploadedVideoId);
		FacebookGraphSdkUtils::deleteCaptions(//$appId, $appSecret, $accessToken, $videoId, $locale
			self::$app_id,
			self::$app_secret,
			self::PAGE_ACCESS_TOKEN,
			self::$uploadedVideoId,
			'en_US');
	}

	public function testUploadImage()
	{
		$imageUrl = "http://corp.kaltura.com/sites/default/files/Homepage%20New%20Logo.png";
		KalturaLog::debug("testing ".__METHOD__." uploading image to page id : ".self::PAGE_ID);
		FacebookGraphSdkUtils::uploadPhoto(
			self::$app_id,
			self::$app_secret,
			self::PAGE_ACCESS_TOKEN,
			self::PAGE_ID,
			$imageUrl);
	}




}

