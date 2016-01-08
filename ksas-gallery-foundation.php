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

    public static function init(){
        remove_shortcode( 'gallery' ); // Remove the default gallery shortcode implementation
        add_shortcode( 'gallery', array( __CLASS__, "gallery_shortcode" ) ); // And replace it with our own!
    }

    /**
    * The Gallery shortcode.
    *
    * This has been taken verbatim from wp-includes/media.php and modified    */
    public static function gallery_shortcode($attr) {
            $post = get_post();

    static $instance = 0;
    $instance++;

    if ( ! empty( $attr['ids'] ) ) {
        // 'ids' is explicitly ordered, unless you specify otherwise.
        if ( empty( $attr['orderby'] ) ) {
            $attr['orderby'] = 'post__in';
        }
        $attr['include'] = $attr['ids'];
    }

    /**
     * Filter the default gallery shortcode output.
     *
     * If the filtered output isn't empty, it will be used instead of generating
     * the default gallery template.
     *
     * @since 2.5.0
     *
     * @see gallery_shortcode()
     *
     * @param string $output The gallery output. Default empty.
     * @param array  $attr   Attributes of the gallery shortcode.
     */
    $output = apply_filters( 'post_gallery', '', $attr );
    if ( $output != '' ) {
        return $output;
    }

        // NOTE: These are all the 'options' you can pass in through the shortcode definition, eg: [gallery itemtag='p']
        $html5 = current_theme_supports( 'html5', 'gallery' );
        $atts = shortcode_atts( array(
        'order'      => 'ASC',
        'orderby'    => 'menu_order ID',
        'id'         => $post ? $post->ID : 0,
        'itemtag'    => $html5 ? 'figure'     : 'dl',
        'icontag'    => $html5 ? 'div'        : 'dt',
        'captiontag' => $html5 ? 'figcaption' : 'dd',
        'columns'    => 3,
        'size'       => 'thumbnail',
        'include'    => '',
        'exclude'    => '',
        'link'       => ''
       ), $attr, 'gallery' );

        $id = intval( $atts['id'] );

    if ( ! empty( $atts['include'] ) ) {
        $_attachments = get_posts( array( 'include' => $atts['include'], 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );

        $attachments = array();
        foreach ( $_attachments as $key => $val ) {
            $attachments[$val->ID] = $_attachments[$key];
        }
    } elseif ( ! empty( $atts['exclude'] ) ) {
        $attachments = get_children( array( 'post_parent' => $id, 'exclude' => $atts['exclude'], 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );
    } else {
        $attachments = get_children( array( 'post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );
    }

    if ( empty( $attachments ) ) {
        return '';
    }

    if ( is_feed() ) {
        $output = "\n";
        foreach ( $attachments as $att_id => $attachment ) {
            $output .= wp_get_attachment_link( $att_id, $atts['size'], true ) . "\n";
        }
        return $output;
    }

    $itemtag = tag_escape( $atts['itemtag'] );
    $captiontag = tag_escape( $atts['captiontag'] );
    $icontag = tag_escape( $atts['icontag'] );
    $valid_tags = wp_kses_allowed_html( 'post' );
    if ( ! isset( $valid_tags[ $itemtag ] ) ) {
        $itemtag = 'dl';
    }
    if ( ! isset( $valid_tags[ $captiontag ] ) ) {
        $captiontag = 'dd';
    }
    if ( ! isset( $valid_tags[ $icontag ] ) ) {
        $icontag = 'dt';
    }

    $columns = intval( $atts['columns'] );
    $itemwidth = $columns > 0 ? floor(100/$columns) : 100;
    $float = is_rtl() ? 'right' : 'left';
        $selector = "gallery-{$instance}";
        $output .= "<div class='row'><figure class='gallery small-12 columns radius10'><ul id='$selector' class='clearing-thumbs' data-clearing>";
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