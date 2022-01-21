# Hostfully Sync - WordPress Function
Import properties from hostfully into WordPress. 
Import can be performed manually or automatically with recurrence.

## Shortcode Properties
Use shortcode `[hfsync-properties]` to display available properties in grid format.
Use shortcode `[hfsync-properties filter="1"]` to display properties with filter form.

## Shortcode Booking Widget
Use shortcode `[hfsync-booking-widget]` to display hostfully booking widget on single property page.

### Requirements
* WordPress 5.8.1 or up.
* Custom Post Type `accommodation`
* Custom Taxonomy `accommodation_cat`
* Custom Taxonomy `accommodation_amenity`
* Custom Taxonomy `accommodation_type_tag`
* Advanced Custom Fields 5.10.2 or up.
* Hostfully Agency Uid & Api Key.


### Reference
* [Hostfully Api Docs](https://dev.hostfully.com/reference/getting-started)
* [Github Repo](https://github.com/shazzad/hostfully-sync)