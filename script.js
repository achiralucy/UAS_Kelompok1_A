document.addEventListener("DOMContentLoaded", function () {

    const btnMenu = document.getElementById('menu');
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('main');

    if (btnMenu) {
        btnMenu.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
            main.classList.toggle('full');
        });
    }

    const menuProfile = document.getElementById("menuProfile");
    const menuPassword = document.getElementById("menuPassword");
    const profileForm = document.getElementById("profileForm");
    const passwordForm = document.getElementById("passwordForm");

    if (menuProfile && menuPassword) {
        menuProfile.addEventListener("click", function () {
            profileForm.style.display = "block";
            passwordForm.style.display = "none";
            menuProfile.classList.add("active");
            menuPassword.classList.remove("active");
        });

        menuPassword.addEventListener("click", function () {
            profileForm.style.display = "none";
            passwordForm.style.display = "block";
            menuPassword.classList.add("active");
            menuProfile.classList.remove("active");
        });
    }

    if (document.getElementById('daftar-lapangan')) {
        loadLapangan();
    }

});

function loadLapangan() {
    fetch("daftar_lapangan.php")
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('daftar-lapangan');

            container.innerHTML = data
                .filter(l => l.status.toLowerCase() === 'aktif')
                .map(l => `
                    <div class="court-card">
                        <h3>${l.nama}</h3>
                        <p>Harga: <b>Rp ${parseInt(l.harga).toLocaleString('id-ID')} / Jam</b></p>
                        <button class="btn-pink" onclick="bukaBooking('${l.nama}')">Booking</button>
                    </div>
                `).join('');
        });
}

function bukaBooking(nama) {
    document.getElementById('modal-booking').style.display = 'flex';
    document.getElementById('input-lapangan').value = nama;

    const today = new Date().toISOString().split("T")[0];
    document.getElementById('input-tanggal').value = today;
    document.getElementById('input-tanggal').min = today;
}

function tutupBooking() {
    document.getElementById('modal-booking').style.display = 'none';
}