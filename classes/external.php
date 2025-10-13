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

        try {
            // Validate context
            $context = context_system::instance();
            self::validate_context($context);
            require_capability('moodle/site:config', $context);

            $info = array(
                'success' => true,
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
        } catch (Exception $e) {
            // Log error for debugging
            error_log("DevControl get_system_info error: " . $e->getMessage());
            
            return array(
                'success' => false,
                'error' => 'Failed to retrieve system information',
                'error_code' => 'SYSTEM_INFO_ERROR',
                'timestamp' => time()
            );
        }
    }

    /**
     * Validate container name for security
     *
     * @param string $container Container name
     * @return bool
     */
    private static function validate_container_name($container) {
        // Only allow alphanumeric, hyphens, underscores, and dots
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $container)) {
            return false;
        }
        
        // Prevent path traversal attempts
        if (strpos($container, '..') !== false || strpos($container, '/') !== false) {
            return false;
        }
        
        // Limit length
        if (strlen($container) > 100) {
            return false;
        }
        
        return true;
    }

    /**
     * Check rate limiting for API calls
     *
     * @param string $function Function name
     * @param int $limit Maximum calls per minute
     * @return bool
     */
    private static function check_rate_limit($function, $limit = 60) {
        global $DB, $USER;
        
        $user_id = $USER->id;
        $minute_ago = time() - 60;
        
        // Count calls in the last minute
        $count = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {log} 
             WHERE userid = ? AND action = ? AND time > ?",
            array($user_id, $function, $minute_ago)
        );
        
        if ($count >= $limit) {
            return false;
        }
        
        // Log this call
        $DB->insert_record('log', array(
            'userid' => $user_id,
            'action' => $function,
            'time' => time(),
            'ip' => getremoteaddr()
        ));
        
        return true;
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
        
        // Validate container name for security
        if (!self::validate_container_name($container)) {
            throw new invalid_parameter_exception('Invalid container name');
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
     * Get Moodle users
     *
     * @param int $page Page number
     * @param int $perpage Users per page
     * @param string $search Search term
     * @return array
     */
    public static function get_users($page = 0, $perpage = 50, $search = '') {
        global $DB;

        try {
            // Enable read-only session for better performance
            if (defined('MOODLE_INTERNAL') && !defined('READ_ONLY_SESSION')) {
                define('READ_ONLY_SESSION', true);
            }
            
            // Validate context
            $context = context_system::instance();
            self::validate_context($context);
            require_capability('moodle/site:config', $context);

            // Check rate limiting
            if (!self::check_rate_limit('get_users', 30)) {
                throw new moodle_exception('ratelimit', 'local_devcontrol', '', null, 'Too many requests');
            }

            // Input validation - fail fast
            if ($page < 0) {
                throw new invalid_parameter_exception('Page number must be non-negative');
            }
            if ($perpage < 1 || $perpage > 1000) {
                throw new invalid_parameter_exception('Per page must be between 1 and 1000');
            }
            if (strlen($search) > 255) {
                throw new invalid_parameter_exception('Search term too long');
            }

            $params = array();
            $where = '1=1';
            
            if (!empty($search)) {
                // Sanitize search term
                $search = trim($search);
                $where .= ' AND (firstname LIKE ? OR lastname LIKE ? OR username LIKE ? OR email LIKE ?)';
                $searchterm = '%' . $search . '%';
                $params = array($searchterm, $searchterm, $searchterm, $searchterm);
            }

            $offset = $page * $perpage;
            $users = $DB->get_records_sql(
                "SELECT id, username, firstname, lastname, email, suspended, lastaccess, timecreated 
                 FROM {user} 
                 WHERE $where AND deleted = 0 AND id > 1 
                 ORDER BY lastaccess DESC 
                 LIMIT $perpage OFFSET $offset",
                $params
            );

            $result = array();
            foreach ($users as $user) {
                $result[] = array(
                    'id' => $user->id,
                    'username' => $user->username,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'fullname' => $user->firstname . ' ' . $user->lastname,
                    'email' => $user->email,
                    'suspended' => $user->suspended,
                    'lastaccess' => $user->lastaccess,
                    'timecreated' => $user->timecreated,
                    'roles' => self::get_user_roles($user->id)
                );
            }

            return array(
                'success' => true,
                'users' => $result,
                'total' => count($result)
            );
        } catch (Exception $e) {
            // Log error for debugging
            error_log("DevControl get_users error: " . $e->getMessage());
            
            return array(
                'success' => false,
                'error' => 'Failed to retrieve users',
                'error_code' => 'USERS_RETRIEVAL_ERROR',
                'users' => array(),
                'total' => 0
            );
        }
    }

    /**
     * Get Moodle user count
     *
     * @return array
     */
    public static function get_user_count() {
        global $DB;

        // Validate context
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $count = $DB->count_records('user', array('deleted' => 0), 'id > 1');

        return array(
            'success' => true,
            'count' => $count
        );
    }

    /**
     * Get Moodle plugins
     *
     * @return array
     */
    public static function get_plugins() {
        global $DB;

        // Validate context
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $plugins = $DB->get_records('config_plugins', array('plugin' => 'core'), '', 'plugin, name, value');

        $result = array();
        foreach ($plugins as $plugin) {
            if (strpos($plugin->name, 'version') !== false) {
                $component = str_replace('_version', '', $plugin->name);
                $result[] = array(
                    'component' => $component,
                    'type' => 'core',
                    'name' => $component,
                    'displayname' => ucfirst(str_replace('_', ' ', $component)),
                    'release' => 'Unknown',
                    'version' => $plugin->value,
                    'enabled' => 1,
                    'source' => 'core'
                );
            }
        }

        return array(
            'success' => true,
            'plugins' => $result
        );
    }

    /**
     * Get Moodle metrics
     *
     * @return array
     */
    public static function get_metrics() {
        global $DB;

        // Validate context
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        // Get active sessions
        $active_sessions = $DB->count_records('sessions', array(), 'timemodified > ?', array(time() - 300));

        // Get cron info
        $cron_lastrun = get_config('core', 'lastcronstart') ?: 0;
        $cron_nextrun = $cron_lastrun + 3600; // Default 1 hour

        // Get adhoc tasks
        $adhoc_tasks = $DB->count_records('task_adhoc');

        return array(
            'success' => true,
            'metrics' => array(
                'active_sessions' => $active_sessions,
                'cron_lastrun' => $cron_lastrun,
                'cron_nextrun' => $cron_nextrun,
                'adhoc_tasks_pending' => $adhoc_tasks
            )
        );
    }

    /**
     * Get database statistics
     *
     * @return array
     */
    public static function get_database_stats() {
        global $DB;

        // Validate context
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        // Get course count
        $course_count = $DB->count_records('course', array('id' => 1), 'id > 1');

        // Get user count
        $user_count = $DB->count_records('user', array('deleted' => 0), 'id > 1');

        // Get table count
        $tables = $DB->get_tables();
        $table_count = count($tables);

        // Get largest tables
        $largest_tables = array();
        foreach ($tables as $table) {
            $size = $DB->get_record_sql("SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb FROM information_schema.TABLES WHERE table_schema = DATABASE() AND table_name = ?", array($table));
            if ($size && $size->size_mb > 0) {
                $largest_tables[] = array(
                    'name' => $table,
                    'size_mb' => $size->size_mb
                );
            }
        }
        usort($largest_tables, function($a, $b) { return $b['size_mb'] <=> $a['size_mb']; });
        $largest_tables = array_slice($largest_tables, 0, 10);

        return array(
            'success' => true,
            'stats' => array(
                'course_count' => $course_count,
                'user_count' => $user_count,
                'table_count' => $table_count,
                'largest_tables' => $largest_tables
            )
        );
    }

    /**
     * Get user roles
     *
     * @param int $userid User ID
     * @return string
     */
    private static function get_user_roles($userid) {
        global $DB;

        $roles = $DB->get_records_sql(
            "SELECT r.shortname 
             FROM {role_assignments} ra 
             JOIN {role} r ON ra.roleid = r.id 
             WHERE ra.userid = ? AND ra.contextid = 1",
            array($userid)
        );

        $role_names = array();
        foreach ($roles as $role) {
            $role_names[] = $role->shortname;
        }

        return implode(', ', $role_names) ?: 'user';
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
    public static function get_container_status_parameters() {
        return new external_function_parameters(array());
    }

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

    /**
     * Parameter definition for get_users
     */
    public static function get_users_parameters() {
        return new external_function_parameters(array(
            'page' => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
            'perpage' => new external_value(PARAM_INT, 'Users per page', VALUE_DEFAULT, 50),
            'search' => new external_value(PARAM_TEXT, 'Search term', VALUE_DEFAULT, ''),
        ));
    }

    public static function get_users_returns() {
        return new external_single_structure(array(
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'users' => new external_multiple_structure(
                new external_single_structure(array(
                    'id' => new external_value(PARAM_INT, 'User ID'),
                    'username' => new external_value(PARAM_TEXT, 'Username'),
                    'firstname' => new external_value(PARAM_TEXT, 'First name'),
                    'lastname' => new external_value(PARAM_TEXT, 'Last name'),
                    'fullname' => new external_value(PARAM_TEXT, 'Full name'),
                    'email' => new external_value(PARAM_TEXT, 'Email'),
                    'suspended' => new external_value(PARAM_INT, 'Suspended status'),
                    'lastaccess' => new external_value(PARAM_INT, 'Last access time'),
                    'timecreated' => new external_value(PARAM_INT, 'Creation time'),
                    'roles' => new external_value(PARAM_TEXT, 'User roles'),
                ))
            ),
            'total' => new external_value(PARAM_INT, 'Total users'),
        ));
    }

    /**
     * Parameter definition for get_user_count
     */
    public static function get_user_count_parameters() {
        return new external_function_parameters(array());
    }

    public static function get_user_count_returns() {
        return new external_single_structure(array(
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'count' => new external_value(PARAM_INT, 'User count'),
        ));
    }

    /**
     * Parameter definition for get_plugins
     */
    public static function get_plugins_parameters() {
        return new external_function_parameters(array());
    }

    public static function get_plugins_returns() {
        return new external_single_structure(array(
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'plugins' => new external_multiple_structure(
                new external_single_structure(array(
                    'component' => new external_value(PARAM_TEXT, 'Component name'),
                    'type' => new external_value(PARAM_TEXT, 'Plugin type'),
                    'name' => new external_value(PARAM_TEXT, 'Plugin name'),
                    'displayname' => new external_value(PARAM_TEXT, 'Display name'),
                    'release' => new external_value(PARAM_TEXT, 'Release version'),
                    'version' => new external_value(PARAM_TEXT, 'Version'),
                    'enabled' => new external_value(PARAM_INT, 'Enabled status'),
                    'source' => new external_value(PARAM_TEXT, 'Source'),
                ))
            ),
        ));
    }

    /**
     * Parameter definition for get_metrics
     */
    public static function get_metrics_parameters() {
        return new external_function_parameters(array());
    }

    public static function get_metrics_returns() {
        return new external_single_structure(array(
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'metrics' => new external_single_structure(array(
                'active_sessions' => new external_value(PARAM_INT, 'Active sessions'),
                'cron_lastrun' => new external_value(PARAM_INT, 'Cron last run'),
                'cron_nextrun' => new external_value(PARAM_INT, 'Cron next run'),
                'adhoc_tasks_pending' => new external_value(PARAM_INT, 'Adhoc tasks pending'),
            )),
        ));
    }

    /**
     * Parameter definition for get_database_stats
     */
    public static function get_database_stats_parameters() {
        return new external_function_parameters(array());
    }

    public static function get_database_stats_returns() {
        return new external_single_structure(array(
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'stats' => new external_single_structure(array(
                'course_count' => new external_value(PARAM_INT, 'Course count'),
                'user_count' => new external_value(PARAM_INT, 'User count'),
                'table_count' => new external_value(PARAM_INT, 'Table count'),
                'largest_tables' => new external_multiple_structure(
                    new external_single_structure(array(
                        'name' => new external_value(PARAM_TEXT, 'Table name'),
                        'size_mb' => new external_value(PARAM_FLOAT, 'Size in MB'),
                    ))
                ),
            )),
        ));
    }
}
