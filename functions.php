<?php
/**
 * Theme functions and definitions
 * @package HelloElementor
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


// 🔥 Claude API 설정 추가
define('CLAUDE_API_KEY', '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'); // 실제 API 키로 변경 필요
define('CLAUDE_API_URL', 'https://api.anthropic.com/v1/messages');
define('CLAUDE_API_VERSION', '2024-01-01');  // 최신 API 버전
define('CLAUDE_MODEL', 'claude-3-5-sonnet-20241022');  // 최신 모델

define('HELLO_ELEMENTOR_VERSION', '3.4.3');
define('EHP_THEME_SLUG', 'hello-elementor');
define('HELLO_THEME_PATH', get_template_directory());
define('HELLO_THEME_URL', get_template_directory_uri());
define('HELLO_THEME_ASSETS_PATH', HELLO_THEME_PATH . '/assets/');
define('HELLO_THEME_ASSETS_URL', HELLO_THEME_URL . '/assets/');
define('HELLO_THEME_SCRIPTS_PATH', HELLO_THEME_ASSETS_PATH . 'js/');
define('HELLO_THEME_SCRIPTS_URL', HELLO_THEME_ASSETS_URL . 'js/');
define('HELLO_THEME_STYLE_PATH', HELLO_THEME_ASSETS_PATH . 'css/');
define('HELLO_THEME_STYLE_URL', HELLO_THEME_ASSETS_URL . 'css/');
define('HELLO_THEME_IMAGES_PATH', HELLO_THEME_ASSETS_PATH . 'images/');
define('HELLO_THEME_IMAGES_URL', HELLO_THEME_ASSETS_URL . 'images/');

if (!isset($content_width)) {
    $content_width = 800;
}

if (!function_exists('hello_elementor_setup')) {
    function hello_elementor_setup()
    {
        if (is_admin()) {
            hello_maybe_update_theme_version_in_db();
        }
        if (apply_filters('hello_elementor_register_menus', true)) {
            register_nav_menus(['menu-1' => esc_html__('Header', 'hello-elementor')]);
            register_nav_menus(['menu-2' => esc_html__('Footer', 'hello-elementor')]);
        }
        if (apply_filters('hello_elementor_post_type_support', true)) {
            add_post_type_support('page', 'excerpt');
        }
        if (apply_filters('hello_elementor_add_theme_support', true)) {
            add_theme_support('post-thumbnails');
            add_theme_support('automatic-feed-links');
            add_theme_support('title-tag');
            add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'script', 'style']);
            add_theme_support('custom-logo', ['height' => 100, 'width' => 350, 'flex-height' => true, 'flex-width' => true]);
            add_theme_support('align-wide');
            add_theme_support('responsive-embeds');
            add_theme_support('editor-styles');
            add_editor_style('editor-styles.css');
            if (apply_filters('hello_elementor_add_woocommerce_support', true)) {
                add_theme_support('woocommerce');
                add_theme_support('wc-product-gallery-zoom');
                add_theme_support('wc-product-gallery-lightbox');
                add_theme_support('wc-product-gallery-slider');
            }
        }
    }
}
add_action('after_setup_theme', 'hello_elementor_setup');

function hello_maybe_update_theme_version_in_db()
{
    $theme_version_option_name = 'hello_theme_version';
    $hello_theme_db_version = get_option($theme_version_option_name);
    if (!$hello_theme_db_version || version_compare($hello_theme_db_version, HELLO_ELEMENTOR_VERSION, '<')) {
        update_option($theme_version_option_name, HELLO_ELEMENTOR_VERSION);
    }
}

if (!function_exists('hello_elementor_display_header_footer')) {
    function hello_elementor_display_header_footer()
    {
        $hello_elementor_header_footer = true;
        return apply_filters('hello_elementor_header_footer', $hello_elementor_header_footer);
    }
}

if (!function_exists('hello_elementor_scripts_styles')) {
    function hello_elementor_scripts_styles()
    {
        $min_suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        if (apply_filters('hello_elementor_enqueue_style', true)) {
            wp_enqueue_style('hello-elementor', get_template_directory_uri() . '/style' . $min_suffix . '.css', [], HELLO_ELEMENTOR_VERSION);
        }
        if (apply_filters('hello_elementor_enqueue_theme_style', true)) {
            wp_enqueue_style('hello-elementor-theme-style', get_template_directory_uri() . '/theme' . $min_suffix . '.css', [], HELLO_ELEMENTOR_VERSION);
        }
        if (hello_elementor_display_header_footer()) {
            wp_enqueue_style('hello-elementor-header-footer', get_template_directory_uri() . '/header-footer' . $min_suffix . '.css', [], HELLO_ELEMENTOR_VERSION);
        }
    }
}
add_action('wp_enqueue_scripts', 'hello_elementor_scripts_styles');

if (!function_exists('hello_elementor_register_elementor_locations')) {
    function hello_elementor_register_elementor_locations($elementor_theme_manager)
    {
        if (apply_filters('hello_elementor_register_elementor_locations', true)) {
            $elementor_theme_manager->register_all_core_location();
        }
    }
}
add_action('elementor/theme/register_locations', 'hello_elementor_register_elementor_locations');

if (!function_exists('hello_elementor_content_width')) {
    function hello_elementor_content_width()
    {
        $GLOBALS['content_width'] = apply_filters('hello_elementor_content_width', 800);
    }
}
add_action('after_setup_theme', 'hello_elementor_content_width', 0);

if (!function_exists('hello_elementor_add_description_meta_tag')) {
    function hello_elementor_add_description_meta_tag()
    {
        if (!apply_filters('hello_elementor_description_meta_tag', true))
            return;
        if (!is_singular())
            return;
        $post = get_queried_object();
        if (empty($post->post_excerpt))
            return;
        echo '<meta name="description" content="' . esc_attr(wp_strip_all_tags($post->post_excerpt)) . '">' . "\n";
    }
}
add_action('wp_head', 'hello_elementor_add_description_meta_tag');

// Settings page
require get_template_directory() . '/includes/settings-functions.php';
require get_template_directory() . '/includes/elementor-functions.php';

if (!function_exists('hello_elementor_customizer')) {
    function hello_elementor_customizer()
    {
        if (!is_customize_preview())
            return;
        if (!hello_elementor_display_header_footer())
            return;
        require get_template_directory() . '/includes/customizer-functions.php';
    }
}
add_action('init', 'hello_elementor_customizer');

if (!function_exists('hello_elementor_check_hide_title')) {
    function hello_elementor_check_hide_title($val)
    {
        if (defined('ELEMENTOR_VERSION')) {
            $current_doc = Elementor\Plugin::instance()->documents->get(get_the_ID());
            if ($current_doc && 'yes' === $current_doc->get_settings('hide_title')) {
                $val = false;
            }
        }
        return $val;
    }
}
add_filter('hello_elementor_page_title', 'hello_elementor_check_hide_title');

if (!function_exists('hello_elementor_body_open')) {
    function hello_elementor_body_open()
    {
        wp_body_open();
    }
}

require HELLO_THEME_PATH . '/theme.php';
HelloTheme\Theme::instance();

// ========================================
// 🔥 여행지 지도 + 구글 로그인 시스템 (완전 수정 버전)
// ========================================

// 🔧 에러 로깅 비활성화 (콘솔 오류 방지)
function travel_suppress_php_errors()
{
    // Google Maps 관련 오류만 숨김
    if (
        strpos($_SERVER['REQUEST_URI'], 'travel') !== false ||
        strpos($_SERVER['REQUEST_URI'], 'maps') !== false
    ) {
        error_reporting(E_ERROR | E_PARSE);
    }
}
add_action('init', 'travel_suppress_php_errors');

// 🔧 개선된 세션 관리
class TravelSessionManager
{
    private static $session_started = false;

    public static function start_session()
    {
        if (self::$session_started || session_id() || is_admin() || headers_sent()) {
            return;
        }

        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start([
                    'cookie_lifetime' => 7200,
                    'cookie_secure' => is_ssl(),
                    'cookie_httponly' => true,
                    'cookie_samesite' => 'Strict',
                    'use_only_cookies' => true
                ]);
                self::$session_started = true;
            }
        } catch (Exception $e) {
            // 조용히 실패 처리
        }
    }

    public static function get_session_id()
    {
        self::start_session();
        return session_id() ?: 'no-session';
    }

    public static function destroy_session()
    {
        if (session_id()) {
            $_SESSION = array();
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            self::$session_started = false;
        }
    }
}

// 초기화
add_action('init', function () {
    TravelSessionManager::start_session();

    if (!headers_sent()) {
        add_filter('auth_cookie_expiration', function ($length) {
            return 7200;
        });
    }
}, 1);

// 🔧 개선된 CORS 설정
function travel_add_cors_http_header()
{
    if (!headers_sent()) {
        header("Access-Control-Allow-Origin: " . get_site_url());
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-WP-Nonce");
        header("Access-Control-Allow-Credentials: true");
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: SAMEORIGIN");
    }
}
add_action('init', 'travel_add_cors_http_header');

// 🔧 향상된 인증 관리
class TravelAuthManager
{
    private static $auth_cache = array();
    private static $auth_check_in_progress = false;

    public static function is_authenticated()
    {
        if (self::$auth_check_in_progress) {
            return false;
        }

        self::$auth_check_in_progress = true;

        try {
            // WordPress 인증 우선 확인
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                self::$auth_cache['user_id'] = $user_id;
                self::$auth_check_in_progress = false;
                return $user_id;
            }

            // 세션 인증 확인
            TravelSessionManager::start_session();
            if (isset($_SESSION['user_id'])) {
                $session_user_id = intval($_SESSION['user_id']);
                $user = get_user_by('ID', $session_user_id);

                if ($user) {
                    wp_set_current_user($session_user_id);
                    wp_set_auth_cookie($session_user_id, true, is_ssl());
                    self::$auth_cache['user_id'] = $session_user_id;
                    self::$auth_check_in_progress = false;
                    return $session_user_id;
                } else {
                    unset($_SESSION['user_id']);
                }
            }

            self::$auth_check_in_progress = false;
            return false;

        } catch (Exception $e) {
            self::$auth_check_in_progress = false;
            return false;
        }
    }

    public static function check_permission()
    {
        return self::is_authenticated() !== false;
    }

    public static function clear_cache()
    {
        self::$auth_cache = array();
    }
}

// 🔧 개선된 캐시 관리
class TravelCacheManager
{
    private static $cache_prefix = 'travel_cache_';
    private static $cache_version = '2.0';

    public static function get($key, $default = null)
    {
        try {
            $cache_key = self::$cache_prefix . md5($key . self::$cache_version);
            $cached = get_transient($cache_key);
            return $cached !== false ? $cached : $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    public static function set($key, $value, $expiration = 300)
    {
        try {
            $cache_key = self::$cache_prefix . md5($key . self::$cache_version);
            return set_transient($cache_key, $value, $expiration);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function delete($key)
    {
        try {
            $cache_key = self::$cache_prefix . md5($key . self::$cache_version);
            return delete_transient($cache_key);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function clear_all()
    {
        try {
            global $wpdb;
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                    '_transient_' . self::$cache_prefix . '%',
                    '_transient_timeout_' . self::$cache_prefix . '%'
                )
            );
        } catch (Exception $e) {
            // 조용히 실패 처리
        }
    }
}

// Google Maps API Key
function travel_maps_get_api_key()
{
    return 'AIzaSyCKge-X-CFVTsCRlPz1tG-56xk2gtK2FXc';
}

// 🔧 안전한 이미지 업로드 처리
function travel_maps_handle_image_uploads($files, $place_id)
{
    $image_files = null;

    // 다양한 FormData 키 형식 지원
    if (isset($files['images'])) {
        $image_files = $files['images'];
    } elseif (isset($files['images[]'])) {
        $image_files = $files['images[]'];
    } else {
        foreach ($files as $key => $file) {
            if (strpos($key, 'image') !== false) {
                $image_files = $file;
                break;
            }
        }
    }

    if (empty($image_files)) {
        return array();
    }

    $uploaded_images = array();
    $max_images = 5;
    $max_size = 5 * 1024 * 1024;
    $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');

    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    if (!function_exists('wp_generate_attachment_metadata')) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    }

    $files_array = $image_files;
    $file_count = is_array($files_array['name']) ? count($files_array['name']) : 1;

    for ($i = 0; $i < min($file_count, $max_images); $i++) {
        try {
            if (is_array($files_array['name'])) {
                $file = array(
                    'name' => $files_array['name'][$i],
                    'type' => $files_array['type'][$i],
                    'tmp_name' => $files_array['tmp_name'][$i],
                    'error' => $files_array['error'][$i],
                    'size' => $files_array['size'][$i]
                );
            } else {
                $file = $files_array;
            }

            if ($file['error'] !== UPLOAD_ERR_OK)
                continue;
            if ($file['size'] > $max_size)
                continue;
            if (!in_array($file['type'], $allowed_types))
                continue;

            $filename = sanitize_file_name($file['name']);
            $filename = preg_replace('/[^a-zA-Z0-9가-힣._-]/', '', $filename);
            $filename = current_time('YmdHis') . '_' . $place_id . '_' . $filename;
            $file['name'] = $filename;

            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($file, $upload_overrides);

            if ($movefile && !isset($movefile['error'])) {
                $attachment = array(
                    'guid' => $movefile['url'],
                    'post_mime_type' => $file['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );

                $attach_id = wp_insert_attachment($attachment, $movefile['file']);

                if ($attach_id) {
                    $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
                    wp_update_attachment_metadata($attach_id, $attach_data);

                    $thumb_url = wp_get_attachment_image_url($attach_id, 'medium');
                    $full_url = wp_get_attachment_url($attach_id);

                    $uploaded_images[] = array(
                        'id' => $attach_id,
                        'url' => $full_url,
                        'thumb' => $thumb_url ?: $full_url,
                        'filename' => $filename,
                        'size' => $file['size']
                    );
                }
            }

            if (!is_array($files_array['name']))
                break;
        } catch (Exception $e) {
            continue; // 개별 파일 업로드 실패시 계속 진행
        }
    }

    return $uploaded_images;
}


// 🔥 Claude용 프롬프트 생성
function create_claude_prompt($query, $db_results)
{
    $has_our_data = !empty($db_results);

    if ($has_our_data) {
        $db_info = "📍 **아여기에 등록된 실제 이용후기 기반 정보:**\n";
        foreach ($db_results as $index => $result) {
            $place = $result['place'];
            $rating = $place['best_age_rating'] > 0 ? $place['best_age_rating'] : 'N/A';
            $reviews = $place['review_count'] > 0 ? $place['review_count'] : 0;

            $db_info .= ($index + 1) . ". **{$place['title']}** ({$place['address']})\n";
            $db_info .= "   - 실제 부모님들의 연령별 평점: {$rating}/10\n";
            $db_info .= "   - 검증된 후기 수: {$reviews}개\n";
            $db_info .= "   - 여행 유형: {$place['travel_category']}\n";
            if (!empty($place['location_region'])) {
                $db_info .= "   - 위치: {$place['location_region']}\n";
            }
            $db_info .= "\n";
        }

        return "당신은 아이와 함께하는 여행 전문가입니다. 

사용자 질문: \"{$query}\"

{$db_info}

위 정보는 '아여기' 서비스에 실제로 아이와 함께 방문한 부모님들이 직접 등록하고 후기를 남긴 검증된 정보입니다.

다음 형식으로 답변해주세요:

**🎯 아여기 분석 결과**
아여기에 등록된 실제 이용후기를 분석해보니, [사용자 질문에 가장 적합한 장소들을 추천하고 구체적인 이유 설명]

**👨‍👩‍👧‍👦 부모님들의 실제 경험**
[위 장소들에 대한 실제 후기와 연령별 평점을 바탕으로 한 구체적인 팁]

**📝 여행 전문가 조언**
[아이와 함께하는 여행 관점에서 추가 팁과 주의사항]

답변은 친근하고 실용적으로 작성하되, 아여기의 검증된 정보임을 자연스럽게 강조해주세요.";
    } else {
        return "당신은 아이와 함께하는 여행 전문가입니다.

사용자 질문: \"{$query}\"

아여기 서비스에는 해당 질문과 직접 관련된 장소 정보가 없습니다.

다음 형식으로 답변해주세요:

**🔍 검색 결과**
아여기에 등록된 장소 중에는 정확히 일치하는 곳이 없지만, 일반적인 여행 조언을 드리겠습니다.

**👨‍👩‍👧‍👦 전문가 추천**
[사용자 질문에 대한 일반적인 여행 조언과 추천사항]

**💡 아여기 활용 팁**
아여기에 더 많은 정보가 등록되면 실제 부모님들의 후기를 바탕으로 더 정확한 추천을 받을 수 있습니다.

답변은 친근하고 실용적으로 작성해주세요.";
    }
}



// 🔥 진짜 RAG 방식으로 수정된 Claude 분석 함수
// 🔥 Python RAG API 연동 함수
function analyze_with_claude_enhanced($query, $all_places)
{
    // Python Backend API URL (로컬 테스트용)
    $python_api_url = 'https://iron-land-ai-f.onrender.com/chat';

    try {
        $response = wp_remote_post($python_api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'query' => $query,
                'history' => array()
            )),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log('RAG API Error: ' . $response->get_error_message());
            return array(
                'success' => true,
                'main_content' => array(
                    'content' => "죄송합니다. AI 서버와 연결할 수 없습니다. (Python Backend가 실행 중인지 확인해주세요)\n\n" . $response->get_error_message(),
                    'source' => 'System Error'
                ),
                'our_places' => array(
                    'places' => array(),
                    'message' => ''
                )
            );
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['answer'])) {
            $answer_content = $result['answer'];

            // 소스 링크 추가
            if (!empty($result['sources'])) {
                $answer_content .= "\n\n<div class='ai-sources' style='margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee;'>";
                $answer_content .= "<strong>참고한 철산랜드 기록:</strong><ul style='list-style: none; padding-left: 0; margin-top: 5px;'>";
                foreach ($result['sources'] as $source) {
                    $title = $source['title'] ?: '관련 영상/글';
                    $url = $source['url'] ?: '#';
                    $timestamp = $source['timestamp'] ? " ({$source['timestamp']})" : '';
                    $answer_content .= "<li style='margin-bottom: 5px;'>📺 <a href='{$url}' target='_blank' style='color: #0073aa; text-decoration: underline;'>{$title}{$timestamp}</a></li>";
                }
                $answer_content .= "</ul></div>";
            }

            return array(
                'success' => true,
                'main_content' => array(
                    'content' => $answer_content,
                    'source' => 'Iron Land AI'
                ),
                'our_places' => array(
                    'places' => array(),
                    'message' => 'AI 분석 결과'
                )
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Invalid API Response'
            );
        }

    } catch (Exception $e) {
        return array(
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}




// 🔥 장소별 상세 컨텍스트 생성 함수
function get_place_detailed_context($place_id, $place_basic_info)
{
    $context = "\n\n=== {$place_basic_info['title']} ===\n";
    $context .= "📍 위치: {$place_basic_info['address']}\n";
    $context .= "🏷️ 카테고리: {$place_basic_info['travel_category']}\n";

    // 실제 후기 데이터 가져오기
    $reviews_json = get_post_meta($place_id, 'reviews_data', true);
    $reviews = empty($reviews_json) ? array() : json_decode($reviews_json, true);

    if (!empty($reviews) && is_array($reviews)) {
        $context .= "💬 실제 부모님들의 후기 (" . count($reviews) . "개):\n";

        // 최근 3개 후기만 포함
        $recent_reviews = array_slice($reviews, 0, 3);
        foreach ($recent_reviews as $review) {
            if (!empty($review['text_review'])) {
                $context .= "- \"{$review['text_review']}\" - {$review['user_name']}\n";
            }

            // 연령별 평점 정보
            if (!empty($review['age_ratings'])) {
                $context .= "  연령별 추천: ";
                foreach ($review['age_ratings'] as $age => $rating) {
                    $age_name = get_age_name($age);
                    $context .= "{$age_name}: {$rating}/10점 ";
                }
                $context .= "\n";
            }
        }
    }

    // 연령별 통계 정보
    $age_stats = get_post_meta($place_id, 'age_statistics', true);
    if (!empty($age_stats) && is_array($age_stats)) {
        $context .= "📊 연령별 평균 평점:\n";
        foreach ($age_stats as $age => $stat) {
            $age_name = get_age_name($age);
            $context .= "- {$age_name}: {$stat['average']}/10점 ({$stat['count']}명 평가)\n";
        }
    }

    $context .= "\n" . str_repeat("-", 50) . "\n";

    return $context;
}

// 연령대 이름 변환 함수
function get_age_name($age_key)
{
    $age_names = array(
        'age_3_4' => '3-4세',
        'age_5_6' => '5-6세',
        'age_7_9' => '7-9세',
        'age_10_12' => '10-12세',
        'age_13_15' => '13-15세'
    );
    return $age_names[$age_key] ?? $age_key;
}

// 🔥 RAG 방식 프롬프트 생성
function create_comprehensive_claude_prompt($query, $detailed_context, $relevant_places)
{
    return "당신은 '아여기' 서비스의 여행 전문가입니다. 아래는 실제 부모님들이 직접 방문하고 작성한 후기 데이터입니다.

🎯 사용자 질문: \"{$query}\"

📋 아여기 실제 후기 데이터:
{$detailed_context}

🔍 분석 지침:
- 위 실제 후기 내용을 바탕으로 답변해주세요
- 연령별 평점과 실제 이용 경험을 활용해주세요
- 구체적인 사용자 후기 내용을 인용해주세요
- 아여기에 등록된 검증된 정보임을 강조해주세요

📝 답변 형식:
**🎯 아여기 실제 후기 분석 결과**
[위 실제 후기 데이터를 바탕으로 한 구체적인 분석과 추천]

**👨‍👩‍👧‍👦 부모님들의 생생한 경험**
[실제 후기 내용을 인용하며 연령별 추천 사항 설명]

**💡 전문가 조언**
[종합적인 여행 팁과 주의사항]

실제 부모님들의 경험을 바탕으로 친근하고 신뢰할 수 있는 답변을 작성해주세요.";
}

// 데이터가 없는 경우 프롬프트
function create_general_claude_prompt($query)
{
    return "당신은 '아여기' 서비스의 여행 전문가입니다.

🎯 사용자 질문: \"{$query}\"

아여기 서비스에는 해당 질문과 관련된 실제 후기 데이터가 아직 없습니다.

📝 답변 형식:
**🔍 검색 결과**
아여기에 등록된 실제 후기는 없지만, 일반적인 여행 조언을 드리겠습니다.

**👨‍👩‍👧‍👦 전문가 추천**
[사용자 질문에 대한 일반적인 여행 조언]

**💡 아여기 활용 안내**
더 많은 부모님들이 후기를 등록하시면 실제 경험을 바탕으로 더 정확한 추천을 받을 수 있습니다.

친근하고 도움이 되는 답변을 작성해주세요.";
}







// 🔥 관련 장소 찾기 (초간단 텍스트 매칭)
function find_relevant_places_simple($query, $all_places)
{
    $query_lower = strtolower($query);
    $relevant_places = array();

    foreach ($all_places as $place) {
        $place_text = strtolower(
            $place['title'] . ' ' .
            $place['address'] . ' ' .
            $place['location_region'] . ' ' .
            $place['travel_category']
        );

        // 간단한 포함 관계 체크
        $words = explode(' ', $query_lower);
        $matches = 0;

        foreach ($words as $word) {
            if (strlen($word) > 1 && strpos($place_text, $word) !== false) {
                $matches++;
            }
        }

        // 매칭되는 단어가 있거나, 리뷰가 많은 경우 포함
        if ($matches > 0 || $place['review_count'] > 5) {
            $place['match_score'] = $matches + ($place['review_count'] * 0.1);
            $relevant_places[] = $place;
        }
    }

    // 매칭 점수순으로 정렬하고 상위 5개만
    usort($relevant_places, function ($a, $b) {
        return $b['match_score'] - $a['match_score'];
    });

    return array_slice($relevant_places, 0, 5);
}

// 🔥 디버깅이 포함된 Claude API 호출
function call_claude_api($prompt)
{
    try {
        // API 키 확인
        $api_key = defined('CLAUDE_API_KEY') ? CLAUDE_API_KEY : '';
        if (empty($api_key)) {
            return array('success' => false, 'error' => 'API 키가 설정되지 않았습니다');
        }

        $data = array(
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 1000,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            )
        );

        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'  // 안정적인 버전 사용
            ),
            'body' => json_encode($data),
            'timeout' => 30
        ));

        // 응답 상태 확인
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('Claude API WP Error: ' . $error_message);
            return array('success' => false, 'error' => 'API 연결 실패: ' . $error_message);
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // HTTP 상태 코드 확인
        if ($response_code !== 200) {
            error_log('Claude API HTTP Error: ' . $response_code . ' - ' . $body);
            return array('success' => false, 'error' => "API 오류 (코드: {$response_code})");
        }

        $result = json_decode($body, true);

        // JSON 파싱 확인
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Claude API JSON Error: ' . json_last_error_msg());
            return array('success' => false, 'error' => 'API 응답 파싱 실패');
        }

        // 응답 구조 확인
        if (isset($result['content'][0]['text'])) {
            return array(
                'success' => true,
                'content' => $result['content'][0]['text']
            );
        } else {
            error_log('Claude API Response Structure: ' . print_r($result, true));
            return array('success' => false, 'error' => 'API 응답 형식이 예상과 다릅니다');
        }

    } catch (Exception $e) {
        error_log('Claude API Exception: ' . $e->getMessage());
        return array('success' => false, 'error' => 'API 호출 중 예외 발생: ' . $e->getMessage());
    }
}



// 🚀 개선된 장소 데이터 조회
function travel_maps_get_places_data()
{
    $cache_key = 'places_data_v3';
    $cached_data = TravelCacheManager::get($cache_key);

    if ($cached_data !== null) {
        return $cached_data;
    }

    try {
        $trashed_ids = get_posts(array(
            'post_type' => 'travel_place',
            'post_status' => 'trash',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));

        $places = get_posts(array(
            'post_type' => 'travel_place',
            'posts_per_page' => 1000,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
            'post__not_in' => $trashed_ids, // 🔥 삭제된 포스트 명시적 제외
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'place_latitude',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => 'place_longitude',
                    'compare' => 'EXISTS'
                )
            )
        ));

        $data = array();
        foreach ($places as $place) {
            $lat = get_post_meta($place->ID, 'place_latitude', true);
            $lng = get_post_meta($place->ID, 'place_longitude', true);

            if ($lat && $lng) {
                $age_stats = get_post_meta($place->ID, 'age_statistics', true);
                if (is_string($age_stats) && !empty($age_stats)) {
                    $decoded = json_decode($age_stats, true);
                    $age_stats = is_array($decoded) ? $decoded : array();
                } elseif (!is_array($age_stats)) {
                    $age_stats = array();
                }

                $best_rating = 0;
                if (!empty($age_stats) && is_array($age_stats)) {
                    foreach ($age_stats as $stat) {
                        if (isset($stat['average']) && $stat['average'] > $best_rating) {
                            $best_rating = $stat['average'];
                        }
                    }
                }

                $data[] = array(
                    'id' => $place->ID,
                    'title' => $place->post_title,
                    'lat' => floatval($lat),
                    'lng' => floatval($lng),
                    'address' => get_post_meta($place->ID, 'place_address', true),
                    'contact' => get_post_meta($place->ID, 'place_contact', true),
                    'website' => get_post_meta($place->ID, 'place_website', true),
                    'hours' => get_post_meta($place->ID, 'place_hours', true),
                    'travel_category' => get_post_meta($place->ID, 'travel_category', true),
                    'location_country' => get_post_meta($place->ID, 'location_country', true),
                    'location_region' => get_post_meta($place->ID, 'location_region', true),
                    'review_count' => intval(get_post_meta($place->ID, 'review_count', true) ?: 0),
                    'best_age_rating' => round($best_rating, 1)
                );
            }
        }

        // 10분간 캐시
        TravelCacheManager::set($cache_key, $data, 600);

        return $data;

    } catch (Exception $e) {
        return array();
    }
}



// 🔥 브랜드 강조 종합 답변 생성
function generate_comprehensive_response($query, $db_results, $ai_analysis)
{
    $has_our_data = !empty($db_results);

    if ($has_our_data) {
        // 아여기 데이터가 있는 경우
        return array(
            'success' => true,
            'type' => 'comprehensive',
            'source_priority' => 'ayeogi_first',
            'web_results' => array(), // ← JavaScript 에러 방지용
            'main_content' => array(
                'source' => '아여기 등록 정보 + AI 분석',
                'content' => isset($ai_analysis['analysis']) ? $ai_analysis['analysis'] : '',
                'confidence' => 'high',
                'data_source' => '실제 부모님 후기 기반'
            ),
            'our_places' => array(
                'title' => '📍 분석에 사용된 아여기 등록 장소',
                'description' => '실제 부모님들의 후기가 있는 검증된 장소들입니다',
                'places' => $db_results,
                'total_reviews' => array_sum(array_column(array_column($db_results, 'place'), 'review_count')),
                'clickable' => true
            ),
            'meta' => array(
                'query' => $query,
                'primary_source' => 'ayeogi_verified_reviews',
                'search_time' => current_time('mysql'),
                'recommendation_strength' => 'strong'
            )
        );
    } else {
        // 아여기 데이터가 없는 경우
        return array(
            'success' => true,
            'type' => 'ai_only',
            'source_priority' => 'ai_knowledge',
            'web_results' => array(), // ← JavaScript 에러 방지용
            'main_content' => array(
                'source' => 'AI 일반 지식',
                'content' => isset($ai_analysis['analysis']) ? $ai_analysis['analysis'] : '',
                'confidence' => 'medium',
                'data_source' => '일반적인 여행 정보'
            ),
            'our_places' => array(
                'title' => '📍 아여기 등록 장소',
                'description' => '해당 질문과 직접 관련된 장소가 아직 등록되지 않았습니다',
                'places' => array(),
                'suggestion' => '이런 장소를 방문하시면 후기를 남겨주세요!'
            ),
            'meta' => array(
                'query' => $query,
                'primary_source' => 'ai_general_knowledge',
                'search_time' => current_time('mysql'),
                'recommendation_strength' => 'general'
            )
        );
    }
}




// 🔥 완전히 새로운 AI 검색 함수
function travel_maps_smart_search($request)
{
    $query = sanitize_text_field($request->get_param('query'));

    if (empty($query)) {
        return new WP_Error('empty_query', '검색어를 입력해주세요.', array('status' => 400));
    }

    // 캐시 확인
    $cache_key = 'smart_search_' . md5($query);
    $cached_result = TravelCacheManager::get($cache_key);

    if ($cached_result !== null) {
        return $cached_result;
    }

    try {
        // 1단계: 모든 DB 데이터를 Claude에게 제공
        $all_places = travel_maps_get_places_data();

        // 2단계: Claude가 판단해서 관련 장소 추천 + 일반 답변
        $ai_analysis = analyze_with_claude_enhanced($query, $all_places);

        // 3단계: 결과 반환
        TravelCacheManager::set($cache_key, $ai_analysis, 300);

        return $ai_analysis;

    } catch (Exception $e) {
        return new WP_Error('search_error', '검색 중 오류가 발생했습니다.', array('status' => 500));
    }
}

// 포인트 시스템
function travel_add_points($user_id, $points, $reason = '')
{
    try {
        $current_points = (int) get_user_meta($user_id, 'travel_points', true);
        $new_points = $current_points + $points;

        update_user_meta($user_id, 'travel_points', $new_points);

        $history = get_user_meta($user_id, 'travel_point_history', true) ?: array();
        $history[] = array(
            'points' => $points,
            'reason' => $reason,
            'date' => current_time('mysql'),
            'total' => $new_points
        );

        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }

        update_user_meta($user_id, 'travel_point_history', $history);

        // 캐시 무효화
        TravelCacheManager::delete('user_rankings_v2');

        return $new_points;
    } catch (Exception $e) {
        return false;
    }
}

// 사용자 통계 업데이트
function travel_update_user_stats($user_id)
{
    try {
        // 사용자가 등록한 장소 수
        $places_count = get_posts(array(
            'post_type' => 'travel_place',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'submitted_by_user',
                    'value' => $user_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));

        // 사용자가 작성한 리뷰 수 계산
        $reviews_count = 0;
        $recent_places = get_posts(array(
            'post_type' => 'travel_place',
            'posts_per_page' => 200,
            'post_status' => 'publish',
            'fields' => 'ids'
        ));

        foreach ($recent_places as $place_id) {
            $reviews_json = get_post_meta($place_id, 'reviews_data', true);
            if (!empty($reviews_json)) {
                $reviews = json_decode($reviews_json, true);
                if (is_array($reviews)) {
                    foreach ($reviews as $review) {
                        if (isset($review['user_id']) && $review['user_id'] == $user_id) {
                            $reviews_count++;
                        }
                    }
                }
            }
        }

        update_user_meta($user_id, 'travel_places_count', count($places_count));
        update_user_meta($user_id, 'travel_reviews_count', $reviews_count);
        update_user_meta($user_id, 'travel_stats_updated', current_time('mysql'));

    } catch (Exception $e) {
        // 조용히 실패 처리
    }
}

// 새 장소 등록
function travel_maps_add_new_place($request)
{
    $user_id = TravelAuthManager::is_authenticated();
    if (!$user_id) {
        return new WP_Error('auth_required', '로그인이 필요합니다.', array('status' => 401));
    }

    $data = array(
        'name' => sanitize_text_field($request->get_param('place_name')),
        'address' => sanitize_text_field($request->get_param('place_address')),
        'lat' => floatval($request->get_param('place_latitude')),
        'lng' => floatval($request->get_param('place_longitude')),
        'travel_category' => sanitize_text_field($request->get_param('travel_category')),
        'location_country' => sanitize_text_field($request->get_param('location_country')),
        'location_region' => sanitize_text_field($request->get_param('location_region'))
    );

    if (empty($data['name']) || empty($data['address']) || !$data['lat'] || !$data['lng']) {
        return new WP_Error('missing_data', '필수 정보가 누락되었습니다.', array('status' => 400));
    }

    try {
        $post_id = wp_insert_post(array(
            'post_title' => $data['name'],
            'post_type' => 'travel_place',
            'post_status' => 'publish',
            'meta_input' => array(
                'place_address' => $data['address'],
                'place_contact' => sanitize_text_field($request->get_param('place_contact')),
                'place_website' => esc_url_raw($request->get_param('place_website')),
                'place_hours' => sanitize_textarea_field($request->get_param('place_hours')),
                'place_latitude' => $data['lat'],
                'place_longitude' => $data['lng'],
                'travel_category' => $data['travel_category'],
                'location_country' => $data['location_country'],
                'location_region' => $data['location_region'],
                'reviews_data' => '',
                'review_count' => 0,
                'age_statistics' => array(),
                'submission_ip' => $_SERVER['REMOTE_ADDR'],
                'submission_user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'submitted_by_user' => $user_id
            )
        ));

        if (!is_wp_error($post_id)) {
            travel_add_points($user_id, 50, '새 여행지 등록');
            travel_maps_send_new_place_notification($post_id);

            // 캐시 무효화
            TravelCacheManager::clear_all();

            return array(
                'success' => true,
                'message' => '등록 완료! 사이트에 바로 표시됩니다. +50 포인트 획득!',
                'place_id' => $post_id,
                'points_earned' => 50
            );
        }

        return new WP_Error('creation_failed', '등록에 실패했습니다.', array('status' => 500));

    } catch (Exception $e) {
        return new WP_Error('creation_failed', '등록 중 오류가 발생했습니다.', array('status' => 500));
    }
}

// 통계 계산
function travel_maps_calc_stats($reviews)
{
    $ages = ['age_3_4', 'age_5_6', 'age_7_9', 'age_10_12', 'age_13_15'];
    $stats = array();

    if (empty($reviews) || !is_array($reviews))
        return $stats;

    foreach ($ages as $age) {
        $total = 0;
        $count = 0;

        foreach ($reviews as $review) {
            if (!isset($review['age_ratings'][$age]))
                continue;
            $rating = intval($review['age_ratings'][$age]);
            if ($rating > 0 && $rating <= 10) {
                $total += $rating;
                $count++;
            }
        }

        if ($count > 0) {
            $stats[$age] = array(
                'average' => round($total / $count, 1),
                'count' => $count
            );
        }
    }

    if (!empty($stats)) {
        uasort($stats, function ($a, $b) {
            return $b['average'] <=> $a['average'];
        });
    }

    return $stats;
}

// 🔧 완전히 수정된 리뷰 시스템
function travel_maps_add_review($request)
{
    $user_id = TravelAuthManager::is_authenticated();
    if (!$user_id) {
        return new WP_Error('auth_required', '로그인이 필요합니다.', array('status' => 401));
    }

    $place_id = intval($request->get_param('place_id'));
    $age_ratings_json = $request->get_param('age_ratings');
    $text_review = sanitize_textarea_field($request->get_param('text_review') ?: '');
    $user_ip = $_SERVER['REMOTE_ADDR'];

    // 연령별 평점 처리
    if (is_string($age_ratings_json)) {
        $age_ratings = json_decode($age_ratings_json, true);
    } else {
        $age_ratings = $age_ratings_json;
    }

    if (empty($age_ratings) || !is_array($age_ratings)) {
        $age_ratings = array();
    }

    // 연령별 평점 또는 텍스트 리뷰 중 하나는 있어야 함
    if (empty($age_ratings) && empty($text_review)) {
        return new WP_Error('invalid_data', '연령별 추천 또는 후기 내용 중 하나는 필요합니다.', array('status' => 400));
    }

    // 키 매핑
    $age_mapping = array(
        'age_rating_3_4' => 'age_3_4',
        'age_rating_5_6' => 'age_5_6',
        'age_rating_7_9' => 'age_7_9',
        'age_rating_10_12' => 'age_10_12',
        'age_rating_13_15' => 'age_13_15',
        'age_3_4' => 'age_3_4',
        'age_5_6' => 'age_5_6',
        'age_7_9' => 'age_7_9',
        'age_10_12' => 'age_10_12',
        'age_13_15' => 'age_13_15'
    );

    $normalized_ratings = array();
    foreach ($age_ratings as $key => $value) {
        if (isset($age_mapping[$key])) {
            $rating = intval($value);
            if ($rating > 0 && $rating <= 10) {
                $normalized_ratings[$age_mapping[$key]] = $rating;
            }
        }
    }

    $post = get_post($place_id);
    if (!$post || $post->post_type !== 'travel_place') {
        return new WP_Error('invalid_place', '유효하지 않은 장소입니다.', array('status' => 404));
    }

    try {
        $uploaded_images = array();
        if (!empty($_FILES)) {
            $uploaded_images = travel_maps_handle_image_uploads($_FILES, $place_id);
        }

        $current_user = wp_get_current_user();
        $user_name = get_user_meta($user_id, 'travel_nickname', true);
        if (empty($user_name)) {
            $user_name = $current_user->display_name;
            if (empty($user_name)) {
                $user_name = $current_user->user_login;
            }
            update_user_meta($user_id, 'travel_nickname', $user_name);
        }

        $review_data = array(
            'age_ratings' => $normalized_ratings,
            'text_review' => $text_review,
            'user_name' => $user_name,
            'timestamp' => current_time('mysql'),
            'user_ip' => $user_ip,
            'user_id' => $user_id,
            'likes_count' => 0,
            'liked_by_users' => array(),
            'images' => $uploaded_images,
            'id' => uniqid('review_')
        );

        $reviews_json = get_post_meta($place_id, 'reviews_data', true);
        $reviews = empty($reviews_json) ? array() : json_decode($reviews_json, true);
        if (!is_array($reviews))
            $reviews = array();

        // 🔥 새로운 리뷰 처리 로직
        $found_index = -1;
        $existing_review = null;

        // 기존 리뷰 찾기
        foreach ($reviews as $index => $review) {
            if (isset($review['user_id']) && $review['user_id'] === $user_id) {
                $found_index = $index;
                $existing_review = $review;
                break;
            }
        }

        $points_earned = 0;
        $message_parts = array();

        if ($found_index !== -1) {
            // 기존 리뷰가 있는 경우 - 병합 처리
            $merged_review = $existing_review;
            $new_content_added = false;

            // 연령별 추천 처리
            if (!empty($normalized_ratings)) {
                $had_age_ratings = !empty($existing_review['age_ratings']);
                $merged_review['age_ratings'] = $normalized_ratings;

                if (!$had_age_ratings) {
                    $points_earned += 20;
                    $message_parts[] = '연령별 추천';
                    $new_content_added = true;
                }
            }

            // 텍스트 리뷰 처리
            if (!empty($text_review)) {
                $had_text_review = !empty($existing_review['text_review']);
                $merged_review['text_review'] = $text_review;

                if (!$had_text_review) {
                    $text_length = mb_strlen(trim($text_review));
                    if ($text_length >= 5) {
                        $text_points = min(50, max(10, floor($text_length / 20) * 5 + 10));
                        $points_earned += $text_points;
                        $message_parts[] = '후기 작성';
                        $new_content_added = true;
                    }
                }
            }

            // 이미지 처리
            if (!empty($uploaded_images)) {
                $had_images = !empty($existing_review['images']);

                // 기존 이미지 삭제
                if ($had_images && is_array($existing_review['images'])) {
                    foreach ($existing_review['images'] as $old_image) {
                        if (isset($old_image['id'])) {
                            wp_delete_attachment($old_image['id'], true);
                        }
                    }
                }

                $merged_review['images'] = $uploaded_images;

                if (!$had_images) {
                    $image_count = count($uploaded_images);
                    $image_points = min(50, max(20, $image_count * 10));
                    $points_earned += $image_points;
                    $new_content_added = true;
                }
            }

            // 병합된 리뷰로 업데이트
            $merged_review['timestamp'] = current_time('mysql');
            $reviews[$found_index] = $merged_review;

            if ($new_content_added) {
                $message = '새로운 ' . implode(' 및 ', $message_parts) . '이(가) 추가되었습니다.';
            } else {
                $message = '리뷰가 수정되었습니다.';
            }

        } else {
            // 새 리뷰 추가
            if (!empty($normalized_ratings)) {
                $points_earned += 20;
                $message_parts[] = '연령별 추천';
            }

            if (!empty($text_review)) {
                $text_length = mb_strlen(trim($text_review));
                if ($text_length >= 5) {
                    $text_points = min(50, max(10, floor($text_length / 20) * 5 + 10));
                    $points_earned += $text_points;
                    $message_parts[] = '후기 작성';
                }
            }

            if (!empty($uploaded_images)) {
                $image_count = count($uploaded_images);
                $image_points = min(50, max(20, $image_count * 10));
                $points_earned += $image_points;
            }

            array_unshift($reviews, $review_data);
            $message = '리뷰가 등록되었습니다.';

            travel_update_user_stats($user_id);
        }

        // 포인트 지급
        if ($points_earned > 0) {
            travel_add_points($user_id, $points_earned, '여행지 리뷰 작성');
            $message .= " +{$points_earned} 포인트 획득!";
        }

        $age_stats = travel_maps_calc_stats($reviews);

        $reviews_json_final = json_encode($reviews, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $stats_array = is_array($age_stats) ? $age_stats : array();

        update_post_meta($place_id, 'reviews_data', $reviews_json_final);
        update_post_meta($place_id, 'review_count', count($reviews));
        update_post_meta($place_id, 'age_statistics', $stats_array);

        // 캐시 무효화
        TravelCacheManager::clear_all();

        return array(
            'success' => true,
            'message' => $message,
            'uploaded_images' => count($uploaded_images),
            'images_info' => $uploaded_images,
            'points_earned' => $points_earned
        );

    } catch (Exception $e) {
        return new WP_Error('review_failed', '리뷰 등록 중 오류가 발생했습니다.', array('status' => 500));
    }
}

// 리뷰 추천 기능
function travel_maps_like_review($request)
{
    $user_id = TravelAuthManager::is_authenticated();
    if (!$user_id) {
        return new WP_Error('auth_required', '로그인이 필요합니다.', array('status' => 401));
    }

    $place_id = intval($request->get_param('place_id'));
    $review_id = sanitize_text_field($request->get_param('review_id'));

    if (!$place_id || !$review_id) {
        return new WP_Error('invalid_data', '필수 정보가 누락되었습니다.', array('status' => 400));
    }

    try {
        $post = get_post($place_id);
        if (!$post || $post->post_type !== 'travel_place') {
            return new WP_Error('invalid_place', '유효하지 않은 장소입니다.', array('status' => 404));
        }

        $reviews_json = get_post_meta($place_id, 'reviews_data', true);
        $reviews = empty($reviews_json) ? array() : json_decode($reviews_json, true);
        if (!is_array($reviews)) {
            return new WP_Error('no_reviews', '리뷰가 없습니다.', array('status' => 404));
        }

        $review_found = false;
        $action_taken = '';

        foreach ($reviews as &$review) {
            if (isset($review['id']) && $review['id'] === $review_id) {
                $review_found = true;

                if (!isset($review['liked_by_users']) || !is_array($review['liked_by_users'])) {
                    $review['liked_by_users'] = array();
                }

                if (!isset($review['likes_count'])) {
                    $review['likes_count'] = 0;
                }

                $already_liked = in_array($user_id, $review['liked_by_users']);

                if ($already_liked) {
                    $review['liked_by_users'] = array_diff($review['liked_by_users'], array($user_id));
                    $review['liked_by_users'] = array_values($review['liked_by_users']);
                    $review['likes_count'] = max(0, $review['likes_count'] - 1);
                    $action_taken = 'removed';
                } else {
                    $review['liked_by_users'][] = $user_id;
                    $review['likes_count'] = $review['likes_count'] + 1;
                    $action_taken = 'added';
                }

                break;
            }
        }

        if (!$review_found) {
            return new WP_Error('review_not_found', '해당 리뷰를 찾을 수 없습니다.', array('status' => 404));
        }

        $reviews_json_final = json_encode($reviews, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        update_post_meta($place_id, 'reviews_data', $reviews_json_final);

        $message = ($action_taken === 'added') ? '추천하였습니다.' : '추천을 취소하였습니다.';

        $new_likes_count = 0;
        foreach ($reviews as $r) {
            if (isset($r['id']) && $r['id'] === $review_id) {
                $new_likes_count = $r['likes_count'];
                break;
            }
        }

        $response = array(
            'success' => true,
            'message' => $message,
            'uploaded_images' => count($uploaded_images),
            'images_info' => $uploaded_images
        );

        // 포인트 정보 추가
        if ($points_earned > 0) {
            $response['points'] = $points_earned;
            $response['reason'] = '여행지 리뷰 작성';
        }

        return $response;

    } catch (Exception $e) {
        return new WP_Error('like_failed', '추천 처리 중 오류가 발생했습니다.', array('status' => 500));
    }
}

// 리뷰 조회
function travel_maps_get_reviews($request)
{
    $place_id = $request->get_param('place_id');

    try {
        $post = get_post($place_id);

        if (!$post || $post->post_type !== 'travel_place') {
            return new WP_Error('invalid_place', '유효하지 않은 장소입니다.', array('status' => 404));
        }

        $reviews_json = get_post_meta($place_id, 'reviews_data', true) ?: '[]';
        $reviews = json_decode($reviews_json, true) ?: array();

        $current_user_id = TravelAuthManager::is_authenticated();

        foreach ($reviews as &$review) {
            unset($review['user_ip']);

            if (!isset($review['id'])) {
                $review['id'] = uniqid('review_');
            }

            $review['time_ago'] = human_time_diff(strtotime($review['timestamp']), current_time('timestamp')) . ' 전';

            if (!isset($review['likes_count'])) {
                $review['likes_count'] = 0;
            }

            $review['user_liked'] = false;
            if ($current_user_id && isset($review['liked_by_users']) && is_array($review['liked_by_users'])) {
                $review['user_liked'] = in_array($current_user_id, $review['liked_by_users']);
            }

            unset($review['liked_by_users'], $review['user_id']);
        }

        usort($reviews, function ($a, $b) {
            $likes_a = isset($a['likes_count']) ? $a['likes_count'] : 0;
            $likes_b = isset($b['likes_count']) ? $b['likes_count'] : 0;

            if ($likes_a === $likes_b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            }

            return $likes_b - $likes_a;
        });

        $age_stats = get_post_meta($place_id, 'age_statistics', true);
        if (is_string($age_stats) && !empty($age_stats)) {
            $decoded = json_decode($age_stats, true);
            $age_stats = is_array($decoded) ? $decoded : array();
        } elseif (!is_array($age_stats)) {
            $age_stats = array();
        }

        $age_names = ['age_3_4' => '3-4세', 'age_5_6' => '5-6세', 'age_7_9' => '7-9세', 'age_10_12' => '10-12세', 'age_13_15' => '13-15세'];
        $named_stats = array();
        foreach ($age_stats as $key => $stat) {
            $named_stats[$age_names[$key] ?? $key] = $stat;
        }

        return array(
            'reviews' => $reviews,
            'total_reviews' => count($reviews),
            'age_statistics' => $named_stats,
            'statistics' => $named_stats
        );

    } catch (Exception $e) {
        return new WP_Error('review_get_failed', '리뷰 조회 중 오류가 발생했습니다.', array('status' => 500));
    }
}

// 기존 리뷰 확인
function travel_maps_check_review($request)
{
    $user_id = TravelAuthManager::is_authenticated();
    if (!$user_id) {
        return array('has_review' => false);
    }

    $place_id = $request->get_param('place_id');

    try {
        $reviews_json = get_post_meta($place_id, 'reviews_data', true) ?: '[]';
        $reviews = json_decode($reviews_json, true) ?: array();

        foreach ($reviews as $review) {
            if (isset($review['user_id']) && $review['user_id'] === $user_id) {
                unset($review['user_ip'], $review['liked_by_users'], $review['user_id']);
                return array('has_review' => true, 'review_data' => $review);
            }
        }

        return array('has_review' => false);

    } catch (Exception $e) {
        return array('has_review' => false);
    }
}

// 여행유형 목록 API
function travel_maps_get_categories()
{
    $cache_key = 'categories_v3';
    $cached = TravelCacheManager::get($cache_key);

    if ($cached !== null) {
        return $cached;
    }

    try {
        global $wpdb;
        $categories = $wpdb->get_results("
            SELECT meta_value as category, COUNT(*) as count 
            FROM {$wpdb->postmeta} pm 
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
            WHERE p.post_type = 'travel_place' 
            AND p.post_status = 'publish' 
            AND pm.meta_key = 'travel_category' 
            AND pm.meta_value != '' 
            GROUP BY pm.meta_value 
            ORDER BY count DESC
        ");

        $category_names = [
            'restaurant' => '음식점/카페',
            'education' => '교육/문화 체험',
            'city' => '도시 탐방',
            'accommodation' => '숙박시설',
            'activity' => '액티비티/모험',
            'nature' => '자연/야외 체험',
            'theme-park' => '테마파크/놀이시설',
            'healing' => '휴양/힐링'
        ];

        $result = array();
        foreach ($categories as $cat) {
            $result[] = array(
                'value' => $cat->category,
                'name' => $category_names[$cat->category] ?? $cat->category,
                'count' => intval($cat->count)
            );
        }

        TravelCacheManager::set($cache_key, $result, 600);
        return $result;

    } catch (Exception $e) {
        return array();
    }
}

// 국가 목록 API
function travel_maps_get_countries()
{
    $cache_key = 'countries_v3';
    $cached = TravelCacheManager::get($cache_key);

    if ($cached !== null) {
        return $cached;
    }

    try {
        global $wpdb;
        $countries = $wpdb->get_results("
            SELECT meta_value as country, COUNT(*) as count 
            FROM {$wpdb->postmeta} pm 
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
            WHERE p.post_type = 'travel_place' 
            AND p.post_status = 'publish' 
            AND pm.meta_key = 'location_country' 
            AND pm.meta_value != '' 
            GROUP BY pm.meta_value 
            ORDER BY count DESC
        ");

        $result = array();
        foreach ($countries as $country) {
            $display_name = ($country->country === '대한민국') ? '국내' : $country->country;
            $result[] = array(
                'value' => $country->country,
                'name' => $display_name,
                'count' => intval($country->count)
            );
        }

        TravelCacheManager::set($cache_key, $result, 600);
        return $result;

    } catch (Exception $e) {
        return array();
    }
}

// 지역 목록 API
function travel_maps_get_regions($request)
{
    $country = $request->get_param('country');

    if (!$country || $country === 'all') {
        return array();
    }

    $cache_key = 'regions_' . md5($country);
    $cached = TravelCacheManager::get($cache_key);

    if ($cached !== null) {
        return $cached;
    }

    try {
        global $wpdb;
        $regions = $wpdb->get_results($wpdb->prepare("
            SELECT r.meta_value as region, COUNT(*) as count 
            FROM {$wpdb->postmeta} c 
            JOIN {$wpdb->postmeta} r ON c.post_id = r.post_id 
            JOIN {$wpdb->posts} p ON c.post_id = p.ID 
            WHERE p.post_type = 'travel_place' 
            AND p.post_status = 'publish' 
            AND c.meta_key = 'location_country' 
            AND c.meta_value = %s 
            AND r.meta_key = 'location_region' 
            AND r.meta_value != '' 
            GROUP BY r.meta_value 
            ORDER BY count DESC
        ", $country));

        $result = array();
        foreach ($regions as $region) {
            $result[] = array(
                'value' => $region->region,
                'name' => $region->region,
                'count' => intval($region->count)
            );
        }

        TravelCacheManager::set($cache_key, $result, 600);
        return $result;

    } catch (Exception $e) {
        return array();
    }
}

// 🔥 필터 옵션 통합 API 함수 (새로 추가)
function travel_maps_get_filter_options($request)
{
    $cache_key = 'filter_options_v1';
    $cached = TravelCacheManager::get($cache_key);

    if ($cached !== null) {
        return $cached;
    }

    try {
        global $wpdb;

        // 국가 목록 가져오기
        $countries = $wpdb->get_results("
            SELECT DISTINCT meta_value as country
            FROM {$wpdb->postmeta} pm 
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
            WHERE p.post_type = 'travel_place' 
            AND p.post_status = 'publish' 
            AND pm.meta_key = 'location_country' 
            AND pm.meta_value != '' 
            AND pm.meta_value IS NOT NULL
            ORDER BY pm.meta_value
        ");

        // 지역 목록 가져오기
        $regions = $wpdb->get_results("
            SELECT DISTINCT meta_value as region
            FROM {$wpdb->postmeta} pm 
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
            WHERE p.post_type = 'travel_place' 
            AND p.post_status = 'publish' 
            AND pm.meta_key = 'location_region' 
            AND pm.meta_value != '' 
            AND pm.meta_value IS NOT NULL
            ORDER BY pm.meta_value
        ");

        // 배열로 변환
        $countries_array = array();
        foreach ($countries as $country) {
            $display_name = ($country->country === '대한민국') ? '국내' : $country->country;
            $countries_array[] = $country->country;
        }

        $regions_array = array();
        foreach ($regions as $region) {
            $regions_array[] = $region->region;
        }

        $result = array(
            'success' => true,
            'countries' => $countries_array,
            'regions' => $regions_array,
            'total_countries' => count($countries_array),
            'total_regions' => count($regions_array)
        );

        // 30분간 캐시
        TravelCacheManager::set($cache_key, $result, 1800);

        return $result;

    } catch (Exception $e) {
        return array(
            'success' => false,
            'countries' => array(),
            'regions' => array(),
            'error' => $e->getMessage()
        );
    }
}

// 전체 점수 계산
function travel_maps_calc_overall_score($age_stats, $review_count)
{
    if (empty($age_stats) || !is_array($age_stats))
        return 0;

    $total_score = 0;
    $total_participants = 0;

    foreach ($age_stats as $age_data) {
        if (isset($age_data['average'], $age_data['count'])) {
            $average = floatval($age_data['average']);
            $count = intval($age_data['count']);

            if ($average > 0 && $count > 0) {
                $total_score += $average * $count;
                $total_participants += $count;
            }
        }
    }

    return $total_participants === 0 ? 0 : $total_score / $total_participants;
}

// 랭킹 API
function travel_maps_get_rankings($request)
{
    $type = $request->get_param('type') ?: 'overall';
    $age_filter = $request->get_param('age_filter') ?: 'all';
    $category_filter = $request->get_param('category_filter') ?: 'all';
    $country_filter = $request->get_param('country_filter') ?: 'all';
    $region_filter = $request->get_param('region_filter') ?: 'all';
    $limit = intval($request->get_param('limit') ?: 10);

    $cache_key = 'rankings_' . md5($type . $age_filter . $category_filter . $country_filter . $region_filter . $limit);
    $cached = TravelCacheManager::get($cache_key);

    if ($cached !== null) {
        return $cached;
    }

    try {
        $args = array(
            'post_type' => 'travel_place',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );

        $meta_query = array('relation' => 'AND');

        if ($category_filter !== 'all') {
            $meta_query[] = array(
                'key' => 'travel_category',
                'value' => $category_filter,
                'compare' => '='
            );
        }

        if ($country_filter !== 'all') {
            $meta_query[] = array(
                'key' => 'location_country',
                'value' => $country_filter,
                'compare' => '='
            );
        }

        if ($region_filter !== 'all') {
            $meta_query[] = array(
                'key' => 'location_region',
                'value' => $region_filter,
                'compare' => '='
            );
        }

        if (!empty($meta_query) && count($meta_query) > 1) {
            $args['meta_query'] = $meta_query;
        }

        $places = get_posts($args);
        $rankings = array();

        foreach ($places as $place) {
            $lat = get_post_meta($place->ID, 'place_latitude', true);
            $lng = get_post_meta($place->ID, 'place_longitude', true);
            if (!$lat || !$lng)
                continue;

            $age_stats = get_post_meta($place->ID, 'age_statistics', true);
            if (is_string($age_stats) && !empty($age_stats)) {
                $decoded = json_decode($age_stats, true);
                $age_stats = is_array($decoded) ? $decoded : array();
            } elseif (!is_array($age_stats)) {
                $age_stats = array();
            }

            $review_count = get_post_meta($place->ID, 'review_count', true) ?: 0;

            $place_data = array(
                'id' => $place->ID,
                'title' => $place->post_title,
                'lat' => floatval($lat),
                'lng' => floatval($lng),
                'address' => get_post_meta($place->ID, 'place_address', true),
                'review_count' => $review_count,
                'created_date' => $place->post_date,
                'travel_category' => get_post_meta($place->ID, 'travel_category', true),
                'location_country' => get_post_meta($place->ID, 'location_country', true),
                'location_region' => get_post_meta($place->ID, 'location_region', true),
                // 🔥 연령별 통계 추가
                'age_statistics' => $age_stats
            );

            if ($type === 'recent') {
                $place_data['score'] = strtotime($place->post_date);
            } else {
                if ($age_filter !== 'all') {
                    $place_data['score'] = isset($age_stats[$age_filter]['average']) ? $age_stats[$age_filter]['average'] : 0;
                } else {
                    $place_data['score'] = travel_maps_calc_overall_score($age_stats, $review_count);
                }
            }

            if ($type === 'recent' || $place_data['score'] > 0) {
                $rankings[] = $place_data;
            }
        }

        usort($rankings, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $rankings = array_slice($rankings, 0, $limit);

        foreach ($rankings as $index => &$place) {
            $place['rank'] = $index + 1;
            if ($type !== 'recent') {
                $place['display_score'] = round($place['score'], 1);
                // 🔥 프론트엔드에서 사용할 필드명들 추가
                $place['best_age_rating'] = round($place['score'], 1);
                $place['average_rating'] = round($place['score'], 1);
                $place['rating'] = round($place['score'], 1);
            } else {
                $place['display_date'] = date('Y-m-d', strtotime($place['created_date']));
                // 🔥 최근 등록 타입에서도 평점 필드 추가
                $place['best_age_rating'] = 0;
                $place['average_rating'] = 0;
                $place['rating'] = 0;
            }
        }

        $result = array(
            'rankings' => $rankings,
            'type' => $type,
            'filters' => array(
                'age' => $age_filter,
                'category' => $category_filter,
                'country' => $country_filter,
                'region' => $region_filter
            )
        );

        TravelCacheManager::set($cache_key, $result, 300);
        return $result;

    } catch (Exception $e) {
        return array(
            'rankings' => array(),
            'type' => $type,
            'filters' => array()
        );
    }
}

// 사용자 포인트 랭킹 조회
function travel_maps_get_user_rankings($request)
{
    $limit = intval($request->get_param('limit') ?: 10);

    $cache_key = 'user_rankings_v3';
    $cached_data = TravelCacheManager::get($cache_key);

    if ($cached_data !== null) {
        return $cached_data;
    }

    try {
        $users = get_users(array(
            'meta_key' => 'travel_points',
            'meta_value' => 1,
            'meta_compare' => '>=',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'number' => $limit
        ));

        $rankings = array();
        $rank = 1;

        foreach ($users as $user) {
            $points = intval(get_user_meta($user->ID, 'travel_points', true));
            $nickname = get_user_meta($user->ID, 'travel_nickname', true) ?: $user->display_name ?: '익명';
            $places_count = intval(get_user_meta($user->ID, 'travel_places_count', true));
            $reviews_count = intval(get_user_meta($user->ID, 'travel_reviews_count', true));

            if ($places_count === 0 && $reviews_count === 0) {
                travel_update_user_stats($user->ID);
                $places_count = intval(get_user_meta($user->ID, 'travel_places_count', true));
                $reviews_count = intval(get_user_meta($user->ID, 'travel_reviews_count', true));
            }

            $rankings[] = array(
                'rank' => $rank,
                'user_id' => $user->ID,
                'nickname' => $nickname,
                'points' => $points,
                'places_count' => $places_count,
                'reviews_count' => $reviews_count,
                'join_date' => $user->user_registered
            );

            $rank++;
        }

        $result = array(
            'success' => true,
            'users' => $rankings,
            'total_users' => count($rankings),
            'cache_time' => current_time('mysql')
        );

        TravelCacheManager::set($cache_key, $result, 600);
        return $result;

    } catch (Exception $e) {
        return array(
            'success' => false,
            'users' => array(),
            'total_users' => 0,
            'error' => $e->getMessage()
        );
    }
}



// 인증 체크
function travel_auth_check($request)
{
    try {
        $user_id = TravelAuthManager::is_authenticated();

        if ($user_id) {
            $user_data = travel_get_user_data($user_id);
            $nonce = wp_create_nonce('travel_auth_' . $user_id);

            TravelSessionManager::start_session();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['last_activity'] = current_time('timestamp');

            return array(
                'logged_in' => true,
                'user_id' => $user_id,
                'user' => $user_data,
                'nonce' => $nonce,
                'timestamp' => current_time('timestamp'),
                'session_id' => TravelSessionManager::get_session_id()
            );
        } else {
            return array(
                'logged_in' => false,
                'user_id' => null,
                'user' => null,
                'nonce' => null,
                'timestamp' => current_time('timestamp'),
                'session_id' => TravelSessionManager::get_session_id()
            );
        }
    } catch (Exception $e) {
        return array(
            'logged_in' => false,
            'user_id' => null,
            'user' => null,
            'nonce' => null,
            'timestamp' => current_time('timestamp'),
            'session_id' => null
        );
    }
}

// 사용자 프로필 조회
function travel_get_user_profile($request)
{
    $user_id = TravelAuthManager::is_authenticated();
    if (!$user_id) {
        return new WP_Error('auth_required', '로그인이 필요합니다.', array('status' => 401));
    }

    $user_profile = travel_get_user_data($user_id);

    return array(
        'success' => true,
        'user' => $user_profile
    );
}

// 닉네임 변경
function travel_update_nickname($request)
{
    $user_id = TravelAuthManager::is_authenticated();
    if (!$user_id) {
        return new WP_Error('auth_required', '로그인이 필요합니다.', array('status' => 401));
    }

    $new_nickname = sanitize_text_field($request->get_param('nickname'));

    if (empty($new_nickname)) {
        return new WP_Error('empty_nickname', '닉네임을 입력해주세요.', array('status' => 400));
    }

    if (mb_strlen($new_nickname) < 2 || mb_strlen($new_nickname) > 20) {
        return new WP_Error('invalid_nickname', '닉네임은 2-20자로 입력해주세요.', array('status' => 400));
    }

    if (!preg_match('/^[가-힣a-zA-Z0-9._-]+$/', $new_nickname)) {
        return new WP_Error('invalid_chars', '닉네임에는 한글, 영어, 숫자, ., _, - 만 사용 가능합니다.', array('status' => 400));
    }

    try {
        $current_nickname = get_user_meta($user_id, 'travel_nickname', true);
        if ($current_nickname === $new_nickname) {
            return new WP_Error('same_nickname', '현재 닉네임과 동일합니다.', array('status' => 400));
        }

        $update_result = update_user_meta($user_id, 'travel_nickname', $new_nickname);

        if ($update_result === false) {
            return new WP_Error('update_failed', '닉네임 변경에 실패했습니다.', array('status' => 500));
        }

        // 닉네임 변경시 캐시 무효화
        TravelCacheManager::delete('user_rankings_v3');

        return array(
            'success' => true,
            'message' => '닉네임이 변경되었습니다!',
            'nickname' => $new_nickname,
            'nonce' => wp_create_nonce('travel_auth_' . $user_id)
        );

    } catch (Exception $e) {
        return new WP_Error('update_failed', '닉네임 변경 중 오류가 발생했습니다.', array('status' => 500));
    }
}

// 로그아웃
function travel_logout($request)
{
    try {
        wp_logout();
        wp_clear_auth_cookie();

        TravelSessionManager::destroy_session();

        if (isset($_COOKIE['wordpress_logged_in_' . COOKIEHASH])) {
            unset($_COOKIE['wordpress_logged_in_' . COOKIEHASH]);
            setcookie('wordpress_logged_in_' . COOKIEHASH, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        }

        TravelAuthManager::clear_cache();

        return array(
            'success' => true,
            'message' => '완전 로그아웃되었습니다.',
            'timestamp' => current_time('timestamp')
        );

    } catch (Exception $e) {
        return array(
            'success' => true,
            'message' => '로그아웃되었습니다.',
            'timestamp' => current_time('timestamp')
        );
    }
}

// 사용자 데이터 조회 함수
function travel_get_user_data($user_id)
{
    try {
        $user = get_userdata($user_id);

        if (!$user) {
            return null;
        }

        return array(
            'id' => $user_id,
            'email' => $user->user_email,
            'nickname' => get_user_meta($user_id, 'travel_nickname', true) ?: $user->display_name ?: $user->user_login,
            'profile_picture' => get_user_meta($user_id, 'profile_picture', true),
            'points' => (int) get_user_meta($user_id, 'travel_points', true),
            'join_date' => $user->user_registered,
            'login_method' => get_user_meta($user_id, 'login_method', true) ?: 'unknown'
        );
    } catch (Exception $e) {
        return null;
    }
}

// 관리자 메뉴 추가
function travel_maps_admin_menu()
{
    add_menu_page(
        '여행지 승인 관리',
        '여행지 승인',
        'manage_options',
        'travel-place-approval',
        'travel_maps_approval_page',
        'dashicons-location-alt',
        25
    );
}
add_action('admin_menu', 'travel_maps_admin_menu');

// 승인 대기 목록 페이지
function travel_maps_approval_page()
{
    // 🔥 수동 캐시 삭제 처리 추가
    if (isset($_POST['clear_cache'])) {
        TravelCacheManager::clear_all();
        echo '<div class="notice notice-success"><p>✅ 캐시가 모두 삭제되었습니다!</p></div>';
    }

    if (isset($_POST['action']) && isset($_POST['place_id'])) {
        $place_id = intval($_POST['place_id']);
        $action = sanitize_text_field($_POST['action']);

        if ($action === 'reject') {
            wp_update_post(array(
                'ID' => $place_id,
                'post_status' => 'trash'
            ));
            // 🔥 캐시 무효화 추가
            TravelCacheManager::clear_all();

            travel_maps_send_approval_email($place_id, 'rejected');
            echo '<div class="notice notice-error"><p>여행지가 삭제되었습니다.</p></div>';
        }
    }


    $pending_places = get_posts(array(
        'post_type' => 'travel_place',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    ));



    // 🧪 Claude API 테스트 함수 (임시)
    function test_claude_api()
    {
        $result = call_claude_api("안녕하세요. 간단한 테스트입니다.");

        if ($result['success']) {
            error_log('Claude API 테스트 성공: ' . $result['content']);
            return "✅ API 테스트 성공";
        } else {
            error_log('Claude API 테스트 실패: ' . $result['error']);
            return "❌ API 테스트 실패: " . $result['error'];
        }
    }

    // 테스트 API 엔드포인트
    add_action('rest_api_init', function () {
        register_rest_route('travel/v1', '/test-claude', array(
            'methods' => 'GET',
            'callback' => function () {
                return test_claude_api();
            },
            'permission_callback' => '__return_true'
        ));
    });


    // functions.php에 디버깅 함수 추가
    function travel_debug_api()
    {
        return array(
            'success' => true,
            'message' => 'API 연결 성공!',
            'timestamp' => current_time('mysql'),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION
        );
    }

    // 디버깅 엔드포인트 추가
    add_action('rest_api_init', function () {
        register_rest_route('travel/v1', '/debug', array(
            'methods' => 'GET',
            'callback' => 'travel_debug_api',
            'permission_callback' => '__return_true'
        ));
    });






    ?>
    <div class="wrap">
        <h1>🗺️ 여행지 관리</h1>

        <?php if (empty($pending_places)): ?>
            <div class="notice notice-info">
                <p>등록된 여행지가 없습니다.</p>
            </div>
        <?php else: ?>
            <p><strong>총 <?php echo count($pending_places); ?>개의 여행지가 등록되어 있습니다.</strong></p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 300px;">여행지 정보</th>
                        <th style="width: 200px;">위치 정보</th>
                        <th style="width: 150px;">등록일</th>
                        <th style="width: 200px;">관리 액션</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_places as $place):
                        $lat = get_post_meta($place->ID, 'place_latitude', true);
                        $lng = get_post_meta($place->ID, 'place_longitude', true);
                        $address = get_post_meta($place->ID, 'place_address', true);
                        $contact = get_post_meta($place->ID, 'place_contact', true);
                        $website = get_post_meta($place->ID, 'place_website', true);
                        $category = get_post_meta($place->ID, 'travel_category', true);
                        $country = get_post_meta($place->ID, 'location_country', true);
                        $region = get_post_meta($place->ID, 'location_region', true);

                        $category_names = array(
                            'restaurant' => '음식점/카페',
                            'education' => '교육/문화 체험',
                            'city' => '도시 탐방',
                            'accommodation' => '숙박시설',
                            'activity' => '액티비티/모험',
                            'nature' => '자연/야외 체험',
                            'theme-park' => '테마파크/놀이시설',
                            'healing' => '휴양/힐링'
                        );
                        ?>
                        <tr>
                            <td>
                                <strong
                                    style="color: #2271b1; font-size: 14px;"><?php echo esc_html($place->post_title); ?></strong>
                                <div style="margin-top: 5px; font-size: 12px; color: #666;">
                                    📍 <?php echo esc_html($address); ?><br>
                                    <?php if ($contact): ?>📞 <?php echo esc_html($contact); ?><br><?php endif; ?>
                                    <?php if ($website): ?>🌐 <a href="<?php echo esc_url($website); ?>"
                                            target="_blank">웹사이트</a><br><?php endif; ?>
                                    🎯 <?php echo $category_names[$category] ?? $category; ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 12px;">
                                    🌍 <?php echo ($country === '대한민국') ? '국내' : $country; ?><br>
                                    📍 <?php echo esc_html($region); ?><br>
                                    📊 <?php echo $lat; ?>, <?php echo $lng; ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 12px; color: #666;">
                                    <?php echo date('Y-m-d H:i', strtotime($place->post_date)); ?>
                                </div>
                            </td>
                            <td>
                                <form method="post" style="display: inline-block;">
                                    <input type="hidden" name="place_id" value="<?php echo $place->ID; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="button button-secondary"
                                        onclick="return confirm('이 여행지를 삭제하시겠습니까? 되돌릴 수 없습니다.')"
                                        style="background: #dc3545; border-color: #dc3545; color: white;">
                                        ❌ 삭제
                                    </button>
                                </form>
                                <div style="margin-top: 5px;">
                                    <a href="https://www.google.com/maps?q=<?php echo $lat; ?>,<?php echo $lng; ?>" target="_blank"
                                        class="button button-small">
                                        🗺️ 지도 확인
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div style="margin-top: 30px; padding: 15px; background: #f0f0f1; border-radius: 5px;">
            <h3>📊 통계 정보</h3>
            <?php
            $total_places = wp_count_posts('travel_place');
            $published = $total_places->publish;
            $pending = $total_places->draft;
            $rejected = $total_places->trash;
            ?>
            <p>
                ✅ <strong>등록된 여행지:</strong> <?php echo $published; ?>개<br>
                ❌ <strong>삭제된 여행지:</strong> <?php echo $rejected; ?>개
            </p>
        </div>

        <!-- 🔥 여기에 새로운 캐시 관리 div 추가 👇 -->
        <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
            <h3>🔧 캐시 관리</h3>
            <form method="post" style="display: inline;">
                <input type="hidden" name="clear_cache" value="1">
                <button type="submit" class="button button-secondary" onclick="return confirm('캐시를 모두 삭제하시겠습니까?')">
                    🗑️ 전체 캐시 삭제
                </button>
            </form>
            <p><small>⚠️ 삭제된 장소가 프론트에서 계속 보인다면 이 버튼을 클릭하세요.</small></p>
        </div>

    </div>
    <?php
}

// 승인/거부 이메일 발송
function travel_maps_send_approval_email($place_id, $status)
{
    try {
        $place = get_post($place_id);
        $admin_email = get_option('admin_email');

        if ($status === 'approved') {
            $subject = '[여행지 승인] ' . $place->post_title . ' 승인 완료';
            $message = "여행지 '{$place->post_title}'이(가) 승인되어 사이트에 게시되었습니다.\n\n";
            $message .= "승인 일시: " . current_time('Y-m-d H:i:s') . "\n";
            $message .= "관리자: " . wp_get_current_user()->display_name . "\n";
        } else {
            $subject = '[여행지 거부] ' . $place->post_title . ' 승인 거부';
            $message = "여행지 '{$place->post_title}'이(가) 승인 거부되었습니다.\n\n";
            $message .= "거부 일시: " . current_time('Y-m-d H:i:s') . "\n";
            $message .= "관리자: " . wp_get_current_user()->display_name . "\n";
        }

        wp_mail($admin_email, $subject, $message);
    } catch (Exception $e) {
        // 조용히 실패 처리
    }
}

// 새 등록 알림 이메일
function travel_maps_send_new_place_notification($place_id)
{
    try {
        $place = get_post($place_id);
        $admin_email = get_option('admin_email');
        $admin_url = admin_url('admin.php?page=travel-place-approval');

        $subject = '[새 여행지 등록] ' . $place->post_title . ' 승인 대기';
        $message = "새로운 여행지가 등록되어 승인을 기다리고 있습니다.\n\n";
        $message .= "여행지명: " . $place->post_title . "\n";
        $message .= "주소: " . get_post_meta($place_id, 'place_address', true) . "\n";
        $message .= "등록 일시: " . $place->post_date . "\n\n";
        $message .= "승인하려면 관리자 패널로 이동하세요:\n";
        $message .= $admin_url . "\n\n";

        wp_mail($admin_email, $subject, $message);
    } catch (Exception $e) {
        // 조용히 실패 처리
    }
}

// 승인 후 자동 작업
function travel_maps_on_place_approved($new_status, $old_status, $post)
{
    if ($post->post_type === 'travel_place' && $old_status === 'draft' && $new_status === 'publish') {
        update_post_meta($post->ID, 'approval_date', current_time('mysql'));
        update_post_meta($post->ID, 'approved_by', get_current_user_id());

        // 장소 승인시 등록자 통계 업데이트
        $submitted_by = get_post_meta($post->ID, 'submitted_by_user', true);
        if ($submitted_by) {
            travel_update_user_stats($submitted_by);
        }

        // 캐시 무효화
        TravelCacheManager::clear_all();
    }
}
add_action('transition_post_status', 'travel_maps_on_place_approved', 10, 3);

// 여행지 삭제/복원 시 캐시 무효화
function travel_maps_on_place_status_changed($new_status, $old_status, $post)
{
    if ($post->post_type === 'travel_place') {
        // publish <-> trash 변경 시에만 캐시 무효화
        if (
            ($old_status === 'publish' && $new_status === 'trash') ||
            ($old_status === 'trash' && $new_status === 'publish')
        ) {

            // 전체 캐시 무효화
            TravelCacheManager::clear_all();

            error_log("여행지 상태 변경: {$post->post_title} ({$old_status} → {$new_status})");
        }
    }
}
add_action('transition_post_status', 'travel_maps_on_place_status_changed', 10, 3);



// 관리자 패널 대시보드 위젯
function travel_maps_dashboard_widget()
{
    $pending_count = wp_count_posts('travel_place')->draft;

    echo '<div style="text-align: center;">';
    if ($pending_count > 0) {
        echo '<div style="background: #ff6b6b; color: white; padding: 15px; border-radius: 5px; margin-bottom: 10px;">';
        echo '<h3 style="margin: 0; color: white;">⚠️ 승인 대기 중</h3>';
        echo '<p style="margin: 5px 0; font-size: 18px;"><strong>' . $pending_count . '개</strong>의 여행지</p>';
        echo '</div>';

        echo '<a href="' . admin_url('admin.php?page=travel-place-approval') . '" class="button button-primary">';
        echo '승인 관리로 이동 →</a>';
    } else {
        echo '<div style="background: #28a745; color: white; padding: 15px; border-radius: 5px;">';
        echo '<h3 style="margin: 0; color: white;">✅ 모든 승인 완료</h3>';
        echo '<p style="margin: 5px 0;">승인 대기 중인 여행지가 없습니다</p>';
        echo '</div>';
    }
    echo '</div>';
}

function travel_maps_add_dashboard_widget()
{
    wp_add_dashboard_widget(
        'travel_maps_approval_widget',
        '🗺️ 여행지 승인 현황',
        'travel_maps_dashboard_widget'
    );
}
add_action('wp_dashboard_setup', 'travel_maps_add_dashboard_widget');

// 🔥 구글 로그인 처리 함수 (새로 추가)
function travel_google_login_handler($request)
{
    try {
        $google_id = sanitize_text_field($request->get_param('google_id'));
        $email = sanitize_email($request->get_param('email'));
        $nickname = sanitize_text_field($request->get_param('nickname'));
        $profile_image = esc_url_raw($request->get_param('profile_image'));

        if (empty($google_id) || empty($email)) {
            return new WP_Error('missing_data', '구글 로그인 정보가 누락되었습니다.', array('status' => 400));
        }

        // 기존 구글 사용자 확인
        $existing_user = get_users(array(
            'meta_key' => 'google_id',
            'meta_value' => $google_id,
            'number' => 1
        ));

        $user_id = null;
        $is_new_user = false;

        if (!empty($existing_user)) {
            // 기존 사용자 로그인
            $user_id = $existing_user[0]->ID;

            // 프로필 이미지 업데이트
            if (!empty($profile_image)) {
                update_user_meta($user_id, 'profile_picture', $profile_image);
            }

        } else {
            // 이메일로 기존 사용자 확인
            $user_by_email = get_user_by('email', $email);

            if ($user_by_email) {
                // 기존 이메일 사용자에 구글 ID 연결
                $user_id = $user_by_email->ID;
                update_user_meta($user_id, 'google_id', $google_id);
                update_user_meta($user_id, 'login_method', 'google');

                if (!empty($profile_image)) {
                    update_user_meta($user_id, 'profile_picture', $profile_image);
                }
            } else {
                // 새 사용자 생성
                $user_login = 'google_' . $google_id;
                $display_name = !empty($nickname) ? $nickname : '구글사용자_' . substr($google_id, -4);

                $user_id = wp_create_user($user_login, wp_generate_password(), $email);

                if (is_wp_error($user_id)) {
                    return new WP_Error('user_creation_failed', '사용자 생성에 실패했습니다.', array('status' => 500));
                }

                // 사용자 메타데이터 설정
                update_user_meta($user_id, 'google_id', $google_id);
                update_user_meta($user_id, 'travel_nickname', $display_name);
                update_user_meta($user_id, 'travel_points', 50); // 가입 보너스
                update_user_meta($user_id, 'login_method', 'google');
                update_user_meta($user_id, 'travel_places_count', 0);
                update_user_meta($user_id, 'travel_reviews_count', 0);

                if (!empty($profile_image)) {
                    update_user_meta($user_id, 'profile_picture', $profile_image);
                }

                // 사용자 정보 업데이트
                wp_update_user(array(
                    'ID' => $user_id,
                    'display_name' => $display_name,
                    'first_name' => '',
                    'last_name' => ''
                ));

                $is_new_user = true;
            }
        }

        if (!$user_id) {
            return new WP_Error('login_failed', '로그인 처리에 실패했습니다.', array('status' => 500));
        }

        // 로그인 처리
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true, is_ssl());

        // 세션 설정
        TravelSessionManager::start_session();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['login_method'] = 'google';
        $_SESSION['login_time'] = current_time('timestamp');

        // 사용자 데이터 조회
        $user_data = travel_get_user_data($user_id);
        $nonce = wp_create_nonce('travel_auth_' . $user_id);

        return array(
            'success' => true,
            'message' => $is_new_user ? '구글 회원가입이 완료되었습니다!' : '구글 로그인 성공!',
            'user' => $user_data,
            'nonce' => $nonce,
            'is_new_user' => $is_new_user,
            'session_id' => TravelSessionManager::get_session_id()
        );

    } catch (Exception $e) {
        return new WP_Error('google_login_error', '구글 로그인 처리 중 오류가 발생했습니다: ' . $e->getMessage(), array('status' => 500));
    }
}


// 🔧 개선된 REST API 등록 (에러 처리 강화)
function travel_maps_init_api()
{
    try {
        // 🔥 디버깅 엔드포인트 먼저 추가
        register_rest_route('travel/v1', '/debug', array(
            'methods' => 'GET',
            'callback' => function () {
                return array(
                    'success' => true,
                    'message' => 'API 연결 성공!',
                    'timestamp' => current_time('mysql'),
                    'wordpress_version' => get_bloginfo('version'),
                    'php_version' => PHP_VERSION,
                    'travel_api_loaded' => true
                );
            },
            'permission_callback' => '__return_true'
        ));

        // 기본 장소 관련 API
        register_rest_route('travel/v1', '/places', array(
            'methods' => 'GET',
            'callback' => 'travel_maps_get_places_data',
            'permission_callback' => '__return_true'
        ));

        // 🔥 스마트 검색 테스트 추가
        register_rest_route('travel/v1', '/smart-search-test', array(
            'methods' => 'GET',
            'callback' => function () {
                $query = "서울 아이와 가볼만한 곳";

                try {
                    // 1. 장소 데이터 로드
                    $all_places = travel_maps_get_places_data();

                    // 2. Claude API 호출
                    $ai_analysis = analyze_with_claude_enhanced($query, $all_places);

                    return array(
                        'success' => true,
                        'query' => $query,
                        'total_places' => count($all_places),
                        'ai_analysis' => $ai_analysis,
                        'test_type' => 'smart_search'
                    );

                } catch (Exception $e) {
                    return array(
                        'success' => false,
                        'error' => $e->getMessage(),
                        'line' => $e->getLine()
                    );
                }
            },
            'permission_callback' => '__return_true'
        ));

        // 🔥 GET 방식 스마트 검색 추가 (프론트엔드 테스트용)
        register_rest_route('travel/v1', '/smart-search', array(
            'methods' => 'GET',
            'callback' => function ($request) {
                $query = $request->get_param('query') ?: "서울 아이와 가볼만한 곳";

                try {
                    $all_places = travel_maps_get_places_data();
                    $ai_analysis = analyze_with_claude_enhanced($query, $all_places);

                    return $ai_analysis;

                } catch (Exception $e) {
                    return array(
                        'success' => false,
                        'error' => $e->getMessage()
                    );
                }
            },
            'permission_callback' => '__return_true'
        ));

        register_rest_route('travel/v1', '/add-place', array(
            'methods' => 'POST',
            'callback' => 'travel_maps_add_new_place',
            'permission_callback' => function () {
                return TravelAuthManager::check_permission();
            }
        ));

        // 🔥 구글 로그인 API 추가 (이 부분이 없어서 404 오류 발생)
        register_rest_route('travel/v1', '/google-login', array(
            'methods' => 'POST',
            'callback' => 'travel_google_login_handler',
            'permission_callback' => '__return_true'
        ));

        // AI 스마트 검색 API
        register_rest_route('travel/v1', '/smart-search', array(
            'methods' => 'POST',
            'callback' => 'travel_maps_smart_search',
            'permission_callback' => '__return_true'
        ));

        // 리뷰 관련 API
        register_rest_route('travel/v1', '/new-review', array(
            'methods' => 'POST',
            'callback' => 'travel_maps_add_review',
            'permission_callback' => function () {
                return TravelAuthManager::check_permission();
            }
        ));

        register_rest_route('travel/v1', '/place-reviews/(?P<place_id>\d+)', array(
            'methods' => 'GET',
            'callback' => 'travel_maps_get_reviews',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('travel/v1', '/check-review/(?P<place_id>\d+)', array(
            'methods' => 'GET',
            'callback' => 'travel_maps_check_review',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('travel/v1', '/toggle-like', array(
            'methods' => 'POST',
            'callback' => 'travel_maps_like_review',
            'permission_callback' => function () {
                return TravelAuthManager::check_permission();
            }
        ));

        // 랭킹 및 통계 API
        register_rest_route('travel/v1', '/rankings', array(
            'methods' => 'GET',
            'callback' => 'travel_maps_get_rankings',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('travel/v1', '/user-rankings', array(
            'methods' => 'GET',
            'callback' => 'travel_maps_get_user_rankings',
            'permission_callback' => '__return_true'
        ));

        // 카테고리 및 지역 API
        register_rest_route('travel/v1', '/categories', array(
            'methods' => 'GET',
            'callback' => 'travel_maps_get_categories',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('travel/v1', '/countries', array(
            'methods' => 'GET',
            'callback' => 'travel_maps_get_countries',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('travel/v1', '/regions', array(
            'methods' => 'GET',
            'callback' => 'travel_maps_get_regions',
            'permission_callback' => '__return_true'
        ));
        // 🔥 필터 옵션 통합 API 추가
        register_rest_route('travel/v1', '/filter-options', array(
            'methods' => 'GET',
            'callback' => 'travel_maps_get_filter_options',
            'permission_callback' => '__return_true'
        ));

        // 인증 관련 API
        register_rest_route('travel/v1', '/auth-check', array(
            'methods' => 'GET',
            'callback' => 'travel_auth_check',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('travel/v1', '/user-profile', array(
            'methods' => 'GET',
            'callback' => 'travel_get_user_profile',
            'permission_callback' => function () {
                return TravelAuthManager::check_permission();
            }
        ));

        register_rest_route('travel/v1', '/update-nickname', array(
            'methods' => 'POST',
            'callback' => 'travel_update_nickname',
            'permission_callback' => function () {
                return TravelAuthManager::check_permission();
            }
        ));

        register_rest_route('travel/v1', '/logout', array(
            'methods' => 'POST',
            'callback' => 'travel_logout',
            'permission_callback' => '__return_true'
        ));
    } catch (Exception $e) {
        // 조용히 실패 처리
    }
}
add_action('rest_api_init', 'travel_maps_init_api');

// 🔧 정리 작업 - 오래된 캐시 자동 삭제
function travel_cleanup_old_cache()
{
    TravelCacheManager::clear_all();
}
add_action('wp_scheduled_delete', 'travel_cleanup_old_cache');

// 플러그인 비활성화 시 정리
function travel_maps_cleanup_on_deactivation()
{
    TravelCacheManager::clear_all();
    TravelSessionManager::destroy_session();
}
register_deactivation_hook(__FILE__, 'travel_maps_cleanup_on_deactivation');

// 🔧 Google Maps API 에러 억제 (콘솔 오류 해결)
function travel_maps_suppress_js_errors()
{
    if (!is_admin()) {
        echo '<script>
            window.addEventListener("error", function(e) {
                if (e.message && (
                    e.message.includes("google") || 
                    e.message.includes("maps") || 
                    e.message.includes("HTMLElement") ||
                    e.message.includes("deprecated")
                )) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Console.error 억제
            const originalError = console.error;
            console.error = function() {
                const args = Array.from(arguments);
                const message = args.join(" ");
                if (message.includes("google") || 
                    message.includes("maps") || 
                    message.includes("deprecated") ||
                    message.includes("HTMLElement")) {
                    return;
                }
                originalError.apply(console, arguments);
            };
        </script>';
    }
}
add_action('wp_head', 'travel_maps_suppress_js_errors');

// functions.php에 완전한 커스텀 필드 시스템 추가
function add_sentence_custom_field()
{
    add_meta_box(
        'sentence-custom-field',
        'Sentence Field',
        'sentence_custom_field_callback',
        'post'
    );
}
add_action('add_meta_boxes', 'add_sentence_custom_field');

function sentence_custom_field_callback($post)
{
    wp_nonce_field('sentence_custom_field_nonce', 'sentence_custom_field_nonce');

    $value = get_post_meta($post->ID, 'Sentence', true);

    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th><label for="sentence_field_id">Sentence</label></th>';
    echo '<td>';
    echo '<input type="text" id="sentence_field_id" name="sentence_field_name" value="' . esc_attr($value) . '" size="50" />';
    echo '<p class="description">엘리멘터에서 사용할 Sentence 필드입니다.</p>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
}

function save_sentence_custom_field($post_id)
{
    if (!isset($_POST['sentence_custom_field_nonce'])) {
        return $post_id;
    }

    $nonce = $_POST['sentence_custom_field_nonce'];
    if (!wp_verify_nonce($nonce, 'sentence_custom_field_nonce')) {
        return $post_id;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    if ('post' == $_POST['post_type']) {
        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }
    }

    if (!isset($_POST['sentence_field_name'])) {
        return $post_id;
    }

    $my_data = sanitize_text_field($_POST['sentence_field_name']);
    update_post_meta($post_id, 'Sentence', $my_data);
}
add_action('save_post', 'save_sentence_custom_field');


// ===============================
// 이메일 인증코드 발송/검증 API
// ===============================

// REST API 등록
add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/send-code', array(
        'methods' => 'POST',
        'callback' => 'custom_send_email_code',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('custom/v1', '/verify-code', array(
        'methods' => 'POST',
        'callback' => 'custom_verify_email_code',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('custom/v1', '/register', array(
        'methods' => 'POST',
        'callback' => 'custom_register_user',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('custom/v1', '/login', array(
        'methods' => 'POST',
        'callback' => 'custom_login_user',
        'permission_callback' => '__return_true',
    ));
});

// 인증코드 발송
function custom_send_email_code($request)
{
    $email = sanitize_email($request->get_param('email'));
    if (empty($email) || !is_email($email)) {
        return new WP_Error('invalid_email', '유효한 이메일을 입력하세요.', array('status' => 400));
    }
    // 이미 인증된 경우
    if (get_transient('email_verified_' . md5($email))) {
        return array('success' => true, 'message' => '이미 인증된 이메일입니다.');
    }
    // 인증코드 생성 및 저장 (10분간)
    $code = str_pad(strval(rand(0, 999999)), 6, '0', STR_PAD_LEFT);
    set_transient('email_code_' . md5($email), $code, 10 * MINUTE_IN_SECONDS);

    // 메일 발송
    $subject = '[아여기] 회원가입 이메일 인증코드 입니다';
    $message = "아래 인증번호를 입력해주세요:\n\n인증번호: {$code}\n\n10분 이내에 입력해주세요.";
    $sent = wp_mail($email, $subject, $message);

    if ($sent) {
        return array('success' => true, 'message' => '인증번호가 발송되었습니다.');
    } else {
        return new WP_Error('mail_failed', '메일 발송에 실패했습니다. 서버 메일 설정을 확인하세요.', array('status' => 500));
    }
}

// 인증코드 검증
function custom_verify_email_code($request)
{
    $email = sanitize_email($request->get_param('email'));
    $code = sanitize_text_field($request->get_param('code'));
    if (empty($email) || !is_email($email) || empty($code)) {
        return new WP_Error('invalid_data', '이메일과 인증번호를 모두 입력하세요.', array('status' => 400));
    }
    $saved_code = get_transient('email_code_' . md5($email));
    if (!$saved_code) {
        return new WP_Error('code_expired', '인증번호가 만료되었거나 없습니다. 다시 요청해주세요.', array('status' => 400));
    }
    if ($saved_code !== $code) {
        return new WP_Error('code_mismatch', '인증번호가 일치하지 않습니다.', array('status' => 400));
    }
    // 인증 성공: 인증 플래그 저장(10분간)
    set_transient('email_verified_' . md5($email), true, 10 * MINUTE_IN_SECONDS);
    delete_transient('email_code_' . md5($email));
    return array('success' => true, 'message' => '이메일 인증이 완료되었습니다.');
}

// 비밀번호 강도 검증 함수
function validate_password_strength($password)
{
    $errors = array();

    // 길이 체크 (8자 이상)
    if (strlen($password) < 8) {
        $errors[] = '비밀번호는 8자 이상이어야 합니다.';
    }

    // 영문 소문자 포함
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = '영문 소문자를 포함해야 합니다.';
    }

    // 숫자 포함
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = '숫자를 포함해야 합니다.';
    }

    // 특수문자 포함
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
        $errors[] = '특수문자를 포함해야 합니다.';
    }

    return $errors;
}

// 회원가입 처리 함수
function custom_register_user($request)
{
    $nickname = sanitize_text_field($request->get_param('nickname'));
    $email = sanitize_email($request->get_param('email'));
    $password = $request->get_param('password');

    // 입력값 검증
    if (empty($nickname) || empty($email) || empty($password)) {
        return new WP_Error('missing_data', '모든 필수 항목을 입력해주세요.', array('status' => 400));
    }

    if (!is_email($email)) {
        return new WP_Error('invalid_email', '유효한 이메일을 입력해주세요.', array('status' => 400));
    }

    // 이메일 인증 확인
    if (!get_transient('email_verified_' . md5($email))) {
        return new WP_Error('email_not_verified', '이메일 인증이 필요합니다.', array('status' => 400));
    }

    // 이메일 중복 확인
    if (email_exists($email)) {
        return new WP_Error('email_exists', '이미 사용 중인 이메일입니다.', array('status' => 400));
    }

    // 닉네임 중복 확인 (user_meta 기준)
    $users = get_users(array(
        'meta_key' => 'travel_nickname',
        'meta_value' => $nickname,
        'number' => 1
    ));
    if (!empty($users)) {
        return new WP_Error('username_exists', '이미 사용 중인 닉네임입니다.', array('status' => 400));
    }

    // 비밀번호 강도 확인
    $password_errors = validate_password_strength($password);
    if (!empty($password_errors)) {
        return new WP_Error('weak_password', '비밀번호 요구사항: ' . implode(' ', $password_errors), array('status' => 400));
    }

    try {
        // user_login 생성 (영문/숫자/언더바만, 한글 닉네임도 허용)
        $user_login = preg_replace('/[^a-zA-Z0-9_]/', '', @iconv('UTF-8', 'ASCII//TRANSLIT', $nickname));
        if (empty($user_login) || strlen($user_login) < 4 || username_exists($user_login)) {
            $user_login = 'user' . time() . rand(100, 999);
        }
        // 사용자 생성
        $user_id = wp_create_user($user_login, $password, $email);
        if (is_wp_error($user_id)) {
            return new WP_Error('registration_failed', '회원가입 실패: ' . $user_id->get_error_message(), array('status' => 500));
        }

        // 추가 메타데이터 설정
        update_user_meta($user_id, 'travel_nickname', $nickname);
        update_user_meta($user_id, 'travel_points', 100); // 가입 보너스 100포인트
        update_user_meta($user_id, 'login_method', 'email');
        update_user_meta($user_id, 'travel_places_count', 0);
        update_user_meta($user_id, 'travel_reviews_count', 0);

        // 이메일 인증 토큰 삭제
        delete_transient('email_verified_' . md5($email));

        // 자동 로그인
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true, is_ssl());

        return array(
            'success' => true,
            'message' => '회원가입이 완료되었습니다! 가입 보너스 100포인트가 지급되었습니다.',
            'user_id' => $user_id,
            'points_earned' => 100
        );

    } catch (Exception $e) {
        return new WP_Error('registration_failed', '회원가입 중 오류가 발생했습니다.', array('status' => 500));
    }
}

function custom_login_user($request)
{
    $login = sanitize_text_field($request->get_param('login'));
    $password = $request->get_param('password');

    if (empty($login) || empty($password)) {
        return new WP_Error('missing_data', '로그인 정보를 입력해주세요.', array('status' => 400));
    }

    $user = get_user_by('login', $login);
    if (!$user && is_email($login)) {
        $user = get_user_by('email', $login);
    }
    // 닉네임(travel_nickname)으로도 시도
    if (!$user) {
        $users = get_users(array(
            'meta_key' => 'travel_nickname',
            'meta_value' => $login,
            'number' => 1
        ));
        if (!empty($users)) {
            $user = $users[0];
        }
    }

    if (!$user || !wp_check_password($password, $user->user_pass, $user->ID)) {
        return new WP_Error('login_failed', '로그인 정보가 올바르지 않습니다.', array('status' => 401));
    }

    // 자동 로그인 처리 (쿠키/세션)
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true, is_ssl());

    // 세션에도 저장
    TravelSessionManager::start_session();
    $_SESSION['user_id'] = $user->ID;
    $_SESSION['login_method'] = 'email';
    $_SESSION['login_time'] = current_time('timestamp');

    $user_profile = travel_get_user_data($user->ID);

    return array(
        'success' => true,
        'message' => '로그인 성공!',
        'user' => $user_profile,
        'nonce' => wp_create_nonce('travel_auth_' . $user->ID),
        'session_id' => TravelSessionManager::get_session_id()
    );
}

