<?php

/**
 * Helper Functions - Các hàm hỗ trợ đơn giản
 */

/**
 * Tạo URL tuyệt đối
 */
function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Tạo URL cho asset (css, js, images)
 */
function asset($path)
{
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Redirect đến một trang
 */
function redirect_to($path)
{
    header("Location: " . url($path));
    exit;
}
