<?php
/**
 * Cấu hình cơ bản cho WordPress
 *
 * Trong quá trình cài đặt, file "wp-config.php" sẽ được tạo dựa trên nội dung 
 * mẫu của file này. Bạn không bắt buộc phải sử dụng giao diện web để cài đặt, 
 * chỉ cần lưu file này lại với tên "wp-config.php" và điền các thông tin cần thiết.
 *
 * File này chứa các thiết lập sau:
 *
 * * Thiết lập MySQL
 * * Các khóa bí mật
 * * Tiền tố cho các bảng database
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** Thiết lập MySQL - Bạn có thể lấy các thông tin này từ host/server ** //
/** Tên database MySQL */
define( 'DB_NAME', 'myWordPress' );

/** Username của database */
define( 'DB_USER', 'root' );

/** Mật khẩu của database */
define( 'DB_PASSWORD', '' );

/** Hostname của database */
define( 'DB_HOST', 'localhost' );

/** Database charset sử dụng để tạo bảng database. */
define( 'DB_CHARSET', 'utf8mb4' );

/** Kiểu database collate. Đừng thay đổi nếu không hiểu rõ. */
define('DB_COLLATE', '');

/**#@+
 * Khóa xác thực và salt.
 *
 * Thay đổi các giá trị dưới đây thành các khóa không trùng nhau!
 * Bạn có thể tạo ra các khóa này bằng công cụ
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * Bạn có thể thay đổi chúng bất cứ lúc nào để vô hiệu hóa tất cả
 * các cookie hiện có. Điều này sẽ buộc tất cả người dùng phải đăng nhập lại.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '!c3}Llo1@uR(<uch_+FyTi*e&^_-ee)4{c/TaqAt;@}sTDJPv_j[^DJR.,p1pE4u' );
define( 'SECURE_AUTH_KEY',  ']Q^kp9:0UEZVmSz}~MYj8U7B0~v;*GvdF5cQ-7~5D3#X7d-2;4F}Q],jq:61?S`:' );
define( 'LOGGED_IN_KEY',    '#:J~6IA4e,$x3Vef@Zv*>]P:G@v9.^>OBM]YFojE@[K* S- ;}51&2lGc ID}FKR' );
define( 'NONCE_KEY',        'U71{riyaigW&Z0q)8SFz6ec|4c3ZR!ZH(^ER|L%Y,5siYd2No=@=|k(&>CuL/&3^' );
define( 'AUTH_SALT',        'A}6K_+-IfEu3!kEwe;JZ_34C+(J:Aj, rs2a=$_ha7K.)Ju ,el`GlD|R(GfIdGu' );
define( 'SECURE_AUTH_SALT', '_40Q/Y]E,$g%/zLK&zf(r:@)<Xt<2b[ G+w9=5v9I&XC(5.A(Y@Vo3MtvPSZK;[~' );
define( 'LOGGED_IN_SALT',   '2x$U/!-QmIdd!qgS?v>c&uEHpau#6O&lP)y}Jmx@ }u$kVXz1iiGla=9}]wE 4FQ' );
define( 'NONCE_SALT',       'kLQ7zlB)tiAuXENut-7r0^S#E 8c=Z6PE`.)/TV|F6bTHA!,U%83-t|>>kJ#b,v5' );

/**#@-*/

/**
 * Tiền tố cho bảng database.
 *
 * Đặt tiền tố cho bảng giúp bạn có thể cài nhiều site WordPress vào cùng một database.
 * Chỉ sử dụng số, ký tự và dấu gạch dưới!
 */
$table_prefix = 'wp_';

/**
 * Dành cho developer: Chế độ debug.
 *
 * Thay đổi hằng số này thành true sẽ làm hiện lên các thông báo trong quá trình phát triển.
 * Chúng tôi khuyến cáo các developer sử dụng WP_DEBUG trong quá trình phát triển plugin và theme.
 *
 * Để có thông tin về các hằng số khác có thể sử dụng khi debug, hãy xem tại Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* Đó là tất cả thiết lập, ngưng sửa từ phần này trở xuống. Chúc bạn viết blog vui vẻ. */

/** Đường dẫn tuyệt đối đến thư mục cài đặt WordPress. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Thiết lập biến và include file. */
require_once(ABSPATH . 'wp-settings.php');
