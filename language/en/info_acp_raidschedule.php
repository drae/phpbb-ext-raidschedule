<?php
/**
 * DO NOT CHANGE
**/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || is_array($lang) === false)
{
	$lang = [];
}

$lang = array_merge($lang, [
	// module category and section titles
	'ACP_CAT_GUILD'				=> 'Guild',
	'ACP_RAID_SCHEDULE'			=> 'Raid Schedule',
	'ACP_RAID_CALENDAR'			=> 'Calendar',
	'ACP_NEW_RAID'				=> 'New raid instance',

	'LOG_GUILD_EVT_ADD'			=> 'Added raid event for <strong>%s</strong> on <em>%s</em>',
	'LOG_GUILD_EVT_ADD_RECURSE' => 'Added recursive raid event for <strong>%s</strong> starting on <em>%s</em> repeating every %s week(s) on %s until %s',

	'LOG_GUILD_EVT_UPD_SINGLE'	=> 'Updated raid event for <strong>%</strong> on <em>%s</em>',
	'LOG_GUILD_EVT_UPD_FUTURE'	=> 'Updated raid event for <strong>%s</strong> on <em>%s</em> and all that follow',
	'LOG_GUILD_EVT_UPD_ALL'		=> 'Updated all raid events for <strong>%s</strong> on <em>%s</em>',

	'LOG_GUILD_EVT_DEL_SINGLE' 	=> 'Deleted raid event for <strong>%s</strong> on <em>%s</em>',
	'LOG_GUILD_EVT_DEL_FUTURE'	=> 'Deleted raid event for <strong>%s</strong> on <em>%s</em> and all that follow',
	'LOG_GUILD_EVT_DEL_ALL'		=> 'Deleted raid event for all <strong>%s</strong> starting on <em>%s</em>',
]);
