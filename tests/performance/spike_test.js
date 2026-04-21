import { pacedSleep, runCoreUserJourney } from './common.js';

export const options = {
  stages: [
    { duration: '30s', target: 10 },
    { duration: '30s', target: 10 },
    { duration: '20s', target: 180 },
    { duration: '1m', target: 180 },
    { duration: '20s', target: 10 },
    { duration: '30s', target: 10 },
    { duration: '20s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<1200'],
    http_req_failed: ['rate<0.03'],
  },
};

export default function () {
  runCoreUserJourney();
  pacedSleep(0.5);
}
