<?php


class ElasticConstants
{
	const ENTRY_INDEX_TYPE = "entry";
	const CATEGORY_INDEX_TYPE = "category";
	const KUSER_INDEX_TYPE = "kuser";

	const SEARCH_TYPE_EXACT_MATCH = 1;
	const SEARCH_TYPE_PARTIAL = 2;
	const SEARCH_TYPE_STARTS_WITH = 3;
	const SEARCH_TYPE_DOESNT_CONTAIN = 4;
	// TODO add when implemented
	const SEARCH_TYPE_RANGE = 5;

	const SEARCH_TYPE_EXACT_MATCH_TEXT = 'EXACT_MATCH';
	const SEARCH_TYPE_PARTIAL_TEXT = "PARTIAL";
	const SEARCH_TYPE_STARTS_WITH_TEXT = 'STARTS_WITH';
	const SEARCH_TYPE_DOESNT_CONTAIN_TEXT = 'DOESNT_CONTAIN';
	// TODO add when implemented
	const SEARCH_TYPE_RANGE_TEXT = 'RANGE';

	const TYPE_STRING = 1;
	const TYPE_NUMBER = 2;
	const TYPE_ARRAY = 3;
	const TYPE_DATE = 4;

	const CONF_DC_URL = 'dc_url';
	const CONF_ELASTIC_HOST = 'elastic_host';
	const CONF_ELASTIC_PORT = 'elastic_port';
	const CONF_GENKS_PATH = 'genks_path';
	const CONF_KALCLI_PATH = 'kalcli_path';
	const CONF_SPECIFIC_PARTNER = 'run_on_specific_partner';
	const CONF_NUM_PARTNERS_TO_DRAW = 'num_of_partners_to_draw';
	const CONF_NUM_DOCS_PER_PARTNER = 'num_docs_draw_per_partner';
	const CONF_MAX_PARTNER_ID = 'max_partner_id';
	const CONF_LOG_DIR = 'log_dir';
	const CONF_TYPE_ENTRY = 'entry_index_test';
	const CONF_TYPE_CATEGORY = 'category_index_test';
	const CONF_TYPE_KUSER = 'kuser_index_test';
	const CONF_TESTS_TO_RUN = 'tests_to_run';
	const CONF_INDEX_NAME = 'index_name';

	const CONF_TEST_TYPE_RANDOM = "random";




}