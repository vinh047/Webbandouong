<?php
require_once '../includes/DBConnect.php';
$db = DBConnect::getInstance();

// Lấy danh sách phiếu nhập
$receipts = $db->select("SELECT * FROM import_order");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$permissions = $_SESSION['permissions'] ?? [];
$canWrite = in_array('write', $permissions['Quản lý đơn nhập'] ?? []);
$canDelete = in_array('delete', $permissions['Quản lý đơn nhập'] ?? []);
?>

<div>
    <div class="p-3 d-flex align-items-center rounded" style="background-color: #f0f0f0; height: 80px;">
        <?php if ($canWrite): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReceiptModal">
                <i class="fa-solid fa-plus me-1"></i> THÊM
            </button>
        <?php endif; ?>

        <!-- Thanh tìm kiếm -->
        <div class="flex-grow-1">
            <form class="d-flex justify-content-center mx-auto" style="max-width: 400px; width: 100%;" role="search" id="form-search-id">
                <input class="receipt-id form-control me-2" type="search" placeholder="Tìm theo mã phiếu nhập"
                    aria-label="Search" name="receipt_id">
                <button type="button" class="btn-search btn btn-sm p-0 border-0 bg-transparent">
                    <i class="fas fa-search fa-lg"></i>
                </button>
            </form>
        </div>

        <select id="exportExcelOption" class="form-select w-auto">
            <option value="all">Xuất tất cả phiếu nhập</option>
            <option value="filtered">Xuất theo bộ lọc</option>
        </select>
        <button id="btnExportExcel" class="btn btn-success ms-2">Xuất Excel</button>
    </div>

    <!-- Tìm kiếm nâng cao -->
    <form method="GET" action=""
        class="form-search d-flex gap-2 align-items-center container mt-3 flex-wrap justify-content-center">

        <input type="number" class="min-price form-control w-auto" style="width: 120px;" name="price_min"
            placeholder="Tổng giá từ">

        <input type="number" class="max-price form-control w-auto" style="width: 120px;" name="price_max"
            placeholder="Tổng giá đến">

        <!-- Từ ngày -->
        <div class="form-group d-flex align-items-center ms-2">
            <label for="from_date" class="form-label mb-1 me-2">Từ ngày</label>
            <input type="date" class="form-control w-auto" style="width: 180px;" name="from_date" id="from_date">
        </div>

        <!-- Đến ngày -->
        <div class="form-group d-flex align-items-center">
            <label for="to_date" class="form-label mb-1 me-2">Đến ngày</label>
            <input type="date" class="form-control w-auto" style="width: 180px;" name="to_date" id="to_date">
        </div>

        <select name="status" class="form-select w-auto">
            <option value="">Tất cả tình trạng</option>
            <option value="Chờ xác nhận">Chờ xác nhận</option>
            <option value="Đã xác nhận">Đã xác nhận</option>
        </select>

    </form>


    <!-- Bảng danh sách phiếu nhập -->
    <div class="table-responsive mt-4 pe-3">
        <table class="table align-middle table-bordered">
            <thead class="table-light text-center">
                <tr>
                    <th scope="col">Mã phiếu nhập</th>
                    <th scope="col">Nhà cung cấp</th>
                    <th scope="col">Người nhập</th>
                    <th scope="col">Tổng giá</th>
                    <th scope="col">Ngày nhập</th>
                    <th scope="col">Tình trạng</th>
                    <?php if ($canWrite || $canDelete): ?>
                        <th scope="col">Chức năng</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody class="receipt-wrap text-center align-middle">
                <!-- Dữ liệu sẽ được đổ vào đây -->
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap"></div>
</div>

<!-- Modal Thêm Phiếu Nhập -->
<div class="modal fade" id="addReceiptModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold">Thêm Phiếu Nhập Hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Thông tin chung -->
                <div class="row mb-3">
                    <!-- Hidden input chứa mã nhân viên -->
                    <input type="hidden" name="user_id" id="user_id" value="<?= $_SESSION['user_id'] ?? '' ?>">

                    <!-- Hidden input chứa mã nhà cung cấp -->
                    <input type="hidden" name="supplier_id" id="supplier_id">

                    <!-- Ô nhập chọn nhà cung cấp -->
                    <div class="col-md-5">
                        <label class="form-label">Nhà cung cấp:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="supplier_name" name="supplier_name"
                                placeholder="Chọn nhà cung cấp" readonly>
                            <button type="button" class="btn btn-outline-primary"
                                onclick="openSupplierModal()">Chọn</button>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Ngày nhập:</label>
                        <input type="date" class="form-control" name="import_date">
                    </div>
                </div>

                <!-- Danh sách sản phẩm -->
                <table class="table table-bordered text-center">
                    <thead class="table-light">
                        <tr>
                            <th>Tên sản phẩm</th>
                            <th>Kiểu đóng gói</th>
                            <th>Số lượng</th>
                            <th>Đơn giá</th>
                            <th>Thành tiền</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="product-list">
                        <tr>
                            <td class="d-flex gap-2">
                                <input type="hidden" name="product_id[]">
                                <input type="hidden" name="packaging_option[]">
                                <input type="text" name="product_name[]" class="selected-product-name form-control"
                                    readonly placeholder="Sản phẩm" />
                                <button class="btn btn-success btn-sm btn-select-product"
                                    onclick="openPackagingSelector(this)">
                                    Chọn
                                </button>
                            </td>
                            <td>
                                <input type="text" name="packaging[]" class="packaging form-control text-capitalize"
                                    readonly>
                            </td>
                            <td>
                                <input type="number" name="quantity[]" class="quantity form-control" value="0"
                                    min="0" />
                            </td>
                            <td>
                                <input type="number" name="price[]" class="price form-control" value="0" />
                            </td>
                            <td>
                                <span class="total">0</span>
                            </td>
                            <td>
                                <button onclick="removeRow(this)" class="btn btn-danger btn-sm">Xóa</button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button onclick="addRow()" class="btn btn-primary mt-3">+ Thêm sản phẩm</button>

                <!-- Tổng cộng -->
                <div class="text-end mt-3">
                    <strong>Tổng tiền: <span id="grand-total">0</span> VND</strong>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-success" id="btnLuuPhieuNhap">Lưu Phiếu Nhập</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chọn Sản Phẩm -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chọn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <!-- 🔍 Tìm kiếm sản phẩm -->
                <input type="text" id="searchProduct" class="form-control" placeholder="Tìm theo tên sản phẩm...">

                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-hover text-center align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tên sản phẩm</th>
                                <th>Kiểu đóng gói</th>
                                <th>Đơn vị</th>
                                <th>Giá</th>
                                <th>Ảnh</th>
                                <th>Chọn</th>
                            </tr>
                        </thead>
                        <tbody id="productTable">
                            <!-- Dữ liệu sản phẩm sẽ được load vào đây -->
                            <!-- Ví dụ:
              <tr>
                <td>SP001</td>
                <td>Sữa tươi Vinamilk</td>
                <td>45.000đ</td>
                <td><img src="path.jpg" width="50"></td>
                <td><button class="btn btn-success btn-sm" onclick="selectProduct(this)">Chọn</button></td>
              </tr>
              -->
                        </tbody>
                    </table>
                </div>
                <div class="pagination-product-wrap"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chọn Nhà Cung Cấp -->
<div class="modal fade" id="supplierModal" tabindex="-1" aria-labelledby="supplierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chọn nhà cung cấp</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <!-- 🔍 Thanh tìm kiếm -->
                <input type="text" id="searchSupplier" class="form-control mb-3"
                    placeholder="Tìm theo tên nhà cung cấp...">

                <!-- Bảng danh sách nhà cung cấp -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Mã NCC</th>
                                <th>Tên nhà cung cấp</th>
                                <th>Địa chỉ</th>
                                <th>Email</th>
                                <th>Chọn</th>
                            </tr>
                        </thead>
                        <tbody id="supplierTable">
                            <!-- Dữ liệu NCC sẽ được render ở đây -->
                        </tbody>
                    </table>
                </div>
                <div class="pagination-supplier-wrap mt-2"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal xác nhận xoá phiếu nhập -->
<div class="modal fade" id="modalXoaPhieuNhap" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xoá</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Bạn có chắc chắn muốn xoá phiếu nhập có mã <strong id="phieu-nhap-id-display"></strong> không?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                <button type="button" class="btn btn-danger" id="btnXacNhanXoaPhieuNhap">Xoá</button>
            </div>
        </div>
    </div>
</div>


<!-- Modal Sửa Phiếu Nhập -->
<div class="modal fade" id="editReceiptModal" tabindex="-1" aria-labelledby="modalEditTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold">Sửa Phiếu Nhập Hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Thông tin chung -->
                <div class="row mb-3">
                    <!-- Hidden input chứa mã phiếu nhập -->
                    <input type="hidden" id="import_order_id_sua">

                    <!-- Hidden input chứa mã nhân viên -->
                    <input type="hidden" name="user_id" id="user_id_sua" value="<?= $_SESSION['user_id'] ?? '' ?>">

                    <!-- Hidden input chứa mã nhà cung cấp -->
                    <input type="hidden" name="supplier_id" id="supplier_id_sua">

                    <!-- Ô nhập chọn nhà cung cấp -->
                    <div class="col-md-5">
                        <label class="form-label">Nhà cung cấp:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="supplier_name_sua" name="supplier_name"
                                placeholder="Chọn nhà cung cấp" readonly>
                            <button type="button" class="btn btn-outline-primary"
                                onclick="openSupplierModal('sua')">Chọn</button>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Ngày nhập:</label>
                        <input type="date" class="form-control" id="import_date_sua" name="import_date">
                    </div>
                </div>

                <!-- Danh sách sản phẩm -->
                <table class="table table-bordered text-center">
                    <thead class="table-light">
                        <tr>
                            <th>Tên sản phẩm</th>
                            <th>Kiểu đóng gói</th>
                            <th>Số lượng</th>
                            <th>Đơn giá</th>
                            <th>Thành tiền</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="product-list-sua">
                        <!-- Dữ liệu sản phẩm sẽ được render tại đây khi sửa -->
                    </tbody>
                </table>

                <button type="button" onclick="addRow('sua')" class="btn btn-primary mt-3">+ Thêm sản phẩm</button>

                <!-- Tổng cộng -->
                <div class="text-end mt-3">
                    <strong>Tổng tiền: <span id="grand-total-sua">0</span> VND</strong>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-success" id="btnSuaPhieuNhap">Cập nhật</button>
            </div>
        </div>
    </div>
</div>

<!-- modal chi tiết -->
<div class="modal fade" id="viewReceiptModal" tabindex="-1" aria-labelledby="modalViewTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold">Chi tiết Phiếu Nhập Hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Thông tin chung -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nhà cung cấp:</label>
                        <input type="text" class="form-control" id="supplier_name_view" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ngày nhập:</label>
                        <input type="date" class="form-control" id="import_date_view" readonly>
                    </div>
                </div>

                <!-- Danh sách sản phẩm -->
                <table class="table table-bordered text-center">
                    <thead class="table-light">
                        <tr>
                            <th>Tên sản phẩm</th>
                            <th>Kiểu đóng gói</th>
                            <th>Số lượng</th>
                            <th>Đơn giá</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody id="product-list-view">
                        <!-- Render sản phẩm ở đây -->
                    </tbody>
                </table>

                <!-- Tổng cộng -->
                <div class="text-end mt-3">
                    <strong>Tổng tiền: <span id="grand-total-view">0</span> VND</strong>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger d-flex justify-content-end align-items-center"
                    id="btnExportReceiptPdf">
                    <i class="fa-solid fa-file-pdf me-1"></i> Xuất PDF
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal xác nhận phiếu nhập -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-body text-center">
                <p class="fw-bold fs-5 text-dark">
                    Khi chọn xác nhận sẽ không còn xử lý được nữa!<br>
                    Bạn có chắc chắn không?
                </p>
                <div class="mt-4 d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-danger" id="btnConfirmAction">Xác nhận</button>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Hủy</button>
                </div>
            </div>
        </div>
    </div>
</div>





<script>
    let currentTargetRow = null;
    let currentMode = 'add'; // mặc định là thêm

    let currentFilterParams = "";

    function loadReceipts(page = 1, params = "") {
        const receiptWrap = document.querySelector('.receipt-wrap');
        const paginationWrap = document.querySelector('.pagination-wrap');

        fetch(`ajax/load_importOrder.php?page=${page}&${params}`)
            .then(res => res.text())
            .then(data => {
                const parts = data.split('SPLIT');
                receiptWrap.innerHTML = parts[0] || '';
                paginationWrap.innerHTML = parts[1] || '';
            })
            .catch(error => console.error('Error loading receipts:', error));
    }

    // Tải danh sách phiếu nhập khi trang được load
    loadReceipts(1, currentFilterParams);

    document.addEventListener("pagination:change", function(e) {
        const {
            page,
            target
        } = e.detail;

        if (target === "receiptpage") {
            loadReceipts(page, currentFilterParams);
        }
        if (target === 'packagingpage') {
            loadPackagingOptions(page, currentFilterParamsPackaging);
        }
        if (target === 'supplierpage') {
            loadSuppliers(page, currentSupplierParams);
        }

    });

    document.addEventListener("click", function(e) {
        const targetLink = e.target.closest("[data-page][data-target]");
        if (targetLink) {
            e.preventDefault();
            console.log('DEBUG CLICK PAGINATION:', targetLink.dataset.page, targetLink.dataset.target);
            const page = parseInt(targetLink.getAttribute("data-page"));
            const targetName = targetLink.getAttribute("data-target");

            if (!isNaN(page) && targetName) {
                const event = new CustomEvent("pagination:change", {
                    detail: {
                        page,
                        target: targetName
                    }
                });
                document.dispatchEvent(event);
            }
        }
    });

    document.addEventListener("click", function(e) {
        const targetLink = e.target.closest("[data-page][data-target]");
        if (targetLink) {
            e.preventDefault();
            const page = parseInt(targetLink.getAttribute("data-page"));
            const targetName = targetLink.getAttribute("data-target");

            if (!isNaN(page) && targetName) {
                const event = new CustomEvent("pagination:change", {
                    detail: {
                        page,
                        target: targetName
                    }
                });
                document.dispatchEvent(event);
            }
        }
    });

    // 🎯 Lắng nghe sự kiện tìm kiếm
    document.querySelector(".form-search").addEventListener("submit", function(e) {
        e.preventDefault();

        let formData = new FormData(e.target);
        currentFilterParams = new URLSearchParams(formData).toString();

        loadReceipts(1, currentFilterParams);
    });

    // 🎯 Lắng nghe sự kiện input (cho tìm kiếm động)
    document.querySelectorAll('.form-search input, .form-search select').forEach(element => {
        element.addEventListener('input', debounce(function() {
            let formData = new FormData(document.querySelector('.form-search'));
            currentFilterParams = new URLSearchParams(formData).toString();

            loadReceipts(1, currentFilterParams);
        }, 300));
    });

    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // Lấy phần tử form, input, button
    const receiptSearchForm = document.getElementById('form-search-id');
    const receiptSearchInput = receiptSearchForm.querySelector('.receipt-id');
    const receiptSearchButton = receiptSearchForm.querySelector('.btn-search');

    // Sự kiện click nút tìm kiếm
    receiptSearchButton.addEventListener('click', function(e) {
        e.preventDefault();
        handleFilterChange();
    });

    // Sự kiện nhấn Enter trong ô tìm kiếm
    receiptSearchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleFilterChange();
        }
    });

    function handleFilterChange() {
        const form1 = document.querySelector('.form-search'); // form lọc nâng cao
        const form2 = document.getElementById('form-search-id'); // form tìm theo mã

        const data = new URLSearchParams();

        // Lấy dữ liệu từ form lọc nâng cao
        for (let [key, value] of new FormData(form1).entries()) {
            if (value) data.append(key, value);
        }

        // Lấy thêm dữ liệu từ form tìm kiếm mã
        for (let [key, value] of new FormData(form2).entries()) {
            if (value) data.append('search_id', value); // ⚠ đổi 'order_id' → 'search_id' nếu cần
        }

        currentFilterParams = data.toString();
        loadReceipts(1, currentFilterParams);
    }


    // 🎯 Lắng nghe sự kiện tìm kiếm tự động và theo phím bấm
    document.querySelectorAll('.form-search input, .form-search select').forEach(element => {
        element.addEventListener('input', debounce(handleFilterChange, 300));
        element.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleFilterChange();
            }
        });
        element.addEventListener('keyup', function(e) {
            if (e.key === 'Delete' || e.key === 'Backspace') {
                setTimeout(handleFilterChange, 300);
            }
        });
    });

    document.querySelector(".btn-search").addEventListener("click", function(e) {
        e.preventDefault();
        handleFilterChange();
    });


    function addRow() {
        const tableBody = document.getElementById('product-list');

        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td class="d-flex gap-2">
                <input type="hidden" name="product_id[]">
                <input type="hidden" name="packaging_option[]">
                <input type="text" name="product_name[]" class="selected-product-name form-control" readonly placeholder="Sản phẩm" />
                <button type="button" class="btn btn-success btn-sm btn-select-product" onclick="openPackagingSelector(this)">
                    Chọn sản phẩm
                </button>
            </td>
            <td>
                <input type="text" name="packaging[]" class="packaging form-control text-capitalize" readonly>
            </td>
            <td>
                <input type="number" name="quantity[]" class="quantity form-control" value="0" min="0" 
                    oninput="updateRowTotal(this)">
            </td>
            <td>
                <input type="number" name="price[]" class="price form-control" value="0" 
                    oninput="updateRowTotal(this)">
            </td>
            <td>
                <span class="total">0</span>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Xóa</button>
            </td>
        `;

        tableBody.appendChild(newRow);
    }

    function removeRow(button) {
        const row = button.closest("tr");
        row.remove();
        updateGrandTotal();
    }

    function updateRowTotal(input) {
        const row = input.closest("tr");
        const qtyInput = row.querySelector('input[name="quantity[]"]');
        const priceInput = row.querySelector('input[name="price[]"]');

        const quantity = parseInt(qtyInput.value) || 0;
        const price = parseInt(priceInput.value.replace(/,/g, '')) || 0;

        const total = quantity * price;
        row.querySelector('.total').innerText = total.toLocaleString();

        updateGrandTotal();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Gắn sự kiện cho tất cả ô quantity và price hiện có
        document.querySelectorAll('input[name="quantity[]"], input[name="price[]"]').forEach(input => {
            input.addEventListener('keyup', function() {
                updateRowTotal(this);
            });
        });
    });



    // modal chọn sp
    // Global variable dùng để lưu bộ lọc cho đóng gói
    let currentFilterParamsPackaging = "";

    // Hàm tải danh sách sản phẩm kiểu đóng gói từ file PHP dựa trên tham số tìm kiếm và phân trang
    function loadPackagingOptions(page = 1, params = "") {
        fetch('ajax/load_packaging_options.php?page=' + page + params)
            .then(res => res.json())
            .then(data => {
                // Gán kết quả HTML trả về vào tbody của bảng đóng gói
                document.getElementById('productTable').innerHTML = data.packaging_html || '';
                // Gán phân trang vào vùng đặt phân trang
                document.querySelector('.pagination-product-wrap').innerHTML = data.pagination || '';
            })
            .catch(error => console.error("Lỗi khi tải danh sách sản phẩm:", error));
    }

    // Lắng nghe sự kiện tìm kiếm gần đúng trên ô input tìm kiếm
    const searchPackaging = document.getElementById("searchProduct");
    if (searchPackaging) {
        searchPackaging.addEventListener("input", debounce(function() {
            const keyword = searchPackaging.value.trim();
            currentFilterParamsPackaging = keyword ? `&search=${encodeURIComponent(keyword)}` : '';
            loadPackagingOptions(1, currentFilterParamsPackaging);
        }, 300));
    }

    // Hàm mở modal chọn đóng gói, được gọi khi click vào nút mở modal
    window.openPackagingSelector = function(button, mode = 'add') {
        currentTargetRow = button.closest("tr");
        currentMode = mode; // 'add' hoặc 'edit'

        if (mode === 'edit') {
            const editModal = bootstrap.Modal.getInstance(document.getElementById('editReceiptModal'));
            if (editModal) editModal.hide();
        } else {
            const addModal = bootstrap.Modal.getInstance(document.getElementById('addReceiptModal'));
            if (addModal) addModal.hide();
        }

        const packagingModal = new bootstrap.Modal(document.getElementById('productModal'));
        packagingModal.show();

        loadPackagingOptions(1, currentFilterParamsPackaging);
    };

    // Hàm gán thông tin sản phẩm đóng gói đã chọn
    window.selectPackaging = function(btn) {
        const name = btn.dataset.product;
        const packagingId = btn.dataset.packagingId;
        const price = btn.dataset.price;
        const id = btn.dataset.productId;
        const packaging = btn.dataset.packaging;

        if (currentTargetRow) {
            currentTargetRow.querySelector('input[name="product_id[]"]').value = id;
            currentTargetRow.querySelector('input[name="packaging[]"]').value = packaging;
            currentTargetRow.querySelector('input[name="product_name[]"]').value = name;
            currentTargetRow.querySelector('input[name="packaging_option[]"]').value = packagingId;
            currentTargetRow.querySelector('.total').innerText = (price * (currentTargetRow.querySelector('input[name="quantity[]"]').value || 0)).toLocaleString();
        }

        // Đóng modal chọn sản phẩm
        const packagingModal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
        if (packagingModal) packagingModal.hide();

        // Mở lại đúng modal thêm/sửa
        if (currentMode === 'edit') {
            const editModal = new bootstrap.Modal(document.getElementById('editReceiptModal'));
            editModal.show();
            updateGrandTotal('edit');
        } else {
            const addModal = new bootstrap.Modal(document.getElementById('addReceiptModal'));
            addModal.show();
            updateGrandTotal('add');
        }
    };


    // Tải mặc định danh sách nếu mở modal đóng gói thông qua nút có data-bs-target="#selectPackagingModal"
    document.querySelector('[data-bs-target="#selectPackagingModal"]')?.addEventListener("click", function() {
        loadPackagingOptions(1);
    });

    function updateGrandTotal(mode = 'add') {
        let total = 0;
        const tableSelector = mode === 'edit' ? '#product-list-sua tr' : '#product-list tr';
        const totalSelector = mode === 'edit' ? 'grand-total-sua' : 'grand-total';

        document.querySelectorAll(tableSelector).forEach(row => {
            const qty = parseInt(row.querySelector('input[name="quantity[]"]').value) || 0;
            const price = parseInt(row.querySelector('input[name="price[]"]').value.replace(/,/g, '')) || 0;
            const subtotal = qty * price;
            row.querySelector('.total').innerText = subtotal.toLocaleString();
            total += subtotal;
        });

        document.getElementById(totalSelector).innerText = total.toLocaleString();
    }


    // Gọi modal
    function openSupplierModal() {
        const modal = new bootstrap.Modal(document.getElementById('supplierModal'));
        modal.show();
        loadSuppliers(1);
    }

    let currentSupplierParams = "";

    function loadSuppliers(page = 1, params = "") {
        fetch('ajax/load_suppliers.php?page=' + page + params)
            .then(res => res.json())
            .then(data => {
                document.getElementById('supplierTable').innerHTML = data.supplier_html || '';
                document.querySelector('.pagination-supplier-wrap').innerHTML = data.pagination || '';
            })
            .catch(error => console.error("Lỗi tải danh sách nhà cung cấp:", error));
    }

    document.getElementById("searchSupplier").addEventListener("input", debounce(function() {
        const keyword = this.value.trim();
        currentSupplierParams = keyword ? `&search=${encodeURIComponent(keyword)}` : "";
        loadSuppliers(1, currentSupplierParams);
    }, 300));

    // Hàm chọn NCC
    window.selectSupplier = function(btn) {
        const id = btn.dataset.supplierId;
        const name = btn.dataset.supplierName;

        document.getElementById("supplier_id").value = id;
        document.getElementById("supplier_name").value = name;

        const modal = bootstrap.Modal.getInstance(document.getElementById("supplierModal"));
        if (modal) modal.hide();
    }

    document.getElementById("btnLuuPhieuNhap").addEventListener('click', async function() {
        const formData = new FormData();

        formData.append("user_id", document.getElementById("user_id").value);
        formData.append("supplier_id", document.getElementById("supplier_id").value);
        formData.append("import_date", document.querySelector("input[name='import_date']").value);

        const productIds = document.querySelectorAll('input[name="product_id[]"]');
        const packagingOptions = document.querySelectorAll('input[name="packaging_option[]"]');
        const quantities = document.querySelectorAll('input[name="quantity[]"]');
        const prices = document.querySelectorAll('input[name="price[]"]');

        for (let i = 0; i < productIds.length; i++) {
            const quantity = quantities[i].value.trim();
            const price = prices[i].value.trim();

            if (!quantity || isNaN(quantity) || Number(quantity) < 0) {
                alert("Vui lòng nhập số lượng hợp lệ (số > 0) cho sản phẩm dòng " + (i + 1));
                return; // chặn lưu luôn
            }

            if (!price || isNaN(price) || Number(price) < 0) {
                alert("Vui lòng nhập đơn giá hợp lệ (số > 0) cho sản phẩm dòng " + (i + 1));
                return; // chặn lưu luôn
            }

            formData.append("product_id[]", productIds[i].value);
            formData.append("packaging_option[]", packagingOptions[i].value);
            formData.append("quantity[]", quantity);
            formData.append("price[]", price);
        }


        try {
            const res = await fetch("ajax/add_import_order.php", {
                method: "POST",
                body: formData
            });

            const data = await res.json();
            if (data.success) {
                alert(data.message);
                bootstrap.Modal.getInstance(document.getElementById("addReceiptModal")).hide();
                loadReceipts(1, currentFilterParams); // reload danh sách

                document.getElementById("supplier_id").value = "";
                document.getElementById("supplier_name").value = "";
                document.querySelector("input[name='import_date']").value = "";

                const productList = document.getElementById("product-list");
                productList.innerHTML = `
                    <tr>
                        <td class="d-flex gap-2">
                            <input type="hidden" name="product_id[]">
                            <input type="hidden" name="packaging_option[]">
                            <input type="text" name="product_name[]" class="selected-product-name form-control" readonly placeholder="Sản phẩm" />
                            <button class="btn btn-success btn-sm btn-select-product" onclick="openPackagingSelector(this)">
                                Chọn
                            </button>
                        </td>
                        <td>
                            <input type="number" name="quantity[]" class="quantity form-control" value="1" min="0" />
                        </td>
                        <td>
                            <input type="number" name="price[]" class="price form-control" value="0" />
                        </td>
                        <td>
                            <span class="total">0</span>
                        </td>
                        <td>
                            <button onclick="removeRow(this)" class="btn btn-danger btn-sm">Xóa</button>
                        </td>
                    </tr>
                `;

                document.getElementById("grand-total").textContent = "0";
            } else {
                alert("Lỗi: " + data.message);
            }
        } catch (err) {
            console.log(err.message);
            alert("Lỗi hệ thống: " + err.message);
        }
    });

    let idPhieuNhapDangXoa = null;

    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-delete-receipt')) {
            e.preventDefault();
            const btn = e.target.closest('.btn-delete-receipt');
            idPhieuNhapDangXoa = btn.getAttribute('data-id');

            // Gán vào modal
            document.getElementById('phieu-nhap-id-display').textContent = idPhieuNhapDangXoa;
        }
    });

    document.getElementById('btnXacNhanXoaPhieuNhap').addEventListener('click', function() {
        if (!idPhieuNhapDangXoa) return;

        fetch('ajax/delete_import_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    import_order_id: idPhieuNhapDangXoa
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Xoá thành công');
                    loadReceipts(1, currentFilterParams);
                } else {
                    alert('❌ Xoá thất bại: ' + data.message);
                }

                const modal = bootstrap.Modal.getInstance(document.getElementById('modalXoaPhieuNhap'));
                modal.hide();
            });
    });

    document.addEventListener("click", function(e) {
        const btn = e.target.closest(".btn-edit-receipt");
        if (!btn) return;

        const receiptId = btn.dataset.id;
        const supplierId = btn.dataset.supplier;
        const userId = btn.dataset.user;

        // Gán các trường ẩn
        document.getElementById("import_order_id_sua").value = receiptId;
        document.getElementById("supplier_id_sua").value = supplierId;
        document.getElementById("user_id_sua").value = userId;

        fetch(`ajax/get_import_order_details.php?id=${receiptId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert("❌ Lỗi: " + data.message);
                    return;
                }


                // Gán thông tin chung phiếu nhập
                document.getElementById("import_date_sua").value = data.receipt.created_at.substring(0, 10);
                document.getElementById("supplier_name_sua").value = data.receipt.supplier_name;

                // Xoá dữ liệu cũ trong bảng
                const tbody = document.getElementById("product-list-sua");
                tbody.innerHTML = "";

                // Render lại danh sách sản phẩm chi tiết
                data.products.forEach(item => {
                    const row = document.createElement("tr");
                    const total = item.quantity * item.price;

                    row.innerHTML = `
                        <td class="d-flex gap-2">
                            <input type="hidden" name="product_id[]" value="${item.product_id}">
                            <input type="hidden" name="packaging_option[]" value="${item.packaging_option_id}">
                            <input type="text" name="product_name[]" class="selected-product-name form-control" readonly value="${item.product_name}" />
                            <button type="button" class="btn btn-success btn-sm btn-select-product" onclick="openPackagingSelector(this, 'edit')">Chọn</button>
                        </td>
                        <td>
                            <input type="text" name="packaging[]" class="packaging form-control text-capitalize" value="${item.packaging_type + ' - ' + item.unit_quantity}" readonly>
                        </td>
                        <td>
                            <input type="number" name="quantity[]" class="quantity form-control" value="${item.quantity}" oninput="updateRowTotal(this, 'sua')" />
                        </td>
                        <td>
                            <input type="number" name="price[]" class="price form-control" value="${item.price}" oninput="updateRowTotal(this, 'sua')" />
                        </td>
                        <td><span class="total">${total.toLocaleString()}</span></td>
                        <td><button type="button" onclick="removeRow(this, 'sua')" class="btn btn-danger btn-sm">Xoá</button></td>
                    `;
                    tbody.appendChild(row);
                });

                updateGrandTotal('sua');
            });
    });

    function addRow(mode = '') {
        const tableBody = document.getElementById(mode === 'sua' ? 'product-list-sua' : 'product-list');

        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td class="d-flex gap-2">
                <input type="hidden" name="product_id[]" />
                <input type="hidden" name="packaging_option[]" />
                <input type="text" name="product_name[]" class="selected-product-name form-control" readonly placeholder="Sản phẩm" />
                <button type="button" class="btn btn-success btn-sm btn-select-product" onclick="openPackagingSelector(this, '${mode}')">Chọn</button>
            </td>
            <td>
                <input type="text" name="packaging[]" class="packaging form-control text-capitalize" readonly>
            </td>
            <td>
                <input type="number" name="quantity[]" class="quantity form-control" value="0" min="0" oninput="updateRowTotal(this, '${mode}')" />
            </td>
            <td>
                <input type="number" name="price[]" class="price form-control" value="0" oninput="updateRowTotal(this, '${mode}')" />
            </td>
            <td>
                <span class="total">0</span>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this, '${mode}')">Xoá</button>
            </td>
        `;

        tableBody.appendChild(newRow);
    }

    document.getElementById("btnSuaPhieuNhap").addEventListener("click", async function() {
        const formData = new FormData();

        const importOrderId = document.getElementById("import_order_id_sua").value;
        const userId = document.getElementById("user_id_sua").value;
        const supplierId = document.getElementById("supplier_id_sua").value;
        const importDate = document.getElementById("import_date_sua").value;

        if (!importOrderId || !supplierId || !importDate) {
            alert("❌ Vui lòng điền đầy đủ thông tin phiếu nhập!");
            return;
        }

        formData.append("import_order_id", importOrderId);
        formData.append("user_id", userId);
        formData.append("supplier_id", supplierId);
        formData.append("import_date", importDate);

        const productIds = document.querySelectorAll('#product-list-sua input[name="product_id[]"]');
        const packagingOptions = document.querySelectorAll('#product-list-sua input[name="packaging_option[]"]');
        const quantities = document.querySelectorAll('#product-list-sua input[name="quantity[]"]');
        const prices = document.querySelectorAll('#product-list-sua input[name="price[]"]');

        for (let i = 0; i < productIds.length; i++) {
            formData.append("product_id[]", productIds[i].value);
            formData.append("packaging_option[]", packagingOptions[i].value);
            formData.append("quantity[]", quantities[i].value);
            formData.append("price[]", prices[i].value);
        }

        try {
            const res = await fetch("ajax/update_import.php", {
                method: "POST",
                body: formData
            });

            const data = await res.json();

            if (data.success) {
                alert("✅ Cập nhật phiếu nhập thành công!");
                bootstrap.Modal.getInstance(document.getElementById("editReceiptModal")).hide();
                loadReceipts(1, currentFilterParams); // Reload danh sách phiếu nhập
            } else {
                alert("❌ Lỗi: " + data.message);
            }
        } catch (err) {
            alert("❌ Lỗi hệ thống: " + err.message);
            console.error(err);
        }
    });

    document.addEventListener("click", function(e) {
        const btn = e.target.closest(".btn-view-receipt");
        if (!btn) return;

        const receiptId = btn.dataset.id;



        fetch(`ajax/get_import_order_details.php?id=${receiptId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert("❌ " + data.message);
                    return;
                }

                window.currentReceiptForPdf = data;
                // Gán dữ liệu vào modal
                document.getElementById("supplier_name_view").value = data.receipt.supplier_name;
                document.getElementById("import_date_view").value = data.receipt.created_at.substring(0, 10);

                const tbody = document.getElementById("product-list-view");
                tbody.innerHTML = "";

                let total = 0;

                data.products.forEach(item => {
                    const row = document.createElement("tr");
                    const subtotal = item.quantity * item.price;
                    total += subtotal;

                    row.innerHTML = `
                    <td><input type="text" class="form-control" value="${item.product_name}" readonly></td>
                    <td><input type = "text" class="form-control" value="${item.packaging_type + ' - ' + item.unit_quantity}" readonly></td>
                    <td><input type="number" class="form-control text-center" value="${item.quantity}" readonly></td>
                    <td><input type="text" class="form-control text-end" value="${item.price.toLocaleString()}" readonly></td>
                    <td><input type="text" class="form-control text-end" value="${subtotal.toLocaleString()}" readonly></td>
                `;
                    tbody.appendChild(row);
                });

                document.getElementById("grand-total-view").innerText = total.toLocaleString();

                // Kiểm tra trạng thái
                const exportBtn = document.getElementById("btnExportReceiptPdf");
                if (data.receipt.status === "Đã xác nhận") {
                    exportBtn.style.setProperty('display', 'inline-flex', 'important');
                } else {
                    exportBtn.style.setProperty('display', 'none', 'important');
                }
            })
            .catch(err => {
                console.error("Lỗi khi lấy chi tiết phiếu nhập:", err);
                alert("❌ Lỗi hệ thống khi xem chi tiết!");
            });
    });

    let selectedImportOrderId = null; // Lưu id phiếu nhập cần xác nhận

    function openConfirmModal(importOrderId) {
        selectedImportOrderId = importOrderId;
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        confirmModal.show();
    }

    document.getElementById('btnConfirmAction').addEventListener('click', function() {
        if (selectedImportOrderId) {
            fetch('ajax/confirm_receipt.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'import_order_id=' + selectedImportOrderId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Xác nhận phiếu nhập thành công!');
                        location.reload();
                    } else {
                        alert('Xác nhận thất bại: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Đã có lỗi xảy ra.');
                });
        }
    });

    // xuất pdf
    document.getElementById("btnExportReceiptPdf").addEventListener("click", function() {
        if (!window.currentReceiptForPdf) {
            alert("Không tìm thấy dữ liệu phiếu nhập để xuất PDF.");
            return;
        }

        fetch('ajax/export_receipt_pdf.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(window.currentReceiptForPdf)
            })
            .then(response => {
                if (!response.ok) throw new Error("Không thể xuất PDF");

                return response.blob();
            })
            .then(blob => {
                const blobUrl = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = blobUrl;
                a.download = 'phieu_nhap.pdf'; // Hoặc không set thì tự động mở tab
                a.click();
                URL.revokeObjectURL(blobUrl);
            })
            .catch(error => {
                console.error("Lỗi xuất PDF:", error);
                alert("❌ Lỗi khi xuất PDF");
            });
    });


    // xuất excel
    document.getElementById('btnExportExcel').addEventListener('click', function() {
        const option = document.getElementById('exportExcelOption').value;
        let exportUrl = 'ajax/export_receipts_excel.php';

        if (option === 'filtered') {
            const form1 = document.querySelector('.form-search');
            const form2 = document.getElementById('form-search-id');

            const data = new URLSearchParams();

            for (let [key, value] of new FormData(form1).entries()) {
                if (value) data.append(key, value);
            }

            for (let [key, value] of new FormData(form2).entries()) {
                if (value) data.append('search_id', value); // Đảm bảo đúng tên biến server đang nhận
            }

            const queryString = data.toString();
            if (queryString) exportUrl += '?' + queryString;
        }

        window.location.href = exportUrl;
    });
</script>