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

use dml_exception;
use coding_exception;
use stdClass;

class quiz extends \mod_mootimeter\toolhelper {

    const MTMT_IS_POLL = 1;
    const MTMT_IS_QUIZ = 2;

    const ANSWER_COLUMN = "optionid";

    /**
     * Insert the answer.
     *
     * @param object $page
     * @param mixed $answer
     * @return void
     */
    public function insert_answer($page, $aoid) {
        global $USER;

        $record = new \stdClass();
        $record->pageid = $page->id;
        $record->usermodified = $USER->id;
        if ($USER->id < 5) {
            $record->usermodified = time();
        }
        $record->optionid = $aoid;
        $record->timecreated = time();

        $this->store_answer('mtmt_quiz_answers', $record, true, self::ANSWER_COLUMN);
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

    /**
     * Get the settings column.
     *
     * @param object $page
     * @return mixed
     */
    public function get_col_settings(object $page) {
        global $OUTPUT;

        $params['question'] = [
            'mtm-input-id' => 'mtm_input_question',
            'mtm-input-value' => $page->question,
            'mtm-input-placeholder' => get_string('enter_question', 'mod_mootimeter'),
            'mtm-input-name' => "question",
            'additional_class' => 'mootimeter_settings_selector',
            'pageid' => $page->id,
            'ajaxmethode' => "mod_mootimeter_store_page_details",
        ];

        $answeroptions = $this->get_answer_options($page->id);
        foreach ($answeroptions as $answeroption) {
            $params['answeroptions'][] = [
                'mtm-input-name' => 'question1',
                'additional_class' => 'mootimeter-answer-options',
                'dataset' => 'data-pageid="' . $page->id . '" data-aoid="' . $answeroption->id . '"',
                'mtm-input-value' => $answeroption->optiontext,
                'icon' => 'fa-close',
                // 'mtm-cb-without-label-classes' => ($PAGE->theme->name == 'mebis') ? "ao-checkbox-hide" : "",
            ];
        }

        $params['addoption'] = [
            'icon' => 'fa-plus',
            'button-text' => 'Frage hinzufügen',

        ];

        $params['visualization'] = [
            [
                'img' => [
                    'path' => "tools/" . $page->tool . "/pix/chart-pillar.svg",
                    'width' => "24px",
                ]
            ],
            [
                'img' => [
                    'path' => "tools/" . $page->tool . "/pix/chart-bar.svg",
                    'width' => "24px",
                ],
                'active' => true,
            ],
            [
                'img' => [
                    'path' => "tools/" . $page->tool . "/pix/chart-line.svg",
                    'width' => "24px",
                ]
            ],
            [
                'img' => [
                    'path' => "tools/" . $page->tool . "/pix/chart-pie.svg",
                    'width' => "24px",
                ]
            ]


        ];

        return $OUTPUT->render_from_template("mootimetertool_quiz/view_settings", $params);
    }


    /**
     * Renders the result page of the quiz.
     *
     * @param object $page
     * @return string
     * @throws dml_exception
     * @throws coding_exception
     */
    public function get_result_page(object $page): string {

        global $OUTPUT, $DB;
        $chart = new \core\chart_bar();
        $labelsrecords = $DB->get_records('mtmt_quiz_options', ["pageid" => $page->id]);

        $labels = [];
        foreach ($labelsrecords as $record) {
            $labels[] = $record->optiontext;
        }

        $answersgrouped = $this->get_answers_grouped("mtmt_quiz_answers", ["pageid" => $page->id], 'optionid');
        // var_dump($answersgrouped);
        foreach ($labelsrecords as $key => $label) {
            if (!key_exists($key, $answersgrouped)) {
                $answersgrouped[$key] = ['optionid' => $label->id, 'cnt' => 0];
            }
        }
        // var_dump($answersgrouped);

        // var_dump($labelsrecords);die;

        $chart->set_labels($labels);
        $values = array_map(function ($obj) {
            return (!empty($obj->cnt)) ? $obj->cnt : 0;
        }, (array)$answersgrouped);
        $series = new \core\chart_series($page->question, array_values(array_map("floatval", $values)));
        $chart->add_series($series);

        if (empty($labels) || empty($values)) {
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
