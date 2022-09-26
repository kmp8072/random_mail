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

defined('MOODLE_INTERNAL') || die();

define('RMS_EMAIL_SEND_FAILED', 0);
define('RMS_EMAIL_SENT_SUCCESS', 1);
define('RMS_EMAIL_TO_BE_SENT', 2);

/**
 * Tracking of processed users while uploading csv.
 *
 * This class prints user information into a html table.
 *
 * @package    local_random_mail_send
 * @copyright  2022 Krishna Mohan Prasad<kmp802@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rms_progress_tracker
{
    /**
     * The columns shown on the table.
     * @var array
     */
    public $columns = [];
    /** @var array */
    protected $_row;
    /** @var array column headers */
    protected $headers = [];

    /**
     * rms_progress_tracker constructor.
     */
    public function __construct()
    {
        $this->headers = [
            'status' => get_string('status'),
            'line' => get_string('uucsvline', 'tool_uploaduser'),
            'firstname' => get_string('firstname'),
            'lastname' => get_string('lastname'),
            'email' => get_string('email'),
        ];
        $this->columns = array_keys($this->headers);
    }

    /**
     * Print table header.
     * @return void
     */
    public function start()
    {
        $ci = 0;
        echo '<table id="rmsresults" class="generaltable boxaligncenter flexible-wrap" summary="' . get_string('uploadusersresult', 'local_random_mail_send') . '">';
        echo '<tr class="heading r0">';
        foreach ($this->headers as $key => $header) {
            echo '<th class="header c' . $ci++ . '" scope="col">' . $header . '</th>';
        }
        echo '</tr>';
        $this->_row = null;
    }

    /**
     * Add tracking info
     * @param string $col name of column
     * @param string $msg message
     * @param string $level 'normal', 'warning' or 'error'
     * @param bool $merge true means add as new line, false means override all previous text of the same type
     * @return void
     */
    public function track($col, $msg, $level = 'normal', $merge = true)
    {
        if (empty($this->_row)) {
            $this->flush(); //init arrays
        }
        if (!in_array($col, $this->columns)) {
            debugging('Incorrect column:' . $col);
            return;
        }
        if ($merge) {
            if ($this->_row[$col][$level] != '') {
                $this->_row[$col][$level] .= '<br />';
            }
            $this->_row[$col][$level] .= $msg;
        } else {
            $this->_row[$col][$level] = $msg;
        }
    }

    /**
     * Flush previous line and start a new one.
     * @return void
     */
    public function flush()
    {
        if (empty($this->_row) or empty($this->_row['line']['normal'])) {
            // Nothing to print - each line has to have at least number
            $this->_row = array();
            foreach ($this->columns as $col) {
                $this->_row[$col] = array('normal' => '', 'info' => '', 'warning' => '', 'error' => '');
            }
            return;
        }
        $ci = 0;
        $ri = 1;
        echo '<tr class="r' . $ri . '">';
        foreach ($this->_row as $key => $field) {
            foreach ($field as $type => $content) {
                if ($field[$type] !== '') {
                    $field[$type] = '<span class="uu' . $type . '">' . $field[$type] . '</span>';
                } else {
                    unset($field[$type]);
                }
            }
            echo '<td class="cell c' . $ci++ . '">';
            if (!empty($field)) {
                echo implode('<br />', $field);
            } else {
                echo '&nbsp;';
            }
            echo '</td>';
        }
        echo '</tr>';
        foreach ($this->columns as $col) {
            $this->_row[$col] = array('normal' => '', 'info' => '', 'warning' => '', 'error' => '');
        }
    }

    /**
     * Print the table end
     * @return void
     */
    public function close()
    {
        $this->flush();
        echo '</table>';
    }
}

/**
 * Validation callback function - verified the column line of csv file.
 * Converts standard column names to lowercase.
 * @param csv_import_reader $cir
 * @param array $stdfields standard user fields
 * @param moodle_url $returnurl return url in case of any error
 * @return array list of fields
 */
function rms_validate_user_upload_columns(csv_import_reader $cir, $stdfields, moodle_url $returnurl)
{
    $columns = $cir->get_columns();

    if (empty($columns)) {
        $cir->close();
        $cir->cleanup();
        print_error('cannotreadtmpfile', 'error', $returnurl);
    }
    if (count($columns) < 3) {
        $cir->close();
        $cir->cleanup();
        print_error('csvfewcolumns', 'error', $returnurl);
    }

    // test columns
    $processed = array();
    foreach ($columns as $key => $unused) {
        $field = $columns[$key];
        $field = trim($field);
        $lcfield = core_text::strtolower($field);
        if (in_array($field, $stdfields) or in_array($lcfield, $stdfields)) {
            // standard fields are only lowercase
            $newfield = $lcfield;

        } else {
            $cir->close();
            $cir->cleanup();
            print_error('invalidfieldname', 'error', $returnurl, $field);
        }
        if (in_array($newfield, $processed)) {
            $cir->close();
            $cir->cleanup();
            print_error('duplicatefieldname', 'error', $returnurl, $newfield);
        }
        $processed[$key] = $newfield;
    }

    return $processed;
}

function checkfilecolumns($columns)
{
    $errors = [];
    if (!in_array('firstname', $columns)) {
        $errors[] = get_string('missingfield', 'error', 'firstname');
    }

    if (!in_array('lastname', $columns)) {
        $errors[] = get_string('missingfield', 'error', 'lastname');
    }

    if (!in_array('email', $columns)) {
        $errors[] = get_string('missingfield', 'error', 'email');
    }
    return $errors;
}