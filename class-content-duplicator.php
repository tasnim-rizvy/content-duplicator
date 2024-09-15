<?php

/**
 * Duplicator by Rizvy
 *
 * @package           content-duplicator
 * @author            Tasnim Rizvy
 * @copyright         2024 Tasnim Rizvy
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Content Duplicator by Rizvy
 * Plugin URI:        https://example.com/plugin-name
 * Description:       Duplicate any post pr page with one click.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Tasnim Rizvy
 * Author URI:        https://tasnimrizvy.com
 * Text Domain:       content-duplicator
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PLUGIN_VERSION' ) ) {
	define( 'PLUGIN_VERSION', '4.5.3' );
}

if ( ! class_exists( 'Content_Duplicator' ) ) {
	class Content_Duplicator {
		public function __construct() {
			$opt = get_option( 'duplicator_options' );

			add_action( 'init', array( &$this, 'load_textdomain' ) );
			register_activation_hook( __FILE__, array( &$this, 'duplicator_install' ) );
			add_action( 'admin_menu', array( &$this, 'duplicator_options_page' ) );

			add_filter( 'post_row_actions', array( &$this, 'duplicator_link' ), 10, 2 );
			add_filter( 'page_row_actions', array( &$this, 'duplicator_link' ), 10, 2 );

			add_action( 'admin_action_duplicate', array( &$this, 'duplicate' ) );
		}

		public function load_textdomain() {
			load_plugin_textdomain( 'content-duplicator', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
		}

		public function duplicator_install() {
			update_option(
				'duplicator_options',
				array(
					'duplicate_post_status' => 'draft',
					'redirect_to'           => 'list',
				)
			);
		}

		public function duplicator_options_page() {
			add_options_page(
				__( 'Content Duplicator', 'content-duplicator' ),
				__( 'Content Duplicator', 'content-duplicator' ),
				'manage_options',
				'duplicator_settings',
				array( &$this, 'duplicator_settings' )
			);
		}

		public function duplicator_settings() {
			if ( current_user_can( 'manage_options' ) ) {
				include 'inc/admin-settings.php';
			}
		}

		public function duplicate() {
			$nonce        = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
			$current_user = get_current_user_id();

			if ( isset( $_GET['post'] ) ) {
				if ( wp_verify_nonce( $nonce, 'duplicate' . sanitize_text_field( wp_unslash( $_GET['post'] ) ) ) ) {
					if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' ) ) {
						$this->action_duplicate( sanitize_text_field( wp_unslash( $_GET['post'] ) ) );
					} elseif ( current_user_can( 'contributor' ) ) {
						$this->action_duplicate( sanitize_text_field( wp_unslash( $_GET['post'] ) ), 'pending' );
					} else {
						wp_die( esc_html__( 'Unauthorized Access', 'content-duplicator' ) );
					}
				} else {
					wp_die( esc_html__( 'Security issue, try again', 'content-duplicator' ) );
				}
			} else {
				wp_die( esc_html__( 'No content to duplicate has been supplied', 'content-duplicator' ) );
			}
		}

		public function action_duplicate( $post_id, $status = '' ) {
			global $wpdb;
			$opt  = get_option( 'duplicator_options' );
			$post = get_post( $post_id );

			if ( '' === $status ) {
				$post_status = $opt['duplicate_post_status'] ?? 'draft';
			} else {
				$post_status = $status;
			}

			if ( isset( $post ) && null !== $post ) {
				$args = array(
					'comment_status' => $post->comment_status,
					'ping_status'    => $post->ping_status,
					'post_author'    => wp_get_current_user()->ID,
					'post_content'   => $post->post_content,
					'post_excerpt'   => $post->post_excerpt,
					'post_parent'    => $post->post_parent,
					'post_password'  => $post->post_password,
					'post_status'    => $post_status,
					'post_title'     => $post->post_title . ' ' . __( 'Duplicated', 'content-duplicator' ),
					'post_type'      => $post->post_type,
					'to_ping'        => $post->to_ping,
					'menu_order'     => $post->menu_order,
				);

				$new_post = wp_insert_post( $args );
				if ( is_wp_error( $new_post ) ) {
					wp_die( esc_html( $new_post->get_error_message() ) );
				}

				$taxonomies = get_object_taxonomies( $post->post_type );
				if ( ! empty( $taxonomies ) ) {
					foreach ( $taxonomies as $taxonomy ) {
						$post_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
						wp_set_object_terms( $new_post, $post_terms, $taxonomy, false );
					}
				}

				$post_meta_keys = get_post_custom_keys( $post_id );
				if ( ! empty( $post_meta_keys ) ) {
					foreach ( $post_meta_keys as $meta_key ) {
						$meta_values = get_post_custom_values( $meta_key, $post_id );
						foreach ( $meta_values as $meta ) {
							$meta = maybe_unserialize( $meta );
							update_post_meta( $post_id, $meta_key, wp_slash( $meta ) );
						}
					}
				}

				if ( 'list' === $opt['redirect_to'] ) {
					$redirect_to_post  = 'edit.php';
					$redirect_to_other = 'edit.php?post_type=' . $post->post_type;
					wp_safe_redirect(
						esc_url_raw(
							admin_url(
								'post' === $post->post_type ? $redirect_to_post : $redirect_to_other
							)
						)
					);
				} else {
					wp_safe_redirect( esc_url_raw( admin_url( 'post.php?action=edit&post=' . $new_post ) ) );
				}

				exit;
			}
		}

		public function duplicator_link( $actions, $post ) {
			if ( current_user_can( 'edit_posts' ) ) {
				$actions['duplicate'] = isset( $post ) ? '<a href="admin.php?action=duplicate&amp;post=' . $post->ID . '&amp;nonce=' . wp_create_nonce( 'duplicate' . $post->ID ) . '" rel="permalink">' . __( 'Duplicate', 'content-duplicator' ) . '</a>' : '';
			}

			return $actions;
		}

		public function custom_assets() {
			wp_enqueue_style( 'duplicator', plugins_url( '/css/duplicator-style.css', __FILE__ ), null, '1.0.0' );
			wp_enqueue_script( 'duplicator', plugins_url( '/js/duplicator-script.js', __FILE__ ), 'jquery', '1.0.0', true );
			wp_localize_script(
				'duplicator',
				'dt_params',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'nc_help_desk' ),
				)
			);
		}
	}

	new Content_Duplicator();
}
