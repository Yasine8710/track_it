import { pacedSleep, runCoreUserJourney } from './common.js';

export const options = {
  stages: [
    { duration: '1m', target: 10 },
    { duration: '1m', target: 50 },
    { duration: '1m', target: 100 },
    { duration: '1m', target: 200 },
    { duration: '1m', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<1500'],
    http_req_failed: ['rate<0.03'],
  },
};

export default function () {
  runCoreUserJourney();
  pacedSleep(0.5);
}
