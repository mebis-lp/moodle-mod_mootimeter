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

use dml_exception;
use mod_mootimeter\page_manager;

class wordcloud extends \mod_mootimeter\toollib {

    /** Show Results live */
    const MTMT_VIEW_RESULT_LIVE = 1;
    /** Show Results after teacher permission */
    const MTMT_VIEW_RESULT_TEACHERPERMISSION = 2;

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
        global $OUTPUT, $PAGE, $USER;

        // Parameter for initial wordcloud rendering.
        $params['answerslist'] = json_encode($this->get_answerlist_wordcloud($page->id));

        // Parameter for initializing Badges.
        $params['answers'] = array_map(function ($element) {
            return ['answer' => $element->answer];
        }, $this->get_user_answers($page->id, $USER->id));

        // Parameter for last updated.
        $params['lastupdated'] = $this->get_last_update_time($page->id);

        if (
            has_capability('mod/mootimeter:moderator', \context_module::instance($PAGE->cm->id))
            && $this->get_tool_config($page->id, 'showresult') == self::MTMT_VIEW_RESULT_TEACHERPERMISSION
        ) {

            if (empty($this->get_tool_config($page->id, 'teacherpermission'))) {
                $tmparams = [
                    'id' => 'toggleteacherpermission',
                    'text' => get_string('show_results', 'mootimetertool_wordcloud'),
                    'cssclasses' => 'mootimeter_margin_top_50 mootimeterfullwidth',
                ];
                $params['teacherpermission'] = $OUTPUT->render_from_template('mod_mootimeter/snippet_button', $tmparams);
            }

            if (!empty($this->get_tool_config($page->id, 'teacherpermission'))) {
                $tmparams = [
                    'id' => 'toggleteacherpermission',
                    'text' => get_string('hide_results', 'mootimetertool_wordcloud'),
                    'cssclasses' => 'mootimeter_margin_top_50 mootimeterfullwidth',
                    'pageid' => $page->id,
                ];
                $params['teacherpermission'] = $OUTPUT->render_from_template('mod_mootimeter/snippet_button', $tmparams);
            }
        }

        return $params;
    }

    /**
     * Get the lastupdated timestamp.
     *
     * @param int $pageid
     * @return int
     */
    public function get_last_update_time(int $pageid, string $tool = 'wordcloud'): int {

        // We only want to deliver results if showresults is true or the teacher allowed to view it.
        if (
            $this->get_tool_config($pageid, 'showresult') == self::MTMT_VIEW_RESULT_TEACHERPERMISSION
            && empty($this->get_tool_config($pageid, 'teacherpermission'))
        ) {
            return 0;
        }
        return parent::get_last_update_time($pageid, $tool);
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
     */
    public function toggle_show_results_state(object $page): int {

        $teacherpermission = $this->get_tool_config($page->id, 'teacherpermission');

        if (empty($teacherpermission)) {
            // The config is not set yet. Set the value to 1.
            page_manager::set_tool_config($page, 'teacherpermission', 1);
            return 1;
        }

        // The config was already set. Toggle it.
        page_manager::set_tool_config($page, 'teacherpermission', 0);
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
