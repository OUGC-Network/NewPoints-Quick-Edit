<?php

/***************************************************************************
 *
 *    NewPoints Quick Edit plugin (/inc/plugins/newpoints/plugins/ougc/QuickEdit/core.php)
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

namespace NewPoints\QuickEdit\Core;

use const NewPoints\QuickEdit\ROOT;

function templates_get(string $template_name = '', bool $enable_html_comments = true): string
{
    return \NewPoints\Core\templates_get($template_name, $enable_html_comments, ROOT, 'quickedit_');
}

function build_field_check_box(
    string &$alternative_background,
    string $title,
    string $description,
    string $label,
    int $value,
    bool $is_checked,
    string $name,
    string $id = ''
): string {
    if (empty($id)) {
        $id = $name;
    }

    $checked_element = '';

    if ($is_checked) {
        $checked_element = 'checked="checked"';
    }

    $field_code = eval(templates_get('page_field_check_box'));

    $alternative_background = alt_trow();

    return $field_code;
}