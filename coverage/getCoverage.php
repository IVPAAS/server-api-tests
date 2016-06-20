<?php
/**
 * Created by IntelliJ IDEA.
 * User: noam.arad
 * Date: 7/26/2015
 * Time: 10:57 AM
 */
header('Content-Type: application/json');
$initialData = file_get_contents('./allServices.txt');
//print_r($initialData);
$dict = json_decode($initialData);
//$theFile = fopen("/tmp/theFile.txt","r");
$theFile = fopen("/opt/kaltura/log/kaltura_apache_access.log","r");

$excludedServices = array("thumbAsset", "schema");

//$dict = array();
while (($currLine = fgets ($theFile)) !== false)
{
//	echo "trying current line [$currLine] <br/>";
	preg_match("/api_v3.*(service=)(.*)&(action=)([a-zA-Z]*)&.*$/", $currLine, $matches);
	if (!count($matches))
	{
		preg_match("/.*api_v3.*.*\/(service)\/(.*)\/(action)\/(.*)[\/|\?|&|\s]HTTP.*$/", $currLine, $matches);
		if (!count($matches))
		{
			continue;
		}
	}
	$serviceName = $matches[2];
	$actionName = $matches[4];
	if (!isset($dict->$serviceName))
	{
		$dict->$serviceName = new stdClass();
	}
	if (!isset($dict->$serviceName->$actionName))
	{
//		$dict->$serviceName->$actionName = array("counter" => 0, "lastUpdate" => 0);
		$dict->$serviceName->$actionName = new stdClass();
		$dict->$serviceName->$actionName->counter = 0;
		$dict->$serviceName->$actionName->lastUpdate = 0;
	}
//	echo print_r($dict->$serviceName,true)." actionName [$actionName]";
	$dict->$serviceName->$actionName->counter++;
	$dict->$serviceName->$actionName->lastUpdate = time();
}
fclose($theFile);
//echo "dict = [".print_r($dict,true)."]";
$jsonOutput = json_encode($dict);
echo "$jsonOutput";
//echo "finished <br />";

?>
