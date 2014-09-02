<?php
/**
 * Limit Number of Threads
 * Copyright 2009 Starpaul20
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Tell MyBB when to run the hooks
$plugins->add_hook("newthread_start", "limitthreads_run");
$plugins->add_hook("newthread_do_newthread_start", "limitthreads_run");

$plugins->add_hook("admin_formcontainer_output_row", "limitthreads_usergroup_permission");
$plugins->add_hook("admin_user_groups_edit_commit", "limitthreads_usergroup_permission_commit");

// The information that shows up on the plugin manager
function limitthreads_info()
{
	global $lang;
	$lang->load("limitthreads", true);

	return array(
		"name"				=> $lang->limitthreads_info_name,
		"description"		=> $lang->limitthreads_info_desc,
		"website"			=> "http://galaxiesrealm.com/index.php",
		"author"			=> "Starpaul20",
		"authorsite"		=> "http://galaxiesrealm.com/index.php",
		"version"			=> "1.0",
		"compatibility"		=> "18*"
	);
}

// This function runs when the plugin is activated.
function limitthreads_activate()
{
	global $db, $cache;
	$db->add_column("usergroups", "maxthreadsday", "int(3) NOT NULL default '10'");

	$cache->update_usergroups();
}

// This function runs when the plugin is deactivated.
function limitthreads_deactivate()
{
	global $db, $cache;
	if($db->field_exists("maxthreadsday", "usergroups"))
	{
		$db->drop_column("usergroups", "maxthreadsday");
	}

	$cache->update_usergroups();
}

// Limit Threads per day
function limitthreads_run()
{
	global $mybb, $db, $lang;
	$lang->load("limitthreads");

	// Check group limits
	if($mybb->usergroup['maxthreadsday'] > 0)
	{
		$query = $db->simple_select("threads", "COUNT(*) AS thread_count", "uid='".intval($mybb->user['uid'])."' AND dateline >='".(TIME_NOW - (60*60*24))."'");
		$thread_count = $db->fetch_field($query, "thread_count");
		if($thread_count >= $mybb->usergroup['maxthreadsday'])
		{
			$lang->error_max_threads_day = $lang->sprintf($lang->error_max_threads_day, $mybb->usergroup['maxthreadsday']);
			error($lang->error_max_threads_day);
		}
	}
}

// Admin CP permission control
function limitthreads_usergroup_permission($above)
{
	global $mybb, $lang, $form;
	$lang->load("limitthreads", true);

	if($above['title'] == $lang->posting_rating_options && $lang->posting_rating_options)
	{
		$above['content'] .= "<div class=\"group_settings_bit\">{$lang->maxthreadsday}:<br /><small>{$lang->maxthreadsday_desc}</small><br /></div>".$form->generate_text_box('maxthreadsday', $mybb->input['maxthreadsday'], array('id' => 'maxthreadsday', 'class' => 'field50'));
	}

	return $above;
}

function limitthreads_usergroup_permission_commit()
{
	global $mybb, $updated_group;
	$updated_group['maxthreadsday'] = intval($mybb->input['maxthreadsday']);
}

?>