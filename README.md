# halftheory/wp-lib
A PHP library for WordPress developers. Use Composer to load this repository into your WordPress theme/plugin/core for faster development and simple accessibility to common functions.

## Functions: /functions
Types of functions include:

- Extensions of regular PHP functions (`is_true`, `make_array`).
- Fixed WP bugs and inconsistent function behaviors (`ht_get_posts`, `ht_is_front_page`).
- Functions to help avoid database data duplication (`the_excerpt_fallback`, `post_thumbnail_id_fallback`).
- Wrapper functions for groups of similar WP functions. For example, to retrieve image data:

Old way | New way
:--- | :---
`get_attached_file($id)` | `get_image_context('file', $id)`
`wp_get_attachment_image($id)` | `get_image_context('img', $id)`
`wp_get_attachment_image_src($id)` | `get_image_context('src', $id)`
`wp_get_attachment_image_url($id)` | `get_image_context('url', $id)`
`wp_get_attachment_link($id)` | `get_image_context('link', $id)`

- Many more.

## Classes
### Halftheory\Lib\Core
The parent class for all other classes. Methods include:

Method | Description
:--- | :---
`get_instance` | For static class calling.
`set_handle` | Creates a useful identifier.
`load_functions` | Selectively load functions for better performance.

### Halftheory\Lib\Filters
Base class for defining filters.

- Filter methods are named as: `[scope]_[filter]`. For example: `[public]_[wp_head]` = `public_wp_head()`.
- The filter scopes are:
 - `global_` (both public + admin)
 - `public_`
 - `admin_`
 - `rest_`
- This class has its own methods for easily adding/removing filters without needing to hook into the `$wp_filters` global, and therefore avoiding the need to remember the filter priority. Methods include:

Method | Description
:--- | :---
`add_filter` | Adds a single filter from the class.
`add_filters_all` | Loaded by default. Adds all filters defined within the class. You can also specify an optional prefix. e.g. `add_filters_all('public_')`.
`remove_filter` | Removes a single filter from the class.
`remove_filters_all` | Removes all filters defined within the class. You can also specify an optional prefix. e.g. `remove_filters_all('admin_')`.

- See here for info on the execution order of actions/filters: [https://codex.wordpress.org/Plugin_API/Action_Reference](https://codex.wordpress.org/Plugin_API/Action_Reference)

### Halftheory\Lib\Theme
The main class for controlling a WP theme. Custom themes should extend this class. Methods include:

Method | Description
:--- | :---
`load_filters` | Attaches one or more `Filters` class(es). See above.
`load_helpers` | Load one or more **helper** classes. See below.
`load_plugins` | Selectively load code that extends/modifies a WP plugin. The code is only activated when the plugin is active.

## Helpers: /helpers
Helpers are **collections** of filters to easily make common modifications in WordPress. Some helpers have a single purpose (e.g. `menus-smartmenus` will add a mobile menu) and some helpers will make many changes at once. For example, enabling the `media-common` helper will:

- Increase the sizes of intermediate images.
- Remove unnecessary intermediate image sizes.
- Add a 'Regenerate Images' function in Admin > Media.
- Enable .svg file uploads.

Available helpers include:

- admin-common
- authors-common
- feed-common
- gallery-carousel
- gallery-common
- gallery-ratio
- i18n-common
- mail-common
- media-common
- menus-slicknav
- menus-smartmenus
- minify
- no-authors
- no-blocks
- no-categories
- no-comments
- no-posts
- no-tags
- shortcode-code
- shortcode-no-embed
- taxonomy-thumbnails
- video-common
- video-featured

## Examples
See the following for ideas on how to use this library in your own project:

- [wp-halftheory-clean](https://github.com/halftheory/wp-halftheory-clean/)

## Requirements
- [Composer](https://getcomposer.org/download/)

## Install
After `composer init` add the following to your `composer.json` file:

```
"autoload": {
    "psr-4": {
        "Halftheory\\Lib\\": "vendor/halftheory/wp-lib/src/"
    }
},
"repositories": [
    {
        "type": "package",
        "package": {
            "name": "halftheory/wp-lib",
            "version": "1.0.4",
            "source": {
                "url": "https://github.com/halftheory/wp-lib/",
                "type": "git",
                "reference": "1.0.4"
            }
        }
    }
],
"require": {
    "halftheory/wp-lib": "^1.0"
}
```
Then execute the following commmands:

```
composer u
composer dumpautoload
```
Add the following to your functions.php file:

```
require __DIR__ . '/vendor/autoload.php';
```

## Credits
- [Fuzzysort](https://github.com/farzher/fuzzysort/) (helper: `search-fuzzysort`)
- [Minify](https://github.com/mrclay/minify/) (helper: `minify`)
- [Slick Carousel](https://kenwheeler.github.io/slick/) (helper: `gallery-carousel`)
- [Slicknav](https://computerwolf.github.io/SlickNav/) (helper: `menus-slicknav`)
- [SmartMenus](https://www.smartmenus.org/) (helper: `menus-smartmenus`)

## Future
- Halftheory\Lib\Plugin class.
- Helpers:
    - cdn
    - infinite-scroll
- More microdata functions.
