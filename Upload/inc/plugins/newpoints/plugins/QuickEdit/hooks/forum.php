<?php

/***************************************************************************
 *
 *    Newpoints Quick Edit plugin (/inc/plugins/newpoints/plugins/ougc/QuickEdit/hooks/forum.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2012 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Quickly edit user's Newpoints data from the forums.
 *
 ***************************************************************************
 ****************************************************************************
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 ****************************************************************************/

declare(strict_types=1);

namespace Newpoints\QuickEdit\Hooks\Forum;

use MyBB;

use function Newpoints\Core\language_load;
use function Newpoints\Core\log_add;
use function Newpoints\Core\main_file_name;
use function Newpoints\Core\points_add;
use function Newpoints\Core\points_format;
use function Newpoints\Core\run_hooks;
use function Newpoints\Core\url_handler_build;

function newpoints_global_start(array &$hook_arguments): array
{
    global $mybb;

    foreach (['newpoints.php', 'showthread.php', 'member.php'] as $script_name) {
        $hook_arguments[$script_name] = array_merge(
            $hook_arguments[$script_name] ?? [],
            [
                'newpoints_quickedit',
                'newpoints_quickedit_bank',
                'newpoints_quickedit_postbit',
                'newpoints_quickedit_profile',
            ]
        );
    }


    return $hook_arguments;
}

function postbit50(array &$post_data): array
{
    global $mybb;

    $post_data['newpoints_quick_edit'] = '';

    if (!empty($mybb->usergroup['newpoints_quick_edit_can_use'])) {
        global $lang;

        language_load('quickedit');

        $page_url = url_handler_build(
            ['action' => 'quick_edit', 'uid' => (int)$post_data['uid'], 'pid' => (int)$post_data['pid']]
        );

        $post_data['newpoints_quick_edit'] = eval(newpoints_quickedit_get_template('postbit'));
    }

    return $post_data;
}

function member_profile_end(): bool
{
    global $mybb;
    global $newpoints_quick_edit;

    $newpoints_quick_edit = '';

    if (!empty($mybb->usergroup['newpoints_quick_edit_can_use'])) {
        global $lang;
        global $memprofile;

        language_load('quickedit');

        $page_url = url_handler_build(['action' => 'quick_edit', 'uid' => (int)$memprofile['uid']]);

        $newpoints_quick_edit = eval(newpoints_quickedit_get_template('profile'));
    }

    return true;
}

function newpoints_default_menu(array &$menu_items): array
{
    global $mybb;

    if (!empty($mybb->usergroup['newpoints_quick_edit_can_use']) && $mybb->get_input('action') === 'quick_edit') {
        language_load('quickedit');

        $menu_items[90] = [
            'action' => 'quick_edit',
            'lang_string' => 'newpoints_quickedit_newpoints_menu',
        ];
    }

    return $menu_items;
}

function newpoints_terminate(): bool
{
    global $mybb;

    if ($mybb->get_input('action') !== 'quick_edit') {
        return false;
    }

    global $newpoints_menu;

    $newpointsFile = main_file_name();

    $current_user_id = (int)$mybb->user['uid'];

    $user_id = $mybb->get_input('uid', MYBB::INPUT_INT);

    if (empty($mybb->usergroup['newpoints_quick_edit_can_use'])) {
        error_no_permission();
    }

    global $db, $lang, $theme, $header, $templates, $headerinclude, $footer, $options;

    language_load('quickedit');

    $post_id = $mybb->get_input('pid', MYBB::INPUT_INT);

    $db_fields = ['uid', 'username', 'newpoints'];

    $hook_arguments = [
        'db_fields' => &$db_fields
    ];

    $hook_arguments = run_hooks('quick_edit_start', $hook_arguments);

    /*
    if (newpoints_quickedit_bank_is_installed()) {
        $db_fields[] = 'newpoints_bankoffset';

        $db_fields[] = 'newpoints_bankbasetime';
    }
    */

    $query = $db->simple_select('users', implode(',', $db_fields), "uid='{$user_id}'");

    if (!$db->num_rows($query)) {
        error_no_permission();
    }

    $user_data = $db->fetch_array($query);

    $hook_arguments['user_data'] = &$user_data;

    $alternative_background = alt_trow();

    $hook_arguments['alternative_background'] = &$alternative_background;

    if ($post_id) {
        $redirect_url = get_post_link($post_id) . "#pid{$post_id}";
    } else {
        $redirect_url = get_profile_link($user_id);
    }

    $hook_arguments['redirect_url'] = &$redirect_url;

    $user_points = (float)$user_data['newpoints'];

    $hook_arguments = run_hooks('quick_edit_intermediate', $hook_arguments);

    if ($mybb->request_method == 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        $hook_arguments = run_hooks('quick_edit_post_start', $hook_arguments);

        $user_points_input = $mybb->get_input('userPoints', MyBB::INPUT_FLOAT);

        if (!empty($user_points_input)) {
            points_add($user_id, $user_points_input);
        }

        /*
        if (newpoints_quickedit_bank_is_installed() && $mybb->get_input(
                'my_post_key',
                MyBB::INPUT_FLOAT
            ) != $user_data['newpoints_bankoffset']) {
            $db->update_query(
                'users',
                ['newpoints_bankoffset' => $mybb->get_input('my_post_key', MyBB::INPUT_FLOAT)],
                "uid='{$user_id}'"
            );
        }
        */

        $hook_arguments = run_hooks('quick_edit_post_end', $hook_arguments);

        log_add(
            'quickedit',
            "uid:{$user_id};points:{$user_points_input}",
            $mybb->user['username'] ?? '',
            $current_user_id
        );

        redirect("{$mybb->settings['bburl']}/{$redirect_url}", $lang->newpoints_quick_edit_redirect_successful);
    }

    $user_name = htmlspecialchars_uni($user_data['username']);

    $user_name = build_profile_link($user_name, $user_id);

    $user_points_formatted = points_format($user_points);

    $form_title = $lang->sprintf($lang->newpoints_quick_edit_table_title, $user_name);

    $page_url = url_handler_build(['action' => 'quick_edit', 'uid' => $current_user_id, 'pid' => $post_id]);

    add_breadcrumb($lang->newpoints_quick_edit_page_nav, $page_url);

    $additional_rows = '';

    $hook_arguments['additional_rows'] = &$additional_rows;

    $newpoints_bank = '';

    /*
    if (newpoints_quickedit_bank_is_installed()) {
        $newpoints_bankoffset = $user_data['newpoints_bankoffset'];

        $user_data['newpoints_bankoffset'] = points_format((float)$user_data['newpoints_bankoffset']);

        $newpoints_bank = eval(newpoints_quickedit_get_template('bank'));
    }
    */

    $hook_arguments = run_hooks('quick_edit_end', $hook_arguments);

    $page = eval(newpoints_quickedit_get_template());

    output_page($page);

    exit;
}