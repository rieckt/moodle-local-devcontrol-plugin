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
 * Language strings for DevControl plugin
 *
 * @package    local_devcontrol
 * @copyright  2024 DevControl Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'DevControl';
$string['devcontrol'] = 'DevControl';
$string['devcontrol:manage'] = 'Manage DevControl settings';

// Webservice functions
$string['get_system_info'] = 'Get system information and Docker container status';
$string['manage_containers'] = 'Manage Docker containers (start, stop, restart)';
$string['get_logs'] = 'Get Docker container logs';
$string['backup_restore'] = 'Backup and restore Moodle data';
$string['get_container_status'] = 'Get detailed container status information';
$string['start_container'] = 'Start a specific Docker container';
$string['stop_container'] = 'Stop a specific Docker container';
$string['restart_container'] = 'Restart a specific Docker container';

// Settings
$string['settings'] = 'DevControl Settings';
$string['enabled'] = 'Enable DevControl';
$string['enabled_desc'] = 'Enable DevControl plugin functionality';
$string['docker_path'] = 'Docker Path';
$string['docker_path_desc'] = 'Path to Docker executable (default: docker)';
$string['backup_path'] = 'Backup Path';
$string['backup_path_desc'] = 'Path for backup files (default: moodledata/backup)';

// Capabilities
$string['devcontrol:view'] = 'View DevControl information';
$string['devcontrol:manage'] = 'Manage DevControl settings';

// Errors
$string['error_docker_not_available'] = 'Docker is not available on this system';
$string['error_container_not_found'] = 'Container not found';
$string['error_permission_denied'] = 'Permission denied';
$string['error_invalid_action'] = 'Invalid action specified';
$string['error_backup_failed'] = 'Backup operation failed';
$string['error_restore_failed'] = 'Restore operation failed';

// Success messages
$string['success_container_started'] = 'Container started successfully';
$string['success_container_stopped'] = 'Container stopped successfully';
$string['success_container_restarted'] = 'Container restarted successfully';
$string['success_backup_created'] = 'Backup created successfully';
$string['success_restore_completed'] = 'Restore completed successfully';
