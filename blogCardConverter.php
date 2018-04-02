<?php
/**
 * @package BlogCardConverter
 * @version 0.0.2
 */
/*
Plugin Name: ブログカード変換(STINGER系⇔Cocoon系)
Plugin URI: http://www.naenote.net
Description: 投稿・固定ページ内で使われているブログカードを、STINGER系とCocoon系で相互に置換するプラグイン
Author: NAE
Version: 0.0.2
Author URI: http://www.naenote.net
*/

if(!function_exists('nae_conv_blogcard_w2c')):
function nae_conv_blogcard_w2c () {
    $articles = nae_get_articles();
    
    $pregs = [];
    foreach ($articles as $ar) {
        $pregs[] = [
            'regexp'=> '/\[st-card id=[”"\']?'.$ar->ID.'[”"\']?.*?\]/m',
            'replace' => get_permalink($ar->ID)
        ];
    }

    foreach($articles as $ar) {
        $content = $ar->post_content;

        foreach($pregs as $pr){
            $content = preg_replace($pr['regexp'], $pr['replace'], $content);
        }
        
        $my_post = [
            'ID'           => $ar->ID,
            'post_content' => $content
        ];
        $post_id = wp_update_post($my_post, true);
        if ( is_wp_error( $post_id ) ) {
            $errors = $post_id->get_error_messages();
            foreach ( $errors as $error ) {
                echo $error;
            }
        }
    }
    return "done!";
}
add_shortcode( "conv-blogcard-w2c", 'nae_conv_blogcard_w2c');
endif;

if(!function_exists('nae_conv_blogcard_c2w')):
function nae_conv_blogcard_c2w () {
    $articles = nae_get_articles();
    
    foreach ($articles as $ar) {
        $content = $ar->post_content;
        $res = preg_match_all('/^(<p>)?(<a[^>]+?>)?https?:\/\/'.preg_quote(get_the_site_domain()).'\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+(<\/a>)?(<\/p>)?/im', $content,$m);
        foreach ($m[0] as $match) {
            //マッチしたpタグが適正でないときはブログカード化しない
            if ( !is_p_tag_appropriate($match) ) {
                continue;
            }
            $url = strip_tags($match);//URL
            $id = url_to_postid($url);
            if($id==0) {
                continue;
            } else {
                $tag = '[st-card id='.$id.']';
                $content = preg_replace('{^'.preg_quote($match).'}im', $tag , $content, 1);
            }
        }

        $my_post = [
            'ID'           => $ar->ID,
            'post_content' => $content
        ];
        $post_id = wp_update_post($my_post, true);
        if ( is_wp_error( $post_id ) ) {
            $errors = $post_id->get_error_messages();
            foreach ( $errors as $error ) {
                echo $error;
            }
        }
    }
    return "done!";
}
add_shortcode( "conv-blogcard-c2w", 'nae_conv_blogcard_c2w');
endif;

if(!function_exists('nae_get_articles')):
function nae_get_articles () {
    $args_post = [
        'posts_per_page' => -1,
        'post_type'        => 'post',
        'post_status'      => 'publish'
    ];
    $args_page = [
        'posts_per_page' => -1,
        'post_type'        => 'page',
        'post_status'      => 'publish'
    ];
    $post_array = get_posts( $args_post );
    $page_array = get_pages( $args_page );
    
    $articles = array_merge($post_array, $page_array);
    return $articles;
}
endif;

// below here... thanks to @MrYhira

//ブログカード置換用テキストにpタグが含まれているかどうか
if ( !function_exists( 'is_p_tag_appropriate' ) ):
function is_p_tag_appropriate($match){
  if (strpos($match,'p>') !== false){
    //pタグが含まれていた場合は開始タグと終了タグが揃っているかどうか
    if ( (strpos($match,'<p>') !== false) && (strpos($match,'</p>') !== false) ) {
      return true;
    }
    return false;
  }
  return true;
}
endif;

//サイトのドメインを取得
if ( !function_exists( 'get_the_site_domain' ) ):
function get_the_site_domain(){
  // // //ドメイン情報を$results[1]に取得する
  // preg_match( '/https?:\/\/(.+?)\//i', admin_url(), $results );
  // return $results[1];
  return get_domain_name(home_url());
}
endif;

//URLからドメインを取得
if ( !function_exists( 'get_domain_name' ) ):
function get_domain_name($url){
  return parse_url($url, PHP_URL_HOST);
}
endif;
