# WordPress Role

This role handles the deployment and configuration of WordPress installs, including database setup, file management, and plugin installations.

## Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `wordpress_enabled` | Enable or disable WordPress tasks. | `true` |
| `wpcli_enabled` | Enable or disable WP-CLI installation. | `true` |
| `is_active_web` | Marks whether this node is an active web server. | `true` |
| `wp_environment_type` | Sets the WordPress environment (e.g., production, staging). | `production` |
| `wordpress_installs` | A list of specific WordPress site installations to manage. | `[]` |
| `wp_main_plugins` | A default list of plugins to be installed on WordPress sites. | `[]` |

## Components

- **WP-CLI:** Standard management tool for WordPress.
- **mu-plugins:** Custom Must-Use plugins provided by the role.
- **Themes:** Support for parent and child theme deployments.
