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

namespace local_random_mail_send\table;

defined('MOODLE_INTERNAL') || die();

use core_user;
use dml_exception;
use moodle_url;
use stdClass;
use table_sql;

require_once $CFG->libdir . '/tablelib.php';
require_once $CFG->dirroot . '/local/random_mail_send/locallib.php';

/**
 * Report table for the random_mail_send Sent Report.
 *
 * @copyright  2022 Krishna Mohan Prasad <kmp8072@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_table extends table_sql {
	/**
	 * @var stdClass
	 */
	private $userdata;

	/**
	 * @param string $uniqueid a string identifying this table.Used as a key in
	 *                          session  vars.
	 */
	public function __construct($uniqueid) {
		global $CFG;
		parent::__construct($uniqueid);
		$this->define_baseurl(new moodle_url($CFG->wwwroot . '/local/random_mail_send/report.php'));
		$fields = 'id,userid,email_send_status, timesent';
		$from = '{local_random_mail_send}';
		$where = '1=1';
//        $where = 'email_send_status<>2';

		$columns = array(
			'firstname',
			'lastname',
			'email',
			'sent_status',
			'timesent',
		);
		$headers = array(
			get_string('column_firstname', 'local_random_mail_send'),
			get_string('column_lastname', 'local_random_mail_send'),
			get_string('column_email', 'local_random_mail_send'),
			get_string('column_sent_status', 'local_random_mail_send'),
			get_string('column_timesent', 'local_random_mail_send'),
		);
		$this->define_columns($columns);
		$this->define_headers($headers);
		$this->no_sorting('firstname');
		$this->no_sorting('lastname');
		$this->no_sorting('email');
		$this->no_sorting('sent_status');
		$this->no_sorting('timesent');
		$this->downloadable = false;
		$this->set_sql($fields, $from, $where);

		$this->userdata = new stdClass();
	}

	/**
	 * @param object $data - a table row
	 * @return string user first name
	 * @throws dml_exception
	 */
	public function col_firstname($data) {
		$this->get_user($data->userid);
		return $this->userdata->firstname;
	}

	/**
	 * @param integer $id user id
	 * @return bool|stdClass user object
	 * @throws dml_exception
	 */
	public function get_user($id) {
		if (isset($this->userdata->id) && $this->userdata->id == $id) {
			return $this->userdata;
		}
		return $this->userdata = core_user::get_user($id);
	}

	/**
	 * @param object $data - a table row
	 * @return string user last name
	 */
	public function col_lastname($data) {
		return $this->userdata->lastname;
	}

	/**
	 * @param object $data - a table row
	 * @return string user email
	 */
	public function col_email($data) {
		return $this->userdata->email;
	}

	/**
	 * @param object $data - a table row
	 * @return string email status
	 */
	public function col_sent_status($data) {
		if ($data->email_send_status == RMS_EMAIL_SENT_SUCCESS) {
			return get_string('success', 'local_random_mail_send');
		} elseif ($data->email_send_status == RMS_EMAIL_TO_BE_SENT) {
			return get_string('row_email_to_be_sent', 'local_random_mail_send');
		}
		return get_string('failed', 'local_random_mail_send');
	}

	/**
	 * @param object $data - a table row
	 * @return string date & time of email sent
	 */
	public function col_timesent($data) {
		if (!$data->timesent) {
			return get_string('row_empty', 'local_random_mail_send');
		}
		return userdate($data->timesent);
	}
}
