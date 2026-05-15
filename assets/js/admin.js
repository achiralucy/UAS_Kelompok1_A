function bukaModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function tutupModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('show');
        document.body.style.overflow = '';
    }
});

function konfirmasiHapus(url, nama) {
    if (confirm(`Yakin mau hapus "${nama}"?\nData yang dihapus tidak bisa dikembalikan.`)) {
        window.location.href = url;
    }
}

function editLapangan(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_nama').value = data.nama;
    document.getElementById('edit_lokasi').value = data.lokasi;
    document.getElementById('edit_deskripsi').value = data.deskripsi;
    document.getElementById('edit_harga').value = data.harga_per_jam;
    document.getElementById('edit_status').value = data.status;
    bukaModal('modal-edit-lapangan');
}

function updateStatus(bookingId, status) {
    const label = status === 'confirmed' ? 'konfirmasi' : 'batalkan';
    if (confirm(`Yakin mau ${label} booking ini?`)) {
        window.location.href = `booking_admin.php?update_status=${bookingId}&status=${status}`;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 4500);
    });
});