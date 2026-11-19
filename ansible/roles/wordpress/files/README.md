# WordPress role files

The `wordpress` role copies these files to hosts whose `active_theme` is `TheSource-child`.
Only the paths named in `tasks/` are deployed; this README is not.

- `TheSource/`: TheSource parent theme (4.8.13, Elegant Themes). Vendored; do not edit it.
  Deploy-time PHP 8.4 fixes are applied by `tasks/patches.yml`.
- `TheSource-child/`: the pausatf.org child theme (1.3.0). Make all theme changes here.
  Its PHP matches the live capture in `pausatf/pausatf-wordpress` (2026-07-21) apart from comments.
- `mu-plugins/`, `cf-auto-purge.php`, `wp-cron-if-active.sh`: must-use plugins and cron helper.

The `copy` tasks add and overwrite files but never remove them, so deleting a file here does not
delete it from a host.

Test theme changes in staging before production. Production is moving to the GeneratePress
child theme (`pausatf/pausatf-generatepress-child`, issue #148).
