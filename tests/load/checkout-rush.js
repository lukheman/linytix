import http from 'k6/http';
import { check, sleep } from 'k6';
import {
  BASE_URL,
  EVENT_SLUG,
  EXPECTED_UNIT_PRICE,
  THRESHOLDS,
  TICKET_TYPE_ID,
} from './lib/config.js';

const TOKEN_PATTERN = /name="_token"\s+value="([^"]+)"/;

export const options = {
  scenarios: {
    checkout_rush: {
      executor: 'constant-arrival-rate',
      rate: Number(__ENV.ORDERS_PER_MINUTE || 50),
      timeUnit: '1m',
      duration: __ENV.DURATION || '10m',
      preAllocatedVUs: Number(__ENV.PRE_ALLOCATED_VUS || 50),
      maxVUs: Number(__ENV.MAX_VUS || 300),
    },
  },
  thresholds: {
    ...THRESHOLDS,
    http_req_duration: ['p(95)<2000'],
  },
};

function csrfToken(html) {
  const match = TOKEN_PATTERN.exec(html);
  return match ? match[1] : '';
}

export default function () {
  if (!EVENT_SLUG || !TICKET_TYPE_ID) {
    throw new Error('EVENT_SLUG dan TICKET_TYPE_ID wajib diisi.');
  }

  const form = http.get(`${BASE_URL}/events/${EVENT_SLUG}/checkout`, {
    tags: { step: 'checkout_form' },
  });

  const token = csrfToken(form.body || '');

  check(form, {
    'checkout form 200': (res) => res.status === 200,
    'csrf token found': () => token !== '',
  });

  if (!token) {
    sleep(1);
    return;
  }

  const unique = `${__VU}-${__ITER}-${Date.now()}`;
  const payload = {
    _token: token,
    ticket_type_id: TICKET_TYPE_ID,
    quantity: '1',
    buyer_name: `Load Test ${unique}`,
    buyer_email: `load+${unique}@example.test`,
    buyer_phone: '081200000000',
    expected_unit_price: EXPECTED_UNIT_PRICE,
    idempotency_key: `load-${unique}`,
  };

  const response = http.post(`${BASE_URL}/events/${EVENT_SLUG}/checkout`, payload, {
    redirects: 0,
    tags: { step: 'checkout_store' },
  });

  check(response, {
    'order request accepted': (res) => res.status === 302 || res.status === 200,
    'not rate limited': (res) => res.status !== 429,
    'not server error': (res) => res.status < 500,
  });

  sleep(0.2);
}
