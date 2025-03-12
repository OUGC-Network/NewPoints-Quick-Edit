<?php

/***************************************************************************
 *
 *    NewPoints Quick Edit plugin (/inc/plugins/newpoints/plugins/ougc/QuickEdit/hooks/admin.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2012 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Quickly edit user's NewPoints data from the forums.
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

namespace NewPoints\QuickEdit\Hooks\Admin;

use function NewPoints\Core\language_load;

use const NewPoints\QuickEdit\ROOT;

function newpoints_settings_rebuild_start(array &$hook_arguments): array
{
    language_load('quickedit');

    $hook_arguments['settings_directories'][] = ROOT . '/settings';

    return $hook_arguments;
}

function newpoints_admin_settings_intermediate(array &$hook_arguments): array
{
    language_load('quickedit');

    unset($hook_arguments['active_plugins']['newpoints_quickedit']);

    $hook_arguments['setting_groups_objects']['quick_edit'] = [];

    return $hook_arguments;
}

function newpoints_admin_settings_commit_start(array &$setting_groups_objects): array
{
    language_load('quickedit');

    $setting_groups_objects['quick_edit'] = [];

    return $setting_groups_objects;
}

function newpoints_templates_rebuild_start(array &$hook_arguments): array
{
    $hook_arguments['templates_directories']['quickedit'] = ROOT . '/templates';

    return $hook_arguments;
}