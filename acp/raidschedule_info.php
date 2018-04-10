<?php
/**
 *
 * This file is part of the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * For full copyright and license information, please see
 * the docs/CREDITS.txt file.
 *
 */

namespace numeric\raidschedule\acp;

class raidschedule_info
{
	function module()
	{
		return array(
			'filename'	=> '\numeric\raidschedule\acp\raidschedule_module',
			'title'		=> 'ACP_RAID_SCHEDULE',
			'modes'		=> [
				'calendar'	=> [
					'title'	=> 'ACP_RAID_CALENDAR',
					'auth'	=> 'ext_numeric/raidschedule && acl_a_board',
					'cat'	=> ['ACP_CAT_GUILD']
				],
				'newraid'	=> [
					'title'	=> 'ACP_NEW_RAID',
					'auth'	=> 'ext_numeric/raidschedule && acl_a_board',
					'cat'	=> ['ACP_CAT_GUILD']
				]
			],
		);
	}
}
