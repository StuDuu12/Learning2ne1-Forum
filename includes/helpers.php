<?php
function url($path = '')
{
    return  '/' . ltrim($path, '/');
}

function asset($path)
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect_to($path)
{
    header("Location: " . url($path));
    exit;
}
