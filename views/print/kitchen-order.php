<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Kitchen Order #<?= $printData['orderNumber'] ?></title>
    <style>
        @media print {
            @page {
                margin: 1cm;
            }
        }
        
        body {
            font-family: 'Segoe UI', monospace;
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
        .footer {
            text-align: center;
            border-top: 1px dashed #000;
            padding-top: 10px;
            font-size: 90%;
        }
       @media print {
    @page {
        size: 80mm auto; /* Matches your body width */
        margin: 0;        /* Removes all browser-imposed margin */
    }
    html, body {
        width: 80mm;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        font-family: 'Courier New', monospace;
        font-size: 18px;
    }
    .footer {
        margin-bottom: 0 !important; /* Prevents footer from adding extra feed */
         margin: 0 !important;
         padding: 0 !important;
    }
    .print-wrapper {
        display: block;
        width: 100%;
        height: auto !important;
        page-break-after: avoid;
    }
}
        
    </style>
</head>
<body>
     <div class="print-wrapper">
    <div class="header">
        <h2>KITCHEN ORDER</h2>
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
                        <strong><?= $item['quantity'] ?>x</strong> <?= $item['name'] ?>
                    </div>
                    <div class="item-total">
                        <?= number_format($item['total'], 2) ?> <?= CURRENCY ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

    <div class="totals">
        <?php 
        // Calculate total for kitchen items only
        $kitchenTotal = 0;
        foreach ($printData['items'] as $category => $items) {
            foreach ($items as $item) {
                $kitchenTotal += $item['total'];
            }
        }
        ?>
        <div class="total-line">
            <strong>Kitchen Total:</strong>
            <span><?= number_format($kitchenTotal, 2) ?> <?= CURRENCY ?></span>
        </div>
    </div>

    <div class="footer">
        <p>*** KITCHEN COPY ***</p>
        <p><?= date('Y-m-d H:i:s') ?></p>
    </div>
    </div>
    
    <script>
        window.onload = function() {
            window.print();
            setTimeout(function() {
                window.close();
            }, 500);
        };
    </script>
</body>
</html>