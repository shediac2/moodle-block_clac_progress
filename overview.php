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
 * Clac Progress block overview page
 *
 * @package    block_clac_progress
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include required files.
//defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../config.php');
require_login();
require_once($CFG->dirroot.'/local/clac/lib.php');
require_once($CFG->dirroot.'/blocks/clac_progress/locallib.php');

//use block_clac_progress\completion_progress;
// Gather form data.

$id       = required_param('instanceid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$role     = optional_param('role', 0, PARAM_INT);
$sendemail = optional_param('sendemail', 0, PARAM_INT);
// Determine course and context.
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

// Get specific block config and context.
$block = $DB->get_record('block_instances', array('id' => $id), '*', MUST_EXIST);
$blockcontext = context_block::instance($id);

// Set up page parameters.
$PAGE->set_course($course);
$PAGE->set_url(
    '/blocks/clac_progress/overview.php',
    array(
        'instanceid' => $id,
        'courseid'   => $courseid,
        'role'       => $role,
    )
);
$PAGE->set_context($context);
$title = get_string('overview', 'block_clac_progress');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);
$PAGE->set_pagelayout('report');

require_capability('block/clac_progress:overview', $blockcontext);

$roleoptions = block_clac_progress_student_list();

$output = $PAGE->get_renderer('block_clac_progress');

// Start page output.
echo $output->header();
echo $output->container_start('block_clac_progress');

echo $output->container_start('progressoverviewmenus');
if ($roleoptions) {
    echo $output->single_select($PAGE->url, 'role', $roleoptions, $role,
        ['' => 'choosedots'], null, ['label' => get_string('students')]);
}
if ($role > 0) {
    $formattributes = array('action' => $CFG->wwwroot.'/blocks/clac_progress/overview.php', 'method' => 'post',
                           'id' => 'participantsform');
    echo html_writer::start_tag('form', $formattributes);
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'role', 'value' => $role ));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'instanceid', 'value' => $id ));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'courseid', 'value' => $courseid ));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sendemail', 'value' => '1' ));

    echo '<div><input type="submit" value="'.get_string('sendemail', 'block_clac_progress').'" /></div>';
    echo html_writer::end_tag('form');

}
echo $output->container_end();

$student = $role;
// Generate report for all programs the user is in.
if ($student > 0) {
    for ($i = 1; $i <= 5; $i++) {
        $catfield = 'catfield'.$i;
        $catvalue = get_config('local_clac', $catfield);
        if ($catvalue > 0) {
            $incat = false;
            $courselists = $DB->get_records('course', array ('category' => $catvalue, 'visible' => '1'));
            foreach ($courselists as $courselist) {
                $cid = $courselist->id;
                $rolerec = $DB->get_record("role", array("shortname" => 'student'));
                $course = $DB->get_record("course", array("id" => $cid));
                $context = context_course::instance($cid);
                $userlist = get_role_users($rolerec->id, $context);
                foreach ($userlist as $user) {
                    $userid = $user->id;
                    if ($userid == $student) {
                        $incat = true;
                        break;
                    }
                }
                if ($incat) {
                    break;
                }
            }
            if ($incat) {
                if ($sendemail == 1) {
                    $ln = local_clac_generate_report($student, $catvalue, 2);
                } else {
                    $ln = local_clac_generate_report($student, $catvalue, 1);
                }
            }
        }
    }
    if ($sendemail == 1) {
        echo get_string('reportsent', 'block_clac_progress');
    }
    echo $ln.'<br>';
}
echo $output->container_end();
echo $output->footer();
