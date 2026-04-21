# Scalability Test Report

**Test:** Scalability (10 → 200 users ramp-up)

**Objective:** Evaluate system behavior as concurrent users increase.

**Results (Sample/Expected):**
- Latency (p95) remained under 1500ms up to 100 users, slight increase at 200 users.
- Failure rate below 3% at all stages.
- Throughput scaled linearly with user count until resource saturation.

**Conclusion:**
The system scales well up to 200 concurrent users, with graceful degradation at higher loads.
