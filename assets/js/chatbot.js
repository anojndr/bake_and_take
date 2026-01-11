/**
 * Bake & Take AI Chatbot
 * Uses Ollama with qwen3:0.6b model
 */

class BakeAndTakeChatbot {
    constructor() {
        this.isOpen = false;
        this.isLoading = false;
        this.messages = [];
        this.init();
    }

    init() {
        this.createChatbotUI();
        this.attachEventListeners();
        this.addWelcomeMessage();
    }

    createChatbotUI() {
        // Create chatbot container
        const chatbotHTML = `
            <div class="chatbot-container" id="chatbotContainer">
                <!-- Chatbot Toggle Button -->
                <button class="chatbot-toggle" id="chatbotToggle" aria-label="Open Chat">
                    <i class="bi bi-chat-dots-fill"></i>
                    <span class="chatbot-toggle-text">Ask AI</span>
                </button>

                <!-- Chatbot Window -->
                <div class="chatbot-window" id="chatbotWindow">
                    <div class="chatbot-header">
                        <div class="chatbot-header-info">
                            <div class="chatbot-avatar">
                                <i class="bi bi-robot"></i>
                            </div>
                            <div>
                                <h4>Bake & Take Assistant</h4>
                                <span class="chatbot-status">
                                    <span class="status-dot"></span>
                                    Online
                                </span>
                            </div>
                        </div>
                        <button class="chatbot-close" id="chatbotClose" aria-label="Close Chat">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    
                    <div class="chatbot-messages" id="chatbotMessages">
                        <!-- Messages will be added here -->
                    </div>

                    <div class="chatbot-input-container">
                        <form class="chatbot-form" id="chatbotForm">
                            <input 
                                type="text" 
                                class="chatbot-input" 
                                id="chatbotInput" 
                                placeholder="Ask about our bakery..."
                                autocomplete="off"
                                maxlength="500"
                            >
                            <button type="submit" class="chatbot-send" id="chatbotSend" aria-label="Send Message">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </form>
                        <div class="chatbot-powered">
                            <i class="bi bi-stars"></i> Powered by AI
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Append to body
        document.body.insertAdjacentHTML('beforeend', chatbotHTML);

        // Cache DOM elements
        this.container = document.getElementById('chatbotContainer');
        this.toggleBtn = document.getElementById('chatbotToggle');
        this.window = document.getElementById('chatbotWindow');
        this.closeBtn = document.getElementById('chatbotClose');
        this.messagesContainer = document.getElementById('chatbotMessages');
        this.form = document.getElementById('chatbotForm');
        this.input = document.getElementById('chatbotInput');
        this.sendBtn = document.getElementById('chatbotSend');
    }

    attachEventListeners() {
        // Toggle chatbot
        this.toggleBtn.addEventListener('click', () => this.toggle());

        // Close chatbot
        this.closeBtn.addEventListener('click', () => this.close());

        // Handle form submission
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });

        // Quick action buttons
        this.messagesContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('quick-action')) {
                const question = e.target.dataset.question;
                if (question) {
                    this.input.value = question;
                    this.sendMessage();
                }
            }
        });
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.isOpen = true;
        this.window.classList.add('open');
        this.toggleBtn.classList.add('hidden');
        this.input.focus();
    }

    close() {
        this.isOpen = false;
        this.window.classList.remove('open');
        this.toggleBtn.classList.remove('hidden');
    }

    addWelcomeMessage() {
        const welcomeHTML = `
            <div class="chatbot-message bot">
                <div class="message-avatar">
                    <i class="bi bi-robot"></i>
                </div>
                <div class="message-content">
                    <p>Hi there! 👋 I'm your Bake & Take assistant. I can help you with:</p>
                    <ul>
                        <li>🍞 Our products & menu</li>
                        <li>📍 Store location & hours</li>
                        <li>🛒 Ordering process</li>
                        <li>ℹ️ General information</li>
                    </ul>
                    <div class="quick-actions">
                        <button class="quick-action" data-question="What products do you have?">View Products</button>
                        <button class="quick-action" data-question="What are your opening hours?">Opening Hours</button>
                        <button class="quick-action" data-question="How do I place an order?">How to Order</button>
                    </div>
                </div>
            </div>
        `;
        this.messagesContainer.innerHTML = welcomeHTML;
    }

    async sendMessage() {
        const message = this.input.value.trim();
        if (!message || this.isLoading) return;

        // Add user message
        this.addMessage(message, 'user');
        this.input.value = '';

        // Show loading
        this.setLoading(true);

        try {
            const response = await fetch('includes/chatbot_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message })
            });

            const data = await response.json();
            console.log('Chatbot response:', data);

            if (data.success && data.message) {
                this.addMessage(data.message, 'bot');
            } else if (data.message) {
                // Even if success is false, show the message if available
                this.addMessage(data.message, 'bot', true);
            } else {
                const errorMsg = data.error || "Unknown error occurred";
                console.error('Chatbot API error:', data);
                this.addMessage(
                    `I'm sorry, I'm having trouble connecting right now. Error: ${errorMsg}. Please try again later or contact us at anojndr@gmail.com.`,
                    'bot',
                    true
                );
            }
        } catch (error) {
            console.error('Chatbot fetch error:', error);
            this.addMessage(
                "I'm sorry, I couldn't process your request. Please make sure the server is running. You can also reach us at anojndr@gmail.com.",
                'bot',
                true
            );
        } finally {
            this.setLoading(false);
        }
    }

    addMessage(text, sender, isError = false) {
        const messageHTML = `
            <div class="chatbot-message ${sender} ${isError ? 'error' : ''}">
                <div class="message-avatar">
                    <i class="bi ${sender === 'user' ? 'bi-person-fill' : 'bi-robot'}"></i>
                </div>
                <div class="message-content">
                    <p>${this.formatMessage(text)}</p>
                </div>
            </div>
        `;

        // Remove loading indicator if present
        const loadingEl = this.messagesContainer.querySelector('.loading-message');
        if (loadingEl) {
            loadingEl.remove();
        }

        this.messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();
    }

    formatMessage(text) {
        // Convert markdown-like formatting to HTML
        return text
            // Bold
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            // Italic
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            // Line breaks
            .replace(/\n/g, '<br>')
            // Lists (basic)
            .replace(/^- (.*)/gm, '• $1');
    }

    setLoading(loading) {
        this.isLoading = loading;
        this.sendBtn.disabled = loading;
        this.input.disabled = loading;

        if (loading) {
            const loadingHTML = `
                <div class="chatbot-message bot loading-message">
                    <div class="message-avatar">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div class="message-content">
                        <div class="typing-indicator">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            `;
            this.messagesContainer.insertAdjacentHTML('beforeend', loadingHTML);
            this.scrollToBottom();
        }
    }

    scrollToBottom() {
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }
}

// Initialize chatbot when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    window.bakeAndTakeChatbot = new BakeAndTakeChatbot();
});
