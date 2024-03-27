<?php
/**
 * Adds Gmedia Widgets.
 */

class GrandMedia_Gallery_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
			'gmedia_gallery_widget', // Base ID.
			esc_html__( 'Gmedia Gallery', 'grand-media' ), // Name.
			array( 'description' => esc_html__( 'Display Gmedia Gallery in the widget', 'grand-media' ) ) // Args.
		);
	}

	/**
	 * Back-end widget form.
	 *
	 * @param array $instance Previously saved values from database.
	 *
	 * @return void
	 * @see WP_Widget::form()
	 */
	public function form( $instance ) {
		global $gmDB;
		$title        = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'New title', 'grand-media' );
		$gmedia_terms = $gmDB->get_terms( 'gmedia_gallery', array( 'status' => array( 'publish', 'private' ), 'orderby' => 'name', 'order' => 'ASC' ) );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'grand-media' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Choose Gallery', 'grand-media' ); ?>:</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'term_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'term_id' ) ); ?>">
				<option value=""><?php esc_html_e( 'Random Gallery with status "publish"', 'grand-media' ); ?></option>
				<?php
				foreach ( $gmedia_terms as &$item ) {
					gmedia_gallery_more_data( $item );
					echo '<option value="' . esc_attr( $item->term_id ) . '" ' . selected( $instance['term_id'], $item->term_id ) . '>';
					// translators: author name.
					echo esc_html( $item->name . " [{$item->status}] " . ( $item->author_name ? sprintf( __( 'by %s', 'grand-media' ), $item->author_name ) : '(' . __( 'deleted author', 'grand-media' ) . ')' ) );
					echo '</option>';
				}
				?>
			</select>
		</p>
		<?php
	}

	/**
	 * Front-end display of widget.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 *
	 * @see WP_Widget::widget()
	 */
	public function widget( $args, $instance ) {
		global $gmDB, $gm_allowed_tags;

		if ( empty( $instance['term_id'] ) ) {
			$term_id = $gmDB->get_terms( 'gmedia_gallery', array( 'status' => array( 'publish' ), 'fields' => 'ids', 'number' => 1, 'orderby' => 'rand' ) );
			if ( empty( $term_id ) ) {
				return;
			}
			$instance['term_id'] = $term_id[0];
		}

		$output = $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			$output .= $args['before_title'] . esc_html( apply_filters( 'widget_title', $instance['title'] ) ) . $args['after_title'];
		}

		$atts = array( 'id' => (int) $instance['term_id'] );

		$output .= gmedia_shortcode( $atts );
		$output .= $args['after_widget'];

		echo wp_kses( $output, $gm_allowed_tags );
	}


	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 * @see WP_Widget::update()
	 */
	public function update( $new_instance, $old_instance ) {
		$instance            = array();
		$instance['title']   = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['term_id'] = ( ! empty( $new_instance['term_id'] ) ) ? (int) $new_instance['term_id'] : '';

		return $instance;
	}

} // class GrandMedia_Gallery_Widget

class GrandMedia_Album_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
			'gmedia_album_widget', // Base ID.
			esc_html__( 'Gmedia Album', 'grand-media' ), // Name.
			array( 'description' => esc_html__( 'Display Gmedia Album in the widget', 'grand-media' ) ) // Args.
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 *
	 * @see WP_Widget::widget()
	 */
	public function widget( $args, $instance ) {
		global $gmDB, $gm_allowed_tags;

		if ( empty( $instance['term_id'] ) ) {
			$term_id = $gmDB->get_terms( 'gmedia_album', array( 'status' => array( 'publish' ), 'fields' => 'ids', 'number' => 1, 'orderby' => 'rand', 'hide_empty' => true ) );
			if ( empty( $term_id ) ) {
				return;
			}
			$instance['term_id'] = $term_id[0];
		}

		$output = $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			$output .= $args['before_title'] . esc_html( apply_filters( 'widget_title', $instance['title'] ) ) . $args['after_title'];
		}

		$atts = array( 'id' => (int) $instance['term_id'] );

		$output .= gmedia_shortcode( $atts );
		$output .= $args['after_widget'];

		echo wp_kses( $output, $gm_allowed_tags );
	}

	/**
	 * Back-end widget form.
	 *
	 * @param array $instance Previously saved values from database.
	 *
	 * @return void
	 * @see WP_Widget::form()
	 */
	public function form( $instance ) {
		global $gmDB;
		$title        = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'New title', 'grand-media' );
		$selected     = ! empty( $instance['term_id'] ) ? $instance['term_id'] : '';
		$gmedia_terms = $gmDB->get_terms( 'gmedia_album', array( 'status' => array( 'publish', 'private' ), 'orderby' => 'name', 'order' => 'ASC' ) );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'grand-media' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Choose Album', 'grand-media' ); ?>:</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'term_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'term_id' ) ); ?>">
				<option value=""><?php esc_html_e( 'Random Album with status "publish"', 'grand-media' ); ?></option>
				<?php
				foreach ( $gmedia_terms as &$item ) {
					gmedia_term_item_more_data( $item );
					echo '<option value="' . esc_attr( $item->term_id ) . '" ' . selected( $selected, $item->term_id ) . '>';
					// translators: author name.
					echo esc_html( $item->name . " ({$item->count}) [{$item->status}] " . ( $item->author_name ? sprintf( __( 'by %s', 'grand-media' ), $item->author_name ) : '(' . __( 'deleted author', 'grand-media' ) . ')' ) );
					echo '</option>';
				}
				?>
			</select>
		</p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 * @see WP_Widget::update()
	 */
	public function update( $new_instance, $old_instance ) {
		$instance            = array();
		$instance['title']   = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['term_id'] = ( ! empty( $new_instance['term_id'] ) ) ? (int) $new_instance['term_id'] : '';

		return $instance;
	}

} // class GrandMedia_Album_Widget.
