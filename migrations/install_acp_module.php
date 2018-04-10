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

namespace numeric\raidschedule\migrations;

class install_acp_module extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['raid_schedule_enabled']);
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v31x\v314');
	}

	/**
	 * Initial config settings & ACP module.
	 *
	 * @return array
	**/
	public function update_data()
	{
		return [
			['config.add', ['raid_schedule_enabled', 1]],
			['permission.add', ['u_cal_sign']],
			['permission.add', ['u_cal_select']],
			['permission.add', ['a_cal_create']],
			['permission.add', ['a_cal_delete']],
			['module.add', ['acp', 'ACP_CAT_GUILD', 'ACP_RAID_SCHEDULE']],
			['module.add', ['acp', 'ACP_RAID_SCHEDULE', ['module_basename' => '\numeric\raidschedule\acp\raidschedule_module', 'modes' => ['calendar', 'newraid']]]],
		];
	}

	/**
	 * Remove FrontPage config data & module.
	 *
	 * @return array
	**/
	public function revert_data()
	{
		return [
			['config.remove', ['raid_schedule_enabled']],
			['permission.remove', ['u_cal_sign']],
			['permission.remove', ['u_cal_select']],
			['permission.remove', ['a_cal_create']],
			['permission.remove', ['a_cal_delete']],
			['module.remove', ['acp', 'ACP_CAT_GUILD', 'ACP_RAID_SCHEDULE']],
			['module.remove', ['acp', 'ACP_RAID_SCHEDULE', ['module_basename' => '\numeric\raidschedule\acp\raidschedule_module', 'modes' => ['calendar', 'newraid']]]],
		];
	}
}
