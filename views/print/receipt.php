<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?= $printData['orderNumber'] ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 20px;
            font-size: 18px;
            width: 80mm; /* Standard thermal receipt width */
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .restaurant-info {
            text-align: center;
            margin-bottom: 20px;
        }
        .order-info {
            margin-bottom: 20px;
        }
        .items {
            width: 100%;
            margin-bottom: 20px;
        }
        .category {
            font-weight: bold;
            padding-top: 10px;
            border-top: 1px dashed #000;
        }
        .item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .item-details {
            flex-grow: 1;
            padding-left: 10px;
        }
        .totals {
            border-top: 1px dashed #000;
            margin-top: 10px;
            padding-top: 10px;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .discount-line {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-weight: bold;
        }
        .final-total {
            border-top: 1px dashed #000;
            padding-top: 5px;
            margin-top: 5px;
            font-weight: bold;
            font-size: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
        .discount-info {
            font-size: 14px;
            font-style: italic;
            color: #666;
        }
       @media print {
    @page {
        size: 80mm auto;
        margin: 0;
    }

    html, body {
        width: 80mm;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        font-size: 18px;
    }

    .footer {
         margin: 0 !important;
         padding: 0 !important;
    }

    .print-wrapper {
        display: block;
        width: 100%;
        height: auto !important;
        page-break-after: avoid;
    }

    .discount-info {
        color: #000 !important;
    }
}
    </style>
</head>
<body>
     <div class="print-wrapper">
    <div class="header">
        <h2><?= SITE_NAME ?></h2>
    </div>

    <div class="restaurant-info">
        <p>N'S Bliss Lounge, Kigali</p>
        <p>Tel: (250)795569628</p>
        <p>Momo:*182*8*1*5505#</p>
    </div>

    <div class="order-info">
        <p><strong>Order #:</strong> <?= $printData['orderNumber'] ?></p>
        <p><strong>Table:</strong> <?= $printData['tableNumber'] ?></p>
        <p><strong>Time:</strong> <?= $printData['datetime'] ?></p>
        <?php if ($printData['employee']): ?>
            <p><strong>Waiter(ess):</strong> <?= $printData['employee'] ?></p>
        <?php endif; ?>
    </div>

    <div class="items">
        <?php foreach ($printData['items'] as $category => $items): ?>
            <b><div class="category"><?= $category ?></div></b>
            <?php foreach ($items as $item): ?>
                <div class="item">
                    <div class="item-details">
                        <?= $item['quantity'] ?>x <?= $item['name'] ?>
                    </div>
                    <div class="item-total">
                        <?php 
                        // Show the original stored price (quantity * price)
                        echo number_format($item['total'], 0) . ' ' . CURRENCY;
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

    <div class="totals">
        <!-- Show subtotal if there's a discount -->
        <?php if (isset($printData['discount_amount']) && $printData['discount_amount'] > 0): ?>
            <div class="total-line">
                <span>Pre-Discount total:</span>
                <span><?= number_format($printData['subtotal_amount'], 0) ?> <?= CURRENCY ?></span>
            </div>
            
            <div class="discount-line">
                <span>Discount:</span>
                <span>-<?= number_format($printData['discount_amount'], 0) ?> <?= CURRENCY ?></span>
            </div>
            
            <div class="discount-info" style="text-align: center; margin: 10px 0;">
                <strong>You saved <?= number_format($printData['discount_amount'], 0) ?> <?= CURRENCY ?>!</strong>
            </div>
        <?php endif; ?>

        <!-- Final Total -->
        <div class="total-line final-total">
            <strong>Total:</strong>
            <span><?= number_format($printData['total'], 0) ?> <?= CURRENCY ?></span>
        </div>
    </div>

    <div class="footer">
        <p>Thank you for dining with us!</p>
        <p>*** CUSTOMER COPY ***</p>
        <p><?= date('Y-m-d H:i:s') ?></p>
    </div>
     </div>

    <script>
        // Auto print and close
        window.onload = function() {
            window.print();
            setTimeout(function() {
                window.close();
            }, 500);
        };
    </script>
</body>
</html>