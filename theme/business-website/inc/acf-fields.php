<?php
/**
 * Custom ACF fields.
 *
 * @package Business_Website
 */

/**
 * Registers the Gravity Form ACF field type.
 *
 * @return void
 */
function business_website_register_acf_gravity_form_field() {
	if ( ! class_exists( 'acf_field' ) ) {
		return;
	}

	if ( class_exists( 'Business_Website_ACF_Field_Gravity_Form' ) ) {
		return;
	}

	/**
	 * Gravity Form select field for ACF.
	 */
	class Business_Website_ACF_Field_Gravity_Form extends acf_field {

		/**
		 * Sets up the field type data.
		 *
		 * @return void
		 */
		public function initialize() {
			$this->name     = 'gravity_form';
			$this->label    = __( 'Gravity Form', 'business-website' );
			$this->category = 'choice';
			$this->defaults = array(
				'allow_null'  => 1,
				'placeholder' => __( 'Select form', 'business-website' ),
				'ui'          => 1,
			);
		}

		/**
		 * Enqueues Select2 assets from the native ACF select field.
		 *
		 * @return void
		 */
		public function input_admin_enqueue_scripts() {
			if ( ! function_exists( 'acf_get_field_type' ) ) {
				return;
			}

			$select_field = acf_get_field_type( 'select' );

			if ( $select_field && method_exists( $select_field, 'input_admin_enqueue_scripts' ) ) {
				$select_field->input_admin_enqueue_scripts();
			}
		}

		/**
		 * Renders the field input.
		 *
		 * @param array $field Field data.
		 * @return void
		 */
		public function render_field( $field ) {
			$value = empty( $field['value'] ) ? '' : (string) $field['value'];

			if ( ! class_exists( 'GFAPI' ) ) {
				acf_hidden_input(
					array(
						'name'  => $field['name'],
						'value' => $value,
					)
				);

				echo '<p>' . esc_html__( 'Gravity Forms plugin is not active.', 'business-website' ) . '</p>';

				return;
			}

			$choices = $this->get_form_choices();

			if ( empty( $choices ) ) {
				acf_hidden_input(
					array(
						'name'  => $field['name'],
						'value' => $value,
					)
				);

				echo '<p>' . esc_html__( 'No Gravity Forms found.', 'business-website' ) . '</p>';

				return;
			}

			if ( ! empty( $field['allow_null'] ) ) {
				$choices = array( '' => '- ' . $field['placeholder'] . ' -' ) + $choices;
			}

			$select = array(
				'id'               => $field['id'],
				'class'            => $field['class'],
				'name'             => $field['name'],
				'value'            => $value,
				'choices'          => $choices,
				'data-ui'          => $field['ui'],
				'data-placeholder' => $field['placeholder'],
				'data-allow_null'  => $field['allow_null'],
			);

			if ( ! empty( $field['ui'] ) ) {
				acf_hidden_input(
					array(
						'id'   => $field['id'] . '-input',
						'name' => $field['name'],
					)
				);
			}

			acf_select_input( $select );
		}

		/**
		 * Renders field settings.
		 *
		 * @param array $field Field data.
		 * @return void
		 */
		public function render_field_settings( $field ) {
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Allow Null?', 'acf' ),
					'instructions' => '',
					'name'         => 'allow_null',
					'type'         => 'true_false',
					'ui'           => 1,
				)
			);

			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Stylised UI', 'acf' ),
					'instructions' => '',
					'name'         => 'ui',
					'type'         => 'true_false',
					'ui'           => 1,
				)
			);
		}

		/**
		 * Normalizes value before saving.
		 *
		 * @param mixed $value   Field value.
		 * @param mixed $post_id Post ID.
		 * @param array $field   Field data.
		 * @return int|string
		 */
		public function update_value( $value, $post_id, $field ) {
			return empty( $value ) ? '' : absint( $value );
		}

		/**
		 * Returns Gravity Forms choices.
		 *
		 * @return array
		 */
		private function get_form_choices() {
			$choices = array();
			$forms   = GFAPI::get_forms( true, false, 'title', 'ASC' );

			foreach ( $forms as $form ) {
				if ( empty( $form['id'] ) || empty( $form['title'] ) ) {
					continue;
				}

				$choices[ (string) $form['id'] ] = sprintf(
					/* translators: 1: Gravity Form title, 2: Gravity Form ID. */
					__( '%1$s (ID: %2$d)', 'business-website' ),
					$form['title'],
					absint( $form['id'] )
				);
			}

			return $choices;
		}
	}

	acf_register_field_type( 'Business_Website_ACF_Field_Gravity_Form' );
}
add_action( 'acf/include_field_types', 'business_website_register_acf_gravity_form_field' );
