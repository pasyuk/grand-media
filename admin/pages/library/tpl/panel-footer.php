<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * @var $gmedia_pager
 * @var $gm_allowed_tags
 */?>
<div class="card-footer clearfix">
	<?php echo wp_kses( $gmedia_pager, $gm_allowed_tags ); ?>

	<a href="#top" class="btn btn-secondary btn-sm"><i class='fa-solid fa-arrow-up'></i> <?php esc_html_e( 'Back to top', 'grand-media' ); ?></a>
</div>
