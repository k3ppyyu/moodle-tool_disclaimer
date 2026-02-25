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
 * User response table for displaying user disclaimer responses.
 *
 * @package    tool_disclaimer
 * @copyright  2026 ED&IT York University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_disclaimer\tables;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table class for displaying user responses to disclaimers.
 *
 * @package    tool_disclaimer
 * @copyright  2026 ED&IT York University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_response_table extends \table_sql {

    /**
     * Constructor for user_response_table.
     *
     * @param string $uniqueid Unique identifier for the table
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        // Define the columns to be displayed.
        $columns = ['userid', 'firstname', 'lastname', 'email', 'disclaimername',
                    'context', 'courseid', 'coursefullname', 'response', 'attempt', 'timecreated', 'actions'];
        $this->define_columns($columns);

        // Define the headers for the columns.
        $headers = [
            get_string('userid', 'tool_disclaimer'),
            get_string('firstname'),
            get_string('lastname'),
            get_string('email'),
            get_string('disclaimer_name', 'tool_disclaimer'),
            get_string('context', 'tool_disclaimer'),
            get_string('courseid', 'tool_disclaimer'),
            get_string('coursefullname', 'tool_disclaimer'),
            get_string('response_status', 'tool_disclaimer'),
            get_string('attempt', 'tool_disclaimer'),
            get_string('timecreated', 'tool_disclaimer'),
            get_string('actions', 'tool_disclaimer'),
        ];

        $this->define_headers($headers);

        // Make table sortable.
        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->no_sorting('actions');
        $this->no_sorting('coursefullname');

        // Don't allow wrapping for better display.
        $this->column_class('userid', 'text-center');
        $this->column_class('courseid', 'text-center');
        $this->column_class('response', 'text-center');
        $this->column_class('attempt', 'text-center');
        $this->column_class('actions', 'text-center');
    }

    /**
     * Format the course ID column - only shown for course context disclaimers.
     *
     * @param object $values Row data
     * @return string Formatted HTML
     */
    public function col_courseid($values) {
        if ($values->context === 'course' && !empty($values->courseid)) {
            return \html_writer::tag('span', (int)$values->courseid);
        }
        return \html_writer::tag('span', '-', ['class' => 'text-muted']);
    }

    /**
     * Format the course full name column - only shown for course context disclaimers.
     *
     * @param object $values Row data
     * @return string Formatted HTML
     */
    public function col_coursefullname($values) {
        global $CFG;
        if ($values->context === 'course' && !empty($values->coursefullname)) {
            $url = new \moodle_url('/course/view.php', ['id' => $values->courseid]);
            return \html_writer::link($url, format_string($values->coursefullname));
        }
        return \html_writer::tag('span', '-', ['class' => 'text-muted']);
    }

    /**
     * Format the response column with badge styling.
     *
     * @param object $values Row data
     * @return string Formatted HTML
     */
    public function col_response($values) {
        if ($values->response == 1) {
            return \html_writer::tag('span', get_string('accepted', 'tool_disclaimer'),
                ['class' => 'badge badge-success']);
        } else {
            return \html_writer::tag('span', get_string('declined', 'tool_disclaimer'),
                ['class' => 'badge badge-danger']);
        }
    }

    /**
     * Format the time created column.
     *
     * @param object $values Row data
     * @return string Formatted date string
     */
    public function col_timecreated($values) {
        return userdate($values->timecreated, get_string('strftimedatetime', 'langconfig'));
    }

    /**
     * Format the actions column with reset button.
     *
     * @param object $values Row data
     * @return string Formatted HTML
     */
    public function col_actions($values) {
        global $OUTPUT, $CFG;

        $actions = [
            'reset_url' => $CFG->wwwroot . '/admin/tool/disclaimer/reset_response.php?id=' . $values->id,
            'id' => $values->id,
        ];

        return $OUTPUT->render_from_template('tool_disclaimer/user_response_action_buttons', $actions);
    }
}
