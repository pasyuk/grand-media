<?php

function gmedia_template_xpath( $path ) {
	$source = file_get_contents( $path );
	if ( false === $source ) {
		throw new RuntimeException( 'Could not read template: ' . $path );
	}

	$html = '';
	foreach ( token_get_all( $source ) as $token ) {
		if ( is_array( $token ) && T_INLINE_HTML === $token[0] ) {
			$html .= $token[1];
		}
	}

	$document = new DOMDocument();
	libxml_use_internal_errors( true );
	$document->loadHTML( $html );
	libxml_clear_errors();

	return new DOMXPath( $document );
}
