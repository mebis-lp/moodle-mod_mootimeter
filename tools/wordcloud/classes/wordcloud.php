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
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_wordcloud;

use coding_exception;
use dml_exception;
use moodle_exception;
use moodle_url;

/**
 * Pluginlib
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wordcloud extends \mod_mootimeter\toolhelper {

    /**
     * @var string Answer cloum
     */
    const ANSWER_COLUMN = "answer";
    /**
     * @var string Answer table
     */
    const ANSWER_TABLE = "mtmt_wordcloud_answers";

    /** Show Results live */
    const MTMT_VIEW_RESULT_LIVE = 1;
    /** Show Results after teacher permission */
    const MTMT_VIEW_RESULT_TEACHERPERMISSION = 2;

    /**
     * Get the tools answer column.
     * @return string
     */
    public function get_answer_column() {
        return self::ANSWER_COLUMN;
    }

    /**
     * Get the tools answer table.
     * @return string
     */
    public function get_answer_table() {
        return self::ANSWER_TABLE;
    }

    /**
     * Will be executed after the page is created.
     * @param object $page
     * @return void
     */
    public function hook_after_new_page_created(object $page): void {
        return;
    }

    /**
     * Handels inplace_edit.
     *
     * @param string $itemtype
     * @param string $itemid
     * @param mixed $newvalue
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     */
    public function handle_inplace_edit(string $itemtype, string $itemid, mixed $newvalue) {
        if ($itemtype === 'editanswer') {
            return \mootimetertool_wordcloud\local\inplace_edit_answer::update($itemid, $newvalue);
        }
    }

    /**
     * Get the answer overview.
     *
     * @param object $page
     * @return string
     */
    public function get_answer_overview(object $page): string {
        global $OUTPUT, $PAGE;

        $answers = $this->get_answers($this::ANSWER_TABLE, $page->id, $this::ANSWER_COLUMN);
        $params = [];
        $i = 1;

        $table = new \html_table();
        $table->head = [
            '#',
            get_string('name'),
            get_string('answer'),
            get_string('date') . " " . get_string('time'),
            get_string('options'),
        ];

        foreach ($answers as $answer) {

            $user = $this->get_user_by_id($answer->usermodified);

            $userfullname = "";
            if (!empty($user)) {
                $userfullname = $user->firstname . " " . $user->lastname;
            }

            $tmpl = new \mootimetertool_wordcloud\local\inplace_edit_answer($page, $answer);
            $answerstr = $OUTPUT->render_from_template('core/inplace_editable', $tmpl->export_for_template($OUTPUT));

            // Add delte button to answer.
            $dataseticonrestart = [
                'data-ajaxmethode = "mod_mootimeter_delete_single_answer"',
                'data-pageid="' . $page->id . '"',
                'data-answerid="' . $answer->id . '"',
                'data-confirmationtitlestr="' . get_string('delete_single_answer_dialog_title', 'mod_mootimeter') . '"',
                'data-confirmationquestionstr="' . get_string('delete_single_answer_dialog_question', 'mod_mootimeter') . '"',
                'data-confirmationtype="DELETE_CANCEL"',
            ];

            $buttonid = 'mtmt_delete_answer_' . $answer->id;

            $paramstemp = [
                'icon' => 'fa-trash',
                'id' => $buttonid,
                'iconid' => 'mtmt_delte_iconid_' . $answer->id,
                'dataset' => join(" ", $dataseticonrestart),
            ];

            $options = $OUTPUT->render_from_template("mod_mootimeter/elements/snippet_button_icon_only_rounded", $paramstemp);
            $PAGE->requires->js_call_amd('mod_mootimeter/handle_button_clicked', 'init', [$buttonid]);

            $table->data[] = [
                $i,
                $userfullname,
                $answerstr,
                userdate($answer->timecreated, get_string('strftimedatetimeshortaccurate', 'core_langconfig')),
                $options,
            ];

            // Count up ansers.
            $i++;
        }

        $params['answers'] = \html_writer::table($table);

        return $OUTPUT->render_from_template("mod_mootimeter/answers_overview", $params);
    }

    /**
     * Page type specivic insert_answer
     *
     * @param object $page
     * @param string $answer
     * @return void
     */
    public function insert_answer(object $page, $answer): void {

        $record = new \stdClass();
        $record->pageid = $page->id;
        $record->answer = $answer;
        $record->timecreated = time();

        $this->store_answer(self::ANSWER_TABLE, $record);
    }

    /**
     * Get all grouped and counted answers of a page.
     *
     * @param int $pageid
     * @param int $userid
     * @return array
     * @throws dml_exception
     */
    protected function get_answers_list(int $pageid, int $userid = 0) {

        // We only want to deliver results if showresults is true or the teacher allowed to view it.
        if (!empty(self::get_tool_config($pageid, 'showonteacherpermission'))) {
            $params = [
                'pageid' => $pageid,
            ];

            if (!empty($userid)) {
                $params['usermodified'] = $userid;
            }

            return (array)$this->get_answers_grouped(self::ANSWER_TABLE, $params);
        }

        return [];
    }

    /**
     * Convert a grouped answer list to an array, that is readable by wordcloud2.js.
     *
     * @param array $answerslist
     * @return array
     */
    protected function convert_answer_list_to_wordcloud_list(array $answerslist) {
        $templist = [];
        foreach ($answerslist as $key => $value) {
            $templist[] = array_values((array)$value);
        }

        return $templist;
    }

    /**
     * Get the answer list in wordcloud2 format.
     * [{"answer":"Answer word 1","cnt":"3"},{"answer":"Answer word 2","cnt":"1"}, ...]
     *
     * @param int $pageid
     * @param int $userid
     * @return array
     * @throws dml_exception
     */
    public function get_answerlist_wordcloud(int $pageid, int $userid = 0): array {

        $answerlist = $this->get_answers_list($pageid, $userid);
        return $this->convert_answer_list_to_wordcloud_list($answerlist);
    }

    /**
     * Get a pure list of unique answers, without id etc.
     *
     * @param int $pageid
     * @param int $userid
     * @return array
     * @throws dml_exception
     */
    public function get_answer_list_array(int $pageid, int $userid = 0): array {
        global $DB;

        $params = [
            'pageid' => $pageid,
        ];

        if (!empty($userid)) {
            $params['usermodified'] = $userid;
        }

        return array_keys($DB->get_records_menu(self::ANSWER_TABLE, $params, "", "answer"));
    }

    /**
     * Get all parameters that are necessary for rendering the tools view.
     *
     * @param object $page
     * @return array
     */
    public function get_renderer_params(object $page) {
        global $USER;

        // Parameter for initial wordcloud rendering.
        $params['answerslist'] = json_encode([]);

        $params['pageid'] = $page->id;

        // Parameter for initializing Badges.
        $params["toolname"] = ['pill' => get_string("pluginname", "mootimetertool_" . $page->tool)];

        $params["answers"] = array_values(array_map(function ($element) {
            return [
                'pill' => $element->answer,
                'additional_class' => 'mootimeter-pill-inline',
            ];
        }, $this->get_user_answers(self::ANSWER_TABLE, $page->id, self::ANSWER_COLUMN, $USER->id)));

        $params['input_answer'] = [
            'mtm-input-id' => 'mootimeter_type_answer',
            'mtm-input-name' => 'answer',
            'dataset' => 'data-pageid="' . $page->id . '"',
        ];

        $params['button_answer'] = [
            'mtm-button-id' => 'mootimeter_enter_answer',
            'mtm-button-text' => 'Senden',
        ];

        return $params;
    }

    /**
     * Get the params for settings column.
     *
     * @param object $page
     * @param array $params
     * @return array
     * @throws dml_exception
     * @throws coding_exception
     */
    public function get_col_settings_tool_params(object $page, array $params = []) {
        global $USER;

        $instance = self::get_instance_by_pageid($page->id);
        $cm = self::get_cm_by_instance($instance);
        if (!has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id)) || empty($USER->editing)) {
            return [];
        }

        $params['template'] = 'mootimetertool_wordcloud/view_settings';

        $params['question'] = [
            'mtm-input-id' => 'mtm_input_question',
            'mtm-input-value' => s(self::get_tool_config($page, 'question')),
            'mtm-input-placeholder' => get_string('enter_question', 'mod_mootimeter'),
            'mtm-input-name' => "question",
            'additional_class' => 'mootimeter_settings_selector',
            'pageid' => $page->id,
            'ajaxmethode' => "mod_mootimeter_store_setting",
        ];

        $params['maxinputsperuser'] = [
            'title' => get_string('answers_max_number', 'mootimetertool_wordcloud'),
            'additional_class' => 'mootimeter_settings_selector',
            'id' => "maxinputsperuser",
            'name' => "maxinputsperuser",
            'min' => 0,
            'pageid' => $page->id,
            'ajaxmethode' => "mod_mootimeter_store_setting",
            'value' => (empty(self::get_tool_config($page->id, "maxinputsperuser"))) ? 0 : self::get_tool_config(
                $page->id,
                "maxinputsperuser"
            ),
        ];

        $params['settings']['allowduplicateanswers'] = [
            'cb_with_label_id' => 'allowduplicateanswers',
            'pageid' => $page->id,
            'cb_with_label_text' => get_string('allowduplicateanswers', 'mootimetertool_wordcloud'),
            'cb_with_label_name' => 'allowduplicateanswers',
            'cb_with_label_additional_class' => 'mootimeter_settings_selector',
            'cb_with_label_ajaxmethod' => "mod_mootimeter_store_setting",
            'cb_with_label_checked' => (\mod_mootimeter\helper::get_tool_config($page, 'allowduplicateanswers') ? "checked" : ""),
        ];
        $returnparams['colsettings'] = $params;
        return $returnparams;
    }

    /**
     * Get the lastupdated timestamp.
     *
     * @param int $pageid
     * @return int
     */
    public function get_last_update_time(int $pageid): int {
        global $DB;

        // We only want to deliver results if showresults is true or the teacher allowed to view it.
        if (
            $this->get_tool_config($pageid, 'showresult') == self::MTMT_VIEW_RESULT_TEACHERPERMISSION
            && empty($this->get_tool_config($pageid, 'showonteacherpermission'))
        ) {
            return 0;
        }

        $records = $DB->get_records(self::ANSWER_TABLE, ['pageid' => $pageid], 'timecreated DESC', 'timecreated', 0, 1);

        if (empty($records)) {
            return 0;
        }
        $record = array_shift($records);
        return $record->timecreated;
    }

    /**
     * Delete all DB entries related to a specific page.
     * @param object $page
     * @return bool
     */
    public function delete_page_tool(object $page) {
        global $DB;
        try {
            // Table not written yet.
            $DB->delete_records(self::ANSWER_TABLE, ['pageid' => $page->id]);
        } catch (\Exception $e) {
            // Todo handling.
            echo 'Something went wrong';
            return false;
        }
        return true;
    }

    /**
     * Get content menu bar params.
     *
     * @param object $page
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_content_menu_tool_params(object $page) {

        $instance = \mod_mootimeter\helper::get_instance_by_pageid($page->id);
        $cm = \mod_mootimeter\helper::get_cm_by_instance($instance);

        $params = $this->get_content_menu_default_parameters($page);

        if (has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id))) {

            $params['icon-eye'] = [
                'icon' => 'fa-eye',
                'id' => 'toggleteacherpermission',
                'iconid' => 'toggleteacherpermissionid',
                'dataset' => 'data-pageid="' . $page->id . '" data-iconid="toggleteacherpermissionid"',
            ];
            if (!empty(self::get_tool_config($page->id, 'showonteacherpermission'))) {
                $params['icon-eye']['tooltip'] = get_string('tooltip_content_menu_teacherpermission_disabled', 'mod_mootimeter');
            } else if (empty(self::get_tool_config($page->id, 'showonteacherpermission'))) {
                $params['icon-eye']['icon'] = "fa-eye-slash";
                $params['icon-eye']['tooltip'] = get_string('tooltip_content_menu_teacherpermission', 'mod_mootimeter');
            }

            // Reset Question.
            $dataseticonrestart = [
                'data-ajaxmethode = "mod_mootimeter_delete_all_answers"',
                'data-pageid="' . $page->id . '"',
                'data-confirmationtitlestr="' . get_string('delete_all_answers_dialog_title', 'mod_mootimeter') . '"',
                'data-confirmationquestionstr="' . get_string('delete_all_answers_dialog_question', 'mod_mootimeter') . '"',
                'data-confirmationtype="DELETE_CANCEL"',
            ];
            $params['icon-restart'] = [
                'icon' => 'fa-trash',
                'id' => 'mtmt_restart',
                'iconid' => 'mtmt_restart_iconid',
                'dataset' => join(" ", $dataseticonrestart),
            ];
        }

        $params['icon-showresults'] = [
            'icon' => 'fa-bar-chart',
            'id' => 'showresults',
            'additional_class' => 'mtm_redirect_selector',
            'href' => (new \moodle_url('/mod/mootimeter/view.php', ['id' => $cm->id, 'pageid' => $page->id, 'r' => 1]))->out(true),
            'tooltip' => get_string('tooltip_show_results_page', 'mod_mootimeter'),
        ];
        if (optional_param('r', "", PARAM_INT)) {
            $params['icon-showresults']['icon'] = 'fa-pencil-square-o';
            $params['icon-showresults']['href'] = (new \moodle_url(
                '/mod/mootimeter/view.php',
                ['id' => $cm->id, 'pageid' => $page->id]
            ))->out(true);
            $params['icon-showresults']['tooltip'] = get_string('tooltip_show_question_page', 'mod_mootimeter');
        }
        return ['contentmenu' => $params];
    }

    /**
     * Renders the result page of the wordcloud.
     *
     * @param object $page
     * @return string
     * @throws dml_exception
     * @throws coding_exception
     */
    public function get_result_page(object $page): string {
        global $OUTPUT;

        $params = $this->get_renderer_params($page);
        return $OUTPUT->render_from_template("mootimetertool_wordcloud/view_results", $params);
    }
}
