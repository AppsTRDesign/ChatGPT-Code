<?php
/**
 * VBModern Forum Theme functions and definitions
 *
 * @package VBModern_Forum
 */

define( 'VBMODERN_FORUM_VERSION', '1.0.0' );

define( 'VBMODERN_FORUM_DIR', get_template_directory() );
define( 'VBMODERN_FORUM_URI', get_template_directory_uri() );

if ( ! function_exists( 'vbmodern_forum_setup' ) ) {
    /**
     * Sets up theme defaults and registers support for various WordPress features.
     */
    function vbmodern_forum_setup() {
        load_theme_textdomain( 'vbmodern-forum', VBMODERN_FORUM_DIR . '/languages' );

        add_theme_support( 'automatic-feed-links' );
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'custom-logo', [
            'height'      => 120,
            'width'       => 120,
            'flex-height' => true,
            'flex-width'  => true,
        ] );

        add_theme_support( 'customize-selective-refresh-widgets' );
        add_theme_support( 'responsive-embeds' );
        add_theme_support( 'editor-styles' );
        add_editor_style( 'assets/css/editor.css' );

        register_nav_menus( [
            'primary'   => __( 'Primary Menu', 'vbmodern-forum' ),
            'secondary' => __( 'Secondary Menu', 'vbmodern-forum' ),
            'footer'    => __( 'Footer Menu', 'vbmodern-forum' ),
        ] );

        add_theme_support( 'html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        ] );
    }
}
add_action( 'after_setup_theme', 'vbmodern_forum_setup' );

/**
 * Enqueue scripts and styles.
 */
function vbmodern_forum_scripts() {
    wp_enqueue_style( 'vbmodern-forum-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fira+Code:wght@400;500;600&display=swap', [], null );
    wp_enqueue_style( 'vbmodern-forum-style', get_stylesheet_uri(), [], VBMODERN_FORUM_VERSION );
    wp_enqueue_style( 'vbmodern-forum-ui', VBMODERN_FORUM_URI . '/assets/css/forum.css', [ 'vbmodern-forum-style' ], VBMODERN_FORUM_VERSION );

    wp_enqueue_script( 'vbmodern-forum-navigation', VBMODERN_FORUM_URI . '/assets/js/navigation.js', [ 'jquery' ], VBMODERN_FORUM_VERSION, true );
    wp_enqueue_script( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js', [], '11.9.0', true );
    wp_enqueue_script( 'vbmodern-forum-interactions', VBMODERN_FORUM_URI . '/assets/js/interactions.js', [ 'jquery', 'sweetalert2' ], VBMODERN_FORUM_VERSION, true );

    wp_localize_script( 'vbmodern-forum-interactions', 'vbModernForum', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'vbmodern_forum_nonce' ),
        'i18n'    => [
            'processing'       => __( 'Processing request…', 'vbmodern-forum' ),
            'unknownError'     => __( 'Beklenmeyen bir hata oluştu. Lütfen tekrar deneyin.', 'vbmodern-forum' ),
            'registrationDone' => __( 'Üyelik başarıyla oluşturuldu, hoş geldiniz!', 'vbmodern-forum' ),
            'passwordReset'    => __( 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.', 'vbmodern-forum' ),
            'topicCreated'     => __( 'Yeni konunuz başarıyla yayınlandı.', 'vbmodern-forum' ),
            'topicLocked'      => __( 'Konu durumu güncellendi.', 'vbmodern-forum' ),
            'userBanned'       => __( 'Kullanıcı erişimi güncellendi.', 'vbmodern-forum' ),
            'lockTopic'        => __( 'Konu Kilitle', 'vbmodern-forum' ),
            'unlockTopic'      => __( 'Kilidi Aç', 'vbmodern-forum' ),
            'banUser'          => __( 'Kullanıcı Yasakla', 'vbmodern-forum' ),
            'unbanUser'        => __( 'Yasağı Kaldır', 'vbmodern-forum' ),
        ],
    ] );
}
add_action( 'wp_enqueue_scripts', 'vbmodern_forum_scripts' );

/**
 * Register forum topic post type.
 */
function vbmodern_forum_register_topic_post_type() {
    $labels = [
        'name'                  => _x( 'Forum Konuları', 'Post type general name', 'vbmodern-forum' ),
        'singular_name'         => _x( 'Forum Konusu', 'Post type singular name', 'vbmodern-forum' ),
        'menu_name'             => _x( 'Forum Konuları', 'Admin Menu text', 'vbmodern-forum' ),
        'name_admin_bar'        => _x( 'Forum Konusu', 'Add New on Toolbar', 'vbmodern-forum' ),
        'add_new'               => __( 'Yeni Konu Ekle', 'vbmodern-forum' ),
        'add_new_item'          => __( 'Yeni Forum Konusu Ekle', 'vbmodern-forum' ),
        'new_item'              => __( 'Yeni Konu', 'vbmodern-forum' ),
        'edit_item'             => __( 'Konuyu Düzenle', 'vbmodern-forum' ),
        'view_item'             => __( 'Konuyu Gör', 'vbmodern-forum' ),
        'all_items'             => __( 'Tüm Konular', 'vbmodern-forum' ),
        'search_items'          => __( 'Konu Ara', 'vbmodern-forum' ),
        'parent_item_colon'     => __( 'Üst Konular:', 'vbmodern-forum' ),
        'not_found'             => __( 'Konu bulunamadı.', 'vbmodern-forum' ),
        'not_found_in_trash'    => __( 'Çöp kutusunda konu bulunamadı.', 'vbmodern-forum' ),
        'featured_image'        => _x( 'Kapak görseli', 'Overrides the “Featured Image” phrase', 'vbmodern-forum' ),
        'set_featured_image'    => _x( 'Kapak görseli ayarla', 'Overrides the “Set featured image” phrase', 'vbmodern-forum' ),
        'remove_featured_image' => _x( 'Kapak görselini kaldır', 'Overrides the “Remove featured image” phrase', 'vbmodern-forum' ),
        'use_featured_image'    => _x( 'Kapak görseli olarak kullan', 'Overrides the “Use as featured image” phrase', 'vbmodern-forum' ),
        'archives'              => _x( 'Forum Arşivleri', 'The post type archive label', 'vbmodern-forum' ),
        'insert_into_item'      => _x( 'Konuya ekle', 'Overrides the “Insert into post” phrase', 'vbmodern-forum' ),
        'uploaded_to_this_item' => _x( 'Bu konuya yüklenenler', 'Overrides the “Uploaded to this post” phrase', 'vbmodern-forum' ),
        'filter_items_list'     => _x( 'Konu listesini filtrele', 'Screen reader text', 'vbmodern-forum' ),
        'items_list_navigation' => _x( 'Konular listesi gezinimi', 'Screen reader text', 'vbmodern-forum' ),
        'items_list'            => _x( 'Konular listesi', 'Screen reader text', 'vbmodern-forum' ),
    ];

    $capabilities = [
        'edit_post'              => 'edit_forum_topic',
        'read_post'              => 'read_forum_topic',
        'delete_post'            => 'delete_forum_topic',
        'edit_posts'             => 'edit_forum_topics',
        'edit_others_posts'      => 'edit_others_forum_topics',
        'publish_posts'          => 'publish_forum_topics',
        'read_private_posts'     => 'read_private_forum_topics',
        'delete_posts'           => 'delete_forum_topics',
        'delete_private_posts'   => 'delete_private_forum_topics',
        'delete_published_posts' => 'delete_published_forum_topics',
        'delete_others_posts'    => 'delete_others_forum_topics',
        'edit_private_posts'     => 'edit_private_forum_topics',
        'edit_published_posts'   => 'edit_published_forum_topics',
    ];

    register_post_type(
        'forum_topic',
        [
            'labels'             => $labels,
            'public'             => true,
            'has_archive'        => true,
            'rewrite'            => [ 'slug' => 'forum' ],
            'show_in_rest'       => true,
            'menu_icon'          => 'dashicons-format-chat',
            'supports'           => [ 'title', 'editor', 'author', 'excerpt', 'thumbnail', 'comments' ],
            'taxonomies'         => [ 'category', 'post_tag' ],
            'capability_type'    => [ 'forum_topic', 'forum_topics' ],
            'map_meta_cap'       => true,
            'capabilities'       => $capabilities,
            'show_in_menu'       => true,
        ]
    );
}
add_action( 'init', 'vbmodern_forum_register_topic_post_type' );

/**
 * Setup forum specific roles and capabilities.
 */
function vbmodern_forum_register_roles() {
    $member_caps = [
        'read'                    => true,
        'edit_forum_topics'       => true,
        'publish_forum_topics'    => true,
        'edit_published_forum_topics' => true,
        'delete_forum_topics'     => true,
    ];

    add_role( 'vb_forum_member', __( 'Forum Üyesi', 'vbmodern-forum' ), $member_caps );

    $general_caps = array_merge(
        $member_caps,
        [
            'edit_others_forum_topics'      => true,
            'delete_others_forum_topics'    => true,
            'delete_published_forum_topics' => true,
            'moderate_comments'             => true,
            'vb_forum_ban_users'            => true,
            'vb_forum_lock_topics'          => true,
        ]
    );

    add_role( 'vb_general_moderator', __( 'Genel Moderator', 'vbmodern-forum' ), $general_caps );

    $super_caps = array_merge(
        $general_caps,
        [
            'manage_options'               => true,
            'delete_private_forum_topics'  => true,
            'edit_private_forum_topics'    => true,
            'read_private_forum_topics'    => true,
        ]
    );

    add_role( 'vb_super_moderator', __( 'Süper Moderator', 'vbmodern-forum' ), $super_caps );

    $category_caps = $general_caps;
    add_role( 'vb_category_moderator', __( 'Kategori Moderatörü', 'vbmodern-forum' ), $category_caps );
}
add_action( 'after_switch_theme', 'vbmodern_forum_register_roles' );

/**
 * Ensure custom roles exist even if the theme was activated before the update.
 */
function vbmodern_forum_ensure_roles_exist() {
    if ( ! get_role( 'vb_forum_member' ) ) {
        vbmodern_forum_register_roles();
    }
}
add_action( 'init', 'vbmodern_forum_ensure_roles_exist', 5 );

/**
 * Ensure default forum member role inherits subscriber capabilities.
 */
function vbmodern_forum_adjust_default_roles() {
    $subscriber = get_role( 'subscriber' );
    if ( $subscriber && ! $subscriber->has_cap( 'publish_forum_topics' ) ) {
        $subscriber->add_cap( 'edit_forum_topics' );
        $subscriber->add_cap( 'publish_forum_topics' );
        $subscriber->add_cap( 'edit_published_forum_topics' );
    }
}
add_action( 'init', 'vbmodern_forum_adjust_default_roles', 20 );

/**
 * Admin page for forum configuration.
 */
function vbmodern_forum_register_admin_page() {
    add_menu_page(
        __( 'VBModern Forum', 'vbmodern-forum' ),
        __( 'VB Forum', 'vbmodern-forum' ),
        'manage_options',
        'vbmodern-forum-settings',
        'vbmodern_forum_render_settings_page',
        'dashicons-admin-users',
        58
    );
}
add_action( 'admin_menu', 'vbmodern_forum_register_admin_page' );

/**
 * Render settings page contents.
 */
function vbmodern_forum_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $users = get_users( [ 'fields' => [ 'ID', 'display_name' ] ] );
    $categories = get_categories( [ 'hide_empty' => false ] );
    $options = get_option( 'vbmodern_forum_settings', [
        'general_moderators'  => [],
        'super_moderators'    => [],
        'category_moderators' => [],
        'auto_publish_topics' => 1,
    ] );

    if ( isset( $_POST['vbmodern_forum_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vbmodern_forum_settings_nonce'] ) ), 'vbmodern_forum_settings' ) ) {
        $general = isset( $_POST['general_moderators'] ) ? array_map( 'intval', (array) $_POST['general_moderators'] ) : [];
        $super   = isset( $_POST['super_moderators'] ) ? array_map( 'intval', (array) $_POST['super_moderators'] ) : [];
        $category_assignments = [];

        if ( isset( $_POST['category_moderators'] ) && is_array( $_POST['category_moderators'] ) ) {
            foreach ( $_POST['category_moderators'] as $cat_id => $user_ids ) {
                $cat_id = (int) $cat_id;
                $category_assignments[ $cat_id ] = array_map( 'intval', (array) $user_ids );
            }
        }

        $auto_publish = isset( $_POST['auto_publish_topics'] ) ? 1 : 0;

        $options = [
            'general_moderators'  => $general,
            'super_moderators'    => $super,
            'category_moderators' => $category_assignments,
            'auto_publish_topics' => $auto_publish,
        ];

        update_option( 'vbmodern_forum_settings', $options );

        vbmodern_forum_update_user_roles( $general, 'vb_general_moderator' );
        vbmodern_forum_update_user_roles( $super, 'vb_super_moderator' );

        $category_users = [];
        foreach ( $category_assignments as $user_ids ) {
            foreach ( $user_ids as $user_id ) {
                $category_users[] = (int) $user_id;
            }
        }
        $category_users = array_unique( $category_users );

        vbmodern_forum_update_user_roles( $category_users, 'vb_category_moderator' );

        // Sync category moderator assignments.
        vbmodern_forum_sync_category_moderators( $category_assignments );

        echo '<div class="notice notice-success"><p>' . esc_html__( 'Ayarlar kaydedildi.', 'vbmodern-forum' ) . '</p></div>';
    }

    $options = wp_parse_args( $options, [
        'general_moderators'  => [],
        'super_moderators'    => [],
        'category_moderators' => [],
        'auto_publish_topics' => 1,
    ] );

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'VBModern Forum Ayarları', 'vbmodern-forum' ); ?></h1>
        <form method="post">
            <?php wp_nonce_field( 'vbmodern_forum_settings', 'vbmodern_forum_settings_nonce' ); ?>

            <h2><?php esc_html_e( 'Genel Ayarlar', 'vbmodern-forum' ); ?></h2>
            <label>
                <input type="checkbox" name="auto_publish_topics" value="1" <?php checked( (int) $options['auto_publish_topics'], 1 ); ?> />
                <?php esc_html_e( 'Üyeler tarafından açılan konuları otomatik olarak yayınla', 'vbmodern-forum' ); ?>
            </label>

            <hr />

            <h2><?php esc_html_e( 'Moderator Rollerini Yönet', 'vbmodern-forum' ); ?></h2>
            <p><?php esc_html_e( 'Genel moderatorler tüm konuları yönetebilir. Süper moderatorler ek olarak yönetici yetkilerine sahiptir.', 'vbmodern-forum' ); ?></p>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Rol', 'vbmodern-forum' ); ?></th>
                        <th><?php esc_html_e( 'Kullanıcılar', 'vbmodern-forum' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php esc_html_e( 'Genel Moderatorler', 'vbmodern-forum' ); ?></td>
                        <td>
                            <select name="general_moderators[]" multiple size="8" style="width:100%">
                                <?php foreach ( $users as $user ) : ?>
                                    <option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( in_array( $user->ID, $options['general_moderators'], true ), true ); ?>><?php echo esc_html( $user->display_name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Süper Moderatorler', 'vbmodern-forum' ); ?></td>
                        <td>
                            <select name="super_moderators[]" multiple size="8" style="width:100%">
                                <?php foreach ( $users as $user ) : ?>
                                    <option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( in_array( $user->ID, $options['super_moderators'], true ), true ); ?>><?php echo esc_html( $user->display_name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'Kategori Moderatorleri', 'vbmodern-forum' ); ?></h2>
            <p><?php esc_html_e( 'Kategori moderatorleri yalnızca atanmış oldukları kategorilerde işlem yapabilir.', 'vbmodern-forum' ); ?></p>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Kategori', 'vbmodern-forum' ); ?></th>
                        <th><?php esc_html_e( 'Moderatorler', 'vbmodern-forum' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $categories as $category ) :
                        $assigned = $options['category_moderators'][ $category->term_id ] ?? [];
                        ?>
                        <tr>
                            <td><?php echo esc_html( $category->name ); ?></td>
                            <td>
                                <select name="category_moderators[<?php echo esc_attr( $category->term_id ); ?>][]" multiple size="6" style="width:100%">
                                    <?php foreach ( $users as $user ) : ?>
                                        <option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( in_array( $user->ID, $assigned, true ), true ); ?>><?php echo esc_html( $user->display_name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Ayarları Kaydet', 'vbmodern-forum' ); ?></button>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Assign the provided users to a role exclusively.
 *
 * @param array  $user_ids Users that should receive the role.
 * @param string $role     Role name.
 */
function vbmodern_forum_update_user_roles( array $user_ids, $role ) {
    $existing = get_users( [
        'role'   => $role,
        'fields' => 'ID',
    ] );

    foreach ( $existing as $user_id ) {
        if ( ! in_array( (int) $user_id, $user_ids, true ) ) {
            $user = new WP_User( $user_id );
            $user->remove_role( $role );
        }
    }

    foreach ( $user_ids as $user_id ) {
        $user = new WP_User( $user_id );
        if ( ! in_array( $role, (array) $user->roles, true ) ) {
            $user->add_role( $role );
        }
    }
}

/**
 * Persist category moderator assignments to user meta for faster checks.
 *
 * @param array $assignments Category => user IDs.
 */
function vbmodern_forum_sync_category_moderators( array $assignments ) {
    update_option( 'vbmodern_forum_category_moderators', $assignments );

    $users = get_users( [ 'role' => 'vb_category_moderator', 'fields' => 'ID' ] );
    foreach ( $users as $user_id ) {
        $categories = [];
        foreach ( $assignments as $category_id => $user_ids ) {
            if ( in_array( (int) $user_id, $user_ids, true ) ) {
                $categories[] = (int) $category_id;
            }
        }

        update_user_meta( $user_id, 'vb_forum_categories', $categories );
    }

    // Clear category assignment meta for users that are no longer moderators.
    $all_assigned_user_ids = [];
    foreach ( $assignments as $user_ids ) {
        foreach ( $user_ids as $user_id ) {
            $all_assigned_user_ids[] = (int) $user_id;
        }
    }
    $all_assigned_user_ids = array_unique( $all_assigned_user_ids );

    $existing_meta_users = get_users( [
        'meta_key'     => 'vb_forum_categories',
        'meta_compare' => 'EXISTS',
        'fields'       => 'ID',
    ] );
    foreach ( $existing_meta_users as $user_id ) {
        if ( ! in_array( (int) $user_id, $all_assigned_user_ids, true ) ) {
            delete_user_meta( $user_id, 'vb_forum_categories' );
        }
    }
}

/**
 * Determine if a user is allowed to moderate a topic.
 *
 * @param int      $post_id Post ID.
 * @param int|null $user_id User ID.
 */
function vbmodern_forum_user_can_moderate( $post_id, $user_id = null ) {
    $user = $user_id ? get_user_by( 'id', $user_id ) : wp_get_current_user();

    if ( ! $user || ! $user->exists() ) {
        return false;
    }

    if ( in_array( 'administrator', (array) $user->roles, true ) || user_can( $user, 'manage_options' ) || in_array( 'vb_super_moderator', (array) $user->roles, true ) ) {
        return true;
    }

    if ( in_array( 'vb_general_moderator', (array) $user->roles, true ) ) {
        return true;
    }

    if ( in_array( 'vb_category_moderator', (array) $user->roles, true ) ) {
        $assigned_categories = (array) get_user_meta( $user->ID, 'vb_forum_categories', true );
        $post_categories     = wp_get_post_categories( $post_id );

        return array_intersect( $assigned_categories, $post_categories ) ? true : false;
    }

    return false;
}

/**
 * Check if a topic is locked.
 *
 * @param int $post_id Post ID.
 */
function vbmodern_forum_is_topic_locked( $post_id ) {
    return (bool) get_post_meta( $post_id, '_vb_forum_locked', true );
}

/**
 * AJAX handler: user registration.
 */
function vbmodern_forum_ajax_register_user() {
    check_ajax_referer( 'vbmodern_forum_nonce', 'nonce' );

    if ( is_user_logged_in() ) {
        wp_send_json_error( [ 'message' => __( 'Zaten giriş yaptınız.', 'vbmodern-forum' ) ] );
    }

    $username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
    $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $password = isset( $_POST['password'] ) ? trim( wp_unslash( $_POST['password'] ) ) : '';

    if ( empty( $username ) || empty( $email ) || empty( $password ) ) {
        wp_send_json_error( [ 'message' => __( 'Lütfen tüm alanları doldurun.', 'vbmodern-forum' ) ] );
    }

    if ( username_exists( $username ) ) {
        wp_send_json_error( [ 'message' => __( 'Bu kullanıcı adı kullanımda.', 'vbmodern-forum' ) ] );
    }

    if ( email_exists( $email ) ) {
        wp_send_json_error( [ 'message' => __( 'Bu e-posta adresiyle bir hesap mevcut.', 'vbmodern-forum' ) ] );
    }

    $user_id = wp_create_user( $username, $password, $email );

    if ( is_wp_error( $user_id ) ) {
        wp_send_json_error( [ 'message' => $user_id->get_error_message() ] );
    }

    $user = new WP_User( $user_id );
    $user->add_role( 'vb_forum_member' );

    wp_signon(
        [
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true,
        ],
        false
    );

    wp_send_json_success( [ 'message' => __( 'Üyelik oluşturuldu.', 'vbmodern-forum' ), 'redirect' => home_url( '/' ) ] );
}
add_action( 'wp_ajax_nopriv_vbmodern_forum_register_user', 'vbmodern_forum_ajax_register_user' );

/**
 * AJAX handler: password reset.
 */
function vbmodern_forum_ajax_password_reset() {
    check_ajax_referer( 'vbmodern_forum_nonce', 'nonce' );

    $user_login = isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : '';

    if ( empty( $user_login ) ) {
        wp_send_json_error( [ 'message' => __( 'Lütfen kullanıcı adı veya e-posta girin.', 'vbmodern-forum' ) ] );
    }

    $result = retrieve_password( $user_login );

    if ( true === $result ) {
        wp_send_json_success( [ 'message' => __( 'E-posta gönderildi. Gelen kutunuzu kontrol edin.', 'vbmodern-forum' ) ] );
    }

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( [ 'message' => $result->get_error_message() ] );
    }

    wp_send_json_error( [ 'message' => __( 'İşlem gerçekleştirilemedi.', 'vbmodern-forum' ) ] );
}
add_action( 'wp_ajax_nopriv_vbmodern_forum_password_reset', 'vbmodern_forum_ajax_password_reset' );
add_action( 'wp_ajax_vbmodern_forum_password_reset', 'vbmodern_forum_ajax_password_reset' );

/**
 * AJAX handler: create a new topic.
 */
function vbmodern_forum_ajax_create_topic() {
    check_ajax_referer( 'vbmodern_forum_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'message' => __( 'Konu oluşturmak için giriş yapmalısınız.', 'vbmodern-forum' ) ] );
    }

    $current_user = wp_get_current_user();
    if ( (bool) get_user_meta( $current_user->ID, 'vb_forum_banned', true ) ) {
        wp_send_json_error( [ 'message' => __( 'Hesabınız kısıtlandığı için işlem yapılamıyor.', 'vbmodern-forum' ) ] );
    }

    $title      = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
    $content    = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
    $categories = isset( $_POST['categories'] ) ? array_map( 'intval', (array) $_POST['categories'] ) : [];

    if ( empty( $title ) || empty( $content ) ) {
        wp_send_json_error( [ 'message' => __( 'Başlık ve içerik gereklidir.', 'vbmodern-forum' ) ] );
    }

    $settings     = get_option( 'vbmodern_forum_settings', [] );
    $auto_publish = isset( $settings['auto_publish_topics'] ) ? (int) $settings['auto_publish_topics'] : 1;

    $post_data = [
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => $auto_publish ? 'publish' : 'pending',
        'post_author'  => $current_user->ID,
        'post_type'    => 'forum_topic',
    ];

    if ( ! empty( $categories ) ) {
        $post_data['tax_input'] = [ 'category' => $categories ];
    }

    $post_id = wp_insert_post( $post_data, true );

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( [ 'message' => $post_id->get_error_message() ] );
    }

    wp_send_json_success( [
        'message'   => __( 'Konu başarıyla oluşturuldu.', 'vbmodern-forum' ),
        'redirect'  => get_permalink( $post_id ),
        'post_id'   => $post_id,
    ] );
}
add_action( 'wp_ajax_vbmodern_forum_create_topic', 'vbmodern_forum_ajax_create_topic' );
add_action( 'wp_ajax_nopriv_vbmodern_forum_create_topic', 'vbmodern_forum_ajax_create_topic' );

/**
 * AJAX handler: lock or unlock a topic.
 */
function vbmodern_forum_ajax_toggle_lock_topic() {
    check_ajax_referer( 'vbmodern_forum_nonce', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;

    if ( ! $post_id || 'forum_topic' !== get_post_type( $post_id ) ) {
        wp_send_json_error( [ 'message' => __( 'Geçersiz konu.', 'vbmodern-forum' ) ] );
    }

    if ( ! vbmodern_forum_user_can_moderate( $post_id ) || ! current_user_can( 'vb_forum_lock_topics' ) ) {
        wp_send_json_error( [ 'message' => __( 'Bu işlem için yetkiniz yok.', 'vbmodern-forum' ) ] );
    }

    $locked = vbmodern_forum_is_topic_locked( $post_id );
    update_post_meta( $post_id, '_vb_forum_locked', $locked ? 0 : 1 );

    wp_send_json_success( [
        'message' => $locked ? __( 'Konu kilidi açıldı.', 'vbmodern-forum' ) : __( 'Konu kilitlendi.', 'vbmodern-forum' ),
        'locked'  => ! $locked,
    ] );
}
add_action( 'wp_ajax_vbmodern_forum_toggle_lock', 'vbmodern_forum_ajax_toggle_lock_topic' );

/**
 * AJAX handler: ban or unban user.
 */
function vbmodern_forum_ajax_toggle_ban_user() {
    check_ajax_referer( 'vbmodern_forum_nonce', 'nonce' );

    $user_id = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;

    if ( ! $user_id ) {
        wp_send_json_error( [ 'message' => __( 'Kullanıcı bulunamadı.', 'vbmodern-forum' ) ] );
    }

    $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
    if ( $post_id && ! vbmodern_forum_user_can_moderate( $post_id ) ) {
        wp_send_json_error( [ 'message' => __( 'Bu işlem için yetkiniz yok.', 'vbmodern-forum' ) ] );
    }

    if ( ! current_user_can( 'vb_forum_ban_users' ) ) {
        wp_send_json_error( [ 'message' => __( 'Bu işlem için yetkiniz yok.', 'vbmodern-forum' ) ] );
    }

    $banned = (bool) get_user_meta( $user_id, 'vb_forum_banned', true );
    update_user_meta( $user_id, 'vb_forum_banned', $banned ? 0 : 1 );

    wp_send_json_success( [
        'message' => $banned ? __( 'Kullanıcı yasağı kaldırıldı.', 'vbmodern-forum' ) : __( 'Kullanıcı yasaklandı.', 'vbmodern-forum' ),
        'banned'  => ! $banned,
    ] );
}
add_action( 'wp_ajax_vbmodern_forum_toggle_ban', 'vbmodern_forum_ajax_toggle_ban_user' );

/**
 * Prevent comments on locked topics.
 */
function vbmodern_forum_filter_comments_open( $open, $post_id ) {
    if ( 'forum_topic' === get_post_type( $post_id ) && vbmodern_forum_is_topic_locked( $post_id ) ) {
        return false;
    }

    return $open;
}
add_filter( 'comments_open', 'vbmodern_forum_filter_comments_open', 10, 2 );

/**
 * Prevent banned users from creating topics via wp-admin.
 */
function vbmodern_forum_block_banned_author( $caps, $cap, $user_id, $args ) {
    if ( in_array( $cap, [ 'publish_forum_topics', 'edit_forum_topic', 'edit_forum_topics', 'delete_forum_topic', 'delete_forum_topics' ], true ) && (bool) get_user_meta( $user_id, 'vb_forum_banned', true ) ) {
        $caps[] = 'do_not_allow';
    }

    return $caps;
}
add_filter( 'map_meta_cap', 'vbmodern_forum_block_banned_author', 10, 4 );

/**
 * Prevent banned members from commenting.
 */
function vbmodern_forum_prevent_banned_comment() {
    $user_id = get_current_user_id();

    if ( $user_id && (bool) get_user_meta( $user_id, 'vb_forum_banned', true ) ) {
        wp_die(
            esc_html__( 'Hesabınız kısıtlandığı için bu işlemi yapamazsınız.', 'vbmodern-forum' ),
            esc_html__( 'Erişim Engellendi', 'vbmodern-forum' ),
            [ 'response' => 403 ]
        );
    }
}
add_action( 'pre_comment_on_post', 'vbmodern_forum_prevent_banned_comment' );

/**
 * Force public queries to use forum topics by default.
 *
 * @param WP_Query $query Query instance.
 */
function vbmodern_forum_adjust_queries( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( $query->is_home() || $query->is_post_type_archive( 'forum_topic' ) ) {
        $query->set( 'post_type', 'forum_topic' );
    }

    if ( $query->is_category() || $query->is_tag() ) {
        $query->set( 'post_type', [ 'forum_topic' ] );
    }

    if ( $query->is_search() ) {
        $post_types = (array) $query->get( 'post_type' );
        if ( empty( $post_types ) || in_array( 'post', $post_types, true ) ) {
            $query->set( 'post_type', [ 'forum_topic' ] );
        }
    }
}
add_action( 'pre_get_posts', 'vbmodern_forum_adjust_queries' );

/**
 * Register widget areas.
 */
function vbmodern_forum_widgets_init() {
    register_sidebar( [
        'name'          => __( 'Primary Sidebar', 'vbmodern-forum' ),
        'id'            => 'sidebar-1',
        'description'   => __( 'Add widgets here to appear in your sidebar.', 'vbmodern-forum' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget__title">',
        'after_title'   => '</h3>',
    ] );

    register_sidebar( [
        'name'          => __( 'Footer Widgets', 'vbmodern-forum' ),
        'id'            => 'footer-widgets',
        'description'   => __( 'Widgets displayed across the footer columns.', 'vbmodern-forum' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget__title">',
        'after_title'   => '</h3>',
    ] );
}
add_action( 'widgets_init', 'vbmodern_forum_widgets_init' );

/**
 * Include custom template tags and helpers.
 */
require_once VBMODERN_FORUM_DIR . '/inc/template-tags.php';
require_once VBMODERN_FORUM_DIR . '/inc/template-functions.php';
