import http from 'k6/http';
import { check, sleep } from 'k6';
import { BASE_URL, EVENT_SLUG, THRESHOLDS } from './lib/config.js';

export const options = {
  scenarios: {
    public_browse: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '2m', target: 100 },
        { duration: '3m', target: 500 },
        { duration: '5m', target: 500 },
        { duration: '2m', target: 0 },
      ],
      gracefulRampDown: '30s',
    },
  },
  thresholds: THRESHOLDS,
};

export default function () {
  const paths = ['/', '/events'];

  if (EVENT_SLUG) {
    paths.push(`/events/${EVENT_SLUG}`);
  }

  const path = paths[Math.floor(Math.random() * paths.length)];
  const response = http.get(`${BASE_URL}${path}`, {
    tags: { page: path === '/' ? 'home' : path === '/events' ? 'events' : 'event_detail' },
  });

  check(response, {
    'status is 200': (res) => res.status === 200,
    'html returned': (res) => String(res.headers['Content-Type'] || '').includes('text/html'),
  });

  sleep(1 + Math.random() * 2);
}
