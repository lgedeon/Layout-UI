<?php

/**
 * Plugin Name: Term Meta
 * Version: 1.0
 * Description: Uses a custom post type to shadow each term and optionally create a taxonomy default
 * and store data specific to that taxonomy term or taxonomy default.
 * 
 * This plugin also enables multiple term-meta containers to be associated with the same taxonomy
 * or term which can be really handy for a homepage. Using this plugin, you can stage and even
 * preview all of the changes for the home page and set a publish date so that all of the content
 * and layout of the page will change at one time. Contrast this with the current widget api where
 * each change is reflected immediately. So using traditional widgets, if you have changes in
 * multiple locations the page will be in multiple stages of trasistion while you make those changes.
 * But you can have a clean transistion with Term Meta.
 * 
 * And... Although, having multiple term-meta containers per taxonomy term is cool and all, it can
 * get a little complex. To simplify things, the plugin can be set to only allow a single container
 * per term. In that scenario, when you call set_term_meta against a term with an existing container
 * it will use the existing container ignoring date parameters. If you are using the optional UI, 
 * when you choose "Add New" you will be asked which term to add or edit. If you choose a pre-
 * existing one, you edit it instead of adding a new one. In this mode the UI hides the publish date
 * fields.
 * 
 * On the other side, if complexity is your thing, term-meta can shadow pages, posts, and custom
 * post types as well. So you could define and pre-schedule multiple versions of a page. We call
 * this the multi-singularity! 
 * 
 * Reference: Term meta has been requested many times including here - http://wordpress.org/ideas/topic/custom-fields-meta-data-for-categories-and-tags-terms
 * 
 * Author: 10up, Eric Mann, Luke Gedeon, John P. Bloch
 * License: GPLv2
 */

//
// Term meta functions based on Post meta API
//

/**
 * Add meta data field to a term.
 *
 * @since
 * @link http://codex.wordpress.org/Function_Reference/add_term_meta
 *
 * @param int $term_id Term ID.
 * @param string $taxonomy Taxonomy term is inside.
 * @param string $meta_key Metadata name.
 * @param mixed $meta_value Metadata value.
 * @param bool $unique Optional, default is false. Whether the same key should not be added.
 * @return int|bool Meta ID on success, false on failure.
 */
function add_post_meta($term_id, $taxonomy, $meta_key, $meta_value, $unique = false) {

    return add_metadata('post', $post_id, $meta_key, $meta_value, $unique);
}

/**
 * Remove metadata matching criteria from a term.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * @since
 * @link http://codex.wordpress.org/Function_Reference/delete_term_meta
 *
 * @param int $term_id Term ID
 * @param string $taxonomy Taxonomy term is inside.
 * @param string $meta_key Metadata name.
 * @param mixed $meta_value Optional. Metadata value.
 * @return bool True on success, false on failure.
 */
function delete_post_meta($term_id, $taxonomy, $meta_key, $meta_value = '') {

    return delete_metadata('post', $post_id, $meta_key, $meta_value);
}

/**
 * Retrieve post meta field for a post.
 *
 * @since
 * @link http://codex.wordpress.org/Function_Reference/get_term_meta
 *
 * @param int $term_id Post ID.
 * @param string $taxonomy Taxonomy term is inside.
 * @param string $key Optional. The meta key to retrieve. By default, returns data for all keys.
 * @param bool $single Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
 *  is true.
 */
function get_term_meta($term_id, $taxonomy, $key = '', $single = false) {

    return get_metadata('post', $post_id, $key, $single);
}

/**
 * Update term meta field based on term ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and term ID.
 *
 * If the meta field for the term does not exist, it will be added.
 *
 * @since
 * @link http://codex.wordpress.org/Function_Reference/update_term_meta
 *
 * @param int $term_id Term ID.
 * @param string $taxonomy Taxonomy term is inside.
 * @param string $meta_key Metadata key.
 * @param mixed $meta_value Metadata value.
 * @param mixed $prev_value Optional. Previous value to check before removing.
 * @return bool True on success, false on failure.
 */
function update_term_meta($term_id, $taxonomy, $meta_key, $meta_value, $prev_value = '') {

    return update_metadata('post', $post_id, $meta_key, $meta_value, $prev_value);
}

/**
 * Delete everything from term meta matching meta key.
 *
 * @since
 *
 * @param string $term_meta_key Key to search for when deleting.
 * @param string $taxonomy The taxonomy to work against.
 * @return bool Whether the post meta key was deleted from the database
 */
function delete_term_meta_by_key($term_meta_key, $taxonomy) {

    return delete_metadata( 'post', null, $term_meta_key, '', true );
}

/**
 * hmm... this one is going to take a little thought
 *
 * Retrieve term meta fields, based on term ID.
 *
 * The term meta fields are retrieved from the cache where possible,
 * so the function is optimized to be called more than once.
 *
 * @since
 * @link http://codex.wordpress.org/Function_Reference/get_post_custom
 *
 * @param int $post_id Post ID.
 * @return array
 *
function get_post_custom( $post_id = 0 ) {
    $post_id = absint( $post_id );
    if ( ! $post_id )
        $post_id = get_the_ID();

    return get_post_meta( $post_id );
}*/

/**
 * could also be a useful function, but need to think about naming
 *
 * Retrieve meta field names for a post.
 *
 * If there are no meta fields, then nothing (null) will be returned.
 *
 * @since 1.2.0
 * @link http://codex.wordpress.org/Function_Reference/get_post_custom_keys
 *
 * @param int $post_id post ID
 * @return array|null Either array of the keys, or null if keys could not be retrieved.
 *
function get_post_custom_keys( $post_id = 0 ) {
    $custom = get_post_custom( $post_id );

    if ( !is_array($custom) )
        return;

    if ( $keys = array_keys($custom) )
        return $keys;
}*/

/**
 * ditto...
 *
 * Retrieve values for a custom post field.
 *
 * The parameters must not be considered optional. All of the post meta fields
 * will be retrieved and only the meta field key values returned.
 *
 * @since 1.2.0
 * @link http://codex.wordpress.org/Function_Reference/get_post_custom_values
 *
 * @param string $key Meta field key.
 * @param int $post_id Post ID
 * @return array Meta field values.
 *
function get_post_custom_values( $key = '', $post_id = 0 ) {
    if ( !$key )
        return null;

    $custom = get_post_custom($post_id);

    return isset($custom[$key]) ? $custom[$key] : null;
}*/
