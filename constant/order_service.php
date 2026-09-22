<?php
function order_execute(mysqli $db, string $sql, array $values = []): mysqli_stmt {
    $stmt = $db->prepare($sql);
    if ($values) $stmt->bind_param(str_repeat('s', count($values)), ...$values);
    $stmt->execute();
    return $stmt;
}
function order_restore_stock(mysqli $db, int $id): void {
    $items = order_execute($db, 'SELECT productName, quantity FROM order_item WHERE lastid=?', [$id])->get_result();
    foreach ($items as $item) order_execute($db, 'UPDATE product SET quantity=quantity+? WHERE product_id=?', [$item['quantity'], $item['productName']]);
}
function order_save(mysqli $db, array $input, ?int $id = null): int {
    $products = $input['productName'] ?? [];
    if (!is_array($products) || !$products || count($products) > 100) throw new InvalidArgumentException('Seleccione medicamentos.');
    $date = (string)($input['orderDate'] ?? '');
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date || trim($input['clientName'] ?? '') === '') throw new InvalidArgumentException('Fecha o cliente inválido.');
    $db->begin_transaction();
    try {
        if ($id !== null) {
            $old = order_execute($db, 'SELECT id FROM orders WHERE id=? AND delete_status=0 FOR UPDATE', [$id])->get_result()->fetch_assoc();
            if (!$old) throw new InvalidArgumentException('Factura no encontrada.');
            order_restore_stock($db, $id);
            order_execute($db, 'DELETE FROM order_item WHERE lastid=?', [$id]);
        }
        $items = [];
        $subtotal = 0;
        foreach ($products as $i => $productId) {
            if ($productId === '') continue;
            $qty = filter_var($input['quantity'][$i] ?? null, FILTER_VALIDATE_INT);
            if ($qty === false || $qty < 1) throw new InvalidArgumentException('Cantidad inválida.');
            $product = order_execute($db, 'SELECT product_id, rate, quantity, expdate FROM product WHERE product_id=? AND active=1 AND status=1 FOR UPDATE', [(int)$productId])->get_result()->fetch_assoc();
            if (!$product || $product['quantity'] < $qty) throw new InvalidArgumentException('Existencias insuficientes.');
            if ($product['expdate'] < date('Y-m-d')) throw new InvalidArgumentException('No se pueden vender medicamentos vencidos.');
            $total = (int)round((float)$product['rate'] * 100) * $qty;
            $subtotal += $total;
            $items[] = [(int)$productId, $qty, $product['rate'], $total / 100];
            order_execute($db, 'UPDATE product SET quantity=quantity-? WHERE product_id=?', [$qty, (int)$productId]);
        }
        if (!$items) throw new InvalidArgumentException('Seleccione medicamentos.');
        $discount = filter_var($input['discount'] ?? 0, FILTER_VALIDATE_FLOAT);
        $paid = filter_var($input['paid'] ?? 0, FILTER_VALIDATE_FLOAT);
        if ($discount === false || $paid === false || $discount < 0 || $paid < 0) throw new InvalidArgumentException('Pago o descuento inválido.');
        // Preserve the original laboratory tax calculation (18%); not a fiscal invoice configuration.
        $tax = (int)round($subtotal * 0.18);
        $gross = $subtotal + $tax;
        $grand = $gross - (int)round($discount * 100);
        if ($grand < 0 || (int)round($paid * 100) > $grand) throw new InvalidArgumentException('Pago o descuento supera el importe.');
        $due = ($grand - (int)round($paid * 100)) / 100;
        $status = $due == 0 ? 1 : ($paid > 0 ? 2 : 3);
        $values = [$date, trim($input['clientName']), $input['clientContact'] ?? '', $subtotal/100, $gross/100, $discount, $grand/100, $paid, $due, (int)($input['paymentType'] ?? 2), $status, (int)($input['paymentPlace'] ?? 1), $tax/100];
        if ($id === null) {
            order_execute($db, 'INSERT INTO orders(orderDate,clientName,clientContact,subTotal,totalAmount,discount,grandTotalValue,paid,dueValue,paymentType,paymentStatus,paymentPlace,gstn,uno) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [...$values, $input['uno'] ?? '']);
            $id = $db->insert_id;
        } else {
            order_execute($db, 'UPDATE orders SET orderDate=?,clientName=?,clientContact=?,subTotal=?,totalAmount=?,discount=?,grandTotalValue=?,paid=?,dueValue=?,paymentType=?,paymentStatus=?,paymentPlace=?,gstn=? WHERE id=?', [...$values, $id]);
        }
        foreach ($items as $item) order_execute($db, 'INSERT INTO order_item(productName,quantity,rate,total,lastid,added_date) VALUES(?,?,?,?,?,?)', [...$item, $id, $date]);
        $db->commit();
        return $id;
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}
