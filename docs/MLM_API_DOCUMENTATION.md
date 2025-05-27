# MLM System API Documentation

## 🔌 API Overview

The MLM System provides a comprehensive RESTful API for integrating with external systems, mobile applications, and third-party services.

### Base URL
```
https://yourdomain.com/api/mlm/v1
```

### Authentication
All API requests require authentication using JWT tokens or API keys.

```http
Authorization: Bearer {jwt_token}
```

## 📋 API Endpoints

### 1. User Management

#### Get User Profile
```http
GET /api/mlm/v1/users/{user_id}
```

**Response:**
```json
{
  "id": 123,
  "username": "john_doe",
  "email": "john@example.com",
  "sponsor_id": 456,
  "rank": "Silver Leader",
  "join_date": "2024-01-15T10:30:00Z",
  "status": "active",
  "personal_volume": 1500.00,
  "team_volume": 25000.00,
  "total_earnings": 5250.75
}
```

#### Update User Profile
```http
PUT /api/mlm/v1/users/{user_id}
```

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "phone": "+1234567890",
  "address": {
    "street": "123 Main St",
    "city": "Anytown",
    "state": "CA",
    "zip": "12345",
    "country": "US"
  }
}
```

#### Get User Genealogy
```http
GET /api/mlm/v1/users/{user_id}/genealogy?levels={number}
```

**Response:**
```json
{
  "user_id": 123,
  "levels": 3,
  "tree": {
    "level_1": [
      {
        "id": 124,
        "username": "jane_smith",
        "join_date": "2024-02-01T14:20:00Z",
        "status": "active",
        "personal_volume": 800.00
      }
    ],
    "level_2": [...],
    "level_3": [...]
  },
  "statistics": {
    "total_downline": 45,
    "active_members": 38,
    "total_volume": 125000.00
  }
}
```

### 2. Commission Management

#### Get Commission History
```http
GET /api/mlm/v1/commissions?user_id={user_id}&from={date}&to={date}
```

**Response:**
```json
{
  "commissions": [
    {
      "id": 789,
      "user_id": 123,
      "type": "level_commission",
      "level": 1,
      "amount": 150.00,
      "source_user_id": 124,
      "source_transaction_id": 456,
      "date": "2024-12-01T09:15:00Z",
      "status": "paid"
    }
  ],
  "total_amount": 2750.50,
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total_pages": 3,
    "total_records": 125
  }
}
```

#### Calculate Commission
```http
POST /api/mlm/v1/commissions/calculate
```

**Request Body:**
```json
{
  "transaction_id": 456,
  "user_id": 124,
  "amount": 1500.00,
  "product_id": 789,
  "commission_type": "sales"
}
```

**Response:**
```json
{
  "transaction_id": 456,
  "total_commission": 375.00,
  "commissions": [
    {
      "user_id": 123,
      "level": 1,
      "rate": 10.00,
      "amount": 150.00
    },
    {
      "user_id": 122,
      "level": 2,
      "rate": 5.00,
      "amount": 75.00
    }
  ]
}
```

### 3. Payout Management

#### Get Payout History
```http
GET /api/mlm/v1/payouts?user_id={user_id}&status={status}
```

**Response:**
```json
{
  "payouts": [
    {
      "id": 101,
      "user_id": 123,
      "amount": 500.00,
      "fee": 2.50,
      "net_amount": 497.50,
      "method": "bank_transfer",
      "status": "completed",
      "request_date": "2024-11-25T10:00:00Z",
      "processed_date": "2024-11-26T14:30:00Z",
      "transaction_id": "TXN123456789"
    }
  ],
  "total_amount": 2500.00,
  "pending_amount": 750.00
}
```

#### Request Payout
```http
POST /api/mlm/v1/payouts/request
```

**Request Body:**
```json
{
  "user_id": 123,
  "amount": 500.00,
  "method": "bank_transfer",
  "account_details": {
    "bank_name": "Example Bank",
    "account_number": "1234567890",
    "routing_number": "987654321"
  }
}
```

#### Process Payout
```http
POST /api/mlm/v1/payouts/{payout_id}/process
```

**Request Body:**
```json
{
  "transaction_id": "TXN123456789",
  "notes": "Processed via bank transfer"
}
```

### 4. Product Management

#### Get Products
```http
GET /api/mlm/v1/products?category={category}&status={status}
```

**Response:**
```json
{
  "products": [
    {
      "id": 789,
      "name": "Premium Package",
      "description": "Complete MLM starter package",
      "price": 1500.00,
      "commission_rate": 10.00,
      "category": "packages",
      "status": "active",
      "created_date": "2024-01-01T00:00:00Z"
    }
  ]
}
```

#### Create Product
```http
POST /api/mlm/v1/products
```

**Request Body:**
```json
{
  "name": "Starter Package",
  "description": "Basic MLM package for new members",
  "price": 500.00,
  "commission_rate": 8.00,
  "category": "packages",
  "status": "active"
}
```

### 5. Analytics and Reports

#### Get Dashboard Statistics
```http
GET /api/mlm/v1/analytics/dashboard?user_id={user_id}
```

**Response:**
```json
{
  "user_id": 123,
  "period": "current_month",
  "statistics": {
    "personal_sales": 3000.00,
    "team_sales": 45000.00,
    "commissions_earned": 2250.00,
    "new_recruits": 5,
    "active_downline": 38,
    "rank": "Gold Leader",
    "next_rank_progress": 75.5
  },
  "charts": {
    "sales_trend": [...],
    "commission_breakdown": [...],
    "team_growth": [...]
  }
}
```

#### Generate Report
```http
POST /api/mlm/v1/reports/generate
```

**Request Body:**
```json
{
  "type": "commission_report",
  "user_id": 123,
  "date_range": {
    "from": "2024-11-01",
    "to": "2024-11-30"
  },
  "format": "pdf",
  "email": true
}
```

### 6. Rank Management

#### Get Rank Requirements
```http
GET /api/mlm/v1/ranks
```

**Response:**
```json
{
  "ranks": [
    {
      "id": 1,
      "name": "Bronze",
      "requirements": {
        "personal_volume": 500.00,
        "team_volume": 2000.00,
        "active_legs": 2
      },
      "benefits": {
        "commission_bonus": 0.00,
        "rank_bonus": 100.00
      }
    },
    {
      "id": 2,
      "name": "Silver",
      "requirements": {
        "personal_volume": 1000.00,
        "team_volume": 5000.00,
        "active_legs": 3
      },
      "benefits": {
        "commission_bonus": 1.00,
        "rank_bonus": 250.00
      }
    }
  ]
}
```

#### Check Rank Advancement
```http
GET /api/mlm/v1/users/{user_id}/rank-progress
```

**Response:**
```json
{
  "user_id": 123,
  "current_rank": "Silver",
  "next_rank": "Gold",
  "progress": {
    "personal_volume": {
      "current": 1500.00,
      "required": 2000.00,
      "percentage": 75.0
    },
    "team_volume": {
      "current": 8500.00,
      "required": 10000.00,
      "percentage": 85.0
    },
    "active_legs": {
      "current": 4,
      "required": 5,
      "percentage": 80.0
    }
  },
  "estimated_advancement": "2024-12-15"
}
```

## 🔒 Authentication

### JWT Token Authentication
```http
POST /api/mlm/v1/auth/login
```

**Request Body:**
```json
{
  "username": "john_doe",
  "password": "secure_password"
}
```

**Response:**
```json
{
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_in": 3600,
  "token_type": "Bearer"
}
```

### API Key Authentication
```http
GET /api/mlm/v1/users/123
X-API-Key: your_api_key_here
```

## 📊 Response Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Internal Server Error |

## 🔄 Webhooks

### Commission Calculated
```json
{
  "event": "commission.calculated",
  "data": {
    "commission_id": 789,
    "user_id": 123,
    "amount": 150.00,
    "type": "level_commission",
    "level": 1
  },
  "timestamp": "2024-12-01T09:15:00Z"
}
```

### Payout Processed
```json
{
  "event": "payout.processed",
  "data": {
    "payout_id": 101,
    "user_id": 123,
    "amount": 500.00,
    "status": "completed"
  },
  "timestamp": "2024-12-01T14:30:00Z"
}
```

### Rank Advanced
```json
{
  "event": "rank.advanced",
  "data": {
    "user_id": 123,
    "old_rank": "Silver",
    "new_rank": "Gold",
    "advancement_date": "2024-12-01T00:00:00Z"
  },
  "timestamp": "2024-12-01T00:00:00Z"
}
```

## 📝 Rate Limiting

- **Standard Users**: 100 requests per minute
- **Premium Users**: 500 requests per minute
- **Admin Users**: 1000 requests per minute

## 🛠️ SDKs and Libraries

### PHP SDK
```php
use MLM\SDK\Client;

$client = new Client([
    'base_uri' => 'https://yourdomain.com/api/mlm/v1',
    'api_key' => 'your_api_key'
]);

$user = $client->users()->get(123);
$commissions = $client->commissions()->getHistory(123);
```

### JavaScript SDK
```javascript
import MLMClient from 'mlm-api-client';

const client = new MLMClient({
  baseURL: 'https://yourdomain.com/api/mlm/v1',
  apiKey: 'your_api_key'
});

const user = await client.users.get(123);
const commissions = await client.commissions.getHistory(123);
```

---

**API Version**: v1.0
**Last Updated**: December 2024
**Support**: api-support@yourdomain.com
