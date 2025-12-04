// Fake captcha handler
const fakeCaptchaLabel = document.getElementById('fakeCaptchaLabel');
if (fakeCaptchaLabel) {
    fakeCaptchaLabel.addEventListener('click', function(e) {
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
}

// Avatar upload preview
const avatarUpload = document.getElementById('avatar_upload');
const avatarPreviewImg = document.getElementById('avatar-preview-img');

if (avatarUpload && avatarPreviewImg) {
    avatarUpload.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Povolené formáty jsou pouze JPG, PNG a GIF.');
                e.target.value = '';
                return;
            }
            
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('Soubor je příliš velký. Maximální velikost je 5MB.');
                e.target.value = '';
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                avatarPreviewImg.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
}