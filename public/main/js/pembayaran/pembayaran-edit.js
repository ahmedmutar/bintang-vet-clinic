$(document).ready(function() {

  const url = window.location.pathname;
  const stuff = url.split('/');
  const id = stuff[stuff.length-1];
  let getCheckUpResultId = null;

  let selectedListJasa = [];
  let selectedListBarang = [];
  let listTagihanJasa = [];
  let listTagihanBarang = [];
  let calculationPay = [];

  if (role.toLowerCase() == 'dokter') {
		window.location.href = $('.baseUrl').val() + `/unauthorized`;	
	}

  $('#totalBayarTxt').text('-');
  $('.btn-back-to-list .text, #btnKembali').click(function() {
    window.location.href = $('.baseUrl').val() + '/pembayaran';
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
      console.log('data', data);
      getCheckUpResultId = data.check_up_result_id;
      $('#nomorPasienTxt').text(data.registration.patient_number); $('#jenisHewanTxt').text(data.registration.pet_category);
      $('#namaHewanTxt').text(data.registration.pet_name); $('#jenisKelaminTxt').text(data.registration.pet_gender);
      $('#usiaHewanTahunTxt').text(`${data.registration.pet_year_age} Tahun`); $('#usiaHewanBulanTxt').text(`${data.registration.pet_month_age} Bulan`);
      $('#namaPemilikTxt').text(data.registration.owner_name); $('#alamatPemilikTxt').text(data.registration.owner_address);
      $('#nomorHpPemilikTxt').text(data.registration.owner_phone_number); $('#nomorRegistrasiTxt').text(data.registration.registration_number);
      $('#keluhanTxt').text(data.registration.complaint); $('#namaPendaftarTxt').text(data.registration.registrant);
      $('#rawatInapTxt').text(data.status_outpatient_inpatient ? 'Ya' : 'Tidak'); $('#statusPemeriksaanTxt').text(data.status_finish ? 'Selesai' : 'Belum');

      selectedListJasa = data.services; selectedListBarang = data.item;
      processAppendListSelectedJasa(); processAppendListSelectedBarang();

      listTagihanJasa = data.paid_services;
      listTagihanBarang = data.paid_item;

      processAppendListTagihanJasa(); processAppendListTagihanBarang();

    }, complete: function() { $('#loading-screen').hide(); },
    error: function(err) {
      if (err.status == 401) {
        localStorage.removeItem('vet-clinic');
        location.href = $('.baseUrl').val() + '/masuk';
      }
    }
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
        + `<td>${typeof(lj.selling_price) == 'number' ? lj.selling_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}</td>`
        + `<td>${typeof(lj.price_overall) == 'number' ? lj.price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}</td>`
        + `<td>${lj.status_paid_off ? 'Lunas' : '<input type="checkbox" index='+idx+' class="isBayarJasa"/>'}</td>`
        + `</tr>`;
        ++no;
    });
    $('#list-selected-jasa').append(rowSelectedListJasa);

    $('.isBayarJasa').click(function() {
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
          + `${'<td>'+(lj.paid_date ? lj.paid_date : '-')+'</td>'}`
          + `<td>${lj.created_at}</td>`
          + `<td>${lj.created_by}</td>`
          + `<td>${lj.category_name}</td>`
          + `<td>${lj.service_name}</td>`
          + `<td>${lj.quantity}</td>`
          + `<td>${typeof(lj.selling_price) == 'number' ? lj.selling_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}</td>`
          + `<td>${typeof(lj.price_overall) == 'number' ? lj.price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}</td>`
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
        + `<td>${typeof(lb.selling_price) == 'number' ? lb.selling_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}</td>`
        + `<td>${typeof(lb.price_overall) == 'number' ? lb.price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}</td>`
        + `<td>${lb.status_paid_off ? 'Lunas' : '<input type="checkbox" index='+idx+' class="isBayarBarang"/>'}</td>`
        + `</tr>`;
        ++no;
    });
    $('#list-selected-barang').append(rowSelectedListBarang);

    $('.isBayarBarang').click(function() {
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
          + `${'<td>'+(lb.paid_date ? lb.paid_date : '-')+'</td>'}`
          + `<td>${lb.created_at}</td>`
          + `<td>${lb.created_by}</td>`
          + `<td>${lb.group_name}</td>`
          + `<td>${lb.item_name}</td>`
          + `<td>${lb.category_name}</td>`
          + `<td>${lb.unit_name}</td>`
          + `<td>${lb.quantity}</td>`
          + `<td>${typeof(lb.selling_price) == 'number' ? lb.selling_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}</td>`
          + `<td>${typeof(lb.price_overall) == 'number' ? lb.price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}</td>`
          + `</tr>`;
          ++no;
      });
    } else { rowListTagihanBarang += `<tr class="text-center"><td colspan="9">Tidak ada data.</td></tr>` }
    $('#list-tagihan-barang').append(rowListTagihanBarang);
  }

  function processCalculationTagihan() {
    let total = 0;

    calculationPay.forEach(calc => total += calc.price );

    let totalText = `Rp. ${total.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')},00`;
    $('#totalBayarTxt').text(totalText);
  }

  function processSaved() {

    let finalSelectedJasa = []; let finalSelectedBarang = [];
    
    calculationPay.forEach(dt => {
      if (dt.type == 'jasa') {
        finalSelectedJasa.push({ detail_service_patient_id: dt.id});
      } else {
        finalSelectedBarang.push({ detail_item_patient_id: dt.id});
      }
    });

    const datas = {
      check_up_result_id: getCheckUpResultId,
      service_payment: finalSelectedJasa.length ? finalSelectedJasa : [{detail_service_patient_id: null}],
      item_payment: finalSelectedBarang.length ? finalSelectedBarang : [{detail_item_patient_id: null}]
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


});
