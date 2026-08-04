<?php
/**
 * SalesDrive and Dilovod integration for the validation form.
 *
 * @package Eko_Plastics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the default Dilovod details template.
 *
 * @return string
 */
function eko_plastics_get_default_dilovod_details_template() {
	return '{"phones":[{"pr":"{phone}","kind":"phone"}],"emails":[],"messengers":[],"urls":[],"attributes":[],"notes":[]}';
}

/**
 * Returns CRM integration options with defaults.
 *
 * @return array
 */
function eko_plastics_get_crm_integration_options() {
	$options = get_option( 'eko_plastics_crm_integrations', array() );

	$options = wp_parse_args(
		is_array( $options ) ? $options : array(),
		array(
			'salesdrive_enabled'  => '0',
			'salesdrive_endpoint' => 'https://emanon-sandbox.salesdrive.me/handler/',
			'salesdrive_api_key'  => '',
			'salesdrive_form_id'  => '',
			'salesdrive_source'   => get_bloginfo( 'name' ),
			'salesdrive_format'   => 'json',
			'dilovod_enabled'     => '0',
			'dilovod_endpoint'    => 'https://api.dilovod.ua',
			'dilovod_key'         => '',
			'dilovod_parent_id'   => '',
			'dilovod_person_type' => '',
			'dilovod_details_tpl' => eko_plastics_get_default_dilovod_details_template(),
			'dilovod_trigger'     => 'salesdrive_webhook',
			'webhook_secret'      => '',
			'telegram_enabled'    => '0',
			'telegram_bot_token'  => '',
			'telegram_chat_id'    => '',
			'telegram_cooldown'   => 900,
			'debug'               => '0',
		)
	);

	if ( empty( $options['dilovod_details_tpl'] ) ) {
		$options['dilovod_details_tpl'] = eko_plastics_get_default_dilovod_details_template();
	}

	return $options;
}

/**
 * Sanitizes CRM integration settings.
 *
 * @param array $input Raw settings.
 * @return array
 */
function eko_plastics_sanitize_crm_integration_options( $input ) {
	$input = is_array( $input ) ? $input : array();
	$format = isset( $input['salesdrive_format'] ) ? sanitize_key( wp_unslash( $input['salesdrive_format'] ) ) : 'json';

	if ( ! in_array( $format, array( 'json', 'form' ), true ) ) {
		$format = 'json';
	}

	$trigger = isset( $input['dilovod_trigger'] ) ? sanitize_key( wp_unslash( $input['dilovod_trigger'] ) ) : 'salesdrive_webhook';

	if ( ! in_array( $trigger, array( 'salesdrive_webhook', 'after_form_submit' ), true ) ) {
		$trigger = 'salesdrive_webhook';
	}

	$dilovod_details_template = isset( $input['dilovod_details_tpl'] ) ? trim( sanitize_textarea_field( wp_unslash( $input['dilovod_details_tpl'] ) ) ) : '';

	if ( '' === $dilovod_details_template ) {
		$dilovod_details_template = eko_plastics_get_default_dilovod_details_template();
	}

	$settings = array(
		'salesdrive_enabled'  => empty( $input['salesdrive_enabled'] ) ? '0' : '1',
		'salesdrive_endpoint' => isset( $input['salesdrive_endpoint'] ) ? esc_url_raw( trim( $input['salesdrive_endpoint'] ) ) : '',
		'salesdrive_api_key'  => isset( $input['salesdrive_api_key'] ) ? sanitize_text_field( wp_unslash( $input['salesdrive_api_key'] ) ) : '',
		'salesdrive_form_id'  => isset( $input['salesdrive_form_id'] ) ? sanitize_text_field( wp_unslash( $input['salesdrive_form_id'] ) ) : '',
		'salesdrive_source'   => isset( $input['salesdrive_source'] ) ? sanitize_text_field( wp_unslash( $input['salesdrive_source'] ) ) : '',
		'salesdrive_format'   => $format,
		'dilovod_enabled'     => empty( $input['dilovod_enabled'] ) ? '0' : '1',
		'dilovod_endpoint'    => isset( $input['dilovod_endpoint'] ) ? esc_url_raw( trim( $input['dilovod_endpoint'] ) ) : 'https://api.dilovod.ua',
		'dilovod_key'         => isset( $input['dilovod_key'] ) ? sanitize_text_field( wp_unslash( $input['dilovod_key'] ) ) : '',
		'dilovod_parent_id'   => isset( $input['dilovod_parent_id'] ) ? preg_replace( '/\D+/', '', sanitize_text_field( wp_unslash( $input['dilovod_parent_id'] ) ) ) : '',
		'dilovod_person_type' => isset( $input['dilovod_person_type'] ) ? preg_replace( '/\D+/', '', sanitize_text_field( wp_unslash( $input['dilovod_person_type'] ) ) ) : '',
		'dilovod_details_tpl' => $dilovod_details_template,
		'dilovod_trigger'     => $trigger,
		'webhook_secret'      => isset( $input['webhook_secret'] ) ? sanitize_text_field( wp_unslash( $input['webhook_secret'] ) ) : '',
		'telegram_enabled'    => empty( $input['telegram_enabled'] ) ? '0' : '1',
		'telegram_bot_token'  => isset( $input['telegram_bot_token'] ) ? sanitize_text_field( wp_unslash( $input['telegram_bot_token'] ) ) : '',
		'telegram_chat_id'    => isset( $input['telegram_chat_id'] ) ? sanitize_text_field( wp_unslash( $input['telegram_chat_id'] ) ) : '',
		'telegram_cooldown'   => isset( $input['telegram_cooldown'] ) ? max( 60, absint( $input['telegram_cooldown'] ) ) : 900,
		'debug'               => empty( $input['debug'] ) ? '0' : '1',
	);

	if ( '1' === $settings['dilovod_enabled'] && empty( $settings['dilovod_parent_id'] ) ) {
		add_settings_error(
			'eko_plastics_crm_integrations',
			'dilovod_parent_id_missing',
			__( 'Dilovod Client category parent ID is required when Dilovod integration is enabled.', 'eko-plastics' )
		);
	}

	if ( '1' === $settings['dilovod_enabled'] && 'salesdrive_webhook' === $settings['dilovod_trigger'] && empty( $settings['webhook_secret'] ) ) {
		add_settings_error(
			'eko_plastics_crm_integrations',
			'webhook_secret_missing',
			__( 'Webhook secret is required when Dilovod trigger is set to SalesDrive webhook.', 'eko-plastics' )
		);
	}

	if ( '1' === $settings['telegram_enabled'] && ( empty( $settings['telegram_bot_token'] ) || empty( $settings['telegram_chat_id'] ) ) ) {
		add_settings_error(
			'eko_plastics_crm_integrations',
			'telegram_not_configured',
			__( 'Telegram bot token and chat ID are required when Telegram alerts are enabled.', 'eko-plastics' )
		);
	}

	$details_test = strtr(
		$settings['dilovod_details_tpl'],
		array(
			'{phone}' => '+380671234567',
			'{name}'  => 'Test',
		)
	);

	json_decode( $details_test, true );

	if ( JSON_ERROR_NONE !== json_last_error() ) {
		add_settings_error(
			'eko_plastics_crm_integrations',
			'dilovod_details_invalid',
			__( 'Dilovod details JSON template is not valid JSON.', 'eko-plastics' )
		);
	}

	return $settings;
}

/**
 * Registers CRM integration settings.
 */
function eko_plastics_register_crm_integration_settings() {
	register_setting(
		'eko_plastics_crm_integrations',
		'eko_plastics_crm_integrations',
		array(
			'sanitize_callback' => 'eko_plastics_sanitize_crm_integration_options',
		)
	);
}
add_action( 'admin_init', 'eko_plastics_register_crm_integration_settings' );

/**
 * Adds CRM integration settings page.
 */
function eko_plastics_add_crm_integration_settings_page() {
	add_options_page(
		__( 'CRM Integrations', 'eko-plastics' ),
		__( 'CRM Integrations', 'eko-plastics' ),
		'manage_options',
		'eko-plastics-crm-integrations',
		'eko_plastics_render_crm_integration_settings_page'
	);
}
add_action( 'admin_menu', 'eko_plastics_add_crm_integration_settings_page' );

/**
 * Renders a text field for CRM settings.
 *
 * @param string $name        Option name.
 * @param string $label       Label.
 * @param string $description Field description.
 * @param string $type        Input type.
 */
function eko_plastics_render_crm_field( $name, $label, $description = '', $type = 'text' ) {
	$options = eko_plastics_get_crm_integration_options();
	$value   = isset( $options[ $name ] ) ? $options[ $name ] : '';
	$id      = 'eko_plastics_crm_' . $name;
	?>
	<tr>
		<th scope="row">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		</th>
		<td>
			<input
				id="<?php echo esc_attr( $id ); ?>"
				class="regular-text"
				type="<?php echo esc_attr( $type ); ?>"
				name="eko_plastics_crm_integrations[<?php echo esc_attr( $name ); ?>]"
				value="<?php echo esc_attr( $value ); ?>"
			>
			<?php if ( $description ) : ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Renders a checkbox field for CRM settings.
 *
 * @param string $name        Option name.
 * @param string $label       Label.
 * @param string $description Field description.
 */
function eko_plastics_render_crm_checkbox( $name, $label, $description = '' ) {
	$options = eko_plastics_get_crm_integration_options();
	$id      = 'eko_plastics_crm_' . $name;
	?>
	<tr>
		<th scope="row"><?php echo esc_html( $label ); ?></th>
		<td>
			<label for="<?php echo esc_attr( $id ); ?>">
				<input
					id="<?php echo esc_attr( $id ); ?>"
					type="checkbox"
					name="eko_plastics_crm_integrations[<?php echo esc_attr( $name ); ?>]"
					value="1"
					<?php checked( '1', $options[ $name ] ); ?>
				>
				<?php echo esc_html( $description ); ?>
			</label>
		</td>
	</tr>
	<?php
}

/**
 * Renders a textarea field for CRM settings.
 *
 * @param string $name        Option name.
 * @param string $label       Label.
 * @param string $description Field description.
 */
function eko_plastics_render_crm_textarea( $name, $label, $description = '' ) {
	$options = eko_plastics_get_crm_integration_options();
	$value   = isset( $options[ $name ] ) ? $options[ $name ] : '';
	$id      = 'eko_plastics_crm_' . $name;
	?>
	<tr>
		<th scope="row">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		</th>
		<td>
			<textarea
				id="<?php echo esc_attr( $id ); ?>"
				class="large-text code"
				rows="5"
				name="eko_plastics_crm_integrations[<?php echo esc_attr( $name ); ?>]"
			><?php echo esc_textarea( $value ); ?></textarea>
			<?php if ( $description ) : ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Renders SalesDrive request format select.
 */
function eko_plastics_render_salesdrive_format_field() {
	$options = eko_plastics_get_crm_integration_options();
	?>
	<tr>
		<th scope="row">
			<label for="eko_plastics_crm_salesdrive_format"><?php esc_html_e( 'Request format', 'eko-plastics' ); ?></label>
		</th>
		<td>
			<select id="eko_plastics_crm_salesdrive_format" name="eko_plastics_crm_integrations[salesdrive_format]">
				<option value="json" <?php selected( 'json', $options['salesdrive_format'] ); ?>>JSON</option>
				<option value="form" <?php selected( 'form', $options['salesdrive_format'] ); ?>>Form URL Encoded</option>
			</select>
			<p class="description"><?php esc_html_e( 'Use JSON for API endpoints and Form URL Encoded for website-form endpoints.', 'eko-plastics' ); ?></p>
		</td>
	</tr>
	<?php
}

/**
 * Renders Dilovod trigger select.
 */
function eko_plastics_render_dilovod_trigger_field() {
	$options = eko_plastics_get_crm_integration_options();
	?>
	<tr>
		<th scope="row">
			<label for="eko_plastics_crm_dilovod_trigger"><?php esc_html_e( 'Dilovod trigger', 'eko-plastics' ); ?></label>
		</th>
		<td>
			<select id="eko_plastics_crm_dilovod_trigger" name="eko_plastics_crm_integrations[dilovod_trigger]">
				<option value="salesdrive_webhook" <?php selected( 'salesdrive_webhook', $options['dilovod_trigger'] ); ?>>SalesDrive webhook</option>
				<option value="after_form_submit" <?php selected( 'after_form_submit', $options['dilovod_trigger'] ); ?>>After form submit</option>
			</select>
			<p class="description"><?php esc_html_e( 'Use SalesDrive webhook for the event-based flow required by the task.', 'eko-plastics' ); ?></p>
		</td>
	</tr>
	<?php
}

/**
 * Renders CRM integration settings page.
 */
function eko_plastics_render_crm_integration_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$options = eko_plastics_get_crm_integration_options();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'CRM Integrations', 'eko-plastics' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'eko_plastics_crm_integrations' ); ?>

			<h2><?php esc_html_e( 'SalesDrive', 'eko-plastics' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				eko_plastics_render_crm_checkbox( 'salesdrive_enabled', __( 'Enable SalesDrive', 'eko-plastics' ), __( 'Send validated form submissions to SalesDrive.', 'eko-plastics' ) );
				eko_plastics_render_crm_field( 'salesdrive_endpoint', __( 'Endpoint URL', 'eko-plastics' ), __( 'Use the SalesDrive order creation endpoint, for example https://emanon-sandbox.salesdrive.me/handler/.', 'eko-plastics' ) );
				eko_plastics_render_crm_field( 'salesdrive_api_key', __( 'API key', 'eko-plastics' ), __( 'Stored in WordPress options; do not commit real keys to code.', 'eko-plastics' ), 'password' );
				eko_plastics_render_crm_field( 'salesdrive_form_id', __( 'Form ID', 'eko-plastics' ), __( 'Optional. Used if your SalesDrive endpoint expects a form/base identifier.', 'eko-plastics' ) );
				eko_plastics_render_crm_field( 'salesdrive_source', __( 'Source', 'eko-plastics' ), __( 'Source label that will be sent with the lead.', 'eko-plastics' ) );
				eko_plastics_render_salesdrive_format_field();
				?>
			</table>

			<h2><?php esc_html_e( 'Dilovod', 'eko-plastics' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				eko_plastics_render_crm_checkbox( 'dilovod_enabled', __( 'Enable Dilovod', 'eko-plastics' ), __( 'Create a Dilovod client after SalesDrive accepts the lead.', 'eko-plastics' ) );
				eko_plastics_render_crm_field( 'dilovod_endpoint', __( 'Endpoint URL', 'eko-plastics' ), __( 'Default Dilovod gateway is https://api.dilovod.ua.', 'eko-plastics' ) );
				eko_plastics_render_crm_field( 'dilovod_key', __( 'API key', 'eko-plastics' ), __( 'API node key from Dilovod.', 'eko-plastics' ), 'password' );
				eko_plastics_render_crm_field( 'dilovod_parent_id', __( 'Client category parent ID', 'eko-plastics' ), __( 'ID of the Dilovod persons folder/category named Client.', 'eko-plastics' ) );
				eko_plastics_render_crm_field( 'dilovod_person_type', __( 'Person type ID', 'eko-plastics' ), __( 'Optional Dilovod personType ID. Leave empty if your base sets it automatically.', 'eko-plastics' ) );
				eko_plastics_render_crm_textarea( 'dilovod_details_tpl', __( 'Details JSON template', 'eko-plastics' ), __( 'Optional. Paste details JSON from Dilovod developer tools and use {phone} as phone placeholder.', 'eko-plastics' ) );
				eko_plastics_render_dilovod_trigger_field();
				eko_plastics_render_crm_field( 'webhook_secret', __( 'Webhook secret', 'eko-plastics' ), __( 'Add this secret to the SalesDrive webhook URL as ?secret=your-secret.', 'eko-plastics' ), 'password' );
				eko_plastics_render_crm_checkbox( 'debug', __( 'Debug log', 'eko-plastics' ), __( 'Write integration errors to the PHP error log.', 'eko-plastics' ) );
				?>
			</table>

			<p>
				<strong><?php esc_html_e( 'SalesDrive webhook URL:', 'eko-plastics' ); ?></strong>
				<code><?php echo esc_html( add_query_arg( 'secret', $options['webhook_secret'], rest_url( 'eko-plastics/v1/salesdrive-webhook' ) ) ); ?></code>
			</p>

			<h2><?php esc_html_e( 'Telegram alerts', 'eko-plastics' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				eko_plastics_render_crm_checkbox( 'telegram_enabled', __( 'Enable Telegram alerts', 'eko-plastics' ), __( 'Send a Telegram notification when SalesDrive or Dilovod API fails.', 'eko-plastics' ) );
				eko_plastics_render_crm_field( 'telegram_bot_token', __( 'Bot token', 'eko-plastics' ), __( 'Create a bot with BotFather and paste its token here.', 'eko-plastics' ), 'password' );
				eko_plastics_render_crm_field( 'telegram_chat_id', __( 'Chat ID', 'eko-plastics' ), __( 'Chat, group, or channel ID where alerts should be sent.', 'eko-plastics' ) );
				eko_plastics_render_crm_field( 'telegram_cooldown', __( 'Alert cooldown', 'eko-plastics' ), __( 'Minimum seconds between repeated alerts for the same service. Default: 900.', 'eko-plastics' ), 'number' );
				?>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Logs CRM integration details when debug is enabled.
 *
 * @param string $message Message.
 * @param mixed  $context Context.
 */
function eko_plastics_crm_log( $message, $context = null ) {
	$options = eko_plastics_get_crm_integration_options();

	if ( '1' !== $options['debug'] ) {
		return;
	}

	error_log( '[Eko Plastics CRM] ' . $message . ( null === $context ? '' : ' ' . wp_json_encode( $context ) ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
}

/**
 * Sends a throttled Telegram alert about an integration failure.
 *
 * @param string $service Service name.
 * @param string $message Alert message.
 * @param mixed  $context Optional context.
 * @param bool   $force   Whether to bypass cooldown.
 * @return true|WP_Error
 */
function eko_plastics_send_telegram_api_alert( $service, $message, $context = null, $force = false ) {
	$options = eko_plastics_get_crm_integration_options();

	if ( '1' !== $options['telegram_enabled'] || empty( $options['telegram_bot_token'] ) || empty( $options['telegram_chat_id'] ) ) {
		return new WP_Error( 'telegram_not_configured', __( 'Telegram alerts are not configured.', 'eko-plastics' ) );
	}

	$service = sanitize_key( $service );
	$cooldown_key = 'eko_plastics_telegram_alert_' . md5( $service . '|' . $message );

	if ( ! $force && get_transient( $cooldown_key ) ) {
		return true;
	}

	$text = sprintf(
		"API alert: %s\nSite: %s\nMessage: %s",
		$service,
		home_url( '/' ),
		$message
	);

	if ( null !== $context ) {
		$context_json = wp_json_encode( $context );

		if ( $context_json ) {
			$text .= "\nContext: " . wp_strip_all_tags( substr( $context_json, 0, 700 ) );
		}
	}

	$response = wp_remote_post(
		'https://api.telegram.org/bot' . $options['telegram_bot_token'] . '/sendMessage',
		array(
			'timeout' => 10,
			'body'    => array(
				'chat_id' => $options['telegram_chat_id'],
				'text'    => $text,
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		eko_plastics_crm_log( 'Telegram alert request failed.', $response->get_error_message() );
		return $response;
	}

	$status_code = wp_remote_retrieve_response_code( $response );

	if ( $status_code < 200 || $status_code >= 300 ) {
		$body = wp_remote_retrieve_body( $response );

		eko_plastics_crm_log(
			'Telegram alert returned a bad HTTP status.',
			array(
				'status' => $status_code,
				'body'   => $body,
			)
		);
		return new WP_Error( 'telegram_bad_status', sprintf( 'Telegram returned HTTP %1$d: %2$s', $status_code, $body ) );
	}

	if ( ! $force ) {
		set_transient( $cooldown_key, 1, absint( $options['telegram_cooldown'] ) );
	}

	return true;
}

/**
 * Gets SalesDrive order ID from an API or webhook response.
 *
 * @param array $data SalesDrive data.
 * @return string
 */
function eko_plastics_get_salesdrive_order_id( $data ) {
	if ( isset( $data['data']['orderId'] ) ) {
		return sanitize_text_field( (string) $data['data']['orderId'] );
	}

	if ( isset( $data['data']['id'] ) ) {
		return sanitize_text_field( (string) $data['data']['id'] );
	}

	if ( isset( $data['orderId'] ) ) {
		return sanitize_text_field( (string) $data['orderId'] );
	}

	if ( isset( $data['id'] ) ) {
		return sanitize_text_field( (string) $data['id'] );
	}

	return '';
}

/**
 * Tracks a SalesDrive lead while waiting for its webhook.
 *
 * @param array $payload           Validated form payload.
 * @param array $salesdrive_result SalesDrive response.
 */
function eko_plastics_track_salesdrive_webhook_wait( $payload, $salesdrive_result ) {
	$order_id = eko_plastics_get_salesdrive_order_id( $salesdrive_result );

	if ( '' === $order_id ) {
		$order_id = md5( wp_json_encode( $payload ) . time() );
	}

	$transient_key = 'eko_plastics_salesdrive_webhook_pending_' . md5( $order_id );

	set_transient(
		$transient_key,
		array(
			'order_id' => $order_id,
			'name'     => isset( $payload['name'] ) ? $payload['name'] : '',
			'phone'    => isset( $payload['phone'] ) ? $payload['phone'] : '',
			'page'     => isset( $payload['page'] ) ? $payload['page'] : '',
		),
		10 * MINUTE_IN_SECONDS
	);

	if ( ! wp_next_scheduled( 'eko_plastics_salesdrive_webhook_timeout', array( $order_id ) ) ) {
		wp_schedule_single_event( time() + 5 * MINUTE_IN_SECONDS, 'eko_plastics_salesdrive_webhook_timeout', array( $order_id ) );
	}
}

/**
 * Marks a SalesDrive webhook as received.
 *
 * @param array $body Webhook body.
 */
function eko_plastics_mark_salesdrive_webhook_received( $body ) {
	$order_id = eko_plastics_get_salesdrive_order_id( $body );

	if ( '' === $order_id ) {
		return;
	}

	delete_transient( 'eko_plastics_salesdrive_webhook_pending_' . md5( $order_id ) );
}

/**
 * Alerts when a SalesDrive webhook was expected but did not arrive.
 *
 * @param string $order_id SalesDrive order ID.
 */
function eko_plastics_alert_missing_salesdrive_webhook( $order_id ) {
	$transient_key = 'eko_plastics_salesdrive_webhook_pending_' . md5( $order_id );
	$pending       = get_transient( $transient_key );

	if ( ! is_array( $pending ) ) {
		return;
	}

	eko_plastics_send_telegram_api_alert(
		'SalesDrive',
		'SalesDrive lead was created, but webhook was not received within 5 minutes.',
		$pending
	);
	eko_plastics_crm_log( 'SalesDrive webhook timeout.', $pending );
	delete_transient( $transient_key );
}
add_action( 'eko_plastics_salesdrive_webhook_timeout', 'eko_plastics_alert_missing_salesdrive_webhook' );

/**
 * Adds a simple interval for CRM API health checks.
 *
 * @param array $schedules Cron schedules.
 * @return array
 */
function eko_plastics_add_crm_health_check_schedule( $schedules ) {
	$schedules['eko_plastics_15_minutes'] = array(
		'interval' => 15 * MINUTE_IN_SECONDS,
		'display'  => __( 'Every 15 minutes', 'eko-plastics' ),
	);

	return $schedules;
}
add_filter( 'cron_schedules', 'eko_plastics_add_crm_health_check_schedule' );

/**
 * Schedules CRM API health checks.
 */
function eko_plastics_schedule_crm_health_check() {
	if ( ! wp_next_scheduled( 'eko_plastics_crm_health_check' ) ) {
		wp_schedule_event( time() + MINUTE_IN_SECONDS, 'eko_plastics_15_minutes', 'eko_plastics_crm_health_check' );
	}
}
add_action( 'init', 'eko_plastics_schedule_crm_health_check' );

/**
 * Clears CRM API health checks when the theme is switched.
 */
function eko_plastics_clear_crm_health_check() {
	wp_clear_scheduled_hook( 'eko_plastics_crm_health_check' );
}
add_action( 'switch_theme', 'eko_plastics_clear_crm_health_check' );

/**
 * Checks whether an API endpoint responds.
 *
 * @param string $service Service name.
 * @param string $url     Endpoint URL.
 */
function eko_plastics_check_crm_api_endpoint( $service, $url ) {
	if ( empty( $url ) ) {
		return;
	}

	$response = wp_remote_request(
		$url,
		array(
			'method'      => 'HEAD',
			'timeout'     => 10,
			'redirection' => 2,
		)
	);

	if ( is_wp_error( $response ) ) {
		eko_plastics_send_telegram_api_alert( $service, 'Health check failed: ' . $response->get_error_message() );
		eko_plastics_crm_log( $service . ' health check failed.', $response->get_error_message() );
		return;
	}

	$status_code = (int) wp_remote_retrieve_response_code( $response );

	if ( 0 === $status_code || $status_code >= 500 ) {
		eko_plastics_send_telegram_api_alert( $service, 'Health check returned HTTP status: ' . $status_code );
		eko_plastics_crm_log( $service . ' health check returned a bad HTTP status.', $status_code );
	}
}

/**
 * Runs CRM API health checks and alerts Telegram when an API stops responding.
 */
function eko_plastics_run_crm_health_check() {
	$options = eko_plastics_get_crm_integration_options();

	if ( '1' !== $options['telegram_enabled'] ) {
		return;
	}

	if ( '1' === $options['salesdrive_enabled'] ) {
		eko_plastics_check_crm_api_endpoint( 'SalesDrive', $options['salesdrive_endpoint'] );
	}

	if ( '1' === $options['dilovod_enabled'] ) {
		eko_plastics_check_crm_api_endpoint( 'Dilovod', $options['dilovod_endpoint'] );
	}
}
add_action( 'eko_plastics_crm_health_check', 'eko_plastics_run_crm_health_check' );

/**
 * Sends the validated lead to SalesDrive.
 *
 * @param array $payload Validated form payload.
 * @return array|WP_Error
 */
function eko_plastics_send_salesdrive_lead( $payload ) {
	$options = eko_plastics_get_crm_integration_options();

	if ( '1' !== $options['salesdrive_enabled'] ) {
		eko_plastics_send_telegram_api_alert( 'SalesDrive', 'SalesDrive integration is disabled while trying to send a lead.', $payload );
		return new WP_Error( 'salesdrive_disabled', __( 'SalesDrive integration is disabled.', 'eko-plastics' ) );
	}

	if ( empty( $options['salesdrive_endpoint'] ) || empty( $options['salesdrive_api_key'] ) ) {
		eko_plastics_send_telegram_api_alert( 'SalesDrive', 'SalesDrive integration is not configured while trying to send a lead.', $payload );
		return new WP_Error( 'salesdrive_not_configured', __( 'SalesDrive integration is not configured.', 'eko-plastics' ) );
	}

	$request_body = array(
		'getResultData' => 1,
		'fName'         => $payload['name'],
		'phone'         => $payload['phone'],
		'comment'       => sprintf(
			'%s %s',
			__( 'Lead from validation form.', 'eko-plastics' ),
			isset( $payload['page'] ) ? $payload['page'] : ''
		),
		'utmPage'       => isset( $payload['page'] ) ? $payload['page'] : '',
		'utmSource'     => $options['salesdrive_source'],
		'externalId'    => 'validation-form-' . time(),
	);

	if ( ! empty( $options['salesdrive_form_id'] ) ) {
		$request_body['form']   = $options['salesdrive_form_id'];
		$request_body['formId'] = $options['salesdrive_form_id'];
	}

	$request_body = apply_filters( 'eko_plastics_salesdrive_lead_payload', $request_body, $payload, $options );

	$args = array(
		'timeout' => 20,
		'headers' => array(
			'X-Api-Key' => $options['salesdrive_api_key'],
		),
	);

	if ( 'form' === $options['salesdrive_format'] ) {
		$args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
		$args['body']                    = $request_body;
	} else {
		$args['headers']['Content-Type'] = 'application/json';
		$args['body']                    = wp_json_encode( $request_body );
	}

	$response = wp_remote_post( $options['salesdrive_endpoint'], $args );

	if ( is_wp_error( $response ) ) {
		eko_plastics_crm_log( 'SalesDrive request failed.', $response->get_error_message() );
		eko_plastics_send_telegram_api_alert( 'SalesDrive', 'Request failed: ' . $response->get_error_message() );
		return new WP_Error( 'salesdrive_request_failed', __( 'Could not send the lead to SalesDrive.', 'eko-plastics' ) );
	}

	$status_code = wp_remote_retrieve_response_code( $response );
	$body_raw    = wp_remote_retrieve_body( $response );
	$body        = json_decode( $body_raw, true );

	if ( $status_code < 200 || $status_code >= 300 ) {
		eko_plastics_crm_log( 'SalesDrive returned a bad HTTP status.', array( 'status' => $status_code, 'body' => $body_raw ) );
		eko_plastics_send_telegram_api_alert( 'SalesDrive', 'Bad HTTP status: ' . $status_code, $body_raw );
		return new WP_Error( 'salesdrive_bad_status', __( 'SalesDrive did not accept the lead.', 'eko-plastics' ) );
	}

	if ( is_array( $body ) && isset( $body['success'] ) && true !== (bool) $body['success'] ) {
		eko_plastics_crm_log( 'SalesDrive returned success=false.', $body );
		eko_plastics_send_telegram_api_alert( 'SalesDrive', 'API returned success=false.', $body );
		return new WP_Error( 'salesdrive_bad_response', __( 'SalesDrive did not accept the lead.', 'eko-plastics' ) );
	}

	eko_plastics_crm_log( 'SalesDrive accepted the lead.', array( 'status' => $status_code, 'body' => $body ) );

	return is_array( $body ) ? $body : array( 'raw' => $body_raw );
}

/**
 * Creates a Dilovod client after SalesDrive lead creation.
 *
 * @param array $payload           Validated form payload.
 * @param array $salesdrive_result SalesDrive response.
 * @return array|WP_Error
 */
function eko_plastics_send_dilovod_client( $payload, $salesdrive_result = array() ) {
	$options = eko_plastics_get_crm_integration_options();

	if ( '1' !== $options['dilovod_enabled'] ) {
		eko_plastics_send_telegram_api_alert( 'Dilovod', 'Dilovod integration is disabled while trying to create a client.', $payload );
		return array(
			'skipped' => true,
			'reason'  => 'disabled',
		);
	}

	if ( empty( $options['dilovod_endpoint'] ) || empty( $options['dilovod_key'] ) || empty( $options['dilovod_parent_id'] ) ) {
		eko_plastics_send_telegram_api_alert( 'Dilovod', 'Dilovod integration is not configured while trying to create a client.', $payload );
		return new WP_Error( 'dilovod_not_configured', __( 'Dilovod integration is not configured.', 'eko-plastics' ) );
	}

	$salesdrive_order_id = '';
	if ( isset( $salesdrive_result['data']['orderId'] ) ) {
		$salesdrive_order_id = (string) $salesdrive_result['data']['orderId'];
	}

	$header = array(
		'id'     => 'catalogs.persons',
		'name'   => array(
			'uk' => $payload['name'],
			'ru' => $payload['name'],
		),
		'parent' => $options['dilovod_parent_id'],
		'phone'  => $payload['phone'],
		'remark' => sprintf(
			'%s%s',
			__( 'Created from website form after SalesDrive lead.', 'eko-plastics' ),
			$salesdrive_order_id ? ' SalesDrive ID: ' . $salesdrive_order_id : ''
		),
	);

	if ( ! empty( $options['dilovod_person_type'] ) ) {
		$header['personType'] = $options['dilovod_person_type'];
	}

	$details = eko_plastics_prepare_dilovod_details( $options['dilovod_details_tpl'], $payload );

	if ( is_wp_error( $details ) ) {
		eko_plastics_send_telegram_api_alert( 'Dilovod', $details->get_error_message() );
		return $details;
	}

	if ( '' !== $details ) {
		$header['details'] = $details;
	}

	$packet = array(
		'version' => '0.25',
		'key'     => $options['dilovod_key'],
		'action'  => 'saveObject',
		'params'  => array(
			'header' => apply_filters( 'eko_plastics_dilovod_client_header', $header, $payload, $salesdrive_result, $options ),
		),
	);

	eko_plastics_crm_log(
		'Sending client to Dilovod.',
		array(
			'name'       => $payload['name'],
			'phone'      => $payload['phone'],
			'parent'     => $options['dilovod_parent_id'],
			'personType' => $options['dilovod_person_type'],
		)
	);

	$response = wp_remote_post(
		$options['dilovod_endpoint'],
		array(
			'timeout' => 20,
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
			),
			'body'    => array(
				'packet' => wp_json_encode( $packet ),
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		eko_plastics_crm_log( 'Dilovod request failed.', $response->get_error_message() );
		eko_plastics_send_telegram_api_alert( 'Dilovod', 'Request failed: ' . $response->get_error_message() );
		return new WP_Error( 'dilovod_request_failed', __( 'Could not create a Dilovod client.', 'eko-plastics' ) );
	}

	$status_code = wp_remote_retrieve_response_code( $response );
	$body_raw    = wp_remote_retrieve_body( $response );
	$body        = json_decode( $body_raw, true );

	eko_plastics_crm_log(
		'Dilovod response received.',
		array(
			'status' => $status_code,
			'body'   => $body,
			'raw'    => null === $body ? $body_raw : null,
		)
	);

	if ( $status_code < 200 || $status_code >= 300 || null === $body ) {
		eko_plastics_crm_log( 'Dilovod returned an unsuccessful response.', array( 'status' => $status_code, 'body' => $body_raw ) );
		eko_plastics_send_telegram_api_alert( 'Dilovod', 'Bad response from API. HTTP status: ' . $status_code, $body_raw );
		return new WP_Error( 'dilovod_bad_response', __( 'Dilovod did not accept the client.', 'eko-plastics' ) );
	}

	if ( is_array( $body ) && ( isset( $body['error'] ) || ( isset( $body['success'] ) && true !== (bool) $body['success'] ) ) ) {
		eko_plastics_crm_log( 'Dilovod returned an API error.', $body );
		eko_plastics_send_telegram_api_alert( 'Dilovod', 'API returned an error.', $body );
		return new WP_Error( 'dilovod_api_error', __( 'Dilovod returned an API error.', 'eko-plastics' ) );
	}

	return is_array( $body ) ? $body : array( 'result' => $body );
}

/**
 * Prepares Dilovod details JSON string from a user-provided template.
 *
 * @param string $template Details JSON template.
 * @param array  $payload  Validated payload.
 * @return string|WP_Error
 */
function eko_plastics_prepare_dilovod_details( $template, $payload ) {
	$template = trim( (string) $template );

	if ( '' === $template ) {
		$template = eko_plastics_get_default_dilovod_details_template();
	}

	$details = strtr(
		$template,
		array(
			'{phone}' => $payload['phone'],
			'{name}'  => $payload['name'],
		)
	);

	json_decode( $details, true );

	if ( JSON_ERROR_NONE !== json_last_error() ) {
		eko_plastics_crm_log( 'Dilovod details template is not valid JSON.', json_last_error_msg() );
		return new WP_Error( 'dilovod_details_invalid', __( 'Dilovod details template is not valid JSON.', 'eko-plastics' ) );
	}

	return $details;
}

/**
 * Registers SalesDrive webhook REST route.
 */
function eko_plastics_register_salesdrive_webhook_route() {
	register_rest_route(
		'eko-plastics/v1',
		'/salesdrive-webhook',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'eko_plastics_handle_salesdrive_webhook',
			'permission_callback' => 'eko_plastics_verify_salesdrive_webhook',
		)
	);
}
add_action( 'rest_api_init', 'eko_plastics_register_salesdrive_webhook_route' );

/**
 * Verifies SalesDrive webhook secret.
 *
 * @param WP_REST_Request $request Request.
 * @return bool
 */
function eko_plastics_verify_salesdrive_webhook( $request ) {
	$options = eko_plastics_get_crm_integration_options();

	if ( empty( $options['webhook_secret'] ) ) {
		eko_plastics_send_telegram_api_alert( 'SalesDrive', 'SalesDrive webhook rejected: webhook secret is not configured.' );
		eko_plastics_crm_log( 'SalesDrive webhook rejected: webhook secret is not configured.' );
		return false;
	}

	$secret = $request->get_param( 'secret' );

	if ( empty( $secret ) ) {
		$secret = $request->get_header( 'x-eko-plastics-secret' );
	}

	$is_valid = hash_equals( $options['webhook_secret'], (string) $secret );

	if ( ! $is_valid ) {
		eko_plastics_send_telegram_api_alert( 'SalesDrive', 'SalesDrive webhook rejected: invalid secret.' );
		eko_plastics_crm_log( 'SalesDrive webhook rejected: invalid secret.' );
	}

	return $is_valid;
}

/**
 * Handles SalesDrive webhook and sends contact data to Dilovod.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function eko_plastics_handle_salesdrive_webhook( $request ) {
	$body = $request->get_json_params();

	if ( empty( $body ) || ! is_array( $body ) ) {
		$body = $request->get_body_params();
	}

	eko_plastics_crm_log( 'SalesDrive webhook received.', $body );
	eko_plastics_mark_salesdrive_webhook_received( $body );

	$payload = eko_plastics_extract_salesdrive_webhook_payload( $body );

	if ( is_wp_error( $payload ) ) {
		eko_plastics_crm_log( 'SalesDrive webhook payload is invalid.', $body );
		eko_plastics_send_telegram_api_alert( 'SalesDrive', 'SalesDrive webhook payload is invalid.', $body );
		return $payload;
	}

	$result = eko_plastics_send_dilovod_client( $payload, $body );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return rest_ensure_response(
		array(
			'success' => true,
			'dilovod' => $result,
		)
	);
}

/**
 * Extracts name and phone from a SalesDrive webhook payload.
 *
 * @param array $body Webhook body.
 * @return array|WP_Error
 */
function eko_plastics_extract_salesdrive_webhook_payload( $body ) {
	$data     = isset( $body['data'] ) && is_array( $body['data'] ) ? $body['data'] : $body;
	$contacts = isset( $data['contacts'] ) && is_array( $data['contacts'] ) ? $data['contacts'] : array();
	$contact  = isset( $contacts[0] ) && is_array( $contacts[0] ) ? $contacts[0] : array();
	$name     = '';
	$phone    = '';

	if ( isset( $contact['fName'] ) ) {
		$name = trim( (string) $contact['fName'] );
	}

	if ( isset( $contact['lName'] ) && '' !== $contact['lName'] ) {
		$name = trim( $name . ' ' . (string) $contact['lName'] );
	}

	if ( isset( $contact['phone'] ) ) {
		$phone = is_array( $contact['phone'] ) ? reset( $contact['phone'] ) : $contact['phone'];
		$phone = (string) $phone;
	}

	if ( '' === $name && isset( $data['fName'] ) ) {
		$name = (string) $data['fName'];
	}

	if ( '' === $phone && isset( $data['phone'] ) ) {
		$phone = is_array( $data['phone'] ) ? reset( $data['phone'] ) : $data['phone'];
		$phone = (string) $phone;
	}

	$phone = eko_plastics_normalize_ukrainian_phone( $phone );

	if ( '' === $name || '' === $phone ) {
		return new WP_Error( 'salesdrive_webhook_missing_contact', __( 'SalesDrive webhook does not contain a valid contact name and phone.', 'eko-plastics' ) );
	}

	return array(
		'name'  => sanitize_text_field( $name ),
		'phone' => $phone,
		'page'  => isset( $data['utmPage'] ) ? esc_url_raw( $data['utmPage'] ) : '',
	);
}
