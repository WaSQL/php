# PostPortal User Guide

## What is PostPortal?

PostPortal is a built-in API testing tool that lets you send HTTP requests and inspect responses directly from your WaSQL admin interface. Think of it as Postman, but integrated right into your application—no external tools needed!

**Perfect for:**
- Testing your own APIs
- Debugging web services
- Exploring third-party APIs
- Checking API responses
- Developing and troubleshooting integrations

---

## Getting Started

### Accessing PostPortal

1. Log in to your WaSQL admin interface as an administrator
2. Click on **PostPortal** in the admin menu
3. You'll see three main tabs: **Requests**, **History**, and **Environment**

> **Note:** PostPortal is only available to administrators for security reasons.

---

## Making Your First Request

Let's test a simple API:

1. Make sure you're on the **Requests** tab
2. Leave the method as **GET**
3. Enter this URL: `https://api.github.com/users/octocat`
4. Click **Send Request**

You'll see the response with user information from GitHub's API!

---

## The Request Builder

### Basic Request

<img src="request-builder.png" alt="Request Builder" />

**1. HTTP Method**
Choose from:
- **GET** - Retrieve data (most common)
- **POST** - Send data to create something new
- **PUT** - Update existing data
- **DELETE** - Remove data
- **PATCH** - Partially update data
- **OPTIONS** - Check what methods are allowed

**2. URL**
Enter the full API endpoint URL:
```
https://api.example.com/users
https://myapp.com/api/products/123
```

**3. Send Request Button**
Click to execute your request and see the response below.

---

## Advanced Features

### Params Tab

Build query parameters visually instead of typing them in the URL.

**Example:**
- Click **Add Parameter**
- Key: `per_page`, Value: `10`
- Key: `page`, Value: `1`

This automatically adds `?per_page=10&page=1` to your URL.

**Tips:**
- Parameters are great for search, filtering, and pagination
- Click **Remove** to delete parameters you don't need
- Parameters are automatically URL-encoded for you

---

### Authorization Tab

Authenticate your API requests.

#### No Auth
Use when the API doesn't require authentication.

#### Basic Auth
Username and password authentication.

**Example:**
```
Username: admin
Password: mypassword
```

**Common for:**
- Internal APIs
- Simple web services
- Legacy systems

#### Bearer Token
Token-based authentication (most common for modern APIs).

**Example:**
```
Token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**Common for:**
- REST APIs
- OAuth 2.0 APIs
- JWT authentication

> **Tip:** Use Environment Variables (see below) to store tokens securely!

---

### Headers Tab

Add custom HTTP headers to your request.

**Format:** One header per line as `Key: Value`

**Common Headers:**
```
Content-Type: application/json
Accept: application/json
X-API-Key: your-api-key-here
User-Agent: MyApp/1.0
```

**When to use headers:**
- Setting content type (JSON, XML, etc.)
- Adding API keys
- Custom authentication
- Requesting specific response formats

---

### Body Tab

Send data with POST, PUT, or PATCH requests.

**JSON Example:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "role": "developer"
}
```

**XML Example:**
```xml
<user>
  <name>John Doe</name>
  <email>john@example.com</email>
</user>
```

**Tips:**
- Make sure your `Content-Type` header matches your body format
- JSON: `Content-Type: application/json`
- XML: `Content-Type: application/xml`
- The body is ignored for GET requests

---

## Understanding Responses

After sending a request, you'll see:

### Status Information

- **Status Code** - Color-coded for quick understanding:
  - 🟢 **Green (2xx)** - Success! Everything worked
  - 🟡 **Yellow (3xx)** - Redirect or cached response
  - 🔴 **Red (4xx/5xx)** - Error (client or server)

- **Time** - How long the request took in milliseconds
- **Size** - Response body size

### Common Status Codes

| Code | Meaning | What It Means |
|------|---------|---------------|
| 200 | OK | Success! Your request worked |
| 201 | Created | New resource was created |
| 400 | Bad Request | Check your request format |
| 401 | Unauthorized | Authentication required or failed |
| 403 | Forbidden | You don't have permission |
| 404 | Not Found | The endpoint doesn't exist |
| 500 | Server Error | Something broke on the server |

### Response Tabs

**Request Headers**
Shows what headers you sent. Useful for verifying authentication was included.

**Request Body**
Shows the exact data you sent. Good for confirming format.

**Response Headers**
Shows headers the server sent back. Check for:
- Content type
- Rate limiting information
- Caching headers
- Server information

**Response Body**
The actual data returned. Automatically formatted for:
- **JSON** - Pretty-printed and syntax-highlighted
- **XML** - Indented and formatted
- **HTML** - Shown as code
- **Plain Text** - Displayed as-is

---

## Request History

PostPortal automatically saves your last 15 requests so you can easily replay them.

### Using History

1. Click the **History** tab
2. Find the request you want to replay
3. Click the ▶️ forward arrow icon
4. The request loads into the builder—modify if needed and send again!

### Managing History

- **Replay** - ▶️ icon loads the request
- **Delete** - ❌ icon removes individual requests
- **Clear History** - Removes all saved requests

**What's Saved:**
- URL and method
- Headers and body
- Authentication settings
- Response data and timing

**Tip:** History is perfect for:
- Repeating API calls during debugging
- Comparing different parameter values
- Tracking down intermittent issues

---

## Environment Variables

Store values you use repeatedly like API keys, base URLs, or user IDs.

### Creating Variables

1. Go to the **Environment** tab
2. Enter a **Variable name**: `api_key`
3. Enter a **Variable value**: `sk_test_123456789`
4. Click **Add Variable**

### Using Variables

Use double curly braces `{{variable_name}}` anywhere in your requests:

**In URLs:**
```
https://api.example.com/users/{{user_id}}
{{base_url}}/products
```

**In Headers:**
```
Authorization: Bearer {{auth_token}}
X-API-Key: {{api_key}}
```

**In Body:**
```json
{
  "api_key": "{{api_key}}",
  "user_id": "{{user_id}}"
}
```

### Common Variable Examples

```
base_url = https://api.example.com
api_key = your-secret-key-here
auth_token = eyJhbGciOiJIUzI1NiIs...
user_id = 12345
test_email = test@example.com
```

**Benefits:**
- Change once, update everywhere
- Keep API keys out of URLs
- Easy switching between environments
- Secure storage per user

---

## Practical Examples

### Example 1: Testing a Public API

**Goal:** Get information about a GitHub user

```
Method: GET
URL: https://api.github.com/users/octocat
Headers: (none needed)
```

**Response:** User profile data in JSON format

---

### Example 2: Creating a New Record

**Goal:** Add a new user to your API

```
Method: POST
URL: https://myapp.com/api/users
Headers:
  Content-Type: application/json
  Authorization: Bearer {{api_token}}
Body:
  {
    "name": "Jane Smith",
    "email": "jane@example.com",
    "role": "editor"
  }
```

**Response:** 201 Created with new user data

---

### Example 3: Updating Data

**Goal:** Update user information

```
Method: PUT
URL: https://myapp.com/api/users/{{user_id}}
Headers:
  Content-Type: application/json
  Authorization: Bearer {{api_token}}
Body:
  {
    "name": "Jane Smith-Jones",
    "role": "admin"
  }
```

**Response:** 200 OK with updated user data

---

### Example 4: Deleting a Record

**Goal:** Remove a user

```
Method: DELETE
URL: https://myapp.com/api/users/{{user_id}}
Headers:
  Authorization: Bearer {{api_token}}
```

**Response:** 204 No Content (success)

---

### Example 5: Using Query Parameters

**Goal:** Search for products

```
Method: GET
URL: https://myapp.com/api/products

Params Tab:
  category = electronics
  min_price = 100
  max_price = 500
  sort = price_asc
```

**Becomes:** `https://myapp.com/api/products?category=electronics&min_price=100&max_price=500&sort=price_asc`

---

## Tips & Best Practices

### Organization Tips

✅ **Use Environment Variables** for anything you use repeatedly
✅ **Save useful requests** by using history
✅ **Name your variables clearly** - `api_key` not `k1`
✅ **Test with small requests first** then add complexity

### Debugging Tips

🔍 **Check the status code first** - it tells you what went wrong
🔍 **Look at response headers** - they often contain clues
🔍 **Compare request vs response** - did you send what you thought?
🔍 **Use history** to see if something changed between attempts

### Security Tips

🔒 **Store API keys in environment variables**, not in URLs
🔒 **Clear history** after testing with sensitive data
🔒 **Don't share screenshots** of responses with tokens
🔒 **Use test accounts** when developing

---

## Common Issues & Solutions

### "Invalid URL format"
**Problem:** URL is missing `http://` or `https://`
**Solution:** Always include the protocol: `https://api.example.com`

### "401 Unauthorized"
**Problem:** Authentication is missing or incorrect
**Solution:**
- Check your auth type (Basic vs Bearer)
- Verify your credentials are correct
- Look for typos in tokens
- Check if token has expired

### "404 Not Found"
**Problem:** The endpoint doesn't exist
**Solution:**
- Double-check the URL spelling
- Verify the API version in the URL
- Check API documentation for correct endpoint

### "Timeout"
**Problem:** Request took longer than 30 seconds
**Solution:**
- API might be down or slow
- Try a simpler endpoint first
- Check your internet connection

### "Request body too large"
**Problem:** Body exceeds 10MB limit
**Solution:**
- Reduce the data you're sending
- Use pagination for large datasets
- Upload files separately if needed

### JSON Parse Error
**Problem:** Response isn't valid JSON
**Solution:**
- API might be returning HTML error page
- Check status code (might be 404 or 500)
- View as plain text to see actual response

---

## Keyboard Shortcuts

- **Ctrl+Enter** - Send request (when focus is in form)
- **Ctrl+K** - Focus URL field

---

## Limits & Restrictions

- **History:** Last 15 requests saved per user
- **Request Body:** 10MB maximum
- **Request Headers:** 100KB maximum
- **Response Size:** 50MB maximum
- **Timeout:** 30 seconds
- **Access:** Administrators only

---

## FAQ

**Q: Can I share my environment variables with other users?**
A: No, environment variables are private to each user account.

**Q: Can I export my request history?**
A: Not currently. Use your browser's copy/paste to save important requests.

**Q: Does PostPortal work with GraphQL APIs?**
A: Yes! Use POST method with your GraphQL query in the body.

**Q: Can I test localhost/local APIs?**
A: Yes, as long as the WaSQL server can reach that URL.

**Q: Is my data secure?**
A: Yes, all data is stored in your database and only accessible by your user account. History and environment variables are private.

**Q: Can I use PostPortal for file uploads?**
A: Limited support. You can send file data in the body, but there's no file picker interface.

**Q: What's the difference between PostPortal and Postman?**
A: PostPortal is simpler and integrated into WaSQL. Postman has more advanced features like automated testing, mock servers, and team collaboration.

---

## Getting Help

If you encounter issues:

1. Check the status code and error message
2. Review the response headers for clues
3. Try the request in a simpler form
4. Check API documentation for requirements
5. Clear history and environment if something seems cached

---

## Quick Reference Card

### Most Common Use Cases

| Task | Method | Needs Body? | Common Headers |
|------|--------|-------------|----------------|
| Get data | GET | No | Accept: application/json |
| Create new | POST | Yes | Content-Type: application/json |
| Update all | PUT | Yes | Content-Type: application/json |
| Update part | PATCH | Yes | Content-Type: application/json |
| Delete | DELETE | No | Authorization: Bearer {token} |
| Search | GET | No | Use query parameters |

### Header Quick Copy

```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
X-API-Key: {{api_key}}
```

### JSON Body Template

```json
{
  "key": "value",
  "number": 123,
  "array": ["item1", "item2"],
  "nested": {
    "key": "value"
  }
}
```

---

## Summary

PostPortal gives you everything you need to test APIs right from your WaSQL admin interface:

✅ Send any HTTP request (GET, POST, PUT, DELETE, etc.)
✅ Authenticate with Basic Auth or Bearer tokens
✅ View formatted responses (JSON, XML, HTML)
✅ Save and replay requests from history
✅ Store reusable values in environment variables
✅ All your data stays private and secure

**Start testing APIs in seconds—no external tools required!**
