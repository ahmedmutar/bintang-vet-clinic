$(document).ready(function () {
  let optPasien = '';
  let optMetodePembayaran = '';
  let isValidSelectedPasien = false;
  let isValidMetodePembayaran = false;
  let isValidCalculationPay = false;
  let listPasien = [];
  let selectedListJasa = [];
  let selectedListBarang = [];
  let listTagihanJasa = [];
  let listTagihanBarang = [];
  let calculationPay = [];
  let listBarang = [];
  let listSelectedBarangPetShop = [];

  let isBeErr = false;

  // if (role.toLowerCase() == 'dokter') {
  //   window.location.href = $('.baseUrl').val() + `/unauthorized`;
  // }

  loadPasien();
  loadMetodePembayaran();
  refreshText();
  refreshForm();
  formConfigure();

  $('.btn-back-to-list .text, #btnKembali').click(function () {
    window.location.href = $('.baseUrl').val() + '/pembayaran';
  });

  $('#metodePembayaran').on('select2:select', function (e) {
    validationForm();
  });

  $('#petShop').on('select2:select', function (e) {
    const getBarangId = $(this).val();
    let getObj = listBarang.find(barang => barang.id == getBarangId);
    listSelectedBarangPetShop.push({
      id: getObj.id,
      item_name: getObj.item_name,
      total_item: 0,
      selling_price: getObj.selling_price,
      price_overall: 0
    });
    drawTableSelectedBarang();

    // deleted list barang
    let getIdxBarang = listBarang.findIndex(barang => barang.id == getBarangId);
    listBarang.splice(getIdxBarang, 1);
    drawDropdownListBarang();

    validationForm();
  });

  function drawDropdownListBarang() {
    optBarang = `<option value=''>Pilih Barang</option>`;
    $('#selectedBarang option').remove();

    if (listBarang.length) {
      for (let i = 0 ; i < listBarang.length ; i++) {
        optBarang += `<option value=${listBarang[i].id}>${listBarang[i].item_name}</option>`;
      }
    }
    $('#selectedBarang').append(optBarang);
  }

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

          data.services.forEach(s => { s.new_price_overall = s.price_overall; }); data.item.forEach(i => { i.new_price_overall = i.price_overall; });
          selectedListJasa = data.services; selectedListBarang = data.item;
          processAppendListSelectedJasa(); processAppendListSelectedBarang();
          loadPetShop(data.user.branch_id);

        }, complete: function () { $('#loading-screen').hide(); },
        error: function (err) {
          if (err.status == 401) {
            localStorage.removeItem('vet-clinic');
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

  $('#list-selected-jasa').on('input', '.diskon-list-jasa', function() {
    const idx = $(this).attr('index');
    const getPriceOverall = selectedListJasa[idx].price_overall;
    let newPriceOverall = selectedListJasa[idx].price_overall;

    selectedListJasa[idx].discount = parseFloat($(this).val());
    selectedListJasa[idx].amount_discount = (parseFloat($(this).val()) / 100) * getPriceOverall;
    newPriceOverall = getPriceOverall - selectedListJasa[idx].amount_discount;

    selectedListJasa[idx].new_price_overall = newPriceOverall;

    $(`#totalJasa-${idx}`).text(
      newPriceOverall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'));
  });

  $('#list-selected-barang').on('input', '.diskon-list-barang', function() {
    const idx = $(this).attr('index');
    const getPriceOverall = selectedListBarang[idx].price_overall;
    let newPriceOverall = selectedListBarang[idx].price_overall;

    selectedListBarang[idx].discount = parseFloat($(this).val());
    selectedListBarang[idx].amount_discount = (parseFloat($(this).val()) / 100) * getPriceOverall;
    newPriceOverall = getPriceOverall - selectedListBarang[idx].amount_discount;

    selectedListBarang[idx].new_price_overall = newPriceOverall;

    $(`#totalBarang-${idx}`).text(
      newPriceOverall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'));
  });

  function processSaved() {

    let finalSelectedJasa = []; let finalSelectedBarang = [];

    const fd = new FormData();
    fd.append('check_up_result_id', $('#selectedPasien').val());
    fd.append('payment_method_id', $('#metodePembayaran').val());

    calculationPay.forEach(dt => {
      if (dt.type == 'jasa') {
        finalSelectedJasa.push({ detail_service_patient_id: dt.id, discount: dt.discount, amount_discount: dt.amount_discount });
      } else {
        finalSelectedBarang.push({ id: dt.id, medicine_group_id: dt.medicineGroupId, quantity: dt.quantity, discount: dt.discount, amount_discount: dt.amount_discount });
      }
    });
    fd.append('service_payment', JSON.stringify(finalSelectedJasa));
    fd.append('item_payment', JSON.stringify(finalSelectedBarang));
    fd.append('petshop_payment',JSON.stringify(listSelectedBarangPetShop));

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
          localStorage.removeItem('vet-clinic');
          location.href = $('.baseUrl').val() + '/masuk';
        }
      }
    });
  }

  function formConfigure() {
    $('#selectedPasien').select2();
    $('#metodePembayaran').select2({placeholder: 'Pilih Metode Pembayaran'});
    $('#petShop').select2({placeholder: 'Pilih Barang'});
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
          Number(lj.selling_price || 0).toLocaleString('id-ID')
          // typeof (lj.selling_price) == 'number' ? lj.selling_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
        }</td>`
        + `<td class="d-flex align-item-c">
            <input type="number" min="0" max="100" maxlength="3" class="form-control diskon-list-jasa" index=${idx} value=${lj.discount} style="width:65px">&nbsp;%
          </td>`
        + `<td>
              <span id="totalJasa-${idx}">${
                Number(lj.price_overall || 0).toLocaleString('id-ID')
                // typeof(lj.price_overall) == 'number' ? lj.price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
              }</span>
          </td>`
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
        calculationPay.push({ id: getDetailJasa.detail_service_patient_id, type: 'jasa', price: getDetailJasa.new_price_overall,
          discount: getDetailJasa.discount, amount_discount: getDetailJasa.amount_discount });
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
            // typeof (lj.new_price_overall) == 'number' ? lj.new_price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
            Number(lj.new_price_overall || 0).toLocaleString('id-ID')
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
        + `<td>${lb.quantity}</td>`
        + `<td>${
          // typeof (lb.each_price) == 'number' ? lb.each_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
          Number(lb.each_price || 0).toLocaleString('id-ID')
        }</td>`
        + `<td class="d-flex align-item-c">
            <input type="number" min="0" max="100" maxlength="3" class="form-control diskon-list-barang" index=${idx} value=${lb.discount} style="width:65px">&nbsp;%
          </td>`
        + `<td>
              <span id="totalBarang-${idx}">${
                Number(lb.price_overall || 0).toLocaleString('id-ID')
                // typeof(lb.price_overall) == 'number' ? lb.price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
              }</span>
          </td>`
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
        calculationPay.push({ id: getDetailBarang.id, medicineGroupId: getDetailBarang.medicine_group_id, type: 'barang',
        quantity: getDetailBarang.quantity, price: getDetailBarang.new_price_overall, discount: getDetailBarang.discount, amount_discount: getDetailBarang.amount_discount });
      } else {
        const getIdxTagihanBarang = listTagihanBarang.findIndex(i => i.id == getDetailBarang.id);
        const getIdxCalculation = calculationPay.findIndex(i => (i.type == 'barang' && i.id == getDetailBarang.id));

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
          + `<td>${lb.quantity}</td>`
          + `<td>${

            // typeof (lb.each_price) == 'number' ? lb.each_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
            Number(lb.each_price || 0).toLocaleString('id-ID')
          }</td>`
          + `<td>${

            // typeof (lb.new_price_overall) == 'number' ? lb.new_price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
            Number(lb.new_price_overall || 0).toLocaleString('id-ID')
          }</td>`
          + `</tr>`;
        ++no;
      });
    } else { rowListTagihanBarang += `<tr class="text-center"><td colspan="10">Tidak ada data.</td></tr>` }
    $('#list-tagihan-barang').append(rowListTagihanBarang);
  }

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

  // function processPrint(check_up_result_id, service_payment, item_payment) {
  //   let url = '/pembayaran/print/' + check_up_result_id + '/' + service_payment + '/' + item_payment;
  //   window.open($('.baseUrl').val() + url, '_blank');
  // }

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
          localStorage.removeItem('vet-clinic');
          location.href = $('.baseUrl').val() + '/masuk';
        }
      }
    });
  }

  function loadMetodePembayaran() {
    $.ajax({
      url     : $('.baseUrl').val() + '/api/metode-pembayaran',
      headers : { 'Authorization': `Bearer ${token}` },
      type    : 'GET',
      beforeSend: function() { $('#loading-screen').show(); },
      success: function(data) {
        const getData = data;
        optMetodePembayaran += `<option value=""></option>`;

				if (getData.length) {
					for (let i = 0 ; i < getData.length ; i++) {
						optMetodePembayaran += `<option value=${getData[i].id}>${getData[i].payment_name}</option>`;
					}
				}
				$('#metodePembayaran').append(optMetodePembayaran);

      }, complete: function() { $('#loading-screen').hide(); },
      error: function(err) {
        if (err.status == 401) {
          localStorage.removeItem('vet-clinic');
          location.href = $('.baseUrl').val() + '/masuk';
        }
      }
    });
  }

  function loadPetShop(getCabangId){
    optBarang = `<option value=''>Pilih Barang</option>`;

    $.ajax({
      url: $('.baseUrl').val() + '/api/pembayaranpetshop/filteritem',
      headers: { 'Authorization': `Bearer ${token}` },
      type: 'GET',
      data: { branch_id: getCabangId },
      beforeSend: function () { $('#loading-screen').show(); },
      success: function (data) {
        $('#petShop option').remove();

        if (data.length) {
          for (let i = 0 ; i < data.length ; i++) {
            optBarang += `<option value=${data[i].id}>${data[i].item_name}</option>`;
            listBarang.push(data[i]);
          }
        }
        // $('#petShop').prop('disabled', true);
        $('#petShop').append(optBarang);

        validationForm();
      }, complete: function() { $('#loading-screen').hide(); },
      error: function(err) {
        if (err.status == 401) {
          localStorage.removeItem('vet-shop');
          location.href = $('.baseUrl').val() + '/masuk';
        }
      }
    });
  }

  function drawTableSelectedBarang() {
    let listSelectedBarangTxt = '';
    $('#list-selected-pet-shop tr').remove();

    if (listSelectedBarangPetShop.length) {
      listSelectedBarangPetShop.forEach((barang, idx) => {
        listSelectedBarangTxt += `<tr>`
          + `<td>${idx + 1}</td>`
          + `<td>${barang.item_name}</td>`
          + `<td><input type="number" min="0" class="qty-input-barang" index=${idx} value=${barang.total_item}></td>`
          + `<td>Rp ${
            // barang.selling_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')
            Number(barang.selling_price || 0).toLocaleString('id-ID')
          }</td>`
          + `<td>Rp <span id="overallPrice-${idx}">
              ${
                // typeof(barang.price_overall) == 'number' ? barang.price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
                Number(barang.price_overall || 0).toLocaleString('id-ID')
              }</span>
            </td>`
          +`<td>
              <button type="button" class="btn btn-danger btnDeleteSelectedBarang" value=${idx}>
                <i class="fa fa-trash-o" aria-hidden="true"></i>
              </button>
            </td>`
          + `</tr>`;
      });
    } else {
      listSelectedBarangTxt += `<tr class="text-center"><td colspan="7">Tidak ada data.</td></tr>`;
    }

    $('#list-selected-pet-shop').append(listSelectedBarangTxt);

    $('.qty-input-barang').on('input', function(e) {
      const idx        = $(this).attr('index');
      const value      = parseFloat($(this).val());
      const eachItem   = parseFloat(listSelectedBarangPetShop[idx].selling_price);
      let overallPrice = value * eachItem;

      listSelectedBarangPetShop[idx].total_item = value;
      listSelectedBarangPetShop[idx].price_overall = overallPrice;
      validationForm();

      $('#overallPrice-'+idx).text(overallPrice.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'));
    });

    $('.btnDeleteSelectedBarang').click(function() {
      const getObj = listSelectedBarangPetShop[$(this).val()];
      listBarang.push(getObj);

      drawDropdownListBarang();

      listSelectedBarangPetShop.splice($(this).val(), 1);
      drawTableSelectedBarang();
      validationForm();
    });
  }

  function validationForm() {
    if (!$('#selectedPasien').val()) {
      $('#pasienErr1').text('Pasien harus di isi'); isValidSelectedPasien = false;
    } else {
      $('#pasienErr1').text(''); isValidSelectedPasien = true;
    }

    if (!$('#metodePembayaran').val()) {
      $('#metodePembayaranErr1').text('Metode pembayaran harus di isi'); isValidMetodePembayaran = false;
    } else {
      $('#metodePembayaranErr1').text(''); isValidMetodePembayaran = true;
    }

    isValidCalculationPay = (!calculationPay.length) ? false : true;

    $('#beErr').empty(); isBeErr = false;

    $('#btnSubmitPembayaran').attr('disabled', (!isValidSelectedPasien || !isValidMetodePembayaran || !isValidCalculationPay || isBeErr) ? true : false);
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
    $('#metodePembayaran').val(null);
    $('#metodePembayaranErr1').text(''); isValidMetodePembayaran = true;
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
