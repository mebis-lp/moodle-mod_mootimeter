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
        $params = [
            'pageid' => $pageid,
        ];
        $sql = "SELECT answer, count(*) * 24 FROM {mtmt_wordcloud_answers} WHERE pageid = :pageid GROUP BY answer";
        return $DB->get_records_sql($sql, $params);
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
        global $USER;

        // Parameters for Badges list.
        $answers = $this->get_user_answers($USER->id, $page->id);
        foreach ($answers as $answer) {
            $params['answers'][] = ['answer' => $answer->answer];
        }

        // Parameter for initial wordcloud rendering.
        $params['answerslist'] = json_encode($this->get_answerlist($page->id));

        // Parameter for last updated.
        $params['lastupdated'] = $this->get_last_update_time($page->id);

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

        $records = $DB->get_records('mtmt_wordcloud_answers', ['pageid' => $pageid], 'timecreated DESC', 'timecreated', 0, 1);

        if(empty($records)){
            return 0;
        }
        $record = array_shift($records);
        return $record->timecreated;
    }
}
