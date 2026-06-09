<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION


// Měření zobrazení článku pro filtr nejoblíbenějšího článku:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );
         
if ( !function_exists( 'child_theme_configurator_css' ) ):
    function child_theme_configurator_css() {
        wp_enqueue_style( 'chld_thm_cfg_child', trailingslashit( get_stylesheet_directory_uri() ) . 'style.css', array( 'hello-elementor','hello-elementor-theme-style','hello-elementor-header-footer' ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'child_theme_configurator_css', 10 );

// END ENQUEUE PARENT ACTION

// ==========================================
// VÝKON – Reboost performance optimalizace
// ==========================================

// --- 1. Odstraň emoji skripty (zbytečné ~20 KB) ---
add_action('init', function () {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
});

// --- 2. Preconnect + DNS prefetch pro externí zdroje ---
add_action('wp_head', function () {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    echo '<link rel="dns-prefetch" href="https://cdn.trustindex.io">' . "\n";
    echo '<link rel="dns-prefetch" href="https://lh3.googleusercontent.com">' . "\n";
}, 2);

// --- 3. Defer non-critical JavaScript (ne jQuery, ne Elementor core) ---
add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if (is_admin()) return $tag;
    // Tyto skripty NESMÍ být defernuty – stránka by se rozbila
    $no_defer = [
        'jquery', 'jquery-core', 'jquery-migrate',
        'elementor-frontend', 'elementor-pro-frontend',
        'wp-embed',
    ];
    if (in_array($handle, $no_defer, true)) return $tag;
    // Přidej defer pouze pokud ho tag ještě nemá
    if (strpos($tag, ' defer') !== false || strpos($tag, ' async') !== false) return $tag;
    return str_replace('<script ', '<script defer ', $tag);
}, 10, 3);

// --- 4. Odstraň query strings ze statických souborů (lepší cache) ---
add_filter('style_loader_src', 'reboost_remove_ver_query', 9999);
add_filter('script_loader_src', 'reboost_remove_ver_query', 9999);
function reboost_remove_ver_query($src) {
    if (is_admin()) return $src;
    // Ponech ver pro Elementor a pluginy (mohou záviset na verzi)
    $keep_ver = ['elementor', 'wp-block'];
    foreach ($keep_ver as $k) {
        if (strpos($src, $k) !== false) return $src;
    }
    return $src ? esc_url(remove_query_arg('ver', $src)) : $src;
}

// --- 5. Oprav lazy loading na velkých obrázcích nad foldem ---
// Silver badge (wp-image-570, 800x800) má lazy loading ale je nad foldem na mobilu
add_filter('wp_content_img_tag', function ($filtered_image, $context, $attachment_id) {
    // wp-image-570 = Silver partner badge (LCP kandidát)
    if ($attachment_id === 570) {
        $filtered_image = str_replace(' loading="lazy"', '', $filtered_image);
        $filtered_image = str_replace(' decoding="async"', ' decoding="sync"', $filtered_image);
        // Přidej fetchpriority jen pokud tam ještě není (Elementor ho může přidat sám)
        if (strpos($filtered_image, 'fetchpriority') === false) {
            $filtered_image = str_replace('<img ', '<img fetchpriority="high" ', $filtered_image);
        }
    }
    return $filtered_image;
}, 10, 3);

// --- 6. Odstraň zbytečné WP hlavičky ---
add_action('init', function () {
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
});

// --- 7. Lokální fonty @font-face (ArchivoNarrow, Instrument Sans) ---
// Elementor Custom Fonts negeneruje font-weight range pro variable fonty.
// Tato pravidla zajistí správné matchování všech vah + italic/oblique variant.
add_action('wp_head', function () {
    if (is_admin()) return;
    $base = esc_url(content_url('uploads/2026/06'));
    echo "<style id='reboost-local-fonts'>\n";
    // "ArchivoNarrow" = custom font název v Elementoru
    echo "@font-face{font-family:'ArchivoNarrow';font-style:normal;font-weight:100 900;src:url('{$base}/ArchivoNarrow-VariableFont_wght.ttf') format('truetype');font-display:swap;}\n";
    echo "@font-face{font-family:'ArchivoNarrow';font-style:italic;font-weight:100 900;src:url('{$base}/ArchivoNarrow-Italic-VariableFont_wght.ttf') format('truetype');font-display:swap;}\n";
    echo "@font-face{font-family:'ArchivoNarrow';font-style:oblique;font-weight:100 900;src:url('{$base}/ArchivoNarrow-Italic-VariableFont_wght.ttf') format('truetype');font-display:swap;}\n";
    // "Archivo Narrow" = Google Fonts název (s mezerou) – globální Elementor presety ho používají
    echo "@font-face{font-family:'Archivo Narrow';font-style:normal;font-weight:100 900;src:url('{$base}/ArchivoNarrow-VariableFont_wght.ttf') format('truetype');font-display:swap;}\n";
    echo "@font-face{font-family:'Archivo Narrow';font-style:italic;font-weight:100 900;src:url('{$base}/ArchivoNarrow-Italic-VariableFont_wght.ttf') format('truetype');font-display:swap;}\n";
    echo "@font-face{font-family:'Archivo Narrow';font-style:oblique;font-weight:100 900;src:url('{$base}/ArchivoNarrow-Italic-VariableFont_wght.ttf') format('truetype');font-display:swap;}\n";
    // "Instrument Sans" – lokální variable font (nahrazen Google Fonts)
    echo "@font-face{font-family:'Instrument Sans';font-style:normal;font-weight:100 900;src:url('{$base}/InstrumentSans-VariableFont_wdthwght.ttf') format('truetype');font-display:swap;}\n";
    echo "@font-face{font-family:'Instrument Sans';font-style:italic;font-weight:100 900;src:url('{$base}/InstrumentSans-Italic-VariableFont_wdthwght.ttf') format('truetype');font-display:swap;}\n";
    echo "@font-face{font-family:'Instrument Sans';font-style:oblique;font-weight:100 900;src:url('{$base}/InstrumentSans-Italic-VariableFont_wdthwght.ttf') format('truetype');font-display:swap;}\n";
    echo "</style>\n";
}, 20);

// ==========================================
// KONEC výkonnostních optimalizací
// ==========================================

add_action('template_redirect', function () {
    if (is_admin() || is_feed() || is_preview()) {
        return;
    }

    if (!is_singular('post')) {
        return;
    }

    $post_id = get_queried_object_id();

    if (!$post_id) {
        return;
    }

    $cookie_name = 'article_viewed_' . $post_id;

    // Ochrana proti opakovanému započítání při refreshi.
    if (isset($_COOKIE[$cookie_name])) {
        return;
    }

    $views = (int) get_post_meta($post_id, 'article_views', true);
    update_post_meta($post_id, 'article_views', $views + 1);

    setcookie(
        $cookie_name,
        '1',
        time() + HOUR_IN_SECONDS,
        COOKIEPATH,
        COOKIE_DOMAIN
    );
});

// Elementor Loop / Posts – řazení podle počtu zobrazení článku
add_action('elementor/query/nejctenejsi', function( $query ) {

    $query->set('post_type', 'post');

    // Meta klíč, do kterého ukládáš počet zobrazení
    $query->set('meta_key', 'article_views');

    // Řazení podle čísla
    $query->set('orderby', 'meta_value_num');

    // Od nejčtenějšího po nejméně čtený
    $query->set('order', 'DESC');
});

///////////////////////////////////////////////////////////////////////////////////////////////////
// Doba čtení článků
/**
 * Shortcode: [reboost_read_time]
 *
 * Příklad:
 * [reboost_read_time]
 * [reboost_read_time label="Doba čtení: " suffix=" min" wpm="220"]
 * [reboost_read_time post_id="123"]
 */

if ( ! function_exists( 'reboost_get_reading_time_minutes' ) ) {
	function reboost_get_reading_time_minutes( $post_id = 0, $wpm = 200 ) {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		if ( ! $post_id ) {
			return 0;
		}

		$post = get_post( $post_id );

		if ( ! $post || 'publish' !== $post->post_status ) {
			return 0;
		}

		$content = $post->post_content;

		// Odstranění shortcodů a HTML tagů
		$content = strip_shortcodes( $content );
		$content = wp_strip_all_tags( $content );

		// Decode HTML entit
		$content = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );

		// Zredukuj vícenásobné mezery
		$content = preg_replace( '/\s+/u', ' ', trim( $content ) );

		if ( empty( $content ) ) {
			return 0;
		}

		// Počet slov – UTF-8 safe, funguje i pro češtinu
		preg_match_all( '/[\p{L}\p{N}\']+/u', $content, $matches );
		$word_count = ! empty( $matches[0] ) ? count( $matches[0] ) : 0;

		if ( $word_count <= 0 ) {
			return 0;
		}

		$wpm = absint( $wpm );

		if ( $wpm <= 0 ) {
			$wpm = 200;
		}

		$minutes = (int) ceil( $word_count / $wpm );

		return max( 1, $minutes );
	}
}

if ( ! function_exists( 'reboost_read_time_shortcode' ) ) {
	function reboost_read_time_shortcode( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'post_id'     =>  0,
				'label'       => 'Doba čtení:  ',
				'suffix'      => ' min',
				'wpm'         => 140,
				'show_icon'   => 'yes',
				'icon'        => '',
				'wrapper_tag' => 'span',
				'class'       => 'reboost-reading-time',
			),
			$atts,
			'reboost_read_time'
		);

		$minutes = reboost_get_reading_time_minutes( $atts['post_id'], $atts['wpm'] );

		if ( $minutes <= 0 ) {
			return '';
		}

		// Povolené HTML tagy pro wrapper
		$allowed_tags = array( 'span', 'div', 'p' );
		$tag = strtolower( tag_escape( $atts['wrapper_tag'] ) );

		if ( ! in_array( $tag, $allowed_tags, true ) ) {
			$tag = 'span';
		}

		$class = sanitize_html_class( $atts['class'] );

		$icon = '';

		if ( 'yes' === strtolower( $atts['show_icon'] ) && ! empty( $atts['icon'] ) ) {
			$icon = '<span class="reboost-reading-time__icon" aria-hidden="true">' . esc_html( $atts['icon'] ) . '</span> ';
		}

		$output  = '<' . $tag . ' class="' . esc_attr( $class ) . '">';
		$output .= $icon;
		$output .= '<span class="reboost-reading-time__label">' . esc_html( $atts['label'] ) . '</span>';
		$output .= '<span class="reboost-reading-time__value">' . esc_html( $minutes . $atts['suffix'] ) . '</span>';
		$output .= '</' . $tag . '>';

		return $output;
	}

	add_shortcode( 'reboost_read_time', 'reboost_read_time_shortcode' );
}