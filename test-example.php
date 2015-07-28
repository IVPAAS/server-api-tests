<?php
require_once('KalturaPHPUnit_Framework_TestCase.php');
/**
 * @group BaseEntry
 */
class BaseEntry extends KalturaPHPUnit_Framework_TestCase {

	/**
	 * this is a regular comment
	 * @group list
	 */
	public function testListAction1()
	{
		$test1 = $this->createTest('baseEntry', 'listAction', array(), 'validateTestListAction1');
		$test1->runTest();
	}

	/*
	 * Another completly regular comment
	 * @param $result
	 */
	public function validateTestListAction1($result)
	{
		$this->assertTrue(true);
		return;
	}

	/**
	 * @group get
	 * @pre entry
	 */
	public function testGetAction1()
	{
		$test1 = $this->createTest('baseEntry', 'get', array(), 'validateTestGetAction1');
		$test1->runTest();
	}

	public function validateTestGetAction1($result)
	{
		$this->assertFalse($result instanceof KalturaException);
		return;
	}
	
}

