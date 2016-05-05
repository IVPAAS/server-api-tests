<?php
ini_set('memory_limit', '256M');

require_once __DIR__ . '/ResponseProfileServiceTest.php';
require_once '/opt/kaltura/web/content/clientlibs/testsClient/KalturaPlugins/KalturaMetadataClientPlugin.php';
require_once '/opt/kaltura/web/content/clientlibs/testsClient/KalturaPlugins/KalturaAnnotationClientPlugin.php';
require_once '/opt/kaltura/web/content/clientlibs/testsClient/KalturaPlugins/KalturaThumbCuePointClientPlugin.php';

/**
 * Real world test cases.
 */
class ResponseProfileCacheTest extends ResponseProfileServiceTest
{
	public function testCache()
	{
		$largePager = new KalturaFilterPager();
		$largePager->pageSize = 100;
		
		$smallPager = new KalturaFilterPager();
		$smallPager->pageSize = 2;
		
		$filter = new KalturaMediaEntryFilter();
		$filter->idIn = '0_ag24xnfv,0_u2t299aj';
		
		$metadataFilter = new KalturaMetadataFilter();
		$metadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::THUMB_CUE_POINT;
		
		$metadataMapping = new KalturaResponseProfileMapping();
		$metadataMapping->filterProperty = 'objectIdEqual';
		$metadataMapping->parentProperty = 'id';
		
		$metadataResponseProfile = new KalturaDetachedResponseProfile();
		$metadataResponseProfile->name = uniqid('test_');
		$metadataResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$metadataResponseProfile->fields = 'id,objectId,metadataProfileId';
		$metadataResponseProfile->filter = $metadataFilter;
		$metadataResponseProfile->pager = $largePager;
		$metadataResponseProfile->mappings = array($metadataMapping);
		
		$entryMapping = new KalturaResponseProfileMapping();
		$entryMapping->filterProperty = 'entryIdEqual';
		$entryMapping->parentProperty = 'id';
		
		$chaptersFilter = new KalturaThumbCuePointFilter();
		$chaptersFilter->subTypeEqual = KalturaThumbCuePointSubType::CHAPTER;
		$chaptersFilter->statusEqual = KalturaCuePointStatus::READY;
		
		$chaptersResponseProfile = new KalturaDetachedResponseProfile();
		$chaptersResponseProfile->name = uniqid('test_');
		$chaptersResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$chaptersResponseProfile->fields = 'id,entryId';
		$chaptersResponseProfile->filter = $chaptersFilter;
		$chaptersResponseProfile->pager = $largePager;
		$chaptersResponseProfile->mappings = array($entryMapping);
		$chaptersResponseProfile->relatedProfiles = array($metadataResponseProfile);
		
		$responseProfile = new KalturaDetachedResponseProfile();
		$responseProfile->name = uniqid('test_');
		$responseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$responseProfile->fields = 'id,name';
		$responseProfile->relatedProfiles = array($chaptersResponseProfile);
		
		$client = $this->getClient(KalturaSessionType::ADMIN);
		
		$client->setResponseProfile($responseProfile);
		$list = $client->media->listAction($filter, $smallPager);
		
		$client->setResponseProfile($responseProfile);
		$list = $client->media->listAction($filter, $smallPager);
	}
}

