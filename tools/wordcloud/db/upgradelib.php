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
 * @package     mootimetertool_wordcloud
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

/**
 * Create wordcloud tabels
 * @return void
 * @throws coding_exception
 * @throws ddl_exception
 * @throws ddl_change_structure_exception
 */
function mootimetertool_wordcloud_create_tables() {
    global $DB;

    $dbman = $DB->get_manager();

    // Define table mtmt_wordcloud_answers to be created.
    $table = new xmldb_table('mtmt_wordcloud_answers');

    // Adding fields to table mtmt_wordcloud_answers.
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('pageid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
    $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('answer', XMLDB_TYPE_CHAR, '255', null, null, null, null);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

    // Adding keys to table mtmt_wordcloud_answers.
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

    // Adding indexes to table mtmt_wordcloud_answers.
    $table->add_index('pageid_answer', XMLDB_INDEX_NOTUNIQUE, ['pageid', 'answer']);

    // Conditionally launch create table for mtmt_wordcloud_answers.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }
}
