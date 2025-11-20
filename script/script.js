document.getElementById('fakeCaptchaLabel').addEventListener('click', function(e) {
    const checkbox = document.getElementById('fakeCaptcha');
    const submitBtn = document.querySelector('.submit-button');

    // If it's already checked → do nothing (prevents unchecking)
    if (checkbox.checked) {
        e.preventDefault();
        return false;
    }

    // First click only: check it and enable button
    setTimeout(() => {
        checkbox.checked = true;
        submitBtn.disabled = false;
        submitBtn.style.opacity = "1";
        submitBtn.style.cursor = "pointer";
    }, 300);
});