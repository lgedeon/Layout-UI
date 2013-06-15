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

/* 
 * TODO:
 *   Store and retrieve data.
 *   whitelist supports array
 *   allow some TM's to have featured images
 */


/* 
 * Single class - multiple objects. Each object creates a CPT associated with one or more taxonomies,
 * the home page, or a post type. By default, the homepage term meta UI still uses a full "posts"
 * listing to allow editing of multiple scheduled updates and stored variations. If, however,
 * multiple containers per term is disabled for the "homepage" type the "posts" list is skipped. 
 * 
 * 
 * Only most recent single match is ever used. Update post date to make the prefered varation
 * the most recent and therefore active. Scheduled term meta containers and drafts will be used when
 * published.
 */
if (!class_exists('Term_Meta')) {

class Term_Meta {

	private $_taxonomies;
	private $_post_types;
	private $_no_versions;
	private $_layout_name;
	private $_short_name;
	private $_archive_elements;

	/**
	 * Accept values and place into instance variables.
	 * 
	 *
	 * @param string|array $type        String assumed to be the name of a taxonomy. Array keys can be 'home', 'taxonomy', or 'post_type' and value is a taxonomy or CPT or null for home.
	 * @param string|array $no_versions True if only one term meta conatainer per term allowed.
	 * @param string       $layout_name If different than first taxonomy in $taxonomies
	 * @param string       $short_name  Short version of $layout_name must but 20 characters or less
	 */
	public function __construct( $type, $no_versions = false, $layout_name = '', $short_name = '' ) {
		$this->_no_versions = (bool) $no_versions;
		
		if ( is_string( $type ) ) {
			$type = array( 'taxonomy' => $type );
		}

		foreach ( $type as $key => $value ) {
			switch ( $key ) {
				case 'taxonomy' :
					if ( taxonomy_exists( $value ) ) {
						$this->_taxonomies[] = $value;
					}
					break;
				case 'post_type' :
					if ( post_type_exists( $value ) ) {
						$this->_post_types[] = $value;
					}
					break;
				case 'home' :
					$this->_layout_name = $layout_name ?: 'home';
					$this->_short_name = 'TM_home'; // hard-coding this one for home because home behaves different
				default :
					return;
			}
				
		}

		if ( empty( $this->_taxonomies ) && empty( $this->_post_types ) ) {
			wp_die('no valid values provided to construct method of Term_Meta');
		}

		$names = array_merge( $this->_taxonomies, $this->_post_types );
		$this->_layout_name = $layout_name ?: implode( '', array_map( 'ucwords', $names ) );
		$this->_short_name = $short_name ?: 'TM_' . strtolower( substr( $this->_layout_name, 0, 17 ) );
	}

	/**
	 * Register a custom post type to store data associtated with a .
	 *
	 * @param array $supports   Specify whether this term meta container has a thumbnail, title, editor, etc.
	 * @param array $show_ui    Allow editors to control the data directly through a normal cpt interface.
	 */
	public function setup( $supports = array(), $show_ui = true ) {

		$labels = array(
			'name' => "$this->_layout_name Layouts",
			'singular_name' => "$this->_layout_name Layout",
			'add_new' => 'Add New',
			'add_new_item' => "Add New $this->_layout_name Layout",
			'edit_item' => "Edit $this->_layout_name Layout",
			'new_item' => "New $this->_layout_name Layout",
			'all_items' => "$this->_layout_name Layouts",
			'view_item' => "View $this->_layout_name Layout",
			'menu_name' => "$this->_layout_name Layouts"
		);

		foreach ((array) $supports as $support) {
			//sanitize against whitelist
		}

		$args = array(
			'labels' => $labels,
			'public' => true,
			'exclude_from_search' => true,
			'publicly_queryable' => true, // may need this for rewrites - todo: find out
			'show_ui' => (bool) $show_ui,
			'show_in_menu' => 'archive_layouts',
			'query_var' => true, //do we need a query var?
			'capability_type' => 'page',
			'menu_position' => 20,
			'supports' => $supports,
			'taxonomies' => $this->_taxonomies,
		);

		register_post_type( $this->_short_name, $args);
		
	}


	/** deprecate?
	 * Return data array for most recent published Archive Layout that matches the current archive page.
	 */
	function get_data () {
		
	}

	/**
	 * Set-up caching
	 */
}

}

/**
 * Create a top level menu for various taxonomy's ALs to sit under. 
 */
function archive_layouts_menu() {
	add_menu_page('Archive Layouts', 'Archive Layouts', 'manage_options', 'archive_layouts', '', '', 20);
}

add_action('admin_menu', 'archive_layouts_menu');

/*
 * Initialize all registered AL's
 */
function archive_layouts_init() {
	$archive_layouts = get_archive_layouts_list();

	foreach ( $archive_layouts as $archive_layout ) {
		$archive_layout_objects[] = new Archive_Layout( $archive_layout['taxonomies'], $archive_layout['layout_name'], $archive_layout['short_name'] );
		end($archive_layout_objects)->setup( $archive_layout['supports'] );
	}
}

// Don't do all the setup unless we actually need it, but admin_init is too late.
if ( is_admin() ) {
	// Add late so that CPT's have time to be registered.
	add_action( 'init', 'archive_layouts_init', 50 );
}

/* 
 * Setup defaults and allow addtional.
 * 
 * Note to implementors: always use 'archive_layouts_list' filter to register, rather instantiating
 * manually so that it will be setup correctly on the front-end and in the admin.
 * 
 * Note for Unit Tests: should remove any archive layout with empty or non-valid taxonomies, should
 * generate a name if none provided, and should calculate a shortname of 20 characters or less.
 */
function get_archive_layouts_list() {
	// Declare default archive layouts and allow themes and plugins to over-ride as needed.
	$archive_layouts = apply_filters( 'archive_layouts_list', array(
		array(
			'taxonomies' => 'category',
			'supports' => array(),
		),
		array(
			'taxonomies' => 'post_tag',
			'supports' => array(),
			'layout_name' => 'Tags',
		),
	));

	foreach ( $archive_layouts as $archive_layout ) {
		foreach ((array) $archive_layout['taxonomies'] as $taxonomy) {
			if (taxonomy_exists($taxonomy) || 'home' == $taxonomy) {
				$taxonomies[] = $taxonomy;
			}
		}

		if ( empty($taxonomies) ) {
			continue;
		}

		$archive_layout['taxonomies'] = $taxonomies;
		$archive_layout['layout_name'] = ( empty( $archive_layout['layout_name'] ) ) ? implode( '', array_map( 'ucwords', $taxonomies ) ) : $archive_layout['layout_name'];
		$archive_layout['short_name'] = strtolower( substr( $archive_layout['layout_name'], 0, 17 ) ) . '_al';

		$archive_layouts_list[] = $archive_layout;
	}

	return $archive_layouts_list;	
}

function archive_layout_content() {
	$archive_layouts = get_archive_layouts_list();

	$queried_object = get_queried_object();
	var_dump( $queried_object );

	foreach ( $archive_layouts as $archive_layout ) {
		if ( in_array( $queried_object['taxonomy'], $archive_layout['taxonomies'] ) ) {
			$post_types[] = $archive_layout['short_name'];
		}
	}

	$args = array(
		'post_type' => $post_types,
		'tax_query' => array(
			array(
				'taxonomy' => $queried_object->taxonomy,
				'field' => 'id',
				'terms' => $queried_object->term_id,
			)
		)
	);

	$archive_layout_object = new WP_Query( $args );
}
