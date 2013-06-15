<?php

/**
 * Adds a meta-box to a term-meta CPT that gives editors a drag and drop interface to rearrange
 * elements on the page.
 *  
 */

if (!class_exists('Layout_UI')) {

class Layout_UI {

	function setup() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		add_action( 'save_post', array( $this, 'save_layout_elements' ) );

	}

	/**
	 * Add metabox that allows user to select which term(s) to attach this Archive Layout to.
	 */
	function add_meta_boxes() {
		add_meta_box('layout_elements', 'Layout Elements', array( $this, 'layout_elements_callback' ), $this->_short_name, 'normal');
	}

	/**
	 * Render the LE sort and setup form in a metabox. 
	 */
	function layout_elements_callback( $al_post ) {

		$this->setup_layout_elements();
		//container for blank LE's
		?>
		<h2>Available</h2>
		<div class="ae-sortable source" style="min-height: 20px;">
		<?php
		// render one each of blank LEs for users to drag into into active area
		foreach ( $this->_layout_elements as $layout_element ) {
			$layout_element->form_container( array() );
		}
		// container for active LE's
		?>
		</div>
		<h2>Active</h2>
		<div class="ae-sortable target" style="min-height: 40px; border: 1px dashed #999;">
			<div class="placeholder">Drop here to activate.</div>
			<?php
			// render any active LEs

			?>
		</div>
		<?php
wp_enqueue_script( 'jquery-ui-droppable' );		
?><script>
	jQuery(function(){
		jQuery( ".ae-sortable .widget" ).draggable({
			appendTo: "body",
			helper: "clone"
		});
		jQuery( ".ae-sortable.target" ).css('background','#f00;').droppable({
			activeClass: "ui-state-default",
			hoverClass: "ui-state-hover",
			accept: ":not(.ui-sortable-helper)",
			drop: function( event, ui ) {
				jQuery( this ).find( ".placeholder" ).remove();
				jQuery( "<div class=\"widget\"></div>" ).html( ui.draggable.html() ).appendTo( this );
			}
		})
		.sortable({
			items: ".widget",
			sort: function() {
				// gets added unintentionally by droppable interacting with sortable
				// using connectWithSortable fixes this, but doesn't allow you to customize active/hoverClass options
				jQuery( this ).removeClass( "ui-state-default" );
			}
		});

		jQuery('.ae-sortable.target').click(function(e){
			//console.log(e);
			//console.log(jQuery(e.target).parent().siblings('.widget-inside'));
			jQuery(e.target).parents('.widget-top').siblings('.widget-inside').toggle();
			return false;
		});
	/*	jQuery('.ae-sortable').sortable({
			connectWith: ".ae-sortable",
			placeholder: "ui-state-highlight"
		});*/
	});
</script>
<style>
	.ae-sortable.source .widget {display:inline;margin:3px;}
	.ae-sortable.source .ui-state-highlight,
	.ae-sortable.source .widget-top,
	.ae-sortable.source .widget-title,
	.ae-sortable.source .widget-title h4 {display:inline}
	.ae-sortable.source .widget-title-action {display:none}
	.ui-state-highlight {min-height: 20px; background: #999;}
</style>
	<?php
	}

	/**
	 * Process save on LE metabox - store values in an array in post-meta
	 */
	function save_layout_elements( $al_post_id ) {
		if(isset($_POST['category'])){ var_dump($_POST);var_dump($_POST['category']['loop']);}
		// setup_layout_elements() - THIS IS HUGE - do not initialize these objects until you need them.
		// loop through LE's sanitizing everything in $_POST and saving to a big array
		// save everything as one peice
	}

	/**
	 * Instatiate classes to render or sanitize values.
	 * 
	 * @return type 
	 */
	function setup_layout_elements() {
		if ( ! isset($this->_layout_elements ) ) {
			$classes = array (
				'Layout_Element_Loop',
				'Layout_Element_Text_Box',
				'Layout_Element_Widget',
				'Layout_Element_Post',
				'Layout_Element_Grunion',
				'Layout_Element_Inherit',
				'Layout_Element_RepTest',
			);

			$classes = apply_filters( 'layout_element_classes', $classes, $this->_short_name );
			
			foreach ( $classes as $class ) {
				$this->_layout_elements[$class] = new $class();
			}
		}
		return $this->_layout_elements;
	}

	/**
	 * Adjust query inside pre_get_post
	 */
	function pre_get_post() {
		// are we really smart enough to be able to do this?
	}

	/**
	 * Render the page as template parts.
	 */
	function render_page() {
		global $achive_layout_query;

		// For our first trick, get the most recent published AL that matches the current layout page.
		$archive_layout_term = get_queried_object();
		var_dump($archive_layout_term);
		$archive_layout_object = new WP_Query( $args );


		// use $this->_used_post_ids to keep track of posts that were already included in a different LE
	}

}}