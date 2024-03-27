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
 * Web service to reload_pagelist.
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

/**
 * Web service to reload_pagelist.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reload_pagelist extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_RAW, 'pageid to be active', VALUE_REQUIRED),
            'cmid' => new external_value(PARAM_INT, 'The coursemodule id.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @param int $cmid
     * @return array
     */
    public static function execute(int $pageid, int $cmid): array {

        [
            'pageid' => $pageid,
            'cmid' => $cmid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
            'cmid' => $cmid,
        ]);

        try {

            $pageslisthelper = new \mod_mootimeter\local\pagelist();
            $pageslistparams = json_encode($pageslisthelper->get_pagelist_params($cmid, $pageid));

            $return = ['code' => 200, 'string' => 'ok', 'pagelist' => $pageslistparams];
        } catch (\Exception $e) {

            $return = ['code' => 500, 'string' => $e->getMessage(), 'pagelist' => ''];
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
                'code' => new external_value(PARAM_INT, 'Return code of pagelist.'),
                'string' => new external_value(PARAM_TEXT, 'Return string of pagelist.'),
                'pagelist' => new external_value(PARAM_RAW, 'Returned pagelist.'),
            ],
            'Pagelist to be shown.'
        );
    }
}
