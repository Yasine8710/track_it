import http from 'k6/http';
import { check, group, sleep } from 'k6';

export const BASE_URL = __ENV.BASE_URL || 'http://localhost/track_it';

export function okStatus(status) {
  return status === 200 || status === 201 || status === 302;
}

export function runCoreUserJourney() {
  group('core_user_journey', () => {
    const indexRes = http.get(`${BASE_URL}/index.php`);
    check(indexRes, {
      'index status ok': (r) => okStatus(r.status),
    });

    const dashboardRes = http.get(`${BASE_URL}/dashboard.php`);
    check(dashboardRes, {
      'dashboard status ok': (r) => okStatus(r.status),
    });

    const dataRes = http.get(`${BASE_URL}/api/data.php`);
    check(dataRes, {
      'api data status ok': (r) => okStatus(r.status),
    });
  });
}

export function runVolumeJourney() {
  group('volume_payload_journey', () => {
    const largeDescription = 'PERF_VOLUME_' + 'X'.repeat(4096);
    const payload = JSON.stringify({
      amount: 1,
      description: largeDescription,
      type: 'inflow',
    });

    const postRes = http.post(`${BASE_URL}/api/transaction.php`, payload, {
      headers: { 'Content-Type': 'application/json' },
    });
    check(postRes, {
      'volume transaction status ok': (r) => okStatus(r.status),
    });

    const historyRes = http.get(`${BASE_URL}/api/history.php`);
    check(historyRes, {
      'history status ok': (r) => okStatus(r.status),
    });
  });
}

export function pacedSleep(seconds = 1) {
  sleep(seconds);
}
