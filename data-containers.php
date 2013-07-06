<?php

namespace LayoutUI;

class DataContainer {

	/**
	 * @var \stdClass The WordPress term object
	 */
	protected $_term;

	/**
	 * @var \WP_Post The WordPress post object
	 */
	protected $_post;

	protected $_allowed_values = array();

	/**
	 * Create a new data container object from an existing data_element post object. If no post
	 * object is supplied, leave null until data is sent to the object and then create a post.
	 * 
	 * Don't use this class directly. Use add_data_container() and update_data_container().
	 * 
	 * @param int|WP_Post $post A post object or id of one of the data_element post_types
	 */
	public function __construct( $post = null ) {
		if ( ! empty( $post ) && $post = $get_post( $post ) ) {
			
		}
	}
	
	/**
	 * Magic getter for data-container objects
	 *
	 * For the following keys, return the following values:
	 *  - post_id  : $this->_post->ID
	 *  - term_id  : $this->_term->term_id
	 *
	 * If $key isn't in $this->_allowed_values, throw an Invalid_Input_Exception
	 *
	 * If the function gets to this point return $this->_crunchbase_data[$key]
	 * or null.
	 *
	 * @throws Exception
	 *
	 * @param string $key The key to look up by
	 *
	 * @return mixed The value
	 */
	public function __get( $key ) {
		if ( ! empty( $key ) ) {
			if ( $key === 'post_id' ) {
				return $this->_post->ID;
			}
			if ( $key === 'term_id' ) {
				return $this->_term->term_id;
			}

			if ( ! in_array( $key, $this->_allowed_values ) ) {
				throw new Exception( "Cannot get key. Key is invalid: $key" );
			} else {
				return $this->_crunchbase_data[$key];
			}
		}
		return null;
	}

	/**
	 * Is the value set
	 *
	 * Returns $this->__get( $key ) cast as a boolean. If an exception is caught
	 * return false.
	 *
	 * @param string $key The value to look up
	 *
	 * @return bool Whether the value is set
	 */
	public function __isset( $key ) {
		if ( ! empty( $key ) ) {
			try {
				return bool( $this->__get( $key ) );
			} catch ( Exception $e ) {
				return false;
			}
		}
	}

}

function get_data_container( $container ) {
	
	if ( is_int( $container ) ) {
		$container = get_post( $container );
	}
	
	if ( is_a( $container, 'Data_Container' ) ) {
		return $container;
	} elseif ( is_a( $container, 'WP_Post' ) ) {
		return new DataContainer( $container );
	}

	return false;
}

function get_data_containers( $args ) {
	if ( isset( $args['taxonomy'] ) &&  taxonomy_exists( $args['taxonomy'] ) && isset( $args['term'] ) ) {
		$_args = array(
				'post_type' => PREFIX . "_tax_" . $args['taxonomy'],
				'tax_query' => array(
					array(
						'taxonomy' => $args['taxonomy'],
						'field' => 'slug',
						'terms' => sanitize_key( $args['term'] )
					),
				),
			);
	} elseif ( isset( $args['homepage'] ) ) {
		$_args = array ( 'post_type' => PREFIX . '_homepage' );
	/*
	 * Another option is to get all data_containers that are associated with a given post, page, or
	 * cpt. If it feels a little strange to be adding a post so that you can add post meta to
	 * something that can already have meta, remember that post_meta cannot have versions (yet) and
	 * that they cannot have future versions. This also gives you an easy interface to maintain
	 * multiple versions at once. This gives post_meta revisions, but not on every auto save, which
	 * would be too heavy (that is why they are not included in revisions in core).
	 */
	} elseif ( isset( $args['post_id' ] ) ) { //note this can include pages - and even attachements. I have no idea why you would use this for attachments but you could.
		$_args = array (
			'post_type' => PREFIX . '_post_type',
			'post_parent' => $args['post_id' ]
		);
	} else {
		return false;
	}

	$_args['posts_per_page'] = ( isset( $args['count'] ) ) ? $args['count'] : 1;


	$filter = new FilterWithArgs( 'posts_where', 'filter_where_date_less_than', 10, 1, time() );
	$containers = new WP_Query( $_args );
	$filter->remove_filter();

}

function filter_where_date_less_than( $where = '', $date = '' ) {
	if ( $date ) {
		$where .= " AND post_date <= '" . $date . "'";
	}
	return $where;
}

class FilterWithArgs {
	protected $filter;
	protected $function;
	protected $priority;
	protected $arg_count;
	protected $args;
	public function __construct( $filter, $function, $priority, $arg_count, $args ) {
		$this->filter    = $filter;
		$this->function  = $function;
		$this->priority  = $priority;
		$this->arg_count = $arg_count;
		$this->args      = $args;
		add_filter( $this->filter, array( $this, 'do_filter' ), $this->priority, $this->arg_count );
	}
	public function do_filter() {
		$args = array_merge( func_get_args(), $this->args );
		call_user_func( $this->function, $args );
	}
	public function remove_filter() {
		remove_filter( $this->filter, array( $this, 'do_filter' ), $this->priority, $this->arg_count );
	}
}

/*   
 * need to be able to register a cpt on the spot
 * 
 * add_container( $group_key, $data_key, $value, $time_stamp = time() ) // be careful this will always add new revision.
 * update_container( $container, $data, $time_stamp = time() ) // serilize $data and store in post_meta of $container
 * get_container( $container ) // finds the correct container based on its post_id or post.
 * get_container_by( $type, $group_key, $data_key, $time_stamp = time() ) // returns most recent container object with $time_stamp <= requested $time_stamp.
 * set_container_meta( $data_container_object_id, $meta_key, $value ) 
 * get_container_meta( $data_container_object_id, $meta_key ) // if $group_key, $data_key, $value don't match, add, else find most recent container with $time_stamp <= requested $time_stamp.
 * $type = whether data is associated with a 'taxonomy' or 'post_type'
 * $group_key = taxonomy_slug or post_type slug (post_type can be post, page, or any other - also special type of homepage)
 * $data_key = cat/tag/term slug or post_id
 * $meta_key = slug of a term, post_id of a cpt, or null. If null apply_filters() that is picked up by default-data-container.php
 */