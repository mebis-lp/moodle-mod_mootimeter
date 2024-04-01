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
 * Web service to store a phrase.
 *
 * @package     mootimetertool_ranking
 * @copyright   2024, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_ranking\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_mootimeter\helper;

/**
 * Web service to store a phrase.
 *
 * @package     mootimetertool_ranking
 * @copyright   2024, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class vote_phrase_thumb extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain results for.', VALUE_REQUIRED),
            'thisDataset' => new external_value(PARAM_TEXT, 'The dataset of the clicked object.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @param string $thisdataset
     * @return array
     */
    public static function execute(int $pageid, string $thisdataset): array {
        global $USER;

        [
            'pageid' => $pageid,
            'thisDataset' => $thisdataset,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
            'thisDataset' => $thisdataset,
        ]);

        $dataset = json_decode($thisdataset);
        $ranking = new \mootimetertool_ranking\ranking();

        $phrase = $ranking->get_phrase($dataset->phraseid);
        $ranking->insert_answer($phrase, $dataset->thumbvalue);
        return ['code' => helper::ERRORCODE_OK, 'string' => 'ok', 'reload' => true];
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
                'reload' => new external_value(PARAM_BOOL, 'Indicator to reload page.', VALUE_DEFAULT, false),
            ],
            'Status of phrase storing process'
        );
    }
}
