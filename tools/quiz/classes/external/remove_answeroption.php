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
 * Web service to remove an answer option.
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
 * Web service to remove an answer option.
 *
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class remove_answeroption extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain results for.', VALUE_REQUIRED),
            'aoid' => new external_value(PARAM_INT, 'The id of the answer option.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     * @param int $pageid
     * @param int $aoid "answer option id"
     * @return array
     * @throws invalid_parameter_exception
     * @throws dml_exception
     */
    public static function execute(int $pageid, int $aoid): array {

        [
            'pageid' => $pageid,
            'aoid' => $aoid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
            'aoid' => $aoid,
        ]);

        $quiz = new \mootimetertool_quiz\quiz();
        return $quiz->remove_answer_option($pageid, $aoid);

    }

    /**
     * Describes the return structure of the service..
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
            [
                'code' => new external_value(PARAM_INT, 'Return code of removal process.'),
                'string' => new external_value(PARAM_TEXT, 'Return string of removal process.'),
            ],
            'Status of removal of an answer option'
        );
    }
}
