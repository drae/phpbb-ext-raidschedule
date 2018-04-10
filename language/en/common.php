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
	'SCHEDULE'	=> 'Schedule',

	'PLAYERS'			=> 'Players',
	'PLAYERS_SIGNED'	=> 'Players signed',
	'PLAYERS_UNSIGNED'	=> 'Players unsigned',
	'PLAYERS_SELECTED'	=> 'Players selected',

	'SIGN'		=> 'Sign',
	'UNSIGN'	=> 'Unsign',

	'DISCUSS_THIS'	=> 'Discuss this',

	'selections' => [
		'AVAILABLE'=> 'Available',
		'SELECTED'	=> 'Selected',
		'RESERVE' 	=> 'Reserve',
	],
]);
