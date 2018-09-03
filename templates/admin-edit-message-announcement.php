<?php
/**
 * Message and Announcement edit from back-end
 *
 * @package Front End PM
 * @since 10.1.2
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline">
	<?php
	echo esc_html( sprintf( __( 'Edit %s', 'front-end-pm' ), ucwords( $message->mgs_type ) ) );
	?>
	</h1>
	<hr class="wp-header-end">
	<form id="fep_mgs_edit" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
		<input type="hidden" name="action" value="fep-edit" />
		<input type="hidden" name="fep_id" value="<?php echo esc_attr( $message->mgs_id ); ?>" />
		<?php wp_nonce_field( "fep-edit-{$message->mgs_id}" ); ?>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div id="titlediv">
					<div id="titlewrap">
						<label class="screen-reader-text" id="title-prompt-text" for="title"><?php esc_html_e( 'Enter title here', 'front-end-pm' ); ?></label>
						<input name="mgs_title" value="<?php echo esc_attr( $message->mgs_title ); ?>" size="30" id="title" spellcheck="true" autocomplete="off" type="text">
					</div><!-- #titlewrap -->
				</div>
				<div id="postdivrich" class="postarea wp-editor-expand">
					<div id="wp-content-wrap" class="wp-core-ui wp-editor-wrap html-active has-dfw" style="padding-top: 55px;">
						<?php
						wp_editor(
							wp_kses_post( $message->mgs_content ), 'mgs_content', array(
								'textarea_name' => 'mgs_content',
								'editor_class'  => 'mgs_content',
								'media_buttons' => false,
							)
						);
?>
					</div>
				</div>
			</div><!-- #post-body-content -->
			<div id="postbox-container-1" class="postbox-container">
				<div id="submitdiv" class="postbox">
					<h3 class="hndle" style="text-align:center;">
						<span><?php esc_html_e( 'Publish', 'front-end-pm' ); ?></span>
					</h3>
					<div class="inside">
						<div class="submitbox" id="submitpost">
							<div id="minor-publishing-actions">
							</div><!-- #minor-publishing-actions -->

							<div id="misc-publishing-actions">
								<div id="post-status-select" class="misc-pub-section">
									<label for="mgs_status"><?php esc_html_e( 'Status', 'front-end-pm' ); ?>: </label>
									<select id="mgs_status" name="mgs_status">
										<?php foreach ( fep_get_statuses( $message->mgs_type ) as $key => $value ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $message->mgs_status ); ?>><?php echo esc_html( $value ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div><!-- #misc-publishing-actions -->

							<div id="major-publishing-actions">

								<div id="delete-action">
									<a class="submitdelete deletion" href="
									<?php
									echo wp_nonce_url(
										add_query_arg(
											array(
												'page'   => ( 'announcement' === $message->mgs_type ) ? 'fep-all-announcements' : 'fep-all-messages',
												'action' => 'delete',
												'fep_id' => $message->mgs_id,
											),
											admin_url( 'admin.php' )
										),
										"delete-fep-message-{$message->mgs_id}"
									);
										?>
										" onclick="javascript:if( ! confirm('<?php esc_attr_e( 'Are you sure you want to delete this?', 'front-end-pm' ); ?>' ) ) return false;"><?php esc_html_e( 'Delete', 'front-end-pm' ); ?></a>
								</div><!-- #delete-action -->

								<div id="publishing-action">
									<span class="spinner"></span>
									<input id="fep-save" class="button-primary" name="fep-save" value="<?php esc_attr_e( 'Save', 'front-end-pm' ); ?>" type="submit">
								</div>
								<div class="clear"></div>
							</div><!-- #major-publishing-actions -->
						</div><!-- #submitpost -->
					</div>
				</div>
			</div><!-- #postbox-container-1 -->
			<div id="postbox-container-2" class="postbox-container">
				<div id="attachmentdiv" class="postbox">
					<h3 class="hndle" style="text-align:center;">
						<span><?php esc_html_e( 'Attachments', 'front-end-pm' ); ?></span>
					</h3>
					<div class="inside">
						<table class="fep_edit_attachments_table widefat fixed striped">
							<tbody>
							<?php
							$attachments = $message->get_attachments( false, 'any' );
							foreach ( $attachments as $attachment ) :
								if ( 0 === stripos( $attachment->att_mime, 'image/' ) ) {
									$src = fep_query_url(
										'view-download', [
											'fep_id' => $attachment->att_id,
											'fep_parent_id' => $attachment->mgs_id,
										]
									);
								} else {
									$src = wp_mime_type_icon( $attachment->att_mime );
								}
								?>
									<tr>
										<td>
											<img src="<?php echo esc_attr( $src ); ?>" width="200px" height="150px" />
										</td>
										<td>
											<?php echo esc_html( basename( $attachment->att_file ) ); ?>
										</td>
										<td>
											<a href="#" class="fep_att_delete_ajax" data-fep_id="<?php echo esc_attr( $attachment->att_id ); ?>" data-fep_parent_id="<?php echo esc_attr( $attachment->mgs_id ); ?>" data-nonce="<?php echo wp_create_nonce( "delete-att-{$attachment->att_id}" ); ?>" ><?php esc_html_e( 'Delete', 'front-end-pm' ); ?></a>
											<span class="spinner"></span>
										</td>
									</tr>
								<?php
							endforeach;
							?>
							</tbody>
						</table>
						<div><button class="button fep-att-upload" data-uploader_title="<?php esc_attr_e( 'Attachments', 'front-end-pm' ); ?>" data-uploader_button_text="<?php esc_attr_e( 'Use this', 'front-end-pm' ); ?>"><?php esc_attr_e( 'Add More', 'front-end-pm' ); ?></button></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	</form>
</div>
