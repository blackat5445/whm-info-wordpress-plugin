<?php
/**
 * Email template: Service Expiration Notification (Split View)
 *
 * Variables available:
 * - $recipient_name
 * - $site_name
 * - $site_url
 * - $list_expired (array of expired items)
 * - $list_soon (array of upcoming/unlimited items)
 * - $text_config (array of customizable strings)
 * - $brand_link (URL for branding)
 * - $subject
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Helper to render a table row
function whmin_render_email_row($service, $is_expired_section, $text_config) {
    $row_style  = $is_expired_section ? 'background-color:#fff5f5;' : '';
    $text_color = $is_expired_section ? '#dc3545' : '#333333';
    $exp_display = !empty($service['unlimited']) ? 'Unlimited' : $service['expiration_date'];
    $price_disp  = !empty($service['price']) ? $service['price'] . '&euro;' : '-';
    
    $detail = !empty($service['domain_detail']) ? ' <span style="color:#6c757d; font-size:12px;">[' . esc_html($service['domain_detail']) . ']</span>' : '';
    
    ?>
    <tr style="<?php echo $row_style; ?>">
        <td style="padding:10px; font-size:13px; border-bottom:1px solid #eee; color:<?php echo $text_color; ?>;">
            <strong><?php echo esc_html( $service['name'] ); ?></strong><?php echo $detail; ?>
        </td>
        <td style="padding:10px; text-align:right; font-size:13px; border-bottom:1px solid #eee; color:#333333;">
            <?php echo $price_disp; ?>
        </td>
        <td style="padding:10px; text-align:right; font-size:13px; border-bottom:1px solid #eee; color:<?php echo $text_color; ?>;">
            <?php echo esc_html( $exp_display ); ?>
        </td>
    </tr>
    <?php
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
                    <td style="background-color:#075b63; padding:16px 24px; color:#ffffff;">
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
                            <?php echo nl2br(esc_html($text_config['greeting_text'])); ?>
                        </p>

                        <p style="margin:0 0 16px 0; font-size:14px;">
                            <strong><?php esc_html_e('Website:', 'whmin'); ?></strong> 
                            <a href="<?php echo esc_url($site_url); ?>" target="_blank" style="color:#0a8a96; text-decoration:none;"><?php echo esc_html($site_url); ?></a>
                        </p>

                        <!-- SECTION 1: EXPIRED SERVICES -->
                        <?php if ( ! empty( $list_expired ) ) : ?>
                            <div style="margin-top: 20px; margin-bottom: 10px;">
                                <h3 style="margin:0; font-size:16px; color:#dc3545; border-bottom: 2px solid #dc3545; padding-bottom: 5px;">
                                    <?php echo esc_html($text_config['header_expired']); ?>
                                </h3>
                                <p style="margin:10px 0; font-size:13px; color:#555;">
                                    <?php echo nl2br(esc_html($text_config['body_expired'])); ?>
                                </p>
                            </div>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:25px; border-collapse: collapse;">
                                <thead>
                                    <tr style="background-color:#f8f9fa;">
                                        <th style="padding:10px; text-align:left; font-size:12px; border-bottom:2px solid #dee2e6; color:#6c757d; text-transform:uppercase;">
                                            <?php echo esc_html($text_config['table_header_service']); ?>
                                        </th>
                                        <th style="padding:10px; text-align:right; font-size:12px; border-bottom:2px solid #dee2e6; color:#6c757d; text-transform:uppercase;">
                                            <?php echo esc_html($text_config['table_header_price']); ?>
                                        </th>
                                        <th style="padding:10px; text-align:right; font-size:12px; border-bottom:2px solid #dee2e6; color:#6c757d; text-transform:uppercase;">
                                            <?php echo esc_html($text_config['table_header_expiration']); ?>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $list_expired as $item ) { whmin_render_email_row($item, true, $text_config); } ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                        <!-- SECTION 2: SOON / ACTIVE SERVICES -->
                        <?php if ( ! empty( $list_soon ) ) : ?>
                            <div style="margin-top: 20px; margin-bottom: 10px;">
                                <h3 style="margin:0; font-size:16px; color:#ffc107; border-bottom: 2px solid #ffc107; padding-bottom: 5px; color:#b58900;">
                                    <?php echo esc_html($text_config['header_soon']); ?>
                                </h3>
                                <p style="margin:10px 0; font-size:13px; color:#555;">
                                    <?php echo nl2br(esc_html($text_config['body_soon'])); ?>
                                </p>
                            </div>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:16px; border-collapse: collapse;">
                                <thead>
                                    <tr style="background-color:#f8f9fa;">
                                        <th style="padding:10px; text-align:left; font-size:12px; border-bottom:2px solid #dee2e6; color:#6c757d; text-transform:uppercase;">
                                            <?php echo esc_html($text_config['table_header_service']); ?>
                                        </th>
                                        <th style="padding:10px; text-align:right; font-size:12px; border-bottom:2px solid #dee2e6; color:#6c757d; text-transform:uppercase;">
                                            <?php echo esc_html($text_config['table_header_price']); ?>
                                        </th>
                                        <th style="padding:10px; text-align:right; font-size:12px; border-bottom:2px solid #dee2e6; color:#6c757d; text-transform:uppercase;">
                                            <?php echo esc_html($text_config['table_header_expiration']); ?>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $list_soon as $item ) { whmin_render_email_row($item, false, $text_config); } ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

                        <p style="margin:0; font-size:12px; color:#adb5bd; text-align: center;">
                            <?php echo nl2br(esc_html($text_config['footer_text'])); ?>
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