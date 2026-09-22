<?php 	

require_once 'core.php';

$orderId = (int)($_POST['orderId'] ?? 0);

$valid = array('order' => array(), 'order_item' => array());

$sql = "SELECT orders.id, orders.orderDate, orders.clientName, orders.clientContact, orders.subTotal, orders.gstn, orders.totalAmount, orders.discount, orders.grandTotalValue, orders.paid, orders.dueValue, orders.paymentType, orders.paymentStatus FROM orders 	
	WHERE orders.id = {$orderId}";

$result = $connect->query($sql);
$data = $result->fetch_row();
$valid['order'] = $data;


$connect->close();

echo json_encode($valid);