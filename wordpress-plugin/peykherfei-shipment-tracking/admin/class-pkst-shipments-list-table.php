<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class PKST_Shipments_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'shipment',
				'plural'   => 'shipments',
				'ajax'     => false,
			)
		);
	}

	public function get_columns() {
		return array(
			'cb'                   => '<input type="checkbox" />',
			'tracking_code'        => __( 'کد رهگیری', 'peykherfei-shipment-tracking' ),
			'recipient'            => __( 'گیرنده', 'peykherfei-shipment-tracking' ),
			'destination'          => __( 'مقصد', 'peykherfei-shipment-tracking' ),
			'price'                => __( 'قیمت', 'peykherfei-shipment-tracking' ),
			'status'               => __( 'وضعیت', 'peykherfei-shipment-tracking' ),
			'courier'              => __( 'پیک', 'peykherfei-shipment-tracking' ),
			'handed_to_courier_at' => __( 'تحویل به پیک', 'peykherfei-shipment-tracking' ),
			'created_at'           => __( 'تاریخ ثبت', 'peykherfei-shipment-tracking' ),
		);
	}

	public function get_sortable_columns() {
		return array(
			'created_at'           => array( 'created_at', true ),
			'handed_to_courier_at' => array( 'handed_to_courier_at', false ),
			'status'               => array( 'status', false ),
			'price'                => array( 'price', false ),
		);
	}

	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="shipment_ids[]" value="%d" />', $item['id'] );
	}

	protected function column_tracking_code( $item ) {
		$view_url = add_query_arg(
			array(
				'page'   => 'pkst-shipments',
				'action' => 'view',
				'id'     => $item['id'],
			),
			admin_url( 'admin.php' )
		);
		$edit_url = add_query_arg(
			array(
				'page' => 'pkst-shipment-add',
				'id'   => $item['id'],
			),
			admin_url( 'admin.php' )
		);
		$delete_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'  => 'pkst_delete_shipment',
					'id'      => $item['id'],
				),
				admin_url( 'admin-post.php' )
			),
			'pkst_delete_shipment_' . $item['id']
		);

		$actions = array(
			'view'   => sprintf( '<a href="%s">%s</a>', esc_url( $view_url ), esc_html__( 'مشاهده', 'peykherfei-shipment-tracking' ) ),
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'ویرایش', 'peykherfei-shipment-tracking' ) ),
			'delete' => sprintf(
				'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
				esc_url( $delete_url ),
				esc_js( __( 'این مرسوله برای همیشه حذف شود؟', 'peykherfei-shipment-tracking' ) ),
				esc_html__( 'حذف', 'peykherfei-shipment-tracking' )
			),
		);

		return sprintf(
			'<a href="%s"><strong>%s</strong></a>%s',
			esc_url( $view_url ),
			esc_html( $item['tracking_code'] ),
			$this->row_actions( $actions )
		);
	}

	protected function column_recipient( $item ) {
		return esc_html( $item['recipient_name'] ) . '<br><span class="description" dir="ltr">' . esc_html( $item['recipient_phone'] ) . '</span>';
	}

	protected function column_destination( $item ) {
		return esc_html( wp_trim_words( $item['destination'], 8 ) );
	}

	protected function column_price( $item ) {
		$formatted = PKST_Shipment::format_price( $item['price'] ?? null );
		return $formatted ? '<span class="pkst-price">' . esc_html( $formatted ) . '</span>' : '<span class="description">—</span>';
	}

	protected function column_status( $item ) {
		return sprintf(
			'<span class="pkst-badge %s">%s</span>',
			esc_attr( PKST_Status::badge_class( $item['status'] ) ),
			esc_html( PKST_Status::label( $item['status'] ) )
		);
	}

	protected function column_courier( $item ) {
		if ( ! $item['courier_id'] ) {
			return '<span class="description">' . esc_html__( 'تخصیص‌نیافته', 'peykherfei-shipment-tracking' ) . '</span>';
		}
		$user = get_userdata( $item['courier_id'] );
		return $user ? esc_html( $user->display_name ) : '—';
	}

	protected function column_handed_to_courier_at( $item ) {
		return $item['handed_to_courier_at'] ? esc_html( PKST_Jalali::format( $item['handed_to_courier_at'] ) ) : '—';
	}

	protected function column_created_at( $item ) {
		return esc_html( PKST_Jalali::format( $item['created_at'] ) );
	}

	public function get_bulk_actions() {
		return array(
			'delete' => __( 'حذف', 'peykherfei-shipment-tracking' ),
		);
	}

	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		$status  = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		$from    = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
		$to      = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
		?>
		<div class="alignleft actions">
			<select name="status">
				<option value=""><?php esc_html_e( 'همه وضعیت‌ها', 'peykherfei-shipment-tracking' ); ?></option>
				<?php foreach ( PKST_Status::labels() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<input type="date" name="date_from" value="<?php echo esc_attr( $from ); ?>" placeholder="<?php esc_attr_e( 'از تاریخ', 'peykherfei-shipment-tracking' ); ?>" />
			<input type="date" name="date_to" value="<?php echo esc_attr( $to ); ?>" placeholder="<?php esc_attr_e( 'تا تاریخ', 'peykherfei-shipment-tracking' ); ?>" />
			<?php submit_button( __( 'اعمال فیلتر', 'peykherfei-shipment-tracking' ), '', 'filter_action', false ); ?>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=pkst-shipments' ) ); ?>"><?php esc_html_e( 'حذف فیلتر', 'peykherfei-shipment-tracking' ); ?></a>
			<?php
			$export_url = wp_nonce_url(
				add_query_arg(
					array_merge(
						array( 'action' => 'pkst_export_csv' ),
						array(
							's'         => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
							'status'    => $status,
							'date_from' => $from,
							'date_to'   => $to,
						)
					),
					admin_url( 'admin-post.php' )
				),
				'pkst_export_csv'
			);
			?>
			<a class="button button-secondary" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'خروجی Excel', 'peykherfei-shipment-tracking' ); ?></a>
		</div>
		<?php
	}

	public function process_bulk_action() {
		$action = $this->current_action();
		if ( 'delete' !== $action ) {
			return;
		}

		check_admin_referer( 'bulk-' . $this->_args['plural'] );

		if ( ! current_user_can( 'pkst_manage_shipments' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}

		$ids = isset( $_REQUEST['shipment_ids'] ) ? array_map( 'absint', (array) $_REQUEST['shipment_ids'] ) : array();
		foreach ( $ids as $id ) {
			PKST_Shipment::delete( $id );
		}
	}

	public function prepare_items() {
		$this->process_bulk_action();

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$per_page     = 20;
		$current_page = $this->get_pagenum();

		$args = array(
			'search'    => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'status'    => isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '',
			'date_from' => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
			'date_to'   => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
			'orderby'   => isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'created_at',
			'order'     => isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC',
			'page'      => $current_page,
			'per_page'  => $per_page,
		);

		$result = PKST_Shipment::query( $args );

		$this->items = $result['items'];

		$this->set_pagination_args(
			array(
				'total_items' => $result['total'],
				'per_page'    => $per_page,
				'total_pages' => ceil( $result['total'] / $per_page ),
			)
		);
	}
}
