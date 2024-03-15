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
 * Plugin administration pages are defined here.
 *
 * @package     mod_mootimeter
 * @category    admin
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('mod_mootimeter_settings', new lang_string('pluginname', 'mod_mootimeter'));

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configtext(
            'mod_mootimeter/refreshinterval',
            get_string('refreshinterval', 'mod_mootimeter'),
            get_string('refreshinterval_desc', 'mod_mootimeter'),
            1000,
            PARAM_INT
        ));

        $settings->add(new admin_setting_configcheckbox(
            'mod_mootimeter/default_new_page_visibility',
            get_string('default_new_page_visibility', 'mod_mootimeter'),
            get_string('default_new_page_visibility_desc', 'mod_mootimeter'),
            0
        ));

    }
}
