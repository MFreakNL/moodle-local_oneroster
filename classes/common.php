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

namespace local_oneroster;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

/**
 *
 * This is a class containing constants and static functions for general use around the plugin
 *
 * @package     local_oneroster
 * @copyright   2019 Michael Gardener <mgardener@cissq.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class common {

    public static function sync_districts() {
        global $DB;

        $client = new client();

        $limit = client::RECORD_LIMIT;
        $page = 0;

        do {
            $districts = $client->send_request('/orgs', $page*$limit);
            $page++;
            if (!empty($districts)) {
                foreach ($districts['orgs'] as $district) {
                    $rec = new \stdClass();

                    if (isset($district['identifier'])) {
                        $rec->identifier = $district['identifier'];
                    } else {
                        $rec->identifier = '';
                    }

                    $rec->name = $district['name'];
                    $rec->type = $district['type'];
                    $rec->status = $district['status'];
                    $rec->datelastmodified = $district['dateLastModified'];

                    if (isset($district['parent']['sourcedId'])) {
                        $rec->parent = $district['parent']['sourcedId'];
                    } else {
                        $rec->parent = '';
                    }

                    if ($update = $DB->get_record('local_oneroster_orgs', ['sourceid' => $district['sourcedId']])) {
                        if ($update->datelastmodified != $district['dateLastModified']) {
                            $rec->id = $update->id;
                            $rec->timemodified = time();
                            $DB->update_record('local_oneroster_orgs', $rec);
                        }
                    } else {
                        $rec->sourceid = $district['sourcedId'];
                        $rec->timecreated = $rec->timemodified = time();
                        $DB->insert_record('local_oneroster_orgs', $rec);
                    }
                }
            }
        } while (isset($districts['orgs']) && count($districts['orgs']));
    }

    public static function sync_classes() {
        global $DB;

        $client = new client();

        $limit = client::RECORD_LIMIT;
        $page = 0;

        do {
            $classes = $client->send_request('/classes', $page * $limit);
            $page++;
            if (!empty($classes)) {
                foreach ($classes['classes'] as $class) {
                    $rec = new \stdClass();

                    $rec->status = $class['status'];
                    $rec->datelastmodified = $class['dateLastModified'];
                    $rec->title = $class['title'];
                    $rec->classcode = $class['classCode'];
                    $rec->classtype = $class['classType'];
                    $rec->location = $class['location'];
                    $rec->grades = implode(',', $class['grades']);
                    $rec->subjects = implode(',', $class['subjects']);
                    $rec->course = $class['course']['sourcedId'];
                    $rec->school = $class['school']['sourcedId'];
                    $rec->periods = implode(',', $class['periods']);

                    if ($update = $DB->get_record('local_oneroster_classes', ['sourceid' => $class['sourcedId']])) {
                        if ($update->datelastmodified != $class['dateLastModified']) {
                            $rec->id = $update->id;
                            $rec->timemodified = time();
                            $DB->update_record('local_oneroster_classes', $rec);
                        }
                    } else {
                        $rec->sourceid = $class['sourcedId'];
                        $rec->timecreated = $rec->timemodified = time();
                        $DB->insert_record('local_oneroster_classes', $rec);
                    }
                }
            }
        } while (isset($classes['classes']) && count($classes['classes']));
    }

    public static function sync_courses() {
        global $DB;

        $client = new client();

        $limit = client::RECORD_LIMIT;
        $page = 0;

        do {
            $courses = $client->send_request('/courses', $page * $limit);
            $page++;
            if ($courses) {
                foreach ($courses['courses'] as $course) {
                    $rec = new \stdClass();

                    $rec->status = $course['status'];
                    $rec->datelastmodified = $course['dateLastModified'];
                    $rec->title = $course['title'];
                    $rec->coursecode = $course['courseCode'];
                    $rec->grades = implode(',', $course['grades']);
                    $rec->subjects = implode(',', $course['subjects']);
                    $rec->org = $course['org']['sourcedId'];

                    if ($update = $DB->get_record('local_oneroster_courses', ['sourceid' => $course['sourcedId']])) {
                        if ($update->datelastmodified != $course['dateLastModified']) {
                            $rec->id = $update->id;
                            $rec->timemodified = time();
                            $DB->update_record('local_oneroster_courses', $rec);
                        }
                    } else {
                        $rec->sourceid = $course['sourcedId'];
                        $rec->timecreated = $rec->timemodified = time();
                        $DB->insert_record('local_oneroster_courses', $rec);
                    }
                }
            }
        } while (isset($courses['courses']) && count($courses['courses']));
    }

    public static function sync_enrollments() {
        global $DB;

        $client = new client();

        $limit = client::RECORD_LIMIT;
        $page = 0;

        do {
            $enrollments = $client->send_request('/enrollments', $page * $limit);
            $page++;
            if ($enrollments) {
                foreach ($enrollments['enrollments'] as $enrollment) {
                    $rec = new \stdClass();

                    $rec->status = $enrollment['status'];
                    $rec->datelastmodified = $enrollment['dateLastModified'];
                    $rec->role = $enrollment['role'];
                    $rec->user = $enrollment['user']['sourcedId'];
                    $rec->class = $enrollment['class']['sourcedId'];
                    $rec->school = $enrollment['school']['sourcedId'];
                    $rec->begindate = $enrollment['beginDate'];
                    $rec->enddate = $enrollment['endDate'];

                    if ($update = $DB->get_record('local_oneroster_enrollments', ['sourceid' => $enrollment['sourcedId']])) {
                        if ($update->datelastmodified != $enrollment['dateLastModified']) {
                            $rec->id = $update->id;
                            $rec->timemodified = time();
                            $DB->update_record('local_oneroster_enrollments', $rec);
                        }
                    } else {
                        $rec->sourceid = $enrollment['sourcedId'];
                        $rec->timecreated = $rec->timemodified = time();
                        $DB->insert_record('local_oneroster_enrollments', $rec);
                    }
                }
            }
        } while (isset($enrollments['enrollments']) && count($enrollments['enrollments']));
    }

    public static function sync_users() {
        global $DB;

        $client = new client();

        $limit = client::RECORD_LIMIT;
        $page = 0;

        do {
            $users = $client->send_request('/users', $page * $limit);
            $page++;
            if ($users) {
                foreach ($users['users'] as $user) {
                    $rec = new \stdClass();

                    $rec->status = $user['status'];
                    $rec->datelastmodified = $user['dateLastModified'];
                    $rec->username = $user['username'];
                    $rec->enableduser = $user['enabledUser'];
                    $rec->givenname = $user['givenName'];
                    $rec->familyname = $user['familyName'];
                    $rec->middlename = (isset($user['middleName'])) ? $user['middleName'] : '';
                    $rec->role = $user['role'];
                    $rec->identifier = $user['identifier'];
                    $rec->email = $user['email'];
                    $rec->sms = $user['sms'];
                    $rec->phone = $user['phone'];

                    if (!empty($user['orgs'])) {
                        $orgs = [];
                        foreach ($user['orgs'] as $org) {
                            $orgs[] = $org['sourcedId'];
                        }
                        $rec->orgs = implode(',', $orgs);
                    }

                    if (!empty($user['grades'])) {
                        $rec->grades = implode(',', $user['grades']);
                    }

                    if ($update = $DB->get_record('local_oneroster_users', ['sourceid' => $user['sourcedId']])) {
                        if ($update->datelastmodified != $user['dateLastModified']) {
                            $rec->id = $update->id;
                            $rec->timemodified = time();
                            $DB->update_record('local_oneroster_users', $rec);
                        }
                    } else {
                        $rec->userid = self::create_user($rec);
                        $rec->sourceid = $user['sourcedId'];
                        $rec->timecreated = $rec->timemodified = time();
                        $DB->insert_record('local_oneroster_users', $rec);
                    }
                }
            }
        } while (isset($users['users']) && count($users['users']));
    }

    public static function get_districts() {
        global $DB;

        return $DB->get_records('local_oneroster_orgs', ['type' => 'district', 'status' => 'active'], 'name');
    }

    public static function create_user($rawuserdata) {
        global $CFG;

        $availableauths  = \core_component::get_plugin_list('auth');
        if (isset($availableauths['oauth2'])) {
            $auth = 'oauth2';
        } else {
            $auth = 'manual';
        }

        $rawuserdata->username = strtolower($rawuserdata->username);
        $rawuserdata->email = strtolower($rawuserdata->email);

        if ($systemuser = \core_user::get_user_by_username($rawuserdata->username)) {
            return $systemuser->id;
        }

        $user = new \stdClass();
        $user->auth = $auth;
        $user->confirmed = 1;
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->username = $rawuserdata->username;
        $user->password = generate_password(8);
        $user->idnumber = $rawuserdata->identifier;
        $user->firstname = $rawuserdata->givenname;
        $user->lastname = $rawuserdata->familyname;
        $user->middlename = $rawuserdata->middlename;
        $user->email = $rawuserdata->email;

        return user_create_user($user, true, false);
    }
}
