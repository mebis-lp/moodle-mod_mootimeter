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

    const ANSWER_COLUMN = "optionid";

    const VISUALIZATION_ID_CHART_PILLAR = 1;
    const VISUALIZATION_ID_CHART_BAR = 2;
    const VISUALIZATION_ID_CHART_LINE = 3;
    const VISUALIZATION_ID_CHART_PIE = 4;

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
     * Remove an answer option.
     * @param int $pageid
     * @param int $aoid
     * @return array
     */
    public function remove_answer_option(int $pageid, int $aoid): array {
        global $DB;

        try {

            $transaction = $DB->start_delegated_transaction();

            $DB->delete_records('mtmt_quiz_options', ['pageid' => $pageid, 'id' => $aoid]);
            $DB->delete_records('mtmt_quiz_answers', ['pageid' => $pageid, 'optionid' => $aoid]);

            $transaction->allow_commit();

            $return = ['code' => 200, 'string' => 'ok'];
        } catch (\Exception $e) {

            $transaction->rollback($e);
            $return = ['code' => 500, 'string' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * Get quiztype.
     *
     * @param int $pageid
     * @return string
     * @deprecated
     */
    public function get_quiztype(int $pageid): string {
        if (!empty(self::get_tool_config($pageid, 'ispoll'))) {
            switch (self::get_tool_config($pageid, 'ispoll')) {
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
     * @deprecated
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

        $params['question_text'] = self::get_tool_config($page)->question;
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
     * @deprecated
     */
    public function get_tool_setting_definitions(object $page): array {
        $settings = [];

        $config = self::get_tool_config($page);

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
    public function get_col_settings_tool(object $page) {
        global $OUTPUT, $PAGE;

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
                'mtm-ao-wrapper-id' => 'ao_wrapper_' . $answeroption->id,

                'mtm-input-id' => 'ao_text_' . $answeroption->id,
                'mtm-input-name' => 'ao_text',
                'mtm-input-value' => $answeroption->optiontext,
                'ajaxmethode' => "mootimetertool_quiz_store_answeroption_text",
                'additional_class' => 'mootimeter-answer-options mootimeter_settings_selector',
                'dataset' => 'data-pageid=' . $page->id . ' data-aoid=' . $answeroption->id,

                'mtm-cb-without-label-id' => 'ao_iscorrect_' . $answeroption->id,
                'mtm-cb-without-label-name' => 'ao_iscorrect',
                'mtm-cb-without-label-ajaxmethode' => "mootimetertool_quiz_store_answeroption_is_correct",
                'mtm-cb-without-label-checked' => ($answeroption->optioniscorrect) ? "checked" : "",

                'button_icon_only_transparent_id' => 'ao_delete_' . $answeroption->id,
                'button_icon_only_transparent_dataset' => 'data-pageid=' . $page->id . ' data-aoid=' . $answeroption->id,
                'button_icon_only_transparent_icon' => 'fa-close',
                'button_icon_only_transparent_additionalclass' => 'mtmt-remove-answeroption',
                'button_icon_only_transparent_ajaxmethode' => 'mootimetertool_quiz_remove_anseroption',
            ];
            $PAGE->requires->js_call_amd('mootimetertool_quiz/remove_answer_option','init', ['ao_delete_' . $answeroption->id]);
        }

        $params['addoption'] = [
            'button_icon_only_transparent_icon' => 'fa-plus',
            'button-text' => get_string('add_question_option', 'mootimetertool_quiz'),
            'button_icon_only_transparent_id' => 'add_answer_option',
            'button_icon_only_transparent_dataset' => 'data-pageid="' . $page->id . '"',
        ];

        $visualizationtype = \mod_mootimeter\helper::get_tool_config($page, 'visualizationtype');

        $params['visualization'] = [
            [
                'mtm-button-icon-id' => 'visualization_' . self::VISUALIZATION_ID_CHART_PILLAR,
                'mtm-button-icon-additionalclass' => 'mtmt_visualization_selector',
                'mtm-button-icon-img' => [
                    'path' => "tools/" . $page->tool . "/pix/chart-pillar.svg",
                    'width' => "24px",
                ],
                'mtm-button-icon-active' => ($visualizationtype == self::VISUALIZATION_ID_CHART_PILLAR)? true : false,
                'mtm-button-icon-dataset' => 'data-pageid="' . $page->id . '" data-visuid=' .self::VISUALIZATION_ID_CHART_PILLAR,
            ],
            [
                'mtm-button-icon-id' => 'visualization_' . self::VISUALIZATION_ID_CHART_BAR,
                'mtm-button-icon-additionalclass' => 'mtmt_visualization_selector',
                'mtm-button-icon-img' => [
                    'path' => "tools/" . $page->tool . "/pix/chart-bar.svg",
                    'width' => "24px",
                ],
                'mtm-button-icon-active' => ($visualizationtype == self::VISUALIZATION_ID_CHART_BAR) ? true : false,
                'mtm-button-icon-dataset' => 'data-pageid="' . $page->id . '" data-visuid=' . self::VISUALIZATION_ID_CHART_BAR,
            ],
            [
                'mtm-button-icon-id' => 'visualization_' . self::VISUALIZATION_ID_CHART_LINE,
                'mtm-button-icon-additionalclass' => 'mtmt_visualization_selector',
                'mtm-button-icon-img' => [
                    'path' => "tools/" . $page->tool . "/pix/chart-line.svg",
                    'width' => "24px",
                ],
                'mtm-button-icon-active' => ($visualizationtype == self::VISUALIZATION_ID_CHART_LINE) ? true : false,
                'mtm-button-icon-dataset' => 'data-pageid="' . $page->id . '" data-visuid=' . self::VISUALIZATION_ID_CHART_LINE,
            ],
            [
                'mtm-button-icon-id' => 'visualization_' . self::VISUALIZATION_ID_CHART_PIE,
                'mtm-button-icon-additionalclass' => 'mtmt_visualization_selector',
                'mtm-button-icon-img' => [
                    'path' => "tools/" . $page->tool . "/pix/chart-pie.svg",
                    'width' => "24px",
                ],
                'mtm-button-icon-active' => ($visualizationtype == self::VISUALIZATION_ID_CHART_PIE) ? true : false,
                'mtm-button-icon-dataset' => 'data-pageid="' . $page->id . '" data-visuid=' . self::VISUALIZATION_ID_CHART_PIE,
           ]
        ];
        $PAGE->requires->js_call_amd('mootimetertool_quiz/store_visualization', 'init');

        $multipleanswerschecked = \mod_mootimeter\helper::get_tool_config($page, 'multipleanswers');
        $params['multipleanswers'] = [
            'cb_with_label_id' => 'multipleanswers',
            'cb_with_label_text' => get_string('multiple_answers', 'mootimetertool_quiz'),
            'pageid' => $page->id,
            'cb_with_label_name' => 'multipleanswers',
            'cb_with_label_additional_class' => 'mootimeter_settings_selector',
            'cb_with_label_ajaxmethode' => "mod_mootimeter_store_setting",
            'cb_with_label_checked' => ($multipleanswerschecked) ? "checked" : "",
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
    public function delete_page_tool(object $page) {
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
