<?php
/*
Plugin Name: Auto Enroll on Subscription
Description: Automatically enrolls users in all Tutor LMS courses when their WooCommerce subscription becomes active.
Version: 1.0
Author: Mazhar Ali
*/

// shortcode — show status and enrollment info
add_shortcode('subscription_debug_info', 'show_subscription_debug_info');
function show_subscription_debug_info() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your subscription details.</p>';
    }

    $user_id = get_current_user_id();
    $has_active_subscription = false;
    $subscriptions = wcs_get_users_subscriptions($user_id);

    // Check if the user has an active subscription
    if (!empty($subscriptions)) {
        foreach ($subscriptions as $subscription) {
            if ($subscription->has_status('active')) {
                $has_active_subscription = true;
                break;
            }
        }
    }

    // Fetch all published courses
    $args = array(
        'post_type' => 'courses',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    );
    $all_courses = get_posts($args);
    $total_courses = count($all_courses);

    // Count the enrolled courses
    $enrolled_courses = 0;
    foreach ($all_courses as $course) {
        if (tutils()->is_enrolled($course->ID, $user_id)) {
            $enrolled_courses++;
        }
    }

    ob_start();
    echo "<div style='background:#f1f1f1;padding:20px;border-left:4px solid #0073aa;'>";
    echo "<h3>Subscription Debug Info:</h3>";

    // Show active subscription status
    if ($has_active_subscription) {
        echo "<p><strong>Status:</strong> ✅ Subscription is <strong>ACTIVE</strong></p>";
    } else {
        echo "<p><strong>Status:</strong> ❌ No active subscription found.</p>";
    }

    // Show the total courses and enrolled courses
    echo "<p><strong>Total Tutor LMS Courses:</strong> $total_courses</p>";
    echo "<p><strong>User Enrolled Courses:</strong> $enrolled_courses</p>";
    echo "</div>";
    return ob_get_clean();
}






//Custom code subscription

add_action('woocommerce_checkout_create_order', 'auto_add_lifetime_products_to_order', 20, 2);
function auto_add_lifetime_products_to_order($order, $data) {
 
    $subscription_product_id = 1618; // Subscription Product ID
 
    $has_subscription_product = false;
 
    // Check if subscription product is in the order
    foreach ($order->get_items() as $item) {
        if ($item->get_product_id() == $subscription_product_id) {
            $has_subscription_product = true;
            break;
        }
    }
 
    if (!$has_subscription_product) {
        return;
    }
 
    // Get all products in "life-time" category
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => 'life-time',
            ),
        ),
    );
 
    $products = get_posts($args);
 
    foreach ($products as $product_post) {
        $product = wc_get_product($product_post->ID);
        if (!$product) continue;
 
        // Add product as free item
        $item = new WC_Order_Item_Product();
        $item->set_product($product);
        $item->set_quantity(1);
        $item->set_total(0);
        $item->set_subtotal(0);
        $order->add_item($item);
    }
 
    // Recalculate totals after adding items
    $order->calculate_totals();
}
 
