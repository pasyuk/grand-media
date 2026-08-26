<?php
// No database is connected in the setup-query test. Unexpected DDL must fail.
function dbDelta( $sql ) {
	throw new RuntimeException( 'Setup unexpectedly attempted a schema change.' );
}
