<?php

require_once(dirname(__FILE__) .'/Utils/ElasticConstants.php');
require_once(dirname(__FILE__) .'/Utils/ElasticTestConfiguration.php');
require_once(dirname(__FILE__) .'/Tests/VerifyApiFields/RandomElasticDocVerificationCategoryTest.php');
require_once(dirname(__FILE__) .'/Tests/VerifyApiFields/RandomElasticDocVerificationKuserTest.php');
require_once(dirname(__FILE__) .'/Tests/VerifyApiFields/RandomElasticDocVerificationEntryTest.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');

function createRandomTestInstance($elasticSearchClient, $indexType, $indexName, $conf)
{
	switch($indexType)
	{
		case ElasticConstants::ENTRY_INDEX_TYPE:
			return new RandomElasticDocVerificationEntryTest($elasticSearchClient, $indexName, $conf);
		case ElasticConstants::CATEGORY_INDEX_TYPE:
			return new RandomElasticDocVerificationCategoryTest($elasticSearchClient, $indexName, $conf);
		case ElasticConstants::KUSER_INDEX_TYPE:
			return new RandomElasticDocVerificationKuserTest($elasticSearchClient, $indexName, $conf);
		default:
			die("Unsupported index was given :$indexType");
	}
}

function initializeReportFiles($conf, $testType, $confType)
{
	$numCores = ElasticTestUtils::getProcessorCoresNumber();
	for ($i = 1; $i <= $numCores; ++$i)
	{
		$reportFilePath = ElasticIndexTestReport::getReportFilePath($conf->getConfValue(ElasticConstants::CONF_LOG_DIR),$i, $testType, $confType);
		$fd = fopen($reportFilePath, "w");
		fclose($fd);
	}
}

function runTestMultiThreaded($test, $numberOfRunsPerPartner, $partnerId, $testType, $confType, $conf)
{
	$numCores = ElasticTestUtils::getProcessorCoresNumber();
	$numObjectedPerThread = intval($numberOfRunsPerPartner / $numCores);

	for ($i = 1; $i <= $numCores; ++$i) {
		$pid = pcntl_fork();

		if ($pid === -1)
			die("Could not fork");
		else if($pid === 0) // this is each child code run
		{
			$reportFilePath = ElasticIndexTestReport::getReportFilePath($conf->getConfValue(ElasticConstants::CONF_LOG_DIR),$i, $testType, $confType);
			$test->runTest($reportFilePath, $numObjectedPerThread, $partnerId);
			exit();
		}
	}
	// wait for all children to end their run
	while (pcntl_waitpid(0, $status) != -1)
		$status = pcntl_wexitstatus($status);
}

function getValidRandomPartnerId($maxPartnerId, $conf, $retries = 0)
{
	$randPartnerId = rand(100, $maxPartnerId);
	$genksPath = $conf->getConfValue(ElasticConstants::CONF_GENKS_PATH);
	$kalCliPath = $conf->getConfValue(ElasticConstants::CONF_KALCLI_PATH);
	$ks = shell_exec("php $genksPath $randPartnerId");
	if (strpos($ks, "Failed to get secret for") === 0)
		return getValidRandomPartnerId($maxPartnerId, $conf, $retries + 1);
	$genPartnerId = shell_exec("php $genksPath $randPartnerId | php $kalCliPath partner getInfo | grep -A1 KalturaPartner | grep id | awk '{print \$2}'");
	if (strcmp( strval($genPartnerId), strval($randPartnerId) === 0 ))
		return $randPartnerId;
	else
	{
		if ($retries > 100)
			throw new Exception("Failed to randomly generate a valid partner id - look for another method to do this - 100 tries failed");
		return getValidRandomPartnerId($maxPartnerId, $conf, $retries + 1);
	}
}

function runTestForMultiPartners($conf, $test, $testType, $confType)
{
	$maxPartnerId = $conf->getConfValue(ElasticConstants::CONF_MAX_PARTNER_ID);
	$numPartnersToDraw = $conf->getConfValue(ElasticConstants::CONF_NUM_PARTNERS_TO_DRAW);
	$numDocsPerPartner = $conf->getConfValue(ElasticConstants::CONF_NUM_DOCS_PER_PARTNER);

	for ($i=0 ; $i < $numPartnersToDraw; $i++)
	{
		$randomPartnerId = getValidRandomPartnerId($maxPartnerId, $conf);
		runTestMultiThreaded($test, $numDocsPerPartner, $randomPartnerId, $testType, $confType, $conf);
	}
}

function runTestForPartner($conf, $test, $testType, $confType, $partnerId)
{
	$numDocsPerPartner = $conf->getConfValue(ElasticConstants::CONF_NUM_DOCS_PER_PARTNER);
	runTestMultiThreaded($test, $numDocsPerPartner, $partnerId, $testType, $confType, $conf);
}

function generateReportForTest($testType, $confType, $conf)
{
	$reports = array();
	$numCores = ElasticTestUtils::getProcessorCoresNumber();
	for ($i = 1; $i <= $numCores; ++$i)
	{
		$reportFilePath = ElasticIndexTestReport::getReportFilePath($conf->getConfValue(ElasticConstants::CONF_LOG_DIR), $i, $testType, $confType);
		$reportObject = ElasticIndexTestReport::readReportFromFile($reportFilePath);
		array_push($reports, $reportObject);
	}
	$accumulatedReport = ElasticIndexTestReport::accumulateReports($reports, $confType);
	$accumulatedReport->printReport();
}

function runConfiguredTestsTypes($conf, $elasticClient, $confType, $partnerId = null)
{
	if ($conf->isValueSet($confType, ElasticConstants::CONF_INDEX_NAME) &&
		$conf->isValueSet($confType, ElasticConstants::CONF_TESTS_TO_RUN))
	{
		$testsToRun = explode(",", $conf->getConfValue($confType, ElasticConstants::CONF_TESTS_TO_RUN));
		$indexName = $conf->getConfValue($confType, ElasticConstants::CONF_INDEX_NAME);
		$indexType = ElasticTestConfiguration::fromConfTypeToIndexType($confType);
		foreach ($testsToRun as $testType)
		{
			switch ($testType)
			{
				case ElasticConstants::CONF_TEST_TYPE_RANDOM :
					$test = createRandomTestInstance($elasticClient, $indexType, $indexName, $conf);
					break;
				default:
					throw new Exception("Found an unknown test type in configuration for $confType tests : $testType ");
			}
			initializeReportFiles($conf, $testType, $confType);
			if ($partnerId)
				runTestForPartner($conf, $test, $testType, $confType, $partnerId);
			else
				runTestForMultiPartners($conf, $test, $testType, $confType);

			generateReportForTest($testType, $confType, $conf);
		}
	}
}

function main()
{
	$conf = new ElasticTestConfiguration();
	$conf->validateConfiguration();
	$elasticSearchClient = new ElasticClientFromServer(
		$conf->getConfValue(ElasticConstants::CONF_ELASTIC_HOST),
		$conf->getConfValue(ElasticConstants::CONF_ELASTIC_PORT));

	$specificPartnerRun = $conf->getConfValue(ElasticConstants::CONF_SPECIFIC_PARTNER);
	if ($specificPartnerRun !== null && $specificPartnerRun > 0)
		runConfiguredTestsTypes($conf, $elasticSearchClient, ElasticConstants::CONF_TYPE_ENTRY, $specificPartnerRun);
	else
		runConfiguredTestsTypes($conf, $elasticSearchClient, ElasticConstants::CONF_TYPE_ENTRY);
}

main();