<?php
/**
 * Plugin Name: Layout UI
 * Version: 1.0
 * Description: Uses term meta capability provided by Term Meta Plugin and the field api provided by
 * the WP Fields plugin (both included with this plugin) to add content areas to any page of a
 * site. Most interestingly, this includes taxonomy and taxonomy-term archive pages. And since the
 * homepage is either an archive page or a single page, any theme can use the Layout UI to provide
 * most of the options included in a typical custom options page in a format that is both highly
 * compatible with WordPress design patterns and reusable.
 * 
 * The data is stored in elements (look like widgets) that define and position various content on
 * the page. These content areas are populated using standard WordPress UI conventions. Using
 * existing design patterns keeps the plugin forward compatable.
 * 
 * One benefit of this project is that customizations made while using one theme are still available
 * in a different theme. For this to work the theme will need to support Layout UI. Which is similar
 * to what was needed when the Widget API was introduced.
 * 
 * Author: 10up, Eric Mann, Luke Gedeon, John P. Bloch
 * License: GPLv2
 */

/* 
 * IDEA: Simplest way to share content from one Layout to another is to include a checkbox called "share as"
 * that opens a field that let's you name the LE. Then when that hits post_save, store the name it is
 * shared as and the id of the Layout where it is defined in an options array. Can store content in a
 * transient. Also may include a link that ajax saves that one LE and updates the transient. Or
 * curatables may be a good option for sharing content.
 *
 * TODO:
 *   Store and retrieve data.
 *   Sanitize default LE's
 *   Render active LE forms
 *   work on repeater
 *   pre_get_posts adjust posts per page based on total number of posts in loop LE's plus any LE's that exclude their posts from the loop.
 *   handle alternate starting point when special LE's caused the loop to go deeper than expected.
 *   x finish query in function archive_layout_content() to only get most recent post. Also return the AL classname or object.
 *   add a new default LE type for featured content that demo's the exclude option.
 *   attach LE's to pages, author settings page, possibly some CPT's, and even featured posts.
 *   whitelist supports array
 */




if (!class_exists('Layout_Element')) {

abstract class Layout_Element {

	protected $fields;

	/**
	 * Render containing box with header. Calls render() which is defined by the child class.
	 * Clicking reveal button only shows description when it is in the available column. When in an
	 * active column, it shows description plus form.
	 */
	public function form_container( $values ) {
		?>
		<div class='widget'>
			<div class="widget-top">
				<div class="widget-title-action">
					<a class="widget-action" href="#"></a>
				</div>
				<div class="widget-title"><h4><?php echo $this->title(); ?></h4></div>
			</div>

			<div class="widget-inside">
				<?php $this->form( $values ); ?>

				<div class="widget-control-actions">
					<div class="alignleft">
						<a class="widget-control-remove" href="#remove">Delete</a> |
						<a class="widget-control-close" href="#close">Close</a>
					</div>
					<br class="clear" />
				</div>
			</div>

			<div class="widget-description">
				Here is a description.
			</div>
		</div>
		<?php
	}

	/**
	 * Return Title for LE header bar.
	 */
	abstract protected function title();

	/**
	 * Render a instance of the layout element. Provide defaults for each field if no values are passed.
	 */
	abstract protected function form($values);

	/**
	 * Sanitize a instance of the layout element.
	 */
	abstract protected function sanitize($values);

	/**
	 * Create and return an array of fields that can be used for rendering or sanitization.
	 */
	abstract protected function setup_fields();

	/**
	 * Optional function that will render a field in a format that will work well inside an LE. For
	 * special cases or custom field types, it is perfectly valid to render directly inside the LE's
	 * render() function. You can also use this function and then render other parts seperately.
	 * 
	 * Returns an html field that can be further processed if needed.
	 * 
	 * @param object       $field_object
	 * @param string|array $start_value           single value from db or defaults unless field type is repeater, in which case this should be an array of arrays
	 * @param string       $type                  
	 * @param string       $additional_attributes sanitize before passing anything into this.
	 */
	public function render_field($field_object, $start_value = '', $type = '', $additional_attributes = '') {
		// Requires WP_Fields objects
		if ( ! class_exists( 'WP_Field' ) ) {
			return false;
		}

		/*
		 * Handle repeaters first
		 */
		if ( 'WP_Field_Repeater' == get_class( $field_object ) ) {
			$rendered = '';
			foreach ( (array) $start_value as $section ) {
				// output section header field
				$rendered .= "<input type=\"text\" name=\"{$field_object->name()}[__new_repeatable_section__]\" >";
				$fields = $field_object->fields();
				foreach ( $fields as $field ) {
					$value = ( isset( $section[$field->id()] ) ) ? $section[$field->id()]: '';
					$rendered .= $this->render_field( $field, $value );
				}
			}
			return $rendered;
		}

		// use render hint if available
		if ( empty( $type ) ) {
			$type = $field_object->render_hint;
		}

		// common elements for all fields
		$field_attrs = 'name="' . $field_object->name() . '" id="' . $field_object->id() . '"' . ' class="' . $field_object->css_classes() . '"';

		// textarea is "special" but common enough we handle it here
		if ('textarea' == $type) {
			return '<textarea ' . $field_attrs . '>' . $field_object->esc_value($start_value) . '</textarea>';
		}

		// checkbox is too - any other special fields, though, should be rendered without this function
		if ('checkbox' == $type && '' == $additional_attributes) {
			$value = '1';
			$additional_attributes = checked($field_object->esc_value($start_value), true, false);
		} else {
			$value = $field_object->esc_value($start_value);
		}

		// render all the rest as <input> prevent evil - leaving the door open for stupid, though - use this wisely
		$additional_attributes = preg_replace('/[^a-zA-Z0-9\-="\s]/', '', $additional_attributes);
		if (in_array($type, array('text', 'password', 'checkbox', 'color', 'date', 'datetime', 'datetime-local', 'email', 'month', 'number', 'range', 'search', 'tel', 'time', 'url', 'week'))) {
			return "<input type=\"{$type}\" {$field_attrs} value=\"{$value}\" {$additional_attributes} />";
		}
	}

}
}


/**
 * Just a test of a Repeater field. Remove before moving to production.
 */

if (!class_exists('Layout_Element_RepTest')) {

class Layout_Element_RepTest extends Layout_Element {
	function title(){ return 'Rep Test'; }

	/**
	 * Render a instance of the layout element. Provide defaults for each field if no values are passed.
	 */
	function form($values){
		$fields = $this->setup_fields();
		foreach ( $fields as $field ) {
			?>
			<p>
				<?php echo $field->label(); ?>: 
				<?php echo $this->render_field( $field ); ?>
			</p>
			<?php
		}
	}


	/**
	 * Sanitize a instance of the layout element.
	 */
	function sanitize($values){}

	/**
	 * Create and return an array of field objects (WP_Field) that can be used for rendering and
	 * sanitization.
	 */
	function setup_fields(){
		$repeater2[] = new WP_Field_Integer('Inner Int');
		$repeater2[] = new WP_Field_Check_Box('Check Inner');

		$repeater[] = new WP_Field_Integer('Outer Int');
		$repeater[] = new WP_Field_Check_Box('Check Outer');
		$repeater[] = new WP_Field_Repeater('Inner Repeater', array( 'fields' => $repeater2));

		$fields[] = new WP_Field_Integer('Free Int');
		$fields[] = new WP_Field_Check_Box('Check Free');
		$fields[] = new WP_Field_Repeater('Outer Repeater', array( 'fields' => $repeater));

		
		return $fields;
	}

}
}


/**
 * Define default Layout Elements
 */

if (!class_exists('Layout_Element_Loop')) {

class Layout_Element_Loop extends Layout_Element {
	function title(){ return 'Loop'; }

	/**
	 * Render a instance of the layout element. Provide defaults for each field if no values are passed.
	 */
	function form($values){
		$fields = $this->setup_fields();
		foreach ( $fields as $field ) {
			?>
			<p>
				<?php echo $field->label(); ?>: 
				<?php echo $this->render_field( $field ); ?>
			</p>
			<?php
		}
	}

	/**
	 * Sanitize a instance of the layout element.
	 */
	function sanitize($values){}

	/**
	 * Create and return an array of field objects (WP ) that can be used for rendering or sanitization.
	 */
	function setup_fields(){
		$fields[] = new WP_Field_Integer('Number of Posts');
		$fields[] = new WP_Field_Check_Box('Exclude Featured Items');
		
		return $fields;
	}

}

}

/**
 * Define default Layout Elements
 */

if (!class_exists('Layout_Element_Text_Box')) {

class Layout_Element_Text_Box extends Layout_Element {
	function title(){ return 'Text Box'; }

	/**
	 * Render a instance of the layout element. Provide defaults for each field if no values are passed.
	 */
	function form($values){
		$fields = $this->setup_fields();
		foreach ( $fields as $field ) {
			?>
			<p>
				<?php echo $field->label(); ?>: 
			</p>
			<p>
				<?php echo $this->render_field( $field, '', 'textarea' ); ?>
			</p>
			<?php
		}
	}

	/**
	 * Sanitize a instance of the layout element.
	 */
	function sanitize($values){}

	/**
	 * Create and return an array of field objects (WP ) that can be used for rendering or sanitization.
	 */
	function setup_fields(){
		$fields[] = new WP_Field_Text( 'Text' );
		
		return $fields;
	}

}

}

/**
 * Define default Layout Elements
 */

if (!class_exists('Layout_Element_Widget')) {

class Layout_Element_Widget extends Layout_Element {
	function title(){ return 'WordPress Widget'; }

	/**
	 * Render a instance of the layout element. Provide defaults for each field if no values are passed.
	 */
	function form($values){
		$fields = $this->setup_fields();
		foreach ( $fields as $field ) {
			?>
			<p>
				<?php echo $field->label(); ?>: 
				<?php echo $this->render_field( $field ); ?>
			</p>
			<?php
		}
	}

	/**
	 * Sanitize a instance of the layout element.
	 */
	function sanitize($values){}

	/**
	 * Create and return an array of field objects (WP ) that can be used for rendering or sanitization.
	 */
	function setup_fields(){
		$fields[] = new WP_Field_Integer('Number of Posts');
		$fields[] = new WP_Field_Check_Box('Exclude Featured Items');
		
		return $fields;
	}

}

}

/**
 * Define default Layout Elements
 */

if (!class_exists('Layout_Element_Post')) {

class Layout_Element_Post extends Layout_Element {
	function title(){ return 'Post/Page'; }

	/**
	 * Render a instance of the layout element. Provide defaults for each field if no values are passed.
	 */
	function form($values){
		$fields = $this->setup_fields();
		foreach ( $fields as $field ) {
			?>
			<p>
				<?php echo $field->label(); ?>: 
				<?php echo $this->render_field( $field ); ?>
			</p>
			<?php
		}
	}

	/**
	 * Sanitize a instance of the layout element.
	 */
	function sanitize($values){}

	/**
	 * Create and return an array of field objects (WP ) that can be used for rendering or sanitization.
	 */
	function setup_fields(){
		$fields[] = new WP_Field_Integer('Number of Posts');
		$fields[] = new WP_Field_Check_Box('Exclude Featured Items');
		
		return $fields;
	}

}

}

/**
 * Define default Layout Elements
 */

if (!class_exists('Layout_Element_Grunion')) {

class Layout_Element_Grunion extends Layout_Element {
	function title(){ return 'Simple Form'; }

	/**
	 * Render a instance of the layout element. Provide defaults for each field if no values are passed.
	 */
	function form($values){
		$fields = $this->setup_fields();
		foreach ( $fields as $field ) {
			?>
			<p>
				<?php echo $field->label(); ?>: 
				<?php echo $this->render_field( $field ); ?>
			</p>
			<?php
		}
	}

	/**
	 * Sanitize a instance of the layout element.
	 */
	function sanitize($values){}

	/**
	 * Create and return an array of field objects (WP ) that can be used for rendering or sanitization.
	 */
	function setup_fields(){
		$fields[] = new WP_Field_Integer('Number of Posts');
		$fields[] = new WP_Field_Check_Box('Exclude Featured Items');
		
		return $fields;
	}

}

}

/**
 * Define default Layout Elements
 */

if (!class_exists('Layout_Element_Inherit')) {

class Layout_Element_Inherit extends Layout_Element {
	function title(){ return 'Inherit from another AL'; }

	/**
	 * Render a instance of the layout element. Provide defaults for each field if no values are passed.
	 */
	function form($values){
		$fields = $this->setup_fields();
		foreach ( $fields as $field ) {
			?>
			<p>
				<?php echo $field->label(); ?>: 
				<?php echo $this->render_field( $field ); ?>
			</p>
			<?php
		}
	}

	/**
	 * Sanitize a instance of the layout element.
	 */
	function sanitize($values){}

	/**
	 * Create and return an array of field objects (WP ) that can be used for rendering or sanitization.
	 */
	function setup_fields(){
		$fields[] = new WP_Field_Integer('Number of Posts');
		$fields[] = new WP_Field_Check_Box('Exclude Featured Items');
		
		return $fields;
	}

}

}



/**
 * store widget instance (regular wp widget) for use in this LE
 * this one will need a little work determine how to pass data in to our faux widget area, get it back out, and store it efficiently.
 * trying utilize the widget's form(), update(), and widget() functions. so this function would just call the right update().
 *
  if ( ! class_exists( 'Layout_Element_WP_Widget' ) ) {
  class Layout_Element_WP_Widget extends Layout_Element {

  /**
 * Sanitization callback in this case just calls sanitization for the widget.
 *
  public function sanitize( $value ) {
  return 'clean widget';
  }
  public function render( $value ) {
  return 'ha';
  }
  }
  } */


