<?php

/***************************************************************************
 *
 *   Newpoints Quick Edit plugin (/inc/plugins/newpoints/newpoints_quickedit.php)
 *	 Author: Omar Gonzalez
 *   Copyright: © 2012 Omar Gonzalez
 *   
 *   Website: http://community.mybb.com/user-25096.html
 *
 *   Quickly edit user's points without accessing to the ACP.
 *
 ***************************************************************************
 
****************************************************************************
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
		'name'			=> 'Quick Edit Points',
		'description'	=> $lang->quickedit_plugin_d,
		'website'		=> 'http://forums.mybb-plugins.com/Thread-Plugin-Quick-Edit-1-1',
		'author'		=> 'Omar Gonzalez',
		'authorsite'	=> 'http://community.mybb.com/user-25096.html',
		'version'		=> '1.2',
		'compatibility'	=> '19*'
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
				<input type="hidden" name="action" value="quickedit" />
				<input type="hidden" name="uid" value="{$mybb->input[\'uid\']}" />
				<input type="hidden" name="pid" value="{$mybb->input[\'pid\']}" />
	<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
		<tr><td class="thead" colspan="2"><strong>{$lang->quickedit_editing_points}</strong></td></tr>
		<tr><td class="tcat smalltext" colspan="2"><strong>{$lang->quickedit_editing_desc}</strong></td></tr>
		<tr>
			<td class="{$trow}" style="white-space:nowrap;"><strong>{$lang->quickedit_edit_points_desc}</strong><br /><span class="smalltext">({$lang->quickedit_current}: {$user[\'newpoints\']})</span></td>
			<td class="{$trow}" width="100%">
				<input type="text" class="textbox" name="points" value="0" /> <label><input name="points_action" type="radio" value="add" checked="checked" /> {$lang->quickedit_action_add}</label> <label><input name="points_action" type="radio" value="remove" /> {$lang->quickedit_action_remove}</label>
			</td>
		</tr>
		{$newpoints_bank}
		{$newpoints_shop}
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
	newpoints_add_template("newpoints_quickedit_shop", '<tr>
			<td class="{$trow}" style="white-space:nowrap;"><strong>{$lang->quickedit_edit_shop_desc}</strong></td>
			<td class="{$trow}" width="100%">
				<div style="max-height:200px;overflow:auto;">
					{$shop_items}
				</div>
			</td>
		</tr>', "-1");
	newpoints_add_template("newpoints_quickedit_shop_item", '<a href="{$mybb->settings[\'bburl\']}/newpoints.php?action=shop&amp;shop_action=view&amp;iid={$item[\'iid\']}"><img src="{$mybb->settings[\'bburl\']}/{$item[\'icon\']}" title="{$item[\'name\']}" style="vertical-align: middle;" /></a>
<label><input type="checkbox" class="checkbox" name="items[]" value="{$item[\'iid\']}" tabindex="{$tabindex}" /> {$item[\'name\']}</label>', "-1");
	newpoints_add_template("newpoints_quickedit_bank", '<tr>
			<td class="{$trow}" style="white-space:nowrap;"><strong>{$lang->quickedit_edit_bank_desc}</strong><br /><span class="smalltext">({$lang->quickedit_current}: {$user[\'newpoints_bankoffset\']})</span></td>
			<td class="{$trow}" width="100%">
				<input type="text" class="textbox" name="newpoints_bankoffset" value="{$newpoints_bankoffset}" />
			</td>
		</tr>', "-1");
	// Modify the postbit template to add the link variable.
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('newpoints_postbit', '#'.preg_quote('{$donate}').'#', '{$donate}{$quickedit}');
	find_replace_templatesets('newpoints_profile', '#'.preg_quote('{$donate}').'#', '{$donate}{$quickedit}');
}
function newpoints_quickedit_deactivate()
{
	global $mybb;
	// Remove the plugin template.
	newpoints_remove_templates("'newpoints_quickedit', 'newpoints_quickedit_profile', 'newpoints_quickedit_postbit', 'newpoints_quickedit_shop', 'newpoints_quickedit_shop_item', 'newpoints_quickedit_bank'");
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
	newpoints_add_setting("newpoints_quickedit_on", "main", $lang->quickedit_on_n, $lang->quickedit_on_d, "yesno", 0, 7);
	newpoints_add_setting("newpoints_quickedit_shop_on", "newpoints_shop", $lang->quickedit_shop_on_n, $lang->quickedit_shop_on_d, "yesno", 0, -2);
	newpoints_add_setting("newpoints_quickedit_shop_stock", "newpoints_shop", $lang->quickedit_shop_stock_n, $lang->quickedit_shop_stock_d, "yesno", 0, -1);
	newpoints_add_setting("newpoints_quickedit_bank_on", "newpoints_bank", $lang->quickedit_bank_on_n, $lang->quickedit_bank_on_d, "yesno", 0, 5);
}
function newpoints_quickedit_uninstall()
{
	global $mybb;

	// Remove the plugin settings.
	newpoints_remove_settings("'newpoints_quickedit_on'");

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

	if($mybb->settings['newpoints_quickedit_on'] == 1)
	{
		global $lang, $templates, $currency, $points, $quickedit, $donate;
		newpoints_lang_load("newpoints_quickedit");

		$quickedit = '';
		if($mybb->usergroup['cancp'] == 1 || ($mybb->usergroup['issupermod'] == 1 &&  $post['uid'] != $mybb->user['uid']))
		{
			eval('$quickedit = "'.$templates->get("newpoints_quickedit_postbit").'";');
			eval("\$post['newpoints_postbit'] = \"".$templates->get('newpoints_postbit')."\";");
		}
	}
}
function newpoints_quickedit_profile()
{
	global $mybb;

	if($mybb->settings['newpoints_quickedit_on'] == 1)
	{
		global $templates, $lang, $memprofile, $newpoints_profile, $quickedit, $currency, $points;
		newpoints_lang_load("newpoints_quickedit");

		$quickedit = '';
		if($mybb->usergroup['cancp'] == 1 || ($mybb->usergroup['issupermod'] == 1 &&  $post['uid'] != $mybb->user['uid']))
		{
			eval('$quickedit = "'.$templates->get("newpoints_quickedit_profile").'";');
			eval("\$newpoints_profile = \"".$templates->get('newpoints_profile')."\";");
		}
	}
}
function newpoints_quickedit_start()
{
	global $mybb;

	if($mybb->settings['newpoints_quickedit_on'] == 1 && $mybb->input['action'] == 'quickedit' && ($mybb->usergroup['cancp'] == 1 || $mybb->usergroup['cancp'] == 1))
	{
		global $db, $lang, $theme, $header, $templates, $headerinclude, $footer, $options;
		newpoints_lang_load("newpoints_quickedit");

		$mybb->input['uid'] = (int)$mybb->input['uid'];
		$mybb->input['pid'] = (int)$mybb->input['pid'];

		$colums = '';
		//*\\ Newpoints Shop Code START //*\\
		if(function_exists('newpoints_shop_page') && $mybb->settings['newpoints_quickedit_shop_on'] == 1)
		{
			$colums .= ', newpoints_items';
		}
		//*\\ Newpoints Shop Code END //*\\
		//*\\ Newpoints Bank Code START //*\\
		if(function_exists('newpoints_bank_page') && $mybb->settings['newpoints_quickedit_bank_on'] == 1)
		{
			$colums .= ', newpoints_bankoffset, newpoints_bankbasetime';
		}
		//*\\ Newpoints Bank Code END //*\\
	
		$query = $db->simple_select("users", "uid, username, newpoints{$colums}", "uid='{$mybb->input['uid']}'");
		$user = $db->fetch_array($query);

		$trow = alt_trow();
		$title = "{$lang->newpoints} {$lang->quick_edit} - {$mybb->settings['bbname']}";
		// There is no user, show error.
		if(!$user['uid'])
		{
			error($lang->quickedit_wronguser, $title);
		}
		// Super moderators can not edit their own stuff.
		if($user['uid'] == $mybb->user['uid'] && $mybb->usergroup['cancp'] != 1)
		{
			error($lang->quickedit_no_selftediting, $title);
		}

		// Lets figure out the redirect link first..
		$link = get_profile_link($user['uid']);
		if($mybb->input['pid'] > 0)
		{
			$link = get_post_link($mybb->input['pid'])."#pid{$mybb->input['pid']}";
		}
		$link = $mybb->settings['bburl'].'/'.$link;
		if($mybb->request_method == 'post')
		{
			//Verify the incoming post check to continue...
			verify_post_check($mybb->input['my_post_key']);

			//If the 'point_action' is 'remove', then remove points, otherwise, add..
			$mark = "+";
			if($mybb->input['points_action'] == "remove")
			{
				$mark = "-";
			}

			// Add/remove points now...
			$mybb->input['points'] = ((float)$mybb->input['points'] < 0 ? 0 : (float)$mybb->input['points']);
			newpoints_addpoints($user['uid'], $mark.$mybb->input['points']);

			//*\\ Newpoints Shop Code START //*\\
			if(function_exists('newpoints_shop_page') && !empty($user['newpoints_items']) && $mybb->input['items'] && $mybb->settings['newpoints_quickedit_shop_on'] == 1)
			{
				if(!is_array($mybb->input['items']))
				{
					$mybb->input['items'] = array();
				}
				$user_items = @unserialize($user['newpoints_items']);
				foreach($mybb->input['items'] as $item)
				{
					if(!($check_item = newpoints_shop_get_item($item)))
					{
						error($lang->quickedit_wrongitem, $title);
					}
					elseif(!($check_cat = newpoints_shop_get_item($item)))
					{
						error($lang->quickedit_wrongitemcat, $title);
					}
					else
					{
						if(!empty($user_items))
						{
							$key = array_search($check_item['iid'], $user_items);
							if($key === false)
							{
								error($lang->quickedit_wrongitem, $title);
							}
							else
							{
								unset($user_items[$key]);
								if($mybb->settings['newpoints_quickedit_shop_stock'] == 1)
								{
									$db->update_query('newpoints_shop_items', array('stock' => ((int)$check_item['stock'])+1), "iid='{$check_item['iid']}'");
								}
							}
						}
					}
				}
				sort($user_items);
				$db->update_query('users', array('newpoints_items' => serialize($user_items)), "uid='{$user['uid']}'");
			}
			//*\\ Newpoints Shop Code END //*\\
			//*\\ Newpoints Bank Code START //*\\
			if(function_exists('newpoints_bank_page') && $mybb->settings['newpoints_quickedit_bank_on'] == 1 && $mybb->input['newpoints_bankoffset'] != $user['newpoints_bankoffset'])
			{
				$db->update_query('users', array('newpoints_bankoffset' => floatval($mybb->input['newpoints_bankoffset'])), "uid='{$user['uid']}'");
			}
			//*\\ Newpoints Bank Code END //*\\

			// Lets finish...
			// TODO: We need to log the items that were removed / bank points being edited, right now we don't do so.
			$lang->quickedit_log = $lang->sprintf($lang->quickedit_log, htmlspecialchars_uni($user['username']), newpoints_format_points($mybb->input['points']), newpoints_format_points($user['newpoints']));
			newpoints_log("quickedit", $lang->quickedit_log, $mybb->user['username'], $mybb->user['uid']);

			// We are done.
			redirect($link, $lang->quickedit_edited);
		}

		// Get user's profile link and format points to look nice :)
		$user['username'] = build_profile_link($user['username'], $user['uid']);
		$user['newpoints'] = newpoints_format_points($user['newpoints']);

		$lang->quickedit_editing_points = $lang->sprintf($lang->quickedit_editing_points, $user['username']);
		add_breadcrumb($lang->edit_newpoints, "newpoints.php?action=quickedit");

		//*\\ Newpoints Shop Code START //*\\
		$newpoints_shop = '';
		if(function_exists('newpoints_shop_page') && !empty($user['newpoints_items']) && $mybb->settings['newpoints_quickedit_shop_on'] == 1)
		{
			$items = unserialize($user['newpoints_items']);
			$shop_items = '';
			if(!empty($items))
			{
				$query = $db->simple_select('newpoints_shop_items', 'iid, name, icon', 'visible=1 AND iid IN ('.implode(',', array_unique($items)).')', array('order_by' => 'disporder'));
				while($item = $db->fetch_array($query))
				{
					$item['iid'] = (int)$item['iid'];
					$item['name'] = htmlspecialchars_uni($item['name']);
					$item['icon'] = htmlspecialchars_uni((!empty($item['icon']) ? $item['icon'] : 'images/newpoints/default.png'));
					$tabindex = $item['iid']+10;
					eval("\$shop_items .= \"".$templates->get("newpoints_quickedit_shop_item")."\";");
				}
				eval("\$newpoints_shop = \"".$templates->get("newpoints_quickedit_shop")."\";");
			}
		}
		//*\\ Newpoints Shop Code END //*\\
		//*\\ Newpoints Bank Code START //*\\
		$newpoints_bank = '';
		if(function_exists('newpoints_bank_page') && $mybb->settings['newpoints_quickedit_bank_on'] == 1)
		{
			$newpoints_bankoffset = $user['newpoints_bankoffset'];
			$user['newpoints_bankoffset'] = newpoints_format_points($user['newpoints_bankoffset']);
			eval("\$newpoints_bank = \"".$templates->get("newpoints_quickedit_bank")."\";");
		}
		//*\\ Newpoints Bank Code END //*\\

		// Output the page...
		eval("\$page = \"".$templates->get("newpoints_quickedit")."\";");
		output_page($page);
		exit;
	}
	elseif($mybb->input['action'] == "quickedit")
	{
		error_no_permission();
	}
}
?>