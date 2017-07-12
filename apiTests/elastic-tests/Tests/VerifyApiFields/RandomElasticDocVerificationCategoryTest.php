<?php

require_once(dirname(__FILE__) . '/RandomElasticDocVerificationBaseTest.php');

/**
 * Class ElasticVsKalturaServerEntryTest
 * This class is dedicated to the entry query and validation
 */
class RandomElasticDocVerificationCategoryTest extends RandomElasticDocVerificationBaseTest
{
	protected  function getSearchTermsConstantsClassName()
	{
		return 'KalturaESearchCategoryFieldName';
	}

	protected  function getIndexName()
	{
		return ElasticConstants::CATEGORY_INDEX_NAME;
	}

	protected  function getIndexType()
	{
		return ElasticConstants::CATEGORY_INDEX_TYPE;
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

		$kalturaESearchCategoryItem = new KalturaESearchCategoryItem();
		$kalturaESearchCategoryItem->fieldName =$fieldName;
		$kalturaESearchCategoryItem->itemType = $searchType;
		$kalturaESearchCategoryItem->searchTerm = $fieldValue;
		array_push($kalturaESearchOperator->searchItems, $kalturaESearchCategoryItem);

		$answer = $apiClient->eSearch->search($kalturaESearchOperator);
		return $answer;
	}

}

