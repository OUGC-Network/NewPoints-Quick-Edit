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

const FIELDS_DATA = [
    'usergroups' => [
        'newpoints_quick_edit_can_use' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0,
            'formType' => 'checkBox'
        ]
    ]
];

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

    /*~*~* RUN UPDATES END *~*~*/

    db_verify_columns(FIELDS_DATA);

    $plugins_list['newpoints_quick_edit'] = $plugin_information['versioncode'];

    $cache->update('ougc_plugins', $plugins_list);

    return true;
}

function plugin_is_installed(): bool
{
    static $isInstalled = null;

    if ($isInstalled === null) {
        global $db;

        $isInstalledEach = true;

        foreach (FIELDS_DATA as $table_name => $table_columns) {
            foreach ($table_columns as $field_name => $field_data) {
                $isInstalledEach = $db->field_exists($field_name, $table_name) && $isInstalledEach;
            }
        }

        $isInstalled = $isInstalledEach;
    }

    return $isInstalled;
}

function plugin_uninstallation(): bool
{
    global $db, $cache;

    log_remove(['quickedit']);

    foreach (FIELDS_DATA as $table_name => $table_columns) {
        if ($db->table_exists($table_name)) {
            foreach ($table_columns as $field_name => $field_data) {
                if ($db->field_exists($field_name, $table_name)) {
                    $db->drop_column($table_name, $field_name);
                }
            }
        }
    }

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