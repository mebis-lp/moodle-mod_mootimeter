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
use mod_mootyper_generator;

/**
 * mod_mootimeter helper test
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper_test extends advanced_testcase {

    /** @var \stdClass The course used for testing */
    private $course;
    /** @var \stdClass The kanban used for testing */
    private $mootimeter;
    /** @var array The users used for testing */
    private $users;
    /** @var array The roles defined for testing */
    private $roles;

    /** @var string test question */
    const TEST_QUESTION_TITLE = 'Is this a test question?';

    /** @var string toolname: quiz */
    const TOOLNAME_QUIZ = 'quiz';

    /**
     * Setup the test cases.
     */
    public function setup(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $this->mootimeter = $generator->create_module('mootimeter', ['course' => $this->course]);

        $this->roles['student'] = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $this->roles['teacher'] = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);

        $this->users['teacher'] = $generator->create_user();
        $this->users['student'] = $generator->create_user();

        $generator->role_assign('teacher', $this->users['teacher']->id, \context_module::instance($this->mootimeter->cmid));
        $generator->role_assign('student', $this->users['student']->id, \context_module::instance($this->mootimeter->cmid));

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
    public function test_create_delete_page() {
        $this->resetAfterTest();

        $mtmgenerator = $this->getDataGenerator()->get_plugin_generator('mod_mootimeter');
        $helper = new \mod_mootimeter\helper();

        $this->assertCount(0, $helper->get_pages($this->mootimeter->id));

        $this->setUser($this->users['teacher']);
        $record = [
            'instance' => $this->mootimeter->id,
            'tool' => 'wordcloud',
        ];
        $page = $mtmgenerator->create_page($record);
        $this->assertCount(1, $helper->get_pages($this->mootimeter->id));

        // Now check that a student gets an Exception, when creating a page.
        $this->setUser($this->users['student']);
        $this->expectException(\required_capability_exception::class);
        $mtmgenerator->create_page($record);

        // Now delete the page.
        // First use student.
        $this->expectException(\required_capability_exception::class);
        $helper->delete_page($page->id);

        // Second use teacher.
        $this->setUser($this->users['teacher']);
        $helper->delete_page($page->id);
        $this->assertEmpty($helper->get_pages($this->mootimeter->id));
    }

    /**
     * Store page details
     * @covers \mod_mootimeter\helper->store_page_detail
     */
    public function test_store_page_detail() {
        $this->resetAfterTest();

        $mtmgenerator = $this->getDataGenerator()->get_plugin_generator('mod_mootimeter');
        $helper = new \mod_mootimeter\helper();

        $this->setUser($this->users['teacher']);
        $record = [
            'instance' => $this->mootimeter->id,
            'tool' => 'wordcloud',
        ];
        $page = $mtmgenerator->create_page($record);

        $helper->store_page_detail($page->id, 'tool', self::TOOLNAME_QUIZ);
        $pagenew = $helper->get_page($page->id);
        $this->assertEquals(self::TOOLNAME_QUIZ, $pagenew->tool);
    }
}
