<?php
/**
 * Email template: Status notification
 *
 * Available variables:
 * - $type           ('problem' or 'resolved')
 * - $overall        (array from whmin_calculate_overall_status())
 * - $recipient      (full recipient array)
 * - $recipient_name (string)
 * - $percent        (float)
 * - $status_text    (string)
 * - $counts         (array: operational, degraded, down, total)
 * - $problems       (array of problematic sites)
 * - $timestamp      (string, already formatted)
 * - $subject        (string)
 * - $site_name      (string)
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$recipient_name = ! empty( $recipient_name ) ? $recipient_name : ( $recipient['name'] ?? '' );
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
                            <?php echo esc_html( $site_name ); ?> – <?php esc_html_e( 'Status Update', 'whmin' ); ?>
                        </h1>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:24px;">
                        <?php if ( $recipient_name ) : ?>
                            <p style="margin:0 0 12px 0; font-size:14px;">
                                <?php printf( esc_html__( 'Hi %s,', 'whmin' ), esc_html( $recipient_name ) ); ?>
                            </p>
                        <?php endif; ?>

                        <p style="margin:0 0 16px 0; font-size:14px; color:#333333;">
                            <?php if ( $type === 'problem' ) : ?>
                                <?php esc_html_e( 'One or more monitored websites are currently having issues.', 'whmin' ); ?>
                            <?php else : ?>
                                <?php esc_html_e( 'All monitored websites are currently operational again.', 'whmin' ); ?>
                            <?php endif; ?>
                        </p>

                        <!-- Overall status box -->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:16px;">
                            <tr>
                                <td style="background-color:#f1f3f5; border-radius:6px; padding:12px 16px;">
                                    <p style="margin:0 0 4px 0; font-size:13px; color:#495057;">
                                        <?php esc_html_e( 'Overall status', 'whmin' ); ?>:
                                        <strong><?php echo esc_html( $status_text ); ?></strong>
                                    </p>
                                    <p style="margin:0; font-size:13px; color:#495057;">
                                        <?php printf(
                                            esc_html__( 'Uptime: %s%%', 'whmin' ),
                                            esc_html( number_format_i18n( $percent, 2 ) )
                                        ); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <!-- Counts -->
                        <?php if ( ! empty( $counts ) ) : ?>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:16px; font-size:13px; color:#495057;">
                                <tr>
                                    <td style="padding-right:16px;">
                                        <?php esc_html_e( 'Operational', 'whmin' ); ?>:
                                        <strong><?php echo (int) ( $counts['operational'] ?? 0 ); ?></strong>
                                    </td>
                                    <td style="padding-right:16px;">
                                        <?php esc_html_e( 'Degraded', 'whmin' ); ?>:
                                        <strong><?php echo (int) ( $counts['degraded'] ?? 0 ); ?></strong>
                                    </td>
                                    <td style="padding-right:16px;">
                                        <?php esc_html_e( 'Down', 'whmin' ); ?>:
                                        <strong><?php echo (int) ( $counts['down'] ?? 0 ); ?></strong>
                                    </td>
                                    <td>
                                        <?php esc_html_e( 'Total monitored', 'whmin' ); ?>:
                                        <strong><?php echo (int) ( $counts['total'] ?? 0 ); ?></strong>
                                    </td>
                                </tr>
                            </table>
                        <?php endif; ?>

                        <!-- Problem list -->
                        <?php if ( ! empty( $problems ) && is_array( $problems ) ) : ?>
                            <p style="margin:0 0 8px 0; font-size:14px; font-weight:600; color:#333333;">
                                <?php esc_html_e( 'Problematic websites:', 'whmin' ); ?>
                            </p>
                            <ul style="margin:0 0 16px 20px; padding:0; font-size:13px; color:#495057;">
                                <?php foreach ( $problems as $site ) : ?>
                                    <li style="margin-bottom:4px;">
                                        <strong><?php echo esc_html( $site['name'] ?? 'Unknown' ); ?></strong>
                                        <?php if ( ! empty( $site['status'] ) ) : ?>
                                            (<?php echo esc_html( ucfirst( $site['status'] ) ); ?>)
                                        <?php endif; ?>
                                        <?php if ( ! empty( $site['url'] ) ) : ?>
                                            – <a href="<?php echo esc_url( $site['url'] ); ?>" target="_blank" style="color:#075b63; text-decoration:none;">
                                                <?php echo esc_html( $site['url'] ); ?>
                                            </a>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if ( ! empty( $timestamp ) ) : ?>
                            <p style="margin:0 0 16px 0; font-size:12px; color:#868e96;">
                                <?php printf( esc_html__( 'Timestamp: %s', 'whmin' ), esc_html( $timestamp ) ); ?>
                            </p>
                        <?php endif; ?>

                        <p style="margin:0; font-size:12px; color:#adb5bd;">
                            <?php esc_html_e( 'This message was generated automatically by the WHM Info plugin.', 'whmin' ); ?>
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background-color:#f1f3f5; padding:12px 24px; text-align:center; font-size:11px; color:#868e96;">
                        &copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php echo esc_html( $site_name ); ?>.
                        <?php esc_html_e( 'All rights reserved.', 'whmin' ); ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
