<?php

require_once(dirname(__FILE__) . '/RandomElasticDocVerificationBaseTest.php');

/**
 * Class ElasticVsKalturaServerEntryTest
 * This class is dedicated to the entry query and validation
 */
class RandomElasticDocVerificationEntryTest extends RandomElasticDocVerificationBaseTest
{
	const RANDOM_ENTRY_RETRIES = 10;

	protected  function getSearchTermsConstantsClassName()
	{
		return 'KalturaESearchEntryFieldName';
	}

	protected  function getIndexType()
	{
		return ElasticConstants::ENTRY_INDEX_TYPE;
	}

	protected function isInIgnoreList($fieldName)
	{
		switch ($fieldName)
		{
			case 'creator_kuser_id':
			case 'media_type': // TODO should find the best match for this type
			case 'duration': // TODO depends on bug PLAT-7578 duration mismatch
			case 'source_type':
				return true;
			default:
				return false;
		}
	}

	protected function getObjectFromElasticViaServer($fieldName, $fieldValue, $searchType, $apiClient)
	{
		$kalturaESearchOperator = new KalturaESearchOperator();
		$kalturaESearchOperator->searchItems = array();

		$kalturaESearchEntryItem = new KalturaESearchEntryItem();
		$kalturaESearchEntryItem->fieldName =$fieldName;
		$kalturaESearchEntryItem->itemType = $searchType;
		$kalturaESearchEntryItem->searchTerm = $fieldValue;
		array_push($kalturaESearchOperator->searchItems, $kalturaESearchEntryItem);

		$answer = $apiClient->eSearch->search($kalturaESearchOperator);
		return $answer;
	}

}

