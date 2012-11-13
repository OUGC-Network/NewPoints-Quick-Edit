<?php

/***************************************************************************
 *
 *   Newpoints Quick Edit plugin (/inc/plugins/newpoints/newpoints_quickedit.php)
 *	 Author: Sama34 (Omar G.)
 *   
 *   Website: http://udezain.com.ar
 *
 *   Allows administrators and global moderator to edit points without accessing the ACP.
 *
 ***************************************************************************/

/****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Add the hooks we are going to use.
if(!defined("IN_ADMINCP"))
{
	$plugins->add_hook('postbit', 'newpoints_quickedit_postbit', 50);
	$plugins->add_hook('member_profile_end', 'newpoints_quickedit_profile');
	$plugins->add_hook('newpoints_start', 'newpoints_quickedit_start');
	$plugins->add_hook('global_start', 'newpoints_quickedit_cachetemplate');
}

/*** Newpoints ACP side. ***/
function newpoints_quickedit_info()
{
	global $lang;
	newpoints_lang_load("newpoints_quickedit");

	return array(
		'name'			=> $lang->quickedit_plugin_n,
		'description'	=> $lang->quickedit_plugin_d,
		'website'		=> 'http://udezain.com.ar',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://udezain.com.ar',
		'version'		=> '1.0',
		'compatibility'	=> '16*',
		'codename'		=> 'quickedit',
	);
}
function newpoints_quickedit_activate()
{
	global $mybb;
	// Add the plugin template.
	newpoints_add_template("newpoints_quickedit", '<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->newpoints} {$lang->quieck_edit}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
<td valign="top" width="180">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><strong>{$lang->newpoints_menu}</strong></td>
</tr>
{$options}
</table>
</td>
<td valign="top">
			<form action="{$mybb->settings[\'bburl\']}/newpoints.php" method="post" enctype="multipart/form-data" name="input">
				<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
				<input type="hidden" name="action" value="do_quickedit" />
				<input type="hidden" name="uid" value="{$uid}" />
				<input type="hidden" name="pid" value="{$pid}" />



	<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
		<tr><td class="thead" colspan="2"><strong>{$lang->quickedit_editing_1_points}</strong></td></tr>
		<tr><td class="tcat" colspan="2"><strong>{$lang->quickedit_editing_desc}</strong></td></tr>
		<tr>
			<td class="trow1" style="white-space:nowrap;"><strong>{$lang->quickedit_currentpoints}</strong></td>
			<td class="trow1" width="100%">{$user[\'newpoints\']}</td>
		</tr>
		<tr>
			<td class="trow1" style="white-space:nowrap;"><strong>{$lang->quickedit_edit_points_desc}</strong></td>
			<td class="trow1" width="100%">
				<input type="text" class="textbox" name="points" value="0" />
			</td>
		</tr>
		<tr>
			<td class="trow1" style="white-space:nowrap;"><strong>{$lang->quickedit_action}</strong></td>
			<td class="trow1">
				<label><input name="points_action" type="radio" value="add" checked="checked" value="" /> {$lang->quickedit_action_add}</label> <label><input name="points_action" type="radio" value="remove" value="" /> {$lang->quickedit_action_remove}</label>
			</td>
		</tr>
		<tr>
			<td class="thead" colspan="2" align="center">
				<input type="submit" class="button" name="submit" value="{$lang->go}" tabindex="4" accesskey="s" />
			</td>
		</tr>
	</table>
			</form>
</td>
</tr>
</table>
{$footer}
</body>
</html>', "-1");
	newpoints_add_template("newpoints_quickedit_profile", '[<a href="{$mybb->settings[\'bburl\']}/newpoints.php?action=quickedit&amp;uid={$memprofile[\'uid\']}">{$lang->quickedit_edit_points}</a>]', "-1");
	newpoints_add_template("newpoints_quickedit_postbit", '[<a href="{$mybb->settings[\'bburl\']}/newpoints.php?action=quickedit&amp;uid={$post[\'uid\']}&amp;pid={$post[\'pid\']}">{$lang->quickedit_edit_points}</a>]', "-1");
	// Modify the postbit template to add the link variable.
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('newpoints_postbit', '#'.preg_quote('{$donate}').'#', '{$donate}{$quickedit}');
	find_replace_templatesets('newpoints_profile', '#'.preg_quote('{$donate}').'#', '{$donate}{$quickedit}');
}
function newpoints_quickedit_deactivate()
{
	global $mybb;
	// Remove the plugin template.
	newpoints_remove_templates("'newpoints_quickedit', 'newpoints_quickedit_profile','newpoints_quickedit_postbit'");
	// Modify the postbit template to remove the link variable.
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('newpoints_postbit', '#'.preg_quote('{$quickedit}').'#', '',0);
	find_replace_templatesets('newpoints_profile', '#'.preg_quote('{$quickedit}').'#', '',0);

}
function newpoints_quickedit_install()
{
	global $mybb, $lang;

	// Now we can insert them so everything is clean.
	newpoints_lang_load("newpoints_quickedit");
	newpoints_add_setting("newpoints_quickedit_on", "main", $lang->quickedit_on_n, $lang->quickedit_on_d, "yesno", "0", "7");
	rebuild_settings();
}
function newpoints_quickedit_uninstall()
{
	global $mybb;

	// Remove the plugin settings.
	newpoints_remove_settings("'newpoints_quickedit_on'");
	rebuild_settings();

	// Clean any logs from this plugin.
	newpoints_remove_log(array("quickedit"));
}
function newpoints_quickedit_is_installed()
{
	global $db;
	
	$query = $db->simple_select('newpoints_settings', 'sid', 'name=\'newpoints_quickedit_on\'', array('limit' => 1));
	if($db->fetch_field($query, 'sid'))
	{
		return true;
	}
	return false;
}

/*** Forum side. ***/
function newpoints_quickedit_cachetemplate()
{
	global $templatelist, $current_page;
	if($current_page == 'newpoints.php' && isset($templatelist))
	{
		$templatelist .= ',newpoints_quickedit';
	}
	if($current_page == 'showthread.php' && isset($templatelist))
	{
		$templatelist .= ',newpoints_quickedit_postbit';
	}
	if($current_page == 'member.php' && isset($templatelist))
	{
		$templatelist .= ',newpoints_quickedit_profile';
	}
}
function newpoints_quickedit_postbit(&$post)
{
	global $mybb;

	if($mybb->settings['newpoints_quickedit_on'] != 0)
	{
		global $lang, $templates, $currency, $points, $quickedit, $donate;
		newpoints_lang_load("newpoints_quickedit");

		if($mybb->usergroup['cancp'] != 0 || $mybb->usergroup['issupermod'] != 0 && $post['uid'] != $mybb->user['uid'])
		{
			eval('$quickedit = "'.$templates->get("newpoints_quickedit_postbit").'";');
		}
		else
		{
			$quickedit = '';
		}
		eval("\$post['newpoints_postbit'] = \"".$templates->get('newpoints_postbit')."\";");
	}
}
function newpoints_quickedit_profile()
{
	global $mybb;

	if($mybb->settings['newpoints_quickedit_on'] != 0)
	{
		global $templates, $lang, $memprofile, $newpoints_profile, $quickedit, $currency, $points;
		newpoints_lang_load("newpoints_quickedit");

		if($mybb->usergroup['cancp'] != 0 || $mybb->usergroup['issupermod'] != 0 && $memprofile['uid'] != $mybb->user['uid'])
		{
			eval('$quickedit = "'.$templates->get("newpoints_quickedit_profile").'";');
		}
		else
		{
			$quickedit = '';
		}
		eval("\$newpoints_profile = \"".$templates->get('newpoints_profile')."\";");
	}
}
function newpoints_quickedit_start()
{
	global $mybb, $db, $lang, $theme, $header, $templates, $headerinclude, $footer, $options;
	newpoints_lang_load("newpoints_quickedit");
	if($mybb->settings['newpoints_quickedit_on'] != 0)
	{
		$uid = intval($mybb->input['uid']);
		$pid = intval($mybb->input['pid']);
	
		$user = get_user($uid);
			$user['uid'] = intval($user['uid']);
			$user['newpoints'] = intval($user['newpoints']);
			$user['username'] = htmlspecialchars_uni($user['username']);
		// We want to use this feature in users profile, so lets figure out the link first..
		if(isset($mybb->input['pid']) && !empty($pid))
		{
			$link = $mybb->settings['bburl'].'/'.get_post_link($pid).'#pid'.$pid;
		}
		else
		{
			$link = $mybb->settings['bburl'].'/'.get_profile_link($user['uid']);
		}
		// Is user a Administrator or Global Moderator? If not, show no permission page.
		if($mybb->usergroup['cancp'] != 1 && $mybb->usergroup['issupermod'] != 1)
		{
			error_no_permission();
		}
		// Is user trying to edit its own points? If yes and it is not a administrator, show error page.
		if($user['uid'] == $mybb->user['uid'] && $mybb->usergroup['cancp'] != 1)
		{
			error($lang->quickedit_no_selftediting, $lang->quickedit_edit_points);
		}
		// You are editing a valid user, right?
		if($user['uid'] < 1)
		{
			error($lang->quickedit_wronguser, $lang->quickedit_edit_points);
		}
		if($mybb->input['action'] == 'quickedit')
		{
			// Get user's profile link and format points to look nice :)
			$user['username'] = build_profile_link($user['username'], $user['uid']);
			$user['newpoints'] = newpoints_format_points($user['newpoints']);

			$lang->quickedit_editing_1_points = $lang->sprintf($lang->quickedit_editing_1_points, $user['username']);
			add_breadcrumb($lang->edit_newpoints, "newpoints.php?action=quickedit");

			// Output the page...
			eval("\$quickedit = \"".$templates->get("newpoints_quickedit")."\";");
			output_page($quickedit);
		}
		elseif($mybb->input['action'] == 'do_quickedit' && $mybb->request_method == 'post')
		{
			//Verify the incoming post check to continue...
			verify_post_check($mybb->input['my_post_key']);
			//If the 'point_action' is 'remove', then remove points, otherwise, add..
			if($mybb->input['points_action'] == "remove")
			{
				$mark = "-";
			}
			else
			{
				$mark = "+";
			}
			// Add/remove them right now...
			$points = $mark.floatval(intval($mybb->input['points']));
			if($points == 0)
			{
				error($lang->quickedit_points_invalid);
			}
			newpoints_addpoints($user['uid'], $points);
			// Lets log it...
			$lang->quickedit_log = $lang->sprintf($lang->quickedit_log, $user['username'], $points, $user['newpoints']);
			newpoints_log("quickedit", $lang->quickedit_log, $mybb->user['username'], $mybb->user['uid']);
			// Redirect to user's profile...
			redirect($link, $lang->quickedit_edited);
		}
		else
		{
			error_no_permission();
		}
	}
	elseif($mybb->input['action'] == "quickedit" || $mybb->input['action'] == "do_quickedit")
	{
		error_no_permission();
	}
}
?>