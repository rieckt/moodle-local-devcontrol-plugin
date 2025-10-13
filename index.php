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
 * DevControl Dashboard
 *
 * @package    local_devcontrol
 * @copyright  2024 DevControl Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/devcontrol/lib.php');

require_login();
require_capability('local/devcontrol:view', context_system::instance());

$PAGE->set_url('/local/devcontrol/index.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_devcontrol'));
$PAGE->set_heading(get_string('pluginname', 'local_devcontrol'));
$PAGE->set_pagelayout('admin');

// Add navigation
$PAGE->navbar->add(get_string('pluginname', 'local_devcontrol'));

echo $OUTPUT->header();

// Check if plugin is enabled
if (!local_devcontrol_is_enabled()) {
    echo $OUTPUT->notification(get_string('plugin_disabled', 'local_devcontrol'), 'notifywarning');
    echo $OUTPUT->footer();
    exit;
}

// Get system information
try {
    $system_info = local_devcontrol_external::get_system_info();
    $container_status = local_devcontrol_external::get_container_status();
    $metrics = local_devcontrol_external::get_metrics();
} catch (Exception $e) {
    echo $OUTPUT->notification('Error: ' . $e->getMessage(), 'notifyerror');
    echo $OUTPUT->footer();
    exit;
}

// Display system information
echo $OUTPUT->heading(get_string('system_info', 'local_devcontrol'), 3);

$system_table = new html_table();
$system_table->head = array(
    get_string('property', 'local_devcontrol'),
    get_string('value', 'local_devcontrol')
);

$system_table->data = array(
    array('Moodle Version', $system_info['moodle_version']),
    array('Moodle Release', $system_info['moodle_release']),
    array('Site Name', $system_info['site_name']),
    array('PHP Version', $system_info['php_version']),
    array('Server Software', $system_info['server_software']),
    array('Docker Status', $system_info['docker_status']['available'] ? 'Available' : 'Not Available'),
);

echo html_writer::table($system_table);

// Display container status
if ($container_status['success']) {
    echo $OUTPUT->heading(get_string('container_status', 'local_devcontrol'), 3);
    
    if (empty($container_status['containers'])) {
        echo $OUTPUT->notification(get_string('no_containers', 'local_devcontrol'), 'notifyinfo');
    } else {
        $container_table = new html_table();
        $container_table->head = array(
            get_string('container_name', 'local_devcontrol'),
            get_string('container_image', 'local_devcontrol'),
            get_string('container_status', 'local_devcontrol'),
            get_string('actions', 'local_devcontrol')
        );
        
        foreach ($container_status['containers'] as $container) {
            $actions = '';
            if (has_capability('local/devcontrol:containers', context_system::instance())) {
                $actions .= html_writer::link(
                    new moodle_url('/local/devcontrol/action.php', array('action' => 'start', 'container' => $container['name'])),
                    get_string('start', 'local_devcontrol'),
                    array('class' => 'btn btn-sm btn-success')
                ) . ' ';
                $actions .= html_writer::link(
                    new moodle_url('/local/devcontrol/action.php', array('action' => 'stop', 'container' => $container['name'])),
                    get_string('stop', 'local_devcontrol'),
                    array('class' => 'btn btn-sm btn-danger')
                ) . ' ';
                $actions .= html_writer::link(
                    new moodle_url('/local/devcontrol/action.php', array('action' => 'restart', 'container' => $container['name'])),
                    get_string('restart', 'local_devcontrol'),
                    array('class' => 'btn btn-sm btn-warning')
                );
            }
            
            $container_table->data[] = array(
                $container['name'],
                $container['image'],
                $container['status'],
                $actions
            );
        }
        
        echo html_writer::table($container_table);
    }
}

// Display metrics
if ($metrics['success']) {
    echo $OUTPUT->heading(get_string('system_metrics', 'local_devcontrol'), 3);
    
    $metrics_table = new html_table();
    $metrics_table->head = array(
        get_string('metric', 'local_devcontrol'),
        get_string('value', 'local_devcontrol')
    );
    
    $metrics_table->data = array(
        array('Active Sessions', $metrics['metrics']['active_sessions']),
        array('Cron Last Run', userdate($metrics['metrics']['cron_lastrun'])),
        array('Cron Next Run', userdate($metrics['metrics']['cron_nextrun'])),
        array('Adhoc Tasks Pending', $metrics['metrics']['adhoc_tasks_pending']),
    );
    
    echo html_writer::table($metrics_table);
}

// Display quick actions
if (has_capability('local/devcontrol:manage', context_system::instance())) {
    echo $OUTPUT->heading(get_string('quick_actions', 'local_devcontrol'), 3);
    
    $actions = array(
        'settings' => new moodle_url('/admin/settings.php', array('section' => 'local_devcontrol_settings')),
        'backup' => new moodle_url('/local/devcontrol/backup.php'),
        'logs' => new moodle_url('/local/devcontrol/logs.php'),
    );
    
    foreach ($actions as $key => $url) {
        echo html_writer::link($url, get_string($key, 'local_devcontrol'), array('class' => 'btn btn-primary mr-2'));
    }
}

echo $OUTPUT->footer();
