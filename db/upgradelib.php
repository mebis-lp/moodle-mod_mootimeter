<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin upgrade helper functions are defined here.
 *
 * @package     mod_mootimeter
 * @category    upgrade
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Helper function used by the upgrade.php file.
 */
function mod_mootimeter_helper_function() {
    global $DB;

    // Please note: you can only use raw low level database access here.
    // Avoid Moodle API calls in upgrade steps.
    //
    // For more information please read {@link https://docs.moodle.org/dev/Upgrade_API}.
}

function mod_mootimeter_create_mootimeter_pages_table() {
    global $DB;

    $dbman = $DB->get_manager();

    // Define table mootimeter_pages to be created.
    $table = new xmldb_table('mootimeter_pages');

    // Adding fields to table mootimeter_pages.
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('instance', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
    $table->add_field('tool', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
    $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null);
    $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '5', null, null, null, null);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

    // Adding keys to table mootimeter_pages.
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

    // Adding indexes to table mootimeter_pages.
    $table->add_index('instance_tool', XMLDB_INDEX_NOTUNIQUE, ['instance', 'tool']);
    $table->add_index('tool', XMLDB_INDEX_NOTUNIQUE, ['tool']);

    // Conditionally launch create table for mootimeter_pages.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }
}

function mod_mootimeter_create_mootimeter_tool_settings_table() {
    global $DB;

    $dbman = $DB->get_manager();

    // Define table mootimeter_tool_settings to be created.
    $table = new xmldb_table('mootimeter_tool_settings');

    // Adding fields to table mootimeter_tool_settings.
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('tool', XMLDB_TYPE_CHAR, '100', null, null, null, null);
    $table->add_field('pageid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
    $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, null, null, null);
    $table->add_field('value', XMLDB_TYPE_CHAR, '1333', null, null, null, null);

    // Adding keys to table mootimeter_tool_settings.
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

    // Adding indexes to table mootimeter_tool_settings.
    $table->add_index('tool_pageid_name', XMLDB_INDEX_UNIQUE, ['tool', 'pageid', 'name']);

    // Conditionally launch create table for mootimeter_tool_settings.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }
}
