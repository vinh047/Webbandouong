<?php
session_start(); // Start session to retrieve cart data
include '../includes/DBConnect.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_items'])) {
  $selectedItems = $_POST['selected_items'];
  $cartItems = $_SESSION['cartItems'] ?? [];

  // Filter cart items to only include selected items
  $filteredCartItems = array_filter($cartItems, function ($item) use ($selectedItems) {
    return in_array($item['cart_detail_id'], $selectedItems);
  });

  $_SESSION['filteredCartItems'] = $filteredCartItems;
  $_SESSION['grandTotal'] = array_reduce($filteredCartItems, function ($total, $item) {
    return $total + $item['total'];
  }, 0);
} else {
  echo '<p>Không tìm thấy thông tin đơn hàng. Vui lòng quay lại giỏ hàng.</p>';
  exit;
}

$user_id = $_SESSION['user_id'];
$db = DBConnect::getInstance();

$user = $db->selectOne('SELECT * FROM users WHERE user_id = ?', [$user_id]);

$cartItems = $_SESSION['filteredCartItems'] ?? [];
$grandTotal = $_SESSION['grandTotal'] ?? 0;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Thanh toán đơn hàng</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link href="../assets/css/checkOut.css" rel="stylesheet">
</head>

<body>
  <div class="container">
    <div class="row">
      <div class="col-8 bg-white">
        <div class="payment-card">
          <h2 class="mb-4 text-center">Chi tiết đơn hàng &amp; Thông tin thanh toán</h2>

          <!-- 1. Bảng chi tiết đơn hàng -->
          <div class="mb-5">
            <h4 class="mb-3">Chi tiết đơn hàng</h4>
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>Hình ảnh</th>
                  <th>Sản phẩm</th>
                  <th>Số lượng</th>
                  <th>Thành tiền</th>
                </tr>
              </thead>
              <tbody id="order-table">
                <?php foreach ($cartItems as $item): ?>
                  <tr>
                    <td><img src="../assets/images/SanPham/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" style="width: 50px; height: auto;"></td>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= number_format($item['total']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                  <td><strong><span id="order-total"><?= number_format($grandTotal) ?></span> VND</strong></td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- 2. Form nhập thông tin khách hàng -->
          <form action="placeOrder.php" method="POST" id="payment-form" novalidate>
            <div class="row g-3">
              <div class="col-md-12">
                <label for="address" class="form-label">Địa chỉ</label>
                <textarea class="form-control" id="address" name="address" rows="2" required><?= $user['address'] ?></textarea>
              </div>

              <div class="col-md-12">
                <label for="phone" class="form-label">Số điện thoại</label>
                <input type="tel" class="form-control" id="phone" name="phone" pattern="[0-9]{10,11}" required value="<?= $user['phone'] ?>">
              </div>

              <div class="col-md-12">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required value="<?= $user['email'] ?>">
              </div>
            </div>

            <!-- Hidden inputs để gửi thông tin đơn hàng -->
            <div id="hidden-items"></div>

            <div class="mt-4 d-grid">
              <button type="submit" class="btn btn-primary btn-lg">Đặt hàng</button>
            </div>
          </form>
        </div>
      </div>

      <!-- thanh toán -->
      <div class="col-4">
        <div class="bg-white p-4 rounded shadow-sm">
          <h4 class="mb-3">Phương thức thanh toán</h4>

          <?php
          // Lấy danh sách phương thức thanh toán từ DB
          $paymentMethods = $db->select("SELECT * FROM payment_method");
          ?>

          <?php foreach ($paymentMethods as $method): ?>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="payment_method_id" id="method<?= $method['payment_method_id'] ?>" value="<?= $method['payment_method_id'] ?>" required>
              <label class="form-check-label" for="method<?= $method['payment_method_id'] ?>">
                <?= htmlspecialchars($method['name']) ?>
              </label>
            </div>
          <?php endforeach; ?>

          <div class="mt-4">
            <p><strong>Tổng thanh toán:</strong> <span class="text-danger fs-5"><?= number_format($grandTotal) ?> VND</span></p>
          </div>
        </div>
      </div>



    </div>
  </div>

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Custom JS -->
  <script src="../assets/javascript/checkOut.js"></script>
  <script>
    document.querySelector('form').addEventListener('submit', function(event) {
      event.preventDefault(); // Ngăn chặn hành động mặc định của form

      // Hiển thị thông báo thanh toán thành công
      alert('Bạn đã thanh toán thành công!');

      // Chuyển hướng về trang giỏ hàng
      window.location.href = 'Cart.php';
    });
  </script>
</body>

</html>