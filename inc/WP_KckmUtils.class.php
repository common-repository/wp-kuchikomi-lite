<?php
/***********************************************
 Utils
/***********************************************/

class WP_KckmUtils {


    /**
     * レビュー済み状態かチェック
     *
     * @param int $id post_id
     * @return boolean
     */
    public static function is_review($id) {
        return get_post_meta($id, "wp_kuchikomi_rating", TRUE) == null ? false : true;
    }

    /**
     * 記事閲覧ユーザーが著者かどうかチェック
     * 
     * @return boolean
     */
    public static function user_is_author() {
        global $post;	
        $current_user = wp_get_current_user();
        return $post->post_author == $current_user->ID ? true :false;
    }

    /**
     * IPの取得
     */
    public static function getClientIp() {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        return esc_attr($ip_address);
    }
}

