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
use function Newpoints\Core\templates_get;

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

        $quick_edit = eval(templates_get('quickedit_postbit'));

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

        $quick_edit = eval(templates_get('quickedit_profile'));

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

    if ($mybb->get_input('action') !== 'quickedit') {
        return false;
    }

    $currentUserID = (int)$mybb->user['uid'];

    $userID = $mybb->get_input('uid', MYBB::INPUT_INT);

    if (empty($mybb->usergroup['cancp']) || (!empty($mybb->usergroup['issupermod']) && $userID === $currentUserID)) {
        error_no_permission();
    }

    global $db, $lang, $theme, $header, $templates, $headerinclude, $footer, $options;

    language_load('quickedit');

    $postID = $mybb->get_input('pid', MYBB::INPUT_INT);

    $inputPoints = $mybb->get_input('points', MYBB::INPUT_FLOAT);

    if ($inputPoints < 0) {
        $inputPoints = 0;
    }

    $colums = '';
    //*\\ Newpoints Shop Code START //*\\
    if (function_exists('newpoints_shop_page') && $mybb->settings['newpoints_quickedit_shop_on'] == 1) {
        $colums .= ', newpoints_items';
    }
    //*\\ Newpoints Shop Code END //*\\
    //*\\ Newpoints Bank Code START //*\\
    if (function_exists('newpoints_bank_page') && $mybb->settings['newpoints_quickedit_bank_on'] == 1) {
        $colums .= ', newpoints_bankoffset, newpoints_bankbasetime';
    }
    //*\\ Newpoints Bank Code END //*\\

    $query = $db->simple_select('users', "uid, username, newpoints{$colums}", "uid='{$userID}'");
    $userData = $db->fetch_array($query);

    $trow = alt_trow();
    $title = "{$lang->newpoints} {$lang->quick_edit} - {$mybb->settings['bbname']}";
    // There is no user, show error.
    if (!$userData['uid']) {
        error($lang->quickedit_wronguser, $title);
    }
    // Super moderators can not edit their own stuff.
    if ($userData['uid'] == $mybb->user['uid'] && $mybb->usergroup['cancp'] != 1) {
        error($lang->quickedit_no_selftediting, $title);
    }

    // Lets figure out the redirect link first..
    $link = get_profile_link($userID);

    if ($postID) {
        $link = get_post_link($postID) . "#pid{$postID}";
    }

    $link = $mybb->settings['bburl'] . '/' . $link;

    if ($mybb->request_method == 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        newpoints_addpoints($userID, $inputPoints);

        //*\\ Newpoints Shop Code START //*\\
        if (function_exists(
                'newpoints_shop_page'
            ) && !empty($userData['newpoints_items']) && $mybb->get_input(
                'items',
                MyBB::INPUT_ARRAY
            ) && $mybb->settings['newpoints_quickedit_shop_on'] == 1) {
            $user_items = @unserialize($userData['newpoints_items']);

            foreach ($mybb->get_input('items', MyBB::INPUT_ARRAY) as $item) {
                if (!($check_item = newpoints_shop_get_item($item))) {
                    error($lang->quickedit_wrongitem, $title);
                } elseif (!($check_cat = newpoints_shop_get_item($item))) {
                    error($lang->quickedit_wrongitemcat, $title);
                } elseif (!empty($user_items)) {
                    $key = array_search($check_item['iid'], $user_items);

                    if ($key === false) {
                        error($lang->quickedit_wrongitem, $title);
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

        //*\\ Newpoints Shop Code END //*\\
        //*\\ Newpoints Bank Code START //*\\
        if (function_exists(
                'newpoints_bank_page'
            ) && $mybb->settings['newpoints_quickedit_bank_on'] == 1 && $mybb->get_input(
                'my_post_key',
                MyBB::INPUT_FLOAT
            ) != $userData['newpoints_bankoffset']) {
            $db->update_query(
                'users',
                ['newpoints_bankoffset' => $mybb->get_input('my_post_key', MyBB::INPUT_FLOAT)],
                "uid='{$userData['uid']}'"
            );
        }
        //*\\ Newpoints Bank Code END //*\\

        // Lets finish...
        // TODO: We need to log the items that were removed / bank points being edited, right now we don't do so.
        $lang->quickedit_log = $lang->sprintf(
            $lang->quickedit_log,
            htmlspecialchars_uni($userData['username']),
            newpoints_format_points($inputPoints),
            newpoints_format_points($userData['newpoints'])
        );

        newpoints_log('quickedit', $lang->quickedit_log, $mybb->user['username'], $mybb->user['uid']);

        redirect($link, $lang->quickedit_edited);
    }

    // Get user's profile link and format points to look nice :)
    $userData['username'] = build_profile_link($userData['username'], $userID);

    $userData['newpoints'] = newpoints_format_points($userData['newpoints']);

    $lang->quickedit_editing_points = $lang->sprintf($lang->quickedit_editing_points, $userData['username']);

    add_breadcrumb($lang->edit_newpoints, 'newpoints.php?action=quickedit');

    //*\\ Newpoints Shop Code START //*\\
    $newpoints_shop = '';

    if (function_exists(
            'newpoints_shop_page'
        ) && !empty($userData['newpoints_items']) && $mybb->settings['newpoints_quickedit_shop_on'] == 1) {
        $items = unserialize($userData['newpoints_items']);

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

                $shop_items .= eval(templates_get('quickedit_shop_item'));
            }

            $newpoints_shop = eval(templates_get('quickedit_shop'));
        }
    }
    //*\\ Newpoints Shop Code END //*\\
    //*\\ Newpoints Bank Code START //*\\
    $newpoints_bank = '';
    if (function_exists('newpoints_bank_page') && $mybb->settings['newpoints_quickedit_bank_on'] == 1) {
        $newpoints_bankoffset = $userData['newpoints_bankoffset'];

        $userData['newpoints_bankoffset'] = newpoints_format_points($userData['newpoints_bankoffset']);

        $newpoints_bank = eval(templates_get('quickedit_bank'));
    }
    //*\\ Newpoints Bank Code END //*\\

    $page = eval(templates_get('quickedit'));

    output_page($page);

    exit;
}