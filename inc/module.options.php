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
		echo '<li><a href="#gallery_settings' . $i . '" data-toggle="tab">' . $section['label'] . '</a></li>';
	}
}

/**
 * @param       $options_tree
 * @param       $default
 * @param array $value
 */
function gmedia_gallery_options_fieldset( $options_tree, $default, $value = array() ) {
	$i = 0;
	foreach ( $options_tree as $section ) {
		$i ++;
		?>
		<fieldset id="gallery_settings<?php echo $i; ?>" class="tab-pane">
			<?php
			foreach ( $section['fields'] as $name => $field ) {
				if ( 'textblock' == $field['tag'] ) {
					$args = array(
						'id'    => $name,
						'field' => $field
					);
				} else {
					if ( isset( $section['key'] ) ) {
						$key = $section['key'];
						if ( ! isset( $default[ $key ][ $name ] ) ) {
							$default[ $key ][ $name ] = false;
						}
						$val  = isset( $value[ $key ][ $name ] ) ? $value[ $key ][ $name ] : $default[ $key ][ $name ];
						$args = array(
							'id'      => strtolower( "{$key}_{$name}" ),
							'name'    => "module[{$key}][{$name}]",
							'field'   => $field,
							'value'   => $val,
							'default' => $default[ $key ][ $name ]
						);
					} else {
						if ( ! isset( $default[ $name ] ) ) {
							$default[ $name ] = false;
						}
						$val  = isset( $value[ $name ] ) ? $value[ $name ] : $default[ $name ];
						$args = array(
							'id'      => strtolower( $name ),
							'name'    => "module[{$name}]",
							'field'   => $field,
							'value'   => $val,
							'default' => $default[ $name ]
						);
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
	if ( 'input' == $field['tag'] ) {
		?>
		<div class="form-group" id="div_<?php echo $id; ?>">
			<label><?php echo $field['label']; ?></label>
			<input <?php echo $field['attr']; ?> id="<?php echo $id; ?>" class="form-control input-sm" name="<?php echo $name; ?>" value="<?php echo esc_attr( $value ); ?>" data-value="<?php echo $default; ?>" placeholder="<?php echo $default; ?>"/>
			<?php if ( ! empty( $field['text'] ) ) {
				echo "<p class='help-block'>{$field['text']}</p>";
			} ?>
		</div>
	<?php } elseif ( 'checkbox' == $field['tag'] ) { ?>
		<div class="form-group" id="div_<?php echo $id; ?>">
			<div class="checkbox">
				<input type="hidden" name="<?php echo $name; ?>" value="0"/>
				<label><input type="checkbox" <?php echo $field['attr']; ?> id="<?php echo $id; ?>" name="<?php echo $name; ?>" value="1" data-value="<?php echo $default; ?>" <?php echo checked( $value, '1' ); ?>/> <?php echo $field['label']; ?>
				</label>
				<?php if ( ! empty( $field['text'] ) ) {
					echo "<p class='help-block'>{$field['text']}</p>";
				} ?>
			</div>
		</div>
	<?php } elseif ( 'select' == $field['tag'] ) { ?>
		<div class="form-group" id="div_<?php echo $id; ?>">
			<label><?php echo $field['label']; ?></label>
			<select <?php echo $field['attr']; ?> id="<?php echo $id; ?>" class="form-control input-sm" name="<?php echo $name; ?>" data-value="<?php echo $default; ?>">
				<?php foreach ( $field['choices'] as $choice ) { ?>
					<option value="<?php echo esc_attr( $choice['value'] ); ?>" <?php echo selected( $value, $choice['value'] ); ?>><?php echo $choice['label']; ?></option>
				<?php } ?>
			</select>
			<?php if ( ! empty( $field['text'] ) ) {
				echo "<p class='help-block'>{$field['text']}</p>";
			} ?>
		</div>
	<?php } elseif ( 'textarea' == $field['tag'] ) { ?>
		<div class="form-group" id="div_<?php echo $id; ?>">
			<label><?php echo $field['label']; ?></label>
			<textarea <?php echo $field['attr']; ?> id="<?php echo $id; ?>" class="form-control input-sm" name="<?php echo $name; ?>"><?php echo esc_html( $value ); ?></textarea>
			<?php if ( ! empty( $field['text'] ) ) {
				echo "<p class='help-block'>{$field['text']}</p>";
			} ?>
		</div>
	<?php } elseif ( 'textblock' == $field['tag'] ) { ?>
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
 * @uses      apply_filters()
 *
 * @return    array
 */
if ( ! function_exists( 'gm_recognized_font_styles' ) ) {

	/**
	 * @param string $field_id
	 *
	 * @return mixed|void
	 */
	function gm_recognized_font_styles( $field_id = '' ) {

		return apply_filters( 'gm_recognized_font_styles', array(
			'normal'  => 'Normal',
			'italic'  => 'Italic',
			'oblique' => 'Oblique',
			'inherit' => 'Inherit'
		), $field_id );

	}

}

/**
 * Recognized font weights.
 * Returns an array of all recognized font weights.
 *
 * @uses      apply_filters()
 *
 * @return    array
 */
if ( ! function_exists( 'gm_recognized_font_weights' ) ) {

	/**
	 * @param string $field_id
	 *
	 * @return mixed|void
	 */
	function gm_recognized_font_weights( $field_id = '' ) {

		return apply_filters( 'gm_recognized_font_weights', array(
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
			'inherit' => 'Inherit'
		), $field_id );

	}

}

/**
 * Recognized font variants.
 * Returns an array of all recognized font variants.
 *
 * @uses      apply_filters()
 *
 * @return    array
 */
if ( ! function_exists( 'gm_recognized_font_variants' ) ) {

	/**
	 * @param string $field_id
	 *
	 * @return mixed|void
	 */
	function gm_recognized_font_variants( $field_id = '' ) {

		return apply_filters( 'gm_recognized_font_variants', array(
			'normal'     => 'Normal',
			'small-caps' => 'Small Caps',
			'inherit'    => 'Inherit'
		), $field_id );

	}

}

/**
 * Recognized font families.
 * Returns an array of all recognized font families.
 * Keys are intended to be stored in the database
 * while values are ready for display in html.
 *
 * @uses      apply_filters()
 *
 * @return    array
 */
if ( ! function_exists( 'gm_recognized_font_families' ) ) {

	/**
	 * @param string $field_id
	 *
	 * @return mixed|void
	 */
	function gm_recognized_font_families( $field_id = '' ) {

		return apply_filters( 'gm_recognized_font_families', array(
			'arial'     => 'Arial',
			'georgia'   => 'Georgia',
			'helvetica' => 'Helvetica',
			'palatino'  => 'Palatino',
			'tahoma'    => 'Tahoma',
			'times'     => '"Times New Roman", sans-serif',
			'trebuchet' => 'Trebuchet',
			'verdana'   => 'Verdana'
		), $field_id );

	}

}


