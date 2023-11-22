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
 * Web service to get_pagecontentparams.
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

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/externallib.php');

/**
 * Web service to get_pagecontentparams.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_pagecontentparams extends external_api {
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

            $helper = new \mod_mootimeter\helper();
            $pageparams = json_encode($helper->get_page_content_params($cmid, $pageid));

            $return = ['code' => 200, 'string' => 'ok', 'pageparams' => $pageparams];
        } catch (\Exception $e) {

            $return = ['code' => 500, 'string' => $e->getMessage() . json_encode($e->getTrace()), 'pageparams' => ''];
        }

        return $return;
    }

    /**
     * Describes the return structure of the service..
     *
     * @return external_multiple_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
            [
                'code' => new external_value(PARAM_INT, 'Return code of pageparams.'),
                'string' => new external_value(PARAM_TEXT, 'Return string of pageparams.'),
                'pageparams' => new external_value(PARAM_RAW, 'Returned pageparams.'),
            ],
            'pageparams to be shown.'
        );
    }
}
