<?php

require_once(dirname(__FILE__) .'/ElasticConstants.php');


class ElasticTestConfiguration
{
	private $conf;

	function __construct()
	{
		$confFilePath = dirname(__FILE__)."/../elasticTestConf.ini";
		if (!file_exists($confFilePath))
			throw new Exception("Failed to find configuration file at $confFilePath");
		$this->conf =  parse_ini_file($confFilePath, true);
	}

	public function validateConfiguration()
	{
		$genKsPath = $this->conf[ElasticConstants::CONF_GENKS_PATH];
		if (file_exists($genKsPath) === false)
			throw new Exception("Could not find genks execute file as given in the configuration: $genKsPath");
		// TODO add all validations
	}

	/**
	 * @param $name
	 * @param null $child
	 * @return mixed
	 * @throws Exception
	 */
	public function getConfValue($name, $child = null)
	{
		if (!array_key_exists($name, $this->conf))
			throw new Exception("Missing key in configuration for $name");
		$value = $this->conf[$name];
		if ($child !== null)
			if(!array_key_exists($child, $value))
				throw new Exception("Missing key in configuration for $name, $child");
			else
				return $value[$child];
		return $value;
	}

	public function isValueSet($name, $child = null)
	{
		if (!array_key_exists($name, $this->conf))
			return false;
		$value = $this->conf[$name];
		if ($child !== null)
			if(!array_key_exists($child, $value))
				return false;
			else
				return true;
		return true;
	}

	public static function fromConfTypeToIndexType($confType)
	{
		switch ($confType)
		{
			case ElasticConstants::CONF_TYPE_ENTRY:
				return ElasticConstants::ENTRY_INDEX_TYPE;
			case ElasticConstants::CONF_TYPE_KUSER:
				return ElasticConstants::KUSER_INDEX_TYPE;
			case ElasticConstants::CONF_TYPE_CATEGORY:
				return ElasticConstants::CATEGORY_INDEX_TYPE;
			default:
				throw new Exception("We still do not support a certain index for conf type $confType");
		}
	}

}

