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
 * Dashboard
 *
 * @package   local_oneroster
 * @copyright   2019 Michael Gardener <mgardener@cissq.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oneroster\output;

use block_readseedteacher\common;
use local_oneroster\district;
use local_oneroster\school;

defined('MOODLE_INTERNAL') || die();

/**
 * Class to help display the readseed student block
 *
 * @package   local_oneroster
 * @copyright   2019 Michael Gardener <mgardener@cissq.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard implements \renderable, \templatable {

    /**
     * Export dashboard contents for template
     *
     * @param \renderer_base $output
     * @return array|mixed|\stdClass
     */
    public function export_for_template(\renderer_base $output) {
        global $CFG, $OUTPUT;

        $districts = \local_oneroster\common::get_districts();
        foreach ($districts as $key => $district) {
            $districtobj = new district($district->id);
            $schools = $districtobj->get_schools();
            if ($schools) {
                $district->selected = true;
                $districts[$key] = $district;
                $data['schools'] = array_values($schools);
                $data['numberofschools'] = count($data['schools']);
                break;
            }
        }
        if (!empty($data['schools'])) {
            $data['schools'][0]->selected = true;
            $school = new school($data['schools'][0]->id);
            $classes = $school->get_classes();
            $data['schoolname'] = $data['schools'][0]->name;
            $data['schoolcreated'] = $data['schools'][0]->categoryid;
            $data['classes'] = array_values($classes);
            $data['numberofclasses'] = count($data['classes']);
        }

        $data['districts'] = array_values($districts);
        $data['numberofdistricts'] = count($data['districts']);
        $data['districtname'] = (isset($data['districts'][0])) ? ($data['districts'][0])->name : '';

        return $data;
    }
}