# WordPress Themes

This directory contains the WordPress themes for pausatf.org.

## Structure

- **`thesource/`** - TheSource parent theme (Version 4.8.13 by Elegant Themes)
- **`thesource-child/`** - TheSource child theme with custom modifications

## TheSource Parent Theme

The parent theme is TheSource by Elegant Themes. This is the base theme that provides the core functionality and design framework.

**Important**: Never modify the parent theme directly. All customizations should be made in the child theme.

## TheSource Child Theme

The child theme contains all custom modifications and overrides specific to pausatf.org. This includes:

- Custom CSS styling
- Template overrides
- Custom functionality
- Site-specific configurations

## Development

When making changes to the WordPress theme:

1. Always work in the child theme (`thesource-child/`)
2. Never modify the parent theme files
3. Test changes in a staging environment before deploying to production
4. Document any significant customizations

## Deployment

These themes are typically deployed as part of the WordPress installation on the pausatf.org server. See the [Ansible documentation](../ansible/README.md) for automated deployment procedures.

## Support

For questions about theme customization or issues, please open a GitHub issue or contact @thomasvincent.
