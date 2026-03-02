# Address Book for WooCommerce REST API Documentation

This document describes the REST API endpoints provided by the Address Book for WooCommerce plugin.

## Base URL

All endpoints are prefixed with the WooCommerce REST API namespace:

```
/wp-json/wc/v3/customers/{customer_id}/addresses
```

## Authentication

The API uses standard [WooCommerce REST API authentication](https://woocommerce.github.io/woocommerce-rest-api-docs/#authentication). You can authenticate using:

- **Consumer Key & Secret**: Pass as query parameters (`consumer_key`, `consumer_secret`)
- **OAuth 1.0a**: Header-based authentication
- **Application Passwords**: WordPress application passwords

## Permissions

All endpoints require appropriate WooCommerce REST API permissions:

| Endpoint | Permission Required |
|----------|---------------------|
| GET (read) | `read` customers |
| POST (create) | `create` customers |
| PUT (update) | `edit` customers |
| DELETE | `delete` customers |

---

## Endpoints

### 1. Get All Addresses

Retrieves all billing and shipping addresses for a customer.

**Endpoint:** `GET /wc/v3/customers/{customer_id}/addresses`

**Example Request:**
```bash
GET /wp-json/wc/v3/customers/123/addresses
```

**Example Response:**
```json
{
  "id": 123,
  "billing": {
    "home": {
      "first_name": "John",
      "last_name": "Doe",
      "company": "Acme Inc",
      "address_1": "123 Main Street",
      "address_2": "Apt 4",
      "city": "New York",
      "state": "NY",
      "postcode": "10001",
      "country": "US",
      "email": "john@example.com",
      "phone": "555-123-4567",
      "address_nickname": "Home"
    }
  },
  "billing_default": "home",
  "shipping": {},
  "shipping_default": null
}
```

---

### 2. Get Addresses by Type

Retrieves addresses for a specific type (billing or shipping).

**Endpoint:** `GET /wc/v3/customers/{customer_id}/addresses/{address_type}`

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `customer_id` | integer | The WordPress user ID |
| `address_type` | string | Either `billing` or `shipping` |

**Example Request:**
```bash
GET /wp-json/wc/v3/customers/123/addresses/billing
```

**Example Response:**
```json
{
  "id": 123,
  "billing": {
    "home": {
      "first_name": "John",
      "last_name": "Doe",
      "address_1": "123 Main Street",
      "city": "New York",
      "state": "NY",
      "postcode": "10001",
      "country": "US"
    }
  },
  "billing_default": "home"
}
```

---

### 3. Create Address

Creates a new address for a customer.

**Endpoint:** `POST /wc/v3/customers/{customer_id}/addresses/{address_type}`

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `customer_id` | integer | The WordPress user ID |
| `address_type` | string | Either `billing` or `shipping` |

**Request Body (JSON):**
```json
{
  "first_name": "Jane",
  "last_name": "Doe",
  "company": "Acme Inc",
  "address_1": "123 Main Street",
  "address_2": "Apt 4",
  "city": "New York",
  "state": "NY",
  "postcode": "10001",
  "country": "US",
  "email": "jane@example.com",
  "phone": "555-123-4567",
  "address_nickname": "Home"
}
```

**Example Request:**
```bash
curl -X POST https://example.com/wp-json/wc/v3/customers/123/addresses/billing \
  -H "Content-Type: application/json" \
  -u "consumer_key:consumer_secret" \
  -d '{"first_name":"Jane","last_name":"Doe","address_1":"123 Main St","city":"Anytown","state":"CA","postcode":"90210","country":"US"}'
```

**Example Response (HTTP 201):**
```json
{
  "home": {
    "first_name": "Jane",
    "last_name": "Doe",
    "address_1": "123 Main St",
    "city": "Anytown",
    "state": "CA",
    "postcode": "90210",
    "country": "US"
  }
}
```

---

### 4. Update Address

Updates an existing address for a customer.

**Endpoint:** `PUT /wc/v3/customers/{customer_id}/addresses/{address_type}/{address_id}`

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `customer_id` | integer | The WordPress user ID |
| `address_type` | string | Either `billing` or `shipping` |
| `address_id` | string | The address key (e.g., "home", "work") |

**Request Body (JSON):**
```json
{
  "first_name": "Jane",
  "last_name": "Smith",
  "city": "Los Angeles"
}
```

**Example Request:**
```bash
curl -X PUT https://example.com/wp-json/wc/v3/customers/123/addresses/billing/home \
  -H "Content-Type: application/json" \
  -u "consumer_key:consumer_secret" \
  -d '{"first_name":"Jane","last_name":"Smith","city":"Los Angeles"}'
```

**Example Response (HTTP 200):**
```json
{
  "home": {
    "first_name": "Jane",
    "last_name": "Smith",
    "address_1": "123 Main St",
    "city": "Los Angeles",
    "state": "CA",
    "postcode": "90210",
    "country": "US"
  }
}
```

---

### 5. Delete Address

Deletes an address from a customer's address book.

**Endpoint:** `DELETE /wc/v3/customers/{customer_id}/addresses/{address_type}/{address_id}`

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `customer_id` | integer | The WordPress user ID |
| `address_type` | string | Either `billing` or `shipping` |
| `address_id` | string | The address key to delete |

**Example Request:**
```bash
curl -X DELETE https://example.com/wp-json/wc/v3/customers/123/addresses/billing/home \
  -u "consumer_key:consumer_secret"
```

**Response:** HTTP 202 (No Content)

---

## Address Fields

The following fields are supported for addresses:

| Field | Type | Description |
|-------|------|-------------|
| `first_name` | string | First name |
| `last_name` | string | Last name |
| `company` | string | Company name |
| `address_1` | string | Address line 1 |
| `address_2` | string | Address line 2 |
| `city` | string | City |
| `state` | string | State/Province |
| `postcode` | string | Postal/ZIP code |
| `country` | string | Country code (2-letter ISO) |
| `email` | string | Email address (billing only) |
| `phone` | string | Phone number (billing only) |
| `address_nickname` | string | A friendly name for the address |

---

## Error Responses

### 400 Bad Request
```json
{
  "code": "woocommerce_api_missing_address_data",
  "message": "No billing data provided to create address.",
  "data": { "status": 400 }
}
```

### 401 Unauthorized
```json
{
  "code": "woocommerce_rest_cannot_edit",
  "message": "Sorry, you are not allowed to edit this resource.",
  "data": { "status": 401 }
}
```

### 404 Not Found
```json
{
  "code": "woocommerce_api_address_not_found",
  "message": "Address not found.",
  "data": { "status": 404 }
}
```

---

## Hooks

The API supports the following WordPress hooks:

- `woocommerce_api_create_address_book_data` - Filter address data before creating
- `woocommerce_api_edit_address_book_data` - Filter address data before updating
- `woocommerce_api_create_address_book_address` - Action after creating an address
- `woocommerce_api_edit_address_book_address` - Action after updating an address
- `woocommerce_api_delete_address_book_address` - Action after deleting an address
