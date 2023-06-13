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
 * Web service to store a answer.
 *
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_quiz\external;

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
 * Web service to store an option.
 *
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class store_answeroption extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain results for.', VALUE_REQUIRED),
            'aoid' => new external_value(PARAM_INT, 'The id of the answer option.', VALUE_REQUIRED),
            'value' => new external_value(PARAM_RAW, 'The text value of the answer option.', VALUE_REQUIRED),
            'id' => new external_value(PARAM_RAW, 'The inputs id.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     * @param int $pageid
     * @param int $aoid
     * @param string $inputvalue
     * @param string $inputid
     * @return void
     * @throws invalid_parameter_exception
     * @throws dml_exception
     */
    public static function execute(int $pageid, int $aoid, string $value, string $id): void {

        [
            'pageid' => $pageid,
            'aoid' => $aoid,
            'value' => $value,
            'id' => $id,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
            'aoid' => $aoid,
            'value' => $value,
            'id' => $id,
        ]);

        // \local_mbs\performance\debugger::print_debug('test', 'hook', [$pageid, $aoid, $value, $id]);

        // $mtmhelper = new \mod_mootimeter\helper();
        // $page = $mtmhelper->get_page($pageid);

        // $quiz = new \mootimetertool_quiz\quiz();
        // $quiz->insert_answer($page, $answer);

        return;
    }

    /**
     * Describes the return structure of the service..
     *
     * @return external_multiple_structure
     */
    public static function execute_returns() {
        // return new external_multiple_structure(
        //     new external_single_structure(
        //         [
        //             'cmid' => new external_value(PARAM_INT, 'ID'),
        //             'numerrors' => new external_value(PARAM_INT, 'Number of errors.'),
        //             'numchecks' => new external_value(PARAM_INT, 'Number of checks.'),
        //         ]
        //     )
        // );
    }
}
