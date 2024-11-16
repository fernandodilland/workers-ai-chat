jQuery(document).ready(function($) {
    // Function to convert Markdown to HTML using Marked.js
    function convertMarkdownToHTML(markdownText) {
        if (typeof marked !== 'undefined') {
            // Configure Marked.js to sanitize generated HTML
            marked.setOptions({
                sanitize: true, // Sanitize HTML to prevent XSS
                breaks: true,   // Optional: convert line breaks to <br>
            });
            return marked.parse(markdownText);
        } else {
            // If Marked.js is not loaded, return the text unchanged
            return $('<div>').text(markdownText).html();
        }
    }

    // Handle form submission
    $('#workers-ai-chat-form').on('submit', function(e) {
        e.preventDefault();

        var userInput = $('#workers-ai-user-input').val().trim();
        if (userInput === '') {
            alert(workersAIChatData.error_empty_message || 'Please enter a message.');
            return;
        }

        sendMessage(userInput);
    });

    // Handle clicks on predefined questions
    $(document).on('click', '.workers-ai-question-button', function() {
        var question = $(this).text().trim();
        if (question !== '') {
            sendMessage(question);
        }
    });

    // Function to send messages
    function sendMessage(message) {
        // If ephemeral chats are enabled, clear the chat history
        if (workersAIChatData.ephemeral_chats === 'yes') {
            $('#workers-ai-chat-box').empty();
        }

        // Display user's message
        $('#workers-ai-chat-box').append('<div class="user-message"><strong>' + workersAIChatData.you_text + ':</strong> ' + $('<div>').text(message).html() + '</div>');

        // Scroll to the bottom
        $('#workers-ai-chat-box').scrollTop($('#workers-ai-chat-box')[0].scrollHeight);

        // Clear the input field
        $('#workers-ai-user-input').val('');

        // Show "Thinking..." indicator
        var loadingHTML = '<div class="ai-message"><strong>' + workersAIChatData.ai_name + ':</strong> <span class="loading-indicator">' + workersAIChatData.thinking_text + '</span></div>';
        $('#workers-ai-chat-box').append(loadingHTML);

        // Scroll to the bottom
        $('#workers-ai-chat-box').scrollTop($('#workers-ai-chat-box')[0].scrollHeight);

        // Disable the send button to prevent multiple submissions
        $('#workers-ai-submit-button').prop('disabled', true);

        // Send AJAX request
        $.ajax({
            type: 'POST',
            url: workersAIChatData.ajax_url,
            data: {
                action: 'workers_ai_chat',
                nonce: workersAIChatData.nonce,
                user_input: message
            },
            success: function(response) {
                if (response.success) {
                    // Convert Markdown to HTML
                    var aiResponseHTML = convertMarkdownToHTML(response.data);
                    // Replace loading indicator with AI response
                    $('#workers-ai-chat-box .ai-message:last').html('<strong>' + workersAIChatData.ai_name + ':</strong> ' + aiResponseHTML);
                } else {
                    $('#workers-ai-chat-box .ai-message:last').html('<strong>' + workersAIChatData.ai_name + ':</strong> <span style="color: red;">' + $('<div>').text(response.data).html() + '</span>');
                }

                // Scroll to the bottom
                $('#workers-ai-chat-box').scrollTop($('#workers-ai-chat-box')[0].scrollHeight);

                // Enable the send button
                $('#workers-ai-submit-button').prop('disabled', false);
            },
            error: function() {
                $('#workers-ai-chat-box .ai-message:last').html('<strong>' + workersAIChatData.ai_name + ':</strong> <span style="color: red;">' + workersAIChatData.error_processing_request + '</span>');

                // Scroll to the bottom
                $('#workers-ai-chat-box').scrollTop($('#workers-ai-chat-box')[0].scrollHeight);

                // Enable the send button
                $('#workers-ai-submit-button').prop('disabled', false);
            }
        });
    }
});
