<?php
/*
Plugin Name: BNS Featured Category
Plugin URI: http://buynowshop.com/plugins/bns-featured-category/
Description: Plugin with multi-widget functionality that displays most recent posts from specific category or categories (set with user options). Also includes user options to display: Category Description; Author and meta details; comment totals; post categories; post tags; and either full post, excerpt, or your choice of the amount of words (or any combination). Please make sure to read the latest changelog for new and modified features and options.
Version: 2.8.3
Author: Edward Caissie
Author URI: http://edwardcaissie.com/
Text Domain: bns-featured-category
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
 * @link        https://wordpress.org/plugins/bns-featured-category/
 * @version     2.8.3
 * @author      Edward Caissie <edward.caissie@gmail.com>
 * @copyright   Copyright (c) 2009-2017, Edward Caissie
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
 * @version     2.8.3
 * @date        July 2017
 */
class BNS_Featured_Category extends WP_Widget {

	private static $instance = null;

	/**
	 * Create Instance
	 *
	 * Creates a single instance of the class
	 *
	 * @package BNS_Featured_Category
	 * @since   2.8
	 * @date    January 10, 2016
	 *
	 * @return null|BNS_Featured_Category
	 */
	public static function create_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	/**
	 * Constructor
	 *
	 * @package    BNS_Featured_Category
	 *
	 * @class      WP_Widget
	 * @uses       (CONSTANT) WP_CONTENT_DIR
	 * @uses       __
	 * @uses       add_action
	 * @uses       add_filter
	 * @uses       add_shortcode
	 * @uses       content_url
	 *
	 * @version    2.8
	 * @date       January 10, 2016
	 * Moved "in plugin update message" function into class
	 */
	function __construct() {

		register_activation_hook( __FILE__, array( $this, 'install' ) );

		load_plugin_textdomain( 'bns-featured-category' );

		/** Widget settings. */
		$widget_ops = array(
			'classname'   => 'bns-featured-category',
			'description' => __( 'Displays most recent posts from a specific featured category or categories.', 'bns-featured-category' )
		);

		/** Widget control settings. */
		$control_ops = array(
			'width'   => 200,
			'id_base' => 'bns-featured-category'
		);

		/** Create the widget. */
		parent::__construct( 'bns-featured-category', 'BNS Featured Category', $widget_ops, $control_ops );

		/**
		 * Check installed WordPress version for compatibility
		 * @internal    Requires WordPress version 3.6
		 * @internal    @uses shortcode_atts with optional shortcode filter parameter
		 */
		global $wp_version;
		$exit_message = 'BNS Featured Category requires WordPress version 3.6 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please Update!</a>';
		if ( version_compare( $wp_version, "3.6", "<" ) ) {
			exit ( $exit_message );
		}

		/** Define location for BNS plugin customizations */
		if ( ! defined( 'BNS_CUSTOM_PATH' ) ) {
			define( 'BNS_CUSTOM_PATH', WP_CONTENT_DIR . '/bns-customs/' );
		}
		if ( ! defined( 'BNS_CUSTOM_URL' ) ) {
			define( 'BNS_CUSTOM_URL', content_url( '/bns-customs/' ) );
		}

		/** Enqueue Scripts and Styles for front-facing views */
		add_action( 'wp_enqueue_scripts', array(
			$this,
			'BNSFC_Scripts_and_Styles'
		) );

		/** Enqueue Widget Options Panel Scripts and Styles */
		add_action( 'admin_enqueue_scripts', array(
			$this,
			'BNSFC_Options_Scripts_and_Styles'
		) );

		/** Add shortcode */
		add_shortcode( 'bnsfc', array( $this, 'bnsfc_shortcode' ) );

		/** Add widget */
		add_action( 'widgets_init', array( $this, 'load_bnsfc_widget' ) );

		/** Add Plugin Row Meta details */
		add_filter( 'plugin_row_meta', array(
			$this,
			'bnsfc_plugin_meta'
		), 10, 2 );

		/** Add "in plugin update message" text */
		add_action( 'in_plugin_update_message-' . plugin_basename( __FILE__ ), array(
			$this,
			'bnsfc_in_plugin_update_message'
		) );

	}


	/**
	 * Check installed WordPress version for compatibility
	 *
	 * @package     BNS_Featured_Category
	 * @since       2.8.1
	 * @date        February 21, 2016
	 *
	 * @internal    Version 3.6 being used in reference to shortcode attributes
	 *
	 * @uses        BNS_Featured_Category::plugin_data
	 * @uses        __
	 * @uses        apply_filters
	 * @uses        deactivate_plugins
	 * @uses        get_bloginfo
	 */
	function install() {

		/** @var float $version_required - see "Requires at least" from `readme.txt` */
		$version_required = apply_filters( 'bns_featured_category_requires_at_least_version', '3.6' );

		$plugin_data = $this->plugin_data();

		/** @var string $exit_message - build an explanation message */
		$exit_message = sprintf( __( '%1$s requires WordPress version %2$s or later.', 'bns-featured_category' ), $plugin_data['Name'], $version_required );
		$exit_message .= '<br />';
		$exit_message .= sprintf( '<a href="http://codex.wordpress.org/Upgrading_WordPress" target="_blank">%1$s</a>', __( 'Please Update!', 'bns-featured_category' ) );

		/** Conditional check of current WordPress version */
		if ( version_compare( get_bloginfo( 'version' ), floatval( $version_required ), '<' ) ) {

			deactivate_plugins( basename( __FILE__ ) );
			exit( $exit_message );

		}

	}


	/**
	 * Widget
	 *
	 * @package    BNS_Featured_Category
	 *
	 * @class      WP_Query
	 * @uses       __
	 * @uses       apply_filters
	 * @uses       category_description
	 * @uses       current_theme_supports
	 * @uses       get_category_link
	 * @uses       get_option
	 * @uses       get_the_author
	 * @uses       get_the_category_list
	 * @uses       get_the_content
	 * @uses       get_the_ID
	 * @uses       get_the_time
	 * @uses       has_post_thumbnail
	 * @uses       have_posts
	 * @uses       is_single
	 * @uses       post_class
	 * @uses       post_password_required
	 * @uses       the_permalink
	 * @uses       the_post
	 * @uses       the_post_thumbnail
	 * @uses       the_title
	 * @uses       the_title_attribute
	 * @uses       wp_get_post_categories
	 * @uses       wp_trim_words
	 *
	 * @param   array $args
	 * @param   array $instance
	 *
	 * @version    2.8.2
	 * @date       March 5, 2016
	 * Added custom meta field search option
	 *
	 * @version    2.8.3
	 * @date       July 29, 2017
	 * Fixed issue with the `$bnsfc_output` hook not being honored as expected
	 */
	function widget( $args, $instance ) {
		extract( $args );

		/** User-selected settings. */
		$title                = apply_filters( 'widget_title', $instance['title'] );
		$cat_choice           = $instance['cat_choice'];
		$display_children     = $instance['display_children'];
		$union                = $instance['union'];
		$use_current          = $instance['use_current'];
		$show_count           = $instance['show_count'];
		$offset               = $instance['offset'];
		$sort_order           = $instance['sort_order'];
		$use_thumbnails       = $instance['use_thumbnails'];
		$content_thumb        = $instance['content_thumb'];
		$excerpt_thumb        = $instance['excerpt_thumb'];
		$show_meta            = $instance['show_meta'];
		$show_comments        = $instance['show_comments'];
		$show_cats            = $instance['show_cats'];
		$show_cat_desc        = $instance['show_cat_desc'];
		$link_title           = $instance['link_title'];
		$show_tags            = $instance['show_tags'];
		$featured_image_first = $instance['featured_image_first'];
		$only_titles          = $instance['only_titles'];
		$no_titles            = $instance['no_titles'];
		$show_full            = $instance['show_full'];
		$excerpt_length       = $instance['excerpt_length'];
		$no_excerpt           = $instance['no_excerpt'];
		$use_meta_field       = $instance['use_meta_field'];
		$meta_field_name      = $instance['meta_field_name'];
		/** Plugin requires counter variable to be part of its arguments?! */
		$count = $instance['count'];

		/** @var    $before_widget  string - defined by theme */
		echo $before_widget;

		/**
		 * @var $cat_choice_class - CSS element created from category choices by removing whitespace and replacing commas with hyphens
		 */
		$cat_choice_class = preg_replace( '/\\040/', '', $cat_choice );
		$cat_choice_class = preg_replace( "/[,]/", "-", $cat_choice_class );

		/** Check if multiple categories have been chosen */
		if ( strpos( $cat_choice_class, '-' ) !== false ) {
			$multiple_cats = true;
		} else {
			$multiple_cats = false;
		}

		/** Widget $title, $before_widget, and $after_widget defined by theme */
		if ( $title ) {
			/**
			 * @var $before_title   string - defined by theme
			 * @var $after_title    string - defined by theme
			 */
			if ( ( true == $link_title ) && ( false == $multiple_cats ) ) {
				echo $before_title . '<span class="bnsfc-widget-title bnsfc-cat-class-' . $cat_choice_class . '"><a href="' . get_category_link( $cat_choice ) . '">' . $title . '</a></span>' . $after_title;
			} else {
				echo $before_title . '<span class="bnsfc-widget-title bnsfc-cat-class-' . $cat_choice_class . '">' . $title . '</span>' . $after_title;
			}
		}

		/** Display posts from widget settings */
		/**
		 * If viewing a page displaying a single post add the current post
		 * first category to category choices used by plugin
		 */
		if ( is_single() && $use_current ) {
			$cat_choices = wp_get_post_categories( get_the_ID() );
			$cat_choice  = $cat_choices[0];
		}

		/** @var array $query_args - holds query arguments to be passed */
		$query_args = array(
			'cat'            => $cat_choice,
			'posts_per_page' => $show_count,
			'offset'         => $offset,
		);

		/** Check for sort by meta field */
		if ( $use_meta_field ) {

			/** Merge the meta field args array with the existing query args array */
			$query_args = array_merge( $query_args, array(
				'orderby'  => 'meta_value',
				'meta_key' => $meta_field_name,
			) );

		}

		/** Alpha code - Use with extreme caution */
		/** Only display post from the child categories */
		if ( $display_children ) {

			/** @var array $child_categories - contains child-categories object */
			$child_categories = get_categories( array( 'parent' => $cat_choice ) );

			/** Sanity Check - Do we have child categories */
			if ( ! empty( $child_categories ) ) {

				/** Unset the current 'cat' parameter */
				unset( $query_args['cat'] );

				/** @var array $child_category_list - initialize as empty */
				$child_category_list = '';

				/**
				 * Cycle through the child categories object to get the category ID
				 * of each child category
				 */
				foreach ( $child_categories as $child_category ) {

					$child_category_list[] = $child_category->term_id;

				}

				/** @var array $query_args - re-assembled query arguments to use for child only option */
				$query_args = array_merge( $query_args, array( 'cat' => implode( ',', $child_category_list ) ) );

			}

		}

		/**
		 * Check if $sort_order is set to rand (random) or title_* use the
		 * `orderby` parameter; otherwise use the `order` parameter
		 */
		if ( 'rand' == $sort_order || 'title_az' == $sort_order || 'title_za' == $sort_order ) {

			/* If sorting by title make sure to set the `$sort_order` parameter correctly */
			if ( 'title_az' == $sort_order || 'title_za' == $sort_order ) {

				/* Before setting `$sort_order` to title set the A to Z order */
				if ( 'title_az' == $sort_order ) {
					$query_args = array_merge( $query_args, array( 'order' => 'asc' ) );
				}
				$sort_order = 'title';

			}

			/* Uses the rand option by default if title is not selected */
			$query_args = array_merge( $query_args, array( 'orderby' => $sort_order ) );

		} else {

			$query_args = array_merge( $query_args, array( 'order' => $sort_order ) );

		}

		/**
		 * Check if post need to be in *all* categories and make necessary
		 * changes to the data so it can be correctly used
		 */
		if ( $union ) {

			/** Remove the default use any category parameter */
			unset( $query_args['cat'] );

			/** @var string $cat_choice - category choices without spaces */
			$cat_choice = preg_replace( '/\s+/', '', $cat_choice );
			/** @var array $cat_choice_union - derived from the string */
			$cat_choice_union = explode( ",", $cat_choice );

			/** Sanity testing? - Change strings to integer values */
			foreach ( $cat_choice_union AS $index => $value ) {
				$cat_choice_union[ $index ] = (int) $value;
			}

			/** @var array $query_args - merged new query arguments */
			$query_args = array_merge( $query_args, array( 'category__and' => $cat_choice_union ) );

		}


		/** @var $bnsfc_query - object of posts matching query criteria */
		$bnsfc_query = false;
		/** Allow query to be completely over-written via `bnsfc_query` hook */
		apply_filters( 'bnsfc_query', $bnsfc_query );
		if ( false == $bnsfc_query ) {
			$bnsfc_query = new WP_Query( $query_args );
		}

		/** @var $bnsfc_output - hook test */
		$bnsfc_output = false;

		/** Allow entire output to be filtered via hook `bnsfc_output` */
		$bnsfc_output = apply_filters( 'bnsfc_output', $bnsfc_output );

		if ( false == $bnsfc_output ) {

			/** Wrapping CSS container element */
			echo '<div class="bnsfc-container">';

			/** Show the category description */
			if ( $show_cat_desc ) {
				echo '<div class="bnsfc-cat-desc">' . category_description() . '</div>';
			}

			/** Start the Loop */
			if ( $bnsfc_query->have_posts() ) {

				while ( $bnsfc_query->have_posts() ) {

					/** Get the post based on the query */
					$bnsfc_query->the_post();

					/** Check if post count has been met */
					if ( $count == $show_count ) {

						break;

					} else { ?>

						<div <?php post_class(); ?>>

							<?php if ( ! $only_titles && $featured_image_first ) { ?>

								<span class="bnsfc-featured-image-first">

									<?php /** Conditions: Theme supports post-thumbnails -and- there is a post-thumbnail -and- the option to show the post thumbnail is checked */
									if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail() && ( $use_thumbnails ) ) {
										?>
										<a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-featured-category' ); ?> <?php the_title_attribute(); ?>"><?php the_post_thumbnail(
												array(
													$excerpt_thumb,
													$excerpt_thumb
												) /*, array( 'class' => 'alignleft' ) */
											); ?>
										</a>
										<?php
									}
									?>

									<!-- Display the Post Title -->
									<strong>
										<a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-featured-category' ); ?> <?php the_title_attribute(); ?>">
											<?php the_title(); ?>
										</a>
									</strong>

								</span><!-- .bnsfc-featured-image-first -->

							<?php } ?>

							<?php if ( ! $no_titles & ! $featured_image_first ) { ?>
								<strong><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-featured-category' ); ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a></strong>
							<?php } ?>

							<div class="post-details">

								<?php /** Show Post Meta Data */
								if ( $show_meta ) {
									echo apply_filters( 'bnsfc_show_meta', sprintf( __( 'by %1$s on %2$s', 'bns-featured-category' ), get_the_author(), get_the_time( get_option( 'date_format' ) ) ) ); ?>
									<br />
									<?php
								}

								/** Show Comments */
								if ( ( $show_comments ) && ( ! post_password_required() ) ) {
									comments_popup_link( __( 'with No Comments', 'bns-featured-category' ), __( 'with 1 Comment', 'bns-featured-category' ), __( 'with % Comments', 'bns-featured-category' ), '', __( 'with Comments Closed', 'bns-featured-category' ) ); ?>
									<br />
								<?php }

								/** Show all categories */
								if ( $show_cats ) {
									echo apply_filters( 'bnsfc_show_cats', sprintf( __( 'in %s', 'bns-featured-category' ), get_the_category_list( ', ' ) ) ); ?>
									<br />
								<?php }

								/** Show all tags */
								if ( $show_tags ) {
									$show_all_tags = get_the_tag_list( __( 'as ', 'bns-featured-category' ), ', ', '' );
									echo apply_filters( 'bnsfc_show_tags', $show_all_tags );
									?>
									<br />
								<?php } ?>

							</div>
							<!-- .post-details -->

							<?php if ( ! $only_titles ) { ?>

								<div class="bnsfc-content">

									<?php /** Show full post */
									if ( $show_full ) {

										/** Conditions: Theme supports post-thumbnails -and- there is a post-thumbnail -and- the option to show the post thumbnail is checked */
										if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail() && ( $use_thumbnails ) && ! $featured_image_first ) {
											?>
											<a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-featured-category' ); ?> <?php the_title_attribute(); ?>"><?php the_post_thumbnail(
													array(
														$content_thumb,
														$content_thumb
													), array( 'class' => 'alignleft' )
												); ?></a>
											<?php
										}

										the_content(); ?>

										<div class="bnsfc-clear"></div>

										<?php wp_link_pages(
											array(
												'before'         => '<p><strong>' . __( 'Pages: ', 'bns-featured-category' ) . '</strong>',
												'after'          => '</p>',
												'next_or_number' => 'number'
											)
										);

									} /** Only show excerpt with custom length */
									elseif ( isset( $instance['excerpt_length'] ) && $instance['excerpt_length'] > 0 ) {

										if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail() && ( $use_thumbnails ) && ! $featured_image_first ) {
											?>
											<a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-featured-category' ); ?> <?php the_title_attribute(); ?>"><?php the_post_thumbnail(
													array(
														$excerpt_thumb,
														$excerpt_thumb
													), array( 'class' => 'alignleft' )
												); ?></a>
										<?php }

										echo wp_trim_words( get_the_content(), $instance['excerpt_length'], $this->excerpt_link() );

									} /** Show excerpt */
									elseif ( ! $instance['no_excerpt'] ) {

										if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail() && ( $use_thumbnails ) && ! $featured_image_first ) { ?>
											<a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-featured-category' ); ?> <?php the_title_attribute(); ?>"><?php the_post_thumbnail(
													array(
														$excerpt_thumb,
														$excerpt_thumb
													), array( 'class' => 'alignleft' )
												); ?></a>
										<?php }

										the_excerpt();

									} /** Just show the title */
									else {

										if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail() && ( $use_thumbnails ) && ! $featured_image_first ) { ?>
											<a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-featured-category' ); ?> <?php the_title_attribute(); ?>"><?php the_post_thumbnail(
													array(
														$content_thumb,
														$content_thumb
													), array( 'class' => 'alignleft' )
												); ?></a>
										<?php }

									} ?>

								</div><!-- .bnsfc-content -->

							<?php } ?>

						</div><!-- .post #post-ID -->

						<?php $count ++;

					}

				}

			} else {

				/** There are no posts. Leave a filterable message */
				apply_filters( 'bnsfc_no_posts_message', '<span class="bnsfc-no-posts-message">' . __( 'Yes, we have no bananas, or posts, today.', 'bns-featured-category' ) . '</span>' );

			}

			echo '</div><!-- bnsfc-container -->';

		}

		/** @var $after_widget string - defined by theme */
		echo $after_widget;

		/** Reset post data - see $bnsfc_query object */
		wp_reset_postdata();

	}


	/**
	 * Update
	 *
	 * @package    BNS_Featured_Image
	 * @since      1.0
	 *
	 * @param   array $new_instance
	 * @param   array $old_instance
	 *
	 * @return  array
	 *
	 * @version    2.8.2
	 * @date       March 5, 2016
	 * Added custom meta field search option
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/** Strip tags (if needed) and update the widget settings */
		$instance['title']                = strip_tags( $new_instance['title'] );
		$instance['cat_choice']           = strip_tags( $new_instance['cat_choice'] );
		$instance['display_children']     = $new_instance['display_children'];
		$instance['union']                = $new_instance['union'];
		$instance['use_current']          = $new_instance['use_current'];
		$instance['show_count']           = $new_instance['show_count'];
		$instance['offset']               = $new_instance['offset'];
		$instance['sort_order']           = $new_instance['sort_order'];
		$instance['use_thumbnails']       = $new_instance['use_thumbnails'];
		$instance['content_thumb']        = $new_instance['content_thumb'];
		$instance['excerpt_thumb']        = $new_instance['excerpt_thumb'];
		$instance['show_meta']            = $new_instance['show_meta'];
		$instance['show_comments']        = $new_instance['show_comments'];
		$instance['show_cats']            = $new_instance['show_cats'];
		$instance['show_cat_desc']        = $new_instance['show_cat_desc'];
		$instance['link_title']           = $new_instance['link_title'];
		$instance['show_tags']            = $new_instance['show_tags'];
		$instance['featured_image_first'] = $new_instance['featured_image_first'];
		$instance['only_titles']          = $new_instance['only_titles'];
		$instance['no_titles']            = $new_instance['no_titles'];
		$instance['show_full']            = $new_instance['show_full'];
		$instance['excerpt_length']       = $new_instance['excerpt_length'];
		$instance['no_excerpt']           = $new_instance['no_excerpt'];
		$instance['use_meta_field']       = $new_instance['use_meta_field'];
		$instance['meta_field_name']      = $new_instance['meta_field_name'];
		/** Added to reset count for every instance of the plugin */
		$instance['count'] = $new_instance['count'];

		return $instance;

	}


	/**
	 * Extend the `form` function
	 *
	 * @package    BNS_Featured_Category
	 * @since      1.0
	 *
	 * @param   $instance
	 *
	 * @uses       checked
	 * @uses       current_theme_supports
	 * @uses       get_field_id
	 * @uses       get_field_name
	 * @uses       selected
	 * @uses       wp_parse_args
	 *
	 * @return string|void
	 *
	 * @version    2.7
	 * @date       May 31, 2014
	 * Fixed sort order implementation
	 *
	 * @version    2.8
	 * @date       February 21, 2016
	 * Added Title (A to Z) and (Z to A) option to sort order
	 *
	 * @version    2.8.2
	 * @date       March 5, 2016
	 * Added custom meta field search option
	 */
	function form( $instance ) {
		/** Set default widget settings */
		$defaults = array(
			'title'                => __( 'Featured Category', 'bns-featured-category' ),
			'cat_choice'           => '1',
			'display_children'     => false,
			'union'                => false,
			'use_current'          => false,
			'count'                => '0',
			'show_count'           => '3',
			'offset'               => '0',
			'sort_order'           => 'desc',
			'use_thumbnails'       => true,
			'content_thumb'        => '100',
			'excerpt_thumb'        => '50',
			'show_meta'            => false,
			'show_comments'        => false,
			'show_cats'            => false,
			'show_cat_desc'        => false,
			'link_title'           => false,
			'show_tags'            => false,
			'featured_image_first' => false,
			'only_titles'          => false,
			'no_titles'            => false,
			'show_full'            => false,
			'excerpt_length'       => '',
			'no_excerpt'           => false,
			'use_meta_field'       => false,
			'meta_field_name'      => ''
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'bns-featured-category' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:95%;" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'cat_choice' ); ?>"><?php _e( 'Category IDs, separated by commas:', 'bns-featured-category' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'cat_choice' ); ?>" name="<?php echo $this->get_field_name( 'cat_choice' ); ?>" value="<?php echo $instance['cat_choice']; ?>" style="width:95%;" />
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['display_children'], true ); ?> id="<?php echo $this->get_field_id( 'display_children' ); ?>" name="<?php echo $this->get_field_name( 'display_children' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'display_children' ); ?>"><?php _e( "<strong>ONLY</strong> show posts that are from child categories?", 'bns-featured-category' ); ?></label>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['union'], true ); ?> id="<?php echo $this->get_field_id( 'union' ); ?>" name="<?php echo $this->get_field_name( 'union' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'union' ); ?>"><?php _e( "<strong>ONLY</strong> show posts that have <strong>ALL</strong> Categories?", 'bns-featured-category' ); ?></label>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_cat_desc'], true ); ?> id="<?php echo $this->get_field_id( 'show_cat_desc' ); ?>" name="<?php echo $this->get_field_name( 'show_cat_desc' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show_cat_desc' ); ?>"><?php _e( "Show first category's description?", 'bns-featured-category' ); ?></label>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['link_title'], true ); ?> id="<?php echo $this->get_field_id( 'link_title' ); ?>" name="<?php echo $this->get_field_name( 'link_title' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'link_title' ); ?>"><?php _e( 'Link widget title to category?<br /><strong>NB: Use a single category choice only!</strong>', 'bns-featured-category' ); ?></label>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['use_current'], true ); ?> id="<?php echo $this->get_field_id( 'use_current' ); ?>" name="<?php echo $this->get_field_name( 'use_current' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'use_current' ); ?>"><?php _e( 'Use current category in single view?', 'bns-featured-category' ); ?></label>
		</p>

		<table class="bnsfc-counts">
			<tr id="bnsfc-show-count-option">
				<td>
					<p>
						<label for="<?php echo $this->get_field_id( 'show_count' ); ?>"><?php _e( 'Posts to Display:', 'bns-featured-category' ); ?></label>
						<input id="<?php echo $this->get_field_id( 'show_count' ); ?>" name="<?php echo $this->get_field_name( 'show_count' ); ?>" value="<?php echo $instance['show_count']; ?>" style="width:85%;" />
					</p>
				</td>
				<td>
					<p class="bnsfc-offset-options">
						<label for="<?php echo $this->get_field_id( 'offset' ); ?>"><?php _e( 'Posts Offset:', 'bns-featured-category' ); ?></label>
						<input id="<?php echo $this->get_field_id( 'offset' ); ?>" name="<?php echo $this->get_field_name( 'offset' ); ?>" value="<?php echo $instance['offset']; ?>" style="width:85%;" />
					</p>
				</td>
			</tr>
			<tr id="bnsfc-meta-field-options">
				<td colspan="2">
					<?php _e( 'Sort Options:', 'bns-featured-category' ); ?>
					<hr />
					<p class="bnsfc-use-meta-field-checkbox">
						<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['use_meta_field'], true ); ?> id="<?php echo $this->get_field_id( 'use_meta_field' ); ?>" name="<?php echo $this->get_field_name( 'use_meta_field' ); ?>" />
						<?php $title_sort_options_toggle = ( checked( (bool) $instance['use_meta_field'], true, false ) ) ? 'disable' : 'enable'; ?>
						<label for="<?php echo $this->get_field_id( 'use_meta_field' ); ?>"><?php _e( ' Sort by custom meta data value (disables title sort options).', 'bns-featured-category' ); ?></label>
					</p>
					<p>
						<label for="<?php echo $this->get_field_id( 'meta_field_name' ); ?>"><?php _e( 'Meta data field name:', 'bns-featured-category' ); ?></label>
						<input id="<?php echo $this->get_field_id( 'meta_field_name' ); ?>" name="<?php echo $this->get_field_name( 'meta_field_name' ); ?>" value="<?php echo $instance['meta_field_name']; ?>" style="width:95%;" />
					</p>
				</td>
			</tr>
			<tr>
				<td>
					<p id="bnsfc-sort-options">
						<label for="<?php echo $this->get_field_id( 'sort_order' ); ?>"><?php _e( 'Sort Order:', 'bns-featured-category' ); ?></label>
						<select id="<?php echo $this->get_field_id( 'sort_order' ); ?>" name="<?php echo $this->get_field_name( 'sort_order' ); ?>" class="widefat">
							<option value="asc" <?php selected( 'asc', $instance['sort_order'], true ); ?>><?php _e( 'Ascending', 'bns-featured-category' ); ?></option>
							<option value="desc" <?php selected( 'desc', $instance['sort_order'], true ); ?>><?php _e( 'Descending', 'bns-featured-category' ); ?></option>
							<option value="rand" <?php selected( 'rand', $instance['sort_order'], true ); ?>><?php _e( 'Random', 'bns-featured-category' ); ?></option>
							<option value="title_az" <?php selected( 'title_az', $instance['sort_order'], true ); ?>><?php _e( 'Title (A to Z)', 'bns-featured-category' ); ?></option>
							<option value="title_za" <?php selected( 'title_za', $instance['sort_order'], true ); ?>><?php _e( 'Title (Z to A)', 'bns-featured-category' ); ?></option>
						</select>
					</p>
				</td>
			</tr>
		</table><!-- End table -->

		<hr />
		<!-- The following option choices may affect the widget option panel layout -->
		<p><?php _e( 'NB: Some options may not be available depending on which ones are selected.', 'bns-featured-category' ); ?></p>

		<p class="bnsfc-display-all-posts-check">
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['only_titles'], true ); ?> id="<?php echo $this->get_field_id( 'only_titles' ); ?>" name="<?php echo $this->get_field_name( 'only_titles' ); ?>" />
			<?php $all_options_toggle = ( checked( (bool) $instance['only_titles'], true, false ) ) ? 'closed' : 'open'; ?>
			<label for="<?php echo $this->get_field_id( 'only_titles' ); ?>"><?php _e( 'ONLY display Post Titles?', 'bns-featured-category' ); ?></label>
		</p>

		<p class="bnsfc-all-options-<?php echo $all_options_toggle; ?> bnsfc-no-titles">
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['no_titles'], true ); ?> id="<?php echo $this->get_field_id( 'no_titles' ); ?>" name="<?php echo $this->get_field_name( 'no_titles' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'no_titles' ); ?>"><?php _e( 'Do NOT display Post Titles?', 'bns-featured-category' ); ?></label>
		</p>

		<!-- If the theme supports post-thumbnails carry on; otherwise hide the thumbnails section -->
		<?php if ( ! current_theme_supports( 'post-thumbnails' ) ) {
			echo '<div class="bnsfc-thumbnails-closed">';
		} ?>

		<p class="bnsfc-all-options-<?php echo $all_options_toggle; ?> bnsfc-display-thumbnail-sizes"><!-- Hide all options below if ONLY post titles are to be displayed -->
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['use_thumbnails'], true ); ?> id="<?php echo $this->get_field_id( 'use_thumbnails' ); ?>" name="<?php echo $this->get_field_name( 'use_thumbnails' ); ?>" />
			<?php $thumbnails_toggle = ( checked( (bool) $instance['use_thumbnails'], true, false ) ) ? 'open' : 'closed'; ?>
			<label for="<?php echo $this->get_field_id( 'use_thumbnails' ); ?>"><?php _e( 'Use Featured Image Thumbnails?', 'bns-featured-category' ); ?></label>
		</p>

		<p class="bnsfc-all-options-<?php echo $all_options_toggle; ?> bnsfc-display-thumbnail-sizes"><!-- Hide all options below if ONLY post titles are to be displayed -->
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['featured_image_first'], true ); ?> id="<?php echo $this->get_field_id( 'featured_image_first' ); ?>" name="<?php echo $this->get_field_name( 'featured_image_first' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'featured_image_first' ); ?>"><?php _e( 'Display Featured Images before Title?', 'bns-featured-category' ); ?></label>
		</p>

		<table class="bnsfc-thumbnails-<?php echo $thumbnails_toggle; ?> bnsfc-all-options-<?php echo $all_options_toggle; ?>"><!-- Hide table if featured image / thumbnails are not used -->
			<tr>
				<td>
					<p>
						<label for="<?php echo $this->get_field_id( 'content_thumb' ); ?>"><?php _e( 'Content Thumbnail Size (in px):', 'bns-featured-category' ); ?></label>
						<input id="<?php echo $this->get_field_id( 'content_thumb' ); ?>" name="<?php echo $this->get_field_name( 'content_thumb' ); ?>" value="<?php echo $instance['content_thumb']; ?>" style="width:85%;" />
					</p>
				</td>
				<td>
					<p>
						<label for="<?php echo $this->get_field_id( 'excerpt_thumb' ); ?>"><?php _e( 'Excerpt Thumbnail Size (in px):', 'bns-featured-category' ); ?></label>
						<input id="<?php echo $this->get_field_id( 'excerpt_thumb' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_thumb' ); ?>" value="<?php echo $instance['excerpt_thumb']; ?>" style="width:85%;" />
					</p>
				</td>
			</tr>
		</table> <!-- End table -->

		<?php if ( ! current_theme_supports( 'post-thumbnails' ) ) {
			echo '</div><!-- bnsfc-thumbnails-closed -->';
		} ?>

		<!-- Carry on from here if there is no thumbnail support -->

		<p class="bnsfc-all-options-<?php echo $all_options_toggle; ?>">
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_meta'], true ); ?> id="<?php echo $this->get_field_id( 'show_meta' ); ?>" name="<?php echo $this->get_field_name( 'show_meta' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show_meta' ); ?>"><?php _e( 'Display Author Meta Details?', 'bns-featured-category' ); ?></label>
		</p>

		<p class="bnsfc-all-options-<?php echo $all_options_toggle; ?>">
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_comments'], true ); ?> id="<?php echo $this->get_field_id( 'show_comments' ); ?>" name="<?php echo $this->get_field_name( 'show_comments' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show_comments' ); ?>"><?php _e( 'Display Comment Totals?', 'bns-featured-category' ); ?></label>
		</p>

		<p class="bnsfc-all-options-<?php echo $all_options_toggle; ?>">
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_cats'], true ); ?> id="<?php echo $this->get_field_id( 'show_cats' ); ?>" name="<?php echo $this->get_field_name( 'show_cats' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show_cats' ); ?>"><?php _e( 'Display the Post Categories?', 'bns-featured-category' ); ?></label>
		</p>

		<p class="bnsfc-all-options-<?php echo $all_options_toggle; ?>">
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_tags'], true ); ?> id="<?php echo $this->get_field_id( 'show_tags' ); ?>" name="<?php echo $this->get_field_name( 'show_tags' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show_tags' ); ?>"><?php _e( 'Display the Post Tags?', 'bns-featured-category' ); ?></label>
		</p>

		<p class="bnsfc-all-options-<?php echo $all_options_toggle; ?> bnsfc-excerpt-option-open-check">
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_full'], true ); ?> id="<?php echo $this->get_field_id( 'show_full' ); ?>" name="<?php echo $this->get_field_name( 'show_full' ); ?>" />
			<?php $show_full_toggle = ( checked( (bool) $instance['show_full'], true, false ) ) ? 'closed' : 'open'; ?>
			<label for="<?php echo $this->get_field_id( 'show_full' ); ?>"><?php _e( 'Display entire Post?', 'bns-featured-category' ); ?></label>
		</p>

		<hr />
		<!-- Hide excerpt explanation and word count option if entire post is displayed -->
		<p class="bnsfc-all-options-<?php echo $all_options_toggle; ?> bnsfc-excerpt-option-<?php echo $show_full_toggle; ?>">
			<?php _e( 'The post excerpt is shown by default, if it exists; otherwise the first 55 words of the post are shown as the excerpt ...', 'bns-featured-category' ); ?>
		</p>

		<p class="bnsfc-all-options-<?php echo $all_options_toggle; ?> bnsfc-excerpt-option-<?php echo $show_full_toggle; ?>">
			<label for="<?php echo $this->get_field_id( 'excerpt_length' ); ?>"><?php _e( '... or set the amount of words you want to show:', 'bns-featured-category' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'excerpt_length' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_length' ); ?>" value="<?php echo $instance['excerpt_length']; ?>" style="width:95%;" />
		</p>

		<p class="bnsfc-all-options-<?php echo $all_options_toggle; ?> bnsfc-excerpt-option-<?php echo $show_full_toggle; ?>">
			<label for="<?php echo $this->get_field_id( 'no_excerpt' ); ?>"><?php _e( '... or have no excerpt at all!', 'bns-featured-category' ); ?></label>
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['no_excerpt'], true ); ?> id="<?php echo $this->get_field_id( 'no_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'no_excerpt' ); ?>" />
		</p>

	<?php }


	/**
	 * Enqueue Plugin Scripts and Styles
	 *
	 * Adds plugin stylesheet and allows for custom stylesheet to be added by end-user.
	 *
	 * @package    BNS_Featured_Category
	 * @since      1.9
	 *
	 * @uses       BNS_Featured_Category_Widget::plugin_data
	 * @uses       plugin_dir_path
	 * @uses       plugin_dir_url
	 * @uses       wp_enqueue_style
	 *
	 * @internal   Used with action: wp_enqueue_scripts
	 *
	 * @version    2.2
	 * @date       August 2, 2012
	 * Programmatically add version number to enqueue calls
	 *
	 * @version    2.6
	 * @date       March 10, 2014
	 * Extracted code to create plugin data method
	 *
	 * @version    2.7
	 * @date       April 20, 2014
	 * Added new enqueue statement to read from update safe folder
	 */
	function BNSFC_Scripts_and_Styles() {

		/** @var object $bnsfc_data - plugin header data */
		$bnsfc_data = $this->plugin_data();

		/** Enqueue Scripts */
		/** Enqueue Style Sheets */
		wp_enqueue_style( 'BNSFC-Style', plugin_dir_url( __FILE__ ) . 'bnsfc-style.css', array(), $bnsfc_data['Version'], 'screen' );

		/** Custom Stylesheets */
		if ( is_readable( plugin_dir_path( __FILE__ ) . 'bnsfc-custom-style.css' ) ) {
			wp_enqueue_style( 'BNSFC-Custom-Style', plugin_dir_url( __FILE__ ) . 'bnsfc-custom-style.css', array(), $bnsfc_data['Version'] . '-old', 'screen' );
		}

		/** Move to use generic folder for all "BNS" plugins to use */
		if ( is_readable( BNS_CUSTOM_PATH . 'bnsfc-custom-style.css' ) ) {
			wp_enqueue_style( 'BNSFC-Custom-Style', BNS_CUSTOM_URL . 'bnsfc-custom-style.css', array(), $bnsfc_data['Version'], 'screen' );
		}

	}


	/**
	 * Enqueue Options Plugin Scripts and Styles
	 *
	 * Add plugin options scripts and stylesheet(s) to be used only on the
	 * Administration Panels
	 *
	 * @package    BNS_Featured_Category
	 * @since      2.0
	 *
	 * @uses       BNS_Featured_Category_Widget::plugin_data
	 * @uses       plugin_dir_path
	 * @uses       plugin_dir_url
	 * @uses       wp_enqueue_script
	 * @uses       wp_enqueue_style
	 *
	 * @internal   Used with action: admin_enqueue_scripts
	 *
	 * @version    2.4
	 * @date       January 31, 2013
	 * Added dynamic version to enqueue parameters
	 *
	 * @version    2.6
	 * @date       March 10, 2014
	 * Extracted code to create plugin data method
	 */
	function BNSFC_Options_Scripts_and_Styles() {

		/** @var object $bnsfc_data - plugin header data */
		$bnsfc_data = $this->plugin_data();

		/** Enqueue Options Scripts; 'jQuery' is enqueued as a dependency */
		wp_enqueue_script( 'bnsfc-options', plugin_dir_url( __FILE__ ) . 'bnsfc-options.js', array( 'jquery' ), $bnsfc_data['Version'] );

		/** Enqueue Options Style Sheets */
		wp_enqueue_style( 'BNSFC-Option-Style', plugin_dir_url( __FILE__ ) . 'bnsfc-option-style.css', array(), $bnsfc_data['Version'], 'screen' );
		if ( is_readable( plugin_dir_path( __FILE__ ) . 'bnsfc-options-custom-style.css' ) ) {
			wp_enqueue_style( 'BNSFC-Options-Custom-Style', plugin_dir_url( __FILE__ ) . 'bnsfc-options-custom-style.css', array(), $bnsfc_data['Version'], 'screen' );
		}

	}


	/**
	 * Load BNSFC Widget
	 * Register widget to be used in the widget init hook
	 *
	 * @package BNS_Featured_Category
	 *
	 * @uses    register_widget
	 */
	function load_bnsfc_widget() {
		register_widget( 'BNS_Featured_Category' );
	}


	/**
	 * BNSFC Shortcode
	 * - May the Gods of programming protect us all!
	 *
	 * @package    BNS_Featured_Category
	 * @since      1.8
	 *
	 * @param   $atts
	 *
	 * @uses       shortcode_atts
	 * @uses       the_widget
	 *
	 * @internal   Do NOT set 'show_full=true' it will create a recursive loop and crash
	 * @internal   Note 'content_thumb' although available has no use if 'show_full=false'
	 * @internal   Used with `add_shortcode`
	 *
	 * @return  string
	 *
	 * @version    2.3
	 * @date       November 30, 2012
	 * Add option to use widget title as link to single category archive
	 * Optimize output buffer closure
	 *
	 * @version    2.4.3
	 * @date       September 7, 2013
	 * Added third parameter to `shortcode_atts` for automatic filter creation
	 *
	 * @version    2.7
	 * @date       April 19, 2014
	 * Added CSS wrapper class to separate style elements from widget usage
	 *
	 * @version    2.8.2
	 * @date       March 5, 2016
	 * Added custom meta field search option
	 *
	 * @todo       Fix 'show_full=true' issue
	 * @todo       Sort out "conflict" between title sorting and meta field sorting
	 */
	function bnsfc_shortcode( $atts ) {
		/** Get ready to capture the elusive widget output */
		ob_start();

		echo '<div class="bnsfc-shortcode">';

		the_widget(
			'BNS_Featured_Category',
			$instance = shortcode_atts(
				array(
					'title'                => __( 'Featured Category', 'bns-featured-category' ),
					'cat_choice'           => '1',
					'display_children'     => false,
					'union'                => false,
					'use_current'          => false,
					'count'                => '0',
					'show_count'           => '3',
					'offset'               => '0',
					'sort_order'           => 'DESC',
					'use_thumbnails'       => true,
					'content_thumb'        => '100',
					'excerpt_thumb'        => '50',
					'show_meta'            => false,
					'show_comments'        => false,
					'show_cats'            => false,
					'show_cat_desc'        => false,
					'link_title'           => false,
					'show_tags'            => false,
					'featured_image_first' => false,
					'only_titles'          => false,
					'no_titles'            => false,
					/** Do not set `show_full` to true!!! */
					'show_full'            => false,
					'excerpt_length'       => '',
					'no_excerpt'           => false,
					/** Not to be used with orderby title options */
					'use_meta_field'       => false,
					'meta_field_name'      => ''
				), $atts, 'bnsfc'
			),
			$args = array(
				/** clear variables defined by theme for widgets */
				$before_widget = '',
				$after_widget = '',
				$before_title = '',
				$after_title = '',
			)
		);

		echo '</div>';

		/** Get the_widget output and put into its own container */
		$bnsfc_content = ob_get_clean();

		return $bnsfc_content;

	}


	/**
	 * Plugin Data
	 * Returns the plugin header data as an array
	 *
	 * @package    BNS_Featured_Category
	 * @since      2.6
	 *
	 * @uses       get_plugin_data
	 *
	 * @return array
	 */
	function plugin_data() {
		/** Call the wp-admin plugin code */
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		/** @var $plugin_data - holds the plugin header data */
		$plugin_data = get_plugin_data( __FILE__ );

		return $plugin_data;
	}


	/**
	 * BNSFC Plugin Meta
	 * Adds additional links to plugin meta links
	 *
	 * @package    BNS_Featured_Category
	 * @since      2.4.4
	 *
	 * @uses       __
	 * @uses       plugin_basename
	 *
	 * @param   $links
	 * @param   $file
	 *
	 * @return  array $links
	 *
	 * @version    2.6
	 * @date       March 15, 2014
	 * Moved into main class of plugin
	 * Added a "wish link"
	 * Added a "support link"
	 */
	function bnsfc_plugin_meta( $links, $file ) {

		$plugin_file = plugin_basename( __FILE__ );

		if ( $file == $plugin_file ) {

			$links = array_merge(
				$links, array(
					'fork_link'    => '<a href="https://github.com/Cais/BNS-Featured-Category">' . __( 'Fork on GitHub', 'bns-featured-category' ) . '</a>',
					'wish_link'    => '<a href="http://www.amazon.ca/registry/wishlist/2NNNE1PAQIRUL">' . __( 'Grant a wish?', 'bns-featured-category' ) . '</a>',
					'support_link' => '<a href="http://wordpress.org/support/plugin/bns-featured-category">' . __( 'WordPress Support Forums', 'bns-featured-category' ) . '</a>'
				)
			);

		}

		return $links;

	}

	/**
	 * Excerpt Link
	 *
	 * Returns a filterable ellipsis and infinity character combination
	 *
	 * @package    BNS_Featured_Category
	 * @since      2.2
	 * @date       January 10, 2016
	 *
	 * @uses       __
	 * @uses       apply_filters
	 * @uses       get_permalink
	 * @uses       the_title_attribute
	 *
	 * @return string
	 */
	public function excerpt_link() {
		$bnsfc_link = '<a class="bnsfc-link" href="' . get_permalink() . '" title="' . the_title_attribute(
				array(
					'before' => __( 'Permalink to: ', 'bns-featured-category' ),
					'after'  => '',
					'echo'   => false
				)
			) . '">' . apply_filters( 'bnsfc_link', '&hellip; &infin;' ) . '</a>';

		return $bnsfc_link;
	}


	/**
	 * BNS Featured Category Update Message
	 *
	 * @package BNS_Featured_Category
	 * @since   2.7.1
	 *
	 * @uses    get_transient
	 * @uses    is_wp_error
	 * @uses    set_transient
	 * @uses    wp_kses_post
	 * @uses    wp_remote_get
	 *
	 * @param $args
	 */
	function bnsfc_in_plugin_update_message( $args ) {

		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		$bnsfc_data = get_plugin_data( __FILE__ );

		$transient_name = 'bnsfc_upgrade_notice_' . $args['Version'];
		if ( false === ( $upgrade_notice = get_transient( $transient_name ) ) ) {

			/** @var string $response - get the readme.txt file from WordPress */
			$response = wp_remote_get( 'https://plugins.svn.wordpress.org/bns-featured-category/trunk/readme.txt' );

			if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {
				$matches = null;
			}
			$regexp         = '~==\s*Changelog\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( $bnsfc_data['Version'] ) . '\s*=|$)~Uis';
			$upgrade_notice = '';

			if ( preg_match( $regexp, $response['body'], $matches ) ) {
				$version = trim( $matches[1] );
				$notices = (array) preg_split( '~[\r\n]+~', trim( $matches[2] ) );

				if ( version_compare( $bnsfc_data['Version'], $version, '<' ) ) {

					/** @var string $upgrade_notice - start building message (inline styles) */
					$upgrade_notice = '<style type="text/css">
							.bnsfc_plugin_upgrade_notice { padding-top: 20px; }
							.bnsfc_plugin_upgrade_notice ul { width: 50%; list-style: disc; margin-left: 20px; margin-top: 0; }
							.bnsfc_plugin_upgrade_notice li { margin: 0; }
						</style>';

					/** @var string $upgrade_notice - start building message (begin block) */
					$upgrade_notice .= '<div class="bnsfc_plugin_upgrade_notice">';

					$ul = false;

					foreach ( $notices as $index => $line ) {

						if ( preg_match( '~^=\s*(.*)\s*=$~i', $line ) ) {

							if ( $ul ) {
								$upgrade_notice .= '</ul><div style="clear: left;"></div>';
							}

							$upgrade_notice .= '<hr/>';
							continue;

						}

						/** @var string $return_value - body of message */
						$return_value = '';

						if ( preg_match( '~^\s*\*\s*~', $line ) ) {

							if ( ! $ul ) {
								$return_value = '<ul">';
								$ul           = true;
							}

							$line         = preg_replace( '~^\s*\*\s*~', '', htmlspecialchars( $line ) );
							$return_value .= '<li style=" ' . ( $index % 2 == 0 ? 'clear: left;' : '' ) . '">' . $line . '</li>';

						} else {

							if ( $ul ) {

								$return_value = '</ul><div style="clear: left;"></div>';
								$return_value .= '<p>' . $line . '</p>';
								$ul           = false;

							} else {

								$return_value .= '<p>' . $line . '</p>';

							}

						}

						$upgrade_notice .= wp_kses_post( preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $return_value ) );

					}

					$upgrade_notice .= '</div>';

				}

			}

			/** Set transient - minimize calls to WordPress */
			set_transient( $transient_name, $upgrade_notice, DAY_IN_SECONDS );

		}

		echo $upgrade_notice;

	}


}

/** @var $bnsfc - instantiate the class */
$bnsfc = new BNS_Featured_Category();