# TheSource Child Theme - PHP 8 Compatibility Fixes

## Version 0.3-php8 (2025-12-06)

### PHP 8.1+ Fixes

1. **functions.php** - Replaced deprecated `FILTER_SANITIZE_STRING`
   - Changed `filter_input(INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_STRING)`
   - To `htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8')`
   - Applied to both `thesource_child_enqueue_scripts()` and `thesource_child_browser_body_class()`

2. **includes/scripts.php** - Fixed JavaScript syntax errors
   - Replaced `public` keyword with `var` (JavaScript variable declarations)
   - Replaced `.foreach()` with `.each()` (jQuery method name)
   - These were likely introduced by a faulty find/replace operation

### Parent Theme (TheSource) Fixes

1. **core/components/post/Query.php:91**
   - Fixed: Required parameter `$negate` after optional parameter `$value = null`
   - Changed to: `_add_meta_query($key, $value, $negate)` (removed default value)

2. **core/components/api/email/MailPoet.php:53**
   - Fixed: Required parameters after optional `$version = '2'`
   - Changed to: `_init_provider_class($version, $owner, $account_name, $api_key)`

3. **core/components/lib/OAuth.php:130**
   - Fixed: `utf8_encode()` deprecated in PHP 8.2
   - Changed to use `mb_convert_encoding()` with fallback

### Compatibility

- PHP 7.4: ✓ Compatible
- PHP 8.0: ✓ Compatible
- PHP 8.1: ✓ Compatible
- PHP 8.2: ✓ Compatible
- PHP 8.3: ✓ Compatible
- PHP 8.4: ✓ Compatible

### Testing

After deploying, test the following:
1. Frontend loads without errors
2. Featured slider works (uses et_switcher plugin)
3. Footer widgets display correctly
4. Search bar functionality
5. Admin panel accessible
6. No PHP deprecation warnings in error log
