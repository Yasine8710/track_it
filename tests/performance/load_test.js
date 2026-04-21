import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 20 }, // Ramp-up to 20 users
    { duration: '1m', target: 20 },  // Stay at 20 users
    { duration: '30s', target: 0 },  // Ramp-down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'], // 95% of requests must complete below 500ms
    http_req_failed: ['rate<0.01'],    // Error rate should be less than 1%
  },
};

const BASE_URL = 'http://localhost/track_it';

export default function () {
  // 1. Visit Dashboard (Simulate GET)
  const dashboardRes = http.get(`${BASE_URL}/dashboard.php`);
  check(dashboardRes, {
    'dashboard status is 200': (r) => r.status === 200,
  });

  // 2. Simulate API Call (Get Transactions)
  const apiRes = http.get(`${BASE_URL}/api/transaction.php`);
  check(apiRes, {
    'api status is 200': (r) => r.status === 200,
  });

  sleep(1);
}
