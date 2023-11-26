<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Helper class to handle inplace edit of answeroptions.
 *
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_quiz\local;

use coding_exception;
use dml_exception;

/**
 * Helper class to handle inplace edit of answeroptions.
 *
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inplace_edit_answer extends \core\output\inplace_editable {

    /**
     * Constructor.
     *
     * @param object $page
     * @param object $answer
     * @return void
     * @throws dml_exception
     * @throws coding_exception
     */
    public function __construct(object $page, object $answer) {
        $quiz = new \mootimetertool_quiz\quiz();
        $answeroptions = $quiz->get_answer_options($page->id);

        $answeroptionstemp = [];
        foreach ($answeroptions as $answeroption) {
            $answeroptionstemp[$answeroption->id] = $answeroption->optiontext;
        }

        $instance = $quiz::get_instance_by_pageid($page->id);
        $cm = $quiz::get_cm_by_instance($instance);

        parent::__construct(
            'mootimeter',
            'quiz_editanswerselect',
            $page->id . "_" . $answer->id,
            has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id)),
            $answeroptionstemp[$answer->{$quiz::ANSWER_COLUMN}],
            $answer->{$quiz::ANSWER_COLUMN}
        );
        $this->set_type_select($answeroptionstemp);
    }

    /**
     * Updates the value in database and returns itself, called from inplace_editable callback
     *
     * @param int $itemid
     * @param mixed $newvalue
     * @return \self
     */
    public static function update($itemid, $newvalue) {
        global $DB, $PAGE;

        // Clean the new value.
        $newvalue = clean_param($newvalue, PARAM_INT);

        // Extract pageid and answerid.
        list($pageid, $answerid) = explode("_", $itemid);

        // Generate answeroption array.
        $quiz = new \mootimetertool_quiz\quiz();
        $answeroptions = $quiz->get_answer_options($pageid);
        $answeroptionstemp = [];
        foreach ($answeroptions as $answeroption) {
            $answeroptionstemp[$answeroption->id] = $answeroption->optiontext;
        }

        // Check capabilities.
        $helper = new \mod_mootimeter\helper();
        $instance = $helper::get_instance_by_pageid($pageid);
        $cm = $helper::get_cm_by_instance($instance);
        $modulecontext = \context_module::instance($cm->id);
        $PAGE->set_context($modulecontext);
        require_capability('mod/mootimeter:moderator', \context_module::instance($cm->id));

        $answertable = $helper->get_tool_answer_table($pageid);
        $answercol = $helper->get_tool_answer_column($pageid);

        // Now check if answeroptionid is in the allowed range.
        if (!array_key_exists($newvalue, $answeroptionstemp)) {
            throw new \moodle_exception('invalidparameter', 'debug');
        }

        // Next update the existing value.
        $answerrecord = $DB->get_record($answertable, ['id' => $answerid]);
        $answerrecord->{$answercol} = $newvalue;
        $DB->update_record($answertable, $answerrecord);

        // Now clear the answers cache to make the new answer instantly viewable.
        $helper->clear_caches($pageid);

        // Finally return itself.
        $tmpl = new self($helper->get_page($pageid), $answerrecord);
        return $tmpl;
    }
}
