<?php

require_once(dirname(__FILE__) . '/../../Utils/ElasticClientFromServer.php');
require_once(dirname(__FILE__) . '/../../Utils/ElasticToApiMapping.php');
require_once(dirname(__FILE__) . '/../../Utils/ElasticConstants.php');
require_once(dirname(__FILE__) . '/../../Utils/AvailableMethodsForSearchTerms.php');
require_once(dirname(__FILE__) . '/../../Utils/IdGenerator.php');
require_once(dirname(__FILE__) . '/../../Utils/ElasticIndexTestReport.php');
require_once(dirname(__FILE__) . '/../../Utils/ElasticTestUtils.php');
require_once(dirname(__FILE__) . '/../../../testsHelpers/apiTestHelper.php');
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');


abstract class RandomElasticDocVerificationBaseTest
{

	protected $elasticSearchClient;
	protected $myReport;
	protected $indexName;
	protected $conf;
	protected $reportPath;

	/**
	 * @param $elasticClient
	 * @param $indexName
	 * @param $conf ElasticTestConfiguration
	 */
	public function  __construct($elasticClient, $indexName, $conf)
	{
		$this->elasticSearchClient = $elasticClient;
		$this->myReport = new ElasticIndexTestReport($indexName);
		$this->indexName = $indexName;
		$this->conf = $conf;
	}

	protected abstract function getSearchTermsConstantsClassName();
	protected abstract function getIndexType();
	protected abstract function isInIgnoreList($fieldName);
	protected abstract function getObjectFromElasticViaServer($fieldName, $fieldValue, $searchType, $apiClient);

	protected function getAPIClient($partnerId)
	{
		$apiHost = $this->conf->getConfValue(ElasticConstants::CONF_DC_URL);
		$genKsPath = $this->conf->getConfValue(ElasticConstants::CONF_GENKS_PATH);
		$ks = shell_exec("php $genKsPath $partnerId | awk '{print $2}'");
		return startKSSession($partnerId, $apiHost, $ks);
	}

	public static function splitIntoWords($value)
	{
		return preg_split('/\s+/', $value);
	}


	protected function getPartialValueTrim($value)
	{
		$words = self::splitIntoWords($value);
		$result = array();
		for ($i = 0; $i < count($words); $i++)
		{
			if ($i % 2 === 0)
				array_push($result, $words[$i]);
		}
		return implode(' ', $result);
	}

	/**
	 * 1. Draw a random object from elastic
	 * 2. For each field on the random object
	 * 2a. search in the API using eSearch
	 * 2b. For each result from the API
	 * 2aa. Validate the field we searched for matches between the elastic and API result
	 *
	 * @param $reportPath
	 * @param $numIterations
	 * @param $partnerId
	 * @return int|string
	 * @throws Exception
	 */
	public function runTest($reportPath, $numIterations, $partnerId)
	{
		$apiClient = $this->getAPIClient($partnerId);

		for ($iteration = 0; $iteration < $numIterations ; $iteration++)
		{
			try
			{
				$randomElasticObject = $this->getRandomElasticObject($partnerId);
			}
			catch (Exception $e)
			{
				return fail('Cannot Run test due to ' . $e->getMessage());
			}
			if (!isset($randomElasticObject['_source']))
				return fail('Object returned from elastic query did not have valid structure ' . print_r($randomElasticObject, true));
			$this->myReport->increaseTestedElasticDocCounter();
			$randomObjectId = $randomElasticObject['_id'];
			$randomElasticObject = $randomElasticObject['_source'];
			foreach ($randomElasticObject as $elasticField => $elasticValue)
			{
				if ($this->isValidSearchItem($elasticField, $elasticValue))
				{
					$availableSearchTerms = AvailableMethodsForSearchTerms::getAvailableSearchMethods($apiClient, $this->getIndexType(), $elasticField);
					foreach ($availableSearchTerms as $searchTerm)
					{
						$valueToCompare = $elasticValue;
						if ($searchTerm === ElasticConstants::SEARCH_TYPE_PARTIAL)
							$valueToCompare = $this->getPartialValueTrim($elasticValue);

						try
						{
							$answer = $this->getObjectFromElasticViaServer($elasticField, $valueToCompare, $searchTerm, $apiClient);
						}
						catch (Exception $e)
						{
							$this->myReport->increaseFailedElasticDocCounter();
							fail("Failed on ".ElasticTestUtils::getSearchTypeText($searchTerm)." field [$elasticField] and pId: [$partnerId] , searched for: [$elasticValue] ,due to ".$e->getMessage());
							continue;
						}

						if (count($answer) === 0)
						{
							// these are the tests that failed to get even one result from tha API server
							$this->myReport->increaseFailedElasticDocCounter();
							fail("Failed on ".ElasticTestUtils::getSearchTypeText($searchTerm)." field [$elasticField] and pId: [$partnerId] , searched for: [$elasticValue] ,due to count 0 from server");
							continue;
						}
						$isFirstComparison = true;
						foreach ($answer as $apiResultObject)
						{
							$type = $this->getIndexType();
							$apiResultObject = $apiResultObject->$type;
							if ($this->isValidElasticAndApiResultsMatch($apiResultObject, $randomElasticObject, $randomObjectId, $elasticField, $valueToCompare, $searchTerm))
								$this->myReport->addSuccessfulComparison($elasticField, $searchTerm);
							else
								$this->myReport->addFailedComparison($elasticField, $searchTerm);
							if ($isFirstComparison && $searchTerm === ElasticConstants::SEARCH_TYPE_PARTIAL)
								break; // we know how to validate only the first result
						}
					}
				}
				else
				{
					if (is_null($elasticValue) )
						$this->myReport->setRejectedField($elasticField, "Null value");
					else if (is_array($elasticValue))
						$this->myReport->setRejectedField($elasticField, "Array type");
					else if (strlen($elasticValue) === 0 )
						$this->myReport->setRejectedField($elasticField, "Empty value ");
					else if ($this->isInIgnoreList($elasticValue))
						$this->myReport->setRejectedField($elasticField, "In ignore list");
					else
						$this->myReport->setRejectedField($elasticField, "Not a valid search term");
				}
			}
		}
		$this->exportReport($reportPath);
	}

	public function printReport()
	{
		$this->myReport->printReport();
	}

	public function exportReport($filePath)
	{
		$this->myReport->saveReportObjectToFile($filePath);
	}

	protected function getIndexName()
	{
		return $this->indexName;
	}
	/**
	 * We do not search for empty values of fields that are not part of the client map (according to its class)
	 *
	 * @param string $key
	 * @param string $value
	 * @return bool
	 */
	protected function isValidSearchItem($key, $value)
	{
		if (!isset($value) || is_array($value) || strlen($value) === 0 || $this->isInIgnoreList($key))
			return false;
		$KalturaESearchFieldName = new ReflectionClass($this->getSearchTermsConstantsClassName());
		$validConstants = $KalturaESearchFieldName->getConstants();
		foreach ($validConstants  as $constant => $text)
		{
			if ($key === $text )
				return true;
		}
		return false;
	}

	protected function isValidElasticAndApiResultsMatch($apiObject, $elasticObject, $elasticObjectId, $fieldPath, $searchText, $searchType)
	{
		$apiFieldName = ElasticToApiMapping::getMappingFor($this->getIndexType(), $fieldPath);
		if (!property_exists($apiObject, $apiFieldName))
		{
			fail("API object did not have field $apiFieldName yet was on elastic as ".$elasticObject[$fieldPath]." ".print_r($apiObject, true));
			return false;
		}
		$apiFieldLowerCase = strtolower($apiObject->$apiFieldName);
		$elasticFieldLowerCase = strtolower($elasticObject[$fieldPath]);
		$searchText = strtolower($searchText);
		switch ($searchType)
		{
			case ElasticConstants::SEARCH_TYPE_EXACT_MATCH:
				$result = (strcmp($apiFieldLowerCase, $elasticFieldLowerCase) === 0);
				break;
			case ElasticConstants::SEARCH_TYPE_PARTIAL:
				$wordsToLookFor = self::splitIntoWords($searchText);
				$result = true;
				foreach ($wordsToLookFor as $word)
				{
					if (strpos($apiFieldLowerCase, $word) === false)
					{
						$result = false;
						break;
					}
				}
				break;
			case ElasticConstants::SEARCH_TYPE_STARTS_WITH:
				$result = (strpos($apiFieldLowerCase, $searchText) === 0) && (strpos($elasticFieldLowerCase, $searchText) === 0);
				break;
			case ElasticConstants::SEARCH_TYPE_DOESNT_CONTAIN:
				$wordsToLookFor = self::splitIntoWords($searchText);
				$wordsThatExist = self::splitIntoWords($apiFieldLowerCase);
				$result = true;
				foreach ($wordsToLookFor as $word)
					$result &= in_array($word, $wordsThatExist);
				$result = !$result;
				break;
			default:
				throw new Exception("Cannot search for invalid search type ($searchType)");
		}
		if (!$result)
			fail("Found a mismatch: Search type: ".ElasticTestUtils::getSearchTypeText($searchType).",id: $elasticObjectId, partnerId is :".$elasticObject['partner_id'].
				" searched on field [$apiFieldName] with text [$searchText], API result : [".$apiObject->$apiFieldName."]");
		return $result;
	}

	protected function getRandomElasticObject($partnerId)
	{
		$params = array(
			'index' => $this->getIndexName(),
			'body' => array(
				'query' => array (
					'function_score' => array (
						'query' => array(
							'match' => array(
								'partner_id' => $partnerId
							)
						),
						'boost' => 5,
						'random_score' => array(
							"seed" => time()
						),
						'boost_mode' => 'multiply'
					)
				),
				'timeout' => '4s'
			)
		);
		$val = $this->elasticSearchClient->search($params);
		ElasticTestUtils::validateNoError($val);
		if ($val['hits']['total'] === 0)
			throw new Exception("Drawn partner $partnerId has no entries indexed in elastic ");
		return $val['hits']['hits'][0];
	}

}