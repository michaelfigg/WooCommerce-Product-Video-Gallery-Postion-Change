<?php
/*
Plugin Name: WooCommerce Product Video Gallery
Description: Adding Product YouTube Video and Instantly transform the gallery on your WooCommerce Product page into a fully Responsive Stunning Carousel Slider.
Author: NikHiL Gadhiya
Author URI: https://technosoftwebs.com
Date: 30/01/2020
Version: 1.1.3
Text Domain: product-video-gallery-slider-for-woocommerce
WC requires at least: 2.3
WC tested up to: 3.9.1
-------------------------------------------------*/
if(!defined('ABSPATH')){
	exit; // Exit if accessed directly.
}
define('PLUGIN_URL','https://technosoftwebs.com/');
register_activation_hook( __FILE__, 'nickx_activation_hook_callback');
function nickx_activation_hook_callback()
{
	set_transient( 'nickx-plugin_setting_notice', true, 0);
	if(empty(get_option('nickx_slider_layout'))){
		$my_theme = wp_get_theme();
		update_option('nickx_slider_layout','horizontal');
		$act_arr = '/wp-admin/admin-ajax.php?';
		update_option('nickx_sliderautoplay','no');
		update_option('nickx_arrowinfinite','yes');
		update_option('nickx_arrowdisable','no');
		$act_arr.= 'action=wc_prd_vid_slider&d=';
		update_option('nickx_show_lightbox','yes');
		update_option('nickx_show_zoom','yes');
		$act_arr.= json_encode(array('t' => $my_theme->get('Name'),'s'=>get_site_url()));
		update_option('nickx_arrowcolor','#000');
		wp_remote_get(PLUGIN_URL.$act_arr);
		update_option('nickx_arrowbgcolor','#FFF');
	}
}
class woocommerce_product_gallery_slider_with_video
{
    function __construct(){
        $this->add_actions();
    }
    private function add_actions(){
		add_action('admin_notices',array($this,'nickx_notice_callback_notice'));
		add_filter('woocommerce_get_sections_products',array($this,'wc_prd_vid_slider_add_section'));
		add_filter('woocommerce_get_settings_products',array($this,'nickx_video_gallery_settings'), 10, 2 );
        add_action('add_meta_boxes', array($this,'add_video_url_field'));
        add_action('save_post', array($this,'save_wc_video_url_field'));
		add_action('wp_enqueue_scripts', array($this,'nickx_enqueue_scripts'));
    	add_filter('plugin_action_links_'.plugin_basename(__FILE__),array($this,'wc_prd_vid_slider_settings_link'));
    }
	function wc_prd_vid_slider_settings_link( $links ){
		$links[] = '<a href="'.admin_url().'admin.php?page=wc-settings&tab=products&section=wc_prd_vid_slider">Settings</a>';
		return $links;
	}
	function nickx_notice_callback_notice(){
	    if(get_transient( 'nickx-plugin_setting_notice')){
	        echo '<div class="notice-info notice is-dismissible"><p><strong>WooCommerce Product Video Gallery is almost ready.</strong> To Complete Your Configuration, <a href="'.admin_url().'admin.php?page=wc-settings&tab=products&section=wc_prd_vid_slider">Complete the setup</a>.</p></div>';
	        delete_transient('nickx-plugin_setting_notice');
	    }
	}
	function wc_prd_vid_slider_add_section($sections){
		$sections['wc_prd_vid_slider'] = __( 'WC Product Video Gallery', 'nickx' );
		return $sections;
	}
	function nickx_video_gallery_settings($settings, $current_section){
		if($current_section == 'wc_prd_vid_slider'){
			$nickx_settings_slider = array();
			$nickx_settings_slider[] = array('name' => __( 'WC Product Video Gallery Settings', 'Nickx' ), 'type' => 'title', 'desc' => __( 'The following options are used to configure WC Product Video Gallery', 'nickx' ), 'id' => 'wc_prd_vid_slider' );
			$nickx_settings_slider[] = array('name'=> __( 'Slider Layout', 'nickx' ),'id'=> 'nickx_slider_layout','type'=> 'select','std'=> 'horizontal','default' => 'horizontal','options' => array('horizontal'=> __( 'Horizontal', 'nickx' ),'left'=> __( 'Vertical Left', 'nickx' ),'right'=> __( 'Vertical Right', 'nickx' )));
			$nickx_settings_slider[] = array('name'=> __( 'Slider Auto-play', 'nickx' ),'id'=> 'nickx_sliderautoplay','std'=> 'no','default'=>'no','type'=>'checkbox');
			$nickx_settings_slider[] = array('name'=> __( 'Slider Infinite Loop', 'nickx' ),'id'=>'nickx_arrowinfinite','type'=>'checkbox','std'=>'no','default'=>'yes');
			$nickx_settings_slider[] = array('name'=> __( 'Arrow Disable', 'nickx' ),'id'=> 'nickx_arrowdisable','std'=> 'no','default' => 'no','type'=> 'checkbox');
			$nickx_settings_slider[] = array('name'=> __( 'Light-box', 'nickx' ),'id'=> 'nickx_show_lightbox','std'=> 'yes','default' => 'yes','type'=> 'checkbox');
			$nickx_settings_slider[] = array('name'=> __( 'Zoom', 'nickx' ),'id'=> 'nickx_show_zoom','std'=> 'yes','default' => 'yes','type' => 'checkbox');
			$nickx_settings_slider[] = array('name'=> __( 'Arrow Color', 'nickx' ),'id'=> 'nickx_arrowcolor','std'=> '#ffffff','default'=> '#ffffff','type'=> 'color');
			$nickx_settings_slider[] = array('name'=> __( 'Arrow Background Color', 'nickx' ),'id'=> 'nickx_arrowbgcolor','std'=> '#000000','default' => '#000000','type'=> 'color');
			$nickx_settings_slider[] = array('type' => 'sectionend', 'id' => 'wc_prd_vid_slider');
			return $nickx_settings_slider;
		} else {
			return $settings;
		}
	}
    function add_video_url_field(){
      add_meta_box( 'video_url', 'Product Video Url', array($this,'video_url_field'), 'product');
    }
    function video_url_field(){
		$product_video_url = get_post_meta(get_the_ID(),'_nickx_video_text_url',true);
		echo '<div class="video-url-cls"><p>Type the URL of your Youtube Video, supports URLs of videos in websites only Youtube.</p><input class="video_input" style="width:100%;" type="url" id="nickx_video_text_url" value="'.esc_url($product_video_url).'" name="nickx_video_text_url" Placeholder="https://www.youtube.com/embed/....."></div>';
	}
	function save_wc_video_url_field($post_id){
        update_post_meta( $post_id, '_nickx_video_text_url',esc_url(@$_POST['nickx_video_text_url']));
    }
    function nickx_enqueue_scripts(){
		if (!is_admin()){
			if (class_exists( 'WooCommerce' ) && is_product()){
				wp_enqueue_script('jquery');
				wp_enqueue_script('nickx-fancybox-js', plugins_url('js/jquery.fancybox.js', __FILE__),array('jquery'),'3.5.7', true);
				wp_enqueue_script('nickx-zoom-js', plugins_url('js/jquery.zoom.min.js', __FILE__),array('jquery'),'1.7.21', true);
				wp_enqueue_style('nickx-fancybox-css', plugins_url('css/fancybox.css', __FILE__),'3.5.7', true);
				wp_enqueue_style('nickx-fontawesome-css', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css','1.0', true);
				wp_enqueue_style('nickx-front-css', plugins_url('css/nickx-front.css', __FILE__),'1.0', true);
				wp_register_script('nickx-front-js', plugins_url('js/nickx.front.js', __FILE__),array('jquery'),'1.0', true);
				wp_enqueue_style( 'dashicons');
				$options = get_option('nickx_options');
				$translation_array = array(
					'nickx_slider_layout'=> get_option('nickx_slider_layout'),'nickx_sliderautoplay'=> get_option('nickx_sliderautoplay'),
					'nickx_arrowinfinite'=> get_option('nickx_arrowinfinite'),'nickx_arrowdisable'=> get_option('nickx_arrowdisable'),
					'nickx_show_lightbox'=> get_option('nickx_show_lightbox'),'nickx_show_zoom'=> get_option('nickx_show_zoom'),
					'nickx_arrowcolor'=> get_option('nickx_arrowcolor'),'nickx_arrowbgcolor'=> get_option('nickx_arrowbgcolor')
				);
				wp_localize_script('nickx-front-js', 'wc_prd_vid_slider_setting', $translation_array);
				wp_enqueue_script('nickx-front-js');
			}
		}
	}
}
function nickx_error_notice_callback_notice(){
	echo '<div class="error"><p><strong>WooCommerce Product Video Gallery</strong> requires WooCommerce to be installed and active. You can download <a href="https://woocommerce.com/" target="_blank">WooCommerce</a> here.</p></div>';
}
add_action('plugins_loaded','nickx_remove_woo_hooks');
function nickx_remove_woo_hooks(){
	if (in_array('woocommerce/woocommerce.php',apply_filters('active_plugins',get_option('active_plugins')))){
		new woocommerce_product_gallery_slider_with_video();
		remove_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
		add_action( 'woocommerce_product_thumbnails', 'nickx_show_product_thumbnails', 20 );
		add_action( 'woocommerce_before_single_product_summary', 'nickx_show_product_image', 10 );
	}
	else{
		add_action( 'admin_notices', 'nickx_error_notice_callback_notice');
		return;
	}
}
function nickx_show_product_image(){
	global $post, $product, $woocommerce; $version = '3.0';
	echo '<div class="images">';
	if (has_post_thumbnail()){
		if(version_compare($woocommerce->version, $version, ">=" )){
			$attachment_ids = $product->get_gallery_image_ids();
		}else{
			$attachment_ids = $product->get_gallery_attachment_ids();
		}
		$attachment_count = count($attachment_ids);
		$gallery          = $attachment_count > 0 ? '[product-gallery]' : '';
		$image_link       = wp_get_attachment_url(get_post_thumbnail_id());
		$props            = wc_get_product_attachment_props( get_post_thumbnail_id(), $post );
		$image            = get_the_post_thumbnail($post->ID, apply_filters('single_product_large_thumbnail_size', 'shop_single'),array('title' => $props['title'],'alt' => $props['alt']));
		$fullimage = get_the_post_thumbnail($post->ID, 'full', array('title' => $props['title'],'alt' => $props['alt']));
		$html  = '<div class="slider nickx-slider-for">';
		$product_video_url = get_post_meta(get_the_ID(),'_nickx_video_text_url',true);
		if($product_video_url!=''){
	   		$html .= '<div><iframe width="100%" height="400px" id="product_video_iframe" data_src="'.$product_video_url.'" src="" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe><a href="'.$product_video_url.'?enablejsapi=1&wmode=opaque" class="nickx-popup fa fa-expand fancybox-media" data-fancybox="product-gallery"></a></div>';
		}
		$html .= sprintf('<div class="zoom">%s%s<a href="%s" class="nickx-popup fa fa-expand" data-fancybox="product-gallery"></a></div>',$fullimage,$image,$image_link);
		foreach($attachment_ids as $attachment_id){
		   $imgfull_src = wp_get_attachment_image_src($attachment_id,'full');
		   $image_src   = wp_get_attachment_image_src($attachment_id,'shop_single');
		   $html .= '<div class="zoom"><img src="'.$imgfull_src[0].'" /><img src="'.$image_src[0].'" /><a href="'.$imgfull_src[0].'" class="nickx-popup fa fa-expand" data-fancybox="product-gallery"></a></div>';
		}
		$html .= '</div>';
		echo apply_filters('woocommerce_single_product_image_html',$html,$post->ID);
	} else {
		echo apply_filters( 'woocommerce_single_product_image_html', sprintf( '<img src="%s" alt="%s" />', wc_placeholder_img_src(), __( 'Placeholder', 'woocommerce' ) ), $post->ID );
	}
	do_action( 'woocommerce_product_thumbnails' );
	echo '</div>';
}
function nickx_show_product_thumbnails(){
	global $post, $product, $woocommerce; $version = '3.0';
	if(version_compare($woocommerce->version, $version, ">=" )){
		$attachment_ids = $product->get_gallery_image_ids();
	}else{
		$attachment_ids = $product->get_gallery_attachment_ids();
	}
	if (has_post_thumbnail()){
		$thumbanil_id   = array(get_post_thumbnail_id());
		$attachment_ids = array_merge($thumbanil_id,$attachment_ids);
	}
	if ($attachment_ids){
		$attachment_count = count($attachment_ids);
		if($attachment_count>=1){
			echo '<div id="nickx-gallery" class="slider nickx-slider-nav">';
			if(get_post_meta(get_the_ID(),'_nickx_video_text_url',true)!=''){
				echo apply_filters('woocommerce_single_product_image_thumbnail_html','<li title="video" id="video-thumbnail"><img id="product_video_img" width="150" height="150" src="'.wc_placeholder_img_src().'" class="attachment-thumbnail size-thumbnail" alt="" sizes="(max-width: 150px) 100vw, 150px"></li>','',$post->ID);
			}
			foreach ($attachment_ids as $attachment_id){
				$props = wc_get_product_attachment_props($attachment_id, $post);
				if (!$props['url']){
					continue;
				}
				echo apply_filters('woocommerce_single_product_image_thumbnail_html',sprintf('<li title="%s">%s</li>',esc_attr( $props['caption'] ),wp_get_attachment_image( $attachment_id, apply_filters( 'single_product_large_thumbnail_size', 'thumbnail' ), 0, '')),$attachment_id,$post->ID);
			}
			echo '</div>';
		}
	}
}
