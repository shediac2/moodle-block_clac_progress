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

defined('MOODLE_INTERNAL') || die();

/**
 * Get a list of all active students in the system.
 *
 */
function block_clac_progress_student_list() {
    global $DB;
    $userarray = array();
    for ($i = 1; $i <= 5; $i++) {
        $catfield = 'catfield'.$i;
        $catvalue = get_config('local_clac', $catfield);
        if ($catvalue > 0) {
            // Get all students in all courses in the category.
            $courselists = $DB->get_records('course', array ('category' => $catvalue, 'visible' => '1'));
            foreach ($courselists as $courselist) {
                $cid = $courselist->id;
                $role = $DB->get_record("role", array("shortname" => 'student'));
                $course = $DB->get_record("course", array("id" => $cid));
                $context = context_course::instance($cid);
                $userlist = get_role_users($role->id, $context);
                foreach ($userlist as $user) {
                    $userid = $user->id;
                    if (!in_array($userid, $userarray)) {
                        $userarray[] = $userid;
                    }
                }
            }
        }
    }
    $ulist = implode(',', $userarray);
    $sql = "SELECT id, lastname, firstname
               FROM {user} u
               WHERE u.id in (".$ulist ." ) AND u.deleted = 0
               ORDER by lastname, firstname";
    $userlist = array();
    $userlist[0] = get_string('selectstudent', 'block_clac_progress');
    $users = $DB->get_records_sql($sql, array());
    foreach ($users as $user) {
         $userlist[$user->id] = $user->firstname." ".$user->lastname;
    }
    return $userlist;
}
