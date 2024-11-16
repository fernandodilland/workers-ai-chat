<?php
/**
 * Plugin Name: Workers AI Chat
 * Description: Uses Cloudflare Workers AI in a web chat embedded via shortcode.
 * Version: 1.3
 * Author: Fernando Dilland
 * Author URI: https://fernandodilland.com
 * Text Domain: workers-ai-chat
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

class WorkersAIChat {
    private $options;

    public function __construct() {
        // Cargar el dominio de texto para traducciones con alta prioridad
        add_action('plugins_loaded', array($this, 'load_textdomain'), 10);

        // Cargar configuraciones del plugin
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));

        // Registrar shortcode
        add_shortcode('workers-ai', array($this, 'render_chat'));

        // Encolar scripts y estilos
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Registrar manejadores AJAX
        add_action('wp_ajax_workers_ai_chat', array($this, 'handle_ajax'));
        add_action('wp_ajax_nopriv_workers_ai_chat', array($this, 'handle_ajax'));
    }

    /**
     * Cargar el dominio de texto para traducciones
     */
    public function load_textdomain() {
        $locale = apply_filters('plugin_locale', get_locale(), 'workers-ai-chat');
        $loaded = load_textdomain('workers-ai-chat', WP_LANG_DIR . '/workers-ai-chat/workers-ai-chat-' . $locale . '.mo');
        $loaded_plugin = load_plugin_textdomain('workers-ai-chat', false, dirname(plugin_basename(__FILE__)) . '/languages/');

        // Eliminado: llamadas a error_log()
        // Si necesitas manejar errores de carga del dominio de texto, considera otras alternativas.
    }

    /**
     * Agregar página de configuraciones al menú de administración
     */
    public function add_plugin_page() {
        add_options_page(
            esc_html__('Workers AI Chat Settings', 'workers-ai-chat'),
            esc_html__('Workers AI Chat', 'workers-ai-chat'),
            'manage_options',
            'workers-ai-chat',
            array($this, 'create_admin_page')
        );
    }

    /**
     * Crear la página de configuración del plugin
     */
    public function create_admin_page() {
        $this->options = get_option('workers_ai_chat_options');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Workers AI Chat Settings', 'workers-ai-chat'); ?></h1>
            <form method="post" action="options.php">
            <?php
                settings_fields('workers_ai_chat_option_group');
                do_settings_sections('workers-ai-chat-admin');
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Inicializar configuraciones del plugin
     */
    public function page_init() {
        register_setting(
            'workers_ai_chat_option_group', // Grupo de opciones
            'workers_ai_chat_options',      // Nombre de la opción
            array($this, 'sanitize')        // Callback de sanitización
        );

        add_settings_section(
            'setting_section_id', // ID
            esc_html__('API and Security Settings', 'workers-ai-chat'), // Título
            array($this, 'print_section_info'),    // Callback
            'workers-ai-chat-admin'                // Página
        );

        // Campos de configuración existentes
        add_settings_field(
            'account_id', // ID
            esc_html__('Account ID', 'workers-ai-chat'), // Título
            array($this, 'account_id_callback'), // Callback
            'workers-ai-chat-admin', // Página
            'setting_section_id' // Sección
        );

        add_settings_field(
            'api_token',
            esc_html__('API Token', 'workers-ai-chat'),
            array($this, 'api_token_callback'),
            'workers-ai-chat-admin',
            'setting_section_id'
        );

        add_settings_field(
            'api_endpoint',
            esc_html__('API Endpoint', 'workers-ai-chat'),
            array($this, 'api_endpoint_callback'),
            'workers-ai-chat-admin',
            'setting_section_id'
        );

        add_settings_field(
            'prompting_type',
            esc_html__('Prompting Type', 'workers-ai-chat'),
            array($this, 'prompting_type_callback'),
            'workers-ai-chat-admin',
            'setting_section_id'
        );

        add_settings_field(
            'system_message',
            esc_html__('System Message (for Scoped Prompts)', 'workers-ai-chat'),
            array($this, 'system_message_callback'),
            'workers-ai-chat-admin',
            'setting_section_id'
        );

        // Campo para el Nombre de la AI
        add_settings_field(
            'ai_name',
            esc_html__('AI Name', 'workers-ai-chat'),
            array($this, 'ai_name_callback'),
            'workers-ai-chat-admin',
            'setting_section_id'
        );

        // Campo para Preguntas Predefinidas
        add_settings_field(
            'predefined_questions',
            esc_html__('Predefined Questions', 'workers-ai-chat'),
            array($this, 'predefined_questions_callback'),
            'workers-ai-chat-admin',
            'setting_section_id'
        );

        // Campo para Chats Efímeros
        add_settings_field(
            'ephemeral_chats',
            esc_html__('Ephemeral Chats', 'workers-ai-chat'),
            array($this, 'ephemeral_chats_callback'),
            'workers-ai-chat-admin',
            'setting_section_id'
        );
    }

    /**
     * Sanitizar las entradas de configuración
     */
    public function sanitize($input) {
        $sanitized = array();
        if (isset($input['account_id'])) {
            $sanitized['account_id'] = sanitize_text_field($input['account_id']);
        }
        if (isset($input['api_token'])) {
            $sanitized['api_token'] = sanitize_text_field($input['api_token']);
        }
        if (isset($input['api_endpoint'])) {
            $sanitized['api_endpoint'] = esc_url_raw($input['api_endpoint']);
        }
        if (isset($input['prompting_type'])) {
            $sanitized['prompting_type'] = sanitize_text_field($input['prompting_type']);
        }
        if (isset($input['system_message'])) {
            $sanitized['system_message'] = sanitize_textarea_field($input['system_message']);
        }
        if (isset($input['ai_name'])) {
            $sanitized['ai_name'] = sanitize_text_field($input['ai_name']);
        }
        if (isset($input['predefined_questions'])) {
            $sanitized['predefined_questions'] = sanitize_textarea_field($input['predefined_questions']);
        }
        if (isset($input['ephemeral_chats'])) {
            $sanitized['ephemeral_chats'] = $input['ephemeral_chats'] === 'yes' ? 'yes' : 'no';
        } else {
            $sanitized['ephemeral_chats'] = 'no';
        }
        return $sanitized;
    }

    /**
     * Imprimir información de la sección de configuración
     */
    public function print_section_info() {
        esc_html_e('Enter the necessary settings to use the Cloudflare Workers AI API, as well as the AI name and predefined questions:', 'workers-ai-chat');
    }

    /**
     * Callback para el campo Account ID
     */
    public function account_id_callback() {
        printf(
            '<input type="text" id="account_id" name="workers_ai_chat_options[account_id]" value="%s" size="50"/>',
            isset($this->options['account_id']) ? esc_attr($this->options['account_id']) : ''
        );
    }

    /**
     * Callback para el campo API Token
     */
    public function api_token_callback() {
        printf(
            '<input type="text" id="api_token" name="workers_ai_chat_options[api_token]" value="%s" size="50"/>',
            isset($this->options['api_token']) ? esc_attr($this->options['api_token']) : ''
        );
    }

    /**
     * Callback para el campo API Endpoint
     */
    public function api_endpoint_callback() {
        $default_endpoint = 'https://api.cloudflare.com/client/v4/accounts/{ACCOUNT_ID}/ai/run/@cf/meta/llama-3.1-8b-instruct';
        printf(
            '<input type="text" id="api_endpoint" name="workers_ai_chat_options[api_endpoint]" value="%s" size="50"/>',
            isset($this->options['api_endpoint']) ? esc_attr($this->options['api_endpoint']) : esc_html($default_endpoint)
        );
    }

    /**
     * Callback para el campo Prompting Type
     */
    public function prompting_type_callback() {
        $options = isset($this->options['prompting_type']) ? $this->options['prompting_type'] : 'unscoped';
        ?>
        <select id="prompting_type" name="workers_ai_chat_options[prompting_type]">
            <option value="unscoped" <?php selected($options, 'unscoped'); ?>><?php esc_html_e('Unscoped', 'workers-ai-chat'); ?></option>
            <option value="scoped" <?php selected($options, 'scoped'); ?>><?php esc_html_e('Scoped', 'workers-ai-chat'); ?></option>
        </select>
        <?php
    }

    /**
     * Callback para el campo System Message
     */
    public function system_message_callback() {
        printf(
            '<textarea id="system_message" name="workers_ai_chat_options[system_message]" rows="5" cols="50">%s</textarea>',
            isset($this->options['system_message']) ? esc_textarea($this->options['system_message']) : ''
        );
        echo '<p class="description">' . esc_html__('Only used if "Scoped" is selected in Prompting Type.', 'workers-ai-chat') . '</p>';
    }

    /**
     * Callback para el campo AI Name
     */
    public function ai_name_callback() {
        printf(
            '<input type="text" id="ai_name" name="workers_ai_chat_options[ai_name]" value="%s" size="50"/>',
            isset($this->options['ai_name']) ? esc_attr($this->options['ai_name']) : 'AI'
        );
        echo '<p class="description">' . esc_html__('This will be the name displayed in the AI responses.', 'workers-ai-chat') . '</p>';
    }

    /**
     * Callback para el campo Predefined Questions
     */
    public function predefined_questions_callback() {
        printf(
            '<textarea id="predefined_questions" name="workers_ai_chat_options[predefined_questions]" rows="5" cols="50">%s</textarea>',
            isset($this->options['predefined_questions']) ? esc_textarea($this->options['predefined_questions']) : ''
        );
        echo '<p class="description">' . esc_html__('Enter predefined questions, one per line.', 'workers-ai-chat') . '</p>';
    }

    /**
     * Callback para el campo Ephemeral Chats
     */
    public function ephemeral_chats_callback() {
        $checked = isset($this->options['ephemeral_chats']) ? $this->options['ephemeral_chats'] : 'yes';
        ?>
        <label>
            <input type="checkbox" id="ephemeral_chats" name="workers_ai_chat_options[ephemeral_chats]" value="yes" <?php checked($checked, 'yes'); ?> />
            <?php esc_html_e('Enable ephemeral chats (default)', 'workers-ai-chat'); ?>
        </label>
        <?php
    }

    /**
     * Encolar scripts y estilos necesarios
     */
    public function enqueue_scripts() {
        if (is_singular() && has_shortcode(get_post()->post_content, 'workers-ai')) {
            // Asegurar que las opciones estén cargadas
            $this->options = get_option('workers_ai_chat_options');

            // Encolar CSS con versión dinámica para evitar caché
            $css_file = plugin_dir_path(__FILE__) . 'css/workers-ai-chat.css';
            $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0';

            wp_enqueue_style(
                'workers-ai-chat-css',
                plugin_dir_url(__FILE__) . 'css/workers-ai-chat.css',
                array(),
                $css_version, // Parámetro de versión dinámico
                'all'
            );

            // Encolar JavaScript principal
            wp_enqueue_script('workers-ai-chat-js', plugin_dir_url(__FILE__) . 'js/workers-ai-chat.js', array('jquery'), '2.1', true);

            // Encolar Marked.js localmente para evitar cargar scripts externos
            wp_enqueue_script('marked-js', plugin_dir_url(__FILE__) . 'js/vendor/marked.min.js', array(), '4.3.0', true);

            // Obtener configuraciones
            $ai_name = isset($this->options['ai_name']) ? esc_attr($this->options['ai_name']) : 'AI';
            $predefined_questions = isset($this->options['predefined_questions']) ? esc_textarea($this->options['predefined_questions']) : '';
            $ephemeral_chats = isset($this->options['ephemeral_chats']) ? $this->options['ephemeral_chats'] : 'yes';

            // Localizar scripts con datos necesarios para JavaScript
            wp_localize_script('workers-ai-chat-js', 'workersAIChatData', array(
                'ajax_url'                  => admin_url('admin-ajax.php'),
                'nonce'                     => wp_create_nonce('workers_ai_chat_nonce'),
                'ai_name'                   => $ai_name,
                'predefined_questions'      => $predefined_questions,
                'ephemeral_chats'           => $ephemeral_chats,
                'thinking_text'             => esc_html__('Thinking...', 'workers-ai-chat'),
                'you_text'                  => esc_html__('You', 'workers-ai-chat'),
                'error_processing_request'  => esc_html__('Error processing the request.', 'workers-ai-chat'),
                'error_empty_message'       => esc_html__('Please enter a message.', 'workers-ai-chat'),
            ));
        }
    }

    /**
     * Renderizar el shortcode del chat
     */
    public function render_chat($atts) {
        // Asegurar que las opciones estén cargadas
        $this->options = get_option('workers_ai_chat_options');

        // Obtener el nombre de la AI
        $ai_name = isset($this->options['ai_name']) ? esc_attr($this->options['ai_name']) : 'AI';

        // Obtener preguntas predefinidas
        $predefined_questions = isset($this->options['predefined_questions']) ? $this->options['predefined_questions'] : '';
        $questions_array = array_filter(array_map('trim', explode("\n", $predefined_questions)));

        // Renderizar HTML del chat
        ob_start();
        ?>
        <div class="workers-ai-chat-container">
            <?php if (!empty($questions_array)): ?>
                <div class="workers-ai-predefined-questions">
                    <?php foreach ($questions_array as $question): ?>
                        <button type="button" class="workers-ai-question-button"><?php echo esc_html($question); ?></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="workers-ai-chat-box" id="workers-ai-chat-box">
                <!-- Aquí aparecerán los mensajes del chat -->
            </div>
            <form id="workers-ai-chat-form">
                <input type="text" id="workers-ai-user-input" name="user_input" placeholder="<?php echo esc_attr__('Type a message...', 'workers-ai-chat'); ?>" required autofocus />
                <button type="submit" id="workers-ai-submit-button"><?php esc_html_e('Send', 'workers-ai-chat'); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Manejar solicitudes AJAX
     */
    public function handle_ajax() {
        check_ajax_referer('workers_ai_chat_nonce', 'nonce');

        // Obtener entrada del usuario
        $user_input = isset($_POST['user_input']) ? sanitize_text_field( wp_unslash( $_POST['user_input'] ) ) : '';

        // Obtener opciones del plugin
        $options = get_option('workers_ai_chat_options');
        $account_id = isset($options['account_id']) ? sanitize_text_field($options['account_id']) : '';
        $api_token = isset($options['api_token']) ? sanitize_text_field($options['api_token']) : '';
        $api_endpoint = isset($options['api_endpoint']) ? esc_url_raw($options['api_endpoint']) : '';
        $prompting_type = isset($options['prompting_type']) ? sanitize_text_field($options['prompting_type']) : 'unscoped';
        $system_message = isset($options['system_message']) ? sanitize_textarea_field($options['system_message']) : '';
        $ai_name = isset($options['ai_name']) ? sanitize_text_field($options['ai_name']) : 'AI';

        // Verificar configuraciones necesarias
        if (empty($account_id) || empty($api_token) || empty($api_endpoint)) {
            wp_send_json_error(esc_html__('Incomplete plugin settings.', 'workers-ai-chat'));
            wp_die();
        }

        // Reemplazar {ACCOUNT_ID} en el endpoint
        $api_url = str_replace('{ACCOUNT_ID}', urlencode($account_id), $api_endpoint);

        // Construir cuerpo de la solicitud basado en el tipo de prompting
        if ($prompting_type === 'scoped') {
            $messages = array(
                array(
                    'role' => 'system',
                    'content' => $system_message
                ),
                array(
                    'role' => 'user',
                    'content' => $user_input
                )
            );
            $body = wp_json_encode(array('messages' => $messages));
        } else { // Unscoped
            $body = wp_json_encode(array('prompt' => $user_input));
        }

        // Realizar la solicitud a la API de Cloudflare Workers AI
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_token,
                'Content-Type'  => 'application/json',
            ),
            'body'    => $body,
            'timeout' => 60,
        ));

        // Manejar errores de la solicitud
        if (is_wp_error($response)) {
            wp_send_json_error(esc_html__('Error connecting to the API: ', 'workers-ai-chat') . esc_html($response->get_error_message()));
            wp_die();
        }

        // Obtener y decodificar la respuesta
        $response_body = wp_remote_retrieve_body($response);
        $decoded_response = json_decode($response_body, true);

        // Verificar si la respuesta es exitosa
        if (isset($decoded_response['success']) && $decoded_response['success']) {
            $ai_response = isset($decoded_response['result']['response']) ? sanitize_text_field($decoded_response['result']['response']) : esc_html__('No response received.', 'workers-ai-chat');
            wp_send_json_success($ai_response);
        } else {
            $error_messages = isset($decoded_response['errors']) ? implode(', ', array_map('esc_html', $decoded_response['errors'])) : esc_html__('Unknown error.', 'workers-ai-chat');
            wp_send_json_error(esc_html__('API Error: ', 'workers-ai-chat') . $error_messages);
        }

        wp_die();
    }
}

new WorkersAIChat();
