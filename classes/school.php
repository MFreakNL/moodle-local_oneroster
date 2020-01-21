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

/**
 * Class school
 * @package local_oneroster
 * @copyright 2020 Michael Gardener <mgardener@cissq.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class school {

    /**
     * @var mixed
     */
    public $instance;

    /**
     * school constructor.
     * @param int $id DB record ID.
     * @param string $sourceid Source ID string.
     * @throws \dml_exception
     */
    public function __construct($id = 0, $sourceid = null) {
        global $DB;
        if ($id) {
            $school = $DB->get_record('local_oneroster_orgs', ['id' => $id, 'type' => 'school'], '*', MUST_EXIST);
        } else if ($sourceid) {
            $school = $DB->get_record('local_oneroster_orgs', ['sourceid' => $sourceid, 'type' => 'school'], '*', MUST_EXIST);
        } else {
            throw new \moodle_exception('invalidschoolid');
        }
        $this->instance = $school;
    }


    /**
     * @param int $districtcategoryid District category ID
     * @return bool|int
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function set_course_category($districtcategoryid) {
        global $DB;

        $sql = "SELECT cc.*
                  FROM {course_categories} cc
                  JOIN {course_categories} cc2
                    ON cc.parent = cc2.id
                 WHERE cc2.idnumber = 99";

        if (!$categoryraw =  $DB->get_record_sql($sql, null, IGNORE_MULTIPLE)) {
            return false;
        }

        $category = \core_course_category::get($categoryraw->id, MUST_EXIST, true);

        // Rename category and move it under district category.
        $data = new \stdClass();
        $data->id = $category->id;
        $data->parent = $districtcategoryid;
        $data->name = $this->instance->name;
        $data->idnumber = $this->instance->identifier;
        $data->description_editor = ['text' => '', 'format' => FORMAT_HTML];
        $category->update($data);

        $sch = new \stdClass();
        $sch->id = $this->instance->id;
        $sch->categoryid = $categoryraw->id;
        $DB->update_record('local_oneroster_orgs', $sch);
        return $category->id;
    }


    /**
     * @return array|bool
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_classes() {
        global $DB;

        $sql = "SELECT cl.id, cl.id classid, cl.sourceid, cl.title, cl.grades, co.title course, cl.levels,
                       (SELECT COUNT(1) FROM {local_oneroster_enrollments} e
                          JOIN {local_oneroster_users} u ON e.user = u.sourceid
                         WHERE e.class = cl.sourceid
                           AND e.role = 'teacher' AND e.status = 'active' 
                           AND u.enableduser = 'true' AND u.status = 'active') teachers,
                       (SELECT COUNT(1) FROM {local_oneroster_enrollments} e
                          JOIN {local_oneroster_users} u ON e.user = u.sourceid
                         WHERE e.class = cl.sourceid
                           AND e.role = 'student' AND e.status = 'active' 
                           AND u.enableduser = 'true' AND u.status = 'active') students
                  FROM {local_oneroster_classes} cl
                  JOIN {local_oneroster_courses} co
                    ON cl.course = co.sourceid
                 WHERE cl.status = 'active'
                   AND cl.school = ?
              ORDER BY cl.title ASC";

        if (!$classes =  $DB->get_records_sql($sql, [$this->instance->sourceid])) {
            return false;
        }

        foreach ($classes as $index => $class) {
            $class->status = get_string($this->get_class_status($class->id), 'local_oneroster');
            $classes[$index] = $class;
        }
        return $classes;
    }

    /**
     * @param $classid
     * @return string
     * @throws \dml_exception
     */
    public function get_class_status($classid) {
        global $DB;

        $numbeofprocessed = $DB->count_records('local_oneroster_class_enroll', ['classid' => $classid, 'processed' => '1']);
        $numbeofunprocessed = $DB->count_records('local_oneroster_class_enroll', ['classid' => $classid, 'processed' => '0']);

        if ($numbeofprocessed === 0 && $numbeofunprocessed === 0) {
            return 'unsynced';
        } else if ($numbeofprocessed === 0 && $numbeofunprocessed > 0) {
            return 'unsynced';
        } else if ($numbeofprocessed > 0 && $numbeofunprocessed > 0) {
            return 'partial';
        } else {
            return 'complete';
        }
    }
}