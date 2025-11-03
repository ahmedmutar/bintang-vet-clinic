$(document).ready(function () {
  let optPasien = '';
  let isValidSelectedPasien = false;
  let isValidCalculationPay = false;
  let listPasien = [];
  let selectedListJasa = [];
  let selectedListBarang = [];
  let listTagihanJasa = [];
  let listTagihanBarang = [];
  let calculationPay = [];

  let isBeErr = false;

  if (role.toLowerCase() == 'dokter') {
    window.location.href = $('.baseUrl').val() + `/unauthorized`;
  }

  loadPasien();
  refreshText();
  refreshForm();
  formConfigure();

  $('.btn-back-to-list .text, #btnKembali').click(function () {
    window.location.href = $('.baseUrl').val() + '/pembayaran';
  });

  $('#selectedPasien').on('select2:select', function (e) {
    refreshVariableTambahPembayaran();

    if ($(this).val()) {
      $.ajax({
        url: $('.baseUrl').val() + '/api/hasil-pemeriksaan/pembayaran',
        headers: { 'Authorization': `Bearer ${token}` },
        type: 'GET',
        data: { id: $(this).val() },
        beforeSend: function () { $('#loading-screen').show(); },
        success: function (data) {

          $('#nomorPasienTxt').text(data.registration.patient_number); $('#jenisHewanTxt').text(data.registration.pet_category);
          $('#namaHewanTxt').text(data.registration.pet_name); $('#jenisKelaminTxt').text(data.registration.pet_gender);
          $('#usiaHewanTahunTxt').text(`${data.registration.pet_year_age} Tahun`); $('#usiaHewanBulanTxt').text(`${data.registration.pet_month_age} Bulan`);
          $('#namaPemilikTxt').text(data.registration.owner_name); $('#alamatPemilikTxt').text(data.registration.owner_address);
          $('#nomorHpPemilikTxt').text(data.registration.owner_phone_number); $('#nomorRegistrasiTxt').text(data.registration.registration_number);
          $('#keluhanTxt').text(data.registration.complaint); $('#namaPendaftarTxt').text(data.registration.registrant);
          $('#rawatInapTxt').text(data.status_outpatient_inpatient ? 'Ya' : 'Tidak'); $('#statusPemeriksaanTxt').text(data.status_finish ? 'Selesai' : 'Belum');

          selectedListJasa = data.services; selectedListBarang = data.item;
          processAppendListSelectedJasa(); processAppendListSelectedBarang();

        }, complete: function () { $('#loading-screen').hide(); },
        error: function (err) {
          if (err.status == 401) {
            localStorage.removeItem('vet-shop');
            location.href = $('.baseUrl').val() + '/masuk';
          }
        }
      });
    }
    validationForm();
  });

  $('#submitConfirm').click(function () {
    processSaved();
    $('#modal-confirmation .modal-title').text('Peringatan');
    $('#modal-confirmation').modal('toggle');
  });

  $('#btnSubmitPembayaran').click(function () {

    $('#modal-confirmation .modal-title').text('Peringatan');
    $('#modal-confirmation .box-body').text('Anda yakin ingin menyimpan Pembayaran ini? Data yang akan anda tambahkan tidak dapat diubah kembali.');
    $('#modal-confirmation').modal('show');
  });

  function processSaved() {

    let finalSelectedJasa = []; let finalSelectedBarang = [];

    const fd = new FormData();
    fd.append('check_up_result_id', $('#selectedPasien').val());
    calculationPay.forEach(dt => {
      if (dt.type == 'jasa') {
        finalSelectedJasa.push({ detail_service_patient_id: dt.id });
      } else {
        finalSelectedBarang.push({ detail_item_patient_id: dt.id });
      }
    });
    fd.append('service_payment', JSON.stringify(finalSelectedJasa));
    fd.append('item_payment', JSON.stringify(finalSelectedBarang));

    $.ajax({
      url: $('.baseUrl').val() + '/api/pembayaran',
      type: 'POST',
      dataType: 'JSON',
      headers: { 'Authorization': `Bearer ${token}` },
      data: fd, contentType: false, cache: false,
      processData: false,
      beforeSend: function () { $('#loading-screen').show(); },
      success: function (resp) {

        $("#msg-box .modal-body").text('Berhasil Menambah Data');
        $('#msg-box').modal('show');

        processPrint($('#selectedPasien').val(), JSON.stringify(finalSelectedJasa), JSON.stringify(finalSelectedBarang));

        setTimeout(() => {
          window.location.href = $('.baseUrl').val() + '/pembayaran';
        }, 1000);
      }, complete: function () { $('#loading-screen').hide(); }
      , error: function (err) {
        if (err.status === 422) {
          let errText = ''; $('#beErr').empty(); $('#btnSubmitPembayaran').attr('disabled', true);
          $.each(err.responseJSON.errors, function (idx, v) {
            errText += v + ((idx !== err.responseJSON.errors.length - 1) ? '<br/>' : '');
          });
          $('#beErr').append(errText); isBeErr = true;
        } else if (err.status == 401) {
          localStorage.removeItem('vet-shop');
          location.href = $('.baseUrl').val() + '/masuk';
        }
      }
    });
  }

  function formConfigure() {
    $('#selectedPasien').select2();
    $('#btnSubmitPembayaran').attr('disabled', true);
  }

  function processAppendListSelectedJasa() {
    let rowSelectedListJasa = '';
    let no = 1;
    $('#list-selected-jasa tr').remove();

    selectedListJasa.forEach((lj, idx) => {
      rowSelectedListJasa += `<tr>`
        + `<td>${no}</td>`
        + `<td>${lj.created_at}</td>`
        + `<td>${lj.created_by}</td>`
        + `<td>${lj.category_name}</td>`
        + `<td>${lj.service_name}</td>`
        + `<td>${lj.quantity}</td>`
        + `<td>${
          // typeof (lj.selling_price) == 'number' ? lj.selling_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
          Number(lj.selling_price || 0).toLocaleString('id-ID')
        }</td>`
        + `<td>${
          // typeof (lj.price_overall) == 'number' ? lj.price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
          Number(lj.price_overall || 0).toLocaleString('id-ID')
        }</td>`
        + `<td><input type="checkbox" index=${idx} class="isBayarJasa"/></td>`
        + `</tr>`;
      ++no;
    });
    $('#list-selected-jasa').append(rowSelectedListJasa);

    $('.isBayarJasa').click(function () {
      const idx = $(this).attr('index');
      const getDetailJasa = selectedListJasa[idx];

      if (this.checked) {
        listTagihanJasa.push(getDetailJasa);
        calculationPay.push({ id: getDetailJasa.detail_service_patient_id, type: 'jasa', price: getDetailJasa.price_overall });
      } else {
        const getIdxTagihanJasa = listTagihanJasa.findIndex(i => i.detail_service_patient_id == getDetailJasa.detail_service_patient_id);
        const getIdxCalculation = calculationPay.findIndex(i => (i.type == 'jasa' && i.id == getDetailJasa.detail_service_patient_id));

        listTagihanJasa.splice(getIdxTagihanJasa, 1);
        calculationPay.splice(getIdxCalculation, 1);
      }

      processAppendListTagihanJasa();
      processCalculationTagihan();
      validationForm();
    });
  }

  function processAppendListTagihanJasa() {
    let rowListTagihanJasa = '';
    let no = 1;
    $('#list-tagihan-jasa tr').remove();

    if (listTagihanJasa.length) {
      listTagihanJasa.forEach((lj) => {
        rowListTagihanJasa += `<tr>`
          + `<td>${no}</td>`
          + `<td>${lj.created_at}</td>`
          + `<td>${lj.created_by}</td>`
          + `<td>${lj.category_name}</td>`
          + `<td>${lj.service_name}</td>`
          + `<td>${lj.quantity}</td>`
          + `<td>${
            // typeof (lj.selling_price) == 'number' ? lj.selling_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
            Number(lj.selling_price || 0).toLocaleString('id-ID')
          }</td>`
          + `<td>${
            // typeof (lj.price_overall) == 'number' ? lj.price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
            Number(lj.price_overall || 0).toLocaleString('id-ID')
          }</td>`
          + `</tr>`;
        ++no;
      });
    } else { rowListTagihanJasa += `<tr class="text-center"><td colspan="8">Tidak ada data.</td></tr>` }
    $('#list-tagihan-jasa').append(rowListTagihanJasa);
  }

  function processAppendListSelectedBarang() {
    let rowSelectedListBarang = '';
    let no = 1;

    $('#list-selected-barang tr').remove();
    selectedListBarang.forEach((lb, idx) => {
      rowSelectedListBarang += `<tr>`
        + `<td>${no}</td>`
        + `<td>${lb.created_at}</td>`
        + `<td>${lb.created_by}</td>`
        + `<td>${lb.group_name}</td>`
        + `<td>${lb.item_name}</td>`
        + `<td>${lb.category_name}</td>`
        + `<td>${lb.unit_name}</td>`
        + `<td>${lb.quantity}</td>`
        + `<td>${
          // typeof (lj.selling_price) == 'number' ? lj.selling_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
          Number(lb.selling_price || 0).toLocaleString('id-ID')
        }</td>`
        + `<td>${
          // typeof (lj.price_overall) == 'number' ? lj.price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
          Number(lb.price_overall || 0).toLocaleString('id-ID')
        }</td>`
        + `<td><input type="checkbox" index=${idx} class="isBayarBarang"/></td>`
        + `</tr>`;
      ++no;
    });
    $('#list-selected-barang').append(rowSelectedListBarang);

    $('.isBayarBarang').click(function () {
      const idx = $(this).attr('index');
      const getDetailBarang = selectedListBarang[idx];

      if (this.checked) {
        listTagihanBarang.push(getDetailBarang);
        calculationPay.push({ id: getDetailBarang.detail_item_patients_id, type: 'barang', price: getDetailBarang.price_overall });
      } else {
        const getIdxTagihanBarang = listTagihanJasa.findIndex(i => i.detail_item_patients_id == getDetailBarang.detail_item_patients_id);
        const getIdxCalculation = calculationPay.findIndex(i => (i.type == 'barang' && i.id == getDetailBarang.detail_item_patients_id));

        listTagihanBarang.splice(getIdxTagihanBarang, 1);
        calculationPay.splice(getIdxCalculation, 1);
      }

      processAppendListTagihanBarang();
      processCalculationTagihan();
      validationForm();
    });

  }

  function processAppendListTagihanBarang() {
    let rowListTagihanBarang = '';
    let no = 1;
    $('#list-tagihan-barang tr').remove();

    if (listTagihanBarang.length) {
      listTagihanBarang.forEach((lb) => {
        rowListTagihanBarang += `<tr>`
          + `<td>${no}</td>`
          + `<td>${lb.created_at}</td>`
          + `<td>${lb.created_by}</td>`
          + `<td>${lb.group_name}</td>`
          + `<td>${lb.item_name}</td>`
          + `<td>${lb.category_name}</td>`
          + `<td>${lb.unit_name}</td>`
          + `<td>${lb.quantity}</td>`
          + `<td>${
            // typeof (lj.selling_price) == 'number' ? lj.selling_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
            Number(lb.selling_price || 0).toLocaleString('id-ID')
          }</td>`
          + `<td>${
            // typeof (lj.price_overall) == 'number' ? lj.price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
            Number(lb.price_overall || 0).toLocaleString('id-ID')
          }</td>`
          + `</tr>`;
        ++no;
      });
    } else { rowListTagihanBarang += `<tr class="text-center"><td colspan="10">Tidak ada data.</td></tr>` }
    $('#list-tagihan-barang').append(rowListTagihanBarang);
  }

  // function processPrint(check_up_result_id, service_payment, item_payment) {
  //   let url = '/pembayaran/print/' + check_up_result_id + '/' + service_payment + '/' + item_payment;
  //   window.open($('.baseUrl').val() + url, '_blank');
  // }
  function processPrint(check_up_result_id, service_payment, item_payment) {
    // 1. Definisikan endpoint untuk mendapatkan file PDF (receipt)
    let downloadUrl = '/pembayaran/print/' + check_up_result_id + '/' + service_payment + '/' + item_payment;
    //let downloadUrl = $('.baseUrl').val() + '/api/pembayaranpetshop/generate-receipt/' + master_payment_id;

    $.ajax({
        url: downloadUrl,
        headers: { 'Authorization': `Bearer ${token}` }, // Pastikan token didefinisikan di scope ini
        type: 'GET',
        xhrFields: { responseType: 'blob' }, // Kunci: meminta data biner (blob)
        beforeSend: function () {
            // Opsional: tampilkan loading screen jika perlu, tapi karena sudah ada di AJAX utama, ini bisa dilewati
        },
        success: function(data, status, xhr) {
            // Logika unduh file
            let disposition = xhr.getResponseHeader('content-disposition');
            // Ambil nama file dari header Content-Disposition
            let matches = /"([^"]*)"/.exec(disposition);
            let filename = (matches != null && matches[1] ? matches[1] : `receipt-${check_up_result_id}.pdf`);

            // 2. Buat Blob dengan MIME Type PDF
            let blob = new Blob([data], {type:'application/pdf'});
            let url = URL.createObjectURL(blob);

            // 3. Pemicu Unduh
            let a = document.createElement("a");
            a.href = url;
            a.download = filename; // Nama file yang akan diunduh
            document.body.appendChild(a);
            a.click();

            // Bersihkan URL object setelah diunduh
            URL.revokeObjectURL(url);
            document.body.removeChild(a);

        },
        error: function(err) {
            console.error('Gagal mengunduh PDF receipt:', err);
            alert('Gagal mengunduh struk pembayaran.');
            // Penanganan error 401 bisa ditambahkan di sini jika perlu
        }
    });
}

  function processCalculationTagihan() {
    let total = 0;

    calculationPay.forEach(calc => total += parseInt(calc.price));

    let totalText = `Rp. ${Number(total || 0).toLocaleString('id-ID')},00`;
    $('#totalBayarTxt').text(totalText);
  }

  function loadPasien() {
    $.ajax({
      url: $('.baseUrl').val() + '/api/pembayaran/pasien',
      headers: { 'Authorization': `Bearer ${token}` },
      type: 'GET',
      beforeSend: function () { $('#loading-screen').show(); },
      success: function (data) {
        optPasien += `<option value=''>Pilih Pasien</option>`
        listPasien = data;

        if (listPasien.length) {
          for (let i = 0; i < listPasien.length; i++) {
            optPasien += `<option value=${listPasien[i].check_up_result_id}>${listPasien[i].pet_name} - ${listPasien[i].registration_number}</option>`;
          }
        }
        $('#selectedPasien').append(optPasien);
      }, complete: function () { $('#loading-screen').hide(); },
      error: function (err) {
        if (err.status == 401) {
          localStorage.removeItem('vet-shop');
          location.href = $('.baseUrl').val() + '/masuk';
        }
      }
    });
  }

  function validationForm() {
    if (!$('#selectedPasien').val()) {
      $('#pasienErr1').text('Pasien harus di isi'); isValidSelectedPasien = false;
    } else {
      $('#pasienErr1').text(''); isValidSelectedPasien = true;
    }

    isValidCalculationPay = (!calculationPay.length) ? false : true;

    $('#beErr').empty(); isBeErr = false;

    $('#btnSubmitPembayaran').attr('disabled', (!isValidSelectedPasien || !isValidCalculationPay || isBeErr) ? true : false);
  }

  function refreshText() {
    $('#nomorPasienTxt').text('-'); $('#jenisHewanTxt').text('-');
    $('#namaHewanTxt').text('-'); $('#jenisKelaminTxt').text('-');
    $('#usiaHewanTahunTxt').text('- Tahun'); $('#usiaHewanBulanTxt').text('- Bulan');
    $('#namaPemilikTxt').text('-'); $('#alamatPemilikTxt').text('-');
    $('#nomorHpPemilikTxt').text('-'); $('#nomorRegistrasiTxt').text('-');
    $('#keluhanTxt').text('-'); $('#namaPendaftarTxt').text('-');
    $('#totalBayarTxt').text('-'); $('#rawatInapTxt').text('-');
    $('#statusPemeriksaanTxt').text('-');
  }

  function refreshForm() {
    $('#selectedPasien').val(null);
    $('#pasienErr1').text(''); isValidSelectedPasien = true;
    $('#beErr').empty(); isBeErr = false;
  }

  function refreshVariableTambahPembayaran() {
    selectedListJasa = []; selectedListBarang = [];
    listTagihanJasa = []; listTagihanBarang = [];
    calculationPay = [];
    processAppendListSelectedJasa();
    processAppendListSelectedBarang();
    processAppendListTagihanJasa();
    processAppendListTagihanBarang();
    refreshText();
  }

});
