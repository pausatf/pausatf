# Contributing to TheSource Parent Theme

## ⚠️ Important: Do Not Modify This Repository

This repository contains the **parent theme** (TheSource v4.8.13 by Elegant Themes) and should **NOT be modified directly**.

### Why Not Modify the Parent Theme?

1. **Updates:** Parent theme updates would overwrite any modifications
2. **Upgrades:** When Elegant Themes releases updates, we need to be able to upgrade safely
3. **Maintenance:** Keeping the parent theme pristine makes troubleshooting easier
4. **Best Practice:** WordPress child themes exist specifically to avoid modifying parent themes

## Where Should I Make Changes?

All PAUSATF customizations should be made in the **child theme repository**:

**👉 [pausatf-theme-thesource-child](https://github.com/pausatf/pausatf-theme-thesource-child)**

### What Goes in the Child Theme?

- Custom templates
- Style overrides
- New functionality
- Custom widgets or shortcodes
- JavaScript modifications
- Any PAUSATF-specific features

See the [child theme CONTRIBUTING.md](https://github.com/pausatf/pausatf-theme-thesource-child/blob/main/CONTRIBUTING.md) for full development guidelines.

## When Would We Update This Repository?

This parent theme repository would only be updated when:

1. **Elegant Themes releases a new version** of TheSource
2. **Security patches** are released
3. **Critical bug fixes** from Elegant Themes

These updates would be carefully tested on staging before deploying to production.

## Reporting Issues

### Found a Bug in PAUSATF Site?

If you found a bug on pausatf.org:
- It's likely in the child theme customizations
- Report it here: [Child Theme Issues](https://github.com/pausatf/pausatf-theme-thesource-child/issues)

### Found a Bug in TheSource Theme Itself?

If you found a bug in the parent TheSource theme:
- Contact [Elegant Themes Support](https://www.elegantthemes.com/contact/)
- Open an issue here to track it: [Parent Theme Issues](https://github.com/pausatf/pausatf-theme-thesource/issues)

## Theme Architecture

```
WordPress Installation
├── wp-content/themes/
    ├── TheSource/              ← This repo (DO NOT MODIFY)
    │   └── (parent theme files)
    └── TheSource-child/        ← Development happens here
        └── (our customizations)
```

WordPress loads themes in this order:
1. Child theme `functions.php` (our code runs first)
2. Parent theme `functions.php`
3. Template files (child overrides parent)
4. Stylesheets (child extends parent)

## Need Help?

- **Theme Development:** See [child theme repository](https://github.com/pausatf/pausatf-theme-thesource-child)
- **Infrastructure:** See [infrastructure docs](https://github.com/pausatf/pausatf-infrastructure-docs)
- **Questions:** Use [GitHub Discussions](https://github.com/pausatf/pausatf-infrastructure-docs/discussions)
- **Contact:** Reach out to @thomasvincent

## Resources

- [WordPress Child Themes](https://developer.wordpress.org/themes/advanced-topics/child-themes/)
- [TheSource Documentation](https://elegantthemes.com/documentation/thesource/)
- [PAUSATF Infrastructure Docs](https://github.com/pausatf/pausatf-infrastructure-docs)

---

**Remember: All development should happen in the [child theme](https://github.com/pausatf/pausatf-theme-thesource-child)!**
