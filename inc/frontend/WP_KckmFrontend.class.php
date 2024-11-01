<?php
/***********************************************
 フロントエンドの設定
/***********************************************/

// 直アクセスの防止
if (!class_exists('WP')) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

class WP_KckmFrontend {

    private $option_name;
    private $domain;
    private $plugin_slug;

    function __construct() {

    }
    
    /**
     * 初期化
     * @param str $option_name WPのオプション設定に保存する為の名前
     * @param str $doamin ドメイン
     */
    public function init($option_name, $domain, $plugin_slug) {
        $this->option_name = $option_name;
        $this->domain = $domain;
        $this->plugin_slug = $plugin_slug;

        // 記事内に評価を表示する
        add_filter('the_content', array($this, 'show_post_rating'));

        // コメント投稿フォームの文言変更
        add_filter('comment_form_defaults', array($this, 'modify_comment_title'));
        // コメント投稿フォームに枠を追加
        add_action('wp_footer', array($this, 'add_user_rating_fields'));
        // コメントの保存
        add_action('comment_post', array($this, 'save_userrating_fields'));

        // コメント表示に評価表示を付与
	    if (!is_admin()) {
	    	add_filter('comment_text', array($this, 'display_user_rating_in_comment'), 99);
	    	add_filter('thesis_comment_text', array($this, 'display_user_rating_in_comment'), 99);
	    }
    }

    /**
     * 記事内に評価を表示する
     */
    public function show_post_rating($content){
        global $wp, $post;

	    if (!in_the_loop () || !is_main_query ()) {
            return $content;
        }

        // 投稿ページではないなら終了
	    if (!is_single($post->ID) || is_page($post->ID)) return $content;


        // ==== 設定を取得
        $options = get_option($this->option_name);
	    $custom = get_post_custom($post->ID);

        $items = array();
        $items[] = array(
            'name' => isset($options['item_1']) ? $options['item_1']: null,
            'rating' => isset($custom['wp_kuchikomi_item_1'][0]) ? $custom['wp_kuchikomi_item_1'][0]: null,
            'hide' => isset($options['item_1_hide']) ? $options['item_1_hide']: null,
        );
        $items[] = array(
            'name' => isset($options['item_2']) ? $options['item_2']: null,
            'rating' => isset($custom['wp_kuchikomi_item_2'][0]) ? $custom['wp_kuchikomi_item_2'][0]: null,
            'hide' => isset($options['item_2_hide']) ? $options['item_2_hide']: null,
        );
        $items[] = array(
            'name' => isset($options['item_3']) ? $options['item_3']: null,
            'rating' => isset($custom['wp_kuchikomi_item_3'][0]) ? $custom['wp_kuchikomi_item_3'][0]: null,
            'hide' => isset($options['item_3_hide']) ? $options['item_3_hide']: null,
        );
        $position = isset($options['position']) ? $options['position']: 'top';

        $rating = isset($custom["wp_kuchikomi_rating"][0]) ? $custom["wp_kuchikomi_rating"][0] : null;

        // 総合評価がされてない場合は表示しない
        // FIXME: 設定項目につけるべき
        if(!$rating) return $content;

        // ==== 描画用HTMLの設定

        $html = "<div class='wp_kuchikomi_display_rating' itemtype='http://schema.org/Thing' itemscope='' itemprop='itemReviewed'>";
        $html .= '<meta content="'.get_the_title().'" itemprop="name">';

        // 個別評価
        foreach($items as $item) {
            if($item['hide'] == "1") continue;
            $html .= "<div>";
            $html .=    "<span style='display: inline-block; min-width: 150px;'>".$item['name']."</span>";
            $class_rate = str_replace('.', '', $item['rating']);
            $html .=    "<div style='display: inline-block;'>";
            $html .=        '<span class="rate-base rate-'.$class_rate.'"></span>';
            $html .=        '<span>('.$item['rating'].')</span>';
            $html .=    "</div>";
            $html .= "</div>";
        }
        
        // 総合評価
        $class_rate = str_replace('.', '', $rating);
        $html .= "<div>";
        $html .=    "<span style='display: inline-block; min-width: 150px;'><b>".__('Total Rating', $this->domain)."</b></span>";
        $html .=    "<div style='display: inline-block;'>";
        $html .=        '<div itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating">';
        $html .=           '<meta itemprop="worstRating" content="1" ></meta>';
        $html .=           '<meta itemprop="ratingValue" content="'.$rating.'" ></meta>';
        $html .=           '<meta itemprop="bestRating" content="5" ></meta>';
        $html .=           '<span class="rate-base rate-'.$class_rate.'"></span>';
        $html .=           '<span>('.$rating.')</span>';
        $html .=        "</div>";
        $html .=    "</div>";
        $html .= "</div>";

        // 表示位置の反映
        if($position == "top") {
            $content = $html.$content;
        } else {
            $content = $content.$html;
        }

        return $content;
    }

    /**
     * コメント投稿フォームの文言変更
     */
    public function modify_comment_title($arg) {
	    global $post;

        // 評価されてなければ拡張なし
        if (!WP_KckmUtils::is_review($post->ID)) return $arg;

        // 記事を書いたユーザーと一致しているなら拡張なし
	    if (WP_KckmUtils::user_is_author()) return $arg;

        // TODO: ユーザーに評価をさせない設定に場合
	    
        // 「コメントを残す」部分の文言変更
	    $arg['title_reply'] = __('Add Review:', $this->domain);
	    return $arg;
    }

    /**
     * コメント投稿フォームの拡張
     */
    public function add_user_rating_fields() {
        global $post;

        $id = get_the_ID();

        // 評価されてなければ拡張しない
        if (!WP_KckmUtils::is_review($post->ID)) return;

        // 記事を書いたユーザーと一致しているなら拡張しない
	    if (WP_KckmUtils::user_is_author()) return;

        // 初期化
        $options = get_option($this->option_name);
        wp_reset_query();

        // TODO: ユーザーに評価をさせない場合の設定

        $mypost = get_post($id);
        $custom = get_post_custom($mypost->ID);

        // コメント欄の状態チェック
        if ($mypost->comment_status != 'open') return;

        $field = '';

        // 個別項目
        $items = array();
        $items[1] = isset($options['item_1']) ? $options['item_1']: null;
        $items[2] = isset($options['item_2']) ? $options['item_2']: null;
        $items[3] = isset($options['item_3']) ? $options['item_3']: null;
        foreach($items as $key => $val) {
            if(isset($options['item_'.$key.'_hide']) && $options['item_'.$key.'_hide'] == "1") continue;
            $field .= "<div>";
            $field .= '<div style="display: inline-block; min-width: 180px;">'.$val.' : </div>';
            $field .= '<div style="display: inline-block;">';
            $field .=   '<select id="wp_kuchikomi_user_rating_item_'.$key.'" name="wp_kuchikomi_user_rating_item_'.$key.'">';
            $field .=       '<option value=></option>';
            $field .=       '<option value=1>1</option>';
            $field .=       '<option value=2>2</option>';
            $field .=       '<option value=3>3</option>';
            $field .=       '<option value=4>4</option>';
            $field .=       '<option value=5>5</option>';
            $field .=   '</select>';
            $field .= '</div>';
            $field .= "</div>";
        }

        // 総合評価
        $field .= "<div>";
        $field .= '<div style="display: inline-block; min-width: 180px;"><b>'.__('Total Rating', $this->domain).'</b> : </div>';
        $field .= '<div style="display: inline-block;">';
        $field .=   '<select id="wp_kuchikomi_user_rating" name="wp_kuchikomi_user_rating">';
        $field .=     '<option value=></option>';
        $field .=     '<option value=1>1</option>';
        $field .=     '<option value=2>2</option>';
        $field .=     '<option value=3>3</option>';
        $field .=     '<option value=4>4</option>';
        $field .=     '<option value=5>5</option>';
        $field .=   '</select>';
        $field .= '</div>';
        $field .= "</div>";

    
        // 復数回評価のチェック
        $is_rated = false;
        if($options['check_multiple'] == "ip_address") {

            // IPでのチェック
            $ip = WP_KckmUtils::getClientIp();

            // 承認済みコメントを取得して重複チェック
            $args = array(
                'post_id' => $mypost->ID,
                'status' => 'approve',
                'number' => '',
                'meta_key' => 'wp_kuchikomi_user_rating',
                'comment_author_ip' => $ip,
            );
		    $comments = get_comments($args);
            foreach($comments as $comment) {
			    $userrating = get_comment_meta($comment->comment_ID, 'wp_kuchikomi_user_rating', true);
                // 既に評価を入れているなら
                if($comment->comment_author_IP == $ip && $userrating != '') {
                    $is_rated = true;
                    break;
                }
            }
        } else if($options['check_multiple'] == "cookie") {
            
            // Cookieでのチェック
            $cookie_name = WP_Kckm::COOKIE_PREFIX."_is_rated_".$post->ID;
            if (isset($_COOKIE[$cookie_name])) {
                // 既に評価済み
                $is_rated = true;
            }
        }

        if($is_rated) {
            // 既に評価済みの場合
            $field = "<span>";
            $field .= __('You have already rated.', $this->domain);
            $field .= "</span>";

        }

        // footer部分にjavascript記述してコメント欄の上部にフォームを追加する
        ?>
        <script type="text/javascript">
	    jQuery(document).ready(function($) {
            if ($('form#commentform textarea[name=comment]').length > 0) {
	    		var commentField = $('form#commentform textarea[name=comment]');
	    		var parentTagName = $(commentField).parent().get(0).tagName;

                // コメント欄の上部にhtml挿入
	    		if (parentTagName == 'P' || parentTagName == 'DIV' || parentTagName == 'LI') {
	    		    $(commentField).parent().before('<'+parentTagName+' class="wp-kuchikomi-field"><?php echo $field; ?></'+parentTagName+'>');
	    		} else {
	    		   $(commentField).before('<?php echo $field; ?>');
	    		}
	        }
            // コメント部分の星表示
            $('#wp_kuchikomi_user_rating, #wp_kuchikomi_user_rating_item_1, #wp_kuchikomi_user_rating_item_2, #wp_kuchikomi_user_rating_item_3').barrating({
                theme: 'fontawesome-stars-o'
            });
	    });
        </script>
        <?php

    }

    /**
     * コメントの保存
     *
     * @param int $comment_id
     */
    public function save_userrating_fields($comment_id){
        global $wpdb;
    
        if (!isset($_POST['wp_kuchikomi_user_rating']) || $_POST['wp_kuchikomi_user_rating'] == 0) return;
    
        $comment = get_comment($comment_id);

        // 総合評価を更新
        add_comment_meta($comment_id, 'wp_kuchikomi_user_rating', $_POST[ 'wp_kuchikomi_user_rating' ] );
    
        // 個別評価を更新
        $keys = array(1,2,3);
        foreach($keys as $key) {
            add_comment_meta($comment_id, 'wp_kuchikomi_user_rating_item_'.$key, $_POST['wp_kuchikomi_user_rating_item_'.$key]);
        }
    
        // TODO: ユーザー評価の平均値を更新する
    
        // 頻繁な復数回答防止用にCOOKIEに評価情報を設定
        $cookie_name = WP_Kckm::COOKIE_PREFIX."_is_rated_".$comment->comment_post_ID;
        if (!isset($_COOKIE[$cookie_name])) {
            $userrating = $_POST['wp_kuchikomi_user_rating'];
            // FIXME: expireの値
    	    if($userrating) setcookie($cookie_name, $userrating, strtotime('+90 day'));
        }
    }

    /**
     * コメント表示に評価を表示
     * @param str $content
     */
    public function display_user_rating_in_comment($content) {
        global $post;

        // 評価情報の取得
        $comment_id = get_comment_ID();
        if(!$comment_id) {
            return $content;
        }
        $options = get_option($this->option_name);
		$userrating = get_comment_meta( $comment_id, 'wp_kuchikomi_user_rating', true);
        $userrating_item_1 = get_comment_meta( $comment_id, 'wp_kuchikomi_user_rating_item_1', true);
        $userrating_item_2 = get_comment_meta( $comment_id, 'wp_kuchikomi_user_rating_item_2', true);
        $userrating_item_3 = get_comment_meta( $comment_id, 'wp_kuchikomi_user_rating_item_3', true);
	    $custom = get_post_custom($post->ID);

        if(!$userrating) return $content;

        // TODO: 表示有無設定での切り替え

        $items = array();
        $items[] = array(
            'name' => isset($options['item_1']) ? $options['item_1']: null,
            'rating' => $userrating_item_1,
            'hide' => isset($options['item_1_hide']) ? $options['item_1_hide']: null,
        );
        $items[] = array(
            'name' => isset($options['item_2']) ? $options['item_2']: null,
            'rating' => $userrating_item_2,
            'hide' => isset($options['item_2_hide']) ? $options['item_2_hide']: null,
        );
        $items[] = array(
            'name' => isset($options['item_3']) ? $options['item_3']: null,
            'rating' => $userrating_item_3,
            'hide' => isset($options['item_3_hide']) ? $options['item_3_hide']: null,
        );

        $box = "<div class='wp_kuchikomi_userrating_in_comment'>";
        $box .= "<div class='wp_kuchikomi_display_rating'>";

        // 個別評価
        foreach($items as $item) {
            if($item['hide'] == "1") continue;
            $box .= "<div>";
            $box .= "<span style='display: inline-block; min-width: 150px;'>".$item['name']."</span>";
            $class_rate = str_replace('.', '', $item['rating']);
            $box .= '<span class="rate-base rate-'.$class_rate.'"></span>';
            $box .= "</div>";
        }

        // 総合評価
        $class_rate = str_replace('.', '', $userrating);
        $box .=     "<div>";
        $box .=        "<span style='display: inline-block; min-width: 150px;'><b>".__('Total Rating', $this->domain)."</b></span>";
        $box .=        '<span class="rate-base rate-'.$class_rate.'"></span>';
        $box .=     "</div>";

        $box .= "</div>";

        $box .= "<div class='wp_kuchikomi_display_comment'>";
        $box .=     $content;
        $box .= "</div>";
        $box .= "</div>";

        return $box;
    }

}

