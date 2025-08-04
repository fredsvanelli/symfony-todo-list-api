# Tasks Checklist API - Symfony Docker

This is a REST API for task management (task checklist) built with Symfony and Docker.

## 🚀 Technologies Used

- **Symfony 7.3** - PHP Framework
- **Docker** - Containerization
- **PostgreSQL** - Database
- **API Platform** - Automatic REST API generation
- **Doctrine ORM** - Object-relational mapping
- **JWT Authentication** - Secure authentication

## 📋 Features

- ✅ User registration and authentication
- ✅ JWT-based authentication
- ✅ Create tasks (authenticated users only)
- ✅ List all tasks (user's own tasks only) with pagination
- ✅ View specific task (user's own tasks only)
- ✅ Update tasks (partial updates with PATCH)
- ✅ Delete tasks (user's own tasks only)
- ✅ Automatic timestamps (createdAt, updatedAt)
- ✅ API status information
- ✅ Support for both JSON and JSON-LD formats
- ✅ Request validation with detailed error messages
- ✅ Access control (users can only access their own tasks)
- ✅ Clean JSON serialization (no duplicate properties)
- ✅ Pagination (10 items per page)

## 🏃‍♂️ How to Run

### Prerequisites
- Docker and Docker Compose installed

### Execution steps

1. **Clone the repository**
```bash
git clone <your-repository>
cd symfony-docker
```

2. **Start the containers**
```bash
docker compose up -d
```

3. **Wait for services to be ready**
```bash
docker compose ps
```

4. **Run migrations (if needed)**
```bash
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

5. **Generate JWT keys (if needed)**
```bash
docker compose exec php bin/console lexik:jwt:generate-keypair
```

6. **Access the API**
- API Documentation: https://localhost/api
- API Endpoint: https://localhost/api/tasks
- Health Check: https://localhost/api/health
- API Status: https://localhost/api/status

## 📚 API Endpoints

### Authentication
- `POST /api/auth/register` - Register a new user
- `POST /api/auth/login` - Login and get JWT token

### Health & Status
- `GET /api/status` - API information and available endpoints
- `GET /api/health` - Health check with database connectivity test

### Tasks (Requires Authentication)
- `GET /api/tasks` - List all user's tasks (paginated, 10 per page)
- `GET /api/tasks?page=1` - List first page of user's tasks
- `GET /api/tasks?page=2` - List second page of user's tasks
- `GET /api/tasks/{id}` - Get a specific task (user's own)
- `POST /api/tasks` - Create a new task
- `PATCH /api/tasks/{id}` - Update a task (partial update)
- `DELETE /api/tasks/{id}` - Remove a task

## 📝 Entity Structures

### User Entity
```json
{
  "id": 1,
  "email": "user@example.com",
  "roles": ["ROLE_USER"]
}
```

### Task Entity
```json
{
  "id": 1,
  "title": "Task title",
  "description": "Task description (optional)",
  "isDone": false,
  "createdAt": "2025-08-03T18:30:30+00:00",
  "updatedAt": "2025-08-03T18:30:30+00:00"
}
```

### Paginated Response Structure
```json
{
  "tasks": [
    {
      "id": 1,
      "title": "Task 1",
      "description": "Description",
      "isDone": false,
      "createdAt": "2025-08-03T18:30:30+00:00",
      "updatedAt": "2025-08-03T18:30:30+00:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 3,
    "total_items": 25,
    "items_per_page": 10,
    "has_next_page": true,
    "has_previous_page": false,
    "next_page": 2,
    "previous_page": null
  }
}
```

## 🔍 Validation Rules

### User Registration
- **Email**: Required, valid email format, unique
- **Password**: Required, minimum 6 characters

### Task Creation/Update
- **Title**: Required, 1-255 characters, cannot be empty
- **Description**: Optional, maximum 1000 characters
- **isDone**: Required, boolean value (true/false)

## 🔧 Usage Examples

### User Registration
```bash
curl -k -X POST https://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

### User Login
```bash
curl -k -X POST https://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

### Create a new task (with authentication)
```bash
# First, get JWT token from login
TOKEN="your_jwt_token_here"

curl -k -X POST https://localhost/api/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "title": "My new task",
    "description": "Task description",
    "isDone": false
  }'
```

### List user's tasks (first page)
```bash
TOKEN="your_jwt_token_here"

curl -k https://localhost/api/tasks \
  -H "Authorization: Bearer $TOKEN"
```

### List user's tasks (specific page)
```bash
TOKEN="your_jwt_token_here"

curl -k "https://localhost/api/tasks?page=2" \
  -H "Authorization: Bearer $TOKEN"
```

### Update a task (with authentication)
```bash
TOKEN="your_jwt_token_here"

curl -k -X PATCH https://localhost/api/tasks/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "title": "Updated task title",
    "isDone": true
  }'
```

### Delete a task (with authentication)
```bash
TOKEN="your_jwt_token_here"

curl -k -X DELETE https://localhost/api/tasks/1 \
  -H "Authorization: Bearer $TOKEN"
```

## 📄 Pagination

The tasks list endpoint supports pagination with the following features:

### Parameters
- **page** (optional): Page number (default: 1)
- **items_per_page**: Fixed at 10 items per page

### Response Structure
The pagination object contains:
- **current_page**: Current page number
- **total_pages**: Total number of pages
- **total_items**: Total number of tasks
- **items_per_page**: Number of items per page (10)
- **has_next_page**: Boolean indicating if there's a next page
- **has_previous_page**: Boolean indicating if there's a previous page
- **next_page**: Next page number (null if no next page)
- **previous_page**: Previous page number (null if no previous page)

### Examples

#### First Page (default)
```bash
curl -k https://localhost/api/tasks
```

#### Second Page
```bash
curl -k "https://localhost/api/tasks?page=2"
```

#### Last Page
```bash
curl -k "https://localhost/api/tasks?page=3"
```

## ❌ Error Examples

### Authentication Required
```bash
curl -k https://localhost/api/tasks
```
**Response**: `401 Unauthorized` - Authentication required

### Invalid Credentials
```bash
curl -k -X POST https://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "wrong@example.com",
    "password": "wrongpassword"
  }'
```
**Response**: `401 Unauthorized` - Invalid credentials

### Access Denied (trying to access another user's task)
```bash
TOKEN="user1_token"

curl -k https://localhost/api/tasks/999 \
  -H "Authorization: Bearer $TOKEN"
```
**Response**: `403 Forbidden` - Access denied

### Invalid Page Number
```bash
TOKEN="your_jwt_token_here"

curl -k "https://localhost/api/tasks?page=0" \
  -H "Authorization: Bearer $TOKEN"
```
**Response**: Returns page 1 (minimum page number enforced)

## 🔒 Security Features

- **JWT Authentication**: All task endpoints require valid JWT token
- **User Isolation**: Users can only access their own tasks
- **Password Hashing**: Passwords are securely hashed using bcrypt
- **Input Validation**: All inputs are validated before processing
- **HTTPS**: API is configured for HTTPS requests
- **CORS**: Configured to allow cross-origin requests

## 🛠️ Useful Commands

### Access PHP container
```bash
docker compose exec php bash
```

### Run Symfony commands
```bash
docker compose exec php bin/console <command>
```

### View logs
```bash
docker compose logs -f php
```

### Stop containers
```bash
docker compose down
```

## 📁 Project Structure

```
symfony-docker/
├── src/
│   ├── Controller/
│   │   ├── TaskController.php
│   │   ├── AuthController.php
│   │   └── HealthController.php
│   ├── Entity/
│   │   ├── Task.php
│   │   └── User.php
│   └── Repository/
│       ├── TaskRepository.php
│       └── UserRepository.php
├── migrations/
├── config/
├── public/
├── compose.yaml
├── Dockerfile
└── README.md
```

## 🚀 Next Steps

- [ ] Add JWT token refresh functionality
- [ ] Implement password reset
- [ ] Add user profile management
- [ ] Add sorting options (by title, date, status)
- [ ] Add filtering options (by status, date range)
- [ ] Create automated tests
- [ ] Implement caching
- [ ] Add Swagger/OpenAPI documentation

## 📞 Support

For questions or issues, consult the Symfony and API Platform documentation:

- [Symfony Documentation](https://symfony.com/doc/)
- [API Platform Documentation](https://api-platform.com/docs/)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/orm.html)
- [LexikJWTAuthenticationBundle Documentation](https://github.com/lexik/LexikJWTAuthenticationBundle) 
