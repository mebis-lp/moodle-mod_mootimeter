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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use dml_exception;
use invalid_parameter_exception;

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
            'thisDataset' => new external_value(PARAM_RAW, 'The value of the input to store.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @param string $inputname
     * @param string $inputvalue
     * @param string $datasetjson
     * @return array
     * @throws invalid_parameter_exception
     * @throws dml_exception
     */
    public static function execute(int $pageid, string $inputname, string $inputvalue, string $datasetjson = ""): array {

        [
            'pageid' => $pageid,
            'inputname' => $inputname,
            'inputvalue' => $inputvalue,
            'thisDataset' => $datasetjson,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
            'inputname' => $inputname,
            'inputvalue' => $inputvalue,
            'thisDataset' => $datasetjson,
        ]);

        $instance = \mod_mootimeter\helper::get_instance_by_pageid($pageid);
        $cm = \mod_mootimeter\helper::get_cm_by_instance($instance);

        if (!has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id))) {
            return ['code' => 403, 'string' => 'Forbidden'];
        }

        try {

            $dataset = json_decode($datasetjson);

            $helper = new \mod_mootimeter\helper();
            $helper->set_tool_config($pageid, $inputname, $inputvalue);
            $return = ['code' => 200, 'string' => 'ok', 'options' => json_encode(['reload' => !empty($dataset->reload)])];

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
                'options' => new external_value(PARAM_TEXT, 'Additional options for js executions', VALUE_OPTIONAL, ""),
            ],
            'Set Setting State.'
        );
    }
}
