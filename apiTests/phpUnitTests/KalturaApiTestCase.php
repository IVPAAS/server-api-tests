<?php
date_default_timezone_set('Asia/Jerusalem');

require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');

/**
 * test case.
 */
class KalturaApiTestCase extends PHPUnit_Framework_TestCase implements IKalturaLogger
{	
	protected $ffmpeg = '/opt/kaltura/bin/ffmpeg';
	protected $fps = 30;
	protected $videoBitrate = 400;
	protected $audioBitrate = 64;
	protected $outputFileExtension = 'mp4';
	protected $videoResolution = '640x480';
	protected $duration = 40;
	protected $keyframeInterval = 3;
	
	/**
	 * @var KalturaConfiguration
	 */
	protected $config;
	
	/**
	 * @var string
	 */
	protected $adminSecret;
	
	/**
	 * @var int
	 */
	protected $partnerId;
	
	/**
	 * @var int
	 */
	protected $alternatePartnerId;
	
	/**
	 * @var string
	 */
	protected $alternateAdminSecret;
	
	/**
	 * @var string
	 */
	protected $uniqueTag;
	
	/**
	 * @var array
	 */
	protected $createdEntries = array();
	
	/**
	 * @var array
	 */
	protected $createdCategories = array();
	
	/**
	 * @var array
	 */
	protected $createdUsers = array();
	
	/**
	 * @var array
	 */
	protected $createdMetadataObjects = array();
	
	/**
	 * @var array
	 */
	protected $createdMetadataProfiles = array();
	
	/**
	 * @var array
	 */
	protected $createdDistributionProfiles = array();
	
	/**
	 * @var array
	 */
	protected $createdAccessControlProfiles = array();
	
	/**
	 * @var array
	 */
	protected $createdConversionProfiles = array();
	
	/**
	 * Constructs the test case.
	 */
	public function __construct()
	{
		$this->config = new KalturaConfiguration();
		$this->config->curlTimeout = 1000;
		
		$this->config->serviceUrl = 'http://allinone-be.dev.kaltura.com';
		$this->partnerId = 2054;
		$this->adminSecret = '26119aeb9b6258bb6c2ba952024d7b95';

		$this->config->setLogger($this);

		$this->alternatePartnerId = -2;
		$this->alternateAdminSecret = 'eb59eef581b03fb2be930a9c705629dd';
	}
	
	/* (non-PHPdoc)
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		$this->uniqueTag = uniqid('test_');
		
		parent::setUp();
	}
	
	/* (non-PHPdoc)
	 * @see KalturaApiTestCase::tearDown()
	 */
	protected function tearDown()
	{
		if($this->getStatus() == PHPUnit_Runner_BaseTestRunner::STATUS_PASSED)
		{
			foreach($this->createdMetadataObjects as $id)
			{
				$this->deleteMetadata($id);
			}
			
			foreach($this->createdMetadataProfiles as $id)
			{
				$this->deleteMetadataProfile($id);
			}
			
			foreach($this->createdDistributionProfiles as $id)
			{
				$this->deleteDistributionProfile($id);
			}
			
			foreach($this->createdAccessControlProfiles as $id)
			{
				$this->deleteAccessControlProfile($id);
			}
			
			foreach($this->createdConversionProfiles as $id)
			{
				$this->deleteConversionProfile($id);
			}
			
			foreach($this->createdCategories as $id)
			{
				$this->deleteCategory($id, false);
			}
			
			foreach($this->createdUsers as $id)
			{
				$this->deleteUser($id);
			}
			
			foreach($this->createdEntries as $id)
			{
				$this->deleteEntry($id);
			}
		}
		
		parent::tearDown();
	}
	
	protected function getVideoFilePath()
	{
		$filePath = tempnam(sys_get_temp_dir(), 'test_') . '.' . $this->outputFileExtension;
		$cmd = "$this->ffmpeg -y -f dshow -i video=\"Kaltura Virtual Camera\" -f dshow -i audio=\"Kaltura Virtual Microphone\" -framerate $this->fps  -vcodec libx264 -x264opts  \"keyint={$this->keyframeInterval}\" -preset ultrafast -acodec libmp3lame -vf scale={$this->videoResolution}  -b:v {$this->videoBitrate}k -b:a {$this->audioBitrate}k  -t $this->duration $filePath";
		$rt = null;
		passthru($cmd, $rt);
		$this->assertEquals(0, $rt);
		
		return $filePath;
	}
	
	protected function getClient($type = KalturaSessionType::USER, $partnerId = null, $userId = null, $expiry = 86400, $privileges = '', $adminSecret = null)
	{
		if(is_null($partnerId))
			$partnerId = $this->partnerId;
		if(is_null($adminSecret))
			$adminSecret = $this->adminSecret;
			
		$client = new KalturaClient($this->config);
		$client->setPartnerId($this->partnerId);
		$ks = $client->generateSessionV2($adminSecret, $userId, $type, $partnerId, $expiry, $privileges);
		$client->setKs($ks);
		return $client;
	}
	
	protected function getUserClient($userId = null)
	{
		return $this->getClient(KalturaSessionType::USER, null, $userId);
	}
	
	protected function getAdminClient()
	{
		return $this->getClient(KalturaSessionType::ADMIN);
	}
	
	protected function getAlternateClient($type = KalturaSessionType::USER, $partnerId = null, $userId = null, $expiry = 86400, $privileges = '', $adminSecret = null)
	{
		if(is_null($partnerId))
			$partnerId = $this->alternatePartnerId;
		if(is_null($adminSecret))
			$adminSecret = $this->alternateAdminSecret;
			
		$client = new KalturaClient($this->config);
		$client->setPartnerId($this->alternatePartnerId);
		$client->setKs($client->generateSessionV2($adminSecret, $userId, $type, $partnerId, $expiry, $privileges));
		
		return $client;
	}
	
	public function _testClient()
	{
		$this->log("Test: testClient");
		
		$client = $this->getClient(KalturaSessionType::USER);
		$client->system->ping();
		
		$client = $this->getAdminClient();
		$client->system->pingDatabase();
	}
	
	public function log($msg)
	{
		echo "$msg\n";
	}
	
	/**
	 * @param int $parentId
	 * @return KalturaCategory
	 */
	protected function createCategory($parentId = null)
	{
		$category = new KalturaCategory();
		$category->name = uniqid('test_');
		$category->parentId = $parentId;
		$category->tags = $this->uniqueTag;
		
		$client = $this->getAdminClient();
		$category = $client->category->add($category);
		$this->createdCategories[$category->id] = $category->id;
		
		return $category;
	}
	
	/**
	 * @return KalturaUser
	 */
	protected function createUser()
	{
		$user = new KalturaUser();
		$user->id = uniqid('test_');
		$user->tags = $this->uniqueTag;
		
		$client = $this->getAdminClient();
		$user = $client->user->add($user);
		$this->createdUsers[$user->id] = $user->id;
		
		return $user;
	}
	
	/**
	 * @return KalturaMediaEntry
	 */
	protected function addEntry(KalturaMediaEntry $entry)
	{
		$client = $this->getAdminClient();
		$entry = $client->media->add($entry);
		$this->createdEntries[$entry->id] = $entry->id;
		
		return $entry;
	}
	
	/**
	 * @return KalturaMediaEntry
	 */
	protected function createEntry($sourceFlavor = null, $additionalAttributes = array())
	{
		$entry = new KalturaMediaEntry();
		$entry->mediaType = KalturaMediaType::VIDEO;
		$entry->name = uniqid('test_');
		$entry->description = uniqid('test ');
		$entry->tags = $this->uniqueTag;
		$entry->referenceId = uniqid('test_');
		$entry->userId = uniqid('test_');

		foreach($additionalAttributes as $attribute => $value)
			$entry->$attribute = $value;

		$this->createdUsers[$entry->userId] = $entry->userId;

		$entry = $this->addEntry($entry);

		if($sourceFlavor)
		{
			$client = $this->getClient();

			$uploadToken = $client->uploadToken->add();
			$client->uploadToken->upload($uploadToken->id, $sourceFlavor);

			$resource = new KalturaUploadedFileTokenResource();
			$resource->token = $uploadToken->id;

			$client->media->addContent($entry->id, $resource);
		}

		return $entry;
	}
	
	/**
	 * @return KalturaLiveStreamEntry
	 */
	protected function createLiveStreamEntry()
	{
		$entry = new KalturaLiveStreamEntry();
		$entry->sourceType = KalturaSourceType::LIVE_STREAM;
		$entry->mediaType = KalturaMediaType::LIVE_STREAM_FLASH;
		$entry->name = uniqid('test_');
		$entry->description = uniqid('test ');
		$entry->tags = $this->uniqueTag;
		
		$client = $this->getAdminClient();
		$entry = $client->liveStream->add($entry);
		$this->createdEntries[$entry->id] = $entry->id;
		
		return $entry;
	}
	
	/**
	 * @return KalturaMetadataProfile
	 */
	protected function createMetadataProfile($objectType, $xsdData)
	{
		$metadataProfile = new KalturaMetadataProfile();
		$metadataProfile->metadataObjectType = $objectType;
		$metadataProfile->name = uniqid('test_');
		$metadataProfile->systemName = uniqid('test_');
		$metadataProfile->tags = $this->uniqueTag;
		
		$metadataPlugin = KalturaMetadataClientPlugin::get($this->getAdminClient());
		$metadataProfile = $metadataPlugin->metadataProfile->add($metadataProfile, $xsdData);
		$this->createdMetadataProfiles[$metadataProfile->id] = $metadataProfile->id;
		
		return $metadataProfile;
	}

	protected function createAccessControlProfile()
	{
		$accessControlProfile = new KalturaAccessControlProfile();
		$accessControlProfile->name = uniqid();
		
		$client = $this->getAdminClient();
		$accessControlProfile = $client->accessControlProfile->add($accessControlProfile);
		$this->createdAccessControlProfiles[$accessControlProfile->id] = $accessControlProfile->id;
		
		return $accessControlProfile;
	}

	protected function createConversionProfile()
	{
		$client = $this->getAdminClient();
		$defaultConversionProfile = $client->conversionProfile->getDefault();
		
		$conversionProfile = new KalturaConversionProfile();
		$conversionProfile->name = uniqid();
		$conversionProfile->tags = $this->uniqueTag;
		$conversionProfile->flavorParamsIds = $defaultConversionProfile->flavorParamsIds;
		
		$conversionProfile = $client->conversionProfile->add($conversionProfile);
		$this->createdConversionProfiles[$conversionProfile->id] = $conversionProfile->id;
		
		return $conversionProfile;
	}
	
	/**
	 * @return KalturaDistributionProfile
	 */
	protected function createDistributionProfile(KalturaDistributionProfile $distributionProfile)
	{
		$distributionProfile->name = uniqid('test_');
		$distributionProfile->submitEnabled = KalturaDistributionProfileActionStatus::MANUAL;
		$distributionProfile->updateEnabled = KalturaDistributionProfileActionStatus::MANUAL;
		$distributionProfile->deleteEnabled = KalturaDistributionProfileActionStatus::AUTOMATIC;
		$distributionProfile->requiredFlavorParamsIds = '0';
		
		$distributionPlugin = KalturaContentDistributionClientPlugin::get($this->getAdminClient());
		$distributionProfile = $distributionPlugin->distributionProfile->add($distributionProfile);
		$this->createdDistributionProfiles[$distributionProfile->id] = $distributionProfile->id;
		
		$distributionPlugin->distributionProfile->updateStatus($distributionProfile->id, KalturaDistributionProfileStatus::ENABLED);
		return $distributionProfile;
	}
	
	/**
	 * @return KalturaMetadata
	 */
	protected function createMetadata($metadataProfileId, $objectType, $objectId, $xmlData)
	{
		$metadata = new KalturaMetadata();
		$metadata->metadataObjectType = $objectType;
		$metadata->objectId = $objectId;
		$metadata->tags = $this->uniqueTag;
		
		$metadataPlugin = KalturaMetadataClientPlugin::get($this->getAdminClient());
		$metadata = $metadataPlugin->metadata->add($metadataProfileId, $objectType, $objectId, $xmlData);
		$this->createdMetadataObjects[$metadata->id] = $metadata->id;
		
		return $metadata;
	}

	/**
	 * @param string $entryId
	 * @param int $count
	 * @return array<KalturaCategoryEntry>
	 */
	protected function addCategoriesToEntry($entryId, $count)
	{
		$categoryEntries = array();
		for($i = 0; $i < $count; $i++)
		{
			$categoryEntries[] = $this->addCategoryToEntry($entryId);
		}
		return $categoryEntries;
	}

	/**
	 * @param string $categoryId
	 * @param int $count
	 * @return array<KalturaCategoryEntry>
	 */
	protected function addEntriesToCategory($categoryId, $count)
	{
		$categoryEntries = array();
		for($i = 0; $i < $count; $i++)
		{
			$categoryEntries[] = $this->addCategoryToEntry(null, $categoryId);
		}
		return $categoryEntries;
	}
	
	/**
	 * @param string $entryId
	 * @param int $categoryId
	 * @return KalturaCategoryEntry
	 */
	protected function addCategoryToEntry($entryId = null, $categoryId = null)
	{
		if(is_null($entryId))
		{
			$entry = $this->createEntry();
			$entryId = $entry->id;
		}
	
		if(is_null($categoryId))
		{
			$category = $this->createCategory();
			$categoryId = $category->id;
		}
		
		$categoryEntry = new KalturaCategoryEntry();
		$categoryEntry->entryId = $entryId;
		$categoryEntry->categoryId = $categoryId;
		
		$client = $this->getAdminClient();
		$category = $client->categoryEntry->add($categoryEntry);
		
		return $categoryEntry;
	}
	
	protected function deleteCategory($id, $moveEntriesToParentCategory = null)
	{
		$client = $this->getAdminClient();
		try{
			$client->category->delete($id, $moveEntriesToParentCategory);
		}
		catch (KalturaException $e) {
			
		}
		
		if(isset($this->createdCategories[$id]))
			unset($this->createdCategories[$id]);
	}

	protected function deleteUser($id)
	{
		$client = $this->getAdminClient();
		try{
			$client->user->delete($id);
		}
		catch (KalturaException $e) {
			
		}
		
		if(isset($this->createdUsers[$id]))
			unset($this->createdUsers[$id]);
	}
	
	protected function deleteEntry($id)
	{
		try{
			$this->getAdminClient()->baseEntry->delete($id);
		}
		catch (KalturaException $e) {
			
		}
		
		if(isset($this->createdEntries[$id]))
			unset($this->createdEntries[$id]);
	}
	
	protected function deleteMetadataProfile($id)
	{
		$metadataPlugin = KalturaMetadataClientPlugin::get($this->getAdminClient());
		try{
			$metadataPlugin->metadataProfile->delete($id);
		}
		catch (KalturaException $e) {
			
		}
		
		if(isset($this->createdMetadataProfiles[$id]))
			unset($this->createdMetadataProfiles[$id]);
	}
	
	protected function deleteDistributionProfile($id)
	{
		$distributionPlugin = KalturaContentDistributionClientPlugin::get($this->getAdminClient());
		try{
			$distributionProfile = $distributionPlugin->distributionProfile->delete($id);
		}
		catch (KalturaException $e) {
			
		}
		
		if(isset($this->createdDistributionProfiles[$id]))
			unset($this->createdDistributionProfiles[$id]);
	}
	
	protected function deleteConversionProfile($id)
	{
		$client = $this->getAdminClient();
		try{
			$client->conversionProfile->delete($id);
		}
		catch (KalturaException $e) {
			
		}
		
		if(isset($this->createdConversionProfiles[$id]))
			unset($this->createdConversionProfiles[$id]);
	}
	
	protected function deleteAccessControlProfile($id)
	{
		$client = $this->getAdminClient();
		try{
			$client->accessControlProfile->delete($id);
		}
		catch (KalturaException $e) {
			
		}
		
		if(isset($this->createdAccessControlProfiles[$id]))
			unset($this->createdAccessControlProfiles[$id]);
	}
	
	protected function deleteMetadata($id)
	{
		$metadataPlugin = KalturaMetadataClientPlugin::get($this->getAdminClient());
		try{
			$metadataPlugin->metadata->delete($id);
		}
		catch (KalturaException $e) {
			
		}
		
		if(isset($this->createdMetadataObjects[$id]))
			unset($this->createdMetadataObjects[$id]);
	}
}

