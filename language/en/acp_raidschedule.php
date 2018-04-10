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
	'ACP_CAT_GUILD'	=> 'Guild',
	'ACP_RAID_SCHEDULE' => 'Raid Schedule',
	'ACP_RAID_CALENDAR'	=> 'Manage events',

	'ACP_RAID_CALENDAR_EXPLAIN'	=> 'To schedule an event click on the appropriate day and enter the details into the popup dialog box. To alter an existing event (which has yet to take place) simply click it. Altering or cancelling a raid will cause all signed to it to be notified.',

	'TODAY'		=> 'Today',
	'SUNDAY'	=> 'Sunday',
	'MONDAY'	=> 'Monday',
	'TUESDAY'	=> 'Tuesday',
	'WEDNESDAY'	=> 'Wednesday',
	'THURSDAY'	=> 'Thursday',
	'FRIDAY'	=> 'Friday',
	'SATURDAY'	=> 'Saturday',

	'DAY'			=> 'Day',
	'START_TIME'	=> 'Start time',
	'LOCAL_TIME'	=> 'Local time',
	'INSTANCE'		=> 'Instance',
	'REPEAT'		=> 'Repeat',
	'NEVER'			=> 'Never',
	'WEEKLY'		=> 'Weekly',
	'REPEAT_EVERY'	=> 'Repeat every',
	'UNTIL'			=> 'Until',
	'UNTIL_EXPLAIN'	=> 'Leave blank to repeat forever',

	'APPLY_TO'		=> 'Apply to',
	'RECURSE_THIS'	=> 'This only',
	'RECURSE_FUTURE'=> 'This and beyond',
	'RECURSE_ALL'	=> 'All',

	'RAID_DELETE_EXPLAIN'	=> 'Choose which event or events to delete. There is no undo!',
]);
