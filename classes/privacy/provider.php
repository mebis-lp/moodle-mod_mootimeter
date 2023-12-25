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

        // Link to subplugins.
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
            writer::with_context($context)->export_data([], $mootimeterdata);

            $cm = get_coursemodule_from_id('', $context->instanceid);
            $mtmhelper = new \mod_mootimeter\helper();
            $mootimeterinstance = $mtmhelper::get_mootimeter_instance($cm->instance);
            writer::with_context($context)->export_data([], $mootimeterinstance);

            static::export_mootimetertool_data($mootimeterinstance, $user, $context, []);

        }
    }

    /**
     * Exports answered data for a user.
     *
     * @param  object          $mootimeterinstance           The mootimeterinstance object
     * @param  object          $user                         The user object
     * @param  \context        $context                      The context
     */
    protected static function export_mootimetertool_data(
        object $mootimeterinstance,
        object $user,
        object $context,
        array $path,
    ) {
        $helper = new \mod_mootimeter\helper();
        $toolpages = $helper->get_all_tools_of_instance($mootimeterinstance);
        foreach ($toolpages as $tool => $pages) {

            foreach($pages as $page){
                $subpath = array_merge($path, [get_string('privacy:pagepath', 'mod_mootimeter', $page->id)]);
                $params = new mootimeter_plugin_request_data($context, $page, $user, $subpath);
                manager::plugintype_class_callback(
                    'mootimetertool',
                    self::MOOTIMETERTOOL_INTERFACE,
                    'export_mootimetertool_user_data',
                    [$params]
                );
            }

            // writer::with_context($context)->export_data(['test'], (object)['text' => "TEST"]);

            // self::export_mootimetertool_data($mootimeterinstance, $user, $context, $submissionpath);
            // $grade = $assign->get_user_grade($user->id, false, $submission->attemptnumber);
            // if ($grade) {
            //     $params = new assign_plugin_request_data($context, $assign, $grade, $submissionpath, $teacher);
            //     manager::plugintype_class_callback(
            //         'assignfeedback',
            //         self::ASSIGNFEEDBACK_INTERFACE,
            //         'export_feedback_user_data',
            //         [$params]
            //     );

            //     self::export_grade_data($grade, $context, $submissionpath);
            //     // Check for advanced grading and retrieve that information.
            //     if (isset($controller)) {
            //         \core_grading\privacy\provider::export_item_data($context, $grade->id, $submissionpath);
            //     }
            // }
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
