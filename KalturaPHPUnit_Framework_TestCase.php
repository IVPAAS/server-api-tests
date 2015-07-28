<?php
/**
* Created by IntelliJ IDEA.
* User: root
* Date: 6/23/15
* Time: 11:42 PM
*/
require_once('clientManager.php');
require_once('BaseTest.php');
/**
* @group BaseEntry
*/
class KalturaPHPUnit_Framework_TestCase extends PHPUnit_Framework_TestCase
{

	const DOCCOMMENT_PREREQUISITE = "/\\@pre ([\\w\\,\\s]*)/i";
	
	const CONFIG_FILE = "config.ini";
	/**
	 * @var KalturaClient
	 */
	public $client;
	
	public $preReqs = array();
	
	

	/**
	 * @before
	 */
	public function getClient()
	{
		$config = parse_ini_file(dirname(__FILE__).'/'.self::CONFIG_FILE);
		if ($config === false)
		{
			$cm = new clientManager;
		}
		else
		{
			$cm = new clientManager($config['serviceUrl'],$config['partnerId'], $config['secret'], $config['ksType']);
		}
		$this->client = $cm->getClient();
		$this->doAllPrerequisites();
	}

	protected function doAllPrerequisites()
	{
		$allPreReqs = $this->getAllPrerequisites();
		foreach ($allPreReqs as $preReq)
		{
			$this->doPreReq($preReq);			
		}
	}
	
	protected function doPreReq($preReqName)
	{
		$preReqName = trim($preReqName);
		if (isset($this->preReqs[$preReqName]))
		{
			return;
		}
		switch ($preReqName)
		{
			case "entry":
				$this->createEntry();
				break;
			default:
				echo "prerequisite [$preReqName] unknown, please contact developer to resolve\n";
				break;
		}
	}
	
	protected function createEntry()
	{
		$baseEntry = new KalturaBaseEntry();
		$result = call_user_func(array($this->client->baseEntry, 'add'), $baseEntry);
//		echo "new entry created [".$result->id."]\n";
		$this->preReqs['entry'] = $result;		
	}

	/**
	 * @return array
	 */
	protected function getAllPrerequisites()
	{
		$allPreReqs = array();
		$reflectClass = new ReflectionClass(get_class($this));
		$classesHierarchy = array();
		$classesHierarchy[] = $reflectClass;
		$parentClass = $reflectClass;

		// lets get the class hierarchy so we could order the properties in the right order
		while ($parentClass = $parentClass->getParentClass())
		{
			$classesHierarchy[] = $parentClass;
		}

		// reverse the hierarchy, top class properties should be first 
		$classesHierarchy = array_reverse($classesHierarchy);
		foreach ($classesHierarchy as $currentReflectClass)
		{
			/**
			 * @var ReflectionClass $currentReflectClass
			 */
			$methods = $currentReflectClass->getMethods(ReflectionProperty::IS_PUBLIC);
			foreach ($methods as $method)
			{
				if ($method->getDeclaringClass() == $currentReflectClass)
				{
					$docComment = $method->getDocComment();
					$allPreReqs = array_merge($allPreReqs, $this->parsePrereqDocComment($docComment));
				}
			}

		}
		return $allPreReqs;
	}

	/**
	 * @param $docComment
	 * @param $currentReflectClass
	 * @param $methodName
	 * @param $match
	 * @param $allPreReqs
	 * @return array
	 */
	public static function parsePrereqDocComment($docComment)
	{
		if ($docComment)
		{
			$pre = preg_match(self::DOCCOMMENT_PREREQUISITE, $docComment, $match);
			if ($pre)
			{
				return preg_split("/(,| )/", trim($match[1]));
			}
		}
		return array();
	}
	
	protected function createTest($service, $action, $actionParameters, $checkResultFuncName = 'assertTrue')
	{
		$trace=debug_backtrace();
		$caller=$trace[1];
		$method = new ReflectionMethod($caller['class'],$caller['function']);
		$docComment = $method->getDocComment();
		$preReqs = KalturaPHPUnit_Framework_TestCase::parsePrereqDocComment($docComment);
		$additionalParams = array();
		foreach ($preReqs as $preReq)
		{
			if (isset($this->preReqs[$preReq]))
			{
				array_merge($additionalParams, $this->getAdditionalParam($preReq));
			}
		}
		return new BaseTest($service,$action,array_merge($actionParameters,$additionalParams),$this->client,array($this,$checkResultFuncName));
	}

	/**
	 * TODO: add transformation map since entry_id might be called something different in other services and actions.
	 * @param $paramName
	 * @return string
	 */
	protected function getAdditionalParam($paramName)
	{
		switch ($paramName)
		{
			case 'entry':
				return array('entry_id' => $this->preReqs[$paramName]->id);
			case 'metadata':
				return 'metadata_id';
			default:
				return $paramName;
		}
	}
}
