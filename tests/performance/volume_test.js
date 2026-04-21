import { pacedSleep, runVolumeJourney } from './common.js';

export const options = {
  stages: [
    { duration: '2m', target: 20 },
    { duration: '2m', target: 40 },
    { duration: '1m', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'],
    http_req_failed: ['rate<0.05'],
  },
};

export default function () {
  runVolumeJourney();
  pacedSleep(1);
}
