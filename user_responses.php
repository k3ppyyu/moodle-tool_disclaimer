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
 * User responses management page for disclaimer plugin.
 *
 * This page allows administrators to search and manage user responses to disclaimers.
 *
 * @package    tool_disclaimer
 * @copyright  2026 ED&IT York University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

use tool_disclaimer\tables\user_response_table;
use tool_disclaimer\forms\user_response_filter_form;

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');

// Capability check - only admins who can edit disclaimers.
require_capability('tool/disclaimer:edit', $context);

// Get filter parameters - properly sanitized.
$userid = optional_param('userid', 0, PARAM_INT);
$firstname = optional_param('firstname', '', PARAM_TEXT);
$lastname = optional_param('lastname', '', PARAM_TEXT);
$disclaimercontext = optional_param('context', '', PARAM_ALPHANUMEXT);
$responsestatus = optional_param('response', -1, PARAM_INT);

// Set page URL with all filter parameters for pagination to work correctly.
$PAGE->set_url(new moodle_url('/admin/tool/disclaimer/user_responses.php', [
    'userid' => $userid,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'context' => $disclaimercontext,
    'response' => $responsestatus
]));

// Set page details.
$PAGE->set_title(get_string('user_responses', 'tool_disclaimer'));
$PAGE->set_heading(get_string('user_responses', 'tool_disclaimer'));

// Load AMD module.
$PAGE->requires->js_call_amd('tool_disclaimer/user_responses', 'init');

// Prepare form data.
$formdata = new stdClass();
$formdata->userid = $userid;
$formdata->firstname = $firstname;
$formdata->lastname = $lastname;
$formdata->context = $disclaimercontext;
$formdata->response = $responsestatus;

$mform = new user_response_filter_form(null, ['formdata' => $formdata]);

// Handle form submission.
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/disclaimer/user_responses.php'));
} else if ($data = $mform->get_data()) {
    // Redirect with the new filter parameters (POST-REDIRECT-GET pattern).
    redirect(new moodle_url('/admin/tool/disclaimer/user_responses.php', [
        'userid' => $data->userid ?? 0,
        'firstname' => $data->firstname ?? '',
        'lastname' => $data->lastname ?? '',
        'context' => $data->context ?? '',
        'response' => $data->response ?? -1
    ]));
}

// Create table instance.
$table = new user_response_table('tool_disclaimer_user_response_table');

// Build SQL query with filters.
$sqlwhere = [];
$params = [];

if (!empty($userid)) {
    $sqlwhere[] = "u.id = :userid";
    $params['userid'] = $userid;
}

if (!empty($firstname)) {
    $sqlwhere[] = $DB->sql_like('u.firstname', ':firstname', false);
    $params['firstname'] = '%' . $DB->sql_like_escape($firstname) . '%';
}

if (!empty($lastname)) {
    $sqlwhere[] = $DB->sql_like('u.lastname', ':lastname', false);
    $params['lastname'] = '%' . $DB->sql_like_escape($lastname) . '%';
}

if (!empty($disclaimercontext)) {
    $sqlwhere[] = "d.context = :context";
    $params['context'] = $disclaimercontext;
}

if ($responsestatus >= 0) {
    $sqlwhere[] = "dl.response = :response";
    $params['response'] = $responsestatus;
}

$where = !empty($sqlwhere) ? implode(' AND ', $sqlwhere) : '1=1';

// Define the SQL query to fetch data.
$fields = 'dl.id, u.id as userid, u.firstname, u.lastname, u.email, ' .
          'd.name as disclaimername, d.context, dl.objectid as courseid, ' .
          'c.fullname as coursefullname, dl.response, dl.attempt, dl.timecreated';
$from = '{tool_disclaimer_log} dl 
         JOIN {user} u ON u.id = dl.userid 
         JOIN {tool_disclaimer} d ON d.id = dl.disclaimerid
         LEFT JOIN {course} c ON c.id = dl.objectid AND d.context = \'course\'';

$table->set_sql($fields, $from, $where, $params);

// Define the base URL for the table.
$baseurl = new moodle_url('/admin/tool/disclaimer/user_responses.php', [
    'userid' => $userid,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'context' => $disclaimercontext,
    'response' => $responsestatus
]);
$table->define_baseurl($baseurl);

// Output page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('user_responses', 'tool_disclaimer'));

// Display filter form.
$mform->display();

// Display table.
$table->out(20, true);

echo $OUTPUT->footer();
