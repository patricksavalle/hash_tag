<?php /** @noinspection SqlResolve */

class Hash_tag_upd {

	var $version			= '1.0';

	function install()
	{
		ee()->db->query("INSERT INTO exp_modules (module_name, module_version, has_cp_backend)
				  VALUES ('Hash_tag', '$this->version', 'n')");

		ee()->db->query("CREATE TABLE IF NOT EXISTS exp_hash_tags (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			entry_id INT UNSIGNED NOT NULL,
			hash_tag CHAR(30) NOT NULL,
			field_id INT UNSIGNED NOT NULL,
			date_time DATETIME NOT NULL DEFAULT NOW(),
			PRIMARY KEY id (id),
			KEY entry_id (entry_id),
			KEY hash_tag(hash_tag)
		) DEFAULT CHARACTER SET ".ee()->db->escape_str(ee()->db->char_set)." COLLATE ".ee()->db->escape_str(ee()->db->dbcollat));

		return TRUE;
	}

	function uninstall()
	{
		$query = ee()->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Hash_tag'");

		ee()->db->query("DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'");
		ee()->db->query("DELETE FROM exp_modules WHERE module_name = 'Hash_tag'");
		ee()->db->query("DELETE FROM exp_actions WHERE class = 'Hash_tag'");
		ee()->db->query("DROP TABLE IF EXISTS exp_hash_tags");

		ee()->config->_update_config('', []);

		return TRUE;
	}

	function update($current = '')
	{
		if ($current == $this->version)
		{
  			return FALSE;
		}
		ee()->db->query("UPDATE exp_modules SET module_version='{$this->version}' WHERE module_name='Hash_tag'");
		return TRUE;
	}
}

// EOF
