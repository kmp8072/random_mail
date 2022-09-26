<?php
//This file is a part of random_mail_send local plugin.
//
//Local plugin - random_mail_send is an application
//for user finding and saving for sending random mail.
//
//This application is developed as per task assigned by lingel learning.

/**
 * Plugin administration pages are defined here.
 *
 * @package     local_random_mail_send
 * @category    admin
 * @copyright   2021 Your Name <kmp8072@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('accounts',
    new admin_externalpage('sendraandommails',
        get_string('send_random_mail_bulk', 'local_random_mail_send'),
        "$CFG->wwwroot/local/random_mail_send/index.php",
        'local/random_mail_send:cansendrandommail'));
