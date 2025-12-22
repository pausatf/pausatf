# TheSource Child Theme for PAUSATF.org

**WordPress child theme for pausatf.org based on TheSource by Elegant Themes**

[![WordPress](https://img.shields.io/badge/WordPress-6.9-blue)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL--2.0-red)](LICENSE)

---

## Overview

This is a custom child theme for pausatf.org (Pacific Association of USA Track & Field) based on TheSource theme from Elegant Themes. The child theme contains all customizations, modifications, and overrides specific to the PAUSATF website.

**Parent Theme:** TheSource v4.8.13 by Elegant Themes
**Child Theme Version:** 0.2
**Website:** https://www.pausatf.org

---

## Features & Customizations

### Custom Functionality
- `ClubsPHP.php` - Custom club listings and management
- Custom dropdown menu styling (`dropdown.css`)
- Chrome-specific fixes (`chrome-fixes.css`)
- Custom header modifications
- Custom footer modifications
- Custom single post template
- Custom blog page template

### Color Schemes
- Default theme styling (`style.css`)
- Blue color scheme (`style-Blue.css`)
- Light color scheme (`style-Light.css`)
- Red color scheme (`style-Red.css`)

### Custom Templates
- `page-blog.php` - Custom blog page layout
- `single.php` - Custom single post layout
- `header.php` - Customized header
- `footer.php` - Customized footer

### Widgets & Includes
Located in `includes/` directory:
- Breadcrumb navigation
- Entry/post formatting
- Featured content
- Navigation enhancements
- Post info display
- Recent categories
- Custom scripts
- Widget modifications

---

## Installation

### Prerequisites
- WordPress 6.0 or higher
- PHP 7.4 or higher
- TheSource parent theme v4.8.13 (see [pausatf-theme-thesource](https://github.com/pausatf/pausatf-theme-thesource))

### Steps

1. **Install Parent Theme First:**
   ```bash
   cd wp-content/themes/
   git clone https://github.com/pausatf/pausatf-theme-thesource.git TheSource
   ```

2. **Install Child Theme:**
   ```bash
   cd wp-content/themes/
   git clone https://github.com/pausatf/pausatf-theme-thesource-child.git TheSource-child
   ```

3. **Activate in WordPress:**
   - Go to Appearance → Themes
   - Activate "TheSource-child"

---

## Development

### Local Development Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/pausatf/pausatf-theme-thesource-child.git
   cd pausatf-theme-thesource-child
   ```

2. **Make your changes**

3. **Test thoroughly:**
   - Test on local WordPress installation
   - Test with parent theme
   - Test all color schemes
   - Test custom templates

### File Structure

```
TheSource-child/
├── README.md                    # This file
├── CHANGELOG.md                 # Version history
├── LICENSE                      # GPL-2.0 license
├── style.css                    # Main stylesheet and theme metadata
├── functions.php                # Theme functions and hooks
├── ClubsPHP.php                 # Custom club functionality
├── chrome-fixes.css             # Browser-specific fixes
├── dropdown.css                 # Dropdown menu styling
│
├── images/                      # Theme images and graphics
│   ├── blue/                    # Blue theme graphics
│   ├── light/                   # Light theme graphics
│   └── red/                     # Red theme graphics
│
└── includes/                    # PHP includes and templates
    ├── breadcrumb.php
    ├── entry.php
    ├── featured.php
    ├── navigation.php
    ├── widgets.php
    ├── functions/               # Function libraries
    └── widgets/                 # Custom widgets
```

### Best Practices

**DO:**
- ✅ Test all changes locally before deploying
- ✅ Use child theme for all customizations
- ✅ Document your changes in CHANGELOG.md
- ✅ Follow WordPress coding standards
- ✅ Keep backup files (.bak) out of version control
- ✅ Test with parent theme updates

**DON'T:**
- ❌ Modify parent theme files
- ❌ Commit backup files (*.bak)
- ❌ Commit system files (._*, Thumbs.db)
- ❌ Hard-code site-specific URLs
- ❌ Remove security features

---

## Customizations Documentation

### Header Modifications
- Custom logo handling
- Custom navigation menu
- Responsive design adjustments

### Footer Modifications
- Custom widget areas
- Custom footer content
- Social media integration

### Color Schemes
Three alternative color schemes available:
- **Blue:** Professional blue theme
- **Light:** Light and minimal theme
- **Red:** Bold red theme

To change color scheme, enqueue different stylesheet in `functions.php`.

### Custom Club Functionality
`ClubsPHP.php` provides custom functionality for managing and displaying PAUSATF member clubs.

---

## Deployment

### To Staging

```bash
# On local machine
git push origin main

# On staging server
cd /var/www/html/wp-content/themes/TheSource-child
git pull origin main
```

### To Production

1. **Test on staging first**
2. **Create backup:**
   ```bash
   ssh root@prod.pausatf.org "cd /var/www/html/wp-content/themes && tar -czf ~/TheSource-child-backup-$(date +%Y%m%d).tar.gz TheSource-child"
   ```

3. **Deploy:**
   ```bash
   ssh root@prod.pausatf.org "cd /var/www/html/wp-content/themes/TheSource-child && git pull origin main"
   ```

4. **Clear caches:**
   ```bash
   # WordPress cache
   wp cache flush --path=/var/www/html/ --allow-root

   # Cloudflare cache
   /usr/local/bin/purge_cloudflare_cache.sh all
   ```

---

## Troubleshooting

### Theme Not Showing Up
- Ensure parent theme is installed
- Check theme directory name is exactly `TheSource-child`
- Verify file permissions

### Styles Not Loading
- Clear WordPress cache
- Clear browser cache
- Check file paths in `functions.php`
- Verify parent theme is active

### Customizations Not Working
- Check `functions.php` for errors
- Review WordPress error log
- Ensure parent theme compatibility
- Check PHP version compatibility

---

## Security

### Best Practices Implemented
- ✅ Sanitized user inputs
- ✅ Escaped outputs
- ✅ Secure database queries
- ✅ Nonce verification for forms
- ✅ Capability checks for admin functions

### Security Audits
- Last security audit: December 21, 2025
- See [Security Audit Report](https://github.com/pausatf/pausatf-infrastructure-docs/blob/main/docs/reports/04-security-audit-report.md)

---

## Contributing

This is a private theme for pausatf.org. For authorized contributors:

1. Create a feature branch
2. Make your changes
3. Test thoroughly
4. Submit pull request
5. Wait for review and approval

See [CONTRIBUTING.md](CONTRIBUTING.md) for details.

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

**Latest Changes:**
- v0.2: Custom modifications for PAUSATF
- Added custom club functionality
- Custom header and footer
- Multiple color scheme support

---

## Support & Documentation

### Resources
- **PAUSATF Website:** https://www.pausatf.org
- **Infrastructure Docs:** https://github.com/pausatf/pausatf-infrastructure-docs
- **Parent Theme:** https://github.com/pausatf/pausatf-theme-thesource
- **WordPress Codex:** https://codex.wordpress.org/Child_Themes

### Getting Help
- 💬 [GitHub Discussions](https://github.com/pausatf/pausatf-infrastructure-docs/discussions)
- 🐛 [Report Issues](https://github.com/pausatf/pausatf-theme-thesource-child/issues)
- 📖 [Documentation](https://github.com/pausatf/pausatf-infrastructure-docs/wiki)

---

## License

This child theme is licensed under the GPL-2.0 License - see the [LICENSE](LICENSE) file for details.

**Parent Theme:** TheSource by Elegant Themes (GPL-2.0)

---

## Maintainers

**Primary Maintainer:** PAUSATF Infrastructure Team
**Repository:** https://github.com/pausatf/pausatf-theme-thesource-child
**Organization:** Pacific Association of USA Track and Field

---

## Related Repositories

| Repository | Purpose |
|------------|---------|
| [pausatf-theme-thesource](https://github.com/pausatf/pausatf-theme-thesource) | Parent theme |
| [pausatf-infrastructure-docs](https://github.com/pausatf/pausatf-infrastructure-docs) | Infrastructure documentation |
| [pausatf-ansible](https://github.com/pausatf/pausatf-ansible) | WordPress configuration management |
| [pausatf-scripts](https://github.com/pausatf/pausatf-scripts) | Deployment and maintenance scripts |

---

**Last Updated:** December 21, 2025
