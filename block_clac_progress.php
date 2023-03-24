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
 * Clac Progress block definition
 *
 * @package    block_clac_progress
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Clac Progress block class
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
class block_clac_progress extends block_base {

    /**
     * Sets the block title
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_clac_progress');
    }

    /**
     *  we have global config/settings data
     *
     * @return bool
     */
    public function has_config() {
        return false;
    }

    /**
     * Controls whether multiple instances of the block are allowed on a page
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return !self::on_site_page($this->page);
    }

    /**
     * Controls whether the block is configurable
     *
     * @return bool
     */
    public function instance_allow_config() {
        return !self::on_site_page($this->page);
    }

    /**
     * Defines where the block can be added
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'course-view'    => true,
            'site'           => false,
            'mod'            => false,
            'my'             => false
        );
    }

    /**
     * Creates the blocks main content
     *
     * @return string
     */
    public function get_content() {
        // If content has already been generated, don't waste time generating it again.
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
        $barinstances = array();

        // Guests do not have any progress. Don't show them the block.
        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        // Gather content for block on regular course.
        if (!$this->prepare_course_content($barinstances)) {
            return $this->content;
        }

        return $this->content;
    }

    /**
     * Produce content for a course page.
     * @param array $barinstances receives block instance ids
     * @return boolean false if an early exit
     */
    protected function prepare_course_content(&$barinstances) {
        global $USER, $COURSE, $CFG, $OUTPUT;
        // Check if completion is enabled at site level.
        if (!$CFG->enablecompletion) {
            if (has_capability('moodle/block:edit', $this->context)) {
                $this->content->text .= get_string('completion_not_enabled', 'block_clac_progress');
            }
            return false;
        }

        require_once($CFG->dirroot.'/local/clac/lib.php');
        $tm = local_clac_get_course_time($COURSE->id);
        $tmused = local_clac_get_course_completed_time($COURSE->id, $USER->id);
        if ($tm > 0) {
            $complete = ($tmused / $tm) * 100;
            $complete = round($complete, 2);
        }
        $this->content->text = '<progress id="file" max="100" value="'.$complete.'"> </progress><br> '.$complete.' % complete';
        $parameters = array('instanceid' => $this->instance->id, 'courseid' => $COURSE->id);
        $url = new moodle_url('/blocks/clac_progress/overview.php', $parameters);
        $label = get_string('overview', 'block_clac_progress');
        $options = array('class' => 'overviewButton');
        $this->content->text .= $OUTPUT->single_button($url, $label, 'get', $options);
        return;

    }

    /**
     * Checks whether the given page is site-level (Dashboard or Front page) or not.
     *
     * @param moodle_page $page the page to check, or the current page if not passed.
     * @return boolean True when on the Dashboard or Site home page.
     */
    public static function on_site_page($page = null) {
        global $PAGE;

        $page = $page ?? $PAGE;
        $context = $page->context ?? null;

        if (!$page || !$context) {
            return false;
        } else if ($context->contextlevel === CONTEXT_SYSTEM && $page->requestorigin === 'restore') {
            return false;
        } else if ($context->contextlevel === CONTEXT_COURSE && $context->instanceid == SITEID) {
            return true;  // Front page.
        } else if ($context->contextlevel < CONTEXT_COURSE) {
            return true;  // System, user (i.e. dashboard), course category.
        } else {
            return false;
        }
    }

}
