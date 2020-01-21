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
 * External API
 *
 * @package     local_oneroster
 * @copyright   2019 Michael Gardener <mgardener@cissq.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oneroster;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/cohort/lib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_format_value;
use external_single_structure;
use external_multiple_structure;
use core_course\external\course_summary_exporter;
use context_user;
use context_course;
use context_helper;

/**
 * External functions
 *
 * @package     local_oneroster
 * @copyright   2019 Michael Gardener <mgardener@cissq.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends \external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_external_classes_parameters() {
        return new external_function_parameters(
            [
                'schoolid' => new external_value(PARAM_INT, 'School ID', VALUE_DEFAULT, 0)
            ]
        );
    }

    public static function get_external_classes($schoolid = 0) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/course/lib.php');

        $params = self::validate_parameters(self::get_external_classes_parameters(),
            ['schoolid' => $schoolid]
        );

        $schoolid = $params['schoolid'];

        $context = context_user::instance($USER->id);
        self::validate_context($context);
        require_capability('local/oneroster:manage', $context);

        $school = new school($schoolid);
        $classes = $school->get_classes();

        return [
            'schoolname' => $school->instance->name,
            'classes' => $classes,
            'numberofclasses' => count($classes)
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_external_classes_returns() {
        return new external_single_structure(
            array(
                'schoolname' => new external_value(PARAM_TEXT, 'School name'),
                'classes' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Class ID'),
                            'classid' => new external_value(PARAM_TEXT, 'Class ID - CourseID'),
                            'sourceid' => new external_value(PARAM_TEXT, 'External source ID'),
                            'title' => new external_value(PARAM_TEXT, 'Title', VALUE_OPTIONAL),
                            'grades' => new external_value(PARAM_TEXT, 'Comma separated grade list', VALUE_OPTIONAL),
                            'course' => new external_value(PARAM_TEXT, 'Course name', VALUE_OPTIONAL),
                            'levels' => new external_value(PARAM_TEXT, 'Levels', VALUE_OPTIONAL),
                            'status' => new external_value(PARAM_TEXT, 'Sync status', VALUE_OPTIONAL),
                            'teachers' => new external_value(PARAM_INT, 'Number of teachers', VALUE_OPTIONAL),
                            'students' => new external_value(PARAM_INT, 'Number of students', VALUE_OPTIONAL),
                        )
                    )
                ),
                'numberofclasses' => new external_value(PARAM_INT, 'Number of classes')
            )
        );
    }


    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function create_school_parameters() {
        return new external_function_parameters(
            [
                'schoolid' => new external_value(PARAM_INT, 'School ID', VALUE_DEFAULT, 0)
            ]
        );
    }

    public static function create_school($schoolid = 0) {
        global $DB, $CFG, $PAGE, $USER;
        require_once($CFG->dirroot . '/course/lib.php');

        $params = self::validate_parameters(
            self::create_school_parameters(), ['schoolid' => $schoolid]
        );

        $schoolid = $params['schoolid'];

        $context = context_user::instance($USER->id);
        self::validate_context($context);
        require_capability('local/oneroster:manage', $context);

        $school = new school($schoolid);

        if ($school->instance->categoryid) {
            return ['categoryid' => 0, 'message' => get_string('alreadycreated', 'local_oneroster')];
        }

        $district = new district(0, $school->instance->parent);
        $districtcategory = $district->get_course_category();

        $categoryid = $school->set_course_category($districtcategory->id);
        if ($categoryid === false) {
            return ['categoryid' => 0, 'message' => get_string('noavailablecategory', 'local_oneroster')];
        }

        return ['categoryid' => $categoryid, 'message' => ''];
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function create_school_returns() {
        return new external_single_structure(
            array(
                'categoryid' => new external_value(PARAM_INT, 'Course category ID if the school is created, 0 if not'),
                'message' => new external_value(PARAM_TEXT, 'Message'),
            )
        );
    }


    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function create_level_parameters() {
        return new external_function_parameters(
            [
                'classids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Class ID'),
                    'List of Class IDs'
                ),
                'levels' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Level'),
                    'List of class levels'
                ),
                'unenroll' => new external_value(PARAM_BOOL, 'Unenroll if it is true', VALUE_DEFAULT, false),
            ]
        );
    }

    public static function create_level($classids, $levels, $unenroll = false) {
        global $DB, $USER;

        $params = self::validate_parameters(
            self::create_level_parameters(), ['classids' => $classids, 'levels' => $levels, 'unenroll' => $unenroll]
        );

        $context = context_user::instance($USER->id);
        self::validate_context($context);
        require_capability('local/oneroster:manage', $context);

        foreach ($params['classids'] as $classid) {

            $class = $DB->get_record('local_oneroster_classes', ['id' => $classid],'*', MUST_EXIST);
            $school = new school(0, $class->school);

            if (empty($school->instance->categoryid)) {
                return false;
            }

            $classlevels = [];
            if ($class->levels) {
                foreach (explode(',', $class->levels) as $item) {
                    $classlevels[$item] = $item;
                }
            }

            foreach ($params['levels'] as $level) {
                $levelenrollment = $DB->get_record('local_oneroster_class_enroll', ['classid' => $classid, 'level' => $level]);

                // Cannot change unprocessed tasks.
                if ($levelenrollment && !$levelenrollment->processed) {
                    continue;
                }

                $action = ($params['unenroll']) ? 'unenroll' : 'enroll';

                if (!$levelenrollment) {
                    $rec = new \stdClass();
                    $rec->classid = $classid;
                    $rec->level = $level;
                    $rec->action = $action;
                    $rec->processed = 0;
                    $rec->timecreated = time();
                    $DB->insert_record('local_oneroster_class_enroll', $rec);
                } else {
                    $rec = new \stdClass();
                    $rec->id = $levelenrollment->id;
                    $rec->action = $action;
                    $rec->processed = 0;
                    $DB->update_record('local_oneroster_class_enroll', $rec);
                }

                if ($params['unenroll']) {
                    if (in_array($level, $classlevels)) {
                        unset($classlevels[$level]);
                    }
                } else {
                    $classlevels[$level] = $level;
                }
            }

            if (!empty($classlevels)) {
                sort($classlevels);
            }

            $rec = new \stdClass();
            $rec->id = $class->id;
            $rec->levels = (!empty($classlevels)) ? implode(',', $classlevels) : '';
            $DB->update_record('local_oneroster_classes', $rec);
        }

        return true;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function create_level_returns() {
        return new external_value(PARAM_BOOL, 'True if successfull');
    }


    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function sync_members_parameters() {
        return new external_function_parameters(
            [
                'schoolid' => new external_value(PARAM_INT, 'School ID', VALUE_DEFAULT, 0)
            ]
        );
    }

    public static function sync_members($schoolid) {
        global $DB, $USER;

        $params = self::validate_parameters(
            self::sync_members_parameters(), ['schoolid' => $schoolid]
        );

        $context = context_user::instance($USER->id);
        self::validate_context($context);
        require_capability('local/oneroster:manage', $context);

        $school = new school($params['schoolid']);

        $sql = "SELECT e.id, e.classid, e.level, e.action, e.groupid, e.courseid, 
                       e.studentenrolid, e.teacherenrolid, c.studentcohortid,
                       c.teachercohortid, c.levels
                  FROM {local_oneroster_orgs} s
                  JOIN {local_oneroster_classes} c
                    ON s.sourceid = c.school
                  JOIN {local_oneroster_class_enroll} e
                    ON c.id = e.classid
                 WHERE s.id = ?
                   AND e.processed = 1";

        if (!$tasks = $DB->get_records_sql($sql, [$school->instance->id])) {
            return true;
        }

        foreach ($tasks as $task) {
            foreach (['student', 'teacher'] as $role) {
                if (empty($task->{$role . 'cohortid'})) {
                    continue;
                }

                $cohort = $DB->get_record('cohort', ['id' => $task->{$role . 'cohortid'}], '*', MUST_EXIST);

                // Class users.
                $sql = "SELECT u.userid
                          FROM {local_oneroster_classes} c
                          JOIN {local_oneroster_enrollments} e
                            ON c.sourceid = e.class
                          JOIN {local_oneroster_users} u
                            ON e.user = u.sourceid
                         WHERE c.id = ?
                           AND e.role = ?
                           AND e.status = 'active'
                           AND u.status = 'active'";

                $users = $DB->get_records_sql($sql, [$task->classid, $role]);

                // Cohort users.
                $sql = 'SELECT cm.userid FROM {cohort} c JOIN {cohort_members} cm ON c.id = cm.cohortid WHERE c.id = ?';
                $cohortusers = $DB->get_records_sql($sql, [$cohort->id]);

                $newusers = array_diff(array_keys($users), array_keys($cohortusers));
                $removedusers = array_diff(array_keys($cohortusers), array_keys($users));

                if ($task->action == 'enroll') {
                    // Enroll new users.
                    if ($newusers) {
                        foreach ($newusers as $newuser) {
                            cohort_add_member($cohort->id, $newuser);
                        }
                    }
                    // Unenroll removed users.
                    if ($removedusers) {
                        foreach ($removedusers as $removeduser) {
                            cohort_remove_member($cohort->id, $removeduser);
                        }
                    }
                } else if ($task->action == 'uenroll') {
                    if ($cohortusers) {
                        foreach ($cohortusers as $cohortuser) {
                            cohort_remove_member($cohort->id, $cohortuser->userid);
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function sync_members_returns() {
        return new external_value(PARAM_BOOL, 'True if synced correctly');
    }
}