<?php
/**
 * @package plugins.elasticSearch
 * @subpackage client
 */
class SimpleElasticClient
{

	protected $elasticHost;
	protected $elasticPort;
	protected $ch;

	/**
	 * @param null $host
	 * @param null $port
	 * @param null $curlTimeout
	 * @throws Exception
	 */
	public function __construct($host = null, $port = null, $curlTimeout = null)
	{
		if(!$host)
			throw new Exception('Cannot run elastic tests without host');
		$this->elasticHost = $host;

		if(!$port)
			throw new Exception('Cannot run elastic tests without port');
		$this->elasticPort = $port;

		$this->ch = curl_init();

		curl_setopt($this->ch, CURLOPT_FORBID_REUSE, true);
		curl_setopt($this->ch, CURLOPT_PORT, $this->elasticPort);

		if(!$curlTimeout)
			$curlTimeout = 10;
		$this->setTimeout($curlTimeout);
	}

	public function __destruct()
	{
		curl_close($this->ch);
	}

	/**
	 * @param $cmd
	 * @param $method
	 * @param null $body
	 * @return mixed
	 * @throws Exception
	 */
	protected function sendRequest($cmd, $method, $body = null)
	{
		curl_setopt($this->ch, CURLOPT_URL, $cmd);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true); //TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method); // PUT/GET/POST/DELETE
		if($body)
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($body));

		$response = curl_exec($this->ch);
		if (!$response)
			throw new Exception("Elastic client curl error !!! ".print_r($response, true));

		return json_decode($response, true);
	}

	/**
	 * search API
	 * @param array$params
	 * @return mixed
	 */
	public function search(array $params)
	{
		$cmd = $this->elasticHost;
		$cmd .='/'.$params['index']; //index name
		if(isset($params['type']))
			$cmd .= '/'.$params['type'];
		if(isset($params['size']))
			$params['body']['size'] = $params['size'];

		$cmd .= "/_search";
		$val =  $this->sendRequest($cmd, 'POST', $params['body']);
		return $val;
	}
}
