<?php
require_once(dirname(__FILE__) .'/ElasticConstants.php');

class ElasticToApiMapping
{

	public static function getMappingFor($indexType, $elasticName)
	{
		switch ($indexType)
		{
			case ElasticConstants::ENTRY_INDEX_TYPE:
				return self::getEntryMappingFor($elasticName);
			case ElasticConstants::KUSER_INDEX_TYPE:
				return self::getKuserMappingFor($elasticName);
			case ElasticConstants::CATEGORY_INDEX_TYPE:
				return self::getCategoryMappingFor($elasticName);
		}
	}

	private static function getEntryMappingFor($elasticName)
	{
		switch ($elasticName)
		{
			case 'name':
				return 'name';
			case 'description':
				return 'description';
			case 'tags':
				return 'tags';
			case 'admin_tags':
				return 'adminTags';
			case 'category_ids':
				return 'adminTags';
			case 'puser_id':
				return 'userId';
			case 'start_time':
				return 'startDate';
			case 'end_time':
				return 'endDate';
			case 'category_ids':
				return 'categories';
			case 'status':
				return 'status';
			case 'moderation_status':
				return 'moderationStatus';
			case 'moderation_count':
				return 'moderationCount';
			case 'created_at':
				return 'createdAt';
			case 'updated_at':
				return 'updatedAt';
			case 'conversion_profile_id':
				return 'conversionProfileId';
			case 'reference_id':
				return 'referenceId';
			case 'display_in_search':
				return 'displayInSearch';
			case 'source_type':
				return 'sourceType';
			case 'duration':
				return 'duration';
			case 'recorded_entry_id':
				return 'recordedEntryId';
			case 'template_entry_id':
				return 'templateEntryId';
			case 'redirect_entry_id':
				return 'redirect_entry_id';
			case 'parent_id':
				return 'parentEntryId';
			case 'push_publish':
				return 'pushPublish';
			case 'entitled_kusers_edit':
				return 'entitledUsersEdit';
			case 'entitled_kusers_publish':
				return 'entitledUsersPublish';
			default:
				throw new Exception("Have you forgotten about some ENTRY field: $elasticName");
		}
	}

	private static function getKuserMappingFor($elasticName)
	{

	}

	private static function getCategoryMappingFor($elasticName)
	{

	}
}

class ElasticToApiMatchData
{
	public $apiName;
	public $type;
	public $availableSearchTypes;

	const EXACT_ONLY = 1;
	// TODO add after range is supported
//	const RANGE_ONLY = 2;
//	const EXACT_AND_RANGE = 3;
	const STRING_TEXT = 4;


	public function __construct($name, $type, $searchTypes)
	{
		$this->apiName = $name;
		$this->type = $type;
		$this->availableSearchTypes = $this::getSearchTypeMappingByConst($searchTypes);
	}

	private function getSearchTypeMappingByConst($type)
	{
		switch ($type)
		{
			case ElasticToApiMatchData::EXACT_ONLY:
				return array(ElasticConstants::SEARCH_TYPE_EXACT_MATCH);
			// TODO add after range is supported
//			case ElasticToApiMatch::RANGE_ONLY:
//				return array(ElasticConstants::SEARCH_TYPE_RANGE);
//			case ElasticToApiMatch::EXACT_AND_RANGE:
//				return array(ElasticConstants::SEARCH_TYPE_RANGE, ElasticConstants::SEARCH_TYPE_EXACT_MATCH);
			case ElasticToApiMatchData::STRING_TEXT:
				return array(ElasticConstants::SEARCH_TYPE_PARTIAL, ElasticConstants::SEARCH_TYPE_EXACT_MATCH, ElasticConstants::SEARCH_TYPE_DOESNT_CONTAIN, ElasticConstants::SEARCH_TYPE_STARTS_WITH);
			default:
				throw new Exception('Unexpected value given for getSearchTypeMappingByConst');
		}
	}

}
