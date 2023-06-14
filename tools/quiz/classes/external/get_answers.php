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
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_wordcloud\external;

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use tool_brickfield\manager;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/externallib.php');

/**
 * Web service to get all answers.
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_answers extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain results for.', VALUE_REQUIRED),
            'lastupdated' => new external_value(PARAM_INT, 'The timestamp the last answer were added at.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @param int $lastupdated
     * @return array
     */
    public static function execute(int $pageid, int $lastupdated): array {
        ['pageid' => $pageid, 'lastupdated' => $lastupdated] = self::validate_parameters(
            self::execute_parameters(),
            ['pageid' => $pageid, 'lastupdated' => $lastupdated]
        );

        $quiz = new \mootimetertool_quiz\quiz();
        $lastupdatednew = $quiz->get_last_update_time($pageid, "quiz");

        $answerlist = $quiz->get_counted_answers($pageid);
        return ['answerlist' => $answerlist, 'lastupdated' => $lastupdatednew];
    }

    /**
     * Describes the return structure of the service..
     *
     * @return external_multiple_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
            [
                'answerlist' =>  new external_multiple_structure(
                    new external_multiple_structure(
                        new external_value(PARAM_TEXT, 'Answertext'),
                        new external_value(PARAM_INT, 'Fontsize of the answer'),
                        "Answer"
                    ),
                    'The answerslist.',
                ),
                'lastupdated' => new external_value(PARAM_INT, 'Timestamp of last updated')

            ],
            'Information to redraw wordcloud'
        );
    }
}
