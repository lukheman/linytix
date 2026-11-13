import http from 'k6/http';
import { check } from 'k6';
import {
  BASE_URL,
  EVENT_ID,
  JSON_HEADERS,
  STAFF_EMAIL,
  STAFF_PASSWORD,
  THRESHOLDS,
  TICKET_CODES,
} from './lib/config.js';

const TOKEN_PATTERN = /name="_token"\s+value="([^"]+)"/;

export const options = {
  scenarios: {
    gate_scan: {
      executor: 'constant-arrival-rate',
      rate: Number(__ENV.SCANS_PER_SECOND || 20),
      timeUnit: '1s',
      duration: __ENV.DURATION || '5m',
      preAllocatedVUs: Number(__ENV.PRE_ALLOCATED_VUS || 50),
      maxVUs: Number(__ENV.MAX_VUS || 200),
    },
  },
  thresholds: THRESHOLDS,
};

function csrfToken(html) {
  const match = TOKEN_PATTERN.exec(html);
  return match ? match[1] : '';
}

export function setup() {
  if (!STAFF_EMAIL || !STAFF_PASSWORD) {
    throw new Error('STAFF_EMAIL dan STAFF_PASSWORD wajib diisi untuk load test scan.');
  }

  const loginPage = http.get(`${BASE_URL}/login`);
  const token = csrfToken(loginPage.body || '');

  if (!token) {
    throw new Error('CSRF token halaman login tidak ditemukan.');
  }

  const login = http.post(
    `${BASE_URL}/login`,
    { _token: token, email: STAFF_EMAIL, password: STAFF_PASSWORD },
    { redirects: 0 },
  );

  if (login.status !== 302) {
    throw new Error(`Login staff gagal dengan status ${login.status}.`);
  }

  return { cookies: login.cookies, csrf: token };
}

export default function (data) {
  if (!EVENT_ID) {
    throw new Error('EVENT_ID wajib diisi.');
  }

  Object.entries(data.cookies || {}).forEach(([name, cookies]) => {
    const cookie = cookies[0];
    if (cookie) {
      http.cookieJar().set(BASE_URL, name, cookie.value);
    }
  });

  const code = TICKET_CODES.length > 0
    ? TICKET_CODES[Math.floor(Math.random() * TICKET_CODES.length)]
    : `TIX-INVALID-${__VU}-${__ITER}`;

  const response = http.post(
    `${BASE_URL}/scan/${EVENT_ID}`,
    JSON.stringify({ ticket_code: code }),
    {
      headers: { ...JSON_HEADERS, 'X-CSRF-TOKEN': data.csrf },
      redirects: 0,
      tags: { step: 'scan_store' },
    },
  );

  check(response, {
    'scan endpoint 200': (res) => res.status === 200,
    'not forbidden': (res) => res.status !== 403,
    'not rate limited': (res) => res.status !== 429,
    'result is expected': (res) => {
      try {
        return ['success', 'duplicate', 'invalid'].includes(res.json('result'));
      } catch (error) {
        return false;
      }
    },
  });
}
