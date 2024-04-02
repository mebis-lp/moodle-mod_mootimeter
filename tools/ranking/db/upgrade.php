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
 * @package     mootimetertool_ranking
 * @copyright   2024, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/upgradelib.php');

/**
 * Execute mootimetertool_ranking upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_mootimetertool_ranking_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2024030601) {

        mootimetertool_ranking_create_tables();

        // Ranking savepoint reached.
        upgrade_plugin_savepoint(true, 2024030601, 'mootimetertool', 'ranking');
    }

    if ($oldversion < 2024030602) {

        // Define field pageid to be added to mootimetertool_ranking_phrases.
        $table = new xmldb_table('mootimetertool_ranking_phrases');
        $field = new xmldb_field('pageid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'usermodified');

        // Conditionally launch add field pageid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ranking savepoint reached.
        upgrade_plugin_savepoint(true, 2024030602, 'mootimetertool', 'ranking');
    }

    if ($oldversion < 2024030606) {
        // Ranking savepoint reached.
        upgrade_plugin_savepoint(true, 2024030606, 'mootimetertool', 'ranking');
    }

    if ($oldversion < 2024030607) {

        // Define field pageid to be added to mootimetertool_ranking_votes.
        $table = new xmldb_table('mootimetertool_ranking_votes');
        $field = new xmldb_field('pageid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'usermodified');

        // Conditionally launch add field pageid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ranking savepoint reached.
        upgrade_plugin_savepoint(true, 2024030607, 'mootimetertool', 'ranking');
    }

    if ($oldversion < 2024030609) {

        // Define field timemodified to be added to mootimetertool_ranking_votes.
        $table = new xmldb_table('mootimetertool_ranking_votes');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timecreated');

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ranking savepoint reached.
        upgrade_plugin_savepoint(true, 2024030609, 'mootimetertool', 'ranking');
    }
}
