import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

// Test configuration
export const options = {
    scenarios: {
        // Test sustained load (1000 requests per minute)
        sustained_load: {
            executor: 'constant-arrival-rate',
            rate: 17, // ~1000 per minute
            timeUnit: '1s',
            duration: '5m',
            preAllocatedVUs: 20,
            maxVUs: 100,
        },
        // Test spike handling
        spike_test: {
            executor: 'ramping-arrival-rate',
            startRate: 17,
            timeUnit: '1s',
            stages: [
                { duration: '1m', target: 17 },   // Normal load
                { duration: '30s', target: 30 },  // Spike to 1800/minute
                { duration: '1m', target: 17 },   // Back to normal
            ],
            preAllocatedVUs: 20,
            maxVUs: 100,
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<2000'], // 95% of requests should be below 2s
        errors: ['rate<0.1'],              // Error rate should be below 10%
    },
};

const BASE_URL = 'http://localhost:8080';
let token = '';

// Helper function to generate random lead data
function generateLead() {
    const timestamp = new Date().toISOString();
    return {
        firstName: `Test${Math.random().toString(36).substring(7)}`,
        lastName: `User${Math.random().toString(36).substring(7)}`,
        email: `test${Math.random().toString(36).substring(7)}@example.com`,
        phone: `+1${Math.floor(Math.random() * 10000000000)}`,
        dateOfBirth: "1990-01-01",
        additionalData: {
            source: "load_test",
            timestamp: timestamp,
            comment: `Load test comment ${timestamp}`
        }
    };
}

// Setup function to get JWT token
export function setup() {
    const loginRes = http.post(`${BASE_URL}/api/login_check`, JSON.stringify({
        email: 'test@example.com',
        password: 'password123'
    }), {
        headers: { 'Content-Type': 'application/json' }
    });

    check(loginRes, {
        'login successful': (r) => r.status === 200,
    });

    return { token: loginRes.json('token') };
}

// Main test function
export default function(data) {
    const headers = {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${data.token}`,
    };

    // Create lead
    const createRes = http.post(
        `${BASE_URL}/api/leads`,
        JSON.stringify(generateLead()),
        { headers }
    );

    check(createRes, {
        'lead created successfully': (r) => r.status === 201,
    }) || errorRate.add(1);

    // List leads (to test read performance)
    const listRes = http.get(
        `${BASE_URL}/api/leads`,
        { headers }
    );

    check(listRes, {
        'leads listed successfully': (r) => r.status === 200,
    }) || errorRate.add(1);

    sleep(0.1); // Small delay between iterations
} 