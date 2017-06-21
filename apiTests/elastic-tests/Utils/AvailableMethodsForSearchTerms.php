<?php

require_once(dirname(__FILE__).'/ElasticConstants.php');
require_once(dirname(__FILE__).'/ElasticToApiMapping.php');

/**
 * Class AvailableMethodsForSearchTerms
 */
class AvailableMethodsForSearchTerms
{
	/**
	 * Returns the search methods (Partial/Exact match/Starts with/Does not contain/Range) according to the field and index
	 * @param $apiClient
	 * @param $indexType
	 * @param $field
	 * @return array|void
	 * @throws Exception
	 */
	public static function getAvailableSearchMethods($apiClient, $indexType, $field)
	{
		switch ($indexType)
		{
			case ElasticConstants::ENTRY_INDEX_TYPE:
				return self::getEntrySearchMethods($apiClient, $field);
			case ElasticConstants::CATEGORY_INDEX_TYPE:
				return self::getCategorySearchMethods($apiClient, $field);
			case ElasticConstants::KUSER_INDEX_TYPE:
				return self::getKUserSearchMethods($apiClient, $field);
			default:
				throw new Exception("There is no support for index $indexType currently");
		}
	}

	private static function  getEntrySearchMethods($apiClient, $fieldName)
	{
		$kalturaESearchEntryItem = new KalturaESearchEntryItem();
		$kalturaESearchEntryItem->fieldName = $fieldName;
		$answer = $apiClient->eSearch->getAllowedSearchTypes($kalturaESearchEntryItem);
		$availableSearchTerms = array();
		foreach ($answer as $searchItem)
		{
			self::validateOrderOfEnum($searchItem->key, $searchItem->value);
			array_push($availableSearchTerms, $searchItem->value);
		}
		return $availableSearchTerms;
	}

	private static function  getCategorySearchMethods($apiClient, $field)
	{

	}

	private static function  getKUserSearchMethods($apiClient, $field)
	{

	}


	public static function validateOrderOfEnum($apiName, $apiValue)
	{
		if ( (strpos($apiName, ElasticConstants::SEARCH_TYPE_EXACT_MATCH_TEXT) !== false && $apiValue ===  ElasticConstants::SEARCH_TYPE_EXACT_MATCH) ||
			(strpos($apiName, ElasticConstants::SEARCH_TYPE_DOESNT_CONTAIN_TEXT) !== false && $apiValue ===  ElasticConstants::SEARCH_TYPE_DOESNT_CONTAIN) ||
			(strpos($apiName, ElasticConstants::SEARCH_TYPE_RANGE_TEXT) !== false && $apiValue ===  ElasticConstants::SEARCH_TYPE_RANGE) ||
			(strpos($apiName, ElasticConstants::SEARCH_TYPE_PARTIAL_TEXT) !== false && $apiValue ===  ElasticConstants::SEARCH_TYPE_PARTIAL) ||
			(strpos($apiName, ElasticConstants::SEARCH_TYPE_STARTS_WITH_TEXT) !== false && $apiValue ===  ElasticConstants::SEARCH_TYPE_STARTS_WITH))
			return;
		throw new Exception("There is a mismatch between the search terms in the testing and the server (failed on $apiName - got $apiValue)");
	}


}