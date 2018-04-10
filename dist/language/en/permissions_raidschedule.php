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
	'ACL_U_CAL_SELECT'	=> 'Raid Schedule: Can select users',
    'ACL_U_CAL_SIGN'	=> 'Raid Schedule: Can signup to events',
	'ACL_A_CAL_CREATE'	=> 'Raid Schedule: Can create events',
	'ACL_A_CAL_DELETE'	=> 'Raid Schedule: Can delete events',
]);
