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

use help_icon;
use lang_string;

/**
 * Parent class for setting definitions.
 *
 * @package     mod_mootimeter
 * @copyright   2023 Justus Dieckmann WWU
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class setting {

    protected ?help_icon $help;

    public function __construct(protected string $elementname, protected lang_string $label, protected $initialvalue) {
    }

    abstract public function validate(&$value): ?string;

    abstract public function get_javascript_module_name(): string;

    abstract protected function get_custom_config(): array;

    public function get_config(): array {
        global $PAGE;
        $renderer = $PAGE->get_renderer('core');

        return ['module' => $this->get_javascript_module_name(),
                'config' => array_merge([
                        'elementname' => $this->elementname,
                        'id' => "id_$this->elementname",
                        'label' => $this->label->out(),
                        'value' => $this->initialvalue,
                        'help' => $this->help?->export_for_template($renderer)
                ], $this->get_custom_config())
        ];
    }

    public function set_help(string $identifier, string $component) {
        $this->help = new help_icon($identifier, $component);
    }

}
