<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Create tags form
 */

global $gmProcessor;
$gmedia_url = $gmProcessor->url;
?>
<form method="post" id="gmedia-edit-term" name="gmAddTerms" class="card-body" action="<?php echo esc_url( add_query_arg( array( 'term' => 'gmedia_tag' ), $gmedia_url ) ); ?>" style="padding-bottom:0; border-bottom:1px solid #ddd;">
	<div class="row">
		<div class="form-group col-sm-9">
			<label><?php esc_html_e( 'Tags', 'grand-media' ); ?>
				<small class="text-muted">(<?php esc_html_e( 'you can type multiple tags separated by comma' ); ?>)</small>
			</label>
			<input type="text" class="form-control" name="term[name]" placeholder="<?php esc_attr_e( 'Tag Names', 'grand-media' ); ?>" required/>
		</div>
		<div class="form-group col-sm-3">
			<?php
			wp_original_referer_field( true, 'previous' );
			wp_nonce_field( 'gmedia_terms', '_wpnonce_terms' );
			?>
			<input type="hidden" name="term[taxonomy]" value="gmedia_tag"/>

			<label class="w-100">&nbsp;</label>
			<button type="submit" class="btn btn-primary" name="gmedia_tag_add"><?php esc_html_e( 'Add New Tags', 'grand-media' ); ?></button>
		</div>
	</div>
</form>

