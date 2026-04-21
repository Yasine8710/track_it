import { pacedSleep, runCoreUserJourney } from './common.js';

export const options = {
  stages: [
    { duration: '10m', target: 10 }, // 10 users for 10 minutes
    { duration: '1m', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<800'],
    http_req_failed: ['rate<0.01'],
  },
};

export default function () {
  runCoreUserJourney();
  pacedSleep(1);
}
