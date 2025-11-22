let username = '';
let fullname = '';
let role = '';
let imageProfile = '';
let email = '';
let token = '';
let userId = '';
let branchId = '';
let branchName = '';

$(document).ready(function() {

  let getAuthUser = localStorage.getItem('vet-clinic');
  if (!getAuthUser) {
    location.href = $('.baseUrl').val() + '/masuk';
  } else {
    getAuthUser = JSON.parse(getAuthUser);

    username     = getAuthUser.username;
    fullname     = getAuthUser.fullname;
    role         = getAuthUser.role.toLowerCase();
    imageProfile = getAuthUser.image_profile;
    userId       = getAuthUser.user_id;
    token        = getAuthUser.token;
    email        = getAuthUser.email;
    branchId     = getAuthUser.branch_id;
    branchName   = getAuthUser.branch_name;

    $('.username-txt').append(username);
    $('.nameAndRole-txt').append(fullname + ' - ' + role + ' - ' + branchName);

    const setUrlImage = `${$('.baseUrl').val()}${imageProfile ? imageProfile : '/assets/image/avatar-default.svg'}`;
    $('.image-header').attr("src", setUrlImage);

    const getUrl = window.location.pathname;
    if (getUrl.includes('profil')) {
      $('.content-header').append('<h3>User Profil</h3>')
    } else if(getUrl == '/') {
      $('.content-header').append('<h2>Selamat datang di Sistem Administrasi Stella Vet Clinic</h2>')
    }

    const hostname = window.location.hostname;

    if (hostname === 'admin.bintangvet.com' || hostname === 'uat.bintangvet.com' || hostname === '127.0.0.1') {

      if (role === 'admin') {
        $('.menuOmset').show();
      } else if (role === 'resepsionis') {
        $('.menuOmset').hide();
      } else if (role === 'dokter') {
        $('.menuOmset').hide();
      }

    } else {
      $('.menuOmset').hide();
    }

    if (role === 'admin') {
      $('.menuPasien').show();   $('.menuPendaftaran').show();
      $('.menuDokter').show();   $('.menuTindakan').show();
      $('.menuGudang').show();   $('.menuPembayaran').show();
      $('.menuKeuangan').show(); $('.menuKunjungan').show();
      $('.menuCabang').show();   $('.menuUser').show();
      $('.menuPeriksa').show();  $('.menuPenggajian').show();
      $('.menuPengeluaran').show(); $('.menuRekap').show();
    } else if (role === 'resepsionis') {
      $('.menuPasien').show();   $('.menuPendaftaran').show();
      $('.menuTindakan').show(); $('.menuPembayaran').show();
      $('.menuKunjungan').show(); $('.menuGudang').show();
      $('.menuPenggajian').show(); $('.menuPeriksa').show();
      $('.menuPengeluaran').show(); $('.menuKeuangan').show();
      $('.menuRekap').hide();
    } else if (role === 'dokter') {
      $('.menuDokter').show();   $('.menuPasien').show();
      $('.menuTindakan').show(); $('.menuGudang').show();
      $('.menuKunjungan').show(); $('.menuPeriksa').show();
      $('.menuPendaftaran').show(); $('.menuKeuangan').show();
      $('.menuPembayaran').show(); $('.menuPenggajian').show();
      $('.menuPengeluaran').show(); $('.menuRekap').hide();

    }
  }

  // set active class for current page
  const origin = window.location.origin;
  const pathName = window.location.pathname;
  const fullPath = origin + pathName;
  $('.sidebar-menu a').each(function(Key, Value) {
    if ( Value['href'] === fullPath) {
      $(Value).parent().addClass('active');

      if (pathName === '/kategori-barang' || pathName === '/satuan-barang'  || pathName === '/daftar-barang' || pathName === '/daftar-barang-petshop'
        || pathName === '/pembagian-harga-barang' || pathName === '/pembagian-harga-barang-petshop' || pathName === '/kelompok-obat' || pathName === '/pembagian-harga-kelompok-obat') {
        $('.menuGudang').addClass('active');
      } else if(pathName === '/kategori-jasa' || pathName === '/daftar-jasa' || pathName === '/pembagian-harga-jasa' || pathName === '/metode-pembayaran' ) {
        $('.menuLayanan').addClass('active');
      } else if (pathName === '/rawat-jalan' || pathName === '/rawat-inap') {
        $('.menuPendaftaran').addClass('active');
      } else if (pathName === '/dokter-rawat-jalan' || pathName === '/dokter-rawat-inap') {
        $('.menuDokter').addClass('active');
      } else if (pathName === '/laporan-keuangan-harian' || pathName === '/laporan-keuangan-mingguan' || pathName === '/laporan-keuangan-bulanan' || pathName === '/laporan-keuangan-rekap'|| pathName === '/laporan-keuangan-omset') {
        $('.menuKeuangan').addClass('active');
      }
    } else {
      // additional custom url
      if (Value['href'] ==  origin + '/pembayaran'
        && (pathName == '/pembayaran/tambah' || pathName.includes('/pembayaran/edit') || pathName.includes('/pembayaran/detail'))) {
        $(Value).parent().addClass('active');
      } else if (Value['href'] ==  origin + '/pasien' && pathName.includes('/riwayat-pameriksaan')) {
        $(Value).parent().addClass('active');
      } else if (Value['href'] ==  origin + '/hasil-pemeriksaan' && (pathName == '/hasil-pemeriksaan/tambah'
        || pathName.includes('/hasil-pemeriksaan/edit') || pathName.includes('/hasil-pemeriksaan/detail'))) {
        $(Value).parent().addClass('active');
      } else if ((Value['href'] ==  origin + '/laporan-keuangan-harian' && (pathName.includes('/laporan-keuangan-harian/detail')))
        || (Value['href'] ==  origin + '/laporan-keuangan-mingguan' && (pathName.includes('/laporan-keuangan-mingguan/detail')))
        || (Value['href'] ==  origin + '/laporan-keuangan-bulanan' && (pathName.includes('/laporan-keuangan-bulanan/detail')))
        || (Value['href'] ==  origin + '/rekap-keuangan-bulanan') ) {
        $('.menuKeuangan').addClass('active'); $(Value).parent().addClass('active');
      }
    }
  });
  $('#btn-profil').click(function() {
    window.location.href = $('.baseUrl').val() + `/profil/${userId}`;
  });

  $('#btn-logout').click(function() {
    const fd = new FormData();
    fd.append('username', username);

    $.ajax({
      url : $('.baseUrl').val() + '/api/keluar',
      type: 'POST',
      dataType: "json",
      headers: { 'Authorization': `Bearer ${token}` },
      data: fd, contentType: false, cache: false,
      processData: false,
      success:function(resp) {
        localStorage.removeItem('vet-clinic');
        location.href = $('.baseUrl').val() + '/masuk';
      },
      error: function(err) {
        if (err.status == 401) {
          localStorage.removeItem('vet-clinic');
          location.href = $('.baseUrl').val() + '/masuk';
        }
      }
    });
  });

  function showTime() {
    let months  = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    let myDays  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    let date    = new Date();
    let day     = date.getDate();
    let month   = date.getMonth();
    let thisDay = date.getDay();
    let nowDay   = myDays[thisDay];
    let yy      = date.getYear();
    let year    = (yy < 1000) ? yy + 1900 : yy;

    let today       = new Date();
    let curr_hour   = today.getHours();
    let curr_minute = today.getMinutes();
    let curr_second = today.getSeconds();
    let a_p  = (curr_hour < 12) ? "AM" : "PM";

    if (curr_hour == 0) { curr_hour = 12; }
    if (curr_hour > 12) { curr_hour = curr_hour - 12; }

    curr_hour = checkTime(curr_hour);
    curr_minute = checkTime(curr_minute);
    curr_second = checkTime(curr_second);

    let newTime = curr_hour + ":" + curr_minute + ":" + curr_second + " " + a_p;
    let newDay = nowDay + ', ' + day + ' ' + months[month] + ' ' + year;

    // document.getElementById('time').innerHTML= ;

    $('#time-text').text(newDay + ' ' + newTime);
  }

  function checkTime(i) {
    if (i < 10) { i = "0" + i; }
    return i;
  }
  setInterval(showTime, 500);
});

function generatePagination(currentPage, totalPage) {

  let showPage = 5; // Links per page
  let rangeMiddle = 3;
  let min = null;
  let max = null;
  let stringPaginationNumber = '';

  if ((currentPage <= rangeMiddle) || (totalPage <= showPage)) {
    min = 1;
    max = (totalPage <= showPage) ? totalPage : showPage;
  } else {
    let etPage = currentPage + (rangeMiddle - 1);
    max = (etPage <= totalPage) ? etPage : totalPage;
    min = max - (showPage - 1);
  }

  $('.pagination li').remove();

  if(totalPage > 0) {
    stringPaginationNumber += `<li><a class="arrow-left ${(currentPage === 1) ? 'disabled': ''}">«</a></li>`;

    for (let i = min; i <= max; i++) {
      stringPaginationNumber += `<li><a class="number-pagination ${(i === currentPage) ? 'active': ''}">${i}</a></li>`;
    }

    stringPaginationNumber += `<li><a class="arrow-right ${(currentPage === totalPage) ? 'disabled': ''}">»</a></li>`;
  }

  $('.pagination').append(stringPaginationNumber);
}
