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
 * DevControl Container Actions
 *
 * @package    local_devcontrol
 * @copyright  2024 DevControl Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/devcontrol/lib.php');

require_login();
require_capability('local/devcontrol:containers', context_system::instance());

$action = required_param('action', PARAM_TEXT);
$container = required_param('container', PARAM_TEXT);
$sesskey = required_param('sesskey', PARAM_TEXT);

// Validate session key
if (!confirm_sesskey($sesskey)) {
    throw new moodle_exception('invalidsesskey');
}

// Validate action
$valid_actions = array('start', 'stop', 'restart');
if (!in_array($action, $valid_actions)) {
    throw new moodle_exception('invalid_action', 'local_devcontrol');
}

// Validate container name
if (!local_devcontrol_validate_container_name($container)) {
    throw new moodle_exception('invalid_container_name', 'local_devcontrol');
}

// Check if plugin is enabled
if (!local_devcontrol_is_enabled()) {
    throw new moodle_exception('plugin_disabled', 'local_devcontrol');
}

// Execute the action
try {
    $result = local_devcontrol_external::manage_containers($action, $container);
    
    if ($result['success']) {
        redirect(new moodle_url('/local/devcontrol/index.php'), 
                get_string("success_container_{$action}ed", 'local_devcontrol'), 
                null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect(new moodle_url('/local/devcontrol/index.php'), 
                $result['message'], 
                null, \core\output\notification::NOTIFY_ERROR);
    }
} catch (Exception $e) {
    redirect(new moodle_url('/local/devcontrol/index.php'), 
            'Error: ' . $e->getMessage(), 
            null, \core\output\notification::NOTIFY_ERROR);
}
