<?php
//This file is a part of random_mail_send local plugin.
//
//Local plugin - random_mail_send is an application
//for user finding and saving for sending random mail.
//
//This application is developed as per task assigned by lingel learning.

/**
 * user finding and saving for sending random mail
 *
 * @package    local_random_mail_send
 * @copyright  2022 Krishna Mohan Prasad <kmp8072@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;
use core\task\manager;
use local_random_mail_send\process;
use local_random_mail_send\task\sendrandom_mail;
use local_random_mail_send\upload_user_form;

require '../../config.php';
require_once $CFG->libdir . '/csvlib.class.php';
require_once 'locallib.php';
require_once $CFG->libdir . '/adminlib.php';

$iid = optional_param('iid', '', PARAM_INT); // import identifier
$previewrows = optional_param('previewrows', 10, PARAM_INT); // import preview rows

core_php_time_limit::raise(60 * 60);
raise_memory_limit(MEMORY_HUGE);

admin_externalpage_setup('sendraandommails');

$reporturl = new moodle_url('/local/random_mail_send/report.php');
$reportlabel = get_string('reportlabel', 'local_random_mail_send');
$buttons = $OUTPUT->single_button($reporturl, $reportlabel);
$PAGE->set_button($buttons);

notification::add(get_string('mail_send_on_nextcron', 'local_random_mail_send'), notification::WARNING);

$returnurl = get_local_referer(false);

$pageurl = new moodle_url('/local/random_mail_send/index.php');

$upload_user_form = new upload_user_form();

if ($formdata = $upload_user_form->is_cancelled()) {
    // form cancelled redirect to previous page
    redirect($returnurl);
} elseif ($formdata = $upload_user_form->get_data()) {
    $iid = csv_import_reader::get_new_iid('uploaduser');
    $cir = new csv_import_reader($iid, 'uploaduser');

    $content = $upload_user_form->get_file_content('userfile');

    $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
    $csvloaderror = $cir->get_error();
    unset($content);

    if (!is_null($csvloaderror)) {
        print_error('csvloaderror', '', $returnurl, $csvloaderror);
    }

    // Testing if columns missing or not.
    $process = new process($cir);
    $filecolumns = $process->get_file_columns();

    $filecolumserrors = checkfilecolumns($filecolumns);

    if (!empty($filecolumserrors)) {
        $columserrormsg = implode(", ", $filecolumserrors);
        print_error('error_colums', 'local_random_mail_send', $pageurl, $columserrormsg);
    }
    // we are here so good to process the data
    // now start processing the data
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('uploadusersresult', 'tool_uploaduser'));
    $process->process();

    // now lets queue the adhoc task for processing

    $adhoc_randommail_task = new sendrandom_mail();

    manager::queue_adhoc_task($adhoc_randommail_task, true);

    echo $OUTPUT->box_start('boxwidthnarrow boxaligncenter generalbox', 'uploadresults');
    echo html_writer::tag('p', join('<br />', $process->get_stats()));
    echo $OUTPUT->box_end();

    echo $OUTPUT->continue_button($pageurl);

    echo $OUTPUT->footer();
    die;

}

echo $OUTPUT->header();
$upload_user_form->display();
echo $OUTPUT->footer();