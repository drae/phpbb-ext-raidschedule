<?php
/**
 *
 *
 * @copyright (c) Numeric
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * For full copyright and license information, please see
 * the docs/CREDITS.txt file.
 *
 */

namespace numeric\raidschedule\acp;

use numeric\raidschedule\constants;

class raidschedule_module
{
	public $u_action;

	protected $instances = array();

	protected $days_ary = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');

	public function __construct()
	{
		global $db, $user, $cache, $request, $template;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpbb_container, $phpEx, $phpbb_log;

		include_once __DIR__ . '/../constants.' . $phpEx;

		$this->root_path = $phpbb_root_path . 'ext/numeric/raidschedule/';

		$this->db = $db;
		$this->user = $user;
		$this->cache = $cache;
		$this->request = $request;
		$this->template = $template;
		$this->config = $config;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpbb_admin_path = $phpbb_admin_path;
		$this->phpbb_container = $phpbb_container;
		$this->phpEx = $phpEx;
		$this->phpbb_log = $phpbb_log;

		$this->user->add_lang('acp/common');
		$this->user->add_lang_ext('numeric/raidschedule', 'acp_raidschedule');
	}

	function main($id, $mode)
	{
		$this->tpl_name = 'template/acp_calendar';
		$this->page_title = $this->user->lang('ACP_RAID_SCHEDULE');

		$json_response = new \phpbb\json_response;
		$update_event_list = $this->phpbb_container->get('numeric.raidschedule.update_event_list');

		$mode = $this->request->variable('mode', 'calendar');
        $action = $this->request->variable('action', '');
		$recurse = $this->request->variable('recurse', '');
        $cur_month = $this->request->variable('month', (int) date('n'));
        $cur_year = $this->request->variable('year', (int) date('Y'));

        $rid = $this->request->variable('rid', 0);
		$r_time	= $this->request->variable('time', 0);
        $r_instance	= $this->request->variable('instance', '');
        $r_repeat_type = $this->request->variable('repeat_type', '');
        $r_repeat_int = $this->request->variable('repeat_int', 1);
        $r_repeat_end = $this->request->variable('repeat_end', '');
		$r_repeat_days = $this->request->variable('repeat_day', array(0));

		// String containing any error messages to be returned
        $error = '';

        /**
        * Grab all raid events
        */
        $sql = 'SELECT ec.category, et.*
            FROM event_types_cat ec, event_types et
            WHERE et.type IN (' . constants::CAL_EVENT_RAID . ', ' . constants::CAL_EVENT_OTHER . ')
                AND ec.ecid = et.ecid
            ORDER BY ec.order DESC, et.eid ASC';
        $result = $this->db->sql_query($sql, 3600);

        while ($row = $this->db->sql_fetchrow($result))
		{
            $this->instances[$row['eid']] = $row;
		}
        $this->db->sql_freeresult($result);

		if ($mode == 'newraid')
		{
			if ($action == 'submit')
			{
				exit;
			}

			$this->template->assign_vars(array(
				'U_ACTION'		=> $this->u_action,
			));

			exit;
		}

		/**
		 *
		**/
		switch ($action)
		{
			/**
			 * Fetch all events for currently defined month/year
			**/
			case 'fetch':
				$first_of_month = \DateTime::createFromFormat('Y-m-d-H:i:s', $cur_year . '-' . $cur_month . '-1-00:00:00');
				$last_of_month = clone $first_of_month;
				$last_of_month->modify('first day of next month');

				// Add any new scheduled raids first
				$update_event_list->update_event_list($first_of_month->format('U'), $last_of_month->format('U'));

				$json_ary = array();
				$result = $this->db->sql_query('SELECT el.*, elr.end, elr.repeat_type, elr.repeat_int, elr.repeat_type_int
					FROM (event_list el
					LEFT JOIN event_list_recur elr ON elr.cid = el.cid)
					WHERE el.start >= ' . (int) $first_of_month->format('U') . '
						AND el.start <= ' . (int) $last_of_month->format('U') . '
					ORDER BY el.start ASC');
				while ($row = $this->db->sql_fetchrow($result))
				{
					$json_ary[date('j', $row['start'] - 1)][] = array(
						'rid'		=> (int) $row['rid'],
						'cid'		=> (int) $row['cid'],
						'time'		=> (int) $row['start'],
						'instance' 	=> array(
							'id'	=> (int) $this->instances[$row['eid']]['eid'],
							'code'	=> (string) $this->instances[$row['eid']]['code'],
							'name'	=> (string) $this->instances[$row['eid']]['name'],
							'colour'	=> (string) $this->instances[$row['eid']]['colour'],
						),
						'repeat'		=> array(
							'type'	=> (string) $row['repeat_type'],
							'int'	=> (int) $row['repeat_int'],
							'days'	=> explode(',', $row['repeat_type_int']),
							'end'	=> (int) $row['end']
						)
					);
				}
				$this->db->sql_freeresult($result);

				$json_response->send($json_ary, true);
				exit;

			case 'add':
				if (!$r_instance)
				{
					$error .= 'You must specify an instance';
				}

				if ($r_time < time())
				{
					$error .= 'Cannot schedule events in the past';
				}

				// Ignore if we're editing an existing entry - we don't allow updates of this information
				if ($r_repeat_type)
				{
					if (!$r_repeat_int)
					{
						$error .= "You must specify how often to repeat this raid";
					}

					if ($r_repeat_end && $r_repeat_end <= $r_time)
					{
						$error .= 'End date must exceed the current date';
					}

					if (!in_array($r_repeat_type, array('D', 'W', 'M', 'Y')))
					{
						$error .= 'Unrecognised repeat type';
					}

					if (in_array($r_repeat_type, array('D', 'W', 'M', 'Y')) && !sizeof($r_repeat_days))
					{
						$error .= 'No days selected to repeat event';
					}
				}

				if ($error)
				{
					http_response_code(400);
					$json_response->send(array(
						'error' => $error
					), true);
				}

				$log_message = $log_message_recur_dwm = $log_message_recur_until = '';

				$this->db->sql_transaction('begin');

				// Is this a recurring event? If it is create an entry
				if ($r_repeat_type)
				{
					$this->db->sql_query('INSERT INTO event_list_recur ' . $this->db->sql_build_array('INSERT', array(
						'eid'				=> (int) $r_instance,
						'start'				=> (int) $r_time,
						'end'				=> (int) $r_repeat_end,
						'last_evt'			=> 0,
						'repeat_type'		=> (string) $r_repeat_type,
						'repeat_int'		=> (int) $r_repeat_int,
						'repeat_type_int'	=> implode(',', $r_repeat_days))));
					$cid = $this->db->sql_nextid();

					$log_message = 'LOG_GUILD_EVT_ADD_RECURSE';

					foreach ($r_repeat_days as $day)
					{
						$log_message_recur_dwm .= '<strong>' . $this->days_ary[$day] . '</strong>, ';
					}

					$log_message_recur_dwm = substr($log_message_recur_dwm, 0, -2);

					$log_message_recur_until = ($r_repeat_end) ? $this->user->format_date($r_repeat_end, 'd M Y') : ' Forever';
				}
				else
				{
					$log_message = 'LOG_GUILD_EVT_ADD';
				}

				$this->db->sql_query('INSERT INTO event_list ' . $this->db->sql_build_array('INSERT', array(
					'cid'		=> (int) $cid,
					'eid'		=> (int) $r_instance,
					'start'		=> (int) $r_time)));

				$this->db->sql_transaction('commit');

				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, $log_message, false, array($this->instances[$r_instance]['name'], $this->user->format_date($r_time, 'd M Y H:i'), $r_repeat_int, $log_message_recur_dwm, $log_message_recur_until));

				$add_start_time = new \DateTime();
				$add_end_time = clone $add_start_time;
				$add_end_time->modify('last day of');

				if ($error)
				{
					http_response_code(400);
				}
				$json_response->send(array(
					'error' => $error
				), true);
				exit;

			case 'edit':
				if (!$rid)
				{
					$error .= "This raid does not exist";
				}

				if ($r_time < time())
				{
					$error .= 'Cannot alter events which have completed';
				}

				if ($error)
				{
					http_response_code(400);
					$json_response->send(array(
						'error' => $error
					), true);
				}

				// We'll obtain the time of this raid - if it's in the past we know we
				// cannot modify it - thus we'll assume it's for comment entry.

				/**
				* We need the original start time and recurrent id so we can work from that basis
				*/
				$sql = 'SELECT start, cid
					FROM event_list
					WHERE rid = ' . (int) $rid;
				$result = $this->db->sql_query($sql);

				if (!($row = $this->db->sql_fetchrow($result)))
				{
					http_response_code(400);
					$json_response->send(array(
						'error' => 'This raid does not exist'
					), true);
				}
				$this->db->sql_freeresult($result);

				$server_url = generate_board_url();

				if (!class_exists('messenger'))
				{
					include($this->phpbb_root_path . 'includes/functions_messenger.'.$this->phpEx);
				}

				$messenger = new \messenger(false);

				$cid		= (int) $row['cid'];
				$old_start	= (int) $row['start'];
				$start_diff = $r_time - $old_start;

				$sql_where = ($cid) ? 'cid = ' . (($recurse == 'all') ? $cid . ' AND start >= ' . time() : $cid . ' AND start >= ' . (int) $start) : 'rid = ' . $rid;
				$sql = 'SELECT * FROM event_list
					WHERE ' . $sql_where;
				$result = $this->db->sql_query($sql);

				while ($row = $this->db->sql_fetchrow($result))
				{
					$sql = 'SELECT u.username, u.user_email
						FROM forum_users u, event_users s
						WHERE s.rid = ' . $row['rid'] . '
							AND u.user_id = s.user_id';
					$result2 = $this->db->sql_query($sql);

					if ($row2 = $this->db->sql_fetchrow($result2))
					{
						$messenger->reset();
						$messenger->template('@numeric_raidschedule/raid_change', 'en');
						$messenger->set_addresses($this->user->data);
						$messenger->anti_abuse_headers($this->config, $this->user);
						$messenger->replyto($this->config['board_contact']);

						do
						{
							$messenger->bcc($row2['user_email'], $row2['username']);
						}
						while ($row2 = $this->db->sql_fetchrow($result2));

						$messenger->assign_vars(array(
							'OLD_RAID_TITLE'	=> $this->instances[$row['eid']]['name'],
							'OLD_RAID_DATE'		=> date('l d F', $row['start']),
							'OLD_RAID_TIME'		=> date('H:i\G\T', $row['start']),

							'RAID_TITLE'		=> $this->instances[$r_instance]['name'],
							'RAID_DATE'			=> date('l d F', $start),
							'RAID_TIME'			=> date('H:i\G\T', $start),
							'RAID_NOTES'		=> $r_note,

							'EMAIL_SIG'	=> str_replace('<br />', "\n", "-- \n" . $config['board_email_sig']),
						));

						$messenger->send(NOTIFY_EMAIL);
					}
					$this->db->sql_freeresult($result2);
				}
				$this->db->sql_freeresult($result);

				// If this is a recurring event and no confirmation on how to proceed is provided we'll
				// assume it's just this event affected
				switch ($recurse)
				{
					case 'future':
					case 'all':
						$sql_where = ($recurse == 'all') ? 'cid = ' . $cid . ' AND start >= ' . time() : 'cid = ' . $cid . ' AND start >= ' . (int) ($r_time - $start_diff);

						$old_cid = $cid;

						// We'll create a new recurring event since future changes will affect all when in fact we quite probably don't want to do that ...
						// So, grab the data for the existing event, we'll need to change start to the appropriate value
						$sql_r = 'INSERT INTO event_list_recur (start, end, last_evt, repeat_type, repeat_int, repeat_type_int, eid)
							SELECT ' . (int) ($old_start + $start_diff) . ', end, (last_evt + ' . (int) $start_diff . '), repeat_type, repeat_int, repeat_type_int, ' . (int) $r_instance . '
							FROM event_list_recur
							WHERE cid = ' . (int) $old_cid;
						$this->db->sql_query($sql_r);

						$cid = $this->db->sql_nextid();

						$sql_r = 'UPDATE event_list_recur SET ' . $this->db->sql_build_array('UPDATE', array(
							'end'		=> (int) $old_start
						)) . '
							WHERE cid = ' . $old_cid;
						$this->db->sql_query($sql_r);

						$log_message = ($recurse == 'all') ? 'LOG_GUILD_EVT_UPD_ALL' : 'LOG_GUILD_EVT_UPD_FUTURE';
						break;

					case 'this':
					default:
						$sql_where = 'rid = ' . $rid;

						$log_message = 'LOG_GUILD_EVT_UPD_SINGLE';
				}

				// Update should add/subtract from the current time ... rather than replace it
				// if we change a single event, remove it's connection to any recursive event
				$sql = 'UPDATE event_list SET ' . $this->db->sql_build_array('UPDATE', array(
					'cid' => (int) (($recurse == '' || $recurse == 'this') ? 0 : $cid),
					'eid' => (int) $r_instance));
				$sql .= ', start = start + ' . $start_diff . '
					WHERE ' . $sql_where;
				$this->db->sql_query($sql);

				$this->db->sql_transaction('commit');

				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, $log_message, false, array($this->instances[$r_instance]['name'], $this->user->format_date($r_time, 'd M Y H:i'), $r_repeat_int, $log_message_recur_dwm, $log_message_recur_until));

				$add_start_time = new \DateTime();
				$add_end_time = clone $add_start_time;
				$add_end_time->modify('last day of');

				$json_response->send(array(), true);
				exit;

			case 'delete':
				if (!$rid)
				{
					http_response_code(400);
					$json_response->send(array(
						'error' => 'This raid does not exist'
					), true);
				}

				$log_message = '';

				// Send cancellation emails to all signees for all relevent raids
				if (!class_exists('messenger'))
				{
					include($this->phpbb_root_path . 'includes/functions_messenger.'.$this->phpEx);
				}

				$messenger = new \messenger(false);

				$server_url = generate_board_url();

				$result = $this->db->sql_query('SELECT start, cid, eid
					FROM event_list
					WHERE rid = ' . (int) $rid);

				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if (!$row)
				{
					http_response_code(400);
					$json_response->send(array(
						'error' =>'This raid does not exist'
					));
				}

				$cid = (int) $row['cid'];
				$eid = (int) $row['eid'];
				$start = (int) $row['start'];

				// Trying to delete an event in the past ... errr ... I don't think so
				if ($start <= time())
				{
					http_response_code(400);
					$json_response->send(array(
						'error' => 'Cannot delete events in the past'
					), true);
				}

				$sql_where = ($recurse == '' || $recurse == 'this') ? 'rid = ' . $rid : "cid = $cid AND start >= $start";

				$result = $this->db->sql_query('SELECT rid, start FROM event_list
					WHERE ' . $sql_where);

				$log_eid_ary = $log_start_ary = array();
				while ($row = $this->db->sql_fetchrow($result))
				{
					$result2 = $this->db->sql_query('SELECT u.username, u.user_email
						FROM forum_users u, event_users s
						WHERE s.rid = ' . $row['rid'] . '
							AND u.user_id = s.user_id');

					if ($row2 = $this->db->sql_fetchrow($result2))
					{
						$messenger->reset();
						$messenger->template('@numeric_raidschedule/raid_cancel', 'en');
						$messenger->set_addresses($this->user->data);
						$messenger->anti_abuse_headers($this->config, $this->user);
						$messenger->replyto($this->config['board_contact']);

						do
						{
							$messenger->bcc($row2['user_email'], $row2['username']);
						}
						while ($row2 = $this->db->sql_fetchrow($result2));

						$messenger->assign_vars(array(
							'RAID_TITLE'	=> $instances_ary[$row['eid']]['name'],
							'RAID_DATE'		=> date('l d F', $row['start']),
							'RAID_TIME'		=> date('H:i\G\T', $row['start']),

							'EMAIL_SIG'	=> str_replace('<br />', "\n", "-- \n" . $config['board_email_sig']),
						));

						$messenger->send(NOTIFY_EMAIL);
					}
					$this->db->sql_freeresult($result2);

					$log_start_ary[] = $row['start'];
				}
				$this->db->sql_freeresult($result);

				// Do the deletion
				$this->db->sql_transaction('begin');

				$sql_in = $sql_where = '';
				switch ($recurse)
				{
					case 'all':
					case 'future':
						// Update the recursive event entry - delete it completely if all its events are deleted, i.e. it's all and set in the future
						// else set last_evt to be the event <= $start
						$result = $this->db->sql_query_limit('SELECT rid, start
							FROM event_list
							WHERE start < ' . (($recurse == 'future') ? $start : time()) . '
								AND cid = ' . (int) $cid . '
							ORDER BY start DESC', 1);
						$row = $this->db->sql_fetchrow($result);
						$this->db->sql_freeresult($result);

						// We want to delete all events and they've yet to occur, delete the recuring event completely else update
						// the recurring event to reflect the new situation
						$sql = (!$row['start']) ? 'DELETE FROM event_list_recur	WHERE cid = ' . $cid : 'UPDATE event_list_recur	SET last_evt = ' . (int) max($row['start'], time()) . ', end = ' . (int) max($row['start'], time()) . '  WHERE cid = ' . (int) $cid;
						$this->db->sql_query($sql);

						$sql_where = ($recurse == 'all') ? 'cid = ' . $cid . ' AND start >= ' . time() : 'cid = ' . $cid . ' AND start >= ' . (int) $start;

						$result = $this->db->sql_query('SELECT eid, rid
							FROM event_list
							WHERE ' . $sql_where);

						while ($row = $this->db->sql_fetchrow($result))
						{
							$sql_in .= $row['rid'] . ', ';
						}
						$this->db->sql_freeresult($result);

						$log_message = ($recurse == 'all') ? 'LOG_GUILD_EVT_DEL_ALL' : 'LOG_GUILD_EVT_DEL_FUTURE';
						break;

					default;
						$sql_where = 'rid = ' . $rid;

						$log_message = 'LOG_GUILD_EVT_DEL_SINGLE';
				}

				$this->db->sql_query('DELETE FROM event_list
					WHERE ' . $sql_where);

				$this->db->sql_query('DELETE FROM event_users
					WHERE rid ' . (($sql_in) ? 'IN (' . substr($sql_in, 0, -2) . ')' : ' = ' . $rid));

				$this->db->sql_transaction('commit');

				$add_start_time = new \DateTime();
				$add_end_time = clone $add_start_time;
				$add_end_time->modify('last day of');

				foreach ($log_start_ary as $log_start)
				{
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, $log_message, false, array($this->instances[$eid]['name'], $this->user->format_date($log_start, 'd M Y H:i')));
				}

				$json_response->send(array(
					'error' => $error
				), true);
				exit;
		}

		/**
		 * Instance array
		**/
		$ec = 0;
		foreach ($this->instances as $eid => $instance_ary)
		{
			if ($instance_ary['ecid'] != $ec)
			{
				$this->template->assign_block_vars('evt_c', array(
					'CATEGORY' 	=> $instance_ary['category'])
				);

				$ec = $instance_ary['ecid'];
			}

			$this->template->assign_block_vars('evt_c.evt_t', array(
				'EID'	=> $eid,
				'NAME' 	=> $instance_ary['name'])
			);
		}

		$this->template->assign_vars(array(
			'U_ACTION'		=> $this->u_action,
		));
	}
}
