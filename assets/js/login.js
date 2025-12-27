// Login Page JavaScript

function switchTab(tab) {
    // Update tabs
    document.querySelectorAll('.auth-tab').forEach((t) => t.classList.remove('active'));
    event.target.classList.add('active');

    // Update forms
    document.querySelectorAll('.auth-form').forEach((f) => f.classList.remove('active'));
    document.getElementById(tab + '-form').classList.add('active');
}
