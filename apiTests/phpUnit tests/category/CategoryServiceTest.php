<?php

require_once __DIR__ . '/../KalturaApiTestCase.php';

/**
 * category service test case.
 */
class CategoryServiceTest extends KalturaApiTestCase
{	
	public function testCreateEntry()
	{
		$this->log("Test: testCreateEntry");
		
		$entry = $this->createEntry();
		$this->assertNotNull($entry->id);
	}
	
	public function testCreateCategory()
	{
		$this->log("Test: testCreateCategory");
		
		$category = $this->createCategory();
		$this->assertNotNull($category->id);
	}
	
	public function testCrossMaxCategories()
	{
		$this->log("Test: testCrossMaxCategories");
		
		$entry = $this->createEntry();
		$this->addCategoriesToEntry($entry->id, 32);
		try{
			$this->addCategoryToEntry($entry->id);
			$this->fail("Max categories per entry crossed with no exception");
		}
		catch(KalturaException $e)
		{
			$this->assertEquals('MAX_CATEGORIES_FOR_ENTRY_REACHED', $e->getCode());
		}
	}
	
	public function getOpenJobCount($jobType)
	{
		$filter = new KalturaBatchJobFilter();
		$filter->jobTypeEqual = $jobType;
		$filter->statusNotIn = implode(',', array(
			KalturaBatchJobStatus::ABORTED,
			KalturaBatchJobStatus::DONT_PROCESS,
			KalturaBatchJobStatus::FAILED,
			KalturaBatchJobStatus::FATAL,
			KalturaBatchJobStatus::FINISHED,
			KalturaBatchJobStatus::FINISHED_PARTIALLY,
		));
		
		$pager = new KalturaFilterPager();
		$pager->pageSize = 1;
		
		$client = $this->getClient(KalturaSessionType::ADMIN, -2, null, 86400, '', 'eb59eef581b03fb2be930a9c705629dd');
		$list = $client->jobs->listBatchJobs($filter, $pager);
		
		return $list->totalCount;
	}
	
	public function testDeleteWithMaxCategories()
	{
		$this->log("Test: testDeleteWithMaxCategories");
		
		$jobCount = $this->getOpenJobCount(KalturaBatchJobType::MOVE_CATEGORY_ENTRIES);
		$this->assertEquals(0, $jobCount);
			
		$parentCategory = $this->createCategory();
		$category = $this->createCategory($parentCategory->id);
		
		$entry = $this->createEntry();
		$this->addCategoriesToEntry($entry->id, 31);
		$this->addCategoryToEntry($entry->id, $category->id);
		
		$timeout = time() + (60 * 3);
		$this->deleteCategory($category->id);
		
		$jobCount = $this->getOpenJobCount(KalturaBatchJobType::MOVE_CATEGORY_ENTRIES);
		$this->assertEquals(1, $jobCount);
		while($jobCount)
		{
			$this->assertGreaterThan(time(), $timeout);
			sleep(10);
			$jobCount = $this->getOpenJobCount(KalturaBatchJobType::MOVE_CATEGORY_ENTRIES);
		}
	}
}

