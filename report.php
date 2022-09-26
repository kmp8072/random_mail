<?php
//This file is a part of random_mail_send local plugin.
//
//Local plugin - random_mail_send is an application
//for user finding and saving for sending random mail.
//
//This application is developed as per task assigned by lingel learning.

/**
 * a report showing random mail sending status
 *
 * @package    local_random_mail_send
 * @copyright  2022 Krishna Mohan Prasad <kmp8072@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_random_mail_send\table\report_table;

require_once '../../config.php';
require_once $CFG->libdir . '/adminlib.php';

admin_externalpage_setup('sendraandommails');
$PAGE->navbar->add(get_string('reportlabel', 'local_random_mail_send'), new moodle_url('/local/random_mail_send/report.php'));

echo $OUTPUT->header();

$report_table = new report_table('local_random_mail_send_report');
$report_table->out(10, true);

echo $OUTPUT->footer();