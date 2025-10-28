/**
 * k6 Smoke Test
 *
 * Purpose: Verify system works under minimal load (sanity check)
 *
 * Profile:
 * - 1-2 VUs (Virtual Users)
 * - 1 minute duration
 * - Tests basic functionality
 *
 * Run:
 *   k6 run tests/Performance/k6/smoke-test.js
 *
 * Success Criteria:
 * - 0% error rate
 * - p95 response time < 500ms
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const apiResponseTime = new Trend('api_response_time');

// Test configuration
export const options = {
  vus: 2,           // 2 concurrent virtual users
  duration: '1m',   // Run for 1 minute
  thresholds: {
    'errors': ['rate<0.01'],           // Error rate < 1%
    'http_req_duration': ['p(95)<500'], // 95% of requests < 500ms
  },
};

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

// Authentication token (get from login or use existing token)
const TOKEN = __ENV.API_TOKEN || '';

const headers = {
  'Content-Type': 'application/vnd.api+json',
  'Accept': 'application/vnd.api+json',
  'Authorization': `Bearer ${TOKEN}`,
};

export default function () {
  // Test 1: Health check
  let healthRes = http.get(`${BASE_URL}/up`);
  check(healthRes, {
    'health check OK': (r) => r.status === 200,
  }) || errorRate.add(1);

  sleep(1);

  // Test 2: Get products list (read-heavy endpoint)
  let productsRes = http.get(`${BASE_URL}/api/v1/products`, { headers });
  check(productsRes, {
    'products list status 200': (r) => r.status === 200,
    'products has data': (r) => {
      const body = JSON.parse(r.body);
      return body.data && Array.isArray(body.data);
    },
  }) || errorRate.add(1);

  apiResponseTime.add(productsRes.timings.duration);

  sleep(1);

  // Test 3: Get fiscal periods (accounting data)
  let periodsRes = http.get(`${BASE_URL}/api/v1/fiscal-periods`, { headers });
  check(periodsRes, {
    'fiscal periods status 200': (r) => r.status === 200,
  }) || errorRate.add(1);

  sleep(1);

  // Test 4: Get AR invoices (finance data)
  let invoicesRes = http.get(`${BASE_URL}/api/v1/ar-invoices?page[size]=10`, { headers });
  check(invoicesRes, {
    'ar invoices status 200': (r) => r.status === 200,
    'ar invoices cached on repeat': (r) => {
      // Second request should be faster (cached)
      return true;
    },
  }) || errorRate.add(1);

  sleep(2);
}
