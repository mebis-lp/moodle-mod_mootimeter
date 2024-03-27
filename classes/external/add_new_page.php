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
 * Web service to create a new page.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use dml_exception;
use invalid_parameter_exception;

/**
 * Web service to create a new page.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_new_page extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'tool' => new external_value(PARAM_TEXT, 'The new page tool.', VALUE_REQUIRED),
            'instance' => new external_value(PARAM_INT, 'The mootimeter instance.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param string $tool
     * @param int $instance
     * @return array
     * @throws invalid_parameter_exception
     * @throws dml_exception
     */
    public static function execute(string $tool, int $instance): array {
        global $USER;

        [
            'tool' => $tool,
            'instance' => $instance,
        ] = self::validate_parameters(self::execute_parameters(), [
            'tool' => $tool,
            'instance' => $instance,
        ]);

        $mtmhelper = new \mod_mootimeter\helper();
        $record = new \stdClass();
        $record->tool = $tool;
        $record->instance = $instance;
        $record->title = "";

        $pageid = $mtmhelper->store_page($record);
        $cm = \mod_mootimeter\helper::get_cm_by_instance($instance);

        $return = [
            'pageid' => $pageid,
            'cmid' => $cm->id,
        ];

        // If the user is not in editing mode. Switch to editing mode.
        // This is the case if the user enters an blank mootimeter instance with disabled editing mode.
        $USER->editing = true;

        return $return;
    }

    /**
     * Describes the return structure of the service..
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
            [
                'pageid' => new external_value(PARAM_INT, 'Pageid of the page created.'),
                'cmid' => new external_value(PARAM_INT, 'Pageid of the page created.'),
            ],
            'New page info.'
        );
    }
}
