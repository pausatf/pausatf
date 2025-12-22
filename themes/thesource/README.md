# TheSource WordPress Theme (Parent)

**Parent theme for pausatf.org - TheSource v4.8.13 by Elegant Themes**

[![WordPress](https://img.shields.io/badge/WordPress-6.9-blue)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL--2.0-red)](LICENSE)

---

## Overview

This is the parent theme (TheSource v4.8.13 by Elegant Themes) used for pausatf.org. This repository exists for version control and deployment purposes.

**⚠️ IMPORTANT:** Do not modify this parent theme directly. All customizations should be made in the child theme: [pausatf-theme-thesource-child](https://github.com/pausatf/pausatf-theme-thesource-child)

**Theme Details:**
- **Theme Name:** TheSource
- **Version:** 4.8.13
- **Author:** Elegant Themes
- **Author URI:** http://www.elegantthemes.com
- **Description:** 2 Column theme from Elegant Themes

---

## Installation

```bash
cd wp-content/themes/
git clone https://github.com/pausatf/pausatf-theme-thesource.git TheSource
```

Then activate the **child theme** (TheSource-child) in WordPress admin.

---

## Usage

This parent theme should be installed but NOT activated directly. Always activate the child theme.

**Child Theme Repository:** [pausatf-theme-thesource-child](https://github.com/pausatf/pausatf-theme-thesource-child)

---

## Deployment

### To Production

```bash
ssh root@prod.pausatf.org "cd /var/www/html/wp-content/themes/TheSource && git pull origin main"
```

---

## Support

This is a third-party theme by Elegant Themes. For PAUSATF-specific customizations, see the child theme repository.

**Related Documentation:**
- [Infrastructure Documentation](https://github.com/pausatf/pausatf-infrastructure-docs)
- [Child Theme](https://github.com/pausatf/pausatf-theme-thesource-child)

---

## License

GPL-2.0 - Original theme by Elegant Themes

---

**Maintained by:** PAUSATF Infrastructure Team
**Last Updated:** December 21, 2025
