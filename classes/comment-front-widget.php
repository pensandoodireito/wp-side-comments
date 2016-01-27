<?php

class CommentWidgetFront extends WP_Widget {

	function __construct() {
		parent::__construct(
		// Base ID of your widget
			'comment_front_widget',
			// Widget name will appear in UI
			__( 'Texto em debate', 'comment_front_widget_domain' ),
			// Widget description
			array( 'description' => __( 'Últimos parágrafos e seus comentários.', 'comment_front_widget_domain' ), )
		);
	}

	// Creating widget front-end
	// This is where the action happens
	public function widget( $args, $instance ) {
		$title     = apply_filters( 'widget_title', $instance['title'] );
		$post      = get_post( $instance['textID'] );
		$permalink = get_permalink( $post );
		echo $args['before_widget'];
		?>

		<div class="row">
			<div class="col-md-12">
				<h2 class="font-roboto red"><?php echo $title; ?></h2>

				<p><?php echo $instance['text']; ?></p>
			</div>
		</div>

		<div class="row ultimos-comentarios-estructure">
			<div class="col-md-12">

				<div class="comments-structure">
					<div class="comments-main">
						<div></div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12 text-center">
				<p><a href="<?php echo $permalink; ?>"
				      class="btn btn-danger btn-md font-roboto mt-lg mb-lg"><strong>Participe do debate!</strong></a>
				</p>
			</div>
		</div>
		<?php echo $args['after_widget']; ?>
		<script type="text/template" id="comentarioItem">
			<li class='list-group-item' id="comment-<%= id%>">
				<div class='comments-text'>
					<div class='comment-content'>
						<div class='comment-comment'>
							<p><a href='#'><%= comentario %></a></p>
						</div>
						<div class='comments-mic-info'>
							<p>
								<small><%= autor %><span
										class='ml-md'><i
											class='fa fa-clock-o'></i> <%= data %></span>
								</small>
							</p>
						</div>
					</div>
				</div>
				</div>
			</li>
		</script>
		<script type="text/template" id="secaoItem">
			<div class="comments-col" id="section-<%= id%>">
				<div class="comments-header">
					<p class="red"><strong><a href="#"><%= secao %></a></strong>
					</p>
				</div>
				<ul class="list-group">
				</ul>
			</div>
		</script>
		<?php
		echo $args['after_widget'];

		wp_enqueue_script( 'backbone' );
		wp_register_script( 'jquery-sort-elements', CTLT_WP_SIDE_COMMENTS_PLUGIN_URL . 'includes/js/jquery.sortElements.js', array(
			'backbone',
			'jquery'
		) );
		wp_register_script( 'last-comments-debate', CTLT_WP_SIDE_COMMENTS_PLUGIN_URL . 'includes/js/last-comments.js', array( 'jquery-sort-elements' ) );
		wp_localize_script( 'last-comments-debate', 'commentFrontParams',
			array(
				'post_id'  => $post->ID,
				'post_url' => $permalink,
				'nonce'    => wp_create_nonce( 'side_comments_last_comments_nonce' ),
				'delay'    => $instance['delay']
			) );
		wp_enqueue_script( 'jquery-sort-elements' );
		wp_enqueue_script( 'last-comments-debate' );
	}

	// Widget Backend
	public function form( $instance ) {

		$args   = array(
			'post_type' => array( 'texto-em-debate' ),
		);
		$result = new WP_Query( $args );

		$select = array();
		foreach ( $result->posts as $post ) {
			$select[ $post->ID ] = $post->post_title;
		}

		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'Útimos comentários no texto', 'comment_front_widget_domain' );
		}

		if ( isset( $instance['text'] ) ) {
			$text = $instance['text'];
		} else {
			$text = '';
		}

		if ( isset( $instance['delay'] ) ) {
			$delay = (int) $instance['delay'];
		} else {
			$delay = 5;
		}

		if ( isset( $instance['textID'] ) ) {
			$selectId = (int) $instance['textID'];
		} else {
			$selectId = false;
		}

		// Widget admin form
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'postID' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
			       name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
			       value="<?php echo esc_attr( $title ); ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'postID' ); ?>"><?php _e( 'Texto:' ); ?></label>
			<textarea class="widefat" id="<?php echo $this->get_field_id( 'text' ); ?>"
			          name="<?php echo $this->get_field_name( 'text' ); ?>"><?php echo esc_attr( $text ); ?></textarea>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'postID' ); ?>"><?php _e( 'Selecione o texto:' ); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id( 'textID' ); ?>"
			        name="<?php echo $this->get_field_name( 'textID' ); ?>">
				<?php foreach ( $select as $id => $textTitle ): ?>
					<option
						value="<?php echo $id; ?>"<?php if ( $selectId == $id ): ?> selected<?php endif; ?>><?php echo $textTitle; ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'postID' ); ?>"><?php _e( 'Intervalo (segundos):' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'delay' ); ?>"
			       name="<?php echo $this->get_field_name( 'delay' ); ?>" type="text"
			       value="<?php echo esc_attr( $delay ); ?>"/>
		</p>
		<?php
	}

	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance           = array();
		$instance['title']  = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['text']   = ( ! empty( $new_instance['text'] ) ) ? strip_tags( $new_instance['text'] ) : '';
		$instance['delay']  = ( ! empty( $new_instance['delay'] ) ) ? strip_tags( $new_instance['delay'] ) : 5;
		$instance['textID'] = ( ! empty( $new_instance['textID'] ) ) ? strip_tags( $new_instance['textID'] ) : false;

		return $instance;
	}
} // Class wpb_widget ends here

// Register and load the widget
function CommentWidgetFront_load_widget() {
	register_widget( 'CommentWidgetFront' );
}

add_action( 'widgets_init', 'CommentWidgetFront_load_widget' );
