<?php
/**
 * Proposed generic field class to be used by the Settings API, Widgets, Meta boxes, and any other
 * fields in the WP admin and front end.
 * 
 * May eventually include: js data validation and possibly a field renderer.
 * The render logic should be seperate from the basic classes, though. Rendering is very specific to
 * the form where the field will be inserted.
 * 
 * The idea here is not to render the field but to provide common elements that can make the rendering cleaner
 * and more consistent. Only does sanitization/validation. No saving. No rendering. KISS and prosper!
 * 
 * All public variables and return values provided by this class should be sanitized/escaped and ready for use.
 * 
 * Related tickets:
 * http://core.trac.wordpress.org/ticket/18179
 * http://core.trac.wordpress.org/ticket/18285
 * 
 */
if (!class_exists('WP_Field')) {

abstract class WP_Field {

	// Sanitized name of field as passed to constructor. Expected to be title case with spaces.
	protected $field_name;
	// Label for field based on $field_name passed into construct unless specified in $args
	protected $label;
	// Groups are potentially nested and repeatable sections of the form.
	protected $groups;
	// A string containing class="fieldtype-foo and other classes"
	protected $css_classes;

	/* An array of key value pairs for use in a select list or just valid values for other types
	 * When outputting <option value="foo">Bar</option>, foo is the key and Bar value
	 */
	protected $whitelist;
	// type of input field to use
	public $render_hint;

	/**
	 * Set values for public variables. Concrete class should call this function or provide same functionality.
	 *
	 * @param string $field_name used to build id and name in form field and as a default label.
	 * @param array  $args additional info specific to field type
	 * 
	 * $args can include:
	 *   string label - if different than $field_name
	 *   array  groups - used for organization in larger forms and for repeatables. Array elements themselves are repeatable groups that are children of the parent AL.
	 *   array  css_classes - any classes that should be added to the defaults
	 *   array  whitelist - key value pairs for use in a select list or just valid values for other types
	 *   string whitelist_taxonomy - a shortcut that allows you to provide the name of a taxonomy and let the class build a whitelist from its terms.
	 */
	public function __construct($field_name, $args = array()) {

		// Store $field_name for use in html_attributes.
		$this->field_name = sanitize_text_field($field_name);

		// if a label is passed in $args use that, else use $field_name.
		$this->label = sanitize_text_field(( isset($args['label']) ) ? $args['label'] : $field_name );

		// store groups that the field may be nested in.
		$this->groups = ( isset($args['groups']) ) ? (array) $arg['groups'] : array();
		array_walk($this->groups, 'sanitize_key');

		// calculate css class based on php class and any others passed in.
		$classes = ( isset($args['css_classes']) && is_array($args['css_classes']) ) ? $args['css_classes'] : array();
		array_unshift($classes, 'field-type-' . get_class());
		array_walk($classes, 'sanitize_html_class');

		$this->css_classes = implode(' ', $classes);

		// sanitize and store white_list
		$this->whitelist = ( isset($args['whitelist']) ) ? (array) $args['whitelist'] : array();
		array_walk($this->whitelist, 'sanitize_text_field');
	}

	public function add_group( $group ) {
		if ( ! empty( $group ) && is_string( $group ) ) {
			array_unshift( $this->groups, $group );
		}
	}

	public function label() {
		return $this->label;
	}

	public function css_classes() {
		return $this->css_classes;
	}

	public function whitelist() {
		return $this->whitelist;
	}

	/**
	 * Return $field_name formated for use in name="foo"
	 */
	public function name() {
		// Replace spaces with underscore and removes any invalid characters.
		$groups = $this->groups + (array) sanitize_key( preg_replace( '/\s+/', '_', $this->field_name ) );
var_dump($groups);
		$name = array_shift( $groups );
		$name .= ( empty( $groups ) ) ? "" : "[" . implode("][", $this->groups) . "]";

		return $name;
	}

	/**
	 * Return $field_name formated for use in id="foo"
	 */
	public function id() {
		// Replace spaces with hypen  and removes any invalid characters.
		$groups = $this->groups + (array) sanitize_key( preg_replace( '/\s+/', '-', $this->field_name ) );

		$id = ( empty($this->groups) ) ? "" : implode("-", $this->groups);

		return $id;
	}

	/**
	 * Sanitize value before storage in database.
	 */
	abstract protected function sanitize($value);

	/**
	 * Prepare value for output into form.
	 */
	abstract protected function esc_value($value);
}

}

/**
 * Meta field class for the WordPress WYSIWYG Editor and other field types that allow html
 */
if (!class_exists('WP_Field_HTML')) {

class WP_Field_HTML extends WP_Field {

	public $render_hint = 'textarea';

	/**
	 * Sanitization callback for html content.
	 */
	public function sanitize($value) {

		$value = wp_kses($value, wp_kses_allowed_html('post'));
		return $value;
	}

	public function esc_value($value) {
		esc_textarea($value);
	}

}

}

if (!class_exists('WP_Field_Text')) {

	/**
	 * Field class for a basic textbox
	 */
class WP_Field_Text extends WP_Field {

	public $render_hint = 'text';

	/**
	 * Sanitization callback for text field.
	 */
	public function sanitize($value) {
		$value = sanitize_text_field($value);
		return $value;
	}

	public function esc_value($value) {
		return esc_textarea($value);
	}

}

}

if (!class_exists('WP_Field_Integer')) {

	/**
	 * Field class for a basic integer
	 */
class WP_Field_Integer extends WP_Field {

	public $render_hint = 'number';

	/**
	 * Cast to integer in both directions.
	 */
	public function sanitize($value) {
		return (int) $value;
	}

	public function esc_value($value) {
		return (int) $value;
	}

}

}

if (!class_exists('WP_Field_URL')) {

	/**
	 * Field class for a basic URL. Can also be used in conjuction with an upload field.
	 */
class WP_Field_URL extends WP_Field {

	public $render_hint = 'url';

	/**
	 * Sanitization url for use in db.
	 */
	public function sanitize($value) {
		return esc_url_raw($value);
	}

	public function esc_value($value) {
		return esc_url($value);
	}

}

}

if (!class_exists('WP_Field_Check_Box')) {

/**
	* Field class for a basic check-box.
	*/
class WP_Field_Check_Box extends WP_Field {

	public $render_hint = 'checkbox';

	/**
	 * Sanitization url for use in db.
	 */
	public function sanitize($value) {
		return (bool) $value;
	}

	public function esc_value($value) {
		return (bool) $value;
	}

}

}

if (!class_exists('WP_Field_List')) {

/**
	* Field class for a list of one or more values to compare against a whitelist.
	* This works well for select lists, drop downs, and radio options. Note that sanitization
	* and escaping are the same for these types. Only redering is different.
	*/
class WP_Field_List extends WP_Field {

	/**
	 * Sanitization of values against a whitelist.
	 */
	public function sanitize($values) {
		$values = (array) $values;
		foreach ($values as $key => $value) {
			if (in_array($value, $this->whitelist)) {
				$values[$key] = sanitize_key($value);
			} else {
				unset($values[$key]);
			}
		}
		return $values;
	}

	public function esc_value($values) {
		$values = (array) $values;
		array_walk($values, 'esc_textarea');
		return $values;
	}

}

}

if (!class_exists('WP_Field_Taxonomy_List')) {

/**
	* Field class for a list of one or more values to compare against the terms of a taxonomy.
	* Works well for taxonomy drop-downs and select lists, and for tag lists.
	*/
class WP_Field_Taxonomy_List extends WP_Field {

	/**
	 * Store the taxonomy that we test against.
	 */
	protected $whitelist_taxonomy;

	/**
	 * Override to store an additional value but call parent to store all other values.
	 */
	public function __construct($field_name, $args = array()) {
		parent::__construct($field_name, $args);

		// Store the taxonomy that we test against.
		$this->whitelist_taxonomy = ( isset($args['whitelist_taxonomy']) && taxonomy_exists($args['whitelist_taxonomy']) ) ? $args['whitelist_taxonomy'] : '';
	}

	/**
	 * Sanitize values against a whitelist of the terms in this taxonomy.
	 * Can handle an array of terms as actual text values or term ids.
	 */
	public function sanitize($values) {
		$values = (array) $values;
		foreach ($values as $key => $value) {
			if (!term_exists($value, $this->whitelist_taxonomy)) {
				unset($values[$key]);
			}
		}
		return $values;
	}

	public function esc_value($values) {
		$values = (array) $values;
		array_walk($values, 'sanitize_key');
		return $values;
	}

}

/**
 * 
 */
class WP_Field_Repeater extends WP_Field {

	/**
	 * Store the fields that we need to repeat.
	 */
	protected $fields;

	/**
	 * 
	 */
	public function __construct($field_name, $args = array()) {
		parent::__construct($field_name, $args);

		$fields = array();
		if ( isset( $args['fields'] ) ) {
			foreach ( $args['fields'] as $field ) {
				if ( 'WP_Field' == get_parent_class( $field ) ) {
					$field->add_group( sanitize_key( preg_replace( '/\s+/', '_', $this->field_name ) ) );
					$fields[] = $field;
				}
			}
		}

		$this->fields = $fields;
	}

	/**
	 * 
	 */
	public function sanitize($values) {
	}

	public function esc_value($values) {
	}

	public function fields() {
		return (array) $this->fields;
	}
	
	public function add_group( $new_group ) {
		if ( ! empty( $new_group ) && is_string( $new_group ) ) {
			parent::add_group( $new_group );
			foreach ( $this->fields as $field ) {
				$field->add_group( $new_group );
			}
		}
	}
}

}



