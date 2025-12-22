# PAUSATF Legacy Static Content

**Historical static content and data files for pausatf.org**

This repository contains the legacy static content that was hosted on the PAUSATF website before the migration to WordPress. This content includes historical race results, event information, photos, and other static files dating back to the early 2000s.

## Repository Purpose

- **Preservation:** Archive historical PAUSATF content for posterity
- **Version Control:** Track any changes to legacy files
- **Access:** Provide access to historical data and documents
- **Reference:** Maintain historical race results and event records

## Content Overview

This repository contains static files from `/var/www/legacy/public_html/` on the production server.

### Main Directories

- **`data/`** - Historical race results, event schedules, and meet information organized by year (2006-2025)
- **`images/`** - Event photos, logos, and historical images
- **`calendar/`** - Legacy calendar files and event listings
- **`pdf/`** - PDF documents including race results, schedules, and official documents
- **`Flash/`** - Legacy Flash content (historical)
- **`pagallery/`** - Photo galleries from various events
- **`index/`** - Legacy index pages and navigation
- **`PHP/`** - Legacy PHP scripts and utilities
- **`teeters/`** - Historical Teeters family content
- **`WysiwygPro/`** - Legacy WYSIWYG editor files

### Root Files

The root directory contains various legacy HTML pages, PHP scripts, images, and other static assets that were part of the original PAUSATF website.

## File Types

This repository contains:
- HTML files (.html, .htm)
- PHP scripts (.php)
- CSS stylesheets (.css)
- JavaScript files (.js)
- Images (.jpg, .jpeg, .png, .gif)
- PDFs (.pdf)
- Word documents (.doc, .docx)
- Excel files (.xls, .xlsx)
- Video files (.mpg, .mp4, .wmv)
- Text files (.txt)
- Flash files (.swf) - historical

## Historical Context

### Time Period

Content in this repository spans approximately:
- **Earliest content:** ~2000s
- **Most active period:** 2006-2015
- **Recent content:** Through 2025

### Technology

This content represents the pre-WordPress era of pausatf.org:
- Static HTML pages
- PHP-based dynamic content
- Legacy calendar system
- Manual file uploads
- Direct FTP management

## Current Status

### Production Deployment

This content is currently served from:
- **Server:** pausatf-prod (64.225.40.54)
- **Path:** `/var/www/legacy/public_html/`
- **Web Access:** Various legacy URLs on pausatf.org
- **Status:** Read-only archive

### Size Information

- **Total Size:** ~286 MB (4,043 files)
- **Largest Directory:** `data/` (historical race results)
- **Second Largest:** `images/` (event photos)

## Usage Guidelines

### Viewing Historical Content

Files in this repository can be viewed:
1. **On Production Server:** Via legacy URLs on pausatf.org
2. **Locally:** Clone this repository and open HTML files in browser
3. **Archive:** Download specific years or events as needed

### Making Changes

⚠️ **Important:** This is archival content and should generally NOT be modified.

If changes are absolutely necessary:
1. Document the reason in the commit message
2. Create a backup before modifying
3. Test thoroughly before deploying
4. Update this README if structure changes

### Adding New Content

New content should generally go into the WordPress site, not here. However, if legacy-format content needs to be added:
1. Follow existing directory structure
2. Maintain naming conventions
3. Document in commit message
4. Update relevant index pages

## Repository Structure

```
pausatf-legacy-content/
├── data/                   # Race results and event data by year
│   ├── 2006/
│   ├── 2007/
│   ├── ...
│   └── 2025/
├── images/                 # Event photos and images
├── calendar/               # Legacy calendar system
├── pdf/                    # PDF documents
├── pagallery/              # Photo galleries
├── Flash/                  # Legacy Flash content
├── PHP/                    # Legacy PHP utilities
├── index/                  # Legacy navigation
├── teeters/                # Historical Teeters content
├── WysiwygPro/             # Legacy editor files
├── cgi-bin/                # Legacy CGI scripts
├── .smileys/               # Emoticon images
├── *.html                  # Legacy HTML pages
├── *.php                   # Legacy PHP scripts
├── *.css                   # Legacy stylesheets
├── *.js                    # Legacy JavaScript
└── README.md               # This file
```

## Integration with WordPress

The current WordPress site (pausatf.org) may link to or reference some of this legacy content for:
- Historical race results
- Archive pages
- Historical event information
- Legacy photo galleries

Links from WordPress to legacy content should use relative paths or proper URL structure.

## Backup & Recovery

### Backups

This content is backed up in multiple locations:
1. **DigitalOcean Backups:** Daily automated snapshots
2. **Server Backups:** `/root/pausatf-legacy-backup-*.tar.gz`
3. **This GitHub Repository:** Complete version history
4. **Local Clones:** Any checkouts of this repository

### Recovery

To restore this content to the server:
1. Clone this repository
2. rsync to `/var/www/legacy/public_html/`
3. Set proper permissions (www-data:www-data)
4. Test legacy URLs

## Known Issues

### Character Encoding

Some legacy files may have character encoding issues:
- Files with special characters in filenames
- Non-UTF-8 encoded content
- Legacy Latin-1 or ISO-8859-1 encoding

### Deprecated Technology

Some content uses deprecated technologies:
- Flash (.swf files) - requires Flash Player (no longer supported)
- Legacy PHP syntax - may not work on PHP 8+
- Outdated HTML/CSS - may not display correctly on modern browsers

### Broken Links

Some legacy HTML pages may have:
- Broken internal links
- Missing images
- References to removed files
- Absolute URLs to old domains

## Maintenance

### Regular Maintenance

This repository should be reviewed:
- **Annually:** Check for any needed updates or corrections
- **After Events:** Verify new content is added to WordPress, not here
- **Before Migration:** When planning infrastructure changes

### Cleanup Considerations

Consider removing:
- Empty directories
- Duplicate files
- Temporary files (.tmp, .bak)
- System files (.DS_Store, Thumbs.db)
- Editor files (.swp, *~)

## Related Repositories

This repository is part of the PAUSATF infrastructure ecosystem:

- [pausatf-infrastructure-docs](https://github.com/pausatf/pausatf-infrastructure-docs) - Documentation
- [pausatf-terraform](https://github.com/pausatf/pausatf-terraform) - Infrastructure provisioning
- [pausatf-ansible](https://github.com/pausatf/pausatf-ansible) - Configuration management
- [pausatf-scripts](https://github.com/pausatf/pausatf-scripts) - Automation scripts
- [pausatf-theme-thesource](https://github.com/pausatf/pausatf-theme-thesource) - WordPress parent theme
- [pausatf-theme-thesource-child](https://github.com/pausatf/pausatf-theme-thesource-child) - WordPress child theme

## Documentation

For more information:
- [Infrastructure Documentation](https://github.com/pausatf/pausatf-infrastructure-docs)
- [Server Migration Guide](https://github.com/pausatf/pausatf-infrastructure-docs/blob/main/docs/guides/05-server-migration-guide.md)
- [Operational Procedures](https://github.com/pausatf/pausatf-infrastructure-docs/blob/main/docs/guides/10-operational-procedures.md)

## Contact

**For questions about legacy content:**
- Review: [Infrastructure Documentation](https://github.com/pausatf/pausatf-infrastructure-docs)
- Discuss: [GitHub Discussions](https://github.com/pausatf/pausatf-infrastructure-docs/discussions)
- Contact: @thomasvincent

## License

Historical content belonging to Pacific Association of USA Track and Field (PAUSATF).
Not for public redistribution without permission.

---

**Repository Maintainer:** Thomas Vincent
**Organization:** Pacific Association of USA Track and Field (PAUSATF)
**Last Updated:** December 21, 2025
