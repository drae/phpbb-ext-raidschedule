<?php
/**
 * 
 * 
 * 
 */

namespace numeric\raidschedule\api;

use numeric\raidschedule\constants;

class api {

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
    }

}