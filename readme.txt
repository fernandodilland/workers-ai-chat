=== Workers AI Chat ===
Contributors: fernandodilland
Tags: ai, chat, cloudflare, shortcode, chatbot
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.3
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Workers AI Chat allows you to integrate Cloudflare Workers AI into your WordPress site as a chat widget using a simple shortcode.

== Description ==

Workers AI Chat is a powerful plugin that embeds an AI-powered chat widget on your WordPress site. The chat is powered by Cloudflare Workers AI and can be configured through an easy-to-use settings page.

**Features:**
- Display the chat widget anywhere using the `[workers-ai]` shortcode.
- Configure API keys, endpoints, and chat behavior in the settings panel.
- Support for scoped and unscoped prompting types.
- Predefined questions for quick user interactions.
- Multilingual support with `.mo` and `.po` files for translations.
- Lightweight and customizable.

**Use Cases:**
- Customer support automation.
- Website visitor engagement.
- Interactive FAQs.

== Installation ==

1. Upload the `workers-ai-chat` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to 'Settings' > 'Workers AI Chat' to configure the plugin settings.
4. Add the `[workers-ai]` shortcode to any post, page, or widget to display the chat.

== Frequently Asked Questions ==

= How do I configure the API settings? =
Go to 'Settings' > 'Workers AI Chat' and fill in the required fields, such as Account ID, API Token, and API Endpoint.

= Can I customize the AI's name and predefined questions? =
Yes, you can customize the AI's name and predefined questions in the plugin settings under 'Settings' > 'Workers AI Chat.'

= Does the plugin support translations? =
Yes, the plugin is fully translatable. Place your language files in the `/languages/` directory or in `/wp-content/languages/plugins/`.

= Is the chat ephemeral? =
You can enable or disable ephemeral chats in the settings. When enabled, the chat history resets after each session.

== Screenshots ==

1. **Chat Widget** - The AI chat interface displayed on the front end.
2. **Settings Page** - Configure API and behavior options.

== Changelog ==

= 2.2 =
* Added scoped prompting option with system messages.
* Improved multilingual support.
* Added ephemeral chat toggle.

== Upgrade Notice ==

= 2.2 =
Ensure your settings are updated to include the new options for scoped prompts and ephemeral chats.

== License ==

This plugin is licensed under the GPLv2 or later. See [GPL-2.0 License](https://www.gnu.org/licenses/gpl-2.0.html) for more details.
