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
 * Web service to approve a phrase.
 *
 * @package     mootimetertool_ranking
 * @copyright   2024, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_ranking\external;

use coding_exception;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use invalid_parameter_exception;
use dml_exception;
use mod_mootimeter\helper;

/**
 * Web service to approve a phrase.
 *
 * @package     mootimetertool_ranking
 * @copyright   2024, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class approve_phrase extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain results for.', VALUE_REQUIRED),
            'phraseid' => new external_value(PARAM_INT, 'The phraseid of the phrase to be approved.', VALUE_REQUIRED),
            'value' => new external_value(PARAM_INT, 'The new state value.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @param int $phraseid
     * @param int $value
     * @return array
     */
    public static function execute(int $pageid, int $phraseid, int $value): array {
        global $USER;

        [
            'pageid' => $pageid,
            'phraseid' => $phraseid,
            'value' => $value,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
            'phraseid' => $phraseid,
            'value' => $value,
        ]);

        $helper = new helper();
        $page = $helper->get_page($pageid);

        $ranking = new \mootimetertool_ranking\ranking();

        $success = $ranking->set_phrase_approval_state($phraseid, $value);

        if ($success) {
            return ['code' => helper::ERRORCODE_OK, 'string' => 'ok'];
        }
        return ['code' => helper::ERRORCODE_TO_MANY_ANSWERS, 'string' => 'ok'];
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
            'Status of phrase state storing process'
        );
    }
}
