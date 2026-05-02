function toggleSidebar() {
  var sidebar = document.getElementById('sidebar');
  var main = document.getElementById('main');
  sidebar.classList.toggle('hidden');
  main.classList.toggle('full');
}

function showPage(page, el) {
  var pages = document.querySelectorAll('.page');
  for (var i = 0; i < pages.length; i++) {
    pages[i].classList.remove('active');
  }

  var menus = document.querySelectorAll('.menu-item');
  for (var i = 0; i < menus.length; i++) {
    menus[i].classList.remove('active');
  }

  document.getElementById('page-' + page).classList.add('active');
  el.classList.add('active');

  var titles = {
    dashboard: 'Dashboard',
    lapangan: 'Data Lapangan',
    booking: 'Data Booking',
    pengguna: 'Data Pengguna'
  };
  document.getElementById('nav-title').textContent = titles[page];
}

function bukaModalLogout() {
  document.getElementById('modal-logout').style.display = 'flex';
}

function tutupModalLogout() {
  document.getElementById('modal-logout').style.display = 'none';
}

function bukaModal() {
  document.getElementById('modal-lapangan').style.display = 'flex';
}

function tutupModal() {
  document.getElementById('modal-lapangan').style.display = 'none';
}

function loadLapangan() {
  fetch("get_lapangan.php")
    .then(res => res.json())
    .then(data => {
      var table = document.getElementById("lapanganTable");
      if (!table) return;

      table.innerHTML = "";

      for (var i = 0; i < data.length; i++) {
        var l = data[i];
        table.innerHTML += `
          <tr>
            <td>${i + 1}</td>
            <td>${l.nama}</td>
            <td>Rp ${l.harga}</td>
            <td><span class="badge badge-aktif">${l.status}</span></td>
            <td>
              <button onclick="editLapangan(${l.id})" class="btn-edit">Edit</button>
              <button onclick="hapusLapangan(${l.id})" class="btn-hapus">Hapus</button>
            </td>
          </tr>
        `;
      }
    });
}

function loadDashboard(){
  fetch("admin_dashboard.php")
    .then(res => res.json())
    .then(data => {
      var lap = document.getElementById("totalLapangan");
      var book = document.getElementById("totalBooking");
      var user = document.getElementById("totalUser");

      if(lap) lap.innerText = data.lapangan;
      if(book) book.innerText = data.booking;
      if(user) user.innerText = data.user;
    });
}

var idHapus = null;

function hapusLapangan(id){
  idHapus = id;
  document.getElementById('modal-hapus').style.display = 'flex';
}

function confirmHapus(){
  fetch("hapus_lapangan.php?id=" + idHapus)
    .then(() => {
      tutupModalHapus();
      loadLapangan();
    });
}

function tutupModalHapus(){
  document.getElementById('modal-hapus').style.display = 'none';
  idHapus = null;
}

function editLapangan(id){
  fetch("get_lapangan.php")
    .then(res => res.json())
    .then(data => {
      var l = data.find(item => item.id == id);

      if(!l) return;

      document.getElementById('edit-id').value = l.id;
      document.getElementById('edit-nama').value = l.nama;
      document.getElementById('edit-harga').value = l.harga;
      document.getElementById('edit-status').value = l.status;

      document.getElementById('modal-edit').style.display = 'flex';
    });
}

function tutupModalEdit(){
  document.getElementById('modal-edit').style.display = 'none';
}

document.addEventListener("DOMContentLoaded", function () {

  loadLapangan();
  loadDashboard();

  var formEdit = document.getElementById("form-edit");

  if(formEdit){
    formEdit.addEventListener("submit", function(e){
      e.preventDefault();

      var id = document.getElementById('edit-id').value;
      var nama = document.getElementById('edit-nama').value;
      var harga = document.getElementById('edit-harga').value;
      var status = document.getElementById('edit-status').value;

      fetch("edit_lapangan.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `id=${id}&nama=${nama}&harga=${harga}&status=${status}`
      })
      .then(() => {
        tutupModalEdit();
        loadLapangan();
      });
    });
  }

});