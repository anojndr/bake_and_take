# Bake & Take User Authentication API

This API allows external applications to authenticate users against the Bake & Take user database. This enables "Login with Bake & Take" functionality across different platforms.

## Base URL
Assuming the site is hosted at `https://bakeandtake.com`:
`https://bakeandtake.com/api`

## Authentication Endpoint

**URL:** `/login.php`
**Method:** `POST`
**Content-Type:** `application/json`

### Description
Verifies user credentials (email and password) and returns user details if successful.

### Request Body
The request must contain a JSON object with the following fields:

| Field | Type | Required | Description |
|---|---|---|---|
| `email` | string | Yes | The user's registered email address. |
| `password` | string | Yes | The user's password. |

**Example Request:**
```json
{
    "email": "customer@example.com",
    "password": "secret_password"
}
```

### Success Response
**Code:** `200 OK`

**Example Response:**
```json
{
    "status": "success",
    "message": "Login successful.",
    "user": {
        "id": 42,
        "first_name": "John",
        "last_name": "Doe",
        "email": "customer@example.com",
        "phone": "09123456789",
        "is_admin": 0,
        "created_at": "2023-11-15 10:30:00"
    }
}
```

### Error Responses

**1. Invalid Credentials**
**Code:** `401 Unauthorized`
```json
{
    "status": "error",
    "message": "Invalid email or password."
}
```

**2. Missing Parameters**
**Code:** `400 Bad Request`
```json
{
    "status": "error",
    "message": "Incomplete data. Provide email and password."
}
```

**3. Method Not Allowed**
**Code:** `405 Method Not Allowed`
```json
{
    "status": "error",
    "message": "Method not allowed. Use POST."
}
```

## Usage Examples

### JavaScript (Fetch API)
```javascript
const loginUser = async (email, password) => {
    try {
        const response = await fetch('http://localhost/bake_and_take/api/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (response.ok) {
            console.log('User logged in:', data.user);
            // Save user data or token
        } else {
            console.error('Login failed:', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
};

loginUser('customer@example.com', 'mypassword');
```

### cURL
```bash
curl -X POST http://localhost/bake_and_take/api/login.php \
-H "Content-Type: application/json" \
-d '{"email":"customer@example.com", "password":"mypassword"}'
```
