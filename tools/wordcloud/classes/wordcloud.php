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

class wordcloud extends \mod_mootimeter\toolhelper {

    /** Show Results live */
    const MTMT_VIEW_RESULT_LIVE = 1;
    /** Show Results after teacher permission */
    const MTMT_VIEW_RESULT_TEACHERPERMISSION = 2;

    /**
     * Will be executed after the page is created.
     * @param object $page
     * @return void
     */
    public function hook_after_new_page_created(object $page): void {
        return;
    }

    /**
     * Page type specivic insert_answer
     *
     * @param object $page
     * @param string $answer
     * @return void
     */
    public function insert_answer(object $page, $answer): void {
        global $USER;

        $record = new \stdClass();
        $record->pageid = $page->id;
        $record->usermodified = $USER->id;
        $record->answer = $answer;
        $record->timecreated = time();

        $this->store_answer('mtmt_wordcloud_answers', $record);
    }

    /**
     * Get all answers of an user of a page.
     *
     * @param int $userid
     * @param int $pageid
     * @return mixed
     */
    public function get_user_answers(int $pageid, int $userid) {
        global $DB;
        return $DB->get_records('mtmt_wordcloud_answers', ['usermodified' => $userid, 'pageid' => $pageid]);
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
        if (
            $this->get_tool_config($pageid, 'showresult') == self::MTMT_VIEW_RESULT_LIVE
            || ($this->get_tool_config($pageid, 'showresult') == self::MTMT_VIEW_RESULT_TEACHERPERMISSION
                && !empty($this->get_tool_config($pageid, 'teacherpermission'))
            )
        ) {
            $params = [
                'pageid' => $pageid,
            ];

            if(!empty($userid)){
                $params['usermodified'] = $userid;
            }

            return (array)$this->get_answers_grouped('mtmt_wordcloud_answers', $params);
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
     * @return array
     * @throws dml_exception
     */
    public function get_answerlist_wordcloud(int $pageid, int $userid = 0): array {

        $answerlist = $this->get_answers_list($pageid, $userid);
        return $this->convert_answer_list_to_wordcloud_list($answerlist);
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
        $params['answerslist'] = json_encode($this->get_answerlist_wordcloud($page->id));
        $params['pageid'] = $page->id;

        // Parameter for initializing Badges.
        $params["toolname"] = ['pill' => get_string("pluginname", "mootimetertool_" . $page->tool)];

        $params["answers"] = array_values(array_map(function ($element) {
                return [
                    'pill' => $element->answer,
                    'additional_class' => 'mootimeter-pill-inline'
                ];
            }, $this->get_user_answers($page->id, $USER->id)));

        $params['input_answer'] = [
            'mtm-input-id' => 'mootimeter_type_answer',
            'mtm-input-name' => 'answer',
            'dataset' => 'data-pageid="' . $page->id . '"',
        ];

        $params['button_answer'] = [
            'mtm-button-id' => 'mootimeter_enter_answer',
            'mtm-button-text' => 'Senden',
        ];

        // Parameter for last updated.
        $params['lastupdated'] = $this->get_last_update_time($page->id);

        return $params;
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
            'mtm-input-value'=>$page->question,
            'mtm-input-placeholder' => get_string('enter_question', 'mod_mootimeter'),
            'mtm-input-name' => "question",
            'additional_class' => 'mootimeter_settings_selector',
            'pageid' => $page->id,
            'ajaxmethode' => "mod_mootimeter_store_page_details",
        ];

        $params['teacherpermission'] = [
            'id' => 'teacherpermission',
            'pageid' => $page->id,
            'title' => get_string('showresultteacherpermission', 'mootimetertool_wordcloud'),
            'name' => 'teacherpermission',
            'additional_class' => 'mootimeter_settings_selector',
            'ajaxmethode' => "mod_mootimeter_store_setting",
        ];

        if ($this->get_tool_config($page->id, 'teacherpermission')) {
            $params['teacherpermission']['checked'] = 'checked';
        }

        $params['maxinputsperuser'] =[
            'title' => get_string('answers_max_number', 'mootimetertool_wordcloud'),
            'additional_class' => 'mootimeter_settings_selector',
            'id' => "maxinputsperuser",
            'name' => "maxinputsperuser",
            'pageid' => $page->id,
            'ajaxmethode' => "mod_mootimeter_store_setting",
            'value' => $this->get_tool_config($page->id, "maxinputsperuser"),
        ];

        return $OUTPUT->render_from_template("mootimetertool_wordcloud/view_settings", $params);
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
            && empty($this->get_tool_config($pageid, 'teacherpermission'))
        ) {
            return 0;
        }

        $records = $DB->get_records('mtmt_wordcloud_answers', ['pageid' => $pageid], 'timecreated DESC', 'timecreated', 0, 1);

        if (empty($records)) {
            return 0;
        }
        $record = array_shift($records);
        return $record->timecreated;
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

        $config = $this->get_tool_config($page);

        $settings['settingsarray'][] = [
            "select" => true,
            "id" => 'showresult',
            "name" => 'showresult',
            "label" => get_string('showresult_label', 'mootimetertool_wordcloud'),
            "helptitle" => get_string('showresult_helptitle', 'mootimetertool_wordcloud'),
            "help" => get_string('showresult_help', 'mootimetertool_wordcloud'),
            "options" => [
                [
                    'title' => get_string('showresultlive', 'mootimetertool_wordcloud'),
                    'value' => self::MTMT_VIEW_RESULT_LIVE,
                    'selected' => $this->is_option_selected(self::MTMT_VIEW_RESULT_LIVE, $config, 'showresult'),
                ],
                [
                    'title' => get_string('showresultteacherpermission', 'mootimetertool_wordcloud'),
                    'value' => self::MTMT_VIEW_RESULT_TEACHERPERMISSION,
                    'selected' => $this->is_option_selected(self::MTMT_VIEW_RESULT_TEACHERPERMISSION, $config, 'showresult'),
                ],
            ]
        ];

        // TODO: KEEPING THIS FOR DOCUMANTATION UNTIL A PROPPER DOC EXISTS.

        // $settings['settingsarray'][] = [
        // "checkbox" => true,
        // "id" => 'labelid-2',
        // "name" => 'name2',
        // "label" => "This is the settings label of setting 2",
        // "helptitle" => "This is the settings help title 2",
        // "help" => "Test 2",
        // "value" => 1,
        // "checked" => true
        // ];

        // $settings['settingsarray'][] = [
        // "number" => true,
        // "id" => 'labelid-3',
        // "name" => 'name3',
        // "label" => "This is the settings label of setting 3",
        // "helptitle" => "This is the settings help title 3",
        // "help" => "Test 3",
        // "value" => 122,
        // ];

        // $settings['settingsarray'][] = [
        // "text" => true,
        // "id" => 'labelid-4',
        // "name" => 'name4',
        // "label" => "This is the settings label of setting 4",
        // "helptitle" => "This is the settings help title 4",
        // "help" => "Test 4",
        // "value" => "Testtext",
        // ];
        return $settings;
    }

    /**
     * Toggle the show results teacher permission state.
     *
     * @param object $page
     * @return int
     * @deprecated
     */
    public function toggle_show_results_state(object $page): int {

        $teacherpermission = $this->get_tool_config($page->id, 'teacherpermission');

        $helper = new \mod_mootimeter\helper();

        if (empty($teacherpermission)) {
            // The config is not set yet. Set the value to 1.
            $helper->set_tool_config($page, 'teacherpermission', 1);
            return 1;
        }

        // The config was already set. Toggle it.
        $helper->set_tool_config($page, 'teacherpermission', 0);
        return 0;
    }
    /**
     * Delete all DB entries related to a specific page.
     * @param object $page
     * @return bool
     */
    public function delete_page(object $page) {
        global $DB;
        try {
            // Table not written yet
            // $DB->delete_records('mtmt_wordcloud', array('pageid' => $page->id));
            $DB->delete_records('mtmt_wordcloud_answers', array('pageid' => $page->id));
        } catch (\Exception $e) {
            // Todo handling
            echo 'Something went wrong';
            return false;
        }
        return true;
    }

    /**
     * Get content menu bar.
     *
     * @param object $page
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_content_menu(object $page) {
        global $OUTPUT, $PAGE;
        $params = [];
        $params['icon-eye'] = [
            'icon' => 'fa-eye',
            'id' => 'toggleteacherpermission',
            'additional_class' => 'mtm_redirect_selector',
            'href' => new \moodle_url('/mod/mootimeter/view.php', array('id' => $PAGE->cm->id, 'pageid' => $page->id, 'r' => 1))
        ];
        $params['icon-restart'] = [
            'icon' => 'fa-rotate-left',
            'id' => 'resetanswers',
        ];
        if (empty($this->get_tool_config($page->id, 'teacherpermission'))) {
            $params['icon-eye']['additional_class'] = " disabled";
            $params['icon-eye']['href'] = "";
            $params['icon-eye']['tooltip'] = "Die Lehrkraft muss die Freigabe zur Ansicht der Ergebnisseite erteilen";
        } else if (!empty($this->get_tool_config($page->id, 'teacherpermission'))) {
            $params['icon-eye']['additional_class'] .= "";
        }

        if (
            has_capability('mod/mootimeter:moderator', \context_module::instance($PAGE->cm->id))
            && $this->get_tool_config($page->id, 'showresult') == self::MTMT_VIEW_RESULT_TEACHERPERMISSION
        ) {

            if (empty($this->get_tool_config($page->id, 'teacherpermission'))) {
                $params['icon-eye']['additional_class'] = " disabled";
             } else if (!empty($this->get_tool_config($page->id, 'teacherpermission'))) {
                $params['icon-eye']['additional_class'] .= "";
            }
        }

        return $OUTPUT->render_from_template("mootimetertool_wordcloud/snippet_content_menu", $params);
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
