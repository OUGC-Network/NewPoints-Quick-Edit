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

use function Newpoints\Core\get_setting;
use function Newpoints\Core\language_load;
use function Newpoints\Core\log_add;
use function Newpoints\Core\main_file_name;
use function Newpoints\Core\points_add_simple;
use function Newpoints\Core\points_format;
use function Newpoints\Core\points_subtract;
use function Newpoints\Core\run_hooks;
use function Newpoints\Core\url_handler_build;
use function Newpoints\QuickEdit\Core\templates_get;

use const Newpoints\Core\LOGGING_TYPE_CHARGE;
use const Newpoints\Core\LOGGING_TYPE_INCOME;

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

    if (!is_member(get_setting('quick_edit_manage_groups'))) {
        global $lang;

        language_load('quickedit');

        $page_url = url_handler_build(
            [
                'action' => get_setting('quick_edit_action_name'),
                'uid' => (int)$post_data['uid'],
                'pid' => (int)$post_data['pid']
            ]
        );

        $post_data['newpoints_quick_edit'] = eval(templates_get('postbit'));
    }

    return $post_data;
}

function member_profile_end(): bool
{
    global $mybb;
    global $memprofile;

    $memprofile['newpoints_quick_edit'] = '';

    if (!is_member(get_setting('quick_edit_manage_groups'))) {
        global $lang;

        language_load('quickedit');

        $page_url = url_handler_build(
            ['action' => get_setting('quick_edit_action_name'), 'uid' => (int)$memprofile['uid']]
        );

        $memprofile['newpoints_quick_edit'] = eval(templates_get('profile'));
    }

    return true;
}

function newpoints_default_menu(array &$menu_items): array
{
    global $mybb;

    if (!is_member(get_setting('quick_edit_manage_groups')) &&
        $mybb->get_input('action') === get_setting('quick_edit_action_name')) {
        language_load('quickedit');

        $menu_items[] = [
            'action' => get_setting('quick_edit_action_name'),
            'lang_string' => 'newpoints_quickedit_newpoints_menu',
            'category' => 'user'
        ];
    }

    return $menu_items;
}

function newpoints_terminate(): bool
{
    global $mybb;

    if ($mybb->get_input('action') !== get_setting('quick_edit_action_name')) {
        return false;
    }

    global $action_name;

    $action_name = get_setting('quick_edit_action_name');

    global $newpoints_menu;

    $newpointsFile = main_file_name();

    $current_user_id = (int)$mybb->user['uid'];

    $user_id = $mybb->get_input('uid', MYBB::INPUT_INT);

    if (!is_member(get_setting('quick_edit_manage_groups'))) {
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

    $alternative_background = alt_trow(true);

    $hook_arguments['alternative_background'] = &$alternative_background;

    if ($post_id) {
        $redirect_url = get_post_link($post_id) . "#pid{$post_id}";
    } else {
        $redirect_url = get_profile_link($user_id);
    }

    //$redirect_url = url_handler_build(['action' => $action_name, 'uid' => $user_id, 'pid' => $post_id]);

    $hook_arguments['redirect_url'] = &$redirect_url;

    $user_points = (float)$user_data['newpoints'];

    $hook_arguments = run_hooks('quick_edit_intermediate', $hook_arguments);

    if ($mybb->request_method == 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        $hook_arguments = run_hooks('quick_edit_post_start', $hook_arguments);

        $input_user_points = $mybb->get_input('user_points', MyBB::INPUT_FLOAT);

        if (!empty($input_user_points)) {
            if ($mybb->get_input('user_points_subtract', MyBB::INPUT_INT) === 1) {
                points_subtract($user_id, $input_user_points);

                log_add(
                    'quick_edit_points_subtract',
                    '',
                    $user_data['username'] ?? '',
                    $user_id,
                    $input_user_points,
                    $current_user_id,
                    0,
                    0,
                    LOGGING_TYPE_CHARGE
                );
            } else {
                points_add_simple($user_id, $input_user_points);

                log_add(
                    'quick_edit_points_add',
                    '',
                    $user_data['username'] ?? '',
                    $user_id,
                    $input_user_points,
                    $current_user_id,
                    0,
                    0,
                    LOGGING_TYPE_INCOME
                );
            }
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

        redirect("{$mybb->settings['bburl']}/{$redirect_url}", $lang->newpoints_quick_edit_redirect_successful);
    }

    $user_name = htmlspecialchars_uni($user_data['username']);

    $user_name = build_profile_link($user_name, $user_id);

    $user_points_formatted = points_format($user_points);

    $form_title = $lang->sprintf($lang->newpoints_quick_edit_table_title, $user_name);

    $page_url = url_handler_build(
        ['action' => $action_name, 'uid' => $user_id, 'pid' => $post_id]
    );

    add_breadcrumb($lang->newpoints_quick_edit_page_nav, $page_url);

    $additional_rows = [
        eval(templates_get('page_field_edit_points')),
    ];

    $alternative_background = alt_trow();

    $hook_arguments['additional_rows'] = &$additional_rows;

    $newpoints_bank = '';

    /*
    if (newpoints_quickedit_bank_is_installed()) {
        $newpoints_bankoffset = $user_data['newpoints_bankoffset'];

        $user_data['newpoints_bankoffset'] = points_format((float)$user_data['newpoints_bankoffset']);

        $newpoints_bank = eval(\Newpoints\QuickEdit\Core\templates_get('bank'));
    }
    */

    $hook_arguments = run_hooks('quick_edit_end', $hook_arguments);

    $additional_rows = implode('', $additional_rows);

    $page = eval(templates_get());

    output_page($page);

    exit;
}

function newpoints_logs_log_row(): bool
{
    global $log_data;

    if (!in_array($log_data['action'], [
        'quick_edit_points_add',
        'quick_edit_points_subtract'
    ])) {
        return false;
    }

    global $lang;
    global $log_action;

    language_load('quickedit');

    if ($log_data['action'] === 'quick_edit_points_add') {
        $log_action = $lang->newpoints_quick_edit_logs_quick_edit_points_add;
    }

    if ($log_data['action'] === 'quick_edit_points_subtract') {
        $log_action = $lang->newpoints_quick_edit_logs_quick_edit_points_subtract;
    }

    global $log_primary;

    $moderator_user_data = get_user($log_data['log_primary_id']);

    if (!empty($moderator_user_data['uid'])) {
        $log_primary = build_profile_link(
            format_name(
                htmlspecialchars_uni($moderator_user_data['username']),
                $moderator_user_data['usergroup'],
                $moderator_user_data['displaygroup']
            ),
            $moderator_user_data['uid']
        );

        $log_primary = $lang->sprintf(
            $lang->newpoints_sticky_market_page_logs_primary,
            $log_primary
        );
    }

    return true;
}

function newpoints_logs_end(): bool
{
    global $lang;
    global $action_types;

    language_load('quickedit');

    foreach ($action_types as $key => &$action_type) {
        if ($key === 'quick_edit_points_add') {
            $action_type = $lang->newpoints_quick_edit_logs_quick_edit_points_add;
        }

        if ($key === 'quick_edit_points_subtract') {
            $action_type = $lang->newpoints_quick_edit_logs_quick_edit_points_subtract;
        }
    }

    return true;
}

function fetch_wol_activity_end(array &$hook_parameters): array
{
    global $lang;

    if (my_strpos($hook_parameters['location'], main_file_name()) === false ||
        my_strpos($hook_parameters['location'], 'action=' . get_setting('quick_edit_action_name')) === false) {
        return $hook_parameters;
    }

    $hook_parameters['activity'] = 'newpoints_quick_edit';

    return $hook_parameters;
}

function build_friendly_wol_location_end(array $hook_parameters): array
{
    global $mybb, $lang;

    language_load('quickedit');

    switch ($hook_parameters['user_activity']['activity']) {
        case 'newpoints_quick_edit':
            $hook_parameters['location_name'] = $lang->sprintf(
                $lang->newpoints_quick_edit_wol_location,
                $mybb->settings['bburl'],
                main_file_name(),
                url_handler_build(['action' => get_setting('quick_edit_action_name')])
            );
            break;
    }

    return $hook_parameters;
}