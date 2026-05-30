function bukaModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function tutupModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

function tampilkanPratinjauFoto(input, containerId, imageId) {
    const img = document.getElementById(imageId);
    const wrap = document.getElementById(containerId);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (img && wrap) {
                img.src = e.target.result;
                wrap.classList.add('show');
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function editLapangan(data) {
    document.getElementById('edit_id').value        = data.id;
    document.getElementById('edit_nama').value      = data.nama;
    document.getElementById('edit_lokasi').value    = data.lokasi;
    document.getElementById('edit_deskripsi').value = data.deskripsi || '';
    document.getElementById('edit_harga').value     = data.harga;
    document.getElementById('edit_status').value    = data.status;
    document.getElementById('edit_foto_lama').value = data.foto || '';

    const fotoSrc = data.foto 
        ? '../../assets/images/' + data.foto 
        : '../../assets/images/Padel.jpeg';
    document.getElementById('edit_foto_preview_lama').src = fotoSrc;

    document.getElementById('foto-edit').value = '';
    const previewEdit = document.getElementById('kotak-intip-edit');
    if (previewEdit) previewEdit.classList.remove('show');
    const imgEdit = document.getElementById('gambar-intip-edit');
    if (imgEdit) imgEdit.src = '';

    bukaModal('modal-edit-lapangan');
}

function modalHapusLapangan(url, nama) {
    document.getElementById('hapus-nama-lapangan').textContent = nama;
    document.getElementById('hapus-url-lapangan').href          = url;
    bukaModal('modal-hapus-lapangan');
}

function updateStatus(bookingId, status) {
    const label = status === 'confirmed' ? 'konfirmasi' : 'batalkan';
    if (confirm(`Yakin mau ${label} booking ini?`)) {
        window.location.href = `booking_admin.php?update_status=${bookingId}&status=${status}`;
    }
}

function tampilModalLogout(e) {
    e.preventDefault();
    bukaModal('modal-logout');
}

document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 10000);
    });
});