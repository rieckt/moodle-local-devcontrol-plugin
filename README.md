# Moodle DevControl Plugin

A Moodle local plugin that provides webservice functions for managing Docker containers and system operations in a development environment.

## Features

- **Docker Container Management**: Start, stop, and restart Docker containers
- **Container Status**: Get detailed information about running containers
- **Log Access**: Retrieve container logs for debugging
- **Backup/Restore**: Backup and restore Moodle database
- **System Information**: Get system and Docker status information

## Installation

### Automatic Installation

The plugin is automatically installed during the Moodle Dev Control setup process:

```bash
./setup.sh
```

### Manual Installation

1. Clone the plugin repository:

```bash
git clone https://github.com/rieckt/moodle-local-devcontrol.git
```

2. Copy to Moodle local directory:

```bash
cp -r moodle-local-devcontrol /path/to/moodle/local/devcontrol
```

3. Run Moodle upgrade:

```bash
php admin/cli/upgrade.php --non-interactive
```

## Configuration

### Webservice Setup

1. Go to **Site administration > Server > Web services > External services**
2. Enable the "DevControl API Service"
3. Go to **Site administration > Server > Web services > Manage tokens**
4. Create a token for the devcontrol user

### Plugin Settings

Go to **Site administration > Plugins > Local plugins > DevControl** to configure:

- **Enable DevControl**: Enable/disable plugin functionality
- **Docker Path**: Path to Docker executable (default: docker)
- **Backup Path**: Path for backup files

## Webservice Functions

### Core Functions

- `local_devcontrol_get_system_info`: Get system information and Docker status
- `local_devcontrol_get_container_status`: Get detailed container information
- `local_devcontrol_get_logs`: Retrieve container logs

### Management Functions

- `local_devcontrol_start_container`: Start a Docker container
- `local_devcontrol_stop_container`: Stop a Docker container
- `local_devcontrol_restart_container`: Restart a Docker container
- `local_devcontrol_manage_containers`: Generic container management

### Backup Functions

- `local_devcontrol_backup_restore`: Backup or restore Moodle database

## API Usage Examples

### Get System Information

```bash
curl "http://localhost:8000/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN" \
  -d "wsfunction=local_devcontrol_get_system_info" \
  -d "moodlewsrestformat=json"
```

### Start Container

```bash
curl "http://localhost:8000/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN" \
  -d "wsfunction=local_devcontrol_start_container" \
  -d "container=moodle-dev-webserver" \
  -d "moodlewsrestformat=json"
```

### Get Container Logs

```bash
curl "http://localhost:8000/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN" \
  -d "wsfunction=local_devcontrol_get_logs" \
  -d "container=moodle-dev-webserver" \
  -d "lines=50" \
  -d "moodlewsrestformat=json"
```

## Requirements

- Moodle 4.1+
- Docker (for container management)
- MySQL/MariaDB (for backup/restore)
- PHP 8.1+

## Permissions

The plugin requires the following capabilities:

- `moodle/site:config`: For all webservice functions
- `local/devcontrol:view`: To view DevControl information
- `local/devcontrol:manage`: To manage DevControl settings

## Security

- All webservice functions require `moodle/site:config` capability
- Docker commands are executed with system privileges
- Backup files are stored in moodledata/backup directory
- Input validation is performed on all parameters

## Troubleshooting

### Docker Not Available

If Docker is not available on the system:

1. Install Docker
2. Ensure Docker daemon is running
3. Check Docker path in plugin settings

### Permission Denied

If you get permission errors:

1. Ensure the web server user has Docker access
2. Add user to docker group: `sudo usermod -aG docker www-data`
3. Restart web server

### Backup Failed

If backup operations fail:

1. Check MySQL credentials
2. Ensure backup directory is writable
3. Verify database connection

## Development

### Adding New Functions

1. Add function definition to `db/services.php`
2. Implement function in `classes/external.php`
3. Add parameter and return definitions
4. Update language strings in `lang/en/local_devcontrol.php`

### Testing

Test webservice functions using:

```bash
# Test system info
curl "http://localhost:8000/webservice/rest/server.php?wstoken=YOUR_TOKEN&wsfunction=local_devcontrol_get_system_info&moodlewsrestformat=json"

# Test container status
curl "http://localhost:8000/webservice/rest/server.php?wstoken=YOUR_TOKEN&wsfunction=local_devcontrol_get_container_status&moodlewsrestformat=json"
```

## License

This plugin is licensed under the GNU GPL v3 or later.

## Support

For issues and questions:

1. Check the troubleshooting section
2. Review Moodle logs
3. Contact the development team

## Changelog

### Version 1.0.0

- Initial release
- Docker container management
- System information
- Backup/restore functionality
- Webservice API
