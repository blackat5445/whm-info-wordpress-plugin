<?php
/**
 * Email template: News/Blog Post Notification
 *
 * Variables available:
 * - $recipient_name
 * - $post_title
 * - $post_excerpt
 * - $post_content
 * - $post_url
 * - $post_date
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
    <title><?php echo esc_html( str_replace('%title%', $post_title, $text_config['news_subject']) ); ?></title>
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
                            <?php echo esc_html( get_bloginfo('name') ); ?>
                        </h1>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:24px;">
                        <!-- Customizable Greeting -->
                        <p style="margin:0 0 16px 0; font-size:14px; font-weight:500;">
                            <?php echo nl2br(esc_html($text_config['news_greeting'])); ?>
                        </p>

                        <!-- News Header -->
                        <div style="margin-top: 20px; margin-bottom: 16px;">
                            <h3 style="margin:0; font-size:16px; color:#075b63; border-bottom: 2px solid #075b63; padding-bottom: 5px;">
                                <?php echo esc_html($text_config['news_header']); ?>
                            </h3>
                            <p style="margin:10px 0; font-size:13px; color:#555;">
                                <?php echo nl2br(esc_html($text_config['news_body'])); ?>
                            </p>
                        </div>

                        <!-- Blog Post Content -->
                        <div style="background-color:#f8f9fa; border-left: 4px solid #075b63; padding:20px; margin-bottom:20px;">
                            <h2 style="margin:0 0 10px 0; font-size:20px; color:#333;">
                                <?php echo esc_html( $post_title ); ?>
                            </h2>
                            
                            <p style="margin:0 0 15px 0; font-size:12px; color:#6c757d;">
                                <i class="mdi mdi-calendar"></i> <?php echo esc_html( $post_date ); ?>
                            </p>

                            <div style="font-size:14px; line-height:1.6; color:#333; margin-bottom:15px;">
                                <?php echo wp_kses_post( $post_content ); ?>
                            </div>

                            <a href="<?php echo esc_url( $post_url ); ?>" 
                               target="_blank" 
                               style="display:inline-block; background-color:#075b63; color:#ffffff; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:600;">
                                <?php esc_html_e('Read Full Article', 'whmin'); ?> &rarr;
                            </a>
                        </div>

                        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

                        <p style="margin:0; font-size:12px; color:#adb5bd; text-align: center;">
                            <?php echo nl2br(esc_html($text_config['news_footer'])); ?>
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