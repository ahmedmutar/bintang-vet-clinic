// Dropzone.autoDiscover = false;
function waLink(phone) {
  const raw = (phone || '').replace(/\D/g, '');
  const num  = raw.startsWith('0') ? '62' + raw.slice(1) : raw;
  return num ? `<a href="https://wa.me/${num}" target="_blank" rel="noopener" style="color:#25D366;">
    <i class="fa fa-whatsapp" style="font-size:16px; margin-right:4px;"></i>${phone}</a>` : '-';
}

let arrayKelompokObat = [];
let arrayKelompokObatDelete = [];
let formState = '';
let isValidSelectedPasien = false;
let isValidAnamnesa = false;
let isValidSign = false;
let isValidDiagnosa = false;
let isValidRadioRawatInap = false;
let isValidRadioStatusPemeriksa = false;
let isValidAlasanTidak = true;
let isValidFotoKondisiPasien = true;
let customErr1 = false;
let isBeErr = false;
let getFileImagesExisting = [];

$(document).ready(function() {

  let optPasien = '';
  let optJasa = '';

  let listPasien = [];
  let listJasa = [];
  let selectedListJasa = [];
  let deletedUpdateListJasa = [];

  let getId = null;
  let getPatienRegistrationId = null;
  let dropzone = null;

  const url = window.location.pathname;
  const stuff = url.split('/');
  const lastUrl = stuff[stuff.length-1];

  // if (role.toLowerCase() == 'resepsionis') {
	// 	window.location.href = $('.baseUrl').val() + `/unauthorized`;
	// } else {
    formConfigure();
    loadPasien();
    loadJasa();
    // loadDropzone();
    loadKelompokObat(); loadBarang();
    refreshText();
    if (lastUrl == 'tambah') {
      formState = 'add';
      loadFormAdd();
    } else {
      formState = 'edit';
      $('.box-image-upload').magnificPopup({delegate: 'a', type:'image'});
      loadFormEdit();
    }
  // }

  $('.btn-back-to-list .text, #btnKembali').click(function() {
    window.location.href = $('.baseUrl').val() + '/hasil-pemeriksaan';
  });

  $('#btnSubmitHasilPemeriksaan').click(function() {
    const getCheckedStatusPemeriksa = $("input[name='radioStatusPengabaran']:checked").val();

    if (parseInt(getCheckedStatusPemeriksa)) {
      $('#modal-confirmation .modal-title').text('Peringatan');
      $('#modal-confirmation .box-body').text(`Anda yakin ingin menyelesaikan Hasil Pemeriksaan ini? Jika menyelesaikan Hasil Pemeriksaan ini anda
        tidak dapat mengubah data ini kembali`);
      $('#modal-confirmation').modal('show');
    } else {
      if (formState === 'add') {
        processSaved();
      } else {
        // process Edit
        processEdit();
      }
    }
  });

  $('#selectedPasien').on('select2:select', function (e) {
    const getObj = listPasien.find(x => x.registration_id == $(this).val());
		if (getObj) {
			$('#nomorPasienTxt').text(getObj.id_number_patient); $('#jenisHewanTxt').text(getObj.pet_category);
			$('#namaHewanTxt').text(getObj.pet_name); $('#jenisKelaminTxt').text(getObj.pet_gender);
			$('#usiaHewanTahunTxt').text(`${getObj.pet_year_age ?? 0} Tahun`); $('#usiaHewanBulanTxt').text(`${getObj.pet_month_age ?? 0} Bulan`); $('#usiaHewanHariTxt').text(`${getObj.pet_day_age ?? 0} Hari`);
			$('#namaPemilikTxt').text(getObj.owner_name); $('#alamatPemilikTxt').text(getObj.owner_address);
      $('#nomorHpPemilikTxt').html(waLink(getObj.owner_phone_number)); $('#nomorRegistrasiTxt').text(getObj.registration_number);
      $('#keluhanTxt').text(getObj.complaint); $('#namaPendaftarTxt').text(getObj.registrant);

		} else { refreshText(); }

		validationForm();
  });

  $('#anamnesa').keyup(function () { validationForm(); });
  $('#sign').keyup(function () { validationForm(); });
  $('#diagnosa').keyup(function () { validationForm(); });
  $('#descriptionCondPasien').keyup(function () { validationForm(); });

  $('#selectedJasa').on('select2:select', function (e) { processSelectedJasa(e.params.data.id, e.params.data.selected); validationForm(); });
  $('#selectedJasa').on('select2:unselect', function(e) { processSelectedJasa(e.params.data.id, e.params.data.selected); validationForm(); });

  $('input:radio[name="radioRawatInap"]').change(function (e) {
    if (this.checked) {
      if (parseInt(this.value)) {
        $('.form-deskripsi-kondisi-pasien').show();
      } else {
        $('.form-deskripsi-kondisi-pasien').hide();
      }
    }
    validationForm();
  });

  $('input:radio[name="radioStatusPengabaran"]').change(function (e) {
    if ($(this).val() == '0') {
      $('#alasanTidakPengabaranDiv').show();
    } else {
      $('#alasanTidakPengabaranDiv').hide();
      $('#alasanTidakPengabaran').val('');
    }
    validationForm();
  });

  $('#alasanTidakPengabaran').on('input', function() {
    validationForm();
  });

  $('#testUpload').click(function() {
    // dropzone.processQueue();
    let tempFile = [];
    for(let i = 1 ; i <= 5 ; i++) {
      const getFile = $(`#upload-image-${i}`)[0].files[0];
      tempFile.push(getFile);
    }
    console.log('tempFile', tempFile.filter(x => x));
    console.log('getFileImagesExisting', getFileImagesExisting);
  });

  $('#submitConfirm').click(function() {
    if (formState === 'add') {
      processSaved();
      $('#modal-confirmation .modal-title').text('Peringatan');
      $('#modal-confirmation').modal('toggle');
    } else if(formState === 'edit') {
      // process Edit
      processEdit();
      $('#modal-confirmation .modal-title').text('Peringatan');
      $('#modal-confirmation').modal('toggle');
    }
  });

  function validationPhoto() {
    const getLengthPhoto = tempFile.length;
    let isError = false;

    if (getLengthPhoto > 5) {
      detectValidPhoto(false); isError = true;
    }

    if (!isError) { detectValidPhoto(true); }
    validationForm();
  }

  function detectValidPhoto(isValid) {
    if(!isValid) {
      $('#fotoKondErr1').text('Foto tidak boleh lebih dari 5');
      isValidFotoKondisiPasien = false;
    } else {
      $('#fotoKondErr1').text('');
      isValidFotoKondisiPasien = true;
    }
  }

  function processSaved() {
    const fd = new FormData();
    fd.append('patient_registration_id', $('#selectedPasien').val());
    fd.append('anamnesa', $('#anamnesa').val());
    fd.append('sign', $('#sign').val());
    fd.append('diagnosa', $('#diagnosa').val());
    fd.append('status_pengabaran', $("input[name='radioStatusPengabaran']:checked").val());
    fd.append('alasan_tidak_pengabaran', $("input[name='radioStatusPengabaran']:checked").val() == '0' ? $('#alasanTidakPengabaran').val() : '');
    fd.append('status_outpatient_inpatient', $("input[name='radioRawatInap']:checked").val());
    fd.append('inpatient', $('#descriptionCondPasien').val());

    let finalSelectedJasa = [];
    let finalSelectedBarang = [];

    selectedListJasa.forEach(lj => {
      finalSelectedJasa.push({ price_service_id: lj.price_service_id, quantity: lj.quantity, price_overall: lj.price_overall });
    });

    arrayKelompokObat.forEach(ko => {
      let newObj = {medicine_group_id: null, quantity: null, list_of_medicine: []};
      newObj.medicine_group_id = ko.kelompokObatId;
      newObj.quantity = ko.quantity;
      newObj.remark = ko.remark;

      ko.selectedListBarang.forEach(lb => {
        newObj.list_of_medicine.push({price_item_id: lb.price_item_id, quantity: lb.quantity, price_overall: lb.price_overall});
      });

      finalSelectedBarang.push(newObj);
    });
    fd.append('service', JSON.stringify(finalSelectedJasa));
    fd.append('item', JSON.stringify(finalSelectedBarang));

    let tempFile = [];
    for(let i = 1 ; i <= 5 ; i++) {
      const getFile = $(`#upload-image-${i}`)[0].files[0];
      if(getFile) { tempFile.push(getFile); }
    }

    if(tempFile.length) {
      tempFile.forEach(file => { fd.append('filenames[]', file); });
    } else {
      fd.append('filenames[]', []);
    }

    $.ajax({
      url : $('.baseUrl').val() + '/api/hasil-pemeriksaan',
      type: 'POST',
      dataType: 'JSON',
      headers: { 'Authorization': `Bearer ${token}` },
      data: fd, contentType: false, cache: false,
      processData: false,
      beforeSend: function() { $('#loading-screen').show(); },
      success: function(resp) {
        $("#msg-box .modal-body").text(`Berhasil Menambah Data`);
        $('#msg-box').modal('show');
        setTimeout(() => {
          window.location.href = $('.baseUrl').val() + '/hasil-pemeriksaan';
        }, 1000);
      }, complete: function() { $('#loading-screen').hide(); }
      , error: function(err) {
        if (err.status === 422 || err.status === 403) {
          let errText = ''; $('#beErr').empty(); $('#btnSubmitHasilPemeriksaan').attr('disabled', true);
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

  function processEdit() {
    let finalSelectedJasa = [];
    let finalSelectedBarang = [];

    deletedUpdateListJasa.forEach(ulj => {
      finalSelectedJasa.push({ id: ulj.id, price_service_id: ulj.price_service_id, quantity: ulj.quantity, price_overall: ulj.price_overall, status: 'del' });
    });

    selectedListJasa.forEach(lj => {
      finalSelectedJasa.push({ id: lj.id, price_service_id: lj.price_service_id, quantity: lj.quantity, price_overall: lj.price_overall, status: '' });
    });

    arrayKelompokObatDelete.forEach(ko => {
      let newObj = {id: null, medicine_group_id: null, quantity: null, list_of_medicine: [], status: 'del'};
      newObj.id = ko.id;
      newObj.medicine_group_id = ko.kelompokObatId;
      newObj.quantity = ko.quantity;

      ko.deletedUpdateListBarang.forEach(dul => {
        newObj.list_of_medicine.push({id: dul.id, price_item_id: dul.price_item_id, quantity: dul.quantity, price_overall: dul.price_overall, status: 'del'});
      });

      ko.selectedListBarang.forEach(lb => {
        newObj.list_of_medicine.push({id: lb.id, price_item_id: lb.price_item_id, quantity: lb.quantity, price_overall: lb.price_overall, status: ''});
      });

      finalSelectedBarang.push(newObj);
    });

    arrayKelompokObat.forEach(ko => {
      let newObj = {id: null, medicine_group_id: null, quantity: null, list_of_medicine: [], status: ''};
      newObj.id = ko.id;
      newObj.medicine_group_id = ko.kelompokObatId;
      newObj.quantity = ko.quantity;
      newObj.remark = ko.remark;

      ko.deletedUpdateListBarang.forEach(dul => {
        newObj.list_of_medicine.push({id: dul.id, price_item_id: dul.price_item_id, quantity: dul.quantity, price_overall: dul.price_overall, status: 'del'});
      });

      ko.selectedListBarang.forEach(lb => {
        newObj.list_of_medicine.push({id: lb.id, price_item_id: lb.price_item_id, quantity: lb.quantity, price_overall: lb.price_overall, status: ''});
      });

      finalSelectedBarang.push(newObj);
    });

    const datas = {
      id: getId,
      patient_registration_id: getPatienRegistrationId,
      anamnesa: $('#anamnesa').val(),
      sign: $('#sign').val(),
      diagnosa: $('#diagnosa').val(),
      status_pengabaran: parseInt($("input[name='radioStatusPengabaran']:checked").val()),
      alasan_tidak_pengabaran: $("input[name='radioStatusPengabaran']:checked").val() == '0' ? $('#alasanTidakPengabaran').val() : '',
      status_outpatient_inpatient: parseInt($("input[name='radioRawatInap']:checked").val()),
      inpatient: $('#descriptionCondPasien').val(),
      service: finalSelectedJasa,
      item: finalSelectedBarang
    };

    $.ajax({
      url : $('.baseUrl').val() + '/api/hasil-pemeriksaan',
      type: 'PUT',
      dataType: 'JSON',
      headers: { 'Authorization': `Bearer ${token}` },
      data: datas,
      beforeSend: function() { $('#loading-screen').show(); },
      success: function(data) {

        let tempFile = [];
        const fdUpload = new FormData();

        fdUpload.append('check_up_result_id', getId);
        for(let i = 1 ; i <= 5 ; i++) {
          const getFile = $(`#upload-image-${i}`)[0].files[0];
          if (getFile) {
            tempFile.push(getFile);
          }
        }

        if (tempFile.length) {
          tempFile.forEach((tf) => { fdUpload.append('filenames[]', tf); });
        } else {
          fdUpload.append('filenames[]', []);
        }

        fdUpload.append('images', JSON.stringify(getFileImagesExisting));

        $.ajax({
          url : $('.baseUrl').val() + '/api/hasil-pemeriksaan/update-upload-gambar',
          type: 'POST',
          dataType: 'JSON',
          headers: { 'Authorization': `Bearer ${token}` },
          data: fdUpload, contentType: false, cache: false,
          processData: false,
          beforeSend: function() { $('#loading-screen').show(); },
          success: function(resp) {
            $("#msg-box .modal-body").text(`Berhasil Mengubah Data`);
            $('#msg-box').modal('show');
            setTimeout(() => {
              window.location.href = $('.baseUrl').val() + '/hasil-pemeriksaan';
            }, 1000);
          }, complete: function() { $('#loading-screen').hide(); }
          , error: function(err) {
            if (err.status === 422) {
              let errText = ''; $('#beErr').empty(); $('#btnSubmitHasilPemeriksaan').attr('disabled', true);
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

      }, complete: function() { $('#loading-screen').hide(); }
      , error: function(err) {
        if (err.status === 422 || err.status === 403) {
          let errText = ''; $('#beErr').empty(); $('#btnSubmitHasilPemeriksaan').attr('disabled', true);
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

  function processSelectedJasa(selectedId, selected) {

    if (selected) {
      const getObj = listJasa.find(x => x.id == parseInt(selectedId));
      selectedListJasa.push({
        id: null,
        price_service_id: getObj.id,
        category_name: getObj.category_name,
        service_name: getObj.service_name,
        selling_price: getObj.selling_price,
        quantity: null, price_overall: null
      });
      processAppendListSelectedJasa();
      $('.table-list-jasa').show();
    } else {
      const getIds = [];
      const getIdx = selectedListJasa.findIndex(i => i.price_service_id == selectedId);

      deletedUpdateListJasa.push(selectedListJasa[getIdx]);
      selectedListJasa.splice(getIdx, 1);
      selectedListJasa.forEach(lj => { getIds.push(lj.price_service_id); });
      if (!selectedListJasa.length) { $('.table-list-jasa').hide(); }

      $('#selectedJasa').val(getIds); $('#selectedJasa').trigger('change');
      processAppendListSelectedJasa();
    }
  }

  function processAppendListSelectedJasa() {
    let rowSelectedListJasa = '';
    let no = 1;
    $('#list-selected-jasa tr').remove();

    selectedListJasa.forEach((lj, idx) => {
      rowSelectedListJasa += `<tr>`
        + `<td>${no}</td>`
        + `${(formState) == 'edit' ? '<td>'+(lj.created_at ? lj.created_at : '-')+'</td>' : '' }`
        + `${(formState) == 'edit' ? '<td>'+(lj.created_by ? lj.created_by : '-')+'</td>' : '' }`
        + `<td>${lj.category_name}</td>`
        + `<td>${lj.service_name}</td>`
        + `<td><input type="number" min="0" class="qty-input-jasa" index=${idx} value=${lj.quantity}></td>`
        + `<td>${
          Number(lj.selling_price || 0).toLocaleString('id-ID')
          // typeof(lj.selling_price) == 'number' ? lj.selling_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
        }</td>`
        + `<td><span id="totalBarang-jasa-${idx}">${
          Number(lj.price_overall || 0).toLocaleString('id-ID')
          // typeof(lj.price_overall) == 'number' ? lj.price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
        }</span></td>`
        + `<td>
            <button type="button" class="btn btn-danger btnRemoveSelectedListJasa" value=${idx}>
              <i class="fa fa-trash-o" aria-hidden="true"></i>
            </button>
          </td>`
        + `</tr>`;
        ++no;
    });
    $('#list-selected-jasa').append(rowSelectedListJasa);

    $('.qty-input-jasa').on('input', function(e) {
      const idx          = $(this).attr('index');
      const value        = parseFloat($(this).val());
      const sellingPrice = parseFloat(selectedListJasa[idx].selling_price);
      let totalBarang    = value * sellingPrice;

      selectedListJasa[idx].quantity = value;
      selectedListJasa[idx].price_overall = totalBarang;
      validationForm();
      $('#totalBarang-jasa-'+idx).text(totalBarang.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'));
    });

    $('.btnRemoveSelectedListJasa').click(function() {
      const getIds = [];
      deletedUpdateListJasa.push(selectedListJasa[$(this).val()]);
      selectedListJasa.splice($(this).val(), 1);

      selectedListJasa.forEach(lj => { getIds.push(lj.price_service_id); });
      if (!selectedListJasa.length) { $('.table-list-jasa').hide(); }
      validationForm();

      $('#selectedJasa').val(getIds); $('#selectedJasa').trigger('change');
      processAppendListSelectedJasa();
    });
  }

  function loadFormAdd() {
    $('.title-form-hasil-pemeriksaan').text('Tambah Hasil Pemeriksaan');
    $('.form-cari-pasien').show();
    $('.table-list-jasa').hide(); $('.table-list-barang').hide();
    $('.tgl-edit').hide(); $('.dibuat-edit').hide();
    $('.form-deskripsi-kondisi-pasien').hide(); $('.table-deskripsi-kondisi-pasien').hide();
    $('input[name="radioRawatInap"]').prop('disabled', false);
  }

  function loadFormEdit() {
    $('.table-deskripsi-kondisi-pasien').show();
    $('.title-form-hasil-pemeriksaan').text('Ubah Hasil Pemeriksaan');
    $('.tgl-edit').show(); $('.dibuat-edit').show();
    $('.form-cari-pasien').hide(); $('input[name="radioRawatInap"]').prop('disabled', true);

    $.ajax({
      url     : $('.baseUrl').val() + '/api/hasil-pemeriksaan/detail',
      headers : { 'Authorization': `Bearer ${token}` },
      type    : 'GET',
      data	  : { id: lastUrl },
      beforeSend: function() { $('#loading-screen').show(); },
      success: function(data) {
        const getData = data;

        getId = getData.id; getPatienRegistrationId = getData.patient_registration_id;
        $('#nomorRegistrasiTxt').text(getData.registration.registration_number); $('#nomorPasienTxt').text(getData.registration.patient_number);
        $('#jenisHewanTxt').text(getData.registration.pet_category); $('#namaHewanTxt').text(getData.registration.pet_name);
        $('#jenisKelaminTxt').text(getData.registration.pet_gender);
        $('#usiaHewanTahunTxt').text(`${getData.registration.pet_year_age} Tahun`); $('#usiaHewanBulanTxt').text(`${getData.registration.pet_month_age} Bulan`); $('#usiaHewanHariTxt').text(`${getData.registration.pet_day_age} Hari`);
        $('#namaPemilikTxt').text(getData.registration.owner_name); $('#alamatPemilikTxt').text(getData.registration.owner_address);
        $('#nomorHpPemilikTxt').html(waLink(getData.registration.owner_phone_number));
        $('#keluhanTxt').text(getData.registration.complaint); $('#namaPendaftarTxt').text(getData.registration.registrant);

        $('#anamnesa').val(getData.anamnesa); $('#diagnosa').val(getData.diagnosa);
        $('#sign').val(getData.sign);

        const getIdJasa = [];
        if (getData.services.length) {
          getData.services.forEach(item => {
            selectedListJasa.push({
              id: item.detail_service_patient_id,
              price_service_id: item.price_service_id,
              category_name: item.category_name, service_name: item.service_name,
              selling_price: item.selling_price, status_paid_off: item.status_paid_off,
              quantity: item.quantity, price_overall: item.price_overall,
              created_at: item.created_at, created_by: item.created_by
            });
            getIdJasa.push(item.price_service_id);
          });
          processAppendListSelectedJasa();
          $('.table-list-jasa').show();
        } else {
          $('.table-list-jasa').hide();
        }
        $('#selectedJasa').val(getIdJasa); $('#selectedJasa').trigger('change');

        if (getData.item.length) {
          getData.item.forEach(item => {
            let newObj = { id: null, kelompokObatId: null, selectDropdownBarang: [], selectedListBarang: [], deletedUpdateListBarang: [] };
            newObj.id = item.id; // untuk membedakan data lama dan baru
            newObj.kelompokObatId = item.medicine_group_id;
            newObj.quantity = item.quantity;
            newObj.remark = item.remark;
            item.list_of_medicine.forEach(lom => {
              newObj.selectDropdownBarang.push(lom.price_item_id);
              newObj.selectedListBarang.push({
                id: lom.detail_item_patients_id,
                price_item_id: lom.price_item_id,
                category_name: lom.category_name,
                item_name: lom.item_name, unit_name: lom.unit_name,
                selling_price: lom.selling_price,
                status_paid_off: lom.status_paid_off,
                quantity: lom.quantity, price_overall: lom.price_overall,
                created_at: lom.created_at, created_by: lom.created_by
              });
            });
            arrayKelompokObat.push(newObj);
          });
        }

        drawListKelompokObat();

        $(`input[name=radioRawatInap][value=${getData.status_outpatient_inpatient}]`).prop('checked', true);
        if (getData.status_outpatient_inpatient) {
          let rowListCondPasien = '';
          let no = 1;

          $('#list-deskripsi-kondisi-pasien tr').remove();
          getData.inpatient.forEach((lj, idx) => {
            rowListCondPasien += `<tr>`
              + `<td>${no}</td>`
              + `<td>${lj.created_at}</td>`
              + `<td>${lj.created_by}</td>`
              + `<td><div style="word-break: break-word;">${lj.description}</div></td>`
              + `</tr>`;
              ++no;
          });
          $('#list-deskripsi-kondisi-pasien').append(rowListCondPasien);
          $('.form-deskripsi-kondisi-pasien').show();
          $('.table-deskripsi-kondisi-pasien').show();
        } else {
          $('.form-deskripsi-kondisi-pasien').hide();
          $('.table-deskripsi-kondisi-pasien').hide();
        }

        $(`input[name=radioStatusPengabaran][value=${getData.status_pengabaran}]`).prop('checked', true);
        if (getData.status_pengabaran == 0) {
          $('#alasanTidakPengabaranDiv').show();
          $('#alasanTidakPengabaran').val(getData.alasan_tidak_pengabaran || '');
        } else {
          $('#alasanTidakPengabaranDiv').hide();
          $('#alasanTidakPengabaran').val('');
        }

        let rowFotoKondPasien = '';
        $('#section-foto-kondisi-pasien .img-style').remove();
        if (getData.images.length) {

          getData.images.forEach((img, i) => {
            getFileImagesExisting.push({image_id: img.image_id, status: ''});
            $(`.box-image-upload img.img-preview-${i+1}`).attr('src', $('.baseUrl').val()+img.image);
            $(`.box-image-upload a.img-preview-${i+1}`).attr('href', $('.baseUrl').val()+img.image);
            $(`.box-image-upload a.img-preview-${i+1}`).attr('image_id', img.image_id);

            $(`.box-image-upload img.img-preview-${i+1}, .box-image-upload a.img-preview-${i+1}`).show();

            $(`[noUploadImage="${i+1}"]`).show();
            $(`#icon-plus-upload-${i+1}`).hide();
            $(`#upload-image-${i+1}`).hide();
          });
        } else {
          rowFotoKondPasien = 'Tidak ada foto.';
        }
        $('#section-foto-kondisi-pasien').append(rowFotoKondPasien);

        formConfigure();
      }, complete: function() { $('#loading-screen').hide(); },
      error: function(err) {
        if (err.status == 401) {
          localStorage.removeItem('vet-clinic');
          location.href = $('.baseUrl').val() + '/masuk';
        }
      }
    });
  }

  function loadPasien() {
    $.ajax({
			url     : $('.baseUrl').val() + '/api/pasien/status-terima',
			headers : { 'Authorization': `Bearer ${token}` },
			type    : 'GET',
			beforeSend: function() { $('#loading-screen').show(); },
			success: function(data) {
        optPasien += `<option value=''>Pilih Pasien</option>`
        listPasien = data;
				if (listPasien.length) {
					for (let i = 0 ; i < listPasien.length ; i++) {
						optPasien += `<option value=${listPasien[i].registration_id}>${listPasien[i].pet_name} - ${listPasien[i].branch_name}</option>`;
					}
				}
				$('#selectedPasien').append(optPasien);
			}, complete: function() { $('#loading-screen').hide(); },
			error: function(err) {
				if (err.status == 401) {
					localStorage.removeItem('vet-clinic');
					location.href = $('.baseUrl').val() + '/masuk';
				}
			}
		});
  }

  function loadJasa() {
    $.ajax({
			url     : $('.baseUrl').val() + '/api/pembagian-harga-jasa/dropdown',
			headers : { 'Authorization': `Bearer ${token}` },
			type    : 'GET',
			beforeSend: function() { $('#loading-screen').show(); },
			success: function(data) {
        listJasa = data;
				if (listJasa.length) {
					for (let i = 0 ; i < listJasa.length ; i++) {
						optJasa += `<option value=${listJasa[i].id}>${listJasa[i].category_name} - ${listJasa[i].service_name} - ${listJasa[i].branch_name}</option>`;
					}
        }
				$('#selectedJasa').append(optJasa);
			}, complete: function() { $('#loading-screen').hide(); },
			error: function(err) {
				if (err.status == 401) {
					localStorage.removeItem('vet-clinic');
					location.href = $('.baseUrl').val() + '/masuk';
				}
			}
		});
  }

  function formConfigure() {
    $('#selectedPasien').select2();
    $('#selectedJasa').select2({ placeholder: 'Jenis Pelayanan - Kategori Jasa', allowClear: true });

		$('#btnSubmitHasilPemeriksaan').attr('disabled', true);
  }

  function refreshText() {
    $('#nomorPasienTxt').text('-'); $('#jenisHewanTxt').text('-');
		$('#namaHewanTxt').text('-'); $('#jenisKelaminTxt').text('-');
		$('#usiaHewanTahunTxt').text('- Tahun'); $('#usiaHewanBulanTxt').text('- Bulan'); $('#usiaHewanHariTxt').text('- Hari');
		$('#namaPemilikTxt').text('-'); $('#alamatPemilikTxt').text('-');
    $('#nomorHpPemilikTxt').html('-'); $('#nomorRegistrasiTxt').text('-');
    $('#keluhanTxt').text('-'); $('#namaPendaftarTxt').text('-');
  }

  function loadKelompokObat() {
		$.ajax({
			url     : $('.baseUrl').val() + '/api/pembagian-harga-kelompok-obat/dropdown',
			headers : { 'Authorization': `Bearer ${token}` },
			type    : 'GET',
			beforeSend: function() { $('#loading-screen').show(); },
			success: function(data) {
        listKelompokObat = data;
			}, complete: function() { $('#loading-screen').hide(); },
			error: function(err) {
				if (err.status == 401) {
					localStorage.removeItem('vet-clinic');
					location.href = $('.baseUrl').val() + '/masuk';
				}
			}
		});
	}

  function loadBarang() {
    $.ajax({
			url     : $('.baseUrl').val() + '/api/pembagian-harga-barang/dropdown',
			headers : { 'Authorization': `Bearer ${token}` },
			type    : 'GET',
			beforeSend: function() { $('#loading-screen').show(); },
			success: function(data) {
        listBarang = data;
			}, complete: function() { $('#loading-screen').hide(); },
			error: function(err) {
				if (err.status == 401) {
					localStorage.removeItem('vet-clinic');
					location.href = $('.baseUrl').val() + '/masuk';
				}
			}
		});
  }

});

function validationForm() {
  if (formState === 'add') {
    if (!$('#selectedPasien').val()) {
      $('#pasienErr1').text('Pasien harus di isi'); isValidSelectedPasien = false;
    } else {
      $('#pasienErr1').text(''); isValidSelectedPasien = true;
    }
  } else { isValidSelectedPasien = true; }

  // if (!$('#anamnesa').val()) {
  // //   $('#anamnesaErr1').text('Anamnesa harus di isi'); isValidAnamnesa = false;
  // // } else {
  //   $('#anamnesaErr1').text(''); isValidAnamnesa = true;
  // }

  // if (!$('#sign').val()) {
  // //   $('#signErr1').text('Sign harus di isi'); isValidSign = false;
  // // } else {
  //   $('#signErr1').text(''); isValidSign = true;
  // }

  // if (!$('#diagnosa').val()) {
  // //   $('#diagnosaErr1').text('Diagnosa harus di isi'); isValidDiagnosa = false;
  // // } else {
  //   $('#diagnosaErr1').text(''); isValidDiagnosa = true;
  // }

  if (!$("input[name='radioRawatInap']:checked").val()) {
    $('#rawatInapErr1').text('Rawat inap harus di isi'); isValidRadioRawatInap = false;
  } else {
    $('#rawatInapErr1').text(''); isValidRadioRawatInap = true;
  }

  if (!$("input[name='radioStatusPengabaran']:checked").val()) {
    $('#statusPemeriksaErr1').text('Status pengabaran harus di isi'); isValidRadioStatusPemeriksa = false;
  } else {
    $('#statusPemeriksaErr1').text(''); isValidRadioStatusPemeriksa = true;
  }

  if ($("input[name='radioStatusPengabaran']:checked").val() == '0') {
    if (!$('#alasanTidakPengabaran').val().trim()) {
      $('#alasanTidakPengabaranErr').text('Alasan harus diisi'); isValidAlasanTidak = false;
    } else {
      $('#alasanTidakPengabaranErr').text(''); isValidAlasanTidak = true;
    }
  } else {
    $('#alasanTidakPengabaranErr').text(''); isValidAlasanTidak = true;
  }

  let isValidKelompokObat = true;
  for (let i = 0; i < arrayKelompokObat.length; i++) {
    if (!arrayKelompokObat[i].kelompokObatId || !arrayKelompokObat[i].quantity || !arrayKelompokObat[i].selectedListBarang.length) {
      $('#customErr1').text('Terdapat Kelompok Obat atau Daftar Barang yang belum diisi, harap dicek kembali!');
      customErr1 = true; isValidKelompokObat = false;
      break;
    }
  }

  if(isValidKelompokObat) {
    $('#customErr1').empty(); customErr1 = false;
  }

  $('#beErr').empty(); isBeErr = false;

  if (!isValidSelectedPasien //|| !isValidAnamnesa || !isValidSign || !isValidDiagnosa
    || !isValidRadioRawatInap || !isValidRadioStatusPemeriksa || !isValidAlasanTidak
    || !isValidFotoKondisiPasien || isBeErr || customErr1) {
    $('#btnSubmitHasilPemeriksaan').attr('disabled', true);
  } else {
    $('#btnSubmitHasilPemeriksaan').attr('disabled', false);
  }
}
