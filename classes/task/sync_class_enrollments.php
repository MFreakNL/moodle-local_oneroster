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
 * Used to check for users that need to recomple.
 *
 * @package     local_oneroster
 * @copyright   2019 Michael Gardener <mgardener@cissq.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oneroster\task;

use local_oneroster\common;
use local_oneroster\helper;
use local_oneroster\school;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * Sync districts.
 *
 * @package     local_oneroster
 * @copyright   2019 Michael Gardener <mgardener@cissq.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_class_enrollments extends \core\task\scheduled_task {

    /**
     * Returns the name of this task.
     */
    public function get_name() {
        // Shown in admin screens.
        return get_string('syncclassenrollments', 'local_oneroster');
    }

    /**
     * Execute task.
     */
    public function execute() {
        global $DB;

        if (!$enrollmenttasks = $DB->get_records('local_oneroster_class_enroll', ['processed' => 0], '', '*', 0, 25)) {
            return true;
        }

        $cfg = get_config('local_oneroster ');

        foreach ($enrollmenttasks as $enrollmenttask) {

            $class = $DB->get_record('local_oneroster_classes', ['id' => $enrollmenttask->classid],'*', MUST_EXIST);
            $school = new school(0, $class->school);
            if (empty($school->instance->categoryid)) {
                $this->class_enrollment_task_completed($enrollmenttask);
                continue;
            }
            $contextcoursecat = \context_coursecat::instance($school->instance->categoryid, MUST_EXIST);

            if ($enrollmenttask->action == 'enroll') {
                $course = $this->get_level_course($enrollmenttask, $school);
                $group = $this->get_level_group($enrollmenttask, $course, $class);

                foreach (['student', 'teacher'] as $role) {
                    $cohort = 'cohort'.$role;
                    if (empty($class->{$role.'cohortid'})) {
                        $$cohort = new \stdClass();
                        $$cohort->name = get_string($role.'cohortname', 'local_oneroster', $class->title);
                        $$cohort->idnumber = $class->classcode;
                        $$cohort->contextid = $contextcoursecat->id;
                        $$cohort->component = 'local_oneroster';
                        $$cohort->id = cohort_add_cohort($$cohort);

                        $rec = new \stdClass();
                        $rec->id = $class->id;
                        $rec->{$role.'cohortid'} = $$cohort->id;
                        $DB->update_record('local_oneroster_classes', $rec);
                    } else {
                        $$cohort = $DB->get_record('cohort', ['id' => $class->{$role.'cohortid'}], '*', MUST_EXIST);
                    }

                    $enrol = $role.'enrol';
                    if (empty($enrollmenttask->{$role.'enrolid'})) {
                        $plugin = enrol_get_plugin('cohort');
                        if (!$plugin) {
                            throw new moodle_exception('invaliddata', 'error');
                        }

                        $data = new \stdClass();
                        $data->name = get_string($role.'cohortname', 'local_oneroster', $class->title);
                        $data->roleid = $cfg->{$role.'role'};
                        $data->customint1 = $$cohort->id;
                        $data->customint2 = $group->id;

                        $fields = (array) $data;
                        $enrolid = $plugin->add_instance($course, $fields);

                        $rec = new \stdClass();
                        $rec->id = $enrollmenttask->id;
                        $rec->{$role.'enrolid'} = $enrolid;
                        $DB->update_record('local_oneroster_class_enroll', $rec);
                    } else {
                        $$enrol = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'cohort', 'id' => $enrollmenttask->{$role.'enrolid'}), '*', MUST_EXIST);
                    }

                    // Add users to cohort.
                    $sql = "SELECT u.userid
                              FROM {local_oneroster_classes} c
                              JOIN {local_oneroster_enrollments} e
                                ON c.sourceid = e.class
                              JOIN {local_oneroster_users} u
                                ON e.user = u.sourceid
                             WHERE c.id = ?
                               AND e.role = ?";

                    if ($users = $DB->get_records_sql($sql, [$class->id, $role])) {
                        foreach ($users as $user) {
                            cohort_add_member($$cohort->id, $user->userid);
                        }
                    }
                }
            } else if ($enrollmenttask->action == 'unenroll') {
                $plugin = enrol_get_plugin('cohort');
                $rec = new \stdClass();
                $rec->id = $enrollmenttask->id;
                foreach (['student', 'teacher'] as $role) {
                    if (!empty($enrollmenttask->{$role.'enrolid'})) {
                        $instance = $DB->get_record('enrol', ['id' => $enrollmenttask->{$role.'enrolid'}]);
                        $plugin->delete_instance($instance);
                        $rec->{$role.'enrolid'} = 0;
                    }
                }
                $DB->update_record('local_oneroster_class_enroll', $rec);
            }

            $this->class_enrollment_task_completed($enrollmenttask);
        }
        return true;
    }

    /**
     * @param $enrollmenttask
     * @return bool
     * @throws \dml_exception
     */
    public function class_enrollment_task_completed($enrollmenttask) {
        global $DB;
        $rec = new \stdClass();
        $rec->id = $enrollmenttask->id;
        $rec->processed = 1;
        return $DB->update_record('local_oneroster_class_enroll', $rec);
    }

    /**
     * @param $enrollmenttask
     * @param $school
     * @return mixed
     * @throws \dml_exception
     */
    public function get_level_course($enrollmenttask, $school) {
        global $DB;

        if (empty($enrollmenttask->courseid)) {
            $course = $DB->get_record('course', ['category' => $school->instance->categoryid, 'shortname' => 'Level'.$enrollmenttask->level], '*', MUST_EXIST);

            $rec = new \stdClass();
            $rec->id = $enrollmenttask->id;
            $rec->courseid = $course->id;
            $DB->update_record('local_oneroster_class_enroll', $rec);
        } else {
            $course = $DB->get_record('course', ['id' => $enrollmenttask->courseid], '*', MUST_EXIST);
        }

        return $course;
    }

    /**
     * @param $enrollmenttask
     * @param $course
     * @param $class
     * @return mixed|\stdClass
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_level_group($enrollmenttask, $course, $class) {
        global $DB;

        if (empty($enrollmenttask->groupid)) {
            $group = new \stdClass();
            $group->courseid = $course->id;
            $group->name = $class->title;
            $group->idnumber = $class->classcode;
            $group->id = groups_create_group($group);

            $rec = new \stdClass();
            $rec->id = $enrollmenttask->id;
            $rec->groupid = $group->id;
            $DB->update_record('local_oneroster_class_enroll', $rec);
        } else {
            $group = $DB->get_record('groups', ['id' => $enrollmenttask->groupid], '*', MUST_EXIST);
        }

        return $group;
    }
}