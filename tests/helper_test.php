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
    public function test_create_delete_page() {
        $this->resetAfterTest();

        $helper = new \mod_mootimeter\helper();

        $this->assertCount(0, $helper->get_pages($this->mootimeter->id));

        $this->setUser($this->users['teacher']);
        $record = [
            'instance' => $this->mootimeter->id,
            'tool' => 'wordcloud',
        ];
        $pageid = $helper->store_page((object)$record);
        $this->assertCount(1, $helper->get_pages($this->mootimeter->id));

        // Now check that a student gets an Exception, when creating a page.
        $this->setUser($this->users['student']);
        $this->expectException(\required_capability_exception::class);
        $helper->store_page((object)$record);

        // Now delete the page.
        // First use student.
        $this->expectException(\required_capability_exception::class);
        $helper->delete_page($pageid);

        // Second use teacher.
        $this->setUser($this->users['teacher']);
        $helper->delete_page($pageid);
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
        $page = $mtmgenerator->create_page($this, ['instance' => $this->mootimeter->id]);

        $helper->store_page_detail($page->id, 'tool', self::TOOLNAME_QUIZ);
        $pagenew = $helper->get_page($page->id);
        $this->assertEquals(self::TOOLNAME_QUIZ, $pagenew->tool);
    }

    /**
     * Validate that a page belongs to an instance.
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     * @covers \mod_mootimeter\helper->validate_page_belongs_to_instance
     */
    public function test_validate_page_belongs_to_instance() {
        $this->resetAfterTest();

        $helper = new \mod_mootimeter\helper();
        $mtmgenerator = $this->getDataGenerator()->get_plugin_generator('mod_mootimeter');

        $helper = new \mod_mootimeter\helper();

        // Create a second instance.
        $mootimeter2 = $this->generator->create_module('mootimeter', ['course' => $this->course]);

        // Create a page in each instance.
        $page = $mtmgenerator->create_page($this, ['instance' => $this->mootimeter->id]);
        $page2 = $mtmgenerator->create_page($this, ['instance' => $mootimeter2->id]);

        // Get all pages of instance 1.
        $myinstancepages = $helper->get_pages($this->mootimeter->id);

        // Check if the page is / is not present in instance 1 / 2.
        $this->assertTrue($helper->validate_page_belongs_to_instance($page->id, $myinstancepages));
        $this->assertFalse($helper->validate_page_belongs_to_instance($page2->id, $myinstancepages));
    }

    /**
     * Test set / get tool config.
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     * @covers \mod_mootimeter\helper->set_tool_config
     * @covers \mod_mootimeter\helper->get_tool_config
     */
    public function test_set_get_tool_config() {
        $this->resetAfterTest();

        $helper = new \mod_mootimeter\helper();
        $mtmgenerator = $this->getDataGenerator()->get_plugin_generator('mod_mootimeter');

        $page = $mtmgenerator->create_page($this, ['instance' => $this->mootimeter->id]);

        $helper->set_tool_config($page, 'question', self::TEST_QUESTION_TITLE);

        $this->assertEquals(self::TEST_QUESTION_TITLE, \mod_mootimeter\helper::get_tool_config($page, 'question'));

        $helper->set_tool_config($page->id, 'question', self::TEST_QUESTION_TITLE . "2");
        $this->assertEquals(self::TEST_QUESTION_TITLE . "2", \mod_mootimeter\helper::get_tool_config($page->id, 'question'));
    }
}
