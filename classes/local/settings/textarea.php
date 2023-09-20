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
 * Parent class for setting definitions.
 *
 * @package     mod_mootimeter
 * @copyright   2023 Justus Dieckmann WWU
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\local\settings;

use lang_string;

/**
 * Parent class for setting definitions.
 *
 * @package     mod_mootimeter
 * @copyright   2023 Justus Dieckmann WWU
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class textarea extends setting {

    private $paramtype;

    public function __construct(string $elementname, lang_string $label, $initialvalue, $paramtype) {
        parent::__construct($elementname, $label, $initialvalue);
        $this->paramtype = $paramtype;
    }

    public function validate(&$value): ?string {
        $value = clean_param($value, $this->paramtype);
        return null;
    }

    public function get_javascript_module_name(): string {
        return 'mod_mootimeter/settings/textarea';
    }

    protected function get_custom_config(): array {
        return ['paramtype' => $this->paramtype];
    }
}
