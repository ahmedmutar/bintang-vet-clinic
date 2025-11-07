$(document).ready(function() {
	let modalState = '';
	let getId = null
	let optCabang1 = '';
	let optCabang2 = '';
	let showPassword1 = false;
	let showPassword2 = false;
	let getUser = [];
	let listCabang = [];

	let isValidUsername = false;
	let isValidNamaLengkap = false;
	let isValidEmail = false;
	let isValidPassword = false;
	let isValidConfPassword = false;
	let isValidNomPonsel = false;
	let isValidSelectRole = false;
	let isValidSelectCabang = false;
	let isValidSelectStatus = false;

	let isBeErr = false;
	let paramUrlSetup = {
		orderby:'',
		column: '',
    keyword: '',
    branchId: ''
	};

	$('#filterCabang').select2({ placeholder: 'Cabang', allowClear: true });

	if (role.toLowerCase() !== 'admin') {
		window.location.href = $('.baseUrl').val() + `/unauthorized`;
	} else {
		loadUser(); loadCabang();
	}

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

		loadUser();
	});

	$('.openFormAdd').click(function() {
		modalState = 'add';
    $('.modal-title').text('Tambah User');
		$('.detail-user').hide(); $('.stateModalAdd').show();

    refreshForm(); formConfigure();
	});

	$('#username').keyup(function () { validationForm(); });
	$('#namaLengkap').keyup(function () { validationForm(); });
	$('#email').keyup(function () { validationForm(); });
	$('#password').keyup(function () { validationForm(); });
	$('#confPassword').keyup(function () { validationForm(); });
	$('#nomPonsel').keyup(function () { validationForm(); });

	$('#togglePassword').click(function() {
		showPassword1 = !showPassword1;
		if (!showPassword1) {
			$('#password').attr('type', 'password');
			$(this).removeClass('glyphicon-eye-close').addClass('glyphicon-eye-open');
		} else {
			$('#password').attr('type', 'text');
			$(this).removeClass('glyphicon-eye-open').addClass('glyphicon-eye-close');
		}
	});

	$('#toggleConfPassword').click(function() {
		showPassword2 = !showPassword2;
		if (!showPassword2) {
			$('#confPassword').attr('type', 'password');
			$(this).removeClass('glyphicon-eye-close').addClass('glyphicon-eye-open');
		} else {
			$('#confPassword').attr('type', 'text');
			$(this).removeClass('glyphicon-eye-open').addClass('glyphicon-eye-close');
		}
	});

	$('#selectRole').on('select2:select', function (e) { validationForm(); });
	$('#selectCabang').on('select2:select', function (e) { validationForm(); });
	$('#selectStatus').on('select2:select', function (e) { validationForm(); });

	$('#filterCabang').on('select2:select', function () { onFilterCabang($(this).val()); });
	$('#filterCabang').on("select2:unselect", function () { onFilterCabang($(this).val()); });

	$('#btnSubmitUser').click(function() {
    if (modalState == 'add') {
			const objCabang = listCabang.find(x => x.id == $('#selectCabang').val());
			const fd = new FormData();

			fd.append('username', $('#username').val());
			fd.append('nama_lengkap', $('#namaLengkap').val());
			fd.append('email', $('#email').val());
      fd.append('password', $('#password').val());
      fd.append('nomor_ponsel', $('#nomPonsel').val());
			fd.append('role', $('#selectRole').val());
			fd.append('status', $('#selectStatus').val());
			fd.append('id_cabang', $('#selectCabang').val());
			fd.append('kode_cabang', objCabang.branch_code);

			$.ajax({
				url : $('.baseUrl').val() + '/api/user',
				type: 'POST',
				dataType: 'JSON',
				headers: { 'Authorization': `Bearer ${token}` },
				data: fd, contentType: false, cache: false,
				processData: false,
				beforeSend: function() { $('#loading-screen').show(); },
				success: function(resp) {

					$("#msg-box .modal-body").text('Berhasil Menambah User');
					$('#msg-box').modal('show');

					setTimeout(() => {
						$('#modal-user').modal('toggle');
						refreshForm(); loadUser();
					}, 1000);
				}, complete: function() { $('#loading-screen').hide(); }
				, error: function(err) {
					if (err.status === 422) {
						let errText = ''; $('#beErr').empty(); $('#btnSubmitUser').attr('disabled', true);
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
			const objCabang = listCabang.find(x => x.id == $('#selectCabang').val());
			const objUser = getUser.find(x => x.id == getId);

      const datas = {
        id: getId,
				nomor_kepegawaian: objUser.staffing_number,
				nama_lengkap: $('#namaLengkap').val(),
				role: $('#selectRole').val(),
				status: $('#selectStatus').val(),
				id_cabang: $('#selectCabang').val(),
				kode_cabang: objCabang.branch_code
			};

      $.ajax({
        url : $('.baseUrl').val() + '/api/user',
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
            $('#modal-user').modal('toggle');
            refreshForm(); loadUser();
          }, 1000);

        }, complete: function() { $('#loading-screen').hide(); }
        , error: function(err) {
					if (err.status === 422) {
						$('#modal-confirmation').modal('toggle');
						let errText = ''; $('#beErr').empty(); $('#btnSubmitUser').attr('disabled', true);
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
    } else if(modalState == 'delete') {
			// process delete
			$.ajax({
				url     : $('.baseUrl').val() + '/api/user',
				headers : { 'Authorization': `Bearer ${token}` },
				type    : 'DELETE',
				data	  : { user_id: getId },
				beforeSend: function() { $('#loading-screen').show(); },
				success: function(data) {
					$('#modal-confirmation .modal-title').text('Peringatan');
					$('#modal-confirmation').modal('toggle');

					$("#msg-box .modal-body").text('Berhasil menghapus user');
					$('#msg-box').modal('show');

					loadUser();

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

	function onFilterCabang(value) {
    paramUrlSetup.branchId = value;
		loadUser();
	}

	function onSearch(keyword) {
		paramUrlSetup.keyword = keyword;
		loadUser();
	}

	function loadUser() {
		getId = null; modalState = '';
    $.ajax({
			url     : $('.baseUrl').val() + '/api/user',
			headers : { 'Authorization': `Bearer ${token}` },
			type    : 'GET',
			data	  : { orderby: paramUrlSetup.orderby, column: paramUrlSetup.column, keyword: paramUrlSetup.keyword, branch_id: paramUrlSetup.branchId },
			beforeSend: function() { $('#loading-screen').show(); },
			success: function(data) {
				let listUser = '';
				$('#list-user tr').remove();

        if (data.length) {
          $.each(data, function(idx, v) {
            getUser.push(v);
            listUser += `<tr>`
              + `<td>${++idx}</td>`
              + `<td>${v.staffing_number}</td>`
              + `<td>${v.username}</td>`
              + `<td>${v.fullname}</td>`
              + `<td>${v.email}</td>`
              + `<td>${v.role}</td>`
              + `<td>${v.branch_name}</td>`
              + `<td>${v.status === '1' ? 'Aktif' : 'Non Aktif'}</td>`
              + `<td>${v.created_by}</td>`
              + `<td>${v.created_at}</td>`
              + `<td>
                  <button type="button" class="btn btn-warning openFormEdit" value=${v.id}><i class="fa fa-pencil" aria-hidden="true"></i></button>
                  <button type="button" class="btn btn-danger openFormDelete" value=${v.id}><i class="fa fa-trash-o" aria-hidden="true"></i></button>
                </td>`
              + `</tr>`;
          });
        } else {
          listUser += `<tr class="text-center"><td colspan="11">Tidak ada data.</td></tr>`;
        }
				$('#list-user').append(listUser);

				$('.openFormEdit').click(function() {
					const getObj = data.find(x => x.id == $(this).val());
					modalState = 'edit';

					$('.modal-title').text('Edit User');
          refreshForm(); formConfigure();
					$('.detail-user').show(); $('.stateModalAdd').hide();
					getId = getObj.id;

					$('#noPegawaiTxt').text(getObj.staffing_number);
					$('#usernameTxt').text(getObj.username);
					$('#emailTxt').text(getObj.email);
					$('#noPonselTxt').text(getObj.phone_number);

					$('#namaLengkap').val(getObj.fullname);
					$('#selectRole').val(getObj.role); $('#selectRole').trigger('change');
					$('#selectCabang').val(getObj.branch_id); $('#selectCabang').trigger('change');
					$('#selectStatus').val(getObj.status); $('#selectStatus').trigger('change');
				});

				$('.openFormDelete').click(function() {
					getId = $(this).val();
					modalState = 'delete';

					$('#modal-confirmation .modal-title').text('Peringatan');
					$('#modal-confirmation .box-body').text('Anda yakin ingin menghapus User ini?');
					$('#modal-confirmation').modal('show');
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
				optCabang1 += `<option value=''>Cabang</option>`;
				optCabang2 += `<option value=''>Pilih Cabang</option>`;
				if (data.length) {
					for (let i = 0 ; i < data.length ; i++) {
						listCabang.push(data[i]);
						optCabang1 += `<option value=${data[i].id}>${data[i].branch_name}</option>`;
						optCabang2 += `<option value=${data[i].id}>${data[i].branch_name}</option>`;
					}
				}
        $('#selectCabang').append(optCabang2); $('#filterCabang').append(optCabang1);
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
		if (modalState == 'edit') {
			if (!$('#namaLengkap').val()) {
				$('#namaLengkapErr1').text('Nama Lengkap harus di isi'); isValidNamaLengkap = false;
			} else {
				$('#namaLengkapErr1').text(''); isValidNamaLengkap = true;
			}

			if ($('#selectRole').val() == '') {
				$('#roleErr1').text('Role harus di isi'); isValidSelectRole = false;
			} else {
				$('#roleErr1').text(''); isValidSelectRole = true;
			}

			if ($('#selectCabang').val() == '') {
				$('#cabangErr1').text('Cabang harus di isi'); isValidSelectCabang = false;
			} else {
				$('#cabangErr1').text(''); isValidSelectCabang = true;
			}

			if ($('#selectStatus').val() == '') {
				$('#statusErr1').text('Status harus di isi'); isValidSelectStatus = false;
			} else {
				$('#statusErr1').text(''); isValidSelectStatus = true;
			}

			if (!isValidNamaLengkap || !isValidSelectRole || !isValidSelectCabang
				|| !isValidSelectStatus || isBeErr) {
				$('#btnSubmitUser').attr('disabled', true);
			} else {
				$('#btnSubmitUser').attr('disabled', false);
			}

		} else {

			if (!$('#username').val()) {
				$('#usernameErr1').text('Username harus di isi'); isValidUsername = false;
			} else {
				$('#usernameErr1').text(''); isValidUsername = true;
			}

			if (!$('#namaLengkap').val()) {
				$('#namaLengkapErr1').text('Nama Lengkap harus di isi'); isValidNamaLengkap = false;
			} else {
				$('#namaLengkapErr1').text(''); isValidNamaLengkap = true;
			}

			if (!$('#email').val()) {
				$('#emailErr1').text('Email harus di isi'); isValidEmail = false;
			} else {
				$('#emailErr1').text(''); isValidEmail = true;
			}

			if (!$('#password').val()) {
				$('#passwordErr1').text('Password harus di isi'); isValidPassword = false;
			} else if ($('#password').val() != $('#confPassword').val()) {
				$('#passwordErr1').text('Password dan Konfirmasi Password harus sama'); isValidPassword = false;
			} else {
				$('#passwordErr1').text(''); isValidPassword = true;
			}

			if (!$('#confPassword').val()) {
				$('#confPasswordErr1').text('Konfirmasi Password harus di isi'); isValidConfPassword = false;
			} else {
				$('#confPasswordErr1').text(''); isValidConfPassword = true;
			}

			if (!$('#nomPonsel').val()) {
				$('#nomPonselErr1').text('Nomor Ponsel harus di isi'); isValidNomPonsel = false;
			} else {
				$('#nomPonselErr1').text(''); isValidNomPonsel = true;
			}

			if ($('#selectRole').val() == '') {
				$('#roleErr1').text('Role harus di isi'); isValidSelectRole = false;
			} else {
				$('#roleErr1').text(''); isValidSelectRole = true;
			}

			if ($('#selectCabang').val() == '') {
				$('#cabangErr1').text('Cabang harus di isi'); isValidSelectCabang = false;
			} else {
				$('#cabangErr1').text(''); isValidSelectCabang = true;
			}

			if ($('#selectStatus').val() == '') {
				$('#statusErr1').text('Status harus di isi'); isValidSelectStatus = false;
			} else {
				$('#statusErr1').text(''); isValidSelectStatus = true;
			}

			if (!isValidUsername || !isValidNamaLengkap || !isValidEmail  || !isValidPassword
				|| !isValidConfPassword || !isValidNomPonsel || !isValidSelectRole || !isValidSelectCabang
				|| !isValidSelectStatus || isBeErr) {
				$('#btnSubmitUser').attr('disabled', true);
			} else {
				$('#btnSubmitUser').attr('disabled', false);
			}
		}

		$('#beErr').empty(); isBeErr = false

	}

	function refreshForm() {
		$('#username').val(null); isValidUsername = true;
		$('#namaLengkap').val(null); isValidNamaLengkap = true;
		$('#email').val(null); isValidEmail = true;
		$('#password').val(null); isValidPassword = true;
		$('#confPassword').val(null); isValidConfPassword = true;
		$('#nomPonsel').val(null); isValidNomPonsel = true;
		$('#selectRole').val(''); isValidSelectRole = true;
		$('#selectCabang').val(''); isValidSelectCabang = true;
		$('#selectStatus').val(''); isValidSelectStatus = true;

		$('#beErr').empty(); isBeErr = false;

		$('#noPegawaiTxt').text(''); $('#usernameTxt').text('');
		$('#emailTxt').text(''); $('#noPonselTxt').text('');
	}

	function formConfigure() {
		$('#selectRole').select2();
		$('#selectStatus').select2();
		$('#selectCabang').select2();

		$('#modal-user').modal('show');
		$('#btnSubmitUser').attr('disabled', true);
	}

});
