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

class wordcloud extends \mod_mootimeter\toolhelper {

    /** Show Results live */
    const MTMT_VIEW_RESULT_LIVE = 1;
    /** Show Results after teacher permission */
    const MTMT_VIEW_RESULT_TEACHERPERMISSION = 2;

    /**
     *
     * @param string $answer
     * @return void
     */
    public function insert_answer(object $page, $answer): void {
        global $USER, $DB;
        $record = new \stdClass();
        $record->pageid = $page->id;
        $record->usermodified = $USER->id;
        $record->answer = $answer;
        $record->timecreated = time();
        $DB->insert_record('mtmt_wordcloud_answers', $record);
    }

    /**
     * Get all answers of an user of a page.
     *
     * @param int $userid
     * @param int $pageid
     * @return mixed
     */
    public function get_user_answers(int $userid, int $pageid) {
        global $DB;
        return $DB->get_records('mtmt_wordcloud_answers', ['usermodified' => $userid, 'pageid' => $pageid]);
    }

    /**
     * Get all answers of a page.
     *
     * @param int $pageid
     * @return array
     * @throws dml_exception
     */
    public function get_answers(int $pageid) {
        global $DB;
        return $DB->get_records('mtmt_wordcloud_answers', ['pageid' => $pageid]);
    }

    /**
     * Get all grouped and counted answers of a page.
     *
     * @param int $pageid
     * @return array
     * @throws dml_exception
     */
    protected function get_answers_list(int $pageid) {
        global $DB;

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
            $sql = "SELECT answer, count(*) * 24 FROM {mtmt_wordcloud_answers} WHERE pageid = :pageid GROUP BY answer";
            return $DB->get_records_sql($sql, $params);
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
     *
     * @param int $pageid
     * @return array
     * @throws dml_exception
     */
    public function get_answerlist(int $pageid): array {
        return $this->convert_answer_list_to_wordcloud_list($this->get_answers_list($pageid));
    }

    /**
     * Get all parameters that are necessary for rendering the tools view.
     *
     * @param object $page
     * @return array
     */
    public function get_renderer_params(object $page) {
        global $OUTPUT, $PAGE;

        // Parameter for initial wordcloud rendering.
        $params['answerslist'] = json_encode($this->get_answerlist($page->id));

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
                $params['teacherpermission'] = $OUTPUT->render_from_template('mootimetertool_wordcloud/snippet_button', $tmparams);
            }

            if (!empty($this->get_tool_config($page->id, 'teacherpermission'))) {
                $tmparams = [
                    'id' => 'toggleteacherpermission',
                    'text' => get_string('hide_results', 'mootimetertool_wordcloud'),
                    'cssclasses' => 'mootimeter_margin_top_50 mootimeterfullwidth',
                    'pageid' => $page->id,
                ];
                $params['teacherpermission'] = $OUTPUT->render_from_template('mootimetertool_wordcloud/snippet_button', $tmparams);
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
    public function get_last_update_time(int $pageid): int {
        global $DB;

        // We only want to deliver results if showresults is true or the teacher allowed to view it.
        if (
            $this->get_tool_config($pageid, 'showresult') == self::MTMT_VIEW_RESULT_LIVE
            || ($this->get_tool_config($pageid, 'showresult') == self::MTMT_VIEW_RESULT_TEACHERPERMISSION
                && !empty($this->get_tool_config($pageid, 'teacherpermission'))
            )
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

        // $settings['settingsarray'][] = [
        //     "checkbox" => true,
        //     "id" => 'labelid-2',
        //     "name" => 'name2',
        //     "label" => "This is the settings label of setting 2",
        //     "helptitle" => "This is the settings help title 2",
        //     "help" => "Test 2",
        //     "value" => 1,
        //     "checked" => true
        // ];

        // $settings['settingsarray'][] = [
        //     "number" => true,
        //     "id" => 'labelid-3',
        //     "name" => 'name3',
        //     "label" => "This is the settings label of setting 3",
        //     "helptitle" => "This is the settings help title 3",
        //     "help" => "Test 3",
        //     "value" => 122,
        // ];

        // $settings['settingsarray'][] = [
        //     "text" => true,
        //     "id" => 'labelid-4',
        //     "name" => 'name4',
        //     "label" => "This is the settings label of setting 4",
        //     "helptitle" => "This is the settings help title 4",
        //     "help" => "Test 4",
        //     "value" => "Testtext",
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

        $helper = new \mod_mootimeter\helper();

        if(empty($teacherpermission)){
            // The config is not set yet. Set the value to 1.
            $helper->set_tool_config($page, 'teacherpermission', 1);
            return 1;
        }

        // The config was already set. Toggle it.
        $helper->set_tool_config($page, 'teacherpermission', 0);
        return 0;
    }
}
