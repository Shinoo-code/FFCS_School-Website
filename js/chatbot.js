document.addEventListener("DOMContentLoaded", () => {
    // --- Get DOM Elements (using your IDs from footer.php) ---
    const toggleBtn = document.getElementById("chatbot-toggle");
    const sendBtn = document.getElementById("chat-send-btn");
    const chatWindow = document.getElementById("chat-window");
    const chatBody = document.getElementById("chat-body");
    const chatInput = document.getElementById("chat-input");

    // --- Event Listeners ---

    // Toggle the chat window
    toggleBtn.addEventListener("click", toggleChatWindow);

    // Send message on button click
    sendBtn.addEventListener("click", sendMessage);

    // Send message on 'Enter' key press
    chatInput.addEventListener("keypress", (e) => {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // --- Functions ---

    /**
     * Toggles the chat window visibility
     */
    function toggleChatWindow() {
        chatWindow.classList.toggle("active");
        
        // Toggle the button icon
        const icon = toggleBtn.querySelector("i");
        if (icon) {
            if (chatWindow.classList.contains("active")) {
                icon.classList.remove("bi-chat-dots-fill");
                icon.classList.add("bi-x-lg"); 
            } else {
                icon.classList.remove("bi-x-lg");
                icon.classList.add("bi-chat-dots-fill");
            }
        }

        if (chatWindow.classList.contains("active")) {
            chatInput.focus();
            // Add welcome message if chat is empty
            if (chatBody.children.length === 0) {
                 appendMessage("bot", "Hi there! 👋 How can I help you today? You can ask me about enrollment, programs, or contact info.");
            }
            scrollToBottom();
        }
    }

    /**
     * Sends the user's message to the backend
     */
    async function sendMessage() {
        const messageText = chatInput.value.trim();
        if (messageText === "") return;

        // 1. Display user's message
        appendMessage("user", messageText);
        chatInput.value = "";
        scrollToBottom();

        // 2. Show typing indicator
        const loadingIndicator = appendMessage("loading", "");
        scrollToBottom();

        try {
            // 3. Send to backend API
            const response = await fetch("api/chatbot/get_response.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ message: messageText }),
            });

            if (!response.ok) {
                throw new Error("Network response was not ok");
            }

            const data = await response.json();
            const botReply = data.reply || "I'm sorry, I didn't understand that.";

            // 4. Remove typing indicator
            loadingIndicator.remove();

            // 5. Display bot's reply
            appendMessage("bot", botReply);
            scrollToBottom();

        } catch (error) {
            console.error("Chatbot Error:", error);
            // Handle error: show error message in chat
            loadingIndicator.remove();
            appendMessage("bot", "Oops! Something went wrong. Please try again later.");
            scrollToBottom();
        }
    }

    /**
     * Appends a new message to the chat body
     * @param {string} sender - 'user', 'bot', or 'loading'
     * @param {string} text - The message content
     * @returns {HTMLElement} - The created message element
     */
    function appendMessage(sender, text) {
        const messageDiv = document.createElement("div");
        messageDiv.classList.add("chatbot-message", sender);

        const messageTextDiv = document.createElement("div");
        messageTextDiv.classList.add("chatbot-message-text");

        if (sender === "loading") {
            messageTextDiv.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';
        } else {
            // Use innerHTML to render the <a> tags from the PHP script
            messageTextDiv.innerHTML = text;
        }
        
        messageDiv.appendChild(messageTextDiv);
        chatBody.appendChild(messageDiv);
        return messageDiv;
    }

    /**
     * Scrolls the chat body to the bottom
     */
    function scrollToBottom() {
        chatBody.scrollTop = chatBody.scrollHeight;
    }
});