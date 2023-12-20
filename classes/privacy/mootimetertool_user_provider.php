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
 * This file contains the mootimetertool_user_provider interface.
 *
 * Mootimeter Sub plugins should implement this if they store personal information and can retrieve a userid.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mootimeter\privacy;

defined('MOODLE_INTERNAL') || die();

interface mootimetertool_user_provider extends
        \core_privacy\local\request\plugin\subplugin_provider,
        \core_privacy\local\request\shared_userlist_provider
    {

    /**
     * If you have tables that contain userids please fill in this method.
     *
     * @param  \core_privacy\local\request\userlist $userlist The userlist object
     */
    public static function get_userids_from_context(\core_privacy\local\request\userlist $userlist);

    /**
     * Deletes all answers from userids provided in a context.
     * mootimeter_plugin_request_data contains:
     * - context
     * - mootimeter object
     * - user ids
     * @param  mootimeter_plugin_request_data $deletedata A class that contains the relevant information required for deletion.
     */
    // public static function delete_feedback_for_grades(mootimeter_plugin_request_data $deletedata);

}
