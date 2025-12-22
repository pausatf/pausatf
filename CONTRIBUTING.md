# Contributing to PAUSATF Child Theme

Thank you for your interest in contributing to the PAUSATF WordPress theme! This document provides guidelines for making contributions.

## Table of Contents

- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Testing Requirements](#testing-requirements)
- [Submitting Changes](#submitting-changes)
- [Theme Architecture](#theme-architecture)

## Getting Started

### Prerequisites

- Access to the PAUSATF infrastructure
- WordPress development environment or access to staging site
- Git installed locally
- Basic knowledge of WordPress theme development
- Familiarity with TheSource parent theme

### Development Environment Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/pausatf/pausatf-theme-thesource-child.git
   cd pausatf-theme-thesource-child
   ```

2. **Important:** This is a child theme. You'll also need the parent theme:
   - Parent theme: [pausatf-theme-thesource](https://github.com/pausatf/pausatf-theme-thesource)
   - **Never modify the parent theme directly**
   - All customizations must be in this child theme

3. **WordPress setup:**
   - WordPress 6.9+ recommended
   - PHP 7.4+ required (tested up to PHP 8.1)
   - MySQL 5.7+ or MariaDB 10.2+

## Development Workflow

### 1. Create an Issue First

Before starting work, create or comment on an issue:
- **Bug reports:** Use the "Theme Bug Report" template
- **New features:** Use the "Theme Feature Request" template
- Discuss your approach before coding

### 2. Branch Naming Convention

Create a branch from `main` using this format:
```
feature/issue-number-brief-description
bugfix/issue-number-brief-description
enhancement/issue-number-brief-description
```

Examples:
```bash
git checkout -b feature/123-add-club-search
git checkout -b bugfix/456-fix-mobile-menu
git checkout -b enhancement/789-improve-footer
```

### 3. Making Changes

#### What Goes in the Child Theme?

**✅ DO add to child theme:**
- Custom templates (copy from parent, then modify)
- New functionality (functions.php)
- Style overrides (style.css)
- Custom JavaScript
- New template parts
- Custom widgets or shortcodes

**❌ DON'T modify parent theme:**
- Never edit files in `../TheSource/`
- All changes must be in the child theme
- Override parent files by copying to child theme

#### File Organization

```
pausatf-theme-thesource-child/
├── css/              # Custom stylesheets
│   ├── Blue/        # Color scheme variants
│   ├── Light/
│   ├── Red/
│   └── dropdown.css # Custom component styles
├── images/          # Theme images and assets
├── ClubsPHP.php     # Custom club functionality
├── functions.php    # Theme functions and hooks
├── style.css        # Main stylesheet (required)
├── header.php       # Custom header template
├── footer.php       # Custom footer template
├── single.php       # Single post template
└── page-blog.php    # Blog page template
```

## Coding Standards

### PHP

Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/):

```php
<?php
/**
 * Brief description of what this file does
 *
 * @package PAUSATF_Child_Theme
 */

// Example function
function pausatf_custom_function( $param ) {
    // Use WordPress functions when possible
    if ( ! empty( $param ) ) {
        return esc_html( $param );
    }
    return false;
}
```

**Key points:**
- Use WordPress functions (`esc_html()`, `esc_attr()`, `wp_enqueue_script()`, etc.)
- Prefix all custom functions with `pausatf_`
- Add proper docblocks
- Sanitize inputs, escape outputs
- Use `wp_nonce_field()` for forms

### CSS

Follow the existing style patterns:

```css
/* Component or section description */
.pausatf-component {
    property: value;
}

.pausatf-component__element {
    property: value;
}

.pausatf-component--modifier {
    property: value;
}
```

**Key points:**
- Use BEM-like naming for new components
- Maintain existing naming for TheSource overrides
- Include browser prefixes for modern CSS properties
- Test on all major browsers
- Consider mobile-first approach

### JavaScript

If adding JavaScript:

```javascript
/**
 * Brief description of what this script does
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Your code here
    });

})(jQuery);
```

**Key points:**
- Use jQuery if needed (already included in WordPress)
- Wrap in IIFE to avoid global scope pollution
- Use strict mode
- Enqueue properly in `functions.php`

## Testing Requirements

### Before Submitting a PR

Test your changes thoroughly:

#### Browser Testing
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Mobile (Android)

#### Device Testing
- [ ] Desktop (1920x1080)
- [ ] Laptop (1366x768)
- [ ] Tablet (768x1024)
- [ ] Mobile (375x667)

#### Page Testing
Test on these key pages:
- [ ] Homepage
- [ ] Blog listing page
- [ ] Single blog post
- [ ] Club listings (ClubsPHP.php)
- [ ] Standard page template

#### Functionality Testing
- [ ] Navigation menus work correctly
- [ ] Forms submit properly
- [ ] Images load and display correctly
- [ ] No JavaScript console errors
- [ ] No PHP errors in error log

#### Performance Testing
- [ ] Page load time not significantly impacted
- [ ] Images are optimized
- [ ] CSS/JS files are minified (if custom)
- [ ] No unnecessary HTTP requests

#### Accessibility Testing
- [ ] Keyboard navigation works
- [ ] Screen reader compatible
- [ ] Color contrast meets WCAG AA
- [ ] ARIA labels where appropriate

## Submitting Changes

### 1. Commit Your Changes

Write clear commit messages:

```bash
git add .
git commit -m "Fix mobile menu dropdown on iOS

- Corrected z-index issue causing menu to hide behind content
- Added touch event handling for iOS devices
- Tested on iPhone 12 and iPad Pro

Fixes #456"
```

**Commit message format:**
- First line: Brief summary (50 chars max)
- Blank line
- Detailed explanation (wrap at 72 chars)
- Reference issue number

### 2. Push to GitHub

```bash
git push origin feature/123-add-club-search
```

### 3. Create Pull Request

1. Go to GitHub and create a Pull Request
2. Fill out the PR template completely
3. Link the related issue
4. Add screenshots if visual changes
5. Request review from @thomasvincent

### 4. Code Review Process

- Reviewer will check code quality and standards
- May request changes or improvements
- Address feedback and push updates
- Once approved, PR will be merged to main

### 5. Deployment

After merge:
- Changes are tested on staging
- Deployed to production during maintenance window
- Monitored for 24-48 hours

## Theme Architecture

### Parent-Child Relationship

```
TheSource (Parent)           TheSource-child (Child)
├── style.css               ├── style.css (extends parent)
├── functions.php           ├── functions.php (extends parent)
├── header.php              ├── header.php (overrides parent)
├── footer.php              ├── footer.php (overrides parent)
└── ... (all other files)   └── ... (only overrides needed)
```

**How WordPress loads the theme:**
1. Child theme `functions.php` loads first
2. Parent theme `functions.php` loads
3. Child theme templates override parent templates
4. Child theme `style.css` loaded after parent

### Key Customizations

Current child theme customizations:

1. **Club Listings** (`ClubsPHP.php`)
   - Custom functionality for displaying PAUSATF clubs
   - Shortcode for club directory

2. **Color Schemes** (`css/Blue/`, `css/Light/`, `css/Red/`)
   - Multiple color scheme options
   - Dynamically switchable

3. **Custom Navigation** (`dropdown.css`, `chrome-fixes.css`)
   - Enhanced dropdown menus
   - Browser-specific fixes

4. **Template Overrides**
   - `header.php`: Custom header with PAUSATF branding
   - `footer.php`: Custom footer with additional widgets
   - `single.php`: Single post template modifications
   - `page-blog.php`: Custom blog page layout

### WordPress Hooks Used

Common hooks in child theme:

```php
// Enqueue scripts and styles
add_action( 'wp_enqueue_scripts', 'pausatf_enqueue_styles' );

// Modify excerpt length
add_filter( 'excerpt_length', 'pausatf_excerpt_length' );

// Add custom widget areas
add_action( 'widgets_init', 'pausatf_widgets_init' );
```

## Questions or Need Help?

- **Documentation:** Check the [main README](README.md)
- **Issues:** Search [existing issues](https://github.com/pausatf/pausatf-theme-thesource-child/issues)
- **Discussions:** Use [GitHub Discussions](https://github.com/pausatf/pausatf-theme-thesource-child/discussions)
- **Contact:** Reach out to @thomasvincent

## Additional Resources

- [WordPress Theme Development](https://developer.wordpress.org/themes/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Child Themes](https://developer.wordpress.org/themes/advanced-topics/child-themes/)
- [TheSource Theme Documentation](https://elegantthemes.com/documentation/thesource/)

---

**Thank you for contributing to PAUSATF!**
