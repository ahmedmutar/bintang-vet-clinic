$(document).ready(function() {
  let getId = null;
  let modalState = '';
  let optCabang= '';
  let optKelompokObat = '';
  let getCurrentPage = 1;

  let isValidSelectedCabang = false;
  let isValidSelectedKelompokObat = false;

  let isValidHargaJual = false;
  let isValidHargaModal = false;
  let isValidFeeDokter = false;
  let isValidFeePetshop = false;
  let customErr1 = false;
  let isBeErr = false;
	let paramUrlSetup = {
		orderby:'',
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
    $('#selectedCabang').append(`<option value=''>Pilih Cabang</option>`);
    $('#selectedKelompokObat').append(`<option value=''>Pilih Kelompok Obat</option>`);

    $('.section-left-box-title').append(`
      <button class="btn btn-info openFormAdd m-r-10px">Tambah</button>
      <button class="btn btn-info openFormUpload m-r-10px">Upload Sekaligus</button>
      <button type="button" class="btn btn-success btn-download-excel" title="Download Excel">
        <i class="fa fa-file-excel-o" aria-hidden="true"></i>&nbsp;&nbsp;Download Excel
      </button>
    `);
		$('.section-right-box-title').append(`<select id="filterCabang" style="width: 50%"></select>`);

    $('#filterCabang').select2({ placeholder: 'Cabang', allowClear: true });
    $('#filterCabang').append(`<option value=''>Cabang</option>`);
    loadCabang();
  }

  loadHargaKelompokObat();

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

		loadHargaKelompokObat();
  });

  $('.openFormAdd').click(function() {
		modalState = 'add';
    $('.modal-title').text('Tambah Pembagian Harga Kelompok Obat');

    refreshForm(); formConfigure();
  });

  $('.openFormUpload').click(function() {
    $('#modal-upload-harga-kelompok-obat .modal-title').text('Upload Harga Kelompok Obat Sekaligus');
    $('#modal-upload-harga-kelompok-obat').modal('show');
    $('.validate-error').html('');
  });

  $('.btn-download-template').click(function() {
    $.ajax({
      url     : $('.baseUrl').val() + '/api/pembagian-harga-kelompok-obat/download-template',
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
    url: $('.baseUrl').val() + '/api/pembagian-harga-kelompok-obat/upload-template',
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

    $("#msg-box .modal-body").text('Berhasil Upload Harga Kelompok Obat');
    $('#msg-box').modal('show');
    setTimeout(() => {
      $('#modal-upload-harga-kelompok-obat').modal('toggle');
      loadHargaKelompokObat();
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

  $('.btn-download-excel').click(function() {
    $.ajax({
      url     : $('.baseUrl').val() + '/api/pembagian-harga-kelompok-obat/generate-excel',
      headers : { 'Authorization': `Bearer ${token}` },
      type    : 'GET',
      data	  : { orderby: paramUrlSetup.orderby, column: paramUrlSetup.column, keyword: paramUrlSetup.keyword, branch_id: paramUrlSetup.branchId },
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

  $('#btnSubmitHargaKelompokObat').click(function() {
    if (modalState == 'add') {

			const fd = new FormData();
			fd.append('MedicineGroupId', $('#selectedKelompokObat').val());
			fd.append('HargaJual', $('#hargaJual').val().replaceAll('.', ''));
      fd.append('HargaModal', $('#hargaModal').val().replaceAll('.', ''));
      fd.append('FeeDokter', $('#feeDokter').val().replaceAll('.', ''));
      fd.append('FeePetShop', $('#feePetshop').val().replaceAll('.', ''));

			$.ajax({
				url : $('.baseUrl').val() + '/api/pembagian-harga-kelompok-obat',
				type: 'POST',
				dataType: 'JSON',
				headers: { 'Authorization': `Bearer ${token}` },
				data: fd, contentType: false, cache: false,
				processData: false,
				beforeSend: function() { $('#loading-screen').show(); },
				success: function(resp) {

					$("#msg-box .modal-body").text('Berhasil Menambah Data');
					$('#msg-box').modal('show');

					setTimeout(() => {
						$('#modal-harga-kelompok-obat').modal('toggle');
						refreshForm(); loadHargaKelompokObat();
					}, 1000);
				}, complete: function() { $('#loading-screen').hide(); }
				, error: function(err) {
					if (err.status === 422) {
						let errText = ''; $('#beErr').empty(); $('#btnSubmitHargaKelompokObat').attr('disabled', true);
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
			$('#modal-confirmation .modal-title').text('Peringatan');
			$('#modal-confirmation .box-body').text('Anda yakin untuk mengubah data ini ?');
			$('#modal-confirmation').modal('show');
    }
  });

  $('#submitConfirm').click(function() {
    if (modalState == 'edit') {
      // process edit

      const datas = {
        id: getId,
        MedicineGroupId: $('#selectedKelompokObat').val(),
        HargaJual: $('#hargaJual').val().replaceAll('.', ''),
        HargaModal: $('#hargaModal').val().replaceAll('.', ''),
        FeeDokter: $('#feeDokter').val().replaceAll('.', ''),
        FeePetShop: $('#feePetshop').val().replaceAll('.', '')
      };

      $.ajax({
        url : $('.baseUrl').val() + '/api/pembagian-harga-kelompok-obat',
        type: 'PUT',
        dataType: 'JSON',
        headers: { 'Authorization': `Bearer ${token}` },
        data: datas,
        beforeSend: function() { $('#loading-screen').show(); },
        success: function(data) {
          $('#modal-confirmation .modal-title').text('Peringatan');
          $('#modal-confirmation').modal('toggle');

          $("#msg-box .modal-body").text('Berhasil Mengubah Data');
          $('#msg-box').modal('show');

          setTimeout(() => {
            $('#modal-harga-kelompok-obat').modal('toggle');
            refreshForm(); loadHargaKelompokObat();
          }, 1000);

        }, complete: function() { $('#loading-screen').hide(); }
        , error: function(err) {
          if (err.status == 401) {
            localStorage.removeItem('vet-clinic');
            location.href = $('.baseUrl').val() + '/masuk';
          }
        }
      });
    } else {
      // process delete
      $.ajax({
        url     : $('.baseUrl').val() + '/api/pembagian-harga-kelompok-obat',
        headers : { 'Authorization': `Bearer ${token}` },
        type    : 'DELETE',
        data	  : { id: getId },
        beforeSend: function() { $('#loading-screen').show(); },
        success: function(data) {
          $('#modal-confirmation .modal-title').text('Peringatan');
          $('#modal-confirmation').modal('toggle');

          $("#msg-box .modal-body").text('Berhasil Menghapus Data');
          $('#msg-box').modal('show');

          loadHargaKelompokObat();

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

  $('#selectedCabang').on('select2:select', function (e) {
    if ($(this).val()) {
      loadKelompokObat($(this).val());
    } else {
      optKelompokObat = `<option value=''>Pilih Kelompok Obat</option>`;
      $('#selectedKelompokObat option').remove();
      $('#selectedKelompokObat').val(null);
      $('#selectedKelompokObat').append(optKelompokObat);
      $('#selectedKelompokObat').attr('disabled', true);
    }

    validationHargaJual(); validationForm();
  });

  $('#selectedKelompokObat').on('select2:select', function (e) {
    validationHargaJual(); validationForm();
  });

  $('#hargaJual').mask("#.##0", {reverse: true, maxlength: false});
  $('#hargaModal').mask("#.##0", {reverse: true, maxlength: false});
  $('#feeDokter').mask("#.##0", {reverse: true, maxlength: false});
  $('#feePetshop').mask("#.##0", {reverse: true, maxlength: false});

  $('#hargaJual').keyup(function () { validationHargaJual(); validationForm(); });
  $('#hargaModal').keyup(function () { validationHargaJual(); validationForm(); });
  $('#feeDokter').keyup(function () { validationHargaJual(); validationForm(); });
  $('#feePetshop').keyup(function () { validationHargaJual(); validationForm(); });

  $('#filterCabang').on('select2:select', function () { onFilterCabang($(this).val()); });
  $('#filterCabang').on("select2:unselect", function () { onFilterCabang($(this).val()); });

  function onFilterCabang(value) {
    paramUrlSetup.branchId = value;
		loadHargaKelompokObat();
  }

  function onSearch(keyword) {
		paramUrlSetup.keyword = keyword;
		loadHargaKelompokObat();
	}

  function loadHargaKelompokObat() {

    getId = null; modalState = '';
    $.ajax({
			url     : $('.baseUrl').val() + '/api/pembagian-harga-kelompok-obat',
			headers : { 'Authorization': `Bearer ${token}` },
			type    : 'GET',
			data	  : { orderby: paramUrlSetup.orderby, column: paramUrlSetup.column, keyword: paramUrlSetup.keyword, branch_id: paramUrlSetup.branchId, page: getCurrentPage },
			beforeSend: function() { $('#loading-screen').show(); },
			success: function(resp) {
        const getData = resp.data;
				let listHargaKelompokObat = '';

				$('#list-harga-kelompok-obat tr').remove();

        if (getData.length) {
          $.each(getData, function(idx, v) {
            listHargaKelompokObat += `<tr>`
              + `<td>${++idx}</td>`
              + `<td>${v.group_name}</td>`
              + `<td>${v.branch_name}</td>`
              + `<td>Rp ${
                Number(v.selling_price || 0).toLocaleString('id-ID')
                // typeof(v.selling_price) == 'number' ? v.selling_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
              }</td>`
              + `<td>Rp ${
                Number(v.capital_price || 0).toLocaleString('id-ID')
                // typeof(v.capital_price) == 'number' ? v.capital_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
              }</td>`
              + `<td>Rp ${
                Number(v.doctor_fee || 0).toLocaleString('id-ID')
                // typeof(v.doctor_fee) == 'number' ? v.doctor_fee.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
              }</td>`
              + `<td>Rp ${
                Number(v.petshop_fee || 0).toLocaleString('id-ID')
                // typeof(v.petshop_fee) == 'number' ? v.petshop_fee.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''
              }</td>`
              + `<td>${v.created_by}</td>`
              + `<td>${v.created_at}</td>`
              + ((role.toLowerCase() != 'admin') ? `` : `<td>
                  <button type="button" class="btn btn-warning openFormEdit" value=${v.id}><i class="fa fa-pencil" aria-hidden="true"></i></button>
                  <button type="button" class="btn btn-danger openFormDelete" value=${v.id}><i class="fa fa-trash-o" aria-hidden="true"></i></button>
                </td>`)
              + `</tr>`;
          });
        } else {
          listHargaKelompokObat += `<tr class="text-center"><td colspan="10">Tidak ada data.</td></tr>`;
        }

				$('#list-harga-kelompok-obat').append(listHargaKelompokObat);

        generatePagination(getCurrentPage, resp.total_paging);

				$('.openFormEdit').click(function() {
					const getObj = resp.data.find(x => x.id == $(this).val());
					modalState = 'edit';

					$('.modal-title').text('Edit Pembagian Harga Kelompok Obat');
          refreshForm();

          loadKelompokObat(getObj.branch_id, getObj.medicine_group_id); formConfigure();

					getId = getObj.id;
          $('#hargaJual').val(getObj.selling_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'));
          $('#hargaModal').val(getObj.capital_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'));
          $('#feeDokter').val(getObj.doctor_fee.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'));
          $('#feePetshop').val(getObj.petshop_fee.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'));
          $('#selectedCabang').val(getObj.branch_id); $('#selectedCabang').trigger('change');
				});

				$('.openFormDelete').click(function() {
					getId = $(this).val();
					modalState = 'delete';

					$('#modal-confirmation .modal-title').text('Peringatan');
					$('#modal-confirmation .box-body').text('Anda yakin ingin menghapus data ini?');
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

					loadHargaKelompokObat();
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

  function loadCabang() {
		$.ajax({
			url     : $('.baseUrl').val() + '/api/cabang',
			headers : { 'Authorization': `Bearer ${token}` },
			type    : 'GET',
			beforeSend: function() { $('#loading-screen').show(); },
			success: function(data) {
				if (data.length) {
					for (let i = 0 ; i < data.length ; i++) {
						optCabang += `<option value=${data[i].id}>${data[i].branch_name}</option>`;
					}
				}
        $('#selectedCabang').append(optCabang); $('#filterCabang').append(optCabang);
			}, complete: function() { $('#loading-screen').hide(); },
			error: function(err) {
				if (err.status == 401) {
					localStorage.removeItem('vet-clinic');
					location.href = $('.baseUrl').val() + '/masuk';
				}
			}
		});
  }

  function loadKelompokObat(idCabang, idMedicineGroup = null) {
    $.ajax({
			url     : $('.baseUrl').val() + '/api/pembagian-harga-kelompok-obat/cabang-obat',
			headers : { 'Authorization': `Bearer ${token}` },
			type    : 'GET',
			data	  : { branch_id: idCabang },
			beforeSend: function() { $('#loading-screen').show(); },
			success: function(data) {
        optKelompokObat = `<option value=''>Pilih Kelompok Obat</option>`;
        $('#selectedKelompokObat option').remove();

        if (data.length) {
          $('#selectedKelompokObat').attr('disabled', false);
					for (let i = 0 ; i < data.length ; i++) {
						optKelompokObat += `<option value=${data[i].medicine_group_id}>${data[i].group_name}</option>`;
					}
				} else {
          $('#selectedKelompokObat').attr('disabled', true);
          optKelompokObat = `<option value='' selected="selected">Data tidak ada</option>`;
        }
        $('#selectedKelompokObat').append(optKelompokObat);

        if (modalState == 'edit') {
          $('#selectedKelompokObat').val(idMedicineGroup); $('#selectedKelompokObat').trigger('change');
          $('#selectedKelompokObat').attr('disabled', true);
        }

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
    if (!$('#selectedCabang').val()) {
			$('#cabangErr1').text('Cabang barang harus di isi'); isValidSelectedCabang = false;
		} else {
			$('#cabangErr1').text(''); isValidSelectedCabang = true;
		}

		if (!$('#selectedKelompokObat').val()) {
			$('#kelompokObatErr1').text('Kelompok obat harus di isi'); isValidSelectedKelompokObat = false;
		} else {
			$('#kelompokObatErr1').text(''); isValidSelectedKelompokObat = true;
		}

    if ($('#hargaJual').val() == '') {
			$('#hargaJualErr1').text('Harga jual harus di isi'); isValidHargaJual = false;
		} else {
			$('#hargaJualErr1').text(''); isValidHargaJual = true;
    }

    if ($('#hargaModal').val() == '') {
			$('#hargaModalErr1').text('Harga modal harus di isi'); isValidHargaModal = false;
		} else {
			$('#hargaModalErr1').text(''); isValidHargaModal = true;
    }

    if ($('#feeDokter').val() == '') {
			$('#feeDokterErr1').text('Fee dokter harus di isi'); isValidFeeDokter = false;
		} else {
			$('#feeDokterErr1').text(''); isValidFeeDokter = true;
    }

    if ($('#feePetshop').val() == '') {
			$('#feePetshopErr1').text('Fee petshop harus di isi'); isValidFeePetshop = false;
		} else {
			$('#feePetshopErr1').text(''); isValidFeePetshop = true;
    }

    $('#beErr').empty(); isBeErr = false;
    validationBtnSubmitHargaKelompokObat();
  }

  function refreshForm() {
		$('#selectedCabang').val(null);
		$('#selectedKelompokObat').val(null);

    $('#hargaJual').val(null); $('#hargaModal').val(null);
    $('#feeDokter').val(null); $('#feePetshop').val(null);

    $('#customErr1').empty(); customErr1 = false;
    $('#beErr').empty(); isBeErr = false;

    $('#cabangErr1').text(''); isValidSelectedCabang = true;
    $('#kelompokObatErr1').text(''); isValidSelectedKelompokObat = true;

    $('#hargaJualErr1').text(''); isValidHargaJual = true;
    $('#hargaModalErr1').text(''); isValidHargaModal = true;
    $('#feeDokterErr1').text(''); isValidFeeDokter = true;
    $('#feePetshopErr1').text(''); isValidFeePetshop = true;
  }

  function formConfigure() {
    $('#selectedCabang').select2();
		$('#selectedKelompokObat').select2();

		$('#modal-harga-kelompok-obat').modal('show');
		$('#btnSubmitHargaKelompokObat').attr('disabled', true);

    if(modalState == 'add') {
      $('#selectedCabang').attr('disabled', false);
      $('#selectedKelompokObat').attr('disabled', true);
    } else if(modalState == 'edit') {
      $('#selectedCabang').attr('disabled', true);
    }
  }

  function validationHargaJual() {
    let hargaJual  = $('#hargaJual').val();
    let hargaModal = $('#hargaModal').val();
    let feeDokter  = $('#feeDokter').val();
    let feePetshop = $('#feePetshop').val();

    hargaJual  = hargaJual.replaceAll('.', '');
    hargaModal = hargaModal.replaceAll('.', '');
    feeDokter  = feeDokter.replaceAll('.', '');
    feePetshop = feePetshop.replaceAll('.', '');

    const totalHargaJual = parseInt(hargaModal) + parseInt(feeDokter) + parseInt(feePetshop);

    if (parseInt(hargaJual) !== totalHargaJual) {
      $('#customErr1').text('Total harga modal, fee dokter, dan fee petshop tidak sama dengan harga jual');
      customErr1 = false;
		} else {
			$('#customErr1').text(''); customErr1 = true;
		}
  }

  function validationBtnSubmitHargaKelompokObat() {
    if (!isValidSelectedCabang || !isValidSelectedKelompokObat || !isValidHargaJual
      || !isValidHargaModal || !isValidFeeDokter || !isValidFeePetshop
      || !customErr1 || isBeErr) {
			$('#btnSubmitHargaKelompokObat').attr('disabled', true);
		} else {
			$('#btnSubmitHargaKelompokObat').attr('disabled', false);
		}
  }

})
