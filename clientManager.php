<?php
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');

class ksManager {
	private function getNewKs($partnerId,$secret,$destUrl,$ksType) 
	{
		 try
		 {
			  $config = new KalturaConfiguration($partnerId);
			  $config->serviceUrl = $destUrl;
			  $client = new KalturaClient($config);
			  $ks = $client->session->start($secret, null, $ksType, $partnerId, null, null);
			  return $ks;
		 }
		 catch (KalturaException $e)
		 {
			  $msg = $e->getMessage();
			  shout("Problem starting session with message: [$msg]\n");
			  die("ERROR - cannot generate session: partner id:[$partnerId] ,secret:[$secret],DC Url:[$destUrl],type:[$ksType]");
		 }
	}   
	
	public function getKs($partnerId,$secret,$destUrl,$ksType)
	{
		//TODO check if we have this KS in cache.
		
			//Validate KS if found and retrun the valid KS
		
		//Else generate new KS
		$ks=$this->getNewKs($partnerId,$secret,$destUrl,$ksType);
		
		// Store in cache
		
		//return the new KS
		return $ks;
	}
}

class clientManager
{
	public $destUrl;
	public $partnerId;
	public $secret;
	public $ksType;
	
	public $PARAM_FILE="TBD";
	
	public function __construct()
	{
		$this->readClientParamsFromFile();
	}
	private function readClientParamsFromFile()
	{
		//todo try read client info from default config file
		
		//TODO 
		$this->destUrl = "http://centos.kaltura/";
		$this->partnerId=101;
		$this->secret='537389d22dadfec14d9377a8773f37c2';
		$this->ksType=KalturaSessionType::ADMIN;
	}
	public function setDestUrl($destUrl)
	{
		$this->destUrl=$destUrl;
	}
	public function setPartnerId($partnerId)
	{
		$this->partnerId=$partnerId;
	}
	public function setSecret($secret)
	{
		$this->secret=$secret;
	}
	public function setKsType($ksType)
	{
		$this->ksType=$ksType;
	}
	public function setKsTypeAdmin()
	{
		$this->ksType=KalturaSessionType::ADMIN;
	}
	public function setKsTypeUser()
	{
		$this->ksType=KalturaSessionType::USER;
	}
	
	
	public function getClient()
	{
		//todo read client info from default config file
		
		//read all client parameters from config file
		return $this->prvGetClient($this->partnerId,$this->secret,$this->destUrl,$this->ksType);
	}

	private function prvGetClient($partnerId,$secret,$destUrl,$ksType)
	{
		$ksObj = new ksManager;
		$ks = $ksObj->getKs($partnerId,$secret,$destUrl,$ksType);
		$config = new KalturaConfiguration($partnerId);
        $config->serviceUrl = $destUrl;
        $client = new KalturaClient($config);
		$client->setKs($ks);
		return $client;
	}
}