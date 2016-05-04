<?php

require_once __DIR__ . '/../KalturaApiTestCase.php';
require_once '/opt/kaltura/web/content/clientlibs/testsClient/KalturaPlugins/KalturaMetadataClientPlugin.php';
require_once '/opt/kaltura/web/content/clientlibs/testsClient/KalturaPlugins/KalturaBulkUploadClientPlugin.php';

/**
 * ScheduleEvent service test case.
 */
class TemplateEntryTest extends KalturaApiTestCase
{
	/* (non-PHPdoc)
	 * @see KalturaApiTestCase::getAdminClient()
	 */
	protected function getAdminClient()
	{
		return $this->getClient(KalturaSessionType::ADMIN, null, uniqid('test'));
	}

	protected function getAlternateAdminClient()
	{
		return $this->getAlternateClient(KalturaSessionType::ADMIN);
	}

	protected function validateEntry(KalturaMediaEntry $templateEntry, KalturaMediaEntry $createdEntry)
	{
		$this->assertEquals($templateEntry->id, $createdEntry->templateEntryId);
		
		if($templateEntry->description)
			$this->assertEquals($templateEntry->description, $createdEntry->description);

		if($templateEntry->tags)
			$this->assertEquals($templateEntry->tags, $createdEntry->tags);

		if($templateEntry->name)
			$this->assertEquals($templateEntry->name, $createdEntry->name);

// 		if($templateEntry->userId)
// 			$this->assertEquals($templateEntry->userId, $createdEntry->userId);

		if($templateEntry->accessControlId)
			$this->assertEquals($templateEntry->accessControlId, $createdEntry->accessControlId);

		if($templateEntry->conversionProfileId)
			$this->assertEquals($templateEntry->conversionProfileId, $createdEntry->conversionProfileId);
	}

	/**
	 * @return KalturaMediaEntry
	 */
	protected function createTemplateEntry()
	{
		$accessControlProfile = $this->createAccessControlProfile();
		$conversionProfile = $this->createConversionProfile();
		
		return $this->createEntry(null, array(
			'accessControlId' => $accessControlProfile->id,
			'conversionProfileId' => $conversionProfile->id,
		));
	}

	public function testEntry()
	{
		$templateEntry = $this->createTemplateEntry();

		$entry = new KalturaMediaEntry();
		$entry->mediaType = KalturaMediaType::VIDEO;
		$entry->templateEntryId = $templateEntry->id;
		
		$createdEntry = $this->addEntry($entry);
		$this->validateEntry($templateEntry, $createdEntry);
	}

	public function _testEntryWithMetadata()
	{
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
				
		$xml = '<metadata>
	<Choice>on</Choice>
	<FreeText>example text</FreeText>
</metadata>';
		
		$templateEntry = $this->createTemplateEntry();
		$metadataProfile = $this->createMetadataProfile(KalturaMetadataObjectType::ENTRY, $xsd);
		$this->createMetadata($metadataProfile->id, KalturaMetadataObjectType::ENTRY, $templateEntry->id, $xml);

		$entry = new KalturaMediaEntry();
		$entry->mediaType = KalturaMediaType::VIDEO;
		$entry->templateEntryId = $templateEntry->id;
		
		$createdEntry = $this->addEntry($entry);
		$this->validateEntry($templateEntry, $createdEntry);

		$client = $this->getClient();
		$metadataPlugin = KalturaMetadataClientPlugin::get($client);
		
		$metadataFilter = new KalturaMetadataFilter();
		$metadataFilter->objectIdEqual = $createdEntry->id;
		$metadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
		
		$metadataList = $metadataPlugin->metadata->listAction($metadataFilter);
		$metadata = reset($metadataList->objects);
		$this->assertEquals($xml, $metadata->xml);
	}

	public function _testConversionProfile()
	{
		$client = $this->getAdminClient();

		$accessControlProfile = $this->createAccessControlProfile();
		$templateEntry = $this->createEntry(null, array(
				'accessControlId' => $accessControlProfile->id,
		));
		
		$defaultConversionProfile = $client->conversionProfile->getDefault();
		
		$conversionProfile = new KalturaConversionProfile();
		$conversionProfile->name = uniqid('test_');
		$conversionProfile->defaultEntryId = $templateEntry->id;
		$conversionProfile->flavorParamsIds = $defaultConversionProfile->flavorParamsIds;
		
		$createdConversionProfile = $client->conversionProfile->add($conversionProfile);
		
		$entry = new KalturaMediaEntry();
		$entry->mediaType = KalturaMediaType::VIDEO;
		$entry->conversionProfileId = $createdConversionProfile->id;
		
		$createdEntry = $this->addEntry($entry);
		$this->validateEntry($templateEntry, $createdEntry);
	}

	protected function addXmlBulkUpload($input)
	{
		$client = $this->getAdminClient();
		$plugin = KalturaBulkUploadClientPlugin::get($client);
	
		$inputXml = new SimpleXMLElement($input);

		$filename = tempnam(sys_get_temp_dir(), 'bulk.') . '.xml';
		file_put_contents($filename, $input);
	
		$bulkUploadData = new KalturaBulkUploadXmlJobData();
		$bulkUpload = $client->media->bulkUploadAdd($filename, $bulkUploadData);
		$logUrl = $client->bulkUpload->serveLog($bulkUpload->id);
	
		$output = null;
		while(!$output)
		{
			echo "Fetching log.\n";
			$log = file_get_contents($logUrl);
			if(strpos($log, 'Log file is not ready') > 0)
			{
				sleep(2);
				continue;
			}
			
			$output = $log;
		}
		var_dump($output);

		$inputItems = array();
		foreach($inputXml->channel->item as $inputItem)
		{
			$inputItems[] = $inputItem;
		}
		
		$outputXml = new SimpleXMLElement($output);
		$index = -1;
		foreach($outputXml->channel->item as $outputItem)
		{
			$index++;
			$inputItem = $inputItems[$index];
	
			$this->assertGreaterThan(0, strlen("{$outputItem->entryId}"));
			if(isset($inputItem->action) && intval($inputItem->action) == KalturaBulkUploadAction::DELETE)
			{
				try{
					$client->media->get("{$outputItem->entryId}");
					$this->fail('Exception INVALID_OBJECT_ID expected');
				}
				catch(Exception $e) {
					$this->assertEquals('INVALID_OBJECT_ID', $e->getCode());
				}
				continue;
			}
			$entry = $client->media->get("{$outputItem->entryId}");
	
			$this->createdEntries[$entry->id] = $entry->id;
				
			$this->assertEquals($this->partnerId, $entry->partnerId);
				
			if(isset($inputItem->name))
				$this->assertEquals("{$inputItem->name}", $entry->title);
	
			$this->assertEquals($entry->status, intval($outputItem->status));
			if(isset($outputItem->result->errorDescription) && strlen("{$outputItem->result->errorDescription}"))
				$this->fail("{$outputItem->result->errorDescription}");
		}
	
		return $output;
	}
	
	protected function addCsvBulkUpload($columns, $input)
	{
		$client = $this->getAdminClient();
		$plugin = KalturaBulkUploadClientPlugin::get($client);
	
		$data = array();
		foreach($input as $row)
		{
			$data[] = array_combine($columns, $row);
		}
		$rows = array(
				'*' . implode(",", $columns)
		);
		foreach($data as $item)
		{
			$rows[] = implode(",", $item);
		}
		$content = implode("\n", $rows) . "\n";
	
		$filename = tempnam(sys_get_temp_dir(), 'bulk.') . '.csv';
		file_put_contents($filename, $content);
	
		$bulkUpload = $client->media->bulkUploadAdd($filename);
		$logUrl = $client->bulkUpload->serveLog($bulkUpload->id);
	
		$output = array();
		while(!count($output))
		{
			sleep(2);
			
			$bulkUpload = $client->bulkUpload->get($bulkUpload->id);
			if($bulkUpload->status == KalturaBatchJobStatus::FAILED || $bulkUpload->status == KalturaBatchJobStatus::FATAL)
				$this->fail("Bulk upload failed: " . $bulkUpload->error);
			
			if($bulkUpload->status != KalturaBatchJobStatus::FINISHED && $bulkUpload->status != KalturaBatchJobStatus::FINISHED_PARTIALLY)
				continue;
			
			echo "Fetching log.\n";
			$log = file_get_contents($logUrl);
			if($log === 'Log file is not ready')
			{
				continue;
			}
	
			$columns[] = 'resultStatus';
			$columns[] = 'objectId';
			$columns[] = 'objectStatus';
			$columns[] = 'errorDescription';
				
			$rows = str_getcsv(str_replace("\r", '', $log), "\n");
			foreach($rows as $row)
			{
				$output[] = array_combine($columns, str_getcsv($row));
			}
		}
		var_dump($output);
	
		foreach($output as $index => $rowOutput)
		{
			$rowInput = $data[$index];
	
			$this->assertGreaterThan(0, strlen($rowOutput['objectId']));
			if(isset($rowInput['action']) && $rowInput['action'] == KalturaBulkUploadAction::DELETE)
			{
				try{
					$client->media->get($rowOutput['objectId']);
					$this->fail('Exception INVALID_OBJECT_ID expected');
				}
				catch(Exception $e) {
					$this->assertEquals('INVALID_OBJECT_ID', $e->getCode());
				}
				continue;
			}
			$entry = $client->media->get($rowOutput['objectId']);
	
			$this->createdEntries[$entry->id] = $entry->id;
				
			$this->assertEquals($this->partnerId, $entry->partnerId);
				
			if(isset($rowInput['title']))
				$this->assertEquals($rowInput['title'], $entry->title);
	
			$this->assertEquals($entry->status, $rowOutput['objectStatus']);
			$this->assertNotEquals(KalturaBulkUploadResultStatus::ERROR, $rowOutput['resultStatus']);
		}
	
		return $output;
	}
	
	public function testCsvBulkUpload()
	{
		$client = $this->getClient();
		$templateEntry = $this->createTemplateEntry();

		$columns = array(
			'contentType',
			'url',
			'templateEntryId',
		);
	
		$url = 'http://www.kaltura.com/content/r71v1/entry/data/559/505/1_xalu6grp_1_ixrfmq3v_11.flv';
		$input = array(
			array('video', $url, $templateEntry->id),
			array('video', $url, $templateEntry->id),
		);
		
		$csv = $this->addCsvBulkUpload($columns, $input);
		foreach($csv as $index => $rowOutput)
		{
			$createdEntry = $client->media->get($rowOutput['objectId']);
			$this->validateEntry($templateEntry, $createdEntry);
		}
	}
	
	public function testCsvBulkUploadReferenceId()
	{
		$client = $this->getClient();
		
		$columns = array(
			'contentType',
			'url',
			'referenceId',
		);

		$url = 'http://www.kaltura.com/content/r71v1/entry/data/559/505/1_xalu6grp_1_ixrfmq3v_11.flv';
		$input = array(
			array('video', $url, 'referenceId' => uniqid()),
			array('video', $url, 'referenceId' => uniqid()),
		);
		
		$csv = $this->addCsvBulkUpload($columns, $input);
		foreach($csv as $index => $rowOutput)
		{
			$createdEntry = $client->media->get($rowOutput['objectId']);

			$this->assertEquals($input[$index]['referenceId'], $createdEntry->referenceId);
		}
	}

	public function _testXmlBulkUploadEntryId()
	{
		$client = $this->getAdminClient();
		$templateEntry = $this->createTemplateEntry();

		$url = 'http://www.kaltura.com/content/r71v1/entry/data/559/505/1_xalu6grp_1_ixrfmq3v_11.flv';
		$input = "<mrss xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">
	<channel>
		<item>
			<action>add</action>
			<type>1</type>
			<templateEntryId>{$templateEntry->id}</templateEntryId>
			<media>
				<mediaType>1</mediaType> 
			</media>
			<contentAssets>
				<content>
					<urlContentResource url=\"$url\"/>
				</content>
			</contentAssets>
		</item>
	</channel>
</mrss>";
		
		$output = $this->addXmlBulkUpload($input);
		$outputXml = new SimpleXMLElement($output);
		foreach($outputXml->channel->item as $outputItem)
		{
			$createdEntry = $client->media->get("{$outputItem->entryId}");
			$this->validateEntry($templateEntry, $createdEntry);
		}
	}

	public function _testXmlBulkUploadReferenceId()
	{
		$client = $this->getClient();
		$templateEntry = $this->createTemplateEntry();

		$url = 'http://www.kaltura.com/content/r71v1/entry/data/559/505/1_xalu6grp_1_ixrfmq3v_11.flv';
		$input = "<mrss xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">
	<channel>
		<item>
			<action>add</action>
			<type>1</type>
			<templateEntry>{$templateEntry->referenceId}</templateEntry>
			<media>
				<mediaType>1</mediaType> 
			</media>
			<contentAssets>
				<content>
					<urlContentResource url=\"$url\"/>
				</content>
			</contentAssets>
		</item>
	</channel>
</mrss>";
		
		$output = $this->addXmlBulkUpload($input);
		$outputXml = new SimpleXMLElement($output);
		foreach($outputXml->channel->item as $outputItem)
		{
			$createdEntry = $client->media->get("{$outputItem->entryId}");
			$this->validateEntry($templateEntry, $createdEntry);
		}
	}
}

