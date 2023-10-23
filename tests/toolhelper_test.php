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
 * mod_mootimeter helper test
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter;

use advanced_testcase;
use coding_exception;
use dml_exception;
use mod_mootyper_generator;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * mod_mootimeter toolhelper test
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class toolhelper_test extends advanced_testcase {

    /** @var \stdClass The course used for testing */
    private $course;
    /** @var \stdClass The kanban used for testing */
    private $mootimeter;
    /** @var array The users used for testing */
    private $users;
    /** @var array The roles defined for testing */
    private $roles;
    /** @var object $generator The generator instance */
    private $generator;

    /** @var string test question */
    const TEST_QUESTION_TITLE = 'Is this a test question?';

    /** @var string toolname: quiz */
    const TOOLNAME_QUIZ = 'quiz';

    /**
     * Setup the test cases.
     */
    public function setup(): void {
        global $DB;

        $this->generator = $this->getDataGenerator();
        $this->course = $this->generator->create_course();
        $this->mootimeter = $this->generator->create_module('mootimeter', ['course' => $this->course]);

        $this->roles['student'] = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $this->roles['teacher'] = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);

        $this->users['teacher'] = $this->generator->create_user();
        $this->users['student'] = $this->generator->create_user();

        $this->generator->role_assign('teacher', $this->users['teacher']->id, \context_module::instance($this->mootimeter->cmid));
        $this->generator->role_assign('student', $this->users['student']->id, \context_module::instance($this->mootimeter->cmid));

        assign_capability(
            'mod/mootimeter:moderator',
            CAP_ALLOW,
            $this->roles['teacher']->id,
            \context_module::instance($this->mootimeter->cmid)->id,
            true
        );
        parent::setup();
    }

    /**
     * Create page
     * @covers \mod_mootimeter\helper->store_page method
     * @covers \mod_mootimeter\helper->delete_page method
     */
    public function test_store_answer() {

        $toolhelper = new \mod_mootimeter\toolhelper();


    }

}