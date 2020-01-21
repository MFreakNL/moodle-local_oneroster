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
 * External functions and service definitions.
 * @package     local_oneroster
 * @copyright   2019 Michael Gardener <mgardener@cissq.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(
    'local_oneroster_get_external_classes' => array(
        'classname' => 'local_oneroster\external',
        'methodname' => 'get_external_classes',
        'classpath' => '',
        'description' => '',
        'type' => 'read',
        'ajax' => true
    ),
    'local_oneroster_create_school' => array(
        'classname' => 'local_oneroster\external',
        'methodname' => 'create_school',
        'classpath' => '',
        'description' => '',
        'type' => 'read',
        'ajax' => true
    ),
    'local_oneroster_create_level' => array(
        'classname' => 'local_oneroster\external',
        'methodname' => 'create_level',
        'classpath' => '',
        'description' => '',
        'type' => 'read',
        'ajax' => true
    ),
    'local_oneroster_sync_members' => array(
        'classname' => 'local_oneroster\external',
        'methodname' => 'sync_members',
        'classpath' => '',
        'description' => '',
        'type' => 'read',
        'ajax' => true
    ),
);