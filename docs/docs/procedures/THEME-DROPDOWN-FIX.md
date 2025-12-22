# Dropdown Menu Background Color Fix

**Issue:** Dropdown menus have black backgrounds instead of white
**Date Identified:** 2025-12-21
**Theme:** TheSource-child
**Impact:** Reduced menu readability

---

## Issue Summary

About 2 years ago, the dropdown menu background color changed from white to black. This affects all dropdown menus in the main navigation, making them harder to read.

**Current state:** Black/dark dropdown backgrounds
**Desired state:** White dropdown backgrounds (matching parent theme)

**Screenshots:**
- TheSource-child theme: Black dropdown backgrounds (current)
- TheSource theme: White dropdown backgrounds (desired)

---

## Why Use a Child Theme?

**TheSource-child is intentionally used instead of TheSource (parent theme)**

### Benefits of Child Themes:

1. **Update Safety**
   - Parent theme updates don't overwrite customizations
   - Site remains stable when theme updates are released
   - Custom styling is preserved

2. **Customization Preservation**
   - Custom colors (PAUSATF red: #ce0010)
   - Typography adjustments
   - Layout modifications
   - Branding elements

3. **WordPress Best Practice**
   - Recommended by WordPress.org
   - Standard approach for professional sites
   - Easier to maintain and debug

**Bottom line:** The child theme should be kept, but the dropdown styling needs adjustment.

---

## Root Cause Analysis

### Location of Problem

**File:** `/var/www/html/wp-content/themes/TheSource-child/style.css`
**Line:** 1695

### Current CSS (Causing Black Backgrounds)

```css
.nav li ul {
    box-shadow: 3px 6px 7px 1px rgba(0, 0, 0, 0.5);
    -moz-box-shadow: 3px 6px 7px 1px rgba(0, 0, 0, 0.5);
    -webkit-box-shadow: 3px 6px 7px 1px rgba(0, 0, 0, 0.5);
    background: url(images/cat_menu_bg.png) repeat-y;  /* ← DARK BACKGROUND IMAGE */
    border: 1px solid #111010;                         /* ← DARK BORDER */
    -moz-border-radius: 8px;
    -webkit-border-radius: 8px;
    -moz-border-radius-topleft: 0px;
    -webkit-border-top-left-radius: 0px;
    border-top: none;
    padding-bottom: 15px;
}
```

**Problems:**
1. `background: url(images/cat_menu_bg.png)` - Uses dark background image
2. `border: 1px solid #111010` - Nearly black border color (#111010)

---

## Solution

### Replace dark background with white background

**Updated CSS (White Backgrounds):**

```css
.nav li ul {
    box-shadow: 3px 6px 7px 1px rgba(0, 0, 0, 0.5);
    -moz-box-shadow: 3px 6px 7px 1px rgba(0, 0, 0, 0.5);
    -webkit-box-shadow: 3px 6px 7px 1px rgba(0, 0, 0, 0.5);
    background: #ffffff;                               /* ← WHITE BACKGROUND */
    border: 1px solid #d8d8d8;                         /* ← LIGHT GRAY BORDER */
    -moz-border-radius: 8px;
    -webkit-border-radius: 8px;
    -moz-border-radius-topleft: 0px;
    -webkit-border-top-left-radius: 0px;
    border-top: none;
    padding-bottom: 15px;
}
```

**Changes:**
- `background: #ffffff` - Pure white background
- `border: 1px solid #d8d8d8` - Light gray border (matches site's existing border color)

---

## Implementation Options

### Option 1: Apply Fix via WordPress Admin (Recommended)

**Advantages:**
- No SSH access required
- Changes visible immediately
- Easy to revert if needed

**Steps:**
1. Log into WordPress admin: https://pausatf.org/wp-admin
2. Go to **Appearance → Customize**
3. Click **Additional CSS**
4. Add this CSS at the bottom:

```css
/* Fix dropdown menu backgrounds - restore white backgrounds */
.nav li ul {
    background: #ffffff !important;
    border: 1px solid #d8d8d8 !important;
}

/* Ensure text is readable on white background */
#cat-nav-content ul.nav li li a {
    color: #383737 !important;
}

#cat-nav-content ul.nav li li a:hover {
    color: #ce0010 !important;
}
```

5. Click **Publish**
6. View site to confirm dropdown menus now have white backgrounds

### Option 2: Edit Child Theme CSS Directly

**Advantages:**
- Permanent fix in child theme
- Cleaner approach (no !important needed)
- Can be version controlled

**Steps:**

1. **Backup current CSS:**
```bash
ssh root@prod.pausatf.org "cp /var/www/html/wp-content/themes/TheSource-child/style.css /var/www/html/wp-content/themes/TheSource-child/style.css.backup-$(date +%Y%m%d)"
```

2. **Edit the file on server:**
```bash
ssh root@prod.pausatf.org
cd /var/www/html/wp-content/themes/TheSource-child
nano style.css
```

3. **Find line 1695** (search for `.nav li ul {`)

4. **Change:**
   - From: `background: url(images/cat_menu_bg.png) repeat-y;`
   - To: `background: #ffffff;`

   - From: `border: 1px solid #111010;`
   - To: `border: 1px solid #d8d8d8;`

5. **Save and exit** (Ctrl+X, Y, Enter)

6. **Clear WordPress cache:**
```bash
# If using LiteSpeed Cache
wp cache flush --allow-root --path=/var/www/html

# Or clear browser cache and reload
```

7. **Verify changes on site**

### Option 3: Automated Fix Script

**For convenience, run this script:**

```bash
#!/bin/bash
# fix-dropdown-backgrounds.sh
# Restores white backgrounds to dropdown menus

THEME_DIR="/var/www/html/wp-content/themes/TheSource-child"
CSS_FILE="$THEME_DIR/style.css"
BACKUP_FILE="$THEME_DIR/style.css.backup-$(date +%Y%m%d-%H%M%S)"

echo "=== Dropdown Menu Background Fix ==="
echo "Backing up current CSS to: $BACKUP_FILE"
cp "$CSS_FILE" "$BACKUP_FILE"

echo "Applying fix..."
sed -i 's/background: url(images\/cat_menu_bg\.png) repeat-y;/background: #ffffff;/g' "$CSS_FILE"
sed -i 's/border: 1px solid #111010;/border: 1px solid #d8d8d8;/g' "$CSS_FILE"

echo "Clearing cache..."
cd /var/www/html
wp cache flush --allow-root 2>/dev/null || echo "No cache plugin detected"

echo ""
echo "✅ Fix applied successfully!"
echo "   Backup saved to: $BACKUP_FILE"
echo "   Please verify dropdown menus at https://pausatf.org"
echo ""
echo "To revert:"
echo "   cp $BACKUP_FILE $CSS_FILE"
```

**Usage:**
```bash
ssh root@prod.pausatf.org 'bash -s' < fix-dropdown-backgrounds.sh
```

---

## Testing Checklist

After applying the fix, verify:

- [ ] Main navigation dropdown menus have white backgrounds
- [ ] Dropdown text is readable (dark text on white background)
- [ ] Dropdown hover states work correctly (PAUSATF red: #ce0010)
- [ ] Dropdown borders are visible but subtle (light gray)
- [ ] Page menu dropdowns also have white backgrounds
- [ ] No layout breakage or visual artifacts
- [ ] Mobile menu not affected (if applicable)

**Test on:**
- [ ] Desktop (Chrome, Firefox, Safari)
- [ ] Tablet (if applicable)
- [ ] Mobile (if applicable)

---

## Additional Improvements (Optional)

While fixing the dropdown backgrounds, consider these enhancements:

### 1. Improve Dropdown Readability

```css
/* Better text contrast on white backgrounds */
.nav li ul li a {
    color: #383737;
    text-shadow: none; /* Remove dark text shadow */
}

.nav li ul li a:hover {
    color: #ce0010;
    background-color: #f5f5f5; /* Subtle hover highlight */
}
```

### 2. Modern Box Shadow

```css
/* Softer, more modern shadow */
.nav li ul {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
```

### 3. Smoother Transitions

```css
/* Animated dropdown appearance */
.nav li ul {
    transition: opacity 0.3s ease-in-out;
}
```

---

## Rollback Procedure

### If using WordPress Customizer (Option 1):
1. Go to **Appearance → Customize → Additional CSS**
2. Remove the added CSS
3. Click **Publish**

### If edited CSS directly (Option 2):
```bash
# Restore from backup
ssh root@prod.pausatf.org
cp /var/www/html/wp-content/themes/TheSource-child/style.css.backup-YYYYMMDD \
   /var/www/html/wp-content/themes/TheSource-child/style.css

# Clear cache
wp cache flush --allow-root --path=/var/www/html
```

### If using script (Option 3):
Backup file location is shown in script output

---

## Why This Happened

**Likely scenario:**

Around 2 years ago (approximately 2023):
1. A theme update or customization changed the dropdown styling
2. The dark background image (`cat_menu_bg.png`) was added to the child theme
3. This overrode the parent theme's white backgrounds
4. The change may have been intentional at the time but no longer desired

**Recommendation:** After fixing, document the desired dropdown style in site documentation to prevent future reversions.

---

## Related CSS Files

**Child theme files:**
- `/var/www/html/wp-content/themes/TheSource-child/style.css` (main stylesheet)
- `/var/www/html/wp-content/themes/TheSource-child/images/cat_menu_bg.png` (dark background image)

**Parent theme files (for reference):**
- `/var/www/html/wp-content/themes/TheSource/style.css`
- Should not be modified directly (use child theme instead)

**WordPress customizer CSS:**
- Stored in database: `wp_options` table
- Option name: `theme_mods_TheSource-child`
- Contains Additional CSS from Customizer

---

## Documentation Updates

After implementing the fix:

1. **Update CHANGELOG.md:**
```markdown
### Fixed
- Dropdown menu backgrounds restored to white (from black)
- Improved menu readability and contrast
- Reverted unintended theme customization from ~2023
```

2. **Update 07-performance-optimization-complete.md:**
- Note the visual theme improvement
- Document the correct dropdown styling for future reference

3. **Create theme documentation:**
- Document intended color scheme
- Preserve PAUSATF branding colors (#ce0010)
- Specify dropdown menu styling standards

---

## Support

**For questions or issues:**
- Review screenshots before/after
- Check browser console for CSS errors
- Verify no cache plugins interfering
- Test in multiple browsers

**If fix doesn't work:**
- Clear all caches (browser, WordPress, server)
- Check for conflicting CSS in Additional CSS section
- Verify correct child theme is active
- Review browser developer tools (F12 → Elements → Styles)

---

## Recommendation

**Preferred implementation: Option 1 (WordPress Customizer)**

**Reasons:**
1. Non-destructive (doesn't modify theme files)
2. Survives theme updates
3. Easy to adjust or revert
4. No SSH access required
5. Immediate preview before publishing

**After confirming the fix works, consider:**
- Making it permanent in child theme CSS (Option 2)
- Removing from Customizer Additional CSS
- Documenting the change for future reference

---

**Document Version:** 1.0
**Created:** 2025-12-21
**Issue Reporter:** User feedback
**Assigned To:** Thomas Vincent
**Status:** Solution provided, awaiting implementation
**Priority:** Medium (visual/UX improvement)
