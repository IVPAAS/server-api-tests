<?php

require_once __DIR__ . '/ResponseProfileServiceTest.php';
require_once '/opt/kaltura/web/content/clientlibs/testsClient/KalturaPlugins/KalturaMetadataClientPlugin.php';
require_once '/opt/kaltura/web/content/clientlibs/testsClient/KalturaPlugins/KalturaAnnotationClientPlugin.php';

/**
 * Real world test cases.
 */
class ResponseProfileUseCasesTest extends ResponseProfileServiceTest
{
	public function createEntriesWithMetadataObjects($entriesCount, $metadataProfileCount = 2)
	{
		$entries = array();
		$metadataProfiles = array();
		
		for($i = 1; $i <= $metadataProfileCount; $i++)
		{
			$xsd = '<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	<xsd:element name="metadata">
		<xsd:complexType>
			<xsd:sequence>
				<xsd:element name="Choice' . $i . '" minOccurs="0" maxOccurs="1">
					<xsd:annotation>
						<xsd:documentation></xsd:documentation>
						<xsd:appinfo>
							<label>Example choice ' . $i . '</label>
							<key>choice' . $i . '</key>
							<searchable>true</searchable>
							<description>Example choice ' . $i . '</description>
						</xsd:appinfo>
					</xsd:annotation>
					<xsd:simpleType>
						<xsd:restriction base="listType">
							<xsd:enumeration value="on" />
							<xsd:enumeration value="off" />
						</xsd:restriction>
					</xsd:simpleType>
				</xsd:element>
				<xsd:element name="FreeText' . $i . '" minOccurs="0" maxOccurs="1" type="textType">
					<xsd:annotation>
						<xsd:documentation></xsd:documentation>
						<xsd:appinfo>
							<label>Free text ' . $i . '</label>
							<key>freeText' . $i . '</key>
							<searchable>true</searchable>
							<description>Free text ' . $i . '</description>
						</xsd:appinfo>
					</xsd:annotation>
				</xsd:element>
			</xsd:sequence>
		</xsd:complexType>
	</xsd:element>
	<xsd:complexType name="textType">
		<xsd:simpleContent>
			<xsd:extension base="xsd:string" />
		</xsd:simpleContent>
	</xsd:complexType>
	<xsd:complexType name="objectType">
		<xsd:simpleContent>
			<xsd:extension base="xsd:string" />
		</xsd:simpleContent>
	</xsd:complexType>
	<xsd:simpleType name="listType">
		<xsd:restriction base="xsd:string" />
	</xsd:simpleType>
</xsd:schema>';
				
			$metadataProfiles[$i] = $this->createMetadataProfile(KalturaMetadataObjectType::ENTRY, $xsd);
		}
		
		for($i = 0; $i < $entriesCount; $i++)
		{
			$entry = $this->createEntry();
			$entries[] = $entry;
			
			for($j = 1; $j <= $metadataProfileCount; $j++)
			{
				$xml = '<metadata>
	<Choice' . $j . '>on</Choice' . $j . '>
	<FreeText' . $j . '>example text ' . $j . '</FreeText' . $j . '>
</metadata>';
		
				$this->createMetadata($metadataProfiles[$j]->id, KalturaMetadataObjectType::ENTRY, $entry->id, $xml);
			}
		}
		
		return array($entries, $metadataProfiles);
	}
	
	public function _testEntriesWithAllMetadata()
	{
		$entriesTotalCount = 4;
		$entriesPageSize = 3;
		$metadataPageSize = 2;
		
		list ($entries, $metadataProfiles) = $this->createEntriesWithMetadataObjects($entriesTotalCount);
		
		$entriesFilter = new KalturaMediaEntryFilter();
		$entriesFilter->tagsLike = $this->uniqueTag;
		$entriesFilter->statusIn = implode(',', array(
			KalturaEntryStatus::PENDING,
			KalturaEntryStatus::NO_CONTENT,
		));
		
		$entriesPager = new KalturaFilterPager();
		$entriesPager->pageSize = $entriesPageSize;
		
		$metadataFilter = new KalturaMetadataFilter();
		$metadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
		
		$metadataMapping = new KalturaResponseProfileMapping();
		$metadataMapping->filterProperty = 'objectIdEqual';
		$metadataMapping->parentProperty = 'id';
		
		$metadataPager = new KalturaFilterPager();
		$metadataPager->pageSize = $metadataPageSize;
		
		$metadataResponseProfile = new KalturaDetachedResponseProfile();
		$metadataResponseProfile->name = uniqid('test_');
		$metadataResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$metadataResponseProfile->fields = 'id,objectId,createdAt, xml';
		$metadataResponseProfile->filter = $metadataFilter;
		$metadataResponseProfile->pager = $metadataPager;
		$metadataResponseProfile->mappings = array($metadataMapping);
		
		$responseProfile = new KalturaResponseProfile();
		$responseProfile->name = uniqid('test_');
		$responseProfile->systemName = uniqid('test_');
		$responseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$responseProfile->fields = 'id,name,createdAt';
		$responseProfile->relatedProfiles = array($metadataResponseProfile);
		
		$responseProfile = $this->add($responseProfile);
		
		$nestedResponseProfile = new KalturaResponseProfileHolder();
		$nestedResponseProfile->id = $responseProfile->id;
		
		$client = $this->getClient();
		
		$client->setResponseProfile($nestedResponseProfile);
		$list = $client->baseEntry->listAction($entriesFilter, $entriesPager);
		
		$this->assertEquals($entriesTotalCount, $list->totalCount);
		$this->assertEquals($entriesPageSize, count($list->objects));
		
		foreach($list->objects as $entry)
		{
			/* @var $entry KalturaBaseEntry */
			
			$this->assertNotNull($entry->relatedObjects);
			$this->assertArrayHasKey($metadataResponseProfile->name, $entry->relatedObjects);
			$this->assertType('KalturaMetadataListResponse', $entry->relatedObjects[$metadataResponseProfile->name]);
			$this->assertEquals(count($metadataProfiles), count($entry->relatedObjects[$metadataResponseProfile->name]->objects));
			foreach($entry->relatedObjects[$metadataResponseProfile->name]->objects as $metadata)
			{
				$this->assertType('KalturaMetadata', $metadata);
				$this->assertEquals($entry->id, $metadata->objectId);
			}
		}
	}
	
	public function _testEntriesWithMetadataUsingSystemName()
	{
		$entriesTotalCount = 4;
		$entriesPageSize = 3;
		$metadataPageSize = 2;
		
		list ($entries, $metadataProfiles) = $this->createEntriesWithMetadataObjects($entriesTotalCount);
		
		$entriesFilter = new KalturaMediaEntryFilter();
		$entriesFilter->tagsLike = $this->uniqueTag;
		$entriesFilter->statusIn = implode(',', array(
			KalturaEntryStatus::PENDING,
			KalturaEntryStatus::NO_CONTENT,
		));
		
		$entriesPager = new KalturaFilterPager();
		$entriesPager->pageSize = $entriesPageSize;
		
		$metadataFilter = new KalturaMetadataFilter();
		$metadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
		
		$metadataMapping = new KalturaResponseProfileMapping();
		$metadataMapping->filterProperty = 'objectIdEqual';
		$metadataMapping->parentProperty = 'id';
		
		$metadataPager = new KalturaFilterPager();
		$metadataPager->pageSize = $metadataPageSize;
		
		$metadataResponseProfile = new KalturaDetachedResponseProfile();
		$metadataResponseProfile->name = uniqid('test_');
		$metadataResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$metadataResponseProfile->fields = 'id,objectId,createdAt, xml';
		$metadataResponseProfile->filter = $metadataFilter;
		$metadataResponseProfile->pager = $metadataPager;
		$metadataResponseProfile->mappings = array($metadataMapping);
		
		$responseProfile = new KalturaResponseProfile();
		$responseProfile->name = uniqid('test_');
		$responseProfile->systemName = uniqid('test_');
		$responseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$responseProfile->fields = 'id,name,createdAt';
		$responseProfile->relatedProfiles = array($metadataResponseProfile);
		
		$responseProfile = $this->add($responseProfile);
		
		$nestedResponseProfile = new KalturaResponseProfileHolder();
		$nestedResponseProfile->systemName = $responseProfile->systemName;
		
		$client = $this->getClient();
		
		$client->setResponseProfile($nestedResponseProfile);
		$list = $client->baseEntry->listAction($entriesFilter, $entriesPager);
		
		$this->assertEquals($entriesTotalCount, $list->totalCount);
		$this->assertEquals($entriesPageSize, count($list->objects));
		
		foreach($list->objects as $entry)
		{
			/* @var $entry KalturaBaseEntry */
			
			$this->assertNotNull($entry->relatedObjects);
			$this->assertArrayHasKey($metadataResponseProfile->name, $entry->relatedObjects);
			$this->assertType('KalturaMetadataListResponse', $entry->relatedObjects[$metadataResponseProfile->name]);
			$this->assertEquals(count($metadataProfiles), count($entry->relatedObjects[$metadataResponseProfile->name]->objects));
			foreach($entry->relatedObjects[$metadataResponseProfile->name]->objects as $metadata)
			{
				$this->assertType('KalturaMetadata', $metadata);
				$this->assertEquals($entry->id, $metadata->objectId);
			}
		}
	}
	
	public function _testEntriesWithAllMetadataDetached()
	{
		$entriesTotalCount = 4;
		$entriesPageSize = 3;
		$metadataPageSize = 2;
		
		list ($entries, $metadataProfiles) = $this->createEntriesWithMetadataObjects($entriesTotalCount);
		
		$entriesFilter = new KalturaMediaEntryFilter();
		$entriesFilter->tagsLike = $this->uniqueTag;
		$entriesFilter->statusIn = implode(',', array(
			KalturaEntryStatus::PENDING,
			KalturaEntryStatus::NO_CONTENT,
		));
		
		$entriesPager = new KalturaFilterPager();
		$entriesPager->pageSize = $entriesPageSize;
		
		$metadataFilter = new KalturaMetadataFilter();
		$metadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
		
		$metadataMapping = new KalturaResponseProfileMapping();
		$metadataMapping->filterProperty = 'objectIdEqual';
		$metadataMapping->parentProperty = 'id';
		
		$metadataPager = new KalturaFilterPager();
		$metadataPager->pageSize = $metadataPageSize;
		
		$metadataResponseProfile = new KalturaDetachedResponseProfile();
		$metadataResponseProfile->name = uniqid('test_');
		$metadataResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$metadataResponseProfile->fields = 'id,objectId,createdAt, xml';
		$metadataResponseProfile->filter = $metadataFilter;
		$metadataResponseProfile->pager = $metadataPager;
		$metadataResponseProfile->mappings = array($metadataMapping);
		
		$nestedResponseProfile = new KalturaDetachedResponseProfile();
		$nestedResponseProfile->name = uniqid('test_');
		$nestedResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$nestedResponseProfile->fields = 'id,name,createdAt';
		$nestedResponseProfile->relatedProfiles = array($metadataResponseProfile);
		
		$client = $this->getClient();
		
		$client->setResponseProfile($nestedResponseProfile);
		$list = $client->baseEntry->listAction($entriesFilter, $entriesPager);
		
		$this->assertEquals($entriesTotalCount, $list->totalCount);
		$this->assertEquals($entriesPageSize, count($list->objects));
		
		foreach($list->objects as $entry)
		{
			/* @var $entry KalturaBaseEntry */
			
			$this->assertNotNull($entry->relatedObjects);
			$this->assertArrayHasKey($metadataResponseProfile->name, $entry->relatedObjects);
			$this->assertType('KalturaMetadataListResponse', $entry->relatedObjects[$metadataResponseProfile->name]);
			$this->assertEquals(count($metadataProfiles), count($entry->relatedObjects[$metadataResponseProfile->name]->objects));
			foreach($entry->relatedObjects[$metadataResponseProfile->name]->objects as $metadata)
			{
				$this->assertType('KalturaMetadata', $metadata);
				$this->assertEquals($entry->id, $metadata->objectId);
			}
		}
	}
	
	public function _testEntriesWithWrongFields()
	{
		$entriesTotalCount = 4;
		$entriesPageSize = 3;
		$entries = array();
		
		for($i = 0; $i < $entriesTotalCount; $i++)
		{
			$entry = $this->createEntry();
			$entries[] = $entry;
		}
		
		$entriesFilter = new KalturaMediaEntryFilter();
		$entriesFilter->tagsLike = $this->uniqueTag;
		$entriesFilter->statusIn = implode(',', array(
			KalturaEntryStatus::PENDING,
			KalturaEntryStatus::NO_CONTENT,
		));
		
		$entriesPager = new KalturaFilterPager();
		$entriesPager->pageSize = $entriesPageSize;
		
		$nestedResponseProfile = new KalturaDetachedResponseProfile();
		$nestedResponseProfile->name = uniqid('test_');
		$nestedResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$nestedResponseProfile->fields = 'id,name,createdAt,xxx';
		
		$client = $this->getClient();
		
		$client->setResponseProfile($nestedResponseProfile);
		$list = $client->baseEntry->listAction($entriesFilter, $entriesPager);
		
		$this->assertEquals($entriesTotalCount, $list->totalCount);
		$this->assertEquals($entriesPageSize, count($list->objects));
	}
	
	public function _testEntriesWithWrongFieldsNested()
	{
		$entriesTotalCount = 4;
		$entriesPageSize = 3;
		$metadataPageSize = 2;
		
		list ($entries, $metadataProfiles) = $this->createEntriesWithMetadataObjects($entriesTotalCount);
		
		$entriesFilter = new KalturaMediaEntryFilter();
		$entriesFilter->tagsLike = $this->uniqueTag;
		$entriesFilter->statusIn = implode(',', array(
			KalturaEntryStatus::PENDING,
			KalturaEntryStatus::NO_CONTENT,
		));
		
		$entriesPager = new KalturaFilterPager();
		$entriesPager->pageSize = $entriesPageSize;
		
		$metadataFilter = new KalturaMetadataFilter();
		$metadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
		
		$metadataMapping = new KalturaResponseProfileMapping();
		$metadataMapping->filterProperty = 'objectIdEqual';
		$metadataMapping->parentProperty = 'id';
		
		$metadataPager = new KalturaFilterPager();
		$metadataPager->pageSize = $metadataPageSize;
		
		$metadataResponseProfile = new KalturaDetachedResponseProfile();
		$metadataResponseProfile->name = uniqid('test_');
		$metadataResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$metadataResponseProfile->fields = 'id,objectId,createdAt,xml,xxx';
		$metadataResponseProfile->filter = $metadataFilter;
		$metadataResponseProfile->pager = $metadataPager;
		$metadataResponseProfile->mappings = array($metadataMapping);
		
		$nestedResponseProfile = new KalturaDetachedResponseProfile();
		$nestedResponseProfile->name = uniqid('test_');
		$nestedResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$nestedResponseProfile->fields = 'id,name,createdAt';
		$nestedResponseProfile->relatedProfiles = array($metadataResponseProfile);
		
		$client = $this->getClient();
		
		$client->setResponseProfile($nestedResponseProfile);
		$list = $client->baseEntry->listAction($entriesFilter, $entriesPager);
		
		$this->assertEquals($entriesTotalCount, $list->totalCount);
		$this->assertEquals($entriesPageSize, count($list->objects));
		
		foreach($list->objects as $entry)
		{
			/* @var $entry KalturaBaseEntry */
			
			$this->assertNotNull($entry->relatedObjects);
			$this->assertArrayHasKey($metadataResponseProfile->name, $entry->relatedObjects);
			$this->assertType('KalturaMetadataListResponse', $entry->relatedObjects[$metadataResponseProfile->name]);
			$this->assertEquals(count($metadataProfiles), count($entry->relatedObjects[$metadataResponseProfile->name]->objects));
			foreach($entry->relatedObjects[$metadataResponseProfile->name]->objects as $metadata)
			{
				$this->assertType('KalturaMetadata', $metadata);
				$this->assertEquals($entry->id, $metadata->objectId);
			}
		}
	}
	
	public function _testEntriesWithTooBigNestedPageSize()
	{
		$entriesTotalCount = 3;
		$entriesPageSize = 2;
		$metadataTotalCount = 3;
		$metadataPageSize = 140;
		
		list ($entries, $metadataProfiles) = $this->createEntriesWithMetadataObjects($entriesTotalCount, $metadataTotalCount);
		
		$entriesFilter = new KalturaMediaEntryFilter();
		$entriesFilter->tagsLike = $this->uniqueTag;
		$entriesFilter->statusIn = implode(',', array(
			KalturaEntryStatus::PENDING,
			KalturaEntryStatus::NO_CONTENT,
		));
		
		$entriesPager = new KalturaFilterPager();
		$entriesPager->pageSize = $entriesPageSize;
		
		$metadataFilter = new KalturaMetadataFilter();
		$metadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
		
		$metadataMapping = new KalturaResponseProfileMapping();
		$metadataMapping->filterProperty = 'objectIdEqual';
		$metadataMapping->parentProperty = 'id';
		
		$metadataPager = new KalturaFilterPager();
		$metadataPager->pageSize = $metadataPageSize;
		
		$metadataResponseProfile = new KalturaDetachedResponseProfile();
		$metadataResponseProfile->name = uniqid('test_');
		$metadataResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$metadataResponseProfile->fields = 'id,objectId,createdAt, xml';
		$metadataResponseProfile->filter = $metadataFilter;
		$metadataResponseProfile->pager = $metadataPager;
		$metadataResponseProfile->mappings = array($metadataMapping);
		
		$nestedResponseProfile = new KalturaDetachedResponseProfile();
		$nestedResponseProfile->name = uniqid('test_');
		$nestedResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$nestedResponseProfile->fields = 'id,name,createdAt';
		$nestedResponseProfile->relatedProfiles = array($metadataResponseProfile);
		
		$client = $this->getClient();
		
		$client->setResponseProfile($nestedResponseProfile);
		
		try{
			$list = $client->baseEntry->listAction($entriesFilter, $entriesPager);
		}
		catch(KalturaException $e){
			$this->assertEquals('PROPERTY_VALIDATION_MAX_VALUE', $e->getCode());
			$this->assertArrayHasKey('PROP_NAME', $e->getArguments());
			$this->assertEquals('KalturaFilterPager::pageSize', $e->getArgument('PROP_NAME'));
		}
	}

	public function _testEntriesWithSpecificMetadata()
	{
		$entriesTotalCount = 3;
		$entriesPageSize = 2;
		$metadataTotalCount = 3;
		$metadataPageSize = 2;
		
		list ($entries, $metadataProfiles) = $this->createEntriesWithMetadataObjects($entriesTotalCount, $metadataTotalCount);
		
		$metadataProfile = reset($metadataProfiles);
		
		$entriesFilter = new KalturaMediaEntryFilter();
		$entriesFilter->tagsLike = $this->uniqueTag;
		$entriesFilter->statusIn = implode(',', array(
			KalturaEntryStatus::PENDING,
			KalturaEntryStatus::NO_CONTENT,
		));
		
		$entriesPager = new KalturaFilterPager();
		$entriesPager->pageSize = $entriesPageSize;
		
		$metadataFilter = new KalturaMetadataFilter();
		$metadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
		$metadataFilter->metadataProfileIdEqual = $metadataProfile->id;
		
		$metadataMapping = new KalturaResponseProfileMapping();
		$metadataMapping->filterProperty = 'objectIdEqual';
		$metadataMapping->parentProperty = 'id';
		
		$metadataPager = new KalturaFilterPager();
		$metadataPager->pageSize = $metadataPageSize;
		
		$metadataResponseProfile = new KalturaDetachedResponseProfile();
		$metadataResponseProfile->name = uniqid('test_');
		$metadataResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$metadataResponseProfile->fields = 'id,objectId,metadataProfileId';
		$metadataResponseProfile->filter = $metadataFilter;
		$metadataResponseProfile->pager = $metadataPager;
		$metadataResponseProfile->mappings = array($metadataMapping);
		
		$responseProfile = new KalturaResponseProfile();
		$responseProfile->name = uniqid('test_');
		$responseProfile->systemName = uniqid('test_');
		$responseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$responseProfile->fields = 'id,name';
		$responseProfile->relatedProfiles = array($metadataResponseProfile);
		
		$responseProfile = $this->add($responseProfile);
		
		$nestedResponseProfile = new KalturaResponseProfileHolder();
		$nestedResponseProfile->id = $responseProfile->id;
		
		$client = $this->getClient();
		
		$client->setResponseProfile($nestedResponseProfile);
		$list = $client->baseEntry->listAction($entriesFilter, $entriesPager);
		
		$this->assertEquals($entriesTotalCount, $list->totalCount);
		$this->assertEquals($entriesPageSize, count($list->objects));
		
		foreach($list->objects as $entry)
		{
			/* @var $entry KalturaBaseEntry */
			
			$this->assertNotNull($entry->relatedObjects);
			$this->assertArrayHasKey($metadataResponseProfile->name, $entry->relatedObjects);
			$this->assertType('KalturaMetadataListResponse', $entry->relatedObjects[$metadataResponseProfile->name]);
			$this->assertEquals(1, count($entry->relatedObjects[$metadataResponseProfile->name]->objects));
			foreach($entry->relatedObjects[$metadataResponseProfile->name]->objects as $metadata)
			{
				/* @var $metadata KalturaMetadata */
				$this->assertType('KalturaMetadata', $metadata);
				$this->assertEquals($entry->id, $metadata->objectId);
				$this->assertEquals($metadataProfile->id, $metadata->metadataProfileId);
			}
		}
	}

	public function _testEntryWithMetadataAssetAndChaptersDetached()
	{
		$sourceFlavor = $this->getVideoFilePath();
		$hebrewCaption = 'C:\Users\jonathan.kanarek\Documents\My Captions\heb.srt';
		$englishCaption = 'C:\Users\jonathan.kanarek\Documents\My Captions\eng.srt';
		$captersCount = 5;
		
		$entry = $this->createEntry($sourceFlavor);
		
		$client = $this->getClient(KalturaSessionType::ADMIN);
		
		$captionsPlugin = KalturaCaptionClientPlugin::get($client);
		
		$hebrewCaptionAsset = new KalturaCaptionAsset();
		$hebrewCaptionAsset->label = 'Hebrew';
		$hebrewCaptionAsset->language = KalturaLanguage::HE;
				
		$englishCaptionAsset = new KalturaCaptionAsset();
		$englishCaptionAsset->label = 'English';
		$englishCaptionAsset->language = KalturaLanguage::EN;
		
		$hebrewCaptionContentResource = new KalturaStringResource();
		$hebrewCaptionContentResource->content = file_get_contents($hebrewCaption);
		
		$englishCaptionContentResource = new KalturaStringResource();
		$englishCaptionContentResource->content = file_get_contents($englishCaption);
		
		$hebrewCaptionAsset = $captionsPlugin->captionAsset->add($entry->id, $hebrewCaptionAsset);
		$captionsPlugin->captionAsset->setContent($hebrewCaptionAsset->id, $hebrewCaptionContentResource);
		
		$englishCaptionAsset = $captionsPlugin->captionAsset->add($entry->id, $englishCaptionAsset);
		$captionsPlugin->captionAsset->setContent($hebrewCaptionAsset->id, $englishCaptionContentResource);
		
		$xsd = '<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	<xsd:element name="metadata">
		<xsd:complexType>
			<xsd:sequence>
				<xsd:element name="Choice" minOccurs="0" maxOccurs="1">
					<xsd:annotation>
						<xsd:documentation></xsd:documentation>
						<xsd:appinfo>
							<label>Example choice</label>
							<key>choice</key>
							<searchable>true</searchable>
							<description>Example choice</description>
						</xsd:appinfo>
					</xsd:annotation>
					<xsd:simpleType>
						<xsd:restriction base="listType">
							<xsd:enumeration value="on" />
							<xsd:enumeration value="off" />
						</xsd:restriction>
					</xsd:simpleType>
				</xsd:element>
				<xsd:element name="FreeText" minOccurs="0" maxOccurs="1" type="textType">
					<xsd:annotation>
						<xsd:documentation></xsd:documentation>
						<xsd:appinfo>
							<label>Free text</label>
							<key>freeText</key>
							<searchable>true</searchable>
							<description>Free text</description>
						</xsd:appinfo>
					</xsd:annotation>
				</xsd:element>
			</xsd:sequence>
		</xsd:complexType>
	</xsd:element>
	<xsd:complexType name="textType">
		<xsd:simpleContent>
			<xsd:extension base="xsd:string" />
		</xsd:simpleContent>
	</xsd:complexType>
	<xsd:complexType name="objectType">
		<xsd:simpleContent>
			<xsd:extension base="xsd:string" />
		</xsd:simpleContent>
	</xsd:complexType>
	<xsd:simpleType name="listType">
		<xsd:restriction base="xsd:string" />
	</xsd:simpleType>
</xsd:schema>';
				
		$metadataProfile1 = $this->createMetadataProfile(KalturaMetadataObjectType::ENTRY, $xsd);
		$metadataProfile2 = $this->createMetadataProfile(KalturaMetadataObjectType::ENTRY, $xsd);
		
		$xml = '<metadata>
	<Choice>on</Choice>
	<FreeText>example text</FreeText>
</metadata>';
		
		$this->createMetadata($metadataProfile1->id, KalturaMetadataObjectType::ENTRY, $entry->id, $xml);
		$this->createMetadata($metadataProfile2->id, KalturaMetadataObjectType::ENTRY, $entry->id, $xml);
		
		$metadataFilter1 = new KalturaMetadataFilter();
		$metadataFilter1->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
		$metadataFilter1->metadataProfileIdEqual = $metadataProfile1->id;
		
		$metadataFilter2 = new KalturaMetadataFilter();
		$metadataFilter2->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
		$metadataFilter2->metadataProfileIdEqual = $metadataProfile2->id;
		
		$metadataMapping = new KalturaResponseProfileMapping();
		$metadataMapping->filterProperty = 'objectIdEqual';
		$metadataMapping->parentProperty = 'id';
		
		$metadataResponseProfile1 = new KalturaDetachedResponseProfile();
		$metadataResponseProfile1->name = uniqid('test_');
		$metadataResponseProfile1->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$metadataResponseProfile1->fields = 'id,objectId,metadataProfileId';
		$metadataResponseProfile1->filter = $metadataFilter1;
		$metadataResponseProfile1->mappings = array($metadataMapping);
		
		$metadataResponseProfile2 = new KalturaDetachedResponseProfile();
		$metadataResponseProfile2->name = uniqid('test_');
		$metadataResponseProfile2->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$metadataResponseProfile2->fields = 'id,objectId,metadataProfileId';
		$metadataResponseProfile2->filter = $metadataFilter2;
		$metadataResponseProfile2->mappings = array($metadataMapping);
		
		$entryMapping = new KalturaResponseProfileMapping();
		$entryMapping->filterProperty = 'entryIdEqual';
		$entryMapping->parentProperty = 'id';
		
		$flavorFilter = new KalturaFlavorAssetFilter();
		$flavorFilter->statusEqual = KalturaFlavorAssetStatus::READY;
		
		$flavorsResponseProfile = new KalturaDetachedResponseProfile();
		$flavorsResponseProfile->name = uniqid('test_');
		$flavorsResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$flavorsResponseProfile->fields = 'id,entryId';
		$flavorsResponseProfile->filter = $flavorFilter;
		$flavorsResponseProfile->mappings = array($entryMapping);
		
		$captionFilter = new KalturaCaptionAssetFilter();
		
		$captionsResponseProfile = new KalturaDetachedResponseProfile();
		$captionsResponseProfile->name = uniqid('test_');
		$captionsResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$captionsResponseProfile->fields = 'id,entryId';
		$captionsResponseProfile->filter = $captionFilter;
		$captionsResponseProfile->mappings = array($entryMapping);
		
		$thumbFilter = new KalturaThumbAssetFilter();
		$thumbFilter->statusEqual = KalturaThumbAssetStatus::READY;
		
		$thumbResponseProfile = new KalturaDetachedResponseProfile();
		$thumbResponseProfile->name = uniqid('test_');
		$thumbResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$thumbResponseProfile->fields = 'id,entryId';
		$thumbResponseProfile->filter = $thumbFilter;
		$thumbResponseProfile->mappings = array($entryMapping);
		
		$chaptersFilter = new KalturaThumbCuePointFilter();
		$chaptersFilter->subTypeEqual = KalturaThumbCuePointSubType::CHAPTER;
		$chaptersFilter->statusEqual = KalturaCuePointStatus::READY;
		
		$chaptersResponseProfile = new KalturaDetachedResponseProfile();
		$chaptersResponseProfile->name = uniqid('test_');
		$chaptersResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$chaptersResponseProfile->fields = 'id,entryId';
		$chaptersResponseProfile->filter = $chaptersFilter;
		$chaptersResponseProfile->mappings = array($entryMapping);
		
		$nestedResponseProfile = new KalturaDetachedResponseProfile();
		$nestedResponseProfile->name = uniqid('test_');
		$nestedResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$nestedResponseProfile->fields = 'id,name';
		$nestedResponseProfile->relatedProfiles = array(
			$metadataResponseProfile1, 
			$metadataResponseProfile2,
			$flavorsResponseProfile,
			$captionsResponseProfile,
			$thumbResponseProfile,
			$chaptersResponseProfile,
		);
		
		$timeout = time() + (60 * 10);
		while($entry->status != KalturaEntryStatus::READY)
		{
			$this->assertGreaterThan(time(), $timeout, 'Timeout');
			sleep(30);
			$entry = $client->media->get($entry->id);
		}
		
		$thumbParams = new KalturaThumbParams();
		$client->thumbAsset->generate($entry->id, $thumbParams);
		
		$cuePointPlugin = KalturaCuePointClientPlugin::get($client);
		
		$chapterInterval = ceil($entry->msDuration / $captersCount);
		$chapterOffset = $chapterInterval;
		$chaptersCount = 0;
		while($chapterOffset < $entry->msDuration){
			$chapter = new KalturaThumbCuePoint();
			$chapter->entryId = $entry->id;
			$chapter->subType = KalturaThumbCuePointSubType::CHAPTER;
			$chapter->startTime = $chapterOffset;
			
			$cuePointPlugin->cuePoint->add($chapter);
			$chapterOffset += $chapterInterval;
			$chaptersCount ++;
		}
		
		$client->setResponseProfile($nestedResponseProfile);
		$entry = $client->media->get($entry->id);
		
		$this->assertNotNull($entry->relatedObjects);
		
		$this->assertArrayHasKey($metadataResponseProfile1->name, $entry->relatedObjects);
		$this->assertType('KalturaMetadataListResponse', $entry->relatedObjects[$metadataResponseProfile1->name]);
		$this->assertEquals(1, count($entry->relatedObjects[$metadataResponseProfile1->name]->objects));
		foreach($entry->relatedObjects[$metadataResponseProfile1->name]->objects as $metadata)
		{
			/* @var $metadata KalturaMetadata */
			$this->assertType('KalturaMetadata', $metadata);
			$this->assertEquals($entry->id, $metadata->objectId);
			$this->assertEquals($metadataProfile1->id, $metadata->metadataProfileId);
		}
	
		
		$this->assertArrayHasKey($metadataResponseProfile2->name, $entry->relatedObjects);
		$this->assertType('KalturaMetadataListResponse', $entry->relatedObjects[$metadataResponseProfile2->name]);
		$this->assertEquals(1, count($entry->relatedObjects[$metadataResponseProfile2->name]->objects));
		foreach($entry->relatedObjects[$metadataResponseProfile2->name]->objects as $metadata)
		{
			/* @var $metadata KalturaMetadata */
			$this->assertType('KalturaMetadata', $metadata);
			$this->assertEquals($entry->id, $metadata->objectId);
			$this->assertEquals($metadataProfile2->id, $metadata->metadataProfileId);
		}
	
		
		$this->assertArrayHasKey($flavorsResponseProfile->name, $entry->relatedObjects);
		$this->assertType('KalturaFlavorAssetListResponse', $entry->relatedObjects[$flavorsResponseProfile->name]);
		$this->assertGreaterThanOrEqual(1, count($entry->relatedObjects[$flavorsResponseProfile->name]->objects));
		foreach($entry->relatedObjects[$flavorsResponseProfile->name]->objects as $asset)
		{
			/* @var $asset KalturaFlavorAsset */
			$this->assertType('KalturaFlavorAsset', $asset);
			$this->assertEquals($entry->id, $asset->entryId);
		}
			
		
		$this->assertArrayHasKey($captionsResponseProfile->name, $entry->relatedObjects);
		$this->assertType('KalturaCaptionAssetListResponse', $entry->relatedObjects[$captionsResponseProfile->name]);
		$this->assertEquals(2, count($entry->relatedObjects[$captionsResponseProfile->name]->objects));
		foreach($entry->relatedObjects[$captionsResponseProfile->name]->objects as $asset)
		{
			/* @var $asset KalturaCaptionAsset */
			$this->assertType('KalturaCaptionAsset', $asset);
			$this->assertEquals($entry->id, $asset->entryId);
		}
			
		
		$this->assertArrayHasKey($thumbResponseProfile->name, $entry->relatedObjects);
		$this->assertType('KalturaThumbAssetListResponse', $entry->relatedObjects[$thumbResponseProfile->name]);
		$this->assertEquals(2, count($entry->relatedObjects[$thumbResponseProfile->name]->objects));
		foreach($entry->relatedObjects[$thumbResponseProfile->name]->objects as $asset)
		{
			/* @var $asset KalturaThumbAsset */
			$this->assertType('KalturaThumbAsset', $asset);
			$this->assertEquals($entry->id, $asset->entryId);
		}
			
		
		$this->assertArrayHasKey($chaptersResponseProfile->name, $entry->relatedObjects);
		$this->assertType('KalturaCuePointListResponse', $entry->relatedObjects[$chaptersResponseProfile->name]);
		$this->assertEquals($chaptersCount, count($entry->relatedObjects[$chaptersResponseProfile->name]->objects));
		foreach($entry->relatedObjects[$chaptersResponseProfile->name]->objects as $asset)
		{
			/* @var $asset KalturaThumbCuePoint */
			$this->assertType('KalturaThumbCuePoint', $asset);
			$this->assertEquals($entry->id, $asset->entryId);
		}
	}

	public function _testEntryWithChildEntries()
	{
		$sourceFlavor = $this->getVideoFilePath();

		$entry = $this->createEntry($sourceFlavor);
		
		$client = $this->getClient(KalturaSessionType::ADMIN);
		
		$entryMapping = new KalturaResponseProfileMapping();
		$entryMapping->filterProperty = 'rootEntryIdEqual';
		$entryMapping->parentProperty = 'id';
		
		$entiresFilter = new KalturaBaseEntryFilter();
		
		$entiresResponseProfileName = 'sub-entries';
		$entiresResponseProfile = new KalturaDetachedResponseProfile();
		$entiresResponseProfile->name = uniqid($entiresResponseProfileName);
		$entiresResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$entiresResponseProfile->fields = 'id';
		$entiresResponseProfile->filter = $entiresFilter;
		$entiresResponseProfile->mappings = array($entryMapping);
		
		$nestedResponseProfile = new KalturaDetachedResponseProfile();
		$nestedResponseProfile->name = uniqid('entry');
		$nestedResponseProfile->type = KalturaResponseProfileType::EXCLUDE_FIELDS;
		$nestedResponseProfile->relatedProfiles = array(
			$entiresResponseProfile
		);
		
		$client->setResponseProfile($nestedResponseProfile);
		$entry = $client->media->get($entry->id);
		
		$entriesList = $entry->relatedObjects[$entiresResponseProfileName];
		/* @var $entriesList KalturaBaseEntryListResponse */
		$thereAreSubEntries = ($entriesList->totalCount > 0);
	}

	public function _testEntryWithMetadataAssetAndChapters()
	{
		$sourceFlavor = $this->getVideoFilePath();
		$hebrewCaption = 'C:\Users\jonathan.kanarek\Documents\My Captions\heb.srt';
		$englishCaption = 'C:\Users\jonathan.kanarek\Documents\My Captions\eng.srt';
		$captersCount = 5;
		
		$entry = $this->createEntry($sourceFlavor);
		
		$client = $this->getClient(KalturaSessionType::ADMIN);
		
		$captionsPlugin = KalturaCaptionClientPlugin::get($client);
		
		$hebrewCaptionAsset = new KalturaCaptionAsset();
		$hebrewCaptionAsset->label = 'Hebrew';
		$hebrewCaptionAsset->language = KalturaLanguage::HE;
				
		$englishCaptionAsset = new KalturaCaptionAsset();
		$englishCaptionAsset->label = 'English';
		$englishCaptionAsset->language = KalturaLanguage::EN;
		
		$hebrewCaptionContentResource = new KalturaStringResource();
		$hebrewCaptionContentResource->content = file_get_contents($hebrewCaption);
		
		$englishCaptionContentResource = new KalturaStringResource();
		$englishCaptionContentResource->content = file_get_contents($englishCaption);
		
		$hebrewCaptionAsset = $captionsPlugin->captionAsset->add($entry->id, $hebrewCaptionAsset);
		$captionsPlugin->captionAsset->setContent($hebrewCaptionAsset->id, $hebrewCaptionContentResource);
		
		$englishCaptionAsset = $captionsPlugin->captionAsset->add($entry->id, $englishCaptionAsset);
		$captionsPlugin->captionAsset->setContent($hebrewCaptionAsset->id, $englishCaptionContentResource);
		
		$xsd = '<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	<xsd:element name="metadata">
		<xsd:complexType>
			<xsd:sequence>
				<xsd:element name="Choice" minOccurs="0" maxOccurs="1">
					<xsd:annotation>
						<xsd:documentation></xsd:documentation>
						<xsd:appinfo>
							<label>Example choice</label>
							<key>choice</key>
							<searchable>true</searchable>
							<description>Example choice</description>
						</xsd:appinfo>
					</xsd:annotation>
					<xsd:simpleType>
						<xsd:restriction base="listType">
							<xsd:enumeration value="on" />
							<xsd:enumeration value="off" />
						</xsd:restriction>
					</xsd:simpleType>
				</xsd:element>
				<xsd:element name="FreeText" minOccurs="0" maxOccurs="1" type="textType">
					<xsd:annotation>
						<xsd:documentation></xsd:documentation>
						<xsd:appinfo>
							<label>Free text</label>
							<key>freeText</key>
							<searchable>true</searchable>
							<description>Free text</description>
						</xsd:appinfo>
					</xsd:annotation>
				</xsd:element>
			</xsd:sequence>
		</xsd:complexType>
	</xsd:element>
	<xsd:complexType name="textType">
		<xsd:simpleContent>
			<xsd:extension base="xsd:string" />
		</xsd:simpleContent>
	</xsd:complexType>
	<xsd:complexType name="objectType">
		<xsd:simpleContent>
			<xsd:extension base="xsd:string" />
		</xsd:simpleContent>
	</xsd:complexType>
	<xsd:simpleType name="listType">
		<xsd:restriction base="xsd:string" />
	</xsd:simpleType>
</xsd:schema>';
				
		$metadataProfile1 = $this->createMetadataProfile(KalturaMetadataObjectType::ENTRY, $xsd);
		$metadataProfile2 = $this->createMetadataProfile(KalturaMetadataObjectType::ENTRY, $xsd);
		
		$xml = '<metadata>
	<Choice>on</Choice>
	<FreeText>example text</FreeText>
</metadata>';
		
		$this->createMetadata($metadataProfile1->id, KalturaMetadataObjectType::ENTRY, $entry->id, $xml);
		$this->createMetadata($metadataProfile2->id, KalturaMetadataObjectType::ENTRY, $entry->id, $xml);
		
		$metadataFilter1 = new KalturaMetadataFilter();
		$metadataFilter1->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
		$metadataFilter1->metadataProfileIdEqual = $metadataProfile1->id;
		
		$metadataFilter2 = new KalturaMetadataFilter();
		$metadataFilter2->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
		$metadataFilter2->metadataProfileIdEqual = $metadataProfile2->id;
		
		$metadataMapping = new KalturaResponseProfileMapping();
		$metadataMapping->filterProperty = 'objectIdEqual';
		$metadataMapping->parentProperty = 'id';
		
		$metadataResponseProfile1 = new KalturaDetachedResponseProfile();
		$metadataResponseProfile1->name = uniqid('test_');
		$metadataResponseProfile1->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$metadataResponseProfile1->fields = 'id,objectId,metadataProfileId';
		$metadataResponseProfile1->filter = $metadataFilter1;
		$metadataResponseProfile1->mappings = array($metadataMapping);
		
		$metadataResponseProfile2 = new KalturaDetachedResponseProfile();
		$metadataResponseProfile2->name = uniqid('test_');
		$metadataResponseProfile2->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$metadataResponseProfile2->fields = 'id,objectId,metadataProfileId';
		$metadataResponseProfile2->filter = $metadataFilter2;
		$metadataResponseProfile2->mappings = array($metadataMapping);
		
		$entryMapping = new KalturaResponseProfileMapping();
		$entryMapping->filterProperty = 'entryIdEqual';
		$entryMapping->parentProperty = 'id';
		
		$flavorFilter = new KalturaFlavorAssetFilter();
		$flavorFilter->statusEqual = KalturaFlavorAssetStatus::READY;
		
		$flavorsResponseProfile = new KalturaDetachedResponseProfile();
		$flavorsResponseProfile->name = uniqid('test_');
		$flavorsResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$flavorsResponseProfile->fields = 'id,entryId';
		$flavorsResponseProfile->filter = $flavorFilter;
		$flavorsResponseProfile->mappings = array($entryMapping);
		
		$captionFilter = new KalturaCaptionAssetFilter();
		
		$captionsResponseProfile = new KalturaDetachedResponseProfile();
		$captionsResponseProfile->name = uniqid('test_');
		$captionsResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$captionsResponseProfile->fields = 'id,entryId';
		$captionsResponseProfile->filter = $captionFilter;
		$captionsResponseProfile->mappings = array($entryMapping);
		
		$thumbFilter = new KalturaThumbAssetFilter();
		$thumbFilter->statusEqual = KalturaThumbAssetStatus::READY;
		
		$thumbResponseProfile = new KalturaDetachedResponseProfile();
		$thumbResponseProfile->name = uniqid('test_');
		$thumbResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$thumbResponseProfile->fields = 'id,entryId';
		$thumbResponseProfile->filter = $thumbFilter;
		$thumbResponseProfile->mappings = array($entryMapping);
		
		$chaptersFilter = new KalturaThumbCuePointFilter();
		$chaptersFilter->subTypeEqual = KalturaThumbCuePointSubType::CHAPTER;
		$chaptersFilter->statusEqual = KalturaCuePointStatus::READY;
		
		$chaptersResponseProfile = new KalturaDetachedResponseProfile();
		$chaptersResponseProfile->name = uniqid('test_');
		$chaptersResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$chaptersResponseProfile->fields = 'id,entryId';
		$chaptersResponseProfile->filter = $chaptersFilter;
		$chaptersResponseProfile->mappings = array($entryMapping);
		
		$responseProfile = new KalturaResponseProfile();
		$responseProfile->name = uniqid('test_');
		$responseProfile->systemName = uniqid('test_');
		$responseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$responseProfile->fields = 'id,name';
		$responseProfile->relatedProfiles = array(
			$metadataResponseProfile1, 
			$metadataResponseProfile2,
			$flavorsResponseProfile,
			$captionsResponseProfile,
			$thumbResponseProfile,
			$chaptersResponseProfile,
		);
		
		$responseProfile = $this->add($responseProfile);
		
		$nestedResponseProfile = new KalturaResponseProfileHolder();
		$nestedResponseProfile->id = $responseProfile->id;
	
		$timeout = time() + (60 * 10);
		while($entry->status != KalturaEntryStatus::READY)
		{
			$this->assertGreaterThan(time(), $timeout, 'Timeout');
			sleep(30);
			$entry = $client->media->get($entry->id);
		}
		
		$thumbParams = new KalturaThumbParams();
		$client->thumbAsset->generate($entry->id, $thumbParams);
		
		$cuePointPlugin = KalturaCuePointClientPlugin::get($client);
		
		$chapterInterval = ceil($entry->msDuration / $captersCount);
		$chapterOffset = $chapterInterval;
		$chaptersCount = 0;
		while($chapterOffset < $entry->msDuration){
			$chapter = new KalturaThumbCuePoint();
			$chapter->entryId = $entry->id;
			$chapter->subType = KalturaThumbCuePointSubType::CHAPTER;
			$chapter->startTime = $chapterOffset;
			
			$cuePointPlugin->cuePoint->add($chapter);
			$chapterOffset += $chapterInterval;
			$chaptersCount ++;
		}
		
		$client->setResponseProfile($nestedResponseProfile);
		$entry = $client->media->get($entry->id);
		
		$this->assertNotNull($entry->relatedObjects);
		
		$this->assertArrayHasKey($metadataResponseProfile1->name, $entry->relatedObjects);
		$this->assertType('KalturaMetadataListResponse', $entry->relatedObjects[$metadataResponseProfile1->name]);
		$this->assertEquals(1, count($entry->relatedObjects[$metadataResponseProfile1->name]->objects));
		foreach($entry->relatedObjects[$metadataResponseProfile1->name]->objects as $metadata)
		{
			/* @var $metadata KalturaMetadata */
			$this->assertType('KalturaMetadata', $metadata);
			$this->assertEquals($entry->id, $metadata->objectId);
			$this->assertEquals($metadataProfile1->id, $metadata->metadataProfileId);
		}
	
		
		$this->assertArrayHasKey($metadataResponseProfile2->name, $entry->relatedObjects);
		$this->assertType('KalturaMetadataListResponse', $entry->relatedObjects[$metadataResponseProfile2->name]);
		$this->assertEquals(1, count($entry->relatedObjects[$metadataResponseProfile2->name]->objects));
		foreach($entry->relatedObjects[$metadataResponseProfile2->name]->objects as $metadata)
		{
			/* @var $metadata KalturaMetadata */
			$this->assertType('KalturaMetadata', $metadata);
			$this->assertEquals($entry->id, $metadata->objectId);
			$this->assertEquals($metadataProfile2->id, $metadata->metadataProfileId);
		}
	
		
		$this->assertArrayHasKey($flavorsResponseProfile->name, $entry->relatedObjects);
		$this->assertType('KalturaFlavorAssetListResponse', $entry->relatedObjects[$flavorsResponseProfile->name]);
		$this->assertGreaterThanOrEqual(1, count($entry->relatedObjects[$flavorsResponseProfile->name]->objects));
		foreach($entry->relatedObjects[$flavorsResponseProfile->name]->objects as $asset)
		{
			/* @var $asset KalturaFlavorAsset */
			$this->assertType('KalturaFlavorAsset', $asset);
			$this->assertEquals($entry->id, $asset->entryId);
		}
			
		
		$this->assertArrayHasKey($captionsResponseProfile->name, $entry->relatedObjects);
		$this->assertType('KalturaCaptionAssetListResponse', $entry->relatedObjects[$captionsResponseProfile->name]);
		$this->assertEquals(2, count($entry->relatedObjects[$captionsResponseProfile->name]->objects));
		foreach($entry->relatedObjects[$captionsResponseProfile->name]->objects as $asset)
		{
			/* @var $asset KalturaCaptionAsset */
			$this->assertType('KalturaCaptionAsset', $asset);
			$this->assertEquals($entry->id, $asset->entryId);
		}
			
		
		$this->assertArrayHasKey($thumbResponseProfile->name, $entry->relatedObjects);
		$this->assertType('KalturaThumbAssetListResponse', $entry->relatedObjects[$thumbResponseProfile->name]);
		$this->assertEquals(1, count($entry->relatedObjects[$thumbResponseProfile->name]->objects));
		foreach($entry->relatedObjects[$thumbResponseProfile->name]->objects as $asset)
		{
			/* @var $asset KalturaThumbAsset */
			$this->assertType('KalturaThumbAsset', $asset);
			$this->assertEquals($entry->id, $asset->entryId);
		}
			
		
		$this->assertArrayHasKey($chaptersResponseProfile->name, $entry->relatedObjects);
		$this->assertType('KalturaCuePointListResponse', $entry->relatedObjects[$chaptersResponseProfile->name]);
		$this->assertEquals($chaptersCount, count($entry->relatedObjects[$chaptersResponseProfile->name]->objects));
		foreach($entry->relatedObjects[$chaptersResponseProfile->name]->objects as $asset)
		{
			/* @var $asset KalturaThumbCuePoint */
			$this->assertType('KalturaThumbCuePoint', $asset);
			$this->assertEquals($entry->id, $asset->entryId);
		}
	}

	public function testCategoryWithMetadataUserAndEntriesDetached()
	{
		$entriesTotalCount = 2;
		$entriesPageSize = 2;
		
		$client = $this->getClient(KalturaSessionType::ADMIN);
		
		$category = new KalturaCategory();
		$category->name = uniqid('test_');
		$category->tags = $this->uniqueTag;
		$category->inheritanceType = KalturaInheritanceType::MANUAL;
		$category = $client->category->add($category);
		$this->createdCategories[$category->id] = $category->id;
		
		$user = $this->createUser();
		
		$categoryUser = new KalturaCategoryUser();
		$categoryUser->categoryId = $category->id;
		$categoryUser->userId = $user->id;
		$client->categoryUser->add($categoryUser);
		
		$xsd = '<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	<xsd:element name="metadata">
		<xsd:complexType>
			<xsd:sequence>
				<xsd:element name="Choice" minOccurs="0" maxOccurs="1">
					<xsd:annotation>
						<xsd:documentation></xsd:documentation>
						<xsd:appinfo>
							<label>Example choice</label>
							<key>choice</key>
							<searchable>true</searchable>
							<description>Example choice</description>
						</xsd:appinfo>
					</xsd:annotation>
					<xsd:simpleType>
						<xsd:restriction base="listType">
							<xsd:enumeration value="on" />
							<xsd:enumeration value="off" />
						</xsd:restriction>
					</xsd:simpleType>
				</xsd:element>
				<xsd:element name="FreeText" minOccurs="0" maxOccurs="1" type="textType">
					<xsd:annotation>
						<xsd:documentation></xsd:documentation>
						<xsd:appinfo>
							<label>Free text</label>
							<key>freeText</key>
							<searchable>true</searchable>
							<description>Free text</description>
						</xsd:appinfo>
					</xsd:annotation>
				</xsd:element>
			</xsd:sequence>
		</xsd:complexType>
	</xsd:element>
	<xsd:complexType name="textType">
		<xsd:simpleContent>
			<xsd:extension base="xsd:string" />
		</xsd:simpleContent>
	</xsd:complexType>
	<xsd:complexType name="objectType">
		<xsd:simpleContent>
			<xsd:extension base="xsd:string" />
		</xsd:simpleContent>
	</xsd:complexType>
	<xsd:simpleType name="listType">
		<xsd:restriction base="xsd:string" />
	</xsd:simpleType>
</xsd:schema>';
				
		$entryMetadataProfile1 = $this->createMetadataProfile(KalturaMetadataObjectType::ENTRY, $xsd);
		$entryMetadataProfile2 = $this->createMetadataProfile(KalturaMetadataObjectType::ENTRY, $xsd);
		$categoryMetadataProfile1 = $this->createMetadataProfile(KalturaMetadataObjectType::CATEGORY, $xsd);
		$categoryMetadataProfile2 = $this->createMetadataProfile(KalturaMetadataObjectType::CATEGORY, $xsd);
		
		$xml = '<metadata>
	<Choice>on</Choice>
	<FreeText>example text</FreeText>
</metadata>';
		
		$this->createMetadata($categoryMetadataProfile1->id, KalturaMetadataObjectType::CATEGORY, $category->id, $xml);
		$this->createMetadata($categoryMetadataProfile2->id, KalturaMetadataObjectType::CATEGORY, $category->id, $xml);
	
		$categoryEntries = $this->addEntriesToCategory($category->id, $entriesTotalCount);
		foreach($categoryEntries as $categoryEntry){
			/* @var $categoryEntry KalturaCategoryEntry */
			$this->createMetadata($entryMetadataProfile1->id, KalturaMetadataObjectType::ENTRY, $categoryEntry->entryId, $xml);
			$this->createMetadata($entryMetadataProfile2->id, KalturaMetadataObjectType::ENTRY, $categoryEntry->entryId, $xml);
		}
		
		$categoryMetadataFilter1 = new KalturaMetadataFilter();
		$categoryMetadataFilter1->metadataObjectTypeEqual = KalturaMetadataObjectType::CATEGORY;
		$categoryMetadataFilter1->metadataProfileIdEqual = $categoryMetadataProfile1->id;
		
		$categoryMetadataFilter2 = new KalturaMetadataFilter();
		$categoryMetadataFilter2->metadataObjectTypeEqual = KalturaMetadataObjectType::CATEGORY;
		$categoryMetadataFilter2->metadataProfileIdEqual = $categoryMetadataProfile2->id;
		
		$metadataMapping = new KalturaResponseProfileMapping();
		$metadataMapping->filterProperty = 'objectIdEqual';
		$metadataMapping->parentProperty = 'id';
		
		$metadataResponseProfile1 = new KalturaDetachedResponseProfile();
		$metadataResponseProfile1->name = uniqid('test_');
		$metadataResponseProfile1->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$metadataResponseProfile1->fields = 'id,objectId,metadataProfileId';
		$metadataResponseProfile1->filter = $categoryMetadataFilter1;
		$metadataResponseProfile1->mappings = array($metadataMapping);
		
		$metadataResponseProfile2 = new KalturaDetachedResponseProfile();
		$metadataResponseProfile2->name = uniqid('test_');
		$metadataResponseProfile2->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$metadataResponseProfile2->fields = 'id,objectId,metadataProfileId';
		$metadataResponseProfile2->filter = $categoryMetadataFilter2;
		$metadataResponseProfile2->mappings = array($metadataMapping);
		
		$categoryMapping = new KalturaResponseProfileMapping();
		$categoryMapping->filterProperty = 'categoryIdEqual';
		$categoryMapping->parentProperty = 'id';
		
		$categoryUserFilter = new KalturaCategoryUserFilter();
		$categoryUserFilter->userIdEqual = $user->id;
		
		$categoryUserPager = new KalturaFilterPager();
		$categoryUserPager->pageSize = 1;
		
		$categoryUserResponseProfile = new KalturaDetachedResponseProfile();
		$categoryUserResponseProfile->name = uniqid('test_');
		$categoryUserResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$categoryUserResponseProfile->fields = 'categoryId, userId, status';
		$categoryUserResponseProfile->filter = $categoryUserFilter;
		$categoryUserResponseProfile->pager = $categoryUserPager;
		$categoryUserResponseProfile->mappings = array($categoryMapping);
		
		$entryMapping = new KalturaResponseProfileMapping();
		$entryMapping->filterProperty = 'categoryAncestorIdIn';
		$entryMapping->parentProperty = 'id';
		
		$entryFilter = new KalturaMediaEntryFilter();
		$entryFilter->tagsLike = $this->uniqueTag;
		$entryFilter->statusIn = implode(',', array(
			KalturaEntryStatus::PENDING,
			KalturaEntryStatus::NO_CONTENT,
		));
		
		$entryPager = new KalturaFilterPager();
		$entryPager->pageSize = $entriesPageSize;
		
		$entryMetadataFilter = new KalturaMetadataFilter();
		$entryMetadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
		
		$entryMetadataResponseProfile = new KalturaDetachedResponseProfile();
		$entryMetadataResponseProfile->name = uniqid('test_');
		$entryMetadataResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$entryMetadataResponseProfile->fields = 'id,objectId,metadataProfileId';
		$entryMetadataResponseProfile->filter = $entryMetadataFilter;
		$entryMetadataResponseProfile->mappings = array($metadataMapping);
		
		$entryResponseProfile = new KalturaDetachedResponseProfile();
		$entryResponseProfile->name = uniqid('test_');
		$entryResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$entryResponseProfile->fields = 'id,entryId';
		$entryResponseProfile->filter = $entryFilter;
		$entryResponseProfile->pager = $entryPager;
		$entryResponseProfile->mappings = array($entryMapping);
		$entryResponseProfile->relatedProfiles = array($entryMetadataResponseProfile);
		
		$nestedResponseProfile = new KalturaDetachedResponseProfile();
		$nestedResponseProfile->name = uniqid('test_');
		$nestedResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$nestedResponseProfile->fields = 'id,name,privacyContext';
		$nestedResponseProfile->relatedProfiles = array(
			$metadataResponseProfile1, 
			$metadataResponseProfile2,
			$categoryUserResponseProfile,
			$entryResponseProfile,
		);
		
		$client->setResponseProfile($nestedResponseProfile);
		$category = $client->category->get($category->id);
		
		$this->assertNotNull($category->relatedObjects);
		
		$this->assertArrayHasKey($metadataResponseProfile1->name, $category->relatedObjects);
		$this->assertInstanceOf('KalturaMetadataListResponse', $category->relatedObjects[$metadataResponseProfile1->name]);
		$this->assertEquals(1, count($category->relatedObjects[$metadataResponseProfile1->name]->objects));
		foreach($category->relatedObjects[$metadataResponseProfile1->name]->objects as $metadata)
		{
			/* @var $metadata KalturaMetadata */
//			$this->assertType('KalturaMetadata', $metadata);
			$this->assertEquals($category->id, $metadata->objectId);
			$this->assertEquals($categoryMetadataProfile1->id, $metadata->metadataProfileId);
		}
	
		
		$this->assertArrayHasKey($metadataResponseProfile2->name, $category->relatedObjects);
//		$this->assertType('KalturaMetadataListResponse', $category->relatedObjects[$metadataResponseProfile2->name]);
		$this->assertEquals(1, count($category->relatedObjects[$metadataResponseProfile2->name]->objects));
		foreach($category->relatedObjects[$metadataResponseProfile2->name]->objects as $metadata)
		{
			/* @var $metadata KalturaMetadata */
//			$this->assertType('KalturaMetadata', $metadata);
			$this->assertEquals($category->id, $metadata->objectId);
			$this->assertEquals($categoryMetadataProfile2->id, $metadata->metadataProfileId);
		}
	
		
		$this->assertArrayHasKey($categoryUserResponseProfile->name, $category->relatedObjects);
//		$this->assertType('KalturaCategoryUserListResponse', $category->relatedObjects[$categoryUserResponseProfile->name]);
		$this->assertGreaterThanOrEqual(1, count($category->relatedObjects[$categoryUserResponseProfile->name]->objects));
		foreach($category->relatedObjects[$categoryUserResponseProfile->name]->objects as $categoryUser)
		{
			/* @var $categoryUser KalturaCategoryUser */
//			$this->assertType('KalturaCategoryUser', $categoryUser);
			$this->assertEquals($category->id, $categoryUser->categoryId);
			$this->assertEquals($user->id, $categoryUser->userId);
			$this->assertEquals(KalturaCategoryUserStatus::ACTIVE, $categoryUser->status);
		}
			
		
		$this->assertArrayHasKey($entryResponseProfile->name, $category->relatedObjects);
//		$this->assertType('KalturaBaseEntryListResponse', $category->relatedObjects[$entryResponseProfile->name]);
		$this->assertEquals($entriesPageSize, count($category->relatedObjects[$entryResponseProfile->name]->objects));
		foreach($category->relatedObjects[$entryResponseProfile->name]->objects as $entry)
		{
			/* @var $entry KalturaMediaEntry */
//			$this->assertType('KalturaMediaEntry', $entry);
		
			$this->assertArrayHasKey($entryMetadataResponseProfile->name, $entry->relatedObjects);
//			$this->assertType('KalturaMetadataListResponse', $entry->relatedObjects[$entryMetadataResponseProfile->name]);
			$this->assertEquals(2, count($entry->relatedObjects[$entryMetadataResponseProfile->name]->objects));
			foreach($entry->relatedObjects[$entryMetadataResponseProfile->name]->objects as $metadata)
			{
				/* @var $metadata KalturaMetadata */
//				$this->assertType('KalturaMetadata', $metadata);
				$this->assertEquals($entry->id, $metadata->objectId);
				$this->assertTrue(in_array($metadata->metadataProfileId, array($entryMetadataProfile1->id, $entryMetadataProfile2->id)));
			}
		}
	}

	public function _testEntitledCategoryWithMetadataUserAndEntries()
	{
		$entriesTotalCount = 2;
		$entriesPageSize = 2;
		
		$privacyContext = uniqid('test');
		$client = $this->getClient(KalturaSessionType::ADMIN);
		
		$category = new KalturaCategory();
		$category->name = uniqid('test_');
		$category->tags = $this->uniqueTag;
		$category->inheritanceType = KalturaInheritanceType::MANUAL;
		$category->privacyContext = $privacyContext;
		$category = $client->category->add($category);
		$this->createdCategories[$category->id] = $category->id;
		
		$user = $this->createUser();
		
		$categoryUser = new KalturaCategoryUser();
		$categoryUser->categoryId = $category->id;
		$categoryUser->userId = $user->id;
		$client->categoryUser->add($categoryUser);
		
		$xsd = '<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	<xsd:element name="metadata">
		<xsd:complexType>
			<xsd:sequence>
				<xsd:element name="Choice" minOccurs="0" maxOccurs="1">
					<xsd:annotation>
						<xsd:documentation></xsd:documentation>
						<xsd:appinfo>
							<label>Example choice</label>
							<key>choice</key>
							<searchable>true</searchable>
							<description>Example choice</description>
						</xsd:appinfo>
					</xsd:annotation>
					<xsd:simpleType>
						<xsd:restriction base="listType">
							<xsd:enumeration value="on" />
							<xsd:enumeration value="off" />
						</xsd:restriction>
					</xsd:simpleType>
				</xsd:element>
				<xsd:element name="FreeText" minOccurs="0" maxOccurs="1" type="textType">
					<xsd:annotation>
						<xsd:documentation></xsd:documentation>
						<xsd:appinfo>
							<label>Free text</label>
							<key>freeText</key>
							<searchable>true</searchable>
							<description>Free text</description>
						</xsd:appinfo>
					</xsd:annotation>
				</xsd:element>
			</xsd:sequence>
		</xsd:complexType>
	</xsd:element>
	<xsd:complexType name="textType">
		<xsd:simpleContent>
			<xsd:extension base="xsd:string" />
		</xsd:simpleContent>
	</xsd:complexType>
	<xsd:complexType name="objectType">
		<xsd:simpleContent>
			<xsd:extension base="xsd:string" />
		</xsd:simpleContent>
	</xsd:complexType>
	<xsd:simpleType name="listType">
		<xsd:restriction base="xsd:string" />
	</xsd:simpleType>
</xsd:schema>';
				
		$entryMetadataProfile1 = $this->createMetadataProfile(KalturaMetadataObjectType::ENTRY, $xsd);
		$entryMetadataProfile2 = $this->createMetadataProfile(KalturaMetadataObjectType::ENTRY, $xsd);
		$categoryMetadataProfile1 = $this->createMetadataProfile(KalturaMetadataObjectType::CATEGORY, $xsd);
		$categoryMetadataProfile2 = $this->createMetadataProfile(KalturaMetadataObjectType::CATEGORY, $xsd);
		
		$xml = '<metadata>
	<Choice>on</Choice>
	<FreeText>example text</FreeText>
</metadata>';
		
		$this->createMetadata($categoryMetadataProfile1->id, KalturaMetadataObjectType::CATEGORY, $category->id, $xml);
		$this->createMetadata($categoryMetadataProfile2->id, KalturaMetadataObjectType::CATEGORY, $category->id, $xml);
	
		$categoryEntries = $this->addEntriesToCategory($category->id, $entriesTotalCount);
		foreach($categoryEntries as $categoryEntry){
			/* @var $categoryEntry KalturaCategoryEntry */
			$this->createMetadata($entryMetadataProfile1->id, KalturaMetadataObjectType::ENTRY, $categoryEntry->entryId, $xml);
			$this->createMetadata($entryMetadataProfile2->id, KalturaMetadataObjectType::ENTRY, $categoryEntry->entryId, $xml);
		}
		
		$categoryMetadataFilter1 = new KalturaMetadataFilter();
		$categoryMetadataFilter1->metadataObjectTypeEqual = KalturaMetadataObjectType::CATEGORY;
		$categoryMetadataFilter1->metadataProfileIdEqual = $categoryMetadataProfile1->id;
		
		$categoryMetadataFilter2 = new KalturaMetadataFilter();
		$categoryMetadataFilter2->metadataObjectTypeEqual = KalturaMetadataObjectType::CATEGORY;
		$categoryMetadataFilter2->metadataProfileIdEqual = $categoryMetadataProfile2->id;
		
		$metadataMapping = new KalturaResponseProfileMapping();
		$metadataMapping->filterProperty = 'objectIdEqual';
		$metadataMapping->parentProperty = 'id';
		
		$metadataResponseProfile1 = new KalturaDetachedResponseProfile();
		$metadataResponseProfile1->name = uniqid('test_');
		$metadataResponseProfile1->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$metadataResponseProfile1->fields = 'id,objectId,metadataProfileId';
		$metadataResponseProfile1->filter = $categoryMetadataFilter1;
		$metadataResponseProfile1->mappings = array($metadataMapping);
		
		$metadataResponseProfile2 = new KalturaDetachedResponseProfile();
		$metadataResponseProfile2->name = uniqid('test_');
		$metadataResponseProfile2->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$metadataResponseProfile2->fields = 'id,objectId,metadataProfileId';
		$metadataResponseProfile2->filter = $categoryMetadataFilter2;
		$metadataResponseProfile2->mappings = array($metadataMapping);
		
		$categoryMapping = new KalturaResponseProfileMapping();
		$categoryMapping->filterProperty = 'categoryIdEqual';
		$categoryMapping->parentProperty = 'id';
		
		$categoryUserFilter = new KalturaCategoryUserFilter();
		$categoryUserFilter->userIdEqual = $user->id;
		
		$categoryUserPager = new KalturaFilterPager();
		$categoryUserPager->pageSize = 1;
		
		$categoryUserResponseProfile = new KalturaDetachedResponseProfile();
		$categoryUserResponseProfile->name = uniqid('test_');
		$categoryUserResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$categoryUserResponseProfile->fields = 'categoryId, userId, status';
		$categoryUserResponseProfile->filter = $categoryUserFilter;
		$categoryUserResponseProfile->pager = $categoryUserPager;
		$categoryUserResponseProfile->mappings = array($categoryMapping);
		
		$entryMapping = new KalturaResponseProfileMapping();
		$entryMapping->filterProperty = 'categoryAncestorIdIn';
		$entryMapping->parentProperty = 'id';
		
		$entryFilter = new KalturaMediaEntryFilter();
		$entryFilter->tagsLike = $this->uniqueTag;
		$entryFilter->statusIn = implode(',', array(
			KalturaEntryStatus::PENDING,
			KalturaEntryStatus::NO_CONTENT,
		));
		
		$entryPager = new KalturaFilterPager();
		$entryPager->pageSize = $entriesPageSize;
		
		$entryMetadataFilter = new KalturaMetadataFilter();
		$entryMetadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
		
		$entryMetadataResponseProfile = new KalturaDetachedResponseProfile();
		$entryMetadataResponseProfile->name = uniqid('test_');
		$entryMetadataResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$entryMetadataResponseProfile->fields = 'id,objectId,metadataProfileId';
		$entryMetadataResponseProfile->filter = $entryMetadataFilter;
		$entryMetadataResponseProfile->mappings = array($metadataMapping);
		
		$entryResponseProfile = new KalturaDetachedResponseProfile();
		$entryResponseProfile->name = uniqid('test_');
		$entryResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$entryResponseProfile->fields = 'id,entryId';
		$entryResponseProfile->filter = $entryFilter;
		$entryResponseProfile->pager = $entryPager;
		$entryResponseProfile->mappings = array($entryMapping);
		$entryResponseProfile->relatedProfiles = array($entryMetadataResponseProfile);
		
		$responseProfile = new KalturaResponseProfile();
		$responseProfile->name = uniqid('test_');
		$responseProfile->systemName = uniqid('test_');
		$responseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$responseProfile->fields = 'id,name,privacyContext';
		$responseProfile->relatedProfiles = array(
			$metadataResponseProfile1, 
			$metadataResponseProfile2,
			$categoryUserResponseProfile,
			$entryResponseProfile,
		);
		
		$responseProfile = $this->add($responseProfile);
		
		$nestedResponseProfile = new KalturaResponseProfileHolder();
		$nestedResponseProfile->id = $responseProfile->id;
		
		$client = $this->getClient(KalturaSessionType::ADMIN, null, $user->id, 86400, "enableentitlement,privacycontext:$privacyContext");
		$client->setResponseProfile($nestedResponseProfile);
		$category = $client->category->get($category->id);
		
		$this->assertEquals($privacyContext, $category->privacyContext);
		$this->assertNotNull($category->relatedObjects);
		
		$this->assertArrayHasKey($metadataResponseProfile1->name, $category->relatedObjects);
		$this->assertType('KalturaMetadataListResponse', $category->relatedObjects[$metadataResponseProfile1->name]);
		$this->assertEquals(1, count($category->relatedObjects[$metadataResponseProfile1->name]->objects));
		foreach($category->relatedObjects[$metadataResponseProfile1->name]->objects as $metadata)
		{
			/* @var $metadata KalturaMetadata */
			$this->assertType('KalturaMetadata', $metadata);
			$this->assertEquals($category->id, $metadata->objectId);
			$this->assertEquals($categoryMetadataProfile1->id, $metadata->metadataProfileId);
		}
	
		
		$this->assertArrayHasKey($metadataResponseProfile2->name, $category->relatedObjects);
		$this->assertType('KalturaMetadataListResponse', $category->relatedObjects[$metadataResponseProfile2->name]);
		$this->assertEquals(1, count($category->relatedObjects[$metadataResponseProfile2->name]->objects));
		foreach($category->relatedObjects[$metadataResponseProfile2->name]->objects as $metadata)
		{
			/* @var $metadata KalturaMetadata */
			$this->assertType('KalturaMetadata', $metadata);
			$this->assertEquals($category->id, $metadata->objectId);
			$this->assertEquals($categoryMetadataProfile2->id, $metadata->metadataProfileId);
		}
	
		
		$this->assertArrayHasKey($categoryUserResponseProfile->name, $category->relatedObjects);
		$this->assertType('KalturaCategoryUserListResponse', $category->relatedObjects[$categoryUserResponseProfile->name]);
		$this->assertGreaterThanOrEqual(1, count($category->relatedObjects[$categoryUserResponseProfile->name]->objects));
		foreach($category->relatedObjects[$categoryUserResponseProfile->name]->objects as $categoryUser)
		{
			/* @var $categoryUser KalturaCategoryUser */
			$this->assertType('KalturaCategoryUser', $categoryUser);
			$this->assertEquals($category->id, $categoryUser->categoryId);
			$this->assertEquals($user->id, $categoryUser->userId);
			$this->assertEquals(KalturaCategoryUserStatus::ACTIVE, $categoryUser->status);
		}
			
		
		$this->assertArrayHasKey($entryResponseProfile->name, $category->relatedObjects);
		$this->assertType('KalturaBaseEntryListResponse', $category->relatedObjects[$entryResponseProfile->name]);
		$this->assertEquals($entriesPageSize, count($category->relatedObjects[$entryResponseProfile->name]->objects));
		foreach($category->relatedObjects[$entryResponseProfile->name]->objects as $entry)
		{
			/* @var $entry KalturaMediaEntry */
			$this->assertType('KalturaMediaEntry', $entry);
		
			$this->assertArrayHasKey($entryMetadataResponseProfile->name, $entry->relatedObjects);
			$this->assertType('KalturaMetadataListResponse', $entry->relatedObjects[$entryMetadataResponseProfile->name]);
			$this->assertEquals(2, count($entry->relatedObjects[$entryMetadataResponseProfile->name]->objects));
			foreach($entry->relatedObjects[$entryMetadataResponseProfile->name]->objects as $metadata)
			{
				/* @var $metadata KalturaMetadata */
				$this->assertType('KalturaMetadata', $metadata);
				$this->assertEquals($entry->id, $metadata->objectId);
				$this->assertTrue(in_array($metadata->metadataProfileId, array($entryMetadataProfile1->id, $entryMetadataProfile2->id)));
			}
		}
	}
	
	public function testCategoryWithTooManyLevelsDetached()
	{
		$client = $this->getClient(KalturaSessionType::ADMIN);
		
		$xsd = '<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	<xsd:element name="metadata">
		<xsd:complexType>
			<xsd:sequence>
				<xsd:element name="Choice" minOccurs="0" maxOccurs="1">
					<xsd:annotation>
						<xsd:documentation></xsd:documentation>
						<xsd:appinfo>
							<label>Example choice</label>
							<key>choice</key>
							<searchable>true</searchable>
							<description>Example choice</description>
						</xsd:appinfo>
					</xsd:annotation>
					<xsd:simpleType>
						<xsd:restriction base="listType">
							<xsd:enumeration value="on" />
							<xsd:enumeration value="off" />
						</xsd:restriction>
					</xsd:simpleType>
				</xsd:element>
				<xsd:element name="FreeText" minOccurs="0" maxOccurs="1" type="textType">
					<xsd:annotation>
						<xsd:documentation></xsd:documentation>
						<xsd:appinfo>
							<label>Free text</label>
							<key>freeText</key>
							<searchable>true</searchable>
							<description>Free text</description>
						</xsd:appinfo>
					</xsd:annotation>
				</xsd:element>
			</xsd:sequence>
		</xsd:complexType>
	</xsd:element>
	<xsd:complexType name="textType">
		<xsd:simpleContent>
			<xsd:extension base="xsd:string" />
		</xsd:simpleContent>
	</xsd:complexType>
	<xsd:complexType name="objectType">
		<xsd:simpleContent>
			<xsd:extension base="xsd:string" />
		</xsd:simpleContent>
	</xsd:complexType>
	<xsd:simpleType name="listType">
		<xsd:restriction base="xsd:string" />
	</xsd:simpleType>
</xsd:schema>';
				
		$cuePointMetadataProfile = $this->createMetadataProfile(KalturaMetadataObjectType::ANNOTATION, $xsd);
		
		$xml = '<metadata>
	<Choice>on</Choice>
	<FreeText>example text</FreeText>
</metadata>';
		
		$category = $this->createCategory();
		$categoryEntry = $this->addCategoryToEntry(null, $category->id);
		
		$annotation = new KalturaAnnotation();
		$annotation->entryId = $categoryEntry->entryId;
		$annotation->startTime = 0;
		$annotation->text = uniqid('test_');
		
		$cuePointPlugin = KalturaCuePointClientPlugin::get($client);
		$annotation = $cuePointPlugin->cuePoint->add($annotation);
		
		$this->createMetadata($cuePointMetadataProfile->id, KalturaMetadataObjectType::ANNOTATION, $annotation->id, $xml);
	
		$metadataFilter = new KalturaMetadataFilter();
		$metadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::ANNOTATION;
		
		$metadataMapping = new KalturaResponseProfileMapping();
		$metadataMapping->filterProperty = 'objectIdEqual';
		$metadataMapping->parentProperty = 'id';
		
		$metadataResponseProfile = new KalturaDetachedResponseProfile();
		$metadataResponseProfile->name = uniqid('test_');
		$metadataResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$metadataResponseProfile->fields = 'id,objectId,metadataProfileId';
		$metadataResponseProfile->filter = $metadataFilter;
		$metadataResponseProfile->mappings = array($metadataMapping);
		
		$cuePointFilter = new KalturaAnnotationFilter();
		
		$cuePointMapping = new KalturaResponseProfileMapping();
		$cuePointMapping->filterProperty = 'entryIdEqual';
		$cuePointMapping->parentProperty = 'id';
		
		$cuePointResponseProfile = new KalturaDetachedResponseProfile();
		$cuePointResponseProfile->name = uniqid('test_');
		$cuePointResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$cuePointResponseProfile->fields = 'id,objectId,metadataProfileId';
		$cuePointResponseProfile->filter = $cuePointFilter;
		$cuePointResponseProfile->mappings = array($cuePointMapping);
		$cuePointResponseProfile->relatedProfiles = array($metadataResponseProfile);
		
		$entryMapping = new KalturaResponseProfileMapping();
		$entryMapping->filterProperty = 'categoryAncestorIdIn';
		$entryMapping->parentProperty = 'id';
		
		$entryFilter = new KalturaMediaEntryFilter();
		$entryFilter->tagsLike = $this->uniqueTag;
		$entryFilter->statusIn = implode(',', array(
			KalturaEntryStatus::PENDING,
			KalturaEntryStatus::NO_CONTENT,
		));
		
		$entryResponseProfile = new KalturaDetachedResponseProfile();
		$entryResponseProfile->name = uniqid('test_');
		$entryResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$entryResponseProfile->fields = 'id,entryId';
		$entryResponseProfile->filter = $entryFilter;
		$entryResponseProfile->mappings = array($entryMapping);
		$entryResponseProfile->relatedProfiles = array($cuePointResponseProfile);
		
		$nestedResponseProfile = new KalturaDetachedResponseProfile();
		$nestedResponseProfile->name = uniqid('test_');
		$nestedResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$nestedResponseProfile->fields = 'id,name,privacyContext';
		$nestedResponseProfile->relatedProfiles = array(
			$entryResponseProfile,
		);
		
		$client->setResponseProfile($nestedResponseProfile);
		try{
			$category = $client->category->get($category->id);
			$this->fail('Exception RESPONSE_PROFILE_MAX_NESTING_LEVEL expected');
		}
		catch(KalturaException $e){
			$this->assertEquals('RESPONSE_PROFILE_MAX_NESTING_LEVEL', $e->getCode());
		}
	}

	
	public function testTooManyLevels()
	{
		$metadataFilter = new KalturaMetadataFilter();
		$metadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::ANNOTATION;
		
		$metadataMapping = new KalturaResponseProfileMapping();
		$metadataMapping->filterProperty = 'objectIdEqual';
		$metadataMapping->parentProperty = 'id';
		
		$metadataResponseProfile = new KalturaDetachedResponseProfile();
		$metadataResponseProfile->name = uniqid('test_');
		$metadataResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$metadataResponseProfile->fields = 'id,objectId,metadataProfileId';
		$metadataResponseProfile->filter = $metadataFilter;
		$metadataResponseProfile->mappings = array($metadataMapping);
		
		$cuePointFilter = new KalturaAnnotationFilter();
		
		$cuePointMapping = new KalturaResponseProfileMapping();
		$cuePointMapping->filterProperty = 'entryIdEqual';
		$cuePointMapping->parentProperty = 'id';
		
		$cuePointResponseProfile = new KalturaDetachedResponseProfile();
		$cuePointResponseProfile->name = uniqid('test_');
		$cuePointResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$cuePointResponseProfile->fields = 'id,objectId,metadataProfileId';
		$cuePointResponseProfile->filter = $cuePointFilter;
		$cuePointResponseProfile->mappings = array($cuePointMapping);
		$cuePointResponseProfile->relatedProfiles = array($metadataResponseProfile);
		
		$entryMapping = new KalturaResponseProfileMapping();
		$entryMapping->filterProperty = 'categoryAncestorIdIn';
		$entryMapping->parentProperty = 'id';
		
		$entryFilter = new KalturaMediaEntryFilter();
		$entryFilter->tagsLike = $this->uniqueTag;
		$entryFilter->statusIn = implode(',', array(
			KalturaEntryStatus::PENDING,
			KalturaEntryStatus::NO_CONTENT,
		));
		
		$entryResponseProfile = new KalturaDetachedResponseProfile();
		$entryResponseProfile->name = uniqid('test_');
		$entryResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$entryResponseProfile->fields = 'id,entryId';
		$entryResponseProfile->filter = $entryFilter;
		$entryResponseProfile->mappings = array($entryMapping);
		$entryResponseProfile->relatedProfiles = array($cuePointResponseProfile);
		
		$responseProfile = new KalturaResponseProfile();
		$responseProfile->name = uniqid('test_');
		$responseProfile->systemName = uniqid('test_');
		$responseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$responseProfile->fields = 'id,name,privacyContext';
		$responseProfile->relatedProfiles = array(
			$entryResponseProfile,
		);
		
		try{
			$this->add($responseProfile);
			$this->fail('Exception RESPONSE_PROFILE_MAX_NESTING_LEVEL expected');
		}
		catch(KalturaException $e){
			$this->assertEquals('RESPONSE_PROFILE_MAX_NESTING_LEVEL', $e->getCode());
		}
	}
}

