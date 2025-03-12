<?php

/***************************************************************************
 *
 *    Newpoints Quick Edit plugin (/inc/plugins/newpoints/plugins/ougc/QuickEdit/admin.php)
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

namespace Newpoints\QuickEdit\Admin;

use function Newpoints\Admin\db_verify_columns;
use function Newpoints\Core\language_load;
use function Newpoints\Core\log_remove;
use function Newpoints\Core\settings_remove;
use function Newpoints\Core\templates_remove;

function plugin_information(): array
{
    global $lang;

    language_load('quickedit');

    return [
        'name' => 'Quick Edit',
        'description' => $lang->newpoints_quick_edit_desc,
        'website' => 'https://ougc.network',
        'author' => 'Omar G.',
        'authorsite' => 'https://ougc.network',
        'version' => '3.0.0',
        'versioncode' => 3000,
        'compatibility' => '3*'
    ];
}

function plugin_activation(): bool
{
    global $cache;

    language_load('quickedit');

    $plugin_information = plugin_information();

    $plugins_list = $cache->read('ougc_plugins');

    if (!$plugins_list) {
        $plugins_list = [];
    }

    if (!isset($plugins_list['newpoints_quick_edit'])) {
        $plugins_list['newpoints_quick_edit'] = $plugin_information['versioncode'];
    }

    /*~*~* RUN UPDATES START *~*~*/

    global $db;

    if ($db->field_exists('newpoints_quick_edit_can_use', 'usergroups')) {
        $db->drop_column('newpoints_quick_edit_can_use', 'usergroups');
    }

    /*~*~* RUN UPDATES END *~*~*/

    $plugins_list['newpoints_quick_edit'] = $plugin_information['versioncode'];

    $cache->update('ougc_plugins', $plugins_list);

    return true;
}

function plugin_is_installed(): bool
{
    global $cache;

    $plugins_list = (array)$cache->read('ougc_plugins');

    return isset($plugins_list['newpoints_quick_edit']);
}

function plugin_uninstallation(): bool
{
    global $cache;

    log_remove(['quickedit']);

    settings_remove(
        [
            'shop_on',
            'shop_stock',
            'bank_on'
        ],
        'newpoints_quickedit_'
    );

    templates_remove(['', 'profile', 'postbit', 'shop', 'shop_item', 'bank'], 'newpoints_quickedit_');

    $plugins_list = (array)$cache->read('ougc_plugins');

    if (isset($plugins_list['newpoints_quick_edit'])) {
        unset($plugins_list['newpoints_quick_edit']);
    }

    if (!empty($plugins_list)) {
        $cache->update('ougc_plugins', $plugins_list);
    } else {
        $cache->delete('ougc_plugins');
    }

    return true;
}