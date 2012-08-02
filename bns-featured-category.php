<?php
/*
Plugin Name: BNS Featured Category
Plugin URI: http://buynowshop.com/plugins/bns-featured-category/
Description: Plugin with multi-widget functionality that displays most recent posts from specific category or categories (set with user options). Also includes user options to display: Author and meta details; comment totals; post categories; post tags; and either full post, excerpt, or your choice of the amount of words (or any combination).  
Version: 2.2
Author: Edward Caissie
Author URI: http://edwardcaissie.com/
Textdomain: bns-fc
License: GNU General Public License v2
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

/**
 * BNS Featured Category WordPress plugin
 *
 * Plugin with multi-widget functionality that displays most recent posts from
 * specific category or categories (set with user options). Also includes user
 * options to display: Author and meta details; comment totals; post categories;
 * post tags; and either full post, excerpt, or your choice of the amount of
 * words (or any combination).
 *
 * @package     BNS_Featured_Category
 * @link        http://buynowshop.com/plugins/bns-featured-category/
 * @link        https://github.com/Cais/bns-featured-category/
 * @link        http://wordpress.org/extend/plugins/bns-featured-category/
 * @version     2.2
 * @author      Edward Caissie <edward.caissie@gmail.com>
 * @copyright   Copyright (c) 2009-2012, Edward Caissie
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2, as published by the
 * Free Software Foundation.
 *
 * You may NOT assume that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to:
 *
 *      Free Software Foundation, Inc.
 *      51 Franklin St, Fifth Floor
 *      Boston, MA  02110-1301  USA
 *
 * The license for this software can also likely be found here:
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @version     2.2
 * @date        July 23, 2012
 * Featured images link to post
 * Added option to not show the Post Title
 *
 * @todo Review - http://wordpress.org/support/topic/plugin-bns-featured-category-how-to-make-header-title-as-link-to-category
 * @todo Review - http://buynowshop.com/plugins/bns-featured-category/comment-page-2/#comment-13468 - date range option(s)?
 * @todo Review implementing use of WP_Query class versus query_posts; using WP_Query currently breaks the shortcode output
 * @todo New screenshot(s) ... dot-org header image, too?
 */

/**
 * Check installed WordPress version for compatibility
 * @internal    Requires WordPress version 2.9
 * @internal    @uses current_theme_supports
 * @internal    @uses the_post_thumbnail
 * @internal    @uses has_post_thumbnail
 */
global $wp_version;
$exit_message = 'BNS Featured Category requires WordPress version 2.9 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please Update!</a>';
if ( version_compare( $wp_version, "2.9", "<" ) )
    exit ( $exit_message );

/**
 * BNS Featured Category TextDomain
 * Make plugin text available for translation (i18n)
 *
 * @package:    BNS Featured Category
 * @since:      1.9
 *
 * Note: Translation files are expected to be found in the plugin root folder / directory.
 * `bns-fc` is being used in place of `bns-featured-category`
 *
 * Last revised November 12, 2011
 */
load_plugin_textdomain( 'bns-fc' );

/**
 * BNS Featured Category Custom Excerpt
 *
 * Strips the post content of tags and returns the entire post content if there
 * are less than $length words; otherwise the amount of words equal to $length
 * is returned. In both cases, the returned text is appended with a permalink to
 * the full post.
 *
 * @package BNS_Featured_Category
 * @since   1.9
 *
 * @param   $text - post content
 * @param   int $length - user defined amount of words
 *
 * @uses    get_permalink
 * @uses    the_title_attribute
 *
 * @return  string
 */
function bnsfc_custom_excerpt( $text, $length = 55 ) {
    $text = strip_tags( $text );
    $words = explode( ' ', $text, $length + 1 );

    /** Create link to full post for end of custom length excerpt output */
    $bnsfc_link = ' <strong><a class="bnsfc-link" href="' . get_permalink() . '" title="' . the_title_attribute( array( 'before' => __( 'Permalink to: ', 'bns-fc' ), 'after' => '', 'echo' => false ) ) . '">&infin;</a></strong>';

    if ( ( ! $length ) || ( count( $words ) < $length ) ) {
        $text .= $bnsfc_link;
        return $text;
    } else {
        array_pop( $words );
        array_push( $words, '...' );
        $text = implode( ' ', $words );
    }
    $text .= $bnsfc_link;
    return $text;
}

/**
 * Enqueue Plugin Scripts and Styles
 *
 * Adds plugin stylesheet and allows for custom stylesheet to be added by end-user.
 *
 * @package BNS_Featured_Category
 * @since   1.9
 *
 * @uses    get_plugin_data
 * @uses    plugin_dir_path
 * @uses    plugin_dir_url
 * @uses    wp_enqueue_style
 *
 * @internal Used with action: wp_enqueue_scripts
 *
 * @version 2.2
 * @date    August 2, 2012
 * Programmatically add version number to enqueue calls
 */
function BNSFC_Scripts_and_Styles() {
    /** Call the wp-admin plugin code */
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
    /** @var $bnsfc_data - holds the plugin header data */
    $bnsfc_data = get_plugin_data( __FILE__ );

    /** Enqueue Scripts */
    /** Enqueue Style Sheets */
    wp_enqueue_style( 'BNSFC-Style', plugin_dir_url( __FILE__ ) . 'bnsfc-style.css', array(), $bnsfc_data['Version'], 'screen' );
    if ( is_readable( plugin_dir_path( __FILE__ ) . 'bnsfc-custom-style.css' ) ) {
        wp_enqueue_style( 'BNSFC-Custom-Style', plugin_dir_url( __FILE__ ) . 'bnsfc-custom-style.css', array(), $bnsfc_data['Version'], 'screen' );
    }
}
add_action( 'wp_enqueue_scripts', 'BNSFC_Scripts_and_Styles' );

/**
 * Enqueue Options Plugin Scripts and Styles
 *
 * Add plugin options scripts and stylesheet(s) to be used only on the Administration Panels
 *
 * @package BNS_Featured_Category
 * @since   2.0
 *
 * @uses    plugin_dir_path
 * @uses    plugin_dir_url
 * @uses    wp_enqueue_script
 * @uses    wp_enqueue_style
 *
 * @internal 'jQuery' is enqueued as a dependency of the 'bnsfc-options.js' enqueue
 * @internal Used with action: admin_enqueue_scripts
 */
function BNSFC_Options_Scripts_and_Styles() {
    /** Enqueue Options Scripts */
    wp_enqueue_script( 'bnsfc-options', plugin_dir_url( __FILE__ ) . 'bnsfc-options.js', array( 'jquery' ), '2.0' );
    /** Enqueue Options Style Sheets */
    wp_enqueue_style( 'BNSFC-Option-Style', plugin_dir_url( __FILE__ ) . 'bnsfc-option-style.css', array(), '2.0', 'screen' );
    if ( is_readable( plugin_dir_path( __FILE__ ) . 'bnsfc-options-custom-style.css' ) ) {
        wp_enqueue_style( 'BNSFC-Options-Custom-Style', plugin_dir_url( __FILE__ ) . 'bnsfc-options-custom-style.css', array(), '2.0', 'screen' );
    }
}
add_action( 'admin_enqueue_scripts', 'BNSFC_Options_Scripts_and_Styles' );

/** Register widget */
function load_bnsfc_widget() {
    register_widget( 'BNS_Featured_Category_Widget' );
}
add_action( 'widgets_init', 'load_bnsfc_widget' );

class BNS_Featured_Category_Widget extends WP_Widget {
    function BNS_Featured_Category_Widget() {
        /** Widget settings. */
        $widget_ops = array( 'classname' => 'bns-featured-category', 'description' => __( 'Displays most recent posts from a specific featured category or categories.', 'bns-fc' ) );

        /** Widget control settings. */
        $control_ops = array( 'width' => 200, 'id_base' => 'bns-featured-category' );

        /** Create the widget. */
        $this->WP_Widget( 'bns-featured-category', 'BNS Featured Category', $widget_ops, $control_ops );
    }

    function widget( $args, $instance ) {
        extract( $args );

        /** User-selected settings. */
        $title          = apply_filters( 'widget_title', $instance['title'] );
        $cat_choice     = $instance['cat_choice'];
        $use_current    = $instance['use_current'];
        $show_count     = $instance['show_count'];
        $offset         = $instance['offset'];
        $sort_order     = $instance['sort_order'];
        $use_thumbnails = $instance['use_thumbnails'];
        $content_thumb  = $instance['content_thumb'];
        $excerpt_thumb  = $instance['excerpt_thumb'];
        $show_meta      = $instance['show_meta'];
        $show_comments  = $instance['show_comments'];
        $show_cats      = $instance['show_cats'];
        $show_cat_desc	= $instance['show_cat_desc'];
        $show_tags      = $instance['show_tags'];
        $only_titles    = $instance['only_titles'];
        $no_titles      = $instance['no_titles'];
        $show_full      = $instance['show_full'];
        $excerpt_length	= $instance['excerpt_length'];
        $no_excerpt     = $instance['no_excerpt'];
        /** Plugin requires counter variable to be part of its arguments?! */
        $count          = $instance['count'];

        /** @var    $before_widget  string - defined by theme */
        echo $before_widget;

        /**
         * @var $cat_choice_class - CSS element created from category choices by removing whitespace and replacing commas with hyphens
         */
        $cat_choice_class = preg_replace( '/\\040/', '', $cat_choice );
        $cat_choice_class = preg_replace( "/[,]/", "-", $cat_choice_class );
        /** Widget $title, $before_widget, and $after_widget defined by theme */
        if ( $title ) {
            /**
             * @var $before_title   string - defined by theme
             * @var $after_title    string - defined by theme
             */
            echo $before_title . '<span class="bnsfc-cat-class-' . $cat_choice_class . '">' . $title . '</span>' . $after_title;
            // The following `echo` statement will make the widget title link to the category choice
            // @todo requires further review before going live
            // echo $before_title . '<span class="bnsfc-cat-class-' . $cat_choice_class . '"><a href="' . get_category_link( $cat_choice ) . '">' . $title . '</a></span>' . $after_title;
        }

        /** Display posts from widget settings. */
        // If viewing a page displaying a single post add the current post first category to category choices used by plugin
        if ( is_single() && $use_current ){
            $cat_choices = wp_get_post_categories( get_the_ID() );
            $cat_choice = $cat_choices[0];
        }

        /** Check if $sort_order is set to rand (random) and use the `orderby` parameter; otherwise use the `order` parameter */
        if ( 'rand' == $sort_order )
            $query_args = "cat=$cat_choice&posts_per_page=$show_count&offset=$offset&orderby=$sort_order";
        else
            $query_args = "cat=$cat_choice&posts_per_page=$show_count&offset=$offset&order=$sort_order";

        /** @var $bnsfc_query - object of posts matching query criteria */
        // $bnsfc_query = new WP_Query( $query_args );
        query_posts( $query_args );

        if ( $show_cat_desc )
            echo '<div class="bnsfc-cat-desc">' . category_description() . '</div>';

        // if ( have_posts() ) : while ( $bnsfc_query->have_posts() ) : $bnsfc_query->the_post();
        if ( have_posts() ) : while ( have_posts() ) : the_post();
            // static $count = 0; /* see above */
            if ( $count == $show_count ) {
                break;
            } else { ?>
                <div <?php post_class(); ?>>
                    <?php if ( ! $no_titles ) { ?>
                        <strong><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-fc' ); ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a></strong>
                    <?php } ?>
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
                    <?php if ( ! $only_titles ) { ?>
                        <div class="bnsfc-content">
                            <?php if ( $show_full ) {
                                /** Conditions: Theme supports post-thumbnails -and- there is a post-thumbnail -and- the option to show the post thumbnail is checked */
                                if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail() && ( $use_thumbnails ) ) ?>
                                    <a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-fc' ); ?> <?php the_title_attribute(); ?>"><?php the_post_thumbnail( array( $content_thumb, $content_thumb ) , array( 'class' => 'alignleft' ) ); ?></a>
                                <?php the_content(); ?>
                                <div class="bnsfc-clear"></div>
                                <?php wp_link_pages( array( 'before' => '<p><strong>' . __( 'Pages: ', 'bns-fc') . '</strong>', 'after' => '</p>', 'next_or_number' => 'number' ) );
                            } elseif ( isset( $instance['excerpt_length']) && $instance['excerpt_length'] > 0 ) {
                                if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail() && ( $use_thumbnails ) ) ?>
                                    <a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-fc' ); ?> <?php the_title_attribute(); ?>"><?php the_post_thumbnail( array( $content_thumb, $content_thumb ) , array( 'class' => 'alignleft' ) ); ?></a>
                                <?php echo bnsfc_custom_excerpt( get_the_content(), $instance['excerpt_length'] );
                            } elseif ( ! $instance['no_excerpt'] ) {
                                if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail() && ( $use_thumbnails ) ) ?>
                                    <a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-fc' ); ?> <?php the_title_attribute(); ?>"><?php the_post_thumbnail( array( $content_thumb, $content_thumb ) , array( 'class' => 'alignleft' ) ); ?></a>
                                <?php the_excerpt();
                            } else {
                            if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail() && ( $use_thumbnails ) ) ?>
                                <a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-fc' ); ?> <?php the_title_attribute(); ?>"><?php the_post_thumbnail( array( $content_thumb, $content_thumb ) , array( 'class' => 'alignleft' ) ); ?></a>
                            <?php }?>
                        </div> <!-- .bnsfc-content -->
                    <?php } ?>
                </div> <!-- .post #post-ID -->
                <?php $count++;
            }
            endwhile;
        else :
            _e( 'Yes, we have no bananas, or posts, today.', 'bns-fc' );
        endif;

        /** @var $after_widget string - defined by theme */
        echo $after_widget;

        /** Reset post data - see $bnsfc_query object */
        // wp_reset_postdata();
        wp_reset_query();
    }

    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;

        /** Strip tags (if needed) and update the widget settings */
        $instance['title']          = strip_tags( $new_instance['title'] );
        $instance['cat_choice']     = strip_tags( $new_instance['cat_choice'] );
        $instance['use_current']    = $new_instance['use_current'];
        $instance['show_count']     = $new_instance['show_count'];
        $instance['offset']         = $new_instance['offset'];
        $instance['sort_order']     = $new_instance['sort_order'];
        $instance['use_thumbnails'] = $new_instance['use_thumbnails'];
        $instance['content_thumb']  = $new_instance['content_thumb'];
        $instance['excerpt_thumb']  = $new_instance['excerpt_thumb'];
        $instance['show_meta']      = $new_instance['show_meta'];
        $instance['show_comments']  = $new_instance['show_comments'];
        $instance['show_cats']      = $new_instance['show_cats'];
        $instance['show_cat_desc']  = $new_instance['show_cat_desc'];
        $instance['show_tags']      = $new_instance['show_tags'];
        $instance['only_titles']    = $new_instance['only_titles'];
        $instance['no_titles']      = $new_instance['no_titles'];
        $instance['show_full']      = $new_instance['show_full'];
        $instance['excerpt_length']	= $new_instance['excerpt_length'];
        $instance['no_excerpt']     = $new_instance['no_excerpt'];
        /** Added to reset count for every instance of the plugin */
        $instance['count']          = $new_instance['count'];

        return $instance;
    }

    /**
     * Extend the `form` function
     *
     * @package BNS_Featured_Category
     * @since   1.0
     *
     * @param   $instance
     *
     * @uses    checked
     * @uses    current_theme_supports
     * @uses    get_field_id
     * @uses    get_field_name
     * @uses    selected
     * @uses    wp_parse_args
     *
     * @return string|void
     *
     * @version 2.2
     * @date    July 13, 2012
     * Corrected 'no_excerpt" label issue
     */
    function form( $instance ) {
        /** Set default widget settings */
        $defaults = array(
            'title'             => __( 'Featured Category', 'bns-fc' ),
            'cat_choice'        => '1',
            'use_current'       => false,
            'count'             => '0',
            'show_count'        => '3',
            'offset'            => '0',
            'sort_order'        => 'desc',
            'use_thumbnails'    => true,
            'content_thumb'     => '100',
            'excerpt_thumb'     => '50',
            'show_meta'         => false,
            'show_comments'     => false,
            'show_cats'         => false,
            'show_cat_desc'     => false,
            'show_tags'         => false,
            'only_titles'       => false,
            'no_titles'         => false,
            'show_full'         => false,
            'excerpt_length'    => '',
            'no_excerpt'        => false
        );
        $instance = wp_parse_args( (array) $instance, $defaults );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'bns-fc' ); ?></label>
            <input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:95%;" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'cat_choice' ); ?>"><?php _e( 'Category IDs, separated by commas:', 'bns-fc' ); ?></label>
            <input id="<?php echo $this->get_field_id( 'cat_choice' ); ?>" name="<?php echo $this->get_field_name( 'cat_choice' ); ?>" value="<?php echo $instance['cat_choice']; ?>" style="width:95%;" />
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_cat_desc'], true ); ?> id="<?php echo $this->get_field_id( 'show_cat_desc' ); ?>" name="<?php echo $this->get_field_name( 'show_cat_desc' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'show_cat_desc' ); ?>"><?php _e( 'Show first category choice\'s description?', 'bns-fc' ); ?></label>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['use_current'], true ); ?> id="<?php echo $this->get_field_id( 'use_current' ); ?>" name="<?php echo $this->get_field_name( 'use_current' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'use_current' ); ?>"><?php _e( 'Use current category in single view?', 'bns-fc' ); ?></label>
        </p>

        <table class="bnsfc-counts">
            <tr>
                <td>
                    <p>
                        <label for="<?php echo $this->get_field_id( 'show_count' ); ?>"><?php _e( 'Posts to Display:', 'bns-fc' ); ?></label>
                        <input id="<?php echo $this->get_field_id( 'show_count' ); ?>" name="<?php echo $this->get_field_name( 'show_count' ); ?>" value="<?php echo $instance['show_count']; ?>" style="width:85%;" />
                    </p>
                </td>
                <td>
                    <p>
                        <label for="<?php echo $this->get_field_id( 'offset' ); ?>"><?php _e( 'Posts Offset:', 'bns-fc' ); ?></label>
                        <input id="<?php echo $this->get_field_id( 'offset' ); ?>" name="<?php echo $this->get_field_name( 'offset' ); ?>" value="<?php echo $instance['offset']; ?>" style="width:85%;" />
                    </p>
                </td>
            </tr>
            <tr>
                <td>
                    <p>
                        <label for="<?php echo $this->get_field_id( 'sort_order' ); ?>"><?php _e( 'Sort Order:', 'bns-fc' ); ?></label>
                        <select id="<?php echo $this->get_field_id( 'sort_order' ); ?>" name="<?php echo $this->get_field_name( 'sort_order' ); ?>" class="widefat">
                            <option <?php selected( 'asc', $instance['sort_order'], true ); ?>>asc</option>
                            <option <?php selected( 'desc', $instance['sort_order'], true ); ?>>desc</option>
                            <option <?php selected( 'rand', $instance['sort_order'], true ); ?>>rand</option>
                        </select>
                    </p>
                </td>
            </tr>
        </table>

        <hr />
        <!-- The following option choices may affect the widget option panel layout -->
        <p><?php _e( 'NB: Some options may not be available depending on which ones are selected.', 'bns-fc'); ?></p>
        <p class="bnsfc-display-all-posts-check">
            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['only_titles'], true ); ?> id="<?php echo $this->get_field_id( 'only_titles' ); ?>" name="<?php echo $this->get_field_name( 'only_titles' ); ?>" />
            <?php $all_options_toggle = ( checked( (bool) $instance['only_titles'], true, false ) ) ? 'closed' : 'open'; ?>
            <label for="<?php echo $this->get_field_id( 'only_titles' ); ?>"><?php _e( 'ONLY display Post Titles?', 'bns-fc' ); ?></label>
        </p>

        <p class="bnsfc-all-options-<?php echo $all_options_toggle; ?> bnsfc-no-titles">
            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['no_titles'], true ); ?> id="<?php echo $this->get_field_id( 'no_titles' ); ?>" name="<?php echo $this->get_field_name( 'no_titles' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'no_titles' ); ?>"><?php _e( 'Do NOT display Post Titles?', 'bns-fc' ); ?></label>
        </p>

        <!-- If the theme supports post-thumbnails carry on; otherwise hide the thumbnails section -->
        <?php if ( ! current_theme_supports( 'post-thumbnails' ) ) echo '<div class="bnsfc-thumbnails-closed">'; ?>
            <p class="bnsfc-all-options-<?php echo $all_options_toggle; ?> bnsfc-display-thumbnail-sizes"><!-- Hide all options below if ONLY post titles are to be displayed -->
                <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['use_thumbnails'], true ); ?> id="<?php echo $this->get_field_id( 'use_thumbnails' ); ?>" name="<?php echo $this->get_field_name( 'use_thumbnails' ); ?>" />
                <?php $thumbnails_toggle = ( checked( (bool) $instance['use_thumbnails'], true, false ) ) ? 'open' : 'closed'; ?>
                <label for="<?php echo $this->get_field_id( 'use_thumbnails' ); ?>"><?php _e( 'Use Featured Image Thumbnails?', 'bns-fc' ); ?></label>
            </p>

            <table class="bnsfc-thumbnails-<?php echo $thumbnails_toggle; ?> bnsfc-all-options-<?php echo $all_options_toggle; ?>"><!-- Hide table if featured image / thumbnails are not used -->
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
        <?php if ( ! current_theme_supports( 'post-thumbnails' ) ) echo '</div>'; ?>
        <!-- Carry on from here if there is no thumbnail support -->

        <p class="bnsfc-all-options-<?php echo $all_options_toggle; ?>">
            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_meta'], true ); ?> id="<?php echo $this->get_field_id( 'show_meta' ); ?>" name="<?php echo $this->get_field_name( 'show_meta' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'show_meta' ); ?>"><?php _e( 'Display Author Meta Details?', 'bns-fc' ); ?></label>
        </p>

        <p class="bnsfc-all-options-<?php echo $all_options_toggle; ?>">
            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_comments'], true ); ?> id="<?php echo $this->get_field_id( 'show_comments' ); ?>" name="<?php echo $this->get_field_name( 'show_comments' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'show_comments' ); ?>"><?php _e( 'Display Comment Totals?', 'bns-fc' ); ?></label>
        </p>

        <p class="bnsfc-all-options-<?php echo $all_options_toggle; ?>">
            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_cats'], true ); ?> id="<?php echo $this->get_field_id( 'show_cats' ); ?>" name="<?php echo $this->get_field_name( 'show_cats' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'show_cats' ); ?>"><?php _e( 'Display the Post Categories?', 'bns-fc' ); ?></label>
        </p>

        <p class="bnsfc-all-options-<?php echo $all_options_toggle; ?>">
            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_tags'], true ); ?> id="<?php echo $this->get_field_id( 'show_tags' ); ?>" name="<?php echo $this->get_field_name( 'show_tags' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'show_tags' ); ?>"><?php _e( 'Display the Post Tags?', 'bns-fc' ); ?></label>
        </p>

        <p class="bnsfc-all-options-<?php echo $all_options_toggle; ?> bnsfc-excerpt-option-open-check">
            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_full'], true ); ?> id="<?php echo $this->get_field_id( 'show_full' ); ?>" name="<?php echo $this->get_field_name( 'show_full' ); ?>" />
            <?php $show_full_toggle = ( checked( (bool) $instance['show_full'], true, false ) ) ? 'closed' : 'open'; ?>
            <label for="<?php echo $this->get_field_id( 'show_full' ); ?>"><?php _e( 'Display entire Post?', 'bns-fc' ); ?></label>
        </p>

        <hr />
        <!-- Hide excerpt explanation and word count option if entire post is displayed -->
        <p class="bnsfc-all-options-<?php echo $all_options_toggle; ?> bnsfc-excerpt-option-<?php echo $show_full_toggle; ?>">
            <?php _e( 'The post excerpt is shown by default, if it exists; otherwise the first 55 words of the post are shown as the excerpt ...', 'bns-fc'); ?>
        </p>

        <p class="bnsfc-all-options-<?php echo $all_options_toggle; ?> bnsfc-excerpt-option-<?php echo $show_full_toggle; ?>">
            <label for="<?php echo $this->get_field_id( 'excerpt_length' ); ?>"><?php _e( '... or set the amount of words you want to show:', 'bns-fc' ); ?></label>
            <input id="<?php echo $this->get_field_id( 'excerpt_length' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_length' ); ?>" value="<?php echo $instance['excerpt_length']; ?>" style="width:95%;" />
        </p>

        <p class="bnsfc-all-options-<?php echo $all_options_toggle; ?> bnsfc-excerpt-option-<?php echo $show_full_toggle; ?>">
            <label for="<?php echo $this->get_field_id( 'no_excerpt' ); ?>"><?php _e( '... or have no excerpt at all!', 'bns-fc' ); ?></label>
            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['no_excerpt'], true ); ?> id="<?php echo $this->get_field_id( 'no_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'no_excerpt' ); ?>" />
        </p>

    <?php }
}

/**
 * BNSFC Shortcode
 * - May the Gods of programming protect us all!
 *
 * @package BNS_Featured_Category
 * @since   1.8
 *
 * @param   $atts
 *
 * @uses    shortcode_atts
 * @uses    the_widget
 *
 * @internal Do NOT set 'show_full=true' it will create a recursive loop and crash
 * @internal Note 'content_thumb' although available has no use if 'show_full=false'
 * @internal Used with `add_shortcode`
 *
 * @return  string
 *
 * @version 1.9.1
 * Last revised November 24, 2011
 * Added 'content_thumb' and 'show_full' to options; the former has no use as the latter should not be set to true, but the additions remove the errors being thrown by WP_Debug
 * @version 1.9.2
 * Added 'offset' option
 *
 * @todo Fix 'show_full=true' issue
 */
function bnsfc_shortcode( $atts ) {
    /** Get ready to capture the elusive widget output */
    ob_start();
    the_widget( 'BNS_Featured_Category_Widget',
        $instance = shortcode_atts( array(
            'title'             => __( 'Featured Category', 'bns-fc' ),
            'cat_choice'        => '1',
            'use_current'       => false,
            'count'             => '0',
            'show_count'        => '3',
            'offset'            => '0',
            'sort_order'        => 'DESC',
            'use_thumbnails'    => true,
            'content_thumb'     => '100',
            'excerpt_thumb'     => '50',
            'show_meta'         => false,
            'show_comments'     => false,
            'show_cats'         => false,
            'show_cat_desc'     => false,
            'show_tags'         => false,
            'only_titles'       => false,
            'no_titles'         => false,
            'show_full'         => false, /** Do not set to true!!! */
            'excerpt_length'    => '',
            'no_excerpt'        => false
        ), $atts ),
        $args = array(
            /** clear variables defined by theme for widgets */
            $before_widget  = '',
            $after_widget   = '',
            $before_title   = '',
            $after_title    = '',
        )
    );
    /** Get the_widget output and put into its own container */
    $bnsfc_content = ob_get_contents();
    ob_end_clean();
    /** All your snipes belong to us! */

    return $bnsfc_content;
}
add_shortcode( 'bnsfc', 'bnsfc_shortcode' );
// End BNSFC Shortcode - Say your prayers ...