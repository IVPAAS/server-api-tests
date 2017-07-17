<?php

require_once(dirname(__FILE__) .'/ElasticConstants.php');

class ElasticTestUtils
{
	/**
	 * @param $response
	 * @return bool
	 */
	public static function isHit($response)
	{
		if ($response && isset($response['hits']) && isset($response['hits']['total']))
			return $response['hits']['total'] > 0;
		return false;
	}

	public static function validateNoError($response)
	{
		if ($response && isset($response['error']))
			throw new Exception("Found a query with an error : ".print_r($response, true));
	}

	public static function getProcessorCoresNumber() {
		return  (int) shell_exec("cat /proc/cpuinfo | grep processor | wc -l");
	}


	public static function getSearchTypeText($type)
	{
		switch ($type)
		{
			case ElasticConstants::SEARCH_TYPE_EXACT_MATCH :
				return "EXACT_MATCH     ";
			case ElasticConstants::SEARCH_TYPE_PARTIAL :
				return "PARTIAL         ";
			case ElasticConstants::SEARCH_TYPE_STARTS_WITH :
				return "STARTS_WITH     ";
			case ElasticConstants::SEARCH_TYPE_DOESNT_CONTAIN:
				return "DOES_NOT_CONTAIN";
// TODO			case SEARCH_TYPE_RANGE :
// TODO				return "RANGE";
			default:
				throw new Exception("getSearchTypeText got invalid type $type");
		}
	}
}