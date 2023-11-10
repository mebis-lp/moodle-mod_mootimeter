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
 * The mod_mootimeter helper class for mootimeterstate.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\local;

/**
 * The mod_mootimeter helper class for mootimeterstate.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mootimeterstate {

    /** @var array of config. */
    public $mootimeterstate = [];

    /**
     * Get instance of mootimeterstate.
     * @return mootimeterstate
     */
    public static function get_instance(): object {
        static $helper;

        // Force new instance while executing unit test as config may have
        // changed in various testcases.
        $forcenewinstance = (defined('PHPUNIT_TEST') && PHPUNIT_TEST);

        if (isset($helper) && !$forcenewinstance) {
            return $helper;
        }

        $helper = new mootimeterstate();
        return $helper;
    }

    /**
     * Add a state to mootimieterstate
     * @param string $paramname
     * @param string $paramvalue
     * @return void
     */
    public static function add_mootimeterstate(string $paramname, string $paramvalue): void {
        $stateinstanc = self::get_instance();
        $stateinstanc->mootimeterstate["data-" . $paramname] = $paramvalue;
    }

    /**
     * Get the mootimeterstate.
     * @param string $paramname
     * @return string
     */
    public static function get_mootimeterstate(string $paramname): string {
        $stateinstanc = self::get_instance();
        return $stateinstanc->mootimeterstate["data-" . $paramname];
    }

    /**
     * Remove the mootimeterstate.
     * @param string $paramname
     * @return void
     */
    public static function remove_mootimeterstate(string $paramname): void {
        $stateinstanc = self::get_instance();
        unset($stateinstanc->mootimeterstate["data-" . $paramname]);
    }

    /**
     * Get the renderable string for mootimeter state dataset.
     *
     * @return string
     */
    public static function get_mootimeterstate_renderable(): string {
        $stateinstanc = self::get_instance();

        $datasetarray = [];
        foreach ($stateinstanc->mootimeterstate as $name => $value) {
            $datasetarray[] = $name . '="' . $value . '"';
        }
        return join(' ', $datasetarray);
    }
}
