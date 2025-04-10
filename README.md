# High-Throughput Lead Management System

A pure API-based Symfony application for ingesting and managing lead data with high throughput capabilities. Built as a headless service that communicates exclusively through JSON responses.

## ⚙️ Requirements

- Docker
- Docker Compose
- Git
- k6 (for load testing)
  ```bash
  # macOS
  brew install k6

  # Windows & Linux
  # Download from https://k6.io/docs/getting-started/installation
  ```

## 🚀 Quick Start

```bash
# Clone and build the project
git clone https://github.com/Khrischatyy/l2-test.git
cd l2-test

# Add the .env file (provided separately via email for security)
# Place the .env file in the project root directory

make build

# The build command will:
# 1. Start Docker containers
# 2. Install dependencies
# 3. Create database and run migrations
# 4. Create a test user
```

## 🔑 Authentication

Default credentials for testing:
```
Email: test@example.com
Password: password123
```

After login, you'll receive a JWT token valid for 1 hour.

## 📡 API Endpoints

### Authentication
```http
POST /api/login_check
Content-Type: application/json

{
    "email": "test@example.com",
    "password": "password123"
}
```

### Lead Management
```http
# Create Lead
POST /api/leads
Authorization: Bearer <jwt_token>
Content-Type: application/json

{
    "firstName": "John",
    "lastName": "Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "dateOfBirth": "1990-01-01",
    "additionalData": {
        "source": "website"
    }
}

# List Leads
GET /api/leads?page=1&limit=10&sortBy=createdAt&sortOrder=DESC
Authorization: Bearer <jwt_token>
```

## 🛠 Development Commands

```bash
# Project Management
make help              # Show all available commands
make build             # Build and set up the project
make start            # Start containers
make stop             # Stop containers
make restart          # Restart containers
make logs             # View container logs
make clear-cache      # Clear application cache

# API Monitoring
make api-logs          # Show last 10 API requests
make api-logs-errors   # Show last 10 API errors
make api-logs-full     # Show detailed log info by ID
make api-request-data  # Show full request/response JSON data
```

## 🔍 Monitoring & Debugging

### API Logs
Every API request is logged with:
- Request headers, body, and query parameters
- Response data and status codes
- Processing time metrics
- IP address and user agent

View logs using:
```bash
make api-logs              # Recent requests
make api-logs-errors       # Error logs only
make api-request-data      # Full request/response data
```

### Performance Metrics
- Redis caching for optimized responses
- Request processing time tracking
- Detailed error logging in development mode

## ⚠️ System Limits & Security

### Rate Limits
- 1000 requests per minute per IP
- Maximum payload: 10MB
- Batch processing: 1000 leads max

### Security
- JWT-based authentication
- Token expiry: 1 hour
- HTTPS required in production
- Sensitive data in `.env` (provided via email)

### Caching
- Redis-based caching
- Cache lifetime: 1 hour
- Manual cache clear: `make clear-cache`

## 📦 Development Tools

### Postman Collection
Located at `postman/Lead Management API.postman_collection.json`:
- Pre-configured environments
- Automatic JWT token handling
- Example requests for all endpoints

### Important Files
- `.env` - Environment configuration (provided separately)
- `docker-compose.yml` - Container configuration
- `Makefile` - Development commands
- `config/packages/` - Application configuration

### Version Control
- `.env` file is NOT included in repository
- `symfony.lock` and `composer.lock` should be committed
- Exclude `var/`, `vendor/`, and `data/` directories

## 🚀 Performance Testing

![Load Test Performance](docs/assets/load-test.gif)

### Latest Test Results

```
█ THRESHOLDS 
  ✓ errors: rate<0.1 (0.00%)
  ✓ http_req_duration: p(95)<2000ms (actual: 25.42ms)

█ PERFORMANCE METRICS
  ✓ Requests/second: 54.79/s
  ✓ Average response time: 18.3ms
  ✓ Success rate: 100%
  ✓ Total requests: 16,471
  ✓ Data throughput: 56 kB/s received, 62 kB/s sent
```

### Running Tests

Choose the appropriate test command based on your needs:

```bash
make load-test           # Run complete test suite (all scenarios)
make load-test-sustained # Test steady load (1000 req/min, 5 min)
make load-test-spike     # Test traffic spikes (1000→1800→1000 req/min)
```

### Test Types

1. **Complete Suite** (`make load-test`)
   - Runs all test scenarios in sequence
   - Best for comprehensive performance validation
   - Total duration: ~7.5 minutes

2. **Sustained Load** (`make load-test-sustained`)
   - Constant rate: 17 RPS (1000+ per minute)
   - Duration: 5 minutes
   - Best for: Validating steady-state performance
   - Actual p95 response time: 25.42ms

3. **Spike Test** (`make load-test-spike`)
   - Tests system's ability to handle traffic spikes
   - Duration: 2.5 minutes
   - Pattern:
     ```
     1min: Normal load (17 RPS)
     30s:  High load (30 RPS)
     1min: Recovery period
     ```
   - Best for: Testing system recovery & stability