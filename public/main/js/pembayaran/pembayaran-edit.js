$(document).ready(function() {

  const url = window.location.pathname;
  const stuff = url.split('/');
  const id = stuff[stuff.length-1];
  let optMetodePembayaran = '';
  let getCheckUpResultId = null;
  let isValidCalculationPay = false;
  let isValidMetodePembayaran = false;

  let selectedListJasa = [];
  let selectedListBarang = [];
  let listTagihanJasa = [];
  let listTagihanBarang = [];
  let calculationPay = [];

  // if (role.toLowerCase() == 'dokter') {
	// 	window.location.href = $('.baseUrl').val() + `/unauthorized`;
	// }

  $('#totalBayarTxt').text('-');
  $('#metodePembayaran').select2({placeholder: 'Pilih Metode Pembayaran'});

  loadMetodePembayaran();

  $('.btn-back-to-list .text, #btnKembali').click(function() {
    window.location.href = $('.baseUrl').val() + '/pembayaran';
  });

  $('#metodePembayaran').on('select2:select', function (e) {
    validationForm();
  });

  $('#submitConfirm').click(function() {
    processSaved();
    $('#modal-confirmation .modal-title').text('Peringatan');
    $('#modal-confirmation').modal('toggle');
  });

  $('#btnSubmitPembayaran').click(function() {

    $('#modal-confirmation .modal-title').text('Peringatan');
    $('#modal-confirmation .box-body').text('Anda yakin ingin merubah Pembayaran ini? Data yang akan anda tambahkan tidak dapat diubah kembali.');
    $('#modal-confirmation').modal('show');
  });

  $.ajax({
    url     : $('.baseUrl').val() + '/api/pembayaran/detail',
    headers : { 'Authorization': `Bearer ${token}` },
    type    : 'GET',
    data	  : { list_of_payment_id: id },
    beforeSend: function() { $('#loading-screen').show(); },
    success: function(data) {
      getCheckUpResultId = data.check_up_result_id;
      $('#nomorPasienTxt').text(data.registration.patient_number); $('#jenisHewanTxt').text(data.registration.pet_category);
      $('#namaHewanTxt').text(data.registration.pet_name); $('#jenisKelaminTxt').text(data.registration.pet_gender);
      $('#usiaHewanTahunTxt').text(`${data.registration.pet_year_age} Tahun`); $('#usiaHewanBulanTxt').text(`${data.registration.pet_month_age} Bulan`);
      $('#namaPemilikTxt').text(data.registration.owner_name); $('#alamatPemilikTxt').text(data.registration.owner_address);
      $('#nomorHpPemilikTxt').text(data.registration.owner_phone_number); $('#nomorRegistrasiTxt').text(data.registration.registration_number);
      $('#keluhanTxt').text(data.registration.complaint); $('#namaPendaftarTxt').text(data.registration.registrant);
      $('#rawatInapTxt').text(data.status_outpatient_inpatient ? 'Ya' : 'Tidak'); $('#statusPemeriksaanTxt').text(data.status_finish ? 'Selesai' : 'Belum');

      data.services.forEach(sr => { sr.isRevert = false; sr.new_price_overall = sr.price_overall; sr.discount = 0; });
      data.item.forEach(it => { it.isRevert = false; it.new_price_overall = it.price_overall; it.discount = 0; });

      selectedListJasa = data.services; selectedListBarang = data.item;
      processAppendListSelectedJasa(); processAppendListSelectedBarang();

      data.paid_services.forEach(sr => { sr.isRevert = false; sr.new_price_overall = sr.price_overall_after_discount });
      data.paid_item.forEach(sr => { sr.isRevert = false; sr.new_price_overall = sr.price_overall_after_discount });

      listTagihanJasa = data.paid_services;
      listTagihanBarang = data.paid_item;

      processAppendListTagihanJasa(); processAppendListTagihanBarang();
      validationForm();

    }, complete: function() { $('#loading-screen').hide(); },
    error: function(err) {
      if (err.status == 401) {
        localStorage.removeItem('vet-clinic');
        location.href = $('.baseUrl').val() + '/masuk';
      }
    }
  });

  $('#list-selected-jasa').on('input', '.diskon-list-jasa', function() {
    const idx = $(this).attr('index');
    const getPriceOverall = selectedListJasa[idx].price_overall;
    const getValue        = parseFloat($(this).val());
    let newPriceOverall   = selectedListJasa[idx].price_overall;

    selectedListJasa[idx].discount = getValue;
    selectedListJasa[idx].amount_discount = (getValue / 100) * getPriceOverall;
    newPriceOverall = getPriceOverall - selectedListJasa[idx].amount_discount;

    selectedListJasa[idx].new_price_overall = newPriceOverall;

    $(`#totalJasa-${idx}`).text(newPriceOverall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'));
  });

  $('#list-selected-barang').on('input', '.diskon-list-barang', function() {
    const idx = $(this).attr('index');
    const getPriceOverall = selectedListBarang[idx].price_overall;
    const getValue        = parseFloat($(this).val());
    let newPriceOverall   = selectedListBarang[idx].price_overall;

    selectedListBarang[idx].discount = getValue;
    selectedListBarang[idx].amount_discount = (getValue / 100) * getPriceOverall;
    newPriceOverall = getPriceOverall - selectedListBarang[idx].amount_discount;

    selectedListBarang[idx].new_price_overall = newPriceOverall;

    $(`#totalBarang-${idx}`).text(newPriceOverall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'));
  });

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
          // typeof(lj.selling_price) == 'number' ? lj.selling_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
        }</td>`
        + `<td class="d-flex align-item-c">
            <input type="number" min="0" max="100" maxlength="3" index=${idx} style="width:65px"
            class="form-control diskon-list-jasa ${role.toLowerCase() == 'admin' && !lj.status_paid_off ? 'd-block' : 'd-none'}">&nbsp;
            <span class="${role.toLowerCase() == 'admin' && !lj.status_paid_off ? 'd-block' : 'd-none'}">%</span>
          </td>`
        + `<td>
            <span id="totalJasa-${idx}">${
              Number(lj.price_overall || 0).toLocaleString('id-ID')
              // typeof(lj.price_overall) == 'number' ? lj.price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
            }</span>
          </td>`
        + `<td>${ lj.status_paid_off && lj.isRevert ? '<span style="text-decoration: line-through;">Lunas</span>'
              : lj.status_paid_off && !lj.isRevert ? 'Lunas'
              : `<input type="checkbox" index=${idx} class="isBayarJasa" ${lj.checked ? 'checked' : ''}/>`}</td>`
        + `<td>
              <button type="button" class="btn btn-danger cancelPembayaranJasa ${lj.status_paid_off && lj.isRevert ? 'd-none':'d-block'}" title="Membatalkan Pembayaran"
              ${role.toLowerCase() != 'admin' || !lj.status_paid_off ? 'disabled' : ''} index=${idx}><i class="fa fa-close" aria-hidden="true"></i></button>
              <button type="button" class="btn btn-success revertPembayaranJasa
              ${(lj.status_paid_off && !lj.isRevert) || !lj.status_paid_off ? 'd-none':'d-block'}" title="Mengembalikan Pelunasan"
              ${role.toLowerCase() != 'admin' || !lj.status_paid_off ? 'disabled' : ''} index=${idx}><i class="fa fa-undo" aria-hidden="true"></i></button>
          </td>`
        + `</tr>`;
        ++no;
    });
    $('#list-selected-jasa').append(rowSelectedListJasa);

    $('.isBayarJasa').click(function() {
      const idx = $(this).attr('index');
      const getDetailJasa = selectedListJasa[idx];

      if (this.checked) {
        selectedListJasa[idx].checked = true;
        listTagihanJasa.push(getDetailJasa);
        calculationPay.push({ id: getDetailJasa.detail_service_patient_id, type: 'jasa', price: getDetailJasa.new_price_overall,
        discount: getDetailJasa.discount ? getDetailJasa.discount : 0, amount_discount: getDetailJasa.amount_discount, isRevert: false });
      } else {
        const getIdxTagihanJasa = listTagihanJasa.findIndex(i => i.detail_service_patient_id == getDetailJasa.detail_service_patient_id);
        const getIdxCalculation = calculationPay.findIndex(i => (i.type == 'jasa' && i.id == getDetailJasa.detail_service_patient_id));

        selectedListJasa[idx].checked = false;
        listTagihanJasa.splice(getIdxTagihanJasa, 1);
        calculationPay.splice(getIdxCalculation, 1);
      }

      processAppendListTagihanJasa();
      processCalculationTagihan();
      validationForm();
    });

    $('.cancelPembayaranJasa').click(function() {
      getIdx = $(this).attr('index');
      selectedListJasa[getIdx].isRevert = true;
      processAppendListSelectedJasa();

      const getDetailJasa = selectedListJasa[getIdx];
      const getIdxTagihanJasa = listTagihanJasa.findIndex(i => i.detail_service_patient_id == getDetailJasa.detail_service_patient_id);

      listTagihanJasa[getIdxTagihanJasa].isRevert = true;
      calculationPay.push({ id: getDetailJasa.detail_service_patient_id, type: 'jasa', price: getDetailJasa.price_overall, isRevert: true });

      processAppendListTagihanJasa();
      validationForm();
    });

    $('.revertPembayaranJasa').click(function() {
      getIdx = $(this).attr('index');
      selectedListJasa[getIdx].isRevert = false;
      processAppendListSelectedJasa();

      const getDetailJasa = selectedListJasa[getIdx];
      const getIdxTagihanJasa = listTagihanJasa.findIndex(i => i.detail_service_patient_id == getDetailJasa.detail_service_patient_id);
      const getIdxCalculation = calculationPay.findIndex(i => (i.type == 'jasa' && i.id == getDetailJasa.detail_service_patient_id));

      listTagihanJasa[getIdxTagihanJasa].isRevert = false;
      calculationPay[getIdxCalculation].isRevert = null;

      processAppendListTagihanJasa();
      validationForm();
    });
  }

  function processAppendListTagihanJasa() {
    let rowListTagihanJasa = '';
    let no = 1;
    $('#list-tagihan-jasa tr').remove();

    if (listTagihanJasa.length) {
      listTagihanJasa.forEach((lj) => {
        if (!lj.isRevert) {
          rowListTagihanJasa += `<tr>`
            + `<td>${no}</td>`
            + `${'<td>'+(lj.paid_date ? lj.paid_date : '-')+'</td>'}`
            + `<td>${lj.created_by}</td>`
            + `<td>${lj.category_name}</td>`
            + `<td>${lj.service_name}</td>`
            + `<td>${lj.quantity}</td>`
            + `<td>${
              Number(lj.selling_price || 0).toLocaleString('id-ID')
              // typeof(lj.selling_price) == 'number' ? lj.selling_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
            }</td>`
            + `<td>${lj.discount}&nbsp;%</td>`
            + `<td>${
              Number(lj.new_price_overall || 0).toLocaleString('id-ID')
              // typeof(lj.new_price_overall) == 'number' ? lj.new_price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
            }</td>`
            + `<td>${lj.payment_method ? lj.payment_method : ''}</td>`
            + `</tr>`;
            ++no;
        }
      });
    } else { rowListTagihanJasa += `<tr class="text-center"><td colspan="10">Tidak ada data.</td></tr>` }
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
          Number(lb.each_price || 0).toLocaleString('id-ID')
          // typeof(lb.each_price) == 'number' ? lb.each_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
        }</td>`
        + `<td class="d-flex align-item-c">
            <input type="number" min="0" max="100" maxlength="3" index=${idx} style="width:65px"
            class="form-control diskon-list-barang ${role.toLowerCase() == 'admin' && !lb.status_paid_off ? 'd-block' : 'd-none'}">&nbsp;
            <span class="${role.toLowerCase() == 'admin' && !lb.status_paid_off ? 'd-block' : 'd-none'}">%</span>
          </td>`
        + `<td>
            <span id="totalBarang-${idx}">${
              Number(lb.price_overall || 0).toLocaleString('id-ID')
              // typeof(lb.price_overall) == 'number' ? lb.price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
            }</span>
          </td>`
        + `<td>${ lb.status_paid_off && lb.isRevert ? '<span style="text-decoration: line-through;">Lunas</span>'
              : lb.status_paid_off && !lb.isRevert ? 'Lunas'
              : `<input type="checkbox" index=${idx} class="isBayarBarang" ${lb.checked ? 'checked' : ''}/>`}</td>`
        + `<td>
            <button type="button" class="btn btn-danger cancelPembayaranBarang ${lb.status_paid_off && lb.isRevert ? 'd-none':'d-block'}" title="Membatalkan Pembayaran"
            ${role.toLowerCase() != 'admin' || !lb.status_paid_off ? 'disabled' : ''} index=${idx}><i class="fa fa-close" aria-hidden="true"></i></button>
            <button type="button" class="btn btn-success revertPembayaranBarang
            ${(lb.status_paid_off && !lb.isRevert) || !lb.status_paid_off ? 'd-none':'d-block'}" title="Mengembalikan Pelunasan"
            ${role.toLowerCase() != 'admin' || !lb.status_paid_off ? 'disabled' : ''} index=${idx}><i class="fa fa-undo" aria-hidden="true"></i></button>
          </td>`
        + `</tr>`;
        ++no;
    });
    $('#list-selected-barang').append(rowSelectedListBarang);

    $('.isBayarBarang').click(function() {
      const idx = $(this).attr('index');
      const getDetailBarang = selectedListBarang[idx];

      if (this.checked) {
        selectedListBarang[idx].checked = true;
        listTagihanBarang.push(getDetailBarang);

        calculationPay.push({ id: getDetailBarang.id, medicineGroupId: getDetailBarang.medicine_group_id, quantity: getDetailBarang.quantity,
           type: 'barang', price: getDetailBarang.new_price_overall, discount: getDetailBarang.discount ? getDetailBarang.discount : 0, amount_discount: getDetailBarang.amount_discount, isRevert: false });
      } else {
        const getIdxTagihanBarang = listTagihanBarang.findIndex(i => i.id == getDetailBarang.id);
        const getIdxCalculation = calculationPay.findIndex(i => (i.type == 'barang' && i.id == getDetailBarang.id));

        selectedListBarang[idx].checked = false;
        listTagihanBarang.splice(getIdxTagihanBarang, 1);
        calculationPay.splice(getIdxCalculation, 1);
      }

      processAppendListTagihanBarang();
      processCalculationTagihan();
      validationForm();
    });

    $('.cancelPembayaranBarang').click(function() {
      getIdx = $(this).attr('index');
      selectedListBarang[getIdx].isRevert = true;
      processAppendListSelectedBarang();

      const getDetailBarang = selectedListBarang[getIdx];
      const getIdxTagihanBarang = listTagihanBarang.findIndex(i => i.detail_medicine_group_check_up_result_id == getDetailBarang.id);

      listTagihanBarang[getIdxTagihanBarang].isRevert = true;
      calculationPay.push({ id: getDetailBarang.id, medicineGroupId: getDetailBarang.medicine_group_id, type: 'barang', price: getDetailBarang.price_overall, isRevert: true });

      processAppendListTagihanBarang();
      validationForm();
    });

    $('.revertPembayaranBarang').click(function() {
      getIdx = $(this).attr('index');
      selectedListBarang[getIdx].isRevert = false;
      processAppendListSelectedBarang();

      const getDetailBarang = selectedListBarang[getIdx];
      const getIdxTagihanBarang = listTagihanBarang.findIndex(i => i.detail_medicine_group_check_up_result_id == getDetailBarang.id);
      const getIdxCalculation = calculationPay.findIndex(i => (i.type == 'barang' && i.id == getDetailBarang.id));

      listTagihanBarang[getIdxTagihanBarang].isRevert = false;
      calculationPay[getIdxCalculation].isRevert = null;

      processAppendListTagihanBarang();
      validationForm();
    });
  }

  function processAppendListTagihanBarang() {
    let rowListTagihanBarang = '';
    let no = 1;
    $('#list-tagihan-barang tr').remove();

    if (listTagihanBarang.length) {
      listTagihanBarang.forEach((lb) => {
        if (!lb.isRevert) {
          rowListTagihanBarang += `<tr>`
            + `<td>${no}</td>`
            + `${'<td>'+(lb.paid_date ? lb.paid_date : '-')+'</td>'}`
            + `<td>${lb.created_by}</td>`
            + `<td>${lb.group_name}</td>`
            + `<td>${lb.quantity}</td>`
            + `<td>${
              Number(lb.each_price || 0).toLocaleString('id-ID')
              // typeof(lb.each_price) == 'number' ? lb.each_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
            }</td>`
            + `<td>${lb.discount}&nbsp;%</td>`
            + `<td>${
              Number(lb.new_price_overall || 0).toLocaleString('id-ID')
              // typeof(lb.new_price_overall) == 'number' ? lb.new_price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
            }</td>`
            + `<td>${lb.payment_method ? lb.payment_method : ''}</td>`
            + `</tr>`;
            ++no;
        }
      });
    } else { rowListTagihanBarang += `<tr class="text-center"><td colspan="11">Tidak ada data.</td></tr>` }
    $('#list-tagihan-barang').append(rowListTagihanBarang);
  }

  function processCalculationTagihan() {
    let total = 0;

    calculationPay.forEach(calc => {
      if (calc.isRevert == false) { total += parseInt(calc.price); }
    });

    let totalText = `Rp. ${Number(total || 0).toLocaleString('id-ID')},00`;
    $('#totalBayarTxt').text(totalText);
  }

  function processSaved() {

    let finalSelectedJasa = []; let finalSelectedBarang = [];

    calculationPay.forEach(dt => {
      if (dt.type == 'jasa') {
        if (dt.isRevert === true) {
          finalSelectedJasa.push({ detail_service_patient_id: dt.id, status: 'del' });
        } else if (dt.isRevert === false) { finalSelectedJasa.push({ detail_service_patient_id: dt.id, discount: dt.discount, amount_discount: dt.amount_discount, status: null }); }
      } else {
        if (dt.isRevert === true) {
          finalSelectedBarang.push({ medicine_group_id: dt.medicineGroupId, quantity: dt.quantity, status: 'del'});
        } else if (dt.isRevert === false) { finalSelectedBarang.push({ medicine_group_id: dt.medicineGroupId, quantity: dt.quantity, discount: dt.discount, amount_discount: dt.amount_discount, status: null }); }
      }
    });

    const datas = {
      check_up_result_id: getCheckUpResultId,
      payment_method_id: $('#metodePembayaran').val(),
      service_payment: finalSelectedJasa.length ? finalSelectedJasa : [{detail_service_patient_id: null, status: null}],
      item_payment: finalSelectedBarang.length ? finalSelectedBarang : [{medicine_group_id: null, quantity: null, status: null}]
    };

    $.ajax({
      url : $('.baseUrl').val() + '/api/pembayaran',
      type: 'PUT',
      dataType: 'JSON',
      headers: { 'Authorization': `Bearer ${token}` },
      data: datas,
      beforeSend: function() { $('#loading-screen').show(); },
      success: function(resp) {

        $("#msg-box .modal-body").text('Berhasil Merubah Data');
        $('#msg-box').modal('show');

        processPrint(datas.check_up_result_id, JSON.stringify(datas.service_payment), JSON.stringify(datas.item_payment));

        setTimeout(() => {
          window.location.href = $('.baseUrl').val() + '/pembayaran';
        }, 1000);
      }, complete: function() { $('#loading-screen').hide(); }
      , error: function(err) {
        if (err.status === 422) {
          let errText = ''; $('#beErr').empty(); $('#btnSubmitPembayaran').attr('disabled', true);
          $.each(err.responseJSON.errors, function(idx, v) {
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

  function validationForm() {

    if (!$('#metodePembayaran').val()) {
      $('#metodePembayaranErr1').text('Metode pembayaran harus di isi'); isValidMetodePembayaran = false;
    } else {
      $('#metodePembayaranErr1').text(''); isValidMetodePembayaran = true;
    }

    isValidCalculationPay = (!calculationPay.length) ? false : true;

    $('#beErr').empty(); isBeErr = false;

    $('#btnSubmitPembayaran').attr('disabled', (!isValidCalculationPay || !isValidMetodePembayaran || isBeErr) ? true : false);
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

});
