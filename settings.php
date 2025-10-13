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
 * DevControl Plugin Settings
 *
 * @package    local_devcontrol
 * @copyright  2024 DevControl Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category('local_devcontrol', new lang_string('pluginname', 'local_devcontrol')));
    
    $settings = new admin_settingpage('local_devcontrol_settings', new lang_string('settings', 'local_devcontrol'));
    
    if ($ADMIN->fulltree) {
        // Enable/Disable plugin
        $settings->add(new admin_setting_configcheckbox('local_devcontrol/enabled',
            new lang_string('enabled', 'local_devcontrol'),
            new lang_string('enabled_desc', 'local_devcontrol'),
            1));
        
        // Docker configuration
        $settings->add(new admin_setting_heading('local_devcontrol_docker',
            new lang_string('docker_settings', 'local_devcontrol'),
            new lang_string('docker_settings_desc', 'local_devcontrol')));
            
        $settings->add(new admin_setting_configtext('local_devcontrol/docker_path',
            new lang_string('docker_path', 'local_devcontrol'),
            new lang_string('docker_path_desc', 'local_devcontrol'),
            'docker',
            PARAM_TEXT));
            
        $settings->add(new admin_setting_configtext('local_devcontrol/docker_timeout',
            new lang_string('docker_timeout', 'local_devcontrol'),
            new lang_string('docker_timeout_desc', 'local_devcontrol'),
            30,
            PARAM_INT));
        
        // Backup configuration
        $settings->add(new admin_setting_heading('local_devcontrol_backup',
            new lang_string('backup_settings', 'local_devcontrol'),
            new lang_string('backup_settings_desc', 'local_devcontrol')));
            
        $settings->add(new admin_setting_configtext('local_devcontrol/backup_path',
            new lang_string('backup_path', 'local_devcontrol'),
            new lang_string('backup_path_desc', 'local_devcontrol'),
            '',
            PARAM_TEXT));
            
        $settings->add(new admin_setting_configtext('local_devcontrol/mysql_user',
            new lang_string('mysql_user', 'local_devcontrol'),
            new lang_string('mysql_user_desc', 'local_devcontrol'),
            'moodle',
            PARAM_TEXT));
            
        $settings->add(new admin_setting_configpasswordunmask('local_devcontrol/mysql_password',
            new lang_string('mysql_password', 'local_devcontrol'),
            new lang_string('mysql_password_desc', 'local_devcontrol'),
            'moodle'));
            
        $settings->add(new admin_setting_configtext('local_devcontrol/mysql_database',
            new lang_string('mysql_database', 'local_devcontrol'),
            new lang_string('mysql_database_desc', 'local_devcontrol'),
            'moodle',
            PARAM_TEXT));
        
        // Security settings
        $settings->add(new admin_setting_heading('local_devcontrol_security',
            new lang_string('security_settings', 'local_devcontrol'),
            new lang_string('security_settings_desc', 'local_devcontrol')));
            
        $settings->add(new admin_setting_configtext('local_devcontrol/rate_limit',
            new lang_string('rate_limit', 'local_devcontrol'),
            new lang_string('rate_limit_desc', 'local_devcontrol'),
            60,
            PARAM_INT));
            
        $settings->add(new admin_setting_configcheckbox('local_devcontrol/log_actions',
            new lang_string('log_actions', 'local_devcontrol'),
            new lang_string('log_actions_desc', 'local_devcontrol'),
            1));
    }
    
    $ADMIN->add('local_devcontrol', $settings);
}
