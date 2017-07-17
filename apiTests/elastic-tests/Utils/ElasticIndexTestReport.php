<?php

class ElasticIndexTestReport
{
	private $numTestedElasticDocs;
	private $numAPIFieldComparisons;
	private $indexName;
	private $notTestedFields;
	private $testedFields;
	private $failedSearches;

	public function __construct($indexName, $numTestedElasticDocs=0, $numAPIFieldComparisons=0, $notTestedFields=array(), $testedFields=array(), $failedSearches=0)
	{
		$this->numTestedElasticDocs = $numTestedElasticDocs;
		$this->numAPIFieldComparisons = $numAPIFieldComparisons;
		$this->indexName = $indexName;
		$this->notTestedFields = $notTestedFields;
		$this->testedFields = $testedFields;
		$this->failedSearches = $failedSearches;
	}

	/**
	 * @return int
	 */
	public function getNumTestedElasticDocs()
	{
		return $this->numTestedElasticDocs;
	}

	/**
	 * @return array
	 */
	public function getFailedSearches()
	{
		return $this->failedSearches;
	}

	/**
	 * @return array
	 */
	public function getTestedFields()
	{
		return $this->testedFields;
	}

	/**
	 * @return array
	 */
	public function getNotTestedFields()
	{
		return $this->notTestedFields;
	}

	/**
	 * @return int
	 */
	public function getNumAPIFieldComparisons()
	{
		return $this->numAPIFieldComparisons;
	}

	public function increaseTestedElasticDocCounter()
	{
		$this->numTestedElasticDocs++;
	}

	public function increaseFailedElasticDocCounter()
	{
		$this->failedSearches++;
	}

	public function addSuccessfulComparison($fieldName, $searchType)
	{
		$this->numAPIFieldComparisons++;
		if (array_key_exists($fieldName, $this->testedFields) && is_array($this->testedFields[$fieldName]))
		{
			if(array_key_exists($searchType, $this->testedFields[$fieldName]))
				$this->testedFields[$fieldName][$searchType]->successCounter++;
			else
			{
				$this->testedFields[$fieldName][$searchType] = new RateObject(1,0);
			}
		}
		else
		{
			$rateObject = new RateObject(1,0);
			$this->testedFields[$fieldName] = array($searchType => $rateObject);
		}
	}

	public function addFailedComparison($fieldName, $searchType)
	{
		$this->numAPIFieldComparisons++;
		if (array_key_exists($fieldName, $this->testedFields) &&  is_array($this->testedFields[$fieldName]))
		{
			if(array_key_exists($searchType, $this->testedFields[$fieldName]))
				$this->testedFields[$fieldName][$searchType]->failCounter++;
			else
			{
				$this->testedFields[$fieldName][$searchType] = new RateObject(0,1);
			}
		}
		else
		{
			$rateObject = new RateObject(0,1);
			$this->testedFields[$fieldName] = array($searchType => $rateObject);
		}
	}

	public function setRejectedField($fieldName, $reason)
	{
		$this->notTestedFields[$fieldName] = $reason;
	}

	public function printReport()
	{
		$report = "\n------- Elastic Test Report --------------\n";
		$report .= "Number of tested elastic documents: ".$this->numTestedElasticDocs."\n";
		$report .= "Number of elastic documents that failed to load document via API: ".$this->failedSearches."\n";
		$report .= "Number of API fields comparisons: ".$this->numAPIFieldComparisons."\n";
		$report .= "Tested search terms:\n";
		foreach ($this->testedFields as $field => $fieldSummaryArray)
		{
			$report .= "\t$field, summary: \n";
			foreach ($fieldSummaryArray as $searchType => $rate)
			{
				$textSearchType = ElasticTestUtils::getSearchTypeText($searchType);
				$report .= "\t\tSearch Type: $textSearchType,\tSuccess: $rate->successCounter,\tFailure: $rate->failCounter\n";
			}
		}
		$report .= "Rejected search terms:\n";
		foreach ($this->notTestedFields as $field => $reason)
		{
			$report .= "\tSearchTerm: $field,\n\t |->Reason:\t$reason\n";
		}

		print($report);
	}

	public function saveReportObjectToFile($filePath)
	{
		$oldReport = $this->readReportFromFile($filePath);
		$newReport = $this;
		if ($oldReport)
			$newReport = $this->accumulateReports(array($this, $oldReport ), $this->indexName);

		$ser = serialize($newReport);
		file_put_contents($filePath, $ser);
	}

	public static function readReportFromFile($filePath)
	{
		$s = file_get_contents($filePath);
		return unserialize($s);

	}

	public static function getReportFilePath($suffix, $number, $testType, $confType)
	{
		return "$suffix/$number.$testType.$confType";
	}

	public static function accumulateReports($reports, $indexName)
	{
		$numReports = count($reports);
		$accumulatedNumTestedElasticDocs = 0;
		$accumulatedNumAPIFieldComparisons = 0;
		$accumulatedNotTestedFields = array();
		$accumulatedTestedFields = array();
		$accumulatedFailedSearches = 0;


		for ($i=0; $i<$numReports; $i++)
		{
			$report = $reports[$i];
			// accumulate numeric counters
			$accumulatedNumTestedElasticDocs += $report->getNumTestedElasticDocs();
			$accumulatedNumAPIFieldComparisons += $report->getNumAPIFieldComparisons();
			$accumulatedFailedSearches += $report->getFailedSearches();
			// accumulate array indicators
			foreach ($report->getTestedFields() as $testedField => $searchResults)
			{
				if (!array_key_exists($testedField, $accumulatedTestedFields))
					$accumulatedTestedFields[$testedField] = $searchResults;
				else
				{
					foreach($searchResults as $searchType => $rateObject)
					{
						if (!array_key_exists($searchType, $accumulatedTestedFields[$testedField]))
							$accumulatedTestedFields[$testedField][$searchType] = $rateObject;
						else
						{

							$accumulatedTestedFields[$testedField][$searchType]->successCounter += $rateObject->successCounter;
							$accumulatedTestedFields[$testedField][$searchType]->failCounter += $rateObject->failCounter;
						}
					}
				}
			}

			foreach ($report->getNotTestedFields() as $field => $reason)
				$accumulatedNotTestedFields[$field] = $reason;

		}

		return new ElasticIndexTestReport(
			$indexName,
			$accumulatedNumTestedElasticDocs,
			$accumulatedNumAPIFieldComparisons,
			$accumulatedNotTestedFields,
			$accumulatedTestedFields,
			$accumulatedFailedSearches
		);
	}

}

class RateObject {

	public $successCounter;
	public $failCounter;

	public function __construct($successes, $failures)
	{
		$this->successCounter = $successes;
		$this->failCounter = $failures;
	}
}


function testReport()
{
	$report = new ElasticIndexTestReport("my Index");
	$report->increaseTestedElasticDocCounter();
	$report->increaseTestedElasticDocCounter();
	$report->increaseTestedElasticDocCounter();
	$report->increaseFailedElasticDocCounter();
	$report->increaseFailedElasticDocCounter();
	$report->increaseFailedElasticDocCounter();
	$report->increaseFailedElasticDocCounter();
	$report->setNotTestedField("MyNotTestedField","Don't want");
	$report->setNotTestedField("MyNotTestedField2","Don't want to too");
	$report->addFailedComparison("Field1", "Partial");
	$report->addFailedComparison("Field1", "Partial");
	$report->addFailedComparison("Field1", "Exact");
	$report->addFailedComparison("Field1", "Exact");
	$report->addSuccessfulComparison("Field1", "Partial");
	$report->addSuccessfulComparison("Field2", "Exact");
	$report->printReport();

}
//testReport();
