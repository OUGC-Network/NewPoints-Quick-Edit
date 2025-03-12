<?php

/***************************************************************************
 *
 *    NewPoints Quick Edit plugin (/inc/plugins/newpoints/plugins/newpoints_quickedit.php)
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

use function NewPoints\QuickEdit\Admin\plugin_activation;
use function NewPoints\QuickEdit\Admin\plugin_information;
use function NewPoints\QuickEdit\Admin\plugin_is_installed;
use function NewPoints\QuickEdit\Admin\plugin_uninstallation;
use function NewPoints\Core\add_hooks;

use const NewPoints\QuickEdit\ROOT;
use const NewPoints\ROOT_PLUGINS;

defined('IN_MYBB') || die('Direct initialization of this file is not allowed.');

define('NewPoints\QuickEdit\ROOT', ROOT_PLUGINS . '/QuickEdit');

require_once ROOT . '/core.php';

if (defined('IN_ADMINCP')) {
    require_once ROOT . '/admin.php';

    require_once ROOT . '/hooks/admin.php';

    add_hooks('NewPoints\QuickEdit\Hooks\Admin');
} else {
    require_once ROOT . '/hooks/forum.php';

    add_hooks('NewPoints\QuickEdit\Hooks\Forum');
}

function newpoints_quickedit_info(): array
{
    return plugin_information();
}

function newpoints_quickedit_activate(): bool
{
    return plugin_activation();
}

function newpoints_quickedit_uninstall(): bool
{
    return plugin_uninstallation();
}

function newpoints_quickedit_is_installed(): bool
{
    return plugin_is_installed();
}