-- Jalankan setelah skenario checkout-rush.
-- Ganti :event_id dengan UUID event yang dipakai load test.
-- Tidak boleh ada baris berstatus OVERSOLD.

SELECT
    tt.id,
    tt.name,
    tt.quota,
    tt.sold_count,
    (tt.quota - tt.sold_count) AS remaining,
    CASE
        WHEN tt.sold_count > tt.quota THEN 'OVERSOLD'
        ELSE 'OK'
    END AS status
FROM ticket_types tt
WHERE tt.event_id = :event_id
ORDER BY tt.price;

-- Jumlah order yang berhasil dibuat selama load test.
SELECT COUNT(*) AS total_orders
FROM orders
WHERE event_id = :event_id;

-- Pastikan tidak ada tiket terjual melebihi order lunas + pending yang valid.
SELECT
    tt.id,
    tt.name,
    tt.quota,
    tt.sold_count,
    COALESCE(SUM(oi.quantity), 0) AS ordered_quantity
FROM ticket_types tt
LEFT JOIN order_items oi ON oi.ticket_type_id = tt.id
LEFT JOIN orders o ON o.id = oi.order_id
    AND o.status IN ('pending', 'paid')
WHERE tt.event_id = :event_id
GROUP BY tt.id, tt.name, tt.quota, tt.sold_count;
