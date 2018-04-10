<?php

namespace numeric\raidschedule;

class update_event_list {

	protected $helper;

	protected $config;

	protected $db;

	protected $day_ary = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');

	public function __construct(\phpbb\controller\helper $helper, \phpbb\config\config $config, \phpbb\db\driver\driver_interface $db)
	{
		$this->helper = $helper;
		$this->config = $config;
		$this->db = $db;
	}

	/*
		Recurrence

		cid 			= integer, recurrence id
		start			= integer, time recurrent event begins
		end				= integer, time recurrent event ends or 0 for never
		last_evt		= integer, time of last event added
		repeat			= enum, D = Daily event, W = Weekly event, M = Monthly event, Y = Yearly event
		repeat_int		= integer, interval of event (value depends on repeat, e.g. days, weeks, months, years)
		repeat_type_int	= char, colon deliminated list of integers representing the interval between events
	*/
	function update_event_list($start_time, $till_time)
	{
		$sql = 'SELECT *
			FROM event_list_recur
			WHERE start <= ' . $till_time . '
				AND (end >= ' . $start_time . '
					OR end = 0)
				AND last_evt <= ' . $till_time;
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$evt_sql_ary = array();

			switch ($row['repeat_type'])
			{
				case 'D':
					break;

				case 'W':
					$evt_day_ary = explode(',', $row['repeat_type_int']);
					@sort($evt_day_ary);

					$loop_time 	= ($row['last_evt']) ? (int) $row['last_evt'] : (int) $row['start'];

					list($loop_day, $evt_hour, $evt_minute) = explode(':', date('w:H:i', $loop_time));

					$k = 0;
					// Need to find which day to begin on ... so, what we'll do is compare the last
					// numeric (0-6) day to be added to the last recurrent day (evt_day_ary). If they're
					// equal we start at the beginning, else we'll start at next day in the recurrence
					if ($evt_day_ary[count($evt_day_ary) - 1] != $loop_day)
						foreach ($evt_day_ary as $k => $evt_day)
							if ($evt_day > $loop_day)
							{
								$evt_day_ary = array_merge(array_slice($evt_day_ary, $k), array_slice($evt_day_ary, 0, $k));
								break;
							}


					$last_evt = 0;
					// We now start iterating through the days ...
					// We have the starting time, we have the starting date, we know
					// the days (and weeks) on which events should be held. So from the $loop_time
					// we jump to the next day
					while ($loop_time <= $till_time && ($loop_time <= $row['end'] || !$row['end']))
					{
						foreach ($evt_day_ary as $evt_day)
						{
							// Obtain the new time - next <Day> + hour + minute + week offset
							$loop_time = strtotime('next ' . $this->day_ary[$evt_day], $loop_time) + ($evt_hour * 3600) + ($evt_minute * 60) + (($row['repeat_int'] - 1) * 604800);

							// If the event is in the past, ignore it
							if ($loop_time < time())
								continue;

							// break if we've exceeded the till or end time
							if ($loop_time > $till_time ||($loop_time > $row['end'] && $row['end']))
								break;

							// Set last_evt to this loop for updating the last_evt field
							$last_evt = $loop_time;

							$evt_sql_ary[] = '(' . (int) $row['cid'] . ', ' . $loop_time . ", " . (int) $row['eid'] . ")";
						}
					}
					break;

				case 'M':
					break;

				case 'Y':
					break;
			}

			if (sizeof($evt_sql_ary))
			{
				$sql = '';
				foreach ($evt_sql_ary as $evt_sql)
					$sql .= $evt_sql . ', ';

				$this->db->sql_transaction('begin');

				$sql = 'INSERT INTO event_list (cid, start, eid)
					VALUES ' . substr($sql, 0, -2);
				$this->db->sql_query($sql);

				$sql = "UPDATE event_list_recur
					SET last_evt = $last_evt
					WHERE cid = " . $row['cid'];
				$this->db->sql_query($sql);

				$this->db->sql_transaction('end');
			}
		}
		$this->db->sql_freeresult($result);

		return;
	}
}
