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
     * Get all parameters that are necessary for rendering the tools view.
     *
     * @param object $page
     * @return array
     */
    public function get_renderer_params(object $page) {
        global $OUTPUT;
        if (!empty($this->get_tool_config($page->id, 'ispoll'))) {
            switch ($this->get_tool_config($page->id, 'ispoll')) {
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

        $params['answer_options'][] = [
            'ao_id' => 123,
            'ao_heading' => 'Antwort 1',
            'ao_text' => 'Das ist meine Frage',
            $ispoll => true,
            'pageid' => $page->id,
        ];
        $params['answer_options'][] = [
            'ao_id' => 456,
            'ao_heading' => 'Antwort 2',
            'ao_text' => 'Das ist meine Frage 2',
            $ispoll => true,
            'pageid' => $page->id,
        ];
        $params['answer_options'][] = [
            'ao_id' => 234,
            'ao_heading' => 'Antwort 3',
            'ao_text' => 'Das ist meine Frage 3',
            $ispoll => true,
            'pageid' => $page->id,
        ];
        $params['answer_options'][] = [
            'ao_id' => 567,
            'ao_heading' => 'Antwort 4',
            'ao_text' => 'Das ist meine Frage 4',
            $ispoll => true,
            'pageid' => $page->id,
        ];
        $params['question_text'] = "Wie finden Sie Mootimeter?";
        $tmparams = [
            'id' => 'quiz_show_results',
            'text' => get_string('show_results', 'mootimetertool_quiz'),
            'cssclasses' => 'mootimeter_margin_top_5',
        ];

        //$params['redirect'] = new \moodle_url("tools/quiz/results.php", ["m"=> $page->instance,"pageid"=>$page->id]);
        return $params;
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
        global $OUTPUT;
        $chart = new \core\chart_bar();
        $chart->set_labels(["test", "test2"]);
        $series = new \core\chart_series("test",[100, 200]);
        $chart->add_series($series);
        $paramschart = ['charts' => $OUTPUT->render($chart)];

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
