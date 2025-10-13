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
 * DevControl Plugin Library Functions
 *
 * @package    local_devcontrol
 * @copyright  2024 DevControl Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Check if DevControl plugin is enabled
 *
 * @return bool True if enabled, false otherwise
 */
function local_devcontrol_is_enabled() {
    return get_config('local_devcontrol', 'enabled');
}

/**
 * Get Docker executable path from settings
 *
 * @return string Docker executable path
 */
function local_devcontrol_get_docker_path() {
    $path = get_config('local_devcontrol', 'docker_path');
    return $path ?: 'docker';
}

/**
 * Get backup directory path from settings
 *
 * @return string Backup directory path
 */
function local_devcontrol_get_backup_path() {
    global $CFG;
    $path = get_config('local_devcontrol', 'backup_path');
    return $path ?: $CFG->dataroot . '/backup';
}

/**
 * Validate container name for security
 *
 * @param string $container Container name
 * @return bool True if valid, false otherwise
 */
function local_devcontrol_validate_container_name($container) {
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
 * Log DevControl activity
 *
 * @param string $action Action performed
 * @param string $details Additional details
 * @param bool $success Whether the action was successful
 */
function local_devcontrol_log($action, $details = '', $success = true) {
    global $USER;
    
    $logdata = array(
        'userid' => $USER->id,
        'action' => 'devcontrol_' . $action,
        'info' => $details,
        'time' => time(),
        'ip' => getremoteaddr(),
        'success' => $success ? 1 : 0
    );
    
    // Use Moodle's logging system
    $event = \local_devcontrol\event\action_performed::create(array(
        'context' => context_system::instance(),
        'other' => $logdata
    ));
    $event->trigger();
}

/**
 * Check if user has DevControl permissions
 *
 * @param string $capability Capability to check
 * @return bool True if user has permission, false otherwise
 */
function local_devcontrol_has_capability($capability = 'local/devcontrol:view') {
    $context = context_system::instance();
    return has_capability($capability, $context);
}

/**
 * Get plugin version information
 *
 * @return array Version information
 */
function local_devcontrol_get_version_info() {
    global $CFG;
    
    $plugin = new stdClass();
    require_once($CFG->dirroot . '/local/devcontrol/version.php');
    
    return array(
        'version' => $plugin->version,
        'release' => $plugin->release,
        'maturity' => $plugin->maturity,
        'requires' => $plugin->requires,
        'component' => $plugin->component
    );
}

/**
 * Extend navigation for DevControl
 *
 * @param global_navigation $nav Global navigation instance
 */
function local_devcontrol_extend_navigation(global_navigation $nav) {
    global $CFG, $USER;
    
    if (!local_devcontrol_is_enabled() || !local_devcontrol_has_capability()) {
        return;
    }
    
    // Add DevControl node to admin section
    $adminnode = $nav->find('siteadmin', global_navigation::TYPE_SITEADMIN);
    if ($adminnode) {
        $devcontrolnode = $adminnode->add(
            get_string('pluginname', 'local_devcontrol'),
            new moodle_url('/local/devcontrol/index.php'),
            global_navigation::TYPE_CUSTOM,
            null,
            'devcontrol'
        );
        $devcontrolnode->make_active();
    }
}

/**
 * Extend settings navigation for DevControl
 *
 * @param settings_navigation $nav Settings navigation instance
 * @param context $context Current context
 */
function local_devcontrol_extend_settings_navigation(settings_navigation $nav, context $context) {
    global $CFG, $USER;
    
    if (!local_devcontrol_is_enabled() || !local_devcontrol_has_capability('local/devcontrol:manage')) {
        return;
    }
    
    // Add DevControl settings to admin section
    $adminnode = $nav->find('root', settings_navigation::TYPE_SITEADMIN);
    if ($adminnode) {
        $devcontrolnode = $adminnode->add(
            get_string('pluginname', 'local_devcontrol'),
            new moodle_url('/local/devcontrol/settings.php'),
            settings_navigation::TYPE_CUSTOM,
            null,
            'devcontrol_settings'
        );
    }
}
