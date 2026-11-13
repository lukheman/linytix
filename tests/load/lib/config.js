export const BASE_URL = (__ENV.BASE_URL || 'http://localhost:8000').replace(/\/$/, '');

export const EVENT_SLUG = __ENV.EVENT_SLUG || '';
export const EVENT_ID = __ENV.EVENT_ID || '';
export const TICKET_TYPE_ID = __ENV.TICKET_TYPE_ID || '';
export const EXPECTED_UNIT_PRICE = __ENV.EXPECTED_UNIT_PRICE || '0';

export const STAFF_EMAIL = __ENV.STAFF_EMAIL || '';
export const STAFF_PASSWORD = __ENV.STAFF_PASSWORD || '';
export const TICKET_CODES = (__ENV.TICKET_CODES || '')
  .split(',')
  .map((code) => code.trim())
  .filter(Boolean);

export const THRESHOLDS = {
  http_req_duration: ['p(95)<2000'],
  http_req_failed: ['rate<0.01'],
};

export const JSON_HEADERS = {
  'Content-Type': 'application/json',
  Accept: 'application/json',
};
