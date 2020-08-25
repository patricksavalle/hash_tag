<?php

class Hash_tag {

	var $return_data = '';

	function __construct()
	{
		$hash_tag = ee()->TMPL->fetch_param('hash_tag');
		$url_title = ee()->TMPL->fetch_param('url_title');
		if (empty($hash_tag) AND empty($url_title))
		{
			// do nothing, probably invoked implicitly
			return;
		}
		$field_id = ee()->TMPL->fetch_param('field_id');
		if ( empty($field_id))
		{
			ee()->output->fatal_error('The "field_id" parameter is required. {exp:hash_tag field_id="..."}');
		}
		$channel = ee()->TMPL->fetch_param('channel');
		$limit = ee()->TMPL->fetch_param('limit');
		if (empty($limit))
		{
			$limit = 5;
		}
		if (!empty($hash_tag))
		{
			// select all entries for specified hashtag
			$sql = "SELECT t.entry_id, t.hash_tag, c.*, d.field_id_{$field_id},
    	   			(SELECT channel_name FROM exp_channels WHERE channel_id = c.channel_id) as channel_name
				FROM exp_hash_tags AS t
				JOIN exp_channel_titles AS c ON t.entry_id=c.entry_id
				JOIN exp_channel_data_field_{$field_id} AS d ON d.entry_id=t.entry_id
				WHERE c.status='open'
				  	AND (c.expiration_date='0' OR c.expiration_date>NOW())
				  	AND t.hash_tag LIKE  '". $hash_tag . "%'
				  	AND ('". $channel . "'='' OR c.channel_id='". $channel . "')
				GROUP BY c.entry_id
				ORDER BY c.entry_date DESC
				LIMIT {$limit}";
		}
		elseif (!empty($url_title))
		{
			// select entries with related tags, sort on number of matching tags
			$sql = "SELECT hash_tag, c.*, d.field_id_{$field_id},
	       			(SELECT channel_name FROM exp_channels WHERE channel_id = c.channel_id) as channel_name
				FROM exp_channel_titles AS c
				JOIN exp_hash_tags AS t on t.entry_id=c.entry_id
				JOIN exp_channel_data_field_{$field_id} AS d ON d.entry_id=t.entry_id
				WHERE c.url_title<> '" . $url_title . "'
				AND c.status='open'
				AND ('". $channel . "'='' OR c.channel_id='". $channel . "')
				AND (c.expiration_date='0' OR c.expiration_date>NOW())
				AND hash_tag IN (
					SELECT DISTINCT t.hash_tag
					FROM exp_hash_tags AS t
					JOIN exp_channel_titles AS c ON c.entry_id=t.entry_id
					WHERE c.status='open'
						AND (c.expiration_date='0' OR c.expiration_date>NOW())
						AND c.url_title='" . $url_title . "'
				)
				GROUP BY c.entry_id
				ORDER BY count(DISTINCT c.entry_id) desc
				LIMIT {$limit}";
		}
		$query = ee()->db->query($sql);
		if ($query->num_rows() == 0)
		{
			$this->return_data = ee()->TMPL->no_results();
		}
		$results = $query->result_array();

		// Start up pagination
		ee()->load->library('pagination');
		$pagination = ee()->pagination->create();
		ee()->TMPL->tagdata = $pagination->prepare(ee()->TMPL->tagdata);
		$per_page = ee()->TMPL->fetch_param('limit', 0);

		// Disable pagination if the limit parameter isn't set
		if (empty($per_page))
		{
			$pagination->paginate = FALSE;
		}

		if ($pagination->paginate)
		{
			$pagination->build($query->num_rows(), $per_page);
			$results = array_slice($results, $pagination->offset, $pagination->per_page);
		}

		$this->return_data = ee()->TMPL->parse_variables(ee()->TMPL->tagdata, array_values($results));

		if ($pagination->paginate === TRUE)
		{
			$this->return_data = $pagination->render($this->return_data);
		}
	}

	/*
	 * Display an index grouped by first letter
	 */

	function index()
	{
		$channel = ee()->TMPL->fetch_param('channel');
		$xml = ee()->cache->get("hash_tag/index{$channel}");
		if (empty($xml))
		{
			$query = "SELECT t.hash_tag, COUNT(DISTINCT c.entry_id) AS entry_count
				 FROM exp_hash_tags AS t
				 JOIN exp_channel_titles AS c ON t.entry_id=c.entry_id
				 WHERE c.status='open'
				   	AND (c.expiration_date='0' OR c.expiration_date>NOW())
					AND ('". $channel . "'='' OR c.channel_id='". $channel . "')
				 GROUP BY t.hash_tag
				 ORDER BY t.hash_tag";

			$prev_letter = null;
			// the XML-template for items
			$lformat = ee()->TMPL->fetch_param('item_format');
			// the XML-template for separators
			$sformat = ee()->TMPL->fetch_param('separator_format');
			$xml = "";
			$result = ee()->db->query($query)->result_array();
			foreach ( $result as $row)
			{
				if (!empty($sformat) and $prev_letter != $row["hash_tag"][0])
				{
					$xml .= sprintf($sformat, ($prev_letter = $row["hash_tag"][0]));
				}
				$xml .= sprintf($lformat, $row["hash_tag"], $row["entry_count"]);
			}
			ee()->cache->save("hash_tag/index{$channel}", $xml, 60*60 /* one hour */);
		}
		return $xml;
	}

	function top()
	{
		$channel = ee()->TMPL->fetch_param('channel');
		$interval = ee()->TMPL->fetch_param('interval');
		$count = ee()->TMPL->fetch_param('count');
		if (empty($count))
		{
			$count = 10;
		}
		if (!in_array($interval, ['day', 'week', 'month', 'quarter', 'year']))
		{
			ee()->output->fatal_error('The "interval" parameter must be "day", "week", "month", "quarter" or "year" ');
		}
		$xml = ee()->cache->get("hash_tag/{$interval}{$channel}");
		if (empty($xml))
		{
			$query = "SELECT t.hash_tag, COUNT(DISTINCT c.entry_id) AS entry_count
						FROM exp_hash_tags AS t
						JOIN exp_channel_titles AS c ON t.entry_id=c.entry_id
						WHERE c.status='open'
							AND (c.expiration_date='0' OR c.expiration_date>NOW())
							AND ('". $channel . "'='' OR c.channel_id='". $channel . "')
						    AND (FROM_UNIXTIME(c.entry_date) > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 ". $interval . "))
						GROUP BY t.hash_tag
						ORDER BY count(t.hash_tag) DESC
						LIMIT " . $count;

			// the XML-template for items
			$format = ee()->TMPL->fetch_param('item_format');
			$xml = "";
			$result = ee()->db->query($query);
			foreach ($result->result_array() as $row)
			{
				$xml .= sprintf($format, $row["hash_tag"], $row["entry_count"]);
			}
			ee()->cache->save("hash_tag/{$interval}{$channel}", $xml, 60*60*8 /* 8 hours */ );
		}
		return $xml;
	}

	function dehash()
	{
		return preg_replace("/ #([a-zA-Z0-9\-_]{3,31}+)/", " $1", ee()->TMPL->tagdata);
	}

	function keywords()
	{
		preg_match_all(
			"/ #([a-zA-Z0-9\-_]{3,31}+)/",
			ee()->TMPL->tagdata,
			$matches);
		return implode( ",", array_values($matches[1]));
	}

	function linkify()
	{
		// #tag -> <a href="/index/tag">#tag</a>
		$segment_1 = ee()->TMPL->fetch_param('segment_1');
		$class = ee()->TMPL->fetch_param('class');
		if ( ! ee()->TMPL->fetch_param('segment_1'))
		{
			ee()->output->fatal_error('The "segment_1" parameter is required. {exp:hash_tag:linkify segment_1="..."}');
		}
		$replacement = ' <a class="' . $class . '" href="/' . $segment_1 . '/${1}">#${1}</a>';
		$text = ee()->TMPL->tagdata;
		return preg_replace("/ #([a-zA-Z0-9\-_]{3,31}+)/", $replacement, $text);
	}

}

// EOF
