# 🔧 API Reference

API documentation untuk Attendance Management System.

## Base URL

```
Development: http://localhost:8000/api/v1
Production:  https://your-domain.com/api/v1
```

## Authentication

Semua endpoint (kecuali login/register) memerlukan Bearer token:

```http
Authorization: Bearer {token}
```

## Response Format

### Success Response

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... }
}
```

### Pagination Response

```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
  }
}
```

---

## Endpoints

### 🔐 Authentication

#### Login

```http
POST /auth/login
```

**Body:**

```json
{
  "email": "user@example.com",
  "password": "password"
}
```

**Response:**

```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "roles": ["admin"]
  }
}
```

#### Logout

```http
POST /auth/logout
```

#### Get Current User

```http
GET /auth/me
```

---

### 👥 Employees

#### List Employees

```http
GET /employees
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| page | int | Page number |
| per_page | int | Items per page (default: 15) |
| search | string | Search by name/email |
| location_id | int | Filter by location |
| is_active | bool | Filter by status |

#### Get Employee

```http
GET /employees/{id}
```

#### Create Employee

```http
POST /employees
```

**Body:**

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "position": "Staff",
  "location_id": 1,
  "base_salary": 5000000
}
```

#### Update Employee

```http
PUT /employees/{id}
```

#### Delete Employee

```http
DELETE /employees/{id}
```

---

### 📍 Attendance

#### Check-in

```http
POST /attendance/check-in
```

**Body:**

```json
{
  "latitude": -6.200000,
  "longitude": 106.816666,
  "face_image": "base64...",
  "notes": "Optional notes"
}
```

#### Check-out

```http
POST /attendance/check-out
```

**Body:**

```json
{
  "latitude": -6.200000,
  "longitude": 106.816666,
  "face_image": "base64..."
}
```

#### Get Today's Attendance

```http
GET /attendance/today
```

#### Get Attendance History

```http
GET /attendance/history
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| start_date | date | Start date (Y-m-d) |
| end_date | date | End date (Y-m-d) |
| employee_id | int | Filter by employee |

---

### 📅 Schedules

#### List Schedules

```http
GET /schedules
```

#### Get Monthly Schedule

```http
GET /monthly-schedules
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| month | int | Month (1-12) |
| year | int | Year |
| employee_id | int | Filter by employee |

---

### 🏖️ Leave

#### Request Leave

```http
POST /leave/request
```

**Body:**

```json
{
  "leave_type_id": 1,
  "start_date": "2024-01-15",
  "end_date": "2024-01-17",
  "reason": "Family event"
}
```

#### Get Leave Balance

```http
GET /leave/balance
```

#### Get Leave History

```http
GET /leave/history
```

---

### 👤 Face Recognition

#### Register Face

```http
POST /face/register
```

**Body:**

```json
{
  "image": "base64..."
}
```

#### Verify Face

```http
POST /face/verify
```

**Body:**

```json
{
  "image": "base64..."
}
```

**Response:**

```json
{
  "verified": true,
  "confidence": 0.95,
  "message": "Face verified successfully"
}
```

---

### 📊 Reports

#### Generate Report

```http
POST /reports/generate
```

**Body:**

```json
{
  "type": "attendance",
  "format": "excel",
  "start_date": "2024-01-01",
  "end_date": "2024-01-31",
  "employee_ids": [1, 2, 3]
}
```

---

### 📍 Locations

#### List Locations

```http
GET /locations
```

#### Create Location

```http
POST /locations
```

**Body:**

```json
{
  "name": "Head Office",
  "address": "Jl. Sudirman No. 1",
  "latitude": -6.200000,
  "longitude": 106.816666,
  "radius_meters": 100
}
```

---

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad Request - Invalid input |
| 401 | Unauthorized - Invalid/expired token |
| 403 | Forbidden - No permission |
| 404 | Not Found - Resource doesn't exist |
| 422 | Validation Error |
| 500 | Server Error |

## Rate Limiting

- Default: 60 requests/minute
- Face verification: 30 requests/minute

## Interactive Documentation

Akses Swagger UI untuk testing interaktif:

```
http://localhost:8000/api/documentation
```
