import { check, sleep } from 'k6';
import { runCoreUserJourney, runVolumeJourney, pacedSleep } from './common.js';

export const options = {
  scenarios: {
    spike: {
      executor: 'ramping-arrival-rate',
      startRate: 10,
      timeUnit: '1s',
      preAllocatedVUs: 200,
      stages: [
        { target: 10, duration: '30s' },
        { target: 10, duration: '30s' },
        { target: 180, duration: '20s' },
        { target: 180, duration: '1m' },
        { target: 10, duration: '20s' },
        { target: 10, duration: '30s' },
        { target: 0, duration: '20s' },
      ],
      exec: 'spike',
    },
    endurance: {
      executor: 'constant-vus',
      vus: 10,
      duration: '10m',
      exec: 'endurance',
    },
    scalability: {
      executor: 'ramping-vus',
      stages: [
        { duration: '1m', target: 10 },
        { duration: '1m', target: 50 },
        { duration: '1m', target: 100 },
        { duration: '1m', target: 200 },
        { duration: '1m', target: 0 },
      ],
      exec: 'scalability',
    },
    volume: {
      executor: 'ramping-vus',
      stages: [
        { duration: '2m', target: 20 },
        { duration: '2m', target: 40 },
        { duration: '1m', target: 0 },
      ],
      exec: 'volume',
    },
    stress: {
      executor: 'ramping-vus',
      stages: [
        { duration: '1m', target: 20 },
        { duration: '1m', target: 50 },
        { duration: '1m', target: 100 },
        { duration: '1m', target: 200 },
        { duration: '1m', target: 400 },
        { duration: '1m', target: 0 },
      ],
      exec: 'stress',
    },
  },
  thresholds: {
    http_req_duration: ['p(95)<3000'],
    http_req_failed: ['rate<0.10'],
  },
};

export function spike() {
  runCoreUserJourney();
  pacedSleep(0.5);
}

export function endurance() {
  runCoreUserJourney();
  pacedSleep(1);
}

export function scalability() {
  runCoreUserJourney();
  pacedSleep(0.5);
}

export function volume() {
  runVolumeJourney();
  pacedSleep(1);
}

export function stress() {
  runCoreUserJourney();
  pacedSleep(0.25);
}
