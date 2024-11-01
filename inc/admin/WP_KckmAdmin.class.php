<?php
/***********************************************
 管理画面での設定
/***********************************************/

// 直アクセスの防止
if (!class_exists('WP')) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

class WP_KckmAdmin {

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

        add_action('admin_init', array($this, 'settings'));
        // メニューの追加
        add_action('init', array($this, 'addmenu'));

        add_filter('manage_edit-post_columns', array($this, 'add_post_header_columns'), 10, 1);
        add_action('manage_posts_custom_column', array($this, 'add_post_data_row'), 10, 2);


        // プラグインページ限定
        if(isset($_GET['page']) && WP_Kckm::PLUGIN_SLUG === $_GET['page']) {
            // フッターロゴの表示
            add_filter("admin_footer_text", array($this, 'footer'));
        }

    }

    /**
     * 設定
     */
    public function settings(){
        register_setting($this->option_name, $this->option_name, array($this, 'options_sanitize'));
    }

    /**
     * 設定保存前のコールバック用
     */
    public function options_sanitize($input) {
        if(isset($_POST['reset'])){
		    delete_option($this->option_name);
	    }
	    return $input;
    }

    /**
     * 管理画面に設定項目の追加
     */
    public function addmenu() {
        add_action('admin_menu', array($this, 'addmenu_link'));
        add_action('admin_menu', array($this, 'add_box'));
        add_action('save_post',  array($this, 'save_postmeta_data'));
    }

    /**
     * プラグイン設定画面へのメニュー追加
     */
    public function addmenu_link() {
        if ( is_admin() && current_user_can('manage_options') ) {
            // TODO: リンクのプラグインアイコン画像
            add_menu_page(	__(WP_Kckm::PLUGIN_NAME, $this->domain),
							__('Kuchikomi', $this->domain),
							'manage_options', 
							$this->plugin_slug,
							array($this, 'show_setting_page'),
                            plugins_url()."/".WP_Kckm::PLUGIN_DOMAIN."/assets/img/menu_logo.png",
                            null
		    );
			add_submenu_page($this->plugin_slug, 
							__('Settings', $this->domain), 
							__('Settings', $this->domain),
							'manage_options', 
							$this->plugin_slug,
							array($this, 'show_setting_page')
            );
            /*
			add_submenu_page($this->plugin_slug, 
							__('Help', $this->domain), 
							__('Help', $this->domain),
							'manage_options', 
							$this->plugin_slug.'-help', 
							array($this, 'show_help_page')
            );
            */
        }
    }

    /**
     * プラグインページのフッターロゴ
     */
    public function footer() {
        $url = WP_Kckm::COMPANY_URL;
        return "<span>口コミ機能Lite is powered by </span><a href='".$url."' target='_blank'><img src='".plugins_url()."/".WP_Kckm::PLUGIN_DOMAIN."/assets/img/footer_logo.png' alt='side7' width='25px'/></a>";
    }

    /**
     * プラグイン設定画面の描画
     */
    public function show_setting_page() {


        // 設定を取得
        $options = get_option($this->option_name);

        // 投稿タイプを取得
        $post_types_default = array("post", "page");
        $post_types_custom = get_post_types( array(
            'public'   => true,
            '_builtin' => false
        ));
        $post_types = array_merge($post_types_default, $post_types_custom);

        // ==== プラグイン設定画面の描画
        // FIXME: 項目数が多くなってきたらテンプレート形式やフォーム生成するメソッドにデータ突っ込む形とか管理しやすい形式に変える
        ob_start();
    ?>
<div>
    <h2><?php _e(WP_Kckm::PLUGIN_NAME, $this->domain); ?>&nbsp;<?php _e('Settings', $this->domain); ?>&nbsp;<span style="font-size: 10px;"><?php echo 'ver '.WP_Kckm::VERSION; ?></span></h2>
</div> 
<form method="post" action="options.php">
    <?php
        settings_fields($this->option_name);
        settings_errors();
    ?>

  <table class="form-table" style="width: auto;">
    <tr>
      <th><label for="item_1"><?php _e('Item', $this->domain); ?>1</label></th>
      <td>
        <input  id="wp_kuchikomi_options[item_1]" 
                class="regular-text" 
                type="text"
                name="wp_kuchikomi_options[item_1]" 
                value="<?php echo str_replace(" ", "", $options['item_1']); ?>">
	  </td>
      <td>
        <label for="wp_kuchikomi_options[item_1_hide]"><?php _e('hide', $this->domain); ?></label>
        <input  id="wp_kuchikomi_options[item_1_hide]"
                type="checkbox"
                name="wp_kuchikomi_options[item_1_hide]"
                value="1" <?php echo isset($options['item_1_hide']) && $options['item_1_hide'] == "1" ? "checked": ""; ?>>
      </td>
    </tr>
    <tr>
      <th><label for="item_2"><?php _e('Item', $this->domain); ?>2</label></th>
      <td>
        <input  id="wp_kuchikomi_options[item_2]" 
                class="regular-text" 
                type="text"
                name="wp_kuchikomi_options[item_2]" 
                value="<?php echo str_replace(" ", "", $options['item_2']); ?>">
	  </td>
      <td>
        <label for="wp_kuchikomi_options[item_2_hide]"><?php _e('hide', $this->domain); ?></label>
        <input  id="wp_kuchikomi_options[item_2_hide]"
                type="checkbox"
                name="wp_kuchikomi_options[item_2_hide]"
                value="1" <?php echo isset($options['item_2_hide']) && $options['item_2_hide'] == "1" ? "checked": ""; ?>>
      </td>
    </tr>
    <tr>
      <th><label for="item_3"><?php _e('Item', $this->domain); ?>3</label></th>
      <td>
        <input  id="wp_kuchikomi_options[item_3]" 
                class="regular-text" 
                type="text"
                name="wp_kuchikomi_options[item_3]" 
                value="<?php echo str_replace(" ", "", $options['item_3']); ?>">
	  </td>
      <td>
        <label for="wp_kuchikomi_options[item_3_hide]"><?php _e('hide', $this->domain); ?></label>
        <input  id="wp_kuchikomi_options[item_3_hide]"
                type="checkbox"
                name="wp_kuchikomi_options[item_3_hide]"
                value="1" <?php echo isset($options['item_3_hide']) && $options['item_3_hide'] == "1" ? "checked": ""; ?>>
      </td>
    </tr>
    <tr>
        <th><label for="wp_kuchikomi_options[post_types]"><?php _e('Post Types', $this->domain); ?></label></th>
        <td>
        <?php
        foreach($post_types as $post_type) {
            echo '<input id="wp_kuchikomi_options[post_types]" type="checkbox" value="'.$post_type.'" disabled ';
            echo ($post_type == "post" ? 'checked' : '')."/>";
            echo '<span style="margin-right: 10px;">'.$post_type.'</span>';
        }
        echo '<br/>';
        echo '<p>Lite版では対応投稿タイプは"投稿"のみです</p>';
        ?>
        </td>
    </tr>
    <tr>
        <th><label for="wp_kuchikomi_options[check_multiple]"><?php _e('Check Multiple Ratings', $this->domain); ?></label></th>
        <td>
            <select id="wp_kuchikomi_options[check_multiple]" name="wp_kuchikomi_options[check_multiple]">
                <option value=""></option>
                <option value="ip_address" <?php echo isset($options['check_multiple']) && $options['check_multiple'] == 'ip_address' ? 'selected':''; ?> >IP Address</option>
                <option value="cookie" <?php echo isset($options['check_multiple']) && $options['check_multiple'] == 'cookie' ? 'selected':''; ?>>Cookie</option>
            </select>
        </td>
    </tr>
    <tr>
      <th><label for="position"><?php _e('Position', $this->domain); ?></label></th>
      <td>
        <select id="wp_kuchikomi_options[position]" name="wp_kuchikomi_options[position]">
            <option value="top" <?php if($options['position']=="top") echo "selected";?>><?php _e('Top', $this->domain); ?></option>
            <option value="bottom" <?php if($options['position']=="bottom") echo "selected";?>><?php _e('Bottom', $this->domain); ?></option>
        </select>
	  </td>
    </tr>
  </table>
  <input type="submit" class="button-primary lk_key" value="<?php _e('Save', $this->domain) ?>">
</form>
<?php

    }

    /**
     * 使い方画面
     */
    public function show_help_page(){

        // ==== プラグイン使い方画面の描画
?>
    <h2>口コミ機能プラグインLiteの使い方説明</h2>
    <ol>
        <li>「<?php _e('Settings', $this->domain); ?>」画面で評価項目名、表示位置などを設定する</li>
        <li>投稿ページの「<?php _e('Kuchikomi Setting Box', $this->domain); ?>」で評価を設定する</li>
        <li>該当記事を表示して評価が星マークで表示されているのを確認</li>
        <li>該当記事のコメント欄に（ログインユーザーでなければ）項目ごとに評価してコメント投稿ができるのを確認</li>
        <li>投稿したコメントが評価付きで表示されるのを確認</li>
    </ol>
<?php
    }

    /**
     * 投稿画面のメタボックス登録
     */
    public function add_box() {
        $options = get_option($this->option_name);

        // FIXME: prefixどうする？
        $prefix = 'wp_kuchikomi_';
        $meta_box = array(
            'id' => $prefix."_meta_box",
            'title' => __('Kuchikomi Setting Box', $this->domain),
            'context' => 'advanced',
            'priority' => 'default'
        );

        // TODO: 固定ページやカスタム投稿タイプへの対応
        add_meta_box( $meta_box['id'],$meta_box['title'],array($this,'show_meta_box'),'post',$meta_box['context'],$meta_box['priority']);
    }

    /**
     * 投稿画面への設定枠表示
     */
    public function show_meta_box() {
	    global $post;

        // ==== 設定を取得
        $options = get_option($this->option_name);
        $items = array();
        $items[1] = isset($options['item_1']) ? $options['item_1']: null;
        $items[2] = isset($options['item_2']) ? $options['item_2']: null;
        $items[3] = isset($options['item_3']) ? $options['item_3']: null;

        $form_fields = array(
            array(  
            	'name' => __('Total Rating', $this->domain),  
            	'desc'  => '総合口コミ評価',  
            	'id'    => 'wp_kuchikomi_rating',  
            	'type'  => 'select',
            	'options'	=> array(
                    '' => '', '1'=>'1', '1.5'=>'1.5', '2'=>'2', '2.5'=>'2.5', '3'=>'3', '3.5'=>'3.5', '4'=>'4', '4.5'=>'4.5', '5'=>'5'
            	) 
            ),
        );
        foreach($items as $key => $val) {
            $form_fields[] = array(
                'name' => $val,
                'desc' => $val,
                'id'   => 'wp_kuchikomi_item_'.$key,
                'type' => 'select',
            	'options'	=> array(
                    '' => '', '1'=>'1', '1.5'=>'1.5', '2'=>'2', '2.5'=>'2.5', '3'=>'3', '3.5'=>'3.5', '4'=>'4', '4.5'=>'4.5', '5'=>'5'
            	) 
            );
        }

        // ==== メタボックス描画処理 ここから

        echo '<table>';
	    echo '<input type="hidden" name="wp_kuchikomi_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';
        foreach($form_fields as $field) {
            // 設定内容を読込
            $meta = get_post_meta($post->ID, $field['id'], true);

            switch($field['type']) {
            case 'select':
                echo '<tr>';
                echo '<td>'.$field['name'].'</td>';
                echo '<td><select name="'.$field['id'].'" id="'.$field['id'].'" >';
                foreach($field['options'] as $key => $val) {
                    echo '<option ', $meta == $key ? 'selected' : '', '>', $val, '</option>';
                }
                echo '</select></td>';
                echo '</tr>';
                break;
            }
            
        }
        echo '</table>';

        // ==== メタボックス描画処理 ここまで
    }

    /**
     * 投稿メタデータの保存
     * @param 
     */
    public function save_postmeta_data($post_id) {

        // ==== 入力値チェック
	    if (!isset($_POST['wp_kuchikomi_nonce']) ||
            !wp_verify_nonce($_POST['wp_kuchikomi_nonce'], basename(__FILE__))) return $post_id;

	    // 自動保存が働いてる場合は何もしない
	    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		    return $post_id;
	    }

	    if (!current_user_can( 'edit_post', $post_id ) ) return;


        // ==== 入力保存処理

        // TODO: 可変性
        $names = array(
            'wp_kuchikomi_rating',
            'wp_kuchikomi_item_1',
            'wp_kuchikomi_item_2',
            'wp_kuchikomi_item_3',
        );

        foreach($names as $name) {
            $old = get_post_meta($post_id, $name, true);

            // 入力値の初期化
            $new = isset($_POST[$name]) ? $_POST[$name] : '';

            // 設定の反映
            if ($new && $new != $old) {
                update_post_meta($post_id, $name, $new);
            } elseif ('' == $new && $old) {
                delete_post_meta($post_id, $name, $old);
            }
        }
    }


    /**
     * 投稿一覧の評価列の名前設定
     * @param array $columns
     */
    public function add_post_header_columns($columns) {
        if (!isset($columns['wp_kuchikomi_ratings'])) {
            $columns['wp_kuchikomi_ratings'] = __('Total Rating',$this->domain);    
        }
        return $columns;
    }

    /**
     * 投稿一覧の評価列の追加
     * @param array $column_name
     * @param int $post_id
     */
    public function add_post_data_row($column_name, $post_id) {
        $rating_display = 0;

        switch($column_name) {
        case 'wp_kuchikomi_ratings':

            // TODO: Utilsメソッドのクラス化
            if(!WP_KckmUtils::is_review($post_id)) break;

            // レビュー設定を取得
            $custom = get_post_custom();
            $rating = isset($custom["wp_kuchikomi_rating"][0]) ? $custom["wp_kuchikomi_rating"][0] : '';
            if ($rating) {
                $rating_display = str_replace('.', '', $rating);
                echo '<span>'.$rating.'</span>';
            } else {
                echo '<span>'.__('Not a review', 'wp-kuchikomi-lite').'</span>';
            }

            // TODO: ユーザーレビュー平均点の表示
            break;
 
        default:
            break;
        }
    }

}

