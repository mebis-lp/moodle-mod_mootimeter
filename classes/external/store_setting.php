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
 * Web service to store a setting.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\external;

use dml_exception;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use tool_brickfield\manager;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/externallib.php');

/**
 * Web service to store setting.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class store_setting extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain results for.', VALUE_REQUIRED),
            'inputname' => new external_value(PARAM_RAW, 'The name of the input to store.', VALUE_REQUIRED),
            'inputvalue' => new external_value(PARAM_RAW, 'The value of the input to store.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @param string $inputname
     * @param string $inputvalue
     * @return void
     * @throws invalid_parameter_exception
     * @throws dml_exception
     */
    public static function execute(int $pageid, string $inputname, string $inputvalue): void {

        [
            'pageid' => $pageid,
            'inputname' => $inputname,
            'inputvalue' => $inputvalue,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
            'inputname' => $inputname,
            'inputvalue' => $inputvalue,
        ]);

        $mtmhelper = new \mod_mootimeter\helper();
        $mtmhelper->set_tool_config($pageid, $inputname, $inputvalue);
        return;
    }

    /**
     * Describes the return structure of the service..
     *
     * @return external_multiple_structure
     */
    public static function execute_returns() {
        // return new external_multiple_structure(
        // new external_single_structure(
        // [
        // 'cmid' => new external_value(PARAM_INT, 'ID'),
        // 'numerrors' => new external_value(PARAM_INT, 'Number of errors.'),
        // 'numchecks' => new external_value(PARAM_INT, 'Number of checks.'),
        // ]
        // )
        // );
    }
}
