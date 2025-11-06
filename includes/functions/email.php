<?php
/**
 * Email + Telegram helper functions for WHM Info.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Locate an email template file.
 *
 * Priority:
 *  1) Theme override:  /your-theme/whmin/{slug}.php
 *  2) Plugin default:  /wp-content/plugins/whm-info/templates/{slug}.php
 *
 * @param string $template_slug  e.g. 'email-status-notification'
 * @param array  $args           Context array (for filters).
 * @return string                Full path or empty string.
 */
function whmin_get_email_template_path( $template_slug, $args = array() ) {

    $template_slug = apply_filters( 'whmin_email_template_slug', $template_slug, $args );

    $path = '';

    // Allow themes to override plugin templates
    $theme_template = locate_template( 'whmin/' . $template_slug . '.php' );
    if ( $theme_template ) {
        $path = $theme_template;
    } else {
        $plugin_template = trailingslashit( WHMIN_PLUGIN_DIR ) . 'templates/' . $template_slug . '.php';
        if ( file_exists( $plugin_template ) ) {
            $path = $plugin_template;
        }
    }

    return apply_filters( 'whmin_email_template_path', $path, $template_slug, $args );
}

/**
 * Render an email template and return the HTML.
 *
 * @param string $template_slug e.g. 'email-status-notification'
 * @param array  $args          Variables available inside the template.
 * @return string               HTML string (or empty on failure)
 */
function whmin_render_email_template( $template_slug, $args = array() ) {

    $path = whmin_get_email_template_path( $template_slug, $args );
    if ( ! $path ) {
        return '';
    }

    $args = apply_filters( 'whmin_email_template_args', $args, $template_slug );

    ob_start();
    // Make $args keys available as local variables in the template
    extract( $args, EXTR_SKIP );
    include $path;

    return trim( ob_get_clean() );
}

/**
 * Fallback HTML builder in case the template is missing.
 *
 * @param array $context Context array as passed to the template.
 * @return string
 */
function whmin_build_fallback_status_email_html( $context ) {
    $type      = $context['type']      ?? 'problem';
    $overall   = $context['overall']   ?? array();
    $percent   = isset( $context['percent'] ) ? (float) $context['percent'] : 0.0;
    $status_tx = $context['status_text'] ?? '';
    $counts    = $context['counts']    ?? array();
    $problems  = $context['problems']  ?? array();
    $timestamp = $context['timestamp'] ?? '';

    $lines   = array();

    $lines[] = ( $type === 'problem' )
        ? __( 'One or more monitored websites are currently having issues.', 'whmin' )
        : __( 'All monitored websites are currently operational again.', 'whmin' );

    $lines[] = '';
    $lines[] = sprintf(
        __( 'Overall status: %1$s (%2$s%% up)', 'whmin' ),
        $status_tx,
        number_format_i18n( $percent, 2 )
    );

    if ( ! empty( $counts ) ) {
        $lines[] = sprintf(
            __( 'Operational: %1$d, Degraded: %2$d, Down: %3$d, Total monitored: %4$d', 'whmin' ),
            (int) ( $counts['operational'] ?? 0 ),
            (int) ( $counts['degraded']    ?? 0 ),
            (int) ( $counts['down']        ?? 0 ),
            (int) ( $counts['total']       ?? 0 )
        );
    }

    if ( ! empty( $problems ) && is_array( $problems ) ) {
        $lines[] = '';
        $lines[] = __( 'Problematic websites:', 'whmin' );
        foreach ( $problems as $site ) {
            $lines[] = sprintf(
                ' - %1$s (%2$s) – %3$s',
                $site['name']   ?? 'Unknown',
                ucfirst( $site['status'] ?? '' ),
                $site['url']    ?? ''
            );
        }
    }

    if ( ! empty( $timestamp ) ) {
        $lines[] = '';
        $lines[] = sprintf( __( 'Timestamp: %s', 'whmin' ), $timestamp );
    }

    $lines[] = '';
    $lines[] = __( 'This message was generated automatically by the WHM Info plugin.', 'whmin' );

    $body = implode( "\n", $lines );

    // Wrap in a minimal <pre> so HTML email clients keep formatting.
    return '<pre style="font-family: -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif; font-size:14px; line-height:1.5;">'
        . esc_html( $body )
        . '</pre>';
}

/**
 * Wrapper around wp_mail for status notifications (HTML emails).
 *
 * @param string $email
 * @param string $subject
 * @param string $message_html
 * @return void
 */
function whmin_send_email_notification( $email, $subject, $message_html ) {
    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    wp_mail( $email, $subject, $message_html, $headers );
}

/**
 * Dummy Telegram sender – to be implemented later.
 *
 * @param string $chat_id Chat ID or @username.
 * @param string $message Message text.
 * @return bool Always true for now.
 */
function whmin_send_telegram_notification( $chat_id, $message ) {
    // Placeholder: integrate with Telegram Bot API later.
    do_action( 'whmin_telegram_notification', $chat_id, $message );

    return true;
}
