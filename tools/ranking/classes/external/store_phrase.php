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
class store_phrase extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain results for.', VALUE_REQUIRED),
            'phrase' => new external_value(PARAM_RAW, 'The phrase the user entered.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @param string $phrase
     * @return array
     */
    public static function execute(int $pageid, string $phrase): array {
        global $USER;

        [
            'pageid' => $pageid,
            'phrase' => $phrase,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
            'phrase' => $phrase,
        ]);

        if (empty($phrase) && strlen($phrase) == 0) {
            return [
                'code' => helper::ERRORCODE_EMPTY_ANSWER,
                'string' => get_string('error_empty_phrases', 'mootimetertool_ranking'),
            ];
        }

        $helper = new helper();
        $page = $helper->get_page($pageid);

        $maxnumberofphrases = helper::get_tool_config($page->id, "maxinputsperuser");
        $ranking = new \mootimetertool_ranking\ranking();
        $submittedphrases = $ranking->get_user_answers(
            'mootimetertool_ranking_phrases',
            $page->id,
            $ranking::PHRASE_COLUMN,
            $USER->id
        );

        if (count($submittedphrases) >= $maxnumberofphrases && $maxnumberofphrases > 0) {
            return [
                'code' => helper::ERRORCODE_TO_MANY_ANSWERS,
                'string' => get_string('error_to_many_phrases', 'mootimetertool_ranking'),
            ];
        }

        $submittedphrases = $ranking->get_phrase_list_array($page->id, $USER->id);
        // Use strtolower in order to ignore case sensitive inputs of users. This makes the result more precise.
        if (!helper::get_tool_config($page->id, "allowduplicatephrases") && in_array(strtolower($phrase), $submittedphrases)) {
            return [
                'code' => helper::ERRORCODE_DUPLICATE_ANSWER,
                'string' => get_string('error_no_duplicate_phrases', 'mootimetertool_ranking'),
            ];
        }

        $ranking->insert_phrase($page, $phrase);
        return ['code' => helper::ERRORCODE_OK, 'string' => 'ok'];
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
            'Status of phrase storing process'
        );
    }
}
