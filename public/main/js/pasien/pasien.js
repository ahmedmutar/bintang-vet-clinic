$(document).ready(function() {
	let modalState = '';
	let getId = null
	let optCabang = '';
  let optCabang1 = '';
  let ownerId = null;
  let ownerNameValue = '';
  let listOwner = [];
	let getCurrentPage = 1;

  let isTambahPemilik = false;
  let isValidBranch = false;
	let isValidAnimalType = false;
  let isValidAnimalName = false;
  let isValidAnimalSex = false;
	let isValidAnimalAge = false;

	let isValidOwnerName = false;
	let isValidOwnerAddress = false;
	let isValidOwnerTelp = false;

	let isBeErr = false;
	let paramUrlSetup = {
		orderby:'',
		column: '',
    keyword: '',
    branchId: ''
	};

	if (role.toLowerCase() == 'resepsionis') {
		$('.columnAction').hide();
	} else if (role.toLowerCase() == 'admin') {
		$('.section-right-box-title').append(`<select id="filterCabang" style="width: 50%"></select>`);
		$('#filterCabang').select2({ placeholder: 'Cabang', allowClear: true });
    $('#branch').select2({ placeholder: 'Cabang' });
		loadCabang();
	}

	loadPasien();
	loadPetCategories();

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

		loadPasien();
	});

	$('.openFormAdd').click(function() {
		modalState = 'add';
    $('.modal-title').text('Tambah Pasien');
		$('.detail-register').hide();

    if (role.toLowerCase() == 'admin') {
      $('.field-cabang').show();
      $('.isShowPemilik').hide();
    } else {
      $('.field-cabang').hide();
      $('.isShowPemilik').show();
      loadNamaPemilik(branchId);
    }
    refreshForm();
    formConfigure();
    validationFieldNamaPemilik();
	});

  $('#branch').on('select2:select', function (e) {
    validationForm(); loadNamaPemilik($('#branch').val()); $('.isShowPemilik').show();
  });
  $('#ownerDropdown').on('select2:select', function (e) {
    const getOwner = listOwner.find(d => d.id == $(this).val());
    if (getOwner) {
      ownerNameValue = getOwner.owner_name;
      ownerId = getOwner.id;

      $('#ownerAddress').val(getOwner.owner_address);
      $('#ownerTelp').val(getOwner.owner_phone_number);
    }
    validationForm();
  });
  $('#ownerName').keyup(function () { ownerNameValue = $(this).val(); validationForm(); });
	$('#animalSex').on('select2:select', function (e) { validationForm(); });
	$('#animalType').on('select2:select', function () { onAnimalTypeChange(); validationForm(); });
	$('#otherAnimalType').keyup(function () { validationForm(); });
	$('#animalName').keyup(function () { validationForm(); });
	$('#animalAgeYear').keyup(function () { validationForm(); });
	$('#animalAgeMonth').keyup(function () { validationForm(); });
	$('#animalAgeDay').keyup(function () { validationForm(); });
	$('#ownerName').keyup(function () { validationForm(); });
	$('#ownerAddress').keyup(function () { validationForm(); });
	$('#ownerTelp').keyup(function () { validationForm(); });

  $('#btnNamaPemilik').click(function () {
    isTambahPemilik = !isTambahPemilik;
    validationFieldNamaPemilik();
    validationForm();
  });

	$('#filterCabang').on('select2:select', function () { onFilterCabang($(this).val()); });
	$('#filterCabang').on("select2:unselect", function () { onFilterCabang($(this).val()); });

	$('#btnSubmitPasien').click(function() {
    if (modalState == 'add') {

			const fd = new FormData();
			fd.append('pet_category_id', $('#animalType').val());
			fd.append('other_pet_category', $('#animalType').val() == 6 ? $('#otherAnimalType').val() : '');
			fd.append('nama_hewan', $('#animalName').val());
			fd.append('jenis_kelamin_hewan', $('#animalSex').val());
      fd.append('usia_tahun_hewan', $('#animalAgeYear').val());
      fd.append('usia_bulan_hewan', $('#animalAgeMonth').val());
      fd.append('usia_hari_hewan', $('#animalAgeDay').val());
      fd.append('id_pemilik', (isTambahPemilik ? '' : ownerId));
			fd.append('nama_pemilik', ownerNameValue);
			fd.append('alamat_pemilik', $('#ownerAddress').val());
			fd.append('nomor_ponsel_pengirim', $('#ownerTelp').val());
      fd.append('id_cabang', role.toLowerCase() == 'admin' ? $('#branch').val() : 0);

			$.ajax({
				url : $('.baseUrl').val() + '/api/pasien',
				type: 'POST',
				dataType: 'JSON',
				headers: { 'Authorization': `Bearer ${token}` },
				data: fd, contentType: false, cache: false,
				processData: false,
				beforeSend: function() { $('#loading-screen').show(); },
				success: function(resp) {

					$("#msg-box .modal-body").text('Berhasil Menambah Pasien');
					$('#msg-box').modal('show');

					setTimeout(() => {
						$('#modal-pasien').modal('toggle');
						refreshForm();
						loadPasien();
					}, 1000);
				}, complete: function() { $('#loading-screen').hide(); }
				, error: function(err) {
					if (err.status === 422) {
						let errText = ''; $('#beErr').empty(); $('#btnSubmitPasien').attr('disabled', true);
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
        pet_category_id: $('#animalType').val(),
        other_pet_category: $('#animalType').val() == 6 ? $('#otherAnimalType').val() : '',
        nama_hewan: $('#animalName').val(),
        jenis_kelamin_hewan: $('#animalSex').val(),
        usia_tahun_hewan: $('#animalAgeYear').val(),
				usia_bulan_hewan: $('#animalAgeMonth').val(),
				usia_hari_hewan: $('#animalAgeDay').val(),
        id_pemilik: (isTambahPemilik ? ((ownerId == 0) ? 0 : '') : ownerId),
				nama_pemilik: ownerNameValue,
				alamat_pemilik: $('#ownerAddress').val(),
				nomor_ponsel_pengirim: $('#ownerTelp').val(),
        id_cabang: role.toLowerCase() == 'admin' ? Number($('#branch').val()) : 0,
        id_member: $('#noRegisTxt').text()
			};

      $.ajax({
        url : $('.baseUrl').val() + '/api/pasien',
        type: 'PUT',
        dataType: 'JSON',
        headers: { 'Authorization': `Bearer ${token}` },
        data: datas,
        beforeSend: function() { $('#loading-screen').show(); },
        success: function(data) {
          $('#modal-confirmation .modal-title').text('Peringatan');
          $('#modal-confirmation').modal('toggle');

          $("#msg-box .modal-body").text('Berhasil Mengubah data');
          $('#msg-box').modal('show');

          setTimeout(() => {
            $('#modal-pasien').modal('toggle');
            refreshForm(); loadPasien();
          }, 1000);

        }, complete: function() { $('#loading-screen').hide(); }
        , error: function(err) {
					if (err.status === 422) {
						$('#modal-confirmation').modal('toggle');
						let errText = ''; $('#beErr').empty(); $('#btnSubmitPasien').attr('disabled', true);
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
        url     : $('.baseUrl').val() + '/api/pasien',
        headers : { 'Authorization': `Bearer ${token}` },
        type    : 'DELETE',
        data	  : { id: getId },
        beforeSend: function() { $('#loading-screen').show(); },
        success: function(data) {
          $('#modal-confirmation .modal-title').text('Peringatan');
          $('#modal-confirmation').modal('toggle');

          $("#msg-box .modal-body").text('Berhasil menghapus data');
          $('#msg-box').modal('show');

          loadPasien();

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

  function validationFieldNamaPemilik() {
    if(isTambahPemilik) {
      $('#ownerDropdown').next().hide();
      $('#ownerName').show();
      $('#btnNamaPemilik').text('Batal');
      $('#btnNamaPemilik').addClass('btn-danger');
      $('#btnNamaPemilik').removeClass('btn-primary');
    } else {
      $('#ownerDropdown').next().show();
      $('#ownerName').hide();
      $('#btnNamaPemilik').text('Tambah Pemilik');
      $('#btnNamaPemilik').addClass('btn-primary');
      $('#btnNamaPemilik').removeClass('btn-danger');
    }
  }

  function onFilterCabang(value) {
    paramUrlSetup.branchId = value;
		loadPasien();
  }

  function onSearch(keyword) {
		paramUrlSetup.keyword = keyword;
		loadPasien();
	}

	function loadPasien() {
		getId = null; modalState = '';
    $.ajax({
			url     : $('.baseUrl').val() + '/api/pasien',
			headers : { 'Authorization': `Bearer ${token}` },
			type    : 'GET',
			data	  : { orderby: paramUrlSetup.orderby, column: paramUrlSetup.column, keyword: paramUrlSetup.keyword, branch_id: paramUrlSetup.branchId, page: getCurrentPage },
			beforeSend: function() { $('#loading-screen').show(); },
			success: function(resp) {
				const getData = resp.data;
				let listPasien = '';
				$('#list-pasien tr').remove();

        if(getData.length) {
          $.each(getData, function(idx, v) {
            listPasien += `<tr>`
              + `<td>${++idx}</td>`
              + `<td>${v.id_member}</td>`
              + `<td>${v.created_at}</td>`
              + `<td>${v.pet_category}</td>`
              + `<td>${v.pet_name}</td>`
              + `<td>${v.owner_name}</td>`
              + `<td>${v.pet_gender}</td>`
              + `<td>${v.pet_year_age} Tahun ${v.pet_month_age} Bulan ${v.pet_day_age} Hari</td>`
              + `<td>${v.branch_name}</td>`
              + `<td>${v.created_by}</td>`
              + ((role.toLowerCase() == 'resepsionis') ? `` : `<td>
                  <button type="button" class="btn btn-info openDetail" title="Riwayat Pasien" value=${v.id}><i class="fa fa-eye" aria-hidden="true"></i></button>
                  <button type="button" class="btn btn-warning openFormEdit" title="Edit" value=${v.id}><i class="fa fa-pencil" aria-hidden="true"></i></button>
                  <button type="button" class="btn btn-danger openFormDelete" title="Delete" value=${v.id}><i class="fa fa-trash-o" aria-hidden="true"></i></button>
                </td>`)
              + `</tr>`;
          });
        } else {
          listPasien += `<tr class="text-center"><td colspan="11">Tidak ada data.</td></tr>`;
        }
				$('#list-pasien').append(listPasien);

				generatePagination(getCurrentPage, resp.total_paging);

				$('.openFormEdit').click(function() {
					const getObj = resp.data.find(x => x.id == $(this).val());
					modalState = 'edit';

					$('.modal-title').text('Edit Pasien');
          $('.isShowPemilik').show();
          refreshForm(); formConfigure();

          getId = getObj.id;
          ownerId = getObj.owner_id;
          ownerNameValue = getObj.owner_name;
          isTambahPemilik = (ownerId == 0) ? true : false;

					$('.detail-register').show();
          if (role.toLowerCase() == 'admin') {
            $('.field-cabang').show();
            $('#branch').val(getObj.branch_id); $('#branch').trigger('change');
            loadNamaPemilik(getObj.branch_id);
          } else {
            $('.field-cabang').hide();
            loadNamaPemilik(branchId);
          }

          $('#noRegisTxt').text(getObj.id_member);
					$('#animalSex').val(getObj.pet_gender); $('#animalSex').trigger('change');
					$('#animalType').val(getObj.pet_category_id); $('#animalType').trigger('change');
					if (getObj.pet_category_id == 6) {
						$('#otherAnimalType').show().val(getObj.other_pet_category);
					} else {
						$('#otherAnimalType').hide().val('');
					}
					$('#animalName').val(getObj.pet_name);
					$('#animalAgeYear').val(getObj.pet_year_age);
					$('#animalAgeMonth').val(getObj.pet_month_age);
					$('#animalAgeDay').val(getObj.pet_day_age);
          $('#ownerName').val(ownerNameValue);
          $('#ownerAddress').val(getObj.owner_address);
          $('#ownerTelp').val(getObj.owner_phone_number);
          validationFieldNamaPemilik();
				});

				$('.openFormDelete').click(function() {
					getId = $(this).val();
					modalState = 'delete';

					$('#modal-confirmation .modal-title').text('Peringatan');
					$('#modal-confirmation .box-body').text('Anda yakin ingin menghapus data ini?');
					$('#modal-confirmation').modal('show');
				});

				$('.openDetail').click(function() {
					window.location.href = $('.baseUrl').val() + `/riwayat-pameriksaan/${$(this).val()}`;
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

					loadPasien();
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

  function loadNamaPemilik(branchId) {
    $.ajax({
			url     : $('.baseUrl').val() + '/api/pasien/daftar-pemilik',
			headers : { 'Authorization': `Bearer ${token}` },
      data	  : { branch_id: branchId },
			type    : 'GET',
			beforeSend: function() { $('#loading-screen').show(); },
			success: function(data) {
        let optOwnerName = '';
				optOwnerName += `<option value=''>Pilih Nama Pemilik</option>`;
        $('#ownerDropdown option').remove();

        listOwner = data;
				if (data.length) {
					for (let i = 0 ; i < data.length ; i++) {
						optOwnerName += `<option value=${data[i].id}>${data[i].owner_name} - ${data[i].owner_address} - ${data[i].owner_phone_number}</option>`;
					}
				}
        $('#ownerDropdown').append(optOwnerName);

        if (modalState == 'edit') {
          $('#ownerDropdown').val(ownerId); $('#ownerDropdown').trigger('change');
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

	function loadPetCategories() {
		$.ajax({
			url    : $('.baseUrl').val() + '/api/pasien/pet-categories',
			headers: { 'Authorization': `Bearer ${token}` },
			type   : 'GET',
			success: function(data) {
				let opts = '<option value="">Pilih Jenis Hewan</option>';
				data.forEach(function(cat) {
					opts += `<option value="${cat.id}">${cat.name}</option>`;
				});
				$('#animalType').html(opts);
			}
		});
	}

	function onAnimalTypeChange() {
		if ($('#animalType').val() == 6) {
			$('#otherAnimalType').show();
		} else {
			$('#otherAnimalType').hide().val('');
		}
	}

	function loadCabang() {
		$.ajax({
			url     : $('.baseUrl').val() + '/api/cabang',
			headers : { 'Authorization': `Bearer ${token}` },
			type    : 'GET',
			beforeSend: function() { $('#loading-screen').show(); },
			success: function(data) {
				optCabang += `<option value=''>Cabang</option>`;
        optCabang1 += `<option value=''>Cabang</option>`;
				if (data.length) {
					for (let i = 0 ; i < data.length ; i++) {
						optCabang += `<option value=${data[i].id}>${data[i].branch_name}</option>`;
            optCabang1 += `<option value=${data[i].id}>${data[i].branch_name}</option>`;
					}
				}
        $('#filterCabang').append(optCabang);
        $('#branch').append(optCabang1);
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

    if (!$('#branch').val() && role.toLowerCase() == 'admin') {
			$('#branchErr1').text('Cabang Harus di isi!'); isValidBranch = false;
		} else {
			$('#branchErr1').text(''); isValidBranch = true;
    }

		const animalTypeVal = $('#animalType').val();
		if (!animalTypeVal || (animalTypeVal == 6 && !$('#otherAnimalType').val())) {
			$('#animalTypeErr1').text('Jenis hewan harus di isi'); isValidAnimalType = false;
		} else {
			$('#animalTypeErr1').text(''); isValidAnimalType = true;
		}

		if (!$('#animalName').val()) {
			$('#animalNameErr1').text('Nama Hewan harus di isi'); isValidAnimalName = false;
		} else {
			$('#animalNameErr1').text(''); isValidAnimalName = true;
		}

		if (!$('#animalSex').val()) {
			$('#animalSexErr1').text('Jenis Kelamin Hewan harus di isi'); isValidAnimalSex = false;
		} else {
			$('#animalSexErr1').text(''); isValidAnimalSex = true;
    }

    if (!$('#animalAgeYear').val() || !$('#animalAgeMonth').val() || !$('#animalAgeDay').val()) {
			$('#animalAgeErr1').text('Usia Hewan harus di isi'); isValidAnimalAge = false;
		} else if (parseInt($('#animalAgeMonth').val()) > 12) {
			$('#animalAgeErr1').text('Bulan tidak boleh lebih dari 12'); isValidAnimalAge = false;
		} else if (parseInt($('#animalAgeDay').val()) > 30) {
			$('#animalAgeErr1').text('Hari tidak boleh lebih dari 30'); isValidAnimalAge = false;
		} else {
			$('#animalAgeErr1').text(''); isValidAnimalAge = true;
    }

    if ((isTambahPemilik && !$('#ownerName').val()) || (!isTambahPemilik && !$('#ownerDropdown').val())) {
			$('#ownerNameErr1').text('Nama Pemilik harus di isi'); isValidOwnerName = false;
		} else {
			$('#ownerNameErr1').text(''); isValidOwnerName = true;
    }

    if ($('#ownerAddress').val() == '') {
			$('#ownerAddressErr1').text('Alamat Pemilik harus di isi'); isValidOwnerAddress = false;
		} else {
			$('#ownerAddressErr1').text(''); isValidOwnerAddress = true;
		}

		if ($('#ownerTelp').val() == '') {
			$('#ownerTelpErr1').text('Telpon Pemilik harus di isi'); isValidOwnerTelp = false;
		} else {
			$('#ownerTelpErr1').text(''); isValidOwnerTelp = true;
    }

		$('#beErr').empty(); isBeErr = false;

		if ((!isValidBranch && role.toLowerCase() == 'admin') || !isValidAnimalType || !isValidAnimalName
      || !isValidAnimalSex  || !isValidAnimalAge || !isValidOwnerName || !isValidOwnerAddress
      || !isValidOwnerTelp || isBeErr) {
			$('#btnSubmitPasien').attr('disabled', true);
		} else {
			$('#btnSubmitPasien').attr('disabled', false);
		}
	}

	function refreshForm() {
    $('#branch').val(null); $('#branch').trigger('change');
		$('#animalType').val(null); $('#animalType').trigger('change');
		$('#otherAnimalType').hide().val('');
		$('#animalSex').val(null);
    $('#animalName').val(null); $('#ownerTelp').val(null);
		$('#animalAgeYear').val(null); $('#animalAgeDay').val(null); $('#ownerAddress').val(null);

    $('#ownerName').val(null); ownerNameValue = ''; ownerId = null;
    $('#ownerDropdown').val(null); $('#ownerDropdown').trigger('change');

    $('#animalSexErr1').text(''); isValidAnimalSex = true;
    $('#branchErr1').text(''); isValidBranch = true;
    $('#animalAgeMonth').val(null); isValidAnimalAgeMonth = true;
    $('#animalAgeDay').val(null); isValidAnimalAgeDay = true;
    $('#animalTypeErr1').text(''); isValidAnimalType = true;
    $('#animalNameErr1').text('');isValidAnimalName = true;
    $('#animalAgeErr1').text(''); isValidAnimalAgeYear = true;
    $('#ownerNameErr1').text('');isValidOwnerName = true;
    $('#ownerAddressErr1').text('');isValidOwnerAddress = true;
    $('#ownerTelpErr1').text('');isValidOwnerTelp = true;

    $('#beErr').empty(); isBeErr = false;

    $('#noRegisTxt').text('');
    isTambahPemilik = false;
	}

	function formConfigure() {
		$('#animalType').select2({ placeholder: 'Pilih Jenis Hewan' });
		$('#animalSex').select2();
    $('#ownerDropdown').select2();
		$('#modal-pasien').modal('show');
		$('#btnSubmitPasien').attr('disabled', true);
	}

});
