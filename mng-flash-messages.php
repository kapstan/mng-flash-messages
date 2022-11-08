<?php
declare( strict_types = 1 );
namespace MNG\MUPlugins\MNGFlashMessages;

/**
 * MNG Feature Flags
 *
 * @package     MNG
 * @author      Ian Kaplan
 * @copyright   2022 MNG
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       MNG Flash Messages
 * Plugin URI:        https://github.com/wpcomvip/digital-first-media
 * Description:       Provides convenient access to queue flash messages.
 * Version:           0.0.1
 * Author:            Ian Kaplan
 * Author URI:        https://github.com/kapstan
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 6.0
 * Requires PHP:      8.0.2
 */

// Bail if accessed directly
defined( 'ABSPATH' ) || exit;

class MNGFlashMessages {

	protected function __construct() {
		$this->bootstrap();
	}

	protected function bootstrap(): void {

		add_filter( 'safe_style_css', function( array $styles ): array {
			$styles[] = 'position';
			$styles[] = 'bottom';
			$styles[] = 'right';

			return $styles;
		} );

		if ( is_admin() ) :
			add_action( 'admin_notices', [ static::class, 'display_flash_messages_as_notice' ] );
		endif;

		add_action( 'template_redirect', [ static::class, 'display_flash_messages' ] );
	}

	public static function get_instance(): static {
		static $instance;

		if ( ! $instance ) :
			$instance = new MNGFlashMessages();
		endif;

		return $instance;
	}

	public static function queue_message( string $message, string $css_class = '' ): void {
		$default_classes = [
			'error',
			'updated',
		];

		$allowed_classes = apply_filters( 'flash_messages_allowed_classes', $default_classes );
		$default_class = apply_filters( 'flash_messages_default_class', 'updated' );

		if ( ! in_array( $css_class, $allowed_classes, true ) ) :
			$css_class = $default_class;
		endif;

		$flash_messages = maybe_unserialize( get_option( 'wp_flash_messages', [] ) );
		$flash_messages[ $css_class ][] = $message;

		update_option( 'wp_flash_messages', $flash_messages );
	}

	public static function display_flash_messages(): void {
		if ( is_user_logged_in() ) :
			$user = wp_get_current_user();
			$roles = array_values( $user->roles );

			if ( in_array( 'administrator', $roles, true ) ) :

				$flash_messages = maybe_unserialize( get_option( 'wp_flash_messages', '' ) );
				$allowed_markup = [
					'div' => [
						'class' => [],
						'style' => [],
					],
					'p' => [
						'style' => [],
					],
				];

				if ( is_array( $flash_messages ) ) :
					foreach( $flash_messages as $class => $messages ) :
						foreach( $messages as $message ) :

							add_action( 'get_footer', function() use ( $class, $message, $allowed_markup ) {
								$message_markup = <<<ENDMARKUP

									<div class="feature-flag flash-message flash-message-{$class}" style="position:fixed;bottom:2em;right:2em;background-color:#27ae6085;padding:0.5em;">
										<p style="color:#fff;font-family:'Segoe UI', sans-serif;font-size:1em;">{$message}</p>
									</div>

								ENDMARKUP;

								echo wp_kses( $message_markup, $allowed_markup );
							} );

						endforeach;
					endforeach;
				endif;
			endif;
		endif;
	}

	public static function display_flash_messages_as_notice(): void {
		$flash_messages = maybe_unserialize( get_option( 'wp_flash_messages', '' ) );

		$allowed_markup = [
			'div' => [
				'class' => [],
			],
			'p' => [],
		];

		if ( is_array( $flash_messages ) ) :
			foreach( $flash_messages as $class => $messages ) :
				foreach( $messages as $message ) :

					$markup = sprintf(
						'<div class="%s is-dimissible"><p>%s</p></div>',
						$class,
						$message
					);

					echo wp_kses( $markup, $allowed_markup );

				endforeach;
			endforeach;
		endif;

		delete_option( 'wp_flash_messages' );
	}
}
