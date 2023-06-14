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
 * Pluginlib
 *
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_quiz;

use stdClass;

class quiz extends \mod_mootimeter\toolhelper {

    const MTMT_IS_POLL = 1;
    const MTMT_IS_QUIZ = 2;

    /**
     * Insert the answer.
     *
     * @param object $page
     * @param mixed $answer
     * @return void
     */
    public function insert_answer(object $page, $answer) {
    }

    /**
     * Will be executed after the page is created.
     * @param object $page
     * @return void
     */
    public function hook_after_new_page_created(object $page): void {
        global $USER;

        $record = new stdClass();
        $record->pageid = $page->id;
        $record->usermodified = $USER->id;
        $record->optiontext = "";
        $record->optioniscorrect = 0;
        $record->timecreated = time();

        // Store two answer options as default.
        $this->store_answer_option($record);
        $this->store_answer_option($record);
        return;
    }

    /**
     * Store an answer option.
     *
     * @param object $record
     * @return int
     */
    public function store_answer_option(object $record): int {
        global $DB, $USER;

        if (!empty($record->id)) {
            $origrecord = $DB->get_record('mtmt_quiz_options', ['id' => $record->id]);
            $origrecord->pageid = $record->pageid;
            $origrecord->usermodified = $USER->id;
            $origrecord->optiontext = $record->optiontext;
            $origrecord->optioniscorrect = $record->optioniscorrect;
            $origrecord->timemodified = time();

            $DB->update_record('mtmt_quiz_options', $origrecord);
            return $origrecord->id;
        }

        return $DB->insert_record('mtmt_quiz_options', $record, true);

    }

    /**
     * Get quiztype.
     *
     * @param int $pageid
     * @return string
     */
    public function get_quiztype(int $pageid): string {
        if (!empty($this->get_tool_config($pageid, 'ispoll'))) {
            switch ($this->get_tool_config($pageid, 'ispoll')) {
                case self::MTMT_IS_QUIZ:
                    $ispoll = "isquiz";
                    break;
                default:
                    $ispoll = "ispoll";
                    break;
            }
        } else {
            $ispoll = "ispoll";
        }

        return $ispoll;
    }

    /**
     * Get all parameters that are necessary for rendering the tools view.
     *
     * @param object $page
     * @return array
     */
    public function get_renderer_params(object $page) {

        $ispoll = $this->get_quiztype($page->id);
        $answeroptions = $this->get_answer_options($page->id);

        foreach ($answeroptions as $ao) {
            $params['answer_options'][] = [
                'aoid' => $ao->id,
                'ao_text' => $ao->optiontext,
                'ao_iscorrect' => $ao->optioniscorrect,
                $ispoll => true,
                'pageid' => $page->id,
            ];
        }
        $params['question_text'] = $page->question;
        return $params;
    }

    /**
     * Get all answer options of a page.
     *
     * @param int $pageid
     * @return array
     */
    public function get_answer_options(int $pageid): array {
        global $DB;
        return $DB->get_records('mtmt_quiz_options', ['pageid' => $pageid]);
    }

    /**
     * Get the settings definitions.
     *
     * @param object $page
     * @return array
     */
    public function get_tool_setting_definitions(object $page): array {
        $settings = [];

        $config = $this->get_tool_config($page);

        $settings['settingsarray'][] = [
            "select" => true,
            "id" => 'ispoll',
            "name" => 'ispoll',
            "label" => get_string('ispoll_label', 'mootimetertool_quiz'),
            "helptitle" => get_string('ispoll_helptitle', 'mootimetertool_quiz'),
            "help" => get_string('ispoll_help', 'mootimetertool_quiz'),
            "options" => [
                [
                    'title' => get_string('poll', 'mootimetertool_quiz'),
                    'value' => self::MTMT_IS_POLL,
                    'selected' => $this->is_option_selected(self::MTMT_IS_POLL, $config, 'showresult'),
                ],
                [
                    'title' => get_string('quiz', 'mootimetertool_quiz'),
                    'value' => self::MTMT_IS_QUIZ,
                    'selected' => $this->is_option_selected(self::MTMT_IS_QUIZ, $config, 'showresult'),
                ],
            ]
        ];
        return $settings;
    }

    public function get_result_page($page){

        global $OUTPUT, $DB;
        $chart = new \core\chart_bar();
        $records = $DB->get_records('mtmt_quiz_options', ["pageid"=>$page->id]);
        $labels = [];
        foreach($records as $record){
            $labels[] = $record->optiontext;
        }
        $chart->set_labels($labels);
        $values = array_map(function($obj){ return $obj->cnt;},(array)$this->get_answers_grouped("mtmt_quiz_answers", ["pageid"=>$page->id], 'optionid'));
        $series = new \core\chart_series($page->question,array_values(array_map("floatval", $values)));
        $chart->add_series($series);
        if(empty($labels) || empty($values)){
            $paramschart = ['charts' => get_string("nodata", "mootimetertool_quiz")];
        } else {
            $paramschart = ['charts' => $OUTPUT->render($chart)];
        }

        return $OUTPUT->render_from_template("mootimetertool_quiz/view_results", $paramschart);
    }

    /**
     * Delete all DB entries related to a specific page.
     * @param object $page
     * @return bool
     */
    public function delete_page(object $page) {
        global $DB;
        try {
            $DB->delete_records('mtmt_quiz_options', array('pageid' => $page->id));
            $DB->delete_records('mtmt_quiz_answers', array('pageid' => $page->id));
            $DB->delete_records('mootimeter_pages', array('id' => $page->id));
            $DB->delete_records('mootimeter_tool_settings', array('pageid' => $page->id));
        } catch (\Exception $e) {
            // Todo handling
            echo 'Something went wrong';
            return false;
        }
        return true;
    }
}
