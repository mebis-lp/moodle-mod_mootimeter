# Mootimeter #

TODO Describe the plugin shortly here.

TODO Provide more detailed description here.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/mod/mootimeter

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.


## Changes to styles.scss and files in scss/* ##

If you want to change styles, you need to compile the scss files into css first.
For doing so, just run `npm install` once after cloning the repository.

Whenever you apply changes to `styles.scss` or any file in `scss/` directory, you need
to run `npm run compile-css` in the plugin root directory. This creates the minified `styles.css`
file which is being read and used by moodle.


## License ##

2023, ISB Bayern <peter.mayer@isb.bayern.de>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
