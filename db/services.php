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
 * DevControl Plugin Webservice Functions
 *
 * @package    local_devcontrol
 * @copyright  2024 DevControl Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_devcontrol_get_system_info' => array(
        'classname'     => 'local_devcontrol_external',
        'methodname'    => 'get_system_info',
        'classpath'     => 'local/devcontrol/classes/external.php',
        'description'   => 'Get system information and Docker container status',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'moodle/site:config',
    ),
    'local_devcontrol_manage_containers' => array(
        'classname'     => 'local_devcontrol_external',
        'methodname'    => 'manage_containers',
        'classpath'     => 'local/devcontrol/classes/external.php',
        'description'   => 'Manage Docker containers (start, stop, restart)',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'moodle/site:config',
    ),
    'local_devcontrol_get_logs' => array(
        'classname'     => 'local_devcontrol_external',
        'methodname'    => 'get_logs',
        'classpath'     => 'local/devcontrol/classes/external.php',
        'description'   => 'Get Docker container logs',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'moodle/site:config',
    ),
    'local_devcontrol_backup_restore' => array(
        'classname'     => 'local_devcontrol_external',
        'methodname'    => 'backup_restore',
        'classpath'     => 'local/devcontrol/classes/external.php',
        'description'   => 'Backup and restore Moodle data',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'moodle/site:config',
    ),
    'local_devcontrol_get_container_status' => array(
        'classname'     => 'local_devcontrol_external',
        'methodname'    => 'get_container_status',
        'classpath'     => 'local/devcontrol/classes/external.php',
        'description'   => 'Get detailed container status information',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'moodle/site:config',
    ),
    'local_devcontrol_start_container' => array(
        'classname'     => 'local_devcontrol_external',
        'methodname'    => 'start_container',
        'classpath'     => 'local/devcontrol/classes/external.php',
        'description'   => 'Start a specific Docker container',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'moodle/site:config',
    ),
    'local_devcontrol_stop_container' => array(
        'classname'     => 'local_devcontrol_external',
        'methodname'    => 'stop_container',
        'classpath'     => 'local/devcontrol/classes/external.php',
        'description'   => 'Stop a specific Docker container',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'moodle/site:config',
    ),
    'local_devcontrol_restart_container' => array(
        'classname'     => 'local_devcontrol_external',
        'methodname'    => 'restart_container',
        'classpath'     => 'local/devcontrol/classes/external.php',
        'description'   => 'Restart a specific Docker container',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'moodle/site:config',
    ),
);

$services = array(
    'DevControl API Service' => array(
        'functions' => array(
            'local_devcontrol_get_system_info',
            'local_devcontrol_manage_containers',
            'local_devcontrol_get_logs',
            'local_devcontrol_backup_restore',
            'local_devcontrol_get_container_status',
            'local_devcontrol_start_container',
            'local_devcontrol_stop_container',
            'local_devcontrol_restart_container',
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'devcontrol_api',
        'downloadfiles' => 1,
        'uploadfiles' => 1,
    ),
);
