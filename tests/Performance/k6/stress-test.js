/**
 * k6 Stress Test
 *
 * Purpose: Find the breaking point of the system
 *
 * Profile:
 * - Ramp up: 0 → 100 VUs over 3 minutes
 * - High load: 100 VUs for 4 minutes
 * - Extreme: 100 → 200 VUs over 3 minutes
 * - Maintain: 200 VUs for 2 minutes
 * - Recovery: 200 → 0 VUs over 2 minutes
 * - Total: 14 minutes
 *
 * Run:
 *   k6 run tests/Performance/k6/stress-test.js
 *
 * Goals:
 * - Find maximum sustainable load
 * - Identify bottlenecks
 * - Test system recovery
 */

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend, Counter, Gauge } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const successRate = new Rate('success');
const apiResponseTime = new Trend('api_response_time');
const slowResponses = new Counter('slow_responses');
const activeVUs = new Gauge('active_vus');

export const options = {
  stages: [
    { duration: '3m', target: 100 },  // Ramp up to 100 VUs
    { duration: '4m', target: 100 },  // Stay at 100 VUs
    { duration: '3m', target: 200 },  // Ramp to 200 VUs (stress)
    { duration: '2m', target: 200 },  // Maintain stress
    { duration: '2m', target: 0 },    // Recovery
  ],
  thresholds: {
    // More lenient thresholds for stress test
    'errors': ['rate<0.05'],           // < 5% errors acceptable
    'http_req_duration': ['p(95)<1000', 'p(99)<2000'], // Higher limits
    'http_req_failed': ['rate<0.05'],  // < 5% failed requests
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
  activeVUs.add(__VU);

  // Mix of different endpoints to stress the system
  const scenario = Math.random();

  if (scenario < 0.3) {
    // Read-heavy: Products catalog
    group('Products Catalog', function () {
      const page = Math.floor(Math.random() * 5) + 1;
      let res = http.get(`${BASE_URL}/api/v1/products?page[number]=${page}&page[size]=25`, { headers });

      const success = check(res, {
        'products status OK': (r) => r.status === 200 || r.status === 429,
        'products response time': (r) => r.timings.duration < 2000,
      });

      if (success) {
        successRate.add(1);
      } else {
        errorRate.add(1);
      }

      if (res.timings.duration > 1000) {
        slowResponses.add(1);
      }

      apiResponseTime.add(res.timings.duration);
    });

  } else if (scenario < 0.6) {
    // Complex query: Invoices with relationships
    group('AR Invoices Complex', function () {
      let res = http.get(
        `${BASE_URL}/api/v1/ar-invoices?include=contact,salesOrder&filter[status]=unpaid&page[size]=15`,
        { headers }
      );

      const success = check(res, {
        'invoices status OK': (r) => r.status === 200 || r.status === 429,
      });

      if (success) {
        successRate.add(1);
      } else {
        errorRate.add(1);
      }

      if (res.timings.duration > 1000) {
        slowResponses.add(1);
      }

      apiResponseTime.add(res.timings.duration);
    });

  } else if (scenario < 0.8) {
    // Accounting data
    group('Fiscal Periods & Entries', function () {
      // Get fiscal periods
      let periodsRes = http.get(`${BASE_URL}/api/v1/fiscal-periods?filter[status]=open`, { headers });

      check(periodsRes, {
        'periods status OK': (r) => r.status === 200 || r.status === 429,
      }) || errorRate.add(1);

      // Get journal entries (if periods found)
      if (periodsRes.status === 200) {
        let entriesRes = http.get(`${BASE_URL}/api/v1/journal-entries?page[size]=10`, { headers });

        check(entriesRes, {
          'entries status OK': (r) => r.status === 200 || r.status === 429,
        }) || errorRate.add(1);

        apiResponseTime.add(entriesRes.timings.duration);
      }

      apiResponseTime.add(periodsRes.timings.duration);
    });

  } else {
    // Inventory & stock
    group('Inventory Stock', function () {
      let res = http.get(`${BASE_URL}/api/v1/stock?include=product,warehouse&page[size]=20`, { headers });

      const success = check(res, {
        'stock status OK': (r) => r.status === 200 || r.status === 429,
      });

      if (success) {
        successRate.add(1);
      } else {
        errorRate.add(1);
      }

      apiResponseTime.add(res.timings.duration);
    });
  }

  // Shorter sleep during stress test
  sleep(Math.random() * 2); // 0-2 seconds
}

export function handleSummary(data) {
  console.log('\n═══════════════════════════════════════════════════');
  console.log('  STRESS TEST RESULTS');
  console.log('═══════════════════════════════════════════════════\n');

  const httpReqs = data.metrics.http_reqs;
  const httpDuration = data.metrics.http_req_duration;
  const httpFailed = data.metrics.http_req_failed;
  const errors = data.metrics.errors;

  console.log('Performance Metrics:');
  console.log(`  Total Requests: ${httpReqs.values.count}`);
  console.log(`  Requests/sec: ${httpReqs.values.rate.toFixed(2)}`);
  console.log(`  Failed Requests: ${(httpFailed.values.rate * 100).toFixed(2)}%`);
  console.log(`  Error Rate: ${(errors.values.rate * 100).toFixed(2)}%\n`);

  console.log('Response Times:');
  console.log(`  Average: ${httpDuration.values.avg.toFixed(2)}ms`);
  console.log(`  Min: ${httpDuration.values.min.toFixed(2)}ms`);
  console.log(`  Max: ${httpDuration.values.max.toFixed(2)}ms`);
  console.log(`  p50 (median): ${httpDuration.values['p(50)'].toFixed(2)}ms`);
  console.log(`  p90: ${httpDuration.values['p(90)'].toFixed(2)}ms`);
  console.log(`  p95: ${httpDuration.values['p(95)'].toFixed(2)}ms`);
  console.log(`  p99: ${httpDuration.values['p(99)'].toFixed(2)}ms`);
  console.log(`  p99.9: ${httpDuration.values['p(99.9)'].toFixed(2)}ms\n`);

  if (data.metrics.slow_responses) {
    console.log(`Slow Responses (>1s): ${data.metrics.slow_responses.values.count}\n`);
  }

  console.log('═══════════════════════════════════════════════════\n');

  // Determine if system is stable
  const isStable = httpFailed.values.rate < 0.05 && httpDuration.values['p(95)'] < 1000;

  if (isStable) {
    console.log('✅ System is STABLE under stress');
  } else {
    console.log('⚠️  System showed DEGRADATION under stress');
  }

  console.log('\nRecommendations:');
  if (httpDuration.values['p(95)'] > 500) {
    console.log('  • Consider adding more cache layers');
    console.log('  • Review database query performance');
  }
  if (httpFailed.values.rate > 0.01) {
    console.log('  • Investigate error patterns in logs');
    console.log('  • Check database connection pool size');
  }
  if (data.metrics.slow_responses && data.metrics.slow_responses.values.count > 100) {
    console.log('  • Optimize slow endpoints');
    console.log('  • Consider horizontal scaling');
  }

  console.log('\n');

  return {
    'stdout': '',
    'results/stress-test-results.json': JSON.stringify(data),
  };
}
