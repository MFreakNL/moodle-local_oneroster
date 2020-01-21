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
 * Oneroster Dashboard
 *
 * @package     local_oneroster
 * @copyright   2019 Michael Gardener <mgardener@cissq.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

$context = context_system::instance();

// Security.
require_capability('local/oneroster:manage', $context);

$thispageurl = new moodle_url('/local/oneroster/dashboard.php');
$redirecturl = new moodle_url('/local/oneroster/dashboard.php');

$PAGE->set_context($context);
$PAGE->set_url($thispageurl);
$PAGE->set_pagelayout('admin');

$title = get_string('readseedrs', 'local_oneroster');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->requires->js_call_amd('local_oneroster/dashboard', 'init');

$PAGE->navbar->add($title);

$renderer = $PAGE->get_renderer('local_oneroster');
$dashboard = new \local_oneroster\output\dashboard();

echo $OUTPUT->header();
echo $renderer->render($dashboard);
echo $OUTPUT->footer();