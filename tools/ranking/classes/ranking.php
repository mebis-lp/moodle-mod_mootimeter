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
 * @package     mootimetertool_ranking
 * @copyright   2024, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

namespace mootimetertool_ranking;

use coding_exception;
use dml_exception;
use moodle_exception;
use moodle_url;
use pix_icon;

/**
 * Pluginlib
 *
 * @package     mootimetertool_ranking
 * @copyright   2024, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class ranking extends \mod_mootimeter\toolhelper {

    /**
     * @var string Answer coloum
     */
    const ANSWER_COLUMN = "votevalue";

    /**
     * @var string Answer coloum
     */
    const PHRASE_COLUMN = "phrase";

    /**
     * @var string Cacheidentifier phrases.
     */
    const CACHEIDENTIFIER_PHRASES = 'phrases';

    /**
     * @var string Cacheidentifier phrases votes.
     */
    const CACHEIDENTIFIER_VOTES = 'phrasevotes';

    /**
     * @var string Phrases table
     */
    const PHRASES_TABLE = "mootimetertool_ranking_phrases";

    /**
     * @var string Votes table
     */
    const PHRASE_VOTES_TABLE = "mootimetertool_ranking_votes";

    /**
     * @var string Name of table column of the answer table where the user id is stored
     */
    const ANSWER_USERID_COLUMN = 'usermodified';

    /**
     * @var int Phrase state - declined.
     */
    const PHRASE_STATE_DECLINED = 0;

    /**
     * @var int Phrase state - not reviewed.
     */
    const PHRASE_STATE_NOT_REVIEWED = 5;

    /**
     * @var int Phrase state - accepted.
     */
    const PHRASE_STATE_ACCEPTED = 10;

    /**
     * @var int Vote value - thumb down.
     */
    const VOTE_VALUE_THUMBDOWN = -1;

    /**
     * @var int Vote value - thumb up.
     */
    const VOTE_VALUE_THUMBUP = 1;

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
        return self::PHRASE_VOTES_TABLE;
    }

    /**
     * Get the tools answer table.
     * @return string
     */
    public function get_phrases_table() {
        return self::PHRASES_TABLE;
    }

    /**
     * Get the userid column name in the answer table of the tool.
     *
     * @return ?string the column name where the user id is stored in the answer table, null if no user id is stored
     */
    public function get_answer_userid_column(): ?string {
        return self::ANSWER_USERID_COLUMN;
    }

    /**
     * Will be executed after the page is created.
     * @param object $page
     * @return void
     */
    public function hook_after_new_page_created(object $page): void {
        global $USER;

        $record = new \stdClass();
        $record->pageid = $page->id;
        $record->usermodified = $USER->id;
        $record->pageid = $page->id;
        $record->phrase = "";
        $record->state = self::PHRASE_STATE_NOT_REVIEWED;
        $record->timecreated = time();

        // Store two answer options as default.
        $this->store_phrase($record);
        $this->store_phrase($record);
        return;
    }

    /**
     * Store a phrase option.
     *
     * @param object $record
     * @return int
     */
    public function store_phrase(object $record): int {
        global $DB;

        $instance = \mod_mootimeter\helper::get_instance_by_pageid($record->pageid);
        $cm = \mod_mootimeter\helper::get_cm_by_instance($instance);
        if (!has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id))) {
            return 0;
        }

        // Set the default value for timemodified to 0. This is necessary to make usage of GREATEST SQL method possible.
        $record->timemodified = 0;

        if (!empty($record->id)) {
            $page = $this->get_page($record->pageid);
            $origrecord = $DB->get_record($this->get_phrases_table(), ['id' => $record->id], '*', MUST_EXIST);
            $origrecord->pageid = $record->pageid;
            $origrecord->phrase = $record->optiontext;
            $origrecord->timemodified = time();

            $DB->update_record($this->get_phrases_table(), $origrecord);
            return $origrecord->id;
        }

        return $DB->insert_record($this->get_phrases_table(), $record, true);
    }

    /**
     * Get a phrase object.
     *
     * @param int $phraseid
     * @return object|bool
     * @throws dml_exception
     */
    public function get_phrase(int $phraseid): object|bool {
        global $DB;
        return $DB->get_record(self::PHRASES_TABLE, ['id' => $phraseid]);
    }

    /**
     * Get the user vote record of a phrase.
     *
     * @param int $userid
     * @param int $phraseid
     * @return object|bool
     */
    public function get_user_phrase_vote(int $userid, int $phraseid): object|bool {
        global $DB;
        return $DB->get_record(self::PHRASE_VOTES_TABLE, ['phraseid' => $phraseid, 'usermodified' => $userid]);
    }

    /**
     * Delete all DB entries related to a specific page.
     * @param object $page
     * @return bool
     */
    public function delete_page_tool(object $page) {
        global $DB;
        try {
            $DB->delete_records($this->get_answer_table(), ['pageid' => $page->id]);
            $DB->delete_records($this->get_phrases_table(), ['pageid' => $page->id]);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Insert a phrase.
     *
     * @param object $page
     * @param string $phrase
     * @return void
     */
    public function insert_phrase(object $page, string $phrase): void {
        global $USER;

        $record = new \stdClass();
        $record->usermodified = $USER->id;
        $record->pageid = $page->id;
        $record->phrase = $phrase;
        $record->state = self::PHRASE_STATE_NOT_REVIEWED;
        $record->timecreated = time();

        $this->store_answer(self::PHRASES_TABLE, $record, [], 'phrase');
    }

    /**
     * Get a pure list of unique phrases, without id etc.
     *
     * @param int $pageid
     * @param int $userid
     * @return array
     * @throws dml_exception
     */
    public function get_phrase_list_array(int $pageid, int $userid = 0): array {
        global $DB;

        $params = [
            'pageid' => $pageid,
        ];

        if (!empty($userid)) {
            $params['usermodified'] = $userid;
        }

        return array_keys($DB->get_records_menu(self::PHRASES_TABLE, $params, "", self::PHRASE_COLUMN));
    }

    /**
     * Page type specivic insert_answer
     *
     * @param object $phrase
     * @param string $votevalue
     * @return void
     */
    public function insert_answer(object $phrase, $votevalue): void {
        global $USER;

        $record = new \stdClass();
        $record->pageid = $phrase->pageid;
        $record->phraseid = $phrase->id;
        $record->votevalue = $votevalue;
        $record->timecreated = time();

        $updatecondition = ['pageid' => $phrase->pageid, 'usermodified' => $USER->id, 'phraseid' => $phrase->id];

        $this->store_answer(self::PHRASE_VOTES_TABLE, $record, $updatecondition, self::ANSWER_COLUMN);
    }

    /**
     * Get the lastupdated timestamp.
     *
     * @param int|object $pageorid
     * @param bool $ignoreanswers
     * @return int
     */
    public function get_last_update_time(int|object $pageorid, bool $ignoreanswers = false): int {
        global $DB;

        $page = $pageorid;
        if (!is_object($page)) {
            $page = $this->get_page($page);
        }

        $instance = self::get_instance_by_pageid($page->id);
        $cm = self::get_cm_by_instance($instance);

        // We only want to deliver results if the teacher allowed to view it.
        if (
            empty($this->get_tool_config($page->id, 'showonteacherpermission'))
            && !has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id))
        ) {
            return 0;
        }

        $mostrecenttimeanswer = 0;
        if (!$ignoreanswers) {
            // It's important, that the default value is NOT null, but 0 instead. Otherwise GREATEST will return null anyway.
            $sql = 'SELECT SUM(GREATEST(timecreated, timemodified)) as time FROM '
                . '{' . $this->get_answer_table() . '} WHERE pageid = :pageid';
            $mostrecenttimeanswer = $DB->get_field_sql($sql, ['pageid' => $page->id]);
        }

        // It's important, that the default value is NOT null, but 0 instead. Otherwise GREATEST will return null anyway.
        $mostrecenttimephrases = 0;
        $sql = 'SELECT SUM(GREATEST(timecreated, timemodified)) as time FROM '
            . '{' . $this->get_phrases_table() . '} WHERE pageid = :pageid';
        $mostrecenttimephrases = $DB->get_field_sql($sql, ['pageid' => $page->id]);

        return $mostrecenttimeanswer + $mostrecenttimephrases;
    }

    /**
     * Handels inplace_edit.
     *
     * @param string $itemtype
     * @param string $itemid
     * @param mixed $newvalue
     * @return mixed
     */
    public function handle_inplace_edit(string $itemtype, string $itemid, mixed $newvalue) {
        if ($itemtype === 'editanswer') {
            return \mootimetertool_ranking\local\inplace_edit_answer::update($itemid, $newvalue);
        }
    }

    /**
     * Get all parameters that are necessary for rendering the tools view.
     *
     * @param object $page
     * @return array
     */
    public function get_renderer_params(object $page) {

        switch($this->get_tool_config($page, 'phase')) {
            case 2:
                $params = $this->get_renderer_params_phase_two($page);
                break;
            case 3:
                $params = $this->get_renderer_params_phase_three($page);
                break;
            default:
                $params = $this->get_renderer_params_phase_one($page);
                break;
        }

        return $params;
    }

    /**
     * Get the rendering parameters for phase one.
     *
     * @param object $page
     * @return array
     */
    public function get_renderer_params_phase_two(object $page) {
        global $USER;

        $params = $this->get_indicator_params($page);
        $params['pageid'] = $page->id;

        $params['heading_phase_2'] = $this->get_tool_config($page, 'heading_phase_2');

        // Parameter for initializing Badges.
        $params["toolname"] = ['pill' => get_string("pluginname", "mootimetertool_" . $page->tool)];
        $params["phase"] = [
            'pill' => get_string("phase_two_heading", "mootimetertool_" . $page->tool),
            'additional_class' => 'mtmt-ranking-phase-1',
        ];

        $params['template'] = "mootimetertool_" . $page->tool . "/view_phase_2";

        $phrases = $this->get_user_answers(
            self::PHRASES_TABLE,
            $page->id,
            self::PHRASE_COLUMN,
            $USER->id,
            self::CACHEIDENTIFIER_PHRASES
        );

        $params["phrases"] = array_values(array_map(function ($phrase) use ($page) {

            // Define yes toggle.
            $datasettoggleyes = [
                'data-pageid="' . $page->id . '"',
                'data-phraseid="' . $phrase->id . '"',
                'data-togglevalue="' . self::PHRASE_STATE_ACCEPTED . '"',
            ];

            // Define no toggle.
            $datasettoggleno = [
                'data-pageid="' . $page->id . '"',
                'data-phraseid="' . $phrase->id . '"',
                'data-togglevalue="' . self::PHRASE_STATE_DECLINED . '"',
            ];

            // Add delete button to answer.
            $dataseticontrash = [
                'data-ajaxmethode = "mod_mootimeter_delete_single_answer"',
                'data-pageid="' . $page->id . '"',
                'data-answerid="' . $phrase->id . '"',
                'data-confirmationtitlestr="' . get_string('delete_single_answer_dialog_title', 'mod_mootimeter') . '"',
                'data-confirmationquestionstr="' . get_string('delete_single_answer_dialog_question', 'mod_mootimeter') . '"',
                'data-confirmationtype="DELETE_CANCEL"',
            ];

            return [
                'phrase' => $phrase->phrase,
                'deletebutton' => [
                    'id' => 'mtmt_delete_answer_' . $phrase->id,
                    'iconid' => 'mtmt_delete_iconid_' . $phrase->id,
                    'dataset' => implode(" ", $dataseticontrash),
                ],
                'radioname' => 'mtmt_toggle_' . $phrase->id,
                'yes_id' => 'mtmt_toggle_yes_' . $phrase->id,
                'yes_active' => (isset($phrase->state) && $phrase->state == self::PHRASE_STATE_ACCEPTED) ? 'active' : '',
                'yes_checked' => (isset($phrase->state) && $phrase->state == self::PHRASE_STATE_ACCEPTED) ? 'checked' : '',
                'yes_dataset' => implode(" ", $datasettoggleyes),
                'no_id' => 'mtmt_toggle_no_' . $phrase->id,
                'no_active' => (isset($phrase->state) && $phrase->state == self::PHRASE_STATE_DECLINED) ? 'active' : '',
                'no_checked' => (isset($phrase->state) && $phrase->state == self::PHRASE_STATE_DECLINED) ? 'checked' : '',
                'no_dataset' => implode(" ", $datasettoggleno),

            ];
        }, $phrases));

        return $params;
    }

    /**
     * Get the rendering parameters for phase one.
     *
     * @param object $page
     * @return array
     */
    public function get_renderer_params_phase_three(object $page) {
        global $USER;

        $params = $this->get_indicator_params($page);

        $params['pageid'] = $page->id;

        $params['heading_phase_3'] = $this->get_tool_config($page, 'heading_phase_3');

        // Parameter for initializing Badges.
        $params["toolname"] = ['pill' => get_string("pluginname", "mootimetertool_" . $page->tool)];
        $params["phase"] = [
            'pill' => get_string("phase_three_heading", "mootimetertool_" . $page->tool),
            'additional_class' => 'mtmt-ranking-phase-1',
        ];

        $params['template'] = "mootimetertool_" . $page->tool . "/view_phase_3_thumbs";

        $params["phrases"] = array_values(array_map(function ($phrase) use ($page) {
            global $USER;

            // Add thumbs up button.
            $dataseticonthumbup = [
                'data-ajaxmethode = "mootimetertool_ranking_vote_phrase_thumb"',
                'data-pageid="' . $page->id . '"',
                'data-thumbvalue="' . self::VOTE_VALUE_THUMBUP. '"',
                'data-phraseid="' . $phrase->id . '"',
            ];

            // Add thumbs up button.
            $dataseticonthumbdown = [
                'data-ajaxmethode = "mootimetertool_ranking_vote_phrase_thumb"',
                'data-pageid="' . $page->id . '"',
                'data-thumbvalue="' . self::VOTE_VALUE_THUMBDOWN . '"',
                'data-phraseid="' . $phrase->id . '"',
            ];

            $uservote = $this->get_user_phrase_vote($USER->id, $phrase->id);
            $additionalclassthumbup = '';
            $additionalclassthumbdown = '';
            if (!empty($uservote->votevalue)) {
                if ($uservote->votevalue > 0) {
                    $additionalclassthumbup = 'active';
                } else if ($uservote->votevalue < 0) {
                    $additionalclassthumbdown = 'active';
                }
            }

            return [
                'phrase' => $phrase->phrase,

                'thumbup' => [
                    'id' => 'mtmt_ranking_thumbup_' . $phrase->id,
                    'faicon' => 'fa-thumbs-up',
                    'dataset' => implode(' ', $dataseticonthumbup),
                    'additional_class' => 'mtmt_ranking_thumbup ' . $additionalclassthumbup,
                ],
                'thumbdown' => [
                    'id' => 'mtmt_ranking_thumbdown_' . $phrase->id,
                    'faicon' => 'fa-thumbs-down',
                    'dataset' => implode(" ", $dataseticonthumbdown),
                    'additional_class' => 'mtmt_ranking_thumbdown ' . $additionalclassthumbdown,
                ],
            ];
        }, $this->get_user_answers(self::PHRASES_TABLE, $page->id, self::PHRASE_COLUMN, $USER->id, self::CACHEIDENTIFIER_PHRASES)));

        return $params;
    }


    /**
     * Get the rendering parameters for phase one.
     *
     * @param object $page
     * @return array
     */
    public function get_renderer_params_phase_one(object $page) {
        global $USER;

        $params = $this->get_indicator_params($page);
        $params['pageid'] = $page->id;

        $params['heading_phase_1'] = $this->get_tool_config($page, 'heading_phase_1');

        // Parameter for initializing Badges.
        $params["toolname"] = ['pill' => get_string("pluginname", "mootimetertool_" . $page->tool)];

        $params["phase"] = [
            'pill' => get_string("phase_one_heading", "mootimetertool_" . $page->tool),
            'additional_class' => 'mtmt-ranking-phase-1',
        ];

        $params['template'] = "mootimetertool_" . $page->tool . "/view_phase_1";

        $params["phrases"] = array_values(array_map(function ($element) use ($page) {

            // Add delete button to answer.
            $dataseticontrash = [
                'data-ajaxmethode = "mod_mootimeter_delete_single_answer"',
                'data-pageid="' . $page->id . '"',
                'data-answerid="' . $element->id . '"',
                'data-confirmationtitlestr="' . get_string('delete_single_answer_dialog_title', 'mod_mootimeter') . '"',
                'data-confirmationquestionstr="' . get_string('delete_single_answer_dialog_question', 'mod_mootimeter') . '"',
                'data-confirmationtype="DELETE_CANCEL"',
            ];

            return [
                'pill' => $element->phrase,
                'additional_class' => 'mootimeter-pill-inline',
                'deletebutton' => [
                    'id' => 'mtmt_delete_answer_' . $element->id,
                    'iconid' => 'mtmt_delete_iconid_' . $element->id,
                    'dataset' => implode(" ", $dataseticontrash),
                ],
            ];
        }, $this->get_user_answers(self::PHRASES_TABLE, $page->id, self::PHRASE_COLUMN, $USER->id)));

        $params['input_answer'] = [
            'mtm-input-id' => 'mootimeter_type_answer',
            'mtm-input-name' => 'answer',
            'mtm-button-id' => 'mootimeter_enter_answer',
            'dataset' => 'data-pageid="' . $page->id . '"',
            'autofocus' => true,
            'additional_class' => 'mtmt-wc-answerinput',
        ];

        $params['button_answer'] = [
            'mtm-button-id' => 'mootimeter_enter_answer',
            'mtm-button-text' => 'Senden',
        ];

        return $params;
    }

    /**
     * Get the indicatior params.
     *
     * @param object $page
     * @return array
     */
    public function get_indicator_params(object $page): array {
        $datasetphase1 = [
            'data-ajaxmethode="mod_mootimeter_store_setting"',
            'data-value="1"',
            'data-name="phase"',
            'data-reload="1"',
        ];
        $datasetphase2 = [
            'data-ajaxmethode="mod_mootimeter_store_setting"',
            'data-value="2"',
            'data-name="phase"',
            'data-reload="1"',
        ];
        $datasetphase3 = [
            'data-ajaxmethode="mod_mootimeter_store_setting"',
            'data-value="3"',
            'data-name="phase"',
            'data-reload="1"',
        ];

        $params = [];

        $params['indicator_bar'] = [
            'phase_1' => [
                'active' => ($this->get_tool_config($page, 'phase') == 1) ? ' active' : '',
                'id' => 'mtm_indicator_phase_1',
                'value' => 1,
                'dataset' => join(" ", $datasetphase1),
            ],
            'phase_2' => [
                'active' => ($this->get_tool_config($page, 'phase') == 2) ? ' active' : '',
                'id' => 'mtm_indicator_phase_2',
                'value' => 2,
                'dataset' => join(" ", $datasetphase2),
            ],
            'phase_3' => [
                'active' => ($this->get_tool_config($page, 'phase') == 3) ? ' active' : '',
                'id' => 'mtm_indicator_phase_3',
                'value' => 3,
                'dataset' => join(" ", $datasetphase3),
            ],
        ];

        return $params;
    }

    /**
     * Get the params for settings column.
     *
     * @param object $page
     * @param array $params
     * @return array
     */
    public function get_col_settings_tool_params(object $page, array $params = []) {
        global $USER;

        $instance = self::get_instance_by_pageid($page->id);
        $cm = self::get_cm_by_instance($instance);
        if (!has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id)) || empty($USER->editing)) {
            return [];
        }

        $params['template'] = 'mootimetertool_ranking/view_settings';

        $params['heading_phase_1'] = [
            'title' => get_string('settings_heading_phase', 'mootimetertool_ranking'),
            'mtm-input-id' => 'mtm_input_heading_phase_1',
            'mtm-input-value' => s(self::get_tool_config($page, 'heading_phase_1')),
            'mtm-input-placeholder' => get_string('enter_task', 'mootimetertool_ranking'),
            'mtm-input-name' => "heading_phase_1",
            'pageid' => $page->id,
            'ajaxmethode' => "mod_mootimeter_store_setting",
        ];
        $params['heading_phase_2'] = [
            'title' => get_string('settings_heading_phase', 'mootimetertool_ranking'),
            'mtm-input-id' => 'mtm_input_heading_phase_2',
            'mtm-input-value' => s(self::get_tool_config($page, 'heading_phase_2')),
            'mtm-input-placeholder' => get_string('enter_task', 'mootimetertool_ranking'),
            'mtm-input-name' => "heading_phase_2",
            'pageid' => $page->id,
            'ajaxmethode' => "mod_mootimeter_store_setting",
        ];
        $params['heading_phase_3'] = [
            'title' => get_string('settings_heading_phase', 'mootimetertool_ranking'),
            'mtm-input-id' => 'mtm_input_heading_phase_3',
            'mtm-input-value' => s(self::get_tool_config($page, 'heading_phase_3')),
            'mtm-input-placeholder' => get_string('enter_task', 'mootimetertool_ranking'),
            'mtm-input-name' => "heading_phase_3",
            'pageid' => $page->id,
            'ajaxmethode' => "mod_mootimeter_store_setting",
        ];

        $params['maxvotesperuser'] = [
            'title' => get_string('votes_max_number', 'mootimetertool_ranking'),
            'additional_class' => 'mootimeter_settings_selector',
            'id' => "maxvotesperuser",
            'name' => "maxvotesperuser",
            'min' => 0,
            'pageid' => $page->id,
            'ajaxmethode' => "mod_mootimeter_store_setting",
            'value' => (empty(self::get_tool_config($page->id, "maxvotesperuser"))) ? '0' : self::get_tool_config(
                $page->id,
                "maxvotesperuser"
            ),
        ];

        $params['maxphrasesperuser'] = [
            'title' => get_string('phrases_max_number', 'mootimetertool_ranking'),
            'additional_class' => 'mootimeter_settings_selector',
            'id' => "maxphrasesperuser",
            'name' => "maxphrasesperuser",
            'min' => 0,
            'pageid' => $page->id,
            'ajaxmethode' => "mod_mootimeter_store_setting",
            'value' => (empty(self::get_tool_config($page->id, "maxphrasesperuser"))) ? '0' : self::get_tool_config(
                $page->id,
                "maxphrasesperuser"
            ),
        ];

        $params['settings']['anonymousmode'] = [
            'cb_with_label_id' => 'anonymousmode',
            'pageid' => $page->id,
            'cb_with_label_text' => get_string('anonymousmode', 'mod_mootimeter')
                . " " . get_string('anonymousmode_desc', 'mod_mootimeter'),
            'cb_with_label_name' => 'anonymousmode',
            'cb_with_label_additional_class' => 'mootimeter_settings_selector',
            'cb_with_label_ajaxmethod' => "mod_mootimeter_store_setting",
            'cb_with_label_checked' => (\mod_mootimeter\helper::get_tool_config($page, 'anonymousmode') ? "checked" : ""),
        ];

        $answers = $this->get_answers(self::PHRASE_VOTES_TABLE, $page->id, self::PHRASE_COLUMN, self::CACHEIDENTIFIER_VOTES);
        // The anonymous mode could not be changed if, there are any answers already given.
        if (!empty($answers)) {
            $params['settings']['anonymousmode']['cb_with_label_disabled'] = 'disabled';
            unset($params['settings']['anonymousmode']['cb_with_label_ajaxmethod']);
        }

        $returnparams['colsettings'] = $params;
        return $returnparams;
    }

    /**
     * Get the answer overview params.
     *
     * @param object $cm
     * @param object $page
     * @return array
     */
    public function get_tool_answer_overview_params(object $cm, object $page): array {
        return [];
    }


    /**
     * Get the answer overview.
     *
     * @param object $cm
     * @param object $page
     * @return string
     */
    public function get_answer_overview(object $cm, object $page): string {
        return "";
        global $OUTPUT;
        $params = $this->get_answer_overview_params($cm, $page);
        return $OUTPUT->render_from_template("mod_mootimeter/answers_overview", $params['pagecontent']);
    }

    /**
     * Get content menu bar params.
     *
     * @param object $page
     * @param array $params Defaultparams
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_content_menu_tool_params(object $page, array $params) {
        return $params;
    }

    /**
     * Set the phrase approval state.
     *
     * @param int $phraseid
     * @param int $state
     * @return bool
     */
    public function set_phrase_approval_state(int $phraseid, int $state): bool {
        global $DB;

        $phrase = $DB->get_record(self::PHRASES_TABLE, ['id' => $phraseid]);

        $phrase->state = $state;

        $success = $DB->update_record(self::PHRASES_TABLE, $phrase);

        // Rebuild cache.
        $this->clear_caches($phrase->pageid, self::CACHEIDENTIFIER_PHRASES);
        $this->get_answers(self::PHRASES_TABLE, $phrase->pageid, self::PHRASE_COLUMN, self::CACHEIDENTIFIER_PHRASES);

        return $success;
    }

    /**
     * Tool specific cache definitions used in mootimeter core methods
     * @return array
     */
    public function get_tool_cachedefinition() {
        return [
            self::CACHEIDENTIFIER_PHRASES => [
                'mode' => \cache_store::MODE_APPLICATION,
            ],
            self::CACHEIDENTIFIER_VOTES => [
                'mode' => \cache_store::MODE_APPLICATION,
            ],
        ];
    }
}
