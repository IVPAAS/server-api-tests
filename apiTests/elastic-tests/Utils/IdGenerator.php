<?php

class IdGenerator
{

	public static function generateRandomEntryId()
	{
		return rand(0,1)."_".substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', mt_rand(1,10))),1,8);
	}

	public static function generateEntryIds($numberOfIds = 100000)
	{
		$ids = array();
		for ($i = 0; $i <  $numberOfIds ; $i++)
			array_push($ids, self::generateRandomEntryId());

		return $ids;

	}

}



