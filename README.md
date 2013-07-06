Layout-UI
=========

Project Map
-----------
data-containers.php
* DataContainer class - maps to CPT has getters and setters for all the things
* add_container( $group_key, $data_key, $value, $time_stamp = time() ) // be careful this will always add new revision.
* update_container( $group_key, $data_key, $value, $time_stamp = time() ) // if $group_key, $data_key, $value don't match, add, else find most recent container with $time_stamp <= requested $time_stamp.
* get_container( $group_key, $data_key, $time_stamp = time() ) // returns most recent container object with $time_stamp <= requested $time_stamp.
* set_container_meta( $data_container_object_id, $meta_key, $value ) 
* get_container_meta( $data_container_object_id, $meta_key )
* $group_key = tax_{taxonomy_slug} or pt_{post_type} (post_type can be post, page, or any other - also special type of pt_homepage)
* $data_key = cat/tag/term slug or post_id
* $meta_key = slug of a term, post_id of a cpt, or null. If null apply_filters() that is picked up by default-data-container.php
term-meta.php
* set_term_meta( $taxonomy, $term, $meta_key ) // wrapper for get_container then update_container() or add_container() followed by set_container_meta()
* get_term_meta( $taxonomy, $term, $meta_key ) // wrapper for get_container() and then get_container_meta()
* Does not do revisions! This is a feature, but should be noted.
term-meta-ui.php
* replaces default taxonomy term ui with a cpt ui
* default many to many - for 1:1 use default taxonomy term ui and term-meta - for multi-date or single-term without the other, use a filter and adjust the UI to restrict. No need to enforce in module 
* no option for multiple taxonomies on roadmap
* does a callback to allow plugins to add metaboxes (built into WP, just noting extensibility)
default-data-container.php
* Filter on hook set by data-containers.php if $meta_key is null and add the $group_key as a term in the 'container_meta' taxonomy
* filter on "the_posts", to see if none found and grab a default. (http://wordpress.stackexchange.com/questions/91519/the-posts-hook-which-set-of-posts)
* add check box to UI from term-meta-ui.php that says "make default for terms in this taxonomy", and disable term selection
* on save_post check for this and call or do key concepts from (Filter on hook set by data-containers.php if $meta_key is null)
layout-ui.php
* Note: any info that does not need to be sortable can still be requested and stored using a normal meta-box. Don't use this unless the data is optional and needs to be sorted.
* Idea: remove editor, add metabox on right for all available layout elements, separate metabox for each section of the page you are defining (header, menu, sidebar, feature area, footer, etc).
* can add metabox to any post_type or taxonomy (if the tax has a term-meta-ui)
* does not add a cpt, does not interact with data-containers, term-meta, term-meta-ui, default-data-container
* extends term-meta-ui, by adding a metabox to the cpt through a hook so it will fail gracefully if term-meta-ui.php is not present
* provides a ui for sortable elements (mini widgets) and multi-level (repeater) groups you can put them in.
* adds a key to all fields returned by a layout-element's form() function. try to adjust for a form that contains an old style repeating field[]. Else require the use of our specialized repeater method
* disambiguates multiple copies of an element and calls the element's sanitization with an instance object containing fields to be sanitized. So we can totally use existing widgets here.
* stores sanitized data as large array. If a layout element has data that the theme or other plugin needs to search by, it can save that info in regular post meta using
repeatables-helper.php
* takes any form and makes marked sections and fields repeatable.
* does not touch the ui. you will still need js or other methods to create new copies
* handles existing instances and empty ones by analyzing the form output, adding hidden fields, and modifying field names.
* on post, builds a nested array based on $_POST
default-layout-elements.php
* basic text box
* loop
* etc
shared-layout-elements.php
* makes it possible to define content for any layout-element as a cpt
* the editor is simply a single instance of the layout-element in a meta-box place of the editor.
* this also maybe be the preferred way to bring old style widgets into a layout-element
* adds a layout-element called "Shared Data" that can be placed and repeated like the others, but its only field a selector with the name/ids of each
wp-fields
* Define sanitization while rendering the field. At the end of a render you have a sanitization object and if you don't need the output ob_end_clean. no default sanitization or maybe (int)
named-versions.php
* changes the logic and UI so that instead of the most recent version of a layout being the active one, you can select which is active and dates are ignored. Like a page vs. a post.



Notes on Elements Vs. Widgets
-----------------------------
problems with widgets
*Eric:* ajax save + lack of preview + lack of templating for presentation
*John:* widget classes are also backwards OOP
the class is a singleton that represents widget-ness, not an instance of your widget
and the widget factory isn't a factory that creates widgets, it's a factory that creates singletons that create widgets
it's a mash-up of all the worst anti-patterns in PHP
globals, factory factories, and singletons
but I think WP is reaching that moment of critical mass where that is going to need to change
*Eric:* @johnpbloch Hence my ambiguous "I have big plans for 4.0" tweet from this week ...
*John:* If I were going to re-invent widgets separately from WP core, I'd turn existing widgets into shortcodes and use a CPT for all widgets
makes single instances of widgets re-usable across multiple sidebars
at my last job we built a plugin that never got released (my one regret from my time there!) that did just that
it also added a key for post_type_supports to say post types could support a meta box that let you exclude widgets on a post-by-post basis, or include a widget instance on a single post
basically infinitely flexible sidebars
phase 2 was controls for sidebar customization for archive-type pages
