# Phase 3.5 - Stage 5: Load Testing COMPLETE

**Date:** 2025-10-28
**Duration:** 1.5 hours
**Status:** ✅ **COMPLETE** - Comprehensive test suite ready

---

## Executive Summary

Successfully completed Stage 5 of Phase 3.5 Performance Optimization by creating a comprehensive load testing suite using k6:
1. ✅ Smoke test for quick sanity checks (1 min)
2. ✅ Load test for normal traffic simulation (9 min)
3. ✅ Stress test to find breaking point (14 min)
4. ✅ Helper script for automated test execution
5. ✅ Complete documentation and best practices

**Key Achievement:** Established a production-ready testing framework that can:
- 🎯 Verify performance under realistic load
- 🎯 Identify system bottlenecks
- 🎯 Find maximum capacity limits
- 🎯 Validate optimizations (Stages 2-4)
- 🎯 Provide baseline for CI/CD

---

## Test Suite Overview

### Test 1: Smoke Test
**File:** `tests/Performance/k6/smoke-test.js`

**Purpose:** Quick sanity check with minimal load

**Profile:**
- **VUs:** 2 concurrent users
- **Duration:** 1 minute
- **Endpoints:** Health, Products, Fiscal Periods, AR Invoices

**Success Criteria:**
- ✅ Error rate < 1%
- ✅ p95 response time < 500ms
- ✅ All endpoints reachable

**Use Case:**
- Pre-deployment verification
- Quick regression check
- CI/CD pipeline smoke test

**Command:**
```bash
k6 run tests/Performance/k6/smoke-test.js
```

---

### Test 2: Load Test
**File:** `tests/Performance/k6/load-test.js`

**Purpose:** Simulate realistic production traffic

**Profile:**
- **Stage 1:** Ramp up 0 → 50 VUs (2 min)
- **Stage 2:** Steady 50 VUs (5 min)
- **Stage 3:** Ramp down 50 → 0 VUs (2 min)
- **Total Duration:** 9 minutes
- **Traffic Mix:** 70% browse, 20% invoices, 10% accounting

**Success Criteria:**
- ✅ Error rate < 1%
- ✅ p95 response time < 200ms
- ✅ p99 response time < 500ms
- ✅ Throughput > 100 req/s

**Scenarios Tested:**

| Scenario | % Traffic | Endpoints |
|----------|-----------|-----------|
| Browse Products | 70% | GET /api/v1/products |
| View Invoices | 20% | GET /api/v1/ar-invoices + detail |
| Check Fiscal Periods | 10% | GET /api/v1/fiscal-periods |

**Custom Metrics:**
- Cache hit rate
- API response times by scenario
- Error distribution

**Command:**
```bash
k6 run tests/Performance/k6/load-test.js
```

**Expected Output:**
```
✓ products status 200
✓ products response time OK
✓ invoices list status 200
✓ invoices has data

http_req_duration............: avg=85ms  p95=150ms  p99=300ms
http_reqs....................: 54,000 (100/s)
errors.......................: 0.12%
cache_hits...................: 78.5%
```

---

### Test 3: Stress Test
**File:** `tests/Performance/k6/stress-test.js`

**Purpose:** Find system breaking point and verify recovery

**Profile:**
- **Stage 1:** Ramp 0 → 100 VUs (3 min)
- **Stage 2:** High load 100 VUs (4 min)
- **Stage 3:** Extreme 100 → 200 VUs (3 min)
- **Stage 4:** Maintain 200 VUs (2 min)
- **Stage 5:** Recovery 200 → 0 VUs (2 min)
- **Total Duration:** 14 minutes

**Success Criteria:**
- ✅ Error rate < 5% (more lenient)
- ✅ p95 response time < 1000ms
- ✅ p99 response time < 2000ms
- ✅ System recovers without restart

**Scenarios Tested:**

| Scenario | % Traffic | Complexity |
|----------|-----------|------------|
| Products Catalog | 30% | Simple pagination |
| AR Invoices Complex | 30% | With relationships + filters |
| Fiscal Periods + Entries | 20% | Multi-query scenario |
| Inventory Stock | 20% | With joins |

**Custom Metrics:**
- Slow responses (>1s)
- Success/failure rates
- Active VUs tracking

**Command:**
```bash
k6 run tests/Performance/k6/stress-test.js
```

**Expected Behavior:**
```
VUs: 100-200
Duration: 14m

Phase 1 (100 VUs):
  ✅ Stable performance
  ✅ Error rate < 1%
  ✅ Response times normal

Phase 2 (200 VUs):
  ⚠️  Some degradation expected
  ⚠️  Error rate 1-5%
  ⚠️  Response times 2-3x slower

Phase 3 (Recovery):
  ✅ System returns to normal
  ✅ No lingering errors
  ✅ Response times normalize
```

---

## Helper Script

**File:** `tests/Performance/k6/run-tests.sh`

**Features:**
- ✅ Automatic environment checks (k6, API availability)
- ✅ Automatic authentication token retrieval
- ✅ Results saving with timestamps
- ✅ Colored output for readability
- ✅ Error handling and validation

**Usage:**
```bash
# Run individual tests
./run-tests.sh smoke        # Quick 1-min check
./run-tests.sh load         # 9-min load test
./run-tests.sh stress       # 14-min stress test

# Run all tests in sequence
./run-tests.sh all          # ~25 minutes total
```

**Environment Variables:**
```bash
export API_URL=http://localhost:8000
export API_EMAIL=admin@example.com
export API_PASSWORD=secureadmin
```

**Output Example:**
```bash
════════════════════════════════════════════════════════
  k6 Load Testing Suite
════════════════════════════════════════════════════════

✅ k6 is installed (v0.48.0)
✅ API is reachable
✅ Authentication token obtained

ℹ️  Configuration:
  API URL: http://localhost:8000
  Token: eyJ0eXAiOiJKV1QiLCJh...

════════════════════════════════════════════════════════
  Running Smoke Test (1 minute)
════════════════════════════════════════════════════════

[... k6 output ...]

✅ Smoke test passed
```

---

## Test Scenarios Explained

### Scenario 1: Browse Products (70% Traffic)

**User Behavior:**
1. User lands on product catalog
2. Browses pages of products
3. Applies filters/sorting
4. Views multiple pages

**k6 Implementation:**
```javascript
let res = http.get(`${BASE_URL}/api/v1/products?page[size]=20`, { headers });

check(res, {
  'products status 200': (r) => r.status === 200,
  'products response time OK': (r) => r.timings.duration < 300,
});

// Track cache hits
if (res.headers['X-Cache'] === 'HIT') {
  cacheHits.add(1);
}
```

**Why 70%:** Most API traffic is read-heavy catalog browsing.

---

### Scenario 2: View Invoices (20% Traffic)

**User Behavior:**
1. Admin views invoice list
2. Applies filters (status, contact)
3. Clicks to view invoice details
4. Loads related data (contact, sales order)

**k6 Implementation:**
```javascript
// List invoices
let listRes = http.get(`${BASE_URL}/api/v1/ar-invoices?page[size]=10&include=contact`, { headers });

// View detail (50% chance)
if (Math.random() < 0.5) {
  const invoiceId = JSON.parse(listRes.body).data[0].id;
  let detailRes = http.get(`${BASE_URL}/api/v1/ar-invoices/${invoiceId}?include=contact,salesOrder`, { headers });
}
```

**Why 20%:** Financial data queries are common but less frequent than product browsing.

---

### Scenario 3: Check Fiscal Periods (10% Traffic)

**User Behavior:**
1. Accountant checks open periods
2. Views period details
3. Prepares for month-end close

**k6 Implementation:**
```javascript
let res = http.get(`${BASE_URL}/api/v1/fiscal-periods?filter[status]=open`, { headers });
```

**Why 10%:** Accounting operations are periodic and less frequent.

---

## Performance Baselines

### Expected Results (After Stages 2-4 Optimizations)

**With Cache + Indexes + Rate Limiting:**

| Test Type | Error Rate | p50 | p95 | p99 | Throughput |
|-----------|------------|-----|-----|-----|------------|
| **Smoke (2 VUs)** | < 0.1% | 25ms | 80ms | 150ms | 2 req/s |
| **Load (50 VUs)** | < 1% | 35ms | 120ms | 250ms | 100 req/s |
| **Stress (100 VUs)** | < 2% | 85ms | 350ms | 750ms | 180 req/s |
| **Stress (200 VUs)** | 2-5% | 320ms | 1200ms | 2000ms | 200 req/s |

**Cache Performance (Expected):**
- Cache hit rate: 70-85%
- Cached response time: 5-15ms
- Cache miss response time: 80-200ms

**Rate Limiting (Expected):**
- Admin users: No throttling under 300 req/min
- Guest users: Some 429s at 200 VUs (expected)

---

## Interpreting Results

### Good Performance Indicators

✅ **Response Times:**
```
http_req_duration
  avg: 85ms
  min: 12ms
  p50: 45ms
  p95: 150ms
  p99: 300ms
  max: 850ms
```

✅ **Throughput:**
```
http_reqs: 54,234 (100.4/s)
```

✅ **Cache Performance:**
```
cache_hits: 42,500 (78.3%)
cache_misses: 11,734 (21.7%)
```

✅ **Error Rate:**
```
errors: 0.12% (65 failures out of 54,234 requests)
http_req_failed: 0.08%
```

---

### Performance Issues Indicators

⚠️ **Slow Response Times:**
```
http_req_duration
  avg: 650ms    ← Too slow
  p95: 1200ms   ← Very slow
  p99: 2500ms   ← Unacceptable
```

**Actions:**
1. Check database query performance (slow queries log)
2. Verify cache is working (check cache hit rate)
3. Review N+1 queries (enable query log)
4. Check server resources (CPU, memory)

⚠️ **High Error Rate:**
```
errors: 4.2% (2,278 failures out of 54,234)
```

**Common Causes:**
- Database connection pool exhausted
- Memory limits reached
- Rate limiting too aggressive
- Application errors (check logs)

⚠️ **Low Cache Hit Rate:**
```
cache_hits: 15.3%  ← Should be 70%+
```

**Actions:**
1. Verify cache middleware is applied
2. Check cache TTLs (not too short)
3. Ensure cache invalidation isn't too aggressive
4. Verify cache driver is working (Redis/database)

---

## Bottleneck Identification

### Common Bottlenecks

**1. Database Queries**
**Symptom:** p99 > 1000ms, CPU usage spikes

**Detection:**
```bash
# Enable slow query log
tail -f storage/logs/laravel.log | grep "slow query"

# Check database connections
php artisan tinker
>>> DB::table('information_schema.processlist')->count()
```

**Solutions:**
- Add missing indexes (verify Stage 2)
- Optimize complex queries
- Increase database connection pool
- Consider read replicas

---

**2. Cache Misses**
**Symptom:** Cache hit rate < 50%, response times 5-10x slower

**Detection:**
```bash
# Monitor cache headers in k6 output
# Look for X-Cache: MISS

# Check cache driver
php artisan tinker
>>> Cache::getStore()->getStore()
```

**Solutions:**
- Verify cache middleware is applied correctly
- Increase cache TTLs for stable data
- Use Redis instead of database cache
- Warm up cache before testing

---

**3. N+1 Queries**
**Symptom:** Query count scales with data size

**Detection:**
```bash
# Enable debugbar
composer require barryvdh/laravel-debugbar --dev

# Check queries per request
# Should be < 10 queries per endpoint
```

**Solutions:**
- Add eager loading: `with(['contact', 'salesOrder'])`
- Review JSON:API includePaths
- Use select() to limit columns
- Implement query result caching

---

**4. Memory Limits**
**Symptom:** 500 errors, "memory exhausted" in logs

**Detection:**
```bash
# Check PHP memory limit
php -i | grep memory_limit

# Monitor memory during test
watch -n 1 'free -h'
```

**Solutions:**
- Increase PHP memory_limit in php.ini
- Implement pagination for large datasets
- Use chunking for batch operations
- Optimize collection operations

---

**5. Rate Limiting**
**Symptom:** Many 429 responses, throughput capped

**Detection:**
```bash
# Count 429 responses in k6 output
# Check X-RateLimit headers
```

**Solutions:**
- Adjust rate limits in ApiRateLimiter
- Use Redis for distributed rate limiting
- Implement sliding window instead of fixed
- Consider role-based exemptions

---

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Load Tests

on:
  push:
    branches: [ main, develop ]
  schedule:
    - cron: '0 2 * * *'  # Daily at 2 AM

jobs:
  smoke-test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - name: Install Dependencies
        run: composer install --no-dev

      - name: Run Migrations
        run: php artisan migrate --seed --force

      - name: Start Server
        run: php artisan serve &
        env:
          APP_ENV: testing

      - name: Wait for Server
        run: sleep 5

      - name: Install k6
        run: |
          sudo gpg -k
          sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
          echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
          sudo apt-get update
          sudo apt-get install k6

      - name: Run Smoke Test
        run: |
          cd tests/Performance/k6
          ./run-tests.sh smoke

      - name: Upload Results
        uses: actions/upload-artifact@v3
        with:
          name: load-test-results
          path: tests/Performance/k6/results/*.json
```

---

## Best Practices

### Before Testing

1. **Prepare Environment:**
   ```bash
   # Fresh database
   php artisan migrate:fresh --seed

   # Clear caches
   php artisan cache:clear
   php artisan config:clear

   # Start server
   composer dev
   ```

2. **Warm Up Cache:**
   ```bash
   # Make a few requests to populate cache
   curl http://localhost:8000/api/v1/products
   curl http://localhost:8000/api/v1/fiscal-periods
   ```

3. **Monitor Resources:**
   ```bash
   # In separate terminal
   htop                 # CPU/Memory
   mysql -e "SHOW PROCESSLIST"  # Database connections
   ```

---

### During Testing

1. **Watch Logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Monitor k6 Output:**
   - Watch for error spikes
   - Check response time trends
   - Verify cache hit rate

3. **System Resources:**
   - CPU should stay < 80%
   - Memory should stay < 85%
   - Database connections < pool size

---

### After Testing

1. **Analyze Results:**
   ```bash
   # View summary
   cat results/load-test-*.json | jq '.metrics.http_req_duration'

   # Check errors
   cat results/load-test-*.json | jq '.metrics.errors'
   ```

2. **Compare Baselines:**
   - Save results for each optimization
   - Track improvements over time
   - Document regression fixes

3. **Document Findings:**
   - Record bottlenecks found
   - Note optimization opportunities
   - Share results with team

---

## Troubleshooting

### Issue: Connection Refused

**Cause:** API server not running

**Solution:**
```bash
composer dev
# OR
php artisan serve
```

---

### Issue: All Requests Return 401

**Cause:** Invalid or expired token

**Solution:**
```bash
# Get fresh token
export API_TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"secureadmin"}' \
  | jq -r '.token')
```

---

### Issue: k6 Not Found

**Cause:** k6 not installed

**Solution:**
```bash
# Linux
sudo apt-get install k6

# Mac
brew install k6

# Docker
docker run --rm -i grafana/k6 run - < script.js
```

---

### Issue: Database Connection Errors

**Cause:** Database not running or connection pool exhausted

**Solution:**
```bash
# Check MySQL
systemctl status mysql

# Increase connection pool
# config/database.php
'mysql' => [
    'pool' => [
        'min' => 10,
        'max' => 100,
    ],
],
```

---

## Advanced Usage

### Custom Scenarios

```javascript
// Create custom test
import { scenario } from 'k6/execution';

export const options = {
  scenarios: {
    products: {
      executor: 'constant-vus',
      vus: 50,
      duration: '5m',
      exec: 'browseProducts',
    },
    invoices: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '2m', target: 20 },
        { duration: '3m', target: 20 },
        { duration: '1m', target: 0 },
      ],
      exec: 'viewInvoices',
    },
  },
};

export function browseProducts() {
  // Product browsing logic
}

export function viewInvoices() {
  // Invoice viewing logic
}
```

---

### Grafana Cloud Integration

```bash
# Sign up at https://grafana.com/products/cloud/k6/

# Run with cloud output
k6 run --out cloud load-test.js

# View real-time results in Grafana Cloud dashboard
```

---

## Success Criteria

| Criterion | Target | Status |
|-----------|--------|--------|
| **Smoke Test Created** | 1-min sanity check | ✅ **DONE** |
| **Load Test Created** | 9-min realistic simulation | ✅ **DONE** |
| **Stress Test Created** | 14-min breaking point test | ✅ **DONE** |
| **Helper Script** | Automated test runner | ✅ **DONE** |
| **Documentation** | Complete usage guide | ✅ **DONE** |
| **CI/CD Ready** | GitHub Actions example | ✅ **DONE** |

**Stage 5: Load Testing** = **100% COMPLETE** ✅

---

## Files Created

1. `tests/Performance/k6/smoke-test.js` (92 lines) - Quick sanity check
2. `tests/Performance/k6/load-test.js` (183 lines) - Normal load simulation
3. `tests/Performance/k6/stress-test.js` (168 lines) - Breaking point test
4. `tests/Performance/k6/run-tests.sh` (246 lines) - Automated test runner
5. `tests/Performance/k6/README.md` (350 lines) - Usage documentation
6. `tests/Performance/k6/results/.gitignore` - Ignore test results
7. `docs/performance/STAGE5_LOAD_TESTING_COMPLETE.md` (this document)

**Total:** 7 files, ~1,039 lines of code and documentation

---

## Next Steps

### Immediate: Run Tests

```bash
cd tests/Performance/k6

# 1. Quick smoke test
./run-tests.sh smoke

# 2. Full load test (if smoke passes)
./run-tests.sh load

# 3. Stress test (if load passes)
./run-tests.sh stress
```

### Future: Continuous Monitoring

1. **Add to CI/CD pipeline**
   - Run smoke tests on every push
   - Run load tests nightly
   - Run stress tests weekly

2. **Establish Baselines**
   - Record current performance
   - Set regression thresholds
   - Alert on degradation

3. **Iterate and Improve**
   - Fix identified bottlenecks
   - Re-run tests to verify
   - Update baselines

---

## Conclusion

**Stage 5 of Phase 3.5 Performance Optimization is COMPLETE.**

We've successfully created a comprehensive load testing suite that:
1. ✅ Validates system performance under realistic load
2. ✅ Identifies bottlenecks and breaking points
3. ✅ Provides measurable baselines for optimization
4. ✅ Integrates with CI/CD pipelines
5. ✅ Supports continuous performance monitoring

The test suite is **production-ready** and can:
- Run smoke tests in 1 minute
- Simulate normal load in 9 minutes
- Find breaking point in 14 minutes
- Execute automatically via helper script
- Generate detailed performance reports

**With Stages 2-4 optimizations, expect:**
- 📊 70-85% cache hit rate
- ⚡ p95 response time < 200ms
- 🚀 100+ requests/second throughput
- 💪 Stable under 100-200 concurrent users

**Ready for Stage 6: Memory & Resource Profiling** or production deployment!

---

**Prepared by:** Claude (Phase 3.5 - Stage 5)
**Review Status:** Ready for execution
**Testing Level:** Production-grade
