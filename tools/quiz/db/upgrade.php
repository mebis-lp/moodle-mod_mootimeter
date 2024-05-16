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
 * Plugin upgrade steps are defined here.
 *
 * @package     mootimetertool_quiz
 * @category    upgrade
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/upgradelib.php');

/**
 * Execute mootimetertool_quiz upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_mootimetertool_quiz_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // For further information please read {@link https://docs.moodle.org/dev/Upgrade_API}.
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at {@link https://docs.moodle.org/dev/XMLDB_editor}.
    if ($oldversion < 2023060801) {

        mootimetertool_quiz_create_tables();

        // Quiz savepoint reached.
        upgrade_plugin_savepoint(true, 2023060801, 'mootimetertool', 'quiz');
    }

    if ($oldversion < 2023061400) {

        mootimeter_quiz_add_field_optioniscorrect();

        // Quiz savepoint reached.
        upgrade_plugin_savepoint(true, 2023061400, 'mootimetertool', 'quiz');
    }

    if ($oldversion < 2023101600) {

        // Define field usermodified to be dropped from mtmt_quiz_options.
        $table = new xmldb_table('mtmt_quiz_options');
        $field = new xmldb_field('usermodified');

        // Conditionally launch drop field usermodified.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Quiz savepoint reached.
        upgrade_plugin_savepoint(true, 2023101600, 'mootimetertool', 'quiz');
    }

    if ($oldversion < 2024030600) {

        // Define table mtmt_quiz_options to be renamed to mootimetertool_quiz_options.
        $table = new xmldb_table('mtmt_quiz_options');
        // Launch rename table for mootimetertool_quiz_options.
        $dbman->rename_table($table, 'mootimetertool_quiz_options');

        // Define table mtmt_quiz_answers to be renamed to mootimetertool_quiz_answers.
        $table = new xmldb_table('mtmt_quiz_answers');
        // Launch rename table for mootimetertool_quiz_answers.
        $dbman->rename_table($table, 'mootimetertool_quiz_answers');

        // Quiz savepoint reached.
        upgrade_plugin_savepoint(true, 2024030600, 'mootimetertool', 'quiz');
    }

    return true;
}
