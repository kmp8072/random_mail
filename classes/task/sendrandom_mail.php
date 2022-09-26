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

namespace local_random_mail_send\task;

use core\task\adhoc_task;
use core_php_time_limit;
use core_user;
use stdClass;
use function raise_memory_limit;

/**
 *adhoc task to send random mails to users that were uploaded using csv
 *
 * @package    local_random_mail_send
 * @copyright  2022 Krishna Mohan Prasad <kmp8072@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sendrandom_mail extends adhoc_task
{

    /**
     * Performs the email sending for the users whose status is RMS_EMAIL_TO_BE_SENT.
     *
     * @return void
     */
    public function execute()
    {
        global $DB, $CFG;
        require_once $CFG->dirroot . '/local/random_mail_send/locallib.php';
        //this is going to take time
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_EXTRA);
        // get the users
        $sql = "SELECT lrms.id as toupdateid,u.* FROM {user} u
				JOIN {local_random_mail_send} lrms ON lrms.userid = u.id
				WHERE u.deleted = ? AND lrms.email_send_status = ?";
        $users = $DB->get_records_sql($sql, [0, RMS_EMAIL_TO_BE_SENT]);

        if ($users) {
            $fromuser = core_user::get_noreply_user();
            /**
             * configurable subject and message
             * @todo take the subject and message from configuration dynamically
             */
            $subject = "Test Subject";
            $message = "Test Message";
            foreach ($users as $key => $user) {
                $is_sent = email_to_user($user, $fromuser, $subject, $message);
                $update_record = new stdClass();
                $update_record->id = $user->toupdateid;
                $update_record->email_send_status = $is_sent ? 1 : 0;
                $update_record->timesent = time();

                $DB->update_record('local_random_mail_send', $update_record);
            }
        }

    }

}
