<?php

/***************************************************************************
 *
 *    Newpoints Quick Edit plugin (/inc/plugins/newpoints/plugins/ougc/QuickEdit/hooks/forum.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2012 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Quickly edit user's points without accessing to the ACP.
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
use function Newpoints\Core\points_add;
use function Newpoints\Core\points_format;
use function Newpoints\Core\run_hooks;
use function Newpoints\Core\url_handler_build;

function global_start(): bool
{
    if (in_array(THIS_SCRIPT, ['newpoints.php', 'showthread.php', 'member.php'])) {
        global $templatelist;

        if (isset($templatelist)) {
            $templatelist .= ',';
        } else {
            $templatelist = '';
        }

        $templatelist .= 'newpoints_quickedit, newpoints_quickedit, newpoints_quickedit_profile';
    }

    return true;
}

function postbit50(array &$post): array
{
    global $mybb;

    $currentUserID = (int)$mybb->user['uid'];

    $postUserID = (int)$post['uid'];

    $postID = (int)$post['pid'];

    if (!empty($mybb->usergroup['cancp']) || (!empty($mybb->usergroup['issupermod']) && $postUserID !== $currentUserID)) {
        global $lang;

        language_load('quickedit');

        $pageUrl = url_handler_build(['action' => 'quick_edit', 'uid' => $postUserID, 'pid' => $postID]);

        $quick_edit = eval(newpoints_quickedit_get_template('postbit'));

        $post['newpoints_postbit'] = str_replace(
            '<!--NEWPOINTS_QUICK_EDIT-->',
            $quick_edit,
            $post['newpoints_postbit']
        );
    }

    return $post;
}

function member_profile_end(): bool
{
    global $mybb;
    global $memprofile;

    $currentUserID = (int)$mybb->user['uid'];

    $profileUserID = (int)$memprofile['uid'];

    if (!empty($mybb->usergroup['cancp']) || (!empty($mybb->usergroup['issupermod']) && $profileUserID !== $currentUserID)) {
        global $lang;
        global $newpoints_profile;

        language_load('quickedit');

        $pageUrl = url_handler_build(['action' => 'quick_edit', 'uid' => $profileUserID]);

        $quick_edit = eval(newpoints_quickedit_get_template('profile'));

        $newpoints_profile = str_replace(
            '<!--NEWPOINTS_QUICK_EDIT-->',
            $quick_edit,
            $newpoints_profile
        );
    }

    return true;
}

function newpoints_start(): bool
{
    global $mybb;

    if ($mybb->get_input('action') !== 'quick_edit') {
        return false;
    }

    $currentUserID = (int)$mybb->user['uid'];

    $userID = $mybb->get_input('uid', MYBB::INPUT_INT);

    if (!(!empty($mybb->usergroup['cancp']) || (!empty($mybb->usergroup['issupermod']) && $userID === $currentUserID))) {
        error_no_permission();
    }

    global $db, $lang, $theme, $header, $templates, $headerinclude, $footer, $options;

    language_load('quickedit');

    $postID = $mybb->get_input('pid', MYBB::INPUT_INT);

    $db_fields = ['uid', 'username', 'newpoints'];

    $hook_arguments = [
        'db_fields' => &$db_fields
    ];

    $hook_arguments = run_hooks('quick_edit_start', $hook_arguments);

    if (newpoints_quickedit_shop_is_installed()) {
        $db_fields[] = 'newpoints_items';
    }

    if (newpoints_quickedit_bank_is_installed()) {
        $db_fields[] = 'newpoints_bankoffset';

        $db_fields[] = 'newpoints_bankbasetime';
    }

    $query = $db->simple_select('users', implode(',', $db_fields), "uid='{$userID}'");

    if (!$db->num_rows($query)) {
        error($lang->newpoints_quick_edit_invalid_user);
    }

    $user_data = $db->fetch_array($query);

    $hook_arguments['user_data'] = &$user_data;

    $trow = alt_trow();

    if ($postID) {
        $redirect_url = get_post_link($postID) . "#pid{$postID}";
    } else {
        $redirect_url = get_profile_link($userID);
    }

    $hook_arguments['redirect_url'] = &$redirect_url;

    $userPoints = (float)$user_data['newpoints'];

    $hook_arguments = run_hooks('quick_edit_intermediate', $hook_arguments);

    if ($mybb->request_method == 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        $hook_arguments = run_hooks('quick_edit_post_start', $hook_arguments);

        $userPointsInput = $mybb->get_input('userPoints', MyBB::INPUT_FLOAT);

        if (!empty($userPointsInput)) {
            points_add($userID, $userPointsInput);
        }

        if (newpoints_quickedit_shop_is_installed() && !empty($user_data['newpoints_items']) && $mybb->get_input(
                'items',
                MyBB::INPUT_ARRAY
            )) {
            $user_items = @unserialize($user_data['newpoints_items']);

            foreach ($mybb->get_input('items', MyBB::INPUT_ARRAY) as $item) {
                if (!($check_item = newpoints_shop_get_item($item))) {
                    error($lang->quickedit_wrongitem);
                } elseif (!($check_cat = newpoints_shop_get_item($item))) {
                    error($lang->quickedit_wrongitemcat);
                } elseif (!empty($user_items)) {
                    $key = array_search($check_item['iid'], $user_items);

                    if ($key === false) {
                        error($lang->quickedit_wrongitem);
                    } else {
                        unset($user_items[$key]);

                        if ($mybb->settings['newpoints_quickedit_shop_stock'] == 1) {
                            $db->update_query(
                                'newpoints_shop_items',
                                ['stock' => ((int)$check_item['stock']) + 1],
                                "iid='{$check_item['iid']}'"
                            );
                        }
                    }
                }
            }

            sort($user_items);

            $db->update_query('users', ['newpoints_items' => serialize($user_items)], "uid='{$userID}'");
        }

        if (newpoints_quickedit_bank_is_installed() && $mybb->get_input(
                'my_post_key',
                MyBB::INPUT_FLOAT
            ) != $user_data['newpoints_bankoffset']) {
            $db->update_query(
                'users',
                ['newpoints_bankoffset' => $mybb->get_input('my_post_key', MyBB::INPUT_FLOAT)],
                "uid='{$userID}'"
            );
        }

        $hook_arguments = run_hooks('quick_edit_post_end', $hook_arguments);

        // TODO: We need to log the items that were removed / bank points being edited, right now we don't do so.
        $log_text = $lang->sprintf(
            $lang->newpoints_quick_edit_log_item,
            $user_data['username'],
            points_format($userPointsInput),
            points_format($userPoints)
        );

        newpoints_log('quickedit', $log_text, $mybb->user['username'], $currentUserID);

        redirect("{$mybb->settings['bburl']}/{$redirect_url}", $lang->newpoints_quick_edit_redirect_successful);
    }

    $userName = htmlspecialchars_uni($user_data['username'], $userID);

    $userName = build_profile_link($userName, $userID);

    $userPointsFormatted = points_format($userPoints);

    $form_title = $lang->sprintf($lang->newpoints_quick_edit_table_title, $userName);

    $pageUrl = url_handler_build(['action' => 'quick_edit', 'uid' => $currentUserID, 'pid' => $postID]);

    add_breadcrumb($lang->newpoints_quick_edit_page_nav, $pageUrl);

    $newpoints_shop = '';

    if (newpoints_quickedit_shop_is_installed() && !empty($user_data['newpoints_items'])) {
        $items = unserialize($user_data['newpoints_items']);

        $shop_items = '';

        if (!empty($items)) {
            $query = $db->simple_select(
                'newpoints_shop_items',
                'iid, name, icon',
                'visible=1 AND iid IN (' . implode(',', array_unique($items)) . ')',
                ['order_by' => 'disporder']
            );

            while ($item = $db->fetch_array($query)) {
                $item['iid'] = (int)$item['iid'];

                $item['name'] = htmlspecialchars_uni($item['name']);

                $item['icon'] = htmlspecialchars_uni(
                    (!empty($item['icon']) ? $item['icon'] : 'images/newpoints/default.png')
                );

                $tabindex = $item['iid'] + 10;

                $shop_items .= eval(newpoints_quickedit_get_template('shop_item'));
            }

            $newpoints_shop = eval(newpoints_quickedit_get_template('shop'));
        }
    }

    $newpoints_bank = '';

    if (newpoints_quickedit_bank_is_installed()) {
        $newpoints_bankoffset = $user_data['newpoints_bankoffset'];

        $user_data['newpoints_bankoffset'] = points_format((float)$user_data['newpoints_bankoffset']);

        $newpoints_bank = eval(newpoints_quickedit_get_template('bank'));
    }

    $hook_arguments = run_hooks('quick_edit_end', $hook_arguments);

    $page = eval(newpoints_quickedit_get_template());

    output_page($page);

    exit;
}