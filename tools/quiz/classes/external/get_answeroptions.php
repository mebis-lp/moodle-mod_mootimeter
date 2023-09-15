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
 * Web service to get all answers.
 *
 * @package     mootimetertool_quiz
 * @copyright   2023 Justus Dieckmann WWU
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_quiz\external;

use external_api;
use external_description;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use mod_mootimeter\page_manager;
use tool_brickfield\manager;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/externallib.php');

/**
 * Web service to get all answers.
 *
 * @package     mootimetertool_quiz
 * @copyright   2023 Justus Dieckmann WWU
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_answeroptions extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain answer options for', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @return array
     */
    public static function execute(int $pageid): array {
        global $DB;
        ['pageid' => $pageid] = self::validate_parameters(
            self::execute_parameters(),
            ['pageid' => $pageid]
        );

        $context = page_manager::get_context_for_page($pageid);
        require_capability('mod/mootimeter:view', $context);

        return $DB->get_records('mtmt_quiz_options', ['pageid' => $pageid], 'id', 'id, optiontext, optioniscorrect');
    }

    /**
     * Describes the return structure.
     *
     * @return external_description
     */
    public static function execute_returns(): external_description {
        return new external_multiple_structure(
                new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'id of the answer'),
                        'optiontext' => new external_value(PARAM_TEXT, 'text of the answer'),
                        'optioniscorrect' => new external_value(PARAM_BOOL, 'is answer correct')
                ]), 'a answer');
    }
}
