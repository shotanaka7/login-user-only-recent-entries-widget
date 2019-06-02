<?php
/*
Plugin Name: login user onry recent entries widget
Description: 管理者権限を持たないログインユーザーの最近の投稿を、本人の記事に絞って出力するウィジェットを追加します。
Author: Sho Tanaka
Version: 1.1.0
*/


class login_user_onry_recent_entries_widget extends WP_Widget {
	/**
	 * 初期化処理(ウィジェットの各種設定)を行う。
	 **/
	public function __construct() {
		// 情報用の設定値
		$widget_options = array(
			'classname'                   => 'login_user_onry_recent_entries_widget',
			'description'                 => '管理者権限を持たないログインユーザーの最近の投稿を、本人の記事に絞って出力します。',
			'customize_selective_refresh' => true,
		);

		// 操作用の設定値
		$control_options = array(
			// デフォルトからの変更があれば記入する。
		);

		// 親クラスのコンストラクタに値を設定
		parent::__construct( 'login_user_onry_recent_entries_widget', '[o]最近の投稿', $widget_options, $control_options );
	}

	/**
	 * ウィジェットの内容をWebページに出力(HTML表示)
	 *
	 * @param array $args        register_sidebar()で設定したウィジェットの開始/終了タグ、タイトルの開始/終了タグなどが渡される。
	 * @param array $instance    管理画面から入力した値が渡される。
	 **/
	public function widget( $args, $instance ) {
		// ウィジェットのオプション「タイトル(title)」を取得
		$title = empty( $instance['title'] ) ? '' : $instance['title'];

		// ウィジェットのオプション「件数(limit)」を取得
		$limit = empty( $instance['limit'] ) ? '' : $instance['limit'];

		echo $args['before_widget']; // ウィジェット開始タグ

		if ( ! empty( $title ) ) {
			// タイトルの値をタイトル開始/終了タグで囲んで出力
			echo $args['before_title'] . $title . $args['after_title'];
		}

		// ログインユーザーが管理者権限を持つ場合
		if ( current_user_can( 'administrator' ) ) :
			$query     = array(
				'posts_per_page' => $limit,
			);
			$the_query = new WP_Query( $query );

			if ( $the_query->have_posts() ) : // 投稿が存在すれば
				echo '<ul>';

				while ( $the_query->have_posts() ) :
					$the_query->the_post(); ?>
					<li>
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						<span class="author vcard" style="display:block"><a class="url fn n" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>"><?php _e( '作成者: ' ) . the_author(); ?></a></span>
						<span class="post-date" style="display:block;"><?php echo get_the_date(); ?></span>
					</li>
					<?php
				endwhile;
				echo '</ul>';

			else : // 投稿が存在しなければ
				echo '<p>投稿がありません。</p>';
			endif;

		else : // ログインユーザーが管理者権限を持たない場合
			$login_user = wp_get_current_user();

			$query     = array(
				'author'         => $login_user->ID, // ログインユーザーに限定
				'posts_per_page' => $limit,
			);
			$the_query = new WP_Query( $query );

			if ( $the_query->have_posts() ) :
				echo '<ul>';

				while ( $the_query->have_posts() ) :
					$the_query->the_post();
					?>
				<li>
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					<span class="post-date" style="display: block;"><?php echo get_the_date(); ?></span>
				</li>
					<?php
				endwhile;
				echo '</ul>';

			else :
				echo '<p>投稿がありません。</p>';
			endif;
		endif;

		echo $args['after_widget']; // ウィジェット開始タグ
	}

	/**
	 * 管理画面のウィジェット設定フォームを出力
	 *
	 * @param array $instance 現在のオプション値が渡される。
	 **/
	public function form( $instance ) {
		$defaults = array(
			'title' => '',
			'limit' => '',
		);
		// デフォルトのオプション値と現在のオプション値を結合
		$instance = wp_parse_args( (array) $instance, $defaults );

		// タイトルの無害化(サニタイズ)
		$title = sanitize_text_field( $instance['title'] );
		?>
			<!-- 設定フォーム：タイトル -->
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
			</p>

			<!-- 設定フォーム：件数 -->
			<p>
				<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( '表示する投稿数:' ); ?></label>
				<input class="tiny-text" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="number" min="1" step="1" size="3" value="<?php echo esc_attr( $instance['limit'] ); ?>">
			</p>
		<?php
	}

	/**
	 * ウィジェットオプションのデータ検証/無害化
	 *
	 * @param array $mew_instance 新しいオプション値
	 * @param array $old_instance 以前のオプション値
	 *
	 * @return array データ検証/無害化した値を返す
	 **/
	public function update( $new_instance, $old_instance ) {

		// 一時的に以前のオプションを別変数に退避
		$instance = $old_instance;

		// タイトル値を無害化(サニタイズ)
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['limit'] = is_numeric( $new_instance['limit'] ) ? $new_instance['limit'] : 5;
		return $instance;
	}
}

/**
 * ウィジェットテンプレートの登録
 **/
function login_user_onry_recent_entries_widget_register_widget() {
	register_widget( 'login_user_onry_recent_entries_widget' );
}
add_action( 'widgets_init', 'login_user_onry_recent_entries_widget_register_widget' );
