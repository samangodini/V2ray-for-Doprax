<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor widget: the site header's account button + its dropdown
 * (welcome message, login/register, quick links to the customer panel).
 * Only ever loaded from PKST_Elementor::register_widgets(), which itself
 * only runs when Elementor is active -- see class-pkst-elementor.php.
 */
class PKST_Elementor_Account_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'pkst_account_button';
	}

	public function get_title() {
		return __( 'دکمه ورود کاربری (پیک خرفه)', 'peykherfei-shipment-tracking' );
	}

	public function get_icon() {
		return 'eicon-lock-user';
	}

	public function get_categories() {
		return array( 'general' );
	}

	public function get_keywords() {
		return array( 'login', 'account', 'user', 'ورود', 'حساب کاربری', 'پیک خرفه' );
	}

	public function get_style_depends() {
		return array( 'pkst-account-widget' );
	}

	public function get_script_depends() {
		return array( 'pkst-account-widget' );
	}

	private function icon_options() {
		return array(
			'box'     => __( 'بسته / سفارش', 'peykherfei-shipment-tracking' ),
			'pin'     => __( 'مکان / آدرس', 'peykherfei-shipment-tracking' ),
			'clock'   => __( 'تاریخچه', 'peykherfei-shipment-tracking' ),
			'headset' => __( 'پشتیبانی', 'peykherfei-shipment-tracking' ),
			'tag'     => __( 'قیمت', 'peykherfei-shipment-tracking' ),
			'phone'   => __( 'تماس', 'peykherfei-shipment-tracking' ),
			'truck'   => __( 'پیک', 'peykherfei-shipment-tracking' ),
			'check'   => __( 'تأیید', 'peykherfei-shipment-tracking' ),
		);
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			array( 'label' => __( 'محتوا', 'peykherfei-shipment-tracking' ) )
		);

		$this->add_control(
			'panel_url',
			array(
				'label'       => __( 'آدرس صفحه پنل کاربری', 'peykherfei-shipment-tracking' ),
				'type'        => \Elementor\Controls_Manager::URL,
				'placeholder' => PKST_Settings::get( 'panel_page_url', home_url( '/' ) ),
				'description' => __( 'خالی بگذارید تا آدرس تنظیم‌شده در «پنل مرسولات ← تنظیمات» استفاده شود.', 'peykherfei-shipment-tracking' ),
			)
		);

		$this->add_control(
			'heading_text',
			array(
				'label'   => __( 'عنوان خوشامدگویی (حالت خروج از سیستم)', 'peykherfei-shipment-tracking' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => sprintf(
					/* translators: %s: company name */
					__( 'به %s خوش آمدید', 'peykherfei-shipment-tracking' ),
					PKST_Settings::get( 'company_name', 'پیک خرفه' )
				),
			)
		);

		$this->add_control(
			'sub_text',
			array(
				'label'   => __( 'متن توضیح', 'peykherfei-shipment-tracking' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'برای دسترسی به پنل کاربری، وارد حساب خود شوید', 'peykherfei-shipment-tracking' ),
			)
		);

		$this->add_control(
			'show_register',
			array(
				'label'        => __( 'نمایش دکمه ثبت‌نام', 'peykherfei-shipment-tracking' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'label_on'     => __( 'بله', 'peykherfei-shipment-tracking' ),
				'label_off'    => __( 'خیر', 'peykherfei-shipment-tracking' ),
			)
		);

		$this->add_control(
			'register_url',
			array(
				'label'       => __( 'آدرس ثبت‌نام', 'peykherfei-shipment-tracking' ),
				'type'        => \Elementor\Controls_Manager::URL,
				'condition'   => array( 'show_register' => 'yes' ),
				'description' => __( 'حساب مشتری/پیک را فعلاً فقط مدیر می‌سازد؛ این آدرس را به صفحه تماس یا فرم درخواست حساب وصل کنید.', 'peykherfei-shipment-tracking' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'links_section',
			array( 'label' => __( 'لینک‌های سریع', 'peykherfei-shipment-tracking' ) )
		);

		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'icon_type',
			array(
				'label'   => __( 'آیکون', 'peykherfei-shipment-tracking' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'box',
				'options' => $this->icon_options(),
			)
		);
		$repeater->add_control(
			'label',
			array(
				'label'   => __( 'عنوان', 'peykherfei-shipment-tracking' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'عنوان لینک', 'peykherfei-shipment-tracking' ),
			)
		);
		$repeater->add_control(
			'url',
			array(
				'label'       => __( 'آدرس', 'peykherfei-shipment-tracking' ),
				'type'        => \Elementor\Controls_Manager::URL,
				'description' => __( 'خالی بگذارید تا از آدرس پنل کاربری (یا برای «پشتیبانی»، از تنظیمات افزونه) استفاده شود.', 'peykherfei-shipment-tracking' ),
			)
		);

		$this->add_control(
			'quick_links',
			array(
				'label'       => __( 'لینک‌ها', 'peykherfei-shipment-tracking' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'icon_type' => 'box',
						'label'     => __( 'پیگیری سفارشات', 'peykherfei-shipment-tracking' ),
					),
					array(
						'icon_type' => 'pin',
						'label'     => __( 'مدیریت آدرس‌ها', 'peykherfei-shipment-tracking' ),
					),
					array(
						'icon_type' => 'clock',
						'label'     => __( 'تاریخچه سفارشات', 'peykherfei-shipment-tracking' ),
					),
					array(
						'icon_type' => 'headset',
						'label'     => __( 'پشتیبانی و سوالات متداول', 'peykherfei-shipment-tracking' ),
					),
				),
				'title_field' => '{{{ label }}}',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_section',
			array(
				'label' => __( 'ظاهر', 'peykherfei-shipment-tracking' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'accent_color',
			array(
				'label'     => __( 'رنگ طلایی', 'peykherfei-shipment-tracking' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#e0b643',
				'selectors' => array(
					'{{WRAPPER}} .pkst-acct' => '--pkst-acct-gold: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'panel_width',
			array(
				'label'     => __( 'عرض پنل (دسکتاپ)', 'peykherfei-shipment-tracking' ),
				'type'      => \Elementor\Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 280,
						'max' => 480,
					),
				),
				'default'   => array(
					'unit' => 'px',
					'size' => 360,
				),
				'selectors' => array(
					'{{WRAPPER}} .pkst-acct-panel' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$panel_url = ! empty( $settings['panel_url']['url'] )
			? $settings['panel_url']['url']
			: PKST_Settings::get( 'panel_page_url', home_url( '/' ) );

		$user = is_user_logged_in() ? wp_get_current_user() : null;
		$uid  = 'pkst-acct-' . $this->get_id();
		?>
		<div class="pkst-acct">
			<button type="button" class="pkst-acct-trigger" aria-expanded="false" aria-haspopup="true" aria-controls="<?php echo esc_attr( $uid ); ?>">
				<span class="pkst-acct-trigger-icon"><?php echo PKST_Icons::svg( 'user', 17 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static, self-authored SVG markup. ?></span>
				<span class="pkst-acct-trigger-label">
					<?php echo $user ? esc_html( $user->display_name ) : esc_html__( 'ورود / ثبت نام', 'peykherfei-shipment-tracking' ); ?>
				</span>
				<span class="pkst-acct-trigger-chevron"><?php echo PKST_Icons::svg( 'chevron-down', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</button>

			<div class="pkst-acct-panel" id="<?php echo esc_attr( $uid ); ?>" role="menu" aria-hidden="true">
				<div class="pkst-acct-panel-head">
					<span class="pkst-acct-avatar"><?php echo PKST_Icons::svg( 'user', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<div class="pkst-acct-panel-headtext">
						<strong>
							<?php
							if ( $user ) {
								/* translators: %s: the logged-in user's display name */
								printf( esc_html__( 'خوش آمدید، %s', 'peykherfei-shipment-tracking' ), esc_html( $user->display_name ) );
							} else {
								echo esc_html( $settings['heading_text'] );
							}
							?>
						</strong>
						<p>
							<?php
							echo $user
								? esc_html__( 'به پنل کاربری خود دسترسی دارید.', 'peykherfei-shipment-tracking' )
								: esc_html( $settings['sub_text'] );
							?>
						</p>
					</div>
				</div>

				<div class="pkst-acct-actions">
					<?php if ( $user ) : ?>
						<a class="pkst-acct-btn pkst-acct-btn-primary" href="<?php echo esc_url( $panel_url ); ?>">
							<span><?php esc_html_e( 'پنل کاربری من', 'peykherfei-shipment-tracking' ); ?></span>
							<?php echo PKST_Icons::svg( 'user', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
						<a class="pkst-acct-btn pkst-acct-btn-ghost" href="<?php echo esc_url( wp_logout_url( $panel_url ) ); ?>">
							<span><?php esc_html_e( 'خروج از حساب', 'peykherfei-shipment-tracking' ); ?></span>
						</a>
					<?php else : ?>
						<a class="pkst-acct-btn pkst-acct-btn-primary" href="<?php echo esc_url( $panel_url ); ?>">
							<span><?php esc_html_e( 'ورود به حساب کاربری', 'peykherfei-shipment-tracking' ); ?></span>
							<?php echo PKST_Icons::svg( 'login', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
						<?php if ( 'yes' === $settings['show_register'] ) : ?>
							<?php $register_url = ! empty( $settings['register_url']['url'] ) ? $settings['register_url']['url'] : $panel_url; ?>
							<a class="pkst-acct-btn pkst-acct-btn-ghost" href="<?php echo esc_url( $register_url ); ?>">
								<span><?php esc_html_e( 'ثبت نام در سایت', 'peykherfei-shipment-tracking' ); ?></span>
								<?php echo PKST_Icons::svg( 'user-plus', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</a>
						<?php endif; ?>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $settings['quick_links'] ) ) : ?>
					<div class="pkst-acct-divider"></div>
					<ul class="pkst-acct-links">
						<?php foreach ( $settings['quick_links'] as $item ) : ?>
							<?php
							$icon = ! empty( $item['icon_type'] ) ? $item['icon_type'] : 'box';
							$url  = ! empty( $item['url']['url'] )
								? $item['url']['url']
								: ( 'headset' === $icon ? PKST_Settings::get( 'faq_url', $panel_url ) : $panel_url );
							?>
							<li>
								<a href="<?php echo esc_url( $url ); ?>">
									<?php echo PKST_Icons::svg( $icon, 17 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<span><?php echo esc_html( $item['label'] ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
