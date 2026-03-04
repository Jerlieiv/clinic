
<?php
// Include configuration file
require_once '../config/db.php';
?>
<?php include 'includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale</title>
    <style>
        /* ========== RESET & BASE STYLES ========== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f8fafc;
            color: #334155;
            line-height: 1.5;
            overflow-x: hidden;
        }

        /* ========== MAIN CONTAINER ========== */
        .pos-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            padding: 0;
            gap: 0;
        }

        /* ========== TOP BAR ========== */
        .pos-top-bar {
            background: white;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .pos-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .pos-top-actions {
            display: flex;
            gap: 10px;
        }

        .pos-action-btn {
            background: #f1f5f9;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .pos-action-btn:hover {
            background: #e2e8f0;
            transform: translateY(-1px);
        }

        .pos-action-btn.clear {
            color: #dc2626;
            background: #fee2e2;
        }

        .pos-action-btn.clear:hover {
            background: #fecaca;
        }

        .pos-action-btn.hold {
            color: #2563eb;
            background: #dbeafe;
        }

        .pos-action-btn.hold:hover {
            background: #bfdbfe;
        }

        .pos-action-btn.load {
            color: #059669;
            background: #d1fae5;
        }

        .pos-action-btn.load:hover {
            background: #a7f3d0;
        }

        /* ========== MAIN CONTENT AREA ========== */
        .pos-content-area {
            display: flex;
            flex: 1;
            overflow: hidden;
            gap: 0;
            height: calc(100vh - 72px);
        }

        /* ========== LEFT PANEL ========== */
        .left-panel {
            width: 70%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: white;
        }

        /* Customer Section */
        .customer-section {
            padding: 20px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            flex-shrink: 0;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .customer-row {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .customer-select-wrapper {
            flex: 1;
            position: relative;
        }

        .customer-select {
            width: 100%;
            padding: 12px 16px;
            padding-right: 40px;
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            background: white;
            color: #334155;
            cursor: pointer;
            transition: all 0.2s;
        }

        .customer-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .customer-select-arrow {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
        }

        .add-customer-btn {
            background: #3b82f6;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .add-customer-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        /* Products Section */
        .products-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #f8fafc;
        }

        /* Search Bar */
        .search-container {
            padding: 20px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            flex-shrink: 0;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid #cbd5e1;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            background: #f8fafc;
            transition: all 0.2s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-box ion-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 20px;
        }

        /* Categories */
        .categories-container {
            padding: 0 20px 16px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            flex-shrink: 0;
        }

        .categories-container::-webkit-scrollbar {
            display: none;
        }

        .categories {
            display: flex;
            gap: 10px;
            padding-bottom: 4px;
        }

        .category-btn {
            padding: 8px 16px;
            background: #f1f5f9;
            border: none;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s;
        }

        .category-btn:hover {
            background: #e2e8f0;
        }

        .category-btn.active {
            background: #3b82f6;
            color: white;
        }

        /* Products Grid */
        .products-grid-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8fafc;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
        }

        @media (max-width: 1400px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            }
        }

        @media (max-width: 1200px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }

        .product-card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            border: 2px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .product-card:hover {
            border-color: #3b82f6;
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .product-card:active {
            transform: translateY(-2px);
        }

        .product-image {
            width: 100%;
            height: 120px;
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .product-image ion-icon {
            font-size: 40px;
            color: #94a3b8;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-code {
            font-size: 12px;
            color: #64748b;
            font-family: 'Courier New', monospace;
            margin-bottom: 8px;
        }

        .product-price {
            font-size: 18px;
            font-weight: 800;
            color: #3b82f6;
            margin-bottom: 8px;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
        }

        .product-stock {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 12px;
        }

        .stock-in {
            background: #d1fae5;
            color: #065f46;
        }

        .stock-low {
            background: #fef3c7;
            color: #92400e;
        }

        .stock-out {
            background: #fee2e2;
            color: #991b1b;
        }

        .product-vat {
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 10px;
        }

        .vat-included {
            background: #d1fae5;
            color: #065f46;
        }

        .vat-excluded {
            background: #fef3c7;
            color: #92400e;
        }

        /* ========== RIGHT PANEL ========== */
        .right-panel {
            width: 30%;
            display: flex;
            flex-direction: column;
            background: white;
            border-left: 1px solid #e2e8f0;
            min-width: 400px;
        }

        /* Cart Header */
        .cart-header {
            padding: 20px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
        }

        .cart-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .cart-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-stats {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cart-items-count {
            background: #3b82f6;
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 700;
        }

        .vat-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f1f5f9;
            padding: 6px 12px;
            border-radius: 20px;
        }

        .toggle-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
        }

        .toggle-switch {
            position: relative;
            width: 40px;
            height: 24px;
            background: #cbd5e1;
            border-radius: 12px;
            transition: all 0.2s;
        }

        .toggle-switch:after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        input[type="checkbox"]:checked + .toggle-switch {
            background: #10b981;
        }

        input[type="checkbox"]:checked + .toggle-switch:after {
            transform: translateX(16px);
        }

        input[type="checkbox"] {
            display: none;
        }

        /* Cart Items Container */
        .cart-items-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8fafc;
        }

        .cart-empty {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .cart-empty ion-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .cart-empty h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #64748b;
        }

        .cart-empty p {
            font-size: 15px;
            color: #94a3b8;
            max-width: 300px;
            line-height: 1.5;
        }

        /* Cart Items */
        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .cart-item {
            background: white;
            border-radius: 12px;
            padding: 16px;
            border: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.2s;
        }

        .cart-item:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .cart-item-image {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .cart-item-image ion-icon {
            font-size: 28px;
            color: #94a3b8;
        }

        .cart-item-details {
            flex: 1;
            min-width: 0;
        }

        .cart-item-name {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cart-item-price-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }

        .cart-item-price {
            font-size: 15px;
            font-weight: 600;
            color: #64748b;
        }

        .cart-item-vat {
            font-size: 11px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 8px;
        }

        .vat-inc {
            background: #d1fae5;
            color: #065f46;
        }

        .vat-exc {
            background: #fef3c7;
            color: #92400e;
        }

        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 8px;
        }

        .qty-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            font-weight: 700;
            color: #475569;
            transition: all 0.2s;
        }

        .qty-btn:hover {
            background: #e2e8f0;
            transform: scale(1.1);
        }

        .qty-input {
            width: 50px;
            text-align: center;
            border: none;
            background: transparent;
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
        }

        .qty-input::-webkit-outer-spin-button,
        .qty-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .cart-item-total {
            font-size: 18px;
            font-weight: 800;
            color: #3b82f6;
            min-width: 80px;
            text-align: right;
        }

        .remove-item {
            background: #fee2e2;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #dc2626;
            transition: all 0.2s;
        }

        .remove-item:hover {
            background: #fecaca;
            transform: scale(1.1);
        }

        /* Cart Summary */
        .cart-summary {
            background: white;
            padding: 24px;
            border-top: 2px solid #e2e8f0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-size: 15px;
        }

        .summary-label {
            color: #64748b;
            font-weight: 600;
        }

        .summary-value {
            font-weight: 700;
            color: #1e293b;
        }

        .summary-row.total {
            font-size: 22px;
            font-weight: 800;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 2px solid #e2e8f0;
            color: #3b82f6;
        }

        .summary-row.total .summary-label {
            color: #3b82f6;
        }

        /* Payment Section */
        .payment-section {
            padding: 24px;
            background: white;
            border-top: 2px solid #e2e8f0;
        }

        .payment-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .payment-method {
            background: white;
            border: 2px solid #cbd5e1;
            border-radius: 12px;
            padding: 20px 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .payment-method:hover {
            border-color: #3b82f6;
            background: #f8fafc;
            transform: translateY(-2px);
        }

        .payment-method.selected {
            border-color: #3b82f6;
            background: #eff6ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }

        .payment-icon {
            font-size: 28px;
            color: #3b82f6;
        }

        .payment-label {
            font-size: 14px;
            font-weight: 700;
            color: #334155;
        }

        /* Checkout Button */
        .checkout-btn {
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 800;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .checkout-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .checkout-btn:active:not(:disabled) {
            transform: translateY(0);
        }

        .checkout-btn:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            opacity: 0.7;
            box-shadow: none;
        }

        /* ========== RECENT TRANSACTIONS ========== */
        .recent-transactions {
            background: white;
            border-radius: 12px;
            margin: 20px;
            border: 2px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .transactions-header {
            padding: 20px;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }

        .transactions-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .transactions-actions {
            display: flex;
            gap: 10px;
        }

        .trans-btn {
            background: white;
            border: 2px solid #cbd5e1;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .trans-btn:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
            transform: translateY(-1px);
        }

        .trans-btn.primary {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .trans-btn.primary:hover {
            background: #2563eb;
            border-color: #2563eb;
        }

        /* Transactions Table */
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .transactions-table thead {
            background: #f8fafc;
        }

        .transactions-table th {
            padding: 16px 20px;
            text-align: left;
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }

        .transactions-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            color: #334155;
        }

        .transactions-table tr:last-child td {
            border-bottom: none;
        }

        .transactions-table tr:hover {
            background: #f8fafc;
        }

        .trans-id {
            font-weight: 700;
            color: #3b82f6;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }

        .trans-customer {
            font-weight: 600;
            color: #1e293b;
        }

        .trans-phone {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }

        .trans-amount {
            font-weight: 700;
            color: #1e293b;
            font-size: 15px;
        }

        .trans-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .status-paid {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #a7f3d0;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
            border: 2px solid #fde68a;
        }

        .status-voided {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #fecaca;
        }

        .trans-actions {
            display: flex;
            gap: 6px;
        }

        .action-btn {
            background: white;
            border: 2px solid #cbd5e1;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #64748b;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
            transform: translateY(-1px);
        }

        .action-btn.view:hover {
            color: #3b82f6;
            border-color: #3b82f6;
        }

        .action-btn.print:hover {
            color: #10b981;
            border-color: #10b981;
        }

        .action-btn.void:hover {
            color: #dc2626;
            border-color: #dc2626;
        }

        .action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .action-btn:disabled:hover {
            background: white;
            border-color: #cbd5e1;
            color: #64748b;
            transform: none;
        }

        /* ========== MODALS ========== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
            animation: fadeIn 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 450px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: slideUp 0.4s ease;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            border: 2px solid #e2e8f0;
        }

        .modal-header {
            padding: 24px;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }

        .modal-header h3 {
            font-size: 22px;
            font-weight: 800;
            color: #1e293b;
        }

        .modal-close {
            background: #f1f5f9;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 24px;
            color: #64748b;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: #e2e8f0;
            color: #475569;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 24px;
            overflow-y: auto;
            flex: 1;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 8px;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #cbd5e1;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.2s;
            background: white;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .modal-footer {
            padding: 24px;
            border-top: 2px solid #e2e8f0;
            display: flex;
            gap: 16px;
            justify-content: flex-end;
            background: #f8fafc;
        }

        .modal-btn {
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            min-width: 120px;
        }

        .modal-btn.cancel {
            background: #f1f5f9;
            color: #475569;
            border: 2px solid #cbd5e1;
        }

        .modal-btn.cancel:hover {
            background: #e2e8f0;
            transform: translateY(-1px);
        }

        .modal-btn.save {
            background: #3b82f6;
            color: white;
            border: 2px solid #3b82f6;
        }

        .modal-btn.save:hover {
            background: #2563eb;
            border-color: #2563eb;
            transform: translateY(-1px);
        }

        .modal-btn.complete {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
        }

        .modal-btn.complete:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-1px);
        }

        /* ========== ANIMATIONS ========== */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(40px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1400px) {
            .left-panel {
                width: 65%;
            }
            
            .right-panel {
                width: 35%;
            }
        }

        @media (max-width: 1200px) {
            .left-panel {
                width: 60%;
            }
            
            .right-panel {
                width: 40%;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 14px;
            }
        }

        @media (max-width: 1024px) {
            .pos-content-area {
                flex-direction: column;
                height: auto;
                min-height: calc(100vh - 72px);
            }

            .left-panel {
                width: 100%;
                height: 60vh;
                border-right: none;
                border-bottom: 2px solid #e2e8f0;
            }

            .right-panel {
                width: 100%;
                height: 40vh;
                min-width: auto;
            }

            .transactions-table {
                display: block;
                overflow-x: auto;
            }

            .transactions-table thead {
                display: none;
            }

            .transactions-table tbody tr {
                display: block;
                margin-bottom: 16px;
                border: 2px solid #e2e8f0;
                border-radius: 12px;
                padding: 16px;
            }

            .transactions-table td {
                display: block;
                border: none;
                padding: 8px 0;
            }

            .transactions-table td:before {
                content: attr(data-label);
                font-weight: 700;
                color: #64748b;
                margin-right: 12px;
                font-size: 13px;
            }

            .trans-actions {
                justify-content: flex-end;
                margin-top: 12px;
            }
        }

        @media (max-width: 768px) {
            .pos-top-bar {
                padding: 14px 16px;
                flex-direction: column;
                gap: 16px;
                align-items: stretch;
            }

            .pos-title {
                font-size: 18px;
                justify-content: center;
            }

            .pos-top-actions {
                justify-content: center;
                flex-wrap: wrap;
            }

            .pos-action-btn {
                padding: 10px 14px;
                font-size: 13px;
            }

            .customer-section,
            .search-container,
            .categories-container,
            .cart-header,
            .cart-summary,
            .payment-section {
                padding: 16px;
            }

            .customer-row {
                flex-direction: column;
                gap: 12px;
            }

            .add-customer-btn {
                width: 100%;
                justify-content: center;
            }

            .products-grid-container {
                padding: 16px;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
                gap: 12px;
            }

            .product-card {
                padding: 14px;
            }

            .product-image {
                height: 100px;
            }

            .product-name {
                font-size: 14px;
            }

            .product-price {
                font-size: 16px;
            }

            .cart-item {
                padding: 14px;
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .cart-item-image {
                width: 50px;
                height: 50px;
            }

            .cart-item-controls {
                width: 100%;
                justify-content: space-between;
            }

            .payment-methods {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .modal-body {
                padding: 20px;
            }

            .modal-footer {
                padding: 20px;
                flex-direction: column;
            }

            .modal-btn {
                width: 100%;
            }

            .transactions-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .transactions-actions {
                width: 100%;
                justify-content: space-between;
            }

            .trans-btn {
                flex: 1;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .payment-methods {
                grid-template-columns: repeat(2, 1fr);
            }

            .cart-controls {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }

            .vat-toggle {
                align-self: flex-start;
            }
        }

        /* ========== TOAST NOTIFICATION ========== */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            left: 24px;
            max-width: 400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 18px 24px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 16px;
            z-index: 1001;
            animation: slideIn 0.3s ease;
            border-left: 6px solid #3b82f6;
        }

        .toast.success {
            border-left-color: #10b981;
        }

        .toast.error {
            border-left-color: #dc2626;
        }

        .toast.warning {
            border-left-color: #f59e0b;
        }

        .toast.info {
            border-left-color: #3b82f6;
        }

        .toast-icon {
            font-size: 24px;
            flex-shrink: 0;
        }

        .toast.success .toast-icon {
            color: #10b981;
        }

        .toast.error .toast-icon {
            color: #dc2626;
        }

        .toast.warning .toast-icon {
            color: #f59e0b;
        }

        .toast.info .toast-icon {
            color: #3b82f6;
        }

        .toast-message {
            flex: 1;
            font-size: 15px;
            font-weight: 600;
            color: #334155;
        }

        /* ========== LOADING ========== */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .loading.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e2e8f0;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- =============== Navigation ================ -->
    <div class="container">
        <!-- Overlay for closing sidebar on mobile -->
        <div class="overlay" id="overlay"></div>
        
        <?php include 'includes/sidebar.php'; ?>

        <!-- ========================= Main ==================== -->
        <div class="main">
            <?php include 'includes/navigation.php'; ?>

            <!-- ================== POS INTERFACE ============== -->
            <div class="pos-container">
                <!-- Top Bar -->
                <div class="pos-top-bar">
                    <div class="pos-title">
                        <ion-icon name="cart-outline"></ion-icon>
                        Point of Sale System
                    </div>
                    <div class="pos-top-actions">
                        <button class="pos-action-btn clear" onclick="clearCart()">
                            <ion-icon name="trash-outline"></ion-icon>
                            Clear Cart
                        </button>
                        <button class="pos-action-btn hold" onclick="openHoldCartModal()">
                            <ion-icon name="save-outline"></ion-icon>
                            Hold Cart
                        </button>
                        <button class="pos-action-btn load" onclick="loadHeldCart()">
                            <ion-icon name="archive-outline"></ion-icon>
                            Load Cart
                        </button>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="pos-content-area">
                    <!-- Left Panel: Customer & Products -->
                    <div class="left-panel">
                        <!-- Customer Section -->
                        <div class="customer-section">
                            <div class="section-title">
                                <ion-icon name="person-circle-outline"></ion-icon>
                                Customer Information
                            </div>
                            <div class="customer-row">
                                <div class="customer-select-wrapper">
                                    <select class="customer-select" id="customerSelect" onchange="updateCustomer()">
                                        <option value="">Walk-in Customer</option>
                                        <option value="1">John Doe (+265 881 234 567)</option>
                                        <option value="2">Mary Johnson (+265 992 345 678)</option>
                                        <option value="3">Robert Brown (+265 993 456 789)</option>
                                    </select>
                                    <ion-icon name="chevron-down-outline" class="customer-select-arrow"></ion-icon>
                                </div>
                                <button class="add-customer-btn" onclick="openCustomerModal()">
                                    <ion-icon name="person-add-outline"></ion-icon>
                                    Add New Customer
                                </button>
                            </div>
                        </div>

                        <!-- Products Section -->
                        <div class="products-section">
                            <!-- Search -->
                            <div class="search-container">
                                <div class="search-box">
                                    <ion-icon name="search-outline"></ion-icon>
                                    <input type="text" id="productSearch" placeholder="Search products by name or code..." 
                                           oninput="searchProducts()" autocomplete="off">
                                </div>
                            </div>

                            <!-- Categories -->
                            <div class="categories-container">
                                <div class="categories">
                                    <button class="category-btn active" onclick="filterProducts('all')">All Products</button>
                                    <button class="category-btn" onclick="filterProducts('medicines')">Medicines</button>
                                    <button class="category-btn" onclick="filterProducts('equipment')">Equipment</button>
                                    <button class="category-btn" onclick="filterProducts('consumables')">Consumables</button>
                                    <button class="category-btn" onclick="filterProducts('vitamins')">Vitamins</button>
                                    <button class="category-btn" onclick="filterProducts('injections')">Injections</button>
                                    <button class="category-btn" onclick="filterProducts('topicals')">Topicals</button>
                                </div>
                            </div>

                            <!-- Products Grid -->
                            <div class="products-grid-container">
                                <div class="products-grid" id="productsGrid">
                                    <!-- Products will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel: Cart & Payment -->
                    <div class="right-panel">
                        <!-- Cart Header -->
                        <div class="cart-header">
                            <div class="cart-title">
                                <ion-icon name="cart-outline"></ion-icon>
                                Current Sale
                            </div>
                            <div class="cart-controls">
                                <div class="cart-stats">
                                    <div class="cart-items-count" id="itemCount">0 Items</div>
                                    <div class="vat-toggle">
                                        <label class="toggle-label">
                                            <input type="checkbox" id="vatToggle" checked>
                                            <span class="toggle-switch"></span>
                                            <span>Include VAT</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cart Items Container -->
                        <div class="cart-items-container" id="cartItemsContainer">
                            <div class="cart-empty" id="cartEmpty">
                                <ion-icon name="cart-outline"></ion-icon>
                                <h3>Your Cart is Empty</h3>
                                <p>Add products from the left panel to start a new sale</p>
                            </div>
                            <div class="cart-items" id="cartItems" style="display: none;"></div>
                        </div>

                        <!-- Cart Summary -->
                        <div class="cart-summary" id="cartSummary" style="display: none;">
                            <div class="summary-row">
                                <span class="summary-label">Subtotal:</span>
                                <span class="summary-value" id="subtotal">MWK 0.00</span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Discount:</span>
                                <span class="summary-value" id="discount">MWK 0.00</span>
                            </div>
                            <div class="summary-row" id="vatRow" style="display: none;">
                                <span class="summary-label">VAT (16.5%):</span>
                                <span class="summary-value" id="tax">MWK 0.00</span>
                            </div>
                            <div class="summary-row total">
                                <span class="summary-label">Total Amount:</span>
                                <span class="summary-value" id="total">MWK 0.00</span>
                            </div>
                        </div>

                        <!-- Payment Section -->
                        <div class="payment-section" id="paymentSection" style="display: none;">
                            <div class="payment-title">
                                <ion-icon name="card-outline"></ion-icon>
                                Payment Method
                            </div>
                            <div class="payment-methods">
                                <div class="payment-method selected" onclick="selectPayment('cash')">
                                    <div class="payment-icon">
                                        <ion-icon name="cash-outline"></ion-icon>
                                    </div>
                                    <div class="payment-label">Cash</div>
                                </div>
                                <div class="payment-method" onclick="selectPayment('card')">
                                    <div class="payment-icon">
                                        <ion-icon name="card-outline"></ion-icon>
                                    </div>
                                    <div class="payment-label">Card</div>
                                </div>
                                <div class="payment-method" onclick="selectPayment('mobile')">
                                    <div class="payment-icon">
                                        <ion-icon name="phone-portrait-outline"></ion-icon>
                                    </div>
                                    <div class="payment-label">Mobile Money</div>
                                </div>
                                <div class="payment-method" onclick="selectPayment('insurance')">
                                    <div class="payment-icon">
                                        <ion-icon name="medkit-outline"></ion-icon>
                                    </div>
                                    <div class="payment-label">Insurance</div>
                                </div>
                            </div>

                            <!-- Checkout Button -->
                            <button class="checkout-btn" id="checkoutBtn" onclick="processPayment()" disabled>
                                <ion-icon name="checkmark-circle-outline"></ion-icon>
                                Process Payment
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="recent-transactions">
                    <div class="transactions-header">
                        <h2 class="transactions-title">
                            <ion-icon name="receipt-outline"></ion-icon>
                            Recent Transactions
                        </h2>
                        <div class="transactions-actions">
                            <button class="trans-btn" onclick="addSampleTransactions()">
                                <ion-icon name="add-outline"></ion-icon>
                                Add Sample Data
                            </button>
                            <button class="trans-btn" onclick="loadTransactions()">
                                <ion-icon name="refresh-outline"></ion-icon>
                                Refresh
                            </button>
                            <button class="trans-btn primary" onclick="viewAllTransactions()">
                                <ion-icon name="list-outline"></ion-icon>
                                View All
                            </button>
                        </div>
                    </div>
                    <div class="transactions-table-container">
                        <table class="transactions-table">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="transactionsTableBody">
                                <!-- Transactions will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Customer Modal -->
            <div class="modal-overlay" id="customerModal">
                <div class="modal">
                    <div class="modal-header">
                        <h3>Add New Customer</h3>
                        <button class="modal-close" onclick="closeCustomerModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form class="simple-form" id="customerForm" onsubmit="event.preventDefault(); saveCustomer();">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" id="customerName" placeholder="Enter customer's full name" required>
                            </div>
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="tel" id="customerPhone" placeholder="+265 123 456 789" required>
                            </div>
                            <div class="form-group">
                                <label>Email Address (Optional)</label>
                                <input type="email" id="customerEmail" placeholder="customer@example.com">
                            </div>
                            <div class="form-group">
                                <label>Address (Optional)</label>
                                <input type="text" id="customerAddress" placeholder="Enter address">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="modal-btn cancel" onclick="closeCustomerModal()">
                            Cancel
                        </button>
                        <button type="button" class="modal-btn save" onclick="saveCustomer()">
                            <ion-icon name="save-outline" style="margin-right: 8px;"></ion-icon>
                            Save Customer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payment Modal -->
            <div class="modal-overlay" id="paymentModal">
                <div class="modal">
                    <div class="modal-header">
                        <h3>Complete Payment</h3>
                        <button class="modal-close" onclick="closePaymentModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div style="text-align: center; margin-bottom: 24px;">
                            <div style="font-size: 14px; color: #64748b; margin-bottom: 8px; font-weight: 600;">Total Amount Due</div>
                            <div style="font-size: 36px; font-weight: 800; color: #3b82f6;" id="paymentTotal">MWK 0.00</div>
                            <div style="font-size: 13px; color: #94a3b8; margin-top: 8px;">Payment Method: <span id="paymentMethodDisplay" style="font-weight: 600; color: #475569;">Cash</span></div>
                        </div>
                        
                        <form class="simple-form" id="paymentForm" onsubmit="event.preventDefault(); completeSale();">
                            <div class="form-group">
                                <label>Amount Received *</label>
                                <input type="number" id="amountReceived" placeholder="0.00" 
                                       oninput="calculateChange()" required min="0" step="0.01">
                            </div>
                            
                            <div class="form-group">
                                <label>Change Due</label>
                                <input type="text" id="changeDue" placeholder="0.00" readonly 
                                       style="background: #f8fafc; font-weight: 700; font-size: 18px;">
                            </div>
                            
                            <div class="form-group">
                                <label>Payment Reference (Optional)</label>
                                <input type="text" id="paymentRef" placeholder="Enter reference number">
                            </div>
                            
                            <div class="form-group">
                                <label>Notes (Optional)</label>
                                <input type="text" id="paymentNotes" placeholder="Add any notes...">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="modal-btn cancel" onclick="closePaymentModal()">
                            Cancel
                        </button>
                        <button type="button" class="modal-btn complete" onclick="completeSale()">
                            <ion-icon name="checkmark-outline" style="margin-right: 8px;"></ion-icon>
                            Complete Sale
                        </button>
                    </div>
                </div>
            </div>

            <!-- Void Transaction Modal -->
            <div class="modal-overlay" id="voidModal">
                <div class="modal">
                    <div class="modal-header">
                        <h3>Void Transaction</h3>
                        <button class="modal-close" onclick="closeVoidModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div style="background: #fef3c7; padding: 16px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #f59e0b;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                <ion-icon name="warning-outline" style="color: #f59e0b; font-size: 20px;"></ion-icon>
                                <div style="font-weight: 700; color: #92400e;">Important Notice</div>
                            </div>
                            <div style="font-size: 14px; color: #92400e; line-height: 1.5;">
                                Voiding a transaction cannot be undone. This action will mark the transaction as voided in all records.
                            </div>
                        </div>
                        
                        <form class="simple-form" id="voidForm" onsubmit="event.preventDefault(); confirmVoid();">
                            <div class="form-group">
                                <label>Transaction ID</label>
                                <input type="text" id="voidTransId" readonly style="background: #f8fafc; font-weight: 700;">
                            </div>
                            <div class="form-group">
                                <label>Amount</label>
                                <input type="text" id="voidAmount" readonly style="background: #f8fafc; font-weight: 700;">
                            </div>
                            <div class="form-group">
                                <label>Customer</label>
                                <input type="text" id="voidCustomer" readonly style="background: #f8fafc;">
                            </div>
                            <div class="form-group">
                                <label>Reason for Void *</label>
                                <select id="voidReason" required style="padding: 14px 16px;">
                                    <option value="">Select a reason</option>
                                    <option value="Customer cancelled">Customer cancelled</option>
                                    <option value="Wrong amount charged">Wrong amount charged</option>
                                    <option value="System error">System error</option>
                                    <option value="Duplicate transaction">Duplicate transaction</option>
                                    <option value="Item not available">Item not available</option>
                                    <option value="Other">Other reason</option>
                                </select>
                            </div>
                            <div class="form-group" id="customReasonGroup" style="display: none;">
                                <label>Please specify reason</label>
                                <input type="text" id="customReason" placeholder="Enter reason for voiding...">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="modal-btn cancel" onclick="closeVoidModal()">
                            Cancel
                        </button>
                        <button type="button" class="modal-btn save" onclick="confirmVoid()" style="background: linear-gradient(135deg, #dc2626, #b91c1c);">
                            <ion-icon name="ban-outline" style="margin-right: 8px;"></ion-icon>
                            Void Transaction
                        </button>
                    </div>
                </div>
            </div>

            <!-- Transaction Details Modal -->
            <div class="modal-overlay" id="transactionDetailsModal">
                <div class="modal" style="max-width: 550px;">
                    <div class="modal-header">
                        <h3>Transaction Details</h3>
                        <button class="modal-close" onclick="closeTransactionDetailsModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="transactionDetailsContent">
                            <!-- Transaction details will be loaded here -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="modal-btn cancel" onclick="closeTransactionDetailsModal()">
                            Close
                        </button>
                        <button type="button" class="modal-btn primary" onclick="printTransaction()">
                            <ion-icon name="print-outline"></ion-icon>
                            Print Receipt
                        </button>
                    </div>
                </div>
            </div>

            <!-- Toast Notification -->
            <div class="toast" id="toast" style="display: none;"></div>

            <!-- Loading Overlay -->
            <div class="loading" id="loading">
                <div class="spinner"></div>
            </div>
        </div>

        <script>
            // Products Data with VAT information
            const products = [
                {
                    id: 1,
                    name: "Paracetamol 500mg",
                    code: "MED-001",
                    price: 12500,
                    category: "medicines",
                    stock: 45,
                    includesVat: true
                },
                {
                    id: 2,
                    name: "Ibuprofen 400mg",
                    code: "MED-002",
                    price: 15100,
                    category: "medicines",
                    stock: 32,
                    includesVat: true
                },
                {
                    id: 3,
                    name: "Amoxicillin 500mg",
                    code: "MED-003",
                    price: 26000,
                    category: "medicines",
                    stock: 18,
                    includesVat: true
                },
                {
                    id: 4,
                    name: "Blood Pressure Monitor",
                    code: "EQU-001",
                    price: 8500,
                    category: "equipment",
                    stock: 8,
                    includesVat: true
                },
                {
                    id: 5,
                    name: "Digital Thermometer",
                    code: "EQU-002",
                    price: 1200,
                    category: "equipment",
                    stock: 15,
                    includesVat: false
                },
                {
                    id: 6,
                    name: "Surgical Masks (50pcs)",
                    code: "CON-001",
                    price: 800,
                    category: "consumables",
                    stock: 120,
                    includesVat: true
                },
                {
                    id: 7,
                    name: "Medical Gloves (100pcs)",
                    code: "CON-002",
                    price: 1500,
                    category: "consumables",
                    stock: 85,
                    includesVat: false
                },
                {
                    id: 8,
                    name: "Vitamin C 1000mg",
                    code: "VIT-001",
                    price: 18200,
                    category: "vitamins",
                    stock: 60,
                    includesVat: true
                },
                {
                    id: 9,
                    name: "Multivitamin Complex",
                    code: "VIT-002",
                    price: 500,
                    category: "vitamins",
                    stock: 42,
                    includesVat: true
                },
                {
                    id: 10,
                    name: "Insulin Injection",
                    code: "INJ-001",
                    price: 1200,
                    category: "injections",
                    stock: 25,
                    includesVat: true
                },
                {
                    id: 11,
                    name: "Antiseptic Cream",
                    code: "TOP-001",
                    price: 350,
                    category: "topicals",
                    stock: 38,
                    includesVat: true
                },
                {
                    id: 12,
                    name: "Pain Relief Gel",
                    code: "TOP-002",
                    price: 280,
                    category: "topicals",
                    stock: 52,
                    includesVat: true
                }
            ];

            // Cart Data
            let cart = [];
            let currentCustomer = null;
            let paymentMethod = 'cash';
            let discount = 0;
            let includeVat = true;
            const VAT_RATE = 0.165; // 16.5%
            let transactions = JSON.parse(localStorage.getItem('posTransactions')) || [];
            let currentTransactionId = null;

            // Initialize
            document.addEventListener('DOMContentLoaded', function() {
                renderProducts();
                updateCartDisplay();
                selectPayment('cash');
                focusSearch();
                loadTransactions();
                setupEventListeners();
            });

            // Setup Event Listeners
            function setupEventListeners() {
                // VAT toggle
                document.getElementById('vatToggle').addEventListener('change', function() {
                    includeVat = this.checked;
                    updateCartDisplay();
                });

                // Void reason change
                const voidReason = document.getElementById('voidReason');
                if (voidReason) {
                    voidReason.addEventListener('change', function() {
                        const customReasonGroup = document.getElementById('customReasonGroup');
                        if (this.value === 'Other') {
                            customReasonGroup.style.display = 'block';
                        } else {
                            customReasonGroup.style.display = 'none';
                        }
                    });
                }

                // Close modals on outside click
                document.querySelectorAll('.modal-overlay').forEach(overlay => {
                    overlay.addEventListener('click', function(e) {
                        if (e.target === this) {
                            this.classList.remove('active');
                        }
                    });
                });

                // Keyboard shortcuts
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeAllModals();
                    }
                    if (e.key === 'Enter' && e.target.id === 'productSearch') {
                        e.preventDefault();
                        searchProducts();
                    }
                    if (e.key === 'F1') {
                        e.preventDefault();
                        focusSearch();
                    }
                    if (e.key === 'F2' && !document.getElementById('paymentModal').classList.contains('active')) {
                        e.preventDefault();
                        processPayment();
                    }
                    if (e.key === 'F3') {
                        e.preventDefault();
                        clearCart();
                    }
                    if (e.key === 'F5') {
                        e.preventDefault();
                        loadTransactions();
                    }
                });
            }

            // Close all modals
            function closeAllModals() {
                document.getElementById('customerModal').classList.remove('active');
                document.getElementById('paymentModal').classList.remove('active');
                document.getElementById('voidModal').classList.remove('active');
                document.getElementById('transactionDetailsModal').classList.remove('active');
            }

            // Focus search on load
            function focusSearch() {
                setTimeout(() => {
                    document.getElementById('productSearch').focus();
                }, 100);
            }

            // Render Products
            function renderProducts(filteredProducts = products) {
                const grid = document.getElementById('productsGrid');
                
                if (filteredProducts.length === 0) {
                    grid.innerHTML = `
                        <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #94a3b8;">
                            <ion-icon name="search-outline" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></ion-icon>
                            <div style="font-size: 18px; font-weight: 700; margin-bottom: 10px; color: #64748b;">No products found</div>
                            <div style="font-size: 15px;">Try a different search term or category</div>
                        </div>
                    `;
                    return;
                }
                
                let html = '';
                filteredProducts.forEach(product => {
                    const stockClass = product.stock > 20 ? 'stock-in' : 
                                      product.stock > 0 ? 'stock-low' : 'stock-out';
                    const stockText = product.stock > 20 ? 'In Stock' : 
                                     product.stock > 0 ? 'Low Stock' : 'Out of Stock';
                    
                    html += `
                        <div class="product-card" onclick="addToCart(${product.id})" data-id="${product.id}">
                            <div class="product-image">
                                <ion-icon name="medical-outline"></ion-icon>
                            </div>
                            <div class="product-info">
                                <div class="product-name">${product.name}</div>
                                <div class="product-code">${product.code}</div>
                                <div class="product-price">MWK ${product.price.toLocaleString()}</div>
                                <div class="product-footer">
                                    <div class="product-stock ${stockClass}">
                                        ${stockText}
                                    </div>
                                    <div class="product-vat ${product.includesVat ? 'vat-included' : 'vat-excluded'}">
                                        ${product.includesVat ? 'VAT Inc' : 'VAT Exc'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                grid.innerHTML = html;
            }

            // Search Products
            function searchProducts() {
                const searchTerm = document.getElementById('productSearch').value.toLowerCase();
                
                if (!searchTerm) {
                    renderProducts();
                    return;
                }
                
                const filtered = products.filter(product => 
                    product.name.toLowerCase().includes(searchTerm) ||
                    product.code.toLowerCase().includes(searchTerm)
                );
                
                renderProducts(filtered);
            }

            // Filter Products
            function filterProducts(category) {
                document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
                event.target.classList.add('active');
                
                if (category === 'all') {
                    renderProducts();
                    return;
                }
                
                const filtered = products.filter(product => product.category === category);
                renderProducts(filtered);
            }

            // Add to Cart
            function addToCart(productId) {
                const product = products.find(p => p.id === productId);
                if (!product) return;
                
                if (product.stock <= 0) {
                    showToast(`${product.name} is out of stock!`, 'error');
                    return;
                }
                
                const existingItem = cart.find(item => item.id === productId);
                
                if (existingItem) {
                    if (existingItem.quantity >= product.stock) {
                        showToast(`Only ${product.stock} items available!`, 'warning');
                        return;
                    }
                    existingItem.quantity++;
                } else {
                    cart.push({
                        id: product.id,
                        name: product.name,
                        price: product.price,
                        includesVat: product.includesVat,
                        quantity: 1
                    });
                }
                
                updateCartDisplay();
                showToast(`${product.name} added to cart`, 'success');
                
                // Visual feedback
                const productCard = document.querySelector(`[data-id="${productId}"]`);
                if (productCard) {
                    productCard.style.borderColor = '#10b981';
                    productCard.style.boxShadow = '0 8px 30px rgba(16, 185, 129, 0.2)';
                    productCard.style.transform = 'translateY(-4px)';
                    setTimeout(() => {
                        productCard.style.borderColor = '';
                        productCard.style.boxShadow = '';
                        productCard.style.transform = '';
                    }, 500);
                }
            }

            // Calculate totals based on VAT setting
            function calculateTotals() {
                const subtotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
                
                let vat = 0;
                let total = subtotal;
                
                if (includeVat) {
                    // Calculate VAT for items that include VAT in their price
                    const vatableAmount = cart.reduce((total, item) => {
                        return item.includesVat ? total + (item.price * item.quantity) : total;
                    }, 0);
                    
                    vat = vatableAmount * VAT_RATE;
                    total = subtotal; // Price already includes VAT
                }
                
                return { subtotal, vat, total };
            }

            // Update Cart Display
            function updateCartDisplay() {
                const cartEmpty = document.getElementById('cartEmpty');
                const cartItems = document.getElementById('cartItems');
                const cartSummary = document.getElementById('cartSummary');
                const paymentSection = document.getElementById('paymentSection');
                
                if (cart.length === 0) {
                    cartEmpty.style.display = 'flex';
                    cartItems.style.display = 'none';
                    cartSummary.style.display = 'none';
                    paymentSection.style.display = 'none';
                    document.getElementById('checkoutBtn').disabled = true;
                    document.getElementById('itemCount').textContent = '0 Items';
                    return;
                }
                
                const totalItems = cart.reduce((t, i) => t + i.quantity, 0);
                const totalText = totalItems === 1 ? '1 Item' : `${totalItems} Items`;
                
                cartEmpty.style.display = 'none';
                cartItems.style.display = 'block';
                cartSummary.style.display = 'block';
                paymentSection.style.display = 'block';
                document.getElementById('checkoutBtn').disabled = false;
                document.getElementById('itemCount').textContent = totalText;
                
                // Render cart items
                let html = '';
                cart.forEach((item, index) => {
                    const itemTotal = item.price * item.quantity;
                    html += `
                        <div class="cart-item">
                            <div class="cart-item-image">
                                <ion-icon name="medical-outline"></ion-icon>
                            </div>
                            <div class="cart-item-details">
                                <div class="cart-item-name">${item.name}</div>
                                <div class="cart-item-price-row">
                                    <span class="cart-item-price">MWK ${item.price.toLocaleString()} each</span>
                                    <span class="cart-item-vat ${item.includesVat ? 'vat-inc' : 'vat-exc'}">
                                        ${item.includesVat ? 'VAT Inc' : 'VAT Exc'}
                                    </span>
                                </div>
                            </div>
                            <div class="cart-item-controls">
                                <div class="quantity-control">
                                    <button class="qty-btn" onclick="updateQuantity(${index}, -1)">-</button>
                                    <input type="number" class="qty-input" value="${item.quantity}" min="1" 
                                           onchange="changeQuantity(${index}, this.value)">
                                    <button class="qty-btn" onclick="updateQuantity(${index}, 1)">+</button>
                                </div>
                                <div class="cart-item-total">MWK ${itemTotal.toLocaleString()}</div>
                                <button class="remove-item" onclick="removeFromCart(${index})">
                                    <ion-icon name="trash-outline"></ion-icon>
                                </button>
                            </div>
                        </div>
                    `;
                });
                
                cartItems.innerHTML = html;
                updateTotals();
            }

            // Cart Functions
            function updateQuantity(index, change) {
                const productId = cart[index].id;
                const product = products.find(p => p.id === productId);
                const newQty = cart[index].quantity + change;
                
                if (newQty < 1) {
                    removeFromCart(index);
                    return;
                }
                
                if (newQty > product.stock) {
                    showToast(`Only ${product.stock} items available!`, 'warning');
                    return;
                }
                
                cart[index].quantity = newQty;
                updateCartDisplay();
            }

            function changeQuantity(index, newValue) {
                const productId = cart[index].id;
                const product = products.find(p => p.id === productId);
                const newQty = parseInt(newValue) || 1;
                
                if (newQty < 1) {
                    removeFromCart(index);
                    return;
                }
                
                if (newQty > product.stock) {
                    showToast(`Only ${product.stock} items available!`, 'warning');
                    cart[index].quantity = product.stock;
                } else {
                    cart[index].quantity = newQty;
                }
                
                updateCartDisplay();
            }

            function removeFromCart(index) {
                const itemName = cart[index].name;
                cart.splice(index, 1);
                updateCartDisplay();
                showToast(`${itemName} removed from cart`, 'info');
            }

            function clearCart() {
                if (cart.length === 0) {
                    showToast('Cart is already empty', 'info');
                    return;
                }
                
                if (confirm('Are you sure you want to clear the cart?')) {
                    cart = [];
                    discount = 0;
                    updateCartDisplay();
                    showToast('Cart cleared successfully', 'success');
                }
            }

            // Update Totals
            function updateTotals() {
                const { subtotal, vat, total } = calculateTotals();
                
                const finalTotal = total - discount;
                
                document.getElementById('subtotal').textContent = `MWK ${subtotal.toLocaleString()}`;
                document.getElementById('discount').textContent = `MWK ${discount.toLocaleString()}`;
                
                // Show or hide VAT based on setting
                const vatRow = document.getElementById('vatRow');
                if (includeVat && vat > 0) {
                    document.getElementById('tax').textContent = `MWK ${vat.toLocaleString()}`;
                    vatRow.style.display = 'flex';
                } else {
                    vatRow.style.display = 'none';
                }
                
                document.getElementById('total').textContent = `MWK ${finalTotal.toLocaleString()}`;
                
                // Update payment modal total
                const paymentTotal = document.getElementById('paymentTotal');
                if (paymentTotal) {
                    paymentTotal.textContent = `MWK ${finalTotal.toLocaleString()}`;
                }
            }

            // Customer Functions
            function openCustomerModal() {
                document.getElementById('customerModal').classList.add('active');
                setTimeout(() => document.getElementById('customerName').focus(), 100);
            }

            function closeCustomerModal() {
                document.getElementById('customerModal').classList.remove('active');
                document.getElementById('customerForm').reset();
            }

            function saveCustomer() {
                const name = document.getElementById('customerName').value.trim();
                const phone = document.getElementById('customerPhone').value.trim();
                
                if (!name || !phone) {
                    showToast('Please enter name and phone number', 'warning');
                    return;
                }
                
                const select = document.getElementById('customerSelect');
                const option = document.createElement('option');
                const customerId = Date.now().toString();
                option.value = customerId;
                option.textContent = `${name} (${phone})`;
                select.appendChild(option);
                select.value = customerId;
                
                currentCustomer = { id: customerId, name: name, phone: phone };
                closeCustomerModal();
                showToast('Customer added successfully', 'success');
            }

            function updateCustomer() {
                const select = document.getElementById('customerSelect');
                currentCustomer = select.value ? { 
                    id: select.value, 
                    name: select.options[select.selectedIndex].text 
                } : null;
            }

            // Payment Functions
            function selectPayment(method) {
                paymentMethod = method;
                document.querySelectorAll('.payment-method').forEach(opt => opt.classList.remove('selected'));
                event.target.closest('.payment-method').classList.add('selected');
            }

            function processPayment() {
                if (cart.length === 0) {
                    showToast('Cart is empty!', 'warning');
                    return;
                }
                
                const { total } = calculateTotals();
                const finalTotal = total - discount;
                
                document.getElementById('paymentTotal').textContent = `MWK ${finalTotal.toLocaleString()}`;
                document.getElementById('paymentMethodDisplay').textContent = paymentMethod.charAt(0).toUpperCase() + paymentMethod.slice(1);
                document.getElementById('amountReceived').value = '';
                document.getElementById('changeDue').value = '0.00';
                document.getElementById('paymentRef').value = '';
                document.getElementById('paymentNotes').value = '';
                
                document.getElementById('paymentModal').classList.add('active');
                setTimeout(() => document.getElementById('amountReceived').focus(), 100);
            }

            function calculateChange() {
                const { total } = calculateTotals();
                const finalTotal = total - discount;
                const received = parseFloat(document.getElementById('amountReceived').value) || 0;
                const change = received - finalTotal;
                
                const changeInput = document.getElementById('changeDue');
                changeInput.value = change >= 0 ? `MWK ${change.toLocaleString()}` : 'MWK 0.00';
                changeInput.style.color = change >= 0 ? '#10b981' : '#dc2626';
            }

            function closePaymentModal() {
                document.getElementById('paymentModal').classList.remove('active');
                document.getElementById('paymentForm').reset();
            }

            // Generate Transaction ID
            function generateTransactionId() {
                const timestamp = Date.now().toString(36);
                const random = Math.random().toString(36).substr(2, 5);
                return `TXN-${timestamp}-${random}`.toUpperCase();
            }

            function completeSale() {
                const { subtotal, vat, total } = calculateTotals();
                const finalTotal = total - discount;
                const received = parseFloat(document.getElementById('amountReceived').value) || 0;
                
                if (received < finalTotal && paymentMethod === 'cash') {
                    const shortfall = finalTotal - received;
                    showToast(`Insufficient payment! Short: MWK ${shortfall.toLocaleString()}`, 'error');
                    return;
                }
                
                // Generate transaction ID
                currentTransactionId = generateTransactionId();
                
                // Create transaction object
                const transaction = {
                    id: currentTransactionId,
                    invoiceNumber: `INV-${Date.now().toString().slice(-6)}`,
                    customer: document.getElementById('customerSelect').value ? 
                        document.getElementById('customerSelect').options[document.getElementById('customerSelect').selectedIndex].text : 
                        'Walk-in Customer',
                    amount: finalTotal,
                    date: new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }),
                    time: new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }),
                    paymentMethod: paymentMethod,
                    items: [...cart],
                    subtotal: subtotal,
                    discount: discount,
                    vat: vat,
                    change: paymentMethod === 'cash' ? received - finalTotal : 0,
                    amountTendered: paymentMethod === 'cash' ? received : 0,
                    status: 'completed',
                    voided: false,
                    voidReason: '',
                    voidDate: null,
                    includeVat: includeVat,
                    notes: document.getElementById('paymentNotes').value
                };
                
                // Save transaction
                saveTransaction(transaction);
                
                // Clear cart
                cart = [];
                discount = 0;
                
                // Update display
                updateCartDisplay();
                closePaymentModal();
                
                // Load updated transactions
                loadTransactions();
                
                // Reset customer
                document.getElementById('customerSelect').value = '';
                currentCustomer = null;
                
                // Print receipt
                printReceipt(transaction);
                
                showToast('Sale completed successfully! Receipt printed.', 'success');
            }

            // Transaction Functions
            function saveTransaction(transaction) {
                let transactions = JSON.parse(localStorage.getItem('posTransactions')) || [];
                transactions.unshift(transaction); // Add to beginning of array
                // Keep only last 50 transactions
                if (transactions.length > 50) {
                    transactions = transactions.slice(0, 50);
                }
                localStorage.setItem('posTransactions', JSON.stringify(transactions));
            }

            function loadTransactions() {
                transactions = JSON.parse(localStorage.getItem('posTransactions')) || [];
                const tbody = document.getElementById('transactionsTableBody');
                
                if (transactions.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 60px 20px; color: #94a3b8;">
                                <ion-icon name="receipt-outline" style="font-size: 48px; margin-bottom: 20px; display: block; opacity: 0.5;"></ion-icon>
                                <div style="font-size: 18px; font-weight: 700; margin-bottom: 10px; color: #64748b;">No transactions yet</div>
                                <div style="font-size: 15px; max-width: 400px; margin: 0 auto; line-height: 1.5;">
                                    Complete your first sale to see transactions here.<br>
                                    Or click "Add Sample Data" to test the void functionality.
                                </div>
                            </td>
                        </tr>
                    `;
                    return;
                }
                
                // Get recent 5 transactions
                const recentTransactions = transactions.slice(0, 5);
                
                let html = '';
                recentTransactions.forEach(transaction => {
                    const statusClass = transaction.voided ? 'status-voided' : 
                                      transaction.status === 'completed' ? 'status-paid' : 'status-pending';
                    const statusText = transaction.voided ? 'Voided' : 
                                     transaction.status === 'completed' ? 'Completed' : 'Pending';
                    
                    // Extract customer name from the option text
                    const customerText = transaction.customer;
                    const customerName = customerText.split('(')[0].trim();
                    const phoneMatch = customerText.match(/\(([^)]+)\)/);
                    const customerPhone = phoneMatch ? phoneMatch[1] : 'No phone';
                    
                    html += `
                        <tr>
                            <td class="trans-id" data-label="Transaction ID">${transaction.id}</td>
                            <td data-label="Customer">
                                <div class="trans-customer">${customerName}</div>
                                <div class="trans-phone">${customerPhone}</div>
                            </td>
                            <td class="trans-amount" data-label="Amount">MWK ${transaction.amount.toLocaleString()}</td>
                            <td data-label="Date">${transaction.date}</td>
                            <td data-label="Status">
                                <span class="trans-status ${statusClass}">${statusText}</span>
                            </td>
                            <td data-label="Actions">
                                <div class="trans-actions">
                                    <button class="action-btn view" onclick="viewTransaction('${transaction.id}')" title="View Details">
                                        <ion-icon name="eye-outline"></ion-icon>
                                    </button>
                                    <button class="action-btn print" onclick="printTransaction('${transaction.id}')" 
                                            ${transaction.voided ? 'disabled' : ''} title="Print Receipt">
                                        <ion-icon name="print-outline"></ion-icon>
                                    </button>
                                    <button class="action-btn void" onclick="openVoidModal('${transaction.id}')" 
                                            ${transaction.voided ? 'disabled' : ''} title="Void Transaction">
                                        <ion-icon name="ban-outline"></ion-icon>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                
                tbody.innerHTML = html;
            }

            // View Transaction Details
            function viewTransaction(transactionId) {
                const transaction = transactions.find(t => t.id === transactionId);
                if (!transaction) {
                    showToast('Transaction not found!', 'error');
                    return;
                }
                
                const itemsHtml = transaction.items.map(item => `
                    <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e2e8f0;">
                        <div>
                            <div style="font-weight: 700; color: #1e293b;">${item.name}</div>
                            <div style="font-size: 13px; color: #64748b; margin-top: 4px;">
                                MWK ${item.price.toLocaleString()} × ${item.quantity} 
                                <span style="margin-left: 8px; padding: 2px 6px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #f1f5f9;">
                                    ${item.includesVat ? 'VAT Inc' : 'VAT Exc'}
                                </span>
                            </div>
                        </div>
                        <div style="font-weight: 800; color: #3b82f6; font-size: 16px;">MWK ${(item.price * item.quantity).toLocaleString()}</div>
                    </div>
                `).join('');
                
                const voidInfoHtml = transaction.voided ? `
                    <div style="background: linear-gradient(135deg, #fee2e2, #fecaca); padding: 20px; border-radius: 12px; margin-bottom: 20px; border-left: 6px solid #dc2626;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                            <ion-icon name="warning-outline" style="color: #dc2626; font-size: 24px;"></ion-icon>
                            <div style="font-weight: 800; color: #991b1b; font-size: 18px;">VOIDED TRANSACTION</div>
                        </div>
                        <div style="font-size: 14px; color: #991b1b; line-height: 1.6;">
                            <div><strong>Reason:</strong> ${transaction.voidReason}</div>
                            <div><strong>Voided on:</strong> ${transaction.voidDate || 'N/A'}</div>
                        </div>
                    </div>
                ` : '';
                
                const vatInfoHtml = transaction.includeVat ? `
                    <div class="summary-row">
                        <span class="summary-label">VAT (16.5%):</span>
                        <span class="summary-value">MWK ${transaction.vat.toLocaleString()}</span>
                    </div>
                ` : '';
                
                // Extract customer info
                const customerText = transaction.customer;
                const customerName = customerText.split('(')[0].trim();
                const phoneMatch = customerText.match(/\(([^)]+)\)/);
                const customerPhone = phoneMatch ? phoneMatch[1] : 'No phone number';
                
                document.getElementById('transactionDetailsContent').innerHTML = `
                    ${voidInfoHtml}
                    <div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 24px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 16px;">
                            <div>
                                <div style="font-size: 13px; color: #64748b; font-weight: 600; margin-bottom: 4px;">Transaction ID</div>
                                <div style="font-weight: 800; color: #3b82f6; font-size: 18px;">${transaction.id}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 13px; color: #64748b; font-weight: 600; margin-bottom: 4px;">Invoice Number</div>
                                <div style="font-weight: 800; color: #1e293b;">${transaction.invoiceNumber}</div>
                            </div>
                        </div>
                        <div style="font-size: 14px; color: #64748b; font-weight: 600;">
                            <ion-icon name="calendar-outline" style="margin-right: 6px;"></ion-icon>
                            ${transaction.date} • ${transaction.time}
                        </div>
                    </div>
                    
                    <div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 24px;">
                        <div style="font-weight: 700; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 10px; font-size: 16px;">
                            <ion-icon name="person-circle-outline"></ion-icon>
                            Customer Information
                        </div>
                        <div style="font-size: 18px; font-weight: 800; color: #334155; margin-bottom: 8px;">${customerName}</div>
                        <div style="font-size: 14px; color: #64748b; display: flex; align-items: center; gap: 8px;">
                            <ion-icon name="call-outline" style="font-size: 16px;"></ion-icon>
                            ${customerPhone}
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 24px;">
                        <div style="font-weight: 700; color: #1e293b; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; font-size: 16px;">
                            <ion-icon name="cart-outline"></ion-icon>
                            Items (${transaction.items.length})
                        </div>
                        ${itemsHtml}
                    </div>
                    
                    <div style="background: #f8fafc; padding: 24px; border-radius: 12px;">
                        <div class="summary-row">
                            <span class="summary-label">Subtotal:</span>
                            <span class="summary-value">MWK ${transaction.subtotal.toLocaleString()}</span>
                        </div>
                        ${transaction.discount > 0 ? `
                        <div class="summary-row">
                            <span class="summary-label">Discount:</span>
                            <span class="summary-value">- MWK ${transaction.discount.toLocaleString()}</span>
                        </div>
                        ` : ''}
                        ${vatInfoHtml}
                        <div class="summary-row" style="font-size: 24px; font-weight: 800; color: #3b82f6; margin: 16px 0; padding-top: 16px; border-top: 2px solid #e2e8f0;">
                            <span class="summary-label">Total Amount:</span>
                            <span class="summary-value">MWK ${transaction.amount.toLocaleString()}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Payment Method:</span>
                            <span class="summary-value" style="text-transform: capitalize; font-weight: 800;">${transaction.paymentMethod}</span>
                        </div>
                        ${transaction.paymentMethod === 'cash' ? `
                        <div class="summary-row">
                            <span class="summary-label">Amount Tendered:</span>
                            <span class="summary-value">MWK ${transaction.amountTendered.toLocaleString()}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Change Given:</span>
                            <span class="summary-value">MWK ${transaction.change.toLocaleString()}</span>
                        </div>
                        ` : ''}
                        ${transaction.notes ? `
                        <div class="summary-row" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                            <span class="summary-label">Notes:</span>
                            <span class="summary-value" style="font-weight: 500; font-style: italic;">${transaction.notes}</span>
                        </div>
                        ` : ''}
                        <div class="summary-row" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                            <span class="summary-label" style="font-size: 13px; color: #64748b;">VAT Status:</span>
                            <span class="summary-value" style="font-size: 13px; color: #64748b; font-weight: 600;">
                                ${transaction.includeVat ? 'Included in Prices' : 'Not Applied'}
                            </span>
                        </div>
                    </div>
                `;
                
                currentTransactionId = transactionId;
                document.getElementById('transactionDetailsModal').classList.add('active');
            }

            function closeTransactionDetailsModal() {
                document.getElementById('transactionDetailsModal').classList.remove('active');
            }

            // Void Transaction
            function openVoidModal(transactionId) {
                const transaction = transactions.find(t => t.id === transactionId);
                if (!transaction) {
                    showToast('Transaction not found!', 'error');
                    return;
                }
                
                currentTransactionId = transactionId;
                document.getElementById('voidTransId').value = transactionId;
                document.getElementById('voidAmount').value = `MWK ${transaction.amount.toLocaleString()}`;
                document.getElementById('voidCustomer').value = transaction.customer;
                document.getElementById('voidReason').value = '';
                document.getElementById('customReasonGroup').style.display = 'none';
                document.getElementById('customReason').value = '';
                
                document.getElementById('voidModal').classList.add('active');
            }

            function closeVoidModal() {
                document.getElementById('voidModal').classList.remove('active');
                document.getElementById('voidForm').reset();
                currentTransactionId = null;
            }

            function confirmVoid() {
                const reason = document.getElementById('voidReason').value;
                const customReason = document.getElementById('customReason').value;
                const finalReason = reason === 'Other' ? customReason : reason;
                
                if (!finalReason.trim()) {
                    showToast('Please enter a reason for voiding', 'warning');
                    return;
                }
                
                if (!confirm('Are you sure you want to void this transaction? This action cannot be undone.')) {
                    return;
                }
                
                const success = voidTransaction(currentTransactionId, finalReason);
                if (success) {
                    closeVoidModal();
                    showToast('Transaction voided successfully', 'success');
                    loadTransactions();
                }
            }

            function voidTransaction(transactionId, reason = '') {
                const transactionIndex = transactions.findIndex(t => t.id === transactionId);
                
                if (transactionIndex !== -1) {
                    transactions[transactionIndex].status = 'voided';
                    transactions[transactionIndex].voided = true;
                    transactions[transactionIndex].voidReason = reason;
                    transactions[transactionIndex].voidDate = new Date().toLocaleString('en-GB', { 
                        day: '2-digit', 
                        month: 'short', 
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    localStorage.setItem('posTransactions', JSON.stringify(transactions));
                    
                    return true;
                }
                return false;
            }

            // Print Transaction
            function printTransaction(transactionId = null) {
                const id = transactionId || currentTransactionId;
                const transaction = transactions.find(t => t.id === id);
                
                if (transaction) {
                    showLoading();
                    
                    setTimeout(() => {
                        const printContent = `
                            <html>
                                <head>
                                    <title>Receipt ${transaction.id}</title>
                                    <style>
                                        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
                                        body { 
                                            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; 
                                            padding: 20px; 
                                            max-width: 300px; 
                                            margin: 0 auto; 
                                            background: white;
                                        }
                                        .receipt-header { 
                                            text-align: center; 
                                            margin-bottom: 20px; 
                                            padding-bottom: 15px;
                                            border-bottom: 2px dashed #e2e8f0;
                                        }
                                        .receipt-title { 
                                            font-size: 20px; 
                                            font-weight: 800; 
                                            color: #1e293b; 
                                            margin: 0 0 5px 0;
                                            letter-spacing: 0.5px;
                                        }
                                        .receipt-meta { 
                                            font-size: 11px; 
                                            color: #64748b; 
                                            margin: 5px 0;
                                            font-weight: 500;
                                        }
                                        .receipt-void { 
                                            color: #dc2626; 
                                            font-weight: 800;
                                            font-size: 12px;
                                            margin: 8px 0;
                                            text-transform: uppercase;
                                            letter-spacing: 1px;
                                        }
                                        .item-row { 
                                            display: flex; 
                                            justify-content: space-between; 
                                            margin-bottom: 8px; 
                                            font-size: 12px;
                                            padding: 4px 0;
                                        }
                                        .item-name { 
                                            flex: 1; 
                                            font-weight: 600; 
                                            color: #334155;
                                        }
                                        .item-details {
                                            font-size: 10px;
                                            color: #64748b;
                                            margin-top: 2px;
                                        }
                                        .item-price { 
                                            font-weight: 700; 
                                            color: #334155;
                                            text-align: right;
                                            min-width: 80px;
                                        }
                                        .divider { 
                                            border-bottom: 1px dashed #cbd5e1; 
                                            margin: 12px 0;
                                        }
                                        .summary-row { 
                                            display: flex; 
                                            justify-content: space-between; 
                                            margin-bottom: 6px; 
                                            font-size: 12px;
                                        }
                                        .total-row { 
                                            font-weight: 800; 
                                            font-size: 16px; 
                                            margin-top: 10px; 
                                            padding-top: 10px; 
                                            border-top: 2px dashed #cbd5e1; 
                                        }
                                        .footer { 
                                            text-align: center; 
                                            margin-top: 25px; 
                                            font-size: 10px; 
                                            color: #64748b; 
                                            padding-top: 15px;
                                            border-top: 1px dashed #e2e8f0;
                                        }
                                        .void-stamp { 
                                            position: absolute; 
                                            top: 50%; 
                                            left: 50%; 
                                            transform: translate(-50%, -50%) rotate(-45deg); 
                                            font-size: 60px; 
                                            color: rgba(220, 38, 38, 0.2); 
                                            font-weight: 900; 
                                            text-transform: uppercase;
                                            letter-spacing: 5px;
                                            pointer-events: none;
                                        }
                                        .store-info {
                                            font-size: 10px;
                                            color: #64748b;
                                            text-align: center;
                                            margin-bottom: 15px;
                                            line-height: 1.4;
                                        }
                                        @media print { 
                                            body { padding: 10px; margin: 0; }
                                            @page { margin: 0; }
                                        }
                                    </style>
                                </head>
                                <body>
                                    ${transaction.voided ? '<div class="void-stamp">VOID</div>' : ''}
                                    <div class="receipt-header">
                                        <div class="store-info">
                                            <div style="font-size: 14px; font-weight: 800; color: #1e293b; margin-bottom: 2px;">MEDICAL STORE</div>
                                            <div>123 Pharmacy Street, City</div>
                                            <div>Phone: +265 123 456 789</div>
                                            <div>VAT Reg: VAT123456789</div>
                                        </div>
                                        <div class="divider"></div>
                                        <div class="receipt-meta">
                                            <div>Receipt: ${transaction.id}</div>
                                            <div>${transaction.date} • ${transaction.time}</div>
                                            ${transaction.voided ? '<div class="receipt-void">VOIDED TRANSACTION</div>' : ''}
                                            <div>Invoice: ${transaction.invoiceNumber}</div>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <div style="font-size: 11px; color: #64748b; font-weight: 600; margin-bottom: 4px;">Customer:</div>
                                        <div style="font-size: 12px; font-weight: 700; color: #334155;">${transaction.customer}</div>
                                    </div>
                                    
                                    <div class="divider"></div>
                                    ${transaction.items.map(item => `
                                        <div class="item-row">
                                            <div>
                                                <div class="item-name">${item.name} × ${item.quantity}</div>
                                                <div class="item-details">MWK ${item.price.toLocaleString()} each • ${item.includesVat ? 'VAT Inc' : 'VAT Exc'}</div>
                                            </div>
                                            <div class="item-price">MWK ${(item.price * item.quantity).toLocaleString()}</div>
                                        </div>
                                    `).join('')}
                                    <div class="divider"></div>
                                    
                                    <div style="margin: 15px 0;">
                                        <div class="summary-row">
                                            <span>Subtotal:</span>
                                            <span>MWK ${transaction.subtotal.toLocaleString()}</span>
                                        </div>
                                        ${transaction.discount > 0 ? `
                                        <div class="summary-row">
                                            <span>Discount:</span>
                                            <span>- MWK ${transaction.discount.toLocaleString()}</span>
                                        </div>
                                        ` : ''}
                                        ${transaction.includeVat ? `
                                        <div class="summary-row">
                                            <span>VAT (16.5%):</span>
                                            <span>MWK ${transaction.vat.toLocaleString()}</span>
                                        </div>
                                        ` : ''}
                                        <div class="summary-row total-row">
                                            <span>TOTAL:</span>
                                            <span>MWK ${transaction.amount.toLocaleString()}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="divider"></div>
                                    
                                    <div style="margin: 15px 0;">
                                        <div class="summary-row">
                                            <span>Payment Method:</span>
                                            <span style="font-weight: 700; text-transform: uppercase;">${transaction.paymentMethod}</span>
                                        </div>
                                        ${transaction.paymentMethod === 'cash' ? `
                                        <div class="summary-row">
                                            <span>Amount Tendered:</span>
                                            <span>MWK ${transaction.amountTendered.toLocaleString()}</span>
                                        </div>
                                        <div class="summary-row">
                                            <span>Change:</span>
                                            <span>MWK ${transaction.change.toLocaleString()}</span>
                                        </div>
                                        ` : ''}
                                        <div class="summary-row">
                                            <span>VAT:</span>
                                            <span>${transaction.includeVat ? 'Included in Prices' : 'Not Applied'}</span>
                                        </div>
                                        ${transaction.voided ? `
                                        <div class="divider"></div>
                                        <div style="text-align: center; color: #dc2626; font-weight: 700; font-size: 11px; margin-top: 10px;">
                                            <div>VOIDED: ${transaction.voidReason}</div>
                                            <div>${transaction.voidDate}</div>
                                        </div>
                                        ` : ''}
                                        ${transaction.notes ? `
                                        <div class="divider"></div>
                                        <div style="font-size: 10px; color: #64748b; font-style: italic; margin-top: 10px;">
                                            Note: ${transaction.notes}
                                        </div>
                                        ` : ''}
                                    </div>
                                    
                                    <div class="footer">
                                        <div style="font-weight: 700; margin-bottom: 5px;">Thank you for your purchase!</div>
                                        <div>Items sold are non-returnable</div>
                                        <div>For queries, contact: +265 123 456 789</div>
                                        <div style="margin-top: 10px; font-size: 9px; opacity: 0.7;">Generated by POS System v1.0</div>
                                    </div>
                                </body>
                            </html>
                        `;
                        
                        const printWindow = window.open('', '_blank', 'width=350,height=600');
                        printWindow.document.write(printContent);
                        printWindow.document.close();
                        
                        setTimeout(() => {
                            printWindow.print();
                            hideLoading();
                            setTimeout(() => printWindow.close(), 1000);
                        }, 500);
                        
                        showToast('Receipt sent to printer', 'info');
                    }, 300);
                } else {
                    showToast('Transaction not found!', 'error');
                }
            }

            // Hold/Load Cart
            function openHoldCartModal() {
                if (cart.length === 0) {
                    showToast('Cart is empty!', 'warning');
                    return;
                }
                
                const cartName = prompt('Enter a name for this cart:');
                if (!cartName) return;
                
                const heldCart = {
                    name: cartName,
                    items: [...cart],
                    customer: currentCustomer,
                    discount: discount,
                    includeVat: includeVat,
                    timestamp: new Date().toLocaleString()
                };
                
                let heldCarts = JSON.parse(localStorage.getItem('heldCarts') || '[]');
                heldCarts.push(heldCart);
                localStorage.setItem('heldCarts', JSON.stringify(heldCarts));
                
                showToast(`Cart "${cartName}" saved for later`, 'success');
            }

            function loadHeldCart() {
                const heldCarts = JSON.parse(localStorage.getItem('heldCarts') || '[]');
                
                if (heldCarts.length === 0) {
                    showToast('No saved carts found', 'info');
                    return;
                }
                
                let cartList = 'Select a cart to load:\n\n';
                heldCarts.forEach((cart, index) => {
                    const date = new Date(cart.timestamp).toLocaleDateString();
                    cartList += `${index + 1}. ${cart.name} (${cart.items.length} items) - ${date}\n`;
                });
                
                const selection = prompt(cartList + '\nEnter number:');
                if (!selection) return;
                
                const index = parseInt(selection) - 1;
                if (isNaN(index) || index < 0 || index >= heldCarts.length) {
                    showToast('Invalid selection', 'error');
                    return;
                }
                
                const heldCart = heldCarts[index];
                
                if (cart.length > 0 && !confirm('Current cart will be cleared. Continue?')) {
                    return;
                }
                
                cart = [...heldCart.items];
                discount = heldCart.discount;
                includeVat = heldCart.includeVat;
                
                // Update VAT toggle
                document.getElementById('vatToggle').checked = includeVat;
                
                if (heldCart.customer) {
                    currentCustomer = heldCart.customer;
                    const select = document.getElementById('customerSelect');
                    select.value = heldCart.customer.id;
                }
                
                updateCartDisplay();
                showToast(`Cart "${heldCart.name}" loaded successfully`, 'success');
            }

            // Sample Data
            function addSampleTransactions() {
                const sampleTransactions = [
                    {
                        id: 'TXN-001-ABC123',
                        invoiceNumber: 'INV-20240115-1001',
                        customer: 'John Doe (+265 881 234 567)',
                        amount: 42500,
                        date: '15 Jan 2024',
                        time: '10:30 AM',
                        paymentMethod: 'cash',
                        items: [
                            { id: 1, name: 'Paracetamol 500mg', price: 12500, quantity: 2, includesVat: true },
                                                        { id: 3, name: 'Amoxicillin 500mg', price: 26000, quantity: 1, includesVat: true }
                        ],
                        subtotal: 51000,
                        discount: 8500,
                        vat: 8415,
                        change: 0,
                        amountTendered: 42500,
                        status: 'completed',
                        voided: false,
                        includeVat: true,
                        notes: 'Customer with insurance'
                    },
                    {
                        id: 'TXN-002-DEF456',
                        invoiceNumber: 'INV-20240115-1002',
                        customer: 'Mary Johnson (+265 992 345 678)',
                        amount: 24300,
                        date: '15 Jan 2024',
                        time: '11:45 AM',
                        paymentMethod: 'mobile',
                        items: [
                            { id: 2, name: 'Ibuprofen 400mg', price: 15100, quantity: 1, includesVat: true },
                            { id: 6, name: 'Surgical Masks (50pcs)', price: 800, quantity: 5, includesVat: true },
                            { id: 11, name: 'Antiseptic Cream', price: 350, quantity: 4, includesVat: true }
                        ],
                        subtotal: 24300,
                        discount: 0,
                        vat: 4009.5,
                        change: 0,
                        amountTendered: 24300,
                        status: 'completed',
                        voided: false,
                        includeVat: true,
                        notes: 'Paid via Airtel Money'
                    },
                    {
                        id: 'TXN-003-GHI789',
                        invoiceNumber: 'INV-20240114-1003',
                        customer: 'Robert Brown (+265 993 456 789)',
                        amount: 18200,
                        date: '14 Jan 2024',
                        time: '03:20 PM',
                        paymentMethod: 'card',
                        items: [
                            { id: 8, name: 'Vitamin C 1000mg', price: 18200, quantity: 1, includesVat: true }
                        ],
                        subtotal: 18200,
                        discount: 0,
                        vat: 3003,
                        change: 0,
                        amountTendered: 18200,
                        status: 'completed',
                        voided: true,
                        voidReason: 'Customer cancelled purchase',
                        voidDate: '14 Jan 2024, 15:35',
                        includeVat: true,
                        notes: 'Card payment processed but voided later'
                    }
                ];

                // Merge with existing transactions
                let existingTransactions = JSON.parse(localStorage.getItem('posTransactions')) || [];
                const allTransactions = [...sampleTransactions, ...existingTransactions];
                
                // Keep only unique transactions
                const uniqueTransactions = allTransactions.filter((transaction, index, self) =>
                    index === self.findIndex((t) => t.id === transaction.id)
                );

                localStorage.setItem('posTransactions', JSON.stringify(uniqueTransactions));
                
                loadTransactions();
                showToast('Sample transactions added successfully', 'success');
            }

            // Print Receipt Function
            function printReceipt(transaction) {
                showLoading();
                
                setTimeout(() => {
                    const printContent = `
                        <html>
                            <head>
                                <title>Receipt ${transaction.id}</title>
                                <style>
                                    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
                                    body { 
                                        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; 
                                        padding: 20px; 
                                        max-width: 300px; 
                                        margin: 0 auto; 
                                        background: white;
                                    }
                                    .receipt-header { 
                                        text-align: center; 
                                        margin-bottom: 20px; 
                                        padding-bottom: 15px;
                                        border-bottom: 2px dashed #e2e8f0;
                                    }
                                    .receipt-title { 
                                        font-size: 20px; 
                                        font-weight: 800; 
                                        color: #1e293b; 
                                        margin: 0 0 5px 0;
                                        letter-spacing: 0.5px;
                                    }
                                    .receipt-meta { 
                                        font-size: 11px; 
                                        color: #64748b; 
                                        margin: 5px 0;
                                        font-weight: 500;
                                    }
                                    .item-row { 
                                        display: flex; 
                                        justify-content: space-between; 
                                        margin-bottom: 8px; 
                                        font-size: 12px;
                                        padding: 4px 0;
                                    }
                                    .item-name { 
                                        flex: 1; 
                                        font-weight: 600; 
                                        color: #334155;
                                    }
                                    .item-details {
                                        font-size: 10px;
                                        color: #64748b;
                                        margin-top: 2px;
                                    }
                                    .item-price { 
                                        font-weight: 700; 
                                        color: #334155;
                                        text-align: right;
                                        min-width: 80px;
                                    }
                                    .divider { 
                                        border-bottom: 1px dashed #cbd5e1; 
                                        margin: 12px 0;
                                    }
                                    .summary-row { 
                                        display: flex; 
                                        justify-content: space-between; 
                                        margin-bottom: 6px; 
                                        font-size: 12px;
                                    }
                                    .total-row { 
                                        font-weight: 800; 
                                        font-size: 16px; 
                                        margin-top: 10px; 
                                        padding-top: 10px; 
                                        border-top: 2px dashed #cbd5e1; 
                                    }
                                    .footer { 
                                        text-align: center; 
                                        margin-top: 25px; 
                                        font-size: 10px; 
                                        color: #64748b; 
                                        padding-top: 15px;
                                        border-top: 1px dashed #e2e8f0;
                                    }
                                    .store-info {
                                        font-size: 10px;
                                        color: #64748b;
                                        text-align: center;
                                        margin-bottom: 15px;
                                        line-height: 1.4;
                                    }
                                    @media print { 
                                        body { padding: 10px; margin: 0; }
                                        @page { margin: 0; }
                                    }
                                </style>
                            </head>
                            <body>
                                <div class="receipt-header">
                                    <div class="store-info">
                                        <div style="font-size: 14px; font-weight: 800; color: #1e293b; margin-bottom: 2px;">MEDICAL STORE</div>
                                        <div>123 Pharmacy Street, City</div>
                                        <div>Phone: +265 123 456 789</div>
                                        <div>VAT Reg: VAT123456789</div>
                                    </div>
                                    <div class="divider"></div>
                                    <div class="receipt-meta">
                                        <div>Receipt: ${transaction.id}</div>
                                        <div>${transaction.date} • ${transaction.time}</div>
                                        <div>Invoice: ${transaction.invoiceNumber}</div>
                                    </div>
                                </div>
                                
                                <div style="margin-bottom: 15px;">
                                    <div style="font-size: 11px; color: #64748b; font-weight: 600; margin-bottom: 4px;">Customer:</div>
                                    <div style="font-size: 12px; font-weight: 700; color: #334155;">${transaction.customer}</div>
                                </div>
                                
                                <div class="divider"></div>
                                ${transaction.items.map(item => `
                                    <div class="item-row">
                                        <div>
                                            <div class="item-name">${item.name} × ${item.quantity}</div>
                                            <div class="item-details">MWK ${item.price.toLocaleString()} each • ${item.includesVat ? 'VAT Inc' : 'VAT Exc'}</div>
                                        </div>
                                        <div class="item-price">MWK ${(item.price * item.quantity).toLocaleString()}</div>
                                    </div>
                                `).join('')}
                                <div class="divider"></div>
                                
                                <div style="margin: 15px 0;">
                                    <div class="summary-row">
                                        <span>Subtotal:</span>
                                        <span>MWK ${transaction.subtotal.toLocaleString()}</span>
                                    </div>
                                    ${transaction.discount > 0 ? `
                                    <div class="summary-row">
                                        <span>Discount:</span>
                                        <span>- MWK ${transaction.discount.toLocaleString()}</span>
                                    </div>
                                    ` : ''}
                                    ${transaction.includeVat ? `
                                    <div class="summary-row">
                                        <span>VAT (16.5%):</span>
                                        <span>MWK ${transaction.vat.toLocaleString()}</span>
                                    </div>
                                    ` : ''}
                                    <div class="summary-row total-row">
                                        <span>TOTAL:</span>
                                        <span>MWK ${transaction.amount.toLocaleString()}</span>
                                    </div>
                                </div>
                                
                                <div class="divider"></div>
                                
                                <div style="margin: 15px 0;">
                                    <div class="summary-row">
                                        <span>Payment Method:</span>
                                        <span style="font-weight: 700; text-transform: uppercase;">${transaction.paymentMethod}</span>
                                    </div>
                                    ${transaction.paymentMethod === 'cash' ? `
                                    <div class="summary-row">
                                        <span>Amount Tendered:</span>
                                        <span>MWK ${transaction.amountTendered.toLocaleString()}</span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Change:</span>
                                        <span>MWK ${transaction.change.toLocaleString()}</span>
                                    </div>
                                    ` : ''}
                                    <div class="summary-row">
                                        <span>VAT:</span>
                                        <span>${transaction.includeVat ? 'Included in Prices' : 'Not Applied'}</span>
                                    </div>
                                    ${transaction.notes ? `
                                    <div class="divider"></div>
                                    <div style="font-size: 10px; color: #64748b; font-style: italic; margin-top: 10px;">
                                        Note: ${transaction.notes}
                                    </div>
                                    ` : ''}
                                </div>
                                
                                <div class="footer">
                                    <div style="font-weight: 700; margin-bottom: 5px;">Thank you for your purchase!</div>
                                    <div>Items sold are non-returnable</div>
                                    <div>For queries, contact: +265 123 456 789</div>
                                    <div style="margin-top: 10px; font-size: 9px; opacity: 0.7;">Generated by POS System v1.0</div>
                                </div>
                            </body>
                        </html>
                    `;
                    
                    const printWindow = window.open('', '_blank', 'width=350,height=600');
                    printWindow.document.write(printContent);
                    printWindow.document.close();
                    
                    setTimeout(() => {
                        printWindow.print();
                        hideLoading();
                        setTimeout(() => printWindow.close(), 1000);
                    }, 500);
                }, 300);
            }

            // View All Transactions
            function viewAllTransactions() {
                window.open('transactions.php', '_blank');
            }

            // Helper Functions
            function showToast(message, type = 'info') {
                const toast = document.getElementById('toast');
                
                const icons = {
                    success: 'checkmark-circle-outline',
                    error: 'alert-circle-outline',
                    warning: 'warning-outline',
                    info: 'information-circle-outline'
                };
                
                toast.innerHTML = `
                    <div class="toast-icon">
                        <ion-icon name="${icons[type]}"></ion-icon>
                    </div>
                    <div class="toast-message">${message}</div>
                `;
                
                toast.className = `toast ${type}`;
                toast.style.display = 'flex';
                
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 3000);
            }

            function showLoading() {
                document.getElementById('loading').classList.add('active');
            }

            function hideLoading() {
                document.getElementById('loading').classList.remove('active');
            }

            // Apply Discount
            function applyDiscount() {
                const discountType = prompt('Enter discount type:\n1. Percentage (e.g., 10 for 10%)\n2. Fixed amount (e.g., 5000)');
                
                if (!discountType) return;
                
                const { subtotal } = calculateTotals();
                
                if (discountType.includes('%')) {
                    const percent = parseFloat(discountType) || 0;
                    discount = (subtotal * percent / 100);
                    showToast(`Discount applied: ${percent}% (-MWK ${discount.toLocaleString()})`, 'success');
                } else {
                    discount = parseFloat(discountType) || 0;
                    if (discount > subtotal) {
                        showToast('Discount cannot exceed subtotal!', 'error');
                        discount = 0;
                        return;
                    }
                    showToast(`Discount applied: MWK ${discount.toLocaleString()}`, 'success');
                }
                
                updateCartDisplay();
            }

            // Remove Discount
            function removeDiscount() {
                discount = 0;
                updateCartDisplay();
                showToast('Discount removed', 'info');
            }

            // Keyboard Shortcuts Display
            function showKeyboardShortcuts() {
                const shortcuts = [
                    { key: 'F1', action: 'Focus search' },
                    { key: 'F2', action: 'Process payment' },
                    { key: 'F3', action: 'Clear cart' },
                    { key: 'F5', action: 'Refresh transactions' },
                    { key: 'ESC', action: 'Close modals' },
                    { key: 'Enter (in search)', action: 'Search products' }
                ];
                
                let message = 'Keyboard Shortcuts:\n\n';
                shortcuts.forEach(shortcut => {
                    message += `${shortcut.key}: ${shortcut.action}\n`;
                });
                
                alert(message);
            }

            // Auto-save cart (every 30 seconds)
            setInterval(() => {
                if (cart.length > 0) {
                    localStorage.setItem('posCurrentCart', JSON.stringify({
                        cart: cart,
                        customer: currentCustomer,
                        discount: discount,
                        includeVat: includeVat
                    }));
                }
            }, 30000);

            // Load auto-saved cart on page load
            const savedCart = JSON.parse(localStorage.getItem('posCurrentCart'));
            if (savedCart && savedCart.cart && savedCart.cart.length > 0) {
                if (confirm('You have an unsaved cart from your last session. Do you want to restore it?')) {
                    cart = savedCart.cart;
                    discount = savedCart.discount || 0;
                    includeVat = savedCart.includeVat !== false;
                    currentCustomer = savedCart.customer || null;
                    
                    if (currentCustomer && currentCustomer.id) {
                        document.getElementById('customerSelect').value = currentCustomer.id;
                    }
                    
                    document.getElementById('vatToggle').checked = includeVat;
                    updateCartDisplay();
                }
                // Clear the auto-saved cart
                localStorage.removeItem('posCurrentCart');
            }
        </script>

        <!-- Icons -->
        <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
        <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    </div>
</body>
</html>