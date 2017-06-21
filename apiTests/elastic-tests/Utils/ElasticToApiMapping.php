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
				return new ElasticToApiMatchData('name', ElasticConstants::TYPE_STRING, ElasticToApiMatchData::STRING_TEXT);
			case 'description':
				return new ElasticToApiMatchData('description', ElasticConstants::TYPE_STRING, ElasticToApiMatchData::STRING_TEXT);
			case 'tags':
				return new ElasticToApiMatchData('tags', ElasticConstants::TYPE_STRING, ElasticToApiMatchData::STRING_TEXT);
			case 'admin_tags':
				return new ElasticToApiMatchData('adminTags', ElasticConstants::TYPE_STRING, ElasticToApiMatchData::STRING_TEXT);
			case 'category_ids':
				return new ElasticToApiMatchData('adminTags', ElasticConstants::TYPE_STRING, ElasticToApiMatchData::STRING_TEXT);
			case 'puser_id':
				return new ElasticToApiMatchData('userId', ElasticConstants::TYPE_STRING, ElasticToApiMatchData::STRING_TEXT);
			case 'start_time':
				// TODO return new ElasticToApiMatchData('startDate', ElasticConstants::TYPE_NUMBER, ElasticToApiMatch::EXACT_AND_RANGE);
				return new ElasticToApiMatchData('startDate', ElasticConstants::TYPE_NUMBER, ElasticToApiMatchData::EXACT_ONLY);
			case 'end_time':
				// TODO return new ElasticToApiMatchData('endDate', ElasticConstants::TYPE_NUMBER, ElasticToApiMatch::EXACT_AND_RANGE);
				return new ElasticToApiMatchData('endDate', ElasticConstants::TYPE_NUMBER, ElasticToApiMatchData::EXACT_ONLY);
			case 'category_ids':
				return new ElasticToApiMatchData('categories', ElasticConstants::TYPE_STRING, ElasticToApiMatchData::STRING_TEXT);
			case 'status':
				// TODO return new ElasticToApiMatch('status', ElasticConstants::TYPE_NUMBER, ElasticToApiMatch::EXACT_AND_RANGE);
				return new ElasticToApiMatchData('status', ElasticConstants::TYPE_NUMBER, ElasticToApiMatchData::EXACT_ONLY);
			case 'moderation_status':
				// TODO return new ElasticToApiMatch('moderationStatus', ElasticConstants::TYPE_NUMBER, ElasticToApiMatch::EXACT_AND_RANGE);
				return new ElasticToApiMatchData('moderationStatus', ElasticConstants::TYPE_NUMBER, ElasticToApiMatchData::EXACT_ONLY);
			case 'moderation_count':
				// TODO return new ElasticToApiMatch('moderationCount', ElasticConstants::TYPE_NUMBER, ElasticToApiMatch::EXACT_AND_RANGE);
				return new ElasticToApiMatchData('moderationCount', ElasticConstants::TYPE_NUMBER, ElasticToApiMatchData::EXACT_ONLY);
			case 'created_at':
				// TODO return new ElasticToApiMatch('createdAt', ElasticConstants::TYPE_DATE, ElasticToApiMatch::EXACT_AND_RANGE);
				return new ElasticToApiMatchData('createdAt', ElasticConstants::TYPE_DATE, ElasticToApiMatchData::EXACT_ONLY);
			case 'updated_at':
				// TODO return new ElasticToApiMatch('updatedAt', ElasticConstants::TYPE_DATE, ElasticToApiMatch::EXACT_AND_RANGE);
				return new ElasticToApiMatchData('updatedAt', ElasticConstants::TYPE_DATE, ElasticToApiMatchData::EXACT_ONLY);
			case 'conversion_profile_id':
				// TODO return new ElasticToApiMatch('conversionProfileId', ElasticConstants::TYPE_NUMBER, ElasticToApiMatch::EXACT_AND_RANGE);
				return new ElasticToApiMatchData('conversionProfileId', ElasticConstants::TYPE_NUMBER, ElasticToApiMatchData::EXACT_ONLY);
			case 'reference_id':
				return new ElasticToApiMatchData('referenceId', ElasticConstants::TYPE_STRING, ElasticToApiMatchData::STRING_TEXT);
			case 'display_in_search':
				// TODO return new ElasticToApiMatch('displayInSearch', ElasticConstants::TYPE_NUMBER, ElasticToApiMatch::EXACT_AND_RANGE);
				return new ElasticToApiMatchData('displayInSearch', ElasticConstants::TYPE_NUMBER, ElasticToApiMatchData::EXACT_ONLY);
			case 'source_type':
				// TODO return new ElasticToApiMatch('sourceType', ElasticConstants::TYPE_NUMBER, ElasticToApiMatch::EXACT_AND_RANGE);
				return new ElasticToApiMatchData('sourceType', ElasticConstants::TYPE_NUMBER, ElasticToApiMatchData::EXACT_ONLY);
			case 'duration':
				// TODO return new ElasticToApiMatch('duration', ElasticConstants::TYPE_NUMBER, ElasticToApiMatch::EXACT_AND_RANGE);
				return new ElasticToApiMatchData('duration', ElasticConstants::TYPE_NUMBER, ElasticToApiMatchData::EXACT_ONLY);
			case 'recorded_entry_id':
				// TODO return new ElasticToApiMatch('recordedEntryId', ElasticConstants::TYPE_STRING, ElasticToApiMatch::EXACT_AND_RANGE);
				return new ElasticToApiMatchData('recordedEntryId', ElasticConstants::TYPE_STRING, ElasticToApiMatchData::EXACT_ONLY);
			case 'template_entry_id':
				return new ElasticToApiMatchData('templateEntryId', ElasticConstants::TYPE_STRING, ElasticToApiMatchData::EXACT_ONLY);
			case 'redirect_entry_id':
				return new ElasticToApiMatchData('redirect_entry_id', ElasticConstants::TYPE_STRING, ElasticToApiMatchData::EXACT_ONLY);
			case 'parent_id':
				// TODO return new ElasticToApiMatch('parentId', ElasticConstants::TYPE_STRING, ElasticToApiMatch::EXACT_AND_RANGE);
				return new ElasticToApiMatchData('parentEntryId', ElasticConstants::TYPE_STRING, ElasticToApiMatchData::EXACT_ONLY);
			case 'push_publish':
				return new ElasticToApiMatchData('pushPublish', ElasticConstants::TYPE_STRING, ElasticToApiMatchData::EXACT_ONLY);
			case 'entitled_kusers_edit':
				return new ElasticToApiMatchData('entitledUsersEdit', ElasticConstants::TYPE_STRING, ElasticToApiMatchData::STRING_TEXT);
			case 'entitled_kusers_publish':
				return new ElasticToApiMatchData('entitledUsersPublish', ElasticConstants::TYPE_STRING, ElasticToApiMatchData::STRING_TEXT);
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
