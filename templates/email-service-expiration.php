<?php
/**
 * Email template: Service Expiration Notification
 *
 * Available variables:
 * - $recipient_name (string)
 * - $site_name      (string)
 * - $site_url       (string)
 * - $services       (array of services sending notification for)
 * - $subject        (string)
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html( $subject ); ?></title>
</head>
<body style="margin:0; padding:0; background-color:#f5f5f5;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f5f5f5; padding:20px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                <!-- Header -->
                <tr>
                    <td style="background-color:#dc3545; padding:16px 24px; color:#ffffff;">
                        <h1 style="margin:0; font-size:20px; font-weight:600;">
                            <?php echo esc_html( $site_name ); ?> – <?php esc_html_e( 'Service Expiration Notice', 'whmin' ); ?>
                        </h1>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 16px 0; font-size:14px; color:#333333;">
                            <?php printf( esc_html__( 'Hi %s,', 'whmin' ), esc_html( $recipient_name ) ); ?>
                        </p>

                        <p style="margin:0 0 16px 0; font-size:14px; color:#333333;">
                            <?php esc_html_e( 'The following services for your website are expiring or have expired. Please review the details below:', 'whmin' ); ?>
                        </p>

                        <p style="margin:0 0 16px 0; font-size:14px;">
                            <strong><?php esc_html_e('Website:', 'whmin'); ?></strong> 
                            <a href="<?php echo esc_url($site_url); ?>" target="_blank" style="color:#075b63; text-decoration:none;"><?php echo esc_html($site_url); ?></a>
                        </p>

                        <!-- Services List -->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:16px; border-collapse: collapse;">
                            <thead>
                                <tr style="background-color:#f1f3f5;">
                                    <th style="padding:10px; text-align:left; font-size:13px; border-bottom:1px solid #dee2e6; color:#495057;"><?php esc_html_e('Service', 'whmin'); ?></th>
                                    <th style="padding:10px; text-align:right; font-size:13px; border-bottom:1px solid #dee2e6; color:#495057;"><?php esc_html_e('Price', 'whmin'); ?></th>
                                    <th style="padding:10px; text-align:right; font-size:13px; border-bottom:1px solid #dee2e6; color:#495057;"><?php esc_html_e('Expiration', 'whmin'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $services as $service ) : 
                                    $is_expired = ( !empty($service['expiration_date']) && strtotime($service['expiration_date']) < time() && empty($service['unlimited']) );
                                    $row_style = $is_expired ? 'background-color:#fff5f5;' : '';
                                    $text_color = $is_expired ? '#dc3545' : '#333333';
                                    $exp_display = !empty($service['unlimited']) ? __('Unlimited', 'whmin') : $service['expiration_date'];
                                ?>
                                <tr style="<?php echo $row_style; ?>">
                                    <td style="padding:10px; font-size:13px; border-bottom:1px solid #eee; color:<?php echo $text_color; ?>;">
                                        <strong><?php echo esc_html( $service['name'] ); ?></strong>
                                    </td>
                                    <td style="padding:10px; text-align:right; font-size:13px; border-bottom:1px solid #eee; color:#333333;">
                                        <?php echo esc_html( $service['price'] ); ?>&euro;
                                    </td>
                                    <td style="padding:10px; text-align:right; font-size:13px; border-bottom:1px solid #eee; color:<?php echo $text_color; ?>;">
                                        <?php echo esc_html( $exp_display ); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <p style="margin:0; font-size:12px; color:#adb5bd;">
                            <?php esc_html_e( 'This is an automated notification. Please contact us to renew your services.', 'whmin' ); ?>
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background-color:#f1f3f5; padding:12px 24px; text-align:center; font-size:11px; color:#868e96;">
                        &copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo('name') ); ?>.
                        <?php esc_html_e( 'All rights reserved.', 'whmin' ); ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>