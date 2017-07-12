<?php

require_once(dirname(__FILE__) . '/RandomElasticDocVerificationBaseTest.php');

class RandomElasticDocVerificationKuserTest extends RandomElasticDocVerificationBaseTest
{
	protected  function getSearchTermsConstantsClassName()
	{
		return 'KalturaESearchKuserFieldName';
	}

	protected  function getIndexName()
	{
		return ElasticConstants::KUSER_INDEX_NAME;
	}

	protected  function getIndexType()
	{
		return ElasticConstants::KUSER_INDEX_TYPE;
	}

	protected function isInIgnoreList($fieldName)
	{
		// TODO fill with real values
		return false;
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

