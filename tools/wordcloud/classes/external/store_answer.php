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
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_wordcloud\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_mootimeter\helper;

/**
 * Web service to store a answer.
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class store_answer extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain results for.', VALUE_REQUIRED),
            'answer' => new external_value(PARAM_RAW, 'The answer the user entered.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @param string $answer
     * @return array
     */
    public static function execute(int $pageid, string $answer): array {
        global $USER;

        [
            'pageid' => $pageid,
            'answer' => $answer,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
            'answer' => $answer,
        ]);

        if (empty($answer) && strlen($answer) == 0) {
            return [
                'code' => helper::ERRORCODE_EMPTY_ANSWER,
                'string' => get_string('error_empty_answers', 'mootimetertool_wordcloud'),
            ];
        }

        $helper = new helper();
        $page = $helper->get_page($pageid);

        $maxnumberofanswers = helper::get_tool_config($page->id, "maxinputsperuser");
        $wordcloud = new \mootimetertool_wordcloud\wordcloud();
        $submittedanswers = $wordcloud->get_user_answers('mootimetertool_wordcloud_answers', $page->id, 'answer', $USER->id);

        if (count($submittedanswers) >= $maxnumberofanswers && $maxnumberofanswers > 0) {
            return [
                'code' => helper::ERRORCODE_TO_MANY_ANSWERS,
                'string' => get_string('error_to_many_answers', 'mootimetertool_wordcloud'),
            ];
        }

        $submittedanswers = $wordcloud->get_answer_list_array($page->id, $USER->id);
        // Use strtolower in order to ignore case sensitive inputs of users. This makes the result more precise.
        if (!helper::get_tool_config($page->id, "allowduplicateanswers") && in_array(strtolower($answer), $submittedanswers)) {
            return [
                'code' => helper::ERRORCODE_DUPLICATE_ANSWER,
                'string' => get_string('error_no_duplicate_answers', 'mootimetertool_wordcloud'),
            ];
        }

        $wordcloud->insert_answer($page, $answer);
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
            'Status of answer storing process'
        );
    }
}
