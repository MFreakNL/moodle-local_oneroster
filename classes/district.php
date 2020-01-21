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
 * Class district
 * @package local_oneroster
 * @copyright 2020 Michael Gardener <mgardener@cissq.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class district {
    /**
     * @var
     */
    public $instance;


    /**
     * district constructor.
     * @param int $id DB record ID.
     * @param string $sourceid Source ID string.
     * @throws \dml_exception
     */
    public function __construct($id = 0, $sourceid = null) {
        global $DB;
        if ($id) {
            $district = $DB->get_record('local_oneroster_orgs', ['id' => $id, 'type' => 'district'], '*', MUST_EXIST);
        } else if ($sourceid) {
            $district = $DB->get_record('local_oneroster_orgs', ['sourceid' => $sourceid, 'type' => 'district'], '*', MUST_EXIST);
        } else {
            throw new \moodle_exception('invaliddistrictid');
        }
        $this->instance = $district;
    }

    /**
     * @return \core_course_category|null
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_course_category() {
        global $DB;

        if ($this->instance->categoryid) {
            $category = \core_course_category::get($this->instance->categoryid, MUST_EXIST, true);
        } else {
            $data = new \stdClass();
            $data->id = 0;
            $data->parent = 0;
            $data->name = $this->instance->name;
            $data->idnumber = $this->instance->sourceid;
            $data->description_editor = ['text' => '', 'format' => FORMAT_HTML];
            $category = \core_course_category::create($data);

            $rec = new \stdClass();
            $rec->id = $this->instance->id;
            $rec->categoryid = $category->id;
            $DB->update_record('local_oneroster_orgs', $rec);
        }

        return $category;
    }

    /**
     * @return array
     * @throws \dml_exception
     */
    public function get_schools() {
        global $DB;

        return $DB->get_records('local_oneroster_orgs',
            ['type' => 'school', 'status' => 'active', 'parent' => $this->instance->sourceid], 'name');
    }
}