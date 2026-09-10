// Modal Elements
const modal = document.getElementById('authModal');
const openLoginBtn = document.getElementById('openLoginModalBtn');
const openRegisterBtn = document.getElementById('openRegisterModalBtn');
const closeModal = document.querySelector('.close-modal');
const tabBtns = document.querySelectorAll('.tab-btn');
const loginTab = document.getElementById('loginTab');
const registerTab = document.getElementById('registerTab');

if (openLoginBtn) openLoginBtn.onclick = () => { modal.classList.add('active'); switchTab('login'); };
if (openRegisterBtn) openRegisterBtn.onclick = () => { modal.classList.add('active'); switchTab('register'); };
if (closeModal) closeModal.onclick = () => modal.classList.remove('active');
window.onclick = (e) => { if(e.target === modal) modal.classList.remove('active'); };

function switchTab(tab) {
    if(tab === 'login') {
        loginTab.classList.add('active');
        registerTab.classList.remove('active');
        tabBtns[0].classList.add('active');
        tabBtns[1].classList.remove('active');
    } else {
        registerTab.classList.add('active');
        loginTab.classList.remove('active');
        tabBtns[1].classList.add('active');
        tabBtns[0].classList.remove('active');
    }
}
tabBtns[0].onclick = () => switchTab('login');
tabBtns[1].onclick = () => switchTab('register');

// Login AJAX
document.getElementById('loginForm').onsubmit = async (e) => {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    const errorDiv = document.getElementById('loginError');
    const res = await fetch('login-process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
    });
    const data = await res.json();
    if(data.success) {
        location.reload(); // refresh page to show logged-in state
    } else {
        errorDiv.innerText = data.error;
        errorDiv.style.display = 'block';
    }
};

// Register AJAX
document.getElementById('registerForm').onsubmit = async (e) => {
    e.preventDefault();
    const name = document.getElementById('regName').value;
    const email = document.getElementById('regEmail').value;
    const password = document.getElementById('regPassword').value;
    const confirm = document.getElementById('regConfirm').value;
    const errorDiv = document.getElementById('registerError');
    if(password !== confirm) {
        errorDiv.innerText = 'Passwords do not match';
        errorDiv.style.display = 'block';
        return;
    }
    const res = await fetch('register-process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
    });
    const data = await res.json();
    if(data.success) {
        alert('Registration successful! Please login.');
        switchTab('login');
        document.getElementById('loginEmail').value = email;
    } else {
        errorDiv.innerText = data.error;
        errorDiv.style.display = 'block';
    }
};