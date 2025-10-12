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
 * DevControl External API Class
 *
 * @package    local_devcontrol
 * @copyright  2024 DevControl Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * DevControl External API Class
 */
class local_devcontrol_external extends external_api {

    /**
     * Get system information and Docker container status
     *
     * @return array
     */
    public static function get_system_info() {
        global $CFG;

        // Validate context
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $info = array(
            'moodle_version' => $CFG->version,
            'moodle_release' => $CFG->release,
            'site_name' => $CFG->fullname,
            'site_url' => $CFG->wwwroot,
            'php_version' => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'timestamp' => time(),
            'docker_status' => self::get_docker_status(),
        );

        return $info;
    }

    /**
     * Manage Docker containers
     *
     * @param string $action The action to perform (start, stop, restart)
     * @param string $container The container name
     * @return array
     */
    public static function manage_containers($action, $container) {
        global $CFG;

        // Validate context
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        // Validate parameters
        $valid_actions = array('start', 'stop', 'restart');
        if (!in_array($action, $valid_actions)) {
            throw new invalid_parameter_exception('Invalid action');
        }

        $result = self::execute_docker_command($action, $container);

        return array(
            'success' => $result['success'],
            'message' => $result['message'],
            'output' => $result['output'],
            'action' => $action,
            'container' => $container,
        );
    }

    /**
     * Get Docker container logs
     *
     * @param string $container The container name
     * @param int $lines Number of lines to retrieve
     * @return array
     */
    public static function get_logs($container, $lines = 100) {
        global $CFG;

        // Validate context
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $result = self::execute_docker_command('logs', $container, array('--tail', $lines));

        return array(
            'success' => $result['success'],
            'logs' => $result['output'],
            'container' => $container,
            'lines' => $lines,
        );
    }

    /**
     * Backup and restore Moodle data
     *
     * @param string $action The action to perform (backup, restore)
     * @param string $filename The backup filename
     * @return array
     */
    public static function backup_restore($action, $filename = '') {
        global $CFG;

        // Validate context
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        // Validate parameters
        $valid_actions = array('backup', 'restore');
        if (!in_array($action, $valid_actions)) {
            throw new invalid_parameter_exception('Invalid action');
        }

        $result = self::execute_backup_restore($action, $filename);

        return array(
            'success' => $result['success'],
            'message' => $result['message'],
            'filename' => $filename,
            'action' => $action,
        );
    }

    /**
     * Get detailed container status information
     *
     * @return array
     */
    public static function get_container_status() {
        global $CFG;

        // Validate context
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $result = self::execute_docker_command('ps', '', array('-a', '--format', 'json'));

        $containers = array();
        if ($result['success'] && !empty($result['output'])) {
            $lines = explode("\n", trim($result['output']));
            foreach ($lines as $line) {
                if (!empty($line)) {
                    $container = json_decode($line, true);
                    if ($container) {
                        $containers[] = array(
                            'id' => $container['ID'],
                            'name' => $container['Names'],
                            'image' => $container['Image'],
                            'status' => $container['Status'],
                            'ports' => $container['Ports'],
                        );
                    }
                }
            }
        }

        return array(
            'success' => $result['success'],
            'containers' => $containers,
            'total' => count($containers),
        );
    }

    /**
     * Start a specific Docker container
     *
     * @param string $container The container name
     * @return array
     */
    public static function start_container($container) {
        return self::manage_containers('start', $container);
    }

    /**
     * Stop a specific Docker container
     *
     * @param string $container The container name
     * @return array
     */
    public static function stop_container($container) {
        return self::manage_containers('stop', $container);
    }

    /**
     * Restart a specific Docker container
     *
     * @param string $container The container name
     * @return array
     */
    public static function restart_container($container) {
        return self::manage_containers('restart', $container);
    }

    /**
     * Get Docker status
     *
     * @return array
     */
    private static function get_docker_status() {
        $result = self::execute_docker_command('version');
        return array(
            'available' => $result['success'],
            'version' => $result['success'] ? 'Docker available' : 'Docker not available',
            'error' => $result['success'] ? '' : $result['output'],
        );
    }

    /**
     * Execute Docker command
     *
     * @param string $command The Docker command
     * @param string $container The container name
     * @param array $args Additional arguments
     * @return array
     */
    private static function execute_docker_command($command, $container = '', $args = array()) {
        $cmd = "docker $command";
        if (!empty($container)) {
            $cmd .= " $container";
        }
        if (!empty($args)) {
            $cmd .= " " . implode(' ', $args);
        }

        $output = array();
        $return_code = 0;
        exec($cmd . ' 2>&1', $output, $return_code);

        return array(
            'success' => $return_code === 0,
            'output' => implode("\n", $output),
            'return_code' => $return_code,
            'command' => $cmd,
        );
    }

    /**
     * Execute backup/restore operation
     *
     * @param string $action The action to perform
     * @param string $filename The backup filename
     * @return array
     */
    private static function execute_backup_restore($action, $filename) {
        global $CFG;

        $backup_dir = $CFG->dataroot . '/backup';
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }

        if ($action === 'backup') {
            $filename = $filename ?: 'moodle_backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backup_dir . '/' . $filename;
            
            $cmd = "mysqldump -u moodle -pmoodle moodle > $filepath";
            $output = array();
            $return_code = 0;
            exec($cmd . ' 2>&1', $output, $return_code);

            return array(
                'success' => $return_code === 0,
                'message' => $return_code === 0 ? "Backup created: $filename" : "Backup failed",
                'output' => implode("\n", $output),
            );
        } else {
            if (empty($filename)) {
                return array(
                    'success' => false,
                    'message' => 'Filename required for restore',
                );
            }

            $filepath = $backup_dir . '/' . $filename;
            if (!file_exists($filepath)) {
                return array(
                    'success' => false,
                    'message' => "Backup file not found: $filename",
                );
            }

            $cmd = "mysql -u moodle -pmoodle moodle < $filepath";
            $output = array();
            $return_code = 0;
            exec($cmd . ' 2>&1', $output, $return_code);

            return array(
                'success' => $return_code === 0,
                'message' => $return_code === 0 ? "Restore completed: $filename" : "Restore failed",
                'output' => implode("\n", $output),
            );
        }
    }

    // Parameter definitions for external functions

    /**
     * Parameter definition for get_system_info
     */
    public static function get_system_info_parameters() {
        return new external_function_parameters(array());
    }

    public static function get_system_info_returns() {
        return new external_single_structure(array(
            'moodle_version' => new external_value(PARAM_TEXT, 'Moodle version'),
            'moodle_release' => new external_value(PARAM_TEXT, 'Moodle release'),
            'site_name' => new external_value(PARAM_TEXT, 'Site name'),
            'site_url' => new external_value(PARAM_TEXT, 'Site URL'),
            'php_version' => new external_value(PARAM_TEXT, 'PHP version'),
            'server_software' => new external_value(PARAM_TEXT, 'Server software'),
            'timestamp' => new external_value(PARAM_INT, 'Timestamp'),
            'docker_status' => new external_single_structure(array(
                'available' => new external_value(PARAM_BOOL, 'Docker available'),
                'version' => new external_value(PARAM_TEXT, 'Docker version'),
                'error' => new external_value(PARAM_TEXT, 'Error message'),
            )),
        ));
    }

    /**
     * Parameter definition for manage_containers
     */
    public static function manage_containers_parameters() {
        return new external_function_parameters(array(
            'action' => new external_value(PARAM_TEXT, 'Action to perform'),
            'container' => new external_value(PARAM_TEXT, 'Container name'),
        ));
    }

    public static function manage_containers_returns() {
        return new external_single_structure(array(
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
            'output' => new external_value(PARAM_TEXT, 'Command output'),
            'action' => new external_value(PARAM_TEXT, 'Action performed'),
            'container' => new external_value(PARAM_TEXT, 'Container name'),
        ));
    }

    /**
     * Parameter definition for get_logs
     */
    public static function get_logs_parameters() {
        return new external_function_parameters(array(
            'container' => new external_value(PARAM_TEXT, 'Container name'),
            'lines' => new external_value(PARAM_INT, 'Number of lines', VALUE_DEFAULT, 100),
        ));
    }

    public static function get_logs_returns() {
        return new external_single_structure(array(
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'logs' => new external_value(PARAM_TEXT, 'Container logs'),
            'container' => new external_value(PARAM_TEXT, 'Container name'),
            'lines' => new external_value(PARAM_INT, 'Number of lines'),
        ));
    }

    /**
     * Parameter definition for backup_restore
     */
    public static function backup_restore_parameters() {
        return new external_function_parameters(array(
            'action' => new external_value(PARAM_TEXT, 'Action to perform'),
            'filename' => new external_value(PARAM_TEXT, 'Backup filename', VALUE_DEFAULT, ''),
        ));
    }

    public static function backup_restore_returns() {
        return new external_single_structure(array(
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
            'filename' => new external_value(PARAM_TEXT, 'Backup filename'),
            'action' => new external_value(PARAM_TEXT, 'Action performed'),
        ));
    }

    /**
     * Parameter definition for get_container_status
     */
    public static function get_container_status_returns() {
        return new external_single_structure(array(
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'containers' => new external_multiple_structure(
                new external_single_structure(array(
                    'id' => new external_value(PARAM_TEXT, 'Container ID'),
                    'name' => new external_value(PARAM_TEXT, 'Container name'),
                    'image' => new external_value(PARAM_TEXT, 'Container image'),
                    'status' => new external_value(PARAM_TEXT, 'Container status'),
                    'ports' => new external_value(PARAM_TEXT, 'Container ports'),
                ))
            ),
            'total' => new external_value(PARAM_INT, 'Total containers'),
        ));
    }

    /**
     * Parameter definition for start_container
     */
    public static function start_container_parameters() {
        return new external_function_parameters(array(
            'container' => new external_value(PARAM_TEXT, 'Container name'),
        ));
    }

    public static function start_container_returns() {
        return self::manage_containers_returns();
    }

    /**
     * Parameter definition for stop_container
     */
    public static function stop_container_parameters() {
        return new external_function_parameters(array(
            'container' => new external_value(PARAM_TEXT, 'Container name'),
        ));
    }

    public static function stop_container_returns() {
        return self::manage_containers_returns();
    }

    /**
     * Parameter definition for restart_container
     */
    public static function restart_container_parameters() {
        return new external_function_parameters(array(
            'container' => new external_value(PARAM_TEXT, 'Container name'),
        ));
    }

    public static function restart_container_returns() {
        return self::manage_containers_returns();
    }
}
