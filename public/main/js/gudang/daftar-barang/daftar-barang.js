$(document).ready(function() {
  let optSatuanBarang = '';
  let optKategoriBarang = '';
  let optCabang1 = '';
  let optCabang2 = '';
  let getCurrentPage = 1;

  let getId = null;
  let modalState = '';
  let getTanggalKadaluwarsa = '';
  let isValidNamaBarang = false;
  let isValidJumlahBarang = false;
  let isValidLimitBarang = false;
  let isValidSelectedSatuanBarang = false;
  let isValidSelectedKategori = false;
  let isValidSelectedCabang = false;
  let isValidTanggalKadaluwarsa = false;

  let isBeErr = false;
  let paramUrlSetup = {
    orderby: '',
    column: '',
    keyword: '',
    branchId: ''
  };

  if (role.toLowerCase() != 'admin') {
    $('.columnAction').hide(); $('#filterCabang').hide();

    if (role.toLowerCase() == 'dokter' || role.toLowerCase() == 'admin_gudang' || role.toLowerCase() == 'admin gudang') {
      $('.section-left-box-title').append(
        `<button type="button" class="btn btn-success btn-download-excel" title="Download Excel">
          <i class="fa fa-file-excel-o" aria-hidden="true"></i>&nbsp;&nbsp;Download Excel
        </button>`
      );
    }
  } else {
    $('.section-left-box-title').append(
      `<button class="btn btn-info openFormAdd m-r-10px">Tambah</button>
      <button class="btn btn-info openFormUpload m-r-10px">Upload Sekaligus</button>
      <button type="button" class="btn btn-success btn-download-excel" title="Download Excel">
        <i class="fa fa-file-excel-o" aria-hidden="true"></i>&nbsp;&nbsp;Download Excel
      </button>`
    );
    $('.section-right-box-title').append(`<select id="filterCabang" style="width: 50%"></select>`);

    $('#filterCabang').select2({ placeholder: 'Cabang', allowClear: true });

    // load satuan barang
    loadSatuanBarang();

    // load kategori barang
    loadKategoriBarang();

    // load cabang
    loadCabang();

    $('#tanggalKadaluwarsa').datepicker({
      autoclose: true,
      clearBtn: true,
      format: 'dd/mm/yyyy',
      todayHighlight: true
      }).on('changeDate', function(e) {
        getTanggalKadaluwarsa = e.format();
        validationForm();
    });
  }

  // load daftar barang
  loadDaftarBarang();

  $('.input-search-section .fa').click(function() {
    onSearch($('.input-search-section input').val());
  });

  $('.input-search-section input').keypress(function(e) {
    if (e.which == 13) { onSearch($(this).val()); }
  });

  $('.onOrdering').click(function() {
    const column = $(this).attr('data');
    const orderBy = $(this).attr('orderby');
    $('.onOrdering[data="'+column+'"]').children().remove();

    if (orderBy == 'none' || orderBy == 'asc') {
      $(this).attr('orderby', 'desc');
      $(this).append('<span class="fa fa-sort-desc"></span>');

    } else if(orderBy == 'desc') {
      $(this).attr('orderby', 'asc');
      $(this).append('<span class="fa fa-sort-asc"></span>');
    }

    paramUrlSetup.orderby = $(this).attr('orderby');
    paramUrlSetup.column = column;

    loadDaftarBarang();
  });

  $('.openFormAdd').click(function() {
    modalState = 'add';
    $('.modal-title').text('Tambah Daftar Barang');
    $('#selectedCabang').attr('multiple', 'multiple');

    refreshForm();
    formConfigure();
  });

  $('.openFormUpload').click(function() {
    $('#modal-upload-daftar-barang .modal-title').text('Upload Barang Sekaligus');
    $('#modal-upload-daftar-barang').modal('show');
    $('.validate-error').html('');
  });

  $('.btn-download-excel').click(function() {
    $.ajax({
      url     : $('.baseUrl').val() + '/api/daftar-barang/generate-excel',
      headers : { 'Authorization': `Bearer ${token}` },
      type    : 'GET',
      data    : { orderby: paramUrlSetup.orderby, column: paramUrlSetup.column, keyword: paramUrlSetup.keyword, branch_id: paramUrlSetup.branchId },
      xhrFields: { responseType: 'blob' },
      beforeSend: function() { $('#loading-screen').show(); },
      success: function(data, status, xhr) {
        let disposition = xhr.getResponseHeader('content-disposition');
        let matches = /"([^"]*)"/.exec(disposition);
        let filename = (matches != null && matches[1] ? matches[1] : 'file.xlsx');
        let blob = new Blob([data],{type:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
        let downloadUrl = URL.createObjectURL(blob);
        let a = document.createElement("a");

        a.href = downloadUrl;
        a.download = filename
        document.body.appendChild(a);
        a.click();

      }, complete: function() { $('#loading-screen').hide(); },
      error: function(err) {
        if (err.status == 401) {
          localStorage.removeItem('vet-clinic');
          location.href = $('.baseUrl').val() + '/masuk';
        }
      }
    });
  });

  $('.btn-download-template').click(function() {
    $.ajax({
      url     : $('.baseUrl').val() + '/api/daftar-barang/download-template',
      headers : { 'Authorization': `Bearer ${token}` },
      type    : 'GET',
      xhrFields: { responseType: 'blob' },
      beforeSend: function() { $('#loading-screen').show(); },
      success: function(data, status, xhr) {
        let disposition = xhr.getResponseHeader('content-disposition');
        let matches = /"([^"]*)"/.exec(disposition);
        let filename = (matches != null && matches[1] ? matches[1] : 'file.xlsx');
        let blob = new Blob([data],{type:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
        let downloadUrl = URL.createObjectURL(blob);
        let a = document.createElement("a");

        a.href = downloadUrl;
        a.download = filename
        document.body.appendChild(a);
        a.click();

      }, complete: function() { $('#loading-screen').hide(); },
      error: function(err) {
        if (err.status == 401) {
          localStorage.removeItem('vet-clinic');
          location.href = $('.baseUrl').val() + '/masuk';
        }
      }
    });

  });

  $("#fileupload").fileupload({
    url: $('.baseUrl').val() + '/api/daftar-barang/upload',
    headers : { 'Authorization': `Bearer ${token}` },
    dropZone: '#dropZone',
    dataType: 'json',
    autoUpload: false,
  }).on('fileuploadadd', function (e, data) {
    let fileTypeAllowed = /.\.(xlsx|xls)$/i;
    let fileName = data.originalFiles[0]['name'];
    let fileSize = data.originalFiles[0]['size'];

    if (!fileTypeAllowed.test(fileName)) {
      $('.validate-error').html('File harus berformat .xlsx atau .xls');
    } else {
      $('.validate-error').html('');
      data.submit();
    }
  }).on('fileuploaddone', function(e, data) {
    $('#modal-confirmation').hide();

    $("#msg-box .modal-body").text('Berhasil Upload Barang');
    $('#msg-box').modal('show');
    setTimeout(() => {
      $('#modal-upload-daftar-barang').modal('toggle');
      loadDaftarBarang();
    }, 1000);
  }).on('fileuploadfail', function(e, data) {
    const getResponsError = data._response.jqXHR.responseJSON.errors.hasOwnProperty('file') ? data._response.jqXHR.responseJSON.errors.file
      : data._response.jqXHR.responseJSON.errors;

    let errText = '';
    $.each(getResponsError, function(idx, v) {
      errText += v + ((idx !== getResponsError.length - 1) ? '<br/>' : '');
    });
    $('.validate-error').append(errText)
  }).on('fileuploadprogressall', function(e,data) {
  });

  $('#btnSubmitDaftarBarang').click(function() {

    if (modalState == 'add') {

      const fd = new FormData();
      fd.append('nama_barang', $('#namaBarang').val());
      fd.append('jumlah_barang', $('#jumlahBarang').val());
      fd.append('limit_barang', $('#limitBarang').val());
      fd.append('satuan_barang', $('#selectedSatuanBarang').val());
      fd.append('kategori_barang', $('#selectedKategoriBarang').val());
      fd.append('cabang', JSON.stringify($('#selectedCabang').val().map(x => +x)));
      fd.append('tanggal_expired', getTanggalKadaluwarsa);

      $.ajax({
        url : $('.baseUrl').val() + '/api/daftar-barang',
        type: 'POST',
        dataType: 'JSON',
        headers: { 'Authorization': `Bearer ${token}` },
        data: fd, contentType: false, cache: false,
        processData: false,
        beforeSend: function() { $('#loading-screen').show(); },
        success: function(resp) {

          $("#msg-box .modal-body").text('Berhasil Menambah Daftar Barang');
          $('#msg-box').modal('show');

          setTimeout(() => {
            $('#modal-daftar-barang').modal('toggle');
            refreshForm();
            loadDaftarBarang();
          }, 1000);
        }, complete: function() { $('#loading-screen').hide(); }
        , error: function(err) {
          if (err.status === 422) {
            let errText = ''; $('#beErr').empty(); $('#btnSubmitDaftarBarang').attr('disabled', true);
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

    } else {
      // edit
      $('#modal-confirmation .box-body').text('Anda yakin untuk mengubah daftar barang ?');
      $('#modal-confirmation').modal('show');
    }
  });

  $('#submitConfirm').click(function() {
    if (modalState == 'edit') {
      // process edit
      const datas = {
        id: getId,
        nama_barang: $('#namaBarang').val(),
        jumlah_barang: $('#jumlahBarang').val(),
        limit_barang: $('#limitBarang').val(),
        satuan_barang:  $('#selectedSatuanBarang').val(),
        kategori_barang: $('#selectedKategoriBarang').val(),
        cabang_id: $('#selectedCabang').val(),
        tanggal_expired: getTanggalKadaluwarsa
      };

      $.ajax({
        url : $('.baseUrl').val() + '/api/daftar-barang',
        type: 'PUT',
        dataType: 'JSON',
        headers: { 'Authorization': `Bearer ${token}` },
        data: datas,
        beforeSend: function() { $('#loading-screen').show(); },
        success: function(data) {
          $('#modal-confirmation').modal('toggle');

          $("#msg-box .modal-body").text('Berhasil Mengubah Daftar Barang');
          $('#msg-box').modal('show');

          setTimeout(() => {
            $('#modal-daftar-barang').modal('toggle');
            refreshForm();
            loadDaftarBarang();
          }, 1000);

        }, complete: function() { $('#loading-screen').hide(); }
        , error: function(err) {
          if (err.status === 422) {
            let errText = ''; $('#beErr').empty();
            $('#modal-confirmation').modal('toggle');
            $('#btnSubmitDaftarBarang').attr('disabled', true);
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
    } else {
      // process delete
      $.ajax({
        url     : $('.baseUrl').val() + '/api/daftar-barang',
        headers : { 'Authorization': `Bearer ${token}` },
        type    : 'DELETE',
        data    : { id: getId },
        beforeSend: function() { $('#loading-screen').show(); },
        success: function(data) {
          $('#modal-confirmation').modal('toggle');

          $("#msg-box .modal-body").text('Berhasil menghapus daftar barang');
          $('#msg-box').modal('show');
          loadDaftarBarang();

        }, complete: function() { $('#loading-screen').hide(); }
        , error: function(err) {
          if (err.status == 401) {
            localStorage.removeItem('vet-clinic');
            location.href = $('.baseUrl').val() + '/masuk';
          }
        }
      });
    }
  });

  $('#filterCabang').on('select2:select', function () { onFilterCabang($(this).val()); });
  $('#filterCabang').on("select2:unselect", function () { onFilterCabang($(this).val()); });

  function onFilterCabang(value) {
    paramUrlSetup.branchId = value;
    loadDaftarBarang();
  }

  function onSearch(keyword) {
    paramUrlSetup.keyword = keyword;
    loadDaftarBarang();
  }

  function loadDaftarBarang() {
    getId = null;
    modalState = '';
    $.ajax({
      url     : $('.baseUrl').val() + '/api/daftar-barang',
      headers : { 'Authorization': `Bearer ${token}` },
      type    : 'GET',
      data    : { orderby: paramUrlSetup.orderby, column: paramUrlSetup.column, keyword: paramUrlSetup.keyword, branch_id: paramUrlSetup.branchId, page: getCurrentPage },
      beforeSend: function() { $('#loading-screen').show(); },
      success: function(resp) {
        const getData = resp.data;

        let listDaftarBarang = '';
        $('#list-daftar-barang tr').remove();

        if (getData.length) {
          $.each(getData, function(idx, v) {
            listDaftarBarang += `<tr>
              <td class="${v.diff_expired_days <= 60 ? 'expired-date' : ''}">${++idx}</td>
              <td class="${v.diff_item <= 0 ? 'item-outstock' : ''}">${v.item_name}</td>
              <td>${v.total_item}</td>
              <td>${v.unit_name}</td>
              <td>${v.category_name}</td>
              <td>${v.branch_name}</td>
              <td>${v.created_by}</td>
              <td>${v.created_at}</td>
              <td>${v.expired_date}</td>`
              + ((role.toLowerCase() != 'admin') ? `` : `<td>
                <button type="button" class="btn btn-warning openFormEdit" value=${v.id}><i class="fa fa-pencil" aria-hidden="true"></i></button>
                <button type="button" class="btn btn-danger openFormDelete" value=${v.id}><i class="fa fa-trash-o" aria-hidden="true"></i></button>
              </td>`)
            +`</tr>`;
          });
        } else {
          listDaftarBarang += `<tr class="text-center"><td colspan="10">Tidak ada data.</td></tr>`;
        }
        $('#list-daftar-barang').append(listDaftarBarang);

        generatePagination(getCurrentPage, resp.total_paging);

        $('.openFormEdit').click(function() {
          const getObj = resp.data.find(x => x.id == $(this).val());
          modalState = 'edit';
          refreshForm();
          $('.modal-title').text('Edit Daftar Barang');
          $('#selectedCabang').removeAttr('multiple');

          formConfigure();
          getId = getObj.id;
          $('#namaBarang').val(getObj.item_name);
          $('#jumlahBarang').val(getObj.total_item);
          $('#limitBarang').val(getObj.limit_item);
          $('#selectedSatuanBarang').val(getObj.unit_item_id); $('#selectedSatuanBarang').trigger('change');
          $('#selectedKategoriBarang').val(getObj.category_item_id); $('#selectedKategoriBarang').trigger('change');
          $('#selectedCabang').val(getObj.branch_id); $('#selectedCabang').trigger('change');

          const dateArr = getObj.expired_date.split('/');
          getTanggalKadaluwarsa = getObj.expired_date;
          $('#tanggalKadaluwarsa').datepicker('update', new Date(parseFloat(dateArr[2]), parseFloat(dateArr[1])-1, parseFloat(dateArr[0])));
        });

        $('.openFormDelete').click(function() {
          getId = $(this).val();
          modalState = 'delete';
          $('#modal-confirmation .box-body').text('Anda yakin ingin menghapus Daftar Barang ini?');
          $('#modal-confirmation').modal('show');
        });

        $('.pagination > li > a').click(function() {
					const getClassName = this.className;
					const getNumber = parseFloat($(this).text());

					if ((getCurrentPage === 1 && getClassName.includes('arrow-left')
						|| (getCurrentPage === resp.total_paging && getClassName.includes('arrow-right')))) { return; }

					if (getClassName.includes('arrow-left')) {
						getCurrentPage = getCurrentPage - 1;
					} else if (getClassName.includes('arrow-right')) {
						getCurrentPage = getCurrentPage + 1;
					} else {
						getCurrentPage = getNumber;
					}

					loadDaftarBarang();
				});

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
    $('#selectedSatuanBarang').select2();
    $('#selectedKategoriBarang').select2();

    $('#selectedCabang').select2({placeholder: 'Pilih Cabang'});

    $('#modal-daftar-barang').modal('show');
    $('#btnSubmitDaftarBarang').attr('disabled', true);

    $('#namaBarang').keyup(function () { validationForm(); });
    $('#jumlahBarang').keyup(function () { validationForm(); });
    $('#jumlahBarang').change(function() { validationForm(); });
    $('#limitBarang').keyup(function () { validationForm(); });
    $('#limitBarang').change(function() { validationForm(); });
    $('#selectedSatuanBarang').change(function() { validationForm(); });
    $('#hargaSatuanBarang').keyup(function () { validationForm(); });
    $('#selectedKategoriBarang').change(function () { validationForm(); });
    $('#selectedCabang').change(function () { validationForm(); });
  }

  function refreshForm() {
    $('#namaBarang').val(null);
    $('#jumlahBarang').val(null);
    $('#limitBarang').val(null);
    $('#selectedSatuanBarang').val(null);
    $('#selectedKategoriBarang').val(null);
    $('#selectedCabang').val(null);
    $('#tanggalKadaluwarsa').datepicker('update', new Date());
    $('#beErr').empty(); isBeErr = false;
  }

  function validationForm() {
    if (!$('#namaBarang').val()) {
      $('#namaBarangErr1').text('Nama barang harus di isi'); isValidNamaBarang = false;
    } else {
      $('#namaBarangErr1').text(''); isValidNamaBarang = true;
    }

    if (!$('#jumlahBarang').val()) {
      $('#jumlahBarangErr1').text('Jumlah barang harus di isi'); isValidJumlahBarang = false;
    } else {
      $('#jumlahBarangErr1').text(''); isValidJumlahBarang = true;
    }

    if (!$('#limitBarang').val()) {
      $('#limitBarangErr1').text('Limit barang harus di isi'); isValidLimitBarang = false;
    } else {
      $('#limitBarangErr1').text(''); isValidLimitBarang = true;
    }

    // if (Number($('#limitBarang').val()) > Number($('#jumlahBarang').val())) {
    //   $('#limitBarangErr1').text('Limit Barang harus kurang dari jumlah barang'); isValidLimitBarang = false;
    // } else {
    //   $('#limitBarangErr1').text(''); isValidLimitBarang = true;
    // }

    if (!$('#tanggalKadaluwarsa').datepicker('getDate')) {
			$('#tanggalKadaluwarsaErr1').text('Tanggal kadaluwarsa harus di isi'); isValidTanggalKadaluwarsa = false;
		} else { 
			$('#tanggalKadaluwarsaErr1').text(''); isValidTanggalKadaluwarsa = true;
		}

    if (!$('#selectedSatuanBarang').val()) {
      $('#satuanBarangErr1').text('Satuan barang harus di isi'); isValidSelectedSatuanBarang = false;
    } else {
      $('#satuanBarangErr1').text(''); isValidSelectedSatuanBarang = true;
    }

    if (!$('#selectedKategoriBarang').val()) {
      $('#kategoriBarangErr1').text('Kategori barang harus di isi'); isValidSelectedKategori = false;
    } else {
      $('#kategoriBarangErr1').text(''); isValidSelectedKategori = true;
    }
    const getSelectedCabang = $('#selectedCabang').val();
    if ((Array.isArray(getSelectedCabang) && !getSelectedCabang.length) || (!Array.isArray(getSelectedCabang) && !getSelectedCabang)) {
      $('#cabangErr1').text('Cabang harus di isi'); isValidSelectedCabang = false;
    } else {
      $('#cabangErr1').text(''); isValidSelectedCabang = true;
    }

    $('#beErr').empty(); isBeErr = false;

    if (!isValidNamaBarang || !isValidJumlahBarang || !isValidLimitBarang || !isValidTanggalKadaluwarsa || !isValidSelectedSatuanBarang || !isValidSelectedKategori
      || !isValidSelectedCabang || isBeErr) {
      $('#btnSubmitDaftarBarang').attr('disabled', true);
    } else {
      $('#btnSubmitDaftarBarang').attr('disabled', false);
    }
  }

  function loadSatuanBarang() {
    $.ajax({
      url     : $('.baseUrl').val() + '/api/satuan-barang',
      headers : { 'Authorization': `Bearer ${token}` },
      type    : 'GET',
      beforeSend: function() { $('#loading-screen').show(); },
      success: function(data) {
        optSatuanBarang += `<option value=''>Pilih Satuan Barang</option>`

        if (data.length) {
          for (var i = 0 ; i < data.length ; i++){
            optSatuanBarang += `<option value=${data[i].id}>${data[i].unit_name}</option>`;
          }
        }
        $('#selectedSatuanBarang').append(optSatuanBarang);
      }, complete: function() { $('#loading-screen').hide(); },
      error: function(err) {
        if (err.status == 401) {
          localStorage.removeItem('vet-clinic');
          location.href = $('.baseUrl').val() + '/masuk';
        }
      }
    });
  }

  function loadKategoriBarang() {
    $.ajax({
      url     : $('.baseUrl').val() + '/api/kategori-barang',
      headers : { 'Authorization': `Bearer ${token}` },
      type    : 'GET',
      beforeSend: function() { $('#loading-screen').show(); },
      success: function(data) {
        optKategoriBarang += `<option value=''>Pilih Kategori Barang</option>`

        if (data.length) {
          for (let i = 0 ; i < data.length ; i++) {
            optKategoriBarang += `<option value=${data[i].id}>${data[i].category_name}</option>`;
          }
        }
        $('#selectedKategoriBarang').append(optKategoriBarang);
      }, complete: function() { $('#loading-screen').hide(); },
      error: function(err) {
        if (err.status == 401) {
          localStorage.removeItem('vet-clinic');
          location.href = $('.baseUrl').val() + '/masuk';
        }
      }
    });
  }

  function loadCabang() {
    $.ajax({
      url     : $('.baseUrl').val() + '/api/cabang',
      headers : { 'Authorization': `Bearer ${token}` },
      type    : 'GET',
      beforeSend: function() { $('#loading-screen').show(); },
      success: function(data) {
        optCabang2 += `<option value=''>Cabang</option>`

        if (data.length) {
          for (let i = 0 ; i < data.length ; i++) {
            optCabang1 += `<option value=${data[i].id}>${data[i].branch_name}</option>`;
            optCabang2 += `<option value=${data[i].id}>${data[i].branch_name}</option>`;
          }
        }
        $('#selectedCabang').append(optCabang1); $('#filterCabang').append(optCabang2);
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
