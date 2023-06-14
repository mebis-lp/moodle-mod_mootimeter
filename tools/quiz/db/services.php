<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External service definitions for mootimetertool_quiz.
 *
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mootimetertool_quiz_store_answeroption' => [
        'classname'     => 'mootimetertool_quiz\external\store_answeroption',
        'methodname'    => 'execute',
        'description'   => 'Store an answer option of mootimetertool_quiz changed by the teacher',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mootimetertool_quiz_store_answer' => [
        'classname'     => 'mootimetertool_quiz\external\store_answer',
        'methodname'    => 'execute',
        'description'   => 'Store an answer of mootimetertool_quiz by the student',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mootimetertool_quiz_new_answeroption' => [
        'classname'     => 'mootimetertool_quiz\external\new_answeroption',
        'methodname'    => 'execute',
        'description'   => 'Store new answer option of mootimetertool_quiz.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mootimetertool_quiz_get_answers' => [
        'classname'     => 'mootimetertool_quiz\external\get_answers',
        'methodname'    => 'execute',
        'description'   => 'Get Answers.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
];
