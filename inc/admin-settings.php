<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$this->custom_assets();
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Duplicate Content', 'content-duplicator' ); ?></h1>

	<?php
	if ( current_user_can( 'manage_options' ) && isset( $_POST['submit_duplicator_settings'] ) && isset( $_POST['duplicator_nonce_field'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['duplicator_nonce_field'] ) ), 'duplicator_action' ) ) {
		$updated_options = array(
			'duplicate_post_status' => isset( $_POST['duplicate_post_status'] ) ? sanitize_text_field( wp_unslash( $_POST['duplicate_post_status'] ) ) : '',
			'redirect_to'           => isset( $_POST['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_POST['redirect_to'] ) ) : '',
		);

		$save_settings = update_option( 'duplicator_options', $updated_options );

		if ( $save_settings ) :
			?>
		<div class="updated settings-error notice is-dismissible" id="setting-error-settings_updated"> 
			<p><strong>Settings saved.</strong></p>
			<button class="notice-dismiss button-custom-dismiss" type="button">
				<span class="screen-reader-text">Dismiss this notice</span>
			</button>
		</div>
			<?php
		endif;
	}
	$opt = get_option( 'duplicator_options' );
	?>

	<div>
		<div class="metabox-holder columns-2">
			<div>
				<form action="" method="post" name="duplicator_form">
					<?php wp_nonce_field( 'duplicator_action', 'duplicator_nonce_field' ); ?>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="duplicate_post_status"><?php esc_html_e( 'Duplicated Post Status', 'content-duplicator' ); ?></label>
								</th>
								<td>
									<?php
									$draft   = ( 'draft' === $opt['duplicate_post_status'] ) ? "selected = 'selected'" : '';
									$publish = ( 'publish' === $opt['duplicate_post_status'] ) ? "selected = 'selected'" : '';
									$private = ( 'private' === $opt['duplicate_post_status'] ) ? "selected = 'selected'" : '';
									$pending = ( 'pending' === $opt['duplicate_post_status'] ) ? "selected = 'selected'" : '';
									?>
									<select name="duplicate_post_status" id="duplicate_post_status">
										<option value="draft" <?php echo esc_attr( $draft ); ?>><?php esc_html_e( 'Draft', 'content-duplicator' ); ?></option>
										<option value="publish" <?php echo esc_attr( $publish ); ?>><?php esc_html_e( 'Published', 'content-duplicator' ); ?></option>
										<option value="private" <?php echo esc_attr( $private ); ?>><?php esc_html_e( 'Private', 'content-duplicator' ); ?></option>
										<option value="pending" <?php echo esc_attr( $pending ); ?>><?php esc_html_e( 'Pending', 'content-duplicator' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label><?php esc_html_e( 'Redirect after duplication', 'content-duplicator' ); ?></label>
								</th>
								<td>
									<?php
									$list   = ( 'list' === $opt['redirect_to'] ) ? "checked='checked'" : '';
									$editor = ( 'editor' === $opt['redirect_to'] ) ? "checked='checked'" : '';
									?>
									<p><label>
										<input type="radio" name="redirect_to" value="list" class="tog" <?php echo esc_attr( $list ); ?>>
										<?php esc_html_e( 'List', 'content-duplicator' ); ?>
									</label></p>
									<p><label>
										<input type="radio" name="redirect_to" value="editor" class="tog" <?php echo esc_attr( $editor ); ?>>
										<?php esc_html_e( 'Editor', 'content-duplicator' ); ?>
									</label></p>
								</td>
							</tr>
						</tbody>
					</table>
					<p class="submit">
						<input type="submit" value="<?php esc_attr_e( 'Save Changes', 'content-duplicator' ); ?>" class="button button-primary" name="submit_duplicator_settings">
					</p>
				</form>
			</div>
		</div>
	</div>
</div>
