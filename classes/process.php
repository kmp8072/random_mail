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

namespace local_random_mail_send;

use coding_exception;
use core_user;
use csv_import_reader;
use dml_exception;
use moodle_exception;
use moodle_url;
use rms_progress_tracker;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir . '/csvlib.class.php';
require_once $CFG->dirroot . '/local/random_mail_send/locallib.php';

/**
 * Process CSV file with users data, this will save userids if found using the given email address otherwise skip it
 *
 * @package     local_random_mail_send
 * @copyright   2022 Krishna Mohan Prasad
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process
{

    /** @var csv_import_reader */
    protected $cir;
    /** @var rms_progress_tracker */
    protected $upt;
    /** @var array */
    protected $filecolumns = null;
    /** @var array */
    protected $standardfields = [];
    /** @var string|rms_progress_tracker|null */
    protected $progresstrackerclass = null;

    protected $userserrors = 0;

    /**
     * process constructor.
     *
     * @param csv_import_reader $cir
     * @param string|null $progresstrackerclass
     * @throws coding_exception
     */
    public function __construct(csv_import_reader $cir, string $progresstrackerclass = null)
    {
        $this->cir = $cir;
        if ($progresstrackerclass) {
            if (!class_exists($progresstrackerclass) || !is_subclass_of($progresstrackerclass, rms_progress_tracker::class)) {
                throw new coding_exception('Progress tracker class must extend \rms_progress_tracker');
            }
            $this->progresstrackerclass = $progresstrackerclass;
        } else {
            $this->progresstrackerclass = rms_progress_tracker::class;
        }

        $this->find_standard_fields();
    }

    /**
     * Standard user fields.
     */
    protected function find_standard_fields(): void
    {
        $this->standardfields = array('firstname', 'lastname', 'email');
    }

    /**
     * Process the CSV file
     */
    public function process()
    {
        // Init csv import helper.
        $this->cir->init();

        $classname = $this->progresstrackerclass;
        $this->upt = new $classname();
        $this->upt->start(); // Start table.

        $linenum = 1; // Column header is first line.
        while ($line = $this->cir->next()) {
            $this->upt->flush();
            $linenum++;

            $this->upt->track('line', $linenum);
            $this->process_line($line);
        }

        $this->upt->close(); // Close table.
        $this->cir->close();
        $this->cir->cleanup(true);
    }

    /**
     * Process one line from CSV file
     *
     * @param array $line
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function process_line(array $line)
    {
        global $DB, $CFG, $SESSION;
        $user = new stdClass();
        if (!$user = $this->get_user_record($line)) {
            return;
        }

        // here means we have found the user, so lets save their user-ids to send mail which will be sent on next cron run
        $new_record = new stdClass();
        $new_record->userid = $user->id;
        $new_record->email_send_status = RMS_EMAIL_TO_BE_SENT;
        $new_record->timecreated = time();
        $DB->insert_record('local_random_mail_send', $new_record);
    }

    /**
     * Get a user record from one line from CSV file
     *
     * @param array $line
     * @return stdClass|null
     */
    protected function get_user_record(array $line): ?stdClass
    {
        global $CFG, $USER;
        $user = new stdClass();
        foreach ($line as $keynum => $value) {
            if (!isset($this->get_file_columns()[$keynum])) {
                // This should not happen.
                continue;
            }
            $key = $this->get_file_columns()[$keynum];

            if ($key == 'email') {
                $user = core_user::get_user_by_email($value);

                if (!$user) {
                    $this->upt->track('status', get_string('error_email_not_found', 'local_random_mail_send', s($value)), 'error');
                    $this->upt->track('email', get_string('error'), 'error');
                    $this->userserrors++;
                    return null;
                } else {
                    $this->upt->track('status', get_string('success_email_found', 'local_random_mail_send', s($value)), 'normal');
                    $this->upt->track('email', $value, 'normal');

                }
            } else {
                $this->upt->track($key, $value, 'normal');
            }

        }

        return $user;
    }

    /**
     * Returns the list of columns in the file
     *
     * @return array
     */
    public function get_file_columns(): array
    {
        if ($this->filecolumns === null) {
            $returnurl = new moodle_url('/local/random_mail_send/index.php');
            $this->filecolumns = rms_validate_user_upload_columns($this->cir,
                $this->standardfields, $returnurl);
        }
        return $this->filecolumns;
    }

    /**
     * Summary about the whole process (how many users skipped because they were not found)
     *
     * @return array
     */
    public function get_stats()
    {
        $lines = [];
        $lines[] = get_string('usererrors', 'local_random_mail_send') . ': ' . $this->userserrors;

        return $lines;
    }
}
