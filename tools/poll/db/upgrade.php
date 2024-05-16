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
 * @package     mootimetertool_poll
 * @category    upgrade
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/upgradelib.php');

/**
 * Execute mootimetertool_poll upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_mootimetertool_poll_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024030600) {

        // Define table mtmt_poll_options to be renamed to mootimetertool_poll_options.
        $table = new xmldb_table('mtmt_poll_options');
        // Launch rename table for mootimetertool_poll_options.
        $dbman->rename_table($table, 'mootimetertool_poll_options');

        // Define table mtmt_poll_answers to be renamed to mootimetertool_poll_answers.
        $table = new xmldb_table('mtmt_poll_answers');
        // Launch rename table for mootimetertool_poll_answers.
        $dbman->rename_table($table, 'mootimetertool_poll_answers');

        // Poll savepoint reached.
        upgrade_plugin_savepoint(true, 2024030600, 'mootimetertool', 'poll');

    }

    return true;
}
