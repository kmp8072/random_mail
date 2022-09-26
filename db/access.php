<?php
//This file is a part of random_mail_send local plugin.
//
//Local plugin - random_mail_send is an application
//for user finding and saving for sending random mail.
//
//This application is developed as per task assigned by lingel learning.

/**
 * Capability definitions for the local random mail
 * sender.
 *
 * @package    local_random_mail_send
 * @copyright  2022 Krishna Mohan Prasad<kmp8072@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    'local/random_mail_send:cansendrandommail' => [
        'captype' => 'write',
        'riskbitmask' => RISK_SPAM,
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

];