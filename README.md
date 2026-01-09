# Bake & Take - Artisan Bakery Web App

A beautiful, modern bakery web application built with PHP, Bootstrap, CSS, and JavaScript.

## Features

- 🍞 **Product Catalog** - Browse artisan breads, pastries, cakes, and cookies
- 🛒 **Shopping Cart** - Add items, update quantities, and checkout
- 👤 **User Authentication** - Register and login functionality
- 📧 **Contact Form** - Get in touch with the bakery
- 📱 **Responsive Design** - Works on desktop, tablet, and mobile
- ✨ **Modern UI** - Beautiful animations and premium design

## Tech Stack

- **Backend**: PHP 7.4+
- **Frontend**: Bootstrap 5, CSS3, JavaScript (ES6+)
- **Database**: MySQL/MariaDB (optional for full functionality)
- **Icons**: Bootstrap Icons
- **Fonts**: Google Fonts (Playfair Display, Poppins)

## Installation

### Prerequisites

- PHP 7.4 or higher
- Web server (Apache, Nginx, or PHP built-in server)
- MySQL/MariaDB (optional)

### Quick Start

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/bake_and_take.git
   cd bake_and_take
   ```

2. **Configure the database** (optional)
   - Import `database/schema.sql` into MySQL
   - Update `includes/config.php` with your database credentials

3. **Start a local server**
   ```bash
   # Using PHP's built-in server
   php -S localhost:8000
   
   # Or use XAMPP/WAMP and place in htdocs folder
   ```

4. **Open in browser**
   ```
   http://localhost:8000
   ```

## Project Structure

```
bake_and_take/
├── assets/
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   ├── js/
│   │   └── main.js            # JavaScript functionality
│   └── images/
│       └── products/          # Product images
├── database/
│   └── schema.sql             # Database schema
├── includes/
│   ├── config.php             # Configuration settings
│   ├── functions.php          # Helper functions
│   ├── process_*.php          # Form processors
│   └── logout.php             # Logout handler
├── pages/
│   ├── home.php               # Homepage
│   ├── menu.php               # Product listing
│   ├── about.php              # About page
│   ├── contact.php            # Contact page
│   ├── cart.php               # Shopping cart
│   ├── checkout.php           # Checkout page
│   ├── login.php              # Login page
│   ├── register.php           # Registration page
│   └── order-success.php      # Order confirmation
└── index.php                  # Main entry point
```

## Features in Detail

### Shopping Cart
- Client-side cart management using localStorage
- Real-time cart updates without page reload
- Quantity controls and item removal
- Automatic tax calculation

### User Authentication
- Secure login and registration forms
- CSRF protection
- Session-based authentication
- Password validation

### Contact Form
- Form validation (client and server-side)
- CSRF protection
- Success/error notifications

## Customization

### Colors
Edit the CSS variables in `assets/css/style.css`:
```css
:root {
    --primary: #D4A574;
    --secondary: #8B4513;
    --dark: #2C1810;
    /* ... */
}
```

### Products
Update the `$PRODUCTS` array in `includes/config.php` or use the database.

## License

This project is open source and available under the [MIT License](LICENSE).

## Credits

- Design inspired by modern bakery websites
- Icons by [Bootstrap Icons](https://icons.getbootstrap.com/)
- Fonts by [Google Fonts](https://fonts.google.com/)
