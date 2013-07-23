<?php
/*
Plugin Name: Gallery Shortcode
Plugin URI: http://krieger.jhu.edu/communications/web/plugins
Description: Replaces default [gallery] shortcode to use Foundation's clearing javascript
Version: 1.0
Author: Cara Peckens
Author URI: mailto:cpeckens@jhu.edu
License: GPL2
*/
class foundation_clearing_gallery_replacement {
    function __construct(){
        // Hook on the plugins-loaded action since it's the first real action hook that's available to us.
        // However, if you're using a theme and want to replace that theme's `gallery` custom shortcode,
        // you may need to use another action. Search through your parent theme's files for 'gallery' and see
        // what hook it's using to define it's gallery shortcode, so you can make sure this code runs AFTER their code.
        add_action( "init", array( __CLASS__, "init" ) );
    }

    function init(){
        remove_shortcode( 'gallery' ); // Remove the default gallery shortcode implementation
        add_shortcode( 'gallery', array( __CLASS__, "gallery_shortcode" ) ); // And replace it with our own!
    }

    /**
    * The Gallery shortcode.
    *
    * This has been taken verbatim from wp-includes/media.php and modified    */
    function gallery_shortcode($attr) {
        global $post;

        static $instance = 0;
        $instance++;
        if ( ! empty( $attr['ids'] ) ) {
		// 'ids' is explicitly ordered, unless you specify otherwise.
		if ( empty( $attr['orderby'] ) )
			$attr['orderby'] = 'post__in';
		$attr['include'] = $attr['ids'];
	}

        $output = apply_filters('post_gallery', '', $attr);
        if ( $output != '' )
            return $output;

        if ( isset( $attr['orderby'] ) ) {
            $attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
            if ( !$attr['orderby'] )
                unset( $attr['orderby'] );
        }

        // NOTE: These are all the 'options' you can pass in through the shortcode definition, eg: [gallery itemtag='p']
        extract(shortcode_atts(array(
            'order'      => 'ASC',
            'orderby'    => 'menu_order ID',
            'id'         => $post->ID,
            'itemtag'    => 'li',
            'include'    => '',
            'exclude'    => '',
            // Here's the new options stuff we added to the shortcode defaults
        ), $attr));

        $id = intval($id);
        if ( 'RAND' == $order )
            $orderby = 'none';

        if ( !empty($include) ) {
            $include = preg_replace( '/[^0-9,]+/', '', $include );
            $_attachments = get_posts( array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );

            $attachments = array();
            foreach ( $_attachments as $key => $val ) {
                $attachments[$val->ID] = $_attachments[$key];
            }
        } elseif ( !empty($exclude) ) {
            $exclude = preg_replace( '/[^0-9,]+/', '', $exclude );
            $attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
        } else {
            $attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
        }

        if ( empty($attachments) )
            return '';

        if ( is_feed() ) {
            $output = "\n";
            foreach ( $attachments as $att_id => $attachment )
                $output .= wp_get_attachment_link($att_id, $size, true) . "\n";
            return $output;
        }

        $selector = "gallery-{$instance}";
        $output .= "<div class='row'><figure class='gallery twelve columns radius10'><ul id='$selector' class='clearing-thumbs' data-clearing>";
        $i = 0;
        foreach ( $attachments as $id => $attachment ) {
            
            $thumbnail = wp_get_attachment_image_src($id, 'thumbnail', false, false);
            $full_image = wp_get_attachment_image_src($id, 'full', false, false);
            $caption = wptexturize($attachment->post_excerpt);

            $output .= "<li class='gallery-item'>";
            $output .= "<a href='{$full_image[0]}'>";
            $output .= "<img src='{$thumbnail[0]}' data-caption='{$caption}' />";
            $output .= "</a>" ;
            $output .= "</li>";
        }

        $output .= "
            </ul></figure></div>\n";

        return $output;
    }
}

new foundation_clearing_gallery_replacement();