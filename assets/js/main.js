// Main JavaScript file for Bliss Restaurant

// Helper functions in global scope
function showNotification(message, type = 'success') {
    console.log('Showing notification:', message, type);
    const notification = $('<div>')
        .addClass(`alert alert-${type} notification`)
        .text(message)
        .appendTo('body');

    setTimeout(() => {
        notification.fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}

function updateCartBadge(count) {
    const badge = $('.cart-count');
    if (count > 0) {
        badge.text(count).show();
    } else {
        badge.text('0').hide();
    }
}

function updateCartDisplay(data) {
    const cartItems = $('#cartItems');
    const cartTotal = $('#cartTotal');
    const checkoutBtn = $('#checkout');

    if (!data.items || data.items.length === 0) {
        cartItems.html('<p class="text-center my-4">Your cart is empty</p>');
        cartTotal.text(`0.00 ${CURRENCY}`);
        checkoutBtn.prop('disabled', true);
        updateCartBadge(0);
        return;
    }

    let html = '';
    data.items.forEach(item => {
        html += `
            <div class="cart-item mb-3">
                <div class="d-flex align-items-center">
                    <img src="${item.image_path || 'assets/images/default-food.jpg'}" 
                         alt="${item.name}"
                         class="cart-item-image me-3"
                         style="width: 60px; height: 60px; object-fit: cover;">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${item.name}</h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="input-group input-group-sm" style="width: 100px;">
                                <button type="button" class="btn btn-outline-secondary" 
                                        onclick="updateCartQuantity(${item.id}, ${item.quantity - 1})">-</button>
                                <input type="text" class="form-control text-center" 
                                       value="${item.quantity}" readonly>
                                <button type="button" class="btn btn-outline-secondary" 
                                        onclick="updateCartQuantity(${item.id}, ${parseInt(item.quantity, 10) + 1})">+</button>
                            </div>
                            <button class="btn btn-sm btn-link text-danger" 
                                    onclick="removeCartItem(${item.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="text-end mt-1">
                            <small class="text-muted">${item.quantity} × ${parseFloat(item.price).toFixed(2)} ${CURRENCY}</small>
                            <div class="fw-bold">${(item.quantity * item.price).toFixed(2)} ${CURRENCY}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    cartItems.html(html);
    cartTotal.text(`${parseFloat(data.total).toFixed(2)} ${CURRENCY}`);
    checkoutBtn.prop('disabled', false);
    updateCartBadge(data.count);
}

// Cart management functions in global scope
window.loadCartItems = function() {
    $.ajax({
        url: 'api/cart/manage.php?action=get',
        method: 'GET',
        success: function(response) {
            if (response.status === 'success') {
                updateCartDisplay(response.data);
            } else {
                showNotification(response.message || 'Error loading cart', 'error');
            }
        },
        error: function(xhr) {
            showNotification(xhr.responseJSON?.message || 'Error loading cart', 'error');
        }
    });
};

window.updateCartQuantity = function(cartId, quantity) {
    if (quantity <= 0) {
        removeCartItem(cartId);
        return;
    }

    $.ajax({
        url: 'api/cart/manage.php?action=update',
        method: 'POST',
        data: {
            cart_id: cartId,
            quantity: quantity
        },
        success: function(response) {
            if (response.status === 'success') {
                loadCartItems();
            } else {
                showNotification(response.message || 'Error updating cart', 'error');
            }
        },
        error: function(xhr) {
            showNotification(xhr.responseJSON?.message || 'Error updating cart', 'error');
        }
    });
};

window.removeCartItem = function(cartId) {
    $.ajax({
        url: 'api/cart/manage.php?action=remove',
        method: 'POST',
        data: {
            cart_id: cartId
        },
        success: function(response) {
            if (response.status === 'success') {
                loadCartItems();
            } else {
                showNotification(response.message || 'Error removing item', 'error');
            }
        },
        error: function(xhr) {
            showNotification(xhr.responseJSON?.message || 'Error removing item', 'error');
        }
    });
};

// Use jQuery's more robust ready method
jQuery(function($) {
    console.log('DOM Content Loaded - ' + new Date().toISOString());
    
    // Verify jQuery is working
    console.log('jQuery version:', $.fn.jquery);
    
    // Load initial cart state
    loadCartItems();
      // Handle checkout button clicks
    $(document).on('click', '#checkout', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Checkout button clicked');
        const $btn = $(this);
        
        // Check if table number is available
        if (typeof TABLE_NUMBER === 'undefined' || !TABLE_NUMBER) {
            showNotification('Table number not found. Please login again.', 'error');
            return;
        }
        
        // Confirm order placement
        if (!confirm('Are you sure you want to place your order?')) {
            return;
        }
        
        // Disable button and show loading state
        $btn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        // Get cart data and submit order
        $.ajax({
            url: 'api/cart/manage.php?action=get',
            method: 'GET',
            success: function(cartResponse) {
                console.log('Cart data:', cartResponse);
                
                if (cartResponse.status !== 'success' || !cartResponse.data || !cartResponse.data.items) {
                    showNotification('Error: Could not get cart data', 'error');
                    $btn.prop('disabled', false).html('Checkout');
                    return;
                }
                
                if (!cartResponse.data.items.length) {
                    showNotification('Your cart is empty', 'error');
                    $btn.prop('disabled', false).html('Checkout');
                    return;
                }

                // Prepare order data
                const orderData = {
                    table_number: TABLE_NUMBER,
                    items: cartResponse.data.items.map(item => ({
                        id: item.menu_item_id,
                        quantity: item.quantity,
                        price: item.price
                    })),
                    total: cartResponse.data.total
                };
                
                console.log('Submitting order:', orderData);
                
                // Submit order
                $.ajax({
                    url: 'api/orders/create.php',
                    method: 'POST',
                    data: JSON.stringify(orderData),
                    contentType: 'application/json',
                    success: function(response) {
                        console.log('Order response:', response);
                        if (response.success) {
                            showNotification(`Order #${response.orderNumber} placed successfully!`, 'success');
                            loadCartItems();
                            $('#cartSidebar').removeClass('open');
                        } else {
                            showNotification(response.message || 'Error placing order', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Order submission error:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                        
                        let errorMessage = 'Failed to submit order. Please try again.';
                        
                        try {
                            const response = xhr.responseJSON;
                            if (response && response.message) {
                                errorMessage = response.message;
                            }
                        } catch (e) {
                            console.error('Error parsing error response:', e);
                        }
                        
                        showNotification(errorMessage, 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('Checkout');
                    }
                });
            },
            error: function(xhr, status, error) {
                console.error('Cart data fetch error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                showNotification('Failed to get cart data. Please try again.', 'error');
                $btn.prop('disabled', false).html('Checkout');
            }
        });
    });    // Checkout handler function
function handleCheckout(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Checkout initiated with table number:', TABLE_NUMBER);
        const $btn = $(e.currentTarget);
        
        // Validate table number
        if (!TABLE_NUMBER) {
            showNotification('Table number not found. Please log in again.', 'error');
            return;
        }
        
        // Confirm order placement
        if (!confirm('Are you sure you want to place your order?')) {
            return;
        }
        
        // Disable button and show loading state
        $btn.prop('disabled', true)
           .html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        // Get current cart data
        $.ajax({
            url: 'api/cart/manage.php?action=get',
            method: 'GET',
            success: function(cartResponse) {
                console.log('Cart data received:', cartResponse);
                
                if (cartResponse.status !== 'success' || !cartResponse.data || !cartResponse.data.items) {
                    showNotification('Error: Could not get cart data', 'error');
                    $btn.prop('disabled', false).html('Checkout');
                    return;
                }
                
                if (!cartResponse.data.items.length) {
                    showNotification('Your cart is empty', 'error');
                    $btn.prop('disabled', false).html('Checkout');
                    return;
                }

                // Prepare order data
                const orderData = {
                    table_number: TABLE_NUMBER,
                    items: cartResponse.data.items.map(item => ({
                        id: item.menu_item_id,
                        quantity: item.quantity,
                        price: item.price
                    })),
                    total: cartResponse.data.total
                };
                
                console.log('Submitting order:', orderData);
                
                // Submit order
                $.ajax({
                    url: 'api/orders/create.php',
                    method: 'POST',
                    data: JSON.stringify(orderData),
                    contentType: 'application/json',
                    success: function(response) {
                        console.log('Order submission response:', response);
                        if (response.success) {
                            showNotification(`Order #${response.orderNumber} placed successfully!`, 'success');
                            loadCartItems();
                            $('#cartSidebar').removeClass('open');
                        } else {
                            showNotification(response.message || 'Error placing order', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Order submission error:', xhr);
                        const errorMsg = xhr.responseJSON?.message || 'Failed to submit order. Please try again.';
                        showNotification(errorMsg, 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('Checkout');
                    }
                });
            },
            error: function(xhr) {
                console.error('Error loading cart data:', xhr);
                showNotification('Error loading cart data', 'error');
                $btn.prop('disabled', false).html('Checkout');
            }
        });
    }

    // Cart UI elements
    const cartSidebar = document.getElementById('cartSidebar');
    const cartToggle = document.getElementById('cartToggle');
    const closeCart = document.getElementById('closeCart');    // Cart toggle functionality
    if (cartToggle) {
        console.log('Setting up cart toggle handler');
        cartToggle.addEventListener('click', function(e) {
            console.log('Cart toggle clicked');
            e.preventDefault();
            cartSidebar.classList.toggle('open');
            loadCartItems(); // Refresh cart when opened
        });
    } else {
        console.log('Cart toggle button not found');
    }

    if (closeCart) {
        closeCart.addEventListener('click', function() {
            cartSidebar.classList.remove('open');
        });
    }

    // Close cart when clicking outside
    document.addEventListener('click', function(e) {
        if (cartSidebar && !cartSidebar.contains(e.target) && 
            (!cartToggle || !cartToggle.contains(e.target))) {
            cartSidebar.classList.remove('open');
        }
    });

    // Add to cart functionality
    $(document).on('click', '.add-to-cart', function() {
        const btn = $(this);
        const itemId = btn.data('item-id');
        const quantityInput = btn.closest('.card-body').find('.item-quantity');
        const quantity = parseInt(quantityInput.val()) || 1;

        if (!itemId) {
            showNotification('Invalid item', 'error');
            return;
        }

        if (quantity <= 0) {
            showNotification('Please enter a valid quantity', 'error');
            return;
        }

        btn.prop('disabled', true);

        $.ajax({
            url: 'api/cart/manage.php?action=add',
            method: 'POST',
            data: JSON.stringify({ menu_item_id: itemId, quantity: quantity }),
            contentType: 'application/json',
            success: function(response) {
                console.log('Add to cart response:', response);
                if (response.status === 'success') {
                    quantityInput.val(1);
                    loadCartItems();
                    $('#cartSidebar').addClass('open');
                    showNotification('Item added to cart', 'success');
                } else {
                    showNotification(response.message || 'Error adding item to cart', 'error');
                }
            },
            error: function(xhr) {
                console.error('Add to cart error:', xhr);
                const message = xhr.responseJSON?.message || 'Error adding item to cart';
                showNotification(message, 'error');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });
    
    // Search filtering
$('#menuSearch').on('keyup', function() {
    const query = $(this).val().toLowerCase().trim();

    // Search in all visible tab panes
    $('.tab-pane.show.active .menu-item').each(function() {
        const itemName = $(this).data('name');
        if (itemName.includes(query)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });

    // Optionally show a message if no results
    const visibleItems = $('.tab-pane.show.active .menu-item:visible').length;
    if (visibleItems === 0) {
        if ($('#noResultsMsg').length === 0) {
            $('.tab-pane.show.active .row').append('<div id="noResultsMsg" class="col-12 text-center mt-3">No matching items found.</div>');
        }
    } else {
        $('#noResultsMsg').remove();
    }
});


    // Quantity control buttons
    $(document).on('click', '.btn-decrease, .btn-increase', function() {
        const input = $(this).closest('.input-group').find('.item-quantity');
        let quantity = parseInt(input.val()) || 1;
        
        if ($(this).hasClass('btn-decrease')) {
            quantity = Math.max(1, quantity - 1);
        } else {
            quantity = Math.min(99, quantity + 1);
        }
        
        input.val(quantity);
    });
});
