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
 * @package     mootimetertool_quiz
 * @category    upgrade
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Helper function used by the upgrade.php file.
 */

/**
 * Create the necessary tables.
 * @return void
 * @throws coding_exception
 * @throws ddl_exception
 * @throws ddl_change_structure_exception
 */
function mootimetertool_quiz_create_tables() {
    global $DB;

    $dbman = $DB->get_manager();

    // Define table mtmt_quiz_options to be created.
    $table = new xmldb_table('mtmt_quiz_options');

    // Adding fields to table mtmt_quiz_options.
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('pageid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
    $table->add_field('optiontext', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('optioniscorrect', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

    // Adding keys to table mtmt_quiz_options.
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

    // Adding indexes to table mtmt_quiz_options.
    $table->add_index('pageid', XMLDB_INDEX_NOTUNIQUE, ['pageid']);

    // Conditionally launch create table for mtmt_quiz_options.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // Define table mtmt_quiz_answers to be created.
    $table = new xmldb_table('mtmt_quiz_answers');

    // Adding fields to table mtmt_quiz_answers.
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('pageid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
    $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('optionid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

    // Adding keys to table mtmt_quiz_answers.
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

    // Adding indexes to table mtmt_quiz_answers.
    $table->add_index('pageid', XMLDB_INDEX_NOTUNIQUE, ['pageid']);

    // Conditionally launch create table for mtmt_quiz_answers.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }
}

/**
 * Add field optioniscorrect
 * @return void
 * @throws ddl_table_missing_exception
 * @throws ddl_exception
 * @throws dml_exception
 * @throws coding_exception
 * @throws ddl_change_structure_exception
 */
function mootimeter_quiz_add_field_optioniscorrect() {
    global $DB;

    $dbman = $DB->get_manager();

    // Define table mtmt_quiz_options to be created.
    $table = new xmldb_table('mtmt_quiz_options');
    $field = new xmldb_field('optioniscorrect', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

    // Conditionally launch add field metadatasettings.
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
}
