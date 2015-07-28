<?php
require_once('/opt/kaltura/app/api_v3/bootstrap.php');
$services = unserialize(file_get_contents('/opt/kaltura/app/cache/testme/services-testme'));
$fullDict = array();
foreach ($services as $serviceName => $serviceData)
{	
	$fullDict[$serviceName] = array();
	foreach ($serviceData->actionMap as $actionName => $actionData)
	{
		$fullDict[$serviceName][$actionName] = array("counter" => 0, "lastUpdate" => 0);
	}
}
$jsonOutput = json_encode($fullDict);
$outfile = fopen('/tmp/allServices.txt','w');
fwrite($outfile, $jsonOutput);
fclose($outfile);
?>