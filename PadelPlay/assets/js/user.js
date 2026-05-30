function pilihSlot(jam) {

    document.querySelectorAll('.slot-btn').forEach(btn => {
        btn.classList.remove('selected');
    });

    const btn = document.querySelector(`.slot-btn[data-jam="${jam}"]`);
    if (btn) {
        btn.classList.add('selected');
    }

    const input = document.getElementById('jam_mulai');
    if (input) input.value = jam;

    updateRingkasan();
}

function updateRingkasan() {
    const lapanganSelect = document.getElementById('lapangan_id');
    const tanggal = document.getElementById('tanggal') ? document.getElementById('tanggal').value : '';
    const jamMulai = document.getElementById('jam_mulai') ? document.getElementById('jam_mulai').value : '';
    const durasi = document.getElementById('durasi_select')
    ? parseInt(document.getElementById('durasi_select').value)
    : 1;

    const elMulai = document.getElementById('ringkasan_mulai');
    const elSelesai = document.getElementById('ringkasan_selesai');
    const elTotal = document.getElementById('ringkasan_total');
    const elLapangan = document.getElementById('ringkasan_lapangan');
    const elTanggal = document.getElementById('ringkasan_tanggal');

    if (elLapangan && lapanganSelect) {
        const opt = lapanganSelect.options[lapanganSelect.selectedIndex];
        elLapangan.textContent = opt ? opt.text : '-';
    }

    if (elTanggal) elTanggal.textContent = tanggal || '-';

    if (jamMulai && durasi) {

        const [h, m] = jamMulai.split(':').map(Number);
        const selesaiH = h + durasi;
        const selesaiStr = String(selesaiH).padStart(2, '0') + ':' + String(m).padStart(2, '0');

        if (elMulai) elMulai.textContent = jamMulai;
        if (elSelesai) elSelesai.textContent = selesaiStr;

        const inputSelesai = document.getElementById('jam_selesai');
        if (inputSelesai) inputSelesai.value = selesaiStr;
    } else {
        if (elMulai) elMulai.textContent = '-';
        if (elSelesai) elSelesai.textContent = '-';
    }

    if (elTotal && lapanganSelect) {
        const harga = parseInt(lapanganSelect.selectedOptions[0]?.dataset.harga || 0);
        const total = harga * durasi;
        elTotal.textContent = 'Rp ' + total.toLocaleString('id-ID');
    }
}

function konfirmaBatal(bookingId) {
    if (confirm('Yakin mau batalkan booking ini?')) {
        window.location.href = 'riwayat.php?batal=' + bookingId;
    }
}

function autoHideAlert() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 10000);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    autoHideAlert();

    document.querySelectorAll('.slot-btn:not(.booked)').forEach(btn => {
        btn.addEventListener('click', function () {
            pilihSlot(this.dataset.jam);
        });
    });

    const durasi = document.getElementById('durasi_select');
    const lapangan = document.getElementById('lapangan_id');
    const tanggal = document.getElementById('tanggal');

if (durasi) durasi.addEventListener('change', updateRingkasan);
    if (lapangan) lapangan.addEventListener('change', function () {

        const form = document.getElementById('filter-form');
        if (form) form.submit();
    });
    if (tanggal) tanggal.addEventListener('change', function () {
        const form = document.getElementById('filter-form');
        if (form) form.submit();
    });

    updateRingkasan();
});
