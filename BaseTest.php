<?php

class BaseTest {

	protected $service;
	protected $action;
	protected $defaultClient;
	protected $checkResultFuncName;
	
	public function __construct($service, $action, $actionParameters, $defaultClient, $checkResultFuncName)
	{
		$this->service = $service;
		$this->action = $action;
		if ($actionParameters === null)
		{
			$actionParameters = array();
		}
//		echo "created test with parameters [".print_r($actionParameters,true)."]";
		$this->actionParameters = $actionParameters;
		$this->defaultClient = $defaultClient;
		$this->checkResultFuncName = $checkResultFuncName;
	}
	
	public function runTest($client = null)
	{
		if (!$client)
		{
			$client = $this->defaultClient;
		}
		$serviceName = $this->service;
		$actionName = $this->action;
		$currentService = $client->$serviceName;
//		$serviceResult = $currentService->$actionName();
//		echo "this is it [".print_r($currentService->$actionName(),true)."]";
		try
		{
			$serviceResult = call_user_func_array(array($currentService, $actionName), $this->actionParameters);
		}
		catch (Exception $e)
		{
			$serviceResult = $e;
		}
		call_user_func($this->checkResultFuncName,$serviceResult);
//		$this->checkResultFuncName($serviceResult);
	}
	
}