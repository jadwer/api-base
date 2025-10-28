/**
 * k6 Load Test
 *
 * Purpose: Test system under normal/expected load
 *
 * Profile:
 * - Ramp up: 0 → 50 VUs over 2 minutes
 * - Steady: 50 VUs for 5 minutes
 * - Ramp down: 50 → 0 VUs over 2 minutes
 * - Total: 9 minutes
 *
 * Run:
 *   k6 run tests/Performance/k6/load-test.js
 *
 * Success Criteria:
 * - Error rate < 1%
 * - p95 response time < 200ms
 * - p99 response time < 500ms
 */

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const apiResponseTime = new Trend('api_response_time');
const cacheHits = new Counter('cache_hits');
const cacheMisses = new Counter('cache_misses');

// Test configuration
export const options = {
  stages: [
    { duration: '2m', target: 50 },  // Ramp up to 50 users
    { duration: '5m', target: 50 },  // Stay at 50 users
    { duration: '2m', target: 0 },   // Ramp down to 0 users
  ],
  thresholds: {
    'errors': ['rate<0.01'],           // < 1% errors
    'http_req_duration': ['p(95)<200', 'p(99)<500'], // 95% < 200ms, 99% < 500ms
    'http_reqs': ['rate>100'],         // > 100 requests/second
  },
};

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';
const TOKEN = __ENV.API_TOKEN || '';

const headers = {
  'Content-Type': 'application/vnd.api+json',
  'Accept': 'application/vnd.api+json',
  'Authorization': `Bearer ${TOKEN}`,
};

export default function () {
  // Scenario 1: Browse products (70% of traffic)
  if (Math.random() < 0.7) {
    group('Browse Products', function () {
      let res = http.get(`${BASE_URL}/api/v1/products?page[size]=20`, { headers });

      check(res, {
        'products status 200': (r) => r.status === 200,
        'products response time OK': (r) => r.timings.duration < 300,
      }) || errorRate.add(1);

      // Track cache performance
      if (res.headers['X-Cache'] === 'HIT') {
        cacheHits.add(1);
      } else {
        cacheMisses.add(1);
      }

      apiResponseTime.add(res.timings.duration);
    });
  }

  // Scenario 2: View invoices (20% of traffic)
  else if (Math.random() < 0.9) {
    group('View Invoices', function () {
      // List invoices
      let listRes = http.get(`${BASE_URL}/api/v1/ar-invoices?page[size]=10&include=contact`, { headers });

      check(listRes, {
        'invoices list status 200': (r) => r.status === 200,
        'invoices has data': (r) => {
          try {
            const body = JSON.parse(r.body);
            return body.data && Array.isArray(body.data);
          } catch (e) {
            return false;
          }
        },
      }) || errorRate.add(1);

      apiResponseTime.add(listRes.timings.duration);

      // View single invoice (50% chance)
      if (Math.random() < 0.5) {
        try {
          const invoices = JSON.parse(listRes.body).data;
          if (invoices.length > 0) {
            const invoiceId = invoices[0].id;
            let detailRes = http.get(`${BASE_URL}/api/v1/ar-invoices/${invoiceId}?include=contact,salesOrder`, { headers });

            check(detailRes, {
              'invoice detail status 200': (r) => r.status === 200,
            }) || errorRate.add(1);

            apiResponseTime.add(detailRes.timings.duration);
          }
        } catch (e) {
          errorRate.add(1);
        }
      }
    });
  }

  // Scenario 3: Check fiscal periods (10% of traffic)
  else {
    group('Check Fiscal Periods', function () {
      let res = http.get(`${BASE_URL}/api/v1/fiscal-periods?filter[status]=open`, { headers });

      check(res, {
        'periods status 200': (r) => r.status === 200,
      }) || errorRate.add(1);

      apiResponseTime.add(res.timings.duration);
    });
  }

  // Think time (simulate user reading/processing)
  sleep(Math.random() * 3 + 1); // Random sleep 1-4 seconds
}

export function handleSummary(data) {
  return {
    'stdout': textSummary(data, { indent: ' ', enableColors: true }),
    'results/load-test-results.json': JSON.stringify(data),
  };
}

function textSummary(data, options) {
  const indent = options.indent || '';
  const enableColors = options.enableColors || false;

  let summary = '\n';
  summary += indent + '═══════════════════════════════════════════════════\n';
  summary += indent + '  LOAD TEST RESULTS\n';
  summary += indent + '═══════════════════════════════════════════════════\n\n';

  // HTTP metrics
  const httpReqs = data.metrics.http_reqs;
  const httpDuration = data.metrics.http_req_duration;
  const errors = data.metrics.errors;

  summary += indent + 'HTTP Requests:\n';
  summary += indent + `  Total: ${httpReqs.values.count}\n`;
  summary += indent + `  Rate: ${httpReqs.values.rate.toFixed(2)} req/s\n\n`;

  summary += indent + 'Response Times:\n';
  summary += indent + `  Avg: ${httpDuration.values.avg.toFixed(2)}ms\n`;
  summary += indent + `  Min: ${httpDuration.values.min.toFixed(2)}ms\n`;
  summary += indent + `  Max: ${httpDuration.values.max.toFixed(2)}ms\n`;
  summary += indent + `  p50: ${httpDuration.values['p(50)'].toFixed(2)}ms\n`;
  summary += indent + `  p95: ${httpDuration.values['p(95)'].toFixed(2)}ms\n`;
  summary += indent + `  p99: ${httpDuration.values['p(99)'].toFixed(2)}ms\n\n`;

  summary += indent + 'Errors:\n';
  summary += indent + `  Rate: ${(errors.values.rate * 100).toFixed(2)}%\n\n`;

  // Cache metrics
  if (data.metrics.cache_hits && data.metrics.cache_misses) {
    const hits = data.metrics.cache_hits.values.count;
    const misses = data.metrics.cache_misses.values.count;
    const total = hits + misses;
    const hitRate = total > 0 ? (hits / total * 100) : 0;

    summary += indent + 'Cache Performance:\n';
    summary += indent + `  Hits: ${hits}\n`;
    summary += indent + `  Misses: ${misses}\n`;
    summary += indent + `  Hit Rate: ${hitRate.toFixed(2)}%\n\n`;
  }

  summary += indent + '═══════════════════════════════════════════════════\n';

  return summary;
}
