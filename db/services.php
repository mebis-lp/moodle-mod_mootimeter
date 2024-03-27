<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External service definitions for mod_mootimeter.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_mootimeter_store_setting' => [
        'classname'     => 'mod_mootimeter\external\store_setting',
        'methodname'    => 'execute',
        'description'   => 'Set a new setting value.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
    'mod_mootimeter_store_page_details' => [
        'classname'     => 'mod_mootimeter\external\store_page_details',
        'methodname'    => 'execute',
        'description'   => 'Set a new setting value.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
    'mod_mootimeter_add_new_page' => [
        'classname'     => 'mod_mootimeter\external\add_new_page',
        'methodname'    => 'execute',
        'description'   => 'Add a new page.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
    'mod_mootimeter_delete_page' => [
        'classname'     => 'mod_mootimeter\external\delete_page',
        'methodname'    => 'execute',
        'description'   => 'Delete a page.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
    'mod_mootimeter_toggle_state' => [
        'classname'     => 'mod_mootimeter\external\toggle_state',
        'methodname'    => 'execute',
        'description'   => 'Toggle the state of a defined statename',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
    'mod_mootimeter_get_state' => [
        'classname'     => 'mod_mootimeter\external\get_state',
        'methodname'    => 'execute',
        'description'   => 'Toggle the state of a defined statename',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
    'mod_mootimeter_get_pages_list' => [
        'classname'     => 'mod_mootimeter\external\reload_pagelist',
        'methodname'    => 'execute',
        'description'   => 'Get the recent pagelist',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mod_mootimeter_delete_all_answers' => [
        'classname'     => 'mod_mootimeter\external\delete_all_answers',
        'methodname'    => 'execute',
        'description'   => 'Delete all answers of a page',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mod_mootimeter_delete_single_answer' => [
        'classname'     => 'mod_mootimeter\external\delete_single_answer',
        'methodname'    => 'execute',
        'description'   => 'Delete a single answer of a page',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mod_mootimeter_delete_answers_of_user' => [
            'classname'     => 'mod_mootimeter\external\delete_answers_of_user',
            'methodname'    => 'execute',
            'description'   => 'Delete all answers of a user of a page',
            'type'          => 'write',
            'ajax'          => true,
            'capabilities'  => 'mod/mootimeter:view',
    ],
    'mod_mootimeter_get_pagecontentparams' => [
        'classname'     => 'mod_mootimeter\external\get_pagecontentparams',
        'methodname'    => 'execute',
        'description'   => 'Get the params to render the pagecontent',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
];
