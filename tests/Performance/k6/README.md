# k6 Load Testing Suite

Comprehensive load testing suite for the API using k6.

## Installation

### Option 1: Install k6 (Linux)
```bash
sudo gpg -k
sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
sudo apt-get update
sudo apt-get install k6
```

### Option 2: Docker (Recommended)
```bash
docker pull grafana/k6:latest
```

## Test Scenarios

### 1. Smoke Test (smoke-test.js)
**Purpose:** Quick sanity check with minimal load

**Profile:**
- 2 VUs
- 1 minute
- Tests basic functionality

**Run:**
```bash
# Local k6
k6 run smoke-test.js

# Docker
docker run --rm -v $(pwd):/scripts grafana/k6 run /scripts/smoke-test.js
```

### 2. Load Test (load-test.js)
**Purpose:** Test under normal expected load

**Profile:**
- Ramp: 0 → 50 VUs (2 min)
- Steady: 50 VUs (5 min)
- Down: 50 → 0 VUs (2 min)
- Total: 9 minutes

**Run:**
```bash
k6 run load-test.js
```

### 3. Stress Test (stress-test.js)
**Purpose:** Find breaking point

**Profile:**
- Ramp: 0 → 100 → 200 VUs
- Total: 14 minutes
- Finds maximum capacity

**Run:**
```bash
k6 run stress-test.js
```

## Configuration

### Environment Variables

Set these before running tests:

```bash
export API_URL=http://localhost:8000
export API_TOKEN=your_auth_token_here
```

**Getting an API Token:**
```bash
# Login and get token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"secureadmin"}' \
  | jq -r '.token'
```

## Running Tests

### Quick Start
```bash
# 1. Get auth token
export API_TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"secureadmin"}' \
  | jq -r '.token')

# 2. Run smoke test
k6 run smoke-test.js

# 3. Run load test
k6 run load-test.js

# 4. Run stress test (optional)
k6 run stress-test.js
```

### With Custom Configuration
```bash
# Override default settings
k6 run --vus 100 --duration 5m load-test.js

# Save results
k6 run --out json=results.json load-test.js
```

## Reading Results

### Key Metrics

**http_req_duration:**
- Average response time
- p50 (median), p95, p99 percentiles

**http_reqs:**
- Total requests
- Requests per second

**errors:**
- Error rate percentage

**cache_hits / cache_misses:**
- Cache performance

### Success Criteria

**Smoke Test:**
- ✅ Error rate < 1%
- ✅ p95 < 500ms

**Load Test:**
- ✅ Error rate < 1%
- ✅ p95 < 200ms
- ✅ p99 < 500ms
- ✅ Throughput > 100 req/s

**Stress Test:**
- ✅ Error rate < 5%
- ✅ p95 < 1000ms
- ✅ System recovers after stress

## Interpreting Results

### Good Performance
```
http_req_duration............: avg=85ms  p95=150ms  p99=300ms
errors....................... : 0.12%
http_reqs.................... : 125/s
cache_hits................... : 78.5%
```

### Performance Issues
```
http_req_duration............: avg=650ms  p95=1200ms  p99=2500ms
errors....................... : 4.2%
http_reqs.................... : 42/s
```

**Actions:**
1. Check database query performance
2. Verify cache is working
3. Review application logs
4. Check resource usage (CPU, memory)

## Common Issues

### Issue: All requests fail with 401
**Solution:** Invalid or expired token
```bash
# Get fresh token
export API_TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"secureadmin"}' \
  | jq -r '.token')
```

### Issue: Connection refused
**Solution:** Ensure API server is running
```bash
# Start Laravel server
php artisan serve

# Or
composer dev
```

### Issue: Rate limit errors (429)
**Solution:** This is expected during stress tests. Rate limiting is working correctly.

## Advanced Usage

### Run with Grafana Cloud
```bash
# Sign up at https://grafana.com/products/cloud/k6/
k6 run --out cloud load-test.js
```

### Custom Scenarios
```bash
# Create custom test
cp load-test.js my-custom-test.js
# Edit my-custom-test.js
k6 run my-custom-test.js
```

## Results Directory

Test results are saved to:
```
results/
  ├── load-test-results.json
  ├── stress-test-results.json
  └── smoke-test-results.json
```

## CI/CD Integration

### GitHub Actions Example
```yaml
name: Load Tests
on: [push]
jobs:
  load-test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run k6 smoke test
        uses: grafana/k6-action@v0.3.0
        with:
          filename: tests/Performance/k6/smoke-test.js
          flags: --out json=results.json
```

## Best Practices

1. **Start Small:** Always run smoke test first
2. **Gradual Increase:** Progress from load → stress
3. **Monitor Resources:** Watch CPU, memory, DB during tests
4. **Use Realistic Data:** Test with production-like data volumes
5. **Cache Warm-up:** Run light traffic before major tests
6. **Document Findings:** Keep results for comparison

## Next Steps

After running tests:
1. Analyze results in `results/*.json`
2. Identify bottlenecks
3. Optimize based on findings
4. Re-run tests to verify improvements
5. Set performance baselines for CI/CD

## Support

For k6 documentation: https://k6.io/docs/
For issues: Check application logs in `storage/logs/`
