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
 * Web service to delete_all_answers.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\external;

use dml_exception;
use dml_transaction_exception;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use Throwable;
use tool_brickfield\manager;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/externallib.php');

/**
 * Web service to delete_all_answers.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_all_answers extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The new pageid.', VALUE_REQUIRED),
            'thisDataset' => new external_value(PARAM_RAW, 'The dataset of the button.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @param mixed $thisdataset
     * @return array
     * @throws dml_transaction_exception
     * @throws Throwable
     */
    public static function execute(int $pageid, $thisdataset): array {
        global $DB;
        [
            'pageid' => $pageid,
            'thisDataset' => $thisdataset
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
            'thisDataset' => $thisdataset,
        ]);

        try {

            $transaction = $DB->start_delegated_transaction();
            $mtmhelper = new \mod_mootimeter\helper();

            $page = $mtmhelper->get_page($pageid);
            $answertable = $mtmhelper->get_tool_answer_table($page);
            $success = $mtmhelper->delete_all_answers($answertable, $pageid);

            if (!$success) {
                $transaction->dispose();
                return ['code' => 403, 'string' => 'Forbidden'];
            }

            $transaction->allow_commit();

            $return = ['code' => 200, 'string' => 'ok'];
        } catch (\Exception $e) {

            $transaction->rollback($e);
            $return = ['code' => 500, 'string' => $e->getMessage()];
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
                'code' => new external_value(PARAM_INT, 'Return code of storage process.'),
                'string' => new external_value(PARAM_TEXT, 'Return string of storage process.'),
            ],
            'Delete page status.'
        );
    }
}