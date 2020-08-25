<?php

class Hash_tag_ext
{
	public function __construct()
	{
		$this->version = ee('Addon')->get('hash_tag')->getVersion();
	}

	public function activate_extension()
	{
		$hooks = array(
			'after_channel_entry_update' => 'index_hash_tags',
			'after_channel_entry_delete' => 'delete_hash_tags',
		);

		foreach ($hooks as $hook => $method)
		{
			ee('Model')->make('Extension', [
				'class'    => __CLASS__,
				'method'   => $method,
				'hook'     => $hook,
				'settings' => [],
				'version'  => $this->version,
				'enabled'  => 'y'
			])->save();
		}
	}

	/*
	 * try to enforce a uniform tag format
	 */
	private function normalize_tag(string $tag)
	{
		return strtolower(str_replace("_", "-", $tag));
	}

	/*
	 * Remove hashtags from the index
	 */
	/** @noinspection PhpUnusedParameterInspection */
	public function delete_hash_tags($entry, $values)
	{
		$entry_id = $values['entry_id'];
		ee()->db->query("DELETE FROM exp_hash_tags WHERE entry_id = {$entry_id}" );
		// clear all affected potential caches
		ee()->cache->delete("hash_tag/index{$values['channel_id']}");
		ee()->cache->delete("hash_tag/index");
	}

	/*
	 * Harvest hashtag from an entry and insert them in the index
	 */
	/** @noinspection PhpUnusedParameterInspection */
	public function index_hash_tags($entry, $values)
	{
		// should be a transaction together with the item submit by EE doesn't work that way
		$entry_id = $values['entry_id'];
		ee()->db->query("DELETE FROM exp_hash_tags WHERE entry_id = {$entry_id}" );

		// harvest the title and first 10 data fields, quick, not really dirty
		$sql = null;
		// title
		$content = @$values["title"];
		preg_match_all("/ #([a-zA-Z0-9\-_]{3,31}+)/", $content, $matches);
		foreach ($matches[1] as $match)
		{
			$sql[] = "('" . $this->normalize_tag($match) . "','" . $entry_id . "','0')";
		}
		// field_id_1 .. field_id_10
		for($field_id=1; $field_id<=10; $field_id++)
		{
			$content = @$values["field_id_$field_id"];
			if (!empty($content))
			{
				preg_match_all("/ #([a-zA-Z0-9\-_]{3,31}+)/", $content, $matches);
				foreach ($matches[1] as $match)
				{
					$sql[] = "('" . $this->normalize_tag($match) . "','" . $entry_id . "','" . $field_id . "')";
				}
			}
		}
		if (!empty($sql))
		{
			ee()->db->query("INSERT INTO exp_hash_tags(hash_tag, entry_id, field_id) VALUES" . implode(",", $sql));
		}

		// clear all affected potential caches -> not useful on a blog with lots of new content
		// better strategy is shorter caching ttl's (hour) and let caches intact on updates
		// ee()->cache->delete("hash_tag/index{$values['channel_id']}");
		// ee()->cache->delete("hash_tag/index");
	}

	function disable_extension()
	{
		ee('Model')->get('Extension')
			->filter('class', __CLASS__)
			->delete();
	}

}

// EOF
