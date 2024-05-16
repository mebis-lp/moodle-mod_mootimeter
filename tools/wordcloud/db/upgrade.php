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
 * @package     mootimetertool_wordcloud
 * @category    upgrade
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/upgradelib.php');

/**
 * Execute mootimetertool_wordcloud upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_mootimetertool_wordcloud_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // For further information please read {@link https://docs.moodle.org/dev/Upgrade_API}.
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at {@link https://docs.moodle.org/dev/XMLDB_editor}.

    if ($oldversion < 2023040601) {

        mootimetertool_wordcloud_create_tables();

        // Mootimeter savepoint reached.
        upgrade_plugin_savepoint(true, 2023040601, 'mootimetertool', 'wordcloud');
    }

    if ($oldversion < 2024030600) {

        // Define table mtmt_wordcloud_answers to be renamed to mootimetertool_wordcloud_answers.
        $table = new xmldb_table('mtmt_wordcloud_answers');
        // Launch rename table for mootimetertool_wordcloud_answers.
        $dbman->rename_table($table, 'mootimetertool_wordcloud_answers');

        // Wordcloud savepoint reached.
        upgrade_plugin_savepoint(true, 2024030600, 'mootimetertool', 'wordcloud');
    }

    return true;
}
