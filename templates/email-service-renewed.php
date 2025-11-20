<?php
/**
 * Email template: Service Renewal Confirmation
 *
 * Variables available:
 * - $recipient_name
 * - $site_name
 * - $site_url
 * - $renewed_items (array of renewed services)
 * - $text_config (array of customizable strings)
 * - $brand_link (URL for branding)
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html( $text_config['renewal_subject'] ); ?></title>
</head>
<body style="margin:0; padding:0; background-color:#f5f5f5;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f5f5f5; padding:20px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                <!-- Header -->
                <tr>
                    <td style="background-color:#28a745; padding:16px 24px; color:#ffffff;">
                        <h1 style="margin:0; font-size:20px; font-weight:600;">
                            <?php echo esc_html( $site_name ); ?>
                        </h1>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:24px;">
                        <!-- Customizable Greeting -->
                        <p style="margin:0 0 16px 0; font-size:14px; font-weight:500;">
                            <?php echo nl2br(esc_html($text_config['renewal_greeting'])); ?>
                        </p>

                        <p style="margin:0 0 16px 0; font-size:14px;">
                            <strong><?php esc_html_e('Website:', 'whmin'); ?></strong> 
                            <a href="<?php echo esc_url($site_url); ?>" target="_blank" style="color:#0a8a96; text-decoration:none;"><?php echo esc_html($site_url); ?></a>
                        </p>

                        <!-- Renewal Confirmation -->
                        <div style="margin-top: 20px; margin-bottom: 10px;">
                            <h3 style="margin:0; font-size:16px; color:#28a745; border-bottom: 2px solid #28a745; padding-bottom: 5px;">
                                <?php echo esc_html($text_config['renewal_header']); ?>
                            </h3>
                            <p style="margin:10px 0; font-size:13px; color:#555;">
                                <?php echo nl2br(esc_html($text_config['renewal_body'])); ?>
                            </p>
                        </div>

                        <!-- Renewed Services Table -->
                        <?php if ( ! empty( $renewed_items ) ) : ?>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:16px; border-collapse: collapse;">
                                <thead>
                                    <tr style="background-color:#f8f9fa;">
                                        <th style="padding:10px; text-align:left; font-size:12px; border-bottom:2px solid #dee2e6; color:#6c757d; text-transform:uppercase;">
                                            <?php echo esc_html($text_config['renewal_table_header_service']); ?>
                                        </th>
                                        <th style="padding:10px; text-align:right; font-size:12px; border-bottom:2px solid #dee2e6; color:#6c757d; text-transform:uppercase;">
                                            <?php echo esc_html($text_config['renewal_table_header_price']); ?>
                                        </th>
                                        <th style="padding:10px; text-align:right; font-size:12px; border-bottom:2px solid #dee2e6; color:#6c757d; text-transform:uppercase;">
                                            <?php echo esc_html($text_config['renewal_table_header_new_expiration']); ?>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $renewed_items as $item ) : 
                                        $price_disp = !empty($item['price']) ? $item['price'] . '&euro;' : '-';
                                        $exp_display = !empty($item['unlimited']) ? 'Unlimited' : $item['expiration_date'];
                                        $detail = !empty($item['domain_detail']) ? ' <span style="color:#6c757d; font-size:12px;">[' . esc_html($item['domain_detail']) . ']</span>' : '';
                                    ?>
                                        <tr style="background-color:#f0f9f4;">
                                            <td style="padding:10px; font-size:13px; border-bottom:1px solid #eee; color:#333;">
                                                <strong><?php echo esc_html( $item['name'] ); ?></strong><?php echo $detail; ?>
                                            </td>
                                            <td style="padding:10px; text-align:right; font-size:13px; border-bottom:1px solid #eee; color:#333;">
                                                <?php echo $price_disp; ?>
                                            </td>
                                            <td style="padding:10px; text-align:right; font-size:13px; border-bottom:1px solid #eee; color:#28a745; font-weight:600;">
                                                <?php echo esc_html( $exp_display ); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

                        <p style="margin:0; font-size:12px; color:#adb5bd; text-align: center;">
                            <?php echo nl2br(esc_html($text_config['renewal_footer'])); ?>
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background-color:#f1f3f5; padding:15px 24px; text-align:center; font-size:11px; color:#868e96;">
                        &copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> 
                        
                        <?php if ( ! empty( $brand_link ) ) : ?>
                            <a href="<?php echo esc_url( $brand_link ); ?>" target="_blank" style="color:#075b63; text-decoration:none; font-weight:600;">
                                <?php echo esc_html( get_bloginfo('name') ); ?>
                            </a>
                        <?php else : ?>
                            <?php echo esc_html( get_bloginfo('name') ); ?>
                        <?php endif; ?>
                        
                        . <?php esc_html_e( 'All rights reserved.', 'whmin' ); ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>