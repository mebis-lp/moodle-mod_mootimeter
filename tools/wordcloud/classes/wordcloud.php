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

class wordcloud extends \mod_mootimeter\toolhelper{

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
    public function get_user_answers(int $userid, int $pageid){
        global $DB;
        return $DB->get_records('mtmt_wordcloud_answers', ['usermodified' => $userid, 'pageid' => $pageid]);
    }

    /**
     * Get all parameters that are necessary for rendering the tools view.
     *
     * @param object $page
     * @return array
     */
    public function get_renderer_params(object $page){
        global $USER;
        $answers = $this->get_user_answers($USER->id, $page->id);
        foreach($answers as $answer){
            $params['answers'][] = ['answer' => $answer->answer];
        }
        return $params;
    }

}
