# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin that extends Edwiser Bridge to provide a seamless course viewing experience within WordPress by integrating with Moodle. It intercepts course access from the My Courses page and displays course content directly in WordPress instead of redirecting to Moodle.

## Common Development Commands

### Code Quality
```bash
# Check PHP syntax
php -l includes/class-courseexp-core.php

# Run WordPress Coding Standards
./vendor/bin/phpcs includes/
./vendor/bin/phpcs templates/

# Fix auto-fixable coding standards issues
./vendor/bin/phpcbf includes/

# Generate translation POT file
wp i18n make-pot . languages/eb-course-exp.pot --domain=eb-course-exp
```

### WordPress Operations
```bash
# Flush rewrite rules after changing URL patterns
wp rewrite flush

# Activate/deactivate plugin
wp plugin activate course-experience
wp plugin deactivate course-experience
```

## High-Level Architecture

### Plugin Initialization Flow
1. `course-experience.php` defines constants and registers activation hooks
2. `CourseExp_Core::init()` bootstraps the plugin on `plugins_loaded`
3. `CourseExp_Course_Router` sets up URL rewriting and template overrides
4. `CourseExp_API_Client` provides Moodle API connectivity via Edwiser Bridge

### URL Routing Architecture
The plugin creates a custom endpoint that intercepts course access:

- **My Courses Page**: Edwiser Bridge's `[eb_my_courses]` shortcode displays enrolled courses
- **URL Override**: `CourseExp_Course_Router::modify_my_courses_url()` filters `eb_content_course_before` to change course card links from `?mdl_course_id=X` to `/eb-course-experience/{course-slug}/`
- **Custom Endpoint**: Rewrite rule `eb-course-experience/([^/]+)` loads `templates/course-experience.php`

### Moodle API Integration
The plugin communicates with Moodle through Edwiser Bridge's connection helper:

- **Connection**: Uses `Eb_Connection_Helper::connect_moodle_with_args_helper()` which handles authentication via Edwiser Bridge's stored token
- **Web Services**: Calls Moodle's `mod_courselink_*` web services:
  - `mod_courselink_get_course_structure` - Returns course sections, activities, completion status
  - `mod_courselink_get_user_progress` - Returns progress percentage and completion status
  - `mod_courselink_get_activity_content` - Returns content for direct-render activities (page, label, book)
- **User Mapping**: WordPress users must have `moodle_user_id` meta field (set by Edwiser Bridge during SSO)
- **Course Mapping**: WordPress `eb_course` posts store Moodle course ID in `eb_course_options['moodle_course_id']`

### Template Loading
- Query var `eb_course_exp` triggers custom template loading
- Template receives `course_slug` from URL and fetches corresponding `eb_course` post
- API calls require both WordPress post ID and Moodle course/user IDs

## Key Files and Responsibilities

- `includes/class-courseexp-core.php` - Plugin bootstrap, asset enqueueing (only on relevant pages), AJAX handler registration
- `includes/class-course-router.php` - URL rewriting, query var registration, template hijacking, My Courses URL modification
- `includes/class-api-client.php` - Moodle web service client using Edwiser Bridge's connection infrastructure
- `templates/course-experience.php` - Debug template displaying raw API responses (currently var_dump only)

## Dependencies

- **Edwiser Bridge**: Must be active. Provides authentication token, user sync (moodle_user_id), course sync
- **Moodle Plugin**: Requires `mod_courselink` web services to be enabled in Moodle
- **WordPress**: 6.0+, PHP 7.4+

## Development Guidelines

See `GUIDELINES.md` for detailed coding standards, security practices, and naming conventions.

## Testing Checklist

After change:
- Run `php -l` on all modified files
- Run `./vendor/bin/phpcs` on modified files
- Test with Edwiser Bridge deactivated (should fail gracefully)
- Run `wp rewrite flush` after URL-related changes
- Generate POT file if strings changed: `wp i18n make-pot . languages/eb-course-exp.pot --domain=eb-course-exp`
