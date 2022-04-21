<?php if ( ! defined( 'GMEDIA_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 * @param $options_tree
 */
function gmedia_gallery_options_nav( $options_tree ) {
	$i = 0;
	foreach ( $options_tree as $section ) {
		$i ++;
		$active_class = ( 1 === $i ) ? ' class="active"' : '';
		echo '<li' . $active_class . '><a href="#gallery_settings' . $i . '" data-toggle="tab">' . $section['label'] . '</a></li..>';
	}
}

/**
 * @param       $options_tree
 * @param       $default
 * @param array $value
 */
function gmedia_gallery_options_fieldset( $options_tree, $default, $value = [] ) {
	$i = 0;
	foreach ( $options_tree as $section ) {
		$i ++;
		$pane_class = ( 1 === $i ) ? 'tab-pane active' : 'tab-pane';
		?>
		<fieldset id="gallery_settings<?php echo $i; ?>" class="<?php echo esc_attr( $pane_class ); ?>">
			<?php
			foreach ( $section['fields'] as $name => $field ) {
				if ( 'textblock' === $field['tag'] ) {
					$args = [
						'id'    => $name,
						'field' => $field,
					];
				} else {
					if ( isset( $section['key'] ) ) {
						$key = $section['key'];
						if ( ! isset( $default[ $key ][ $name ] ) ) {
							$default[ $key ][ $name ] = false;
						}
						$val  = isset( $value[ $key ][ $name ] ) ? $value[ $key ][ $name ] : $default[ $key ][ $name ];
						$args = [
							'id'      => strtolower( "{$key}_{$name}" ),
							'name'    => "module[{$key}][{$name}]",
							'field'   => $field,
							'value'   => $val,
							'default' => $default[ $key ][ $name ],
						];
					} else {
						if ( ! isset( $default[ $name ] ) ) {
							$default[ $name ] = false;
						}
						$val  = isset( $value[ $name ] ) ? $value[ $name ] : $default[ $name ];
						$args = [
							'id'      => strtolower( $name ),
							'name'    => "module[{$name}]",
							'field'   => $field,
							'value'   => $val,
							'default' => $default[ $name ],
						];
					}
				}
				gmedia_gallery_options_formgroup( $args );
			}
			?>
		</fieldset>
		<?php
	}
}

/**
 * @param $args
 */
function gmedia_gallery_options_formgroup( $args ) {
	/**
	 * @var $id
	 * @var $name
	 * @var $field
	 * @var $value
	 * @var $default
	 */
	extract( $args );
	if ( 'input' === $field['tag'] ) {
		?>
		<div class="form-group" id="div_<?php echo absint( $id ); ?>">
			<label><?php echo $field['label']; ?></label>
			<input <?php echo $field['attr']; ?> id="<?php echo absint( $id ); ?>" class="form-control input-sm" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" data-value="<?php echo esc_attr( $default ); ?>" placeholder="<?php echo esc_attr( $default ); ?>"/>
			<?php if ( ! empty( $field['text'] ) ) {
				echo "<p class='help-block'>{$field['text']}</p>";
			} ?>
		</div>
	<?php } elseif ( 'checkbox' === $field['tag'] ) { ?>
		<div class="form-group" id="div_<?php echo absint( $id ); ?>">
			<div class="checkbox">
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="0"/>
				<label><input type="checkbox" <?php echo $field['attr']; ?> id="<?php echo absint( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" data-value="<?php echo esc_attr( $default ); ?>" <?php echo checked( $value, '1' ); ?>/> <?php echo $field['label']; ?>
				</label>
				<?php if ( ! empty( $field['text'] ) ) {
					echo "<p class='help-block'>{$field['text']}</p>";
				} ?>
			</div>
		</div>
	<?php } elseif ( 'select' === $field['tag'] ) { ?>
		<div class="form-group" id="div_<?php echo absint( $id ); ?>">
			<label><?php echo $field['label']; ?></label>
			<select <?php echo $field['attr']; ?> id="<?php echo absint( $id ); ?>" class="form-control input-sm" name="<?php echo esc_attr( $name ); ?>" data-value="<?php echo esc_attr( $default ); ?>">
				<?php foreach ( $field['choices'] as $choice ) { ?>
					<option value="<?php echo esc_attr( $choice['value'] ); ?>" <?php echo selected( $value, $choice['value'] ); ?>><?php echo $choice['label']; ?></option>
				<?php } ?>
			</select>
			<?php if ( ! empty( $field['text'] ) ) {
				echo "<p class='help-block'>{$field['text']}</p>";
			} ?>
		</div>
	<?php } elseif ( 'textarea' === $field['tag'] ) { ?>
		<div class="form-group" id="div_<?php echo absint( $id ); ?>">
			<label><?php echo $field['label']; ?></label>
			<textarea <?php echo $field['attr']; ?> id="<?php echo absint( $id ); ?>" class="form-control input-sm" name="<?php echo esc_attr( $name ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
			<?php if ( ! empty( $field['text'] ) ) {
				echo "<p class='help-block'>{$field['text']}</p>";
			} ?>
		</div>
	<?php } elseif ( 'textblock' === $field['tag'] ) { ?>
		<div class="text-block">
			<?php echo $field['label']; ?>
			<?php echo $field['text']; ?>
		</div>
	<?php } ?>
	<?php
}


/**
 * Recognized font styles.
 * Returns an array of all recognized font styles.
 *
 * @return    array
 * @uses      apply_filters()
 *
 */
if ( ! function_exists( 'gm_recognized_font_styles' ) ) {

	/**
	 * @param string $field_id
	 *
	 * @return array
	 */
	function gm_recognized_font_styles( $field_id = '' ) {

		return apply_filters( 'gm_recognized_font_styles', [
			'normal'  => 'Normal',
			'italic'  => 'Italic',
			'oblique' => 'Oblique',
			'inherit' => 'Inherit',
		], $field_id );

	}

}

/**
 * Recognized font weights.
 * Returns an array of all recognized font weights.
 *
 * @return    array
 * @uses      apply_filters()
 *
 */
if ( ! function_exists( 'gm_recognized_font_weights' ) ) {

	/**
	 * @param string $field_id
	 *
	 * @return array
	 */
	function gm_recognized_font_weights( $field_id = '' ) {

		return apply_filters( 'gm_recognized_font_weights', [
			'normal'  => 'Normal',
			'bold'    => 'Bold',
			'bolder'  => 'Bolder',
			'lighter' => 'Lighter',
			'100'     => '100',
			'200'     => '200',
			'300'     => '300',
			'400'     => '400',
			'500'     => '500',
			'600'     => '600',
			'700'     => '700',
			'800'     => '800',
			'900'     => '900',
			'inherit' => 'Inherit',
		], $field_id );

	}

}

/**
 * Recognized font variants.
 * Returns an array of all recognized font variants.
 *
 * @return    array
 * @uses      apply_filters()
 *
 */
if ( ! function_exists( 'gm_recognized_font_variants' ) ) {

	/**
	 * @param string $field_id
	 *
	 * @return array
	 */
	function gm_recognized_font_variants( $field_id = '' ) {

		return apply_filters( 'gm_recognized_font_variants', [
			'normal'     => 'Normal',
			'small-caps' => 'Small Caps',
			'inherit'    => 'Inherit',
		], $field_id );

	}

}

/**
 * Recognized font families.
 * Returns an array of all recognized font families.
 * Keys are intended to be stored in the database
 * while values are ready for display in html.
 *
 * @return    array
 * @uses      apply_filters()
 *
 */
if ( ! function_exists( 'gm_recognized_font_families' ) ) {

	/**
	 * @param string $field_id
	 *
	 * @return array
	 */
	function gm_recognized_font_families( $field_id = '' ) {

		return apply_filters( 'gm_recognized_font_families', [
			'arial'     => 'Arial',
			'georgia'   => 'Georgia',
			'helvetica' => 'Helvetica',
			'palatino'  => 'Palatino',
			'tahoma'    => 'Tahoma',
			'times'     => '"Times New Roman", sans-serif',
			'trebuchet' => 'Trebuchet',
			'verdana'   => 'Verdana',
		], $field_id );

	}

}


