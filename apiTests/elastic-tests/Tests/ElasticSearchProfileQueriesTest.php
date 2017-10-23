<?php
if(count($argv) != 4)
	die("Usage is query type partner id search key word\nOptions for query type are:\n1 Captions\n2 Thumb cue point\n3 ".
		"Annotation cue points\n");

$queryType = $argv[1];
$partnerId = $argv[2];
$searchKeyword = $argv[3];
$sp = "searchParams";
$si="searchItems";
$sp_so = "$sp:searchOperator";
$query = "genks $partnerId | kalcli elasticsearch_esearch searchEntry";
switch ($queryType)
{
	case 1: //caption
		$query = $query." $sp:objectType=KalturaESearchParams $sp_so:objectType=KalturaESearchOperator $sp_so:operator=2 ".
			"$sp_so:$si:0:objectType=KalturaESearchCaptionItem $sp_so:$si:0:itemType=2 $sp_so:$si:0:fieldName=caption_assets.lines.content $sp_so:$si:0:searchTerm='$searchKeyword'\n";
		break;
	case 2: //thumb cue point
		$query = $query." $sp:objectType=KalturaESearchParams $sp_so:objectType=KalturaESearchOperator $sp_so:operator=1 ".
			"$sp_so:$si:0:objectType=KalturaESearchOperator $sp_so:$si:0:operator=2  $sp_so:$si:0:searchItems:0:objectType=KalturaESearchCuePointItem ".
			"$sp_so:$si:0:$si:0:objectType=KalturaESearchCuePointItem $sp_so:$si:0:$si:0:fieldName=cue_points.cue_point_tags $sp_so:$si:0:$si:0:itemType=1 $sp_so:$si:0:$si:0:searchTerm='$searchKeyword' ".
			"$sp_so:$si:0:$si:1:objectType=KalturaESearchCuePointItem $sp_so:$si:0:$si:1:fieldName=cue_points.cue_point_text $sp_so:$si:0:$si:1:itemType=2 $sp_so:$si:0:$si:1:searchTerm='$searchKeyword' ".
			"$sp_so:$si:1:objectType=KalturaESearchCuePointItem $sp_so:$si:1:fieldName=cue_points.cue_point_type $sp_so:$si:1:itemType=1 $sp_so:$si:1:searchTerm='thumbCuePoint.Thumb'\n";
		break;
	case 3:
		$query = $query." $sp:objectType=KalturaESearchParams $sp_so:objectType=KalturaESearchOperator $sp_so:operator=1 ".
			"$sp_so:$si:0:objectType=KalturaESearchOperator $sp_so:$si:0:operator=2  $sp_so:$si:0:searchItems:0:objectType=KalturaESearchCuePointItem ".
			"$sp_so:$si:0:$si:0:objectType=KalturaESearchCuePointItem $sp_so:$si:0:$si:0:fieldName=cue_points.cue_point_tags $sp_so:$si:0:$si:0:itemType=1 $sp_so:$si:0:$si:0:searchTerm='$searchKeyword' ".
			"$sp_so:$si:0:$si:1:objectType=KalturaESearchCuePointItem $sp_so:$si:0:$si:1:fieldName=cue_points.cue_point_text $sp_so:$si:0:$si:1:itemType=2 $sp_so:$si:0:$si:1:searchTerm='$searchKeyword' ".
			"$sp_so:$si:1:objectType=KalturaESearchCuePointItem $sp_so:$si:1:fieldName=cue_points.cue_point_type $sp_so:$si:1:itemType=1 $sp_so:$si:1:searchTerm='annotation.Annotation'\n";
		break;
}

echo $query;
