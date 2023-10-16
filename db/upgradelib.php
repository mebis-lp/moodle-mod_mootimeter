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
 * Helper functions used by the upgrade.php file.
 */

/**
 * Helper funciton to create tool settings table.
 * @return void
 * @throws coding_exception
 * @throws ddl_exception
 * @throws ddl_change_structure_exception
 */
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
