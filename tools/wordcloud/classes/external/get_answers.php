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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_mootimeter\helper;
use mootimetertool_wordcloud\wordcloud;

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
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain results for.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @return array
     */
    public static function execute(int $pageid): array {
        [
                'pageid' => $pageid,
        ] = self::validate_parameters(self::execute_parameters(), [
                'pageid' => $pageid,
        ]);
        $cm = helper::get_cm_by_pageid($pageid);
        $cmcontext = \context_module::instance($cm->id);
        self::validate_context($cmcontext);
        require_capability('mod/mootimeter:view', $cmcontext);

        $wordcloud = new wordcloud();
        $answerlist = $wordcloud->get_answerlist_wordcloud($pageid);

        return ['answerlist' => $answerlist];
    }

    /**
     * Describes the return structure of the service..
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'answerlist' => new external_multiple_structure(
                    new external_multiple_structure(
                        new external_value(PARAM_RAW, 'Answertext')
                    ),
                    'The answerslist.',
                ),
            ],
            'Information to redraw wordcloud'
        );
    }
}
