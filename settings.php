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
 * Plugin administration pages are defined here.
 *
 * @package     local_oneroster
 * @category    admin
 * @copyright   2019 Michael Gardener <mgardener@cissq.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_oneroster',
        get_string('pluginname', 'local_oneroster')
    );

    $settings->add(
        new admin_setting_configtext('local_oneroster/baseurl',
            get_string('baseurl', 'local_oneroster'), '', '', PARAM_URL
        )
    );

    $settings->add(
        new admin_setting_configpasswordunmask('local_oneroster/clientid',
            get_string('clientid', 'local_oneroster'), '', ''
        )
    );
    $settings->add(
        new admin_setting_configpasswordunmask('local_oneroster/secretkey',
            get_string('secretkey', 'local_oneroster'), '', ''
        )
    );

    $settings->add(
        new admin_setting_heading('local_oneroster/roleheading',
            get_string('roles', 'local_oneroster'), ''
        )
    );

    $roles = get_assignable_roles(context_course::instance(SITEID));

    $settings->add(
        new admin_setting_configselect('local_oneroster/studentrole',
            get_string('studentrole', 'local_oneroster'), '', '5', $roles
        )
    );
    $settings->add(
        new admin_setting_configselect('local_oneroster/teacherrole',
            get_string('teacherrole', 'local_oneroster'), '', '4', $roles
        )
    );

    $ADMIN->add('localplugins', $settings);
}