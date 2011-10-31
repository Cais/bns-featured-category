<?php
/*
Plugin Name: BNS Featured Category
Plugin URI: http://buynowshop.com/plugins/bns-featured-category/
Description: Plugin with multi-widget functionality that displays most recent posts from specific category or categories (set with user options). Also includes user options to display: Author and meta details; comment totals; post categories; post tags; and either full post, excerpt, or your choice of the amount of words (or any combination).  
Version: 1.8.6
Author: Edward Caissie
Author URI: http://edwardcaissie.com/
License: GNU General Public License v2
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

/* Last revised October 31, 2011 v1.8.6 */

/*  Copyright 2009-2011  Edward Caissie  (email : edward.caissie@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2,
    as published by the Free Software Foundation.

    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

    The license for this software can also likely be found here:
    http://www.gnu.org/licenses/gpl-2.0.html
*/

global $wp_version;
$exit_message = 'BNS Featured Category requires WordPress version 2.8 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please Update!</a>';
if ( version_compare( $wp_version, "2.9", "<" ) ) {
    exit ( $exit_message );
}

/* BNS Featured Category TextDomain
 * Make plugin text available for translation (i18n)
 *
 * @package: BNS Featured Category
 * @since: 1.8.6    October 29, 2011
 *
 * Note: Translation files are expected to be found in the plugin root folder / directory.
 * `bns-fc` is being used in place of `bns-featured-category`
 */
load_plugin_textdomain( 'bns-fc' );
// End: BNS Plugin TextDomain

// Begin the mess of Excerpt Length fiascoes
function bnsfc_first_words( $text, $length = 55 ) {
        if ( !$length )
            return $text;
        $text = strip_tags( $text );
        $words = explode( ' ', $text, $length + 1 );
        if ( count( $words ) > $length ) {
            array_pop( $words );
            array_push( $words, '...' );
            $text = implode( ' ', $words );
        }
        return $text;
}
// End Excerpt Length

/* Add our function to the widgets_init hook. */
add_action( 'widgets_init', 'load_bnsfc_widget' );
  
/* Function that registers our widget. */
function load_bnsfc_widget() {
        register_widget( 'BNS_Featured_Category_Widget' );
}

class BNS_Featured_Category_Widget extends WP_Widget {
    function BNS_Featured_Category_Widget() {
            /* Widget settings. */
            $widget_ops = array( 'classname' => 'bns-featured-category', 'description' => __( 'Displays most recent posts from a specific featured category or categories.', 'bns-fc' ) );
            /* Widget control settings. */
            $control_ops = array( 'width' => 450, 'height' => 350, 'id_base' => 'bns-featured-category' );
            /* Create the widget. */
            $this->WP_Widget( 'bns-featured-category', 'BNS Featured Category', $widget_ops, $control_ops );
    }
    function widget( $args, $instance ) {
        extract( $args );
        /* User-selected settings. */
        $title          = apply_filters( 'widget_title', $instance['title'] );
        $cat_choice     = $instance['cat_choice'];
        $use_current    = $instance['use_current'];
        $show_count     = $instance['show_count'];
        $use_thumbnails = $instance['use_thumbnails'];
        $content_thumb  = $instance['content_thumb'];
        $excerpt_thumb  = $instance['excerpt_thumb'];
        $show_meta      = $instance['show_meta'];
        $show_comments  = $instance['show_comments'];
        $show_cats      = $instance['show_cats'];
        $show_cat_desc	= $instance['show_cat_desc'];
        $show_tags      = $instance['show_tags'];
        $only_titles    = $instance['only_titles'];
        $show_full      = $instance['show_full'];
        $excerpt_length	= $instance['excerpt_length'];
        $count          = $instance['count']; /* Plugin requires counter variable to be part of its arguments?! */

        /** @var $before_widget TYPE_NAME */
        echo $before_widget;

        /* Title of widget (before and after defined by themes). */
        // $cat_choice_class = '';
        $cat_choice_class = preg_replace( "/[,]/", "-", $cat_choice );
        if ( $title ) {
            /** @var $before_title TYPE_NAME */
            /** @var $after_title TYPE_NAME */
            echo $before_title . '<span class="bns-cat-class-' . $cat_choice_class . '">' . $title . '</span>' . $after_title;
        }

        /* Display posts from widget settings. */
        if ( is_single() && $use_current ){
            $cat_choices = wp_get_post_categories( get_the_ID() );
            $cat_choice = $cat_choices[0];
        }

        query_posts( "cat=$cat_choice&posts_per_page=$show_count" );
        if ( $show_cat_desc )
            echo '<div class="bnsfc-cat-desc">' . category_description() . '</div>';

        if ( have_posts() ) : while ( have_posts() ) : the_post();
                // echo $cat_choice . ' bacon!!'; /* Test phrase! */
                // static $count = 0; /* see above */
                if ( $count == $show_count ) {
                    break;
                } else { ?>
                    <div <?php post_class(); ?>>
                        <strong><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-fc' ); ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a></strong>
                        <div class="post-details">
                            <?php if ( $show_meta ) {
                                printf( __( 'by %1$s on %2$s', 'bns-fc' ), get_the_author(), get_the_time( get_option( 'date_format' ) ) ); ?><br />
                            <?php }
                            if ( ( $show_comments ) && ( ! post_password_required() ) ) {
                                comments_popup_link( __( 'with No Comments', 'bns-fc' ), __( 'with 1 Comment', 'bns-fc' ), __( 'with % Comments', 'bns-fc' ), '', __( 'with Comments Closed', 'bns-fc' ) ); ?><br />
                            <?php }
                            if ( $show_cats ) {
                                printf( __( 'in %s', 'bns-fc' ), get_the_category_list( ', ' ) ); ?><br />
                            <?php }
                            if ( $show_tags ) {
                                the_tags( __( 'as ', 'bns-fc' ), ', ', '' ); ?><br />
                            <?php } ?>
                        </div> <!-- .post-details -->
                        <?php if ( !$only_titles ) { ?>
                            <div style="overflow-x: auto"> <!-- for images wider than widget area -->
                                <?php if ( $show_full ) {
                                    if ( has_post_thumbnail() && ( $use_thumbnails ) )
                                        the_post_thumbnail( array( $content_thumb, $content_thumb ) , array( 'class' => 'alignleft' ) );
                                    the_content();
                                } elseif ( isset( $instance['excerpt_length']) && $instance['excerpt_length'] > 0 ) {
                                    if ( has_post_thumbnail() && ( $use_thumbnails ) )
                                        the_post_thumbnail( array( $excerpt_thumb, $excerpt_thumb ) , array( 'class' => 'alignleft' ) );
                                    echo bnsfc_first_words( get_the_content(), $instance['excerpt_length'] );
                                } else {
                                    if ( has_post_thumbnail() && ( $use_thumbnails ) )
                                        the_post_thumbnail( array( $excerpt_thumb, $excerpt_thumb ) , array( 'class' => 'alignleft' ) );
                                    the_excerpt();
                                } ?>
                            </div>
                        <?php } ?>
                    </div> <!-- .post #post-ID -->
                    <?php $count++;
                }
            endwhile;
  		else :
            _e( 'Yes, we have no bananas, or posts, today.', 'bns-fc' );
        endif;

        /** @var $after_widget TYPE_NAME */
        echo $after_widget;
        wp_reset_query();
    }

    function update( $new_instance, $old_instance ) {
            $instance = $old_instance;

            /* Strip tags (if needed) and update the widget settings. */
            $instance['title']          = strip_tags( $new_instance['title'] );
            $instance['cat_choice']     = strip_tags( $new_instance['cat_choice'] );
            $instance['use_current']    = $new_instance['use_current'];
            $instance['show_count']     = $new_instance['show_count'];
            $instance['use_thumbnails'] = $new_instance['use_thumbnails'];
            $instance['content_thumb']  = $new_instance['content_thumb'];
            $instance['excerpt_thumb']  = $new_instance['excerpt_thumb'];
            $instance['show_meta']      = $new_instance['show_meta'];
            $instance['show_comments']  = $new_instance['show_comments'];
            $instance['show_cats']      = $new_instance['show_cats'];
            $instance['show_cat_desc']  = $new_instance['show_cat_desc'];
            $instance['show_tags']      = $new_instance['show_tags'];
            $instance['only_titles']    = $new_instance['only_titles'];
            $instance['show_full']      = $new_instance['show_full'];
            $instance['excerpt_length']	= $new_instance['excerpt_length'];
            $instance['count']          = $new_instance['count']; /* added to be able to reset count to zero for every instance of the plugin */

            return $instance;
    }

    function form( $instance ) {
            /* Set up some default widget settings. */
            $defaults = array(
                'title'           => __( 'Featured Category', 'bns-fc' ),
                'cat_choice'      => '1',
                'use_current'     => false,
                'count'           => '0', /* resets count to zero as default */
                'show_count'      => '3',
                'use_thumbnails'  => true,
                'content_thumb'   => '100',
                'excerpt_thumb'   => '50',
                'show_meta'       => false,
                'show_comments'   => false,
                'show_cats'       => false,
                'show_cat_desc'   => false,
                'show_tags'       => false,
                'only_titles'     => false,
                'show_full'       => false,
                'excerpt_length'  => ''
            );
            $instance = wp_parse_args( (array) $instance, $defaults );
            ?>
            <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'bns-fc' ); ?></label>
                <input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
            </p>

            <p>
                <label for="<?php echo $this->get_field_id( 'cat_choice' ); ?>"><?php _e( 'Category IDs, separated by commas (no spaces):', 'bns-fc' ); ?></label>
                <input id="<?php echo $this->get_field_id( 'cat_choice' ); ?>" name="<?php echo $this->get_field_name( 'cat_choice' ); ?>" value="<?php echo $instance['cat_choice']; ?>" style="width:100%;" />
            </p>

            <p>
                <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['use_current'], true ); ?> id="<?php echo $this->get_field_id( 'use_current' ); ?>" name="<?php echo $this->get_field_name( 'use_current' ); ?>" />
                <label for="<?php echo $this->get_field_id( 'use_current' ); ?>"><?php _e( 'Use current category in single view?', 'bns-fc' ); ?></label>
            </p>

            <p>
                <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['use_thumbnails'], true ); ?> id="<?php echo $this->get_field_id( 'use_thumbnails' ); ?>" name="<?php echo $this->get_field_name( 'use_thumbnails' ); ?>" />
                <label for="<?php echo $this->get_field_id( 'use_thumbnails' ); ?>"><?php _e( 'Use Featured Image / Post Thumbnails?', 'bns-fc' ); ?></label>
            </p>

            <table width="100%">
                <tr>
                    <td>
                        <p>
                            <label for="<?php echo $this->get_field_id( 'content_thumb' ); ?>"><?php _e( 'Content Thumbnail Size (in px):', 'bns-fc' ); ?></label>
                            <input id="<?php echo $this->get_field_id( 'content_thumb' ); ?>" name="<?php echo $this->get_field_name( 'content_thumb' ); ?>" value="<?php echo $instance['content_thumb']; ?>" style="width:85%;" />
                        </p>
                    </td>
                    <td>
                        <p>
                            <label for="<?php echo $this->get_field_id( 'excerpt_thumb' ); ?>"><?php _e( 'Excerpt Thumbnail Size (in px):', 'bns-fc' ); ?></label>
                            <input id="<?php echo $this->get_field_id( 'excerpt_thumb' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_thumb' ); ?>" value="<?php echo $instance['excerpt_thumb']; ?>" style="width:85%;" />
                        </p>
                    </td>
                </tr>
            </table>

            <p>
                <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_cat_desc'], true ); ?> id="<?php echo $this->get_field_id( 'show_cat_desc' ); ?>" name="<?php echo $this->get_field_name( 'show_cat_desc' ); ?>" />
                <label for="<?php echo $this->get_field_id( 'show_cat_desc' ); ?>"><?php _e( 'Show first Category choice description?', 'bns-fc' ); ?></label>
            </p>

            <p>
                <label for="<?php echo $this->get_field_id( 'show_count' ); ?>"><?php _e( 'Total Posts to Display:', 'bns-fc' ); ?></label>
                <input id="<?php echo $this->get_field_id( 'show_count' ); ?>" name="<?php echo $this->get_field_name( 'show_count' ); ?>" value="<?php echo $instance['show_count']; ?>" style="width:100%;" />
            </p>

            <table width="100%">
                <tr>
                    <td>
                        <p>
                            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_meta'], true ); ?> id="<?php echo $this->get_field_id( 'show_meta' ); ?>" name="<?php echo $this->get_field_name( 'show_meta' ); ?>" />
                            <label for="<?php echo $this->get_field_id( 'show_meta' ); ?>"><?php _e( 'Display Author Meta Details?', 'bns-fc' ); ?></label>
                        </p>
                    </td>
                    <td>
                        <p>
                            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_comments'], true ); ?> id="<?php echo $this->get_field_id( 'show_comments' ); ?>" name="<?php echo $this->get_field_name( 'show_comments' ); ?>" />
                            <label for="<?php echo $this->get_field_id( 'show_comments' ); ?>"><?php _e( 'Display Comment Totals?', 'bns-fc' ); ?></label>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p>
                            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_cats'], true ); ?> id="<?php echo $this->get_field_id( 'show_cats' ); ?>" name="<?php echo $this->get_field_name( 'show_cats' ); ?>" />
                            <label for="<?php echo $this->get_field_id( 'show_cats' ); ?>"><?php _e( 'Display the Post Categories?', 'bns-fc' ); ?></label>
                        </p>
                    </td>
                    <td>
                        <p>
                            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_tags'], true ); ?> id="<?php echo $this->get_field_id( 'show_tags' ); ?>" name="<?php echo $this->get_field_name( 'show_tags' ); ?>" />
                            <label for="<?php echo $this->get_field_id( 'show_tags' ); ?>"><?php _e( 'Display the Post Tags?', 'bns-fc' ); ?></label>
                        </p>
                    </td>
                </tr>
            </table>

            <hr /> <!-- separates meta details display from content/excerpt display options -->
            <p><?php _e( 'The default is to show the excerpt, if it exists, or the first 55 words of the post as the excerpt.', 'bns-fc'); ?></p>

            <p>
                <label for="<?php echo $this->get_field_id( 'only_titles' ); ?>"></label><input class="checkbox" type="checkbox" <?php checked( (bool) $instance['only_titles'], true ); ?> id="<?php echo $this->get_field_id( 'only_titles' ); ?>" name="<?php echo $this->get_field_name( 'only_titles' ); ?>" />
                <label for="<?php echo $this->get_field_id( 'show_full' ); ?>"><?php _e( 'Display only the Post Titles?', 'bns-fc' ); ?></label>
            </p>

            <p>
                <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_full'], true ); ?> id="<?php echo $this->get_field_id( 'show_full' ); ?>" name="<?php echo $this->get_field_name( 'show_full' ); ?>" />
                <label for="<?php echo $this->get_field_id( 'show_full' ); ?>"><?php _e( 'Display entire Post?', 'bns-fc' ); ?></label>
            </p>

            <p>
                <label for="<?php echo $this->get_field_id( 'excerpt_length' ); ?>"><?php _e( 'Set your preferred value for the amount of words', 'bns-fc' ); ?></label>
                <input id="<?php echo $this->get_field_id( 'excerpt_length' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_length' ); ?>" value="<?php echo $instance['excerpt_length']; ?>" style="width:100%;" />
            </p>
    <?php }
} // End Class BNS_Featured_Category_Widget

/* BNSFC Shortcode Start - May the Gods of programming protect us all! */
function bnsfc_shortcode( $atts ) {
        ob_start(); /* Get ready to capture the elusive widget output */
        the_widget( 'BNS_Featured_Category_Widget',
                    $instance = shortcode_atts( array(
                                                     'title'           => __( 'Featured Category', 'bns-fc' ),
                                                     'cat_choice'      => '1',
                                                     'use_current'     => false,
                                                     'count'           => '0',
                                                     'show_count'      => '3',
                                                     'use_thumbnails'  => true,
                                                     // 'content_thumb'   => '100', /* Does not apply if show_full is not usable */
                                                     'excerpt_thumb'   => '50',
                                                     'show_meta'       => false,
                                                     'show_comments'   => false,
                                                     'show_cats'       => false,
                                                     'show_cat_desc'   => false,
                                                     'show_tags'       => false,
                                                     'only_titles'     => false,
                                                     // 'show_full'       => false, /* Showing the full post causes a recursive nightmare to infinity and beyond! */
                                                     'excerpt_length'  => ''
                                                ), $atts ),
                    $args = array(
                        $before_widget = '',
                        $after_widget = '',
                        $before_title = '',
                        $after_title = '',
                    )
        );
        $bnsfc_content = ob_get_contents(); /* Get the_widget output and put into its own container */
        ob_end_clean(); /* All your snipes belong to us! */

        return $bnsfc_content;
}
add_shortcode( 'bnsfc', 'bnsfc_shortcode' );
/* BNSFC Shortcode End - Say your prayers ... */
?>
<?php /* Last revised: October 31, 2011 v1.8.6 */ ?>