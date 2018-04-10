<?php
/**
 *
 * This file is part of the phpBB Forum Software package.
 *
 * For full copyright and license information, please see
 * the docs/CREDITS.txt file.
 *
 */

namespace numeric\raidschedule\controller;

use numeric\raidschedule\constants;
use numeric\controller\api;

class main
{
	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\auth\auth */
	protected $auth;

	/* @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\db\driver\driver_interface */
	protected $db;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;

	protected $request;

	protected $update_event_list;

	protected $phpbb_container;

	/* @var phpEx */
	protected $phpEx;

	/* @var phpbb_root_path */
	protected $phpbb_root_path;

	protected $instances = array();

	protected $role_ary = array('ranged', 'melee', 'healer', 'tank');

	protected $raiding_ranks = array(0, 1, 2, 3);

	protected $months_ary = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');

	/**
	* Constructor
	*
	* @param \phpbb\config\config		$config
	* @param \phpbb\controller\helper	$helper
	* @param \phpbb\template\template	$template
	* @param \phpbb\user				$user
	*/
	public function __construct(\phpbb\controller\helper $helper, \phpbb\auth\auth $auth, \phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\template\template $template, \phpbb\user $user, \phpbb\request\request $request, \numeric\raidschedule\update_event_list $update_event_list, $phpbb_container, $phpbb_root_path, $phpEx)
	{
		include_once __DIR__ . '/../constants.' . $phpEx;

		$this->helper = $helper;
		$this->auth = $auth;
		$this->config = $config;
		$this->db = $db;
		$this->template = $template;
		$this->user = $user;
		$this->request = $request;
		$this->update_event_list = $update_event_list;
		$this->phpbb_container = $phpbb_container;
		$this->phpEx = $phpEx;
		$this->phpbb_root_path = $phpbb_root_path;

		$this->user->add_lang_ext('numeric/raidschedule', 'common');

		/**
		* Grab all raid instances
		*/
		$sql = 'SELECT *
			FROM event_types
			WHERE type = ' . constants::CAL_EVENT_RAID;
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
			$this->instances[$row['eid']] = $row;
		$this->db->sql_freeresult($result);
	}

	private function get_raid_info($rid)
	{
		$eid = $topic_id = $raid_date = $total_posts = 0;

		$sql = 'SELECT *
			FROM event_list
			WHERE rid = ' . $rid;
		$result = $this->db->sql_query($sql);

		if ($row = $this->db->sql_fetchrow($result))
		{
			$eid = $row['eid'];
			$topic_id = $row['topic_id'];
			$raid_date = $row['start'];
			$raid_note = ($row['note']) ? preg_replace('#^(.*?)$#m', '<li>\1</li>', $row['note']) : '';
		}
		$this->db->sql_freeresult($result);

		if ($topic_id)
		{
			$sql = 'SELECT topic_posts_approved
				FROM ' . TOPICS_TABLE . '
				WHERE topic_id = ' . (int) $topic_id;
			$result = $this->db->sql_query($sql);

			$total_posts = $this->db->sql_fetchfield('topic_posts_approved', false, $result);
			$this->db->sql_freeresult($result);
		}

		return array(
			'name'		=> $this->instances[$eid]['name'],
			'code'		=> $this->instances[$eid]['code'],
			'colour'	=> $this->instances[$eid]['colour'],
			'banner'	=> $this->instances[$eid]['banner'],
			'date'		=> (int) $raid_date,
			'posts'		=> ($topic_id) ? (($topic_posts == 1) ? '1 post' : $total_posts . ' posts') : 0,
			'url'		=> $this->build_signup_url($rid),
			'signup'	=> ($topic_id) ? append_sid("/viewtopic.php?f=33&amp;t=$topic_id") : '',
		);
	}

	private function get_raid_stats($rid = 0)
	{
		$eid = $raid_date = 0;

		$sql = 'SELECT *
			FROM event_list
			WHERE rid = ' . (int) $rid;
		$result = $this->db->sql_query($sql);

		if ($row = $this->db->sql_fetchrow($result))
		{
			$eid		= $row['eid'];
			$raid_date 	= $row['start'];
		}
		$this->db->sql_freeresult($result);

		$sql = 'SELECT COUNT(*) AS total
			FROM roster_players
			WHERE rank IN (' . implode(', ', $this->raiding_ranks) . ')
				AND left_guild <> 0';
		$result = $this->db->sql_query($sql);

		$total_raiders = $this->db->sql_fetchfield('total', false, $result);
		$this->db->sql_freeresult($result);

		/**
		* Now grab all the player information
		*/
		$sql = 'SELECT selected, unsigned_time
			FROM event_users
			WHERE rid = ' . $rid;
		$result = $this->db->sql_query($sql);

		$signed = $unsigned = $selected = 0;
		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($row['selected'] != constants::RA_RESERVE && $row['selected'] != constants::RA_UNSELECTED && !$row['unsigned_time'])
			{
				$selected++;
			}

			if (!$row['unsigned_time'])
			{
				$signed++;
			}

			if ($row['unsigned_time'])
			{
				$unsigned++;
			}
		}
		$this->db->sql_freeresult($result);

		return array(
			'name' 		=> $this->instances[$eid]['name'],
			'total'		=> $total_raiders,
			'signed'	=> $signed,
			'unsigned' 	=> $unsigned,
			'selected'	=> $selected
		);
	}

	private function generate_signup_list($rid)
	{
		/**
		* Grab players from all relevant ranks
		*/
		$sql = 'SELECT u.user_id, u.username, eu.rid, eu.signed_time, eu.unsigned_time, eu.selected, rp.roster_id, rp.class_clean, rp.active_build, rp.principal_role
			FROM (forum_users u
				LEFT JOIN event_users eu ON eu.rid = ' . (int) $rid . '
					AND eu.user_id = u.user_id), forum_groups g, roster_players rp
			WHERE rp.user_id = u.user_id
				AND g.group_id = u.group_id
				AND rp.rank IN (' . implode(', ', $this->raiding_ranks) . ')
				' . $sql_where . '
				AND rp.left_guild = 0
			ORDER BY selected DESC, unsigned_time, signed_time, u.username_clean';
		$result = $this->db->sql_query($sql);

		$is_signed = 0;
		$row_ary = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$role = (!empty($row['principal_role'])) ? $row['principal_role'] : wow_talent_trees([$row['class_clean']]['default']);

			$row_ary[$role][] = $row;
		}
		$this->db->sql_freeresult($result);

		$i = 0;
		$player_ary = array();
		foreach ($this->role_ary as $role)
		{
			if (!$user_id || ($user_id && !empty($row_ary[$role])))
			{
				$player_ary[$i] = [
					'name'	=> $role,
				];
			}

			foreach ($row_ary[$role] as $row)
			{
				$l_status = '';
				if (!empty($row['signed_time']))
				{
					switch ($row['selected'])
					{
						case constants::RA_SELECTED:
							$l_status = $this->user->lang['selections']['SELECTED'];
							break;
						case constants::RA_RESERVE:
							$l_status = $this->user->lang['selections']['RESERVE'];
							break;
						default:
							$l_status = $this->user->lang['selections']['AVAILABLE'];
					}

				}

				if (!empty($row['signed_time']) && $row['user_id'] == $this->user->data['user_id'])
				{
					$is_signed = 1;
				}

				$player_ary[$i]['players'][] = array(
					'username'	=> (string) $row['username'],
					'uid' 		=> (int) $row['user_id'],
					'css'		=> (string) $row['class_clean'],
					'css_fg'	=> (string) ($row['class_clean'] == 'priest') ? '888' : 'fff',
					'signed' 	=> (int) (!empty($row['signed_time'])) ? 1 : ((!empty($row['unsigned_time'])) ? -1 : 0),
					'selected'	=> (int) (!empty($row['selected'])) ? $row['selected'] : 0,
					'l_selected'=> (string) $l_status,
					'link'		=> (string) append_sid("/roster/character/" . $row['username']),
					'u_select'	=> (string) ($this->auth->acl_get('u_cal_select')) ? $this->build_signup_url($rid, '&amp;mode=user-select&amp;u=' . (int) $row['user_id'], true) : '',
				);
			}

			$i++;
		}
		unset($row_ary);

		return array($is_signed, $player_ary);
	}

	private function get_next_raid_event($after_day = '')
	{
		$event_list_start = new \DateTime();
		$event_list_end = (clone $event_list_start)->modify('+' . constants::TIMELINE_DAYS);

		// Insert recurrent events over the coming $display_days
		$this->update_event_list->update_event_list($event_list_start->format('U'), $event_list_end->format('U'));

		$result = $this->db->sql_query('SELECT rid
			FROM event_list
			WHERE start = (
				SELECT MIN(start)
					FROM event_list
					WHERE start >= ' . (int) $event_list_start->format('U') . '
				)');
		$rid = $this->db->sql_fetchfield('rid', false, $result);
		$this->db->sql_freeresult($result);

		if (empty($rid))
		{
			$result = $this->db->sql_query('SELECT rid
					FROM event_list
					WHERE start = (SELECT MAX(start)
							FROM event_list
					)');
			$rid = $this->db->sql_fetchfield('rid', false, $result);
			$this->db->sql_freeresult($result);
		}

		return $rid;
	}

	private function build_timeline($start, $end)
	{
		// Insert recurrent events over the coming $display_days
		$this->update_event_list->update_event_list($start->format('U'), $end->format('U'));

		//
		$sql = 'SELECT s.*, su.selected, su.signed_time, su.unsigned_time
			FROM (event_list s
			LEFT JOIN event_users su ON su.rid = s.rid
				AND su.user_id = ' . (int) $this->user->data['user_id'] . ')
			WHERE s.start >= ' . $start->format('U') . '
				AND s.start <= ' . $end->format('U') . '
			ORDER BY s.start ASC';
		$result = $this->db->sql_query($sql);

		$event_ary = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$event_ary[] = array(
				'day'		=> (string) date('Y-m-d', $row['start']),
				'type'		=> 1,
				'iam'		=> 'raid',
				'time'		=> (int) $row['start'],
				'title'		=> (string) $this->instances[$row['eid']]['name'],
				'rid'		=> (int) $row['rid'],
				'colour'	=> (string) 'rgba(' . implode(', ', sscanf($this->instances[$row['eid']]['colour'], "%02x%02x%02x")) . ',1)',
				'signed' 	=> (int) ($row['signed_time'] && !$row['unsigned_time'] && empty($row['selected'])) ? true : false,
				'selected'	=> (int) (!empty($row['selected'])) ? $row['selected'] : 0,
				'link'		=> (string) !empty($row['rid']) ? $this->build_signup_url($row['rid']) : ''
			);
		}
		$this->db->sql_freeresult($result);

		// Add reset markings
		$first_reset = clone $start;
		$first_reset->modify(($start->format('D') == 'Wed') ? '07:00 UTC' : 'next Wednesday 07:00 UTC');
		$reset_interval = new \DateInterval('P7D');
		$reset_period = new \DatePeriod($first_reset, $reset_interval, $end);

		foreach ($reset_period as $dt)
		{
			$event_ary[] = array(
				'day'	=> (string) $dt->format('Y-m-d'),
				'type'	=> 0,
				'iam'	=> (string) 'reset',
				'time'	=> (int) $dt->format('U'),
				'title'	=> (string) 'Instance reset',
			);
		}

		uasort($event_ary, function($a, $b)
		{
			return ($a['time'] < $b['time']) ? -1 : (($a['time'] > $b['time']) ? 1 : 0);
		});

		return $event_ary;
	}

	private function event_timeline($rid)
	{
		$event_list_start = new \DateTime('now', new \DateTimeZone('UTC'));
		$event_list_start->setTime(0, 0, 0);

		$event_list_end = clone $event_list_start;
		$event_list_end->modify('+' . constants::TIMELINE_DAYS);
		$event_list_end->setTime(23, 59, 59);

		$today = date('Y-m-d', time());

		$event_ary = $this->build_timeline($event_list_start, $event_list_end);

		// Iterate through the days
		$cur_year = $cur_month = $cur_day = 0;
		foreach ($event_ary as $event)
		{
			$this->template->assign_block_vars('event', array(
				'DAY' 		=> (string) $event['day'],
				'TYPE'		=> (int) $event['type'],
				'IAM'		=> (string) $event['iam'],
				'TIME' 		=> (string) $event['time'],
				'TITLE' 	=> (string) $event['title'],
				'COLOUR'	=> (string) $event['colour'],
				'SIGNED' 	=> (int) $event['signed'],
				'SELECTED'	=> (int) $event['selected'],
				'RID' 		=> (int) $event['rid'],
				'LINK' 		=> (string) $event['link'],
			));
		}

		$this->template->assign_vars(array(
			'TIMELINE_START'	=> $event_list_start->format('U'),
			'TIMELINE_END'		=> $event_list_end->format('U'),
		));
	}

	private function build_signup_url($rid = 0, $amp = '')
	{
		return '/signup/' . (($rid) ? $rid : '');
	}

	/**
	* Outputs the base schedule for the given raid event
	*
	* @param int 	$rid	The raid id
	* @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	*/
	public function use_raid_id($rid = 0)
	{
		if ($this->request->is_ajax())
		{
			$raid_stats = $this->get_raid_stats($rid);
			$raid_info = $this->get_raid_info($rid);
			list($is_signed, $player_ary) = $this->generate_signup_list($rid);

			$json_response = new \phpbb\json_response;
			$json_response->send(array(
				'rid'				=> (int) $rid,
				'uid'				=> (int) $this->user->data['user_id'],
				'raid_name'			=> (string) $raid_stats['name'],
				'raid_date'			=> (string) $raid_info['date'],
				'raid_banner'		=> (string) $raid_info['banner'],
				'raid_posts'		=> (int) $raid_info['posts'],
				'raid_topic'		=> (string) $raid_info['signup'],
				'total_raiders'		=> (int) $raid_stats['total'],
				'total_signed'		=> (int) $raid_stats['signed'],
				'total_unsigned' 	=> (int) $raid_stats['unsigned'],
				'total_selected'	=> (int) $raid_stats['selected'],
				'signed'			=> (int) $is_signed,
				'players'			=> (array) $player_ary,
				'can_sign'			=> (int) $rid && $raid_info['date'] > time() && $this->auth->acl_get('u_cal_sign') && $this->user->data['user_type'] != USER_IGNORE,
				'can_select'		=> (int) $this->auth->acl_get('u_cal_select') ? 1 : 0,
			), true);
		}

		$this->user->add_lang_ext('numeric/raidschedule', 'common');

		if ($rid)
		{
			list($is_signed, $player_ary) = $this->generate_signup_list($rid);

			foreach ($player_ary as $role_ary)
			{
				$this->template->assign_block_vars('roles', array(
					'NAME'			=> $role_ary['name'],
				));

				foreach ($role_ary['players'] as $player)
				{
					$this->template->assign_block_vars('roles.players', array(
						'USERNAME' 	=> $player['username'],
						'UID' 		=> $player['uid'],
						'CSS'		=> $player['css'],
						'CSS_FG'	=> $player['css_fg'],
						'SIGNED' 	=> $player['signed'],
						'SELECTED'	=> $player['selected'],
						'LINK'		=> $player['link'],
						'L_SELECTED'=> $player['l_selected'],
						'U_SELECT'	=> $player['u_select'],
					));
				}
			}
			unset($player_ary);

			$raid_stats = $this->get_raid_stats($rid);
			$raid_info = $this->get_raid_info($rid);

			$this->template->assign_vars(array(
				'RID'				=> $rid,
				'UID'				=> $this->user->data['user_id'],
				'RAID_INSTANCE'		=> $raid_stats['name'],
				'TOTAL_RAIDERS'		=> $raid_stats['total'],
				'TOTAL_SIGNED'		=> $raid_stats['signed'],
				'TOTAL_UNSIGNED' 	=> $raid_stats['unsigned'],
				'TOTAL_SELECTED'	=> $raid_stats['selected'],
				'RAID_CODE'			=> $raid_info['code'],
				'RAID_COLOUR'		=> $raid_info['colour'],
				'RAID_BANNER'		=> $raid_info['banner'],
				'RAID_DATE'			=> $raid_info['date'],
				'POSTS'				=> $raid_info['posts'],
				'U_SIGNUP'			=> $raid_info['url'],
				'U_TOPIC'			=> $raid_info['signup'],
				'S_SIGNED_UP'		=> $is_signed ? 1 : 0,
				'S_CAN_SIGN'		=> ($rid && $raid_info['date'] > time() && $this->auth->acl_get('u_cal_sign') && $this->user->data['user_type'] != USER_IGNORE) ? 1 : 0,
				'S_CAN_SELECT'		=> $this->auth->acl_get('u_cal_select') ? 1 : 0,
			));
			unset($raid_stats);
			unset($raid_info);
		}

//		$this->event_timeline($rid);

		$this->template->assign_vars(array(
			'S_MENU_PAGE'		=> $this->user->lang['SCHEDULE'],
		));

		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME'	=> $this->user->lang['SCHEDULE'],
			'U_VIEW_FORUM'	=> $this->build_signup_url()
		));

		return $this->helper->render('list_players.html', $this->user->lang['SCHEDULE']);
	}

	/**
	* If no raid was specified determine the next available
	*
	* @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	*/
	public function set_raid_id()
	{
		if ($rid = $this->get_next_raid_event())
		{
			return redirect($this->build_signup_url($rid));
		}

		return $this->use_raid_id($rid);
	}

	public function api_sign($rid)
	{
		// Can this user sign?
		if (!$this->auth->acl_get('u_cal_sign') || $this->user->data['user_type'] == USER_IGNORE || !$rid)
		{
			exit;
		}

		/**
		 * Raid information first
		**/
		$sql = 'SELECT *
				FROM event_list
			WHERE rid = ' . $rid . '
				AND start > ' . time();
		$result = $this->db->sql_query($sql);

		if (!($row = $this->db->sql_fetchrow($result)))
		{
			exit;
		}
		$this->db->sql_freeresult($result);

		$raid_date = $row['start'];

		$sql = 'SELECT signed_time, unsigned_time
			FROM event_users
			WHERE rid = ' . (int) $rid . '
				AND user_id = ' . (int) $this->user->data['user_id'];
		$result = $this->db->sql_query($sql);

		$is_signed = $is_unsigned = false;
		if ($row = $this->db->sql_fetchrow($result))
		{
			if ($row['signed_time'])
			{
				$is_signed = true;
			}

			if ($row['unsigned_time'])
			{
				$is_unsigned = true;
			}
		}
		$this->db->sql_freeresult($result);

		/**
		* If less than 12 hours pre-raid, notify selection team/raid leader
		*/
		if ($is_signed && $raid_date < time() + 43200)
		{
			/**
			* List of user_ids with u_cal_select permission - the selection team iow, we also
			* grab the raid leaders (if any) user_id to notify them too
			*/
			$selector_ids = $this->auth->acl_get_list(false, 'u_cal_select', false);

			if (!class_exists('messenger'))
			{
				include($this->phpbb_root_path . 'includes/functions_messenger.'.$this->phpEx);
			}

			$messenger = new \messenger(false);
			$messenger->template('@numeric_raidschedule/raid_unsign_notify');
			$messenger->anti_abuse_headers($this->config, $this->user);
			$messenger->replyto($this->config['board_contact']);

			$server_url = generate_board_url();

			$sql = 'SELECT username, user_email
				FROM ' . USERS_TABLE . '
				WHERE user_id IN (' . implode(', ', $selector_ids[0]['u_cal_select']) . ')';
			$result = $this->db->sql_query($sql);

			$i = 0;
			while ($row = $this->db->sql_fetchrow($result))
			{
				if ($i == 0)
					$messenger->to($row['user_email'], $row['username']);
				else
					$messenger->cc($row['user_email'], $row['username']);
				$i++;
			}
			$this->db->sql_freeresult($result);

			$messenger->assign_vars(array(
				'USERNAME'		=> $this->user->data['username'],
				'RAID_TITLE'	=> $instance,
				'RAID_DATE'		=> date('l d F', $raid_date),
				'RAID_TIME'		=> date('H:i\G\T', $raid_date),

				'EMAIL_SIG'	=> str_replace('<br />', "\n", "-- \n" . $this->config['board_email_sig']),
			));

			$messenger->send(NOTIFY_EMAIL);
		}

		if ($is_signed || $is_unsigned)
		{
			$sql =  'UPDATE event_users SET ' . (($is_signed) ? ' selected = 0, signed_time = 0, unsigned_time = ' . (int) time() : 'signed_time = ' . (int) time() . ', unsigned_time = 0') . '
				WHERE user_id = ' . (int) $this->user->data['user_id'] . '
					AND rid = ' . (int) $rid;
		}
		else
		{
			$sql = 'INSERT INTO event_users ' . $this->db->sql_build_array('INSERT', array(
					'rid'			=> $rid,
					'user_id'		=> (int) $this->user->data['user_id'],
					'signed_time'	=> (int) time(),
					'unsigned_time'	=> 0,
				));
		}
		$this->db->sql_query($sql);

		$is_signed = (bool) !$is_signed;

		$raid_stats = $this->get_raid_stats($rid);

//		$this->cache->destroy('sql', 'event_users');

		$json_response = new \phpbb\json_response;
		$json_response->send(array(
			'rid'				=> (int) $rid,
			'total_raiders'		=> (int) $raid_stats['total'],
			'total_signed'		=> (int) $raid_stats['signed'],
			'total_unsigned' 	=> (int) $raid_stats['unsigned'],
			'total_selected'	=> (int) $raid_stats['selected'],
			'uid' 				=> (int) $this->user->data['user_id'],
			'signed' 			=> (int) $is_signed,
			'selected' 			=> (int) 0,
			'l_selected' 		=> (string) $this->user->lang['selections']['AVAILABLE'],
		));
	}

	public function api_select_user($rid, $uid)
	{
		/**
		* Raid information and user selection status
		*/
		$sql = 'SELECT COUNT(selected) as total_selected
			FROM event_users
			WHERE rid = ' . (int) $rid . '
				AND selected = ' . constants::RA_SELECTED . '
				AND unsigned_time = 0';
		$result = $this->db->sql_query($sql);

		if (!$row = $this->db->sql_fetchrow($result))
		{
			exit;
		}
		$this->db->sql_freeresult($result);

		$total_selected = $row['total_selected'];

		$sql = 'SELECT e.eid, e.start, u.selected, u.signed_time, u.unsigned_time
			FROM event_users u, event_list e, event_types et
			WHERE u.user_id = ' . (int) $uid . '
				AND e.rid = ' . (int) $rid . '
				AND u.rid = e.rid
				AND et.eid = e.eid';
		$result = $this->db->sql_query($sql);

		if (!($row = $this->db->sql_fetchrow($result)))
		{
			exit;
		}
		$this->db->sql_freeresult($result);

		$eid			= $row['eid'];
		$start_time		= $row['start'];
		$selected		= $row['selected'];
		$signed			= $row['signed_time'];

		// Event has already occured or user has unsigned - bail out  else process
		if ($row['start'] > time() && !$row['unsigned_time'] && $this->auth->acl_get('u_cal_select'))
		{
			$selected++;
			if ($selected >= constants::SELECTION_TYPES)
			{
				$selected = 0;
			}

			$sql_ary = array(
				'selected'		=> (int) $selected,
				'selected_time'	=> ($selected) ? time() : 0,
				'selected_by'	=> ($selected) ? (string) $this->user->data['username'] : '',
			);

			$sql = 'UPDATE event_users SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
				WHERE user_id = ' . (int) $uid . '
					AND rid = ' . (int) $rid;
			$this->db->sql_query($sql);

			// Set raid stats
			$raid_stats = $this->get_raid_stats($rid);
			$raid_info = $this->get_raid_info($rid);

			$l_selected = '';
			switch ($selected)
			{
				case constants::RA_SELECTED:
					$l_selected = $this->user->lang['selections']['SELECTED'];
					break;
				case constants::RA_RESERVE:
					$l_selected = $this->user->lang['selections']['RESERVE'];
					break;
				default:
					$l_selected = $this->user->lang['selections']['AVAILABLE'];
			}

			$json_response = new \phpbb\json_response;
			$json_response->send(array(
				'rid'				=> (int) $rid,
				'total_raiders'		=> (int) $raid_stats['total'],
				'total_signed'		=> (int) $raid_stats['signed'],
				'total_unsigned' 	=> (int) $raid_stats['unsigned'],
				'total_selected'	=> (int) $raid_stats['selected'],
				'uid'				=> (int) $uid,
				'signed'			=> (int) ($selected) ? 0 : $signed,
				'selected'			=> (int) $selected,
				'l_selected'		=> (string) $l_selected,
			), true);
		}
	}

	public function api_timeline($start, $action)
	{
		if (!$start)
		{
			$base_start = new \DateTime('now', new \DateTimeZone('UTC'));
			$base_start->setTime(0, 0, 0);
		}
		else
		{
			$base_start = new \Datetime((string) '@' . $start);
			$base_start->setTime(0, 0, 0);
		}
		$base_start->setTimezone(new \DateTimeZone('UTC'));

		switch ($action)
		{
			case 'prev':
				$real_start = clone $base_start;
				$real_start->modify('-7 days');
				$real_start->setTime(0, 0, 0);
				$real_end = clone $base_start;

				$new_start = clone $real_start;
				$new_end = (clone $real_start)->modify('+' . constants::TIMELINE_DAYS);
				$new_end->setTime(23, 59, 59);
			break;

			case 'next':
				if (!$start)
				{
					$new_start = clone $base_start;
					$new_end = (clone $base_start)->modify('+' . constants::TIMELINE_DAYS);

				}
				else
				{
					$new_start = clone $base_start;
					$new_start->modify('+1 day');
					$new_start->setTime(0, 0, 0);

					$new_end = clone $new_start;
					$new_end->modify('+7 days');
					$new_end->setTime(23, 59, 59);
				}
			break;
		}

		$event_ary = $this->build_timeline($new_start, $new_end);

		$json_response = new \phpbb\json_response;
		$json_response->send(array(
			'start'	=> (int) $new_start->format('U'),
			'end'	=> (int) $new_end->format('U'),
			'events'=> (array) array_values($event_ary)
		), true);
	}
}
