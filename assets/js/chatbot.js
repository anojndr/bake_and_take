/**
 * Bake & Take AI Chatbot
 * Uses Ollama with qwen3:0.6b model
 */

class BakeAndTakeChatbot {
    constructor() {
        this.isOpen = false;
        this.isLoading = false;
        this.messages = [];
        this.isDragging = false;
        this.dragStartX = 0;
        this.dragStartY = 0;
        this.buttonStartX = 0;
        this.buttonStartY = 0;
        this.hasDragged = false;
        this.init();
    }

    init() {
        this.createChatbotUI();
        this.attachEventListeners();
        this.attachDragListeners();
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
        // Toggle chatbot (only if not dragging)
        this.toggleBtn.addEventListener('click', (e) => {
            if (!this.hasDragged) {
                this.toggle();
            }
            this.hasDragged = false;
        });

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

    attachDragListeners() {
        // Mouse events
        this.toggleBtn.addEventListener('mousedown', (e) => this.startDrag(e));
        document.addEventListener('mousemove', (e) => this.drag(e));
        document.addEventListener('mouseup', (e) => this.endDrag(e));

        // Touch events for mobile
        this.toggleBtn.addEventListener('touchstart', (e) => this.startDrag(e), { passive: false });
        document.addEventListener('touchmove', (e) => this.drag(e), { passive: false });
        document.addEventListener('touchend', (e) => this.endDrag(e));
    }

    startDrag(e) {
        // Only start drag on left mouse button or touch
        if (e.type === 'mousedown' && e.button !== 0) return;

        const clientX = e.type === 'touchstart' ? e.touches[0].clientX : e.clientX;
        const clientY = e.type === 'touchstart' ? e.touches[0].clientY : e.clientY;

        this.isDragging = true;
        this.hasDragged = false;
        this.dragStartX = clientX;
        this.dragStartY = clientY;

        const rect = this.container.getBoundingClientRect();
        this.buttonStartX = rect.left;
        this.buttonStartY = rect.top;

        this.toggleBtn.classList.add('dragging');
        this.container.style.transition = 'none';

        // Prevent text selection during drag
        e.preventDefault();
    }

    drag(e) {
        if (!this.isDragging) return;

        const clientX = e.type === 'touchmove' ? e.touches[0].clientX : e.clientX;
        const clientY = e.type === 'touchmove' ? e.touches[0].clientY : e.clientY;

        const deltaX = clientX - this.dragStartX;
        const deltaY = clientY - this.dragStartY;

        // Check if user has actually moved the button
        if (Math.abs(deltaX) > 5 || Math.abs(deltaY) > 5) {
            this.hasDragged = true;
        }

        // Calculate new position
        let newX = this.buttonStartX + deltaX;
        let newY = this.buttonStartY + deltaY;

        // Get button dimensions
        const btnRect = this.toggleBtn.getBoundingClientRect();
        const btnWidth = btnRect.width;
        const btnHeight = btnRect.height;

        // Constrain to viewport
        const maxX = window.innerWidth - btnWidth - 10;
        const maxY = window.innerHeight - btnHeight - 10;

        newX = Math.max(10, Math.min(newX, maxX));
        newY = Math.max(10, Math.min(newY, maxY));

        // Apply position using left/top instead of right/bottom
        this.container.style.right = 'auto';
        this.container.style.bottom = 'auto';
        this.container.style.left = newX + 'px';
        this.container.style.top = newY + 'px';

        e.preventDefault();
    }

    endDrag(e) {
        if (!this.isDragging) return;

        this.isDragging = false;
        this.toggleBtn.classList.remove('dragging');
        this.container.style.transition = '';
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
        this.positionChatWindow();
        this.window.classList.add('open');
        this.toggleBtn.classList.add('hidden');
        this.input.focus();
    }

    positionChatWindow() {
        const containerRect = this.container.getBoundingClientRect();
        const btnRect = this.toggleBtn.getBoundingClientRect();
        const padding = 20;
        const isMobile = window.innerWidth <= 480;

        // Get actual window dimensions (considering mobile responsiveness)
        let windowWidth = isMobile ? window.innerWidth - 24 : 380;
        let windowHeight = isMobile ? Math.min(window.innerHeight - 160, 450) : 560;

        // Use fixed positioning for the chat window
        this.window.style.position = 'fixed';

        // Calculate the best position for the window
        let top, left;

        // Vertical positioning - try to position so window is visible
        const spaceBelow = window.innerHeight - btnRect.bottom - padding;
        const spaceAbove = btnRect.top - padding;

        if (spaceBelow >= windowHeight) {
            // Position below the button
            top = btnRect.bottom + 10;
        } else if (spaceAbove >= windowHeight) {
            // Position above the button
            top = btnRect.top - windowHeight - 10;
        } else {
            // Center vertically in viewport
            top = Math.max(padding, (window.innerHeight - windowHeight) / 2);
        }

        // Horizontal positioning
        const spaceRight = window.innerWidth - btnRect.left - padding;
        const spaceLeft = btnRect.right - padding;

        if (spaceRight >= windowWidth) {
            // Align left edge with button left
            left = btnRect.left;
        } else if (spaceLeft >= windowWidth) {
            // Align right edge with button right
            left = btnRect.right - windowWidth;
        } else {
            // Center horizontally in viewport
            left = Math.max(padding, (window.innerWidth - windowWidth) / 2);
        }

        // Ensure window stays within viewport
        top = Math.max(padding, Math.min(top, window.innerHeight - windowHeight - padding));
        left = Math.max(padding, Math.min(left, window.innerWidth - windowWidth - padding));

        this.window.style.top = top + 'px';
        this.window.style.left = left + 'px';
        this.window.style.bottom = 'auto';
        this.window.style.right = 'auto';
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
