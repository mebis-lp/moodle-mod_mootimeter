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
 * Web service to store visualizationtype.
 *
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_quiz\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use dml_exception;
use invalid_parameter_exception;

/**
 * Web service to store visualizationtype.
 *
 * @package     mootimetertool_quiz
 */
class store_visualizationtype extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain results for.', VALUE_REQUIRED),
            'visuid' => new external_value(PARAM_INT, 'The visualizationid to store.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     * @param int $pageid
     * @param int $visuid
     * @return array
     * @throws invalid_parameter_exception
     * @throws dml_exception
     */
    public static function execute(int $pageid, int $visuid): array {

        [
            'pageid' => $pageid,
            'visuid' => $visuid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
            'visuid' => $visuid,
        ]);

        $instance = \mod_mootimeter\helper::get_instance_by_pageid($pageid);
        $cm = \mod_mootimeter\helper::get_cm_by_instance($instance);
        if (!has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id))) {
            return ['code' => 403, 'string' => 'Forbidden'];
        }

        try {

            $helper = new \mod_mootimeter\helper();

            $helper->set_tool_config($pageid, 'visualizationtype', $visuid);

            $return = ['code' => 200, 'string' => 'ok'];

        } catch (\Exception $e) {

            $return = ['code' => 500, 'string' => $e->getMessage()];

        }
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
                'code' => new external_value(PARAM_INT, 'Return code of storage process.'),
                'string' => new external_value(PARAM_TEXT, 'Return string of storage process.'),
            ],
            'Response of storing visualization type'
        );
    }
}
