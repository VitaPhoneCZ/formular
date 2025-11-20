<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Account</title>
    <link rel="stylesheet" href="style/style.css">
    <script src="script/script.js" defer></script>
</head>
<body>
    <main>
        <section class="form-container">
            <div class="form-header">
                <img src="img/logo.png" alt="logo" class="logo">
                <h2>Create an Account</h2>
            </div>
            <form action="#">
                <div class="form-group">
                    <label for="username">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Username
                    </label>
                    <input type="text" id="username" placeholder="Enter Your Username" class="input-field">
                </div>
                <div class="form-group">
                    <label for="password">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Password
                    </label>
                    <input type="password" id="password" placeholder="Enter Your Password" class="input-field">
                </div>
                <div class="form-group">
                    <label for="confirm-password">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Confirm Password
                    </label>
                    <input type="password" id="confirm-password" placeholder="Confirm Your Password" class="input-field">
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="terms" class="checkbox">
                    <label for="terms">I agree with terms of service and privacy policy</label>
                </div>
                <div class="captcha">
                    <label class="cf-challenge" id="fakeCaptchaLabel">
                        <input type="checkbox" id="fakeCaptcha">
                        <span class="checkmark">
                            <svg class="check-icon" viewBox="0 0 24 24">
                                <path d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        <span class="cf-text">Nejsem robot</span>
                    </label>

                    <div class="cloudflare">
                        
                        <div>
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4b/Cloudflare_Logo.svg/1024px-Cloudflare_Logo.svg.png" 
                                 alt="Cloudflare" class="cloudflare-logo">
                            <small style="color:#808080;">Soukromí - Podmínky</small>
                        </div>
                    </div>
                </div>
                <button type="submit" class="submit-button">Create Account</button>
            </form>
            <div class="form-footer">
                <p>Already have an account? <a href="#">Sign In</a></p>
            </div>
        </section>
    </main>
</body>
</html>