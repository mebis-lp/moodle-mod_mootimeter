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
 * Privacy class for requesting user data.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\privacy;

defined('MOODLE_INTERNAL') || die();

// require_once($CFG->dirroot . '/mod/assign/locallib.php');

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\transform;
use \core_privacy\local\request\helper;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\manager;

/**
 * Privacy class for requesting user data.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\user_preference_provider,
    \core_privacy\local\request\core_userlist_provider {

    /** Interface for all mootimeter tool sub-plugins. */
    const MOOTIMETERTOOL_INTERFACE = 'mod_mootimeter\privacy\mootimetertool_provider';

    /** Interface for all mootimeter tool sub-plugins. */
    const MOOTIMETERTOOL_USER_INTERFACE = 'mod_mootimeter\privacy\mootimetertool_user_provider';

    /**
     * Provides meta data that is stored about a user with mod_assign
     *
     * @param  collection $collection A collection of meta data items to be added to.
     * @return  collection Returns the collection of metadata.
     */
    public static function get_metadata(collection $collection): collection {

        $collection->add_plugintype_link('mootimetertool', [], 'privacy:metadata:mootimetertoolpluginsummary');

        return $collection;
    }

    /**
     * Returns all of the contexts that has information relating to the userid.
     *
     * @param  int $userid The user ID.
     * @return contextlist an object with the contexts related to a userid.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {

        $contextlist = new contextlist();

        manager::plugintype_class_callback(
            'mootimetertool',
            self::MOOTIMETERTOOL_INTERFACE,
            'get_context_for_userid_within_mootimetertool',
            [$userid, $contextlist]
        );

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        manager::plugintype_class_callback(
            'mootimetertool',
            self::MOOTIMETERTOOL_USER_INTERFACE,
            'get_userids_from_context',
            [$userlist]
        );
    }

    /**
     * Write out the user data filtered by contexts.
     *
     * @param approved_contextlist $contextlist contexts that we are writing data out from.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        foreach ($contextlist->get_contexts() as $context) {
            // Check that the context is a module context.
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            $user = $contextlist->get_user();
            $mootimeterdata = helper::get_context_data($context, $user);
        }
    }

    public static function delete_data_for_all_users_in_context($context) {

    }

    public static function delete_data_for_user(approved_contextlist $contextlist){

    }

    public static function export_user_preferences(int $userid) {

    }

    public static function delete_data_for_users(approved_userlist $userlist) {

    }
}
