$(document).ready(function () {
  let getId = null;
  let modalState = "";
  let optCabang = "";
  let optKategoriBarang = "";
  let optNamaBarang = "";
  let listNamaBarang = [];
  let getCurrentPage = 1;

  let isValidSelectedCabangOnBarang = false;
  let isValidSelectedKategoriBarang = false;
  let isValidSelectedNamaBarang = false;

  let isValidHargaJualOnBarang = false;
  let isValidHargaModalOnBarang = false;
  let isValidFeeDokterOnBarang = false;
  let isValidFeePetshopOnBarang = false;
  let customErr1 = false;
  let isBeErr = false;
  let paramUrlSetup = {
    orderby: "",
    column: "",
    keyword: "",
    branchId: "",
  };

  if (role.toLowerCase() != "admin") {
    $(".columnAction").hide();
    $("#filterCabang").hide();

    if (role.toLowerCase() == "dokter" || role.toLowerCase() == "admin_gudang" || role.toLowerCase() == "admin gudang") {
      $(".section-left-box-title").append(
        `<button type="button" class="btn btn-success btn-download-excel" title="Download Excel">
          <i class="fa fa-file-excel-o" aria-hidden="true"></i>&nbsp;&nbsp;Download Excel
        </button>`
      );
    }
  } else {
    $("#selectedCabangOnBarang").append(
      `<option value=''>Pilih Cabang</option>`
    );
    $("#selectedKategoriBarang").append(
      `<option value=''>Pilih Kategori Barang</option>`
    );
    $("#selectedNamaBarang").append(
      `<option value=''>Pilih Nama Barang</option>`
    );

    $(".section-left-box-title").append(`
      <button class="btn btn-info openFormAdd m-r-10px">Tambah</button>
      <button class="btn btn-info openFormUpload  m-r-10px">Upload Sekaligus</button>
      <button type="button" class="btn btn-success btn-download-excel" title="Download Excel">
        <i class="fa fa-file-excel-o" aria-hidden="true"></i>&nbsp;&nbsp;Download Excel
      </button>`);
    $(".section-right-box-title").append(
      `<select id="filterCabang" style="width: 50%"></select>`
    );

    $("#filterCabang").select2({ placeholder: "Cabang", allowClear: true });
    $("#filterCabang").append(`<option value=''>Cabang</option>`);
    loadCabang();
  }

  loadHargaBarang();

  $(".input-search-section .fa").click(function () {
    onSearch($(".input-search-section input").val());
  });

  $(".input-search-section input").keypress(function (e) {
    if (e.which == 13) {
      onSearch($(this).val());
    }
  });

  $(".onOrdering").click(function () {
    const column = $(this).attr("data");
    const orderBy = $(this).attr("orderby");
    $('.onOrdering[data="' + column + '"]')
      .children()
      .remove();

    if (orderBy == "none" || orderBy == "asc") {
      $(this).attr("orderby", "desc");
      $(this).append('<span class="fa fa-sort-desc"></span>');
    } else if (orderBy == "desc") {
      $(this).attr("orderby", "asc");
      $(this).append('<span class="fa fa-sort-asc"></span>');
    }

    paramUrlSetup.orderby = $(this).attr("orderby");
    paramUrlSetup.column = column;

    loadHargaBarang();
  });

  $(".openFormAdd").click(function () {
    modalState = "add";
    $(".modal-title").text("Tambah Pembagian Harga Barang");
    $("#selectedKategoriBarang").attr("disabled", true);
    $("#selectedNamaBarang").attr("disabled", true);
    $("#jumlahBarangTxt").text("-");
    $("#satuanBarangTxt").text("-");

    refreshForm();
    formConfigure();
  });

  $(".openFormUpload").click(function () {
    $("#modal-upload-harga-barang .modal-title").text(
      "Upload Harga Barang Sekaligus"
    );
    $("#modal-upload-harga-barang").modal("show");
    $(".validate-error").html("");
  });

  $(".btn-download-excel").click(function () {
    $.ajax({
      url: $(".baseUrl").val() + "/api/pembagian-harga-barang/generate-excel",
      headers: { Authorization: `Bearer ${token}` },
      type: "GET",
      data: {
        orderby: paramUrlSetup.orderby,
        column: paramUrlSetup.column,
        keyword: paramUrlSetup.keyword,
        branch_id: paramUrlSetup.branchId,
      },
      xhrFields: { responseType: "blob" },
      beforeSend: function () {
        $("#loading-screen").show();
      },
      success: function (data, status, xhr) {
        let disposition = xhr.getResponseHeader("content-disposition");
        let matches = /"([^"]*)"/.exec(disposition);
        let filename = matches != null && matches[1] ? matches[1] : "file.xlsx";
        let blob = new Blob([data], {
          type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });
        let downloadUrl = URL.createObjectURL(blob);
        let a = document.createElement("a");

        a.href = downloadUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
      },
      complete: function () {
        $("#loading-screen").hide();
      },
      error: function (err) {
        if (err.status == 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  });

  $(".btn-download-template").click(function () {
    $.ajax({
      url:
        $(".baseUrl").val() + "/api/pembagian-harga-barang/download-template",
      headers: { Authorization: `Bearer ${token}` },
      type: "GET",
      xhrFields: { responseType: "blob" },
      beforeSend: function () {
        $("#loading-screen").show();
      },
      success: function (data, status, xhr) {
        let disposition = xhr.getResponseHeader("content-disposition");
        let matches = /"([^"]*)"/.exec(disposition);
        let filename = matches != null && matches[1] ? matches[1] : "file.xlsx";
        let blob = new Blob([data], {
          type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });
        let downloadUrl = URL.createObjectURL(blob);
        let a = document.createElement("a");

        a.href = downloadUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
      },
      complete: function () {
        $("#loading-screen").hide();
      },
      error: function (err) {
        if (err.status == 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  });

  $("#fileupload")
    .fileupload({
      url: $(".baseUrl").val() + "/api/pembagian-harga-barang/upload",
      headers: { Authorization: `Bearer ${token}` },
      dropZone: "#dropZone",
      dataType: "json",
      autoUpload: false,
    })
    .on("fileuploadadd", function (e, data) {
      let fileTypeAllowed = /.\.(xlsx|xls)$/i;
      let fileName = data.originalFiles[0]["name"];
      let fileSize = data.originalFiles[0]["size"];

      if (!fileTypeAllowed.test(fileName)) {
        $(".validate-error").html("File harus berformat .xlsx atau .xls");
      } else {
        $(".validate-error").html("");
        data.submit();
      }
    })
    .on("fileuploaddone", function (e, data) {
      $("#modal-confirmation").hide();

      $("#msg-box .modal-body").text("Berhasil Upload Barang");
      $("#msg-box").modal("show");
      setTimeout(() => {
        $("#modal-upload-harga-barang").modal("toggle");
        loadHargaBarang();
      }, 1000);
    })
    .on("fileuploadfail", function (e, data) {
      const getResponsError =
        data._response.jqXHR.responseJSON.errors.hasOwnProperty("file")
          ? data._response.jqXHR.responseJSON.errors.file
          : data._response.jqXHR.responseJSON.errors;

      let errText = "";
      $.each(getResponsError, function (idx, v) {
        errText += v + (idx !== getResponsError.length - 1 ? "<br/>" : "");
      });
      $(".validate-error").append(errText);
    })
    .on("fileuploadprogressall", function (e, data) {});

  $("#btnSubmitHargaBarang").click(function () {
    if (modalState == "add") {
      const fd = new FormData();
      fd.append("ListOfItemsId", $("#selectedNamaBarang").val());
      fd.append("HargaJual", $("#hargaJualOnBarang").val().replaceAll(".", ""));
      fd.append(
        "HargaModal",
        $("#hargaModalOnBarang").val().replaceAll(".", "")
      );
      fd.append("FeeDokter", $("#feeDokterOnBarang").val().replaceAll(".", ""));
      fd.append(
        "FeePetShop",
        $("#feePetshopOnBarang").val().replaceAll(".", "")
      );

      $.ajax({
        url: $(".baseUrl").val() + "/api/pembagian-harga-barang",
        type: "POST",
        dataType: "JSON",
        headers: { Authorization: `Bearer ${token}` },
        data: fd,
        contentType: false,
        cache: false,
        processData: false,
        beforeSend: function () {
          $("#loading-screen").show();
        },
        success: function (resp) {
          $("#msg-box .modal-body").text(
            "Berhasil Menambah Pembagian Harga Barang"
          );
          $("#msg-box").modal("show");

          setTimeout(() => {
            $("#modal-harga-barang").modal("toggle");
            refreshForm();
            loadHargaBarang();
          }, 1000);
        },
        complete: function () {
          $("#loading-screen").hide();
        },
        error: function (err) {
          if (err.status === 422) {
            let errText = "";
            $("#beErr").empty();
            $("#btnSubmitHargaBarang").attr("disabled", true);
            $.each(err.responseJSON.errors, function (idx, v) {
              errText +=
                v + (idx !== err.responseJSON.errors.length - 1 ? "<br/>" : "");
            });
            $("#beErr").append(errText);
            isBeErr = true;
          } else if (err.status == 401) {
            localStorage.removeItem("vet-clinic");
            location.href = $(".baseUrl").val() + "/masuk";
          }
        },
      });
    } else {
      // edit
      $("#modal-confirmation .modal-title").text("Peringatan");
      $("#modal-confirmation .box-body").text(
        "Anda yakin untuk mengubah data ini ?"
      );
      $("#modal-confirmation").modal("show");
    }
  });

  $("#submitConfirm").click(function () {
    if (modalState == "edit") {
      // process edit

      const datas = {
        id: getId,
        ListOfItemsId: $("#selectedNamaBarang").val(),
        HargaJual: $("#hargaJualOnBarang").val().replaceAll(".", ""),
        HargaModal: $("#hargaModalOnBarang").val().replaceAll(".", ""),
        FeeDokter: $("#feeDokterOnBarang").val().replaceAll(".", ""),
        FeePetShop: $("#feePetshopOnBarang").val().replaceAll(".", ""),
      };

      $.ajax({
        url: $(".baseUrl").val() + "/api/pembagian-harga-barang",
        type: "PUT",
        dataType: "JSON",
        headers: { Authorization: `Bearer ${token}` },
        data: datas,
        beforeSend: function () {
          $("#loading-screen").show();
        },
        success: function (data) {
          $("#modal-confirmation .modal-title").text("Peringatan");
          $("#modal-confirmation").modal("toggle");

          $("#msg-box .modal-body").text("Berhasil Mengubah harga barang");
          $("#msg-box").modal("show");

          setTimeout(() => {
            $("#modal-harga-barang").modal("toggle");
            refreshForm();
            loadHargaBarang();
          }, 1000);
        },
        complete: function () {
          $("#loading-screen").hide();
        },
        error: function (err) {
          if (err.status == 401) {
            localStorage.removeItem("vet-clinic");
            location.href = $(".baseUrl").val() + "/masuk";
          }
        },
      });
    } else {
      // process delete
      $.ajax({
        url: $(".baseUrl").val() + "/api/pembagian-harga-barang",
        headers: { Authorization: `Bearer ${token}` },
        type: "DELETE",
        data: { id: getId },
        beforeSend: function () {
          $("#loading-screen").show();
        },
        success: function (data) {
          $("#modal-confirmation .modal-title").text("Peringatan");
          $("#modal-confirmation").modal("toggle");

          $("#msg-box .modal-body").text("Berhasil menghapus data");
          $("#msg-box").modal("show");

          loadHargaBarang();
        },
        complete: function () {
          $("#loading-screen").hide();
        },
        error: function (err) {
          if (err.status == 401) {
            localStorage.removeItem("vet-clinic");
            location.href = $(".baseUrl").val() + "/masuk";
          }
        },
      });
    }
  });

  $("#selectedCabangOnBarang").on("select2:select", function (e) {
    if ($(this).val()) {
      loadKategoriBarang($(this).val());
    } else {
      optKategoriBarang = `<option value=''>Pilih Kategori Barang</option>`;
      $("#selectedKategoriBarang option").remove();
      $("#selectedKategoriBarang").val(null);
      $("#selectedKategoriBarang").append(optKategoriBarang);
      $("#selectedKategoriBarang").attr("disabled", true);
    }

    optNamaBarang = `<option value=''>Pilih Nama Barang</option>`;
    $("#selectedNamaBarang").attr("disabled", true);
    $("#selectedNamaBarang option").remove();
    $("#selectedNamaBarang").val(null);
    $("#selectedNamaBarang").append(optNamaBarang);
    $("#jumlahBarangTxt").text("-");
    $("#satuanBarangTxt").text("-");

    validationHargaJual();
    validationForm();
  });

  $("#selectedKategoriBarang ").on("select2:select", function (e) {
    if ($(this).val()) {
      loadNamaBarang($("#selectedCabangOnBarang").val(), $(this).val());
    } else {
      optJenisPelayanan = `<option value=''>Pilih Nama Barang</option>`;
      $("#selectedNamaBarang").attr("disabled", true);
      $("#selectedNamaBarang option").remove();
      $("#selectedNamaBarang").val(null);
      $("#selectedNamaBarang").append(optJenisPelayanan);

      $("#jumlahBarangTxt").text("-");
      $("#satuanBarangTxt").text("-");
    }

    validationHargaJual();
    validationForm();
  });

  $("#selectedNamaBarang ").on("select2:select", function (e) {
    const getObj = listNamaBarang.find((x) => x.id == $(this).val());
    $("#jumlahBarangTxt").text(getObj ? getObj.total_item : "-");
    $("#satuanBarangTxt").text(getObj ? getObj.unit_name : "-");

    validationHargaJual();
    validationForm();
  });

  $("#hargaJualOnBarang").mask("#.##0", { reverse: true, maxlength: false });
  $("#hargaModalOnBarang").mask("#.##0", { reverse: true, maxlength: false });
  $("#feeDokterOnBarang").mask("#.##0", { reverse: true, maxlength: false });
  $("#feePetshopOnBarang").mask("#.##0", { reverse: true, maxlength: false });

  $("#hargaJualOnBarang").keyup(function () {
    validationHargaJual();
    validationForm();
  });
  $("#hargaModalOnBarang").keyup(function () {
    validationHargaJual();
    validationForm();
  });
  $("#feeDokterOnBarang").keyup(function () {
    validationHargaJual();
    validationForm();
  });
  $("#feePetshopOnBarang").keyup(function () {
    validationHargaJual();
    validationForm();
  });

  $("#filterCabang").on("select2:select", function () {
    onFilterCabang($(this).val());
  });
  $("#filterCabang").on("select2:unselect", function () {
    onFilterCabang($(this).val());
  });

  function onFilterCabang(value) {
    paramUrlSetup.branchId = value;
    loadHargaBarang();
  }

  function onSearch(keyword) {
    paramUrlSetup.keyword = keyword;
    loadHargaBarang();
  }

  function loadHargaBarang() {
    getId = null;
    modalState = "";
    $.ajax({
      url: $(".baseUrl").val() + "/api/pembagian-harga-barang",
      headers: { Authorization: `Bearer ${token}` },
      type: "GET",
      data: {
        orderby: paramUrlSetup.orderby,
        column: paramUrlSetup.column,
        keyword: paramUrlSetup.keyword,
        branch_id: paramUrlSetup.branchId,
        page: getCurrentPage,
      },
      beforeSend: function () {
        $("#loading-screen").show();
      },
      success: function (resp) {
        const getData = resp.data;
        let listHargaBarang = "";

        $("#list-harga-barang tr").remove();

        if (getData.length) {
          $.each(getData, function (idx, v) {
            listHargaBarang +=
              `<tr>` +
              `<td>${++idx}</td>` +
              `<td>${v.item_name}</td>` +
              `<td>${v.category_name}</td>` +
              `<td>${v.total_item}</td>` +
              `<td>${v.unit_name}</td>` +
              `<td>Rp ${
                Number(v.selling_price || 0).toLocaleString('id-ID')
                // typeof v.selling_price == "number"
                //   ? v.selling_price
                //       .toString()
                //       .replace(/\B(?=(\d{3})+(?!\d))/g, ".")
                //   : ""
              }</td>` +
              `<td>Rp ${
                Number(v.capital_price || 0).toLocaleString('id-ID')
                // typeof v.capital_price == "number"
                //   ? v.capital_price
                //       .toString()
                //       .replace(/\B(?=(\d{3})+(?!\d))/g, ".")
                //   : ""
              }</td>` +
              `<td>Rp ${
                Number(v.doctor_fee || 0).toLocaleString('id-ID')
                // typeof v.doctor_fee == "number"
                //   ? v.doctor_fee
                //       .toString()
                //       .replace(/\B(?=(\d{3})+(?!\d))/g, ".")
                //   : ""
              }</td>` +
              `<td>Rp ${
                Number(v.petshop_fee || 0).toLocaleString('id-ID')
                // typeof v.petshop_fee == "number"
                //   ? v.petshop_fee
                //       .toString()
                //       .replace(/\B(?=(\d{3})+(?!\d))/g, ".")
                //   : ""
              }</td>` +
              `<td>${v.branch_name}</td>` +
              `<td>${v.created_by}</td>` +
              `<td>${v.created_at}</td>` +
              (role.toLowerCase() != "admin"
                ? ``
                : `<td>
                  <button type="button" class="btn btn-warning openFormEdit" value=${v.id}><i class="fa fa-pencil" aria-hidden="true"></i></button>
                  <button type="button" class="btn btn-danger openFormDelete" value=${v.id}><i class="fa fa-trash-o" aria-hidden="true"></i></button>
                </td>`) +
              `</tr>`;
          });
        } else {
          listHargaBarang += `<tr class="text-center"><td colspan="13">Tidak ada data.</td></tr>`;
        }

        $("#list-harga-barang").append(listHargaBarang);

        generatePagination(getCurrentPage, resp.total_paging);

        $(".openFormEdit").click(function () {
          const getObj = resp.data.find((x) => x.id == $(this).val());
          modalState = "edit";

          $(".modal-title").text("Edit Pembagian Harga Barang");
          refreshForm();

          loadKategoriBarang(getObj.branch_id, getObj.item_categories_id);
          loadNamaBarang(
            getObj.branch_id,
            getObj.item_categories_id,
            getObj.item_name_id
          );
          formConfigure();

          getId = getObj.id;
          console.log(
            getObj.selling_price
              .toString()
              .replace(/\B(?=(\d{3})+(?!\d))/g, ".")
          );
          $("#hargaJualOnBarang").val(
            getObj.selling_price
              .toString()
              .replace(/\B(?=(\d{3})+(?!\d))/g, ".")
          );
          $("#hargaModalOnBarang").val(
            getObj.capital_price
              .toString()
              .replace(/\B(?=(\d{3})+(?!\d))/g, ".")
          );
          $("#feeDokterOnBarang").val(
            getObj.doctor_fee.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".")
          );
          $("#feePetshopOnBarang").val(
            getObj.petshop_fee.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".")
          );
          $("#selectedCabangOnBarang").val(getObj.branch_id);
          $("#selectedCabangOnBarang").trigger("change");
          $("#jumlahBarangTxt").text(getObj.total_item);
          $("#satuanBarangTxt").text(getObj.unit_name);
        });

        $(".openFormDelete").click(function () {
          getId = $(this).val();
          modalState = "delete";

          $("#modal-confirmation .modal-title").text("Peringatan");
          $("#modal-confirmation .box-body").text(
            "Anda yakin ingin menghapus data ini?"
          );
          $("#modal-confirmation").modal("show");
        });

        $(".pagination > li > a").click(function () {
          const getClassName = this.className;
          const getNumber = parseFloat($(this).text());

          if (
            (getCurrentPage === 1 && getClassName.includes("arrow-left")) ||
            (getCurrentPage === resp.total_paging &&
              getClassName.includes("arrow-right"))
          ) {
            return;
          }

          if (getClassName.includes("arrow-left")) {
            getCurrentPage = getCurrentPage - 1;
          } else if (getClassName.includes("arrow-right")) {
            getCurrentPage = getCurrentPage + 1;
          } else {
            getCurrentPage = getNumber;
          }

          loadHargaBarang();
        });
      },
      complete: function () {
        $("#loading-screen").hide();
      },
      error: function (err) {
        if (err.status == 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  }

  function loadCabang() {
    $.ajax({
      url: $(".baseUrl").val() + "/api/cabang",
      headers: { Authorization: `Bearer ${token}` },
      type: "GET",
      beforeSend: function () {
        $("#loading-screen").show();
      },
      success: function (data) {
        if (data.length) {
          for (let i = 0; i < data.length; i++) {
            optCabang += `<option value=${data[i].id}>${data[i].branch_name}</option>`;
          }
        }
        $("#selectedCabangOnBarang").append(optCabang);
        $("#filterCabang").append(optCabang);
      },
      complete: function () {
        $("#loading-screen").hide();
      },
      error: function (err) {
        if (err.status == 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  }

  function loadKategoriBarang(idCabang, itemCategoriesId = null) {
    $.ajax({
      url: $(".baseUrl").val() + "/api/pembagian-harga-barang/kategori-barang",
      headers: { Authorization: `Bearer ${token}` },
      type: "GET",
      data: { branch_id: idCabang },
      beforeSend: function () {
        $("#loading-screen").show();
      },
      success: function (data) {
        optKategoriBarang = `<option value=''>Pilih Kategori Barang</option>`;
        $("#selectedKategoriBarang option").remove();

        if (data.length) {
          $("#selectedKategoriBarang").attr("disabled", false);
          for (let i = 0; i < data.length; i++) {
            optKategoriBarang += `<option value=${data[i].category_item_id}>${data[i].category_name}</option>`;
          }
        } else {
          $("#selectedKategoriBarang").attr("disabled", true);
          optKategoriBarang = `<option value='' selected="selected">Data tidak ada</option>`;
        }
        $("#selectedKategoriBarang").append(optKategoriBarang);

        if (modalState == "edit") {
          $("#selectedKategoriBarang").val(itemCategoriesId);
          $("#selectedKategoriBarang").trigger("change");
        }
      },
      complete: function () {
        $("#loading-screen").hide();
      },
      error: function (err) {
        if (err.status == 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  }

  function loadNamaBarang(idCabang, categoryItemId, itemNameId = null) {
    $.ajax({
      url: $(".baseUrl").val() + "/api/pembagian-harga-barang/nama-barang",
      headers: { Authorization: `Bearer ${token}` },
      type: "GET",
      data: { branch_id: idCabang, category_item_id: categoryItemId },
      beforeSend: function () {
        $("#loading-screen").show();
      },
      success: function (data) {
        optNamaBarang = `<option value=''>Pilih Nama Barang</option>`;
        $("#selectedNamaBarang option").remove();
        listNamaBarang = [];

        if (data.length) {
          $("#selectedNamaBarang").attr("disabled", false);
          for (let i = 0; i < data.length; i++) {
            optNamaBarang += `<option value=${data[i].id}>${data[i].item_name}</option>`;
            listNamaBarang.push(data[i]);
          }
        } else {
          $("#selectedNamaBarang").attr("disabled", true);
          optNamaBarang = `<option value='' selected="selected">Data tidak ada</option>`;
        }
        $("#selectedNamaBarang").append(optNamaBarang);

        if (modalState == "edit") {
          $("#selectedNamaBarang").val(itemNameId);
          $("#selectedNamaBarang").trigger("change");
        }
      },
      complete: function () {
        $("#loading-screen").hide();
      },
      error: function (err) {
        if (err.status == 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  }

  function validationForm() {
    if (!$("#selectedCabangOnBarang").val()) {
      $("#cabangOnBarangErr1").text("Cabang barang harus di isi");
      isValidSelectedCabangOnBarang = false;
    } else {
      $("#cabangOnBarangErr1").text("");
      isValidSelectedCabangOnBarang = true;
    }

    if (!$("#selectedKategoriBarang").val()) {
      $("#kategoriBarangErr1").text("Kategori barang harus di isi");
      isValidSelectedKategoriBarang = false;
    } else {
      $("#kategoriBarangErr1").text("");
      isValidSelectedKategoriBarang = true;
    }

    if (!$("#selectedNamaBarang").val()) {
      $("#namaBarangErr1").text("Nama barang harus di isi");
      isValidSelectedNamaBarang = false;
    } else {
      $("#namaBarangErr1").text("");
      isValidSelectedNamaBarang = true;
    }

    if ($("#hargaJualOnBarang").val() == "") {
      $("#hargaJualOnBarangErr1").text("Harga jual harus di isi");
      isValidHargaJualOnBarang = false;
    } else {
      $("#hargaJualOnBarangErr1").text("");
      isValidHargaJualOnBarang = true;
    }

    if ($("#hargaModalOnBarang").val() == "") {
      $("#hargaModalOnBarangErr1").text("Harga modal harus di isi");
      isValidHargaModalOnBarang = false;
    } else {
      $("#hargaModalOnBarangErr1").text("");
      isValidHargaModalOnBarang = true;
    }

    if ($("#feeDokterOnBarang").val() == "") {
      $("#feeDokterOnBarangErr1").text("Fee dokter harus di isi");
      isValidFeeDokterOnBarang = false;
    } else {
      $("#feeDokterOnBarangErr1").text("");
      isValidFeeDokterOnBarang = true;
    }

    if ($("#feePetshopOnBarang").val() == "") {
      $("#feePetshopOnBarangErr1").text("Fee petshop harus di isi");
      isValidFeePetshopOnBarang = false;
    } else {
      $("#feePetshopOnBarangErr1").text("");
      isValidFeePetshopOnBarang = true;
    }

    $("#beErr").empty();
    isBeErr = false;
    validationBtnSubmitHargaBarang();
  }

  function refreshForm() {
    $("#selectedCabangOnBarang").val(null);
    $("#selectedKategoriBarang").val(null);
    $("#selectedNamaBarang").val(null);

    $("#hargaJualOnBarang").val(null);
    $("#hargaModalOnBarang").val(null);
    $("#feeDokterOnBarang").val(null);
    $("#feePetshopOnBarang").val(null);

    $("#customErr1").empty();
    customErr1 = false;
    $("#beErr").empty();
    isBeErr = false;

    $("#cabangOnBarangErr1").text("");
    isValidSelectedCabangOnBarang = true;
    $("#kategoriBarangErr1").text("");
    isValidSelectedKategoriBarang = true;
    $("#namaBarangErr1").text("");
    isValidSelectedNamaBarang = true;

    $("#hargaJualOnBarangErr1").text("");
    isValidHargaJualOnBarang = true;
    $("#hargaModalOnBarangErr1").text("");
    isValidHargaModalOnBarang = true;
    $("#feeDokterOnBarangErr1").text("");
    isValidFeeDokterOnBarang = true;
    $("#feePetshopOnBarangErr1").text("");
    isValidFeePetshopOnBarang = true;
  }

  function formConfigure() {
    $("#selectedCabangOnBarang").select2();
    $("#selectedKategoriBarang").select2();
    $("#selectedNamaBarang").select2();

    $("#modal-harga-barang").modal("show");
    $("#btnSubmitHargaBarang").attr("disabled", true);
  }

  function validationHargaJual() {
    let hargaJual = $("#hargaJualOnBarang").val();
    let hargaModal = $("#hargaModalOnBarang").val();
    let feeDokter = $("#feeDokterOnBarang").val();
    let feePetshop = $("#feePetshopOnBarang").val();

    hargaJual = hargaJual.replaceAll(".", "");
    hargaModal = hargaModal.replaceAll(".", "");
    feeDokter = feeDokter.replaceAll(".", "");
    feePetshop = feePetshop.replaceAll(".", "");

    const totalHargaJual =
      parseInt(hargaModal) + parseInt(feeDokter) + parseInt(feePetshop);

    if (parseInt(hargaJual) !== totalHargaJual) {
      $("#customErr1").text(
        "Total harga modal, fee dokter, dan fee petshop tidak sama dengan harga jual"
      );
      customErr1 = false;
    } else {
      $("#customErr1").text("");
      customErr1 = true;
    }
  }

  function validationBtnSubmitHargaBarang() {
    if (
      !isValidSelectedCabangOnBarang ||
      !isValidSelectedKategoriBarang ||
      !isValidSelectedNamaBarang ||
      !isValidHargaJualOnBarang ||
      !isValidHargaModalOnBarang ||
      !isValidFeeDokterOnBarang ||
      !isValidFeePetshopOnBarang ||
      !customErr1 ||
      isBeErr
    ) {
      $("#btnSubmitHargaBarang").attr("disabled", true);
    } else {
      $("#btnSubmitHargaBarang").attr("disabled", false);
    }
  }
});
