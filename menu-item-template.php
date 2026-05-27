<?php
/**
 * Menu item template
 * Expected variables:
 * - $item: array containing menu item details
 */
?>
<div class="col-6 col-md-4 col-lg-3" style="margin-bottom: 10px;">
    <div class="card h-100" style="box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 8px;">
        <div style="height: 140px; overflow: hidden;">
            <img src="<?= htmlspecialchars($item['image_path'] ?: 'assets/images/placeholder.jpg') ?>" 
                 class="card-img-top" 
                 alt="<?= htmlspecialchars($item['name']) ?>"
                 style="height: 100%; width: 100%; object-fit: cover;">
        </div>
        <div class="card-body p-2" style="font-size: 0.9rem;">
            <h5 class="card-title mb-1" style="font-size: 1rem; font-weight: 600;"><?= htmlspecialchars($item['name']) ?></h5>
            <p class="card-text mb-1" style="font-size: 0.8rem; color: #666; height: 32px; overflow: hidden;"><?= htmlspecialchars($item['description']) ?></p>
            <div class="price mb-2" style="color: #2c3e50; font-weight: 600;"><?= CURRENCY ?> <?= number_format($item['price'], 2) ?></div>
            <div class="d-flex gap-1 justify-content-between align-items-center">
                <div class="input-group input-group-sm flex-nowrap" style="width: 85px;">
                    <button type="button" class="btn btn-outline-secondary btn-decrease px-1" data-item-id="<?= $item['id'] ?>" style="padding: 2px 6px;">-</button>
                    <input type="text" class="form-control text-center item-quantity p-0" value="1" style="width: 30px; border-left: none; border-right: none;" readonly>
                    <button type="button" class="btn btn-outline-secondary btn-increase px-1" data-item-id="<?= $item['id'] ?>" style="padding: 2px 6px;">+</button>
                </div>
                <button type="button" class="btn btn-primary btn-sm add-to-cart" data-item-id="<?= $item['id'] ?>" style="padding: 4px 8px;">
                    <i class="fas fa-cart-plus"></i>
                </button>
            </div>
        </div>
    </div>
</div>
