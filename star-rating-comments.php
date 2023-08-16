<?php
/*
Plugin Name: star-rating-comment
Description: Custom Plugin for adding custom code
Version: 1.0.0
Author: Meeple Island
Author URI: https://www.meepleisland.com

*/

// Enqueue the plugin's styles.
add_action( 'wp_enqueue_scripts', 'wpp_comment_rating_styles' );

function wpp_comment_rating_styles() {
  wp_register_style( 'wpp-comment-rating-styles', plugins_url( '/', __FILE__ ) . 'assets/style.css' );
  wp_enqueue_style( 'dashicons' );
  wp_enqueue_style( 'wpp-comment-rating-styles' );
}


// Create the rating interface.
add_action( 'comment_form_logged_in_after', 'wpp_comment_rating_rating_field' );
add_action( 'comment_form_before_fields', 'wpp_comment_rating_rating_field' );

function wpp_comment_rating_rating_field () {

    /* If post_type == jeu and if the form isn't the reply form */
    if ( get_post_type( get_queried_object_id() ) == 'jeu' && !isset( $_GET['replytocom'] ) ) {
        ?>
            <label for="rating">Notation</label>
            <fieldset class="comments-rating">
                <span class="rating-container">
                    <?php for ( $i = 5; $i >= 1; $i-- ) : ?>
                    <input type="radio" id="rating-<?php echo esc_attr( $i ); ?>" name="rating" value="<?php echo esc_attr( $i ); ?>" /><label for="rating-<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></label>
                    <?php endfor; ?>
                    <input type="radio" id="rating-0" class="star-cb-clear" name="rating" value="0" /><label for="rating-0">0</label>
                </span>
            </fieldset>
        <?php
    }

}


// Save the rating submitted by the user.
add_action( 'comment_post', 'wpp_comment_rating_save_comment_rating' );

function wpp_comment_rating_save_comment_rating( $comment_id ) {
    if ( ( isset( $_POST['rating'] ) ) && ( '' !== $_POST['rating'] ) )
    $rating = intval( $_POST['rating'] );
    add_comment_meta( $comment_id, 'rating', $rating );
}


// Make the rating required for 'jeu' CPT
add_filter( 'preprocess_comment', 'wpp_comment_rating_require_rating' );

function wpp_comment_rating_require_rating( $commentdata ) {
    $comment_post_id   = $commentdata['comment_post_ID'];
    $comment_post_type = get_post_type($comment_post_id);

    if ( $comment_post_type == 'jeu'  ) {
        if ( ! is_admin() && ( ! isset( $_POST['rating'] ) || 0 === intval( $_POST['rating'] ) ) ) {
            wp_die( __( 'Erreur : Vous n\'avez pas ajouté une note au jeu. Appuyez sur le bouton Précédent de votre navigateur Web et soumettez à nouveau votre commentaire avec une note.' ) );
        }
    }

    return $commentdata;
}


// Check if the author have not reading another rating for 'jeu' CPT
add_filter('preprocess_comment', 'restrict_one_comment_per_email');

function restrict_one_comment_per_email($commentdata) {
    $comment_post_id      = $commentdata['comment_post_ID'];
    $comment_post_type    = get_post_type($comment_post_id);

    $comment_author_email = $commentdata['comment_author_email'];
    $comment_post_id      = $commentdata['comment_post_ID'];

    if ( $comment_post_type == 'jeu'  ) {
        $existing_comments = get_comments(array(
            'post_id' => $comment_post_id,
            'author_email' => $comment_author_email,
        ));
    
        if (!empty($existing_comments)) {
            wp_die('Vous avez déjà soumis un avis sur le jeu avec cette adresse e-mail.');
        }
    }

    return $commentdata;
}



//Display the rating on a submitted comment.
/* add_filter( 'comment_text', 'wpp_comment_rating_display_rating' ); */
add_shortcode( 'comment_rating_displaying', 'wpp_comment_rating_display_rating' );

function wpp_comment_rating_display_rating( $comment_text ){
    if ( $rating = get_comment_meta( get_comment_ID(), 'rating', true ) ) {
        $stars = '<p class="stars">';

        for ( $i = 1; $i <= $rating; $i++ ) {
            $stars .= '<span class="dashicons dashicons-star-filled"></span>';
        }

        if ( $rating < 5 ) {
            for ( $i = $rating; $i < 5; $i++ ) {
                $stars .= '<span class="dashicons dashicons-star-empty"></span>';
            }
        }
        

        $stars .= '</p>';

        $comment_text = $comment_text . $stars;

        return $comment_text;

    } else {
        return $comment_text;
        
    }
}